<?php

require_once __DIR__ . "/../includes/functions.php";
require_staff_login();


/*
|--------------------------------------------------------------------------
| Survey Summary Report
|--------------------------------------------------------------------------
*/

$reports = $conn->query("
    SELECT
        s.survey_id,
        s.title,
        s.start_date,
        s.end_date,
        s.status,

        (
            SELECT COUNT(*)
            FROM responses r
            WHERE r.survey_id = s.survey_id
        ) AS response_count,

        (
            SELECT COUNT(*)
            FROM survey_questions q
            WHERE q.survey_id = s.survey_id
        ) AS question_count

    FROM surveys s

    ORDER BY
        s.created_at DESC
");


/*
|--------------------------------------------------------------------------
| Resident Report Helper
|--------------------------------------------------------------------------
*/

function resident_report_escape($value)
{
    return e(
        $value === null
            ? ""
            : (string) $value
    );
}


/*
|--------------------------------------------------------------------------
| Resident Report Filters
|--------------------------------------------------------------------------
*/

$civil_status_filter =
    isset($_GET["civil_status"])
    ? trim($_GET["civil_status"])
    : "";

$age_group_filter =
    isset($_GET["age_group"])
    ? trim($_GET["age_group"])
    : "";

$occupation_filter =
    isset($_GET["occupation"])
    ? trim($_GET["occupation"])
    : "";

$gender_filter =
    isset($_GET["gender"])
    ? trim($_GET["gender"])
    : "";


/*
|--------------------------------------------------------------------------
| Allowed Filters
|--------------------------------------------------------------------------
*/

$allowed_civil_statuses = [
    "Single",
    "Married",
    "Widowed",
    "Separated",
    "Divorced"
];


$allowed_age_groups = [
    "0-17",
    "18-30",
    "31-45",
    "46-60",
    "61+"
];


$allowed_genders = [
    "Male",
    "Female"
];


/*
|--------------------------------------------------------------------------
| Validate Filters
|--------------------------------------------------------------------------
*/

if (
    $civil_status_filter !== "" &&
    !in_array(
        $civil_status_filter,
        $allowed_civil_statuses,
        true
    )
) {

    $civil_status_filter = "";
}


if (
    $age_group_filter !== "" &&
    !in_array(
        $age_group_filter,
        $allowed_age_groups,
        true
    )
) {

    $age_group_filter = "";
}


if (
    $gender_filter !== "" &&
    !in_array(
        $gender_filter,
        $allowed_genders,
        true
    )
) {

    $gender_filter = "";
}


/*
|--------------------------------------------------------------------------
| Build Resident WHERE Clause
|--------------------------------------------------------------------------
*/

$where = [
    "1 = 1"
];

$params = [];

$types = "";


/*
|--------------------------------------------------------------------------
| Civil Status
|--------------------------------------------------------------------------
*/

if (
    $civil_status_filter !== ""
) {

    $where[] =
        "civil_status = ?";

    $params[] =
        $civil_status_filter;

    $types .= "s";
}


/*
|--------------------------------------------------------------------------
| Gender
|--------------------------------------------------------------------------
*/

if (
    $gender_filter !== ""
) {

    $where[] =
        "gender = ?";

    $params[] =
        $gender_filter;

    $types .= "s";
}


/*
|--------------------------------------------------------------------------
| Age Group
|--------------------------------------------------------------------------
*/

if (
    $age_group_filter !== ""
) {

    switch ($age_group_filter) {

        case "0-17":

            $where[] =
                "age BETWEEN 0 AND 17";

            break;


        case "18-30":

            $where[] =
                "age BETWEEN 18 AND 30";

            break;


        case "31-45":

            $where[] =
                "age BETWEEN 31 AND 45";

            break;


        case "46-60":

            $where[] =
                "age BETWEEN 46 AND 60";

            break;


        case "61+":

            $where[] =
                "age >= 61";

            break;
    }
}


/*
|--------------------------------------------------------------------------
| Occupation
|--------------------------------------------------------------------------
*/

if (
    $occupation_filter !== ""
) {

    $where[] =
        "occupation = ?";

    $params[] =
        $occupation_filter;

    $types .= "s";
}


$where_sql =
    implode(
        " AND ",
        $where
    );


/*
|--------------------------------------------------------------------------
| Prepared Resident Report Query
|--------------------------------------------------------------------------
*/

function resident_report_query(
    $conn,
    $sql,
    $types = "",
    $params = []
) {

    $stmt =
        $conn->prepare(
            $sql
        );


    if (!$stmt) {

        die("Unable to prepare resident report.");
    }


    if (
        $types !== "" &&
        count($params) > 0
    ) {

        $stmt->bind_param(
            $types,
            ...$params
        );
    }


    if (
        !$stmt->execute()
    ) {

        die("Unable to load resident report.");
    }


    return $stmt->get_result();
}


/*
|--------------------------------------------------------------------------
| Total Residents
|--------------------------------------------------------------------------
*/

$total_result =
    resident_report_query(
        $conn,

        "
            SELECT
                COUNT(*) AS total

            FROM residents

            WHERE $where_sql
        ",

        $types,
        $params
    );


$total_row =
    $total_result->fetch_assoc();


$total_residents =
    (int) (
        $total_row["total"] ?? 0
    );


/*
|--------------------------------------------------------------------------
| Age Distribution
|--------------------------------------------------------------------------
*/

$age_result =
    resident_report_query(
        $conn,

        "
            SELECT

                SUM(
                    CASE
                        WHEN age BETWEEN 0 AND 17
                        THEN 1
                        ELSE 0
                    END
                ) AS age_0_17,

                SUM(
                    CASE
                        WHEN age BETWEEN 18 AND 30
                        THEN 1
                        ELSE 0
                    END
                ) AS age_18_30,

                SUM(
                    CASE
                        WHEN age BETWEEN 31 AND 45
                        THEN 1
                        ELSE 0
                    END
                ) AS age_31_45,

                SUM(
                    CASE
                        WHEN age BETWEEN 46 AND 60
                        THEN 1
                        ELSE 0
                    END
                ) AS age_46_60,

                SUM(
                    CASE
                        WHEN age >= 61
                        THEN 1
                        ELSE 0
                    END
                ) AS age_61_plus,

                SUM(
                    CASE
                        WHEN age IS NULL
                        OR age = ''
                        THEN 1
                        ELSE 0
                    END
                ) AS age_not_provided

            FROM residents

            WHERE $where_sql
        ",

        $types,
        $params
    );


$age_data =
    $age_result->fetch_assoc();


$age_0_17 =
    (int) (
        $age_data["age_0_17"] ?? 0
    );


$age_18_30 =
    (int) (
        $age_data["age_18_30"] ?? 0
    );


$age_31_45 =
    (int) (
        $age_data["age_31_45"] ?? 0
    );


$age_46_60 =
    (int) (
        $age_data["age_46_60"] ?? 0
    );


$age_61_plus =
    (int) (
        $age_data["age_61_plus"] ?? 0
    );


$age_not_provided =
    (int) (
        $age_data["age_not_provided"] ?? 0
    );


/*
|--------------------------------------------------------------------------
| Civil Status Distribution
|--------------------------------------------------------------------------
*/

$civil_result =
    resident_report_query(
        $conn,

        "
            SELECT
                civil_status,
                COUNT(*) AS total

            FROM residents

            WHERE $where_sql

            GROUP BY
                civil_status

            ORDER BY
                total DESC
        ",

        $types,
        $params
    );


$civil_data = [];


while (
    $row =
    $civil_result->fetch_assoc()
) {

    $status =
        trim(
            (string) (
                $row["civil_status"] ?? ""
            )
        );


    if (
        $status === ""
    ) {

        $status =
            "Not provided";
    }


    $civil_data[] = [

        "label" =>
        $status,

        "total" =>
        (int) $row["total"]

    ];
}


/*
|--------------------------------------------------------------------------
| Occupation Distribution
|--------------------------------------------------------------------------
*/

$occupation_result =
    resident_report_query(
        $conn,

        "
            SELECT
                occupation,
                COUNT(*) AS total

            FROM residents

            WHERE $where_sql

            GROUP BY
                occupation

            ORDER BY
                total DESC

            LIMIT 10
        ",

        $types,
        $params
    );


$occupation_data = [];


while (
    $row =
    $occupation_result->fetch_assoc()
) {

    $occupation =
        trim(
            (string) (
                $row["occupation"] ?? ""
            )
        );


    if (
        $occupation === ""
    ) {

        $occupation =
            "Not provided";
    }


    $occupation_data[] = [

        "label" =>
        $occupation,

        "total" =>
        (int) $row["total"]

    ];
}


/*
|--------------------------------------------------------------------------
| Employer Distribution
|--------------------------------------------------------------------------
*/

$employer_result =
    resident_report_query(
        $conn,

        "
            SELECT
                employer,
                COUNT(*) AS total

            FROM residents

            WHERE $where_sql

            GROUP BY
                employer

            ORDER BY
                total DESC

            LIMIT 10
        ",

        $types,
        $params
    );


$employer_data = [];


while (
    $row =
    $employer_result->fetch_assoc()
) {

    $employer =
        trim(
            (string) (
                $row["employer"] ?? ""
            )
        );


    if (
        $employer === ""
    ) {

        $employer =
            "Not provided";
    }


    $employer_data[] = [

        "label" =>
        $employer,

        "total" =>
        (int) $row["total"]

    ];
}


/*
|--------------------------------------------------------------------------
| Address Distribution
|--------------------------------------------------------------------------
*/

$address_result =
    resident_report_query(
        $conn,

        "
            SELECT
                address,
                COUNT(*) AS total

            FROM residents

            WHERE $where_sql

            GROUP BY
                address

            ORDER BY
                total DESC

            LIMIT 10
        ",

        $types,
        $params
    );


$address_data = [];


while (
    $row =
    $address_result->fetch_assoc()
) {

    $address =
        trim(
            (string) (
                $row["address"] ?? ""
            )
        );


    if (
        $address === ""
    ) {

        $address =
            "Not provided";
    }


    $address_data[] = [

        "label" =>
        $address,

        "total" =>
        (int) $row["total"]

    ];
}


/*
|--------------------------------------------------------------------------
| Additional Summary
|--------------------------------------------------------------------------
*/

$summary_result =
    resident_report_query(
        $conn,

        "
            SELECT

                SUM(
                    CASE
                        WHEN birthday IS NOT NULL
                        AND birthday <> ''
                        THEN 1
                        ELSE 0
                    END
                ) AS with_birthday,

                SUM(
                    CASE
                        WHEN occupation IS NOT NULL
                        AND TRIM(occupation) <> ''
                        THEN 1
                        ELSE 0
                    END
                ) AS with_occupation,

                SUM(
                    CASE
                        WHEN employer IS NOT NULL
                        AND TRIM(employer) <> ''
                        THEN 1
                        ELSE 0
                    END
                ) AS with_employer,

                SUM(
                    CASE
                        WHEN email IS NOT NULL
                        AND TRIM(email) <> ''
                        THEN 1
                        ELSE 0
                    END
                ) AS with_email,

                SUM(
                    CASE
                        WHEN contact_number IS NOT NULL
                        AND TRIM(contact_number) <> ''
                        THEN 1
                        ELSE 0
                    END
                ) AS with_contact,

                SUM(
                    CASE
                        WHEN address IS NOT NULL
                        AND TRIM(address) <> ''
                        THEN 1
                        ELSE 0
                    END
                ) AS with_address

            FROM residents

            WHERE $where_sql
        ",

        $types,
        $params
    );


$summary_data =
    $summary_result->fetch_assoc();


$with_birthday =
    (int) (
        $summary_data["with_birthday"] ?? 0
    );


$with_occupation =
    (int) (
        $summary_data["with_occupation"] ?? 0
    );


$with_employer =
    (int) (
        $summary_data["with_employer"] ?? 0
    );


$with_email =
    (int) (
        $summary_data["with_email"] ?? 0
    );


$with_contact =
    (int) (
        $summary_data["with_contact"] ?? 0
    );


$with_address =
    (int) (
        $summary_data["with_address"] ?? 0
    );


/*
|--------------------------------------------------------------------------
| Resident Directory
|--------------------------------------------------------------------------
*/

$directory_result =
    resident_report_query(
        $conn,

        "
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

                email,

                contact_number,

                address

            FROM residents

            WHERE $where_sql

            ORDER BY
                last_name ASC,
                first_name ASC
        ",

        $types,
        $params
    );


/*
|--------------------------------------------------------------------------
| Occupation Filter Options
|--------------------------------------------------------------------------
*/

$occupation_options_result =
    resident_report_query(
        $conn,

        "
            SELECT DISTINCT
                occupation

            FROM residents

            WHERE
                occupation IS NOT NULL
                AND TRIM(occupation) <> ''

            ORDER BY
                occupation ASC
        "
    );


$occupation_options = [];


while (
    $occupation_row =
    $occupation_options_result->fetch_assoc()
) {

    $occupation_options[] =
        $occupation_row["occupation"];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Reports
    </title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../assets/css/style.css") ?>">


    <script>
        (function() {

            var theme =
                localStorage.getItem("theme");

            if (
                theme === "dark"
            ) {

                document.documentElement.setAttribute(
                    "data-theme",
                    "dark"
                );

            }

        })();
    </script>


    <script>
        (function() {

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

            } catch (e) {}

        })();
    </script>


    <style>
        /*
|--------------------------------------------------------------------------
| Resident Report Header
|--------------------------------------------------------------------------
*/

        .resident-reports-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 20px;

        }


        .resident-reports-header h2 {

            margin: 0;

        }


        .resident-reports-header p {

            margin: 5px 0 0;

            font-size: 13px;

            opacity: .65;

        }


        .resident-reports-actions {

            display: flex;

            gap: 8px;

            flex-wrap: wrap;

            justify-content: flex-end;

        }


        .resident-reports-actions .btn {

            white-space: nowrap;

        }


        /*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

        .resident-report-filters {

            display: grid;

            grid-template-columns:
                repeat(5,
                    minmax(0,
                        1fr));

            gap: 12px;

            padding: 15px;

            margin-bottom: 18px;

            border:
                1px solid rgba(0, 0, 0, .10);

            border-radius: 10px;

        }


        .resident-report-filter {

            min-width: 0;

        }


        .resident-report-filter label {

            display: block;

            margin-bottom: 6px;

            font-size: 12px;

            font-weight: 700;

        }


        .resident-report-filter select {

            width: 100%;

        }


        .resident-report-filter-actions {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            text-align: center; 
    

        }


        .resident-report-filter-actions .btn {
            flex: 1;

        }


        /*
|--------------------------------------------------------------------------
| Summary Cards
|--------------------------------------------------------------------------
*/

        .resident-report-summary {

            display: grid;

            grid-template-columns:
                repeat(4,
                    minmax(0,
                        1fr));

            gap: 12px;

            margin-bottom: 22px;

        }


        .resident-report-card {

            padding: 16px;

            border:
                1px solid rgba(0, 0, 0, .10);

            border-radius: 10px;

        }


        .resident-report-card-label {

            font-size: 12px;

            opacity: .65;

            margin-bottom: 8px;

        }


        .resident-report-card-value {

            font-size: 28px;

            font-weight: 700;

            line-height: 1.1;

        }


        /*
|--------------------------------------------------------------------------
| Report Grid
|--------------------------------------------------------------------------
*/

        .resident-report-grid {

            display: grid;

            grid-template-columns:
                repeat(2,
                    minmax(0,
                        1fr));

            gap: 18px;

            margin-bottom: 22px;

        }


        .resident-report-panel {

            min-width: 0;

            border:
                1px solid rgba(0, 0, 0, .10);

            border-radius: 10px;

            overflow: hidden;

        }


        .resident-report-panel-full {

            grid-column:
                1 / -1;

        }


        .resident-report-panel-header {

            padding: 15px 18px;

            border-bottom:
                1px solid rgba(0, 0, 0, .08);

        }


        .resident-report-panel-header h3 {

            margin: 0;

            font-size: 16px;

        }


        .resident-report-panel-header p {

            margin: 4px 0 0;

            font-size: 13px;

            opacity: .62;

        }


        .resident-report-panel-body {

            padding: 18px;

        }


        /*
|--------------------------------------------------------------------------
| Report Bars
|--------------------------------------------------------------------------
*/

        .report-bar-row {

            display: grid;

            grid-template-columns:
                120px minmax(0, 1fr) 55px;

            align-items: center;

            gap: 10px;

            margin-bottom: 13px;

        }


        .report-bar-row:last-child {

            margin-bottom: 0;

        }


        .report-bar-label {

            min-width: 0;

            font-size: 13px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;

        }


        .report-bar-track {

            height: 10px;

            background:
                rgba(0, 0, 0, .08);

            border-radius: 99px;

            overflow: hidden;

        }


        .report-bar-fill {

            height: 100%;

            background: #111111;

            border-radius: 99px;

        }


        .report-bar-value {

            text-align: right;

            font-size: 13px;

            font-weight: 700;

        }


        /*
|--------------------------------------------------------------------------
| Resident Directory
|--------------------------------------------------------------------------
*/

        .resident-report-table-wrapper {

            width: 100%;

            overflow-x: auto;

        }


        .resident-report-table {

            width: 100%;

            border-collapse: collapse;

            min-width: 950px;

        }


        .resident-report-table th,
        .resident-report-table td {

            padding: 11px 12px;

            text-align: left;

            border-bottom:
                1px solid rgba(0, 0, 0, .07);

            vertical-align: top;

        }


        .resident-report-table th {

            font-size: 12px;

            text-transform: uppercase;

            letter-spacing: .03em;

            opacity: .68;

        }


        .resident-report-table td {

            font-size: 13px;

        }


        .resident-report-table tbody tr:last-child td {

            border-bottom: 0;

        }


        .resident-report-empty {

            padding: 28px 15px;

            text-align: center;

            opacity: .60;

        }


        /*
|--------------------------------------------------------------------------
| Dark Mode
|--------------------------------------------------------------------------
*/

        [data-theme="dark"] .resident-report-filters,
        [data-theme="dark"] .resident-report-card,
        [data-theme="dark"] .resident-report-panel {

            border-color:
                rgba(255, 255, 255, .12);

        }


        [data-theme="dark"] .resident-report-panel-header {

            border-color:
                rgba(255, 255, 255, .10);

        }


        [data-theme="dark"] .resident-report-table th,
        [data-theme="dark"] .resident-report-table td {

            border-color:
                rgba(255, 255, 255, .08);

        }


        [data-theme="dark"] .report-bar-track {

            background:
                rgba(255, 255, 255, .10);

        }


        [data-theme="dark"] .report-bar-fill {

            background:
                #ffffff;

        }


        /*
|--------------------------------------------------------------------------
| Print
|--------------------------------------------------------------------------
*/

        @media print {

            @page {

                size: A4;

                margin: 12mm;

            }


            body {

                background: #ffffff !important;

                color: #000000 !important;

            }


            .sidebar,
            .topbar,
            nav,
            .no-print,
            .resident-reports-actions,
            .resident-report-filters {

                display: none !important;

            }


            .container {

                margin: 0 !important;

                padding: 0 !important;

                width: 100% !important;

                max-width: none !important;

            }


            .card {

                box-shadow: none !important;

                border: 0 !important;

                padding: 0 !important;

                margin-bottom: 20px !important;

            }


            .resident-report-card,
            .resident-report-panel {

                border:
                    1px solid #999 !important;

            }


            .resident-report-summary {

                grid-template-columns:
                    repeat(4,
                        1fr);

            }


            .resident-report-panel {

                break-inside: avoid;

            }


            .resident-report-table {

                min-width: 0;

            }


            .resident-report-table th,
            .resident-report-table td {

                font-size: 8px;

                padding: 5px;

            }

        }


        /*
|--------------------------------------------------------------------------
| Responsive
|--------------------------------------------------------------------------
*/

        @media (max-width: 1000px) {

            .resident-report-summary {

                grid-template-columns:
                    repeat(2,
                        minmax(0,
                            1fr));

            }


            .resident-report-filters {

                grid-template-columns:
                    repeat(2,
                        minmax(0,
                            1fr));

            }

        }


        @media (max-width: 760px) {

            .resident-reports-header {

                align-items: flex-start;

                flex-direction: column;

            }


            .resident-reports-actions {

                width: 100%;

                justify-content: flex-start;

            }


            .resident-report-summary {

                grid-template-columns:
                    1fr 1fr;

            }


            .resident-report-grid {

                grid-template-columns:
                    1fr;

            }


            .resident-report-panel-full {

                grid-column: auto;

            }


            .resident-report-filters {

                grid-template-columns:
                    1fr;

            }


            .report-bar-row {

                grid-template-columns:
                    90px minmax(0, 1fr) 45px;

            }

        }


        @media (max-width: 500px) {

            .resident-report-summary {

                grid-template-columns:
                    1fr;

            }

        }
    </style>

</head>


<body>


    <?php

    $no_print_nav = true;

    include __DIR__ . "/../includes/staff_nav.php";

    ?>


    <div class="container">


        <?php

        include __DIR__ . "/../includes/staff_topbar.php";

        ?>


        <!--
============================================================================
SURVEY SUMMARY REPORT
============================================================================
-->

        <div class="card">


            <div
                style="
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:15px;
        ">

                <h2>
                    Survey Summary Report
                </h2>


                <button
                    class="no-print"
                    type="button"
                    onclick="window.print()">
                    Print / Export as PDF
                </button>

            </div>


            <p
                style="
            font-size:13px;
            color:#667;
        ">
                Use your browser's Print dialog and choose
                "Save as PDF" to export this report.
            </p>


            <div class="table-scroll">


                <table>


                    <tr>

                        <th>
                            Survey Title
                        </th>

                        <th>
                            Period
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Questions
                        </th>

                        <th>
                            Responses
                        </th>

                        <th class="no-print">
                            View Results
                        </th>

                    </tr>


                    <?php while (
                        $r =
                        $reports->fetch_assoc()
                    ): ?>


                        <tr>

                            <td>

                                <?= e(
                                    $r["title"]
                                ) ?>

                            </td>


                            <td>

                                <?= e(
                                    $r["start_date"]
                                ) ?>

                                to

                                <?= e(
                                    $r["end_date"]
                                ) ?>

                            </td>


                            <td>

                                <?= $r["status"] === "active"
                                    ? "Active"
                                    : "Inactive"
                                ?>

                            </td>


                            <td>

                                <?= (int)
                                $r["question_count"]
                                ?>

                            </td>


                            <td>

                                <?= (int)
                                $r["response_count"]
                                ?>

                            </td>


                            <td class="no-print">

                                <a
                                    class="btn btn-reports"
                                    href="results.php?survey_id=<?= (int) $r["survey_id"] ?>">
                                    View
                                </a>

                            </td>

                        </tr>


                    <?php endwhile; ?>


                </table>


            </div>


        </div>


        <!--
============================================================================
RESIDENT REPORT
============================================================================
-->

        <div class="card">


            <div
                class="resident-reports-header">


                <div>

                    <h2>
                        Resident Report
                    </h2>

                    <p>
                        Reports based on registered resident information.
                    </p>

                </div>


                <div
                    class="resident-reports-actions no-print">


                    <a
                        href="resident_export.php"
                        class="btn btn-sm btn-secondary">
                        Export CSV
                    </a>


                    <button
                        type="button"
                        class="btn btn-sm"
                        id="printResidentReport">
                        Print / Export as PDF
                    </button>


                </div>


            </div>


            <!--
    ==========================================================================
    FILTERS
    ==========================================================================
    -->

            <form
                method="GET"
                class="resident-report-filters no-print">


                <div
                    class="resident-report-filter">

                    <label
                        for="civil_status">
                        Civil Status
                    </label>


                    <select
                        name="civil_status"
                        id="civil_status">

                        <option value="">
                            All Civil Statuses
                        </option>


                        <?php foreach (
                            $allowed_civil_statuses
                            as $status
                        ): ?>

                            <option
                                value="<?= resident_report_escape($status) ?>"
                                <?= $civil_status_filter === $status
                                    ? "selected"
                                    : ""
                                ?>>

                                <?= resident_report_escape(
                                    $status
                                ) ?>

                            </option>

                        <?php endforeach; ?>


                    </select>

                </div>


                <div
                    class="resident-report-filter">

                    <label
                        for="age_group">
                        Age Group
                    </label>


                    <select
                        name="age_group"
                        id="age_group">

                        <option value="">
                            All Age Groups
                        </option>


                        <?php foreach (
                            $allowed_age_groups
                            as $group
                        ): ?>

                            <option
                                value="<?= resident_report_escape($group) ?>"
                                <?= $age_group_filter === $group
                                    ? "selected"
                                    : ""
                                ?>>

                                <?= resident_report_escape(
                                    $group
                                ) ?>

                            </option>

                        <?php endforeach; ?>


                    </select>

                </div>


                <div
                    class="resident-report-filter">

                    <label
                        for="gender">
                        Gender
                    </label>


                    <select
                        name="gender"
                        id="gender">

                        <option value="">
                            All Genders
                        </option>


                        <?php foreach (
                            $allowed_genders
                            as $gender
                        ): ?>

                            <option
                                value="<?= resident_report_escape($gender) ?>"
                                <?= $gender_filter === $gender
                                    ? "selected"
                                    : ""
                                ?>>

                                <?= resident_report_escape(
                                    $gender
                                ) ?>

                            </option>

                        <?php endforeach; ?>


                    </select>

                </div>


                <div
                    class="resident-report-filter">

                    <label
                        for="occupation">
                        Occupation
                    </label>


                    <select
                        name="occupation"
                        id="occupation">

                        <option value="">
                            All Occupations
                        </option>


                        <?php foreach (
                            $occupation_options
                            as $occupation
                        ): ?>

                            <option
                                value="<?= resident_report_escape($occupation) ?>"
                                <?= $occupation_filter === $occupation
                                    ? "selected"
                                    : ""
                                ?>>

                                <?= resident_report_escape(
                                    $occupation
                                ) ?>

                            </option>

                        <?php endforeach; ?>


                    </select>

                </div>


                <div
                    class="resident-report-filter-actions">

                    <button
                        type="submit"
                        class="btn">
                      Filter
                    </button>


                    <a
                        href="reports.php"
                        class="btn">
                        Reset
                    </a>

                </div>


            </form>


            <!--
    ==========================================================================
    SUMMARY CARDS
    ==========================================================================
    -->

            <div
                class="resident-report-summary">


                <div
                    class="resident-report-card">

                    <div
                        class="resident-report-card-label">
                        Total Residents
                    </div>


                    <div
                        class="resident-report-card-value">

                        <?= resident_report_escape(
                            number_format(
                                $total_residents
                            )
                        ) ?>

                    </div>

                </div>


                <div
                    class="resident-report-card">

                    <div
                        class="resident-report-card-label">
                        With Birthday
                    </div>


                    <div
                        class="resident-report-card-value">

                        <?= resident_report_escape(
                            number_format(
                                $with_birthday
                            )
                        ) ?>

                    </div>

                </div>


                <div
                    class="resident-report-card">

                    <div
                        class="resident-report-card-label">
                        With Occupation
                    </div>


                    <div
                        class="resident-report-card-value">

                        <?= resident_report_escape(
                            number_format(
                                $with_occupation
                            )
                        ) ?>

                    </div>

                </div>


                <div
                    class="resident-report-card">

                    <div
                        class="resident-report-card-label">
                        With Address
                    </div>


                    <div
                        class="resident-report-card-value">

                        <?= resident_report_escape(
                            number_format(
                                $with_address
                            )
                        ) ?>

                    </div>

                </div>


            </div>


            <!--
    ==========================================================================
    DISTRIBUTION REPORTS
    ==========================================================================
    -->

            <div
                class="resident-report-grid">


                <!-- AGE -->

                <section
                    class="resident-report-panel">

                    <div
                        class="resident-report-panel-header">

                        <h3>
                            Age Distribution
                        </h3>

                        <p>
                            Residents grouped by age.
                        </p>

                    </div>


                    <div
                        class="resident-report-panel-body">


                        <?php

                        $age_values = [

                            "0–17" =>
                            $age_0_17,

                            "18–30" =>
                            $age_18_30,

                            "31–45" =>
                            $age_31_45,

                            "46–60" =>
                            $age_46_60,

                            "61+" =>
                            $age_61_plus

                        ];


                        $age_max =
                            max(
                                $age_values ?: [1]
                            );


                        if (
                            $age_max <= 0
                        ) {

                            $age_max = 1;
                        }

                        ?>


                        <?php foreach (
                            $age_values
                            as $label =>
                            $total
                        ): ?>


                            <?php

                            $bar_width =
                                (
                                    $total /
                                    $age_max
                                ) * 100;

                            ?>


                            <div
                                class="report-bar-row">

                                <div
                                    class="report-bar-label">

                                    <?= resident_report_escape(
                                        $label
                                    ) ?>

                                </div>


                                <div
                                    class="report-bar-track">

                                    <div
                                        class="report-bar-fill"
                                        style="
                                    width:
                                    <?= number_format(
                                        $bar_width,
                                        2,
                                        ".",
                                        ""
                                    ) ?>%;
                                "></div>

                                </div>


                                <div
                                    class="report-bar-value">

                                    <?= resident_report_escape(
                                        number_format(
                                            $total
                                        )
                                    ) ?>

                                </div>

                            </div>


                        <?php endforeach; ?>


                        <?php if (
                            $age_not_provided > 0
                        ): ?>


                            <div
                                class="report-bar-row">

                                <div
                                    class="report-bar-label">
                                    Not provided
                                </div>


                                <div
                                    class="report-bar-track">

                                    <div
                                        class="report-bar-fill"
                                        style="
                                    width:
                                    <?= number_format(
                                        (
                                            $age_not_provided /
                                            max(
                                                $age_max,
                                                1
                                            )
                                        ) * 100,
                                        2,
                                        ".",
                                        ""
                                    ) ?>%;
                                "></div>

                                </div>


                                <div
                                    class="report-bar-value">

                                    <?= resident_report_escape(
                                        number_format(
                                            $age_not_provided
                                        )
                                    ) ?>

                                </div>

                            </div>


                        <?php endif; ?>


                    </div>

                </section>


                <!-- CIVIL STATUS -->

                <section
                    class="resident-report-panel">

                    <div
                        class="resident-report-panel-header">

                        <h3>
                            Civil Status
                        </h3>

                        <p>
                            Distribution of registered civil statuses.
                        </p>

                    </div>


                    <div
                        class="resident-report-panel-body">


                        <?php

                        $civil_max = 1;


                        foreach (
                            $civil_data
                            as $item
                        ) {

                            if (
                                $item["total"] >
                                $civil_max
                            ) {

                                $civil_max =
                                    $item["total"];
                            }
                        }

                        ?>


                        <?php if (
                            count($civil_data) > 0
                        ): ?>


                            <?php foreach (
                                $civil_data
                                as $item
                            ): ?>


                                <?php

                                $bar_width =
                                    (
                                        $item["total"] /
                                        max(
                                            $civil_max,
                                            1
                                        )
                                    ) * 100;

                                ?>


                                <div
                                    class="report-bar-row">

                                    <div
                                        class="report-bar-label"
                                        title="<?= resident_report_escape($item["label"]) ?>">

                                        <?= resident_report_escape(
                                            $item["label"]
                                        ) ?>

                                    </div>


                                    <div
                                        class="report-bar-track">

                                        <div
                                            class="report-bar-fill"
                                            style="
                                        width:
                                        <?= number_format(
                                            $bar_width,
                                            2,
                                            ".",
                                            ""
                                        ) ?>%;
                                    "></div>

                                    </div>


                                    <div
                                        class="report-bar-value">

                                        <?= resident_report_escape(
                                            number_format(
                                                $item["total"]
                                            )
                                        ) ?>

                                    </div>

                                </div>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <div
                                class="resident-report-empty">
                                No civil status data available.
                            </div>


                        <?php endif; ?>


                    </div>

                </section>


                <!-- OCCUPATION -->

                <section
                    class="resident-report-panel">

                    <div
                        class="resident-report-panel-header">

                        <h3>
                            Top Occupations
                        </h3>

                        <p>
                            Most common occupations among residents.
                        </p>

                    </div>


                    <div
                        class="resident-report-panel-body">


                        <?php

                        $occupation_max = 1;


                        foreach (
                            $occupation_data
                            as $item
                        ) {

                            if (
                                $item["total"] >
                                $occupation_max
                            ) {

                                $occupation_max =
                                    $item["total"];
                            }
                        }

                        ?>


                        <?php if (
                            count($occupation_data) > 0
                        ): ?>


                            <?php foreach (
                                $occupation_data
                                as $item
                            ): ?>


                                <?php

                                $bar_width =
                                    (
                                        $item["total"] /
                                        max(
                                            $occupation_max,
                                            1
                                        )
                                    ) * 100;

                                ?>


                                <div
                                    class="report-bar-row">

                                    <div
                                        class="report-bar-label"
                                        title="<?= resident_report_escape($item["label"]) ?>">

                                        <?= resident_report_escape(
                                            $item["label"]
                                        ) ?>

                                    </div>


                                    <div
                                        class="report-bar-track">

                                        <div
                                            class="report-bar-fill"
                                            style="
                                        width:
                                        <?= number_format(
                                            $bar_width,
                                            2,
                                            ".",
                                            ""
                                        ) ?>%;
                                    "></div>

                                    </div>


                                    <div
                                        class="report-bar-value">

                                        <?= resident_report_escape(
                                            number_format(
                                                $item["total"]
                                            )
                                        ) ?>

                                    </div>

                                </div>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <div
                                class="resident-report-empty">
                                No occupation data available.
                            </div>


                        <?php endif; ?>


                    </div>

                </section>


                <!-- EMPLOYERS -->

                <section
                    class="resident-report-panel">

                    <div
                        class="resident-report-panel-header">

                        <h3>
                            Top Employers
                        </h3>

                        <p>
                            Most common employers among residents.
                        </p>

                    </div>


                    <div
                        class="resident-report-panel-body">


                        <?php

                        $employer_max = 1;


                        foreach (
                            $employer_data
                            as $item
                        ) {

                            if (
                                $item["total"] >
                                $employer_max
                            ) {

                                $employer_max =
                                    $item["total"];
                            }
                        }

                        ?>


                        <?php if (
                            count($employer_data) > 0
                        ): ?>


                            <?php foreach (
                                $employer_data
                                as $item
                            ): ?>


                                <?php

                                $bar_width =
                                    (
                                        $item["total"] /
                                        max(
                                            $employer_max,
                                            1
                                        )
                                    ) * 100;

                                ?>


                                <div
                                    class="report-bar-row">

                                    <div
                                        class="report-bar-label"
                                        title="<?= resident_report_escape($item["label"]) ?>">

                                        <?= resident_report_escape(
                                            $item["label"]
                                        ) ?>

                                    </div>


                                    <div
                                        class="report-bar-track">

                                        <div
                                            class="report-bar-fill"
                                            style="
                                        width:
                                        <?= number_format(
                                            $bar_width,
                                            2,
                                            ".",
                                            ""
                                        ) ?>%;
                                    "></div>

                                    </div>


                                    <div
                                        class="report-bar-value">

                                        <?= resident_report_escape(
                                            number_format(
                                                $item["total"]
                                            )
                                        ) ?>

                                    </div>

                                </div>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <div
                                class="resident-report-empty">
                                No employer data available.
                            </div>


                        <?php endif; ?>


                    </div>

                </section>


                <!-- ADDRESS -->

                <section
                    class="resident-report-panel resident-report-panel-full">

                    <div
                        class="resident-report-panel-header">

                        <h3>
                            Resident Distribution by Address
                        </h3>

                        <p>
                            Top 10 addresses with the highest number
                            of registered residents.
                        </p>

                    </div>


                    <div
                        class="resident-report-panel-body">


                        <?php

                        $address_max = 1;


                        foreach (
                            $address_data
                            as $item
                        ) {

                            if (
                                $item["total"] >
                                $address_max
                            ) {

                                $address_max =
                                    $item["total"];
                            }
                        }

                        ?>


                        <?php if (
                            count($address_data) > 0
                        ): ?>


                            <?php foreach (
                                $address_data
                                as $item
                            ): ?>


                                <?php

                                $bar_width =
                                    (
                                        $item["total"] /
                                        max(
                                            $address_max,
                                            1
                                        )
                                    ) * 100;

                                ?>


                                <div
                                    class="report-bar-row"
                                    style="
                                grid-template-columns:
                                220px
                                minmax(0, 1fr)
                                55px;
                            ">

                                    <div
                                        class="report-bar-label"
                                        title="<?= resident_report_escape($item["label"]) ?>">

                                        <?= resident_report_escape(
                                            $item["label"]
                                        ) ?>

                                    </div>


                                    <div
                                        class="report-bar-track">

                                        <div
                                            class="report-bar-fill"
                                            style="
                                        width:
                                        <?= number_format(
                                            $bar_width,
                                            2,
                                            ".",
                                            ""
                                        ) ?>%;
                                    "></div>

                                    </div>


                                    <div
                                        class="report-bar-value">

                                        <?= resident_report_escape(
                                            number_format(
                                                $item["total"]
                                            )
                                        ) ?>

                                    </div>

                                </div>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <div
                                class="resident-report-empty">
                                No address data available.
                            </div>


                        <?php endif; ?>


                    </div>

                </section>


            </div>


            <!--
    ==========================================================================
    RESIDENT DIRECTORY
    ==========================================================================
    -->

            <section
                class="resident-report-panel resident-report-panel-full">


                <div
                    class="resident-report-panel-header">

                    <h3>
                        Resident Directory
                    </h3>

                    <p>
                        Residents included in the current report filters.
                    </p>

                </div>


                <div
                    class="resident-report-table-wrapper">


                    <table
                        class="resident-report-table">


                        <thead>

                            <tr>

                                <th>
                                    Resident No.
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
                                    Contact
                                </th>

                                <th>
                                    Address
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php if (
                                $directory_result &&
                                $directory_result->num_rows > 0
                            ): ?>


                                <?php while (
                                    $resident =
                                    $directory_result->fetch_assoc()
                                ): ?>


                                    <?php

                                    $full_name =
                                        trim(
                                            $resident["first_name"] .
                                                " " .
                                                ($resident["middle_name"] ?? "") .
                                                " " .
                                                $resident["last_name"] .
                                                " " .
                                                ($resident["extension_name"] ?? "")
                                        );


                                    if (
                                        $full_name === ""
                                    ) {

                                        $full_name =
                                            $resident["resident_number"];
                                    }

                                    ?>


                                    <tr>


                                        <td>

                                            <?= resident_report_escape(
                                                $resident["resident_number"]
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= resident_report_escape(
                                                $full_name
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= !empty($resident["civil_status"])
                                                ? resident_report_escape(
                                                    $resident["civil_status"]
                                                )
                                                : "—"
                                            ?>

                                        </td>


                                        <td>

                                            <?php if (
                                                !empty($resident["birthday"])
                                            ): ?>

                                                <?= resident_report_escape(
                                                    date(
                                                        "M d, Y",
                                                        strtotime(
                                                            $resident["birthday"]
                                                        )
                                                    )
                                                ) ?>

                                            <?php else: ?>

                                                —

                                            <?php endif; ?>

                                        </td>


                                        <td>

                                            <?= (
                                                $resident["age"] !== null &&
                                                $resident["age"] !== ""
                                            )
                                                ? resident_report_escape(
                                                    $resident["age"]
                                                )
                                                : "—"
                                            ?>

                                        </td>


                                        <td>

                                            <?= !empty($resident["occupation"])
                                                ? resident_report_escape(
                                                    $resident["occupation"]
                                                )
                                                : "—"
                                            ?>

                                        </td>


                                        <td>

                                            <?= !empty($resident["employer"])
                                                ? resident_report_escape(
                                                    $resident["employer"]
                                                )
                                                : "—"
                                            ?>

                                        </td>


                                        <td>

                                            <?= !empty($resident["contact_number"])
                                                ? resident_report_escape(
                                                    $resident["contact_number"]
                                                )
                                                : "—"
                                            ?>

                                        </td>


                                        <td>

                                            <?= !empty($resident["address"])
                                                ? resident_report_escape(
                                                    $resident["address"]
                                                )
                                                : "—"
                                            ?>

                                        </td>


                                    </tr>


                                <?php endwhile; ?>


                            <?php else: ?>


                                <tr>

                                    <td
                                        colspan="9"
                                        class="resident-report-empty">

                                        No residents match the selected filters.

                                    </td>

                                </tr>


                            <?php endif; ?>


                        </tbody>


                    </table>


                </div>


            </section>


        </div>


    </div>


    <script src="../assets/js/script.js"></script>


    <script>
        /*
|--------------------------------------------------------------------------
| Resident Report Print
|--------------------------------------------------------------------------
|
| This prints the main reports page using the print stylesheet.
|--------------------------------------------------------------------------
*/

        document.addEventListener(
            "DOMContentLoaded",
            function() {

                const printResidentReport =
                    document.getElementById(
                        "printResidentReport"
                    );


                if (
                    printResidentReport
                ) {

                    printResidentReport.addEventListener(
                        "click",
                        function() {

                            window.print();

                        }
                    );

                }

            }
        );
    </script>


    <?php if (
        isset($_GET["print"]) &&
        $_GET["print"] === "1"
    ): ?>

        <script>
            window.addEventListener(
                "load",
                function() {

                    window.print();

                }
            );
        </script>

    <?php endif; ?>


</body>

</html>