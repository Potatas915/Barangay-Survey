<?php
// Shared staff navbar. Include this from every staff page instead of writing
// the navbar markup by hand, so the active link always matches the current
// page and every page shows the same links (this fixes the highlighting bug
// where different pages had different link sets and no active indicator).
$current_page = basename($_SERVER["PHP_SELF"]);

function nav_active($page, $current_page) {
    return $page === $current_page ? " active" : "";
}
?>
<div class="sidebar<?= isset($no_print_nav) && $no_print_nav ? " no-print" : "" ?>">
    <button type="button" class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()" aria-label="Collapse sidebar" title="Collapse sidebar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </button>
    <div class="sidebar-brand">
        <span class="logo-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11z"/><path d="M12 8v5"/><path d="M9.5 10.5h5"/></svg>
        </span>
        <span class="sidebar-brand-text label">
            BARANGAY<br>HEALTH CENTER
            <small>Health Survey Management</small>
        </span>
    </div>

    <nav class="sidebar-links">
        <a class="<?= trim(nav_active("dashboard.php", $current_page)) ?>" href="dashboard.php" title="Dashboard">
            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/><rect x="14" y="12" width="7" height="9" rx="2"/><rect x="3" y="16" width="7" height="5" rx="2"/></svg></span>
            <span class="label">Dashboard</span>
        </a>
        <a class="<?= trim(nav_active("survey_management.php", $current_page) . " " . nav_active("survey_add.php", $current_page) . " " . nav_active("survey_edit.php", $current_page)) . " " . nav_active("question_management.php", $current_page)?>" href="survey_management.php" title="Survey Management">
            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>
            <span class="label">Survey Management</span>
        </a>

        <a class="<?= trim(nav_active("resident_management.php", $current_page) . nav_active("register.php", $current_page)) ?>" href="resident_management.php" title="Resident Management">
            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
            <span class="label">Resident Management</span>
        </a>

        <a class="<?= trim(nav_active("results.php", $current_page)) ?>" href="results.php" title="Survey Results">
            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 3v9l6 3"/></svg></span>
            <span class="label">Survey Results</span>
        </a>
        <a class="<?= trim(nav_active("reports.php", $current_page)) ?>" href="reports.php" title="Reports">
            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></span>
            <span class="label">Reports</span>
        </a>
    </nav>

    <a class="logout-link" href="logout.php" title="Logout">
        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span>
        <span class="label">Logout</span>
    </a>

    <div class="sidebar-footer">
        <span class="sidebar-footer-ic">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </span>
        <span class="label">Building a healthier<br>community together.</span>
    </div>
</div>