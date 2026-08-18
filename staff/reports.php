<?php
require_once __DIR__ . "/../includes/functions.php";
require_staff_login();

$reports = $conn->query("
    SELECT s.survey_id, s.title, s.start_date, s.end_date, s.status,
    (SELECT COUNT(*) FROM responses r WHERE r.survey_id = s.survey_id) AS response_count,
    (SELECT COUNT(*) FROM survey_questions q WHERE q.survey_id = s.survey_id) AS question_count
    FROM surveys s ORDER BY s.created_at DESC
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
</div>
<script src="../assets/js/script.js"></script>
<?php if (isset($_GET["print"]) && $_GET["print"] === "1"): ?>
<script>window.addEventListener("load", function () { window.print(); });</script>
<?php endif; ?>
</body>
</html>