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

        if ($action === 'create_lecturer') {
            // Create lecturer logic
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $staff_id = trim($_POST['staff_id'] ?? '');
            $department = trim($_POST['department'] ?? '');

            if ($name && $email && $password && $staff_id && $department) {
                try {
                    $db->beginTransaction();

                    // Check if email already exists
                    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email_hash = ?");
                    $stmt->execute([hash('sha256', $email)]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('Email already exists');
                    }

                    // Check if staff_id already exists
                    $stmt = $db->prepare("SELECT COUNT(*) FROM lecturers WHERE staff_id = ?");
                    $stmt->execute([$staff_id]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('Staff ID already exists');
                    }

                    // Insert user
                    $stmt = $db->prepare("INSERT INTO users (name_encrypted, email_hash, email_encrypted, password_hash, role, created_by) VALUES (?, ?, ?, ?, 'lecturer', ?)");
                    $stmt->execute([
                        encryptData($name),
                        hashEmail($email),
                        encryptData($email),
                        password_hash($password, PASSWORD_DEFAULT),
                        $_SESSION['user_id']
                    ]);
                    $user_id = $db->lastInsertId();

                    // Insert lecturer
                    $stmt = $db->prepare("INSERT INTO lecturers (user_id, staff_id, department) VALUES (?, ?, ?)");
                    $stmt->execute([$user_id, $staff_id, $department]);

                    $db->commit();
                    $_SESSION['admin_flash'] = 'Lecturer created successfully';
                    $_SESSION['admin_flash_type'] = 'success';
                } catch (Exception $e) {
                    $db->rollBack();
                    $_SESSION['admin_flash'] = 'Error creating lecturer: ' . $e->getMessage();
                    $_SESSION['admin_flash_type'] = 'danger';
                }
            } else {
                $_SESSION['admin_flash'] = 'All fields are required';
                $_SESSION['admin_flash_type'] = 'danger';
            }
            header('Location: admin_lecturers.php');
            exit;
        } elseif ($action === 'edit_lecturer') {
            // Edit lecturer logic
            $lecturer_id = (int)($_POST['lecturer_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $staff_id = trim($_POST['staff_id'] ?? '');
            $department = trim($_POST['department'] ?? '');

            if ($lecturer_id && $name && $email && $staff_id && $department) {
                try {
                    $db->beginTransaction();

                    // Get current user_id
                    $stmt = $db->prepare("SELECT user_id FROM lecturers WHERE lecturer_id = ?");
                    $stmt->execute([$lecturer_id]);
                    $user_id = $stmt->fetchColumn();

                    if (!$user_id) {
                        throw new Exception('Lecturer not found');
                    }

                    // Check if email already exists for another user
                    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email_hash = ? AND user_id != ?");
                    $stmt->execute([hash('sha256', $email), $user_id]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('Email already exists');
                    }

                    // Check if staff_id already exists for another lecturer
                    $stmt = $db->prepare("SELECT COUNT(*) FROM lecturers WHERE staff_id = ? AND lecturer_id != ?");
                    $stmt->execute([$staff_id, $lecturer_id]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('Staff ID already exists');
                    }

                    // Update user
                    $stmt = $db->prepare("UPDATE users SET name_encrypted = ?, email_hash = ?, email_encrypted = ? WHERE user_id = ?");
                    $stmt->execute([
                        encryptData($name),
                        hashEmail($email),
                        encryptData($email),
                        $user_id
                    ]);

                    // Update lecturer
                    $stmt = $db->prepare("UPDATE lecturers SET staff_id = ?, department = ? WHERE lecturer_id = ?");
                    $stmt->execute([$staff_id, $department, $lecturer_id]);

                    $db->commit();
                    $_SESSION['admin_flash'] = 'Lecturer updated successfully';
                    $_SESSION['admin_flash_type'] = 'success';
                } catch (Exception $e) {
                    $db->rollBack();
                    $_SESSION['admin_flash'] = 'Error updating lecturer: ' . $e->getMessage();
                    $_SESSION['admin_flash_type'] = 'danger';
                }
            } else {
                $_SESSION['admin_flash'] = 'All fields are required';
                $_SESSION['admin_flash_type'] = 'danger';
            }
            header('Location: admin_lecturers.php');
            exit;
        } elseif ($action === 'delete_lecturer') {
            $lecturer_id = (int)($_POST['lecturer_id'] ?? 0);
            if ($lecturer_id) {
                try {
                    $db->beginTransaction();

                    // Get user_id
                    $stmt = $db->prepare("SELECT user_id FROM lecturers WHERE lecturer_id = ?");
                    $stmt->execute([$lecturer_id]);
                    $user_id = $stmt->fetchColumn();

                    if ($user_id) {
                        // Delete lecturer (cascade will delete user)
                        $stmt = $db->prepare("DELETE FROM lecturers WHERE lecturer_id = ?");
                        $stmt->execute([$lecturer_id]);
                    }

                    $db->commit();
                    $_SESSION['admin_flash'] = 'Lecturer deleted successfully';
                    $_SESSION['admin_flash_type'] = 'success';
                } catch (Exception $e) {
                    $db->rollBack();
                    $_SESSION['admin_flash'] = 'Error deleting lecturer: ' . $e->getMessage();
                    $_SESSION['admin_flash_type'] = 'danger';
                }
            }
            header('Location: admin_lecturers.php');
            exit;
        }
    }
}

// Fetch lecturers
$stmt = $db->query("
    SELECT l.lecturer_id, l.staff_id, l.department,
           u.name_encrypted, u.email_encrypted, u.created_at
    FROM lecturers l
    JOIN users u ON l.user_id = u.user_id
    ORDER BY u.created_at DESC
");
$lecturers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Decrypt data
foreach ($lecturers as &$lecturer) {
    $lecturer['name'] = decryptData($lecturer['name_encrypted']);
    $lecturer['email'] = decryptData($lecturer['email_encrypted']);
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
                    <h1 class="h3 fw-bold mb-1">Manage Lecturers</h1>
                    <p class="text-muted mb-0">View and manage all lecturer accounts.</p>
                </div>
                <button class="btn btn-outline-dark fw-bold" type="button" data-bs-toggle="modal" data-bs-target="#addLecturerModal">
                    <i class="bi bi-person-video3 me-1"></i>
                    Add Lecturer
                </button>
            </div>

            <div class="dashboard-card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Staff ID</th>
                                <th>Department</th>
                                <th>Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lecturers)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No lecturers found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($lecturers as $lecturer): ?>
                                    <tr>
                                        <td><?= h($lecturer['name']) ?></td>
                                        <td><?= h($lecturer['email']) ?></td>
                                        <td><?= h($lecturer['staff_id']) ?></td>
                                        <td><?= h($lecturer['department']) ?></td>
                                        <td><?= h(date('M j, Y', strtotime($lecturer['created_at']))) ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary me-1" type="button" data-bs-toggle="modal" data-bs-target="#editLecturerModal<?= $lecturer['lecturer_id'] ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteLecturerModal<?= $lecturer['lecturer_id'] ?>">
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

<!-- Add Lecturer Modal -->
<div class="modal fade" id="addLecturerModal" tabindex="-1" aria-labelledby="addLecturerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" method="post">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5 fw-bold" id="addLecturerModalLabel">Add Lecturer Account</h2>
                    <p class="text-muted small mb-0">Creates a login in users and a lecturer profile.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="admin_action" value="create_lecturer">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="lecturerName">Full Name</label>
                        <input class="form-control" id="lecturerName" name="name" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="lecturerEmail">Email</label>
                        <input class="form-control" id="lecturerEmail" name="email" type="email" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="lecturerPassword">Password</label>
                        <input class="form-control" id="lecturerPassword" name="password" type="password" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="lecturerStaffId">Staff ID</label>
                        <input class="form-control" id="lecturerStaffId" name="staff_id" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="lecturerDepartment">Department</label>
                        <input class="form-control" id="lecturerDepartment" name="department" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning fw-bold">Create Lecturer</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Lecturer Modals -->
<?php foreach ($lecturers as $lecturer): ?>
<div class="modal fade" id="editLecturerModal<?= $lecturer['lecturer_id'] ?>" tabindex="-1" aria-labelledby="editLecturerModalLabel<?= $lecturer['lecturer_id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" method="post">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5 fw-bold" id="editLecturerModalLabel<?= $lecturer['lecturer_id'] ?>">Edit Lecturer</h2>
                    <p class="text-muted small mb-0">Update lecturer information.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="admin_action" value="edit_lecturer">
                <input type="hidden" name="lecturer_id" value="<?= $lecturer['lecturer_id'] ?>">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="editLecturerName<?= $lecturer['lecturer_id'] ?>">Full Name</label>
                        <input class="form-control" id="editLecturerName<?= $lecturer['lecturer_id'] ?>" name="name" value="<?= h($lecturer['name']) ?>" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="editLecturerEmail<?= $lecturer['lecturer_id'] ?>">Email</label>
                        <input class="form-control" id="editLecturerEmail<?= $lecturer['lecturer_id'] ?>" name="email" type="email" value="<?= h($lecturer['email']) ?>" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="editLecturerStaffId<?= $lecturer['lecturer_id'] ?>">Staff ID</label>
                        <input class="form-control" id="editLecturerStaffId<?= $lecturer['lecturer_id'] ?>" name="staff_id" value="<?= h($lecturer['staff_id']) ?>" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="editLecturerDepartment<?= $lecturer['lecturer_id'] ?>">Department</label>
                        <input class="form-control" id="editLecturerDepartment<?= $lecturer['lecturer_id'] ?>" name="department" value="<?= h($lecturer['department']) ?>" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning fw-bold">Update Lecturer</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Lecturer Modal -->
<div class="modal fade" id="deleteLecturerModal<?= $lecturer['lecturer_id'] ?>" tabindex="-1" aria-labelledby="deleteLecturerModalLabel<?= $lecturer['lecturer_id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5 fw-bold" id="deleteLecturerModalLabel<?= $lecturer['lecturer_id'] ?>">Confirm Delete</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong><?= h($lecturer['name']) ?></strong>? This action cannot be undone.</p>
                <div class="alert alert-warning">
                    <strong>Warning:</strong> This will permanently delete the lecturer account and all associated data.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger fw-bold" data-bs-toggle="modal" data-bs-target="#confirmDeleteLecturerModal<?= $lecturer['lecturer_id'] ?>" data-bs-dismiss="modal">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Delete Lecturer Modal -->
<div class="modal fade" id="confirmDeleteLecturerModal<?= $lecturer['lecturer_id'] ?>" tabindex="-1" aria-labelledby="confirmDeleteLecturerModalLabel<?= $lecturer['lecturer_id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5 fw-bold" id="confirmDeleteLecturerModalLabel<?= $lecturer['lecturer_id'] ?>">Final Confirmation</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Please confirm once more that you want to delete <strong><?= h($lecturer['name']) ?></strong>.</p>
                <p class="text-danger fw-bold">This action is irreversible!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="post" class="d-inline">
                    <input type="hidden" name="admin_action" value="delete_lecturer">
                    <input type="hidden" name="lecturer_id" value="<?= $lecturer['lecturer_id'] ?>">
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