<?php
require_once __DIR__ . "/../includes/functions.php";
require_staff_login();

$resident_id = (int)($_GET["resident_id"] ?? ($_POST["resident_id"] ?? 0));
$success = "";
$error = "";
$photo_error = "";
$child_error = "";

// ---------------------------------------------------------
// Update personal / family / reference information
// ---------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_resident"])) {
    $resident_number   = trim($_POST["resident_number"]);
    $first_name        = trim($_POST["first_name"]);
    $middle_name       = trim($_POST["middle_name"]);
    $last_name         = trim($_POST["last_name"]);
    $extension_name    = trim($_POST["extension_name"]);
    $civil_status      = trim($_POST["civil_status"]);
    $email             = trim($_POST["email"]);
    $contact_number    = trim($_POST["contact_number"]);
    $address           = trim($_POST["address"]);
    $birthday          = trim($_POST["birthday"]);
    $occupation        = trim($_POST["occupation"]);
    $employer          = trim($_POST["employer"]);
    $employer_address  = trim($_POST["employer_address"]);
    $father_name       = trim($_POST["father_name"]);
    $mother_name       = trim($_POST["mother_name"]);
    $spouse_name       = trim($_POST["spouse_name"]);
    $spouse_occupation = trim($_POST["spouse_occupation"]);
    $spouse_employer   = trim($_POST["spouse_employer"]);
    $reference1_name      = trim($_POST["reference1_name"]);
    $reference1_signature = trim($_POST["reference1_signature"]);
    $reference2_name      = trim($_POST["reference2_name"]);
    $reference2_signature = trim($_POST["reference2_signature"]);

    if ($resident_number === "" || $first_name === "" || $last_name === "") {
        $error = "Resident number, first name, and last name are required.";
    } else {
        $dup = $conn->prepare("SELECT resident_id FROM residents WHERE resident_number = ? AND resident_id != ?");
        $dup->bind_param("si", $resident_number, $resident_id);
        $dup->execute();
        if ($dup->get_result()->num_rows > 0) {
            $error = "Resident number is already used by another resident.";
        } else {
            $age = calculate_age($birthday);
            $birthday_sql = $birthday !== "" ? $birthday : null;
            $civil_status_sql = $civil_status !== "" ? $civil_status : null;

            $stmt = $conn->prepare("
                UPDATE residents SET
                    resident_number = ?, first_name = ?, middle_name = ?, last_name = ?, extension_name = ?,
                    civil_status = ?, email = ?, contact_number = ?, address = ?,
                    birthday = ?, age = ?, occupation = ?, employer = ?, employer_address = ?,
                    father_name = ?, mother_name = ?,
                    spouse_name = ?, spouse_occupation = ?, spouse_employer = ?,
                    reference1_name = ?, reference1_signature = ?,
                    reference2_name = ?, reference2_signature = ?
                WHERE resident_id = ?
            ");
            $stmt->bind_param(
                "ssssssssssissssssssssssi",
                $resident_number, $first_name, $middle_name, $last_name, $extension_name,
                $civil_status_sql, $email, $contact_number, $address,
                $birthday_sql, $age, $occupation, $employer, $employer_address,
                $father_name, $mother_name,
                $spouse_name, $spouse_occupation, $spouse_employer,
                $reference1_name, $reference1_signature,
                $reference2_name, $reference2_signature,
                $resident_id
            );

            if ($stmt->execute()) {
                $success = "Resident information updated successfully.";
            } else {
                $error = "Unable to update resident. Please try again.";
            }
        }
    }
}

// ---------------------------------------------------------
// Upload / replace photo (staff can do this on behalf of a resident too)
// ---------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["upload_photo"])) {
    [$new_photo, $upload_err] = save_resident_photo($resident_id, $_FILES["photo"] ?? null);
    if ($upload_err) {
        $photo_error = $upload_err;
    } elseif ($new_photo) {
        $old = $conn->prepare("SELECT photo FROM residents WHERE resident_id = ?");
        $old->bind_param("i", $resident_id);
        $old->execute();
        $old_photo = $old->get_result()->fetch_assoc()["photo"] ?? null;

        $stmt = $conn->prepare("UPDATE residents SET photo = ? WHERE resident_id = ?");
        $stmt->bind_param("si", $new_photo, $resident_id);
        if ($stmt->execute()) {
            delete_resident_photo($old_photo);
            $success = "Photo updated successfully.";
        } else {
            $photo_error = "Unable to save photo.";
        }
    } else {
        $photo_error = "Please choose a photo to upload.";
    }
}

// ---------------------------------------------------------
// Add / remove a child
// ---------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_child"])) {
    $child_name = trim($_POST["child_name"]);
    $child_age  = trim($_POST["child_age"]);

    if ($child_name === "") {
        $child_error = "Child's name is required.";
    } else {
        $child_age_sql = ($child_age !== "" && is_numeric($child_age)) ? (int)$child_age : null;
        $stmt = $conn->prepare("INSERT INTO resident_children (resident_id, child_name, age) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $resident_id, $child_name, $child_age_sql);
        $stmt->execute();
        $success = "Child added.";
    }
}

if (isset($_GET["delete_child"])) {
    $child_id = (int)$_GET["delete_child"];
    $stmt = $conn->prepare("DELETE FROM resident_children WHERE child_id = ? AND resident_id = ?");
    $stmt->bind_param("ii", $child_id, $resident_id);
    $stmt->execute();
    redirect("resident_edit.php?resident_id=" . $resident_id);
}

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
$civil_statuses = ["Single", "Married", "Widowed", "Separated", "Divorced"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Resident</title>
<link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../assets/css/style.css") ?>">
<script>(function(){var t=localStorage.getItem("theme");if(t==="dark")document.documentElement.setAttribute("data-theme","dark");})();</script>
<script>(function(){try{if(localStorage.getItem("sidebarCollapsed")==="true")document.documentElement.setAttribute("data-sidebar","collapsed");}catch(e){}})();</script>
</head>
<body>
<?php include __DIR__ . "/../includes/staff_nav.php"; ?>
<div class="container">
<?php include __DIR__ . "/../includes/staff_topbar.php"; ?>

    <div class="welcome-header">
        <div>
            <h1>Edit Resident</h1>
            <p><?= e(full_resident_name($resident)) ?> &middot; <?= e($resident["resident_number"]) ?></p>
        </div>
        <div class="table-actions">
            <a class="btn btn-secondary" href="resident_view.php?resident_id=<?= $resident_id ?>">View / Print</a>
            <a class="btn btn-secondary" href="resident_management.php">Back to List</a>
        </div>
    </div>

    <div class="card">
        <h2>Profile Photo</h2>
        <?php if ($photo_error): ?><div class="error"><?= e($photo_error) ?></div><?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="photo-uploader">
            <input type="hidden" name="upload_photo" value="1">
            <input type="hidden" name="resident_id" value="<?= $resident_id ?>">
            <div class="avatar-lg">
                <?php if ($photo_url): ?>
                    <img src="<?= e($photo_url) ?>" alt="Resident photo">
                <?php else: ?>
                    <?= e($initials) ?>
                <?php endif; ?>
            </div>
            <div>
                <input type="file" name="photo" accept="image/png, image/jpeg, image/webp">
                <p style="font-size:12px; color:#667; margin:6px 0 0;">Passport-size photo. JPG, PNG, or WEBP, up to 3MB.</p>
                <button type="submit" class="btn btn-sm" style="margin-top:10px;">Upload Photo</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Resident Information</h2>
        <?php if ($success): ?><div class="success"><?= e($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>

        <form method="POST">
            <input type="hidden" name="update_resident" value="1">
            <input type="hidden" name="resident_id" value="<?= $resident_id ?>">

            <div class="form-section-title">Personal Information</div>
            <div class="field-grid-2">
                <div class="register-field">
                    <label>Resident Number</label>
                    <input type="text" name="resident_number" required value="<?= e($resident["resident_number"]) ?>">
                </div>
                <div class="register-field">
                    <label>Civil Status</label>
                    <select name="civil_status">
                        <option value="">-- Select --</option>
                        <?php foreach ($civil_statuses as $cs): ?>
                        <option value="<?= e($cs) ?>" <?= $resident["civil_status"] === $cs ? "selected" : "" ?>><?= e($cs) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="register-field">
                    <label>Last Name</label>
                    <input type="text" name="last_name" required value="<?= e($resident["last_name"]) ?>">
                </div>
                <div class="register-field">
                    <label>First Name</label>
                    <input type="text" name="first_name" required value="<?= e($resident["first_name"]) ?>">
                </div>
                <div class="register-field">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" value="<?= e($resident["middle_name"]) ?>">
                </div>
                <div class="register-field">
                    <label>Extension Name</label>
                    <input type="text" name="extension_name" value="<?= e($resident["extension_name"]) ?>">
                </div>
                <div class="register-field span-2">
                    <label>Address</label>
                    <input type="text" name="address" value="<?= e($resident["address"]) ?>">
                </div>
                <div class="register-field">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= e($resident["email"]) ?>">
                </div>
                <div class="register-field">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" value="<?= e($resident["contact_number"]) ?>">
                </div>
                <div class="register-field">
                    <label>Birthday</label>
                    <input type="date" name="birthday" id="birthdayInput" value="<?= e($resident["birthday"]) ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="register-field">
                    <label>Age</label>
                    <input type="text" id="ageDisplay" value="<?= $resident["age"] !== null ? e($resident["age"]) : "" ?>" disabled placeholder="Auto-computed from birthday">
                </div>
                <div class="register-field">
                    <label>Occupation</label>
                    <input type="text" name="occupation" value="<?= e($resident["occupation"]) ?>">
                </div>
                <div class="register-field">
                    <label>Employer</label>
                    <input type="text" name="employer" value="<?= e($resident["employer"]) ?>">
                </div>
                <div class="register-field span-2">
                    <label>Employer Address</label>
                    <input type="text" name="employer_address" value="<?= e($resident["employer_address"]) ?>">
                </div>
            </div>

            <div class="form-section-title">Parents</div>
            <div class="field-grid-2">
                <div class="register-field">
                    <label>Father's Name</label>
                    <input type="text" name="father_name" value="<?= e($resident["father_name"]) ?>">
                </div>
                <div class="register-field">
                    <label>Mother's Name</label>
                    <input type="text" name="mother_name" value="<?= e($resident["mother_name"]) ?>">
                </div>
            </div>

            <div class="form-section-title">Spouse Information</div>
            <div class="field-grid-2">
                <div class="register-field">
                    <label>Spouse Name</label>
                    <input type="text" name="spouse_name" value="<?= e($resident["spouse_name"]) ?>">
                </div>
                <div class="register-field">
                    <label>Occupation</label>
                    <input type="text" name="spouse_occupation" value="<?= e($resident["spouse_occupation"]) ?>">
                </div>
                <div class="register-field">
                    <label>Employer</label>
                    <input type="text" name="spouse_employer" value="<?= e($resident["spouse_employer"]) ?>">
                </div>
            </div>

            <div class="form-section-title">Character References</div>
            <div class="field-grid-2">
                <div class="register-field">
                    <label>Reference 1 - Name</label>
                    <input type="text" name="reference1_name" value="<?= e($resident["reference1_name"]) ?>">
                </div>
                <div class="register-field">
                    <label>Reference 1 - Signature</label>
                    <input type="text" name="reference1_signature" value="<?= e($resident["reference1_signature"]) ?>" placeholder="Optional">
                </div>
                <div class="register-field">
                    <label>Reference 2 - Name</label>
                    <input type="text" name="reference2_name" value="<?= e($resident["reference2_name"]) ?>">
                </div>
                <div class="register-field">
                    <label>Reference 2 - Signature</label>
                    <input type="text" name="reference2_signature" value="<?= e($resident["reference2_signature"]) ?>" placeholder="Optional">
                </div>
            </div>

            <button type="submit" style="margin-top:20px;">Save Changes</button>
            <a href="resident_management.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>

    <div class="card">
        <h2>Children</h2>
        <?php if ($child_error): ?><div class="error"><?= e($child_error) ?></div><?php endif; ?>
        <div class="table-scroll">
        <table>
            <tr><th>Child Name</th><th>Age</th><th>Action</th></tr>
            <?php if ($children->num_rows === 0): ?>
            <tr><td colspan="3" style="text-align:center; color:#889;">No children on record.</td></tr>
            <?php else: $children->data_seek(0); while ($c = $children->fetch_assoc()): ?>
            <tr>
                <td><?= e($c["child_name"]) ?></td>
                <td><?= $c["age"] !== null ? e($c["age"]) : "&mdash;" ?></td>
                <td>
                    <a href="resident_edit.php?resident_id=<?= $resident_id ?>&delete_child=<?= (int)$c["child_id"] ?>" class="btn btn-sm btn-danger"
                       onclick="return confirm('Remove <?= e(addslashes($c['child_name'])) ?>?');">Remove</a>
                </td>
            </tr>
            <?php endwhile; endif; ?>
        </table>
        </div>

        <form method="POST" class="children-add-row">
            <input type="hidden" name="add_child" value="1">
            <input type="hidden" name="resident_id" value="<?= $resident_id ?>">
            <div class="register-field">
                <label>Child Name</label>
                <input type="text" name="child_name" placeholder="Full name" required>
            </div>
            <div class="register-field age-field">
                <label>Age</label>
                <input type="number" name="child_age" min="0" max="120">
            </div>
            <button type="submit" class="btn btn-sm">+ Add Child</button>
        </form>
    </div>
</div>
<script src="../assets/js/script.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const birthdayInput = document.getElementById("birthdayInput");
    const ageDisplay = document.getElementById("ageDisplay");
    function recomputeAge() {
        if (!birthdayInput.value) { ageDisplay.value = ""; return; }
        const b = new Date(birthdayInput.value);
        const today = new Date();
        let age = today.getFullYear() - b.getFullYear();
        const m = today.getMonth() - b.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < b.getDate())) age--;
        ageDisplay.value = age >= 0 ? age : "";
    }
    if (birthdayInput) birthdayInput.addEventListener("change", recomputeAge);
});
</script>
</body>
</html>
