<?php
session_start();
require __DIR__ . '/db.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

            // Always show success to prevent email enumeration
            $message = 'If that email exists in our system, a password reset link has been sent. Please check your inbox.';
            $messageType = 'success';

            // TODO: In production, send actual reset email here using $user data if found
        } catch (PDOException $e) {
            $message = 'An error occurred. Please try again later.';
            $messageType = 'error';
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password – UTM Submission Portal</title>
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
        .login-wrapper { width: min(100%, 460px); }
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
        .msg-success {
            background: #e7f6ed; border: 1px solid #b7dfc8;
            color: #1f7a45; padding: 14px 16px;
            border-radius: 14px; font-size: 0.95rem; margin-bottom: 4px;
        }
        .msg-error {
            background: #fdecea; border: 1px solid #f4c2c2;
            color: #b22222; padding: 14px 16px;
            border-radius: 14px; font-size: 0.95rem; margin-bottom: 4px;
        }
        .icon-wrap {
            width: 62px; height: 62px; border-radius: 50%;
            background: rgba(128,0,32,.08);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
        }
        .icon-wrap svg { width: 30px; height: 30px; color: var(--utm-maroon); }
    </style>
</head>
<body class="login-page">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div>
                    <p class="login-label">Account Recovery</p>
                    <h1 class="login-title">Forgot Password</h1>
                </div>
                <img class="brand-badge" src="assets/utm-logo.png" alt="UTM logo"
                     onerror="this.outerHTML='<div style=\'width:64px;height:64px;border-radius:16px;background:#800020;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;font-weight:800;\'>UTM</div>'">
            </div>

            <p class="login-subtitle">Enter your registered email address and we'll send you a password reset link.</p>

            <?php if ($message): ?>
                <div class="<?= $messageType === 'success' ? 'msg-success' : 'msg-error' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($messageType !== 'success'): ?>
            <form class="login-form" method="post" action="forgot_password.php">
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