<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/encryption.php';

function publicPosterE(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function publicPosterDecrypt(?string $value): string
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

function publicPosterCode(int $projectId): string
{
    return 'UTM-FYP-' . str_pad((string) $projectId, 4, '0', STR_PAD_LEFT);
}

function publicPosterIsPosterFile(string $fileName): bool
{
    $normalized = strtolower($fileName);

    return $normalized !== '' && str_contains($normalized, 'poster');
}

function publicPosterIsImageFile(string $filePath): bool
{
    return in_array(strtolower(pathinfo($filePath, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true);
}

$projects = [];
$posterCount = 0;
$flashMessage = '';
$selectedProjectId = (int) ($_GET['project_id'] ?? 0);

try {
    $projectRows = $db->query(
        "SELECT p.project_id, p.title_encrypted, p.description_encrypted, p.category_encrypted,
                p.study_year, p.created_at, u.name_encrypted AS lecturer_name
         FROM projects p
         LEFT JOIN users u ON u.user_id = p.lecturer_id
         ORDER BY p.created_at DESC, p.project_id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

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

    foreach ($projectRows as $row) {
        $projectId = (int) $row['project_id'];

        $memberStmt->execute([$projectId]);
        $students = [];
        foreach ($memberStmt->fetchAll(PDO::FETCH_ASSOC) as $member) {
            $studentName = publicPosterDecrypt($member['name_encrypted'] ?? '');
            if ($studentName !== '') {
                $students[] = $studentName;
            }
        }

        $fileStmt->execute([$projectId]);
        $posters = [];
        $headerPhoto = null;
        foreach ($fileStmt->fetchAll(PDO::FETCH_ASSOC) as $file) {
            $fileName = publicPosterDecrypt($file['file_name_encrypted'] ?? '');
            $filePath = publicPosterDecrypt($file['file_path_encrypted'] ?? '');
            $fileType = (string) ($file['file_type'] ?? 'document');

            if ($fileType === 'header_photo' && $filePath !== '' && $headerPhoto === null) {
                $headerPhoto = [
                    'id' => (int) ($file['file_id'] ?? 0),
                    'name' => $fileName,
                    'path' => $filePath,
                    'uploaded_by' => (int) ($file['uploaded_by'] ?? 0),
                    'uploaded_at' => $file['uploaded_at'] ?? null,
                ];
                continue;
            }

            if ($fileType === 'poster' || publicPosterIsPosterFile($fileName)) {
                $posters[] = [
                    'id' => (int) ($file['file_id'] ?? 0),
                    'name' => $fileName,
                    'path' => $filePath,
                    'uploaded_by' => (int) ($file['uploaded_by'] ?? 0),
                    'uploaded_at' => $file['uploaded_at'] ?? null,
                ];
            }
        }

        $posterCount += count($posters);

        $projects[] = [
            'id' => $projectId,
            'code' => publicPosterCode($projectId),
            'title' => publicPosterDecrypt($row['title_encrypted'] ?? '') ?: publicPosterCode($projectId),
            'description' => publicPosterDecrypt($row['description_encrypted'] ?? ''),
            'category' => publicPosterDecrypt($row['category_encrypted'] ?? ''),
            'study_year' => $row['study_year'] ?? '',
            'created_at' => $row['created_at'] ?? '',
            'lecturer' => publicPosterDecrypt($row['lecturer_name'] ?? ''),
            'students' => $students,
            'posters' => $posters,
            'header_photo' => $headerPhoto,
            'is_selected' => $selectedProjectId > 0 && $selectedProjectId === $projectId,
        ];
    }

    if ($selectedProjectId > 0) {
        usort($projects, static function (array $a, array $b): int {
            return (int) ($b['is_selected'] ?? false) <=> (int) ($a['is_selected'] ?? false);
        });
    }
} catch (Throwable $error) {
    $flashMessage = 'Public posters are unavailable right now. Please try again later.';
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Posters - JSKP Repository</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="utm-theme.css">
    <style>
        body {
            background:
                radial-gradient(circle at top left, rgba(242, 169, 0, 0.16), transparent 28%),
                linear-gradient(180deg, #fffdfa 0%, #f5f2ee 100%);
        }

        .public-nav {
            min-height: 76px;
            border-bottom: 1px solid rgba(216, 199, 179, 0.75);
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
        }

        .brand-mark {
            width: 44px;
            height: 44px;
            object-fit: contain;
        }

        .catalog-shell {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: 28px 0 56px;
        }

        .catalog-hero {
            padding: 34px 0 24px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 28px;
            align-items: end;
        }

        .catalog-title {
            max-width: 760px;
        }

        .catalog-title h1 {
            color: var(--utm-maroon);
            font-size: clamp(2rem, 4vw, 3.4rem);
            line-height: 1.04;
            margin: 0 0 14px;
        }

        .catalog-title p {
            color: var(--utm-muted);
            font-size: 1.04rem;
            line-height: 1.7;
            margin: 0;
        }

        .catalog-stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(120px, 1fr));
            gap: 12px;
        }

        .stat-tile {
            min-height: 96px;
            padding: 18px;
            border: 1px solid var(--utm-border);
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 14px 34px rgba(0, 0, 0, 0.08);
        }

        .stat-tile strong {
            display: block;
            color: var(--utm-maroon);
            font-size: 1.8rem;
            line-height: 1;
        }

        .stat-tile span {
            color: var(--utm-muted);
            font-size: 0.9rem;
        }

        .toolbar {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(170px, 220px);
            gap: 12px;
            margin: 12px 0 24px;
        }

        .search-control {
            min-height: 48px;
            border-radius: 8px;
            box-shadow: none;
            background: #fff;
        }

        .poster-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 18px;
        }

        .poster-card {
            min-height: 100%;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(216, 199, 179, 0.9);
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 16px 38px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .poster-preview {
            min-height: 176px;
            padding: 22px;
            display: grid;
            place-items: center;
            background:
                linear-gradient(135deg, rgba(128, 0, 32, 0.1), rgba(242, 169, 0, 0.14)),
                #fff7ec;
        }

        .poster-preview.has-photo {
            padding: 0;
            background: #fff7ec;
        }

        .poster-preview img {
            width: 100%;
            height: 176px;
            object-fit: cover;
            display: block;
        }

        .poster-icon {
            width: 86px;
            height: 86px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: var(--utm-maroon);
            color: #fff;
            font-size: 2.6rem;
            box-shadow: 0 18px 30px rgba(128, 0, 32, 0.22);
        }

        .poster-card-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex: 1;
            gap: 14px;
        }

        .poster-code {
            color: var(--utm-gold);
            font-size: 0.82rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .poster-card h2 {
            margin: 0;
            color: var(--utm-dark);
            font-size: 1.18rem;
            line-height: 1.28;
        }

        .poster-description {
            color: var(--utm-muted);
            line-height: 1.58;
            margin: 0;
        }

        .poster-meta {
            display: grid;
            gap: 8px;
            color: var(--utm-muted);
            font-size: 0.93rem;
        }

        .poster-meta i {
            color: var(--utm-maroon);
            margin-right: 6px;
        }

        .poster-files {
            display: grid;
            gap: 10px;
            margin-top: auto;
        }

        .poster-manage {
            display: grid;
            gap: 12px;
            padding: 14px;
            border: 1px solid rgba(128, 0, 32, 0.12);
            border-radius: 8px;
            background: rgba(128, 0, 32, 0.035);
        }

        .poster-manage-title {
            margin: 0;
            color: var(--utm-maroon);
            font-size: 0.9rem;
            font-weight: 800;
        }

        .poster-manage form {
            display: grid;
            gap: 8px;
        }

        .poster-manage-actions {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 8px;
            align-items: center;
        }

        .poster-file {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px;
            border: 1px solid rgba(216, 199, 179, 0.72);
            border-radius: 8px;
            background: #fffdfa;
        }

        .poster-file-name {
            min-width: 0;
            color: var(--utm-dark);
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .poster-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .btn-icon {
            width: 40px;
            height: 40px;
            padding: 0;
            border-radius: 8px;
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

        .empty-state-public {
            padding: 42px 24px;
            border: 1px dashed var(--utm-border);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.8);
            text-align: center;
            color: var(--utm-muted);
        }

        @media (max-width: 760px) {
            .catalog-hero,
            .toolbar {
                grid-template-columns: 1fr;
            }

            .catalog-stats {
                grid-template-columns: 1fr 1fr;
            }

            .poster-file {
                align-items: flex-start;
                flex-direction: column;
            }

            .poster-manage-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="public-nav d-flex align-items-center">
        <div class="catalog-shell py-0 d-flex align-items-center justify-content-between gap-3">
            <a class="d-flex align-items-center gap-2 text-decoration-none" href="posters.php">
                <img class="brand-mark" src="../assets/utm-logo.png" alt="UTM logo">
                <div>
                    <div class="fw-bold text-dark lh-sm">JSKP Repository</div>
                    <div class="small text-muted">Public Posters</div>
                </div>
            </a>
            <a class="btn btn-outline-secondary" href="login.php">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                Login
            </a>
        </div>
    </nav>

    <main class="catalog-shell">
        <section class="catalog-hero">
            <div class="catalog-title">
                <div class="poster-code mb-2">Public Access</div>
                <h1>Project Poster Gallery</h1>
                <p>Browse final year project poster uploads and project summaries without signing in.</p>
            </div>
            <div class="catalog-stats">
                <div class="stat-tile">
                    <strong><?= publicPosterE((string) count($projects)) ?></strong>
                    <span>Projects</span>
                </div>
                <div class="stat-tile">
                    <strong><?= publicPosterE((string) $posterCount) ?></strong>
                    <span>Poster files</span>
                </div>
            </div>
        </section>

        <?php if ($flashMessage): ?>
            <div class="alert alert-danger" role="alert"><?= publicPosterE($flashMessage) ?></div>
        <?php endif; ?>

        <section class="toolbar">
            <div class="input-group">
                <span class="input-group-text search-control bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input id="posterSearch" class="form-control search-control border-start-0" type="search" placeholder="Search poster, project, student, or supervisor">
            </div>
            <select id="yearFilter" class="form-select search-control">
                <option value="all">All Study Years</option>
                <?php foreach (array_unique(array_filter(array_column($projects, 'study_year'))) as $year): ?>
                    <option value="<?= publicPosterE((string) $year) ?>">Year <?= publicPosterE((string) $year) ?></option>
                <?php endforeach; ?>
            </select>
        </section>

        <?php if (!$projects): ?>
            <div class="empty-state-public">
                <i class="bi bi-folder2-open fs-2 d-block mb-2"></i>
                No projects are available yet.
            </div>
        <?php else: ?>
            <section class="poster-grid" id="posterGrid">
                <?php foreach ($projects as $project): ?>
                    <?php
                    $studentNames = implode(', ', $project['students']);
                    $posterNames = implode(' ', array_map(static fn($poster) => $poster['name'], $project['posters']));
                    $searchText = strtolower($project['title'] . ' ' . $project['description'] . ' ' . $project['lecturer'] . ' ' . $studentNames . ' ' . $posterNames);
                    ?>
                    <article class="poster-card poster-item" data-search="<?= publicPosterE($searchText) ?>" data-year="<?= publicPosterE((string) $project['study_year']) ?>">
                        <div class="poster-preview <?= !empty($project['header_photo']['path']) && publicPosterIsImageFile((string) $project['header_photo']['path']) ? 'has-photo' : '' ?>">
                            <?php if (!empty($project['header_photo']['path']) && publicPosterIsImageFile((string) $project['header_photo']['path'])): ?>
                                <img src="<?= publicPosterE($project['header_photo']['path']) ?>" alt="<?= publicPosterE($project['title']) ?> header photo">
                            <?php else: ?>
                                <div class="poster-icon" aria-hidden="true">
                                    <i class="bi bi-file-earmark-richtext"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="poster-card-body">
                            <div>
                                <div class="poster-code"><?= publicPosterE($project['code']) ?></div>
                                <h2><?= publicPosterE($project['title']) ?></h2>
                            </div>
                            <p class="poster-description"><?= publicPosterE($project['description'] ?: 'No project description has been published yet.') ?></p>
                            <div class="poster-meta">
                                <div><i class="bi bi-person-workspace"></i>Supervisor: <?= publicPosterE($project['lecturer'] ?: 'Not assigned') ?></div>
                                <div><i class="bi bi-people"></i>Students: <?= publicPosterE($studentNames ?: 'Not assigned') ?></div>
                                <div><i class="bi bi-calendar3"></i>Study Year: <?= $project['study_year'] !== '' ? publicPosterE((string) $project['study_year']) : 'N/A' ?></div>
                            </div>
                            <div class="poster-files">
                                <?php if (!$project['posters']): ?>
                                    <div class="poster-file text-muted">
                                        <span>No poster uploaded yet.</span>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($project['posters'] as $poster): ?>
                                        <div class="poster-file">
                                            <div>
                                                <div class="poster-file-name">POSTER</div>
                                                <div class="small text-muted">
                                                    <?= $poster['uploaded_at'] ? publicPosterE(date('d M Y', strtotime((string) $poster['uploaded_at']))) : 'Upload date unavailable' ?>
                                                </div>
                                            </div>
                                            <?php if ($poster['path']): ?>
                                                <div class="poster-actions">
                                                    <button class="btn btn-outline-secondary btn-icon" type="button" aria-label="View poster" onclick="openPosterPreview(<?= publicPosterE(json_encode($poster['path'])) ?>)">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <a class="btn btn-utm btn-icon" href="<?= publicPosterE($poster['path']) ?>" download="POSTER" aria-label="Download poster">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
            <div class="empty-state-public d-none mt-3" id="filteredEmpty">
                No posters match your filters.
            </div>
        <?php endif; ?>
    </main>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

        const searchInput = document.getElementById('posterSearch');
        const yearFilter = document.getElementById('yearFilter');
        const posterItems = Array.from(document.querySelectorAll('.poster-item'));
        const filteredEmpty = document.getElementById('filteredEmpty');

        function applyPosterFilters() {
            const query = (searchInput?.value || '').trim().toLowerCase();
            const year = yearFilter?.value || 'all';
            let visible = 0;

            posterItems.forEach((item) => {
                const matchesSearch = !query || item.dataset.search.includes(query);
                const matchesYear = year === 'all' || item.dataset.year === year;
                const shouldShow = matchesSearch && matchesYear;

                item.classList.toggle('d-none', !shouldShow);
                if (shouldShow) {
                    visible++;
                }
            });

            filteredEmpty?.classList.toggle('d-none', visible !== 0);
        }

        searchInput?.addEventListener('input', applyPosterFilters);
        yearFilter?.addEventListener('change', applyPosterFilters);
    </script>
</body>
</html>
