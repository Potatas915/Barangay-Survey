<?php
require_once __DIR__ . "/../includes/functions.php";
require_resident_login();

$resident_id = $_SESSION["resident_id"];
$success = "";
$error = "";
$password_success = "";
$password_error = "";
$photo_success = "";
$photo_error = "";
$child_error = "";

// ---------------------------------------------------------
// Update personal / family / reference information
// ---------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile"])) {
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

    if ($first_name === "" || $last_name === "") {
        $error = "First name and last name are required.";
    } else {
        $age = calculate_age($birthday);
        $birthday_sql = $birthday !== "" ? $birthday : null;
        $civil_status_sql = $civil_status !== "" ? $civil_status : null;

        $stmt = $conn->prepare("
            UPDATE residents SET
                first_name = ?, middle_name = ?, last_name = ?, extension_name = ?,
                civil_status = ?, email = ?, contact_number = ?, address = ?,
                birthday = ?, age = ?, occupation = ?, employer = ?, employer_address = ?,
                father_name = ?, mother_name = ?,
                spouse_name = ?, spouse_occupation = ?, spouse_employer = ?,
                reference1_name = ?, reference1_signature = ?,
                reference2_name = ?, reference2_signature = ?
            WHERE resident_id = ?
        ");
        $stmt->bind_param(
            "sssssssssissssssssssssi",
            $first_name, $middle_name, $last_name, $extension_name,
            $civil_status_sql, $email, $contact_number, $address,
            $birthday_sql, $age, $occupation, $employer, $employer_address,
            $father_name, $mother_name,
            $spouse_name, $spouse_occupation, $spouse_employer,
            $reference1_name, $reference1_signature,
            $reference2_name, $reference2_signature,
            $resident_id
        );

        if ($stmt->execute()) {
            $success = "Profile updated successfully.";
            $_SESSION["resident_name"] = trim($first_name . " " . $last_name);
        } else {
            $error = "Unable to update profile. Please try again.";
        }
    }
}

// ---------------------------------------------------------
// Upload / replace profile photo
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
            $photo_success = "Photo updated successfully.";
        } else {
            $photo_error = "Unable to save photo.";
        }
    } else {
        $photo_error = "Please choose a photo to upload.";
    }
}

// ---------------------------------------------------------
// Add a child
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
        $success = "Child added to your family information.";
    }
}

// ---------------------------------------------------------
// Remove a child (ownership-checked)
// ---------------------------------------------------------
if (isset($_GET["delete_child"])) {
    $child_id = (int)$_GET["delete_child"];
    $stmt = $conn->prepare("DELETE FROM resident_children WHERE child_id = ? AND resident_id = ?");
    $stmt->bind_param("ii", $child_id, $resident_id);
    $stmt->execute();
    redirect("profile.php");
}

// ---------------------------------------------------------
// Change password
// ---------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["change_password"])) {
    $current_password = $_POST["current_password"];
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];

    $stmt = $conn->prepare("SELECT password FROM residents WHERE resident_id = ?");
    $stmt->bind_param("i", $resident_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!password_verify($current_password, $row["password"])) {
        $password_error = "Current password is incorrect.";
    } elseif (strlen($new_password) < 6) {
        $password_error = "New password must be at least 6 characters.";
    } elseif ($new_password !== $confirm_password) {
        $password_error = "New passwords do not match.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE residents SET password = ? WHERE resident_id = ?");
        $update->bind_param("si", $hashed, $resident_id);
        $update->execute();
        $password_success = "Password changed successfully.";
    }
}

$stmt = $conn->prepare("SELECT * FROM residents WHERE resident_id = ?");
$stmt->bind_param("i", $resident_id);
$stmt->execute();
$resident = $stmt->get_result()->fetch_assoc();

$children_stmt = $conn->prepare("SELECT * FROM resident_children WHERE resident_id = ? ORDER BY child_id");
$children_stmt->bind_param("i", $resident_id);
$children_stmt->execute();
$children = $children_stmt->get_result();

$resident_initials = strtoupper(substr($resident["first_name"], 0, 1) . substr($resident["last_name"], 0, 1));
$photo_url = resident_photo_url($resident["photo"]);
$civil_statuses = ["Single", "Married", "Widowed", "Separated", "Divorced"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile</title>
<link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../assets/css/style.css") ?>">
<script>(function(){var t=localStorage.getItem("theme");if(t==="dark")document.documentElement.setAttribute("data-theme","dark");})();</script>
<script>(function(){try{if(localStorage.getItem("sidebarCollapsed")==="true")document.documentElement.setAttribute("data-sidebar","collapsed");}catch(e){}})();</script>
</head>
<body class="profile-page">
<?php include __DIR__ . "/../includes/resident_nav.php"; ?>
<div class="container">
    <?php include __DIR__ . "/../includes/resident_topbar.php"; ?>

    <div class="welcome-header">
        <div>
            <h1>My Profile</h1>
            <p>View and update your personal, family, and contact information.</p>
        </div>
        <button type="button" class="no-print" onclick="window.print()">Print My Profile</button>
    </div>

    <div class="card">
        <h2>Profile Photo</h2>
        <?php if ($photo_success): ?><div class="success"><?= e($photo_success) ?></div><?php endif; ?>
        <?php if ($photo_error): ?><div class="error"><?= e($photo_error) ?></div><?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="photo-uploader">
            <input type="hidden" name="upload_photo" value="1">
            <div class="avatar-lg">
                <?php if ($photo_url): ?>
                    <img src="<?= e($photo_url) ?>" alt="Profile photo">
                <?php else: ?>
                    <?= e($resident_initials) ?>
                <?php endif; ?>
            </div>
            <div>
                <input type="file" name="photo" accept="image/png, image/jpeg, image/webp">
                <p style="font-size:12px; color:#667; margin:6px 0 0;">Passport-size photo. JPG, PNG, or WEBP, up to 3MB.</p>
                <button type="submit" class="btn btn-sm no-print" style="margin-top:10px;">Upload Photo</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Personal &amp; Family Information</h2>
        <div id="profileFeedback" aria-live="polite">
            <?php if ($success): ?><div class="success"><?= e($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
        </div>

        <form method="POST" id="profileForm">
            <input type="hidden" name="update_profile" value="1">

            <div class="form-section-title">Personal Information</div>
            <div class="field-grid-2">
                <div class="register-field">
                    <label>Resident Number</label>
                    <input type="text" value="<?= e($resident["resident_number"]) ?>" disabled>
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
                    <label>Extension Name <small style="font-weight:400;">(Jr., Sr., III)</small></label>
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

            <div class="form-section-title">Spouse Information <small style="font-weight:400; text-transform:none;">(if applicable)</small></div>
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

            <div class="form-section-title">Character References <small style="font-weight:400; text-transform:none;">(signature optional)</small></div>
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

            <div class="profile-form-actions">
                <button type="submit" class="no-print">Save Changes</button>
            </div>
        </form>

        <div class="form-section-title no-print">Children</div>
        <div class="children-table-wrap no-print">
            <?php if ($child_error): ?><div class="error"><?= e($child_error) ?></div><?php endif; ?>
            <div class="table-scroll">
            <table>
                <tr><th>Child Name</th><th>Age</th><th class="no-print">Action</th></tr>
                <?php if ($children->num_rows === 0): ?>
                <tr><td colspan="3" style="text-align:center; color:#889;">No children added yet.</td></tr>
                <?php else: $children->data_seek(0); while ($c = $children->fetch_assoc()): ?>
                <tr>
                    <td><?= e($c["child_name"]) ?></td>
                    <td><?= $c["age"] !== null ? e($c["age"]) : "&mdash;" ?></td>
                    <td class="no-print">
                        <a href="profile.php?delete_child=<?= (int)$c["child_id"] ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Remove <?= e(addslashes($c['child_name'])) ?> from your children list?');">Remove</a>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </table>
            </div>

            <form method="POST" class="children-add-row">
                <input type="hidden" name="add_child" value="1">
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

        <!-- Print-only rendering of the children list (the editable table above is hidden when printing) -->
        <div class="print-only" style="display:none;">
            <div class="form-section-title">Children</div>
            <?php if ($children->num_rows === 0): ?>
                <p style="font-size:13px; color:#667;">No children on record.</p>
            <?php else: $children->data_seek(0); ?>
            <table>
                <tr><th>Child Name</th><th>Age</th></tr>
                <?php while ($c = $children->fetch_assoc()): ?>
                <tr><td><?= e($c["child_name"]) ?></td><td><?= $c["age"] !== null ? e($c["age"]) : "&mdash;" ?></td></tr>
                <?php endwhile; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h2>Change Password</h2>
        <div id="passwordFeedback" aria-live="polite">
            <?php if ($password_success): ?><div class="success"><?= e($password_success) ?></div><?php endif; ?>
            <?php if ($password_error): ?><div class="error"><?= e($password_error) ?></div><?php endif; ?>
        </div>

        <form method="POST" id="passwordForm" class="profile-form">
            <input type="hidden" name="change_password" value="1">

            <div class="profile-fields password-fields">
                <div class="profile-field">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>

                <div class="profile-field">
                    <label>New Password</label>
                    <input type="password" name="new_password" required minlength="6">
                </div>

                <div class="profile-field">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required minlength="6">
                </div>
            </div>

            <div class="profile-form-actions">
                <button type="submit" id="updatePasswordBtn">Update Password</button>
            </div>
        </form>
    </div>
</div>

<!-- Confirmation modal (password change) -->
<div class="modal-overlay" id="profileConfirmModal" aria-hidden="true">
    <div class="modal-box modal-sm" role="dialog" aria-modal="true" aria-labelledby="profileConfirmTitle">
        <div class="modal-icon danger" id="profileConfirmIcon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        <h3 id="profileConfirmTitle">Confirm Password Change</h3>
        <p class="modal-message" id="profileConfirmMessage"></p>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" id="profileConfirmCancel">Cancel</button>
            <button type="button" class="btn btn-danger" id="profileConfirmProceed">Confirm</button>
        </div>
    </div>
</div>

<script src="../assets/js/script.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    // Auto-compute displayed age whenever the birthday changes.
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
    if (birthdayInput) {
        birthdayInput.addEventListener("change", recomputeAge);
    }

    const passwordForm = document.getElementById("passwordForm");
    const modal = document.getElementById("profileConfirmModal");
    const modalMessage = document.getElementById("profileConfirmMessage");
    const modalCancel = document.getElementById("profileConfirmCancel");
    const modalProceed = document.getElementById("profileConfirmProceed");

    function showFeedback(targetId, message, type) {
        const target = document.getElementById(targetId);
        target.innerHTML = '<div class="' + type + '">' + message + '</div>';
    }

    passwordForm.addEventListener("submit", function (event) {
        event.preventDefault();

        if (!passwordForm.checkValidity()) {
            passwordForm.reportValidity();
            return;
        }

        const newPassword = passwordForm.querySelector("input[name='new_password']").value;
        const confirmPassword = passwordForm.querySelector("input[name='confirm_password']").value;

        if (newPassword !== confirmPassword) {
            showFeedback("passwordFeedback", "New passwords do not match.", "error");
            return;
        }

        modalMessage.textContent = "Are you sure you want to change your password? This action will update your account password.";
        modal.classList.add("open");
        modal.setAttribute("aria-hidden", "false");
    });

    modalCancel.addEventListener("click", function () {
        modal.classList.remove("open");
        modal.setAttribute("aria-hidden", "true");
        showFeedback("passwordFeedback", "Password change cancelled.", "success");
    });

    modalProceed.addEventListener("click", function () {
        modal.classList.remove("open");
        modal.setAttribute("aria-hidden", "true");
        passwordForm.submit();
    });

    modal.addEventListener("click", function (event) {
        if (event.target === modal) {
            modal.classList.remove("open");
            modal.setAttribute("aria-hidden", "true");
        }
    });
});
</script>
</body>
</html>
