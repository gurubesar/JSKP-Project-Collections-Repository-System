        <div class="content">
            <?php if ($flashMessage): ?>
                <div class="alert alert-<?= e($flashType) ?> alert-dismissible fade show" role="alert">
                    <?= e($flashMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <section class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4" id="dashboard">
                <div>
                    <h1 class="h3 fw-bold mb-1" style="color: var(--lecturer-maroon);">Academic Project Review</h1>
                    <p class="text-muted mb-0">Dashboard Overview</p>
                </div>
            </section>

            <section class="row g-4 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted fw-semibold mb-2">Total Projects</p>
                                <strong class="stat-value"><?= e($totalProjects) ?></strong>
                            </div>
                            <div class="stat-icon"><i class="bi bi-folder2-open"></i></div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted fw-semibold mb-2">Pending Review</p>
                                <strong class="stat-value text-warning"><?= e($pendingProjects) ?></strong>
                            </div>
                            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted fw-semibold mb-2">Approved</p>
                                <strong class="stat-value text-success"><?= e($approvedProjects) ?></strong>
                            </div>
                            <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted fw-semibold mb-2">Assigned Students</p>
                                <strong class="stat-value"><?= e($assignedStudents) ?></strong>
                            </div>
                            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                        </div>
                    </article>
                </div>
            </section>

            <section class="toolbar mb-4">
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-lg">
                        <div class="input-group">
                            <span class="input-group-text search-control bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input id="projectSearch" class="form-control search-control border-start-0" type="search" placeholder="Search projects or students...">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <select id="yearFilter" class="form-select search-control">
                            <option value="all">All Study Years</option>
                            <?php foreach (array_unique(array_filter(array_column($projects, 'study_year'))) as $year): ?>
                                <option value="<?= e($year) ?>">Year <?= e($year) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <select id="statusFilter" class="form-select search-control">
                            <option value="all">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
            </section>

            <section id="projects" class="mb-4">
                <?php if (!$projects): ?>
                    <div class="empty-state">
                        <div>
                            <i class="bi bi-folder2-open fs-2 d-block mb-2"></i>
                            No data available
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row g-4" id="projectGrid">
                        <?php foreach ($projects as $project): ?>
                            <?php
                            $studentNames = array_column($project['students'], 'name');
                            $searchText = strtolower($project['title'] . ' ' . implode(' ', $studentNames));
                            $status = $project['status'];
                            $submittedAt = $project['submitted_at']
                                ? date('d/m/Y', strtotime((string) $project['submitted_at']))
                                : 'No data available';
                            ?>
                            <div class="col-12 col-lg-6 col-xxl-4 project-item"
                                 data-status="<?= e($status) ?>"
                                 data-year="<?= e($project['study_year']) ?>"
                                 data-search="<?= e($searchText) ?>">
                                <article class="project-card">
                                    <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                        <span class="status-badge status-<?= e($status) ?>"><?= e($statusLabels[$status] ?? $status) ?></span>
                                        <small class="text-muted"><?= e($project['code']) ?></small>
                                    </div>

                                    <h2 class="project-title mb-2"><?= e($project['title']) ?></h2>
                                    <div class="student-list mb-3">
                                        <strong><?= e(count($studentNames)) ?> Student<?= count($studentNames) === 1 ? '' : 's' ?></strong>
                                        <?php if ($studentNames): ?>
                                            <?php foreach ($studentNames as $index => $studentName): ?>
                                                <div><?= e($index + 1) ?>. <?= e($studentName) ?></div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div>No data available</div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="meta-line">Submission Date: <?= e($submittedAt) ?></div>
                                    <div class="meta-line">Study Year: <?= $project['study_year'] !== '' ? e($project['study_year']) : 'No data available' ?></div>

                                    <div class="action-row">
                                        <button class="btn btn-review btn-comment" type="button"
                                                data-bs-toggle="modal"
                                                data-bs-target="#commentModal"
                                                data-project-id="<?= e($project['id']) ?>"
                                                data-project-title="<?= e($project['title']) ?>">
                                            Comment
                                        </button>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="project_id" value="<?= e($project['id']) ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button class="btn btn-review btn-reject" type="submit">Reject</button>
                                        </form>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="project_id" value="<?= e($project['id']) ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button class="btn btn-review btn-approve" type="submit">Approve</button>
                                        </form>
                                    </div>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="empty-state d-none" id="filteredEmpty">No data available</div>
                <?php endif; ?>
            </section>

            <section class="dashboard-card" id="students">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Assigned Students</h2>
                        <p class="text-muted mb-0">Students attached to your supervised projects</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Project</th>
                                <th>Status</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$projects || !$assignedStudents): ?>
                                <tr><td colspan="4" class="text-center text-muted py-5">No data available</td></tr>
                            <?php else: ?>
                                <?php foreach ($projects as $project): ?>
                                    <?php foreach ($project['students'] as $student): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= e($student['name']) ?></td>
                                            <td><?= e($project['title']) ?></td>
                                            <td><span class="status-badge status-<?= e($project['status']) ?>"><?= e($statusLabels[$project['status']] ?? $project['status']) ?></span></td>
                                            <td><?= $project['submitted_at'] ? e(date('d/m/Y', strtotime((string) $project['submitted_at']))) : 'No data available' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</div>

<div class="modal fade" id="commentModal" tabindex="-1" aria-labelledby="commentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5 fw-bold" id="commentModalLabel">Add Feedback</h2>
                    <p class="text-muted mb-0 small" id="commentProjectTitle">Project feedback</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="comment">
                <input type="hidden" name="project_id" id="commentProjectId">
                <label class="form-label fw-semibold" for="commentText">Comment</label>
                <textarea class="form-control" id="commentText" name="comment" rows="5" placeholder="Write your feedback or revision notes..." required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning fw-bold">Submit Feedback</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const searchInput = document.getElementById('projectSearch');
    const statusFilter = document.getElementById('statusFilter');
    const yearFilter = document.getElementById('yearFilter');
    const projectItems = Array.from(document.querySelectorAll('.project-item'));
    const filteredEmpty = document.getElementById('filteredEmpty');

    function applyFilters() {
        const query = (searchInput?.value || '').trim().toLowerCase();
        const status = statusFilter?.value || 'all';
        const year = yearFilter?.value || 'all';
        let visible = 0;

        projectItems.forEach((item) => {
            const matchesSearch = !query || item.dataset.search.includes(query);
            const matchesStatus = status === 'all' || item.dataset.status === status;
            const matchesYear = year === 'all' || item.dataset.year === year;
            const shouldShow = matchesSearch && matchesStatus && matchesYear;
            item.classList.toggle('d-none', !shouldShow);
            if (shouldShow) visible++;
        });

        if (filteredEmpty) {
            filteredEmpty.classList.toggle('d-none', visible !== 0);
        }
    }

    searchInput?.addEventListener('input', applyFilters);
    statusFilter?.addEventListener('change', applyFilters);
    yearFilter?.addEventListener('change', applyFilters);

    const commentModal = document.getElementById('commentModal');
    commentModal?.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        document.getElementById('commentProjectId').value = button?.dataset.projectId || '';
        document.getElementById('commentProjectTitle').textContent = button?.dataset.projectTitle || 'Project feedback';
        document.getElementById('commentText').value = '';
    });
</script>
</body>
</html>