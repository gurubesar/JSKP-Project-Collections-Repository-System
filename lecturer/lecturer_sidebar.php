<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<aside class="sidebar d-flex flex-column">
    <div class="brand">
        <img class="brand-mark" src="../assets/utm-logo.png" alt="UTM logo">
        <div>
            <p class="brand-title">UTM</p>
            <div class="brand-subtitle">Universiti Teknologi Malaysia<br>Academic Review</div>
        </div>
    </div>

    <nav class="sidebar-nav" aria-label="Lecturer navigation">
        <a class="nav-link <?= $currentPage === 'Lecturer_dashboard.php' ? 'active' : '' ?>" href="Lecturer_dashboard.php">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>
        <a class="nav-link <?= $currentPage === 'lecturer_projects.php' ? 'active' : '' ?>" href="lecturer_projects.php">
            <i class="bi bi-folder-fill"></i>
            <span>Projects</span>
        </a>
        <a class="nav-link <?= $currentPage === 'lecturer_students.php' ? 'active' : '' ?>" href="lecturer_students.php">
            <i class="bi bi-people-fill"></i>
            <span>Students</span>
        </a>
        <a class="nav-link <?= $currentPage === 'lecturer_submissions.php' ? 'active' : '' ?>" href="lecturer_submissions.php">
            <i class="bi bi-file-earmark-check-fill"></i>
            <span>Submissions</span>
        </a>
        <a class="nav-link <?= $currentPage === 'lecturer_grades.php' ? 'active' : '' ?>" href="lecturer_grades.php">
            <i class="bi bi-star-fill"></i>
            <span>Grades</span>
        </a>
    </nav>

</aside>
