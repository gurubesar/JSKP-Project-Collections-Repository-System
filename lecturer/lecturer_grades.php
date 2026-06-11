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
    if ($value === null || $value === '') {
        return '';
    }

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

function rubricDefinition(): array
{
    return [
        [
            'id' => 'proposal',
            'title' => 'Proposal: Week 1',
            'subtitle' => '10%',
            'criteria' => [
                [
                    'id' => 'problem_description',
                    'label' => 'Problem Description & Significance',
                    'max' => 3,
                    'levels' => [
                        '3' => 'Exceptional - well-defined, significant, clearly contextualized',
                        '2' => 'Adequate - defined, significance/context slightly weak',
                        '1' => 'Marginal - vague or lacking background research',
                        '0' => 'Inadequate - no clear problem identified',
                    ],
                ],
                [
                    'id' => 'objectives_deliverables',
                    'label' => 'Objectives & Deliverables',
                    'max' => 3,
                    'levels' => [
                        '3' => 'Exceptional - SMART objectives and deliverables',
                        '2' => 'Adequate - clear but some lack specificity',
                        '1' => 'Marginal - too ambitious or vague',
                        '0' => 'Inadequate - no clear objectives',
                    ],
                ],
                [
                    'id' => 'methodology_technical',
                    'label' => 'Methodology & Technical Approach',
                    'max' => 2,
                    'levels' => [
                        '2' => 'Exceptional - detailed approach, algorithms, and tools',
                        '1.5' => 'Adequate - sound methodology with minor missing details',
                        '1' => 'Marginal - superficial or partly inappropriate',
                        '0' => 'Inadequate - no clear technical approach',
                    ],
                ],
                [
                    'id' => 'feasibility_timeline',
                    'label' => 'Feasibility & Timeline',
                    'max' => 2,
                    'levels' => [
                        '2' => 'Exceptional - realistic schedule with milestones and risks',
                        '1.5' => 'Adequate - mostly realistic with minor risks',
                        '1' => 'Marginal - vague or unrealistic schedule',
                        '0' => 'Inadequate - no timeline provided',
                    ],
                ],
            ],
        ],
        [
            'id' => 'sprint1',
            'title' => 'Sprint 1 Review: Week 3',
            'subtitle' => '24%',
            'criteria' => [
                [
                    'id' => 's1_user_stories',
                    'label' => 'Product Backlog - User Stories Quality',
                    'max' => 2,
                    'levels' => [
                        '2' => 'Excellent - clear, structured, valuable',
                        '1' => 'Good - mostly clear',
                        '0' => 'Weak - unclear, inconsistent',
                    ],
                ],
                [
                    'id' => 's1_prioritization',
                    'label' => 'Product Backlog - Prioritization',
                    'max' => 1,
                    'levels' => [
                        '1' => 'Excellent - logical, well-ordered',
                        '0.5' => 'Good - some logic',
                        '0' => 'Weak - random',
                    ],
                ],
                [
                    'id' => 's1_planning',
                    'label' => 'Sprint Execution - Planning',
                    'max' => 2,
                    'levels' => [
                        '2' => 'Excellent - realistic, well-scoped',
                        '1' => 'Good - minor issues',
                        '0' => 'Weak - poor planning',
                    ],
                ],
                [
                    'id' => 's1_completion',
                    'label' => 'Sprint Execution - Completion',
                    'max' => 2,
                    'levels' => [
                        '2' => 'Excellent - most stories done',
                        '1' => 'Good - some incomplete',
                        '0' => 'Weak - many incomplete',
                    ],
                ],
                [
                    'id' => 's1_features',
                    'label' => 'System Functionality - Features',
                    'max' => 3,
                    'levels' => [
                        '3' => 'Excellent - fully working',
                        '2' => 'Good - mostly working',
                        '1' => 'Weak - many broken',
                    ],
                ],
                [
                    'id' => 's1_integration',
                    'label' => 'System Functionality - Integration',
                    'max' => 3,
                    'levels' => [
                        '3' => 'Excellent - smooth flow',
                        '2' => 'Good - minor issues',
                        '1' => 'Weak - disconnected',
                    ],
                ],
                [
                    'id' => 's1_test_coverage',
                    'label' => 'Testing & QA - Test Coverage',
                    'max' => 2,
                    'levels' => [
                        '2' => 'Excellent - comprehensive',
                        '1' => 'Good - partial',
                        '0' => 'Weak - minimal',
                    ],
                ],
                [
                    'id' => 's1_bug_tracking',
                    'label' => 'Testing & QA - Bug Tracking',
                    'max' => 2,
                    'levels' => [
                        '2' => 'Excellent - proper tickets',
                        '1' => 'Good - some tracking',
                        '0' => 'Weak - no tracking',
                    ],
                ],
                [
                    'id' => 's1_role_execution',
                    'label' => 'Scrum Roles & Collaboration - Role Execution',
                    'max' => 7,
                    'levels' => [
                        '7' => 'Excellent - clear, active roles',
                        '5' => 'Good - some imbalance',
                        '3' => 'Weak - poor participation',
                    ],
                ],
            ],
        ],
        [
            'id' => 'sprint2',
            'title' => 'Sprint 2 Review: Week 5',
            'subtitle' => '24%',
            'criteria' => [
                [
                    'id' => 's2_user_stories',
                    'label' => 'Product Backlog - User Stories Quality',
                    'max' => 2,
                    'levels' => [
                        '2' => 'Excellent - clear, structured, valuable',
                        '1' => 'Good - mostly clear',
                        '0' => 'Weak - unclear, inconsistent',
                    ],
                ],
                [
                    'id' => 's2_prioritization',
                    'label' => 'Product Backlog - Prioritization',
                    'max' => 1,
                    'levels' => [
                        '1' => 'Excellent - logical, well-ordered',
                        '0.5' => 'Good - some logic',
                        '0' => 'Weak - random',
                    ],
                ],
                [
                    'id' => 's2_planning',
                    'label' => 'Sprint Execution - Planning',
                    'max' => 2,
                    'levels' => [
                        '2' => 'Excellent - realistic, well-scoped',
                        '1' => 'Good - minor issues',
                        '0' => 'Weak - poor planning',
                    ],
                ],
                [
                    'id' => 's2_completion',
                    'label' => 'Sprint Execution - Completion',
                    'max' => 2,
                    'levels' => [
                        '2' => 'Excellent - most stories done',
                        '1' => 'Good - some incomplete',
                        '0' => 'Weak - many incomplete',
                    ],
                ],
                [
                    'id' => 's2_features',
                    'label' => 'System Functionality - Features',
                    'max' => 3,
                    'levels' => [
                        '3' => 'Excellent - fully working',
                        '2' => 'Good - mostly working',
                        '1' => 'Weak - many broken',
                    ],
                ],
                [
                    'id' => 's2_integration',
                    'label' => 'System Functionality - Integration',
                    'max' => 3,
                    'levels' => [
                        '3' => 'Excellent - smooth flow',
                        '2' => 'Good - minor issues',
                        '1' => 'Weak - disconnected',
                    ],
                ],
                [
                    'id' => 's2_test_coverage',
                    'label' => 'Testing & QA - Test Coverage',
                    'max' => 2,
                    'levels' => [
                        '2' => 'Excellent - comprehensive',
                        '1' => 'Good - partial',
                        '0' => 'Weak - minimal',
                    ],
                ],
                [
                    'id' => 's2_bug_tracking',
                    'label' => 'Testing & QA - Bug Tracking',
                    'max' => 2,
                    'levels' => [
                        '2' => 'Excellent - proper tickets',
                        '1' => 'Good - some tracking',
                        '0' => 'Weak - no tracking',
                    ],
                ],
                [
                    'id' => 's2_role_execution',
                    'label' => 'Scrum Roles & Collaboration - Role Execution',
                    'max' => 7,
                    'levels' => [
                        '7' => 'Excellent - clear, active roles',
                        '5' => 'Good - some imbalance',
                        '3' => 'Weak - poor participation',
                    ],
                ],
            ],
        ],
        [
            'id' => 'sprint3',
            'title' => 'Sprint 3 Review: Week 7',
            'subtitle' => '12%',
            'criteria' => [
                [
                    'id' => 's3_user_stories',
                    'label' => 'Product Backlog - User Stories Quality',
                    'max' => 2,
                    'levels' => [
                        '2' => 'Excellent - clear, structured, valuable',
                        '1' => 'Good - mostly clear',
                        '0' => 'Weak - unclear, inconsistent',
                    ],
                ],
                [
                    'id' => 's3_prioritization',
                    'label' => 'Product Backlog - Prioritization',
                    'max' => 1,
                    'levels' => [
                        '1' => 'Excellent - logical, well-ordered',
                        '0.5' => 'Good - some logic',
                        '0' => 'Weak - random',
                    ],
                ],
                [
                    'id' => 's3_planning',
                    'label' => 'Sprint Execution - Planning',
                    'max' => 2,
                    'levels' => [
                        '2' => 'Excellent - realistic, well-scoped',
                        '1' => 'Good - minor issues',
                        '0' => 'Weak - poor planning',
                    ],
                ],
                [
                    'id' => 's3_completion',
                    'label' => 'Sprint Execution - Completion',
                    'max' => 1,
                    'levels' => [
                        '1' => 'Excellent - most stories done',
                        '0.5' => 'Good - some incomplete',
                        '0' => 'Weak - many incomplete',
                    ],
                ],
                [
                    'id' => 's3_role_execution',
                    'label' => 'Scrum Roles & Collaboration - Role Execution',
                    'max' => 6,
                    'levels' => [
                        '6' => 'Excellent - clear, active roles',
                        '4' => 'Good - some imbalance',
                        '2' => 'Weak - poor participation',
                    ],
                ],
            ],
        ],
        [
            'id' => 'final_product',
            'title' => 'Final Presentation Working Product',
            'subtitle' => '10%',
            'criteria' => [
                [
                    'id' => 'demo_quality',
                    'label' => 'Demo Quality',
                    'max' => 5,
                    'levels' => [
                        '5' => 'Excellent - smooth, confident',
                        '3' => 'Good - minor issues',
                        '1' => 'Weak - poor demo',
                    ],
                ],
                [
                    'id' => 'explanation',
                    'label' => 'Explanation',
                    'max' => 5,
                    'levels' => [
                        '5' => 'Excellent - clear, structured',
                        '3' => 'Good - some confusion',
                        '1' => 'Weak - unclear',
                    ],
                ],
            ],
        ],
        [
            'id' => 'poster',
            'title' => 'Poster',
            'subtitle' => 'Rubric row weights',
            'criteria' => [
                [
                    'id' => 'poster_content',
                    'label' => 'Content Quality & Relevance',
                    'max' => 15,
                    'levels' => [
                        '15' => 'Excellent - clear, accurate, comprehensive',
                        '11.25' => 'Good - mostly clear, minor gaps',
                        '9' => 'Satisfactory - basic content, some unclear sections',
                        '6' => 'Weak - poor or irrelevant content',
                    ],
                ],
                [
                    'id' => 'poster_organization',
                    'label' => 'Organization & Structure',
                    'max' => 5,
                    'levels' => [
                        '5' => 'Excellent - logical flow and easy to follow',
                        '3.75' => 'Good - generally well-structured',
                        '3' => 'Satisfactory - some inconsistency',
                        '2' => 'Weak - disorganized and difficult to follow',
                    ],
                ],
                [
                    'id' => 'poster_technical',
                    'label' => 'Technical Depth & Accuracy',
                    'max' => 15,
                    'levels' => [
                        '15' => 'Excellent - strong technical knowledge',
                        '11.25' => 'Good - good understanding, minor errors',
                        '9' => 'Satisfactory - basic explanation, lacks depth',
                        '6' => 'Weak - many errors',
                    ],
                ],
                [
                    'id' => 'poster_presentation',
                    'label' => 'Presentation Skills (Oral Explanation)',
                    'max' => 10,
                    'levels' => [
                        '10' => 'Excellent - confident, clear, engaging',
                        '7.5' => 'Good - clear explanation, minor hesitation',
                        '6' => 'Satisfactory - basic, lacks confidence or clarity',
                        '4' => 'Weak - unclear or unprepared',
                    ],
                ],
                [
                    'id' => 'poster_creativity',
                    'label' => 'Creativity & Innovation',
                    'max' => 5,
                    'levels' => [
                        '5' => 'Excellent - highly creative and innovative',
                        '3.75' => 'Good - some creativity shown',
                        '3' => 'Satisfactory - limited creativity',
                        '2' => 'Weak - no creativity or originality',
                    ],
                ],
            ],
        ],
    ];
}

function flattenRubric(array $rubric): array
{
    $criteria = [];
    foreach ($rubric as $section) {
        foreach ($section['criteria'] as $criterion) {
            $criteria[$criterion['id']] = $criterion + ['section_id' => $section['id']];
        }
    }
    return $criteria;
}

function rubricMaxTotal(array $rubric): float
{
    $total = 0.0;
    foreach ($rubric as $section) {
        foreach ($section['criteria'] as $criterion) {
            $total += (float) $criterion['max'];
        }
    }
    return $total;
}

function formatScore(float $score): string
{
    return rtrim(rtrim(number_format($score, 2), '0'), '.');
}

function gradeLabel(float $percentage): string
{
    return match (true) {
        $percentage >= 90 => 'A+',
        $percentage >= 80 => 'A',
        $percentage >= 75 => 'A-',
        $percentage >= 70 => 'B+',
        $percentage >= 65 => 'B',
        $percentage >= 60 => 'B-',
        $percentage >= 55 => 'C+',
        $percentage >= 50 => 'C',
        $percentage >= 45 => 'C-',
        $percentage >= 40 => 'D',
        default => 'F',
    };
}

function isAllowedRubricValue(array $criterion, string $value): bool
{
    return array_key_exists($value, $criterion['levels']);
}

function sectionScore(array $section, array $scores): float
{
    $total = 0.0;
    foreach ($section['criteria'] as $criterion) {
        $total += (float) ($scores[$criterion['id']] ?? 0);
    }
    return $total;
}

function sectionMax(array $section): float
{
    return array_sum(array_map(static fn($criterion) => (float) $criterion['max'], $section['criteria']));
}

function normalizeSavedMarks(?array $marks): ?array
{
    if ($marks === null) {
        return null;
    }

    if (isset($marks['criteria_scores'])) {
        return $marks;
    }

    if (isset($marks['total'])) {
        $total = (float) $marks['total'];
        return [
            '_type' => 'legacy_marks',
            'criteria_scores' => [],
            'raw_total' => $total,
            'max_total' => 100,
            'percentage' => $total,
            'saved_at' => $marks['saved_at'] ?? null,
            'legacy' => true,
        ];
    }

    return null;
}

$rubric = rubricDefinition();
$criteriaById = flattenRubric($rubric);
$rubricMaxTotal = rubricMaxTotal($rubric);

$lecturerId = (int) ($_SESSION['user_id'] ?? 0);
$lecturerName = trim((string) ($_SESSION['user_name'] ?? 'Lecturer'));
$lecturerInitials = implode('', array_slice(array_map(
    static fn($part) => strtoupper(substr($part, 0, 1)),
    array_filter(preg_split('/\s+/', $lecturerName) ?: [])
), 0, 2)) ?: 'L';

$flashMessage = '';
$flashType = 'success';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $projectId = (int) ($_POST['project_id'] ?? 0);
    $postedScores = $_POST['rubric_scores'] ?? [];
    $criteriaScores = [];
    $valid = is_array($postedScores);

    if ($valid) {
        foreach ($criteriaById as $criterionId => $criterion) {
            $value = (string) ($postedScores[$criterionId] ?? '');
            if (!isAllowedRubricValue($criterion, $value)) {
                $valid = false;
                break;
            }
            $criteriaScores[$criterionId] = (float) $value;
        }
    }

    if ($projectId > 0 && $valid && lecturerOwnsProject($db, $lecturerId, $projectId)) {
        try {
            $rawTotal = array_sum($criteriaScores);
            $percentage = $rubricMaxTotal > 0 ? round(($rawTotal / $rubricMaxTotal) * 100, 2) : 0;

            $payload = json_encode([
                '_type' => 'rubric_marks',
                'criteria_scores' => $criteriaScores,
                'raw_total' => $rawTotal,
                'max_total' => $rubricMaxTotal,
                'percentage' => $percentage,
                'letter_grade' => gradeLabel($percentage),
                'saved_at' => date('Y-m-d H:i:s'),
            ], JSON_THROW_ON_ERROR);

            $stmt = $db->prepare(
                'INSERT INTO comments (project_id, user_id, content_encrypted) VALUES (?, ?, ?)'
            );
            $stmt->execute([$projectId, $lecturerId, encryptData('__marks__' . $payload)]);

            $flashMessage = 'Rubric grades saved successfully.';
            $flashType = 'success';
        } catch (Throwable $error) {
            $flashMessage = 'Unable to save rubric grades. Please try again.';
            $flashType = 'danger';
        }
    } else {
        $flashMessage = 'Invalid rubric grades or unauthorised project.';
        $flashType = 'danger';
    }
}

$projects = [];
$statusLabels = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];

try {
    $stmt = $db->prepare(
        "SELECT p.project_id, p.title_encrypted, p.study_year, p.created_at,
                s.submitted_at, s.status
         FROM projects p
         LEFT JOIN submissions s ON s.submission_id = (
             SELECT submission_id FROM submissions
             WHERE project_id = p.project_id
             ORDER BY submitted_at DESC
             LIMIT 1
         )
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

    $marksStmt = $db->prepare(
        "SELECT content_encrypted
         FROM comments
         WHERE project_id = ? AND user_id = ?
         ORDER BY created_at DESC, comment_id DESC"
    );

    foreach ($projectRows as $row) {
        $studentStmt->execute([(int) $row['project_id']]);
        $students = [];
        foreach ($studentStmt->fetchAll(PDO::FETCH_ASSOC) as $student) {
            $name = decryptValue($student['name_encrypted'] ?? '');
            if ($name !== '') {
                $students[] = $name;
            }
        }

        $marks = null;
        $marksStmt->execute([(int) $row['project_id'], $lecturerId]);
        foreach ($marksStmt->fetchAll(PDO::FETCH_ASSOC) as $commentRow) {
            try {
                $decrypted = decryptData($commentRow['content_encrypted']);
                if (str_starts_with($decrypted, '__marks__')) {
                    $marks = normalizeSavedMarks(json_decode(substr($decrypted, strlen('__marks__')), true, 512, JSON_THROW_ON_ERROR));
                    break;
                }
            } catch (Throwable $ignored) {
            }
        }

        $projects[] = [
            'id' => (int) $row['project_id'],
            'code' => 'UTM-FYP-' . str_pad((string) $row['project_id'], 4, '0', STR_PAD_LEFT),
            'title' => decryptValue($row['title_encrypted'] ?? '') ?: 'No data available',
            'study_year' => $row['study_year'] ?? '',
            'status' => $row['status'] ?: 'pending',
            'submitted_at' => $row['submitted_at'] ?? null,
            'students' => $students,
            'marks' => $marks,
        ];
    }
} catch (Throwable $error) {
    $flashMessage = $flashMessage ?: 'Unable to load grade data.';
    $flashType = 'danger';
}

$totalProjects = count($projects);
$gradedProjects = count(array_filter($projects, static fn($project) => $project['marks'] !== null));
$pendingGrades = $totalProjects - $gradedProjects;
$avgTotal = $gradedProjects > 0
    ? round(array_sum(array_map(
        static fn($project) => (float) ($project['marks']['percentage'] ?? 0),
        array_filter($projects, static fn($project) => $project['marks'] !== null)
    )) / $gradedProjects, 1)
    : 0;
?>
<?php
$lecturerHeaderSkipDashboardData = true;
require_once __DIR__ . '/lecturer_header.php';
?>
        <div class="content">
            <?php if ($flashMessage): ?>
                <div class="alert alert-<?= e($flashType) ?> alert-dismissible fade show" role="alert">
                    <?= e($flashMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <section class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1" style="color:var(--lecturer-maroon);">Rubric Grades</h1>
                    <p class="text-muted mb-0">Grade projects using the proposal, sprint review, final product, and poster rubric.</p>
                </div>
            </section>

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
                                    <?= $gradedProjects > 0 ? e($avgTotal . '%') : '-' ?>
                                </strong>
                            </div>
                            <div class="stat-icon"><i class="bi bi-bar-chart-line-fill"></i></div>
                        </div>
                    </article>
                </div>
            </div>

            <section class="toolbar mb-4">
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-lg">
                        <div class="input-group">
                            <span class="input-group-text search-control bg-white border-end-0">
                                <i class="bi bi-search"></i>
                            </span>
                            <input id="gradeSearch" class="form-control search-control border-start-0"
                                   type="search" placeholder="Search project or student name...">
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
                            <?php foreach (array_unique(array_filter(array_column($projects, 'study_year'))) as $year): ?>
                                <option value="<?= e($year) ?>">Year <?= e($year) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </section>

            <?php if (!$projects): ?>
                <div class="empty-state">
                    <div><i class="bi bi-star fs-2 d-block mb-2"></i>No projects found for grading.</div>
                </div>
            <?php else: ?>
                <div class="dashboard-card p-0 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Students</th>
                                    <th>Study Year</th>
                                    <th>Submission Status</th>
                                    <th>Grade</th>
                                    <th>Total</th>
                                    <th>Last Updated</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="gradeGrid">
                                <?php foreach ($projects as $project):
                                    $marks = $project['marks'];
                                    $isGraded = $marks !== null;
                                    $percentage = $isGraded ? (float) ($marks['percentage'] ?? 0) : 0;
                                    $rawTotal = $isGraded ? (float) ($marks['raw_total'] ?? 0) : 0;
                                    $maxTotal = $isGraded ? (float) ($marks['max_total'] ?? $rubricMaxTotal) : $rubricMaxTotal;
                                    $grade = $isGraded ? gradeLabel($percentage) : null;
                                    $scores = $isGraded ? ($marks['criteria_scores'] ?? []) : [];
                                    $searchText = strtolower($project['title'] . ' ' . implode(' ', $project['students']));
                                ?>
                                    <tr class="grade-item"
                                        data-graded="<?= $isGraded ? 'graded' : 'ungraded' ?>"
                                        data-year="<?= e($project['study_year']) ?>"
                                        data-search="<?= e($searchText) ?>">
                                        <td>
                                            <div class="fw-bold"><?= e($project['title']) ?></div>
                                            <small class="text-muted"><?= e($project['code']) ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= e(count($project['students'])) ?> Student<?= count($project['students']) !== 1 ? 's' : '' ?></div>
                                            <?php if ($project['students']): ?>
                                                <div class="small text-muted"><?= e(implode(', ', $project['students'])) ?></div>
                                            <?php else: ?>
                                                <div class="small text-muted">No students listed</div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $project['study_year'] !== '' ? e($project['study_year']) : 'N/A' ?></td>
                                        <td><span class="status-badge status-<?= e($project['status']) ?>"><?= e($statusLabels[$project['status']] ?? $project['status']) ?></span></td>
                                        <td>
                                            <span class="grade-badge grade-<?= $isGraded ? e($grade) : 'ungraded' ?>">
                                                <?= $isGraded ? e($grade) : '?' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($isGraded): ?>
                                                <strong><?= e(formatScore($percentage)) ?>%</strong>
                                                <div class="small text-muted"><?= e(formatScore($rawTotal)) ?> / <?= e(formatScore($maxTotal)) ?></div>
                                            <?php else: ?>
                                                <span class="text-muted">Not graded</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= !empty($marks['saved_at']) ? e(date('d/m/Y H:i', strtotime($marks['saved_at']))) : '<span class="text-muted">-</span>' ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex flex-wrap justify-content-end gap-2">
                                                <button class="btn btn-assign"
                                                        type="button"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#marksModal"
                                                        data-project-id="<?= e($project['id']) ?>"
                                                        data-project-title="<?= e($project['title']) ?>"
                                                        data-scores="<?= e(json_encode($scores)) ?>">
                                                    <i class="bi bi-pencil-fill me-1"></i>
                                                    <?= $isGraded ? 'Edit Rubric' : 'Grade Rubric' ?>
                                                </button>
                                              
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="empty-state d-none mt-4" id="filteredEmpty">No projects match your filters.</div>
            <?php endif; ?>
        </div>
    </main>
</div>

<div class="modal fade" id="marksModal" tabindex="-1" aria-labelledby="marksModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <form class="modal-content" method="post" id="marksForm">
            <div class="modal-header">
                <div>
                    <h2 class="h5 fw-bold mb-1" id="marksModalLabel" style="color:var(--lecturer-maroon);">Rubric Grades</h2>
                    <p class="text-muted small mb-0" id="marksProjectTitle">Select a rubric level for each criterion.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" name="project_id" id="marksProjectId">

                <div class="row g-3">
                    <?php foreach ($rubric as $section):
                        $sectionMax = sectionMax($section);
                    ?>
                        <div class="col-12 col-xl-6">
                            <section class="p-3 h-100" style="border:1px solid var(--lecturer-border); border-radius:12px; background:#fbfcfe;">
                                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                    <div>
                                        <h3 class="h6 fw-bold mb-1" style="color:var(--lecturer-maroon);"><?= e($section['title']) ?></h3>
                                        <div class="text-muted" style="font-size:.78rem;"><?= e($section['subtitle']) ?></div>
                                    </div>
                                    <span class="status-badge status-pending"><?= e(formatScore($sectionMax)) ?> pts</span>
                                </div>

                                <div class="row g-3">
                                    <?php foreach ($section['criteria'] as $criterion): ?>
                                        <div class="col-12">
                                            <div class="mark-input-group">
                                                <label for="score_<?= e($criterion['id']) ?>"><?= e($criterion['label']) ?></label>
                                                <select class="form-select rubric-score"
                                                        id="score_<?= e($criterion['id']) ?>"
                                                        name="rubric_scores[<?= e($criterion['id']) ?>]"
                                                        data-max="<?= e($criterion['max']) ?>"
                                                        required>
                                                    <option value="">Choose level</option>
                                                    <?php foreach ($criterion['levels'] as $value => $label): ?>
                                                        <option value="<?= e($value) ?>"><?= e(formatScore((float) $value)) ?> / <?= e(formatScore((float) $criterion['max'])) ?> - <?= e($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="total-preview mt-3">
                    <div>
                        <div class="tp-label">Raw Score / <?= e(formatScore($rubricMaxTotal)) ?></div>
                        <div class="tp-value" id="rawPreview">0</div>
                    </div>
                    <div class="text-end">
                        <div class="tp-label">Normalized Score / 100</div>
                        <div class="tp-value" id="totalPreview">0%</div>
                    </div>
                    <span class="tp-grade status-badge" id="gradePreview" style="font-size:1rem;">-</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn fw-bold"
                        style="background:var(--lecturer-maroon);color:#fff;border-radius:10px;padding:10px 24px;">
                    <i class="bi bi-save me-1"></i>Save Rubric Grades
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const searchInput = document.getElementById('gradeSearch');
const gradeFilter = document.getElementById('gradeFilter');
const yearFilter = document.getElementById('yearFilter');
const items = Array.from(document.querySelectorAll('.grade-item'));
const emptyDiv = document.getElementById('filteredEmpty');
const rubricMaxTotal = <?= json_encode($rubricMaxTotal) ?>;

function applyFilters() {
    const query = (searchInput?.value || '').trim().toLowerCase();
    const graded = gradeFilter?.value || 'all';
    const year = yearFilter?.value || 'all';
    let visible = 0;

    items.forEach(item => {
        const show = (!query || item.dataset.search.includes(query))
            && (graded === 'all' || item.dataset.graded === graded)
            && (year === 'all' || item.dataset.year === year);
        item.classList.toggle('d-none', !show);
        if (show) visible++;
    });

    emptyDiv?.classList.toggle('d-none', visible !== 0);
}

searchInput?.addEventListener('input', applyFilters);
gradeFilter?.addEventListener('change', applyFilters);
yearFilter?.addEventListener('change', applyFilters);

document.getElementById('marksModal')?.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    const scores = JSON.parse(button?.dataset.scores || '{}');

    document.getElementById('marksProjectId').value = button?.dataset.projectId || '';
    document.getElementById('marksProjectTitle').textContent = button?.dataset.projectTitle || '';

    document.querySelectorAll('.rubric-score').forEach(select => {
        const criterionId = select.name.match(/\[(.+)\]/)?.[1] || '';
        const savedScore = scores[criterionId];
        select.value = savedScore === undefined ? '' : String(savedScore);
    });

    updateTotal();
});

document.querySelectorAll('.rubric-score').forEach(select => {
    select.addEventListener('change', updateTotal);
});

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
        'A+': 'status-approved',
        'A': 'status-approved',
        'A-': 'status-approved',
        'B+': 'status-pending',
        'B': 'status-pending',
        'B-': 'status-pending',
        'C+': 'status-pending',
        'C': 'status-pending',
        'C-': 'status-pending',
        'D': 'status-rejected',
        'F': 'status-rejected'
    };
    return map[grade] || '';
}

function formatScore(score) {
    return Number(score.toFixed(2)).toString();
}

function updateTotal() {
    let rawTotal = 0;
    document.querySelectorAll('.rubric-score').forEach(select => {
        rawTotal += parseFloat(select.value || '0') || 0;
    });

    const percentage = rubricMaxTotal > 0 ? (rawTotal / rubricMaxTotal) * 100 : 0;
    const grade = gradeLabel(percentage);
    const rawPreview = document.getElementById('rawPreview');
    const totalPreview = document.getElementById('totalPreview');
    const gradePreview = document.getElementById('gradePreview');

    if (rawPreview) rawPreview.textContent = formatScore(rawTotal);
    if (totalPreview) totalPreview.textContent = formatScore(percentage) + '%';
    if (gradePreview) {
        gradePreview.textContent = grade;
        gradePreview.className = 'tp-grade status-badge ' + gradeClass(grade);
    }
}
</script>
</body>
</html>
