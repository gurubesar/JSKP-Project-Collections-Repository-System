<?php
$lecturerHeaderSkipDashboardData = true;
require_once __DIR__ . '/lecturer_header.php';

$projects = [];
$flashMessage = '';
$flashType = 'success';

try {
    $stmt = $db->prepare(
        "SELECT p.project_id, p.title_encrypted, p.study_year, p.created_at,
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
        "SELECT u.user_id, u.name_encrypted
         FROM project_members pm
         INNER JOIN users u ON u.user_id = pm.user_id
         WHERE pm.project_id = ?
         ORDER BY u.user_id ASC"
    );

    $fileStmt = $db->prepare(
        "SELECT file_id, file_name_encrypted, file_path_encrypted, uploaded_at
         FROM files
         WHERE project_id = ?
         ORDER BY uploaded_at DESC"
    );

    foreach ($projectRows as $row) {
        $studentStmt->execute([(int) $row['project_id']]);

        $students = [];
        foreach ($studentStmt->fetchAll(PDO::FETCH_ASSOC) as $student) {
            $name = decryptValue($student['name_encrypted'] ?? '');
            if ($name !== '') {
                $students[] = [
                    'id' => (int) $student['user_id'],
                    'name' => $name,
                ];
            }
        }

        $fileStmt->execute([(int) $row['project_id']]);
        $files = [];
        foreach ($fileStmt->fetchAll(PDO::FETCH_ASSOC) as $file) {
            $fileName = decryptValue($file['file_name_encrypted'] ?? '');
            $filePath = decryptValue($file['file_path_encrypted'] ?? '');
            if ($fileName !== '') {
                $files[] = [
                    'id' => (int) $file['file_id'],
                    'name' => $fileName,
                    'path' => $filePath,
                    'uploaded_at' => $file['uploaded_at'] ?? null,
                ];
            }
        }

        $projects[] = [
            'id' => (int) $row['project_id'],
            'code' => 'UTM-FYP-' . str_pad((string) $row['project_id'], 4, '0', STR_PAD_LEFT),
            'title' => decryptValue($row['title_encrypted'] ?? '') ?: 'No data available',
            'study_year' => $row['study_year'] ?? '',
            'submitted_at' => $row['submitted_at'] ?? null,
            'status' => $row['status'] ?: 'pending',
            'students' => $students,
            'files' => $files,
        ];
    }
} catch (Throwable $error) {
    $flashMessage = 'Unable to load project data at this time.';
    $flashType = 'danger';
}

$statusLabels = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];
?>

        <div class="content">
            <?php if ($flashMessage): ?>
                <div class="alert alert-<?= e($flashType) ?> alert-dismissible fade show" role="alert">
                    <?= e($flashMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <section class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1" style="color: var(--lecturer-maroon);">Project Workspace</h1>
                    <p class="text-muted mb-0">View student submissions and download generated proposal files.</p>
                </div>
            </section>

            <section class="toolbar mb-4">
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-lg">
                        <div class="input-group">
                            <span class="input-group-text search-control bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input id="projectSearch" class="form-control search-control border-start-0" type="search" placeholder="Search by project or student...">
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

            <section class="mb-4">
                <?php if (!$projects): ?>
                    <div class="empty-state">
                        <div>
                            <i class="bi bi-folder2-open fs-2 d-block mb-2"></i>
                            No project submissions are available yet.
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row g-4" id="projectGrid">
                        <?php foreach ($projects as $project): ?>
                            <?php
                                $studentNames = array_map(static fn($student) => $student['name'], $project['students']);
                                $searchText = strtolower($project['title'] . ' ' . implode(' ', $studentNames));
                                $submittedAt = $project['submitted_at'] ? date('d/m/Y', strtotime((string) $project['submitted_at'])) : 'No data available';
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
                                    <div class="student-list mb-3">
                                        <strong><?= e(count($studentNames)) ?> Student<?= count($studentNames) === 1 ? '' : 's' ?></strong>
                                        <?php if ($studentNames): ?>
                                            <?php foreach ($studentNames as $index => $studentName): ?>
                                                <div><?= e($index + 1) ?>. <?= e($studentName) ?></div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div>No student information available</div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="meta-line">Submission Date: <?= e($submittedAt) ?></div>
                                    <div class="meta-line">Study Year: <?= $project['study_year'] !== '' ? e($project['study_year']) : 'No data available' ?></div>
                                    <div class="meta-line mb-3">Proposal Files: <?= e(count($project['files'])) ?></div>

                                    <div class="action-row">
                                        <?php if ($project['files']): ?>
                                            <?php foreach ($project['files'] as $file): ?>
                                                <a class="btn btn-review btn-approve mb-2" href="<?= e($file['path']) ?>" target="_blank" download="<?= e($file['name']) ?>">
                                                    <?= e(pathinfo($file['name'], PATHINFO_EXTENSION) === 'doc' ? 'Download Proposal' : 'Download File') ?>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted">No uploaded files found.</span>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="empty-state d-none" id="filteredEmpty">No projects match your filters.</div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</div>

<script>
    const searchInput = document.getElementById('projectSearch');
    const statusFilter = document.getElementById('statusFilter');
    const yearFilter = document.getElementById('yearFilter');
    const projectItems = Array.from(document.querySelectorAll('.project-item'));
    const filteredEmpty = document.getElementById('filteredEmpty');

    function applyFilters() {
        const query = (searchInput?.value || '').trim().toLowerCase();
        const status = statusFilter?.value || 'all';
        const year = yearFilter?.value || 'all';
        let visible = 0;

        projectItems.forEach((item) => {
            const matchesSearch = !query || item.dataset.search.includes(query);
            const matchesStatus = status === 'all' || item.dataset.status === status;
            const matchesYear = year === 'all' || item.dataset.year === year;
            const shouldShow = matchesSearch && matchesStatus && matchesYear;
            item.classList.toggle('d-none', !shouldShow);
            if (shouldShow) visible++;
        });

        if (filteredEmpty) {
            filteredEmpty.classList.toggle('d-none', visible !== 0);
        }
    }

    searchInput?.addEventListener('input', applyFilters);
    statusFilter?.addEventListener('change', applyFilters);
    yearFilter?.addEventListener('change', applyFilters);
</script>
