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

        if ($action === 'create_admin') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if ($name && $email && $password) {
                try {
                    $db->beginTransaction();

                    // Check if email already exists
                    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email_hash = ?");
                    $stmt->execute([hashEmail($email)]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('Email already exists');
                    }

                    // Insert admin user
                    $stmt = $db->prepare("INSERT INTO users (name_encrypted, email_hash, email_encrypted, password_hash, role, created_by) VALUES (?, ?, ?, ?, 'admin', ?)");
                    $stmt->execute([
                        encryptData($name),
                        hashEmail($email),
                        encryptData($email),
                        password_hash($password, PASSWORD_DEFAULT),
                        $_SESSION['user_id']
                    ]);

                    $db->commit();
                    $_SESSION['admin_flash'] = 'Admin account created successfully';
                    $_SESSION['admin_flash_type'] = 'success';
                } catch (Exception $e) {
                    $db->rollBack();
                    $_SESSION['admin_flash'] = 'Error creating admin: ' . $e->getMessage();
                    $_SESSION['admin_flash_type'] = 'danger';
                }
            } else {
                $_SESSION['admin_flash'] = 'All fields are required';
                $_SESSION['admin_flash_type'] = 'danger';
            }
            header('Location: admin_admins.php');
            exit;
        } elseif ($action === 'edit_admin') {
            $user_id = (int)($_POST['user_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if ($user_id && $name && $email) {
                try {
                    $db->beginTransaction();

                    // Verify user exists and is an admin
                    $stmt = $db->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'admin'");
                    $stmt->execute([$user_id]);
                    if (!$stmt->fetchColumn()) {
                        throw new Exception('Admin not found');
                    }

                    // Check if email already exists for another user
                    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email_hash = ? AND user_id != ?");
                    $stmt->execute([hashEmail($email), $user_id]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('Email already exists');
                    }

                    // Update user
                    $stmt = $db->prepare("UPDATE users SET name_encrypted = ?, email_hash = ?, email_encrypted = ? WHERE user_id = ?");
                    $stmt->execute([
                        encryptData($name),
                        hashEmail($email),
                        encryptData($email),
                        $user_id
                    ]);

                    $db->commit();
                    $_SESSION['admin_flash'] = 'Admin updated successfully';
                    $_SESSION['admin_flash_type'] = 'success';
                } catch (Exception $e) {
                    $db->rollBack();
                    $_SESSION['admin_flash'] = 'Error updating admin: ' . $e->getMessage();
                    $_SESSION['admin_flash_type'] = 'danger';
                }
            } else {
                $_SESSION['admin_flash'] = 'All fields are required';
                $_SESSION['admin_flash_type'] = 'danger';
            }
            header('Location: admin_admins.php');
            exit;
        } elseif ($action === 'delete_admin') {
            $user_id = (int)($_POST['user_id'] ?? 0);
            if ($user_id) {
                // Prevent self-deletion
                if ($user_id === (int)($_SESSION['user_id'] ?? 0)) {
                    $_SESSION['admin_flash'] = 'You cannot delete your own account';
                    $_SESSION['admin_flash_type'] = 'danger';
                } else {
                    try {
                        $db->beginTransaction();

                        // Verify user is an admin
                        $stmt = $db->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'admin'");
                        $stmt->execute([$user_id]);
                        if ($stmt->fetchColumn()) {
                            $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
                            $stmt->execute([$user_id]);
                        }

                        $db->commit();
                        $_SESSION['admin_flash'] = 'Admin deleted successfully';
                        $_SESSION['admin_flash_type'] = 'success';
                    } catch (Exception $e) {
                        $db->rollBack();
                        $_SESSION['admin_flash'] = 'Error deleting admin: ' . $e->getMessage();
                        $_SESSION['admin_flash_type'] = 'danger';
                    }
                }
            }
            header('Location: admin_admins.php');
            exit;
        }
    }
}

// Fetch all admin users
$stmt = $db->query("
    SELECT u.user_id, u.name_encrypted, u.email_encrypted, u.created_at
    FROM users u
    WHERE u.role = 'admin'
    ORDER BY u.created_at DESC
");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Decrypt data
foreach ($admins as &$admin) {
    $admin['name'] = decryptData($admin['name_encrypted']);
    $admin['email'] = decryptData($admin['email_encrypted']);
}

$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminInitial = strtoupper(substr($adminName, 0, 1));
$adminFlash = $_SESSION['admin_flash'] ?? '';
$adminFlashType = $_SESSION['admin_flash_type'] ?? 'info';
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
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
                    <h1 class="h3 fw-bold mb-1">Manage Administrators</h1>
                    <p class="text-muted mb-0">View and manage all admin accounts.</p>
                </div>
                <button class="btn btn-outline-dark fw-bold" type="button" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                    <i class="bi bi-shield-lock-fill me-1"></i>
                    Add Admin
                </button>
            </div>

            <div class="dashboard-card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($admins)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No admin accounts found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($admins as $admin): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="profile-avatar bg-dark" style="width:32px;height:32px;font-size:0.75rem;">
                                                    <?= h(strtoupper(substr($admin['name'], 0, 1))) ?>
                                                </div>
                                                <span><?= h($admin['name']) ?></span>
                                                <?php if ($admin['user_id'] === $currentUserId): ?>
                                                    <span class="badge bg-success rounded-pill">You</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?= h($admin['email']) ?></td>
                                        <td><span class="badge bg-dark rounded-pill">Administrator</span></td>
                                        <td><?= h(date('M j, Y', strtotime($admin['created_at']))) ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary me-1" type="button" data-bs-toggle="modal" data-bs-target="#editAdminModal<?= $admin['user_id'] ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <?php if ($admin['user_id'] !== $currentUserId): ?>
                                                <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteAdminModal<?= $admin['user_id'] ?>">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            <?php endif; ?>
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

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" method="post">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5 fw-bold" id="addAdminModalLabel">Add Admin Account</h2>
                    <p class="text-muted small mb-0">Create a new administrator with full system access.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="admin_action" value="create_admin">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="adminName">Full Name</label>
                        <input class="form-control" id="adminName" name="name" placeholder="e.g. Ahmad bin Ali" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="adminEmail">Email</label>
                        <input class="form-control" id="adminEmail" name="email" type="email" placeholder="e.g. ahmad@utm.my" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="adminPassword">Password</label>
                        <input class="form-control" id="adminPassword" name="password" type="password" placeholder="Minimum 8 characters" minlength="8" required>
                        <div class="form-text">Use a strong password. The admin will be able to change it later.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning fw-bold">Create Admin</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Admin Modals -->
<?php foreach ($admins as $admin): ?>
<div class="modal fade" id="editAdminModal<?= $admin['user_id'] ?>" tabindex="-1" aria-labelledby="editAdminModalLabel<?= $admin['user_id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" method="post">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5 fw-bold" id="editAdminModalLabel<?= $admin['user_id'] ?>">Edit Admin</h2>
                    <p class="text-muted small mb-0">Update administrator information.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="admin_action" value="edit_admin">
                <input type="hidden" name="user_id" value="<?= $admin['user_id'] ?>">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="editAdminName<?= $admin['user_id'] ?>">Full Name</label>
                        <input class="form-control" id="editAdminName<?= $admin['user_id'] ?>" name="name" value="<?= h($admin['name']) ?>" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="editAdminEmail<?= $admin['user_id'] ?>">Email</label>
                        <input class="form-control" id="editAdminEmail<?= $admin['user_id'] ?>" name="email" type="email" value="<?= h($admin['email']) ?>" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning fw-bold">Update Admin</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Admin Modal -->
<?php if ($admin['user_id'] !== $currentUserId): ?>
<div class="modal fade" id="deleteAdminModal<?= $admin['user_id'] ?>" tabindex="-1" aria-labelledby="deleteAdminModalLabel<?= $admin['user_id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5 fw-bold" id="deleteAdminModalLabel<?= $admin['user_id'] ?>">Confirm Delete</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong><?= h($admin['name']) ?></strong>?</p>
                <div class="alert alert-warning">
                    <strong>Warning:</strong> This will permanently delete the admin account. This action cannot be undone.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger fw-bold" data-bs-toggle="modal" data-bs-target="#confirmDeleteAdminModal<?= $admin['user_id'] ?>" data-bs-dismiss="modal">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Delete Admin Modal -->
<div class="modal fade" id="confirmDeleteAdminModal<?= $admin['user_id'] ?>" tabindex="-1" aria-labelledby="confirmDeleteAdminModalLabel<?= $admin['user_id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5 fw-bold" id="confirmDeleteAdminModalLabel<?= $admin['user_id'] ?>">Final Confirmation</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Please confirm once more that you want to delete <strong><?= h($admin['name']) ?></strong>.</p>
                <p class="text-danger fw-bold">This action is irreversible!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="post" class="d-inline">
                    <input type="hidden" name="admin_action" value="delete_admin">
                    <input type="hidden" name="user_id" value="<?= $admin['user_id'] ?>">
                    <button type="submit" class="btn btn-danger fw-bold">Yes, Permanently Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
