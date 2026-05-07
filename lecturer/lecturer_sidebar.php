<aside class="sidebar d-flex flex-column">
    <div class="brand">
        <img class="brand-mark" src="../assets/utm-logo.png" alt="UTM logo">
        <div>
            <p class="brand-title">UTM</p>
            <div class="brand-subtitle">Universiti Teknologi Malaysia<br>Academic Review</div>
        </div>
    </div>

    <nav class="sidebar-nav" aria-label="Lecturer navigation">
        <a class="nav-link active" href="#dashboard">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>
        <a class="nav-link" href="#projects">
            <i class="bi bi-folder-fill"></i>
            <span>Projects</span>
        </a>
        <a class="nav-link" href="#faculty">
            <i class="bi bi-mortarboard-fill"></i>
            <span>Faculty</span>
        </a>
        <a class="nav-link" href="#students">
            <i class="bi bi-people-fill"></i>
            <span>Students</span>
        </a>
        <a class="nav-link" href="#submissions">
            <i class="bi bi-file-earmark-check-fill"></i>
            <span>Submissions</span>
        </a>
        <a class="nav-link" href="#reports">
            <i class="bi bi-bar-chart-line-fill"></i>
            <span>Reports</span>
        </a>
        <a class="nav-link" href="#settings">
            <i class="bi bi-gear-fill"></i>
            <span>Settings</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="d-flex align-items-center gap-2 mb-2">
            <div class="avatar"><?= e($lecturerInitials) ?></div>
            <div class="min-w-0">
                <div class="fw-bold text-truncate"><?= e($lecturerName) ?></div>
                <small class="text-muted">Lecturer</small>
            </div>
        </div>
        <a class="text-muted small" href="../public/logout.php">Logout</a>
    </div>
</aside>