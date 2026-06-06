        <div class="content">
            <?php if ($flashMessage): ?>
                <div class="alert alert-<?= e($flashType) ?> alert-dismissible fade show" role="alert" style="border-radius: 8px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                    <?= e($flashMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <section class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-5" id="dashboard">
                <div>
                    <h1 class="h2 fw-bold mb-1" style="color: var(--lecturer-maroon); letter-spacing: -0.5px;">Academic Project Review</h1>
                    <p class="text-muted mb-0" style="font-size: 0.95rem;">Overview of all student project submissions and statuses</p>
                </div>
            </section>

            <section class="row g-3 mb-5">
                <div class="col-12 col-sm-6 col-lg-3">
                    <article class="stat-card" style="background: linear-gradient(135deg, #fffcf4 0%, #fef6eb 100%); border: 1px solid rgba(100,64,39,0.1); border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(100,64,39,0.08); transition: all 0.3s ease;">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted fw-semibold mb-2" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.3px;">Total Projects</p>
                                <strong class="stat-value" style="font-size: 2rem; color: #643f27;"><?= e($totalProjects) ?></strong>
                            </div>
                            <div class="stat-icon" style="font-size: 2.5rem; color: #a67c52; opacity: 0.7;"><i class="bi bi-folder2-open"></i></div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <article class="stat-card" style="background: linear-gradient(135deg, #fff8e6 0%, #fff3d6 100%); border: 1px solid rgba(218,169,12,0.15); border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(218,169,12,0.1); transition: all 0.3s ease;">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted fw-semibold mb-2" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.3px; color: #a67c52;">Pending Review</p>
                                <strong class="stat-value" style="font-size: 2rem; color: #daa90c;"><?= e($pendingProjects) ?></strong>
                            </div>
                            <div class="stat-icon" style="font-size: 2.5rem; color: #daa90c; opacity: 0.7;"><i class="bi bi-hourglass-split"></i></div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <article class="stat-card" style="background: linear-gradient(135deg, #e6f7e6 0%, #d6f0d6 100%); border: 1px solid rgba(76,175,80,0.15); border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(76,175,80,0.1); transition: all 0.3s ease;">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted fw-semibold mb-2" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.3px; color: #4caf50;">Approved</p>
                                <strong class="stat-value" style="font-size: 2rem; color: #4caf50;"><?= e($approvedProjects) ?></strong>
                            </div>
                            <div class="stat-icon" style="font-size: 2.5rem; color: #4caf50; opacity: 0.7;"><i class="bi bi-check-circle-fill"></i></div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <article class="stat-card" style="background: linear-gradient(135deg, #f0e6f7 0%, #e6d6f0 100%); border: 1px solid rgba(155,39,176,0.15); border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(155,39,176,0.08); transition: all 0.3s ease;">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted fw-semibold mb-2" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.3px; color: #7b1fa2;">Assigned Students</p>
                                <strong class="stat-value" style="font-size: 2rem; color: #7b1fa2;"><?= e($assignedStudents) ?></strong>
                            </div>
                            <div class="stat-icon" style="font-size: 2.5rem; color: #7b1fa2; opacity: 0.7;"><i class="bi bi-people-fill"></i></div>
                        </div>
                    </article>
                </div>
            </section>

            <section class="toolbar mb-4" style="background: #ffffff; padding: 20px; border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.05);">
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-lg">
                        <div class="input-group" style="border-radius: 8px; overflow: hidden;">
                            <span class="input-group-text search-control bg-white border-end-0" style="border: 1px solid #e0e0e0;"><i class="bi bi-search" style="color: #643f27;"></i></span>
                            <input id="projectSearch" class="form-control search-control border-start-0" type="search" placeholder="Search projects or students..." style="border: 1px solid #e0e0e0; border-left: none;">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <select id="yearFilter" class="form-select search-control" style="border: 1px solid #e0e0e0; border-radius: 8px;">
                            <option value="all">All Study Years</option>
                            <?php foreach (array_unique(array_filter(array_column($projects, 'study_year'))) as $year): ?>
                                <option value="<?= e($year) ?>">Year <?= e($year) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <select id="statusFilter" class="form-select search-control" style="border: 1px solid #e0e0e0; border-radius: 8px;">
                            <option value="all">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
            </section>

            <section id="projects" class="mb-5">
                <?php if (!$projects): ?>
                    <div class="empty-state">
                        <div>
                            <i class="bi bi-folder2-open fs-2 d-block mb-2"></i>
                            No projects available
                        </div>
                    </div>
                <?php else: ?>
                    <div style="position: relative; margin-bottom: 20px;">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                            <h2 class="h5 fw-bold mb-0" style="color: #182033;">Recent Projects</h2>
                            <span class="badge" style="background: rgba(100,64,39,0.1); color: #643f27; padding: 6px 12px; border-radius: 6px; font-weight: 600;"><?= count($projects) ?> Project<?= count($projects) === 1 ? '' : 's' ?></span>
                        </div>
                        <div id="projectGrid" style="display: grid; grid-template-columns: 1fr; gap: 24px; padding: 0;">
                            <?php foreach ($projects as $project): ?>
                                <?php
                                $studentNames = array_column($project['students'], 'name');
                                $searchText = strtolower($project['title'] . ' ' . implode(' ', $studentNames));
                                $status = $project['status'];
                                $submittedAt = $project['submitted_at']
                                    ? date('d/m/Y', strtotime((string) $project['submitted_at']))
                                    : 'No data available';
                                $progressValue = $project['progress'] ?? 0;
                                ?>
                                <div class="project-item" data-status="<?= e($status) ?>" data-year="<?= e($project['study_year']) ?>" data-search="<?= e($searchText) ?>">
                                    <article class="project-card" style="background: #ffffff; border: 1px solid #e0e0e0; border-radius: 12px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); height: 100%; display: flex; flex-direction: column;">
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px;">
                                            <span class="status-badge status-<?= e($status) ?>" style="padding: 6px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px;"><?= e($statusLabels[$status] ?? $status) ?></span>
                                            <small class="text-muted" style="font-size: 0.8rem; font-weight: 600; color: #a67c52;"><?= e($project['code']) ?></small>
                                        </div>

                                        <h2 class="project-title mb-3" style="font-size: 1.1rem; font-weight: 700; color: #182033; line-height: 1.4;"><?= e($project['title']) ?></h2>
                                        
                                        <div style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid rgba(100,64,39,0.08); flex-grow: 1;">
                                            <div style="margin-bottom: 8px;">
                                                <strong style="font-size: 0.9rem; color: #182033;"><?= e(count($studentNames)) ?> Student<?= count($studentNames) === 1 ? '' : 's' ?></strong>
                                                <div style="margin-top: 6px;">
                                                    <?php if ($studentNames): ?>
                                                        <?php foreach (array_slice($studentNames, 0, 2) as $index => $studentName): ?>
                                                            <div style="font-size: 0.85rem; color: #737b8c; margin-top: 2px;">• <?= e($studentName) ?></div>
                                                        <?php endforeach; ?>
                                                        <?php if (count($studentNames) > 2): ?>
                                                            <div style="font-size: 0.85rem; color: #a67c52; margin-top: 4px; font-weight: 600;">+ <?= count($studentNames) - 2 ?> more</div>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <div style="font-size: 0.85rem; color: #a67c52;">No students assigned</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div style="margin-bottom: 16px;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                                <span style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; color: #a67c52;">Progress</span>
                                                <strong style="color: #643f27; font-size: 0.9rem;"><?= e($progressValue) ?>%</strong>
                                            </div>
                                            <div class="progress" style="height: 6px; background: rgba(100,64,39,0.08); border-radius: 3px; overflow: hidden;">
                                                <div class="progress-bar" style="width: <?= e($progressValue) ?>%; background: linear-gradient(90deg, #643f27, #a67c52); height: 100%; border-radius: 3px; transition: width 0.4s ease;"></div>
                                            </div>
                                        </div>

                                        <div style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid rgba(100,64,39,0.08);">
                                            <div style="font-size: 0.85rem; color: #737b8c; margin-bottom: 6px;"><i class="bi bi-calendar-event" style="margin-right: 6px; color: #a67c52;"></i>Submission: <?= e($submittedAt) ?></div>
                                            <div style="font-size: 0.85rem; color: #737b8c;"><i class="bi bi-mortarboard" style="margin-right: 6px; color: #a67c52;"></i>Year <?= $project['study_year'] !== '' ? e($project['study_year']) : 'N/A' ?></div>
                                        </div>

                                        <div class="action-row" style="display: flex; gap: 8px; margin-top: auto;">
                                            <button class="btn btn-review btn-comment" type="button" data-bs-toggle="modal" data-bs-target="#commentModal" data-project-id="<?= e($project['id']) ?>" data-project-title="<?= e($project['title']) ?>" style="flex: 1; padding: 8px 12px; font-size: 0.85rem; border: 1px solid #d4a574; color: #643f27; background: transparent; border-radius: 6px; font-weight: 600; transition: all 0.2s ease; cursor: pointer;">
                                                <i class="bi bi-chat-dots" style="margin-right: 6px;"></i> Comment
                                            </button>
                                            <button class="btn btn-review btn-reject js-review-action" type="button" data-bs-toggle="modal" data-bs-target="#reviewConfirmModal" data-review-action="reject" data-project-id="<?= e($project['id']) ?>" data-project-title="<?= e($project['title']) ?>" style="flex: 1; padding: 8px 12px; font-size: 0.85rem; border: 1px solid #e74c3c; color: #e74c3c; background: transparent; border-radius: 6px; font-weight: 600; transition: all 0.2s ease; cursor: pointer;">
                                                <i class="bi bi-x-circle" style="margin-right: 6px;"></i> Reject
                                            </button>
                                            <button class="btn btn-review btn-approve js-review-action" type="button" data-bs-toggle="modal" data-bs-target="#reviewConfirmModal" data-review-action="approve" data-project-id="<?= e($project['id']) ?>" data-project-title="<?= e($project['title']) ?>" style="flex: 1; padding: 8px 12px; font-size: 0.85rem; border: none; background: linear-gradient(135deg, #daa90c, #c49a0d); color: #ffffff; border-radius: 6px; font-weight: 600; transition: all 0.2s ease; cursor: pointer;">
                                                <i class="bi bi-check-circle" style="margin-right: 6px;"></i> Approve
                                            </button>
                                        </div>
                                    </article>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="empty-state d-none" id="filteredEmpty">No data available</div>
                <?php endif; ?>
            </section>

            <section class="dashboard-card" id="students" style="background: #ffffff; border: 1px solid #e0e0e0; border-radius: 12px; padding: 28px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                    <div>
                        <h2 class="h5 fw-bold mb-1" style="color: #182033;">Assigned Students</h2>
                        <p class="text-muted mb-0" style="font-size: 0.9rem;">Students attached to your supervised projects</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0" style="border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid #e0e0e0; background: rgba(100,64,39,0.02);">
                                <th style="padding: 16px; font-weight: 600; color: #643f27; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.3px;">Student</th>
                                <th style="padding: 16px; font-weight: 600; color: #643f27; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.3px;">Project</th>
                                <th style="padding: 16px; font-weight: 600; color: #643f27; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.3px;">Status</th>
                                <th style="padding: 16px; font-weight: 600; color: #643f27; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.3px;">Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$projects || !$assignedStudents): ?>
                                <tr><td colspan="4" class="text-center text-muted py-5">No data available</td></tr>
                            <?php else: ?>
                                <?php foreach ($projects as $project): ?>
                                    <?php foreach ($project['students'] as $student): ?>
                                        <tr style="border-bottom: 1px solid #e0e0e0; transition: background 0.2s ease;">
                                            <td class="fw-semibold" style="padding: 16px; color: #182033;"><?= e($student['name']) ?></td>
                                            <td style="padding: 16px; color: #737b8c;"><?= e($project['title']) ?></td>
                                            <td style="padding: 16px;"><span class="status-badge status-<?= e($project['status']) ?>" style="padding: 6px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;"><?= e($statusLabels[$project['status']] ?? $project['status']) ?></span></td>
                                            <td style="padding: 16px; color: #737b8c;"><?= $project['submitted_at'] ? e(date('d/m/Y', strtotime((string) $project['submitted_at']))) : 'No data available' ?></td>
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

<style>
    .btn-comment:hover {
        background: rgba(212,165,116,0.1) !important;
        border-color: #a67c52 !important;
        box-shadow: none !important;
        transform: none !important;
    }
    .btn-reject:hover {
        background: rgba(231,76,60,0.1) !important;
        border-color: #c23c2a !important;
        box-shadow: none !important;
        transform: none !important;
    }
    .btn-approve:hover {
        background: #c49a0d !important;
        box-shadow: none !important;
        transform: none !important;
    }
    table tbody tr:hover {
        background: rgba(100,64,39,0.02) !important;
    }

    .review-modal .modal-content {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 28px 70px rgba(24,32,51,0.2);
        overflow: hidden;
    }
    .review-modal .modal-header {
        padding: 22px 24px 14px;
        border-bottom: 1px solid rgba(100,64,39,0.08);
    }
    .review-modal .modal-body {
        padding: 22px 24px;
    }
    .review-modal .modal-footer {
        padding: 16px 24px 22px;
        border-top: 1px solid rgba(100,64,39,0.08);
    }
    .review-icon {
        width: 46px;
        height: 46px;
        display: inline-grid;
        place-items: center;
        border-radius: 14px;
        background: rgba(218,169,12,0.12);
        color: #9b7409;
        font-size: 1.35rem;
    }
    .review-icon.reject {
        background: rgba(231,76,60,0.1);
        color: #c23c2a;
    }
    .review-summary {
        padding: 14px 16px;
        border: 1px solid rgba(100,64,39,0.1);
        border-radius: 12px;
        background: #fbfaf8;
        color: #182033;
        font-weight: 700;
    }
    .btn-modal-primary,
    .btn-modal-danger {
        min-height: 42px;
        border-radius: 10px;
        padding: 9px 16px;
        font-weight: 700;
    }
    .btn-modal-primary {
        border: 1px solid #d1a20d;
        background: #d1a20d;
        color: #fff;
    }
    .btn-modal-danger {
        border: 1px solid #e74c3c;
        background: #e74c3c;
        color: #fff;
    }
    .btn-modal-secondary {
        min-height: 42px;
        border-radius: 10px;
        border: 1px solid #ddd5cb;
        background: #fff;
        color: #643f27;
        font-weight: 700;
    }
</style>

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

<form method="post" id="approveReviewForm" class="d-none">
    <input type="hidden" name="action" value="approve">
    <input type="hidden" name="project_id" id="approveProjectId">
</form>

<div class="modal fade review-modal" id="reviewConfirmModal" tabindex="-1" aria-labelledby="reviewConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="review-icon" id="reviewConfirmIcon"><i class="bi bi-check2-circle"></i></div>
                    <div>
                        <h2 class="modal-title h5 fw-bold mb-1" id="reviewConfirmTitle">Confirm review action</h2>
                        <p class="text-muted mb-0 small" id="reviewConfirmSubtitle">Please confirm before continuing.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" id="reviewConfirmMessage">This action will update the project status.</p>
                <div class="review-summary" id="reviewConfirmProject">Project</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-modal-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-modal-primary" id="reviewConfirmButton">Confirm</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade review-modal" id="rejectFeedbackModal" tabindex="-1" aria-labelledby="rejectFeedbackTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" id="rejectReviewForm">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="review-icon reject"><i class="bi bi-chat-left-text"></i></div>
                    <div>
                        <h2 class="modal-title h5 fw-bold mb-1" id="rejectFeedbackTitle">Add rejection feedback?</h2>
                        <p class="text-muted mb-0 small">Optional feedback helps students understand what to revise.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="project_id" id="rejectProjectId">
                <div class="review-summary mb-3" id="rejectProjectTitle">Project</div>
                <label class="form-label fw-semibold" for="rejectFeedbackText">Feedback for student</label>
                <textarea class="form-control" id="rejectFeedbackText" name="rejection_feedback" rows="5" placeholder="Example: Please clarify the methodology and resubmit the proposal with updated references."></textarea>
                <p class="text-muted small mt-2 mb-0">You can skip this and reject without feedback.</p>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-modal-secondary" id="rejectWithoutFeedbackButton">Reject without feedback</button>
                <button type="submit" class="btn btn-modal-danger">Reject with feedback</button>
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

    const reviewConfirmModal = document.getElementById('reviewConfirmModal');
    const rejectFeedbackModal = document.getElementById('rejectFeedbackModal');
    const approveProjectId = document.getElementById('approveProjectId');
    const rejectProjectId = document.getElementById('rejectProjectId');
    const rejectFeedbackText = document.getElementById('rejectFeedbackText');
    const reviewConfirmButton = document.getElementById('reviewConfirmButton');
    let pendingReviewAction = 'approve';
    let pendingProjectId = '';
    let pendingProjectTitle = '';

    reviewConfirmModal?.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        pendingReviewAction = button?.dataset.reviewAction || 'approve';
        pendingProjectId = button?.dataset.projectId || '';
        pendingProjectTitle = button?.dataset.projectTitle || 'Selected project';

        const isReject = pendingReviewAction === 'reject';
        document.getElementById('reviewConfirmIcon').classList.toggle('reject', isReject);
        document.getElementById('reviewConfirmIcon').innerHTML = isReject ? '<i class="bi bi-x-circle"></i>' : '<i class="bi bi-check2-circle"></i>';
        document.getElementById('reviewConfirmTitle').textContent = isReject ? 'Confirm project rejection' : 'Confirm project approval';
        document.getElementById('reviewConfirmSubtitle').textContent = isReject ? 'This will mark the submission as rejected.' : 'This will mark the submission as approved.';
        document.getElementById('reviewConfirmMessage').textContent = isReject
            ? 'Are you sure you want to reject this project submission?'
            : 'Are you sure you want to approve this project submission?';
        document.getElementById('reviewConfirmProject').textContent = pendingProjectTitle;
        reviewConfirmButton.textContent = isReject ? 'Continue' : 'Approve project';
        reviewConfirmButton.className = isReject ? 'btn btn-modal-danger' : 'btn btn-modal-primary';
    });

    reviewConfirmButton?.addEventListener('click', () => {
        if (pendingReviewAction === 'reject') {
            rejectProjectId.value = pendingProjectId;
            document.getElementById('rejectProjectTitle').textContent = pendingProjectTitle;
            rejectFeedbackText.value = '';
            bootstrap.Modal.getOrCreateInstance(reviewConfirmModal).hide();
            bootstrap.Modal.getOrCreateInstance(rejectFeedbackModal).show();
            return;
        }

        approveProjectId.value = pendingProjectId;
        document.getElementById('approveReviewForm').submit();
    });

    document.getElementById('rejectWithoutFeedbackButton')?.addEventListener('click', () => {
        rejectFeedbackText.value = '';
    });
</script>
</body>
</html>
