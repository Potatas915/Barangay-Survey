<?php

require_once __DIR__ . "/../includes/functions.php";
require_staff_login();


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
| Selected Resident
|--------------------------------------------------------------------------
*/

$selected_resident_id = filter_input(
    INPUT_GET,
    "resident_id",
    FILTER_VALIDATE_INT
);

if (
    $selected_resident_id === false ||
    $selected_resident_id === null ||
    $selected_resident_id <= 0
) {
    $selected_resident_id = null;
}


/*
|--------------------------------------------------------------------------
| HTML Escape
|--------------------------------------------------------------------------
*/

function history_escape($value)
{
    return e(
        $value === null
            ? ""
            : (string) $value
    );
}


/*
|--------------------------------------------------------------------------
| Human-Readable Field Names
|--------------------------------------------------------------------------
*/

function history_field_label($key)
{
    $labels = [

        "first_name" => "First Name",
        "middle_name" => "Middle Name",
        "last_name" => "Last Name",
        "extension_name" => "Extension Name",

        "civil_status" => "Civil Status",
        "birthday" => "Birthday",
        "age" => "Age",

        "occupation" => "Occupation",
        "employer" => "Employer",
        "employer_address" => "Employer Address",

        "email" => "Email",
        "contact_number" => "Contact Number",
        "address" => "Address",

        "father_name" => "Father's Name",
        "mother_name" => "Mother's Name",

        "spouse_name" => "Spouse Name",
        "spouse_occupation" => "Spouse Occupation",
        "spouse_employer" => "Spouse Employer",

        "child_name" => "Child Name",
        "child_age" => "Child Age",

        "reference_name" => "Reference Name",
        "reference_contact" => "Reference Contact",
        "reference_address" => "Reference Address",

        "name" => "Name",
        "contact" => "Contact",
        "phone" => "Phone",

        "photo" => "Photo",

        "password" => "Password"

    ];


    if (
        isset($labels[$key])
    ) {
        return $labels[$key];
    }


    return ucwords(
        str_replace(
            "_",
            " ",
            (string) $key
        )
    );
}


/*
|--------------------------------------------------------------------------
| Section Names
|--------------------------------------------------------------------------
*/

function history_section_label($section)
{
    $labels = [

        "complete_profile"
            => "Complete Profile",

        "personal_information"
            => "Personal Information",

        "spouse"
            => "Spouse Information",

        "children"
            => "Children Information",

        "parents"
            => "Parents Information",

        "references"
            => "Character References",

        "photo"
            => "Passport-Size Photo",

        "password"
            => "Password"

    ];


    if (
        isset($labels[$section])
    ) {
        return $labels[$section];
    }


    return history_field_label($section);
}


/*
|--------------------------------------------------------------------------
| Decode JSON
|--------------------------------------------------------------------------
*/

function decode_history_data($data)
{
    if (
        $data === null ||
        trim((string) $data) === ""
    ) {
        return null;
    }


    $decoded = json_decode(
        $data,
        true
    );


    if (
        json_last_error() !== JSON_ERROR_NONE
    ) {
        return null;
    }


    return $decoded;
}


/*
|--------------------------------------------------------------------------
| Detect Password Data
|--------------------------------------------------------------------------
*/

function history_contains_password($data)
{
    if (
        !is_array($data)
    ) {
        return false;
    }


    foreach (
        $data as $key => $value
    ) {

        $key_lower = strtolower(
            (string) $key
        );


        if (
            $key_lower === "password" ||
            strpos(
                $key_lower,
                "password"
            ) !== false
        ) {
            return true;
        }


        if (
            is_array($value) &&
            history_contains_password($value)
        ) {
            return true;
        }

    }


    return false;
}


/*
|--------------------------------------------------------------------------
| Format Scalar Value
|--------------------------------------------------------------------------
*/

function history_format_scalar(
    $value,
    $key = ""
)
{
    $key_lower = strtolower(
        (string) $key
    );


    if (
        $key_lower === "password" ||
        strpos(
            $key_lower,
            "password"
        ) !== false
    ) {
        return "Password was changed";
    }


    if (
        $value === null ||
        $value === ""
    ) {
        return "Not provided";
    }


    if (
        is_bool($value)
    ) {
        return $value
            ? "Yes"
            : "No";
    }


    return history_escape($value);
}


/*
|--------------------------------------------------------------------------
| Render Field Row
|--------------------------------------------------------------------------
*/

function render_history_field_row(
    $key,
    $value
)
{
    $html =
        '<div class="history-field-row">';


    $html .=
        '<div class="history-field-label">' .
        history_escape(
            history_field_label($key)
        ) .
        '</div>';


    $html .=
        '<div class="history-field-value">';


    $html .=
        render_history_value(
            $value,
            $key
        );


    $html .=
        '</div>';


    $html .=
        '</div>';


    return $html;
}


/*
|--------------------------------------------------------------------------
| Render Section Block
|--------------------------------------------------------------------------
*/

function render_history_section(
    $section_key,
    $section_value
)
{
    $html =
        '<div class="history-section-block">';


    $html .=
        '<div class="history-section-title">' .
        history_escape(
            history_section_label($section_key)
        ) .
        '</div>';


    $html .=
        '<div class="history-section-content">';


    if (
        is_array($section_value)
    ) {

        $html .=
            render_history_value(
                $section_value,
                $section_key
            );

    }
    else {

        $html .=
            '<div class="history-field-value">' .
            history_format_scalar(
                $section_value,
                $section_key
            ) .
            '</div>';

    }


    $html .=
        '</div>';


    $html .=
        '</div>';


    return $html;
}


/*
|--------------------------------------------------------------------------
| Format History Data
|--------------------------------------------------------------------------
*/

function render_history_value(
    $value,
    $key = ""
)
{
    /*
    |--------------------------------------------------------------------------
    | Scalar
    |--------------------------------------------------------------------------
    */

    if (
        !is_array($value)
    ) {

        return history_format_scalar(
            $value,
            $key
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Empty Array
    |--------------------------------------------------------------------------
    */

    if (
        count($value) === 0
    ) {
        return "Not provided";
    }


    /*
    |--------------------------------------------------------------------------
    | Numeric Array
    |--------------------------------------------------------------------------
    |
    | Used for children, references, etc.
    |
    */

    if (
        array_keys($value) ===
        range(
            0,
            count($value) - 1
        )
    ) {

        $html =
            '<div class="history-list">';


        foreach (
            $value as $index => $item
        ) {

            $html .=
                '<div class="history-list-item">';


            $html .=
                '<div class="history-list-number">' .
                history_escape(
                    $index + 1
                ) .
                '</div>';


            $html .=
                '<div class="history-list-content">';


            if (
                is_array($item)
            ) {

                foreach (
                    $item as $item_key => $item_value
                ) {

                    $item_key_lower =
                        strtolower(
                            (string) $item_key
                        );


                    if (
                        strpos(
                            $item_key_lower,
                            "password"
                        ) !== false ||
                        strpos(
                            $item_key_lower,
                            "hash"
                        ) !== false
                    ) {
                        continue;
                    }


                    $html .=
                        render_history_field_row(
                            $item_key,
                            $item_value
                        );

                }

            }
            else {

                $html .=
                    '<div class="history-field-value">' .
                    render_history_value(
                        $item,
                        $key
                    ) .
                    '</div>';

            }


            $html .=
                '</div>';


            $html .=
                '</div>';

        }


        $html .=
            '</div>';


        return $html;
    }


    /*
    |--------------------------------------------------------------------------
    | Associative Array
    |--------------------------------------------------------------------------
    |
    | Nested associative arrays are displayed as section blocks.
    | This keeps titles such as "Personal Information" above the fields
    | instead of placing the title in the left field column.
    |
    */

    $html =
        '<div class="history-fields">';


    foreach (
        $value as $child_key => $child_value
    ) {

        $child_key_lower =
            strtolower(
                (string) $child_key
            );


        /*
        |--------------------------------------------------------------------------
        | Never display password/hash fields
        |--------------------------------------------------------------------------
        */

        if (
            strpos(
                $child_key_lower,
                "password"
            ) !== false ||
            strpos(
                $child_key_lower,
                "hash"
            ) !== false
        ) {
            continue;
        }


        /*
        |--------------------------------------------------------------------------
        | Photo
        |--------------------------------------------------------------------------
        */

        if (
            $child_key_lower === "photo"
        ) {

            $html .=
                '<div class="history-field-row">';


            $html .=
                '<div class="history-field-label">' .
                'Photo' .
                '</div>';


            $html .=
                '<div class="history-field-value">' .
                'Photo information updated' .
                '</div>';


            $html .=
                '</div>';


            continue;
        }


        /*
        |--------------------------------------------------------------------------
        | Nested Section
        |--------------------------------------------------------------------------
        |
        | If the value is an associative array, display its key as a
        | section heading above its fields.
        |
        */

        if (
            is_array($child_value)
        ) {

            $html .=
                render_history_section(
                    $child_key,
                    $child_value
                );

            continue;
        }


        /*
        |--------------------------------------------------------------------------
        | Normal Scalar Field
        |--------------------------------------------------------------------------
        */

        $html .=
            render_history_field_row(
                $child_key,
                $child_value
            );

    }


    $html .=
        '</div>';


    return $html;
}


/*
|--------------------------------------------------------------------------
| Get Selected Resident
|--------------------------------------------------------------------------
*/

$selected_resident = null;


if (
    $selected_resident_id !== null
) {

    $stmt = $conn->prepare("
        SELECT
            resident_id,
            resident_number,
            first_name,
            middle_name,
            last_name,
            extension_name
        FROM residents
        WHERE resident_id = ?
        LIMIT 1
    ");


    if (!$stmt) {
        die(
            "Unable to load resident."
        );
    }


    $stmt->bind_param(
        "i",
        $selected_resident_id
    );


    $stmt->execute();


    $result =
        $stmt->get_result();


    if (
        $result->num_rows === 1
    ) {

        $selected_resident =
            $result->fetch_assoc();

    }


    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| Resident History
|--------------------------------------------------------------------------
*/

$history_records = [];


if (
    $selected_resident_id !== null &&
    $selected_resident !== null
) {

    $stmt = $conn->prepare("
        SELECT
            h.history_id,
            h.resident_id,
            h.updated_by_type,
            h.updated_by_id,
            h.update_section,
            h.old_data,
            h.new_data,
            h.updated_at,
            s.full_name AS staff_name
        FROM resident_update_history h
        LEFT JOIN staff s
            ON s.staff_id = h.updated_by_id
            AND h.updated_by_type = 'staff'
        WHERE h.resident_id = ?
        ORDER BY
            h.updated_at DESC,
            h.history_id DESC
    ");


    if (!$stmt) {
        die(
            "Unable to load resident history."
        );
    }


    $stmt->bind_param(
        "i",
        $selected_resident_id
    );


    $stmt->execute();


    $result =
        $stmt->get_result();


    while (
        $row = $result->fetch_assoc()
    ) {

        $history_records[] = $row;

    }


    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| Resident History Summary
|--------------------------------------------------------------------------
*/

$summary_sql = "
    SELECT

        r.resident_id,

        r.resident_number,

        r.first_name,

        r.middle_name,

        r.last_name,

        r.extension_name,

        COUNT(h.history_id)
            AS update_count,

        MAX(h.updated_at)
            AS last_updated,

        (
            SELECT
                h2.updated_by_type
            FROM resident_update_history h2
            WHERE
                h2.resident_id = r.resident_id
            ORDER BY
                h2.updated_at DESC,
                h2.history_id DESC
            LIMIT 1
        ) AS last_updated_by

    FROM residents r

    INNER JOIN resident_update_history h
        ON h.resident_id = r.resident_id

    WHERE 1 = 1
";


$summary_params = [];
$summary_types = "";


if (
    $search !== ""
) {

    $like =
        "%" .
        $search .
        "%";


    $summary_sql .= "
        AND (
            r.resident_number LIKE ?
            OR r.first_name LIKE ?
            OR r.middle_name LIKE ?
            OR r.last_name LIKE ?
            OR r.extension_name LIKE ?
        )
    ";


    $summary_types = "sssss";


    $summary_params[] = $like;
    $summary_params[] = $like;
    $summary_params[] = $like;
    $summary_params[] = $like;
    $summary_params[] = $like;
}


$summary_sql .= "
    GROUP BY
        r.resident_id,
        r.resident_number,
        r.first_name,
        r.middle_name,
        r.last_name,
        r.extension_name

    ORDER BY
        last_updated DESC,
        r.last_name ASC,
        r.first_name ASC
";


$stmt = $conn->prepare(
    $summary_sql
);


if (!$stmt) {
    die(
        "Unable to load updated resident records."
    );
}


if (
    count($summary_params) > 0
) {

    $stmt->bind_param(
        $summary_types,
        ...$summary_params
    );

}


$stmt->execute();


$summary_result =
    $stmt->get_result();


/*
|--------------------------------------------------------------------------
| Selected Resident Name
|--------------------------------------------------------------------------
*/

$selected_resident_name = "";


if (
    $selected_resident !== null
) {

    $selected_resident_name =
        trim(
            $selected_resident["first_name"] .
            " " .
            ($selected_resident["middle_name"] ?? "") .
            " " .
            $selected_resident["last_name"] .
            " " .
            ($selected_resident["extension_name"] ?? "")
        );


    if (
        $selected_resident_name === ""
    ) {

        $selected_resident_name =
            $selected_resident[
                "resident_number"
            ];

    }
}


/*
|--------------------------------------------------------------------------
| Determine Whether Modal Should Open
|--------------------------------------------------------------------------
*/

$open_modal =
    (
        $selected_resident !== null
    );

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
    View Updated Records
</title>


<link
    rel="stylesheet"
    href="../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../assets/css/style.css") ?>"
>


<script>

(function () {

    const theme =
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
| Updated Records Header
|--------------------------------------------------------------------------
*/

.updated-records-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 16px;

    flex-wrap: wrap;

    margin-bottom: 18px;

}


.updated-records-header h2 {

    margin: 0 0 4px 0;

}


.updated-records-header p {

    margin: 0;

    opacity: .7;

}


.updated-records-header-actions {

    display: flex;

    gap: 10px;

    flex-wrap: wrap;

}


.updated-records-header-actions .btn {

    margin-top: 0;

}


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

.updated-records-search {

    display: flex;

    align-items: center;

    gap: 10px;

    margin-bottom: 18px;

}


.updated-records-search input {

    flex: 1;

    min-width: 250px;

}


.updated-records-search .btn {

    margin-top: 0;

}


/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/

.updated-records-table-wrapper {

    width: 100%;

    overflow-x: auto;

}


.updated-records-table {

    width: 100%;

    min-width: 800px;

    border-collapse: collapse;

}


.updated-records-table th {

    white-space: nowrap;

}


.updated-records-table td {

    vertical-align: middle;

}


.resident-name {

    font-weight: 600;

}


.update-count {

    font-weight: 600;

}


.view-history-btn {

    margin-top: 0;

}


.no-updated-records {

    text-align: center;

    padding: 40px 20px;

    opacity: .65;

}


/*
|--------------------------------------------------------------------------
| MODAL
|--------------------------------------------------------------------------
*/

.history-modal {

    position: fixed;

    inset: 0;

    z-index: 9999;

    display: none;

    align-items: center;

    justify-content: center;

    padding: 24px;

}


.history-modal.is-open {

    display: flex;

}


/*
|--------------------------------------------------------------------------
| Backdrop
|--------------------------------------------------------------------------
*/

.history-modal-backdrop {

    position: absolute;

    inset: 0;

    background:
        rgba(0, 0, 0, .48);

    backdrop-filter:
        blur(7px);

    -webkit-backdrop-filter:
        blur(7px);

}


/*
|--------------------------------------------------------------------------
| Modal Window
|--------------------------------------------------------------------------
*/

.history-modal-window {

    position: relative;

    z-index: 2;

    width: min(
        1100px,
        100%
    );

    max-height: calc(
        100vh - 48px
    );

    display: flex;

    flex-direction: column;

    background: var(
        --card-bg,
        #ffffff
    );

    border-radius: 14px;

    box-shadow:
        0 25px 70px
        rgba(0, 0, 0, .28);

    overflow: hidden;

    animation:
        historyModalOpen
        .18s ease-out;

}


@keyframes historyModalOpen {

    from {

        opacity: 0;

        transform:
            translateY(12px)
            scale(.98);

    }

    to {

        opacity: 1;

        transform:
            translateY(0)
            scale(1);

    }

}


/*
|--------------------------------------------------------------------------
| Modal Header
|--------------------------------------------------------------------------
*/

.history-modal-header {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 20px;

    padding: 20px 24px;

    border-bottom:
        1px solid
        rgba(0, 0, 0, .10);

}


.history-modal-title {

    margin: 0;

    font-size: 21px;

    font-weight: 700;

}


.history-modal-subtitle {

    margin: 5px 0 0;

    font-size: 14px;

    opacity: .65;

}


.history-modal-close {

    flex-shrink: 0;

    width: 36px;

    height: 36px;

    display: flex;

    align-items: center;

    justify-content: center;

    border: 0;

    background: #ff3b3b;

    color: #ffffff;

    cursor: pointer;

    font-size: 25px;

    font-weight: 700;

    line-height: 1;

    padding: 0;

    border-radius: 7px;

    box-shadow:
        0 0 12px
        rgba(255, 59, 59, .55);

    transition:
        all .2s ease;

}


.history-modal-close:hover {

    background: #ff2020;

    box-shadow:
        0 0 18px
        rgba(255, 59, 59, .85);

    transform:
        scale(1.05);

}


.history-modal-close:active {

    transform:
        scale(.95);

}


/*
|--------------------------------------------------------------------------
| Modal Body
|--------------------------------------------------------------------------
*/

.history-modal-body {

    overflow-y: auto;

    padding: 22px 24px 28px;

}


/*
|--------------------------------------------------------------------------
| History Entry
|--------------------------------------------------------------------------
*/

.history-entry {

    border:
        1px solid
        rgba(0, 0, 0, .12);

    border-radius: 10px;

    overflow: hidden;

    margin-bottom: 18px;

}


.history-entry:last-child {

    margin-bottom: 0;

}


.history-entry-header {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    gap: 16px;

    padding: 15px 17px;

    background:
        rgba(0, 0, 0, .025);

    border-bottom:
        1px solid
        rgba(0, 0, 0, .10);

}


.history-entry-section {

    font-weight: 700;

    font-size: 16px;

}


.history-entry-meta {

    margin-top: 4px;

    font-size: 13px;

    opacity: .68;

}


.history-entry-date {

    flex-shrink: 0;

    font-size: 13px;

    opacity: .68;

    white-space: nowrap;

}


/*
|--------------------------------------------------------------------------
| Old / New
|--------------------------------------------------------------------------
*/

.history-change-grid {

    display: grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0, 1fr)
        );

}


.history-change-panel {

    min-width: 0;

}


.history-change-panel + .history-change-panel {

    border-left:
        1px solid
        rgba(0, 0, 0, .10);

}


.history-change-title {

    padding: 12px 18px;

    font-size: 13px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: .03em;

    border-bottom:
        1px solid
        rgba(0, 0, 0, .08);

}


.history-change-body {

    padding: 18px 22px 22px;

}


/*
|--------------------------------------------------------------------------
| History Sections
|--------------------------------------------------------------------------
|
| Section names such as:
| Personal Information
| Spouse Information
| Parents Information
| Character References
|
| are now displayed above their fields.
|--------------------------------------------------------------------------
*/

.history-section-block {

    width: 100%;

    margin-bottom: 14px;

}


.history-section-block:last-child {

    margin-bottom: 0;

}


.history-section-title {

    margin: 0;

    padding: 0 0 9px;

    font-size: 13px;

    font-weight: 700;

    line-height: 1.4;

    opacity: .68;

}


.history-section-content {

    width: 100%;

    padding-left: 0;

}


.history-section-content
.history-fields {

    width: 100%;

}


/*
|--------------------------------------------------------------------------
| Formatted Fields
|--------------------------------------------------------------------------
*/

.history-fields {

    width: 100%;

}


.history-field-row {

    display: grid;

    grid-template-columns:
        130px
        minmax(0, 1fr);

    align-items: start;

    gap: 16px;

    padding: 10px 0;

    border-bottom:
        1px solid
        rgba(0, 0, 0, .07);

}


.history-field-row:last-child {

    border-bottom: 0;

}


.history-field-label {

    min-width: 0;

    font-size: 13px;

    font-weight: 600;

    opacity: .68;

    line-height: 1.45;

}


.history-field-value {

    min-width: 0;

    text-align: left;

    word-break: break-word;

    overflow-wrap: anywhere;

    line-height: 1.45;

}


/*
|--------------------------------------------------------------------------
| Lists
|--------------------------------------------------------------------------
*/

.history-list {

    width: 100%;

}


.history-list-item {

    display: flex;

    gap: 10px;

    padding: 10px 0;

    border-bottom:
        1px solid
        rgba(0, 0, 0, .07);

}


.history-list-item:last-child {

    border-bottom: 0;

}


.history-list-number {

    flex: 0 0 24px;

    width: 24px;

    height: 24px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background:
        rgba(0, 0, 0, .07);

    font-size: 12px;

    font-weight: 700;

}


.history-list-content {

    flex: 1;

    min-width: 0;

}


.history-list-content
.history-field-row {

    padding-top: 4px;

    padding-bottom: 7px;

}


/*
|--------------------------------------------------------------------------
| No History
|--------------------------------------------------------------------------
*/

.history-no-records {

    text-align: center;

    padding: 45px 20px;

    opacity: .65;

}


/*
|--------------------------------------------------------------------------
| Dark Mode
|--------------------------------------------------------------------------
*/

[data-theme="dark"]
.history-modal-window {

    background:
        var(
            --card-bg,
            #1e1e1e
        );

}


[data-theme="dark"]
.history-modal-header {

    border-color:
        rgba(255,255,255,.10);

}


[data-theme="dark"]
.history-entry {

    border-color:
        rgba(255,255,255,.12);

}


[data-theme="dark"]
.history-entry-header {

    background:
        rgba(255,255,255,.04);

    border-color:
        rgba(255,255,255,.10);

}


[data-theme="dark"]
.history-change-panel + .history-change-panel {

    border-color:
        rgba(255,255,255,.10);

}


[data-theme="dark"]
.history-change-title {

    border-color:
        rgba(255,255,255,.08);

}


[data-theme="dark"]
.history-field-row {

    border-color:
        rgba(255,255,255,.08);

}


[data-theme="dark"]
.history-list-item {

    border-color:
        rgba(255,255,255,.08);

}


[data-theme="dark"]
.history-modal-close {

    background: #ff3b3b;

}


[data-theme="dark"]
.history-modal-close:hover {

    background: #ff2020;

}


/*
|--------------------------------------------------------------------------
| Responsive
|--------------------------------------------------------------------------
*/

@media (max-width: 760px) {

    .updated-records-search {

        flex-direction: column;

        align-items: stretch;

    }


    .updated-records-search input {

        min-width: 0;

        width: 100%;

    }


    .history-modal {

        padding: 12px;

    }


    .history-modal-window {

        max-height:
            calc(
                100vh - 24px
            );

    }


    .history-modal-header {

        padding: 17px;

    }


    .history-modal-body {

        padding: 16px;

    }


    .history-change-grid {

        grid-template-columns: 1fr;

    }


    .history-change-panel + .history-change-panel {

        border-left: 0;

        border-top:
            1px solid
            rgba(0, 0, 0, .10);

    }


    .history-entry-header {

        flex-direction: column;

    }


    .history-entry-date {

        white-space: normal;

    }


    .history-change-body {

        padding: 16px;

    }


    .history-field-row {

        display: grid;

        grid-template-columns:
            115px
            minmax(0, 1fr);

        gap: 12px;

    }


    .history-field-label {

        flex-basis: auto;

    }


    .history-field-value {

        text-align: left;

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


<div class="card">


    <div
        class="updated-records-header"
    >


        <div>

            <h2>
                View Updated Records
            </h2>

            <p>
                View the history of resident information updates.
            </p>

        </div>


        <div
            class="updated-records-header-actions"
        >

            <a
                class="btn"
                href="resident_management.php"
            >
                Back to Residents
            </a>

        </div>


    </div>


    <!--
    ======================================================================
    SEARCH
    ======================================================================
    -->

    <form
        method="GET"
        class="updated-records-search"
    >


        <input
            type="text"
            name="search"
            placeholder="Search by resident number or name"
            value="<?= history_escape($search) ?>"
        >


        <button
            type="submit"
            class="btn"
        >
            Search
        </button>


        <?php if (
            $search !== ""
        ): ?>

            <a
                class="btn"
                href="resident_update_history.php"
            >
                Clear
            </a>

        <?php endif; ?>


    </form>


    <!--
    ======================================================================
    UPDATED RESIDENT TABLE
    ======================================================================
    -->

    <div
        class="updated-records-table-wrapper"
    >


        <table
            class="updated-records-table"
        >


            <thead>

                <tr>

                    <th>
                        Resident Number
                    </th>

                    <th>
                        Resident
                    </th>

                    <th>
                        Last Updated
                    </th>

                    <th>
                        Updated By
                    </th>

                    <th>
                        Total Updates
                    </th>

                    <th>
                        Action
                    </th>

                </tr>

            </thead>


            <tbody>


            <?php if (
                $summary_result &&
                $summary_result->num_rows > 0
            ): ?>


                <?php while (
                    $record =
                    $summary_result->fetch_assoc()
                ): ?>


                    <?php

                    $record_name =
                        trim(
                            $record["first_name"] .
                            " " .
                            ($record["middle_name"] ?? "") .
                            " " .
                            $record["last_name"] .
                            " " .
                            ($record["extension_name"] ?? "")
                        );


                    if (
                        $record_name === ""
                    ) {

                        $record_name =
                            $record[
                                "resident_number"
                            ];

                    }


                    $updated_by =
                        $record[
                            "last_updated_by"
                        ] === "staff"
                            ? "Staff"
                            : "Resident";

                    ?>


                    <tr>


                        <td>

                            <?= history_escape(
                                $record[
                                    "resident_number"
                                ]
                            ) ?>

                        </td>


                        <td>

                            <div
                                class="resident-name"
                            >

                                <?= history_escape(
                                    $record_name
                                ) ?>

                            </div>

                        </td>


                        <td>

                            <?= history_escape(
                                date(
                                    "M d, Y h:i A",
                                    strtotime(
                                        $record[
                                            "last_updated"
                                        ]
                                    )
                                )
                            ) ?>

                        </td>


                        <td>

                            <?= history_escape(
                                $updated_by
                            ) ?>

                        </td>


                        <td>

                            <span
                                class="update-count"
                            >

                                <?= history_escape(
                                    $record[
                                        "update_count"
                                    ]
                                ) ?>

                            </span>

                        </td>


                        <td>

                            <a
                                class="btn view-history-btn"
                                href="resident_update_history.php?resident_id=<?= (int) $record["resident_id"] ?>&search=<?= urlencode($search) ?>"
                            >
                                View
                            </a>

                        </td>


                    </tr>


                <?php endwhile; ?>


            <?php else: ?>


                <tr>

                    <td
                        colspan="6"
                        class="no-updated-records"
                    >

                        No updated resident records found.

                    </td>

                </tr>


            <?php endif; ?>


            </tbody>


        </table>


    </div>


</div>


<!--
==========================================================================
HISTORY MODAL
==========================================================================
-->

<?php if (
    $open_modal
): ?>


<div
    id="historyModal"
    class="history-modal is-open"
    role="dialog"
    aria-modal="true"
    aria-labelledby="historyModalTitle"
>


    <!--
    ----------------------------------------------------------------------
    BLURRED BACKDROP
    ----------------------------------------------------------------------
    -->

    <div
        class="history-modal-backdrop"
        data-close-history-modal
    ></div>


    <!--
    ----------------------------------------------------------------------
    MODAL WINDOW
    ----------------------------------------------------------------------
    -->

    <div
        class="history-modal-window"
    >


        <!--
        ------------------------------------------------------------------
        MODAL HEADER
        ------------------------------------------------------------------
        -->

        <div
            class="history-modal-header"
        >


            <div>

                <h3
                    id="historyModalTitle"
                    class="history-modal-title"
                >

                    <?= history_escape(
                        $selected_resident_name
                    ) ?>

                </h3>


                <p
                    class="history-modal-subtitle"
                >

                    Resident Number:

                    <strong>
                        <?= history_escape(
                            $selected_resident[
                                "resident_number"
                            ]
                        ) ?>
                    </strong>

                </p>

            </div>


            <button
                type="button"
                class="history-modal-close"
                aria-label="Close history"
                data-close-history-modal
            >
                &times;
            </button>


        </div>


        <!--
        ------------------------------------------------------------------
        MODAL BODY
        ------------------------------------------------------------------
        -->

        <div
            class="history-modal-body"
        >


            <?php if (
                count($history_records) > 0
            ): ?>


                <?php foreach (
                    $history_records
                    as $history
                ): ?>


                    <?php

                    /*
                    |--------------------------------------------------------------------------
                    | Actor
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $history[
                            "updated_by_type"
                        ] === "staff"
                    ) {

                        $actor_name =
                            !empty(
                                $history[
                                    "staff_name"
                                ]
                            )
                                ? $history[
                                    "staff_name"
                                ]
                                : "Staff";


                        $actor_details =
                            !empty(
                                $history[
                                    "updated_by_id"
                                ]
                            )
                                ? "Staff ID: " .
                                  $history[
                                      "updated_by_id"
                                  ]
                                : "Staff";

                    }
                    else {

                        $actor_name =
                            "Resident";


                        $actor_details =
                            !empty(
                                $history[
                                    "updated_by_id"
                                ]
                            )
                                ? "Resident ID: " .
                                  $history[
                                      "updated_by_id"
                                  ]
                                : "Resident";

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Section
                    |--------------------------------------------------------------------------
                    */

                    $section =
                        history_section_label(
                            $history[
                                "update_section"
                            ]
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Decode History
                    |--------------------------------------------------------------------------
                    */

                    $old_decoded =
                        decode_history_data(
                            $history[
                                "old_data"
                            ]
                        );


                    $new_decoded =
                        decode_history_data(
                            $history[
                                "new_data"
                            ]
                        );

                    ?>


                    <div
                        class="history-entry"
                    >


                        <!--
                        ------------------------------------------------------
                        ENTRY HEADER
                        ------------------------------------------------------
                        -->

                        <div
                            class="history-entry-header"
                        >


                            <div>

                                <div
                                    class="history-entry-section"
                                >

                                    <?= history_escape(
                                        $section
                                    ) ?>

                                </div>


                                <div
                                    class="history-entry-meta"
                                >

                                    Updated by

                                    <strong>
                                        <?= history_escape(
                                            $actor_name
                                        ) ?>
                                    </strong>

                                    ·

                                    <?= history_escape(
                                        $actor_details
                                    ) ?>

                                </div>

                            </div>


                            <div
                                class="history-entry-date"
                            >

                                <?= history_escape(
                                    date(
                                        "M d, Y h:i A",
                                        strtotime(
                                            $history[
                                                "updated_at"
                                            ]
                                        )
                                    )
                                ) ?>

                            </div>


                        </div>


                        <!--
                        ------------------------------------------------------
                        OLD / NEW DATA
                        ------------------------------------------------------
                        -->

                        <div
                            class="history-change-grid"
                        >


                            <!--
                            ==================================================
                            PREVIOUS
                            ==================================================
                            -->

                            <div
                                class="history-change-panel"
                            >


                                <div
                                    class="history-change-title"
                                >

                                    Previous Information

                                </div>


                                <div
                                    class="history-change-body"
                                >

                                    <?php

                                    if (
                                        history_contains_password(
                                            $old_decoded
                                        ) ||
                                        history_contains_password(
                                            $new_decoded
                                        ) ||
                                        $history[
                                            "update_section"
                                        ] === "password"
                                    ):

                                    ?>

                                        <div
                                            class="history-fields"
                                        >

                                            <div
                                                class="history-field-row"
                                            >

                                                <div
                                                    class="history-field-label"
                                                >
                                                    Password
                                                </div>

                                                <div
                                                    class="history-field-value"
                                                >
                                                    Password was changed
                                                </div>

                                            </div>

                                        </div>


                                    <?php else: ?>


                                        <?= render_history_value(
                                            $old_decoded
                                        ) ?>


                                    <?php endif; ?>


                                </div>


                            </div>


                            <!--
                            ==================================================
                            UPDATED
                            ==================================================
                            -->

                            <div
                                class="history-change-panel"
                            >


                                <div
                                    class="history-change-title"
                                >

                                    Updated Information

                                </div>


                                <div
                                    class="history-change-body"
                                >

                                    <?php

                                    if (
                                        history_contains_password(
                                            $old_decoded
                                        ) ||
                                        history_contains_password(
                                            $new_decoded
                                        ) ||
                                        $history[
                                            "update_section"
                                        ] === "password"
                                    ):

                                    ?>

                                        <div
                                            class="history-fields"
                                        >

                                            <div
                                                class="history-field-row"
                                            >

                                                <div
                                                    class="history-field-label"
                                                >
                                                    Password
                                                </div>

                                                <div
                                                    class="history-field-value"
                                                >
                                                    Password was changed
                                                </div>

                                            </div>

                                        </div>


                                    <?php else: ?>


                                        <?= render_history_value(
                                            $new_decoded
                                        ) ?>


                                    <?php endif; ?>


                                </div>


                            </div>


                        </div>


                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <div
                    class="history-no-records"
                >

                    No update history is available for this resident.

                </div>


            <?php endif; ?>


        </div>


    </div>


</div>


<?php endif; ?>


</div>


<script src="../assets/js/script.js"></script>


<script>

/*
|--------------------------------------------------------------------------
| History Modal
|--------------------------------------------------------------------------
*/

(function () {

    const modal =
        document.getElementById(
            "historyModal"
        );


    if (!modal) {
        return;
    }


    const closeButtons =
        modal.querySelectorAll(
            "[data-close-history-modal]"
        );


    function closeModal() {

        const url =
            new URL(
                window.location.href
            );


        url.searchParams.delete(
            "resident_id"
        );


        window.location.href =
            url.toString();

    }


    closeButtons.forEach(
        function (button) {

            button.addEventListener(
                "click",
                closeModal
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Escape key
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Escape"
            ) {

                closeModal();

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Prevent background scrolling while modal is open
    |--------------------------------------------------------------------------
    */

    document.body.style.overflow =
        "hidden";

})();

</script>


</body>

</html>