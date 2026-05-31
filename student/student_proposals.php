<?php
require_once __DIR__ . '/student_header.php';

$proposalFiles = [];
$projectIds = array_column($projects, 'project_id');

if (!empty($projectIds)) {
    $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
    $stmt = $db->prepare(
        "SELECT f.file_id, f.project_id, f.file_name_encrypted, f.file_path_encrypted, f.uploaded_at, f.uploaded_by, u.name_encrypted AS uploader_name, p.title_encrypted
         FROM files f
         LEFT JOIN users u ON u.user_id = f.uploaded_by
         LEFT JOIN projects p ON p.project_id = f.project_id
         WHERE f.project_id IN ($placeholders)
         ORDER BY f.uploaded_at DESC"
    );
    $stmt->execute($projectIds);
    $allFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allFiles as $fileRow) {
        $fileName = '';
        $filePath = '';
        try {
            $fileName = decryptData($fileRow['file_name_encrypted'] ?? '');
            $filePath = decryptData($fileRow['file_path_encrypted'] ?? '');
        } catch (Throwable $error) {
            // ignore
        }

        $normalizedFileName = strtolower($fileName);
        $isProposal = false;
        if ($normalizedFileName !== '' && (str_contains($normalizedFileName, 'proposal') || str_ends_with($normalizedFileName, '.doc') || str_ends_with($normalizedFileName, '.docx'))) {
            $isProposal = true;
        }

        if (!$isProposal) {
            continue;
        }

        $proposalFiles[] = [
            'file_id' => (int) ($fileRow['file_id'] ?? 0),
            'project_id' => (int) ($fileRow['project_id'] ?? 0),
            'project_title' => decryptData($fileRow['title_encrypted'] ?? ''),
            'file_name' => $fileName,
            'file_path' => $filePath,
            'uploaded_at' => $fileRow['uploaded_at'] ?? '',
            'uploaded_by' => (int) ($fileRow['uploaded_by'] ?? 0),
            'uploader_name' => decryptData($fileRow['uploader_name'] ?? ''),
        ];
    }
}
?>

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">Proposals</h1>
                <p class="text-muted mb-0">Your generated proposal documents are listed here.</p>
            </div>
        </div>

        <?php if (empty($proposalFiles)): ?>
            <div class="empty-state">
                <div><i class="bi bi-file-earmark-text fs-2 d-block mb-2"></i>No proposals found.</div>
                <p class="mb-0">Generate a proposal from a project page to see it here.</p>
            </div>
        <?php else: ?>
            <div class="projects-grid">
                <?php foreach ($proposalFiles as $proposal):
                    $projectCode = 'UTM-FYP-' . str_pad((string) $proposal['project_id'], 4, '0', STR_PAD_LEFT);
                ?>
                    <article class="project-card">
                        <div class="project-card-header">
                            <div class="project-title-section">
                                <h2><?= e($proposal['file_name'] ?: 'Generated Proposal') ?></h2>
                                <div class="project-meta-inline">
                                    <span class="project-code"><i class="bi bi-hash"></i> <?= e($projectCode) ?></span>
                                    <span class="project-date"><i class="bi bi-calendar-event"></i> <?= e($proposal['uploaded_at'] ? date('d M Y', strtotime($proposal['uploaded_at'])) : 'Unknown') ?></span>
                                </div>
                            </div>
                            <span class="project-badge status-pending">Proposal</span>
                        </div>
                        <p class="project-description"><?= e($proposal['project_title'] ?: 'Project proposal document') ?></p>
                        <div class="project-supervisor">
                            <i class="bi bi-person-circle"></i>
                            <span>Uploaded by: <strong><?= e($proposal['uploader_name'] ?: 'Student') ?></strong></span>
                        </div>
                        <div class="project-actions">
                            <?php if ($proposal['file_path']): ?>
                                <a href="<?= e($proposal['file_path']) ?>" class="btn btn-primary"><i class="bi bi-download"></i> Download</a>
                            <?php endif; ?>
                            <?php if ($proposal['uploaded_by'] === $studentId): ?>
                                <button type="button" class="btn btn-danger" data-file-id="<?= $proposal['file_id'] ?>" data-file-name="<?= e($proposal['file_name'] ?: 'Proposal') ?>" data-project-id="<?= $proposal['project_id'] ?>" onclick="openDeleteFileModal(this)"><i class="bi bi-trash"></i> Delete</button>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
