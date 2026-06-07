<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/encryption.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'student') {
    header('Location: ../public/login.php');
    exit;
}

$studentId = (int) ($_SESSION['user_id'] ?? 0);
$projectId = (int) ($_GET['project_id'] ?? 0);
if ($projectId <= 0) {
    header('Location: student_dashboard.php');
    exit;
}

function student_project_decrypt(?string $value): string
{
    if ($value === null || $value === '') {
        return '';
    }

    try {
        return decryptData($value);
    } catch (Throwable $error) {
        return '';
    }
}

$title = 'Untitled Project';
$description = '';
$category = '';
$supervisor = '';
$members = [];
$files = [];
$processedFiles = [];
$generatedProposal = null;
$feedbackLog = [];
$submissionHistory = [];
$currentStatus = 'pending';
$latestSubmittedAt = null;
$projectCode = 'UTM-FYP-' . str_pad((string) $projectId, 4, '0', STR_PAD_LEFT);
$statusLabels = ['pending' => 'Pending Review', 'approved' => 'Approved', 'rejected' => 'Rejected'];
$statusClasses = ['pending' => 'status-pending', 'approved' => 'status-approved', 'rejected' => 'status-rejected'];

try {
    $stmt = $db->prepare(
        "SELECT p.project_id, p.title_encrypted, p.description_encrypted, p.category_encrypted, p.study_year, p.progress_percentage, p.created_at,
                p.lecturer_id, u.name_encrypted AS lecturer_name,
                latest.status AS latest_status, latest.submitted_at AS latest_submitted_at
         FROM projects p
         LEFT JOIN users u ON p.lecturer_id = u.user_id
         LEFT JOIN submissions latest ON latest.submission_id = (
             SELECT submission_id FROM submissions
             WHERE project_id = p.project_id
             ORDER BY submitted_at DESC
             LIMIT 1
         )
         INNER JOIN project_members my_pm
             ON my_pm.project_id = p.project_id
            AND my_pm.user_id = ?
         WHERE p.project_id = ?"
    );
    $stmt->execute([$studentId, $projectId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new RuntimeException('Project not found');
    }

    $title = student_project_decrypt($row['title_encrypted'] ?? '') ?: 'Untitled Project';
    $description = student_project_decrypt($row['description_encrypted'] ?? '');
    $category = student_project_decrypt($row['category_encrypted'] ?? '');
    $supervisor = student_project_decrypt($row['lecturer_name'] ?? '');
    $currentStatus = $row['latest_status'] ?: 'pending';
    $latestSubmittedAt = $row['latest_submitted_at'] ?? null;
    $progressPercentage = max(0, min(100, (int) ($row['progress_percentage'] ?? 0)));

    $memberStmt = $db->prepare(
        "SELECT u.user_id, u.name_encrypted, u.role AS user_role, pm.role AS project_role
         FROM project_members pm
         INNER JOIN users u ON u.user_id = pm.user_id
         WHERE pm.project_id = ?
         ORDER BY CASE pm.role WHEN 'lecturer' THEN 0 ELSE 1 END, u.user_id ASC"
    );
    $memberStmt->execute([$projectId]);
    foreach ($memberStmt->fetchAll(PDO::FETCH_ASSOC) as $memberRow) {
        $memberName = student_project_decrypt($memberRow['name_encrypted'] ?? '') ?: 'Unnamed User';
        $members[] = [
            'id' => (int) $memberRow['user_id'],
            'name' => $memberName,
            'role' => (string) ($memberRow['project_role'] ?? $memberRow['user_role'] ?? ''),
        ];
    }

    $fileStmt = $db->prepare(
        "SELECT f.file_id, f.file_name_encrypted, f.file_path_encrypted, f.uploaded_at, u.name_encrypted AS uploader_name, f.uploaded_by
         FROM files f
         LEFT JOIN users u ON u.user_id = f.uploaded_by
         WHERE f.project_id = ?
         ORDER BY f.uploaded_at DESC"
    );
    $fileStmt->execute([$projectId]);
    $files = $fileStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($files as $fileRow) {
        $fileName = '';
        $filePath = '';
        try {
            $fileName = student_project_decrypt($fileRow['file_name_encrypted'] ?? '');
            $filePath = student_project_decrypt($fileRow['file_path_encrypted'] ?? '');
        } catch (Throwable $error) {
            // ignore decryption failures
        }

        $isProposalFile = false;
        $normalizedFileName = strtolower($fileName);
        if ($normalizedFileName !== '' && (str_ends_with($normalizedFileName, '.doc') || str_contains($normalizedFileName, 'proposal'))) {
            $isProposalFile = true;
        }

        $processedFile = [
            'file_id' => (int) ($fileRow['file_id'] ?? 0),
            'file_name' => $fileName,
            'file_path' => $filePath,
            'uploaded_at' => $fileRow['uploaded_at'] ?? '',
            'uploader_name' => student_project_decrypt($fileRow['uploader_name'] ?? ''),
            'uploaded_by' => (int) ($fileRow['uploaded_by'] ?? 0),
            'is_proposal' => $isProposalFile,
        ];

        $processedFiles[] = $processedFile;
        if ($isProposalFile && $generatedProposal === null) {
            $generatedProposal = $processedFile;
        }
    }

    $files = $processedFiles;

    $submissionStmt = $db->prepare(
        "SELECT submission_id, status, submitted_at
         FROM submissions
         WHERE project_id = ?
         ORDER BY submitted_at DESC
         LIMIT 1"
    );
    $submissionStmt->execute([$projectId]);
    $submissionHistory = $submissionStmt->fetchAll(PDO::FETCH_ASSOC);

    $commentStmt = $db->prepare(
        "SELECT c.comment_id, c.user_id AS author_id, c.content_encrypted, c.created_at, u.name_encrypted AS author_name, u.role AS author_role
         FROM comments c
         LEFT JOIN users u ON u.user_id = c.user_id
         WHERE c.project_id = ?
         ORDER BY c.created_at ASC, c.comment_id ASC"
    );
    $commentStmt->execute([$projectId]);

    $currentLecturerIndex = null;
    foreach ($commentStmt->fetchAll(PDO::FETCH_ASSOC) as $commentRow) {
        $comment = student_project_decrypt($commentRow['content_encrypted'] ?? '');
        if ($comment === '' || str_starts_with($comment, '__marks__')) {
            continue;
        }

        $authorName = student_project_decrypt($commentRow['author_name'] ?? '');
        $authorRole = $commentRow['author_role'] ?? '';
        if ((int) ($commentRow['author_id'] ?? 0) === $studentId) {
            $authorName = 'You';
            $authorRole = 'student';
        } elseif ($authorName === '') {
            $authorName = $authorRole === 'lecturer' ? 'Lecturer' : 'Student';
        }

        $commentEntry = [
            'comment_id' => (int) ($commentRow['comment_id'] ?? 0),
            'comment' => $comment,
            'created_at' => $commentRow['created_at'] ?? '',
            'author' => $authorName,
            'author_role' => $authorRole,
            'author_id' => (int) ($commentRow['author_id'] ?? 0),
        ];

        if ($authorRole === 'lecturer') {
            $feedbackLog[] = [
                'base' => $commentEntry,
                'replies' => [],
            ];
            $currentLecturerIndex = count($feedbackLog) - 1;
        } else {
            if ($currentLecturerIndex !== null) {
                $feedbackLog[$currentLecturerIndex]['replies'][] = $commentEntry;
            } else {
                $feedbackLog[] = [
                    'base' => $commentEntry,
                    'replies' => [],
                ];
            }
        }
    }
} catch (Throwable $e) {
    $_SESSION['student_flash'] = 'Error: ' . $e->getMessage();
    $_SESSION['student_flash_type'] = 'danger';
    header('Location: student_projects.php');
    exit;
}

$flash = $_SESSION['student_flash'] ?? '';
$flashType = $_SESSION['student_flash_type'] ?? 'success';
unset($_SESSION['student_flash'], $_SESSION['student_flash_type']);

require_once __DIR__ . '/student_header.php';
?>

<section>
        <?php if ($flash): ?>
            <div id="pageToast" class="toast-box <?= htmlspecialchars($flashType) ?>" role="alert" aria-live="polite" aria-atomic="true">
                <div class="toast-text"><?= htmlspecialchars($flash) ?></div>
                <button type="button" class="btn-close toast-close" aria-label="Close" onclick="hidePageToast()"></button>
            </div>
        <?php endif; ?>

        <?php $fileCount = count($processedFiles); ?>
        <div class="hero-panel mb-4">
            <div class="row align-items-center gx-4">
                <div class="col-lg-8">
                    <span class="badge badge-utm-gold mb-3">Project Overview</span>
                    <h1 class="display-6 fw-bold mb-2"><?= htmlspecialchars($title) ?></h1>
                    <p class="text-muted mb-3">Project Code: <span class="fw-semibold"><?= 'UTM-FYP-' . str_pad((string) $projectId, 4, '0', STR_PAD_LEFT) ?></span></p>
                    <p class="mb-4"><?= nl2br(htmlspecialchars($description ?: 'No project description provided yet.')) ?></p>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-utm" data-bs-toggle="modal" data-bs-target="#generateProposalModal">Generate Proposal</button>
                        <button class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#uploadBox">Upload File</button>
                    </div>
                </div>
                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="card border-utm rounded-4 p-4 shadow-sm">
                        <h2 class="h5 mb-3">Project Snapshot</h2>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <div class="text-muted">Files uploaded</div>
                                <div class="fs-4 fw-bold"><?= $fileCount ?></div>
                            </div>
                            <div class="text-end">
                                <div class="text-muted">Supervisor</div>
                                <div class="fw-semibold"><?= htmlspecialchars($supervisor ?: 'Not assigned') ?></div>
                            </div>
                        </div>
                        <div class="divider mb-3" style="height:1px;background:rgba(128,0,32,0.08);"></div>
                        <div>
                            <div class="text-muted">Latest update</div>
                            <div class="fw-semibold"><?= htmlspecialchars($files[0]['uploaded_at'] ?? 'No uploads yet') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="uploadBox" class="collapse mb-4">
            <div class="card border-utm rounded-4 p-4 shadow-sm">
                <h2 class="h5 mb-3">Upload New File</h2>
                <form action="student_actions.php?action=upload_file&project_id=<?= $projectId ?>" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Select PDF file</label>
                        <input type="file" name="project_file" class="form-control" accept="application/pdf" required>
                        <div class="form-text">Please submit only PDF files (max 200 MB).</div>
                    </div>
                    <button class="btn btn-utm" type="submit">Upload</button>
                </form>
            </div>
        </div>

        <div id="progressSection" class="card border-utm rounded-4 p-4 shadow-sm mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">Project Progress</h2>
                    <p class="text-muted mb-0">Report your current completion percentage so your lecturer can stay informed.</p>
                </div>
                <div class="text-end">
                    <div class="d-inline-flex align-items-baseline gap-2">
                        <span class="fs-3 fw-bold"><?= htmlspecialchars($progressPercentage) ?>%</span>
                    </div>
                    <small class="text-muted">Latest student update</small>
                </div>
            </div>
            <div class="mb-4">
                <div class="progress rounded-pill" style="height: 18px; background: rgba(128,0,32,.08);">
                    <div id="progressBar" class="progress-bar rounded-pill bg-utm-maroon" role="progressbar" style="width: <?= htmlspecialchars($progressPercentage) ?>%;" aria-valuenow="<?= htmlspecialchars($progressPercentage) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="d-flex justify-content-between small text-muted mt-2">
                    <span>0%</span><span>50%</span><span>100%</span>
                </div>
            </div>
            <form action="student_actions.php?action=update_progress&project_id=<?= $projectId ?>" method="post" class="row g-3 align-items-center">
                <div class="col-12 col-md-8">
                    <label for="progressInput" class="form-label fw-semibold">Your progress</label>
                    <input id="progressInput" type="range" name="progress_percentage" min="0" max="100" step="1" value="<?= htmlspecialchars($progressPercentage) ?>" class="form-range">
                </div>
                <div class="col-12 col-md-4">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <div class="small text-muted">Set completion</div>
                            <div id="progressValue" class="fs-4 fw-bold"><?= htmlspecialchars($progressPercentage) ?>%</div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-utm">Save progress update</button>
                </div>
            </form>
        </div>

        <div class="section-title mt-4">
            <div>
                <h2>Project Files</h2>
                <p class="text-muted">Manage all uploaded documents and proposals for this project.</p>
            </div>
            <span class="badge badge-utm-gold align-self-start">Total files: <?= $fileCount ?></span>
        </div>

        <div class="card border-utm rounded-4 p-4 shadow-sm mb-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                <div>
                    <h3 class="h6 mb-1">Generated Proposal</h3>
                    <p class="text-muted mb-0">The latest proposal document created for this project.</p>
                </div>
                <?php if ($generatedProposal): ?>
                    <span class="badge badge-utm-gold">Available</span>
                <?php else: ?>
                    <span class="badge badge-secondary">Not generated</span>
                <?php endif; ?>
            </div>
            <?php if ($generatedProposal): ?>
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <strong><?= htmlspecialchars($generatedProposal['file_name'] ?: 'Generated Proposal') ?></strong>
                        <div class="text-muted small">Uploaded: <?= htmlspecialchars($generatedProposal['uploaded_at'] ?: 'Unknown') ?></div>
                    </div>
                    <div class="d-flex gap-2">
                        <?php if ($generatedProposal['file_path']): ?>
                            <a href="<?= htmlspecialchars($generatedProposal['file_path']) ?>" class="btn btn-outline-secondary btn-sm">Download</a>
                        <?php endif; ?>
                        <?php if ((int) $generatedProposal['uploaded_by'] === (int) ($_SESSION['user_id'] ?? 0)): ?>
                            <button
                                class="btn btn-danger btn-sm"
                                type="button"
                                data-file-id="<?= (int) $generatedProposal['file_id'] ?>"
                                data-file-name="<?= htmlspecialchars($generatedProposal['file_name'] ?: 'Proposal', ENT_QUOTES) ?>"
                                data-project-id="<?= $projectId ?>"
                                onclick="openDeleteFileModal(this)">
                                Delete
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-muted">No generated proposal found yet. Use the Generate Proposal button to create one.</div>
            <?php endif; ?>
        </div>

        <?php if (empty($processedFiles)): ?>
            <div class="card border-utm rounded-4 p-4 shadow-sm">
                <p class="mb-0 text-muted">No files uploaded yet. Use the button above to add a proposal or document.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($files as $file): 
                    $fileName = $file['file_name'] ?? '';
                    $filePath = $file['file_path'] ?? '';
                ?>
                    <div class="col-12">
                        <div class="card border-utm rounded-4 p-3 shadow-sm h-100">
                            <div class="row gx-3 gy-2 align-items-center">
                                <div class="col-auto">
                                    <div class="bg-utm-maroon rounded-4 p-3 text-white d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                                        <i class="bi bi-file-earmark-text-fill fs-4"></i>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                        <h3 class="h6 mb-0 text-truncate"><?= htmlspecialchars($fileName ?: 'Untitled Document') ?></h3>
                                        <?php if (!empty($file['is_proposal'])): ?>
                                            <span class="badge badge-utm-gold py-1 px-2">Proposal</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex flex-wrap gap-3 small text-muted">
                                        <span>Uploaded: <?= htmlspecialchars($file['uploaded_at'] ?? '') ?></span>
                                        <span>By <?= htmlspecialchars($file['uploader_name'] ?: 'Student') ?></span>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                                        <?php if ($filePath): ?>
                                            <a href="<?= htmlspecialchars($filePath) ?>" class="btn btn-outline-secondary btn-sm">Download</a>
                                        <?php endif; ?>
                                        <?php if ((int) ($file['uploaded_by'] ?? 0) === (int) ($_SESSION['user_id'] ?? 0)): ?>
                                            <button
                                                class="btn btn-danger btn-sm"
                                                type="button"
                                                data-file-id="<?= (int) $file['file_id'] ?>"
                                                data-file-name="<?= htmlspecialchars($fileName ?: 'File', ENT_QUOTES) ?>"
                                                data-project-id="<?= $projectId ?>"
                                                onclick="openDeleteFileModal(this)">
                                                Delete
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="row g-4 mt-2">
            <div class="col-12 col-lg-5">
                <h4>Review Status</h4>
                <div class="comment-card border-utm rounded-3 p-3 mb-3">
                    <div class="d-flex gap-3">
                        <div class="avatar bg-utm-maroon text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width:44px;height:44px;font-weight:700;">RS</div>
                        <div class="flex-grow-1">
                            <?php if (empty($submissionHistory)): ?>
                                <div class="text-muted">No submissions yet.</div>
                            <?php else:
                                $latest = $submissionHistory[0];
                                $status = (string) ($latest['status'] ?? 'pending');
                            ?>
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <div>
                                        <div class="small text-muted">Latest Submission</div>
                                        <strong class="h6 mb-0"><?= htmlspecialchars($statusLabels[$status] ?? ucfirst($status)) ?></strong>
                                        <div class="small text-muted"><?= !empty($latest['submitted_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string)$latest['submitted_at']))) : '' ?></div>
                                    </div>
                                    <div>
                                        <span class="status-chip <?= htmlspecialchars($statusClasses[$status] ?? 'status-pending') ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
                                    </div>
                                </div>

                                <?php if (count($submissionHistory) > 1): ?>
                                    <div class="divider mb-2" style="height:1px;background:rgba(128,0,32,0.06);"></div>
                                    <div class="small text-muted mb-2">History</div>
                                    <ul class="timeline-list">
                                        <?php foreach ($submissionHistory as $submission):
                                            $s = (string) ($submission['status'] ?? 'pending');
                                        ?>
                                            <li>
                                                <span class="timeline-dot <?= htmlspecialchars($statusClasses[$s] ?? 'status-pending') ?>"></span>
                                                <strong><?= htmlspecialchars($statusLabels[$s] ?? ucfirst($s)) ?></strong>
                                                <span class="small text-muted"> <?= !empty($submission['submitted_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string)$submission['submitted_at']))) : '' ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-7" id="feedbackSection">
                <h4>Lecturer Comments</h4>
                <?php if (empty($feedbackLog)): ?>
                    <div class="card border-utm rounded-4 p-3 text-muted">No lecturer comments yet.</div>
                <?php else: ?>
                    <div class="comment-stack">
                        <?php foreach ($feedbackLog as $block):
                            $base = $block['base'];
                            $replies = $block['replies'];
                        ?>
                            <div class="comment-card border-utm rounded-3 p-3 mb-3">
                                <div class="d-flex gap-3">
                                    <div class="avatar bg-utm-maroon text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width:44px;height:44px;font-weight:700;"><?= htmlspecialchars(substr($base['author'] ?? 'L', 0, 1)) ?></div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <div class="d-flex align-items-center gap-2">
                                                <button class="btn btn-sm comment-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#comment-<?= (int) $base['comment_id'] ?>" aria-expanded="false" aria-controls="comment-<?= (int) $base['comment_id'] ?>" aria-label="Toggle comment">
                                                    <i class="bi bi-chevron-right"></i>
                                                </button>
                                                <strong><?= htmlspecialchars($base['author']) ?></strong>
                                            </div>
                                            <span class="small text-muted"><?= !empty($base['created_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string) $base['created_at']))) : '' ?></span>
                                        </div>
                                        <div class="collapse" id="comment-<?= (int) $base['comment_id'] ?>">
                                            <div class="comment-content mt-3">
                                                <div class="comment-body"><?= nl2br(htmlspecialchars($base['comment'])) ?></div>
                                                <div class="mt-3 d-flex justify-content-end comment-actions">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm comment-menu" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Comment options">
                                                            <i class="bi bi-three-dots-vertical"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <form action="student_actions.php?action=delete_comment&project_id=<?= $projectId ?>" method="post" class="m-0">
                                                                    <input type="hidden" name="comment_id" value="<?= (int) $base['comment_id'] ?>">
                                                                    <input type="hidden" name="delete_mode" value="me">
                                                                    <button type="submit" class="dropdown-item">Delete for me</button>
                                                                </form>
                                                            </li>
                                                            <?php if ((int) ($base['author_id'] ?? 0) === $studentId): ?>
                                                                <li>
                                                                    <form action="student_actions.php?action=delete_comment&project_id=<?= $projectId ?>" method="post" class="m-0">
                                                                        <input type="hidden" name="comment_id" value="<?= (int) $base['comment_id'] ?>">
                                                                        <input type="hidden" name="delete_mode" value="all">
                                                                        <button type="submit" class="dropdown-item text-danger">Delete for all</button>
                                                                    </form>
                                                                </li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </div>
                                                </div>
                                                <?php if (!empty($replies)): ?>
                                            <?php foreach ($replies as $reply): ?>
                                                <div class="comment-reply mt-3 p-3 rounded-3">
                                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                                        <span class="fw-semibold small"><?= htmlspecialchars($reply['author']) ?></span>
                                                        <span class="small text-muted"><?= !empty($reply['created_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string) $reply['created_at']))) : '' ?></span>
                                                    </div>
                                                    <div class="comment-body"><?= nl2br(htmlspecialchars($reply['comment'])) ?></div>
                                                    <div class="mt-3 d-flex justify-content-end comment-actions">
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm comment-menu" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Comment options">
                                                                <i class="bi bi-three-dots-vertical"></i>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-end">
                                                                <li>
                                                                    <form action="student_actions.php?action=delete_comment&project_id=<?= $projectId ?>" method="post" class="m-0">
                                                                        <input type="hidden" name="comment_id" value="<?= (int) $reply['comment_id'] ?>">
                                                                        <input type="hidden" name="delete_mode" value="me">
                                                                        <button type="submit" class="dropdown-item">Delete for me</button>
                                                                    </form>
                                                                </li>
                                                                <?php if ((int) ($reply['author_id'] ?? 0) === $studentId): ?>
                                                                    <li>
                                                                        <form action="student_actions.php?action=delete_comment&project_id=<?= $projectId ?>" method="post" class="m-0">
                                                                            <input type="hidden" name="comment_id" value="<?= (int) $reply['comment_id'] ?>">
                                                                            <input type="hidden" name="delete_mode" value="all">
                                                                            <button type="submit" class="dropdown-item text-danger">Delete for all</button>
                                                                        </form>
                                                                    </li>
                                                                <?php endif; ?>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <div class="reply-box mt-4 pt-3 border-top">
                                            <form action="student_actions.php?action=post_comment&project_id=<?= $projectId ?>" method="post">
                                                <label class="form-label mb-2">Reply to lecturer</label>
                                                <textarea name="comment_content" class="form-control" rows="3" placeholder="Write a polite reply to your lecturer..." required></textarea>
                                                <div class="d-flex justify-content-end mt-2">
                                                    <button class="btn btn-outline-secondary btn-sm me-2" type="reset">Clear</button>
                                                    <button class="btn btn-utm btn-sm" type="submit">Send Reply</button>
                                                </div>
                                            </form>
                                        </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            
</section>
</main>
</div>

<!-- Generate Proposal Modal -->
<div class="modal fade" id="generateProposalModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <form class="modal-content" method="post" action="student_actions.php?action=generate_proposal&project_id=<?= $projectId ?>">
      <div class="modal-header">
        <h5 class="modal-title">Generate Project Proposal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label">Proposal File Name</label>
            <input class="form-control" name="proposal_file_name" placeholder="e.g. UTM-FYP-0101-Proposal" required>
            <div class="form-text">Enter the file name to use for the downloaded proposal document (without extension).</div>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Project Title</label>
            <input class="form-control" name="proposal_title" value="<?= htmlspecialchars($title) ?>" required>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Project Type</label>
            <input class="form-control" name="proposal_type" placeholder="e.g. Website / Web-Based Application">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Client / Organization Name</label>
            <input class="form-control" name="proposal_client" placeholder="e.g. Ali Restaurant">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Programming Language</label>
            <input class="form-control" name="proposal_language" placeholder="e.g. PHP, HTML, CSS, JavaScript">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Framework</label>
            <input class="form-control" name="proposal_framework" placeholder="e.g. Laravel / Bootstrap">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Database</label>
            <input class="form-control" name="proposal_database" placeholder="e.g. MySQL">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Methodology</label>
            <input class="form-control" name="proposal_methodology" placeholder="e.g. Scrum">
          </div>
          <div class="col-12">
            <label class="form-label">Project Aim</label>
            <textarea class="form-control" name="proposal_aim" rows="2"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Objectives</label>
            <textarea class="form-control" name="proposal_objectives" rows="3"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Project Scopes</label>
            <textarea class="form-control" name="proposal_scopes" rows="3"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">The Problem</label>
            <textarea class="form-control" name="proposal_problem_description" rows="3"></textarea>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Affects</label>
            <input class="form-control" name="proposal_affects" placeholder="Who is affected">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Impact</label>
            <input class="form-control" name="proposal_impact" placeholder="What is the impact">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Successful Solution</label>
            <input class="form-control" name="proposal_successful_solution" placeholder="What success looks like">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">For</label>
            <input class="form-control" name="proposal_product_for" placeholder="For customers, admin, staff">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Who</label>
            <input class="form-control" name="proposal_product_who" placeholder="Who needs the system">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Product Name</label>
            <input class="form-control" name="proposal_product_name" placeholder="e.g. Ali Restaurant Ordering System">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">That</label>
            <textarea class="form-control" name="proposal_product_that" rows="2"></textarea>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Unlike</label>
            <textarea class="form-control" name="proposal_product_unlike" rows="2"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Our product</label>
            <textarea class="form-control" name="proposal_product_our" rows="2"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-utm">Generate Word Proposal</button>
      </div>
    </form>
  </div>
</div>

<!-- Confirm Delete File Modal -->
<div class="modal fade" id="confirmDeleteFileModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm File Deletion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Authorization is required to delete this project file.</p>
        <p class="fw-semibold" id="deleteFileName">File</p>
        <p class="text-muted small">This action cannot be undone. Please confirm before proceeding.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <form id="deleteFileConfirmForm" method="post" action="">
          <input type="hidden" name="file_id" id="confirmFileId" value="">
          <button type="submit" class="btn btn-danger">Delete File</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function openDeleteFileModal(button) {
  const fileId = button.dataset.fileId;
  const fileName = button.dataset.fileName;
  const projectId = button.dataset.projectId;
  document.getElementById('deleteFileName').textContent = fileName;
  const form = document.getElementById('deleteFileConfirmForm');
  form.action = `student_actions.php?action=delete_file&project_id=${encodeURIComponent(projectId)}`;
  document.getElementById('confirmFileId').value = fileId;
  const deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteFileModal'));
  deleteModal.show();
}

function hidePageToast() {
  const toast = document.getElementById('pageToast');
  if (toast) {
    toast.classList.remove('show');
  }
}

window.addEventListener('DOMContentLoaded', () => {
  const toast = document.getElementById('pageToast');
  if (toast) {
    requestAnimationFrame(() => toast.classList.add('show'));
    setTimeout(() => hidePageToast(), 4200);
  }

  const progressInput = document.getElementById('progressInput');
  const progressValue = document.getElementById('progressValue');
  const progressBar = document.getElementById('progressBar');

  if (progressInput && progressValue && progressBar) {
    const updateProgressDisplay = () => {
      const value = progressInput.value;
      progressValue.textContent = `${value}%`;
      progressBar.style.width = `${value}%`;
      progressBar.setAttribute('aria-valuenow', String(value));
    };

    progressInput.addEventListener('input', updateProgressDisplay);
    updateProgressDisplay();
  }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
