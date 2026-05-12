<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['lecturer_logged_in']) || $_SESSION['lecturer_logged_in'] !== true) {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/../database/db.php';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function decryptValue(?string $value): string
{
    if ($value === null || $value === '') return '';
    try {
        return decryptData($value);
    } catch (Throwable $error) {
        return '';
    }
}

function lecturerOwnsProject(PDO $db, int $lecturerId, int $projectId): bool
{
    $stmt = $db->prepare('SELECT COUNT(*) FROM projects WHERE project_id = ? AND lecturer_id = ?');
    $stmt->execute([$projectId, $lecturerId]);
    return (int) $stmt->fetchColumn() > 0;
}

$lecturerId       = (int) ($_SESSION['user_id'] ?? 0);
$lecturerName     = trim((string) ($_SESSION['user_name'] ?? 'Lecturer'));
$lecturerInitials = implode('', array_slice(array_map(
    static fn($part) => strtoupper(substr($part, 0, 1)),
    array_filter(preg_split('/\s+/', $lecturerName) ?: [])
), 0, 2)) ?: 'L';

$flashMessage = '';
$flashType    = 'success';

// ── Handle mark submission ──────────────────────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $projectId     = (int) ($_POST['project_id']     ?? 0);
    $markReport    = (int) ($_POST['mark_report']    ?? 0);
    $markPresent   = (int) ($_POST['mark_present']   ?? 0);
    $markImpl      = (int) ($_POST['mark_impl']      ?? 0);
    $markSuper     = (int) ($_POST['mark_super']     ?? 0);

    // Basic validation against schema caps
    $valid =
        $markReport  >= 0 && $markReport  <= 30 &&
        $markPresent >= 0 && $markPresent <= 25 &&
        $markImpl    >= 0 && $markImpl    <= 30 &&
        $markSuper   >= 0 && $markSuper   <= 15;

    if ($projectId > 0 && $valid && lecturerOwnsProject($db, $lecturerId, $projectId)) {
        try {
            // We store marks as a JSON blob in the comments table (content_encrypted)
            // with a special sentinel prefix so we can distinguish marks from regular comments.
            // This avoids schema changes while keeping data encrypted at rest.
            $payload = json_encode([
                '_type'        => 'marks',
                'report'       => $markReport,
                'presentation' => $markPresent,
                'implementation' => $markImpl,
                'supervisor'   => $markSuper,
                'total'        => $markReport + $markPresent + $markImpl + $markSuper,
                'saved_at'     => date('Y-m-d H:i:s'),
            ], JSON_THROW_ON_ERROR);

            // Upsert: delete previous marks entry then insert fresh one
            $delStmt = $db->prepare(
                "DELETE FROM comments
                 WHERE project_id = ? AND user_id = ?
                   AND content_encrypted LIKE '%__marks__%'"
            );
            // We tag the encrypted blob with a unique searchable marker BEFORE encrypting
            $tagged  = '__marks__' . $payload;
            $enc     = encryptData($tagged);

            // Delete old marks rows (match by decrypting is expensive; use a side-channel marker)
            // Instead: just always insert — UI will show the latest one.
            $insStmt = $db->prepare(
                'INSERT INTO comments (project_id, user_id, content_encrypted) VALUES (?, ?, ?)'
            );
            $insStmt->execute([$projectId, $lecturerId, $enc]);

            $flashMessage = 'Marks saved successfully!';
            $flashType    = 'success';
        } catch (Throwable $error) {
            $flashMessage = 'Unable to save marks. Please try again.';
            $flashType    = 'danger';
        }
    } else {
        $flashMessage = 'Invalid marks or unauthorised project.';
        $flashType    = 'danger';
    }
}

// ── Fetch projects + their latest saved marks ───────────────────────────────
$projects     = [];
$statusLabels = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];

try {
    $stmt = $db->prepare(
        "SELECT p.project_id, p.title_encrypted, p.study_year,
                latest.status, latest.submitted_at
         FROM projects p
         LEFT JOIN LATERAL (
             SELECT status, submitted_at
             FROM submissions
             WHERE submissions.project_id = p.project_id
             ORDER BY submitted_at DESC
             LIMIT 1
         ) latest ON TRUE
         WHERE p.lecturer_id = ?
         ORDER BY p.project_id ASC"
    );
    $stmt->execute([$lecturerId]);
    $projectRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $studentStmt = $db->prepare(
        "SELECT u.name_encrypted
         FROM project_members pm
         INNER JOIN users u ON u.user_id = pm.user_id
         WHERE pm.project_id = ?
         ORDER BY pm.role DESC, u.user_id ASC"
    );

    // Fetch latest marks for each project (last comment from this lecturer tagged __marks__)
    $marksStmt = $db->prepare(
        "SELECT content_encrypted
         FROM comments
         WHERE project_id = ? AND user_id = ?
         ORDER BY created_at DESC"
    );

    foreach ($projectRows as $row) {
        // Students
        $studentStmt->execute([(int) $row['project_id']]);
        $students = [];
        foreach ($studentStmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
            $name = decryptValue($s['name_encrypted'] ?? '');
            if ($name !== '') $students[] = $name;
        }

        // Marks
        $marks = null;
        $marksStmt->execute([(int) $row['project_id'], $lecturerId]);
        foreach ($marksStmt->fetchAll(PDO::FETCH_ASSOC) as $cRow) {
            try {
                $dec = decryptData($cRow['content_encrypted']);
                if (str_starts_with($dec, '__marks__')) {
                    $json  = substr($dec, strlen('__marks__'));
                    $marks = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                    break;
                }
            } catch (Throwable $ignored) {}
        }

        $projects[] = [
            'id'           => (int) $row['project_id'],
            'code'         => 'UTM-FYP-' . str_pad((string) $row['project_id'], 4, '0', STR_PAD_LEFT),
            'title'        => decryptValue($row['title_encrypted'] ?? '') ?: 'No data available',
            'study_year'   => $row['study_year'] ?? '',
            'status'       => $row['status'] ?: 'pending',
            'submitted_at' => $row['submitted_at'] ?? null,
            'students'     => $students,
            'marks'        => $marks,
        ];
    }
} catch (Throwable $error) {
    $flashMessage = $flashMessage ?: 'Unable to load grade data.';
    $flashType    = 'danger';
}

// Summary counts
$totalProjects  = count($projects);
$gradedProjects = count(array_filter($projects, static fn($p) => $p['marks'] !== null));
$pendingGrades  = $totalProjects - $gradedProjects;
$avgTotal       = $gradedProjects > 0
    ? round(array_sum(array_map(static fn($p) => $p['marks']['total'] ?? 0,
        array_filter($projects, static fn($p) => $p['marks'] !== null))) / $gradedProjects, 1)
    : 0;

function gradeLabel(int $total): string {
    return match (true) {
        $total >= 90 => 'A+',  $total >= 80 => 'A',  $total >= 75 => 'A-',
        $total >= 70 => 'B+',  $total >= 65 => 'B',  $total >= 60 => 'B-',
        $total >= 55 => 'C+',  $total >= 50 => 'C',  $total >= 45 => 'C-',
        $total >= 40 => 'D',   default       => 'F',
    };
}
?>
<?php
$lecturerHeaderSkipDashboardData = true;
require_once __DIR__ . '/lecturer_header.php';
?>
        <div class="content">

            <!-- Flash -->
            <?php if ($flashMessage): ?>
                <div class="alert alert-<?= e($flashType) ?> alert-dismissible fade show" role="alert">
                    <?= e($flashMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Page title -->
            <section class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1" style="color:var(--lecturer-maroon);">Final Grades</h1>
                    <p class="text-muted mb-0">Assign and manage marks for each project submission</p>
                </div>
            </section>

            <!-- Stats row -->
            <div class="row g-4 mb-4">
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
                                <p class="text-muted fw-semibold mb-2">Graded</p>
                                <strong class="stat-value text-success"><?= e($gradedProjects) ?></strong>
                            </div>
                            <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted fw-semibold mb-2">Pending Grades</p>
                                <strong class="stat-value text-warning"><?= e($pendingGrades) ?></strong>
                            </div>
                            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                        </div>
                    </article>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <article class="stat-card">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <p class="text-muted fw-semibold mb-2">Class Average</p>
                                <strong class="stat-value" style="color:var(--lecturer-maroon);">
                                    <?= $gradedProjects > 0 ? e($avgTotal) : '—' ?>
                                </strong>
                            </div>
                            <div class="stat-icon"><i class="bi bi-bar-chart-line-fill"></i></div>
                        </div>
                    </article>
                </div>
            </div>

            <!-- Toolbar -->
            <section class="toolbar mb-4">
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-lg">
                        <div class="input-group">
                            <span class="input-group-text search-control bg-white border-end-0">
                                <i class="bi bi-search"></i>
                            </span>
                            <input id="gradeSearch" class="form-control search-control border-start-0"
                                   type="search" placeholder="Search project or student name…">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <select id="gradeFilter" class="form-select search-control">
                            <option value="all">All Projects</option>
                            <option value="graded">Graded</option>
                            <option value="ungraded">Not Graded</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <select id="yearFilter" class="form-select search-control">
                            <option value="all">All Study Years</option>
                            <?php foreach (array_unique(array_filter(array_column($projects, 'study_year'))) as $yr): ?>
                                <option value="<?= e($yr) ?>">Year <?= e($yr) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </section>

            <!-- Grade cards -->
            <?php if (!$projects): ?>
                <div class="empty-state">
                    <div><i class="bi bi-star fs-2 d-block mb-2"></i>No projects found for grading.</div>
                </div>
            <?php else: ?>
                <div class="row g-4" id="gradeGrid">
                    <?php foreach ($projects as $project):
                        $marks      = $project['marks'];
                        $isGraded   = $marks !== null;
                        $total      = $isGraded ? (int)($marks['total'] ?? 0) : 0;
                        $grade      = $isGraded ? gradeLabel($total) : null;
                        $searchText = strtolower($project['title'] . ' ' . implode(' ', $project['students']));
                    ?>
                    <div class="col-12 col-lg-6 col-xxl-4 grade-item"
                         data-graded="<?= $isGraded ? 'graded' : 'ungraded' ?>"
                         data-year="<?= e($project['study_year']) ?>"
                         data-search="<?= e($searchText) ?>">
                        <article class="grade-card">

                            <!-- Card header -->
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div class="grade-badge grade-<?= $isGraded ? e($grade) : 'ungraded' ?>">
                                    <?= $isGraded ? e($grade) : '?' ?>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <small class="text-muted d-block mb-1"><?= e($project['code']) ?></small>
                                    <h2 class="project-title mb-0"><?= e($project['title']) ?></h2>
                                </div>
                                <span class="status-badge status-<?= e($project['status']) ?> flex-shrink-0">
                                    <?= e($statusLabels[$project['status']] ?? $project['status']) ?>
                                </span>
                            </div>

                            <!-- Students -->
                            <div class="mb-3" style="font-size:.875rem; color:var(--lecturer-text); line-height:1.7;">
                                <strong><?= e(count($project['students'])) ?> Student<?= count($project['students']) !== 1 ? 's' : '' ?></strong>
                                <?php foreach ($project['students'] as $idx => $sname): ?>
                                    <div><?= e($idx + 1) ?>. <?= e($sname) ?></div>
                                <?php endforeach; ?>
                                <?php if (empty($project['students'])): ?>
                                    <div class="text-muted">No students listed</div>
                                <?php endif; ?>
                            </div>

                            <!-- Mark breakdown (only if graded) -->
                            <?php if ($isGraded): ?>
                                <div class="mb-3">
                                    <div class="mark-row">
                                        <span class="mark-label">Report <small class="text-muted">/30</small></span>
                                        <div class="mark-bar-wrap"><div class="mark-bar" style="width:<?= round(($marks['report']/30)*100) ?>%"></div></div>
                                        <span class="mark-score"><?= e($marks['report']) ?></span>
                                    </div>
                                    <div class="mark-row">
                                        <span class="mark-label">Presentation <small class="text-muted">/25</small></span>
                                        <div class="mark-bar-wrap"><div class="mark-bar" style="width:<?= round(($marks['presentation']/25)*100) ?>%"></div></div>
                                        <span class="mark-score"><?= e($marks['presentation']) ?></span>
                                    </div>
                                    <div class="mark-row">
                                        <span class="mark-label">Implementation <small class="text-muted">/30</small></span>
                                        <div class="mark-bar-wrap"><div class="mark-bar" style="width:<?= round(($marks['implementation']/30)*100) ?>%"></div></div>
                                        <span class="mark-score"><?= e($marks['implementation']) ?></span>
                                    </div>
                                    <div class="mark-row">
                                        <span class="mark-label">Supervisor <small class="text-muted">/15</small></span>
                                        <div class="mark-bar-wrap"><div class="mark-bar" style="width:<?= round(($marks['supervisor']/15)*100) ?>%"></div></div>
                                        <span class="mark-score"><?= e($marks['supervisor']) ?></span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mt-2 pt-2"
                                         style="border-top:1px solid var(--lecturer-border);">
                                        <span class="text-muted" style="font-size:.82rem;">Total / 100</span>
                                        <strong style="font-size:1.1rem; color:var(--lecturer-maroon);">
                                            <?= e($total) ?> — <?= e($grade) ?>
                                        </strong>
                                    </div>
                                    <?php if (!empty($marks['saved_at'])): ?>
                                        <div class="text-muted mt-1" style="font-size:.75rem;">
                                            Last updated: <?= e(date('d/m/Y H:i', strtotime($marks['saved_at']))) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="mb-3 p-3 text-center text-muted"
                                     style="background:#f8f9fc; border:1px dashed var(--lecturer-border); border-radius:10px; font-size:.875rem;">
                                    <i class="bi bi-pencil-square me-1"></i>No marks assigned yet
                                </div>
                            <?php endif; ?>

                            <!-- Actions -->
                            <div class="action-row">
                                <button class="btn btn-assign"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#marksModal"
                                        data-project-id="<?= e($project['id']) ?>"
                                        data-project-title="<?= e($project['title']) ?>"
                                        data-mark-report="<?= e($marks['report'] ?? '') ?>"
                                        data-mark-present="<?= e($marks['presentation'] ?? '') ?>"
                                        data-mark-impl="<?= e($marks['implementation'] ?? '') ?>"
                                        data-mark-super="<?= e($marks['supervisor'] ?? '') ?>">
                                    <i class="bi bi-pencil-fill me-1"></i>
                                    <?= $isGraded ? 'Edit Marks' : 'Assign Marks' ?>
                                </button>
                                <a class="btn btn-view" href="lecturer_submissions.php">
                                    <i class="bi bi-eye me-1"></i>View Submission
                                </a>
                            </div>
                        </article>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="empty-state d-none mt-4" id="filteredEmpty">No projects match your filters.</div>
            <?php endif; ?>
        </div><!-- /content -->
    </main>
</div><!-- /lecturer-shell -->

<!-- ── Assign Marks Modal ── -->
<div class="modal fade" id="marksModal" tabindex="-1" aria-labelledby="marksModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:500px;">
        <form class="modal-content" method="post" id="marksForm">
            <div class="modal-header">
                <div>
                    <h2 class="h5 fw-bold mb-1" id="marksModalLabel" style="color:var(--lecturer-maroon);">Assign Marks</h2>
                    <p class="text-muted small mb-0" id="marksProjectTitle">Enter marks for each component</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" name="project_id" id="marksProjectId">

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="mark-input-group">
                            <label for="markReport">Report</label>
                            <input type="number" id="markReport" name="mark_report"
                                   class="form-control" min="0" max="30"
                                   placeholder="0 – 30" required>
                            <div class="mark-cap">Maximum: 30 marks</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mark-input-group">
                            <label for="markPresent">Presentation</label>
                            <input type="number" id="markPresent" name="mark_present"
                                   class="form-control" min="0" max="25"
                                   placeholder="0 – 25" required>
                            <div class="mark-cap">Maximum: 25 marks</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mark-input-group">
                            <label for="markImpl">Implementation</label>
                            <input type="number" id="markImpl" name="mark_impl"
                                   class="form-control" min="0" max="30"
                                   placeholder="0 – 30" required>
                            <div class="mark-cap">Maximum: 30 marks</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mark-input-group">
                            <label for="markSuper">Supervisor</label>
                            <input type="number" id="markSuper" name="mark_super"
                                   class="form-control" min="0" max="15"
                                   placeholder="0 – 15" required>
                            <div class="mark-cap">Maximum: 15 marks</div>
                        </div>
                    </div>
                </div>

                <!-- Live total preview -->
                <div class="total-preview">
                    <div>
                        <div class="tp-label">Total Score / 100</div>
                        <div class="tp-value" id="totalPreview">0</div>
                    </div>
                    <span class="tp-grade status-badge" id="gradePreview" style="font-size:1rem;">—</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn fw-bold"
                        style="background:var(--lecturer-maroon);color:#fff;border-radius:10px;padding:10px 24px;">
                    <i class="bi bi-save me-1"></i>Save Marks
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Filter / search ──
const searchInput  = document.getElementById('gradeSearch');
const gradeFilter  = document.getElementById('gradeFilter');
const yearFilter   = document.getElementById('yearFilter');
const items        = Array.from(document.querySelectorAll('.grade-item'));
const emptyDiv     = document.getElementById('filteredEmpty');

function applyFilters() {
    const query  = (searchInput?.value || '').trim().toLowerCase();
    const graded = gradeFilter?.value  || 'all';
    const year   = yearFilter?.value   || 'all';
    let visible  = 0;
    items.forEach(item => {
        const show = (!query  || item.dataset.search.includes(query))
                  && (graded === 'all' || item.dataset.graded === graded)
                  && (year   === 'all' || item.dataset.year   === year);
        item.classList.toggle('d-none', !show);
        if (show) visible++;
    });
    emptyDiv?.classList.toggle('d-none', visible !== 0);
}
searchInput?.addEventListener('input', applyFilters);
gradeFilter?.addEventListener('change', applyFilters);
yearFilter?.addEventListener('change', applyFilters);

// ── Modal population ──
document.getElementById('marksModal')?.addEventListener('show.bs.modal', (e) => {
    const btn = e.relatedTarget;
    document.getElementById('marksProjectId').value     = btn?.dataset.projectId    || '';
    document.getElementById('marksProjectTitle').textContent = btn?.dataset.projectTitle || '';
    document.getElementById('markReport').value  = btn?.dataset.markReport  || '';
    document.getElementById('markPresent').value = btn?.dataset.markPresent || '';
    document.getElementById('markImpl').value    = btn?.dataset.markImpl    || '';
    document.getElementById('markSuper').value   = btn?.dataset.markSuper   || '';
    updateTotal();
});

// ── Live total + grade preview ──
const inputs = ['markReport','markPresent','markImpl','markSuper'];
inputs.forEach(id => document.getElementById(id)?.addEventListener('input', updateTotal));

function gradeLabel(total) {
    if (total >= 90) return 'A+';
    if (total >= 80) return 'A';
    if (total >= 75) return 'A-';
    if (total >= 70) return 'B+';
    if (total >= 65) return 'B';
    if (total >= 60) return 'B-';
    if (total >= 55) return 'C+';
    if (total >= 50) return 'C';
    if (total >= 45) return 'C-';
    if (total >= 40) return 'D';
    return 'F';
}

function gradeClass(grade) {
    const map = {
        'A+':'status-approved','A':'status-approved','A-':'status-approved',
        'B+':'status-pending','B':'status-pending','B-':'status-pending',
        'C+':'status-pending','C':'status-pending','C-':'status-pending',
        'D':'status-rejected','F':'status-rejected'
    };
    return map[grade] || '';
}

function updateTotal() {
    const r = Math.min(parseInt(document.getElementById('markReport')?.value  || 0, 10), 30);
    const p = Math.min(parseInt(document.getElementById('markPresent')?.value || 0, 10), 25);
    const i = Math.min(parseInt(document.getElementById('markImpl')?.value    || 0, 10), 30);
    const s = Math.min(parseInt(document.getElementById('markSuper')?.value   || 0, 10), 15);
    const total = (isNaN(r)?0:r) + (isNaN(p)?0:p) + (isNaN(i)?0:i) + (isNaN(s)?0:s);
    const grade = gradeLabel(total);

    const tv = document.getElementById('totalPreview');
    const gv = document.getElementById('gradePreview');
    if (tv) tv.textContent = total;
    if (gv) {
        gv.textContent = grade;
        gv.className   = 'tp-grade status-badge ' + gradeClass(grade);
    }
}
</script>
</body>
</html>
