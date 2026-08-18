<?php
require_once __DIR__ . "/../includes/functions.php";
require_resident_login();

$resident_id = $_SESSION["resident_id"];

$success = "";
$error = "";

$password_success = "";
$password_error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile"])) {

    $middle_name = trim($_POST["middle_name"] ?? "");
    $extension_name = trim($_POST["extension_name"] ?? "");
    $civil_status = trim($_POST["civil_status"] ?? "");
    $birthday = trim($_POST["birthday"] ?? "");
    $occupation = trim($_POST["occupation"] ?? "");
    $employer = trim($_POST["employer"] ?? "");
    $employer_address = trim($_POST["employer_address"] ?? "");

    $email = trim($_POST["email"] ?? "");
    $contact_number = trim($_POST["contact_number"] ?? "");
    $address = trim($_POST["address"] ?? "");

    $age = null;

    if ($birthday !== "") {

        $birthday_date = DateTime::createFromFormat("Y-m-d", $birthday);

        if (!$birthday_date || $birthday_date->format("Y-m-d") !== $birthday) {
            $error = "Please enter a valid birthday.";
        }
        elseif ($birthday_date > new DateTime("today")) {
            $error = "Birthday cannot be in the future.";
        }
        else {
            $today = new DateTime("today");
            $age = $today->diff($birthday_date)->y;
        }

    }

    if (
        $error === "" &&
        $civil_status !== "" &&
        !in_array(
            $civil_status,
            ["Single", "Married", "Widowed", "Separated", "Divorced"],
            true
        )
    ) {
        $error = "Please select a valid civil status.";
    }

    if ($error === "") {

        $current_stmt = $conn->prepare("
            SELECT
                middle_name,
                extension_name,
                civil_status,
                birthday,
                age,
                occupation,
                employer,
                employer_address,
                email,
                contact_number,
                address
            FROM residents
            WHERE resident_id = ?
        ");

        $current_stmt->bind_param("i", $resident_id);
        $current_stmt->execute();

        $current_profile = $current_stmt->get_result()->fetch_assoc();

        if ($current_profile) {

            $changed =
                $middle_name !== ($current_profile["middle_name"] ?? "") ||
                $extension_name !== ($current_profile["extension_name"] ?? "") ||
                $civil_status !== ($current_profile["civil_status"] ?? "") ||
                $birthday !== ($current_profile["birthday"] ?? "") ||
                (string)$age !== (string)($current_profile["age"] ?? "") ||
                $occupation !== ($current_profile["occupation"] ?? "") ||
                $employer !== ($current_profile["employer"] ?? "") ||
                $employer_address !== ($current_profile["employer_address"] ?? "") ||
                $email !== ($current_profile["email"] ?? "") ||
                $contact_number !== ($current_profile["contact_number"] ?? "") ||
                $address !== ($current_profile["address"] ?? "");

            if ($changed) {

                $stmt = $conn->prepare("
                    UPDATE residents
                    SET
                        middle_name = ?,
                        extension_name = ?,
                        civil_status = ?,
                        birthday = NULLIF(?, ''),
                        age = ?,
                        occupation = ?,
                        employer = ?,
                        employer_address = ?,
                        email = ?,
                        contact_number = ?,
                        address = ?
                    WHERE resident_id = ?
                ");

                $stmt->bind_param(
                    "ssssissssssi",
                    $middle_name,
                    $extension_name,
                    $civil_status,
                    $birthday,
                    $age,
                    $occupation,
                    $employer,
                    $employer_address,
                    $email,
                    $contact_number,
                    $address,
                    $resident_id
                );

                if ($stmt->execute()) {
                    $success = "Profile updated successfully.";
                } else {
                    $error = "Unable to update your profile.";
                }

            } else {
                $success = "No profile changes were made.";
            }
        }
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["change_password"])) {

    $current_password = $_POST["current_password"] ?? "";
    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    $stmt = $conn->prepare(
        "SELECT password FROM residents WHERE resident_id = ?"
    );

    $stmt->bind_param("i", $resident_id);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();

    if (!$row || !password_verify($current_password, $row["password"])) {

        $password_error = "Current password is incorrect.";

    }
    elseif (strlen($new_password) < 6) {

        $password_error = "New password must be at least 6 characters.";

    }
    elseif ($new_password !== $confirm_password) {

        $password_error = "New passwords do not match.";

    }
    else {

        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        $update = $conn->prepare(
            "UPDATE residents SET password = ? WHERE resident_id = ?"
        );

        $update->bind_param("si", $hashed, $resident_id);
        $update->execute();

        $password_success = "Password changed successfully.";
    }
}


$stmt = $conn->prepare("
    SELECT
        resident_number,
        first_name,
        middle_name,
        last_name,
        extension_name,
        civil_status,
        birthday,
        age,
        occupation,
        employer,
        employer_address,
        email,
        contact_number,
        address
    FROM residents
    WHERE resident_id = ?
");

$stmt->bind_param("i", $resident_id);
$stmt->execute();

$resident = $stmt->get_result()->fetch_assoc();

if (!$resident) {
    $resident = [
        "resident_number" => "",
        "first_name" => "",
        "middle_name" => "",
        "last_name" => "",
        "extension_name" => "",
        "civil_status" => "",
        "birthday" => "",
        "age" => "",
        "occupation" => "",
        "employer" => "",
        "employer_address" => "",
        "email" => "",
        "contact_number" => "",
        "address" => ""
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>My Profile</title>

<link
    rel="stylesheet"
    href="../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../assets/css/style.css") ?>"
>

<script>
(function(){
    var t=localStorage.getItem("theme");
    if(t==="dark")
        document.documentElement.setAttribute("data-theme","dark");
})();
</script>

<script>
(function(){
    try{
        if(localStorage.getItem("sidebarCollapsed")==="true")
            document.documentElement.setAttribute("data-sidebar","collapsed");
    }catch(e){}
})();
</script>

</head>

<body>

<?php include __DIR__ . "/../includes/resident_nav.php"; ?>

<div class="container">

    <?php include __DIR__ . "/../includes/resident_topbar.php"; ?>

    <div class="welcome-header">
        <div>
            <h1>My Profile</h1>
            <p>Update your personal information and manage your account security.</p>
        </div>
    </div>

    <div class="profile-grid">

        <div class="card profile-card">

            <h2>Personal Information</h2>

            <div id="profileFeedback" aria-live="polite">

                <?php if ($success): ?>
                    <div class="success"><?= e($success) ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="error"><?= e($error) ?></div>
                <?php endif; ?>

            </div>

            <form method="POST" id="profileForm" class="profile-form">

                <input
                    type="hidden"
                    name="update_profile"
                    value="1"
                >

                <div class="profile-fields">

                    <div class="profile-field">

                        <label>Resident Number</label>

                        <input
                            type="text"
                            value="<?= e($resident["resident_number"]) ?>"
                            disabled
                        >

                    </div>


                    <div class="profile-field">

                        <label>First Name</label>

                        <input
                            type="text"
                            value="<?= e($resident["first_name"]) ?>"
                            disabled
                        >

                    </div>


                    <div class="profile-field">

                        <label>Middle Name</label>

                        <input
                            type="text"
                            id="profileMiddleName"
                            name="middle_name"
                            value="<?= e($resident["middle_name"]) ?>"
                        >

                    </div>


                    <div class="profile-field">

                        <label>Last Name</label>

                        <input
                            type="text"
                            value="<?= e($resident["last_name"]) ?>"
                            disabled
                        >

                    </div>


                    <div class="profile-field">

                        <label>Extension Name</label>

                        <input
                            type="text"
                            id="profileExtensionName"
                            name="extension_name"
                            placeholder="Jr., Sr., III"
                            value="<?= e($resident["extension_name"]) ?>"
                        >

                    </div>


                    <div class="profile-field">

                        <label>Civil Status</label>

                        <select
                            name="civil_status"
                            id="profileCivilStatus"
                        >

                            <option value="">Select Civil Status</option>

                            <option
                                value="Single"
                                <?= $resident["civil_status"] === "Single" ? "selected" : "" ?>
                            >
                                Single
                            </option>

                            <option
                                value="Married"
                                <?= $resident["civil_status"] === "Married" ? "selected" : "" ?>
                            >
                                Married
                            </option>

                            <option
                                value="Widowed"
                                <?= $resident["civil_status"] === "Widowed" ? "selected" : "" ?>
                            >
                                Widowed
                            </option>

                            <option
                                value="Separated"
                                <?= $resident["civil_status"] === "Separated" ? "selected" : "" ?>
                            >
                                Separated
                            </option>

                            <option
                                value="Divorced"
                                <?= $resident["civil_status"] === "Divorced" ? "selected" : "" ?>
                            >
                                Divorced
                            </option>

                        </select>

                    </div>


                    <div class="profile-field">

                        <label>Birthday</label>

                        <input
                            type="date"
                            id="profileBirthday"
                            name="birthday"
                            max="<?= date("Y-m-d") ?>"
                            value="<?= e($resident["birthday"]) ?>"
                        >

                    </div>


                    <div class="profile-field">

                        <label>Age</label>

                        <input
                            type="number"
                            id="profileAge"
                            name="age"
                            value="<?= e($resident["age"]) ?>"
                            readonly
                            min="0"
                        >

                    </div>


                    <div class="profile-field">

                        <label>Occupation</label>

                        <input
                            type="text"
                            id="profileOccupation"
                            name="occupation"
                            value="<?= e($resident["occupation"]) ?>"
                        >

                    </div>


                    <div class="profile-field">

                        <label>Employer</label>

                        <input
                            type="text"
                            id="profileEmployer"
                            name="employer"
                            value="<?= e($resident["employer"]) ?>"
                        >

                    </div>


                    <div class="profile-field">

                        <label>Employer Address</label>

                        <input
                            type="text"
                            id="profileEmployerAddress"
                            name="employer_address"
                            value="<?= e($resident["employer_address"]) ?>"
                        >

                    </div>


                    <div class="profile-field">

                        <label>Email</label>

                        <input
                            type="email"
                            id="profileEmail"
                            name="email"
                            value="<?= e($resident["email"]) ?>"
                        >

                    </div>


                    <div class="profile-field">

                        <label>Contact Number</label>

                        <input
                            type="text"
                            id="profileContact"
                            name="contact_number"
                            value="<?= e($resident["contact_number"]) ?>"
                        >

                    </div>


                    <div class="profile-field">

                        <label>Address</label>

                        <input
                            type="text"
                            id="profileAddress"
                            name="address"
                            value="<?= e($resident["address"]) ?>"
                        >

                    </div>

                </div>


                <div class="profile-form-actions">

                    <button
                        type="submit"
                        id="saveProfileBtn"
                        class="btn btn-disabled"
                        disabled
                    >
                        Save Changes
                    </button>

                </div>

            </form>

        </div>


        <div class="card profile-card">

            <h2>Change Password</h2>

            <div id="passwordFeedback" aria-live="polite">

                <?php if ($password_success): ?>
                    <div class="success"><?= e($password_success) ?></div>
                <?php endif; ?>

                <?php if ($password_error): ?>
                    <div class="error"><?= e($password_error) ?></div>
                <?php endif; ?>

            </div>


            <form
                method="POST"
                id="passwordForm"
                class="profile-form"
            >

                <input
                    type="hidden"
                    name="change_password"
                    value="1"
                >


                <div class="profile-fields password-fields">

                    <div class="profile-field">

                        <label>Current Password</label>

                        <input
                            type="password"
                            name="current_password"
                            required
                        >

                    </div>


                    <div class="profile-field">

                        <label>New Password</label>

                        <input
                            type="password"
                            name="new_password"
                            required
                            minlength="6"
                        >

                    </div>


                    <div class="profile-field">

                        <label>Confirm New Password</label>

                        <input
                            type="password"
                            name="confirm_password"
                            required
                            minlength="6"
                        >

                    </div>

                </div>


                <div class="profile-form-actions">

                    <button
                        type="submit"
                        id="updatePasswordBtn"
                    >
                        Update Password
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- Confirmation modal -->

<div
    class="modal-overlay"
    id="profileConfirmModal"
    aria-hidden="true"
>

    <div
        class="modal-box modal-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="profileConfirmTitle"
    >

        <div
            class="modal-icon success"
            id="profileConfirmIcon"
        >

            <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>

        </div>


        <h3 id="profileConfirmTitle">
            Confirm Changes
        </h3>


        <p
            class="modal-message"
            id="profileConfirmMessage"
        ></p>


        <div class="modal-actions">

            <button
                type="button"
                class="btn btn-secondary"
                id="profileConfirmCancel"
            >
                Cancel
            </button>

            <button
                type="button"
                class="btn"
                id="profileConfirmProceed"
            >
                Confirm
            </button>

        </div>

    </div>

</div>


<script src="../assets/js/script.js"></script>

<script>

document.addEventListener("DOMContentLoaded", function () {

    const profileForm = document.getElementById("profileForm");
    const saveProfileBtn = document.getElementById("saveProfileBtn");

    const birthdayInput = document.getElementById("profileBirthday");
    const ageInput = document.getElementById("profileAge");

    const passwordForm = document.getElementById("passwordForm");

    const modal = document.getElementById("profileConfirmModal");
    const modalTitle = document.getElementById("profileConfirmTitle");
    const modalMessage = document.getElementById("profileConfirmMessage");
    const modalCancel = document.getElementById("profileConfirmCancel");
    const modalProceed = document.getElementById("profileConfirmProceed");
    const modalIcon = document.getElementById("profileConfirmIcon");


    const profileInputs = {

        middle_name:
            document.getElementById("profileMiddleName"),

        extension_name:
            document.getElementById("profileExtensionName"),

        civil_status:
            document.getElementById("profileCivilStatus"),

        birthday:
            birthdayInput,

        occupation:
            document.getElementById("profileOccupation"),

        employer:
            document.getElementById("profileEmployer"),

        employer_address:
            document.getElementById("profileEmployerAddress"),

        email:
            document.getElementById("profileEmail"),

        contact:
            document.getElementById("profileContact"),

        address:
            document.getElementById("profileAddress")
    };


    const originalProfile = {

        middle_name:
            profileInputs.middle_name.value,

        extension_name:
            profileInputs.extension_name.value,

        civil_status:
            profileInputs.civil_status.value,

        birthday:
            profileInputs.birthday.value,

        occupation:
            profileInputs.occupation.value,

        employer:
            profileInputs.employer.value,

        employer_address:
            profileInputs.employer_address.value,

        email:
            profileInputs.email.value,

        contact:
            profileInputs.contact.value,

        address:
            profileInputs.address.value
    };


    let pendingForm = null;


    function calculateAge() {

        if (!birthdayInput.value) {
            ageInput.value = "";
            return;
        }

        const birthday =
            new Date(birthdayInput.value + "T00:00:00");

        const today = new Date();

        let age =
            today.getFullYear() -
            birthday.getFullYear();

        const monthDifference =
            today.getMonth() -
            birthday.getMonth();

        if (
            monthDifference < 0 ||
            (
                monthDifference === 0 &&
                today.getDate() < birthday.getDate()
            )
        ) {
            age--;
        }

        ageInput.value =
            age >= 0 ? age : "";
    }


    birthdayInput.addEventListener(
        "change",
        function () {

            calculateAge();
            updateProfileButton();

        }
    );


    birthdayInput.addEventListener(
        "input",
        function () {

            calculateAge();
            updateProfileButton();

        }
    );


    function showFeedback(
        targetId,
        message,
        type
    ) {

        const target =
            document.getElementById(targetId);

        target.innerHTML =
            '<div class="' +
            type +
            '">' +
            message +
            "</div>";
    }


    function profileHasChanges() {

        return (

            profileInputs.middle_name.value !==
                originalProfile.middle_name ||

            profileInputs.extension_name.value !==
                originalProfile.extension_name ||

            profileInputs.civil_status.value !==
                originalProfile.civil_status ||

            profileInputs.birthday.value !==
                originalProfile.birthday ||

            profileInputs.occupation.value !==
                originalProfile.occupation ||

            profileInputs.employer.value !==
                originalProfile.employer ||

            profileInputs.employer_address.value !==
                originalProfile.employer_address ||

            profileInputs.email.value !==
                originalProfile.email ||

            profileInputs.contact.value !==
                originalProfile.contact ||

            profileInputs.address.value !==
                originalProfile.address
        );
    }


    function updateProfileButton() {

        const changed =
            profileHasChanges();

        saveProfileBtn.disabled =
            !changed;

        saveProfileBtn.classList.toggle(
            "btn-disabled",
            !changed
        );
    }


    Object.values(profileInputs).forEach(
        function (input) {

            input.addEventListener(
                "input",
                updateProfileButton
            );

            input.addEventListener(
                "change",
                updateProfileButton
            );

        }
    );


    function openConfirmation(
        title,
        message,
        form,
        danger
    ) {

        pendingForm = form;

        modalTitle.textContent =
            title;

        modalMessage.textContent =
            message;

        modal.classList.add("open");

        modal.setAttribute(
            "aria-hidden",
            "false"
        );


        modalIcon.className =
            "modal-icon " +
            (
                danger
                    ? "danger"
                    : "success"
            );


        modalProceed.className =
            "btn " +
            (
                danger
                    ? "btn-danger"
                    : "btn-success-soft"
            );

        modalProceed.textContent =
            "Confirm";
    }


    function closeConfirmation(
        cancelled
    ) {

        modal.classList.remove(
            "open"
        );

        modal.setAttribute(
            "aria-hidden",
            "true"
        );


        if (
            cancelled &&
            pendingForm === profileForm
        ) {

            showFeedback(
                "profileFeedback",
                "Profile update cancelled.",
                "success"
            );

        }
        else if (
            cancelled &&
            pendingForm === passwordForm
        ) {

            showFeedback(
                "passwordFeedback",
                "Password change cancelled.",
                "success"
            );

        }


        pendingForm = null;
    }


    profileForm.addEventListener(
        "submit",
        function (event) {

            if (!profileHasChanges()) {

                event.preventDefault();

                updateProfileButton();

                return;
            }


            event.preventDefault();


            openConfirmation(
                "Confirm Profile Changes",
                "Are you sure you want to save these profile changes?",
                profileForm,
                false
            );

        }
    );


    passwordForm.addEventListener(
        "submit",
        function (event) {

            event.preventDefault();


            if (!passwordForm.checkValidity()) {

                passwordForm.reportValidity();

                return;
            }


            const newPassword =
                passwordForm.querySelector(
                    "input[name='new_password']"
                ).value;


            const confirmPassword =
                passwordForm.querySelector(
                    "input[name='confirm_password']"
                ).value;


            if (
                newPassword !==
                confirmPassword
            ) {

                showFeedback(
                    "passwordFeedback",
                    "New passwords do not match.",
                    "error"
                );

                return;
            }


            openConfirmation(
                "Confirm Password Change",
                "Are you sure you want to change your password? This action will update your account password.",
                passwordForm,
                true
            );

        }
    );


    modalCancel.addEventListener(
        "click",
        function () {

            closeConfirmation(true);

        }
    );


    modalProceed.addEventListener(
        "click",
        function () {

            if (!pendingForm)
                return;


            const formToSubmit =
                pendingForm;

            pendingForm = null;


            modal.classList.remove(
                "open"
            );

            modal.setAttribute(
                "aria-hidden",
                "true"
            );


            formToSubmit.submit();

        }
    );


    modal.addEventListener(
        "click",
        function (event) {

            if (
                event.target === modal
            ) {

                closeConfirmation(true);

            }

        }
    );


    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Escape" &&
                modal.classList.contains("open")
            ) {

                closeConfirmation(true);

            }

        }
    );


    calculateAge();
    updateProfileButton();

});

</script>

</body>
</html>