<?php

require_once __DIR__ . "/../includes/functions.php";
require_staff_login();


/*
|--------------------------------------------------------------------------
| Variables
|--------------------------------------------------------------------------
*/

$error = "";
$resident = null;


/*
|--------------------------------------------------------------------------
| Get Resident ID
|--------------------------------------------------------------------------
*/

$resident_id = filter_input(
    INPUT_GET,
    "resident_id",
    FILTER_VALIDATE_INT
);


/*
|--------------------------------------------------------------------------
| Validate Resident ID
|--------------------------------------------------------------------------
*/

if (
    $resident_id === false ||
    $resident_id === null ||
    $resident_id <= 0
) {

    redirect(
        "resident_management.php?error=" .
        urlencode(
            "Invalid resident ID."
        )
    );

}


/*
|--------------------------------------------------------------------------
| Load Resident
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        resident_id,
        resident_number,
        first_name,
        middle_name,
        last_name,
        extension_name,
        email
    FROM residents
    WHERE resident_id = ?
    LIMIT 1
");


if (!$stmt) {

    redirect(
        "resident_management.php?error=" .
        urlencode(
            "Unable to load resident."
        )
    );

}


$stmt->bind_param(
    "i",
    $resident_id
);


if (!$stmt->execute()) {

    $stmt->close();

    redirect(
        "resident_management.php?error=" .
        urlencode(
            "Unable to load resident."
        )
    );

}


$result = $stmt->get_result();


if ($result->num_rows !== 1) {

    $stmt->close();

    redirect(
        "resident_management.php?error=" .
        urlencode(
            "Resident not found."
        )
    );

}


$resident = $result->fetch_assoc();

$stmt->close();


/*
|--------------------------------------------------------------------------
| Build Resident Name
|--------------------------------------------------------------------------
*/

$full_name = trim(
    $resident["first_name"] .
    " " .
    ($resident["middle_name"] ?? "") .
    " " .
    $resident["last_name"] .
    " " .
    ($resident["extension_name"] ?? "")
);


if ($full_name === "") {

    $full_name =
        $resident["resident_number"];

}


/*
|--------------------------------------------------------------------------
| Process Password Reset
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {


    /*
    |--------------------------------------------------------------------------
    | Make sure the POST resident ID matches the page resident
    |--------------------------------------------------------------------------
    */

    $posted_resident_id = filter_input(
        INPUT_POST,
        "resident_id",
        FILTER_VALIDATE_INT
    );


    if (
        $posted_resident_id === false ||
        $posted_resident_id === null ||
        $posted_resident_id <= 0 ||
        (int) $posted_resident_id !== (int) $resident_id
    ) {

        $error =
            "Invalid resident ID.";

    }
    else {


        /*
        |--------------------------------------------------------------------------
        | Get Passwords
        |--------------------------------------------------------------------------
        */

        $new_password =
            $_POST["new_password"] ?? "";

        $confirm_password =
            $_POST["confirm_password"] ?? "";


        /*
        |--------------------------------------------------------------------------
        | Validate Password
        |--------------------------------------------------------------------------
        */

        if (
            $new_password === "" ||
            $confirm_password === ""
        ) {

            $error =
                "Both password fields are required.";

        }
        elseif (
            strlen($new_password) < 6
        ) {

            $error =
                "Password must be at least 6 characters long.";

        }
        elseif (
            strlen($new_password) > 255
        ) {

            $error =
                "Password must not exceed 255 characters.";

        }
        elseif (
            $new_password !== $confirm_password
        ) {

            $error =
                "Passwords do not match.";

        }
        else {


            /*
            |--------------------------------------------------------------------------
            | Hash Password
            |--------------------------------------------------------------------------
            |
            | Never store the password as plain text.
            |
            */

            $hashed_password =
                password_hash(
                    $new_password,
                    PASSWORD_DEFAULT
                );


            if (
                $hashed_password === false
            ) {

                $error =
                    "Unable to secure the new password.";

            }
            else {


                /*
                |--------------------------------------------------------------------------
                | Start Transaction
                |--------------------------------------------------------------------------
                */

                try {

                    $conn->begin_transaction();


                    /*
                    |--------------------------------------------------------------------------
                    | Update Existing Resident Account
                    |--------------------------------------------------------------------------
                    |
                    | This updates the existing resident.
                    | It does NOT create another resident account.
                    |
                    */

                    $update = $conn->prepare("
                        UPDATE residents
                        SET
                            password = ?,
                            is_first_login = 0
                        WHERE resident_id = ?
                    ");


                    if (!$update) {

                        throw new Exception(
                            "Unable to prepare password update."
                        );

                    }


                    $update->bind_param(
                        "si",
                        $hashed_password,
                        $resident_id
                    );


                    if (
                        !$update->execute()
                    ) {

                        $update->close();

                        throw new Exception(
                            "Unable to update the resident password."
                        );

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Make Sure Resident Was Actually Updated
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $update->affected_rows < 1
                    ) {

                        $update->close();

                        throw new Exception(
                            "Resident password was not changed."
                        );

                    }


                    $update->close();


                    /*
                    |--------------------------------------------------------------------------
                    | Record Password Reset in Update History
                    |--------------------------------------------------------------------------
                    |
                    | IMPORTANT:
                    |
                    | We NEVER store the actual password in the history.
                    |
                    | The existing database uses:
                    |
                    | resident_id
                    | updated_by_type
                    | updated_by_id
                    | update_section
                    | old_data
                    | new_data
                    |
                    */


                    $history_type =
                        "staff";


                    $staff_id =
                        isset(
                            $_SESSION["staff_id"]
                        )
                            ? (int)
                                $_SESSION["staff_id"]
                            : null;


                    $history_section =
                        "password";


                    $history_old =
                        "Password reset";


                    $history_new =
                        "Password reset by staff";


                    $history_stmt =
                        $conn->prepare("
                            INSERT INTO resident_update_history
                            (
                                resident_id,
                                updated_by_type,
                                updated_by_id,
                                update_section,
                                old_data,
                                new_data
                            )
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");


                    if (!$history_stmt) {

                        throw new Exception(
                            "Unable to prepare password reset history."
                        );

                    }


                    $history_stmt->bind_param(
                        "isisss",
                        $resident_id,
                        $history_type,
                        $staff_id,
                        $history_section,
                        $history_old,
                        $history_new
                    );


                    if (
                        !$history_stmt->execute()
                    ) {

                        $history_stmt->close();

                        throw new Exception(
                            "Unable to record password reset history."
                        );

                    }


                    $history_stmt->close();


                    /*
                    |--------------------------------------------------------------------------
                    | Commit
                    |--------------------------------------------------------------------------
                    */

                    $conn->commit();


                    /*
                    |--------------------------------------------------------------------------
                    | Redirect After Successful Reset
                    |--------------------------------------------------------------------------
                    */

                    redirect(
                        "resident_management.php?success=" .
                        urlencode(
                            "Password for " .
                            $full_name .
                            " was successfully reset."
                        )
                    );

                }
                catch (Throwable $exception) {


                    /*
                    |--------------------------------------------------------------------------
                    | Rollback
                    |--------------------------------------------------------------------------
                    */

                    try {

                        $conn->rollback();

                    }
                    catch (Throwable $rollback_exception) {}

                    $error =
                        "Unable to reset the resident password. " .
                        "No changes were made.";

                }

            }

        }

    }

}

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Reset Resident Password
</title>


<link
    rel="stylesheet"
    href="../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../assets/css/style.css") ?>"
>


<style>

.reset-password-page {

    max-width: 700px;

    margin: 0 auto;

}


.reset-password-header {

    margin-bottom: 20px;

}


.reset-password-header h1 {

    margin-bottom: 6px;

}


.reset-password-header p {

    opacity: .75;

}


.resident-account-info {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 16px;

    margin-bottom: 24px;

}


.account-info-item {

    padding: 14px;

    border: 1px solid rgba(0,0,0,.15);

    border-radius: 8px;

}


.account-info-label {

    display: block;

    font-size: 13px;

    opacity: .65;

    margin-bottom: 4px;

}


.account-info-value {

    font-weight: 600;

}


.password-field {

    margin-bottom: 18px;

}


.password-field label {

    display: block;

    margin-bottom: 7px;

    font-weight: 600;

}


.password-field input {

    width: 100%;

    box-sizing: border-box;

}


.password-help {

    margin-top: 6px;

    font-size: 13px;

    opacity: .7;

}


.reset-actions {

    display: flex;

    gap: 10px;

    margin-top: 24px;

}


@media (max-width: 600px) {

    .resident-account-info {

        grid-template-columns: 1fr;

    }

    .reset-actions {

        flex-direction: column;

    }

}

</style>

</head>


<body>


<?php

include __DIR__ . "/../includes/staff_nav.php";

?>


<div class="container">


<?php

include __DIR__ . "/../includes/staff_topbar.php";

?>


<div class="reset-password-page">


    <div class="reset-password-header">

        <h1>
            Reset Resident Password
        </h1>

        <p>
            Set a new password for this resident account.
        </p>

    </div>


    <div class="card">


        <?php if ($error !== ""): ?>

            <div class="error">

                <?= e($error) ?>

            </div>

        <?php endif; ?>


        <div class="resident-account-info">


            <div class="account-info-item">

                <span class="account-info-label">
                    Resident
                </span>

                <span class="account-info-value">

                    <?= e($full_name) ?>

                </span>

            </div>


            <div class="account-info-item">

                <span class="account-info-label">
                    Resident Number
                </span>

                <span class="account-info-value">

                    <?= e(
                        $resident["resident_number"]
                    ) ?>

                </span>

            </div>


            <div class="account-info-item">

                <span class="account-info-label">
                    Email
                </span>

                <span class="account-info-value">

                    <?=
                        !empty(
                            $resident["email"]
                        )
                            ? e(
                                $resident["email"]
                            )
                            : "—"
                    ?>

                </span>

            </div>


        </div>


        <form
            method="POST"
            action="resident_reset_password.php?resident_id=<?= (int) $resident_id ?>"
        >


            <input
                type="hidden"
                name="resident_id"
                value="<?= (int) $resident_id ?>"
            >


            <div class="password-field">

                <label for="new_password">
                    New Password
                </label>

                <input
                    type="password"
                    id="new_password"
                    name="new_password"
                    minlength="6"
                    maxlength="255"
                    autocomplete="new-password"
                    required
                >

                <div class="password-help">
                    Password must be at least 6 characters.
                </div>

            </div>


            <div class="password-field">

                <label for="confirm_password">
                    Confirm New Password
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    minlength="6"
                    maxlength="255"
                    autocomplete="new-password"
                    required
                >

            </div>


            <div class="reset-actions">


                <button
                    type="submit"
                    class="btn"
                >
                    Reset Password
                </button>


                <a
                    href="resident_management.php"
                    class="btn btn-secondary"
                >
                    Cancel
                </a>


            </div>


        </form>


    </div>


</div>


</div>


<script src="../assets/js/script.js"></script>

</body>

</html>