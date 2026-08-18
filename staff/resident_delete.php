<?php

require_once __DIR__ . "/../includes/functions.php";
require_staff_login();


/*
|--------------------------------------------------------------------------
| Only allow POST requests
|--------------------------------------------------------------------------
|
| Residents must never be deleted through a normal GET request.
|
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    redirect(
        "resident_management.php?error=" .
        urlencode(
            "Invalid deletion request."
        )
    );

}


/*
|--------------------------------------------------------------------------
| Validate Resident ID
|--------------------------------------------------------------------------
*/

$resident_id = filter_input(
    INPUT_POST,
    "resident_id",
    FILTER_VALIDATE_INT
);


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
| Find the resident before deleting
|--------------------------------------------------------------------------
|
| We retrieve the resident first so we can:
|
| 1. Confirm that the resident exists.
| 2. Get the resident's name for the success message.
| 3. Get the photo path so the physical image can also be deleted.
|
*/

$stmt = $conn->prepare("
    SELECT
        resident_id,
        resident_number,
        first_name,
        middle_name,
        last_name,
        extension_name,
        photo
    FROM residents
    WHERE resident_id = ?
    LIMIT 1
");


if (!$stmt) {

    redirect(
        "resident_management.php?error=" .
        urlencode(
            "Unable to prepare the resident lookup."
        )
    );

}


$stmt->bind_param(
    "i",
    $resident_id
);


if (!$stmt->execute()) {

    redirect(
        "resident_management.php?error=" .
        urlencode(
            "Unable to retrieve the resident."
        )
    );

}


$result = $stmt->get_result();

$resident = $result->fetch_assoc();


if (!$resident) {

    redirect(
        "resident_management.php?error=" .
        urlencode(
            "Resident not found."
        )
    );

}


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
| Remember Existing Photo
|--------------------------------------------------------------------------
|
| The database record will be deleted first.
| Therefore we save the old photo path before deleting it.
|
*/

$old_photo = trim(
    $resident["photo"] ?? ""
);


/*
|--------------------------------------------------------------------------
| Start Database Transaction
|--------------------------------------------------------------------------
*/

try {

    $conn->begin_transaction();


    /*
    |--------------------------------------------------------------------------
    | Delete Resident
    |--------------------------------------------------------------------------
    |
    | The database's ON DELETE CASCADE relationships will automatically
    | remove the resident's related records.
    |
    | This includes:
    |
    | - resident_spouses
    | - resident_children
    | - resident_parents
    | - resident_references
    | - resident_update_history
    | - responses
    |
    */


    $delete = $conn->prepare("
        DELETE FROM residents
        WHERE resident_id = ?
    ");


    if (!$delete) {

        throw new Exception(
            "Unable to prepare resident deletion."
        );

    }


    $delete->bind_param(
        "i",
        $resident_id
    );


    if (!$delete->execute()) {

        throw new Exception(
            "Unable to delete resident."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Confirm that exactly one resident was deleted
    |--------------------------------------------------------------------------
    */

    if ($delete->affected_rows !== 1) {

        throw new Exception(
            "Resident could not be deleted."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Commit
    |--------------------------------------------------------------------------
    */

    $conn->commit();

}
catch (Throwable $exception) {

    /*
    |--------------------------------------------------------------------------
    | Rollback
    |--------------------------------------------------------------------------
    |
    | If anything goes wrong, restore the database to its
    | previous state.
    |
    */

    try {

        $conn->rollback();

    }
    catch (Throwable $rollbackException) {

        // Nothing else to do if rollback fails.

    }


    redirect(
        "resident_management.php?error=" .
        urlencode(
            "Unable to delete resident. No changes were made."
        )
    );

}


/*
|--------------------------------------------------------------------------
| Delete Physical Resident Photo
|--------------------------------------------------------------------------
|
| The database only stores the photo path.
| The actual image file is stored separately on the server.
|
| Only files inside uploads/residents are allowed to be deleted.
|
*/

if ($old_photo !== "") {


    /*
    |--------------------------------------------------------------------------
    | Normalize path separators
    |--------------------------------------------------------------------------
    */

    $normalized_photo = str_replace(
        "\\",
        "/",
        $old_photo
    );


    /*
    |--------------------------------------------------------------------------
    | Reject unsafe paths
    |--------------------------------------------------------------------------
    */

    $is_unsafe_path =
        strpos(
            $normalized_photo,
            ".."
        ) !== false
        ||
        strpos(
            $normalized_photo,
            "/"
        ) === 0
        ||
        preg_match(
            '/^[A-Za-z]:[\/\\\\]/',
            $normalized_photo
        );


    if (!$is_unsafe_path) {


        /*
        |--------------------------------------------------------------------------
        | Build physical file path
        |--------------------------------------------------------------------------
        */

        $photo_file =
            __DIR__ .
            "/../" .
            ltrim(
                $normalized_photo,
                "/"
            );


        /*
        |--------------------------------------------------------------------------
        | Get allowed photo directory
        |--------------------------------------------------------------------------
        */

        $photo_directory = realpath(
            __DIR__ .
            "/../uploads/residents"
        );


        /*
        |--------------------------------------------------------------------------
        | Get actual photo path
        |--------------------------------------------------------------------------
        */

        $photo_real_path =
            is_file($photo_file)
                ? realpath($photo_file)
                : false;


        if (
            $photo_directory !== false &&
            $photo_real_path !== false
        ) {


            /*
            |--------------------------------------------------------------------------
            | Normalize directory path
            |--------------------------------------------------------------------------
            */

            $photo_directory =
                rtrim(
                    $photo_directory,
                    DIRECTORY_SEPARATOR
                ) .
                DIRECTORY_SEPARATOR;


            /*
            |--------------------------------------------------------------------------
            | Make sure the file is inside uploads/residents
            |--------------------------------------------------------------------------
            */

            if (
                strpos(
                    $photo_real_path,
                    $photo_directory
                ) === 0
            ) {

                @unlink(
                    $photo_real_path
                );

            }

        }

    }

}


/*
|--------------------------------------------------------------------------
| Return to Resident Management
|--------------------------------------------------------------------------
*/

redirect(
    "resident_management.php?success=" .
    urlencode(
        "Resident \"" .
        $full_name .
        "\" was permanently deleted."
    )
);

?>