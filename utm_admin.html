<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UTM Admin Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --crimson: #7B1C2E;
    --crimson-dark: #5a1322;
    --crimson-light: #9d2340;
    --gold: #C9A84C;
    --gold-light: #e2c272;
    --gold-pale: #f5e9c8;
    --bg: #f4f1ec;
    --surface: #ffffff;
    --surface2: #faf8f5;
    --text: #1a1410;
    --text-muted: #6b5f55;
    --border: #e8e0d5;
    --green: #2d7a4f;
    --green-bg: #e8f5ee;
    --orange: #c47a1e;
    --orange-bg: #fdf3e3;
    --red: #c0392b;
    --red-bg: #fdecea;
    --gray: #8a8078;
    --gray-bg: #f0ede8;
    --shadow: 0 2px 12px rgba(123,28,46,0.08);
    --shadow-lg: 0 8px 32px rgba(123,28,46,0.14);
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
  }

  /* ── LOGIN ── */
  #login-screen {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--crimson-dark) 0%, var(--crimson) 50%, #a0243a 100%);
    position: relative;
    overflow: hidden;
  }

  #login-screen::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
  }

  .login-card {
    background: var(--surface);
    border-radius: 20px;
    padding: 48px;
    width: 420px;
    box-shadow: var(--shadow-lg);
    position: relative;
    z-index: 1;
    animation: slideUp 0.5s ease;
  }

  @keyframes slideUp {
    from { opacity: 0; transform: translateY(24px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .login-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 32px;
  }

  .logo-circle {
    width: 52px;
    height: 52px;
    background: var(--crimson);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    font-weight: 800;
    color: var(--gold);
    letter-spacing: -1px;
  }

  .login-logo-text h2 { font-size: 20px; font-weight: 800; color: var(--crimson); }
  .login-logo-text p { font-size: 11px; color: var(--text-muted); font-weight: 500; letter-spacing: 0.5px; }

  .login-card h1 { font-size: 26px; font-weight: 800; margin-bottom: 6px; }
  .login-card > p { font-size: 13px; color: var(--text-muted); margin-bottom: 32px; }

  .field { margin-bottom: 18px; }
  .field label { display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 7px; letter-spacing: 0.4px; text-transform: uppercase; }

  .field input {
    width: 100%;
    padding: 12px 16px;
    border: 1.5px solid var(--border);
    border-radius: 10px;
    font-size: 14px;
    font-family: inherit;
    background: var(--surface2);
    color: var(--text);
    transition: border-color 0.2s, box-shadow 0.2s;
    outline: none;
  }

  .field input:focus {
    border-color: var(--crimson);
    box-shadow: 0 0 0 3px rgba(123,28,46,0.1);
    background: white;
  }

  .field-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 7px; }
  .field-row label { margin-bottom: 0; }
  .forgot-link { font-size: 12px; color: var(--crimson); text-decoration: none; font-weight: 600; cursor: pointer; }
  .forgot-link:hover { text-decoration: underline; }

  .btn-login {
    width: 100%;
    padding: 14px;
    background: var(--crimson);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    margin-top: 8px;
    transition: background 0.2s, transform 0.1s;
  }

  .btn-login:hover { background: var(--crimson-dark); transform: translateY(-1px); }
  .btn-login:active { transform: translateY(0); }

  .error-msg {
    background: var(--red-bg);
    color: var(--red);
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 16px;
    display: none;
  }

  .demo-hint {
    text-align: center;
    margin-top: 20px;
    font-size: 12px;
    color: var(--text-muted);
    background: var(--surface2);
    border-radius: 8px;
    padding: 10px;
    border: 1px solid var(--border);
  }

  .demo-hint strong { color: var(--crimson); }

  /* ── APP SHELL ── */
  #app { display: none; height: 100vh; overflow: hidden; flex-direction: row; }

  /* SIDEBAR */
  .sidebar {
    width: 240px;
    background: var(--crimson-dark);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    overflow-y: auto;
  }

  .sidebar-brand {
    padding: 20px 20px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
  }

  .brand-badge {
    width: 42px;
    height: 42px;
    background: var(--gold);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 16px;
    color: var(--crimson-dark);
    flex-shrink: 0;
  }

  .brand-text { line-height: 1.2; }
  .brand-text strong { font-size: 15px; font-weight: 800; color: white; }
  .brand-text span { font-size: 10px; color: rgba(255,255,255,0.5); font-weight: 500; display: block; letter-spacing: 0.5px; }

  .nav { padding: 16px 12px; flex: 1; }

  .nav-section-label {
    font-size: 10px;
    font-weight: 700;
    color: rgba(255,255,255,0.35);
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 12px 10px 6px;
  }

  .nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 10px;
    cursor: pointer;
    color: rgba(255,255,255,0.65);
    font-size: 13.5px;
    font-weight: 500;
    transition: all 0.18s;
    margin-bottom: 2px;
    position: relative;
  }

  .nav-item:hover { background: rgba(255,255,255,0.08); color: white; }

  .nav-item.active {
    background: var(--gold);
    color: var(--crimson-dark);
    font-weight: 700;
  }

  .nav-item .icon { width: 18px; text-align: center; font-size: 15px; }

  .sidebar-footer {
    padding: 14px 16px;
    border-top: 1px solid rgba(255,255,255,0.08);
  }

  .user-info {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    padding: 8px;
    border-radius: 10px;
    transition: background 0.18s;
  }

  .user-info:hover { background: rgba(255,255,255,0.08); }

  .avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--gold);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 13px;
    color: var(--crimson-dark);
    flex-shrink: 0;
  }

  .user-info-text strong { font-size: 13px; font-weight: 700; color: white; display: block; }
  .user-info-text span { font-size: 11px; color: rgba(255,255,255,0.45); }

  /* MAIN */
  .main { flex: 1; display: flex; flex-direction: column; overflow: hidden; }

  /* TOPBAR */
  .topbar {
    background: white;
    border-bottom: 1px solid var(--border);
    padding: 14px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
  }

  .topbar-left h1 { font-size: 20px; font-weight: 800; color: var(--text); }
  .topbar-left p { font-size: 12px; color: var(--text-muted); margin-top: 1px; }

  .topbar-right { display: flex; align-items: center; gap: 12px; }

  .search-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--surface2);
    border: 1.5px solid var(--border);
    border-radius: 10px;
    padding: 8px 14px;
    width: 220px;
  }

  .search-bar input {
    border: none;
    background: transparent;
    outline: none;
    font-size: 13px;
    font-family: inherit;
    color: var(--text);
    width: 100%;
  }

  .search-bar span { color: var(--text-muted); font-size: 14px; }

  .notif-btn {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: var(--surface2);
    border: 1.5px solid var(--border);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    position: relative;
    transition: background 0.18s;
  }

  .notif-btn:hover { background: var(--border); }

  .notif-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: var(--crimson);
    color: white;
    font-size: 9px;
    font-weight: 700;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .signout-btn {
    padding: 8px 14px;
    background: var(--crimson);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: background 0.18s;
  }

  .signout-btn:hover { background: var(--crimson-dark); }

  /* CONTENT */
  .content {
    flex: 1;
    overflow-y: auto;
    padding: 24px 28px;
  }

  /* PAGES */
  .page { display: none; animation: fadeIn 0.25s ease; }
  .page.active { display: block; }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
  }

  /* STAT CARDS */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
    margin-bottom: 24px;
  }

  .stat-card {
    background: white;
    border-radius: 14px;
    padding: 18px 20px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    position: relative;
    overflow: hidden;
    cursor: default;
    transition: transform 0.18s, box-shadow 0.18s;
  }

  .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }

  .stat-card.gold { background: linear-gradient(135deg, var(--crimson), var(--crimson-light)); color: white; border-color: transparent; }
  .stat-card.gold .stat-label { color: rgba(255,255,255,0.7); }
  .stat-card.gold .stat-change { color: rgba(255,255,255,0.8); }
  .stat-card.gold .stat-icon { background: rgba(255,255,255,0.15); color: white; }

  .stat-icon {
    width: 38px;
    height: 38px;
    background: var(--gold-pale);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    margin-bottom: 12px;
    color: var(--gold);
  }

  .stat-label { font-size: 11.5px; font-weight: 600; color: var(--text-muted); margin-bottom: 4px; letter-spacing: 0.3px; }
  .stat-value { font-size: 22px; font-weight: 800; line-height: 1; }
  .stat-change { font-size: 11px; font-weight: 600; color: var(--green); margin-top: 4px; }
  .stat-change.neg { color: var(--red); }

  /* GRID 2 */
  .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
  .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 24px; }

  /* CARD */
  .card {
    background: white;
    border-radius: 14px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    overflow: hidden;
  }

  .card-header {
    padding: 18px 22px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--border);
  }

  .card-header h3 { font-size: 15px; font-weight: 700; }
  .card-header p { font-size: 12px; color: var(--text-muted); margin-top: 1px; }

  .card-body { padding: 20px 22px; }

  /* TABLE */
  .table-wrapper { overflow-x: auto; }

  table { width: 100%; border-collapse: collapse; }

  th {
    background: var(--crimson-dark);
    color: white;
    font-size: 12px;
    font-weight: 600;
    padding: 11px 14px;
    text-align: left;
    letter-spacing: 0.3px;
    white-space: nowrap;
  }

  th:first-child { border-radius: 0; }

  td {
    padding: 11px 14px;
    font-size: 13px;
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
    color: var(--text);
  }

  tr:last-child td { border-bottom: none; }
  tr:hover td { background: var(--surface2); }

  .mini-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: var(--gold-pale);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
    color: var(--crimson);
    margin-right: 8px;
    vertical-align: middle;
    flex-shrink: 0;
  }

  .td-name { display: flex; align-items: center; }

  /* BADGES */
  .badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11.5px;
    font-weight: 600;
  }

  .badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; }
  .badge.active { background: var(--green-bg); color: var(--green); }
  .badge.active::before { background: var(--green); }
  .badge.pending { background: var(--orange-bg); color: var(--orange); }
  .badge.pending::before { background: var(--orange); }
  .badge.inactive { background: var(--gray-bg); color: var(--gray); }
  .badge.inactive::before { background: var(--gray); }

  /* ACTION BUTTONS */
  .action-btns { display: flex; gap: 6px; }

  .btn-sm {
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    border: none;
    transition: all 0.15s;
  }

  .btn-view { background: var(--gold); color: var(--crimson-dark); }
  .btn-view:hover { background: var(--gold-light); }
  .btn-edit { background: var(--surface2); color: var(--text); border: 1px solid var(--border); }
  .btn-edit:hover { background: var(--border); }
  .btn-del { background: var(--red-bg); color: var(--red); }
  .btn-del:hover { background: #fad0cc; }

  /* TOOLBAR */
  .toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    gap: 12px;
    flex-wrap: wrap;
  }

  .toolbar-left { display: flex; align-items: center; gap: 10px; }
  .toolbar-right { display: flex; align-items: center; gap: 10px; }

  .toolbar h3 { font-size: 17px; font-weight: 800; }

  .search-input {
    display: flex;
    align-items: center;
    gap: 8px;
    background: white;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    padding: 7px 12px;
  }

  .search-input input {
    border: none;
    background: transparent;
    outline: none;
    font-size: 13px;
    font-family: inherit;
    width: 160px;
  }

  .btn-primary {
    padding: 9px 16px;
    background: var(--gold);
    color: var(--crimson-dark);
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    transition: background 0.18s;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .btn-primary:hover { background: var(--gold-light); }

  .btn-outline {
    padding: 9px 16px;
    background: white;
    color: var(--text);
    border: 1.5px solid var(--border);
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.18s;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .btn-outline:hover { background: var(--surface2); border-color: var(--text-muted); }

  /* MINI CHART (CSS) */
  .mini-chart {
    height: 40px;
    display: flex;
    align-items: flex-end;
    gap: 3px;
    margin-top: 10px;
  }

  .mini-bar {
    flex: 1;
    border-radius: 3px 3px 0 0;
    background: rgba(255,255,255,0.3);
    transition: height 0.3s;
  }

  .bar-chart {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    height: 150px;
    padding: 0 4px;
  }

  .bar-group { display: flex; flex-direction: column; align-items: center; gap: 4px; flex: 1; }
  .bar-group-bars { display: flex; gap: 3px; align-items: flex-end; }

  .bar {
    width: 14px;
    border-radius: 3px 3px 0 0;
    transition: height 0.4s ease;
  }

  .bar.crimson { background: var(--crimson); }
  .bar.gold { background: var(--gold); }
  .bar.green { background: var(--green); }

  .bar-label { font-size: 10px; color: var(--text-muted); font-weight: 500; }

  /* LINE CHART SVG */
  .chart-svg { width: 100%; height: 160px; }

  /* ACTIVITY LOG */
  .activity-list { display: flex; flex-direction: column; gap: 14px; }

  .activity-item {
    display: flex;
    gap: 12px;
    align-items: flex-start;
  }

  .activity-dot {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
    margin-top: 1px;
  }

  .activity-dot.green { background: var(--green-bg); color: var(--green); }
  .activity-dot.gold { background: var(--gold-pale); color: var(--gold); }
  .activity-dot.red { background: var(--red-bg); color: var(--red); }
  .activity-dot.gray { background: var(--gray-bg); color: var(--gray); }

  .activity-text strong { font-size: 13px; font-weight: 600; display: block; }
  .activity-text span { font-size: 11.5px; color: var(--text-muted); }

  /* SECTION TITLE */
  .section-title { font-size: 18px; font-weight: 800; margin-bottom: 16px; }

  /* PAGINATION */
  .pagination {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 6px;
    padding: 14px 22px;
    border-top: 1px solid var(--border);
  }

  .page-btn {
    width: 30px;
    height: 30px;
    border-radius: 6px;
    border: 1.5px solid var(--border);
    background: white;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
    font-family: inherit;
  }

  .page-btn:hover { border-color: var(--crimson); color: var(--crimson); }
  .page-btn.active { background: var(--crimson); border-color: var(--crimson); color: white; }

  /* MODAL */
  .modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    display: none;
  }

  .modal-overlay.open { display: flex; animation: fadeIn 0.2s ease; }

  .modal {
    background: white;
    border-radius: 16px;
    width: 480px;
    max-width: 95vw;
    box-shadow: var(--shadow-lg);
    animation: slideUp 0.25s ease;
  }

  .modal-header {
    padding: 22px 24px 16px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .modal-header h3 { font-size: 17px; font-weight: 800; }

  .modal-close {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: var(--surface2);
    border: none;
    cursor: pointer;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .modal-close:hover { background: var(--border); }

  .modal-body { padding: 20px 24px; }
  .modal-footer { padding: 14px 24px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 10px; }

  .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  .form-group { display: flex; flex-direction: column; gap: 6px; }
  .form-group.full { grid-column: span 2; }

  .form-group label { font-size: 11.5px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.4px; }

  .form-group input, .form-group select {
    padding: 10px 12px;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    font-size: 13px;
    font-family: inherit;
    outline: none;
    transition: border-color 0.2s;
  }

  .form-group input:focus, .form-group select:focus { border-color: var(--crimson); }

  /* METRIC ROW */
  .metric-row {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
  }

  .metric-box {
    flex: 1;
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px 18px;
    box-shadow: var(--shadow);
  }

  .metric-box h4 { font-size: 12px; color: var(--text-muted); font-weight: 600; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.4px; }
  .metric-box .metric-val { font-size: 20px; font-weight: 800; }
  .metric-box .metric-sub { font-size: 11px; color: var(--green); font-weight: 600; margin-top: 2px; }

  /* REPORTS */
  .report-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }

  .report-item {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 18px 20px;
    cursor: pointer;
    transition: all 0.18s;
    box-shadow: var(--shadow);
  }

  .report-item:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); border-color: var(--gold); }
  .report-item h4 { font-size: 14px; font-weight: 700; margin-bottom: 6px; }
  .report-item p { font-size: 12px; color: var(--text-muted); }
  .report-icon { font-size: 22px; margin-bottom: 10px; }

  /* SETTINGS */
  .settings-section { background: white; border-radius: 14px; border: 1px solid var(--border); margin-bottom: 20px; overflow: hidden; }
  .settings-section .card-header { border-bottom: 1px solid var(--border); }
  .settings-row { padding: 16px 22px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); }
  .settings-row:last-child { border-bottom: none; }
  .settings-row-left h4 { font-size: 14px; font-weight: 600; margin-bottom: 2px; }
  .settings-row-left p { font-size: 12px; color: var(--text-muted); }

  .toggle {
    width: 44px;
    height: 24px;
    background: var(--border);
    border-radius: 12px;
    position: relative;
    cursor: pointer;
    transition: background 0.2s;
  }

  .toggle.on { background: var(--green); }
  .toggle::after {
    content: '';
    position: absolute;
    width: 18px;
    height: 18px;
    background: white;
    border-radius: 50%;
    top: 3px;
    left: 3px;
    transition: transform 0.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
  }

  .toggle.on::after { transform: translateX(20px); }

  /* TAG */
  .tag {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
  }

  .tag.cs { background: #e8f0fe; color: #1a56db; }
  .tag.eng { background: #fef3c7; color: #92400e; }
  .tag.sci { background: #d1fae5; color: #065f46; }

  /* TOAST */
  .toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: var(--text);
    color: white;
    padding: 12px 18px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    box-shadow: var(--shadow-lg);
    z-index: 2000;
    display: none;
    animation: slideUp 0.25s ease;
  }

  .toast.show { display: block; }

  /* FORGOT PASSWORD */
  #forgot-screen {
    min-height: 100vh;
    display: none;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--crimson-dark) 0%, var(--crimson) 50%, #a0243a 100%);
    position: relative;
  }

  #forgot-screen::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
  }

  .scrollbar-thin::-webkit-scrollbar { width: 5px; }
  .scrollbar-thin::-webkit-scrollbar-thumb { background: rgba(123,28,46,0.2); border-radius: 3px; }
</style>
</head>
<body>

<!-- LOGIN -->
<div id="login-screen">
  <div class="login-card">
    <div class="login-logo">
      <div class="logo-circle">UTM</div>
      <div class="login-logo-text">
        <h2>UTM Admin Portal</h2>
        <p>UNIVERSITI TEKNOLOGI MALAYSIA</p>
      </div>
    </div>
    <h1>Welcome back 👋</h1>
    <p>Sign in to your admin account to continue</p>
    <div class="error-msg" id="login-error">❌ Invalid email or password. Please try again.</div>
    <div class="field">
      <label>Email Address</label>
      <input type="email" id="login-email" placeholder="admin@utm.edu.my" value="admin@utm.edu.my">
    </div>
    <div class="field">
      <div class="field-row">
        <label>Password</label>
        <a class="forgot-link" onclick="showForgot()">Forgot password?</a>
      </div>
      <input type="password" id="login-pass" placeholder="••••••••" value="admin123">
    </div>
    <button class="btn-login" onclick="doLogin()">Sign In →</button>
    <div class="demo-hint">Demo: <strong>admin@utm.edu.my</strong> / <strong>admin123</strong></div>
  </div>
</div>

<!-- FORGOT PASSWORD -->
<div id="forgot-screen">
  <div class="login-card">
    <div class="login-logo">
      <div class="logo-circle">UTM</div>
      <div class="login-logo-text">
        <h2>Reset Password</h2>
        <p>UNIVERSITI TEKNOLOGI MALAYSIA</p>
      </div>
    </div>
    <h1>Forgot password?</h1>
    <p>Enter your email and we'll send a reset link.</p>
    <div class="field" style="margin-top:24px;">
      <label>Email Address</label>
      <input type="email" placeholder="admin@utm.edu.my">
    </div>
    <button class="btn-login" onclick="showToast('Reset link sent! Check your email.'); showLogin()">Send Reset Link</button>
    <div style="text-align:center;margin-top:16px;">
      <a class="forgot-link" onclick="showLogin()">← Back to Sign In</a>
    </div>
  </div>
</div>

<!-- APP -->
<div id="app">
  <!-- SIDEBAR -->
  <aside class="sidebar scrollbar-thin">
    <div class="sidebar-brand">
      <div class="brand-badge">UTM</div>
      <div class="brand-text">
        <strong>Admin Portal</strong>
        <span>UTM Management System</span>
      </div>
    </div>
    <nav class="nav">
      <div class="nav-section-label">Main</div>
      <div class="nav-item active" onclick="navigate('dashboard', this)">
        <span class="icon">⊞</span> Dashboard
      </div>
      <div class="nav-section-label">Management</div>
      <div class="nav-item" onclick="navigate('students', this)">
        <span class="icon">👤</span> Students
      </div>
      <div class="nav-item" onclick="navigate('lecturers', this)">
        <span class="icon">🎓</span> Lecturers
      </div>
      <div class="nav-item" onclick="navigate('staff', this)">
        <span class="icon">👥</span> Staff
      </div>
      <div class="nav-section-label">Academic</div>
      <div class="nav-item" onclick="navigate('projects', this)">
        <span class="icon">📁</span> Projects
      </div>
      <div class="nav-item" onclick="navigate('submissions', this)">
        <span class="icon">📄</span> Submissions
      </div>
      <div class="nav-item" onclick="navigate('programs', this)">
        <span class="icon">📚</span> Programs
      </div>
      <div class="nav-section-label">System</div>
      <div class="nav-item" onclick="navigate('reports', this)">
        <span class="icon">📊</span> Reports
      </div>
      <div class="nav-item" onclick="navigate('finance', this)">
        <span class="icon">💰</span> Finance
      </div>
      <div class="nav-item" onclick="navigate('activity', this)">
        <span class="icon">🔍</span> Activity Log
      </div>
      <div class="nav-item" onclick="navigate('backup', this)">
        <span class="icon">💾</span> Backup & Restore
      </div>
      <div class="nav-item" onclick="navigate('storage', this)">
        <span class="icon">🗂️</span> File Storage
      </div>
      <div class="nav-item" onclick="navigate('settings', this)">
        <span class="icon">⚙️</span> Settings
      </div>
    </nav>
    <div class="sidebar-footer">
      <div class="user-info" onclick="navigate('profile', document.querySelector('.nav-item.active'))">
        <div class="avatar">AF</div>
        <div class="user-info-text">
          <strong>Ahmad Fauzi</strong>
          <span>Super Admin</span>
        </div>
      </div>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <h1 id="page-title">Dashboard Overview</h1>
        <p id="page-sub">Monday, May 04, 2026</p>
      </div>
      <div class="topbar-right">
        <button class="signout-btn" onclick="doSignOut()">Sign Out</button>
      </div>
    </div>

    <div class="content scrollbar-thin">

      <!-- DASHBOARD -->
      <div class="page active" id="page-dashboard">
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">👤</div>
            <div class="stat-label">Total Students</div>
            <div class="stat-value">1234</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">🎓</div>
            <div class="stat-label">Active Lecturers</div>
            <div class="stat-value">456</div>
          </div>
          <div class="stat-card gold">
            <div class="stat-icon">📁</div>
            <div class="stat-label">Active Projects</div>
            <div class="stat-value">789</div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">📄</div>
            <div class="stat-label">Total Submissions</div>
            <div class="stat-value">1111</div>
          </div>
        </div>
          
          <div class="card">
            <div class="card-header">
              <div><h3>Weekly Activities</h3><p>Submissions, Projects, Lecturers</p></div>
            </div>
            <div class="card-body">
              <div style="display:flex;gap:14px;margin-bottom:12px;">
                <span style="font-size:11px;font-weight:600;display:flex;align-items:center;gap:5px;"><span style="width:10px;height:10px;background:var(--crimson);border-radius:2px;display:inline-block;"></span>Submissions</span>
                <span style="font-size:11px;font-weight:600;display:flex;align-items:center;gap:5px;"><span style="width:10px;height:10px;background:var(--gold);border-radius:2px;display:inline-block;"></span>Projects</span>
                <span style="font-size:11px;font-weight:600;display:flex;align-items:center;gap:5px;"><span style="width:10px;height:10px;background:var(--green);border-radius:2px;display:inline-block;"></span>Lecturers</span>
              </div>
              <div class="bar-chart">
                <div class="bar-group"><div class="bar-group-bars"><div class="bar crimson" style="height:80px;"></div><div class="bar gold" style="height:60px;"></div><div class="bar green" style="height:50px;"></div></div><div class="bar-label">Mon</div></div>
                <div class="bar-group"><div class="bar-group-bars"><div class="bar crimson" style="height:40px;"></div><div class="bar gold" style="height:70px;"></div><div class="bar green" style="height:30px;"></div></div><div class="bar-label">Tue</div></div>
                <div class="bar-group"><div class="bar-group-bars"><div class="bar crimson" style="height:60px;"></div><div class="bar gold" style="height:45px;"></div><div class="bar green" style="height:55px;"></div></div><div class="bar-label">Wed</div></div>
                <div class="bar-group"><div class="bar-group-bars"><div class="bar crimson" style="height:90px;"></div><div class="bar gold" style="height:80px;"></div><div class="bar green" style="height:70px;"></div></div><div class="bar-label">Thu</div></div>
                <div class="bar-group"><div class="bar-group-bars"><div class="bar crimson" style="height:50px;"></div><div class="bar gold" style="height:55px;"></div><div class="bar green" style="height:40px;"></div></div><div class="bar-label">Fri</div></div>
              </div>
            </div>
          </div>

        <div class="grid-2">
          <div class="card">
            <div class="card-header">
              <div><h3>Recent Student Registrations</h3><p>Latest additions</p></div>
              <button class="btn-primary" onclick="openModal('modal-student')">+ Add New Student</button>
            </div>
            <div class="table-wrapper">
              <table>
                <thead><tr><th>Name</th><th>Matrix ID</th><th>Program</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                  <tr><td><div class="td-name"><div class="mini-avatar">AA</div>Ahmad Afif</div></td><td><span style="font-family:'DM Mono',monospace;font-size:12px;">UTM12345</span></td><td>B.S. CS</td><td><span class="badge active">Active</span></td><td><div class="action-btns"><button class="btn-sm btn-view" onclick="showToast('Viewing profile...')">View</button><button class="btn-sm btn-edit" onclick="showToast('Edit mode')">Edit</button><button class="btn-sm btn-del" onclick="showToast('Deleted')">Del</button></div></td></tr>
                  <tr><td><div class="td-name"><div class="mini-avatar">NF</div>Nazruddin Farhan</div></td><td><span style="font-family:'DM Mono',monospace;font-size:12px;">UTM12346</span></td><td>B.S. CS</td><td><span class="badge active">Active</span></td><td><div class="action-btns"><button class="btn-sm btn-view" onclick="showToast('Viewing profile...')">View</button><button class="btn-sm btn-edit" onclick="showToast('Edit mode')">Edit</button><button class="btn-sm btn-del" onclick="showToast('Deleted')">Del</button></div></td></tr>
                  <tr><td><div class="td-name"><div class="mini-avatar">AS</div>Ammar Syahmi</div></td><td><span style="font-family:'DM Mono',monospace;font-size:12px;">UTM12347</span></td><td>B.Eng</td><td><span class="badge pending">Pending</span></td><td><div class="action-btns"><button class="btn-sm btn-view" onclick="showToast('Viewing profile...')">View</button><button class="btn-sm btn-edit" onclick="showToast('Edit mode')">Edit</button><button class="btn-sm btn-del" onclick="showToast('Deleted')">Del</button></div></td></tr>
                  <tr><td><div class="td-name"><div class="mini-avatar">ML</div>Muhammad Luqman</div></td><td><span style="font-family:'DM Mono',monospace;font-size:12px;">UTM12348</span></td><td>B.S. CS</td><td><span class="badge active">Active</span></td><td><div class="action-btns"><button class="btn-sm btn-view" onclick="showToast('Viewing profile...')">View</button><button class="btn-sm btn-edit" onclick="showToast('Edit mode')">Edit</button><button class="btn-sm btn-del" onclick="showToast('Deleted')">Del</button></div></td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="card">
            <div class="card-header"><div><h3>Recent Activity Log</h3><p>System events</p></div></div>
            <div class="card-body">
              <div class="activity-list">
                <div class="activity-item">
                  <div class="activity-dot green">✔</div>
                  <div class="activity-text"><strong>Ahmad Fauzi added new student record</strong><span>2 minutes ago</span></div>
                </div>
                <div class="activity-item">
                  <div class="activity-dot gold">✏</div>
                  <div class="activity-text"><strong>Supervisor assigned to Amirul Aziz</strong><span>8 minutes ago</span></div>
                </div>
                <div class="activity-item">
                  <div class="activity-dot red">⚠</div>
                  <div class="activity-text"><strong>Submission deadline updated for CS Dept</strong><span>22 minutes ago</span></div>
                </div>
                <div class="activity-item">
                  <div class="activity-dot green">📤</div>
                  <div class="activity-text"><strong>Report generated: Q2 Enrollment Summary</strong><span>1 hour ago</span></div>
                </div>
                <div class="activity-item">
                  <div class="activity-dot gray">💾</div>
                  <div class="activity-text"><strong>System backup completed successfully</strong><span>3 hours ago</span></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- STUDENTS -->
      <div class="page" id="page-students">
        <div class="toolbar">
          <div class="toolbar-left">
            <h3>Student Management</h3>
          </div>
            <button class="btn-outline">Filters ▾</button>
            <button class="btn-primary" onclick="openModal('modal-student')">+ Add New Student</button>
          </div>
        </div>
        <div class="card">
          <div class="table-wrapper">
            <table>
              <thead><tr><th>Profile</th><th>Name</th><th>Matrix ID</th><th>Faculty</th><th>Program</th><th>Intake</th><th>Supervisor</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody>
                <tr><td><div class="mini-avatar">AA</div></td><td>Amirul Aziz</td><td><span style="font-family:'DM Mono',monospace;font-size:12px;">U20CS0123</span></td><td><span class="tag cs">Computing</span></td><td>B.Sc. Comp. Sci.</td><td>2023/24</td><td>Dr. Rahman</td><td><span class="badge active">Active</span></td><td><div class="action-btns"><button class="btn-sm btn-view" onclick="showToast('Viewing...')">View</button><button class="btn-sm btn-edit" onclick="showToast('Edit')">Edit</button><button class="btn-sm btn-del" onclick="showToast('Del')">Del</button></div></td></tr>
                <tr><td><div class="mini-avatar">AK</div></td><td>Aldali Khnah</td><td><span style="font-family:'DM Mono',monospace;font-size:12px;">U20CS0124</span></td><td><span class="tag cs">Computing</span></td><td>B.Sc. Comp. Sci.</td><td>2023/24</td><td>Dr. Siti</td><td><span class="badge active">Active</span></td><td><div class="action-btns"><button class="btn-sm btn-view" onclick="showToast('Viewing...')">View</button><button class="btn-sm btn-edit" onclick="showToast('Edit')">Edit</button><button class="btn-sm btn-del" onclick="showToast('Del')">Del</button></div></td></tr>
                <tr><td><div class="mini-avatar">IH</div></td><td>Ifrara Hamwah</td><td><span style="font-family:'DM Mono',monospace;font-size:12px;">U20CS0125</span></td><td><span class="tag eng">Engineering</span></td><td>B.Eng. Electrical</td><td>2023/24</td><td>Prof. Azman</td><td><span class="badge pending">Pending</span></td><td><div class="action-btns"><button class="btn-sm btn-view" onclick="showToast('Viewing...')">View</button><button class="btn-sm btn-edit" onclick="showToast('Edit')">Edit</button><button class="btn-sm btn-del" onclick="showToast('Del')">Del</button></div></td></tr>
                <tr><td><div class="mini-avatar">AE</div></td><td>Amirul Emar</td><td><span style="font-family:'DM Mono',monospace;font-size:12px;">U20CS0126</span></td><td><span class="tag cs">Computing</span></td><td>B.Sc. Software Eng.</td><td>2023/24</td><td>Dr. Lim</td><td><span class="badge active">Active</span></td><td><div class="action-btns"><button class="btn-sm btn-view" onclick="showToast('Viewing...')">View</button><button class="btn-sm btn-edit" onclick="showToast('Edit')">Edit</button><button class="btn-sm btn-del" onclick="showToast('Del')">Del</button></div></td></tr>
                <tr><td><div class="mini-avatar">NA</div></td><td>Nurul Atiqah</td><td><span style="font-family:'DM Mono',monospace;font-size:12px;">UTM20050</span></td><td><span class="tag sci">Science</span></td><td>B.Sc. Biology</td><td>2022/23</td><td>Dr. Wong</td><td><span class="badge inactive">Inactive</span></td><td><div class="action-btns"><button class="btn-sm btn-view" onclick="showToast('Viewing...')">View</button><button class="btn-sm btn-edit" onclick="showToast('Edit')">Edit</button><button class="btn-sm btn-del" onclick="showToast('Del')">Del</button></div></td></tr>
              </tbody>
            </table>
          </div>
          <div class="pagination">
            <span style="font-size:12px;color:var(--text-muted);margin-right:8px;">Page 1 of 12</span>
            <button class="page-btn active">1</button>
            <button class="page-btn">2</button>
            <button class="page-btn">3</button>
            <button class="page-btn">→</button>
          </div>
        </div>
      </div>

      <!-- LECTURERS -->
      <div class="page" id="page-lecturers">
        <div class="toolbar">
          <div class="toolbar-left"><h3>Lecturer Management</h3></div>
          <div class="toolbar-right">
            <div class="search-input"><span>🔍</span><input placeholder="Search lecturers..."></div>
            <button class="btn-primary" onclick="openModal('modal-lecturer')">+ Add Lecturer</button>
          </div>
        </div>
        <div class="card">
          <div class="table-wrapper">
            <table>
              <thead><tr><th>Profile</th><th>Name</th><th>Staff ID</th><th>Faculty</th><th>Specialization</th><th>Students</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody>
                <tr><td><div class="mini-avatar">DR</div></td><td>Dr. Rahman Harun</td><td><span style="font-family:'DM Mono',monospace;font-size:12px;">L10023</span></td><td><span class="tag cs">Computing</span></td><td>Machine Learning</td><td>12</td><td><span class="badge active">Active</span></td><td><div class="action-btns"><button class="btn-sm btn-view" onclick="showToast('Viewing...')">View</button><button class="btn-sm btn-edit" onclick="showToast('Edit')">Edit</button><button class="btn-sm btn-del" onclick="showToast('Del')">Del</button></div></td></tr>
                <tr><td><div class="mini-avatar">PS</div></td><td>Prof. Siti Norlia</td><td><span style="font-family:'DM Mono',monospace;font-size:12px;">L10024</span></td><td><span class="tag eng">Engineering</span></td><td>Civil Structures</td><td>8</td><td><span class="badge active">Active</span></td><td><div class="action-btns"><button class="btn-sm btn-view" onclick="showToast('Viewing...')">View</button><button class="btn-sm btn-edit" onclick="showToast('Edit')">Edit</button><button class="btn-sm btn-del" onclick="showToast('Del')">Del</button></div></td></tr>
                <tr><td><div class="mini-avatar">DL</div></td><td>Dr. Lim Wei Kang</td><td><span style="font-family:'DM Mono',monospace;font-size:12px;">L10025</span></td><td><span class="tag cs">Computing</span></td><td>Software Engineering</td><td>15</td><td><span class="badge pending">Pending</span></td><td><div class="action-btns"><button class="btn-sm btn-view" onclick="showToast('Viewing...')">View</button><button class="btn-sm btn-edit" onclick="showToast('Edit')">Edit</button><button class="btn-sm btn-del" onclick="showToast('Del')">Del</button></div></td></tr>
              </tbody>
            </table>
          </div>
          <div class="pagination"><button class="page-btn active">1</button><button class="page-btn">2</button><button class="page-btn">→</button></div>
        </div>
      </div>

      <!-- STAFF -->
      <div class="page" id="page-staff">
        <div class="toolbar">
          <div class="toolbar-left"><h3>Staff Management</h3></div>
          <div class="toolbar-right">
            <div class="search-input"><span>🔍</span><input placeholder="Search staff..."></div>
            <button class="btn-primary" onclick="openModal('modal-student')">+ Add Staff</button>
          </div>
        </div>
        <div class="card">
          <div class="table-wrapper">
            <table>
              <thead><tr><th>Name</th><th>Staff ID</th><th>Role</th><th>Department</th><th>Email</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody>
                <tr><td><div class="td-name"><div class="mini-avatar">MA</div>Mohd Azlan</div></td><td><span style="font-family:'DM Mono',monospace;font-size:12px;">S2001</span></td><td>Admin Officer</td><td>Registry</td><td>azlan@utm.my</td><td><span class="badge active">Active</span></td><td><div class="action-btns"><button class="btn-sm btn-view" onclick="showToast('Viewing...')">View</button><button class="btn-sm btn-edit" onclick="showToast('Edit')">Edit</button></div></td></tr>
                <tr><td><div class="td-name"><div class="mini-avatar">FN</div>Fatimah Noor</div></td><td><span style="font-family:'DM Mono',monospace;font-size:12px;">S2002</span></td><td>IT Support</td><td>ICT Unit</td><td>fatimah@utm.my</td><td><span class="badge active">Active</span></td><td><div class="action-btns"><button class="btn-sm btn-view" onclick="showToast('Viewing...')">View</button><button class="btn-sm btn-edit" onclick="showToast('Edit')">Edit</button></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- PROJECTS -->
      <div class="page" id="page-projects">
        <div class="toolbar">
          <div class="toolbar-left"><h3>Project Records</h3></div>
          <div class="toolbar-right">
            <div class="search-input"><span>🔍</span><input placeholder="Search projects..."></div>
            <button class="btn-primary" onclick="showToast('Add Project modal')">+ Add Project</button>
          </div>
        </div>
        <div class="card">
          <div class="table-wrapper">
            <table>
              <thead><tr><th>Project Title</th><th>Student</th><th>Supervisor</th><th>Faculty</th><th>Deadline</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody>
                <tr><td style="max-width:200px;">Smart Campus Energy Monitor</td><td>Amirul Aziz</td><td>Dr. Rahman</td><td><span class="tag cs">Computing</span></td><td>Dec 2024</td><td><span class="badge active">Active</span></td><td><div class="action-btns"><button class="btn-sm btn-view" onclick="showToast('Viewing...')">View</button><button class="btn-sm btn-edit" onclick="showToast('Edit')">Edit</button></div></td></tr>
                <tr><td style="max-width:200px;">AI-Based Flood Prediction System</td><td>Aldali Khnah</td><td>Dr. Siti</td><td><span class="tag cs">Computing</span></td><td>Jan 2025</td><td><span class="badge pending">Pending</span></td><td><div class="action-btns"><button class="btn-sm btn-view" onclick="showToast('Viewing...')">View</button><button class="btn-sm btn-edit" onclick="showToast('Edit')">Edit</button></div></td></tr>
                <tr><td style="max-width:200px;">Structural Load Analysis Tool</td><td>Ifrara Hamwah</td><td>Prof. Azman</td><td><span class="tag eng">Engineering</span></td><td>Nov 2024</td><td><span class="badge active">Active</span></td><td><div class="action-btns"><button class="btn-sm btn-view" onclick="showToast('Viewing...')">View</button><button class="btn-sm btn-edit" onclick="showToast('Edit')">Edit</button></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- SUBMISSIONS -->
      <div class="page" id="page-submissions">
        <div class="toolbar">
          <div class="toolbar-left"><h3>Submission Deadlines</h3></div>
          <div class="toolbar-right">
            <button class="btn-primary" onclick="showToast('Add Deadline modal')">+ Set Deadline</button>
          </div>
        </div>
        <div class="card" style="margin-bottom:20px;">
          <div class="table-wrapper">
            <table>
              <thead><tr><th>Title</th><th>Faculty</th><th>Type</th><th>Deadline</th><th>Submitted</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody>
                <tr><td>Final Year Project Report</td><td><span class="tag cs">Computing</span></td><td>Report</td><td>15 Dec 2024</td><td>842 / 1,200</td><td><span class="badge pending">In Progress</span></td><td><div class="action-btns"><button class="btn-sm btn-edit" onclick="showToast('Edit deadline')">Edit</button></div></td></tr>
                <tr><td>Research Proposal</td><td><span class="tag eng">Engineering</span></td><td>Proposal</td><td>30 Nov 2024</td><td>512 / 512</td><td><span class="badge active">Completed</span></td><td><div class="action-btns"><button class="btn-sm btn-view" onclick="showToast('View')">View</button></div></td></tr>
                <tr><td>Thesis Draft Submission</td><td><span class="tag sci">Science</span></td><td>Thesis</td><td>20 Jan 2025</td><td>10 / 300</td><td><span class="badge inactive">Not Started</span></td><td><div class="action-btns"><button class="btn-sm btn-edit" onclick="showToast('Edit')">Edit</button></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- PROGRAMS -->
      <div class="page" id="page-programs">
        <div class="toolbar">
          <div class="toolbar-left"><h3>Academic Programs</h3></div>
          <div class="toolbar-right"><button class="btn-primary" onclick="showToast('Add Program')">+ Add Program</button></div>
        </div>
        <div class="card">
          <div class="table-wrapper">
            <table>
              <thead><tr><th>Program Name</th><th>Faculty</th><th>Duration</th><th>Credits</th><th>Enrolled</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody>
                <tr><td>B.Sc. Computer Science</td><td><span class="tag cs">Computing</span></td><td>4 years</td><td>120</td><td>1,842</td><td><span class="badge active">Active</span></td><td><div class="action-btns"><button class="btn-sm btn-edit" onclick="showToast('Edit')">Edit</button><button class="btn-sm btn-del" onclick="showToast('Del')">Del</button></div></td></tr>
                <tr><td>B.Eng. Civil Engineering</td><td><span class="tag eng">Engineering</span></td><td>4 years</td><td>130</td><td>924</td><td><span class="badge active">Active</span></td><td><div class="action-btns"><button class="btn-sm btn-edit" onclick="showToast('Edit')">Edit</button><button class="btn-sm btn-del" onclick="showToast('Del')">Del</button></div></td></tr>
                <tr><td>B.Sc. Biology</td><td><span class="tag sci">Science</span></td><td>3 years</td><td>110</td><td>612</td><td><span class="badge pending">Review</span></td><td><div class="action-btns"><button class="btn-sm btn-edit" onclick="showToast('Edit')">Edit</button><button class="btn-sm btn-del" onclick="showToast('Del')">Del</button></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- REPORTS -->
      <div class="page" id="page-reports">
        <div class="section-title">Generate Reports</div>
        <div class="report-grid">
          <div class="report-item" onclick="showToast('Generating Enrollment report...')">
            <div class="report-icon">📈</div>
            <h4>Enrollment Report</h4>
            <p>Student registration stats by faculty and program</p>
          </div>
          <div class="report-item" onclick="showToast('Generating Submission report...')">
            <div class="report-icon">📄</div>
            <h4>Submission Report</h4>
            <p>Deadline compliance and submission rates</p>
          </div>
          <div class="report-item" onclick="showToast('Generating Finance report...')">
            <div class="report-icon">💹</div>
            <h4>Finance Report</h4>
            <p>Revenue breakdown, fees collected, pending payments</p>
          </div>
          <div class="report-item" onclick="showToast('Generating Project report...')">
            <div class="report-icon">📁</div>
            <h4>Project Report</h4>
            <p>Active projects, completion rates, supervisors</p>
          </div>
          <div class="report-item" onclick="showToast('Generating Lecturer report...')">
            <div class="report-icon">🎓</div>
            <h4>Lecturer Report</h4>
            <p>Workload, student ratios, publications</p>
          </div>
          <div class="report-item" onclick="showToast('Generating Activity report...')">
            <div class="report-icon">🔍</div>
            <h4>System Activity Report</h4>
            <p>Admin actions, logins, changes log</p>
          </div>
        </div>
      </div>

      <!-- FINANCE -->
      <div class="page" id="page-finance">
        <div class="section-title">Finance Overview</div>
        <div class="metric-row">
          <div class="metric-box"><h4>Total Revenue</h4><div class="metric-val">RM 5,420,000</div><div class="metric-sub">↑ +6.1% vs last year</div></div>
          <div class="metric-box"><h4>Fees Collected</h4><div class="metric-val">RM 4,810,000</div><div class="metric-sub">↑ +3.2% vs last year</div></div>
          <div class="metric-box"><h4>Pending Payments</h4><div class="metric-val" style="color:var(--orange);">RM 610,000</div><div class="metric-sub" style="color:var(--orange);">⚠ 142 students</div></div>
          <div class="metric-box"><h4>Grants Received</h4><div class="metric-val">RM 890,000</div><div class="metric-sub">↑ +12.4% vs last year</div></div>
        </div>
        <div class="card">
          <div class="card-header"><div><h3>Fee Transactions</h3><p>Recent collections</p></div></div>
          <div class="table-wrapper">
            <table>
              <thead><tr><th>Student</th><th>Matrix ID</th><th>Amount</th><th>Type</th><th>Date</th><th>Status</th></tr></thead>
              <tbody>
                <tr><td>Amirul Aziz</td><td><span style="font-family:'DM Mono',monospace;font-size:12px;">U20CS0123</span></td><td>RM 4,200</td><td>Tuition</td><td>1 Nov 2024</td><td><span class="badge active">Paid</span></td></tr>
                <tr><td>Ifrara Hamwah</td><td><span style="font-family:'DM Mono',monospace;font-size:12px;">U20CS0125</span></td><td>RM 3,800</td><td>Tuition</td><td>3 Nov 2024</td><td><span class="badge pending">Pending</span></td></tr>
                <tr><td>Nurul Atiqah</td><td><span style="font-family:'DM Mono',monospace;font-size:12px;">UTM20050</span></td><td>RM 4,200</td><td>Tuition</td><td>5 Nov 2024</td><td><span class="badge active">Paid</span></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ACTIVITY LOG -->
      <div class="page" id="page-activity">
        <div class="section-title">System Activity Log</div>
        <div class="card">
          <div class="table-wrapper">
            <table>
              <thead><tr><th>Timestamp</th><th>Admin</th><th>Action</th><th>Target</th><th>IP Address</th></tr></thead>
              <tbody>
                <tr><td><span style="font-family:'DM Mono',monospace;font-size:12px;">2026-05-04 09:02</span></td><td>Ahmad Fauzi</td><td>Added student</td><td>Amirul Aziz</td><td>192.168.1.10</td></tr>
                <tr><td><span style="font-family:'DM Mono',monospace;font-size:12px;">2026-05-04 08:55</span></td><td>Ahmad Fauzi</td><td>Assigned supervisor</td><td>Ifrara Hamwah → Dr. Siti</td><td>192.168.1.10</td></tr>
                <tr><td><span style="font-family:'DM Mono',monospace;font-size:12px;">2026-05-04 08:40</span></td><td>Ahmad Fauzi</td><td>Updated deadline</td><td>FYP CS Dept.</td><td>192.168.1.10</td></tr>
                <tr><td><span style="font-family:'DM Mono',monospace;font-size:12px;">2026-05-04 07:30</span></td><td>System</td><td>Auto backup</td><td>All databases</td><td>localhost</td></tr>
                <tr><td><span style="font-family:'DM Mono',monospace;font-size:12px;">2026-05-03 16:10</span></td><td>Ahmad Fauzi</td><td>Generated report</td><td>Q2 Enrollment</td><td>192.168.1.10</td></tr>
                <tr><td><span style="font-family:'DM Mono',monospace;font-size:12px;">2026-05-03 15:00</span></td><td>Ahmad Fauzi</td><td>Deleted student</td><td>John Doe</td><td>192.168.1.10</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- BACKUP -->
      <div class="page" id="page-backup">
        <div class="section-title">Backup & Restore Data</div>
        <div class="grid-2">
          <div class="card">
            <div class="card-header"><div><h3>Create Backup</h3><p>Export a snapshot of all data</p></div></div>
            <div class="card-body">
              <div style="display:flex;flex-direction:column;gap:14px;">
                <div class="settings-row" style="padding:0;border:none;">
                  <div class="settings-row-left"><h4>Student Records</h4><p>All student profiles and data</p></div>
                  <input type="checkbox" checked style="width:16px;height:16px;accent-color:var(--crimson);">
                </div>
                <div class="settings-row" style="padding:0;border:none;">
                  <div class="settings-row-left"><h4>Project Records</h4><p>All project data</p></div>
                  <input type="checkbox" checked style="width:16px;height:16px;accent-color:var(--crimson);">
                </div>
                <div class="settings-row" style="padding:0;border:none;">
                  <div class="settings-row-left"><h4>Finance Records</h4><p>Fee transactions and revenue</p></div>
                  <input type="checkbox" style="width:16px;height:16px;accent-color:var(--crimson);">
                </div>
                <button class="btn-primary" style="justify-content:center;" onclick="showToast('✅ Backup created successfully!')">💾 Create Backup Now</button>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-header"><div><h3>Restore Data</h3><p>Recent backup files</p></div></div>
            <div class="card-body">
              <div style="display:flex;flex-direction:column;gap:10px;">
                <div style="padding:12px;background:var(--surface2);border-radius:10px;border:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                  <div><div style="font-size:13px;font-weight:600;">backup_20260504_0730.zip</div><div style="font-size:11px;color:var(--text-muted);">324 MB · 4 hours ago</div></div>
                  <button class="btn-sm btn-view" onclick="showToast('Restoring...')">Restore</button>
                </div>
                <div style="padding:12px;background:var(--surface2);border-radius:10px;border:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                  <div><div style="font-size:13px;font-weight:600;">backup_20260503_0200.zip</div><div style="font-size:11px;color:var(--text-muted);">321 MB · 1 day ago</div></div>
                  <button class="btn-sm btn-view" onclick="showToast('Restoring...')">Restore</button>
                </div>
                <div style="padding:12px;background:var(--surface2);border-radius:10px;border:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                  <div><div style="font-size:13px;font-weight:600;">backup_20260502_0200.zip</div><div style="font-size:11px;color:var(--text-muted);">318 MB · 2 days ago</div></div>
                  <button class="btn-sm btn-view" onclick="showToast('Restoring...')">Restore</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- FILE STORAGE -->
      <div class="page" id="page-storage">
        <div class="toolbar">
          <div class="toolbar-left"><h3>File Storage</h3></div>
          <div class="toolbar-right"><button class="btn-primary" onclick="showToast('Upload file')">📤 Upload File</button></div>
        </div>
        <div style="background:white;border-radius:14px;border:1px solid var(--border);padding:20px;margin-bottom:20px;box-shadow:var(--shadow);">
          <div style="display:flex;align-items:center;gap:16px;margin-bottom:12px;">
            <div style="flex:1;">
              <div style="font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:6px;">Storage Used</div>
              <div style="height:10px;background:var(--border);border-radius:5px;overflow:hidden;">
                <div style="height:100%;width:62%;background:linear-gradient(90deg,var(--crimson),var(--gold));border-radius:5px;"></div>
              </div>
            </div>
            <div style="font-size:13px;font-weight:700;">62 GB / 100 GB</div>
          </div>
        </div>
        <div class="card">
          <div class="table-wrapper">
            <table>
              <thead><tr><th>File Name</th><th>Type</th><th>Size</th><th>Uploaded By</th><th>Date</th><th>Actions</th></tr></thead>
              <tbody>
                <tr><td>📄 FYP_Report_Amirul.pdf</td><td>PDF</td><td>4.2 MB</td><td>Amirul Aziz</td><td>1 Nov 2024</td><td><div class="action-btns"><button class="btn-sm btn-view" onclick="showToast('Download')">Download</button><button class="btn-sm btn-del" onclick="showToast('Deleted')">Del</button></div></td></tr>
                <tr><td>📊 Q2_Enrollment.xlsx</td><td>Excel</td><td>1.8 MB</td><td>Ahmad Fauzi</td><td>3 Nov 2024</td><td><div class="action-btns"><button class="btn-sm btn-view" onclick="showToast('Download')">Download</button><button class="btn-sm btn-del" onclick="showToast('Deleted')">Del</button></div></td></tr>
                <tr><td>🖼 Campus_Map.png</td><td>Image</td><td>2.4 MB</td><td>Admin</td><td>15 Oct 2024</td><td><div class="action-btns"><button class="btn-sm btn-view" onclick="showToast('Download')">Download</button><button class="btn-sm btn-del" onclick="showToast('Deleted')">Del</button></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- SETTINGS -->
      <div class="page" id="page-settings">
        <div class="section-title">System Settings</div>
        <div class="settings-section">
          <div class="card-header"><div><h3>General</h3></div></div>
          <div class="settings-row">
            <div class="settings-row-left"><h4>System Name</h4><p>Display name for the admin portal</p></div>
            <input value="UTM Admin Portal" style="padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none;">
          </div>
          <div class="settings-row">
            <div class="settings-row-left"><h4>Email Notifications</h4><p>Receive alerts for new registrations</p></div>
            <div class="toggle on" onclick="this.classList.toggle('on')"></div>
          </div>
          <div class="settings-row">
            <div class="settings-row-left"><h4>Auto Backup</h4><p>Automatically backup daily at 2AM</p></div>
            <div class="toggle on" onclick="this.classList.toggle('on')"></div>
          </div>
          <div class="settings-row">
            <div class="settings-row-left"><h4>Maintenance Mode</h4><p>Disable access for non-admin users</p></div>
            <div class="toggle" onclick="this.classList.toggle('on')"></div>
          </div>
        </div>
        <div class="settings-section">
          <div class="card-header"><div><h3>Security</h3></div></div>
          <div class="settings-row">
            <div class="settings-row-left"><h4>Two-Factor Authentication</h4><p>Require 2FA for admin logins</p></div>
            <div class="toggle" onclick="this.classList.toggle('on')"></div>
          </div>
          <div class="settings-row">
            <div class="settings-row-left"><h4>Session Timeout</h4><p>Auto sign out after inactivity</p></div>
            <select style="padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;outline:none;">
              <option>30 minutes</option>
              <option>1 hour</option>
              <option>4 hours</option>
            </select>
          </div>
          <div class="settings-row">
            <div class="settings-row-left"><h4>Activity Logging</h4><p>Log all admin actions</p></div>
            <div class="toggle on" onclick="this.classList.toggle('on')"></div>
          </div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
          <button class="btn-outline" onclick="showToast('Changes discarded')">Discard</button>
          <button class="btn-primary" onclick="showToast('✅ Settings saved!')">Save Changes</button>
        </div>
      </div>

      <!-- PROFILE -->
      <div class="page" id="page-profile">
        <div class="section-title">Admin Profile</div>
        <div class="grid-2">
          <div class="card">
            <div class="card-body" style="text-align:center;padding:32px;">
              <div style="width:80px;height:80px;background:var(--crimson);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;color:var(--gold);margin:0 auto 16px;">AF</div>
              <div style="font-size:20px;font-weight:800;">Ahmad Fauzi</div>
              <div style="font-size:13px;color:var(--text-muted);margin-bottom:4px;">Super Admin</div>
              <div style="font-size:13px;color:var(--text-muted);">admin@utm.edu.my</div>
              <div style="margin-top:16px;display:flex;gap:10px;justify-content:center;">
                <span class="badge active">Active Account</span>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-header"><div><h3>Edit Profile</h3></div></div>
            <div class="card-body">
              <div class="form-grid">
                <div class="form-group"><label>First Name</label><input value="Ahmad"></div>
                <div class="form-group"><label>Last Name</label><input value="Fauzi"></div>
                <div class="form-group full"><label>Email</label><input value="admin@utm.edu.my"></div>
                <div class="form-group full"><label>Current Password</label><input type="password" placeholder="Enter current password"></div>
                <div class="form-group"><label>New Password</label><input type="password" placeholder="New password"></div>
                <div class="form-group"><label>Confirm Password</label><input type="password" placeholder="Confirm password"></div>
              </div>
              <div style="margin-top:16px;display:flex;justify-content:flex-end;">
                <button class="btn-primary" onclick="showToast('✅ Profile updated!')">Save Changes</button>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- MODALS -->
<div class="modal-overlay" id="modal-student">
  <div class="modal">
    <div class="modal-header">
      <h3>Add New Student</h3>
      <button class="modal-close" onclick="closeModal('modal-student')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-group"><label>First Name</label><input placeholder="e.g. Amirul"></div>
        <div class="form-group"><label>Last Name</label><input placeholder="e.g. Aziz"></div>
        <div class="form-group"><label>Matrix ID</label><input placeholder="e.g. U20CS0123"></div>
        <div class="form-group"><label>Faculty</label><select><option>Computing</option><option>Engineering</option><option>Science</option><option>Management</option></select></div>
        <div class="form-group"><label>Program</label><input placeholder="e.g. B.Sc. Computer Science"></div>
        <div class="form-group"><label>Intake</label><input placeholder="e.g. 2023/24"></div>
        <div class="form-group full"><label>Email</label><input placeholder="student@utm.my" type="email"></div>
        <div class="form-group"><label>Supervisor</label><input placeholder="e.g. Dr. Rahman"></div>
        <div class="form-group"><label>Status</label><select><option>Active</option><option>Pending</option><option>Inactive</option></select></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-outline" onclick="closeModal('modal-student')">Cancel</button>
      <button class="btn-primary" onclick="closeModal('modal-student');showToast('✅ Student added successfully!')">Add Student</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modal-lecturer">
  <div class="modal">
    <div class="modal-header">
      <h3>Add New Lecturer</h3>
      <button class="modal-close" onclick="closeModal('modal-lecturer')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-group"><label>Full Name</label><input placeholder="e.g. Dr. Rahman"></div>
        <div class="form-group"><label>Staff ID</label><input placeholder="e.g. L10030"></div>
        <div class="form-group"><label>Faculty</label><select><option>Computing</option><option>Engineering</option><option>Science</option></select></div>
        <div class="form-group"><label>Specialization</label><input placeholder="e.g. Machine Learning"></div>
        <div class="form-group full"><label>Email</label><input placeholder="lecturer@utm.my" type="email"></div>
        <div class="form-group"><label>Status</label><select><option>Active</option><option>Pending</option></select></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-outline" onclick="closeModal('modal-lecturer')">Cancel</button>
      <button class="btn-primary" onclick="closeModal('modal-lecturer');showToast('✅ Lecturer added successfully!')">Add Lecturer</button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<script>
  const CREDS = { email: 'admin@utm.edu.my', password: 'admin123' };

  function doLogin() {
    const email = document.getElementById('login-email').value;
    const pass = document.getElementById('login-pass').value;
    const err = document.getElementById('login-error');
    if (email === CREDS.email && pass === CREDS.password) {
      document.getElementById('login-screen').style.display = 'none';
      document.getElementById('app').style.display = 'flex';
    } else {
      err.style.display = 'block';
    }
  }

  document.getElementById('login-pass').addEventListener('keydown', e => { if (e.key === 'Enter') doLogin(); });

  function doSignOut() {
    document.getElementById('app').style.display = 'none';
    document.getElementById('login-screen').style.display = 'flex';
    document.getElementById('login-error').style.display = 'none';
  }

  function showForgot() {
    document.getElementById('login-screen').style.display = 'none';
    const f = document.getElementById('forgot-screen');
    f.style.display = 'flex';
  }

  function showLogin() {
    document.getElementById('forgot-screen').style.display = 'none';
    document.getElementById('login-screen').style.display = 'flex';
  }

  const pageTitles = {
    dashboard: ['Dashboard Overview', 'Welcome back, Ahmad!'],
    students: ['Student Management', 'Manage all student records'],
    lecturers: ['Lecturer Management', 'View and manage lecturers'],
    staff: ['Staff Management', 'Administrative staff records'],
    projects: ['Project Records', 'Track all active projects'],
    submissions: ['Submission Deadlines', 'Monitor submission progress'],
    programs: ['Academic Programs', 'Manage degree programs'],
    reports: ['Reports', 'Generate and export reports'],
    finance: ['Finance', 'Revenue and fee management'],
    activity: ['Activity Log', 'Monitor all system actions'],
    backup: ['Backup & Restore', 'Data protection and recovery'],
    storage: ['File Storage', 'Manage uploaded files'],
    settings: ['Settings', 'System configuration'],
    profile: ['Admin Profile', 'Manage your account'],
  };

  function navigate(page, el) {
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    el.classList.add('active');
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    const target = document.getElementById('page-' + page);
    if (target) target.classList.add('active');
    const info = pageTitles[page] || ['Dashboard', ''];
    document.getElementById('page-title').textContent = info[0];
    document.getElementById('page-sub').textContent = info[1];
  }

  function openModal(id) {
    document.getElementById(id).classList.add('open');
  }

  function closeModal(id) {
    document.getElementById(id).classList.remove('open');
  }

  document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
  });

  let toastTimer;
  function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('show'), 2800);
  }
</script>
</body>
</html>
