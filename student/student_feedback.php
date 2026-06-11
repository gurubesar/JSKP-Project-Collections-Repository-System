<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'student') {
    header('Location: ../public/login.php');
    exit;
}

require_once __DIR__ . '/student_header.php';

$feedbackItems = [];
$loadError = '';

try {
    $stmt = $db->prepare(
        "SELECT c.comment_id, c.content_encrypted, c.created_at,
                p.project_id, p.title_encrypted,
                u.name_encrypted AS lecturer_name
         FROM comments c
         INNER JOIN projects p ON p.project_id = c.project_id
         INNER JOIN project_members pm ON pm.project_id = p.project_id AND pm.user_id = ?
         LEFT JOIN users u ON u.user_id = c.user_id
         WHERE u.role = 'lecturer'
         ORDER BY c.created_at DESC, c.comment_id DESC"
    );
    $stmt->execute([$studentId]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $message = decryptValue($row['content_encrypted'] ?? '');
        if ($message === '' || str_starts_with($message, '__marks__')) {
            continue;
        }

        $projectId = (int) ($row['project_id'] ?? 0);
        $feedbackItems[] = [
            'comment_id' => (int) ($row['comment_id'] ?? 0),
            'message' => $message,
            'created_at' => $row['created_at'] ?? '',
            'project_code' => 'UTM-FYP-' . str_pad((string) $projectId, 4, '0', STR_PAD_LEFT),
            'project_title' => decryptValue($row['title_encrypted'] ?? '') ?: 'Project',
            'lecturer_name' => decryptValue($row['lecturer_name'] ?? '') ?: 'Lecturer',
        ];
    }
} catch (Throwable $error) {
    $loadError = 'Unable to load lecturer feedback right now.';
}
?>
            <section class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold mb-1" style="color:var(--student-sidebar);">Feedback</h1>
                    <p class="text-muted mb-0">Read lecturer messages and feedback for your assigned projects.</p>
                </div>
            </section>

            <?php if ($loadError): ?>
                <div class="alert alert-danger" role="alert"><?= e($loadError) ?></div>
            <?php endif; ?>

            <?php if (!$feedbackItems && !$loadError): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 text-center text-muted">
                        No lecturer feedback is available yet.
                    </div>
                </div>
            <?php else: ?>
                <div class="feedback-list">
                    <?php foreach ($feedbackItems as $item): ?>
                        <article class="card border-0 shadow-sm mb-3">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
                                    <div>
                                        <div class="fw-bold" style="color:var(--student-sidebar);"><?= e($item['project_title']) ?></div>
                                        <div class="small text-muted"><?= e($item['project_code']) ?> · From <?= e($item['lecturer_name']) ?></div>
                                    </div>
                                    <span class="badge badge-utm-gold">
                                        <?= $item['created_at'] ? e(date('d M Y H:i', strtotime((string) $item['created_at']))) : 'Date unavailable' ?>
                                    </span>
                                </div>
                                <div class="feedback-message">
                                    <?= nl2br(e($item['message'])) ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <style>
                .feedback-message {
                    line-height: 1.7;
                    color: var(--student-text);
                    white-space: normal;
                }
            </style>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
