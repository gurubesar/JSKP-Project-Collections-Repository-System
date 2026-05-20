<?php
require_once __DIR__ . '/student_header.php';
?>

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold mb-1">My Projects</h1>
                <p class="text-muted mb-0">Manage your assigned projects, generate proposals, and upload files.</p>
            </div>
        </div>

        <?php if (count($projects) === 0): ?>
            <div class="empty-state">
                <div><i class="bi bi-folder2-open fs-2 d-block mb-2"></i>No projects found.</div>
                <p class="mb-0">Once your lecturer assigns your project, it will appear here.</p>
            </div>
        <?php else: ?>
            <div class="projects-grid">
                <?php foreach ($projects as $project):
                    $title = decryptValue($project['title_encrypted'] ?? '');
                    $description = decryptValue($project['description_encrypted'] ?? '');
                    $supervisor = decryptValue($project['lecturer_name'] ?? '');
                    $projectStatus = $project['submission_status'] ?: 'pending';
                    $statusText = $statusLabel($projectStatus);
                    $progress = $statusProgress($projectStatus);
                    $created = $project['created_at'] ? date('d M Y', strtotime($project['created_at'])) : 'Unknown';
                    $submittedAt = $project['submitted_at'] ? date('d M Y', strtotime($project['submitted_at'])) : 'Not submitted yet';
                    $projectCode = 'UTM-FYP-' . str_pad((string) $project['project_id'], 4, '0', STR_PAD_LEFT);
                ?>
                    <article class="project-card">
                        <div class="project-card-header">
                            <h2><?= e($title ?: 'Untitled Project') ?></h2>
                            <span class="project-badge <?= e($statusClass($projectStatus)) ?>"><?= e($statusText) ?></span>
                        </div>
                        <div class="project-meta">
                            <span><i class="bi bi-hash"></i> <?= e($projectCode) ?></span>
                            <span><i class="bi bi-person-fill"></i> <?= e($supervisor ?: 'Lecturer not assigned') ?></span>
                            <span><i class="bi bi-calendar-event"></i> <?= e($created) ?></span>
                        </div>
                        <p class="project-description"><?= e($description ?: 'No project description has been assigned yet.') ?></p>
                        <div class="project-actions">
                            <a href="student_project.php?project_id=<?= e($project['project_id']) ?>" class="btn btn-primary">View Details</a>
                            <a href="#" class="btn btn-secondary">Submission History</a>
                        </div>
                        <div>
                            <div class="progress-track"><div class="progress-fill" style="width: <?= e((string) $progress) ?>%;"></div></div>
                            <div style="margin-top: 10px; color: var(--student-muted); font-size: 0.95rem;">Last update: <?= e($submittedAt) ?></div>
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
