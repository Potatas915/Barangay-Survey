<?php
require_once __DIR__ . "/../includes/functions.php";
require_staff_login();

$surveys = $conn->query("SELECT survey_id, title FROM surveys ORDER BY created_at DESC");
$survey_list = $surveys->fetch_all(MYSQLI_ASSOC);

$selected_id = isset($_GET["survey_id"]) ? (int)$_GET["survey_id"] : (count($survey_list) ? $survey_list[0]["survey_id"] : 0);
$selected_title = "Survey Results";
foreach ($survey_list as $survey) {
    if ((int)$survey["survey_id"] === $selected_id) {
        $selected_title = $survey["title"];
        break;
    }
}

$questions = [];
$response_count = 0;
$respondents = [];

if ($selected_id) {
    $rc_stmt = $conn->prepare("SELECT COUNT(*) AS c FROM responses WHERE survey_id = ?");
    $rc_stmt->bind_param("i", $selected_id);
    $rc_stmt->execute();
    $response_count = $rc_stmt->get_result()->fetch_assoc()["c"];

    $q_stmt = $conn->prepare("SELECT question_id, question_text, question_type FROM survey_questions WHERE survey_id = ? ORDER BY question_order");
    $q_stmt->bind_param("i", $selected_id);
    $q_stmt->execute();
    $questions = $q_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($questions as &$q) {
        if ($q["question_type"] === "short_answer") {
            $a_stmt = $conn->prepare("
                SELECT sr.answer_text, r.resident_name
                FROM survey_results sr
                JOIN responses r ON r.response_id = sr.response_id
                WHERE sr.question_id = ? AND sr.answer_text IS NOT NULL
            ");
            $a_stmt->bind_param("i", $q["question_id"]);
            $a_stmt->execute();
            $q["answers"] = $a_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $c_stmt = $conn->prepare("
                SELECT sc.choice_text, COUNT(sr.result_id) AS votes
                FROM survey_choices sc
                LEFT JOIN survey_results sr ON sr.choice_id = sc.choice_id
                WHERE sc.question_id = ?
                GROUP BY sc.choice_id
                ORDER BY sc.choice_order
            ");
            $c_stmt->bind_param("i", $q["question_id"]);
            $c_stmt->execute();
            $q["choice_stats"] = $c_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $q["total_votes"] = array_sum(array_column($q["choice_stats"], "votes"));
        }
    }
    unset($q);

    $resp_stmt = $conn->prepare("
        SELECT r.resident_id, r.resident_name, res.resident_number, res.email, r.submitted_at
        FROM responses r
        JOIN residents res ON res.resident_id = r.resident_id
        WHERE r.survey_id = ?
        ORDER BY r.submitted_at DESC
    ");
    $resp_stmt->bind_param("i", $selected_id);
    $resp_stmt->execute();
    $respondents = $resp_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Survey Results</title>
<link rel="stylesheet" href="../assets/css/style.css">
<script>(function(){var t=localStorage.getItem("theme");if(t==="dark")document.documentElement.setAttribute("data-theme","dark");})();</script>
<script>(function(){try{if(localStorage.getItem("sidebarCollapsed")==="true")document.documentElement.setAttribute("data-sidebar","collapsed");}catch(e){}})();</script>
</head>
<body>
<?php $no_print_nav = true; include __DIR__ . "/../includes/staff_nav.php"; ?>
<div class="container">
<?php include __DIR__ . "/../includes/staff_topbar.php"; ?>
    <div class="card no-print">
        <div class="results-selector-row">
            <div class="selected-survey-title-wrap">
                <span class="selected-survey-label">Survey</span>
                <h2 class="selected-survey-title"><?= e($selected_title) ?></h2>
            </div>

            <form method="GET" class="survey-selector-form">
                <label for="survey_id">Select Survey</label>
                <select id="survey_id" name="survey_id" onchange="this.form.submit()">
                    <?php foreach ($survey_list as $s): ?>
                        <option value="<?= $s["survey_id"] ?>" <?= $s["survey_id"] == $selected_id ? "selected" : "" ?>>
                            <?= e($s["title"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <?php if ($selected_id): ?>
                <button type="button" class="btn btn-secondary respondents-btn" onclick="document.getElementById('respondentsModal').classList.add('open')">
                    View Respondents
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($selected_id): ?>
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2>Results (<?= $response_count ?> responses)</h2>
            <button class="no-print" onclick="window.print()">Print Report</button>
        </div>

        <?php if ($response_count === 0): ?>
            <p class="muted-note" style="margin-top:6px;">This survey hasn't received any responses yet. Once residents start answering, results for each question will appear here automatically.</p>
        <?php else: ?>
        <?php foreach ($questions as $q): ?>
            <div class="card">
                <strong><?= e($q["question_text"]) ?></strong>

                <?php if ($q["question_type"] === "short_answer"): ?>
                    <?php if (count($q["answers"]) === 0): ?>
                        <p style="font-size:13px; color:#667;">No answers yet.</p>
                    <?php else: ?>
                        <ul style="margin-top:10px; padding-left:18px; font-size:14px;">
                            <?php foreach ($q["answers"] as $a): ?>
                                <li><?= e($a["answer_text"]) ?> <span style="color:#667; font-size:12px;">- <?= e($a["resident_name"]) ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if ($q["total_votes"] === 0): ?>
                        <p style="font-size:13px; color:#667;">No responses yet for this question.</p>
                    <?php else: ?>
                    <div class="bar-chart">
                        <?php foreach ($q["choice_stats"] as $c):
                            $pct = $q["total_votes"] > 0 ? round(($c["votes"] / $q["total_votes"]) * 100) : 0;
                        ?>
                        <div class="bar-row">
                            <div style="width:160px;"><?= e($c["choice_text"]) ?></div>
                            <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%;"></div></div>
                            <div style="width:70px;"><?= $c["votes"] ?> (<?= $pct ?>%)</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php else: ?>
        <div class="card"><p>No surveys yet.</p></div>
    <?php endif; ?>
</div>

<?php if ($selected_id): ?>
<div class="modal-overlay no-print" id="respondentsModal">
    <div class="modal-box">
        <span class="modal-close" onclick="document.getElementById('respondentsModal').classList.remove('open')">&times;</span>
        <h3>Respondents (<?= count($respondents) ?>)</h3>
        <?php if (count($respondents) === 0): ?>
            <p style="font-size:13px; color:#667;">No one has answered this survey yet.</p>
        <?php else: ?>
            <table>
                <tr><th>Name</th><th>Resident ID</th><th>Submitted</th></tr>
                <?php foreach ($respondents as $r): ?>
                <tr>
                    <td><?= e($r["resident_name"]) ?></td>
                    <td><?= e($r["resident_number"]) ?></td>
                    <td><?= e($r["submitted_at"]) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<script src="../assets/js/script.js"></script>
</body>
</html>