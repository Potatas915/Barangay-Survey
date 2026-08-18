<?php

require_once __DIR__ . "/../includes/functions.php";
require_resident_login();

$resident_id = (int) $_SESSION["resident_id"];

$success = "";
$error = "";

$password_success = "";
$password_error = "";

$photo_success = "";
$photo_error = "";

$allowed_civil_statuses = [
    "Single",
    "Married",
    "Widowed",
    "Separated",
    "Divorced"
];


/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

function calculate_age_from_birthday($birthday)
{
    if (empty($birthday)) {
        return null;
    }

    $birthday_date = DateTime::createFromFormat(
        "Y-m-d",
        $birthday
    );

    if (
        !$birthday_date ||
        $birthday_date->format("Y-m-d") !== $birthday
    ) {
        return null;
    }

    $today = new DateTime("today");

    return $today->diff($birthday_date)->y;
}


/*
|--------------------------------------------------------------------------
| Update Complete Resident Profile
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_profile"])
) {

    /*
    |--------------------------------------------------------------------------
    | Personal Information
    |--------------------------------------------------------------------------
    */

    $middle_name = trim(
        $_POST["middle_name"] ?? ""
    );

    $extension_name = trim(
        $_POST["extension_name"] ?? ""
    );

    $civil_status = trim(
        $_POST["civil_status"] ?? ""
    );

    $birthday = trim(
        $_POST["birthday"] ?? ""
    );

    $occupation = trim(
        $_POST["occupation"] ?? ""
    );

    $employer = trim(
        $_POST["employer"] ?? ""
    );

    $employer_address = trim(
        $_POST["employer_address"] ?? ""
    );

    $email = trim(
        $_POST["email"] ?? ""
    );

    $contact_number = trim(
        $_POST["contact_number"] ?? ""
    );

    $address = trim(
        $_POST["address"] ?? ""
    );


    /*
    |--------------------------------------------------------------------------
    | Spouse
    |--------------------------------------------------------------------------
    */

    $spouse_name = trim(
        $_POST["spouse_name"] ?? ""
    );

    $spouse_occupation = trim(
        $_POST["spouse_occupation"] ?? ""
    );

    $spouse_employer = trim(
        $_POST["spouse_employer"] ?? ""
    );


    /*
    |--------------------------------------------------------------------------
    | Parents
    |--------------------------------------------------------------------------
    */

    $father_name = trim(
        $_POST["father_name"] ?? ""
    );

    $mother_name = trim(
        $_POST["mother_name"] ?? ""
    );


    /*
    |--------------------------------------------------------------------------
    | Children
    |--------------------------------------------------------------------------
    */

    $children_names =
        isset($_POST["child_name"]) &&
        is_array($_POST["child_name"])
            ? $_POST["child_name"]
            : [];

    $children_ages =
        isset($_POST["child_age"]) &&
        is_array($_POST["child_age"])
            ? $_POST["child_age"]
            : [];


    /*
    |--------------------------------------------------------------------------
    | Character References
    |--------------------------------------------------------------------------
    */

    $reference_names =
        isset($_POST["reference_name"]) &&
        is_array($_POST["reference_name"])
            ? $_POST["reference_name"]
            : [];


    /*
    |--------------------------------------------------------------------------
    | Birthday Validation
    |--------------------------------------------------------------------------
    */

    $age = null;

    if ($birthday !== "") {

        $birthday_date = DateTime::createFromFormat(
            "Y-m-d",
            $birthday
        );

        if (
            !$birthday_date ||
            $birthday_date->format("Y-m-d") !== $birthday
        ) {

            $error =
                "Please enter a valid birthday.";

        }
        elseif (
            $birthday_date > new DateTime("today")
        ) {

            $error =
                "Birthday cannot be in the future.";

        }
        else {

            $age =
                calculate_age_from_birthday(
                    $birthday
                );

        }
    }


    /*
    |--------------------------------------------------------------------------
    | Civil Status Validation
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        $civil_status !== "" &&
        !in_array(
            $civil_status,
            $allowed_civil_statuses,
            true
        )
    ) {

        $error =
            "Please select a valid civil status.";

    }


    /*
    |--------------------------------------------------------------------------
    | Email Validation
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        $email !== "" &&
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "Please enter a valid email address.";

    }


    /*
    |--------------------------------------------------------------------------
    | Contact Number Validation
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        $contact_number !== "" &&
        !preg_match(
            '/^[0-9+\-\s()]{7,20}$/',
            $contact_number
        )
    ) {

        $error =
            "Please enter a valid contact number.";

    }


    /*
    |--------------------------------------------------------------------------
    | Children Validation
    |--------------------------------------------------------------------------
    */

    $clean_children = [];

    if ($error === "") {

        $children_count = max(
            count($children_names),
            count($children_ages)
        );

        for (
            $i = 0;
            $i < $children_count;
            $i++
        ) {

            $child_name = trim(
                $children_names[$i] ?? ""
            );

            $child_age_raw = trim(
                (string) (
                    $children_ages[$i] ?? ""
                )
            );


            /*
            |--------------------------------------------------------------------------
            | Ignore completely empty rows
            |--------------------------------------------------------------------------
            */

            if (
                $child_name === "" &&
                $child_age_raw === ""
            ) {

                continue;

            }


            if ($child_name === "") {

                $error =
                    "Please provide a name for every child.";

                break;

            }


            $child_age = null;

            if ($child_age_raw !== "") {

                if (
                    !ctype_digit($child_age_raw) ||
                    (int) $child_age_raw < 0 ||
                    (int) $child_age_raw > 150
                ) {

                    $error =
                        "Please enter a valid age for every child.";

                    break;

                }

                $child_age =
                    (int) $child_age_raw;
            }


            $clean_children[] = [
                "name" => $child_name,
                "age" => $child_age
            ];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Character Reference Validation
    |--------------------------------------------------------------------------
    |
    | The resident profile requires two character references.
    |
    */

    $clean_references = [];

    if ($error === "") {

        for ($i = 0; $i < 2; $i++) {

            $reference_name = trim(
                $reference_names[$i] ?? ""
            );

            if ($reference_name === "") {

                $error =
                    "Please provide both character references.";

                break;

            }

            $clean_references[] = [
                "name" => $reference_name,
                "order" => $i + 1
            ];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Save Complete Profile
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        try {

            $conn->begin_transaction();


            /*
            |--------------------------------------------------------------------------
            | Get Existing Resident Data For History
            |--------------------------------------------------------------------------
            */

            $old_stmt = $conn->prepare("
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
                LIMIT 1
            ");

            $old_stmt->bind_param(
                "i",
                $resident_id
            );

            $old_stmt->execute();

            $old_resident =
                $old_stmt
                    ->get_result()
                    ->fetch_assoc();


            if (!$old_resident) {

                throw new Exception(
                    "Resident record was not found."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | Update Resident Personal Information
            |--------------------------------------------------------------------------
            */

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

            if (!$stmt->execute()) {

                throw new Exception(
                    "Unable to update personal information."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | Spouse
            |--------------------------------------------------------------------------
            */

            $spouse_check = $conn->prepare("
                SELECT spouse_id
                FROM resident_spouses
                WHERE resident_id = ?
                LIMIT 1
            ");

            $spouse_check->bind_param(
                "i",
                $resident_id
            );

            $spouse_check->execute();

            $existing_spouse =
                $spouse_check
                    ->get_result()
                    ->fetch_assoc();


            if (
                $spouse_name !== "" ||
                $spouse_occupation !== "" ||
                $spouse_employer !== ""
            ) {

                if ($existing_spouse) {

                    $spouse_update = $conn->prepare("
                        UPDATE resident_spouses
                        SET
                            spouse_name = ?,
                            occupation = ?,
                            employer = ?
                        WHERE resident_id = ?
                    ");

                    $spouse_update->bind_param(
                        "sssi",
                        $spouse_name,
                        $spouse_occupation,
                        $spouse_employer,
                        $resident_id
                    );

                    if (!$spouse_update->execute()) {

                        throw new Exception(
                            "Unable to update spouse information."
                        );

                    }

                }
                else {

                    $spouse_insert = $conn->prepare("
                        INSERT INTO resident_spouses
                        (
                            resident_id,
                            spouse_name,
                            occupation,
                            employer
                        )
                        VALUES (?, ?, ?, ?)
                    ");

                    $spouse_insert->bind_param(
                        "isss",
                        $resident_id,
                        $spouse_name,
                        $spouse_occupation,
                        $spouse_employer
                    );

                    if (!$spouse_insert->execute()) {

                        throw new Exception(
                            "Unable to save spouse information."
                        );

                    }
                }

            }
            else {

                if ($existing_spouse) {

                    $spouse_delete = $conn->prepare("
                        DELETE FROM resident_spouses
                        WHERE resident_id = ?
                    ");

                    $spouse_delete->bind_param(
                        "i",
                        $resident_id
                    );

                    if (!$spouse_delete->execute()) {

                        throw new Exception(
                            "Unable to remove spouse information."
                        );

                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Parents
            |--------------------------------------------------------------------------
            */

            $parents_check = $conn->prepare("
                SELECT parent_id
                FROM resident_parents
                WHERE resident_id = ?
                LIMIT 1
            ");

            $parents_check->bind_param(
                "i",
                $resident_id
            );

            $parents_check->execute();

            $existing_parents =
                $parents_check
                    ->get_result()
                    ->fetch_assoc();


            if (
                $father_name !== "" ||
                $mother_name !== ""
            ) {

                if ($existing_parents) {

                    $parents_update = $conn->prepare("
                        UPDATE resident_parents
                        SET
                            father_name = ?,
                            mother_name = ?
                        WHERE resident_id = ?
                    ");

                    $parents_update->bind_param(
                        "ssi",
                        $father_name,
                        $mother_name,
                        $resident_id
                    );

                    if (!$parents_update->execute()) {

                        throw new Exception(
                            "Unable to update parent information."
                        );

                    }

                }
                else {

                    $parents_insert = $conn->prepare("
                        INSERT INTO resident_parents
                        (
                            resident_id,
                            father_name,
                            mother_name
                        )
                        VALUES (?, ?, ?)
                    ");

                    $parents_insert->bind_param(
                        "iss",
                        $resident_id,
                        $father_name,
                        $mother_name
                    );

                    if (!$parents_insert->execute()) {

                        throw new Exception(
                            "Unable to save parent information."
                        );

                    }
                }

            }
            else {

                if ($existing_parents) {

                    $parents_delete = $conn->prepare("
                        DELETE FROM resident_parents
                        WHERE resident_id = ?
                    ");

                    $parents_delete->bind_param(
                        "i",
                        $resident_id
                    );

                    if (!$parents_delete->execute()) {

                        throw new Exception(
                            "Unable to remove parent information."
                        );

                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Children
            |--------------------------------------------------------------------------
            |
            | Replace the current children list for this resident.
            | This prevents duplicate records when editing.
            |
            */

            $delete_children = $conn->prepare("
                DELETE FROM resident_children
                WHERE resident_id = ?
            ");

            $delete_children->bind_param(
                "i",
                $resident_id
            );

            if (!$delete_children->execute()) {

                throw new Exception(
                    "Unable to update children information."
                );

            }


            if (!empty($clean_children)) {

                $child_insert = $conn->prepare("
                    INSERT INTO resident_children
                    (
                        resident_id,
                        child_name,
                        age
                    )
                    VALUES (?, ?, ?)
                ");

                foreach (
                    $clean_children
                    as $child
                ) {

                    $child_age =
                        $child["age"];

                    $child_insert->bind_param(
                        "isi",
                        $resident_id,
                        $child["name"],
                        $child_age
                    );

                    if (!$child_insert->execute()) {

                        throw new Exception(
                            "Unable to save children information."
                        );

                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Character References
            |--------------------------------------------------------------------------
            |
            | Existing signatures are intentionally preserved.
            | Only the reference names are updated by this form.
            |
            */

            $existing_reference_signatures = [];

            $signature_stmt = $conn->prepare("
                SELECT
                    reference_order,
                    signature
                FROM resident_references
                WHERE resident_id = ?
                ORDER BY reference_order ASC
            ");

            $signature_stmt->bind_param(
                "i",
                $resident_id
            );

            $signature_stmt->execute();

            $signature_result =
                $signature_stmt->get_result();

            while (
                $signature_row =
                    $signature_result->fetch_assoc()
            ) {

                $existing_reference_signatures[
                    (int) $signature_row["reference_order"]
                ] =
                    $signature_row["signature"];
            }


            $delete_references = $conn->prepare("
                DELETE FROM resident_references
                WHERE resident_id = ?
            ");

            $delete_references->bind_param(
                "i",
                $resident_id
            );

            if (!$delete_references->execute()) {

                throw new Exception(
                    "Unable to update character references."
                );

            }


            $reference_insert = $conn->prepare("
                INSERT INTO resident_references
                (
                    resident_id,
                    reference_name,
                    signature,
                    reference_order
                )
                VALUES (?, ?, ?, ?)
            ");

            foreach (
                $clean_references
                as $reference
            ) {

                $reference_order =
                    $reference["order"];

                $signature =
                    $existing_reference_signatures[
                        $reference_order
                    ] ?? null;

                $reference_insert->bind_param(
                    "issi",
                    $resident_id,
                    $reference["name"],
                    $signature,
                    $reference_order
                );

                if (!$reference_insert->execute()) {

                    throw new Exception(
                        "Unable to save character references."
                    );

                }
            }


            /*
            |--------------------------------------------------------------------------
            | Record Update History
            |--------------------------------------------------------------------------
            */

            $new_data = [
                "personal_information" => [
                    "middle_name" => $middle_name,
                    "extension_name" => $extension_name,
                    "civil_status" => $civil_status,
                    "birthday" => $birthday,
                    "age" => $age,
                    "occupation" => $occupation,
                    "employer" => $employer,
                    "employer_address" => $employer_address,
                    "email" => $email,
                    "contact_number" => $contact_number,
                    "address" => $address
                ],

                "spouse" => [
                    "name" => $spouse_name,
                    "occupation" => $spouse_occupation,
                    "employer" => $spouse_employer
                ],

                "parents" => [
                    "father_name" => $father_name,
                    "mother_name" => $mother_name
                ],

                "children" => $clean_children,

                "references" => $clean_references
            ];


            $old_data = [
                "personal_information" => $old_resident
            ];


            $history_section =
                "complete_profile";

            $history_old =
                json_encode(
                    $old_data,
                    JSON_UNESCAPED_UNICODE
                );

            $history_new =
                json_encode(
                    $new_data,
                    JSON_UNESCAPED_UNICODE
                );

            $history_type =
                "resident";

            $history_user_id =
                $resident_id;


            $history_stmt = $conn->prepare("
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

            $history_stmt->bind_param(
                "isisss",
                $resident_id,
                $history_type,
                $history_user_id,
                $history_section,
                $history_old,
                $history_new
            );

            if (!$history_stmt->execute()) {

                throw new Exception(
                    "Unable to record profile update history."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | Commit
            |--------------------------------------------------------------------------
            */

            $conn->commit();

            $success =
                "Your complete profile was updated successfully.";

        }
        catch (Throwable $exception) {

            if (
                $conn->errno === 0 ||
                true
            ) {
                try {
                    $conn->rollback();
                }
                catch (Throwable $rollback_exception) {
                    // Ignore rollback failure.
                }
            }

            $error =
                "Unable to update your complete profile. Please try again.";
        }
    }
}


/*
|--------------------------------------------------------------------------
| Upload / Update Resident Photo
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["upload_photo"])
) {

    if (
        !isset($_FILES["photo"]) ||
        $_FILES["photo"]["error"] === UPLOAD_ERR_NO_FILE
    ) {

        $photo_error =
            "Please select a photo to upload.";

    }
    elseif (
        $_FILES["photo"]["error"] !== UPLOAD_ERR_OK
    ) {

        $photo_error =
            "There was a problem uploading the photo.";

    }
    else {

        $photo =
            $_FILES["photo"];

        $max_file_size =
            5 * 1024 * 1024;


        if (
            $photo["size"] > $max_file_size
        ) {

            $photo_error =
                "Photo must not exceed 5 MB.";

        }
        else {

            $image_info =
                @getimagesize(
                    $photo["tmp_name"]
                );


            if ($image_info === false) {

                $photo_error =
                    "The uploaded file must be a valid image.";

            }
            else {

                $allowed_types = [
                    IMAGETYPE_JPEG => "jpg",
                    IMAGETYPE_PNG => "png",
                    IMAGETYPE_WEBP => "webp"
                ];


                $image_type =
                    $image_info[2];


                if (
                    !isset(
                        $allowed_types[$image_type]
                    )
                ) {

                    $photo_error =
                        "Only JPG, PNG, and WEBP images are allowed.";

                }
                else {

                    $extension =
                        $allowed_types[$image_type];


                    $upload_directory =
                        __DIR__ .
                        "/../uploads/residents";


                    if (
                        !is_dir(
                            $upload_directory
                        ) &&
                        !mkdir(
                            $upload_directory,
                            0755,
                            true
                        )
                    ) {

                        $photo_error =
                            "Unable to create the photo upload directory.";

                    }
                    else {

                        $file_name =
                            "resident_" .
                            $resident_id .
                            "_" .
                            bin2hex(
                                random_bytes(8)
                            ) .
                            "." .
                            $extension;


                        $destination =
                            $upload_directory .
                            "/" .
                            $file_name;


                        if (
                            move_uploaded_file(
                                $photo["tmp_name"],
                                $destination
                            )
                        ) {

                            $photo_path =
                                "uploads/residents/" .
                                $file_name;


                            /*
                            |--------------------------------------------------------------------------
                            | Get Existing Photo
                            |--------------------------------------------------------------------------
                            */

                            $old_photo_stmt =
                                $conn->prepare("
                                    SELECT photo
                                    FROM residents
                                    WHERE resident_id = ?
                                    LIMIT 1
                                ");

                            $old_photo_stmt->bind_param(
                                "i",
                                $resident_id
                            );

                            $old_photo_stmt->execute();

                            $old_photo =
                                $old_photo_stmt
                                    ->get_result()
                                    ->fetch_assoc();


                            /*
                            |--------------------------------------------------------------------------
                            | Save New Photo
                            |--------------------------------------------------------------------------
                            */

                            $update_photo =
                                $conn->prepare("
                                    UPDATE residents
                                    SET photo = ?
                                    WHERE resident_id = ?
                                ");

                            $update_photo->bind_param(
                                "si",
                                $photo_path,
                                $resident_id
                            );


                            if (
                                $update_photo->execute()
                            ) {

                                /*
                                |--------------------------------------------------------------------------
                                | Delete Previous Photo
                                |--------------------------------------------------------------------------
                                */

                                if (
                                    !empty(
                                        $old_photo["photo"]
                                    )
                                ) {

                                    $old_photo_file =
                                        __DIR__ .
                                        "/../" .
                                        ltrim(
                                            $old_photo["photo"],
                                            "/"
                                        );


                                    if (
                                        is_file(
                                            $old_photo_file
                                        )
                                    ) {

                                        @unlink(
                                            $old_photo_file
                                        );
                                    }
                                }


                                $photo_success =
                                    "Profile photo updated successfully.";

                            }
                            else {

                                @unlink(
                                    $destination
                                );

                                $photo_error =
                                    "Unable to save the profile photo.";

                            }

                        }
                        else {

                            $photo_error =
                                "Unable to upload the photo.";

                        }
                    }
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Change Password
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["change_password"])
) {

    $current_password =
        $_POST["current_password"] ?? "";

    $new_password =
        $_POST["new_password"] ?? "";

    $confirm_password =
        $_POST["confirm_password"] ?? "";


    $stmt = $conn->prepare("
        SELECT password
        FROM residents
        WHERE resident_id = ?
        LIMIT 1
    ");

    $stmt->bind_param(
        "i",
        $resident_id
    );

    $stmt->execute();

    $row =
        $stmt
            ->get_result()
            ->fetch_assoc();


    if (
        !$row ||
        !password_verify(
            $current_password,
            $row["password"]
        )
    ) {

        $password_error =
            "Current password is incorrect.";

    }
    elseif (
        strlen($new_password) < 6
    ) {

        $password_error =
            "New password must be at least 6 characters.";

    }
    elseif (
        $new_password !== $confirm_password
    ) {

        $password_error =
            "New passwords do not match.";

    }
    else {

        $hashed =
            password_hash(
                $new_password,
                PASSWORD_DEFAULT
            );


        $update =
            $conn->prepare("
                UPDATE residents
                SET password = ?
                WHERE resident_id = ?
            ");

        $update->bind_param(
            "si",
            $hashed,
            $resident_id
        );


        if ($update->execute()) {

            $password_success =
                "Password changed successfully.";

        }
        else {

            $password_error =
                "Unable to change your password.";

        }
    }
}


/*
|--------------------------------------------------------------------------
| Load Resident
|--------------------------------------------------------------------------
*/

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
        address,
        photo
    FROM residents
    WHERE resident_id = ?
    LIMIT 1
");

$stmt->bind_param(
    "i",
    $resident_id
);

$stmt->execute();

$resident =
    $stmt
        ->get_result()
        ->fetch_assoc();


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
        "address" => "",
        "photo" => ""
    ];
}


/*
|--------------------------------------------------------------------------
| Load Spouse
|--------------------------------------------------------------------------
*/

$spouse = [
    "spouse_name" => "",
    "occupation" => "",
    "employer" => ""
];


$spouse_stmt = $conn->prepare("
    SELECT
        spouse_name,
        occupation,
        employer
    FROM resident_spouses
    WHERE resident_id = ?
    LIMIT 1
");

$spouse_stmt->bind_param(
    "i",
    $resident_id
);

$spouse_stmt->execute();

$spouse_result =
    $spouse_stmt
        ->get_result()
        ->fetch_assoc();


if ($spouse_result) {
    $spouse = $spouse_result;
}


/*
|--------------------------------------------------------------------------
| Load Parents
|--------------------------------------------------------------------------
*/

$parents = [
    "father_name" => "",
    "mother_name" => ""
];


$parents_stmt = $conn->prepare("
    SELECT
        father_name,
        mother_name
    FROM resident_parents
    WHERE resident_id = ?
    LIMIT 1
");

$parents_stmt->bind_param(
    "i",
    $resident_id
);

$parents_stmt->execute();

$parents_result =
    $parents_stmt
        ->get_result()
        ->fetch_assoc();


if ($parents_result) {
    $parents = $parents_result;
}


/*
|--------------------------------------------------------------------------
| Load Children
|--------------------------------------------------------------------------
*/

$children = [];


$children_stmt = $conn->prepare("
    SELECT
        child_id,
        child_name,
        age
    FROM resident_children
    WHERE resident_id = ?
    ORDER BY child_id ASC
");

$children_stmt->bind_param(
    "i",
    $resident_id
);

$children_stmt->execute();

$children_result =
    $children_stmt->get_result();


while (
    $child =
    $children_result->fetch_assoc()
) {

    $children[] = $child;
}


/*
|--------------------------------------------------------------------------
| Load Character References
|--------------------------------------------------------------------------
*/

$references = [];


$references_stmt = $conn->prepare("
    SELECT
        reference_id,
        reference_name,
        signature,
        reference_order
    FROM resident_references
    WHERE resident_id = ?
    ORDER BY reference_order ASC
");

$references_stmt->bind_param(
    "i",
    $resident_id
);

$references_stmt->execute();

$references_result =
    $references_stmt->get_result();


while (
    $reference =
    $references_result->fetch_assoc()
) {

    $references[] = $reference;
}


/*
|--------------------------------------------------------------------------
| Always Display Two Reference Rows
|--------------------------------------------------------------------------
*/

while (
    count($references) < 2
) {

    $references[] = [
        "reference_id" => null,
        "reference_name" => "",
        "signature" => "",
        "reference_order" =>
            count($references) + 1
    ];
}


/*
|--------------------------------------------------------------------------
| Full Resident Name
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

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>My Complete Profile</title>

<link
    rel="stylesheet"
    href="../assets/css/style.css?v=<?= filemtime(__DIR__ . "/../assets/css/style.css") ?>"
>

<script>

(function () {

    const theme =
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
    catch (error) {}

})();

</script>


<style>

/* =========================================================
   Complete Resident Profile
   ========================================================= */

.complete-profile {

    display: flex;

    flex-direction: column;

    gap: 24px;

}


.profile-section {

    width: 100%;

}


.profile-section-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 16px;

    margin-bottom: 20px;

}


.profile-section-header h2 {

    margin: 0;

}


.profile-section-description {

    margin: 4px 0 0;

    opacity: 0.7;

}


.complete-profile-grid {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 18px;

}


.complete-profile-field {

    display: flex;

    flex-direction: column;

    gap: 7px;

}


.complete-profile-field.full {

    grid-column: 1 / -1;

}


.complete-profile-field label {

    font-weight: 700;

}


.complete-profile-field input,
.complete-profile-field select,
.complete-profile-field textarea {

    width: 100%;

    box-sizing: border-box;

}


.complete-profile-field textarea {

    min-height: 90px;

    resize: vertical;

}


.readonly-field {

    background: #eeeeee !important;

    cursor: not-allowed;

}


.profile-photo-layout {

    display: grid;

    grid-template-columns:
        180px minmax(0, 1fr);

    gap: 28px;

    align-items: start;

}


.profile-photo-preview {

    width: 160px;

    height: 200px;

    border: 3px solid #111;

    background: #f5f5f5;

    display: flex;

    align-items: center;

    justify-content: center;

    overflow: hidden;

}


.profile-photo-preview img {

    width: 100%;

    height: 100%;

    object-fit: cover;

}


.profile-photo-placeholder {

    text-align: center;

    padding: 15px;

    font-weight: 700;

    opacity: 0.55;

}


.photo-upload-form {

    display: flex;

    flex-direction: column;

    gap: 14px;

}


.photo-upload-note {

    font-size: 13px;

    opacity: 0.7;

}


.children-table-wrapper,
.references-table-wrapper {

    width: 100%;

    overflow-x: auto;

}


.children-table,
.references-table {

    width: 100%;

    border-collapse: collapse;

}


.children-table th,
.children-table td,
.references-table th,
.references-table td {

    border: 1px solid #222;

    padding: 10px;

    text-align: left;

}


.children-table th,
.references-table th {

    font-weight: 800;

}


.children-table input,
.references-table input {

    width: 100%;

    box-sizing: border-box;

}


.table-action-cell {

    width: 70px;

    text-align: center !important;

}


.remove-row-btn {

    border: 2px solid #111;

    background: #fff;

    font-weight: 800;

    padding: 8px 10px;

    cursor: pointer;

}


.remove-row-btn:hover {

    background: #eee;

}


.add-row-btn {

    margin-top: 14px;

}


.profile-actions {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 12px;

    flex-wrap: wrap;

}


.profile-actions-left,
.profile-actions-right {

    display: flex;

    gap: 10px;

    align-items: center;

    flex-wrap: wrap;

}


@media (max-width: 800px) {

    .complete-profile-grid {

        grid-template-columns: 1fr;

    }


    .complete-profile-field.full {

        grid-column: auto;

    }


    .profile-photo-layout {

        grid-template-columns: 1fr;

    }

}


@media print {

    body {

        background: #fff !important;

        color: #000 !important;

    }


    .sidebar,
    .topbar,
    nav,
    .welcome-header,
    .profile-actions,
    .password-card,
    .photo-upload-card,
    .screen-only {

        display: none !important;

    }


    .container {

        margin: 0 !important;

        padding: 0 !important;

        width: 100% !important;

    }


    .complete-profile {

        gap: 15px;

    }


    .card {

        box-shadow: none !important;

        border: 1px solid #000 !important;

        break-inside: avoid;

    }


    .profile-section {

        break-inside: avoid;

    }


    input,
    select,
    textarea {

        border: 0 !important;

        padding: 0 !important;

        background: transparent !important;

        box-shadow: none !important;

    }


    .remove-row-btn,
    .add-row-btn {

        display: none !important;

    }

}

</style>

</head>


<body>

<?php
include __DIR__ . "/../includes/resident_nav.php";
?>


<div class="container">

<?php
include __DIR__ . "/../includes/resident_topbar.php";
?>


<div class="welcome-header screen-only">

    <div>

        <h1>My Complete Profile</h1>

        <p>
            View and update your complete resident information.
        </p>

    </div>

</div>


<div class="complete-profile">


<!-- =========================================================
     PERSONAL INFORMATION
     ========================================================= -->

<div class="card profile-section">

    <div class="profile-section-header">

        <div>

            <h2>Personal Information</h2>

            <p class="profile-section-description">
                Update your complete personal information.
            </p>

        </div>

    </div>


    <?php if ($success): ?>

        <div class="success">
            <?= e($success) ?>
        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="error">
            <?= e($error) ?>
        </div>

    <?php endif; ?>


    <!--
    |--------------------------------------------------------------------------
    | ONE FORM FOR THE COMPLETE RESIDENT PROFILE
    |--------------------------------------------------------------------------
    -->

    <form
        method="POST"
        id="completeProfileForm"
    >

        <input
            type="hidden"
            name="update_profile"
            value="1"
        >


        <div class="complete-profile-grid">


            <!-- Resident Number -->

            <div class="complete-profile-field">

                <label>
                    Resident Number
                </label>

                <input
                    type="text"
                    class="readonly-field"
                    value="<?= e(
                        $resident["resident_number"]
                    ) ?>"
                    readonly
                >

            </div>


            <!-- Civil Status -->

            <div class="complete-profile-field">

                <label>
                    Civil Status
                </label>

                <select
                    name="civil_status"
                    required
                >

                    <option value="">
                        Select Civil Status
                    </option>

                    <?php foreach (
                        $allowed_civil_statuses
                        as $status
                    ): ?>

                        <option
                            value="<?= e($status) ?>"
                            <?= $resident["civil_status"] === $status
                                ? "selected"
                                : ""
                            ?>
                        >
                            <?= e($status) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Last Name -->

            <div class="complete-profile-field">

                <label>
                    Last Name
                </label>

                <input
                    type="text"
                    class="readonly-field"
                    value="<?= e(
                        $resident["last_name"]
                    ) ?>"
                    readonly
                >

            </div>


            <!-- First Name -->

            <div class="complete-profile-field">

                <label>
                    First Name
                </label>

                <input
                    type="text"
                    class="readonly-field"
                    value="<?= e(
                        $resident["first_name"]
                    ) ?>"
                    readonly
                >

            </div>


            <!-- Middle Name -->

            <div class="complete-profile-field">

                <label>
                    Middle Name
                </label>

                <input
                    type="text"
                    name="middle_name"
                    value="<?= e(
                        $resident["middle_name"]
                    ) ?>"
                    maxlength="100"
                >

            </div>


            <!-- Extension Name -->

            <div class="complete-profile-field">

                <label>
                    Extension Name
                </label>

                <input
                    type="text"
                    name="extension_name"
                    placeholder="Jr., Sr., III"
                    value="<?= e(
                        $resident["extension_name"]
                    ) ?>"
                    maxlength="20"
                >

            </div>


            <!-- Birthday -->

            <div class="complete-profile-field">

                <label>
                    Birthday
                </label>

                <input
                    type="date"
                    name="birthday"
                    id="birthday"
                    max="<?= date("Y-m-d") ?>"
                    value="<?= e(
                        $resident["birthday"]
                    ) ?>"
                >

            </div>


            <!-- Age -->

            <div class="complete-profile-field">

                <label>
                    Age
                </label>

                <input
                    type="number"
                    id="age"
                    class="readonly-field"
                    value="<?= e(
                        $resident["age"]
                    ) ?>"
                    readonly
                >

                <small>
                    Age is automatically calculated from your birthday.
                </small>

            </div>


            <!-- Occupation -->

            <div class="complete-profile-field">

                <label>
                    Occupation
                </label>

                <input
                    type="text"
                    name="occupation"
                    value="<?= e(
                        $resident["occupation"]
                    ) ?>"
                    maxlength="150"
                >

            </div>


            <!-- Employer -->

            <div class="complete-profile-field">

                <label>
                    Employer
                </label>

                <input
                    type="text"
                    name="employer"
                    value="<?= e(
                        $resident["employer"]
                    ) ?>"
                    maxlength="150"
                >

            </div>


            <!-- Employer Address -->

            <div class="complete-profile-field full">

                <label>
                    Employer Address
                </label>

                <input
                    type="text"
                    name="employer_address"
                    value="<?= e(
                        $resident["employer_address"]
                    ) ?>"
                    maxlength="255"
                >

            </div>


            <!-- Email -->

            <div class="complete-profile-field">

                <label>
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    value="<?= e(
                        $resident["email"]
                    ) ?>"
                    maxlength="150"
                >

            </div>


            <!-- Contact Number -->

            <div class="complete-profile-field">

                <label>
                    Contact Number
                </label>

                <input
                    type="text"
                    name="contact_number"
                    value="<?= e(
                        $resident["contact_number"]
                    ) ?>"
                    maxlength="20"
                    pattern="[0-9+\-\s()]{7,20}"
                >

            </div>


            <!-- Address -->

            <div class="complete-profile-field full">

                <label>
                    Address
                </label>

                <textarea
                    name="address"
                    maxlength="255"
                ><?= e(
                    $resident["address"]
                ) ?></textarea>

            </div>

        </div>

    </form>

</div>



<!-- =========================================================
     SPOUSE
     ========================================================= -->

<div class="card profile-section">

    <div class="profile-section-header">

        <div>

            <h2>Spouse Information</h2>

            <p class="profile-section-description">
                Update spouse information when applicable.
            </p>

        </div>

    </div>


    <div class="complete-profile-grid">

        <div class="complete-profile-field">

            <label>
                Spouse Name
            </label>

            <input
                type="text"
                name="spouse_name"
                form="completeProfileForm"
                value="<?= e(
                    $spouse["spouse_name"]
                ) ?>"
                maxlength="150"
            >

        </div>


        <div class="complete-profile-field">

            <label>
                Occupation
            </label>

            <input
                type="text"
                name="spouse_occupation"
                form="completeProfileForm"
                value="<?= e(
                    $spouse["occupation"]
                ) ?>"
                maxlength="150"
            >

        </div>


        <div class="complete-profile-field">

            <label>
                Employer
            </label>

            <input
                type="text"
                name="spouse_employer"
                form="completeProfileForm"
                value="<?= e(
                    $spouse["employer"]
                ) ?>"
                maxlength="150"
            >

        </div>

    </div>

</div>



<!-- =========================================================
     CHILDREN
     ========================================================= -->

<div class="card profile-section">

    <div class="profile-section-header">

        <div>

            <h2>Children</h2>

            <p class="profile-section-description">
                Add, edit, or remove children from your profile.
            </p>

        </div>

    </div>


    <div class="children-table-wrapper">

        <table
            class="children-table"
        >

            <thead>

                <tr>

                    <th>
                        Child Name
                    </th>

                    <th>
                        Age
                    </th>

                    <th class="table-action-cell">
                        Action
                    </th>

                </tr>

            </thead>


            <tbody id="childrenTableBody">

            <?php if (!empty($children)): ?>

                <?php foreach (
                    $children
                    as $child
                ): ?>

                    <tr>

                        <td>

                            <input
                                type="text"
                                name="child_name[]"
                                form="completeProfileForm"
                                value="<?= e(
                                    $child["child_name"]
                                ) ?>"
                                maxlength="150"
                            >

                        </td>


                        <td>

                            <input
                                type="number"
                                name="child_age[]"
                                form="completeProfileForm"
                                min="0"
                                max="150"
                                value="<?= e(
                                    $child["age"]
                                ) ?>"
                            >

                        </td>


                        <td class="table-action-cell">

                            <button
                                type="button"
                                class="remove-row-btn"
                            >
                                Remove
                            </button>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>

                    <td>

                        <input
                            type="text"
                            name="child_name[]"
                            form="completeProfileForm"
                            maxlength="150"
                        >

                    </td>


                    <td>

                        <input
                            type="number"
                            name="child_age[]"
                            form="completeProfileForm"
                            min="0"
                            max="150"
                        >

                    </td>


                    <td class="table-action-cell">

                        <button
                            type="button"
                            class="remove-row-btn"
                        >
                            Remove
                        </button>

                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>


    <button
        type="button"
        class="btn add-row-btn"
        id="addChildBtn"
    >
        + Add Child
    </button>

</div>



<!-- =========================================================
     PARENTS
     ========================================================= -->

<div class="card profile-section">

    <div class="profile-section-header">

        <div>

            <h2>Parents</h2>

            <p class="profile-section-description">
                Update your father's and mother's information.
            </p>

        </div>

    </div>


    <div class="complete-profile-grid">

        <div class="complete-profile-field">

            <label>
                Father's Name
            </label>

            <input
                type="text"
                name="father_name"
                form="completeProfileForm"
                value="<?= e(
                    $parents["father_name"]
                ) ?>"
                maxlength="150"
            >

        </div>


        <div class="complete-profile-field">

            <label>
                Mother's Name
            </label>

            <input
                type="text"
                name="mother_name"
                form="completeProfileForm"
                value="<?= e(
                    $parents["mother_name"]
                ) ?>"
                maxlength="150"
            >

        </div>

    </div>

</div>



<!-- =========================================================
     CHARACTER REFERENCES
     ========================================================= -->

<div class="card profile-section">

    <div class="profile-section-header">

        <div>

            <h2>Character References</h2>

            <p class="profile-section-description">
                Update your two character references.
                Existing signatures are preserved.
            </p>

        </div>

    </div>


    <div class="references-table-wrapper">

        <table class="references-table">

            <thead>

                <tr>

                    <th>
                        Reference
                    </th>

                    <th>
                        Character Reference Name
                    </th>

                    <th>
                        Signature
                    </th>

                </tr>

            </thead>


            <tbody>

            <?php for (
                $i = 0;
                $i < 2;
                $i++
            ): ?>

                <tr>

                    <td>
                        Reference <?= $i + 1 ?>
                    </td>


                    <td>

                        <input
                            type="text"
                            name="reference_name[]"
                            form="completeProfileForm"
                            value="<?= e(
                                $references[$i]["reference_name"]
                            ) ?>"
                            maxlength="150"
                            required
                        >

                    </td>


                    <td>

                        <?php if (
                            !empty(
                                $references[$i]["signature"]
                            )
                        ): ?>

                            <span>
                                Signature uploaded
                            </span>

                        <?php else: ?>

                            <span
                                style="opacity:.6;"
                            >
                                Optional
                            </span>

                        <?php endif; ?>

                    </td>

                </tr>

            <?php endfor; ?>

            </tbody>

        </table>

    </div>

</div>



<!-- =========================================================
     PROFILE PHOTO
     ========================================================= -->

<div class="card profile-section photo-upload-card">

    <div class="profile-section-header">

        <div>

            <h2>Passport-Size Photo</h2>

            <p class="profile-section-description">
                Upload or replace your resident profile photo.
            </p>

        </div>

    </div>


    <div class="profile-photo-layout">


        <div>

            <div class="profile-photo-preview">

                <?php if (
                    !empty(
                        $resident["photo"]
                    )
                ): ?>

                    <img
    src="../<?= e(
        ltrim(
            $resident["photo"],
            "/"
        )
    ) ?>"
    alt="Resident Profile Photo"
>

                <?php else: ?>

                    <div class="profile-photo-placeholder">
                        No Photo
                    </div>

                <?php endif; ?>

            </div>

        </div>


        <div>

            <?php if ($photo_success): ?>

                <div class="success">
                    <?= e(
                        $photo_success
                    ) ?>
                </div>

            <?php endif; ?>


            <?php if ($photo_error): ?>

                <div class="error">
                    <?= e(
                        $photo_error
                    ) ?>
                </div>

            <?php endif; ?>


            <form
                method="POST"
                enctype="multipart/form-data"
                class="photo-upload-form"
            >

                <input
                    type="hidden"
                    name="upload_photo"
                    value="1"
                >


                <div class="complete-profile-field">

                    <label>
                        Choose Photo
                    </label>

                    <input
                        type="file"
                        name="photo"
                        accept="
                            .jpg,
                            .jpeg,
                            .png,
                            .webp,
                            image/jpeg,
                            image/png,
                            image/webp
                        "
                        required
                    >

                </div>


                <div class="photo-upload-note">

                    Maximum file size: 5 MB.
                    Allowed formats: JPG, PNG, WEBP.

                </div>


                <button
                    type="submit"
                    class="btn"
                >
                    Upload Photo
                </button>

            </form>

        </div>

    </div>

</div>



<!-- =========================================================
     SAVE / PRINT
     ========================================================= -->

<div class="card profile-section screen-only">

    <div class="profile-actions">

        <div class="profile-actions-left">

            <button
                type="submit"
                form="completeProfileForm"
                class="btn"
                id="saveCompleteProfileBtn"
            >
                Save Complete Profile
            </button>

        </div>


        <div class="profile-actions-right">

            <button
                type="button"
                class="btn btn-secondary"
                id="printProfileBtn"
            >
                Print Profile
            </button>

        </div>

    </div>

</div>



<!-- =========================================================
     PASSWORD
     ========================================================= -->

<div class="card profile-section password-card">

    <div class="profile-section-header">

        <div>

            <h2>Change Password</h2>

            <p class="profile-section-description">
                Change your resident account password.
            </p>

        </div>

    </div>


    <?php if ($password_success): ?>

        <div class="success">
            <?= e(
                $password_success
            ) ?>
        </div>

    <?php endif; ?>


    <?php if ($password_error): ?>

        <div class="error">
            <?= e(
                $password_error
            ) ?>
        </div>

    <?php endif; ?>


    <form
        method="POST"
        class="complete-profile-grid"
    >

        <input
            type="hidden"
            name="change_password"
            value="1"
        >


        <div class="complete-profile-field">

            <label>
                Current Password
            </label>

            <input
                type="password"
                name="current_password"
                required
            >

        </div>


        <div class="complete-profile-field">

            <label>
                New Password
            </label>

            <input
                type="password"
                name="new_password"
                minlength="6"
                required
            >

        </div>


        <div class="complete-profile-field">

            <label>
                Confirm New Password
            </label>

            <input
                type="password"
                name="confirm_password"
                minlength="6"
                required
            >

        </div>


        <div
            class="complete-profile-field"
            style="justify-content:flex-end;"
        >

            <button
                type="submit"
                class="btn"
            >
                Update Password
            </button>

        </div>

    </form>

</div>



</div>

</div>


<script src="../assets/js/script.js"></script>


<script>

/*
|--------------------------------------------------------------------------
| Resident Profile Editing
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    function () {


        /*
        |--------------------------------------------------------------------------
        | Automatic Age Calculation
        |--------------------------------------------------------------------------
        */

        const birthdayInput =
            document.getElementById(
                "birthday"
            );

        const ageInput =
            document.getElementById(
                "age"
            );


        function calculateAge() {

            if (
                !birthdayInput ||
                !ageInput
            ) {

                return;

            }


            if (
                !birthdayInput.value
            ) {

                ageInput.value = "";

                return;

            }


            const birthday =
                new Date(
                    birthdayInput.value +
                    "T00:00:00"
                );

            const today =
                new Date();


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
                    today.getDate() <
                    birthday.getDate()
                )
            ) {

                age--;

            }


            ageInput.value =
                age >= 0
                    ? age
                    : "";

        }


        if (birthdayInput) {

            birthdayInput.addEventListener(
                "change",
                calculateAge
            );

            birthdayInput.addEventListener(
                "input",
                calculateAge
            );

        }


        calculateAge();


        /*
        |--------------------------------------------------------------------------
        | Add Child Row
        |--------------------------------------------------------------------------
        */

        const addChildBtn =
            document.getElementById(
                "addChildBtn"
            );

        const childrenBody =
            document.getElementById(
                "childrenTableBody"
            );


        if (
            addChildBtn &&
            childrenBody
        ) {

            addChildBtn.addEventListener(
                "click",
                function () {

                    const row =
                        document.createElement(
                            "tr"
                        );


                    row.innerHTML = `

                        <td>

                            <input
                                type="text"
                                name="child_name[]"
                                form="completeProfileForm"
                                maxlength="150"
                            >

                        </td>

                        <td>

                            <input
                                type="number"
                                name="child_age[]"
                                form="completeProfileForm"
                                min="0"
                                max="150"
                            >

                        </td>

                        <td class="table-action-cell">

                            <button
                                type="button"
                                class="remove-row-btn"
                            >
                                Remove
                            </button>

                        </td>

                    `;


                    childrenBody.appendChild(
                        row
                    );


                    attachRemoveButton(
                        row.querySelector(
                            ".remove-row-btn"
                        )
                    );

                }
            );


            /*
            |--------------------------------------------------------------------------
            | Remove Child Row
            |--------------------------------------------------------------------------
            */

            function attachRemoveButton(
                button
            ) {

                if (!button) {

                    return;

                }


                button.addEventListener(
                    "click",
                    function () {

                        const row =
                            button.closest(
                                "tr"
                            );


                        if (row) {

                            row.remove();

                        }

                    }
                );

            }


            document
                .querySelectorAll(
                    ".remove-row-btn"
                )
                .forEach(
                    attachRemoveButton
                );

        }


        /*
        |--------------------------------------------------------------------------
        | Print Profile
        |--------------------------------------------------------------------------
        */

        const printButton =
            document.getElementById(
                "printProfileBtn"
            );


        if (printButton) {

            printButton.addEventListener(
                "click",
                function () {

                    window.print();

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Complete Profile Validation
        |--------------------------------------------------------------------------
        */

        const profileForm =
            document.getElementById(
                "completeProfileForm"
            );


        if (profileForm) {

            profileForm.addEventListener(
                "submit",
                function (event) {


                    /*
                    |--------------------------------------------------------------------------
                    | Native HTML Validation
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !profileForm.checkValidity()
                    ) {

                        event.preventDefault();

                        profileForm.reportValidity();

                        return;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Validate Birthday
                    |--------------------------------------------------------------------------
                    */

                    if (
                        birthdayInput &&
                        birthdayInput.value
                    ) {

                        const birthday =
                            new Date(
                                birthdayInput.value +
                                "T00:00:00"
                            );

                        const today =
                            new Date();

                        today.setHours(
                            0,
                            0,
                            0,
                            0
                        );


                        if (
                            birthday > today
                        ) {

                            event.preventDefault();

                            alert(
                                "Birthday cannot be in the future."
                            );

                            return;

                        }

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Validate Character References
                    |--------------------------------------------------------------------------
                    */

                    const referenceInputs =
                        document.querySelectorAll(
                            'input[name="reference_name[]"]'
                        );


                    let referenceCount = 0;


                    referenceInputs.forEach(
                        function (input) {

                            if (
                                input.value.trim() !== ""
                            ) {

                                referenceCount++;

                            }

                        }
                    );


                    if (
                        referenceCount < 2
                    ) {

                        event.preventDefault();

                        alert(
                            "Please provide both character references."
                        );

                        return;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Confirm Save
                    |--------------------------------------------------------------------------
                    */

                    const confirmed =
                        confirm(
                            "Save your complete resident profile?"
                        );


                    if (!confirmed) {

                        event.preventDefault();

                    }

                }
            );

        }

    }
);

</script>

</body>

</html>