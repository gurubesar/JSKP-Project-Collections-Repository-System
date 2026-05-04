<?php
session_start();
if (!isset($_SESSION['lecturer_logged_in']) || $_SESSION['lecturer_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UTM Academic Project Review – Lecturer Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="utm-theme.css">
<style>
  :root {
    --utm-maroon: #800020;
    --utm-maroon-dark: #5c0018;
    --utm-maroon-light: #a21f40;
    --utm-gold: #F2A900;
    --utm-gold-light: #f7c94c;
    --utm-gold-pale: #fff1b8;
    --utm-dark: #222222;
    --utm-bg: #f5f2ee;
    --utm-surface: #ffffff;
    --utm-surface-soft: #faf2eb;
    --utm-sidebar: #6B0000;
    --utm-text: #222222;
    --utm-muted: #4a3d35;
    --utm-border: #d8c7b3;
    --utm-pending: #e8a020;
    --utm-approved: #2e7d32;
    --utm-needs-revision: #c0392b;
    --utm-rejected: #555;
    --utm-transition: 0.22s cubic-bezier(.4,0,.2,1);
    --utm-red: var(--utm-maroon);
    --utm-gold: var(--utm-gold);
    --utm-dark: var(--utm-dark);
    --bg: var(--utm-bg);
    --card-bg: var(--utm-surface);
    --sidebar-bg: var(--utm-sidebar);
    --text: var(--utm-text);
    --muted: var(--utm-muted);
    --border: var(--utm-border);
    --pending: var(--utm-pending);
    --approved: var(--utm-approved);
    --needs-revision: var(--utm-needs-revision);
    --rejected: var(--utm-rejected);
    --transition: var(--utm-transition);
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
  }

  /* ── LOGIN PAGE ── */
  #login-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #6B0000 0%, #1a0505 60%, #3a1a00 100%);
    position: relative;
    overflow: hidden;
  }
  #login-page::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
  }
  .login-box {
    background: #fff;
    border-radius: 18px;
    padding: 50px 48px 44px;
    width: 420px;
    box-shadow: 0 30px 80px rgba(0,0,0,.45);
    position: relative;
    animation: fadeUp .5s ease both;
  }
  @keyframes fadeUp { from { opacity:0; transform:translateY(24px); } to { opacity:1; transform:translateY(0); } }
  .login-logo {
    display: flex; align-items: center; gap: 14px; margin-bottom: 32px;
  }
  .login-logo-mark {
    width: 52px; height: 52px;
    background: var(--utm-red);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Merriweather', serif;
    color: #fff; font-size: 20px; font-weight: 700;
    letter-spacing: -1px;
  }
  .login-logo-text h2 {
    font-family: 'Merriweather', serif;
    font-size: 15px; color: var(--utm-red); font-weight: 700;
  }
  .login-logo-text p { font-size: 11px; color: var(--muted); }
  .login-box h1 {
    font-family: 'Merriweather', serif;
    font-size: 22px; font-weight: 700; color: var(--utm-red); margin-bottom: 6px;
  }
  .login-box .subtitle { font-size: 13.5px; color: var(--muted); margin-bottom: 30px; }
  .form-group { margin-bottom: 18px; }
  .form-group label { display: block; font-size: 12.5px; font-weight: 600; color: var(--text); margin-bottom: 7px; letter-spacing: .3px; }
  .form-group input {
    width: 100%; padding: 11px 14px; border: 1.5px solid var(--border);
    border-radius: 9px; font-family: 'DM Sans', sans-serif; font-size: 14px;
    color: var(--text); background: var(--bg); outline: none;
    transition: border-color var(--transition), box-shadow var(--transition);
  }
  .form-group input:focus { border-color: var(--utm-red); box-shadow: 0 0 0 3px rgba(139,0,0,.1); }
  .forgot-link { text-align: right; margin-top: -10px; margin-bottom: 20px; }
  .forgot-link a { font-size: 12px; color: var(--utm-gold); text-decoration: none; font-weight: 500; }
  .forgot-link a:hover { text-decoration: underline; }
  .btn-login {
    width: 100%; padding: 13px;
    background: var(--utm-red); color: #fff;
    border: none; border-radius: 9px; font-size: 15px; font-weight: 600;
    font-family: 'DM Sans', sans-serif; cursor: pointer;
    transition: background var(--transition), transform var(--transition);
    letter-spacing: .2px;
  }
  .btn-login:hover { background: #6B0000; transform: translateY(-1px); }
  .login-note { margin-top: 18px; text-align: center; font-size: 12px; color: var(--muted); }
  .login-error {
    background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b;
    padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; display: none;
  }

  /* ── DASHBOARD ── */
  #dashboard-page { display: none; min-height: 100vh; flex-direction: row; }

  /* Sidebar */
  .sidebar {
    width: 220px; min-height: 100vh;
    background: var(--utm-red);
    display: flex; flex-direction: column;
    position: fixed; left: 0; top: 0; bottom: 0;
    z-index: 100;
  }
  .sidebar-brand {
    padding: 24px 20px 20px;
    border-bottom: 1px solid rgba(255,255,255,.12);
    display: flex; align-items: center; gap: 12px;
  }
  .brand-mark {
    width: 38px; height: 38px; background: rgba(255,255,255,.15);
    border-radius: 8px; display: flex; align-items: center; justify-content: center;
    font-family: 'Merriweather', serif; color: #fff; font-size: 15px; font-weight: 700;
  }
  .brand-label { color: #fff; font-size: 13px; font-weight: 600; line-height: 1.3; }
  .brand-label span { display: block; font-size: 10px; opacity: .65; font-weight: 400; }
  .sidebar-nav { flex: 1; padding: 18px 10px; }
  .nav-item {
    display: flex; align-items: center; gap: 11px; padding: 11px 13px;
    color: rgba(255,255,255,.72); font-size: 13.5px; border-radius: 8px;
    cursor: pointer; transition: background var(--transition), color var(--transition);
    margin-bottom: 2px; user-select: none;
  }
  .nav-item:hover, .nav-item.active { background: rgba(255,255,255,.15); color: #fff; }
  .nav-item svg { width: 17px; height: 17px; flex-shrink: 0; }
  .sidebar-bottom { padding: 14px 10px 20px; border-top: 1px solid rgba(255,255,255,.12); }
  .nav-item.danger:hover { background: rgba(255,80,80,.18); color: #ff8080; }

  /* Main */
  .main { margin-left: 220px; flex: 1; display: flex; flex-direction: column; }
  .topbar {
    background: #fff; padding: 0 32px; height: 64px;
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 50;
  }
  .topbar-title { font-family: 'Merriweather', serif; font-size: 17px; font-weight: 700; color: var(--utm-red); }
  .topbar-right { display: flex; align-items: center; gap: 18px; }
  .notif-btn {
    position: relative; background: none; border: none; cursor: pointer; padding: 6px;
    color: var(--muted); border-radius: 8px; transition: background var(--transition);
  }
  .notif-btn:hover { background: var(--bg); }
  .notif-badge {
    position: absolute; top: 2px; right: 2px; width: 16px; height: 16px;
    background: var(--utm-gold); color: #fff; font-size: 10px; font-weight: 700;
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
  }
  .user-chip {
    display: flex; align-items: center; gap: 10px;
    background: var(--bg); padding: 7px 14px 7px 7px; border-radius: 50px;
    cursor: pointer;
  }
  .user-avatar {
    width: 32px; height: 32px; border-radius: 50%;
    background: var(--utm-red); color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700;
  }
  .user-info { line-height: 1.25; }
  .user-info strong { font-size: 13px; display: block; }
  .user-info span { font-size: 11px; color: var(--muted); }

  /* Content area */
  .content { padding: 32px; flex: 1; }
  .page-header { margin-bottom: 26px; }
  .page-header h1 { font-family: 'Merriweather', serif; font-size: 22px; font-weight: 700; color: var(--utm-red); }
  .breadcrumb { font-size: 12px; color: var(--muted); margin-top: 4px; }
  .breadcrumb span { color: var(--utm-gold); }

  /* Filters & search */
  .toolbar {
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;
    margin-bottom: 24px;
  }
  .filter-tabs { display: flex; gap: 6px; flex-wrap: wrap; }
  .filter-tab {
    padding: 6px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 500;
    cursor: pointer; border: 1.5px solid var(--border); background: #fff; color: var(--muted);
    transition: all var(--transition);
  }
  .filter-tab.active, .filter-tab:hover { background: var(--utm-red); color: #fff; border-color: var(--utm-red); }
  .search-box {
    display: flex; align-items: center; gap: 8px;
    background: #fff; border: 1.5px solid var(--border); border-radius: 9px; padding: 8px 14px;
  }
  .search-box input {
    border: none; outline: none; font-family: 'DM Sans', sans-serif;
    font-size: 13.5px; background: none; width: 200px; color: var(--text);
  }
  .search-box svg { color: var(--muted); width: 15px; height: 15px; }

  /* Stats row */
  .stats-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 28px; }
  .stat-card {
    background: #fff; border-radius: 12px; padding: 18px 20px;
    border: 1px solid var(--border);
    display: flex; flex-direction: column; gap: 4px;
  }
  .stat-card .label { font-size: 11.5px; color: var(--muted); font-weight: 500; letter-spacing: .3px; text-transform: uppercase; }
  .stat-card .value { font-family: 'Merriweather', serif; font-size: 28px; font-weight: 700; color: var(--utm-red); }
  .stat-card .sub { font-size: 11.5px; color: var(--muted); }

  /* Cards grid */
  .cards-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(290px,1fr)); gap: 18px; }
  .project-card {
    background: #fff; border-radius: 13px; padding: 20px;
    border: 1px solid var(--border); transition: box-shadow var(--transition), transform var(--transition);
    display: flex; flex-direction: column; gap: 10px;
  }
  .project-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.08); transform: translateY(-2px); }
  .card-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
  .card-id { font-size: 10.5px; color: var(--muted); font-weight: 500; }
  .card-title { font-family: 'Merriweather', serif; font-size: 14px; font-weight: 700; color: var(--utm-red); line-height: 1.4; }
  .badge {
    padding: 3px 10px; border-radius: 20px; font-size: 10.5px; font-weight: 600;
    white-space: nowrap; flex-shrink: 0;
  }
  .badge.pending { background: #fff3cd; color: #856404; }
  .badge.approved { background: #d4edda; color: #155724; }
  .badge.revision { background: #f8d7da; color: #721c24; }
  .badge.rejected { background: #e2e3e5; color: #383d41; }
  .card-meta { font-size: 12px; color: var(--muted); line-height: 1.8; }
  .card-meta strong { color: var(--text); font-weight: 500; }
  .card-actions { display: flex; gap: 7px; margin-top: 4px; flex-wrap: wrap; }
  .btn {
    padding: 7px 13px; border-radius: 7px; font-size: 12px; font-weight: 600;
    font-family: 'DM Sans', sans-serif; cursor: pointer; border: none;
    display: flex; align-items: center; gap: 5px; transition: all var(--transition);
  }
  .btn-approve { background: #e8f5e9; color: #2e7d32; }
  .btn-approve:hover { background: #2e7d32; color: #fff; }
  .btn-reject { background: #fdecea; color: #c62828; }
  .btn-reject:hover { background: #c62828; color: #fff; }
  .btn-comment { background: var(--bg); color: var(--muted); border: 1px solid var(--border); }
  .btn-comment:hover { background: var(--utm-gold); color: #fff; border-color: var(--utm-gold); }
  .btn-grades { background: var(--utm-red); color: #fff; }
  .btn-grades:hover { background: #6B0000; }

  /* Pagination */
  .pagination { display: flex; align-items: center; gap: 6px; margin-top: 32px; justify-content: center; }
  .pg-btn {
    width: 34px; height: 34px; border-radius: 8px; border: 1.5px solid var(--border);
    background: #fff; color: var(--text); font-size: 13px; font-weight: 600;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: all var(--transition);
  }
  .pg-btn.active { background: var(--utm-red); color: #fff; border-color: var(--utm-red); }
  .pg-btn:hover:not(.active) { border-color: var(--utm-red); color: var(--utm-red); }

  /* Modal */
  .modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.45); z-index: 500;
    align-items: center; justify-content: center;
    animation: fadeIn .2s ease;
  }
  .modal-overlay.open { display: flex; }
  @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
  .modal {
    background: #fff; border-radius: 16px; padding: 32px 36px;
    width: 480px; max-width: 95vw; box-shadow: 0 24px 60px rgba(0,0,0,.25);
    animation: fadeUp .25s ease both;
  }
  .modal h2 { font-family: 'Merriweather', serif; color: var(--utm-red); font-size: 18px; margin-bottom: 6px; }
  .modal .modal-sub { font-size: 13px; color: var(--muted); margin-bottom: 20px; }
  .modal textarea {
    width: 100%; padding: 12px 14px; border: 1.5px solid var(--border);
    border-radius: 9px; font-family: 'DM Sans', sans-serif; font-size: 13.5px;
    resize: vertical; min-height: 110px; outline: none; color: var(--text);
    transition: border-color var(--transition);
  }
  .modal textarea:focus { border-color: var(--utm-red); }
  .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px; }
  .btn-cancel { padding: 10px 20px; border-radius: 8px; border: 1.5px solid var(--border); background: #fff; color: var(--muted); font-family: 'DM Sans', sans-serif; font-size: 13px; cursor: pointer; font-weight: 600; }
  .btn-submit { padding: 10px 22px; border-radius: 8px; background: var(--utm-red); color: #fff; border: none; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; }
  .btn-submit:hover { background: #6B0000; }

  /* Marks modal */
  .marks-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px; }
  .marks-grid .form-group { margin: 0; }
  .marks-grid input[type=number] {
    width: 100%; padding: 10px 12px; border: 1.5px solid var(--border);
    border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 14px;
    outline: none; transition: border-color var(--transition);
  }
  .marks-grid input[type=number]:focus { border-color: var(--utm-red); }

  /* Toast */
  .toast {
    position: fixed; bottom: 28px; right: 28px; z-index: 999;
    background: #1a0505; color: #fff; padding: 13px 22px; border-radius: 10px;
    font-size: 13.5px; font-weight: 500; box-shadow: 0 8px 24px rgba(0,0,0,.25);
    transform: translateY(20px); opacity: 0; transition: all .35s cubic-bezier(.4,0,.2,1);
    pointer-events: none;
  }
  .toast.show { transform: translateY(0); opacity: 1; }
  .toast.success::before { content: '✓  '; color: #4caf50; }
  .toast.error::before { content: '✕  '; color: #ef5350; }

  /* Section headings */
  .section-label {
    font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .8px;
    color: var(--muted); margin-bottom: 14px;
  }

  /* Hide page */
  .hidden { display: none !important; }

  /* Profile dropdown */
  .profile-dropdown {
    position: fixed; top: 72px; right: 24px; background: #fff;
    border: 1px solid var(--border); border-radius: 12px; padding: 10px;
    width: 210px; box-shadow: 0 12px 32px rgba(0,0,0,.12); z-index: 200;
    display: none;
  }
  .profile-dropdown.open { display: block; animation: fadeUp .2s ease both; }
  .pd-item {
    padding: 9px 12px; border-radius: 8px; font-size: 13px; cursor: pointer;
    color: var(--text); display: flex; align-items: center; gap: 8px;
    transition: background var(--transition);
  }
  .pd-item:hover { background: var(--bg); }
  .pd-item.danger { color: #c62828; }
  .pd-item.danger:hover { background: #fdecea; }
  .pd-divider { border: none; border-top: 1px solid var(--border); margin: 6px 0; }

  /* Reports / Students page placeholder */
  .placeholder-panel { text-align: center; padding: 80px 24px; }
  .placeholder-panel svg { width: 64px; height: 64px; color: #d0c8c0; margin-bottom: 16px; }
  .placeholder-panel h3 { font-family: 'Merriweather', serif; color: var(--utm-red); font-size: 18px; margin-bottom: 8px; }
  .placeholder-panel p { color: var(--muted); font-size: 14px; }
</style>
</head>
<body>

<!-- ═══════════ DASHBOARD ═══════════ -->
<div id="dashboard-page">

  <!-- Sidebar -->
  <nav class="sidebar">
    <div class="sidebar-brand">
      <div class="brand-mark">UTM</div>
      <div class="brand-label">Academic Review<span>Lecturer Portal</span></div>
    </div>
    <div class="sidebar-nav">
      <div class="nav-item active" onclick="showSection('projects', this)">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 7h18M3 12h18M3 17h12"/></svg>
        Dashboard
      </div>
      <div class="nav-item" onclick="showSection('submissions', this)">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0121 9.414V19a2 2 0 01-2 2z"/></svg>
        Project Submissions
      </div>
      <div class="nav-item" onclick="showSection('review', this)">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        Review Queue
      </div>
      <div class="nav-item" onclick="showSection('students', this)">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5zm0 0v6m-4-4h8"/></svg>
        Students
      </div>
      <div class="nav-item" onclick="showSection('grades', this)">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
        Final Grades
      </div>
      <div class="nav-item" onclick="showSection('reports', this)">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
        Reports
      </div>
    </div>
    <div class="sidebar-bottom">
      <div class="nav-item" onclick="showSection('settings', this)">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
        Settings
      </div>
      <div class="nav-item danger" onclick="doLogout()">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Sign Out
      </div>
    </div>
  </nav>

  <!-- Main area -->
  <div class="main">
    <div class="topbar">
      <span class="topbar-title" id="topbar-title">Academic Project Review</span>
      <div class="topbar-right">
        <button class="notif-btn" onclick="showToast('You have 3 new notifications','success')">
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
          <span class="notif-badge">3</span>
        </button>
        <div class="user-chip" onclick="toggleProfile()">
          <div class="user-avatar">AZ</div>
          <div class="user-info">
            <strong>Prof. Dr. Azman</strong>
            <span>Faculty of Computing</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Profile dropdown -->
    <div class="profile-dropdown" id="profile-dropdown">
      <div class="pd-item">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        My Profile
      </div>
      <div class="pd-item">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        Change Password
      </div>
      <hr class="pd-divider">
      <div class="pd-item danger" onclick="doLogout()">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7"/></svg>
        Sign Out
      </div>
    </div>

    <div class="content">
      <!-- ── PROJECTS / DASHBOARD ── -->
      <div id="sec-projects">
        <div class="page-header">
          <h1>Dashboard Overview</h1>
          <div class="breadcrumb">Home › <span>Projects</span> › Current Submissions</div>
        </div>

        <div class="stats-row">
          <div class="stat-card">
            <div class="label">Total Projects</div>
            <div class="value">24</div>
            <div class="sub">Session 2023/2024</div>
          </div>
          <div class="stat-card">
            <div class="label">Pending Review</div>
            <div class="value" style="color:#C8973A">8</div>
            <div class="sub">Awaiting your action</div>
          </div>
          <div class="stat-card">
            <div class="label">Approved</div>
            <div class="value" style="color:#2e7d32">13</div>
            <div class="sub">Passed review</div>
          </div>
          <div class="stat-card">
            <div class="label">Needs Revision</div>
            <div class="value" style="color:#c0392b">3</div>
            <div class="sub">Returned to students</div>
          </div>
        </div>

        <div class="toolbar">
          <div class="filter-tabs">
            <div class="filter-tab active" onclick="filterCards('all',this)">All Projects</div>
            <div class="filter-tab" onclick="filterCards('pending',this)">Pending Review</div>
            <div class="filter-tab" onclick="filterCards('approved',this)">Approved</div>
            <div class="filter-tab" onclick="filterCards('revision',this)">Needs Revision</div>
            <div class="filter-tab" onclick="filterCards('rejected',this)">Rejected</div>
          </div>
          <div class="search-box">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="text" placeholder="Search projects or students…" oninput="searchCards(this.value)">
          </div>
        </div>

        <div class="section-label">Current Submissions</div>
        <div class="cards-grid" id="cards-grid"></div>
        <div class="pagination" id="pagination"></div>
      </div>

      <!-- ── SUBMISSIONS ── -->
      <div id="sec-submissions" class="hidden">
        <div class="page-header">
          <h1>Project Submissions</h1>
          <div class="breadcrumb">Home › <span>Submissions</span></div>
        </div>
        <div class="placeholder-panel">
          <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0121 9.414V19a2 2 0 01-2 2z"/></svg>
          <h3>All Student Submissions</h3>
          <p>View, download, and review all project documents submitted by your students.</p>
        </div>
      </div>

      <!-- ── REVIEW QUEUE ── -->
      <div id="sec-review" class="hidden">
        <div class="page-header">
          <h1>Review Queue</h1>
          <div class="breadcrumb">Home › <span>Review Queue</span></div>
        </div>
        <div class="toolbar" style="margin-bottom:18px">
          <div class="filter-tabs">
            <div class="filter-tab active">Pending (8)</div>
            <div class="filter-tab">Needs Revision (3)</div>
          </div>
        </div>
        <div class="cards-grid" id="review-grid"></div>
      </div>

      <!-- ── STUDENTS ── -->
      <div id="sec-students" class="hidden">
        <div class="page-header">
          <h1>Assigned Students</h1>
          <div class="breadcrumb">Home › <span>Students</span></div>
        </div>
        <div class="placeholder-panel">
          <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5zm0 0v6m-4-4h8"/></svg>
          <h3>Student Progress Tracker</h3>
          <p>Track submission history, progress milestones, and performance for each assigned student.</p>
        </div>
      </div>

      <!-- ── GRADES ── -->
      <div id="sec-grades" class="hidden">
        <div class="page-header">
          <h1>Final Grades</h1>
          <div class="breadcrumb">Home › <span>Final Grades</span></div>
        </div>
        <div class="toolbar" style="margin-bottom:24px">
          <div class="filter-tabs">
            <div class="filter-tab active">All Students</div>
            <div class="filter-tab">Graded</div>
            <div class="filter-tab">Pending</div>
          </div>
          <button class="btn btn-grades" onclick="openMarksModal({title:'Batch Grade Entry', id:'all'})">＋ Assign Marks</button>
        </div>
        <div class="cards-grid" id="grades-grid"></div>
      </div>

      <!-- ── REPORTS ── -->
      <div id="sec-reports" class="hidden">
        <div class="page-header">
          <h1>Reports</h1>
          <div class="breadcrumb">Home › <span>Reports</span></div>
        </div>
        <div class="placeholder-panel">
          <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
          <h3>Analytics & Reports</h3>
          <p>Generate submission reports, approval statistics, and grade summaries for your department.</p>
        </div>
      </div>

      <!-- ── SETTINGS ── -->
      <div id="sec-settings" class="hidden">
        <div class="page-header">
          <h1>Settings</h1>
          <div class="breadcrumb">Home › <span>Settings</span></div>
        </div>
        <div class="placeholder-panel">
          <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
          <h3>Account Settings</h3>
          <p>Manage your preferences, notification settings, and account security.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Comment Modal -->
<div class="modal-overlay" id="comment-modal">
  <div class="modal">
    <h2>Add Comment / Feedback</h2>
    <p class="modal-sub" id="comment-modal-sub">Provide feedback for the student's submission.</p>
    <textarea id="comment-text" placeholder="Write your feedback or revision notes here…"></textarea>
    <div class="modal-actions">
      <button class="btn-cancel" onclick="closeModal('comment-modal')">Cancel</button>
      <button class="btn-submit" onclick="submitComment()">Submit Feedback</button>
    </div>
  </div>
</div>

<!-- Marks Modal -->
<div class="modal-overlay" id="marks-modal">
  <div class="modal">
    <h2>Assign Marks</h2>
    <p class="modal-sub" id="marks-modal-sub">Enter marks for each assessment component.</p>
    <div class="marks-grid">
      <div class="form-group"><label>Report (/ 30)</label><input type="number" min="0" max="30" placeholder="e.g. 24"></div>
      <div class="form-group"><label>Presentation (/ 25)</label><input type="number" min="0" max="25" placeholder="e.g. 20"></div>
      <div class="form-group"><label>Implementation (/ 30)</label><input type="number" min="0" max="30" placeholder="e.g. 26"></div>
      <div class="form-group"><label>Supervisor (/ 15)</label><input type="number" min="0" max="15" placeholder="e.g. 13"></div>
    </div>
    <div class="modal-actions">
      <button class="btn-cancel" onclick="closeModal('marks-modal')">Cancel</button>
      <button class="btn-submit" onclick="submitMarks()">Save Marks</button>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
// ── DATA ──
const projects = [
  { id:'UTM-FYP2023-001', title:'AI-Powered Smart Campus Navigation', students:'Ahmad Bin Razak, Nur Ayuni Idris', status:'pending', supervisor:'Dr. Sarah Lee', date:'12 Oct 2023' },
  { id:'UTM-FYP2023-002', title:'AI-Bcoan Digital Evaluation', students:'Muhammad Adam bin Hassan', status:'approved', supervisor:'Dr. Sarah Lee', date:'12 Oct 2023' },
  { id:'UTM-FYP2023-003', title:'AI-Town Smart Development', students:'Nurul Huda binti Ahmad', status:'approved', supervisor:'Dr. Sarah Lee', date:'12 Oct 2023' },
  { id:'UTM-FYP2023-004', title:'AI-Powered Smart Presentation', students:'Ahmad Bin Razak, Nur Ayuni Idris', status:'pending', supervisor:'Dr. Sarah Lee', date:'12 Oct 2023' },
  { id:'UTM-FYP2023-005', title:'AI-Powered Farmotor Technology', students:'I Bin Razak, Nur Ayuni Idris', status:'revision', supervisor:'Dr. Sarah Lee', date:'12 Oct 2023' },
  { id:'UTM-FYP2023-006', title:'AI-Teen Smart Projects', students:'Muhammad Adam bin Hassan', status:'pending', supervisor:'Dr. Sarah Lee', date:'12 Oct 2023' },
  { id:'UTM-FYP2023-007', title:'AI-Powered Smart Corsotation', students:'Ahmad Bin Razak', status:'approved', supervisor:'Dr. Sarah Lee', date:'12 Oct 2023' },
  { id:'UTM-FYP2023-008', title:'AI-Powered Smart Autorizations', students:'Nur Ayuni Idris', status:'approved', supervisor:'Dr. Sarah Lee', date:'12 Oct 2023' },
  { id:'UTM-FYP2023-009', title:'AI-Smart Favigation System', students:'Ahmad Bin Razak, Nur Ayuni Idris', status:'approved', supervisor:'Dr. Sarah Lee', date:'12 Oct 2023' },
  { id:'UTM-FYP2023-010', title:'AI-Powered Smart Campus Portal', students:'Ahmad Bin Razak, Nur Ayuni Idris', status:'approved', supervisor:'Dr. Sarah Lee', date:'12 Oct 2023' },
  { id:'UTM-FYP2023-011', title:'AI-Powered Invitation System', students:'Ahmad Bin Razak, Nur Ayuni Idris', status:'approved', supervisor:'Dr. Sarah Lee', date:'12 Oct 2023' },
  { id:'UTM-FYP2023-012', title:'AI-Powered Smart Campus Tracker', students:'Ahmad Bin Razak, Nur Ayuni Idris', status:'pending', supervisor:'Dr. Sarah Lee', date:'12 Oct 2023' },
];

const statusMap = { pending:'pending', approved:'approved', revision:'revision', rejected:'rejected' };
const statusLabel = { pending:'Pending Review', approved:'Approved', revision:'Needs Revision', rejected:'Rejected' };
let currentFilter = 'all';
let currentSearch = '';
const ITEMS_PER_PAGE = 8;
let currentPage = 1;
let activeCommentProject = null;

// ── BUILD CARD ──
function buildCard(p) {
  return `<div class="project-card" data-status="${p.status}" data-title="${p.title.toLowerCase()}" data-students="${p.students.toLowerCase()}">
    <div class="card-top">
      <div>
        <div class="card-id">${p.id}</div>
        <div class="card-title">${p.title}</div>
      </div>
      <span class="badge ${p.status}">${statusLabel[p.status]}</span>
    </div>
    <div class="card-meta">
      <strong>Students:</strong> ${p.students}<br>
      <strong>Supervisor:</strong> ${p.supervisor}<br>
      <strong>Submitted:</strong> ${p.date}
    </div>
    <div class="card-actions">
      <button class="btn btn-approve" onclick="approveProject('${p.id}')">✓ Approve</button>
      <button class="btn btn-reject" onclick="rejectProject('${p.id}')">✕ Reject</button>
      <button class="btn btn-comment" onclick="openCommentModal('${p.id}','${p.title}')">💬 Comment</button>
    </div>
  </div>`;
}

function buildGradeCard(p) {
  return `<div class="project-card">
    <div class="card-top">
      <div>
        <div class="card-id">${p.id}</div>
        <div class="card-title">${p.title}</div>
      </div>
      <span class="badge approved">Graded</span>
    </div>
    <div class="card-meta">
      <strong>Students:</strong> ${p.students}<br>
      <strong>Total Marks:</strong> 78 / 100<br>
      <strong>Grade:</strong> A-
    </div>
    <div class="card-actions">
      <button class="btn btn-grades" onclick="openMarksModal({title:'${p.title}',id:'${p.id}'})">✏ Edit Marks</button>
      <button class="btn btn-comment" onclick="openCommentModal('${p.id}','${p.title}')">💬 Feedback</button>
    </div>
  </div>`;
}

function renderCards() {
  const grid = document.getElementById('cards-grid');
  let filtered = projects.filter(p => {
    const matchFilter = currentFilter === 'all' || p.status === currentFilter;
    const matchSearch = !currentSearch || p.title.toLowerCase().includes(currentSearch) || p.students.toLowerCase().includes(currentSearch);
    return matchFilter && matchSearch;
  });
  const total = filtered.length;
  const pages = Math.ceil(total / ITEMS_PER_PAGE);
  if (currentPage > pages) currentPage = 1;
  const start = (currentPage - 1) * ITEMS_PER_PAGE;
  const slice = filtered.slice(start, start + ITEMS_PER_PAGE);
  grid.innerHTML = slice.length ? slice.map(buildCard).join('') : '<p style="color:var(--muted);font-size:14px;padding:24px 0">No projects found.</p>';
  renderPagination(pages);
}

function renderPagination(pages) {
  const pg = document.getElementById('pagination');
  if (pages <= 1) { pg.innerHTML = ''; return; }
  let html = `<button class="pg-btn" onclick="goPage(${currentPage-1})" ${currentPage===1?'disabled':''}>‹</button>`;
  for (let i=1;i<=pages;i++) html += `<button class="pg-btn ${i===currentPage?'active':''}" onclick="goPage(${i})">${i}</button>`;
  html += `<button class="pg-btn" onclick="goPage(${currentPage+1})" ${currentPage===pages?'disabled':''}>›</button>`;
  pg.innerHTML = html;
}

function goPage(n) { const pages = Math.ceil(projects.filter(p => currentFilter==='all'||p.status===currentFilter).length / ITEMS_PER_PAGE); if (n<1||n>pages) return; currentPage=n; renderCards(); }

function filterCards(f, el) {
  currentFilter = f; currentPage = 1;
  document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
  if (el) el.classList.add('active');
  renderCards();
}
function searchCards(v) { currentSearch = v.toLowerCase(); currentPage = 1; renderCards(); }

function renderReviewQueue() {
  const grid = document.getElementById('review-grid');
  const pending = projects.filter(p => p.status === 'pending' || p.status === 'revision');
  grid.innerHTML = pending.map(buildCard).join('');
}

function renderGrades() {
  const grid = document.getElementById('grades-grid');
  grid.innerHTML = projects.slice(0,6).map(buildGradeCard).join('');
}

// ── ACTIONS ──
function approveProject(id) {
  const p = projects.find(x => x.id===id);
  if (p) { p.status = 'approved'; renderCards(); renderReviewQueue(); showToast(`"${p.title.substring(0,30)}…" approved!`, 'success'); }
}
function rejectProject(id) {
  const p = projects.find(x => x.id===id);
  if (p) { p.status = 'rejected'; renderCards(); renderReviewQueue(); showToast(`Submission rejected.`, 'error'); }
}
function openCommentModal(id, title) {
  activeCommentProject = id;
  document.getElementById('comment-modal-sub').textContent = title.length > 45 ? title.substring(0,45)+'…' : title;
  document.getElementById('comment-text').value = '';
  document.getElementById('comment-modal').classList.add('open');
}
function submitComment() {
  const txt = document.getElementById('comment-text').value.trim();
  if (!txt) { showToast('Please enter your feedback.', 'error'); return; }
  closeModal('comment-modal');
  showToast('Feedback submitted successfully!', 'success');
}
function openMarksModal(p) {
  document.getElementById('marks-modal-sub').textContent = p.title.length > 45 ? p.title.substring(0,45)+'…' : p.title;
  document.getElementById('marks-modal').classList.add('open');
}
function submitMarks() {
  closeModal('marks-modal');
  showToast('Marks saved successfully!', 'success');
}
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// ── NAVIGATION ──
const sections = ['projects','submissions','review','students','grades','reports','settings'];
const sectionTitles = { projects:'Academic Project Review', submissions:'Project Submissions', review:'Review Queue', students:'Assigned Students', grades:'Final Grades', reports:'Reports', settings:'Settings' };
function showSection(sec, el) {
  sections.forEach(s => document.getElementById('sec-'+s).classList.add('hidden'));
  document.getElementById('sec-'+sec).classList.remove('hidden');
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  if (el) el.classList.add('active');
  document.getElementById('topbar-title').textContent = sectionTitles[sec];
  if (sec==='review') renderReviewQueue();
  if (sec==='grades') renderGrades();
}

// ── TOAST ──
let toastTimer;
function showToast(msg, type='success') {
  const t = document.getElementById('toast');
  t.textContent = msg; t.className = 'toast '+type+' show';
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => t.classList.remove('show'), 3200);
}

// ── KEYBOARD ──
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') { closeModal('comment-modal'); closeModal('marks-modal'); }
});

// Close modals on overlay click
document.querySelectorAll('.modal-overlay').forEach(o => o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); }));

// New doLogout function
function doLogout() {
  window.location.href = 'login.php'; // Or logout.php if it handles session destruction
}
</script>
</body>
</html>
