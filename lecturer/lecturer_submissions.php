<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['lecturer_logged_in']) || $_SESSION['lecturer_logged_in'] !== true) {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../database/db.php';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function decryptValue(?string $value): string
{
    if ($value === null || $value === '') return '';
    try {
        return decryptData($value);
    } catch (Throwable $error) {
        return '';
    }
}

function lecturerOwnsProject(PDO $db, int $lecturerId, int $projectId): bool
{
    $stmt = $db->prepare('SELECT COUNT(*) FROM projects WHERE project_id = ? AND lecturer_id = ?');
    $stmt->execute([$projectId, $lecturerId]);
    return (int) $stmt->fetchColumn() > 0;
}

function updateSubmissionStatus(PDO $db, int $projectId, string $status): void
{
    $stmt = $db->prepare('SELECT submission_id FROM submissions WHERE project_id = ? ORDER BY submitted_at DESC LIMIT 1');
    $stmt->execute([$projectId]);
    $submissionId = $stmt->fetchColumn();

    if ($submissionId) {
        $update = $db->prepare('UPDATE submissions SET status = ? WHERE submission_id = ?');
        $update->execute([$status, $submissionId]);
        return;
    }

    $insert = $db->prepare('INSERT INTO submissions (project_id, status) VALUES (?, ?)');
    $insert->execute([$projectId, $status]);
}

$lecturerId     = (int) ($_SESSION['user_id'] ?? 0);
$lecturerName   = trim((string) ($_SESSION['user_name'] ?? 'Lecturer'));
$lecturerInitials = implode('', array_slice(array_map(
    static fn($part) => strtoupper(substr($part, 0, 1)),
    array_filter(preg_split('/\s+/', $lecturerName) ?: [])
), 0, 2)) ?: 'L';

$flashMessage = '';
$flashType    = 'success';

// Handle POST actions (approve / reject / comment)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action    = $_POST['action'] ?? '';
    $projectId = (int) ($_POST['project_id'] ?? 0);

    try {
        if ($projectId > 0 && lecturerOwnsProject($db, $lecturerId, $projectId)) {
            if ($action === 'approve') {
                updateSubmissionStatus($db, $projectId, 'approved');
                $flashMessage = 'Project submission approved.';
            } elseif ($action === 'reject') {
                updateSubmissionStatus($db, $projectId, 'rejected');
                $flashMessage = 'Project submission rejected.';
                $flashType    = 'danger';
            } elseif ($action === 'comment') {
                $comment = trim((string) ($_POST['comment'] ?? ''));
                if ($comment !== '') {
                    $stmt = $db->prepare('INSERT INTO comments (project_id, user_id, content_encrypted) VALUES (?, ?, ?)');
                    $stmt->execute([$projectId, $lecturerId, encryptData($comment)]);
                    $flashMessage = 'Feedback submitted.';
                } else {
                    $flashMessage = 'Please enter feedback before submitting.';
                    $flashType    = 'danger';
                }
            }
        }
    } catch (Throwable $error) {
        $flashMessage = 'Unable to update the project right now.';
        $flashType    = 'danger';
    }
}

// Fetch projects + submissions + files
$projects     = [];
$statusLabels = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];

try {
    $stmt = $db->prepare(
        "SELECT p.project_id, p.title_encrypted, p.description_encrypted, p.study_year, p.created_at,
                s.submission_id, s.submitted_at, s.status
         FROM projects p
         LEFT JOIN submissions s ON s.submission_id = (
             SELECT submission_id FROM submissions
             WHERE project_id = p.project_id
             ORDER BY submitted_at DESC
             LIMIT 1
         )
         WHERE p.lecturer_id = ?
         ORDER BY COALESCE(s.submitted_at, p.created_at) DESC"
    );
    $stmt->execute([$lecturerId]);
    $projectRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $studentStmt = $db->prepare(
        "SELECT u.user_id, u.name_encrypted, pm.role
         FROM project_members pm
         INNER JOIN users u ON u.user_id = pm.user_id
         WHERE pm.project_id = ?
         ORDER BY pm.role DESC, u.user_id ASC"
    );

    $fileStmt = $db->prepare(
        "SELECT f.file_id, f.file_name_encrypted, f.file_path_encrypted, f.uploaded_at,
                u.name_encrypted AS uploader_name
         FROM files f
         LEFT JOIN users u ON u.user_id = f.uploaded_by
         WHERE f.project_id = ?
         ORDER BY f.uploaded_at DESC"
    );

    foreach ($projectRows as $row) {
        $studentStmt->execute([(int) $row['project_id']]);
        $students = [];
        foreach ($studentStmt->fetchAll(PDO::FETCH_ASSOC) as $student) {
            $name = decryptValue($student['name_encrypted'] ?? '');
            if ($name !== '') {
                $students[] = ['id' => (int) $student['user_id'], 'name' => $name, 'role' => (string) ($student['role'] ?? '')];
            }
        }

        $fileStmt->execute([(int) $row['project_id']]);
        $files = [];
        foreach ($fileStmt->fetchAll(PDO::FETCH_ASSOC) as $file) {
            $fileName = decryptValue($file['file_name_encrypted'] ?? '');
            $filePath = decryptValue($file['file_path_encrypted'] ?? '');
            if ($fileName !== '') {
                $files[] = [
                    'id'          => (int) $file['file_id'],
                    'name'        => $fileName,
                    'path'        => $filePath,
                    'uploaded_at' => $file['uploaded_at'] ?? null,
                    'uploader'    => decryptValue($file['uploader_name'] ?? ''),
                ];
            }
        }

        $projects[] = [
            'id'           => (int) $row['project_id'],
            'code'         => 'UTM-FYP-' . str_pad((string) $row['project_id'], 4, '0', STR_PAD_LEFT),
            'title'        => decryptValue($row['title_encrypted'] ?? '') ?: 'No data available',
            'study_year'   => $row['study_year'] ?? '',
            'submitted_at' => $row['submitted_at'] ?? null,
            'status'       => $row['status'] ?: 'pending',
            'students'     => $students,
            'files'        => $files,
        ];
    }
} catch (Throwable $error) {
    $flashMessage = $flashMessage ?: 'Unable to load submissions.';
    $flashType    = 'danger';
}

$totalProjects    = count($projects);
$pendingProjects  = count(array_filter($projects, static fn($p) => $p['status'] === 'pending'));
$approvedProjects = count(array_filter($projects, static fn($p) => $p['status'] === 'approved'));
$rejectedProjects = count(array_filter($projects, static fn($p) => $p['status'] === 'rejected'));
?>
<?php
$lecturerHeaderSkipDashboardData = true;
require_once __DIR__ . '/lecturer_header.php';
?>
        <div class="content">
            <?php if ($flashMessage): ?>
                <div class="alert alert-<?= e($flashType) ?> alert-dismissible fade show" role="alert">
                    <?= e($flashMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <section class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1" style="color:var(--lecturer-maroon);">Student Submissions</h1>
                    <p class="text-muted mb-0">Review, approve or reject project submissions and download files</p>
                </div>
            </section>

            <!-- Stats -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div><p class="text-muted fw-semibold mb-2">Total Submissions</p><strong class="stat-value"><?= e($totalProjects) ?></strong></div>
                            <div class="stat-icon"><i class="bi bi-folder2-open"></i></div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div><p class="text-muted fw-semibold mb-2">Pending Review</p><strong class="stat-value text-warning"><?= e($pendingProjects) ?></strong></div>
                            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div><p class="text-muted fw-semibold mb-2">Approved</p><strong class="stat-value text-success"><?= e($approvedProjects) ?></strong></div>
                            <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div><p class="text-muted fw-semibold mb-2">Rejected</p><strong class="stat-value text-danger"><?= e($rejectedProjects) ?></strong></div>
                            <div class="stat-icon"><i class="bi bi-x-circle-fill"></i></div>
                        </div>
                    </article>
                </div>
            </div>

            <!-- Toolbar -->
            <section class="toolbar mb-4">
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-lg">
                        <div class="input-group">
                            <span class="input-group-text search-control bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input id="projectSearch" class="form-control search-control border-start-0" type="search" placeholder="Search projects or students…">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <select id="yearFilter" class="form-select search-control">
                            <option value="all">All Study Years</option>
                            <?php foreach (array_unique(array_filter(array_column($projects, 'study_year'))) as $year): ?>
                                <option value="<?= e($year) ?>">Year <?= e($year) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <select id="statusFilter" class="form-select search-control">
                            <option value="all">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- Project Cards -->
            <?php if (!$projects): ?>
                <div class="empty-state"><div><i class="bi bi-folder2-open fs-2 d-block mb-2"></i>No submissions found.</div></div>
            <?php else: ?>
                <div class="row g-4" id="projectGrid">
                    <?php foreach ($projects as $project): ?>
                        <?php
                        $studentNames = array_column($project['students'], 'name');
                        $searchText   = strtolower($project['title'] . ' ' . implode(' ', $studentNames));
                        $submittedAt  = $project['submitted_at'] ? date('d/m/Y', strtotime((string) $project['submitted_at'])) : 'No data available';
                        ?>
                        <div class="col-12 col-lg-6 col-xxl-4 project-item"
                             data-status="<?= e($project['status']) ?>"
                             data-year="<?= e($project['study_year']) ?>"
                             data-search="<?= e($searchText) ?>">
                            <article class="project-card">
                                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                    <span class="status-badge status-<?= e($project['status']) ?>"><?= e($statusLabels[$project['status']] ?? $project['status']) ?></span>
                                    <small class="text-muted"><?= e($project['code']) ?></small>
                                </div>
                                <h2 class="project-title mb-2"><?= e($project['title']) ?></h2>
                                <div class="mb-2" style="color:var(--lecturer-text);line-height:1.55;">
                                    <strong><?= e(count($studentNames)) ?> Student<?= count($studentNames) === 1 ? '' : 's' ?></strong>
                                    <?php foreach ($studentNames as $i => $sn): ?>
                                        <div><?= e($i + 1) ?>. <?= e($sn) ?></div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-muted mb-1" style="font-size:.9rem;">Submitted: <?= e($submittedAt) ?></div>
                                <div class="text-muted mb-2" style="font-size:.9rem;">Study Year: <?= $project['study_year'] !== '' ? e($project['study_year']) : 'N/A' ?></div>

                                <!-- Files -->
                                <?php if ($project['files']): ?>
                                <div class="files-section">
                                    <h6><i class="bi bi-paperclip me-1"></i>Uploaded Files (<?= count($project['files']) ?>)</h6>
                                    <?php foreach ($project['files'] as $file): ?>
                                        <div class="file-item">
                                            <i class="bi bi-file-earmark-fill text-muted"></i>
                                            <span class="file-name" title="<?= e($file['name']) ?>"><?= e($file['name']) ?></span>
                                            <div class="file-meta d-none d-sm-block">
                                                <?= $file['uploaded_at'] ? e(date('d/m/Y', strtotime((string)$file['uploaded_at']))) : '' ?>
                                            </div>
                                            <?php if ($file['path']): ?>
                                                <a href="<?= e($file['path']) ?>" class="btn-download" download>
                                                    <i class="bi bi-download"></i> Download
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <div class="files-section text-muted" style="font-size:.875rem;">
                                    <i class="bi bi-folder2-open me-1"></i>No files uploaded yet.
                                </div>
                                <?php endif; ?>

                                <!-- Actions -->
                                <div class="action-row">
                                    <button class="btn btn-review btn-comment" type="button"
                                            data-bs-toggle="modal" data-bs-target="#commentModal"
                                            data-project-id="<?= e($project['id']) ?>"
                                            data-project-title="<?= e($project['title']) ?>">
                                        <i class="bi bi-chat-left-text me-1"></i>Comment
                                    </button>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="project_id" value="<?= e($project['id']) ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button class="btn btn-review btn-reject" type="submit"
                                                onclick="return confirm('Reject this submission?')">
                                            <i class="bi bi-x-lg me-1"></i>Reject
                                        </button>
                                    </form>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="project_id" value="<?= e($project['id']) ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button class="btn btn-review btn-approve" type="submit">
                                            <i class="bi bi-check-lg me-1"></i>Approve
                                        </button>
                                    </form>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="empty-state d-none mt-4" id="filteredEmpty">No submissions found.</div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Comment Modal -->
<div class="modal fade" id="commentModal" tabindex="-1" aria-labelledby="commentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5 fw-bold" id="commentModalLabel">Add Feedback</h2>
                    <p class="text-muted mb-0 small" id="commentProjectTitle">Project feedback</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="comment">
                <input type="hidden" name="project_id" id="commentProjectId">
                <label class="form-label fw-semibold" for="commentText">Comment</label>
                <textarea class="form-control" id="commentText" name="comment" rows="5"
                          placeholder="Write your feedback or revision notes…" required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn fw-bold" style="background:var(--lecturer-gold);color:#271700;">Submit Feedback</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const searchInput  = document.getElementById('projectSearch');
    const statusFilter = document.getElementById('statusFilter');
    const yearFilter   = document.getElementById('yearFilter');
    const items        = Array.from(document.querySelectorAll('.project-item'));
    const emptyDiv     = document.getElementById('filteredEmpty');

    function applyFilters() {
        const query  = (searchInput?.value || '').trim().toLowerCase();
        const status = statusFilter?.value || 'all';
        const year   = yearFilter?.value || 'all';
        let visible  = 0;
        items.forEach(item => {
            const show = (!query || item.dataset.search.includes(query))
                      && (status === 'all' || item.dataset.status === status)
                      && (year   === 'all' || item.dataset.year   === year);
            item.classList.toggle('d-none', !show);
            if (show) visible++;
        });
        emptyDiv?.classList.toggle('d-none', visible !== 0);
    }

    searchInput?.addEventListener('input', applyFilters);
    statusFilter?.addEventListener('change', applyFilters);
    yearFilter?.addEventListener('change', applyFilters);

    document.getElementById('commentModal')?.addEventListener('show.bs.modal', (e) => {
        const btn = e.relatedTarget;
        document.getElementById('commentProjectId').value = btn?.dataset.projectId || '';
        document.getElementById('commentProjectTitle').textContent = btn?.dataset.projectTitle || '';
        document.getElementById('commentText').value = '';
    });
</script>
</body>
</html>
