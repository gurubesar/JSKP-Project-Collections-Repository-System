<aside class="sidebar">
    <div class="sidebar-brand">
        <img class="utm-logo" src="../assets/utm-logo.png" alt="UTM logo">
        <div>
            <p class="brand-title">UTM</p>
            <span class="brand-subtitle">Universiti Teknologi Malaysia<br>Admin System</span>
        </div>
    </div>

    <nav class="sidebar-nav" aria-label="Admin navigation">
        <a class="sidebar-link<?= basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? ' active' : '' ?>" href="admin_dashboard.php">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>
        <a class="sidebar-link<?= basename($_SERVER['PHP_SELF']) === 'admin_students.php' ? ' active' : '' ?>" href="admin_students.php">
            <i class="bi bi-mortarboard-fill"></i>
            <span>Students</span>
        </a>
        <a class="sidebar-link<?= basename($_SERVER['PHP_SELF']) === 'admin_lecturers.php' ? ' active' : '' ?>" href="admin_lecturers.php">
            <i class="bi bi-person-video3"></i>
            <span>Lecturers</span>
        </a>
        <a class="sidebar-link<?= basename($_SERVER['PHP_SELF']) === 'admin_admins.php' ? ' active' : '' ?>" href="admin_admins.php">
            <i class="bi bi-shield-lock-fill"></i>
            <span>Admin</span>
        </a>
        <a class="sidebar-link<?= basename($_SERVER['PHP_SELF']) === 'admin_projects.php' ? ' active' : '' ?>" href="admin_projects.php">
            <i class="bi bi-folder2-open"></i>
            <span>Projects</span>
        </a>
        <a class="sidebar-link" href="#">
            <i class="bi bi-file-earmark-check-fill"></i>
            <span>Submissions</span>
        </a>
        <a class="sidebar-link" href="#">
            <i class="bi bi-journal-bookmark-fill"></i>
            <span>Programs</span>
        </a>
        <a class="sidebar-link<?= basename($_SERVER['PHP_SELF']) === 'report.php' ? ' active' : '' ?>" href="report.php">
            <i class="bi bi-bar-chart-line-fill"></i>
            <span>Reports</span>
        </a>
        <a class="sidebar-link" href="#">
            <i class="bi bi-cash-coin"></i>
            <span>Finance</span>
        </a>
        <a class="sidebar-link" href="#">
            <i class="bi bi-gear-fill"></i>
            <span>Settings</span>
        </a>
    </nav>
</aside>
