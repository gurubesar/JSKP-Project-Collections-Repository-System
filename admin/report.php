<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin authentication check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../database/db.php';

// Initialize error array
$errors = [];
$stats = [
    'total_students' => 0,
    'total_lecturers' => 0,
    'total_repositories' => 0,
    'generated_at' => date('Y-m-d H:i:s')
];

try {
    // Get total students
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM students");
    $stmt->execute();
    $result = $stmt->fetch();
    $stats['total_students'] = $result['total'] ?? 0;

    // Get total lecturers
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM lecturers");
    $stmt->execute();
    $result = $stmt->fetch();
    $stats['total_lecturers'] = $result['total'] ?? 0;

    // Get total repositories (projects)
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM projects");
    $stmt->execute();
    $result = $stmt->fetch();
    $stats['total_repositories'] = $result['total'] ?? 0;

} catch (PDOException $e) {
    $errors[] = "Database Error: Unable to retrieve statistics. " . htmlspecialchars($e->getMessage());
    error_log("Report generation error: " . $e->getMessage());
} catch (Exception $e) {
    $errors[] = "Error: " . htmlspecialchars($e->getMessage());
    error_log("Report error: " . $e->getMessage());
}

require __DIR__ . '/admin_header.php';
require __DIR__ . '/admin_sidebar.php';
?>

<main class="admin-shell">
    <div class="top-navbar">
        <h1 class="h3" style="margin: 0;">Reports</h1>
        <div style="display: flex; gap: 12px;">
            <button class="btn btn-primary" id="exportBtn" style="background-color: var(--admin-gold); color: #2b1800; border: none; font-weight: 600;">
                <i class="bi bi-download"></i> Export to Excel
            </button>
        </div>
    </div>

    <div class="admin-content">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <strong>Error:</strong>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Report Header -->
        <div class="card" style="margin-bottom: 28px; border: 1px solid var(--admin-border); box-shadow: var(--admin-shadow);">
            <div class="card-body p-4">
                <h2 class="card-title" style="color: var(--admin-sidebar); margin-bottom: 8px;">
                    <i class="bi bi-graph-up"></i> JSKP Project Repository System Report
                </h2>
                <p class="text-muted" style="margin: 0;">
                    <i class="bi bi-calendar-event"></i>
                    Generated: <strong><?= htmlspecialchars($stats['generated_at']) ?></strong>
                </p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4">
            <!-- Total Students Card -->
            <div class="col-lg-4 col-md-6">
                <div class="card h-100" style="border: 1px solid var(--admin-border); border-top: 4px solid var(--admin-sidebar); box-shadow: var(--admin-shadow); transition: transform 0.2s ease, box-shadow 0.2s ease;">
                    <div class="card-body p-4">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                            <h5 class="card-title mb-0" style="color: var(--admin-text); font-weight: 600;">Total Students</h5>
                            <div style="width: 56px; height: 56px; border-radius: 12px; background: rgba(128, 0, 32, 0.1); display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-mortarboard-fill" style="font-size: 28px; color: var(--admin-sidebar);"></i>
                            </div>
                        </div>
                        <div class="card-text">
                            <p style="margin: 0; font-size: 2.5rem; font-weight: 800; color: var(--admin-sidebar);">
                                <?= number_format($stats['total_students']) ?>
                            </p>
                            <small class="text-muted" style="display: block; margin-top: 8px;">
                                Active student accounts
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Lecturers Card -->
            <div class="col-lg-4 col-md-6">
                <div class="card h-100" style="border: 1px solid var(--admin-border); border-top: 4px solid #28a745; box-shadow: var(--admin-shadow); transition: transform 0.2s ease, box-shadow 0.2s ease;">
                    <div class="card-body p-4">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                            <h5 class="card-title mb-0" style="color: var(--admin-text); font-weight: 600;">Total Lecturers</h5>
                            <div style="width: 56px; height: 56px; border-radius: 12px; background: rgba(40, 167, 69, 0.1); display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-person-video3" style="font-size: 28px; color: #28a745;"></i>
                            </div>
                        </div>
                        <div class="card-text">
                            <p style="margin: 0; font-size: 2.5rem; font-weight: 800; color: #28a745;">
                                <?= number_format($stats['total_lecturers']) ?>
                            </p>
                            <small class="text-muted" style="display: block; margin-top: 8px;">
                                Registered faculty members
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Repositories Card -->
            <div class="col-lg-4 col-md-6">
                <div class="card h-100" style="border: 1px solid var(--admin-border); border-top: 4px solid #0d6efd; box-shadow: var(--admin-shadow); transition: transform 0.2s ease, box-shadow 0.2s ease;">
                    <div class="card-body p-4">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                            <h5 class="card-title mb-0" style="color: var(--admin-text); font-weight: 600;">Total Repositories</h5>
                            <div style="width: 56px; height: 56px; border-radius: 12px; background: rgba(13, 110, 253, 0.1); display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-folder2-open" style="font-size: 28px; color: #0d6efd;"></i>
                            </div>
                        </div>
                        <div class="card-text">
                            <p style="margin: 0; font-size: 2.5rem; font-weight: 800; color: #0d6efd;">
                                <?= number_format($stats['total_repositories']) ?>
                            </p>
                            <small class="text-muted" style="display: block; margin-top: 8px;">
                                Active project repositories
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Table -->
        <div class="card mt-5" style="border: 1px solid var(--admin-border); box-shadow: var(--admin-shadow);">
            <div class="card-header" style="background-color: var(--admin-gold); color: #2b1800; padding: 16px 24px; border: none; font-weight: 600;">
                <i class="bi bi-table"></i> Report Summary
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr style="background-color: var(--admin-bg); border-bottom: 2px solid var(--admin-border);">
                            <th style="color: var(--admin-text); font-weight: 600; padding: 16px 24px;">Metric</th>
                            <th style="color: var(--admin-text); font-weight: 600; padding: 16px 24px; text-align: right;">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid var(--admin-border);">
                            <td style="padding: 16px 24px; color: var(--admin-text);">
                                <i class="bi bi-mortarboard-fill" style="color: var(--admin-sidebar); margin-right: 8px;"></i>
                                Total Students
                            </td>
                            <td style="padding: 16px 24px; color: var(--admin-text); text-align: right; font-weight: 600;">
                                <?= number_format($stats['total_students']) ?>
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid var(--admin-border);">
                            <td style="padding: 16px 24px; color: var(--admin-text);">
                                <i class="bi bi-person-video3" style="color: #28a745; margin-right: 8px;"></i>
                                Total Lecturers
                            </td>
                            <td style="padding: 16px 24px; color: var(--admin-text); text-align: right; font-weight: 600;">
                                <?= number_format($stats['total_lecturers']) ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 16px 24px; color: var(--admin-text);">
                                <i class="bi bi-folder2-open" style="color: #0d6efd; margin-right: 8px;"></i>
                                Total Repositories
                            </td>
                            <td style="padding: 16px 24px; color: var(--admin-text); text-align: right; font-weight: 600;">
                                <?= number_format($stats['total_repositories']) ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        .admin-content {
            padding: 24px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            background: var(--admin-card);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(28, 39, 60, 0.12) !important;
        }

        .table-hover tbody tr:hover {
            background-color: var(--admin-bg);
            cursor: pointer;
        }
    </style>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Export to Excel button handler
    document.getElementById('exportBtn').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Generating...';

        fetch('export_report.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',  // Include cookies with request
            body: JSON.stringify({
                action: 'export'
            })
        })
        .then(response => {
            if (response.status === 200) {
                // Get filename from Content-Disposition header
                const disposition = response.headers.get('Content-Disposition');
                const filename = disposition 
                    ? disposition.split('filename=')[1].replace(/"/g, '')
                    : 'jskp_summary_report.xlsx';
                
                return response.blob().then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(link);
                });
            } else {
                return response.json().then(data => {
                    throw new Error(data.message || data.error || 'Export failed');
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to export report: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-download"></i> Export to Excel';
        });
    });
</script>

<?php
require __DIR__ . '/../admin/admin_footer.php' ?? null;
?>
