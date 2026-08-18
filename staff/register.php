<?php
require_once __DIR__ . "/../includes/functions.php";
require_staff_login();

$error = "";
$success = "";

$resident_number = "";
$first_name = "";
$middle_name = "";
$last_name = "";
$extension_name = "";
$civil_status = "";
$birthday = "";
$age = "";
$occupation = "";
$employer = "";
$employer_address = "";
$email = "";
$contact_number = "";
$address = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $resident_number = trim($_POST["resident_number"] ?? "");
    $first_name = trim($_POST["first_name"] ?? "");
    $middle_name = trim($_POST["middle_name"] ?? "");
    $last_name = trim($_POST["last_name"] ?? "");
    $extension_name = trim($_POST["extension_name"] ?? "");
    $civil_status = trim($_POST["civil_status"] ?? "");
    $birthday = trim($_POST["birthday"] ?? "");
    $occupation = trim($_POST["occupation"] ?? "");
    $employer = trim($_POST["employer"] ?? "");
    $employer_address = trim($_POST["employer_address"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $contact_number = trim($_POST["contact_number"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    /*
     * Age is calculated from the birthday instead of being
     * manually entered.
     */
    $age = "";

    if ($birthday !== "") {
        $birthday_date = DateTime::createFromFormat("Y-m-d", $birthday);

        if (!$birthday_date || $birthday_date->format("Y-m-d") !== $birthday) {
            $error = "Please enter a valid birthday.";
        } elseif ($birthday_date > new DateTime("today")) {
            $error = "Birthday cannot be in the future.";
        } else {
            $today = new DateTime("today");
            $age = $today->diff($birthday_date)->y;
        }
    }

    if ($error === "" && ($resident_number === "" || $first_name === "" || $last_name === "")) {
        $error = "Resident number, first name, and last name are required.";
    }
    elseif ($error === "" && $civil_status !== "" &&
        !in_array($civil_status, ["Single", "Married", "Widowed", "Separated", "Divorced"], true)
    ) {
        $error = "Please select a valid civil status.";
    }
    elseif ($error === "" && $password !== $confirm_password) {
        $error = "Passwords do not match.";
    }
    elseif ($error === "" && strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    }
    else if ($error === "") {

        $check = $conn->prepare(
            "SELECT resident_id FROM residents WHERE resident_number = ?"
        );
        $check->bind_param("s", $resident_number);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = "Resident number already exists.";
        }
        else {

            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO residents
                (
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
                    address,
                    password,
                    is_first_login
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
            ");

            $stmt->bind_param(
                "ssssssisissssss",
                $resident_number,
                $first_name,
                $middle_name,
                $last_name,
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
                $hashed
            );

            if ($stmt->execute()) {
                $success = "Resident account created successfully.";

                $resident_number = "";
                $first_name = "";
                $middle_name = "";
                $last_name = "";
                $extension_name = "";
                $civil_status = "";
                $birthday = "";
                $age = "";
                $occupation = "";
                $employer = "";
                $employer_address = "";
                $email = "";
                $contact_number = "";
                $address = "";
            } else {
                $error = "Unable to create account.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register New Resident</title>
<link rel="stylesheet" href="../assets/css/style.css">
<script>
(function(){
    var t=localStorage.getItem("theme");
    if(t==="dark")document.documentElement.setAttribute("data-theme","dark");
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

<?php include __DIR__ . "/../includes/staff_nav.php"; ?>

<div class="container">

<?php include __DIR__ . "/../includes/staff_topbar.php"; ?>

    <div class="card">

        <h2>Register New Resident</h2>

        <?php if ($error): ?>
            <div class="error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?= e($success) ?></div>
        <?php endif; ?>

        <form method="POST" class="register-form" id="residentRegistrationForm">

            <div class="register-form-grid">

                <div class="register-field">
                    <label>Resident Number</label>
                    <input
                        type="text"
                        name="resident_number"
                        required
                        value="<?= e($resident_number) ?>"
                    >
                </div>

                <div class="register-field">
                    <label>First Name</label>
                    <input
                        type="text"
                        name="first_name"
                        required
                        value="<?= e($first_name) ?>"
                    >
                </div>

                <div class="register-field">
                    <label>Middle Name</label>
                    <input
                        type="text"
                        name="middle_name"
                        value="<?= e($middle_name) ?>"
                    >
                </div>

                <div class="register-field">
                    <label>Last Name</label>
                    <input
                        type="text"
                        name="last_name"
                        required
                        value="<?= e($last_name) ?>"
                    >
                </div>

                <div class="register-field">
                    <label>Extension Name</label>
                    <input
                        type="text"
                        name="extension_name"
                        placeholder="Jr., Sr., III"
                        value="<?= e($extension_name) ?>"
                    >
                </div>

                <div class="register-field">
                    <label>Civil Status</label>
                    <select name="civil_status">
                        <option value="">Select Civil Status</option>
                        <option value="Single" <?= $civil_status === "Single" ? "selected" : "" ?>>Single</option>
                        <option value="Married" <?= $civil_status === "Married" ? "selected" : "" ?>>Married</option>
                        <option value="Widowed" <?= $civil_status === "Widowed" ? "selected" : "" ?>>Widowed</option>
                        <option value="Separated" <?= $civil_status === "Separated" ? "selected" : "" ?>>Separated</option>
                        <option value="Divorced" <?= $civil_status === "Divorced" ? "selected" : "" ?>>Divorced</option>
                    </select>
                </div>

                <div class="register-field">
                    <label>Birthday</label>
                    <input
                        type="date"
                        name="birthday"
                        id="birthday"
                        max="<?= date("Y-m-d") ?>"
                        value="<?= e($birthday) ?>"
                    >
                </div>

                <div class="register-field">
                    <label>Age</label>
                    <input
                        type="number"
                        name="age"
                        id="age"
                        value="<?= e($age) ?>"
                        readonly
                        min="0"
                    >
                </div>

                <div class="register-field">
                    <label>Occupation</label>
                    <input
                        type="text"
                        name="occupation"
                        value="<?= e($occupation) ?>"
                    >
                </div>

                <div class="register-field">
                    <label>Employer</label>
                    <input
                        type="text"
                        name="employer"
                        value="<?= e($employer) ?>"
                    >
                </div>

                <div class="register-field">
                    <label>Employer Address</label>
                    <input
                        type="text"
                        name="employer_address"
                        value="<?= e($employer_address) ?>"
                    >
                </div>

                <div class="register-field">
                    <label>Email</label>
                    <input
                        type="email"
                        name="email"
                        value="<?= e($email) ?>"
                    >
                </div>

                <div class="register-field">
                    <label>Contact Number</label>
                    <input
                        type="text"
                        name="contact_number"
                        value="<?= e($contact_number) ?>"
                    >
                </div>

                <div class="register-field">
                    <label>Address</label>
                    <input
                        type="text"
                        name="address"
                        value="<?= e($address) ?>"
                    >
                </div>

                <div class="register-field">
                    <label>Password</label>
                    <input
                        type="password"
                        name="password"
                        required
                        minlength="6"
                    >
                </div>

                <div class="register-field">
                    <label>Confirm Password</label>
                    <input
                        type="password"
                        name="confirm_password"
                        required
                        minlength="6"
                    >
                </div>

            </div>

            <button type="submit">Register</button>
            <a href="resident_management.php" class="btn btn-secondary">Cancel</a>

        </form>

    </div>

</div>

<script src="../assets/js/script.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const birthdayInput = document.getElementById("birthday");
    const ageInput = document.getElementById("age");

    function calculateAge() {

        if (!birthdayInput.value) {
            ageInput.value = "";
            return;
        }

        const birthday = new Date(birthdayInput.value + "T00:00:00");
        const today = new Date();

        let age = today.getFullYear() - birthday.getFullYear();

        const monthDifference = today.getMonth() - birthday.getMonth();

        if (
            monthDifference < 0 ||
            (
                monthDifference === 0 &&
                today.getDate() < birthday.getDate()
            )
        ) {
            age--;
        }

        ageInput.value = age >= 0 ? age : "";
    }

    birthdayInput.addEventListener("change", calculateAge);
    birthdayInput.addEventListener("input", calculateAge);

    calculateAge();
});
</script>

</body>
</html>