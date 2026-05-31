<aside class="sidebar">
    <div class="sidebar-brand">
        <img class="utm-logo" src="../assets/utm-logo.png" alt="UTM logo">
        <div>
            <p class="brand-title">UTM Student</p>
            <span class="brand-subtitle">FYP Submission</span>
        </div>
    </div>
    <nav class="sidebar-nav" aria-label="Student navigation">
        <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
        <a class="sidebar-link<?= $currentPage === 'student_dashboard.php' ? ' active' : '' ?>" href="student_dashboard.php"><i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span></a>
        <a class="sidebar-link<?= $currentPage === 'student_projects.php' ? ' active' : '' ?>" href="student_projects.php"><i class="bi bi-folder2-open"></i><span>My Projects</span></a>
        <a class="sidebar-link<?= $currentPage === 'student_proposals.php' ? ' active' : '' ?>" href="student_proposals.php"><i class="bi bi-file-earmark-text-fill"></i><span>Proposals</span></a>
        <a class="sidebar-link" href="student_dashboard.php"><i class="bi bi-chat-dots-fill"></i><span>Feedback</span></a>
        <a class="sidebar-link" href="student_dashboard.php"><i class="bi bi-calendar-event-fill"></i><span>Deadlines</span></a>
    </nav>
</aside>