<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: login.php');
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTM Student Dashboard</title>
    <link rel="stylesheet" href="utm-theme.css">
    <style>
        body { margin: 0; background: var(--utm-bg); color: var(--utm-dark); font-family: 'DM Sans', sans-serif; }
        .page { max-width: 1040px; margin: 0 auto; padding: 32px; }
        .topbar { display: flex; justify-content: space-between; gap: 16px; align-items: center; background: var(--utm-white); padding: 20px 28px; border-radius: 24px; border: 1px solid var(--utm-border); box-shadow: var(--utm-shadow); }
        .topbar h1 { margin: 0; font-size: 1.7rem; color: var(--utm-maroon); }
        .topbar p { margin: 0; color: var(--utm-muted); }
        .card-grid { display: grid; gap: 20px; margin-top: 24px; }
        .panel { background: var(--utm-white); border: 1px solid var(--utm-border); border-radius: 24px; padding: 24px; box-shadow: var(--utm-shadow); }
        .panel h2 { margin: 0 0 12px; color: var(--utm-maroon); }
        .panel p { margin: 0; color: var(--utm-muted); line-height: 1.7; }
        .dashboard-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 16px; }
        .btn { padding: 12px 18px; border-radius: 14px; border: none; cursor: pointer; color: var(--utm-white); background: var(--utm-maroon); }
        .btn-secondary { background: var(--utm-gold); color: var(--utm-dark); }
    </style>
</head>
<body>
    <div class="page">
        <div class="topbar">
            <div>
                <h1>Student Dashboard</h1>
                <p>Welcome to the UTM FYP Submission System.</p>
            </div>
            <div class="dashboard-actions">
                <button class="btn">View Projects</button>
                <button class="btn btn-secondary">My Submissions</button>
            </div>
        </div>
        <div class="card-grid">
            <div class="panel">
                <h2>My Projects</h2>
                <p>This page is a placeholder for student project and submission data. The login system will redirect students here automatically.</p>
            </div>
            <div class="panel">
                <h2>Notifications</h2>
                <p>Students will see updates from lecturers, submission deadlines, and review requests here once the system is connected to the backend.</p>
            </div>
        </div>
    </div>
</body>
</html>
