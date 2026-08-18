<?php
require_once __DIR__ . "/../includes/functions.php";
require_resident_login();

$resident_id = $_SESSION["resident_id"];

// active surveys within date range, with a flag for whether this resident already answered
$sql = "SELECT s.survey_id, s.title, s.description, s.end_date,
        (SELECT COUNT(*) FROM responses r WHERE r.survey_id = s.survey_id AND r.resident_id = ?) AS already_answered
        FROM surveys s
        WHERE s.status = 'active' AND CURDATE() BETWEEN s.start_date AND s.end_date
        ORDER BY s.start_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $resident_id);
$stmt->execute();
$surveys = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ---------------------------------------------------------
   Quick snapshot stats for this resident
--------------------------------------------------------- */
$available_count = count($surveys);
$answered_now = count(array_filter($surveys, fn($s) => $s["already_answered"] > 0));
$pending_count = $available_count - $answered_now;

$lt_stmt = $conn->prepare("SELECT COUNT(*) AS c FROM responses WHERE resident_id = ?");
$lt_stmt->bind_param("i", $resident_id);
$lt_stmt->execute();
$lifetime_answered = (int)$lt_stmt->get_result()->fetch_assoc()["c"];

$total_ever_stmt = $conn->query("SELECT COUNT(*) AS c FROM surveys");
$total_ever = (int)$total_ever_stmt->fetch_assoc()["c"];
$completion_rate = $total_ever > 0 ? round(($lifetime_answered / $total_ever) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Resident Dashboard</title>
<link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../assets/css/style.css") ?>">
<script>(function(){var t=localStorage.getItem("theme");if(t==="dark")document.documentElement.setAttribute("data-theme","dark");})();</script>
</head>
<body>
<?php include __DIR__ . "/../includes/resident_nav.php"; ?>
<div class="container">
    <?php include __DIR__ . "/../includes/resident_topbar.php"; ?>

    <div class="welcome-header">
        <div>
            <h1>Welcome back, <?= e($_SESSION["resident_name"]) ?> <span class="wave">👋</span></h1>
            <p>Here's what's happening with your barangay health surveys.</p>
        </div>
    </div>

    <div class="stat-cards-grid">
        <div class="stat-card">
            <div class="stat-card-top">
                <span class="stat-ic teal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>
                <span class="stat-label">Available Surveys</span>
            </div>
            <div class="stat-number"><?= number_format($available_count) ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-card-top">
                <span class="stat-ic green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg></span>
                <span class="stat-label">Answered</span>
            </div>
            <div class="stat-number"><?= number_format($answered_now) ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-card-top">
                <span class="stat-ic amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
                <span class="stat-label">Pending</span>
            </div>
            <div class="stat-number"><?= number_format($pending_count) ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-card-top">
                <span class="stat-ic teal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>
                <span class="stat-label">Lifetime Participation</span>
            </div>
            <div class="stat-number"><?= $completion_rate ?>%</div>
        </div>
    </div>

    <div class="card">
        <h2>Available Surveys</h2>
        <?php if (count($surveys) === 0): ?>
            <p class="muted-note">There are no active surveys right now.</p>
        <?php endif; ?>
        <?php foreach ($surveys as $survey): ?>
            <div class="survey-list-item">
                <div>
                    <strong><?= e($survey["title"]) ?></strong><br>
                    <small><?= e($survey["description"]) ?></small><br>
                    <small>Open until <?= e($survey["end_date"]) ?></small>
                </div>
                <div>
                    <?php if ($survey["already_answered"] > 0): ?>
                        <span class="success" style="padding:6px 10px;">Answered</span>
                    <?php else: ?>
                        <a class="btn" href="survey_form.php?survey_id=<?= (int)$survey["survey_id"] ?>">Answer Survey</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script src="../assets/js/script.js"></script>
</body>
</html>
