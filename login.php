<?php
session_start();
require __DIR__ . '/db.php';

try {
    $db->query('SELECT 1 FROM users LIMIT 1');
} catch (Exception $e) {
    try {
        initializeDatabase($db);
    } catch (Exception $initError) {
        die('Error initializing database: ' . $initError->getMessage());
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        try {
            $emailHash = hashEmail($email);
            $stmt = $db->prepare('SELECT user_id, name_encrypted, password_hash, role FROM users WHERE email_hash = ? LIMIT 1');
            $stmt->execute([$emailHash]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = decryptData($user['name_encrypted']);
                $_SESSION['user_role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    $_SESSION['admin_logged_in'] = true;
                    header('Location: admin_dashboard.php');
                    exit;
                }

                if ($user['role'] === 'lecturer') {
                    $_SESSION['lecturer_logged_in'] = true;
                    header('Location: Lecturer_dashboard.php');
                    exit;
                }

                if ($user['role'] === 'student') {
                    header('Location: student_dashboard.php');
                    exit;
                }

                $error = 'Your account role is not configured correctly.';
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FYP Submission Management System</title>
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

        .login-wrapper {
            width: min(100%, 460px);
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 28px;
            padding: 40px 34px;
            box-shadow: 0 28px 70px rgba(0, 0, 0, 0.22);
            border: 1px solid rgba(255, 255, 255, 0.3);
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
            font-size: clamp(2rem, 2.6vw, 2.4rem);
            color: var(--utm-dark);
            line-height: 1.05;
        }

        .brand-badge {
            width: 64px;
            height: 64px;
            object-fit: contain;
            filter: drop-shadow(0 10px 16px rgba(128, 0, 32, 0.18));
        }

        .login-subtitle {
            margin-bottom: 28px;
            color: var(--utm-muted);
            line-height: 1.7;
            font-size: 0.98rem;
        }

        .login-form {
            display: grid;
            gap: 18px;
        }

        .form-row {
            display: grid;
            gap: 8px;
        }

        .form-row label {
            font-size: 0.95rem;
            color: var(--utm-dark);
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border-radius: 14px;
            border: 1.5px solid rgba(216, 199, 179, 0.9);
            background: var(--utm-white);
            color: var(--utm-dark);
            box-shadow: inset 0 1px 4px rgba(0, 0, 0, 0.04);
            transition: border-color var(--utm-transition), box-shadow var(--utm-transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--utm-maroon);
            box-shadow: 0 0 0 4px rgba(128, 0, 32, 0.12);
        }

        .form-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 2px;
        }

        .forgot-link {
            color: var(--utm-maroon);
            font-size: 0.92rem;
        }

        .btn-login {
            min-width: 130px;
            padding: 14px 22px;
            border-radius: 14px;
            font-weight: 700;
            letter-spacing: 0.02em;
            background: var(--utm-maroon);
            border: 1px solid var(--utm-maroon);
            box-shadow: 0 18px 30px rgba(128, 0, 32, 0.18);
            color: var(--utm-white);
        }

        .btn-login:hover {
            background: var(--utm-maroon-dark);
            border-color: var(--utm-maroon-dark);
            transform: translateY(-1px);
        }

        .login-note {
            margin-top: 26px;
            color: var(--utm-muted);
            font-size: 0.9rem;
            text-align: center;
            line-height: 1.6;
        }

        .error-msg {
            color: #b22222;
            background: #fdecea;
            padding: 12px 14px;
            border-radius: 14px;
            border: 1px solid #f4c2c2;
            font-size: 0.95rem;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div>
                    <p class="login-label">Sign In</p>
                    <h1 class="login-title">UTM Submission Portal</h1>
                </div>
                <img class="brand-badge" src="assets/utm-logo.png" alt="UTM logo">
            </div>

            <div class="login-subtitle">Access your Admin, Lecturer or Student dashboard.</div>

            <?php if ($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form id="loginForm" class="login-form" method="post" action="login.php">
                <div class="form-row">
                    <label for="email">Email</label>
                    <input id="email" name="email" class="form-control" type="email" value="admin@example.com" required>
                </div>

                <div class="form-row">
                    <label for="password">Password</label>
                    <input id="password" name="password" class="form-control" type="password" value="password" required>
                </div>

                <div class="form-footer">
                    <a href="#" class="forgot-link">Forgot your password?</a>
                    <button type="submit" class="btn btn-login">Login</button>
                </div>
            </form>

            <div class="login-note">
                <strong>Demo Accounts:</strong><br>
                <strong>Admin:</strong> admin@example.com<br>
                <strong>Lecturer:</strong> lect@example.com<br>
                <strong>Student:</strong> student@example.com<br>
                <strong>Password:</strong> password
            </div>
        </div>
    </div>
</body>
</html>
