<?php
// Temporarily enable full error reporting to diagnose HTTP 500 issues.
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../php-error.log');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/encryption.php';

function adminProjectDecrypt(?string $value): string
{
    if ($value === null || $value === '') {
        return '';
    }

    try {
        return decryptData($value);
    } catch (Throwable $error) {
        return 'Unable to decrypt';
    }
}

function adminProjectCode(int $projectId): string
{
    return 'UTM-FYP-' . str_pad((string) $projectId, 4, '0', STR_PAD_LEFT);
}

function adminProjectStudyYear(?int $studyYear): ?int
{
    return $studyYear !== null && $studyYear >= 1 && $studyYear <= 5 ? $studyYear : null;
}

function adminProjectAssertLecturer(PDO $db, int $lecturerId): void
{
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE user_id = ? AND role = 'lecturer'");
    $stmt->execute([$lecturerId]);

    if ((int) $stmt->fetchColumn() === 0) {
        throw new RuntimeException('Selected lecturer was not found.');
    }
}

function adminProjectValidStudentIds(PDO $db, array $studentIds): array
{
    $studentIds = array_values(array_unique(array_filter(array_map('intval', $studentIds))));

    if (empty($studentIds)) {
        throw new RuntimeException('Please choose at least one student.');
    }

    $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
    $stmt = $db->prepare("SELECT user_id FROM users WHERE role = 'student' AND user_id IN ($placeholders)");
    $stmt->execute($studentIds);
    $validStudentIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    sort($studentIds);
    sort($validStudentIds);

    if ($validStudentIds !== $studentIds) {
        throw new RuntimeException('One or more selected students were not found.');
    }

    return $validStudentIds;
}

function adminProjectSyncMembers(PDO $db, int $projectId, int $lecturerId, array $studentIds): void
{
    $deleteStmt = $db->prepare('DELETE FROM project_members WHERE project_id = ?');
    $deleteStmt->execute([$projectId]);

    $memberStmt = $db->prepare('INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)');
    $memberStmt->execute([$projectId, $lecturerId, 'lecturer']);

    foreach ($studentIds as $studentId) {
        $memberStmt->execute([$projectId, $studentId, 'student']);
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['admin_action'] ?? '';

    if ($action === 'create_project') {
        $lecturerId = (int) ($_POST['lecturer_id'] ?? 0);
        $studentIds = $_POST['student_ids'] ?? [];
        $studyYearValue = adminProjectStudyYear((int) ($_POST['study_year'] ?? 0));
        $projectTitle = trim((string) ($_POST['title'] ?? ''));
        $projectDescription = trim((string) ($_POST['description'] ?? ''));

        if ($lecturerId <= 0) {
            $_SESSION['admin_flash'] = 'Please choose a lecturer and at least one student.';
            $_SESSION['admin_flash_type'] = 'danger';
            header('Location: admin_projects.php');
            exit;
        }

        try {
            $db->beginTransaction();

            adminProjectAssertLecturer($db, $lecturerId);
            $validStudentIds = adminProjectValidStudentIds($db, $studentIds);

            $stmt = $db->prepare(
                'INSERT INTO projects (title_encrypted, description_encrypted, lecturer_id, study_year) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([
                encryptData('New Project'),
                encryptData($projectDescription ?: 'Project details can be renamed by the lecturer or student later.'),
                $lecturerId,
                $studyYearValue,
            ]);

            $projectId = (int) $db->lastInsertId();
            $projectTitle = $projectTitle !== ''
                ? $projectTitle
                : 'Project #' . str_pad((string) $projectId, 4, '0', STR_PAD_LEFT);

            $stmt = $db->prepare('UPDATE projects SET title_encrypted = ? WHERE project_id = ?');
            $stmt->execute([encryptData($projectTitle), $projectId]);

            adminProjectSyncMembers($db, $projectId, $lecturerId, $validStudentIds);

            $db->commit();
            $_SESSION['admin_flash'] = adminProjectCode($projectId) . ' created successfully.';
            $_SESSION['admin_flash_type'] = 'success';
        } catch (Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $_SESSION['admin_flash'] = 'Error creating project: ' . $error->getMessage();
            $_SESSION['admin_flash_type'] = 'danger';
        }

        header('Location: admin_projects.php');
        exit;
    }

    if ($action === 'edit_project') {
        $projectId = (int) ($_POST['project_id'] ?? 0);
        $lecturerId = (int) ($_POST['lecturer_id'] ?? 0);
        $studentIds = $_POST['student_ids'] ?? [];
        $studyYearValue = adminProjectStudyYear((int) ($_POST['study_year'] ?? 0));
        $projectTitle = trim((string) ($_POST['title'] ?? ''));
        $projectDescription = trim((string) ($_POST['description'] ?? ''));

        if ($projectId <= 0 || $lecturerId <= 0 || $projectTitle === '') {
            $_SESSION['admin_flash'] = 'Project title, lecturer, and students are required.';
            $_SESSION['admin_flash_type'] = 'danger';
            header('Location: admin_projects.php');
            exit;
        }

        try {
            $db->beginTransaction();

            $stmt = $db->prepare('SELECT COUNT(*) FROM projects WHERE project_id = ?');
            $stmt->execute([$projectId]);
            if ((int) $stmt->fetchColumn() === 0) {
                throw new RuntimeException('Project was not found.');
            }

            adminProjectAssertLecturer($db, $lecturerId);
            $validStudentIds = adminProjectValidStudentIds($db, $studentIds);

            $stmt = $db->prepare(
                'UPDATE projects SET title_encrypted = ?, description_encrypted = ?, lecturer_id = ?, study_year = ? WHERE project_id = ?'
            );
            $stmt->execute([
                encryptData($projectTitle),
                encryptData($projectDescription),
                $lecturerId,
                $studyYearValue,
                $projectId,
            ]);

            adminProjectSyncMembers($db, $projectId, $lecturerId, $validStudentIds);

            $db->commit();
            $_SESSION['admin_flash'] = adminProjectCode($projectId) . ' updated successfully.';
            $_SESSION['admin_flash_type'] = 'success';
        } catch (Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            $_SESSION['admin_flash'] = 'Error updating project: ' . $error->getMessage();
            $_SESSION['admin_flash_type'] = 'danger';
        }

        header('Location: admin_projects.php');
        exit;
    }

    if ($action === 'delete_project') {
        $projectId = (int) ($_POST['project_id'] ?? 0);

        if ($projectId > 0) {
            try {
                $db->beginTransaction();

                $stmt = $db->prepare('DELETE FROM file_versions WHERE file_id IN (SELECT file_id FROM files WHERE project_id = ?)');
                $stmt->execute([$projectId]);

                foreach (['files', 'comments', 'submissions', 'project_members'] as $table) {
                    $stmt = $db->prepare("DELETE FROM {$table} WHERE project_id = ?");
                    $stmt->execute([$projectId]);
                }

                $stmt = $db->prepare('DELETE FROM projects WHERE project_id = ?');
                $stmt->execute([$projectId]);

                $db->commit();
                $_SESSION['admin_flash'] = adminProjectCode($projectId) . ' deleted successfully.';
                $_SESSION['admin_flash_type'] = 'success';
            } catch (Throwable $error) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                $_SESSION['admin_flash'] = 'Error deleting project: ' . $error->getMessage();
                $_SESSION['admin_flash_type'] = 'danger';
            }
        }

        header('Location: admin_projects.php');
        exit;
    }
}

$lecturerRows = $db->query(
    "SELECT u.user_id, u.name_encrypted, u.email_encrypted, l.staff_id, l.department
     FROM users u
     INNER JOIN lecturers l ON l.user_id = u.user_id
     WHERE u.role = 'lecturer'
     ORDER BY u.created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$studentRows = $db->query(
    "SELECT u.user_id, u.name_encrypted, u.email_encrypted, s.matric_no, s.course
     FROM users u
     INNER JOIN students s ON s.user_id = u.user_id
     WHERE u.role = 'student'
     ORDER BY u.created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);

foreach ($lecturerRows as &$lecturer) {
    $lecturer['name'] = adminProjectDecrypt($lecturer['name_encrypted'] ?? '');
    $lecturer['email'] = adminProjectDecrypt($lecturer['email_encrypted'] ?? '');
}
unset($lecturer);

foreach ($studentRows as &$student) {
    $student['name'] = adminProjectDecrypt($student['name_encrypted'] ?? '');
    $student['email'] = adminProjectDecrypt($student['email_encrypted'] ?? '');
}
unset($student);

$projectRows = $db->query(
    "SELECT p.project_id, p.title_encrypted, p.description_encrypted, p.lecturer_id, p.study_year, p.created_at,
            lu.name_encrypted AS lecturer_name_encrypted,
            lu.email_encrypted AS lecturer_email_encrypted,
            l.staff_id AS lecturer_staff_id,
            COALESCE(s.latest_status, 'pending') AS latest_status
     FROM projects p
     LEFT JOIN users lu ON lu.user_id = p.lecturer_id
     LEFT JOIN lecturers l ON l.user_id = lu.user_id
     LEFT JOIN (
         SELECT project_id, status AS latest_status
         FROM submissions
         WHERE submission_id IN (
             SELECT MAX(submission_id)
             FROM submissions
             GROUP BY project_id
         )
     ) s ON s.project_id = p.project_id
     ORDER BY p.created_at DESC, p.project_id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$memberStmt = $db->prepare(
    "SELECT pm.role, u.user_id, u.name_encrypted, u.email_encrypted, st.matric_no
     FROM project_members pm
     INNER JOIN users u ON u.user_id = pm.user_id
     LEFT JOIN students st ON st.user_id = u.user_id
     WHERE pm.project_id = ?
     ORDER BY CASE pm.role WHEN 'lecturer' THEN 0 ELSE 1 END, u.user_id ASC"
);

$projects = [];
foreach ($projectRows as $row) {
    $projectId = (int) $row['project_id'];
    $memberStmt->execute([$projectId]);
    $members = [];

    foreach ($memberStmt->fetchAll(PDO::FETCH_ASSOC) as $member) {
        $members[] = [
            'user_id' => (int) $member['user_id'],
            'role' => $member['role'],
            'name' => adminProjectDecrypt($member['name_encrypted'] ?? ''),
            'email' => adminProjectDecrypt($member['email_encrypted'] ?? ''),
            'matric_no' => $member['matric_no'] ?? '',
        ];
    }

    $projects[] = [
        'id' => $projectId,
        'code' => adminProjectCode($projectId),
        'title' => adminProjectDecrypt($row['title_encrypted'] ?? '') ?: adminProjectCode($projectId),
        'description' => adminProjectDecrypt($row['description_encrypted'] ?? ''),
        'lecturer_id' => (int) ($row['lecturer_id'] ?? 0),
        'lecturer_name' => adminProjectDecrypt($row['lecturer_name_encrypted'] ?? ''),
        'lecturer_email' => adminProjectDecrypt($row['lecturer_email_encrypted'] ?? ''),
        'lecturer_staff_id' => $row['lecturer_staff_id'] ?? '',
        'study_year' => $row['study_year'] ?? '',
        'created_at' => $row['created_at'],
        'status' => $row['latest_status'] ?? 'pending',
        'members' => $members,
    ];
}

$adminName = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Admin';
$adminInitial = strtoupper(substr($adminName, 0, 1)) ?: 'A';
$adminFlash = $_SESSION['admin_flash'] ?? '';
$adminFlashType = $_SESSION['admin_flash_type'] ?? 'info';
unset($_SESSION['admin_flash'], $_SESSION['admin_flash_type']);

require __DIR__ . '/admin_header.php';
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
                <button class="icon-button" type="button" aria-label="Notifications">
                    <i class="bi bi-bell"></i>
                </button>
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
                    <h1 class="h3 fw-bold mb-1">Manage Projects</h1>
                    <p class="text-muted mb-0">View every project and assign lecturers and students.</p>
                </div>
                <button class="btn btn-warning fw-bold" type="button" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                    <i class="bi bi-folder-plus me-1"></i>
                    Add Project
                </button>
            </div>

            <div class="dashboard-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Project</th>
                                <th>Lecturer</th>
                                <th>Students</th>
                                <th>Study Year</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($projects)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No projects found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($projects as $project): ?>
                                    <?php
                                    $studentMembers = array_filter(
                                        $project['members'],
                                        static fn($member) => $member['role'] === 'student'
                                    );
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= h($project['title']) ?></div>
                                            <small class="text-muted"><?= h($project['code']) ?></small>
                                        </td>
                                        <td>
                                            <?php if ($project['lecturer_name']): ?>
                                                <div class="fw-semibold"><?= h($project['lecturer_name']) ?></div>
                                                <small class="text-muted"><?= h($project['lecturer_staff_id'] ?: $project['lecturer_email']) ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">No lecturer assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (empty($studentMembers)): ?>
                                                <span class="text-muted">No students assigned</span>
                                            <?php else: ?>
                                                <div class="d-flex flex-column gap-1">
                                                    <?php foreach ($studentMembers as $student): ?>
                                                        <span>
                                                            <?= h($student['name']) ?>
                                                            <?php if ($student['matric_no']): ?>
                                                                <small class="text-muted">(<?= h($student['matric_no']) ?>)</small>
                                                            <?php endif; ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $project['study_year'] !== '' ? h($project['study_year']) : '<span class="text-muted">Not set</span>' ?></td>
                                        <td><span class="status-badge"><?= h(ucfirst((string) $project['status'])) ?></span></td>
                                        <td><?= h(date('M j, Y', strtotime((string) $project['created_at']))) ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary me-1" type="button" data-bs-toggle="modal" data-bs-target="#editProjectModal<?= $project['id'] ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteProjectModal<?= $project['id'] ?>">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="addProjectModal" tabindex="-1" aria-labelledby="addProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" method="post">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5 fw-bold" id="addProjectModalLabel">Add Project</h2>
                    <p class="text-muted small mb-0">Project title is created automatically and can be renamed later.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="admin_action" value="create_project">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="projectTitle">Project Name</label>
                        <input class="form-control" id="projectTitle" name="title" placeholder="Auto: Project #0001">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="projectStudyYear">Study Year</label>
                        <select class="form-select" id="projectStudyYear" name="study_year">
                            <option value="">Not set</option>
                            <?php for ($year = 1; $year <= 5; $year++): ?>
                                <option value="<?= $year ?>">Year <?= $year ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="projectLecturer">Lecturer</label>
                        <select class="form-select" id="projectLecturer" name="lecturer_id" required>
                            <option value="">Choose lecturer</option>
                            <?php foreach ($lecturerRows as $lecturer): ?>
                                <option value="<?= h($lecturer['user_id']) ?>">
                                    <?= h($lecturer['name']) ?> - <?= h($lecturer['staff_id']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="projectStudents">Students</label>
                        <select class="form-select" id="projectStudents" name="student_ids[]" size="8" multiple required>
                            <?php foreach ($studentRows as $student): ?>
                                <option value="<?= h($student['user_id']) ?>">
                                    <?= h($student['name']) ?> - <?= h($student['matric_no']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Hold Ctrl or Cmd to select more than one student.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="projectDescription">Description</label>
                        <textarea class="form-control" id="projectDescription" name="description" rows="3" placeholder="Optional notes"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning fw-bold">Create Project</button>
            </div>
        </form>
    </div>
</div>

<?php foreach ($projects as $project): ?>
<?php
$selectedStudentIds = array_map(
    static fn($member) => (int) $member['user_id'],
    array_filter($project['members'], static fn($member) => $member['role'] === 'student')
);
?>
<div class="modal fade" id="editProjectModal<?= $project['id'] ?>" tabindex="-1" aria-labelledby="editProjectModalLabel<?= $project['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" method="post">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5 fw-bold" id="editProjectModalLabel<?= $project['id'] ?>">Edit Project</h2>
                    <p class="text-muted small mb-0"><?= h($project['code']) ?></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="admin_action" value="edit_project">
                <input type="hidden" name="project_id" value="<?= h($project['id']) ?>">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="editProjectTitle<?= $project['id'] ?>">Project Name</label>
                        <input class="form-control" id="editProjectTitle<?= $project['id'] ?>" name="title" value="<?= h($project['title']) ?>" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="editProjectStudyYear<?= $project['id'] ?>">Study Year</label>
                        <select class="form-select" id="editProjectStudyYear<?= $project['id'] ?>" name="study_year">
                            <option value="">Not set</option>
                            <?php for ($year = 1; $year <= 5; $year++): ?>
                                <option value="<?= $year ?>" <?= (string) $project['study_year'] === (string) $year ? 'selected' : '' ?>>Year <?= $year ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="editProjectLecturer<?= $project['id'] ?>">Lecturer</label>
                        <select class="form-select" id="editProjectLecturer<?= $project['id'] ?>" name="lecturer_id" required>
                            <option value="">Choose lecturer</option>
                            <?php foreach ($lecturerRows as $lecturer): ?>
                                <option value="<?= h($lecturer['user_id']) ?>" <?= (int) $project['lecturer_id'] === (int) $lecturer['user_id'] ? 'selected' : '' ?>>
                                    <?= h($lecturer['name']) ?> - <?= h($lecturer['staff_id']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="editProjectStudents<?= $project['id'] ?>">Students</label>
                        <select class="form-select" id="editProjectStudents<?= $project['id'] ?>" name="student_ids[]" size="8" multiple required>
                            <?php foreach ($studentRows as $student): ?>
                                <option value="<?= h($student['user_id']) ?>" <?= in_array((int) $student['user_id'], $selectedStudentIds, true) ? 'selected' : '' ?>>
                                    <?= h($student['name']) ?> - <?= h($student['matric_no']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Hold Ctrl or Cmd to select more than one student.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="editProjectDescription<?= $project['id'] ?>">Description</label>
                        <textarea class="form-control" id="editProjectDescription<?= $project['id'] ?>" name="description" rows="3"><?= h($project['description']) ?></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning fw-bold">Update Project</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="deleteProjectModal<?= $project['id'] ?>" tabindex="-1" aria-labelledby="deleteProjectModalLabel<?= $project['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5 fw-bold" id="deleteProjectModalLabel<?= $project['id'] ?>">Confirm Delete</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong><?= h($project['title']) ?></strong>?</p>
                <div class="alert alert-warning">
                    <strong>Warning:</strong> This will remove the project, members, submissions, comments, and file records.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger fw-bold" data-bs-toggle="modal" data-bs-target="#confirmDeleteProjectModal<?= $project['id'] ?>" data-bs-dismiss="modal">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmDeleteProjectModal<?= $project['id'] ?>" tabindex="-1" aria-labelledby="confirmDeleteProjectModalLabel<?= $project['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5 fw-bold" id="confirmDeleteProjectModalLabel<?= $project['id'] ?>">Final Confirmation</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Please confirm once more that you want to delete <strong><?= h($project['title']) ?></strong>.</p>
                <p class="text-danger fw-bold">This action is irreversible!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="post" class="d-inline">
                    <input type="hidden" name="admin_action" value="delete_project">
                    <input type="hidden" name="project_id" value="<?= h($project['id']) ?>">
                    <button type="submit" class="btn btn-danger fw-bold">Yes, Permanently Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
