<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Barangay Health Center Survey System</title>
<link rel="stylesheet" href="assets/css/welcome.css">
</head>
<body class="welcome-page">

<div class="welcome-wrapper">

    <!-- ================= LEFT: GRADIENT HERO ================= -->
    <div class="welcome-hero">

        <div class="welcome-decoration">
            <span></span>
            <span></span>
            <span></span>
            <span></span>
        </div>

        <div class="hero-badge">
            <span class="badge-ic">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11z"/><path d="M12 8v5"/><path d="M9.5 10.5h5"/></svg>
            </span>
            Barangay Health Center
        </div>

        <div class="hero-content">
            <span class="hero-eyebrow">Survey Management System</span>
            <h1>Your voice helps<br>build a healthier barangay.</h1>
            <p>Take part in health surveys and track community programs, or manage them from the staff side — all in one place.</p>
        </div>

        <div class="hero-features">
            <div class="hero-feature">
                <span class="feat-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                Quick, guided surveys
            </div>
            <div class="hero-feature">
                <span class="feat-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                Secure resident data
            </div>
            <div class="hero-feature">
                <span class="feat-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                Real-time reports for staff
            </div>
        </div>

        <div class="hero-footer">&copy; <?= date("Y") ?> Barangay Health Center</div>

    </div>

    <!-- ================= RIGHT: PORTAL PICKER ================= -->
    <div class="welcome-panel">
        <div class="welcome-panel-inner">

            <h2>Get Started</h2>
            <p class="welcome-subtitle">Choose how you'd like to continue.</p>

            <div class="portal-options">

                <a href="resident/login.php" class="portal-card">
                    <span class="portal-ic">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <span class="portal-text">
                        <strong>Resident Login</strong>
                        <span>Take surveys &amp; view programs</span>
                    </span>
                    <span class="portal-arrow">&rarr;</span>
                </a>

                <a href="staff/login.php" class="portal-card portal-card-alt">
                    <span class="portal-ic">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="14" rx="2"/><path d="M8 21h8M12 18v3"/></svg>
                    </span>
                    <span class="portal-text">
                        <strong>Staff Login</strong>
                        <span>Manage surveys &amp; reports</span>
                    </span>
                    <span class="portal-arrow">&rarr;</span>
                </a>

            </div>

            <div class="welcome-panel-footer">
                Barangay Health Center Survey Management System
            </div>

        </div>
    </div>

</div>

</body>
</html>