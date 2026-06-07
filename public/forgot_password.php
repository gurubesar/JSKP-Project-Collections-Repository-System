<?php
session_start();
require __DIR__ . '/../database/db.php';

$message = '';
$messageType = '';
$resetToken = trim($_GET['token'] ?? $_POST['token'] ?? '');
$tokenHash = $resetToken !== '' ? hash('sha256', $resetToken) : '';
$tokenRecord = null;
$resetComplete = false;

function currentBaseUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = strtok($_SERVER['REQUEST_URI'] ?? '/forgot_password.php', '?');

    return $scheme . '://' . $host . $path;
}

function findActiveResetToken(PDO $db, string $tokenHash): ?array
{
    if ($tokenHash === '') {
        return null;
    }

    $stmt = $db->prepare(
        'SELECT prt.token_id, prt.user_id, u.name_encrypted, u.role
         FROM password_reset_tokens prt
         JOIN users u ON u.user_id = prt.user_id
         WHERE prt.token_hash = ?
           AND prt.used_at IS NULL
           AND prt.expires_at > CURRENT_TIMESTAMP
         LIMIT 1'
    );
    $stmt->execute([$tokenHash]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    return $record ?: null;
}

function sendPasswordResetEmail(string $email, string $resetLink): bool
{
    $subject = 'UTM Submission Portal password reset';
    $body = "A password reset was requested for your UTM Submission Portal account.\n\n"
        . "Use this link to set a new password:\n{$resetLink}\n\n"
        . "This link expires in 1 hour. If you did not request it, you can ignore this email.";
    $headers = 'From: no-reply@utm-submission.local';

    return function_exists('mail') && @mail($email, $subject, $body, $headers);
}

if ($tokenHash !== '') {
    try {
        $tokenRecord = findActiveResetToken($db, $tokenHash);
        if (!$tokenRecord && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $message = 'This password reset link is invalid or has expired. Please request a new link.';
            $messageType = 'error';
        }
    } catch (PDOException $e) {
        $message = 'An error occurred. Please request a new password reset link.';
        $messageType = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'request';

    if ($action === 'reset') {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!$tokenRecord) {
            $message = 'This password reset link is invalid or has expired. Please request a new link.';
            $messageType = 'error';
        } elseif (strlen($password) < 8) {
            $message = 'Your new password must be at least 8 characters long.';
            $messageType = 'error';
        } elseif ($password !== $confirmPassword) {
            $message = 'Password confirmation does not match.';
            $messageType = 'error';
        } else {
            try {
                $db->beginTransaction();

                $updatePassword = $db->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
                $updatePassword->execute([
                    password_hash($password, PASSWORD_DEFAULT),
                    $tokenRecord['user_id']
                ]);

                $markUsed = $db->prepare('UPDATE password_reset_tokens SET used_at = CURRENT_TIMESTAMP WHERE token_id = ?');
                $markUsed->execute([$tokenRecord['token_id']]);

                $cleanup = $db->prepare('DELETE FROM password_reset_tokens WHERE user_id = ? AND token_id <> ?');
                $cleanup->execute([$tokenRecord['user_id'], $tokenRecord['token_id']]);

                $db->commit();
                $resetComplete = true;
                $tokenRecord = null;
                $message = 'Your password has been updated. You can now sign in with the new password.';
                $messageType = 'success';
            } catch (PDOException $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                $message = 'Unable to update your password. Please try again.';
                $messageType = 'error';
            }
        }
    } else {
        $email = trim($_POST['email'] ?? '');

        if ($email === '') {
            $message = 'Please enter your email address.';
            $messageType = 'error';
        } else {
            try {
                $emailHash = hashEmail($email);
                $stmt = $db->prepare('SELECT user_id, name_encrypted, role FROM users WHERE email_hash = ? LIMIT 1');
                $stmt->execute([$emailHash]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                $message = 'If that email exists in our system, a password reset link has been sent. Please check your inbox.';
                $messageType = 'success';

                if ($user) {
                    $plainToken = bin2hex(random_bytes(32));
                    $resetLink = currentBaseUrl() . '?token=' . urlencode($plainToken);
                    $expiresAt = gmdate('Y-m-d H:i:s', time() + 3600);

                    $deleteOld = $db->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?');
                    $deleteOld->execute([$user['user_id']]);

                    $insertToken = $db->prepare(
                        'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
                         VALUES (?, ?, ?)'
                    );
                    $insertToken->execute([
                        $user['user_id'],
                        hash('sha256', $plainToken),
                        $expiresAt
                    ]);

                    $sent = sendPasswordResetEmail($email, $resetLink);
                    if (!$sent && getenv('APP_ENV') !== 'production') {
                        $message .= ' For testing, use this reset link: ' . $resetLink;
                    }
                }
            } catch (Throwable $e) {
                $message = 'An error occurred. Please try again later.';
                $messageType = 'error';
            }
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Recovery - UTM Submission Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="utm-theme.css">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            background: radial-gradient(circle at top left, rgba(242, 169, 0, 0.12), transparent 24%),
                        linear-gradient(180deg, #940030 0%, #800020 38%, #4f0015 100%);
            color: var(--utm-dark);
        }
        .login-page {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 2rem 1rem;
        }
        .login-wrapper { width: min(100%, 500px); }
        .login-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 28px;
            padding: 40px 34px;
            box-shadow: 0 28px 70px rgba(0,0,0,.22);
            border: 1px solid rgba(255,255,255,.3);
            backdrop-filter: blur(10px);
        }
        .login-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 1rem;
        }
        .login-label {
            margin: 0 0 0.35rem;
            font-size: 0.82rem;
            letter-spacing: 0.18em;
            font-weight: 700;
            color: var(--utm-gold);
            text-transform: uppercase;
        }
        .login-title {
            margin: 0;
            font-size: clamp(1.6rem, 2.4vw, 2rem);
            color: var(--utm-dark);
            line-height: 1.1;
        }
        .brand-badge {
            width: 64px; height: 64px;
            object-fit: contain;
            filter: drop-shadow(0 10px 16px rgba(128,0,32,.18));
        }
        .login-subtitle {
            margin-bottom: 28px;
            color: var(--utm-muted);
            line-height: 1.7;
            font-size: 0.98rem;
        }
        .login-form { display: grid; gap: 18px; }
        .form-row { display: grid; gap: 8px; }
        .form-row label { font-size: 0.95rem; color: var(--utm-dark); font-weight: 600; }
        .form-control {
            width: 100%; padding: 14px 16px;
            border-radius: 14px;
            border: 1.5px solid rgba(216,199,179,.9);
            background: var(--utm-white);
            color: var(--utm-dark);
            box-shadow: inset 0 1px 4px rgba(0,0,0,.04);
            transition: border-color var(--utm-transition), box-shadow var(--utm-transition);
        }
        .form-control:focus {
            outline: none;
            border-color: var(--utm-maroon);
            box-shadow: 0 0 0 4px rgba(128,0,32,.12);
        }
        .btn-login {
            width: 100%; padding: 14px 22px;
            border-radius: 14px; font-weight: 700;
            background: var(--utm-maroon);
            border: 1px solid var(--utm-maroon);
            box-shadow: 0 18px 30px rgba(128,0,32,.18);
            color: var(--utm-white);
        }
        .btn-login:hover {
            background: var(--utm-maroon-dark);
            border-color: var(--utm-maroon-dark);
            transform: translateY(-1px);
        }
        .back-link {
            display: flex; align-items: center; gap: 6px;
            margin-top: 22px; justify-content: center;
            color: var(--utm-maroon); font-size: 0.92rem; font-weight: 600;
        }
        .back-link:hover { color: var(--utm-dark); }
        .reset-meta {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 12px; border-radius: 999px;
            color: var(--utm-maroon); background: rgba(128,0,32,.08);
            font-size: .86rem; font-weight: 700; margin-bottom: 18px;
            text-transform: capitalize;
        }
        .msg-success {
            background: #e7f6ed; border: 1px solid #b7dfc8;
            color: #1f7a45; padding: 14px 16px;
            border-radius: 14px; font-size: 0.95rem; margin-bottom: 4px;
            overflow-wrap: anywhere;
        }
        .msg-error {
            background: #fdecea; border: 1px solid #f4c2c2;
            color: #b22222; padding: 14px 16px;
            border-radius: 14px; font-size: 0.95rem; margin-bottom: 4px;
            overflow-wrap: anywhere;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div>
                    <p class="login-label"><?= $tokenRecord ? 'Reset Password' : 'Account Recovery' ?></p>
                    <h1 class="login-title"><?= $tokenRecord ? 'Create New Password' : 'Forgot Password' ?></h1>
                </div>
                <img class="brand-badge" src="../assets/utm-logo.png" alt="UTM logo"
                     onerror="this.outerHTML='<div style=\'width:64px;height:64px;border-radius:16px;background:#800020;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;font-weight:800;\'>UTM</div>'">
            </div>

            <?php if ($tokenRecord): ?>
                <div class="reset-meta"><?= htmlspecialchars($tokenRecord['role']) ?> account</div>
                <p class="login-subtitle">Enter and confirm your new password for <?= htmlspecialchars(decryptData($tokenRecord['name_encrypted'])) ?>.</p>
            <?php else: ?>
                <p class="login-subtitle">Enter your registered email address and we'll send you a password reset link for your Admin, Lecturer or Student account.</p>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="<?= $messageType === 'success' ? 'msg-success' : 'msg-error' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($tokenRecord): ?>
            <form class="login-form" method="post" action="forgot_password.php">
                <input type="hidden" name="action" value="reset">
                <input type="hidden" name="token" value="<?= htmlspecialchars($resetToken) ?>">
                <div class="form-row">
                    <label for="password">New Password</label>
                    <input id="password" name="password" class="form-control" type="password" minlength="8" required>
                </div>
                <div class="form-row">
                    <label for="confirm_password">Confirm New Password</label>
                    <input id="confirm_password" name="confirm_password" class="form-control" type="password" minlength="8" required>
                </div>
                <button type="submit" class="btn btn-login">Update Password</button>
            </form>
            <?php elseif (!$resetComplete && $messageType !== 'success'): ?>
            <form class="login-form" method="post" action="forgot_password.php">
                <input type="hidden" name="action" value="request">
                <div class="form-row">
                    <label for="email">Email Address</label>
                    <input id="email" name="email" class="form-control" type="email"
                           placeholder="e.g. lecturer@utm.my" required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-login">Send Reset Link</button>
            </form>
            <?php endif; ?>

            <a href="login.php" class="back-link">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16">
                    <path d="M19 12H5M12 5l-7 7 7 7"/>
                </svg>
                Back to Sign In
            </a>
        </div>
    </div>
</body>
</html>
