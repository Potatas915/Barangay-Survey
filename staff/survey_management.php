<?php
require_once __DIR__ . "/../includes/functions.php";
require_staff_login();

// toggle active/inactive
if (isset($_GET["toggle"])) {
    $survey_id = (int)$_GET["toggle"];
    $conn->query("UPDATE surveys SET status = IF(status = 'active', 'inactive', 'active') WHERE survey_id = $survey_id");
    redirect("survey_management.php");
}

$surveys = $conn->query("SELECT s.survey_id, s.title, s.start_date, s.end_date, s.status,
    (SELECT COUNT(*) FROM responses r WHERE r.survey_id = s.survey_id) AS response_count
    FROM surveys s ORDER BY s.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Survey Management</title>
<link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../assets/css/style.css") ?>">
<script>(function(){var t=localStorage.getItem("theme");if(t==="dark")document.documentElement.setAttribute("data-theme","dark");})();</script>
</head>
<body>
<?php include __DIR__ . "/../includes/staff_nav.php"; ?>
<div class="container">
<?php include __DIR__ . "/../includes/staff_topbar.php"; ?>
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2>Survey Management</h2>
            <a class="btn" href="survey_add.php">+ Create New Survey</a>
        </div>
        <div class="table-scroll">
        <table>
            <tr>
                <th>Title</th>
                <th>Start</th>
                <th>End</th>
                <th>Status</th>
                <th>Responses</th>
                <th>Actions</th>
            </tr>
            <?php while ($s = $surveys->fetch_assoc()): ?>
            <tr>
                <td><?= e($s["title"]) ?></td>
                <td><?= e($s["start_date"]) ?></td>
                <td><?= e($s["end_date"]) ?></td>
                <td><?= $s["status"] === "active" ? "Active" : "Inactive" ?></td>
                <td><?= (int)$s["response_count"] ?></td>
                <td>
                    <div class="table-actions">
                        <a class="btn btn-sm btn-secondary" href="survey_edit.php?survey_id=<?= $s["survey_id"] ?>">Manage Survey</a>

                        <?php if ((int)$s["response_count"] === 0): ?>
                            <a class="btn btn-sm btn-secondary" href="question_management.php?survey_id=<?= $s["survey_id"] ?>">Manage Questions</a>
                        <?php else: ?>
                            <span class="btn btn-sm btn-disabled" title="Cannot edit questions after receiving responses">Manage Questions</span>
                        <?php endif; ?>

                        <a class="btn btn-sm btn-secondary" href="results.php?survey_id=<?= $s["survey_id"] ?>">Show    Results</a>

                        <?php
                            $toggle_url = "survey_management.php?toggle=" . $s["survey_id"];
                            // Safe to drop into a single-quoted JS string that itself
                            // sits inside a double-quoted HTML attribute.
                            $survey_title_js = htmlspecialchars(addslashes($s["title"]), ENT_QUOTES, "UTF-8");
                        ?>
                        <?php if ($s["status"] === "active"): ?>
                            <button type="button" class="btn btn-sm btn-danger"
                                onclick="openConfirmModal({
                                    url: '<?= $toggle_url ?>',
                                    title: 'Deactivate this survey?',
                                    message: '\u201c<?= $survey_title_js ?>\u201d will be closed and residents will no longer be able to respond to it. You can reactivate it again anytime.',
                                    confirmLabel: 'Deactivate',
                                    danger: true
                                })">
                               Deactivate
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-success-soft"
                                onclick="openConfirmModal({
                                    url: '<?= $toggle_url ?>',
                                    title: 'Reactivate this survey?',
                                    message: '\u201c<?= $survey_title_js ?>\u201d will go live again and residents will be able to respond to it.',
                                    confirmLabel: 'Activate',
                                    danger: false
                                })">
                               Activate
                            </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
        </div>
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