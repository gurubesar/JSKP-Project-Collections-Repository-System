<?php
require_once __DIR__ . '/student_header.php';

$selectedProjectId = (int) ($_GET['project_id'] ?? 0);
$previewMode = ($_GET['preview'] ?? '') === '1';
$posterProjects = [];
$posterFlash = $_SESSION['student_flash'] ?? '';
$posterFlashType = $_SESSION['student_flash_type'] ?? 'success';
unset($_SESSION['student_flash'], $_SESSION['student_flash_type']);

function studentPosterCode(int $projectId): string
{
    return 'UTM-FYP-' . str_pad((string) $projectId, 4, '0', STR_PAD_LEFT);
}

function studentPosterIsPosterFile(string $fileName): bool
{
    $normalized = strtolower($fileName);
    return $normalized !== '' && str_contains($normalized, 'poster');
}

function studentPosterIsImageFile(string $filePath): bool
{
    return in_array(strtolower(pathinfo($filePath, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true);
}

function studentPosterPublicHref(int $projectId): string
{
    return 'student_posters.php?project_id=' . $projectId . '&preview=1';
}

try {
    $projectStmt = $db->prepare(
        "SELECT p.project_id, p.title_encrypted, p.description_encrypted, p.study_year, p.created_at,
                u.name_encrypted AS lecturer_name
         FROM project_members pm
         INNER JOIN projects p ON p.project_id = pm.project_id
         LEFT JOIN users u ON u.user_id = p.lecturer_id
         WHERE pm.user_id = ? AND pm.role = 'student'
         ORDER BY p.created_at DESC, p.project_id DESC"
    );
    $projectStmt->execute([$studentId]);

    $memberStmt = $db->prepare(
        "SELECT u.name_encrypted
         FROM project_members pm
         INNER JOIN users u ON u.user_id = pm.user_id
         WHERE pm.project_id = ? AND pm.role = 'student' AND u.role = 'student'
         ORDER BY u.user_id ASC"
    );

    $fileStmt = $db->prepare(
        "SELECT file_id, file_name_encrypted, file_path_encrypted, file_type, uploaded_by, uploaded_at
         FROM files
         WHERE project_id = ?
         ORDER BY uploaded_at DESC"
    );

    foreach ($projectStmt->fetchAll(PDO::FETCH_ASSOC) as $projectRow) {
        $projectId = (int) $projectRow['project_id'];

        $memberStmt->execute([$projectId]);
        $studentNames = [];
        foreach ($memberStmt->fetchAll(PDO::FETCH_ASSOC) as $memberRow) {
            $name = decryptValue($memberRow['name_encrypted'] ?? '');
            if ($name !== '') {
                $studentNames[] = $name;
            }
        }

        $fileStmt->execute([$projectId]);
        $posters = [];
        $headerFile = null;
        foreach ($fileStmt->fetchAll(PDO::FETCH_ASSOC) as $fileRow) {
            $fileName = decryptValue($fileRow['file_name_encrypted'] ?? '');
            $filePath = decryptValue($fileRow['file_path_encrypted'] ?? '');
            $fileType = (string) ($fileRow['file_type'] ?? 'document');

            if ($fileType === 'header_photo' && $headerFile === null) {
                $headerFile = [
                    'id' => (int) $fileRow['file_id'],
                    'name' => $fileName,
                    'path' => $filePath,
                    'uploaded_at' => $fileRow['uploaded_at'] ?? null,
                ];
                continue;
            }

            if ($fileType === 'poster' || studentPosterIsPosterFile($fileName)) {
                $posters[] = [
                    'id' => (int) $fileRow['file_id'],
                    'name' => $fileName,
                    'path' => $filePath,
                    'uploaded_at' => $fileRow['uploaded_at'] ?? null,
                ];
            }
        }

        $posterProjects[] = [
            'id' => $projectId,
            'code' => studentPosterCode($projectId),
            'title' => decryptValue($projectRow['title_encrypted'] ?? '') ?: studentPosterCode($projectId),
            'description' => decryptValue($projectRow['description_encrypted'] ?? ''),
            'study_year' => $projectRow['study_year'] ?? '',
            'lecturer' => decryptValue($projectRow['lecturer_name'] ?? ''),
            'students' => $studentNames,
            'posters' => $posters,
            'header_file' => $headerFile,
            'is_selected' => $selectedProjectId > 0 && $selectedProjectId === $projectId,
        ];
    }

    if ($selectedProjectId > 0) {
        $posterProjects = array_values(array_filter(
            $posterProjects,
            static fn(array $project): bool => (int) $project['id'] === $selectedProjectId
        ));
    }
} catch (Throwable $error) {
    $posterFlash = $posterFlash ?: 'Unable to load poster projects.';
    $posterFlashType = 'danger';
}
?>

<section>
    <?php if ($posterFlash): ?>
        <div class="alert alert-<?= e($posterFlashType) ?>" role="alert"><?= e($posterFlash) ?></div>
    <?php endif; ?>

    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <span class="badge badge-utm-gold mb-2"><?= $previewMode ? 'Preview' : 'Student Poster Manager' ?></span>
            <h1 class="h3 fw-bold mb-1"><?= $previewMode ? 'Poster Public Preview' : 'Project Posters' ?></h1>
            <p class="text-muted mb-0"><?= $previewMode ? 'This is how your project poster card will appear to public visitors.' : 'Upload and remove poster files for your assigned project only.' ?></p>
        </div>
        <?php if ($previewMode): ?>
            <a class="btn btn-outline-secondary" href="student_posters.php<?= $selectedProjectId > 0 ? '?project_id=' . e((string) $selectedProjectId) : '' ?>">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        <?php elseif ($posterProjects): ?>
            <a class="btn btn-outline-secondary" href="<?= e(studentPosterPublicHref((int) $posterProjects[0]['id'])) ?>">
                <i class="bi bi-eye me-1"></i> Public View
            </a>
        <?php endif; ?>
    </div>

    <?php if (!$posterProjects): ?>
        <div class="card border-utm rounded-4 p-4 shadow-sm text-muted">No assigned poster project found.</div>
    <?php elseif ($previewMode): ?>
        <div class="poster-public-preview-grid">
            <?php foreach ($posterProjects as $project): ?>
                <?php $studentNames = implode(', ', $project['students']); ?>
                <article class="poster-public-card">
                    <div class="poster-manager-preview <?= !empty($project['header_file']['path']) && studentPosterIsImageFile((string) $project['header_file']['path']) ? 'has-photo' : '' ?>">
                        <?php if (!empty($project['header_file']['path']) && studentPosterIsImageFile((string) $project['header_file']['path'])): ?>
                            <img src="<?= e($project['header_file']['path']) ?>" alt="<?= e($project['title']) ?> header">
                        <?php else: ?>
                            <i class="bi bi-file-earmark-richtext"></i>
                        <?php endif; ?>
                    </div>
                    <div class="poster-manager-body">
                        <div>
                            <div class="poster-code"><?= e($project['code']) ?></div>
                            <h2><?= e($project['title']) ?></h2>
                            <p class="text-muted mb-0"><?= e($project['description'] ?: 'No project description has been published yet.') ?></p>
                        </div>
                        <div class="poster-manager-meta">
                            <span><i class="bi bi-person-workspace"></i> Supervisor: <?= e($project['lecturer'] ?: 'Not assigned') ?></span>
                            <span><i class="bi bi-people"></i> Students: <?= e($studentNames ?: 'Not assigned') ?></span>
                            <span><i class="bi bi-calendar3"></i> Study Year: <?= e((string) ($project['study_year'] ?: 'N/A')) ?></span>
                        </div>
                        <div class="poster-files-stack">
                            <?php if (!$project['posters']): ?>
                                <div class="poster-file-row text-muted">No poster uploaded yet.</div>
                            <?php else: ?>
                                <?php foreach ($project['posters'] as $poster): ?>
                                    <div class="poster-file-row">
                                        <div>
                                            <strong>POSTER</strong>
                                            <div class="small text-muted"><?= $poster['uploaded_at'] ? e(date('d M Y', strtotime((string) $poster['uploaded_at']))) : 'Upload date unavailable' ?></div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <?php if ($poster['path']): ?>
                                                <button class="btn btn-outline-secondary btn-sm" type="button" onclick="openPosterPreview(<?= e(json_encode($poster['path'])) ?>)"><i class="bi bi-eye"></i></button>
                                                <a class="btn btn-utm btn-sm" href="<?= e($poster['path']) ?>" download="POSTER"><i class="bi bi-download"></i></a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="poster-manager-grid">
            <?php foreach ($posterProjects as $project): ?>
                <?php $studentNames = implode(', ', $project['students']); ?>
                <article class="poster-manager-card">
                    <div class="poster-manager-preview <?= !empty($project['header_file']['path']) && studentPosterIsImageFile((string) $project['header_file']['path']) ? 'has-photo' : '' ?>">
                        <?php if (!empty($project['header_file']['path']) && studentPosterIsImageFile((string) $project['header_file']['path'])): ?>
                            <img src="<?= e($project['header_file']['path']) ?>" alt="<?= e($project['title']) ?> header">
                        <?php else: ?>
                            <i class="bi bi-file-earmark-richtext"></i>
                        <?php endif; ?>
                    </div>
                    <div class="poster-manager-body">
                        <div>
                            <div class="poster-code"><?= e($project['code']) ?></div>
                            <h2><?= e($project['title']) ?></h2>
                            <p class="text-muted mb-0"><?= e($project['description'] ?: 'No project description provided yet.') ?></p>
                        </div>

                        <div class="poster-manager-meta">
                            <span><i class="bi bi-person-workspace"></i> <?= e($project['lecturer'] ?: 'Not assigned') ?></span>
                            <span><i class="bi bi-people"></i> <?= e($studentNames ?: 'Not assigned') ?></span>
                            <span><i class="bi bi-calendar3"></i> Year <?= e((string) ($project['study_year'] ?: 'N/A')) ?></span>
                        </div>

                        <div class="poster-toolbox">
                            <?php if ($project['posters']): ?>
                                <div class="poster-upload-locked">
                                    <strong>Poster uploaded</strong>
                                    <span>Delete the current poster before uploading a new one.</span>
                                </div>
                            <?php else: ?>
                                <form action="student_actions.php?action=upload_file&project_id=<?= e((string) $project['id']) ?>" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="upload_type" value="poster">
                                    <input type="hidden" name="return_to" value="posters">
                                    <label class="form-label fw-semibold">Poster file</label>
                                    <div class="poster-upload-row">
                                        <input class="form-control" type="file" name="project_file" required>
                                        <button class="btn btn-utm" type="submit"><i class="bi bi-upload me-1"></i> Upload Poster</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                            <form action="student_actions.php?action=upload_file&project_id=<?= e((string) $project['id']) ?>" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="upload_type" value="header_photo">
                                <input type="hidden" name="return_to" value="posters">
                                <label class="form-label fw-semibold">Header file</label>
                                <div class="poster-upload-row">
                                    <input class="form-control" type="file" name="project_file" required>
                                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-image me-1"></i> Change Header</button>
                                </div>
                                <div class="form-text">Recommended header size: 1600 x 450 px or larger, landscape ratio around 16:4.5.</div>
                            </form>
                        </div>

                        <?php if (!empty($project['header_file'])): ?>
                            <div class="poster-file-row">
                                <div>
                                    <strong><?= e($project['header_file']['name'] ?: 'Header file') ?></strong>
                                    <div class="small text-muted">Header file</div>
                                </div>
                                <form action="student_actions.php?action=delete_file&project_id=<?= e((string) $project['id']) ?>" method="post" class="m-0">
                                    <input type="hidden" name="file_id" value="<?= e((string) $project['header_file']['id']) ?>">
                                    <input type="hidden" name="return_to" value="posters">
                                    <button class="btn btn-outline-danger btn-sm" type="submit" onclick="return confirm('Delete this header file?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>

                        <div class="poster-files-stack">
                            <?php if (!$project['posters']): ?>
                                <div class="poster-file-row text-muted">No poster uploaded yet.</div>
                            <?php else: ?>
                                <?php foreach ($project['posters'] as $poster): ?>
                                    <div class="poster-file-row">
                                        <div>
                                            <strong><?= e($poster['name'] ?: 'Project poster') ?></strong>
                                            <div class="small text-muted"><?= $poster['uploaded_at'] ? e(date('d M Y', strtotime((string) $poster['uploaded_at']))) : 'Upload date unavailable' ?></div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <?php if ($poster['path']): ?>
                                                <button class="btn btn-outline-secondary btn-sm" type="button" onclick="openPosterPreview(<?= e(json_encode($poster['path'])) ?>)"><i class="bi bi-eye"></i></button>
                                                <a class="btn btn-utm btn-sm" href="<?= e($poster['path']) ?>" download="<?= e($poster['name'] ?: 'project-poster') ?>"><i class="bi bi-download"></i></a>
                                            <?php endif; ?>
                                            <form action="student_actions.php?action=delete_file&project_id=<?= e((string) $project['id']) ?>" method="post" class="m-0">
                                                <input type="hidden" name="file_id" value="<?= e((string) $poster['id']) ?>">
                                                <input type="hidden" name="return_to" value="posters">
                                                <button class="btn btn-outline-danger btn-sm" type="submit" onclick="return confirm('Delete this poster?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<div class="modal fade" id="posterPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content poster-preview-modal">
            <div class="modal-header">
                <h2 class="modal-title h5">POSTER</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <iframe id="posterPreviewFrame" title="Poster preview"></iframe>
            </div>
        </div>
    </div>
</div>

<style>
    .poster-manager-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 18px;
    }

    .poster-public-preview-grid {
        display: grid;
        grid-template-columns: minmax(0, 760px);
        gap: 18px;
    }

    .poster-manager-card,
    .poster-public-card {
        overflow: hidden;
        border: 1px solid var(--student-border);
        border-radius: 8px;
        background: #fff;
        box-shadow: var(--student-shadow);
    }

    .poster-manager-preview {
        min-height: 190px;
        display: grid;
        place-items: center;
        background: linear-gradient(135deg, rgba(128, 0, 32, 0.1), rgba(214, 160, 29, 0.18));
        color: var(--student-sidebar);
        font-size: 3rem;
    }

    .poster-manager-preview.has-photo {
        display: block;
    }

    .poster-manager-preview img {
        width: 100%;
        height: 220px;
        object-fit: cover;
        display: block;
    }

    .poster-manager-body {
        display: grid;
        gap: 16px;
        padding: 20px;
    }

    .poster-code {
        color: var(--student-gold);
        font-size: 0.8rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .poster-manager-body h2 {
        margin: 4px 0 8px;
        font-size: 1.25rem;
        color: var(--student-text);
    }

    .poster-manager-meta {
        display: grid;
        gap: 8px;
        color: var(--student-muted);
    }

    .poster-manager-meta i {
        color: var(--student-sidebar);
        margin-right: 6px;
    }

    .poster-toolbox,
    .poster-files-stack {
        display: grid;
        gap: 12px;
    }

    .poster-upload-locked {
        display: grid;
        gap: 2px;
        padding: 12px;
        border: 1px solid rgba(128, 0, 32, 0.14);
        border-radius: 8px;
        background: #fffdfa;
        color: var(--student-muted);
    }

    .poster-upload-locked strong {
        color: var(--student-text);
    }

    .poster-toolbox {
        padding: 14px;
        border: 1px solid rgba(128, 0, 32, 0.12);
        border-radius: 8px;
        background: rgba(128, 0, 32, 0.035);
    }

    .poster-upload-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 10px;
        align-items: center;
    }

    .poster-file-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px;
        border: 1px solid rgba(216, 199, 179, 0.72);
        border-radius: 8px;
        background: #fffdfa;
    }

    .poster-file-row strong {
        overflow-wrap: anywhere;
    }

    .poster-preview-modal .modal-body {
        height: min(78vh, 760px);
        padding: 0;
        background: #f6f7fb;
    }

    .poster-preview-modal iframe {
        width: 100%;
        height: 100%;
        border: 0;
        display: block;
    }

    @media (max-width: 720px) {
        .poster-upload-row,
        .poster-file-row {
            grid-template-columns: 1fr;
            align-items: stretch;
            flex-direction: column;
        }
    }
</style>

<script>
function openPosterPreview(path) {
    const frame = document.getElementById('posterPreviewFrame');
    frame.src = path;
    const modal = new bootstrap.Modal(document.getElementById('posterPreviewModal'));
    modal.show();
}

document.getElementById('posterPreviewModal')?.addEventListener('hidden.bs.modal', () => {
    document.getElementById('posterPreviewFrame').src = '';
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
