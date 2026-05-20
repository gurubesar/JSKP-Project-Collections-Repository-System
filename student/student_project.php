<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/student_header.php';
require_once __DIR__ . '/../database/encryption.php';

$projectId = (int) ($_GET['project_id'] ?? 0);
if ($projectId <= 0) {
    header('Location: student_dashboard.php');
    exit;
}

try {
    $stmt = $db->prepare(
        "SELECT p.project_id, p.title_encrypted, p.description_encrypted, p.study_year, p.created_at, p.lecturer_id, u.name_encrypted AS lecturer_name
         FROM projects p
         LEFT JOIN users u ON p.lecturer_id = u.user_id
         WHERE p.project_id = ?"
    );
    $stmt->execute([$projectId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new RuntimeException('Project not found');
    }

    $title = decryptData($row['title_encrypted'] ?? '') ?: 'Untitled Project';
    $description = decryptData($row['description_encrypted'] ?? '');
    $supervisor = decryptData($row['lecturer_name'] ?? '');

    $fileStmt = $db->prepare(
        "SELECT f.file_id, f.file_name_encrypted, f.file_path_encrypted, f.uploaded_at, u.name_encrypted AS uploader_name, f.uploaded_by
         FROM files f
         LEFT JOIN users u ON u.user_id = f.uploaded_by
         WHERE f.project_id = ?
         ORDER BY f.uploaded_at DESC"
    );
    $fileStmt->execute([$projectId]);
    $files = $fileStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $files = [];
}

$flash = $_SESSION['student_flash'] ?? '';
$flashType = $_SESSION['student_flash_type'] ?? 'success';
unset($_SESSION['student_flash'], $_SESSION['student_flash_type']);
?>

<main>
    <div class="main-content">
        <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flashType) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 fw-bold"><?= htmlspecialchars($title) ?></h1>
                <p class="text-muted mb-0">Project Code: <?= 'UTM-FYP-' . str_pad((string) $projectId, 4, '0', STR_PAD_LEFT) ?></p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#generateProposalModal">Generate Proposal</button>
                <button class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#uploadBox">Upload File</button>
            </div>
        </div>

        <p><?= nl2br(htmlspecialchars($description)) ?></p>

        <div id="uploadBox" class="collapse">
            <form action="student_actions.php?action=upload_file&project_id=<?= $projectId ?>" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Select PDF file</label>
                    <input type="file" name="project_file" class="form-control" accept="application/pdf" required>
                    <div class="form-text">Please submit only PDF files.</div>
                </div>
                <button class="btn btn-primary" type="submit">Upload</button>
            </form>
        </div>

        <h4 class="mt-4">Project Files</h4>
        <?php if (empty($files)): ?>
            <div class="text-muted">No files uploaded yet.</div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($files as $file): 
                    $fileName = '';
                    $filePath = '';
                    try {
                        $fileName = decryptData($file['file_name_encrypted'] ?? '');
                        $filePath = decryptData($file['file_path_encrypted'] ?? '');
                    } catch (Throwable $e) {
                        // ignore
                    }
                ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= htmlspecialchars($fileName ?: 'File') ?></strong>
                            <div class="small text-muted">Uploaded: <?= htmlspecialchars($file['uploaded_at'] ?? '') ?> by <?= htmlspecialchars(decryptData($file['uploader_name'] ?? '') ?: '') ?></div>
                        </div>
                        <div>
                            <?php if ($filePath): ?>
                                <a href="<?= htmlspecialchars($filePath) ?>" class="btn btn-sm btn-outline-secondary me-1" download>Download</a>
                            <?php endif; ?>
                            <?php if ((int) ($file['uploaded_by'] ?? 0) === (int) ($_SESSION['user_id'] ?? 0)): ?>
                                <form action="student_actions.php?action=delete_file&project_id=<?= $projectId ?>" method="post" class="d-inline">
                                    <input type="hidden" name="file_id" value="<?= (int) $file['file_id'] ?>">
                                    <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Delete this file?')">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</main>

<!-- Generate Proposal Modal -->
<div class="modal fade" id="generateProposalModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <form class="modal-content" method="post" action="student_actions.php?action=generate_proposal&project_id=<?= $projectId ?>">
      <div class="modal-header">
        <h5 class="modal-title">Generate Project Proposal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label">Project Title</label>
            <input class="form-control" name="proposal_title" value="<?= htmlspecialchars($title) ?>" required>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Project Type</label>
            <input class="form-control" name="proposal_type" placeholder="e.g. Website / Web-Based Application">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Client / Organization Name</label>
            <input class="form-control" name="proposal_client" placeholder="e.g. Ali Restaurant">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Programming Language</label>
            <input class="form-control" name="proposal_language" placeholder="e.g. PHP, HTML, CSS, JavaScript">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Framework</label>
            <input class="form-control" name="proposal_framework" placeholder="e.g. Laravel / Bootstrap">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Database</label>
            <input class="form-control" name="proposal_database" placeholder="e.g. MySQL">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Methodology</label>
            <input class="form-control" name="proposal_methodology" placeholder="e.g. Scrum">
          </div>
          <div class="col-12">
            <label class="form-label">Project Aim</label>
            <textarea class="form-control" name="proposal_aim" rows="2"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Objectives</label>
            <textarea class="form-control" name="proposal_objectives" rows="3"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Project Scopes</label>
            <textarea class="form-control" name="proposal_scopes" rows="3"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">The Problem</label>
            <textarea class="form-control" name="proposal_problem_description" rows="3"></textarea>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Affects</label>
            <input class="form-control" name="proposal_affects" placeholder="Who is affected">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Impact</label>
            <input class="form-control" name="proposal_impact" placeholder="What is the impact">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Successful Solution</label>
            <input class="form-control" name="proposal_successful_solution" placeholder="What success looks like">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">For</label>
            <input class="form-control" name="proposal_product_for" placeholder="For customers, admin, staff">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Who</label>
            <input class="form-control" name="proposal_product_who" placeholder="Who needs the system">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Product Name</label>
            <input class="form-control" name="proposal_product_name" placeholder="e.g. Ali Restaurant Ordering System">
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">That</label>
            <textarea class="form-control" name="proposal_product_that" rows="2"></textarea>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Unlike</label>
            <textarea class="form-control" name="proposal_product_unlike" rows="2"></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Our product</label>
            <textarea class="form-control" name="proposal_product_our" rows="2"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning">Generate Word Proposal</button>
      </div>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
