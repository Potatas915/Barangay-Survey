<?php
// Shared topbar for resident pages. Include right after opening <div class="container">.
// Shows the current date/time and the logged-in resident.
$resident_initials = "R";
if (isset($_SESSION["resident_name"])) {
    $parts = preg_split('/\s+/', trim($_SESSION["resident_name"]));
    $resident_initials = strtoupper(substr($parts[0], 0, 1) . (count($parts) > 1 ? substr(end($parts), 0, 1) : ""));
}
?>
<div class="topbar">
    <div class="topbar-right">
        <button type="button" class="theme-switch" onclick="toggleTheme()" aria-label="Toggle dark mode" title="Toggle dark mode">
            <span class="theme-switch-icon sun">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
            </span>
            <span class="theme-switch-thumb"></span>
            <span class="theme-switch-icon moon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            </span>
        </button>
        <div class="topbar-datetime" id="residentClock">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <div>
                <strong id="residentClockDate"><?= date("F j, Y") ?></strong>
                <small id="residentClockTime"><?= date("l, g:i A") ?></small>
            </div>
        </div>
        <?php if (isset($_SESSION["resident_name"])): ?>
        <div class="topbar-profile">
            <span class="avatar"><?= e($resident_initials) ?></span>
            <div>
                <strong><?= e($_SESSION["resident_name"]) ?></strong>
                <small>Resident</small>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
