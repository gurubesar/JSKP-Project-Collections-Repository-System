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

        <?php $fileCount = count($files); ?>
        <div class="hero-panel mb-4">
            <div class="row align-items-center gx-4">
                <div class="col-lg-8">
                    <span class="badge badge-utm-gold mb-3">Project Overview</span>
                    <h1 class="display-6 fw-bold mb-2"><?= htmlspecialchars($title) ?></h1>
                    <p class="text-muted mb-3">Project Code: <span class="fw-semibold"><?= 'UTM-FYP-' . str_pad((string) $projectId, 4, '0', STR_PAD_LEFT) ?></span></p>
                    <p class="mb-4"><?= nl2br(htmlspecialchars($description ?: 'No project description provided yet.')) ?></p>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#generateProposalModal">Generate Proposal</button>
                        <button class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#uploadBox">Upload File</button>
                    </div>
                </div>
                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="card border-utm rounded-4 p-4 shadow-sm">
                        <h2 class="h5 mb-3">Project Snapshot</h2>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <div class="text-muted">Files uploaded</div>
                                <div class="fs-4 fw-bold"><?= $fileCount ?></div>
                            </div>
                            <div class="text-end">
                                <div class="text-muted">Supervisor</div>
                                <div class="fw-semibold"><?= htmlspecialchars($supervisor ?: 'Not assigned') ?></div>
                            </div>
                        </div>
                        <div class="divider mb-3" style="height:1px;background:rgba(128,0,32,0.08);"></div>
                        <div>
                            <div class="text-muted">Latest update</div>
                            <div class="fw-semibold"><?= htmlspecialchars($files[0]['uploaded_at'] ?? 'No uploads yet') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="uploadBox" class="collapse mb-4">
            <div class="card border-utm rounded-4 p-4 shadow-sm">
                <h2 class="h5 mb-3">Upload New File</h2>
                <form action="student_actions.php?action=upload_file&project_id=<?= $projectId ?>" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Select PDF file</label>
                        <input type="file" name="project_file" class="form-control" accept="application/pdf" required>
                        <div class=\"form-text\">Please submit only PDF files (max 200 MB).</div>
                    </div>
                    <button class="btn btn-utm" type="submit">Upload</button>
                </form>
            </div>
        </div>

        <div class="section-title mt-4">
            <div>
                <h2>Project Files</h2>
                <p class="text-muted">Manage all uploaded documents and proposals for this project.</p>
            </div>
            <span class="badge badge-utm-gold align-self-start">Total files: <?= $fileCount ?></span>
        </div>

        <?php if (empty($files)): ?>
            <div class="card border-utm rounded-4 p-4 shadow-sm">
                <p class="mb-0 text-muted">No files uploaded yet. Use the button above to add a proposal or document.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
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
                    <div class="col-12 col-md-6">
                        <div class="card border-utm rounded-4 p-3 shadow-sm h-100">
                            <div class="d-flex align-items-start gap-3 mb-3">
                                <div class="bg-utm-maroon rounded-4 p-3 text-white d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                                    <i class="bi bi-file-earmark-text-fill fs-4"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h3 class="h6 mb-1"><?= htmlspecialchars($fileName ?: 'Untitled Document') ?></h3>
                                    <p class="mb-1 text-muted">Uploaded: <?= htmlspecialchars($file['uploaded_at'] ?? '') ?></p>
                                    <p class="mb-0 text-muted">By <?= htmlspecialchars(decryptData($file['uploader_name'] ?? '') ?: 'Student') ?></p>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <?php if ($filePath): ?>
                                    <a href="<?= htmlspecialchars($filePath) ?>" class="btn btn-outline-secondary btn-sm">Download</a>
                                <?php endif; ?>
                                <?php if ((int) ($file['uploaded_by'] ?? 0) === (int) ($_SESSION['user_id'] ?? 0)): ?>
                                    <button
                                        class="btn btn-danger btn-sm"
                                        type="button"
                                        data-file-id="<?= (int) $file['file_id'] ?>"
                                        data-file-name="<?= htmlspecialchars($fileName ?: 'File', ENT_QUOTES) ?>"
                                        data-project-id="<?= $projectId ?>"
                                        onclick="openDeleteFileModal(this)">
                                        Delete
                                    </button>
                                <?php endif; ?>
                            </div>
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
            <label class="form-label">Proposal File Name</label>
            <input class="form-control" name="proposal_file_name" placeholder="e.g. UTM-FYP-0101-Proposal" required>
            <div class="form-text">Enter the file name to use for the downloaded proposal document (without extension).</div>
          </div>
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

<!-- Confirm Delete File Modal -->
<div class="modal fade" id="confirmDeleteFileModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm File Deletion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Authorization is required to delete this project file.</p>
        <p class="fw-semibold" id="deleteFileName">File</p>
        <p class="text-muted small">This action cannot be undone. Please confirm before proceeding.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <form id="deleteFileConfirmForm" method="post" action="">
          <input type="hidden" name="file_id" id="confirmFileId" value="">
          <button type="submit" class="btn btn-danger">Delete File</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function openDeleteFileModal(button) {
  const fileId = button.dataset.fileId;
  const fileName = button.dataset.fileName;
  const projectId = button.dataset.projectId;
  document.getElementById('deleteFileName').textContent = fileName;
  const form = document.getElementById('deleteFileConfirmForm');
  form.action = `student_actions.php?action=delete_file&project_id=${encodeURIComponent(projectId)}`;
  document.getElementById('confirmFileId').value = fileId;
  const deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteFileModal'));
  deleteModal.show();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
