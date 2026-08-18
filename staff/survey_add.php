<?php
require_once __DIR__ . "/../includes/functions.php";
require_staff_login();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $start_date = $_POST["start_date"];
    $end_date = $_POST["end_date"];
    $questions_text = $_POST["question_text"] ?? [];
    $questions_type = $_POST["question_type"] ?? [];
    $questions_required = $_POST["question_required"] ?? [];
    $choices = $_POST["choices"] ?? [];

    if ($title === "" || $start_date === "" || $end_date === "" || count($questions_text) === 0) {
        $error = "Please fill in the survey title, dates, and at least one question.";
    } elseif (!is_valid_start_date($start_date)) {
        $error = "Start date cannot be earlier than today.";
    } elseif ($end_date < $start_date) {
        $error = "End date cannot be earlier than the start date.";
    } else {
        $conn->begin_transaction();
        try {
            $staff_id = $_SESSION["staff_id"];
            $stmt = $conn->prepare("INSERT INTO surveys (title, description, created_by, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, 'active')");
            $stmt->bind_param("ssiss", $title, $description, $staff_id, $start_date, $end_date);
            $stmt->execute();
            $survey_id = $conn->insert_id;

            $q_stmt = $conn->prepare("INSERT INTO survey_questions (survey_id, question_text, question_type, is_required, question_order) VALUES (?, ?, ?, ?, ?)");
            $c_stmt = $conn->prepare("INSERT INTO survey_choices (question_id, choice_text, choice_order) VALUES (?, ?, ?)");

            foreach ($questions_text as $index => $qtext) {
                $qtext = trim($qtext);
                if ($qtext === "") continue;
                $qtype = $questions_type[$index];
                $required = isset($questions_required[$index]) ? 1 : 0;
                $order = $index + 1;

                $q_stmt->bind_param("issii", $survey_id, $qtext, $qtype, $required, $order);
                $q_stmt->execute();
                $question_id = $conn->insert_id;

                if ($qtype === "yes_no") {
                    foreach (["Yes", "No"] as $i => $label) {
                        $order_c = $i + 1;
                        $c_stmt->bind_param("isi", $question_id, $label, $order_c);
                        $c_stmt->execute();
                    }
                } elseif ($qtype === "rating") {
                    $labels = ["1 - Very Dissatisfied", "2 - Dissatisfied", "3 - Neutral", "4 - Satisfied", "5 - Very Satisfied"];
                    foreach ($labels as $i => $label) {
                        $order_c = $i + 1;
                        $c_stmt->bind_param("isi", $question_id, $label, $order_c);
                        $c_stmt->execute();
                    }
                } elseif ($qtype === "multiple_choice" && isset($choices[$index])) {
                    $order_c = 0;
                    foreach ($choices[$index] as $choice_text) {
                        $choice_text = trim($choice_text);
                        if ($choice_text === "") continue;
                        $order_c++;
                        $c_stmt->bind_param("isi", $question_id, $choice_text, $order_c);
                        $c_stmt->execute();
                    }
                }
            }

            $conn->commit();
            redirect("survey_management.php");
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Something went wrong while saving the survey. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Survey</title>
<link rel="stylesheet" href="../assets/css/style.css">
<script>(function(){var t=localStorage.getItem("theme");if(t==="dark")document.documentElement.setAttribute("data-theme","dark");})();</script>
<script>(function(){try{if(localStorage.getItem("sidebarCollapsed")==="true")document.documentElement.setAttribute("data-sidebar","collapsed");}catch(e){}})();</script>
</head>
<body>
<?php include __DIR__ . "/../includes/staff_nav.php"; ?>
<div class="container">
<?php include __DIR__ . "/../includes/staff_topbar.php"; ?>
    <div class="card">
        <h2>Create New Survey</h2>
        <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>

        <form method="POST">
            <label>Survey Title</label>
            <input type="text" name="title" required value="<?= isset($title) ? e($title) : "" ?>">

            <label>Description</label>
            <textarea name="description"><?= isset($description) ? e($description) : "" ?></textarea>

            <label>Start Date</label>
            <input type="date" name="start_date" id="start_date" required min="<?= date("Y-m-d") ?>"
                   value="<?= isset($start_date) ? e($start_date) : "" ?>">

            <label>End Date</label>
            <input type="date" name="end_date" id="end_date" required min="<?= date("Y-m-d") ?>"
                   value="<?= isset($end_date) ? e($end_date) : "" ?>">

            <hr style="margin:20px 0;">
            <h3>Questions</h3>
            <div id="questions-wrapper"></div>

            <button type="button" class="btn btn-secondary" onclick="addQuestion()">+ Add Question</button>
            <br>
            <button type="submit">Save Survey</button>
            <button type="button" class="btn btn-secondary" onclick="openConfirmModal({url: 'survey_management.php', title: 'Discard this new survey?', message: 'Anything you entered on this page will be lost.', confirmLabel: 'Discard', danger: true})">Cancel</button>

        </form>
    </div>
</div>

<template id="question-template">
    <div class="card question-block">
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

function addQuestion() {
    const template = document.getElementById("question-template");
    const clone = template.content.cloneNode(true);
    const block = clone.querySelector(".question-block");
    const index = questionIndex++;

    block.querySelector(".q-text-input").name = "question_text[" + index + "]";
    block.querySelector(".q-type-select").name = "question_type[" + index + "]";
    block.querySelector(".q-required-check").name = "question_required[" + index + "]";

    const choiceInputs = block.querySelectorAll(".choice-input");
    choiceInputs.forEach(input => input.name = "choices[" + index + "][]");

    const typeSelect = block.querySelector(".q-type-select");
    const choicesWrapper = block.querySelector(".q-choices-wrapper");
    typeSelect.addEventListener("change", () => {
        choicesWrapper.style.display = typeSelect.value === "multiple_choice" ? "block" : "none";
    });
    choicesWrapper.style.display = "block";

    block.querySelector(".add-choice-btn").addEventListener("click", () => {
        const container = block.querySelector(".choice-inputs");
        const count = container.querySelectorAll(".choice-input").length;
        const input = document.createElement("input");
        input.type = "text";
        input.className = "choice-input";
        input.style.marginTop = "6px";
        input.placeholder = "Choice " + (count + 1);
        input.name = "choices[" + index + "][]";
        container.appendChild(input);
    });

    block.querySelector(".remove-question-btn").addEventListener("click", () => {
        block.remove();
    });

    document.getElementById("questions-wrapper").appendChild(clone);
}

// start with one question visible
addQuestion();

// Confirm before actually creating the survey
const surveyForm = document.querySelector("form");
surveyForm.addEventListener("submit", function (e) {
    e.preventDefault();
        openConfirmModal({
        title: "Create this survey?",
        message: "This will publish the survey with the questions you've set up below.",
        confirmLabel: "Create Survey",
        danger: false,
        confirmClass: "",
        onConfirm: () => surveyForm.submit()
    });
});

// keep End Date from allowing a date earlier than the chosen Start Date
const startDateInput = document.getElementById("start_date");
const endDateInput = document.getElementById("end_date");
startDateInput.addEventListener("change", () => {
    endDateInput.min = startDateInput.value;
    if (endDateInput.value && endDateInput.value < startDateInput.value) {
        endDateInput.value = startDateInput.value;
    }
});
</script>
</body>
</html>