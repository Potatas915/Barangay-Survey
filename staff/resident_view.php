<?php
require_once __DIR__ . "/../includes/functions.php";
require_staff_login();

$resident_id = (int)($_GET["resident_id"] ?? 0);

$stmt = $conn->prepare("SELECT * FROM residents WHERE resident_id = ?");
$stmt->bind_param("i", $resident_id);
$stmt->execute();
$resident = $stmt->get_result()->fetch_assoc();

if (!$resident) {
    redirect("resident_management.php");
}

$children_stmt = $conn->prepare("SELECT * FROM resident_children WHERE resident_id = ? ORDER BY child_id");
$children_stmt->bind_param("i", $resident_id);
$children_stmt->execute();
$children = $children_stmt->get_result();

$photo_url = resident_photo_url($resident["photo"]);
$initials = strtoupper(substr($resident["first_name"], 0, 1) . substr($resident["last_name"], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Resident Profile - <?= e(full_resident_name($resident)) ?></title>
<link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../assets/css/style.css") ?>">
<script>(function(){var t=localStorage.getItem("theme");if(t==="dark")document.documentElement.setAttribute("data-theme","dark");})();</script>
<script>(function(){try{if(localStorage.getItem("sidebarCollapsed")==="true")document.documentElement.setAttribute("data-sidebar","collapsed");}catch(e){}})();</script>
</head>
<body>
<?php $no_print_nav = true; include __DIR__ . "/../includes/staff_nav.php"; ?>
<div class="container">
<?php include __DIR__ . "/../includes/staff_topbar.php"; ?>

    <div class="welcome-header no-print">
        <div>
            <h1>Resident Profile</h1>
            <p>Read-only record for <?= e(full_resident_name($resident)) ?>.</p>
        </div>
        <div class="table-actions">
            <a class="btn btn-secondary" href="resident_edit.php?resident_id=<?= $resident_id ?>">Edit Record</a>
            <a class="btn btn-secondary" href="resident_management.php">Back to List</a>
            <button type="button" class="btn" onclick="window.print()">Print / Export as PDF</button>
        </div>
    </div>

    <div class="card print-sheet">
        <div class="print-header">
            <h1>Barangay Health Center - Resident Personal Information</h1>
            <p>Printed <?= date("F j, Y, g:i A") ?></p>
        </div>

        <div class="photo-uploader" style="margin-bottom:16px;">
            <div class="avatar-lg">
                <?php if ($photo_url): ?>
                    <img src="<?= e($photo_url) ?>" alt="Resident photo">
                <?php else: ?>
                    <?= e($initials) ?>
                <?php endif; ?>
            </div>
            <div>
                <strong style="font-size:16px;"><?= e(full_resident_name($resident)) ?></strong><br>
                <span style="color:#667; font-size:13px;">Resident Number: <?= e($resident["resident_number"]) ?></span>
            </div>
        </div>

        <div class="form-section-title">Personal Information</div>
        <div class="print-info-grid">
            <div class="info-row"><strong>Civil Status</strong><span><?= e($resident["civil_status"] ?? "—") ?></span></div>
            <div class="info-row"><strong>Birthday</strong><span><?= format_date_display($resident["birthday"]) ?></span></div>
            <div class="info-row"><strong>Age</strong><span><?= $resident["age"] !== null ? e($resident["age"]) : "—" ?></span></div>
            <div class="info-row"><strong>Contact Number</strong><span><?= e($resident["contact_number"]) ?: "—" ?></span></div>
            <div class="info-row"><strong>Email</strong><span><?= e($resident["email"]) ?: "—" ?></span></div>
            <div class="info-row"><strong>Occupation</strong><span><?= e($resident["occupation"]) ?: "—" ?></span></div>
            <div class="info-row"><strong>Employer</strong><span><?= e($resident["employer"]) ?: "—" ?></span></div>
            <div class="info-row" style="grid-column:1/-1;"><strong>Employer Address</strong><span><?= e($resident["employer_address"]) ?: "—" ?></span></div>
            <div class="info-row" style="grid-column:1/-1;"><strong>Address</strong><span><?= e($resident["address"]) ?: "—" ?></span></div>
        </div>

        <div class="form-section-title">Parents</div>
        <div class="print-info-grid">
            <div class="info-row"><strong>Father's Name</strong><span><?= e($resident["father_name"]) ?: "—" ?></span></div>
            <div class="info-row"><strong>Mother's Name</strong><span><?= e($resident["mother_name"]) ?: "—" ?></span></div>
        </div>

        <div class="form-section-title">Spouse Information</div>
        <div class="print-info-grid">
            <div class="info-row"><strong>Spouse Name</strong><span><?= e($resident["spouse_name"]) ?: "—" ?></span></div>
            <div class="info-row"><strong>Occupation</strong><span><?= e($resident["spouse_occupation"]) ?: "—" ?></span></div>
            <div class="info-row"><strong>Employer</strong><span><?= e($resident["spouse_employer"]) ?: "—" ?></span></div>
        </div>

        <div class="form-section-title">Children</div>
        <?php if ($children->num_rows === 0): ?>
            <p style="font-size:13px; color:#667;">No children on record.</p>
        <?php else: ?>
        <table>
            <tr><th>Child Name</th><th>Age</th></tr>
            <?php while ($c = $children->fetch_assoc()): ?>
            <tr><td><?= e($c["child_name"]) ?></td><td><?= $c["age"] !== null ? e($c["age"]) : "—" ?></td></tr>
            <?php endwhile; ?>
        </table>
        <?php endif; ?>

        <div class="form-section-title">Character References</div>
        <div class="print-info-grid">
            <div class="info-row"><strong>Reference 1</strong><span><?= e($resident["reference1_name"]) ?: "—" ?></span></div>
            <div class="info-row"><strong>Signature</strong><span><?= e($resident["reference1_signature"]) ?: "—" ?></span></div>
            <div class="info-row"><strong>Reference 2</strong><span><?= e($resident["reference2_name"]) ?: "—" ?></span></div>
            <div class="info-row"><strong>Signature</strong><span><?= e($resident["reference2_signature"]) ?: "—" ?></span></div>
        </div>

        <div class="form-section-title">Record Info</div>
        <div class="print-info-grid">
            <div class="info-row"><strong>Account Created</strong><span><?= format_date_display($resident["created_at"] ? substr($resident["created_at"], 0, 10) : null) ?></span></div>
            <div class="info-row"><strong>Last Updated</strong><span><?= $resident["updated_at"] ? e(date("M d, Y g:i A", strtotime($resident["updated_at"]))) : "—" ?></span></div>
        </div>
    </div>
</div>
<script src="../assets/js/script.js"></script>
<?php if (isset($_GET["print"]) && $_GET["print"] === "1"): ?>
<script>window.addEventListener("load", function () { window.print(); });</script>
<?php endif; ?>
</body>
</html>
