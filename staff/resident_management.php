<?php

require_once __DIR__ . "/../includes/functions.php";
require_staff_login();


/*
|--------------------------------------------------------------------------
| Messages
|--------------------------------------------------------------------------
*/

$success = isset($_GET["success"])
    ? trim($_GET["success"])
    : "";

$error = isset($_GET["error"])
    ? trim($_GET["error"])
    : "";


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

$search = isset($_GET["search"])
    ? trim($_GET["search"])
    : "";


/*
|--------------------------------------------------------------------------
| Load Residents
|--------------------------------------------------------------------------
*/

if ($search !== "") {

    $like = "%" . $search . "%";

    $stmt = $conn->prepare("
        SELECT
            resident_id,
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
            photo
        FROM residents
        WHERE
            resident_number LIKE ?
            OR first_name LIKE ?
            OR middle_name LIKE ?
            OR last_name LIKE ?
            OR extension_name LIKE ?
            OR email LIKE ?
            OR contact_number LIKE ?
        ORDER BY
            last_name,
            first_name
    ");


    if (!$stmt) {

        die(
            "Unable to prepare resident search."
        );

    }


    $stmt->bind_param(
        "sssssss",
        $like,
        $like,
        $like,
        $like,
        $like,
        $like,
        $like
    );


    $stmt->execute();

    $residents =
        $stmt->get_result();

}
else {

    $residents = $conn->query("
        SELECT
            resident_id,
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
            photo
        FROM residents
        ORDER BY
            last_name,
            first_name
    ");


    if (!$residents) {

        die(
            "Unable to load residents."
        );

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
    Resident Management
</title>


<link
    rel="stylesheet"
    href="../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../assets/css/style.css") ?>"
>


<script>

(function () {

    var theme =
        localStorage.getItem("theme");

    if (theme === "dark") {

        document.documentElement.setAttribute(
            "data-theme",
            "dark"
        );

    }

})();

</script>


<script>

(function () {

    try {

        if (
            localStorage.getItem(
                "sidebarCollapsed"
            ) === "true"
        ) {

            document.documentElement.setAttribute(
                "data-sidebar",
                "collapsed"
            );

        }

    }
    catch (e) {}

})();

</script>


<style>

/*
|--------------------------------------------------------------------------
| Resident Management
|--------------------------------------------------------------------------
*/

.resident-management-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 16px;

    flex-wrap: wrap;

    margin-bottom: 18px;

}


.resident-management-header h2 {

    margin: 0;

}


.resident-management-actions {

    display: flex;

    align-items: center;

    gap: 10px;

    flex-wrap: wrap;

}


.resident-management-actions .btn {

    margin-top: 0;

}


.resident-search-form {

    margin-bottom: 16px;

}


.resident-action-column {

    min-width: 150px;

    text-align: center;

}


.resident-actions {

    display: flex;

    justify-content: center;

    align-items: center;

    gap: 8px;

    flex-wrap: wrap;

}


.resident-actions .btn {

    margin-top: 0;

}


.delete-resident-form {

    display: inline;

    margin: 0;

}


.delete-resident-button {

    margin-top: 0;

    cursor: pointer;

}


.edit-resident-btn {

    white-space: nowrap;

}


.reset-password-btn {

    white-space: nowrap;

}


@media (max-width: 900px) {

    .resident-management-header {

        align-items: flex-start;

    }

    .resident-management-actions {

        width: 100%;

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


<div class="card card-resident">


    <div class="resident-management-header">


        <h2>
            Registered Residents
        </h2>


        <div
            class="resident-management-actions"
        >


            <!--
            ----------------------------------------------------------
            REGISTER NEW RESIDENT
            ----------------------------------------------------------
            -->

            <a
                class="btn"
                href="register.php"
            >
                + Register New Resident
            </a>


            <!--
            ----------------------------------------------------------
            VIEW UPDATED RECORDS
            ----------------------------------------------------------
            -->

            <a
                class="btn"
                href="resident_update_history.php"
            >
                View Updated Records
            </a>


        </div>


    </div>


    <?php if ($success !== ""): ?>

        <div class="success">

            <?= e($success) ?>

        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="error">

            <?= e($error) ?>

        </div>

    <?php endif; ?>


    <form
        method="GET"
        class="resident-search-form"
    >

        <input
            type="text"
            name="search"
            placeholder="Search by name, resident number, email, or contact"
            value="<?= e($search) ?>"
        >

    </form>


    <div class="table-scroll">


        <table>


            <thead>

                <tr>

                    <th>
                        Resident Number
                    </th>

                    <th>
                        Name
                    </th>

                    <th>
                        Civil Status
                    </th>

                    <th>
                        Birthday
                    </th>

                    <th>
                        Age
                    </th>

                    <th>
                        Occupation
                    </th>

                    <th>
                        Employer
                    </th>

                    <th>
                        Email
                    </th>

                    <th>
                        Contact
                    </th>

                    <th>
                        Address
                    </th>

                    <th class="resident-action-column">
                        Actions
                    </th>

                </tr>

            </thead>


            <tbody>


            <?php if (
                $residents &&
                $residents->num_rows > 0
            ): ?>


                <?php while (
                    $r =
                    $residents->fetch_assoc()
                ): ?>


                    <?php

                    $full_name = trim(
                        $r["first_name"] .
                        " " .
                        ($r["middle_name"] ?? "") .
                        " " .
                        $r["last_name"] .
                        " " .
                        ($r["extension_name"] ?? "")
                    );


                    if (
                        $full_name === ""
                    ) {

                        $full_name =
                            $r["resident_number"];

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Delete Confirmation
                    |--------------------------------------------------------------------------
                    */

                    $delete_message =
                        "Are you sure you want to permanently delete " .
                        $full_name .
                        "?\n\n" .
                        "This will delete the resident and all associated " .
                        "information, including spouse, children, parents, " .
                        "character references, update history, and survey responses.\n\n" .
                        "This action cannot be undone.";

                    ?>


                    <tr>


                        <td>

                            <?= e(
                                $r["resident_number"]
                            ) ?>

                        </td>


                        <td>

                            <?= e(
                                $full_name
                            ) ?>

                        </td>


                        <td>

                            <?=
                                !empty(
                                    $r["civil_status"]
                                )
                                    ? e(
                                        $r["civil_status"]
                                    )
                                    : "—"
                            ?>

                        </td>


                        <td>

                            <?=
                                !empty(
                                    $r["birthday"]
                                )
                                    ? e(
                                        date(
                                            "M d, Y",
                                            strtotime(
                                                $r["birthday"]
                                            )
                                        )
                                    )
                                    : "—"
                            ?>

                        </td>


                        <td>

                            <?=
                                $r["age"] !== null &&
                                $r["age"] !== ""
                                    ? e(
                                        $r["age"]
                                    )
                                    : "—"
                            ?>

                        </td>


                        <td>

                            <?=
                                !empty(
                                    $r["occupation"]
                                )
                                    ? e(
                                        $r["occupation"]
                                    )
                                    : "—"
                            ?>

                        </td>


                        <td>

                            <?=
                                !empty(
                                    $r["employer"]
                                )
                                    ? e(
                                        $r["employer"]
                                    )
                                    : "—"
                            ?>

                        </td>


                        <td>

                            <?=
                                !empty(
                                    $r["email"]
                                )
                                    ? e(
                                        $r["email"]
                                    )
                                    : "—"
                            ?>

                        </td>


                        <td>

                            <?=
                                !empty(
                                    $r["contact_number"]
                                )
                                    ? e(
                                        $r["contact_number"]
                                    )
                                    : "—"
                            ?>

                        </td>


                        <td>

                            <?=
                                !empty(
                                    $r["address"]
                                )
                                    ? e(
                                        $r["address"]
                                    )
                                    : "—"
                            ?>

                        </td>


                        <td
                            class="resident-action-column"
                        >


                            <div
                                class="resident-actions"
                            >


                                <!--
                                --------------------------------------------------
                                EDIT
                                --------------------------------------------------
                                -->

                                <a
                                    class="btn edit-resident-btn"
                                    href="resident_edit.php?resident_id=<?= (int) $r["resident_id"] ?>"
                                >
                                    Edit
                                </a>


                                <!--
                                --------------------------------------------------
                                RESET PASSWORD
                                --------------------------------------------------
                                -->

                                <a
                                    class="btn reset-password-btn"
                                    href="resident_reset_password.php?resident_id=<?= (int) $r["resident_id"] ?>"
                                >
                                    Reset Password
                                </a>


                                <!--
                                --------------------------------------------------
                                DELETE
                                --------------------------------------------------
                                -->

                                <form
                                    method="POST"
                                    action="resident_delete.php"
                                    class="delete-resident-form"
                                    onsubmit="return confirm(<?= json_encode($delete_message) ?>);"
                                >


                                    <input
                                        type="hidden"
                                        name="resident_id"
                                        value="<?= (int) $r["resident_id"] ?>"
                                    >


                                    <button
                                        type="submit"
                                        class="btn btn-danger delete-resident-button"
                                    >
                                        Delete
                                    </button>


                                </form>


                            </div>


                        </td>


                    </tr>


                <?php endwhile; ?>


            <?php else: ?>


                <tr>

                    <td
                        colspan="11"
                        style="text-align:center;"
                    >

                        No residents found.

                    </td>

                </tr>


            <?php endif; ?>


            </tbody>


        </table>


    </div>


</div>


</div>


<script src="../assets/js/script.js"></script>


</body>

</html>