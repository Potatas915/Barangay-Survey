<?php

require_once __DIR__ . "/../includes/functions.php";
require_staff_login();

$search = isset($_GET["search"])
    ? trim($_GET["search"])
    : "";

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
        ORDER BY last_name, first_name
    ");

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

    $residents = $stmt->get_result();

} else {

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
        ORDER BY last_name, first_name
    ");

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

<title>Resident Management</title>

<link
    rel="stylesheet"
    href="../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../assets/css/style.css") ?>"
>

<script>

(function () {

    var t =
        localStorage.getItem("theme");

    if (t === "dark") {

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

.resident-management-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 16px;

    flex-wrap: wrap;

}


.resident-search-form {

    margin-bottom: 16px;

}


.resident-action-column {

    min-width: 90px;

    text-align: center;

}


.edit-resident-btn {

    white-space: nowrap;

}


.resident-photo-thumb {

    width: 45px;

    height: 55px;

    object-fit: cover;

    border: 2px solid #111;

}


.no-photo {

    font-size: 12px;

    opacity: 0.6;

}


@media (max-width: 900px) {

    .resident-management-header {

        align-items: flex-start;

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


        <a
            class="btn"
            href="register.php"
        >
            + Register New Resident
        </a>

    </div>


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
                        Action
                    </th>

                </tr>

            </thead>


            <tbody>

            <?php if (
                $residents &&
                $residents->num_rows > 0
            ): ?>

                <?php while (
                    $r = $residents->fetch_assoc()
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
                            <?= e(
                                $r["civil_status"] ?? ""
                            ) ?>
                        </td>


                        <td>

                            <?= !empty(
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

                            <?= $r["age"] !== null &&
                                $r["age"] !== ""
                                    ? e(
                                        $r["age"]
                                    )
                                    : "—"
                            ?>

                        </td>


                        <td>

                            <?= !empty(
                                $r["occupation"]
                            )
                                ? e(
                                    $r["occupation"]
                                )
                                : "—"
                            ?>

                        </td>


                        <td>

                            <?= !empty(
                                $r["employer"]
                            )
                                ? e(
                                    $r["employer"]
                                )
                                : "—"
                            ?>

                        </td>


                        <td>

                            <?= !empty(
                                $r["email"]
                            )
                                ? e(
                                    $r["email"]
                                )
                                : "—"
                            ?>

                        </td>


                        <td>

                            <?= !empty(
                                $r["contact_number"]
                            )
                                ? e(
                                    $r["contact_number"]
                                )
                                : "—"
                            ?>

                        </td>


                        <td>

                            <?= !empty(
                                $r["address"]
                            )
                                ? e(
                                    $r["address"]
                                )
                                : "—"
                            ?>

                        </td>


                        <td class="resident-action-column">

                            <a
                                class="btn edit-resident-btn"
                                href="resident_edit.php?resident_id=<?= (int) $r["resident_id"] ?>"
                            >
                                Edit
                            </a>

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