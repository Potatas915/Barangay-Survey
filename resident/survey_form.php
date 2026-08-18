<?php
require_once __DIR__ . "/../includes/functions.php";
require_resident_login();

$resident_id = $_SESSION["resident_id"];
$survey_id = isset($_GET["survey_id"]) ? (int)$_GET["survey_id"] : 0;

// prevent answering the same survey twice
$check = $conn->prepare("SELECT response_id FROM responses WHERE survey_id = ? AND resident_id = ?");
$check->bind_param("ii", $survey_id, $resident_id);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    redirect("dashboard.php");
}

$survey_stmt = $conn->prepare("SELECT title, description FROM surveys WHERE survey_id = ? AND status = 'active'");
$survey_stmt->bind_param("i", $survey_id);
$survey_stmt->execute();
$survey = $survey_stmt->get_result()->fetch_assoc();
if (!$survey) {
    redirect("dashboard.php");
}

$questions_stmt = $conn->prepare("SELECT question_id, question_text, question_type, is_required FROM survey_questions WHERE survey_id = ? ORDER BY question_order");
$questions_stmt->bind_param("i", $survey_id);
$questions_stmt->execute();
$questions = $questions_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$choices_by_question = [];
$choices_stmt = $conn->prepare("SELECT choice_id, question_id, choice_text FROM survey_choices WHERE question_id = ? ORDER BY choice_order");

foreach ($questions as $q) {
    $choices_stmt->bind_param("i", $q["question_id"]);
    $choices_stmt->execute();
    $choices_by_question[$q["question_id"]] = $choices_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// handle submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $conn->begin_transaction();
    try {
        $resident_name = $_SESSION["resident_name"];
        $resp_stmt = $conn->prepare("INSERT INTO responses (survey_id, resident_id, resident_name) VALUES (?, ?, ?)");
        $resp_stmt->bind_param("iis", $survey_id, $resident_id, $resident_name);
        $resp_stmt->execute();
        $response_id = $conn->insert_id;

        $result_stmt = $conn->prepare("INSERT INTO survey_results (response_id, question_id, choice_id, answer_text) VALUES (?, ?, ?, ?)");

        foreach ($questions as $q) {
            $qid = $q["question_id"];
            $field = "q_" . $qid;

            if ($q["question_type"] === "short_answer") {
                $answer_text = isset($_POST[$field]) ? trim($_POST[$field]) : null;
                if ($answer_text === "") $answer_text = null;
                $choice_id = null;
                $result_stmt->bind_param("iiis", $response_id, $qid, $choice_id, $answer_text);
                $result_stmt->execute();
            } else {
                // multiple_choice, yes_no, rating all use a choice_id
                if (isset($_POST[$field]) && $_POST[$field] !== "") {
                    $choice_id = (int)$_POST[$field];
                    $answer_text = null;
                    $result_stmt->bind_param("iiis", $response_id, $qid, $choice_id, $answer_text);
                    $result_stmt->execute();
                } elseif ($q["is_required"] == 1) {
                    throw new Exception("Missing required answer.");
                }
            }
        }

        $conn->commit();
        redirect("confirmation.php");
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Please answer all required questions.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= e($survey["title"]) ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
<script>(function(){var t=localStorage.getItem("theme");if(t==="dark")document.documentElement.setAttribute("data-theme","dark");})();</script>
<script>(function(){try{if(localStorage.getItem("sidebarCollapsed")==="true")document.documentElement.setAttribute("data-sidebar","collapsed");}catch(e){}})();</script>
</head>
<body>
<?php include __DIR__ . "/../includes/resident_nav.php"; ?>
<div class="container">
    <?php include __DIR__ . "/../includes/resident_topbar.php"; ?>

    <div class="welcome-header">
        <div>
            <h1><?= e($survey["title"]) ?></h1>
            <p><a href="dashboard.php" onclick="event.preventDefault(); openConfirmModal({url: 'dashboard.php', title: 'Leave this survey?', message: 'Your answers on this page have not been submitted and will be lost.', confirmLabel: 'Leave', danger: true})">&larr; Back to Dashboard</a></p>
        </div>
    </div>

    <div class="card">
        <p><?= e($survey["description"]) ?></p>
        <?php if (isset($error)): ?><div class="error"><?= e($error) ?></div><?php endif; ?>

        <form method="POST" onsubmit="return validateSurveyForm()">
            <?php foreach ($questions as $q): ?>
                <div class="card" data-required="<?= $q["is_required"] ?>">
    <label style="font-weight:bold;">
                    <label style="font-weight:bold;">
                        <?= e($q["question_text"]) ?>
                        <?php if ($q["is_required"]): ?><span style="color:#b13f3f;">*</span><?php endif; ?>
                    </label>

                    <?php if ($q["question_type"] === "short_answer"): ?>
                        <textarea name="q_<?= $q["question_id"] ?>"></textarea>

                    <?php elseif ($q["question_type"] === "yes_no"): ?>
                        <div class="choice-options">
                            <?php foreach ($choices_by_question[$q["question_id"]] as $c): ?>
                                <label>
                                    <input type="radio" name="q_<?= $q["question_id"] ?>" value="<?= $c["choice_id"] ?>">
                                    <?= e($c["choice_text"]) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                    <?php elseif ($q["question_type"] === "rating"): ?>
                        <div class="rating-options">
                            <?php foreach ($choices_by_question[$q["question_id"]] as $c): ?>
                                <label>
                                    <input type="radio" name="q_<?= $q["question_id"] ?>" value="<?= $c["choice_id"] ?>">
                                    <?= e($c["choice_text"]) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                    <?php else: // multiple_choice ?>
                        <div class="choice-options">
                            <?php foreach ($choices_by_question[$q["question_id"]] as $c): ?>
                                <label>
                                    <input type="radio" name="q_<?= $q["question_id"] ?>" value="<?= $c["choice_id"] ?>">
                                    <?= e($c["choice_text"]) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <button type="submit">Submit Survey</button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="confirmModal">
    <div class="modal-box modal-sm">
        <div class="modal-icon" id="confirmModalIcon"></div>
        <h3 id="confirmModalTitle">Are you sure?</h3>
        <p class="modal-message" id="confirmModalMessage"></p>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmModalConfirmBtn" onclick="proceedConfirmModal()">Confirm</button>
        </div>
    </div>
</div>

<script src="../assets/js/script.js"></script>
</body>
</html>