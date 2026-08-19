<?php
require_once __DIR__ . "/../includes/functions.php";
require_staff_login();

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $resident_number = trim($_POST["resident_number"]);
    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);
    $email = trim($_POST["email"]);
    $contact_number = trim($_POST["contact_number"]);
    $address = trim($_POST["address"]);

    if ($resident_number === "" || $first_name === "" || $last_name === "") {
        $error = "Resident number, first name, and last name are required.";
    }
    else {

        $check = $conn->prepare(
            "SELECT resident_id FROM residents WHERE resident_number = ?"
        );
        $check->bind_param("s", $resident_number);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = "Resident number already exists.";
        }
        else {

            // Per the resident portal rules, the default password is
            // always the resident's own Resident Number, and they must
            // change it the first time they log in.
            $hashed = password_hash($resident_number, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO residents
                (
                    resident_number,
                    first_name,
                    last_name,
                    email,
                    contact_number,
                    address,
                    password,
                    is_first_login
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ");

            $stmt->bind_param(
                "sssssss",
                $resident_number,
                $first_name,
                $last_name,
                $email,
                $contact_number,
                $address,
                $hashed
            );

            if ($stmt->execute()) {
                $success = "Resident account created successfully. Default password is their Resident Number (" . $resident_number . ").";
                $resident_number = $first_name = $last_name = $email = $contact_number = $address = "";
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
<script>(function(){var t=localStorage.getItem("theme");if(t==="dark")document.documentElement.setAttribute("data-theme","dark");})();</script>
<script>(function(){try{if(localStorage.getItem("sidebarCollapsed")==="true")document.documentElement.setAttribute("data-sidebar","collapsed");}catch(e){}})();</script>
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

        <form method="POST" class="register-form">

            <div class="register-form-grid">
                <div class="register-field">
                    <label>Resident Number</label>
                    <input type="text" name="resident_number" required value="<?= isset($resident_number) ? e($resident_number) : "" ?>">
                </div>

                <div class="register-field">
                    <label>First Name</label>
                    <input type="text" name="first_name" required value="<?= isset($first_name) ? e($first_name) : "" ?>">
                </div>

                <div class="register-field">
                    <label>Last Name</label>
                    <input type="text" name="last_name" required value="<?= isset($last_name) ? e($last_name) : "" ?>">
                </div>

                <div class="register-field">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= isset($email) ? e($email) : "" ?>">
                </div>

                <div class="register-field">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" value="<?= isset($contact_number) ? e($contact_number) : "" ?>">
                </div>

                <div class="register-field">
                    <label>Address</label>
                    <input type="text" name="address" value="<?= isset($address) ? e($address) : "" ?>">
                </div>
            </div>

            <p style="font-size:12.5px; color:#667; margin:14px 0 0;">
                The resident's default password will be set to their Resident Number, and they'll be required to
                change it the first time they log in. Full personal, family, and contact details (spouse, children,
                references, photo, etc.) can be filled in afterward from the resident's own Profile page, or from
                <a href="resident_management.php">Resident Management &rarr; Edit</a>.
            </p>

            <button type="submit" style="margin-top:16px;">Register</button>
            <a href="resident_management.php" class="btn btn-secondary">Cancel</a>

        </form>

       

    </div>
</div>
<script src="../assets/js/script.js"></script>
</body>
</html> 