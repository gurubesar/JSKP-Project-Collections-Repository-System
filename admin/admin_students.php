<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/encryption.php';
require __DIR__ . '/admin_header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['admin_action'])) {
        $action = $_POST['admin_action'];

        if ($action === 'create_student') {
            // Create student logic (similar to dashboard)
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $matric_no = trim($_POST['matric_no'] ?? '');
            $course = trim($_POST['course'] ?? '');
            $intake = trim($_POST['intake'] ?? '');

            if ($name && $email && $password && $matric_no && $course && $intake) {
                try {
                    $db->beginTransaction();

                    // Check if email already exists
                    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email_hash = ?");
                    $stmt->execute([hash('sha256', $email)]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('Email already exists');
                    }

                    // Check if matric_no already exists
                    $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE matric_no = ?");
                    $stmt->execute([$matric_no]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('Matric number already exists');
                    }

                    // Insert user
                    $stmt = $db->prepare("INSERT INTO users (name_encrypted, email_hash, email_encrypted, password_hash, role, created_by) VALUES (?, ?, ?, ?, 'student', ?)");
                    $stmt->execute([
                        encryptData($name),
                        hashEmail($email),
                        encryptData($email),
                        password_hash($password, PASSWORD_DEFAULT),
                        $_SESSION['user_id']
                    ]);
                    $user_id = $db->lastInsertId();

                    // Insert student
                    $stmt = $db->prepare("INSERT INTO students (user_id, matric_no, course, intake) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$user_id, $matric_no, $course, $intake]);

                    $db->commit();
                    $_SESSION['admin_flash'] = 'Student created successfully';
                    $_SESSION['admin_flash_type'] = 'success';
                } catch (Exception $e) {
                    $db->rollBack();
                    $_SESSION['admin_flash'] = 'Error creating student: ' . $e->getMessage();
                    $_SESSION['admin_flash_type'] = 'danger';
                }
            } else {
                $_SESSION['admin_flash'] = 'All fields are required';
                $_SESSION['admin_flash_type'] = 'danger';
            }
            header('Location: admin_students.php');
            exit;
        } elseif ($action === 'edit_student') {
            // Edit student logic
            $student_id = (int)($_POST['student_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $matric_no = trim($_POST['matric_no'] ?? '');
            $course = trim($_POST['course'] ?? '');
            $intake = trim($_POST['intake'] ?? '');

            if ($student_id && $name && $email && $matric_no && $course && $intake) {
                try {
                    $db->beginTransaction();

                    // Get current user_id
                    $stmt = $db->prepare("SELECT user_id FROM students WHERE student_id = ?");
                    $stmt->execute([$student_id]);
                    $user_id = $stmt->fetchColumn();

                    if (!$user_id) {
                        throw new Exception('Student not found');
                    }

                    // Check if email already exists for another user
                    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email_hash = ? AND user_id != ?");
                    $stmt->execute([hash('sha256', $email), $user_id]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('Email already exists');
                    }

                    // Check if matric_no already exists for another student
                    $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE matric_no = ? AND student_id != ?");
                    $stmt->execute([$matric_no, $student_id]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('Matric number already exists');
                    }

                    // Update user
                    $stmt = $db->prepare("UPDATE users SET name_encrypted = ?, email_hash = ?, email_encrypted = ? WHERE user_id = ?");
                    $stmt->execute([
                        encryptData($name),
                        hashEmail($email),
                        encryptData($email),
                        $user_id
                    ]);

                    // Update student
                    $stmt = $db->prepare("UPDATE students SET matric_no = ?, course = ?, intake = ? WHERE student_id = ?");
                    $stmt->execute([$matric_no, $course, $intake, $student_id]);

                    $db->commit();
                    $_SESSION['admin_flash'] = 'Student updated successfully';
                    $_SESSION['admin_flash_type'] = 'success';
                } catch (Exception $e) {
                    $db->rollBack();
                    $_SESSION['admin_flash'] = 'Error updating student: ' . $e->getMessage();
                    $_SESSION['admin_flash_type'] = 'danger';
                }
            } else {
                $_SESSION['admin_flash'] = 'All fields are required';
                $_SESSION['admin_flash_type'] = 'danger';
            }
            header('Location: admin_students.php');
            exit;
        } elseif ($action === 'delete_student') {
            $student_id = (int)($_POST['student_id'] ?? 0);
            if ($student_id) {
                try {
                    $db->beginTransaction();

                    // Get user_id
                    $stmt = $db->prepare("SELECT user_id FROM students WHERE student_id = ?");
                    $stmt->execute([$student_id]);
                    $user_id = $stmt->fetchColumn();

                    if ($user_id) {
                        // Delete student (cascade will delete user)
                        $stmt = $db->prepare("DELETE FROM students WHERE student_id = ?");
                        $stmt->execute([$student_id]);
                    }

                    $db->commit();
                    $_SESSION['admin_flash'] = 'Student deleted successfully';
                    $_SESSION['admin_flash_type'] = 'success';
                } catch (Exception $e) {
                    $db->rollBack();
                    $_SESSION['admin_flash'] = 'Error deleting student: ' . $e->getMessage();
                    $_SESSION['admin_flash_type'] = 'danger';
                }
            }
            header('Location: admin_students.php');
            exit;
        }
    }
}

// Fetch students
$stmt = $db->query("
    SELECT s.student_id, s.matric_no, s.course, s.intake,
           u.name_encrypted, u.email_encrypted, u.created_at
    FROM students s
    JOIN users u ON s.user_id = u.user_id
    ORDER BY u.created_at DESC
");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Decrypt data
foreach ($students as &$student) {
    $student['name'] = decryptData($student['name_encrypted']);
    $student['email'] = decryptData($student['email_encrypted']);
}

$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminInitial = strtoupper(substr($adminName, 0, 1));
$adminFlash = $_SESSION['admin_flash'] ?? '';
$adminFlashType = $_SESSION['admin_flash_type'] ?? 'info';
unset($_SESSION['admin_flash'], $_SESSION['admin_flash_type']);
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
                <a class="icon-button text-decoration-none" href="logout.php" aria-label="Sign out">
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
                    <h1 class="h3 fw-bold mb-1">Manage Students</h1>
                    <p class="text-muted mb-0">View and manage all student accounts.</p>
                </div>
                <button class="btn btn-warning fw-bold" type="button" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                    <i class="bi bi-person-plus-fill me-1"></i>
                    Add Student
                </button>
            </div>

            <div class="dashboard-card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Matric No</th>
                                <th>Course</th>
                                <th>Intake</th>
                                <th>Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No students found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?= h($student['name']) ?></td>
                                        <td><?= h($student['email']) ?></td>
                                        <td><?= h($student['matric_no']) ?></td>
                                        <td><?= h($student['course']) ?></td>
                                        <td><?= h($student['intake']) ?></td>
                                        <td><?= h(date('M j, Y', strtotime($student['created_at']))) ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary me-1" type="button" data-bs-toggle="modal" data-bs-target="#editStudentModal<?= $student['student_id'] ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteStudentModal<?= $student['student_id'] ?>">
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

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" method="post">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5 fw-bold" id="addStudentModalLabel">Add Student Account</h2>
                    <p class="text-muted small mb-0">Creates a login in users and a student profile.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="admin_action" value="create_student">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="studentName">Full Name</label>
                        <input class="form-control" id="studentName" name="name" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="studentEmail">Email</label>
                        <input class="form-control" id="studentEmail" name="email" type="email" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="studentPassword">Password</label>
                        <input class="form-control" id="studentPassword" name="password" type="password" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="studentMatric">Matric No</label>
                        <input class="form-control" id="studentMatric" name="matric_no" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="studentCourse">Course</label>
                        <input class="form-control" id="studentCourse" name="course" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="studentIntake">Intake</label>
                        <input class="form-control" id="studentIntake" name="intake" placeholder="2023/2024" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning fw-bold">Create Student</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Student Modals -->
<?php foreach ($students as $student): ?>
<div class="modal fade" id="editStudentModal<?= $student['student_id'] ?>" tabindex="-1" aria-labelledby="editStudentModalLabel<?= $student['student_id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" method="post">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5 fw-bold" id="editStudentModalLabel<?= $student['student_id'] ?>">Edit Student</h2>
                    <p class="text-muted small mb-0">Update student information.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="admin_action" value="edit_student">
                <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="editStudentName<?= $student['student_id'] ?>">Full Name</label>
                        <input class="form-control" id="editStudentName<?= $student['student_id'] ?>" name="name" value="<?= h($student['name']) ?>" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="editStudentEmail<?= $student['student_id'] ?>">Email</label>
                        <input class="form-control" id="editStudentEmail<?= $student['student_id'] ?>" name="email" type="email" value="<?= h($student['email']) ?>" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="editStudentMatric<?= $student['student_id'] ?>">Matric No</label>
                        <input class="form-control" id="editStudentMatric<?= $student['student_id'] ?>" name="matric_no" value="<?= h($student['matric_no']) ?>" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="editStudentCourse<?= $student['student_id'] ?>">Course</label>
                        <input class="form-control" id="editStudentCourse<?= $student['student_id'] ?>" name="course" value="<?= h($student['course']) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="editStudentIntake<?= $student['student_id'] ?>">Intake</label>
                        <input class="form-control" id="editStudentIntake<?= $student['student_id'] ?>" name="intake" value="<?= h($student['intake']) ?>" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning fw-bold">Update Student</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Student Modal -->
<div class="modal fade" id="deleteStudentModal<?= $student['student_id'] ?>" tabindex="-1" aria-labelledby="deleteStudentModalLabel<?= $student['student_id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5 fw-bold" id="deleteStudentModalLabel<?= $student['student_id'] ?>">Confirm Delete</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong><?= h($student['name']) ?></strong>? This action cannot be undone.</p>
                <div class="alert alert-warning">
                    <strong>Warning:</strong> This will permanently delete the student account and all associated data.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger fw-bold" data-bs-toggle="modal" data-bs-target="#confirmDeleteStudentModal<?= $student['student_id'] ?>" data-bs-dismiss="modal">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Delete Student Modal -->
<div class="modal fade" id="confirmDeleteStudentModal<?= $student['student_id'] ?>" tabindex="-1" aria-labelledby="confirmDeleteStudentModalLabel<?= $student['student_id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5 fw-bold" id="confirmDeleteStudentModalLabel<?= $student['student_id'] ?>">Final Confirmation</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Please confirm once more that you want to delete <strong><?= h($student['name']) ?></strong>.</p>
                <p class="text-danger fw-bold">This action is irreversible!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="post" class="d-inline">
                    <input type="hidden" name="admin_action" value="delete_student">
                    <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
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