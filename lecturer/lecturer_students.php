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

$lecturerId     = (int) ($_SESSION['user_id'] ?? 0);
$lecturerName   = trim((string) ($_SESSION['user_name'] ?? 'Lecturer'));
$lecturerInitials = implode('', array_slice(array_map(
    static fn($part) => strtoupper(substr($part, 0, 1)),
    array_filter(preg_split('/\s+/', $lecturerName) ?: [])
), 0, 2)) ?: 'L';

$students = [];
$flashMessage = '';
$flashType    = 'success';

try {
    // Fetch all unique students assigned to the lecturer's projects
    $stmt = $db->prepare(
        "SELECT DISTINCT
             u.user_id,
             u.name_encrypted,
             s.matric_no,
             s.course,
             s.intake,
             p.project_id,
             p.title_encrypted,
             p.study_year,
             sub.status,
             sub.submitted_at
         FROM project_members pm
         INNER JOIN users u ON u.user_id = pm.user_id
         INNER JOIN students s ON s.user_id = u.user_id
         INNER JOIN projects p ON p.project_id = pm.project_id AND p.lecturer_id = ?
         LEFT JOIN submissions sub ON sub.submission_id = (
             SELECT submission_id FROM submissions
             WHERE project_id = pm.project_id
             ORDER BY submitted_at DESC
             LIMIT 1
         )
         ORDER BY u.user_id ASC, p.project_id ASC"
    );
    $stmt->execute([$lecturerId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by student
    $studentMap = [];
    foreach ($rows as $row) {
        $uid = (int) $row['user_id'];
        if (!isset($studentMap[$uid])) {
            $studentMap[$uid] = [
                'id'       => $uid,
                'name'     => decryptValue($row['name_encrypted'] ?? '') ?: 'Unknown',
                'matric'   => (string) ($row['matric_no'] ?? ''),
                'course'   => (string) ($row['course'] ?? ''),
                'intake'   => (string) ($row['intake'] ?? ''),
                'projects' => [],
            ];
        }
        $studentMap[$uid]['projects'][] = [
            'project_id'   => (int) $row['project_id'],
            'title'        => decryptValue($row['title_encrypted'] ?? '') ?: 'No data available',
            'study_year'   => $row['study_year'] ?? '',
            'status'       => $row['status'] ?: 'pending',
            'submitted_at' => $row['submitted_at'] ?? null,
        ];
    }
    $students = array_values($studentMap);
} catch (Throwable $error) {
    $flashMessage = 'Unable to load student data.';
    $flashType    = 'danger';
}

$totalStudents  = count($students);
$statusLabels   = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];
?>
<?php
$lecturerHeaderSkipDashboardData = true;
require_once __DIR__ . '/lecturer_header.php';
?>
        <div class="content">
            <?php if ($flashMessage): ?>
                <div class="alert alert-<?= e($flashType) ?> alert-dismissible fade show" role="alert">
                    <?= e($flashMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <section class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1" style="color:var(--lecturer-maroon);">Assigned Students</h1>
                    <p class="text-muted mb-0">Track student progress and submission status</p>
                </div>
            </section>

            <!-- Stats -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted fw-semibold mb-2">Total Students</p>
                                <strong class="stat-value"><?= e($totalStudents) ?></strong>
                            </div>
                            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted fw-semibold mb-2">With Pending Work</p>
                                <?php
                                $pendingCount = count(array_filter($students, static function($s) {
                                    foreach ($s['projects'] as $p) {
                                        if ($p['status'] === 'pending') return true;
                                    }
                                    return false;
                                }));
                                ?>
                                <strong class="stat-value text-warning"><?= e($pendingCount) ?></strong>
                            </div>
                            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted fw-semibold mb-2">All Approved</p>
                                <?php
                                $allApproved = count(array_filter($students, static function($s) {
                                    foreach ($s['projects'] as $p) {
                                        if ($p['status'] !== 'approved') return false;
                                    }
                                    return !empty($s['projects']);
                                }));
                                ?>
                                <strong class="stat-value text-success"><?= e($allApproved) ?></strong>
                            </div>
                            <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted fw-semibold mb-2">With Rejections</p>
                                <?php
                                $rejectedCount = count(array_filter($students, static function($s) {
                                    foreach ($s['projects'] as $p) {
                                        if ($p['status'] === 'rejected') return true;
                                    }
                                    return false;
                                }));
                                ?>
                                <strong class="stat-value text-danger"><?= e($rejectedCount) ?></strong>
                            </div>
                            <div class="stat-icon"><i class="bi bi-x-circle-fill"></i></div>
                        </div>
                    </article>
                </div>
            </div>

            <!-- Search & Filter -->
            <section class="toolbar mb-4">
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-lg">
                        <div class="input-group">
                            <span class="input-group-text search-control bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input id="studentSearch" class="form-control search-control border-start-0"
                                   type="search" placeholder="Search by name, matric no or course…">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <select id="statusFilter" class="form-select search-control">
                            <option value="all">All Status</option>
                            <option value="pending">Has Pending</option>
                            <option value="approved">All Approved</option>
                            <option value="rejected">Has Rejection</option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- Student Cards + Progress -->
            <?php if (!$students): ?>
                <div class="empty-state">
                    <div>
                        <i class="bi bi-people fs-2 d-block mb-2"></i>
                        No students assigned to your projects yet.
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4" id="studentGrid">
                    <?php foreach ($students as $student): ?>
                        <?php
                        $statuses = array_column($student['projects'], 'status');
                        $hasRejected = in_array('rejected', $statuses, true);
                        $hasPending  = in_array('pending',  $statuses, true);
                        $allOk       = !$hasPending && !$hasRejected && !empty($statuses);
                        $cardStatus  = $hasRejected ? 'rejected' : ($hasPending ? 'pending' : 'approved');
                        $searchText  = strtolower($student['name'] . ' ' . $student['matric'] . ' ' . $student['course']);
                        ?>
                        <div class="col-12 col-lg-6 student-item"
                             data-status="<?= e($cardStatus) ?>"
                             data-search="<?= e($searchText) ?>">
                            <div class="student-card">
                                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar" style="width:48px;height:48px;font-size:1.1rem;">
                                            <?= e(strtoupper(substr($student['name'], 0, 1))) ?>
                                        </div>
                                        <div>
                                            <div class="student-name"><?= e($student['name']) ?></div>
                                            <div class="student-meta"><?= e($student['matric']) ?> · <?= e($student['course']) ?></div>
                                        </div>
                                    </div>
                                    <span class="status-badge status-<?= e($cardStatus) ?>">
                                        <?= $allOk ? 'On Track' : ($hasPending ? 'Pending' : 'Issues') ?>
                                    </span>
                                </div>
                                <div class="student-meta mb-3">
                                    <strong>Intake:</strong> <?= e($student['intake']) ?><br>
                                    <strong>Projects:</strong> <?= e(count($student['projects'])) ?>
                                </div>

                                <!-- Progress Table -->
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0 progress-table">
                                        <thead>
                                            <tr>
                                                <th>Project</th>
                                                <th>Year</th>
                                                <th>Status</th>
                                                <th>Submitted</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($student['projects'] as $proj): ?>
                                                <tr>
                                                    <td class="fw-semibold" style="max-width:180px;">
                                                        <span title="<?= e($proj['title']) ?>"><?= e(mb_strimwidth($proj['title'], 0, 30, '…')) ?></span>
                                                    </td>
                                                    <td><?= $proj['study_year'] !== '' ? 'Year ' . e($proj['study_year']) : '—' ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?= e($proj['status']) ?>">
                                                            <?= e($statusLabels[$proj['status']] ?? $proj['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-muted" style="font-size:.8rem;">
                                                        <?= $proj['submitted_at'] ? e(date('d/m/Y', strtotime((string)$proj['submitted_at']))) : '—' ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="empty-state d-none mt-4" id="filteredEmpty">No students found matching your filters.</div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const searchInput  = document.getElementById('studentSearch');
    const statusFilter = document.getElementById('statusFilter');
    const items        = Array.from(document.querySelectorAll('.student-item'));
    const emptyDiv     = document.getElementById('filteredEmpty');

    function applyFilters() {
        const query  = (searchInput?.value || '').trim().toLowerCase();
        const status = statusFilter?.value || 'all';
        let visible  = 0;
        items.forEach(item => {
            const matchSearch = !query || item.dataset.search.includes(query);
            const matchStatus = status === 'all' || item.dataset.status === status;
            const show = matchSearch && matchStatus;
            item.classList.toggle('d-none', !show);
            if (show) visible++;
        });
        emptyDiv?.classList.toggle('d-none', visible !== 0);
    }

    searchInput?.addEventListener('input', applyFilters);
    statusFilter?.addEventListener('change', applyFilters);
</script>
</body>
</html>
