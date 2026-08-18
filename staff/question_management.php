<?php
require_once __DIR__ . "/../includes/functions.php";
require_staff_login();

$survey_id = isset($_GET["survey_id"]) ? (int)$_GET["survey_id"] : ((int)($_POST["survey_id"] ?? 0));

// A survey's questions can only be changed while it has zero responses,
// so past results always stay consistent with what was actually asked.
$check_responses = $conn->prepare("SELECT COUNT(*) as count FROM responses WHERE survey_id = ?");
$check_responses->bind_param("i", $survey_id);
$check_responses->execute();
$response_data = $check_responses->get_result()->fetch_assoc();
$is_locked = $response_data["count"] > 0;

if ($is_locked) {
    die("Error: You cannot edit questions for a survey that already has responses.");
}

$survey_stmt = $conn->prepare("SELECT title FROM surveys WHERE survey_id = ?");
$survey_stmt->bind_param("i", $survey_id);
$survey_stmt->execute();
$survey = $survey_stmt->get_result()->fetch_assoc();
if (!$survey) redirect("survey_management.php");

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $question_ids = $_POST["question_id"] ?? [];
    $questions_text = $_POST["question_text"] ?? [];
    $questions_type = $_POST["question_type"] ?? [];
    $questions_required = $_POST["question_required"] ?? [];
    $choices = $_POST["choices"] ?? [];

    $valid_types = ["multiple_choice", "yes_no", "rating", "short_answer"];

    if (count($questions_text) === 0) {
        $error = "Please add at least one question.";
    } else {
        $conn->begin_transaction();
        try {
            $q_insert = $conn->prepare("INSERT INTO survey_questions (survey_id, question_text, question_type, is_required, question_order) VALUES (?, ?, ?, ?, ?)");
            $q_update = $conn->prepare("UPDATE survey_questions SET question_text = ?, question_type = ?, is_required = ?, question_order = ? WHERE question_id = ? AND survey_id = ?");
            $c_delete = $conn->prepare("DELETE FROM survey_choices WHERE question_id = ?");
            $c_insert = $conn->prepare("INSERT INTO survey_choices (question_id, choice_text, choice_order) VALUES (?, ?, ?)");

            $keep_ids = [];
            $order = 0;

            foreach ($questions_text as $index => $qtext) {
                $qtext = trim($qtext);
                if ($qtext === "") continue;

                $qtype = $questions_type[$index] ?? "";
                if (!in_array($qtype, $valid_types, true)) continue;

                $required = isset($questions_required[$index]) ? 1 : 0;
                $order++;
                $qid = isset($question_ids[$index]) ? (int)$question_ids[$index] : 0;

                if ($qid > 0) {
                    // Editing an existing question
                    $q_update->bind_param("ssiiii", $qtext, $qtype, $required, $order, $qid, $survey_id);
                    $q_update->execute();
                    $question_id = $qid;

                    $c_delete->bind_param("i", $question_id);
                    $c_delete->execute();
                } else {
                    // Adding a brand new question
                    $q_insert->bind_param("issii", $survey_id, $qtext, $qtype, $required, $order);
                    $q_insert->execute();
                    $question_id = $conn->insert_id;
                }

                $keep_ids[] = $question_id;

                if ($qtype === "yes_no") {
                    foreach (["Yes", "No"] as $i => $label) {
                        $order_c = $i + 1;
                        $c_insert->bind_param("isi", $question_id, $label, $order_c);
                        $c_insert->execute();
                    }
                } elseif ($qtype === "rating") {
                    $labels = ["1 - Very Dissatisfied", "2 - Dissatisfied", "3 - Neutral", "4 - Satisfied", "5 - Very Satisfied"];
                    foreach ($labels as $i => $label) {
                        $order_c = $i + 1;
                        $c_insert->bind_param("isi", $question_id, $label, $order_c);
                        $c_insert->execute();
                    }
                } elseif ($qtype === "multiple_choice" && isset($choices[$index])) {
                    $order_c = 0;
                    foreach ($choices[$index] as $choice_text) {
                        $choice_text = trim($choice_text);
                        if ($choice_text === "") continue;
                        $order_c++;
                        $c_insert->bind_param("isi", $question_id, $choice_text, $order_c);
                        $c_insert->execute();
                    }
                }
            }

            if (count($keep_ids) === 0) {
                $error = "Please add at least one question.";
                $conn->rollback();
            } else {
                // Any existing question that was removed in the form (i.e. its
                // question_id was not resubmitted) gets deleted here. This is
                // only reachable while the survey has zero responses.
                $placeholders = implode(",", array_fill(0, count($keep_ids), "?"));
                $types = str_repeat("i", count($keep_ids));
                $del_stmt = $conn->prepare("DELETE FROM survey_questions WHERE survey_id = ? AND question_id NOT IN ($placeholders)");
                $del_stmt->bind_param("i" . $types, $survey_id, ...$keep_ids);
                $del_stmt->execute();

                $conn->commit();
                redirect("question_management.php?survey_id=" . $survey_id . "&saved=1");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Something went wrong while saving the questions. Please try again.";
        }
    }
}

$q_stmt = $conn->prepare("SELECT question_id, question_text, question_type, is_required FROM survey_questions WHERE survey_id = ? ORDER BY question_order");
$q_stmt->bind_param("i", $survey_id);
$q_stmt->execute();
$questions_result = $q_stmt->get_result();

$questions = [];
while ($q = $questions_result->fetch_assoc()) {
    $q["choices"] = [];
    if ($q["question_type"] === "multiple_choice") {
        $c_stmt = $conn->prepare("SELECT choice_text FROM survey_choices WHERE question_id = ? ORDER BY choice_order");
        $c_stmt->bind_param("i", $q["question_id"]);
        $c_stmt->execute();
        $c_res = $c_stmt->get_result();
        while ($c = $c_res->fetch_assoc()) {
            $q["choices"][] = $c["choice_text"];
        }
    }
    $questions[] = $q;
}

$type_labels = [
    "multiple_choice" => "Multiple Choice",
    "yes_no" => "Yes / No",
    "rating" => "Rating Scale",
    "short_answer" => "Short Answer"
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Question Management</title>
<link rel="stylesheet" href="../assets/css/style.css">
<script>(function(){var t=localStorage.getItem("theme");if(t==="dark")document.documentElement.setAttribute("data-theme","dark");})();</script>
<script>(function(){try{if(localStorage.getItem("sidebarCollapsed")==="true")document.documentElement.setAttribute("data-sidebar","collapsed");}catch(e){}})();</script>
</head>
<body>
<?php include __DIR__ . "/../includes/staff_nav.php"; ?>
<div class="container">
<?php include __DIR__ . "/../includes/staff_topbar.php"; ?>
    <div class="card">
        <h2>Questions for: <?= e($survey["title"]) ?></h2>

        <?php if (isset($_GET["saved"])): ?>
            <div class="success" style="margin-bottom:16px; color:#1fae82;">Questions saved successfully.</div>
        <?php endif; ?>
        <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>

        <p style="margin-bottom:16px; font-size:13px; color:#667;">This survey has no responses yet, so its questions are unlocked: edit the text, type, or choices below, add new questions, or remove ones you no longer need. Once someone submits a response, questions will lock automatically to keep results consistent.</p>

        <form method="POST" action="question_management.php?survey_id=<?= $survey_id ?>">
            <input type="hidden" name="survey_id" value="<?= $survey_id ?>">
            <div id="questions-wrapper"></div>
            <button type="button" class="btn btn-secondary" onclick="addQuestion()">+ Add Question</button>
            <br><br>
            <button type="submit">Save Questions</button>
            <button type="button" class="btn btn-secondary" onclick="openConfirmModal({url: 'survey_management.php', title: 'Discard these changes?', message: 'Any edits you made to these questions will be lost.', confirmLabel: 'Discard', danger: true})">Cancel</button>
        </form>
    </div>
</div>

<template id="question-template">
        <div class="card question-block">
        <input type="hidden" class="q-id-input" value="">

        <label>Question Text</label>
        <input type="text" class="q-text-input" required>

        <label>Question Type</label>
        <select class="q-type-select">
            <option value="multiple_choice">Multiple Choice</option>
            <option value="yes_no">Yes / No</option>
            <option value="rating">Rating Scale (1-5)</option>
            <option value="short_answer">Short Answer</option>
        </select>

        <label><input type="checkbox" class="q-required-check" style="width:auto;" checked> Required</label>

        <div class="q-choices-wrapper" style="margin-top:10px;">
            <label>Choices (multiple choice only)</label>
            <div class="choice-inputs">
                <input type="text" class="choice-input" placeholder="Choice 1">
                <input type="text" class="choice-input" style="margin-top:6px;" placeholder="Choice 2">
            </div>
            <button type="button" class="btn btn-secondary add-choice-btn" style="padding:6px 10px; font-size:12px;">+ Add Choice</button>
        </div>

        <button type="button" class="btn btn-danger remove-question-btn" style="padding:6px 10px; font-size:12px;">Remove Question</button>
    </div>
</template>

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
<script>
let questionIndex = 0;

function addQuestion(existing) {
    const template = document.getElementById("question-template");
    const clone = template.content.cloneNode(true);
    const block = clone.querySelector(".question-block");
    const index = questionIndex++;

    const idInput = block.querySelector(".q-id-input");
    idInput.name = "question_id[" + index + "]";

    const textInput = block.querySelector(".q-text-input");
    textInput.name = "question_text[" + index + "]";

    const typeSelect = block.querySelector(".q-type-select");
    typeSelect.name = "question_type[" + index + "]";

    const requiredCheck = block.querySelector(".q-required-check");
    requiredCheck.name = "question_required[" + index + "]";

    if (existing) {
        idInput.value = existing.id;
        textInput.value = existing.text;
        typeSelect.value = existing.type;
        requiredCheck.checked = !!existing.required;
    }

    const choicesWrapper = block.querySelector(".q-choices-wrapper");
    const choiceInputsContainer = block.querySelector(".choice-inputs");

    function nameChoiceInputs() {
        choiceInputsContainer.querySelectorAll(".choice-input").forEach(input => {
            input.name = "choices[" + index + "][]";
        });
    }

    if (existing && existing.type === "multiple_choice" && existing.choices && existing.choices.length > 0) {
        choiceInputsContainer.innerHTML = "";
        existing.choices.forEach((choiceText, i) => {
            const input = document.createElement("input");
            input.type = "text";
            input.className = "choice-input";
            if (i > 0) input.style.marginTop = "6px";
            input.placeholder = "Choice " + (i + 1);
            input.value = choiceText;
            choiceInputsContainer.appendChild(input);
        });
    }
    nameChoiceInputs();

    choicesWrapper.style.display = typeSelect.value === "multiple_choice" ? "block" : "none";
    typeSelect.addEventListener("change", () => {
        choicesWrapper.style.display = typeSelect.value === "multiple_choice" ? "block" : "none";
    });

    block.querySelector(".add-choice-btn").addEventListener("click", () => {
        const count = choiceInputsContainer.querySelectorAll(".choice-input").length;
        const input = document.createElement("input");
        input.type = "text";
        input.className = "choice-input";
        input.style.marginTop = "6px";
        input.placeholder = "Choice " + (count + 1);
        input.name = "choices[" + index + "][]";
        choiceInputsContainer.appendChild(input);
    });

    block.querySelector(".remove-question-btn").addEventListener("click", () => {
        block.remove();
    });

    document.getElementById("questions-wrapper").appendChild(clone);
}

// Pre-fill the form with the survey's current questions so they can be edited in place.
const existingQuestions = <?= json_encode(array_map(function ($q) {
    return [
        "id" => (int)$q["question_id"],
        "text" => $q["question_text"],
        "type" => $q["question_type"],
        "required" => (int)$q["is_required"],
        "choices" => $q["choices"]
    ];
}, $questions), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

if (existingQuestions.length > 0) {
    existingQuestions.forEach(q => addQuestion(q));
} else {
    addQuestion();
}

// Confirm before actually saving the questions
const questionsForm = document.querySelector("form");
questionsForm.addEventListener("submit", function (e) {
    e.preventDefault();
        openConfirmModal({
        title: "Save these questions?",
        message: "This will update the questions residents see for this survey.",
        confirmLabel: "Save Questions",
        danger: false,
        confirmClass: "",
        onConfirm: () => questionsForm.submit()
    });
});
</script>
</body>
</html>