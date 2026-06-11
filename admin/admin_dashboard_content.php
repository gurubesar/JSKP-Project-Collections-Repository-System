<?php
function dbDriver(PDO $db): string
{
    return (string) $db->getAttribute(PDO::ATTR_DRIVER_NAME);
}

function tableExists(PDO $db, string $table): bool
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

    if (dbDriver($db) === 'pgsql') {
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = 'public' AND table_name = ?"
        );
        $stmt->execute([$table]);
        return (int) $stmt->fetchColumn() > 0;
    }

    if (dbDriver($db) === 'mysql') {
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $stmt->execute([$table]);
        return (int) $stmt->fetchColumn() > 0;
    }

    $stmt = $db->prepare("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = ?");
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
}

function columnExists(PDO $db, string $table, string $column): bool
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

    if (!tableExists($db, $table)) {
        return false;
    }

    if (dbDriver($db) === 'pgsql') {
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = 'public' AND table_name = ? AND column_name = ?"
        );
        $stmt->execute([$table, $column]);
        return (int) $stmt->fetchColumn() > 0;
    }

    if (dbDriver($db) === 'mysql') {
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $stmt->execute([$table, $column]);
        return (int) $stmt->fetchColumn() > 0;
    }

    $stmt = $db->query("PRAGMA table_info({$table})");
    foreach ($stmt as $row) {
        if (($row['name'] ?? '') === $column) {
            return true;
        }
    }

    return false;
}

function firstExistingColumn(PDO $db, string $table, array $columns): ?string
{
    foreach ($columns as $column) {
        if (columnExists($db, $table, $column)) {
            return $column;
        }
    }

    return null;
}

function fetchScalar(PDO $db, string $sql): float
{
    try {
        $value = $db->query($sql)->fetchColumn();
        return $value === false || $value === null ? 0 : (float) $value;
    } catch (PDOException $e) {
        return 0;
    }
}

function countTable(PDO $db, string $table): int
{
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

    if (!tableExists($db, $safeTable)) {
        return 0;
    }

    return (int) fetchScalar($db, "SELECT COUNT(*) FROM {$safeTable}");
}

function countUsersByRole(PDO $db, string $role): int
{
    if (!tableExists($db, 'users') || !columnExists($db, 'users', 'role')) {
        return 0;
    }

    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE role = ?");
    $stmt->execute([$role]);
    return (int) $stmt->fetchColumn();
}

function countEntity(PDO $db, string $table, ?string $fallbackRole = null): int
{
    if (tableExists($db, $table)) {
        return countTable($db, $table);
    }

    return $fallbackRole ? countUsersByRole($db, $fallbackRole) : 0;
}

function sumColumn(PDO $db, string $table, string $column): float
{
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

    if (!columnExists($db, $safeTable, $safeColumn)) {
        return 0;
    }

    return fetchScalar($db, "SELECT COALESCE(SUM({$safeColumn}), 0) FROM {$safeTable}");
}

function fetchRows(PDO $db, string $sql): array
{
    try {
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function rowValue(array $row, array $keys, string $fallback = ''): string
{
    foreach ($keys as $key) {
        if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
            return (string) $row[$key];
        }
    }

    return $fallback;
}

function initialsFromName(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = array_map(static fn($part) => strtoupper(substr($part, 0, 1)), array_filter($parts));
    return implode('', array_slice($letters, 0, 2)) ?: 'ST';
}

function createRoleUser(PDO $db, string $role, array $data, int $adminId): void
{
    $name = trim((string) ($data['name'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $password = (string) ($data['password'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        throw new InvalidArgumentException('Name, email, and password are required.');
    }

    $db->beginTransaction();

    try {
        $insertUser = $db->prepare(
            'INSERT INTO users (name_encrypted, email_hash, email_encrypted, password_hash, role, created_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $insertUser->execute([
            encryptData($name),
            hashEmail($email),
            encryptData($email),
            password_hash($password, PASSWORD_BCRYPT),
            $role,
            $adminId ?: null,
        ]);

        $userId = (int) $db->lastInsertId();
        if (dbDriver($db) === 'pgsql') {
            $userId = (int) $db->query("SELECT currval(pg_get_serial_sequence('users', 'user_id'))")->fetchColumn();
        }

        if ($role === 'student') {
            $matricNo = trim((string) ($data['matric_no'] ?? ''));
            $course = trim((string) ($data['course'] ?? ''));
            $intake = trim((string) ($data['intake'] ?? ''));

            if ($matricNo === '' || $course === '' || $intake === '') {
                throw new InvalidArgumentException('Matric number, course, and intake are required.');
            }

            $insertStudent = $db->prepare(
                'INSERT INTO students (user_id, matric_no, course, intake) VALUES (?, ?, ?, ?)'
            );
            $insertStudent->execute([$userId, $matricNo, $course, $intake]);
        } elseif ($role === 'lecturer') {
            $staffId = trim((string) ($data['staff_id'] ?? ''));
            $department = trim((string) ($data['department'] ?? ''));

            if ($staffId === '' || $department === '') {
                throw new InvalidArgumentException('Staff ID and department are required.');
            }

            $insertLecturer = $db->prepare(
                'INSERT INTO lecturers (user_id, staff_id, department) VALUES (?, ?, ?)'
            );
            $insertLecturer->execute([$userId, $staffId, $department]);
        }

        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        throw $error;
    }
}

function monthlyStudentCounts(PDO $db): array
{
    $labels = [];
    $values = [];
    $now = new DateTimeImmutable('first day of this month');

    for ($i = 5; $i >= 0; $i--) {
        $month = $now->modify("-{$i} months");
        $labels[] = $month->format('M');
        $values[$month->format('Y-m')] = 0;
    }

    $sourceTable = tableExists($db, 'students') && firstExistingColumn($db, 'students', ['created_at', 'registered_at', 'enrolled_at', 'updated_at'])
        ? 'students'
        : 'users';
    $dateColumn = firstExistingColumn($db, $sourceTable, ['created_at', 'registered_at', 'enrolled_at', 'updated_at']);
    if (!$dateColumn) {
        return [$labels, array_values($values)];
    }

    if (dbDriver($db) === 'pgsql') {
        $monthExpression = "TO_CHAR({$dateColumn}, 'YYYY-MM')";
    } elseif (dbDriver($db) === 'mysql') {
        $monthExpression = "DATE_FORMAT({$dateColumn}, '%Y-%m')";
    } else {
        $monthExpression = "strftime('%Y-%m', {$dateColumn})";
    }

    $rows = fetchRows(
        $db,
        "SELECT {$monthExpression} AS month_key, COUNT(*) AS total
         FROM {$sourceTable}
         WHERE {$dateColumn} IS NOT NULL
         " . ($sourceTable === 'users' ? "AND role = 'student'" : '') . "
         GROUP BY month_key
         ORDER BY month_key ASC"
    );

    foreach ($rows as $row) {
        $key = (string) ($row['month_key'] ?? '');
        if (array_key_exists($key, $values)) {
            $values[$key] = (int) $row['total'];
        }
    }

    return [$labels, array_values($values)];
}

function weeklyCounts(PDO $db, string $table, array $dateColumns): array
{
    $counts = array_fill(0, 7, 0);
    $roleFilter = '';

    if ($table === 'lecturers' && (!tableExists($db, $table) || !firstExistingColumn($db, $table, $dateColumns)) && tableExists($db, 'users')) {
        $table = 'users';
        $roleFilter = " AND role = 'lecturer'";
    }

    $dateColumn = firstExistingColumn($db, $table, $dateColumns);

    if (!$dateColumn) {
        return $counts;
    }

    if (dbDriver($db) === 'pgsql') {
        $dayExpression = "(EXTRACT(ISODOW FROM {$dateColumn})::int - 1)";
    } elseif (dbDriver($db) === 'mysql') {
        $dayExpression = "WEEKDAY({$dateColumn})";
    } else {
        $dayExpression = "(CAST(strftime('%w', {$dateColumn}) AS INTEGER) + 6) % 7";
    }

    $rows = fetchRows(
        $db,
        "SELECT {$dayExpression} AS day_index, COUNT(*) AS total
         FROM {$table}
         WHERE {$dateColumn} IS NOT NULL
         {$roleFilter}
         GROUP BY day_index"
    );

    foreach ($rows as $row) {
        $day = (int) ($row['day_index'] ?? -1);
        if ($day >= 0 && $day <= 6) {
            $counts[$day] = (int) $row['total'];
        }
    }

    return $counts;
}

function recentStudents(PDO $db): array
{
    if (tableExists($db, 'students')) {
        $rows = fetchRows(
            $db,
            "SELECT s.student_id, s.matric_no, s.course, s.intake, u.name_encrypted, u.created_at
             FROM students s
             INNER JOIN users u ON u.user_id = s.user_id
             ORDER BY s.student_id DESC
             LIMIT 5"
        );

        return array_map(static function (array $row): array {
            return [
                'name' => isset($row['name_encrypted']) ? decryptData($row['name_encrypted']) : '',
                'student_id' => $row['matric_no'] ?? '',
                'faculty' => '',
                'program' => $row['course'] ?? '',
                'intake' => $row['intake'] ?? '',
                'status' => 'Active',
            ];
        }, $rows);
    }

    if (!tableExists($db, 'users') || !columnExists($db, 'users', 'role')) {
        return [];
    }

    $orderColumn = firstExistingColumn($db, 'users', ['created_at', 'user_id']) ?: 'user_id';
    $rows = fetchRows($db, "SELECT * FROM users WHERE role = 'student' ORDER BY {$orderColumn} DESC LIMIT 5");

    return array_map(static function (array $row): array {
        $name = isset($row['name_encrypted']) ? decryptData($row['name_encrypted']) : '';

        return [
            'name' => $name,
            'student_id' => $row['user_id'] ?? '',
            'faculty' => '',
            'program' => '',
            'intake' => '',
            'status' => 'Active',
        ];
    }, $rows);
}

function dashboardProjectSummaries(PDO $db): array
{
    if (!tableExists($db, 'projects')) {
        return [];
    }

    $rows = fetchRows(
        $db,
        "SELECT p.project_id, p.title_encrypted, p.study_year, p.created_at,
                u.name_encrypted AS lecturer_name,
                COUNT(DISTINCT CASE WHEN pm.role = 'student' THEN pm.user_id END) AS student_count,
                COALESCE(s.latest_status, 'pending') AS latest_status
         FROM projects p
         LEFT JOIN users u ON u.user_id = p.lecturer_id
         LEFT JOIN project_members pm ON pm.project_id = p.project_id
         LEFT JOIN (
             SELECT project_id, status AS latest_status
             FROM submissions
             WHERE submission_id IN (
                 SELECT MAX(submission_id)
                 FROM submissions
                 GROUP BY project_id
             )
         ) s ON s.project_id = p.project_id
         GROUP BY p.project_id, p.title_encrypted, p.study_year, p.created_at, u.name_encrypted, s.latest_status
         ORDER BY p.created_at DESC, p.project_id DESC"
    );

    return array_map(static function (array $row): array {
        $projectId = (int) ($row['project_id'] ?? 0);

        return [
            'code' => 'UTM-FYP-' . str_pad((string) $projectId, 4, '0', STR_PAD_LEFT),
            'name' => isset($row['title_encrypted']) ? decryptData($row['title_encrypted']) : '',
            'supervisor' => isset($row['lecturer_name']) ? decryptData($row['lecturer_name']) : '',
            'student_count' => (int) ($row['student_count'] ?? 0),
            'study_year' => $row['study_year'] ?? '',
            'status' => ucfirst((string) ($row['latest_status'] ?? 'pending')),
        ];
    }, $rows);
}

$adminFlash = '';
$adminFlashType = 'success';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $adminAction = $_POST['admin_action'] ?? '';

    try {
        if ($adminAction === 'create_student') {
            createRoleUser($db, 'student', $_POST, (int) ($_SESSION['user_id'] ?? 0));
            $adminFlash = 'Student account created successfully.';
        } elseif ($adminAction === 'create_lecturer') {
            createRoleUser($db, 'lecturer', $_POST, (int) ($_SESSION['user_id'] ?? 0));
            $adminFlash = 'Lecturer account created successfully.';
        }
    } catch (Throwable $error) {
        $adminFlash = $error instanceof InvalidArgumentException
            ? $error->getMessage()
            : 'Unable to create account. Check for duplicate email, matric number, or staff ID.';
        $adminFlashType = 'danger';
    }
}

$totalStudents = countEntity($db, 'students', 'student');
$totalLecturers = countEntity($db, 'lecturers', 'lecturer');
$totalAdmins = countUsersByRole($db, 'admin');
$activeProjects = countTable($db, 'projects');
$totalSubmissions = countTable($db, 'submissions');
$totalRevenue = sumColumn($db, 'payments', 'amount');
$recentStudents = recentStudents($db);
$projectSummaries = dashboardProjectSummaries($db);

[$enrollmentLabels, $enrollmentValues] = monthlyStudentCounts($db);
$weeklyLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$weeklySubmissions = weeklyCounts($db, 'submissions', ['submitted_at', 'created_at', 'updated_at']);
$weeklyProjects = weeklyCounts($db, 'projects', ['created_at', 'updated_at']);
$weeklyLecturers = weeklyCounts($db, 'lecturers', ['created_at', 'joined_at', 'updated_at']);

$stats = [
    ['title' => 'Total Students', 'value' => number_format($totalStudents), 'icon' => 'bi-mortarboard-fill', 'change' => '+2.3%'],
    ['title' => 'Total Lecturers', 'value' => number_format($totalLecturers), 'icon' => 'bi-person-video3', 'change' => '+0.8%'],
    ['title' => 'Total Admins', 'value' => number_format($totalAdmins), 'icon' => 'bi-shield-lock-fill', 'change' => '+0.0%'],
    ['title' => 'Active Projects', 'value' => number_format($activeProjects), 'icon' => 'bi-folder2-open', 'change' => '+5.5%'],
    ['title' => 'Total Submissions', 'value' => number_format($totalSubmissions), 'icon' => 'bi-file-earmark-check-fill', 'change' => '+1.2%'],
    ['title' => 'Total Revenue', 'value' => 'RM ' . number_format($totalRevenue, 2), 'icon' => 'bi-cash-coin', 'change' => '+6.1%'],
];
?>
<div class="admin-shell">
    <?php require __DIR__ . '/admin_sidebar.php'; ?>

    <main>
        <header class="top-navbar d-flex align-items-center justify-content-between px-3 px-lg-4">
            <div class="welcome-text">
                <span class="text-muted">Welcome,</span>
                <strong><?= h($adminName) ?></strong>
            </div>
            <div class="d-flex align-items-center gap-2 gap-sm-3 ms-auto">
                <div class="profile-chip">
                    <div class="profile-avatar"><?= h($adminInitial) ?></div>
                    <div class="d-none d-sm-block pe-1">
                        <div class="fw-bold lh-sm"><?= h($adminName) ?></div>
                        <small class="text-muted">UTM Administrator</small>
                    </div>
                </div>
                <a class="icon-button text-decoration-none" href="../public/logout.php" aria-label="Sign out">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </header>

        <div class="main-content">
            <?php if ($adminFlash): ?>
                <div class="alert alert-<?= h($adminFlashType) ?> alert-dismissible fade show" role="alert">
                    <?= h($adminFlash) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1">Dashboard Overview</h1>
                    <p class="text-muted mb-0">University administration summary from your database.</p>
                </div>
            </div>

            <section class="row g-4 mb-4">
                <?php foreach ($stats as $stat): ?>
                    <div class="col-12 col-sm-6 col-xl">
                        <article class="stat-card">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon">
                                    <i class="bi <?= h($stat['icon']) ?>"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-muted fw-semibold mb-2"><?= h($stat['title']) ?></p>
                                    <div class="d-flex align-items-end gap-2 flex-wrap">
                                        <strong class="stat-value"><?= h($stat['value']) ?></strong>
                                        <span class="metric-change"><?= h($stat['change']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </section>

            <section class="row g-4 mb-4">
                <div class="col-12 col-xl-7">
                    <article class="dashboard-card chart-box">
                        <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                            <div>
                                <h2 class="h5 fw-bold mb-1">Student Enrollment Trends</h2>
                                <p class="text-muted mb-0">Monthly student registrations</p>
                            </div>
                        </div>
                        <?php if (array_sum($enrollmentValues) === 0): ?>
                            <div class="empty-state">No data available</div>
                        <?php else: ?>
                            <div class="chart-frame">
                                <canvas id="enrollmentChart"></canvas>
                            </div>
                        <?php endif; ?>
                    </article>
                </div>
                <div class="col-12 col-xl-5">
                    <article class="dashboard-card chart-box">
                        <div class="mb-3">
                            <h2 class="h5 fw-bold mb-1">Weekly Activities</h2>
                            <p class="text-muted mb-0">Submissions, projects, and lecturer records</p>
                        </div>
                        <?php if (array_sum($weeklySubmissions) + array_sum($weeklyProjects) + array_sum($weeklyLecturers) === 0): ?>
                            <div class="empty-state">No data available</div>
                        <?php else: ?>
                            <div class="chart-frame">
                                <canvas id="activityChart"></canvas>
                            </div>
                        <?php endif; ?>
                    </article>
                </div>
            </section>

            <section class="dashboard-card mb-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Project Summary</h2>
                        <p class="text-muted mb-0">Project names, supervisors, and total assigned students.</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Project Code</th>
                                <th>Project Name</th>
                                <th>Supervisor</th>
                                <th>Study Year</th>
                                <th class="text-end">Total Students</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$projectSummaries): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">No projects available</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($projectSummaries as $project): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= h($project['code']) ?></td>
                                        <td><?= h($project['name'] ?: $project['code']) ?></td>
                                        <td><?= h($project['supervisor'] ?: 'Not assigned') ?></td>
                                        <td><?= $project['study_year'] !== '' ? h($project['study_year']) : '<span class="text-muted">Not set</span>' ?></td>
                                        <td class="text-end fw-semibold"><?= h($project['student_count']) ?></td>
                                        <td><span class="status-badge"><?= h($project['status']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="dashboard-card">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Recent Students</h2>
                        <p class="text-muted mb-0">Latest 5 student records</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Profile Image</th>
                                <th>Name</th>
                                <th>ID Number</th>
                                <th>Faculty</th>
                                <th>Program</th>
                                <th>Intake</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$recentStudents): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">No data available</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentStudents as $student): ?>
                                    <?php
                                    $name = rowValue($student, ['name', 'full_name', 'student_name'], 'No data available');
                                    $profileImage = rowValue($student, ['profile_image', 'profile_photo', 'avatar', 'image']);
                                    $status = rowValue($student, ['status'], 'No data available');
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="student-avatar">
                                                <?php if ($profileImage): ?>
                                                    <img src="<?= h($profileImage) ?>" alt="<?= h($name) ?>">
                                                <?php else: ?>
                                                    <?= h(initialsFromName($name)) ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="fw-semibold"><?= h($name) ?></td>
                                        <td><?= h(rowValue($student, ['id_number', 'matrix_id', 'student_number', 'student_id'], 'No data available')) ?></td>
                                        <td><?= h(rowValue($student, ['faculty'], 'No data available')) ?></td>
                                        <td><?= h(rowValue($student, ['program', 'programme'], 'No data available')) ?></td>
                                        <td><?= h(rowValue($student, ['intake', 'intake_session'], 'No data available')) ?></td>
                                        <td><span class="status-badge"><?= h($status) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const enrollmentLabels = <?= json_encode($enrollmentLabels) ?>;
    const enrollmentValues = <?= json_encode($enrollmentValues) ?>;
    const weeklyLabels = <?= json_encode($weeklyLabels) ?>;
    const weeklySubmissions = <?= json_encode($weeklySubmissions) ?>;
    const weeklyProjects = <?= json_encode($weeklyProjects) ?>;
    const weeklyLecturers = <?= json_encode($weeklyLecturers) ?>;

    const enrollmentCanvas = document.getElementById('enrollmentChart');
    if (enrollmentCanvas) {
        new Chart(enrollmentCanvas, {
            type: 'line',
            data: {
                labels: enrollmentLabels,
                datasets: [{
                    label: 'Students',
                    data: enrollmentValues,
                    borderColor: '#800020',
                    backgroundColor: 'rgba(128, 0, 32, 0.12)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.36,
                    pointRadius: 4,
                    pointBackgroundColor: '#800020'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    const activityCanvas = document.getElementById('activityChart');
    if (activityCanvas) {
        new Chart(activityCanvas, {
            type: 'bar',
            data: {
                labels: weeklyLabels,
                datasets: [
                    { label: 'Submissions', data: weeklySubmissions, backgroundColor: '#800020', borderRadius: 8 },
                    { label: 'Projects', data: weeklyProjects, backgroundColor: '#d6a01d', borderRadius: 8 },
                    { label: 'Lecturers', data: weeklyLecturers, backgroundColor: '#2f7d59', borderRadius: 8 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { boxWidth: 12, usePointStyle: true } } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }
</script>
</body>
</html>
