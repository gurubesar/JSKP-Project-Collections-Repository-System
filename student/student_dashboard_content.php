            <section class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1">Dashboard Overview</h1>
                    <p class="text-muted mb-0">Student project summary from your account.</p>
                </div>
            </section>

            <section class="stats-grid">
                <div class="stat-card">
                    <h3>Total Projects</h3>
                    <strong><?= e((string) $summary['total']) ?></strong>
                </div>
                <div class="stat-card">
                    <h3>Pending Review</h3>
                    <strong><?= e((string) $summary['pending']) ?></strong>
                </div>
                <div class="stat-card">
                    <h3>Approved</h3>
                    <strong><?= e((string) $summary['approved']) ?></strong>
                </div>
                <div class="stat-card">
                    <h3>Needs Revision</h3>
                    <strong><?= e((string) $summary['rejected']) ?></strong>
                </div>
            </section>

            <section class="projects-grid">
                <?php if (count($projects) === 0): ?>
                    <div class="project-card">
                        <div>
                            <h2>No Projects Assigned Yet</h2>
                            <p class="project-description">You currently have no project assignments. Your supervisor will assign a project once your group is confirmed.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($projects as $project): ?>
                        <?php
                            $title = decryptValue($project['title_encrypted'] ?? '');
                            $description = decryptValue($project['description_encrypted'] ?? '');
                            $supervisor = decryptValue($project['lecturer_name'] ?? '');
                            $projectStatus = $project['submission_status'] ?: 'pending';
                            $statusText = $statusLabel($projectStatus);
                            $progress = $statusProgress($projectStatus);
                            $created = $project['created_at'] ? date('d M Y', strtotime($project['created_at'])) : '';
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
                                <span><i class="bi bi-person-fill"></i> <?= e($supervisor ?: 'Supervisor not assigned') ?></span>
                                <span><i class="bi bi-calendar-event"></i> <?= e($created ?: 'Date unavailable') ?></span>
                            </div>
                            <p class="project-description"><?= e($description ?: 'Project description will appear here once your group submits details.') ?></p>
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
                <?php endif; ?>
            </section>

            <section class="activity-panel">
                <h3>Activity Summary</h3>
                <ul class="activity-list">
                    <li>Accessed dashboard at <?= date('d M Y, H:i') ?></li>
                    <li>Current role: Student</li>
                    <li><?= e((string) $summary['total']) ?> project(s) assigned to your account</li>
                </ul>
            </section>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>