<?php
require_once __DIR__ . "/../includes/functions.php";
require_staff_login();

$reports = $conn->query("
    SELECT s.survey_id, s.title, s.start_date, s.end_date, s.status,
    (SELECT COUNT(*) FROM responses r WHERE r.survey_id = s.survey_id) AS response_count,
    (SELECT COUNT(*) FROM survey_questions q WHERE q.survey_id = s.survey_id) AS question_count
    FROM surveys s ORDER BY s.created_at DESC
");

// Resident report summary (Act 5 - Set A)
$total_residents = $conn->query("SELECT COUNT(*) AS c FROM residents")->fetch_assoc()["c"];
$updated_residents = $conn->query("SELECT COUNT(*) AS c FROM residents WHERE updated_at > created_at")->fetch_assoc()["c"];
$with_photo = $conn->query("SELECT COUNT(*) AS c FROM residents WHERE photo IS NOT NULL AND photo != ''")->fetch_assoc()["c"];
$civil_status_breakdown = $conn->query("
    SELECT COALESCE(NULLIF(civil_status,''), 'Not specified') AS status_label, COUNT(*) AS c
    FROM residents GROUP BY status_label ORDER BY c DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reports</title>
<link rel="stylesheet" href="../assets/css/style.css">
<script>(function(){var t=localStorage.getItem("theme");if(t==="dark")document.documentElement.setAttribute("data-theme","dark");})();</script>
<script>(function(){try{if(localStorage.getItem("sidebarCollapsed")==="true")document.documentElement.setAttribute("data-sidebar","collapsed");}catch(e){}})();</script>
</head>
<body>
<?php $no_print_nav = true; include __DIR__ . "/../includes/staff_nav.php"; ?>
<div class="container">
<?php include __DIR__ . "/../includes/staff_topbar.php"; ?>
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2>Survey Summary Report</h2>
            <button class="no-print" onclick="window.print()">Print / Export as PDF</button>
        </div>
        <p style="font-size:13px; color:#667;">Use your browser's Print dialog and choose "Save as PDF" to export this report.</p>
        <div class="table-scroll">
        <table>
            <tr>
                <th>Survey Title</th>
                <th>Period</th>
                <th>Status</th>
                <th>Questions</th>
                <th>Responses</th>
                <th class="no-print">View Results</th>
            </tr>
            <?php while ($r = $reports->fetch_assoc()): ?>
            <tr>
                <td><?= e($r["title"]) ?></td>
                <td><?= e($r["start_date"]) ?> to <?= e($r["end_date"]) ?></td>
                <td><?= $r["status"] === "active" ? "Active" : "Inactive" ?></td>
                <td><?= (int)$r["question_count"] ?></td>
                <td><?= (int)$r["response_count"] ?></td>
                <td class="no-print"><a class="btn btn-reports"  href="results.php?survey_id=<?= $r["survey_id"] ?>">View</a></td>


            </tr>
            <?php endwhile; ?>
        </table>
        </div>
    </div>

    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2>Resident Report</h2>
            <div class="table-actions no-print">
                <a class="btn btn-sm btn-secondary" href="resident_export.php">Export CSV</a>
                <button class="btn btn-sm" onclick="window.print()">Print / Export as PDF</button>
            </div>
        </div>
        <p style="font-size:13px; color:#667;">Snapshot of resident records currently stored in the system.</p>
        <div class="print-info-grid" style="margin-bottom:14px;">
            <div class="info-row"><strong>Total Registered Residents</strong><span><?= (int)$total_residents ?></span></div>
            <div class="info-row"><strong>Records Updated At Least Once</strong><span><?= (int)$updated_residents ?></span></div>
            <div class="info-row"><strong>Residents With Photo On File</strong><span><?= (int)$with_photo ?></span></div>
        </div>
        <div class="table-scroll">
        <table>
            <tr><th>Civil Status</th><th>Residents</th></tr>
            <?php while ($cs = $civil_status_breakdown->fetch_assoc()): ?>
            <tr>
                <td><?= e($cs["status_label"]) ?></td>
                <td><?= (int)$cs["c"] ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        </div>
    </div>
</div>
<script src="../assets/js/script.js"></script>
<?php if (isset($_GET["print"]) && $_GET["print"] === "1"): ?>
<script>window.addEventListener("load", function () { window.print(); });</script>
<?php endif; ?>
</body>
</html>