<?php

require_once __DIR__ . "/../includes/functions.php";
require_staff_login();


/*
|--------------------------------------------------------------------------
| Basic Configuration
|--------------------------------------------------------------------------
*/

$allowed_civil_statuses = [
    "Single",
    "Married",
    "Widowed",
    "Separated",
    "Divorced"
];


$success = "";
$error = "";
$photo_success = "";
$photo_error = "";


/*
|--------------------------------------------------------------------------
| Get Resident ID
|--------------------------------------------------------------------------
*/

$resident_id = isset($_GET["resident_id"])
    ? (int) $_GET["resident_id"]
    : (int) ($_POST["resident_id"] ?? 0);


if ($resident_id <= 0) {

    header(
        "Location: resident_management.php"
    );

    exit;

}


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


    $birthday_date =
        DateTime::createFromFormat(
            "Y-m-d",
            $birthday
        );


    if (
        !$birthday_date ||
        $birthday_date->format("Y-m-d") !== $birthday
    ) {

        return null;

    }


    $today =
        new DateTime("today");


    return $today->diff(
        $birthday_date
    )->y;
}


function validate_person_name(
    $value,
    $field_name,
    $required = false
) {

    $value =
        trim($value);


    if (
        $required &&
        $value === ""
    ) {

        return $field_name .
            " is required.";

    }


    if ($value === "") {

        return "";

    }


    /*
    |--------------------------------------------------------------------------
    | Names may contain letters, spaces, periods, hyphens,
    | and apostrophes.
    |--------------------------------------------------------------------------
    */

    if (
        !preg_match(
            "/^[\p{L}\s.'-]+$/u",
            $value
        )
    ) {

        return $field_name .
            " may only contain letters, spaces, periods, apostrophes, and hyphens.";

    }


    return "";

}


/*
|--------------------------------------------------------------------------
| Load Existing Resident
|--------------------------------------------------------------------------
*/

function load_resident(
    $conn,
    $resident_id
) {

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
        WHERE resident_id = ?
        LIMIT 1
    ");


    $stmt->bind_param(
        "i",
        $resident_id
    );


    $stmt->execute();


    return $stmt
        ->get_result()
        ->fetch_assoc();
}


$resident =
    load_resident(
        $conn,
        $resident_id
    );


if (!$resident) {

    header(
        "Location: resident_management.php"
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| Process Complete Resident Update
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_resident"])
) {


    /*
    |--------------------------------------------------------------------------
    | Personal Information
    |--------------------------------------------------------------------------
    */

    $first_name =
        trim(
            $_POST["first_name"] ?? ""
        );

    $middle_name =
        trim(
            $_POST["middle_name"] ?? ""
        );

    $last_name =
        trim(
            $_POST["last_name"] ?? ""
        );

    $extension_name =
        trim(
            $_POST["extension_name"] ?? ""
        );

    $civil_status =
        trim(
            $_POST["civil_status"] ?? ""
        );

    $birthday =
        trim(
            $_POST["birthday"] ?? ""
        );

    $occupation =
        trim(
            $_POST["occupation"] ?? ""
        );

    $employer =
        trim(
            $_POST["employer"] ?? ""
        );

    $employer_address =
        trim(
            $_POST["employer_address"] ?? ""
        );

    $email =
        trim(
            $_POST["email"] ?? ""
        );

    $contact_number =
        trim(
            $_POST["contact_number"] ?? ""
        );

    $address =
        trim(
            $_POST["address"] ?? ""
        );


    /*
    |--------------------------------------------------------------------------
    | Spouse
    |--------------------------------------------------------------------------
    */

    $spouse_name =
        trim(
            $_POST["spouse_name"] ?? ""
        );

    $spouse_occupation =
        trim(
            $_POST["spouse_occupation"] ?? ""
        );

    $spouse_employer =
        trim(
            $_POST["spouse_employer"] ?? ""
        );


    /*
    |--------------------------------------------------------------------------
    | Parents
    |--------------------------------------------------------------------------
    */

    $father_name =
        trim(
            $_POST["father_name"] ?? ""
        );

    $mother_name =
        trim(
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
    | Validate Required Names
    |--------------------------------------------------------------------------
    */

    $name_fields = [
        [
            $first_name,
            "First Name",
            true
        ],
        [
            $middle_name,
            "Middle Name",
            false
        ],
        [
            $last_name,
            "Last Name",
            true
        ],
        [
            $extension_name,
            "Extension Name",
            false
        ]
    ];


    foreach (
        $name_fields
        as $field
    ) {

        $validation_error =
            validate_person_name(
                $field[0],
                $field[1],
                $field[2]
            );


        if (
            $validation_error !== ""
        ) {

            $error =
                $validation_error;

            break;

        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Civil Status
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
    | Validate Birthday and Calculate Age
    |--------------------------------------------------------------------------
    */

    $age = null;


    if (
        $error === "" &&
        $birthday !== ""
    ) {

        $birthday_date =
            DateTime::createFromFormat(
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
            $birthday_date >
            new DateTime("today")
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
    | Validate Email
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
    | Validate Contact Number
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        $contact_number !== ""
    ) {

        /*
        |--------------------------------------------------------------------------
        | Remove common formatting characters first.
        |--------------------------------------------------------------------------
        */

        $contact_digits =
            preg_replace(
                "/\D/",
                "",
                $contact_number
            );


        if (
            strlen($contact_digits) !== 11
        ) {

            $error =
                "Contact number must contain exactly 11 digits.";

        }
        elseif (
            !preg_match(
                "/^09[0-9]{9}$/",
                $contact_digits
            )
        ) {

            $error =
                "Please enter a valid Philippine mobile number starting with 09.";

        }
        else {

            /*
            |--------------------------------------------------------------------------
            | Store the clean 11-digit number.
            |--------------------------------------------------------------------------
            */

            $contact_number =
                $contact_digits;

        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Occupation / Employer lengths
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        strlen($occupation) > 100
    ) {

        $error =
            "Occupation must not exceed 100 characters.";

    }


    if (
        $error === "" &&
        strlen($employer) > 100
    ) {

        $error =
            "Employer must not exceed 100 characters.";

    }


    if (
        $error === "" &&
        strlen($employer_address) > 150
    ) {

        $error =
            "Employer address must not exceed 150 characters.";

    }


    if (
        $error === "" &&
        strlen($address) > 150
    ) {

        $error =
            "Address must not exceed 150 characters.";

    }


    /*
    |--------------------------------------------------------------------------
    | Validate Spouse
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        $spouse_fields = [
            [
                $spouse_name,
                "Spouse Name"
            ],
            [
                $spouse_occupation,
                "Spouse Occupation"
            ],
            [
                $spouse_employer,
                "Spouse Employer"
            ]
        ];


        foreach (
            $spouse_fields
            as $field
        ) {

            if (
                $field[0] !== "" &&
                $field[0] !== null
            ) {

                if (
                    $field[0] === $spouse_name
                ) {

                    $validation_error =
                        validate_person_name(
                            $field[0],
                            $field[1],
                            false
                        );

                }
                else {

                    $validation_error = "";

                }


                if (
                    $validation_error !== ""
                ) {

                    $error =
                        $validation_error;

                    break;

                }
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Parents
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        $parent_fields = [
            [
                $father_name,
                "Father's Name"
            ],
            [
                $mother_name,
                "Mother's Name"
            ]
        ];


        foreach (
            $parent_fields
            as $field
        ) {

            $validation_error =
                validate_person_name(
                    $field[0],
                    $field[1],
                    false
                );


            if (
                $validation_error !== ""
            ) {

                $error =
                    $validation_error;

                break;

            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Children
    |--------------------------------------------------------------------------
    */

    $clean_children = [];


    if ($error === "") {

        $children_count =
            max(
                count($children_names),
                count($children_ages)
            );


        for (
            $i = 0;
            $i < $children_count;
            $i++
        ) {

            $child_name =
                trim(
                    $children_names[$i] ?? ""
                );


            $child_age_raw =
                trim(
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


            $validation_error =
                validate_person_name(
                    $child_name,
                    "Child Name",
                    true
                );


            if (
                $validation_error !== ""
            ) {

                $error =
                    $validation_error;

                break;

            }


            $child_age = null;


            if (
                $child_age_raw !== ""
            ) {

                if (
                    !ctype_digit(
                        $child_age_raw
                    )
                ) {

                    $error =
                        "Child age must be a whole number.";

                    break;

                }


                $child_age =
                    (int) $child_age_raw;


                if (
                    $child_age < 0 ||
                    $child_age > 150
                ) {

                    $error =
                        "Child age must be between 0 and 150.";

                    break;

                }
            }


            $clean_children[] = [
                "name" => $child_name,
                "age" => $child_age
            ];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Two Character References
    |--------------------------------------------------------------------------
    */

    $clean_references = [];


    if ($error === "") {

        for (
            $i = 0;
            $i < 2;
            $i++
        ) {

            $reference_name =
                trim(
                    $reference_names[$i] ?? ""
                );


            if (
                $reference_name === ""
            ) {

                $error =
                    "Please provide both character references.";

                break;

            }


            $validation_error =
                validate_person_name(
                    $reference_name,
                    "Character Reference",
                    true
                );


            if (
                $validation_error !== ""
            ) {

                $error =
                    $validation_error;

                break;

            }


            $clean_references[] = [
                "name" =>
                    $reference_name,

                "order" =>
                    $i + 1
            ];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Save Complete Resident Update
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        try {

            $conn->begin_transaction();


            /*
            |--------------------------------------------------------------------------
            | Get Existing Data Before Update
            |--------------------------------------------------------------------------
            */

            $old_resident =
                load_resident(
                    $conn,
                    $resident_id
                );


            if (!$old_resident) {

                throw new Exception(
                    "Resident record was not found."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | Load Existing Spouse
            |--------------------------------------------------------------------------
            */

            $old_spouse = null;


            $old_spouse_stmt =
                $conn->prepare("
                    SELECT
                        spouse_name,
                        occupation,
                        employer
                    FROM resident_spouses
                    WHERE resident_id = ?
                    LIMIT 1
                ");


            $old_spouse_stmt->bind_param(
                "i",
                $resident_id
            );


            $old_spouse_stmt->execute();


            $old_spouse =
                $old_spouse_stmt
                    ->get_result()
                    ->fetch_assoc();


            /*
            |--------------------------------------------------------------------------
            | Load Existing Parents
            |--------------------------------------------------------------------------
            */

            $old_parents = null;


            $old_parents_stmt =
                $conn->prepare("
                    SELECT
                        father_name,
                        mother_name
                    FROM resident_parents
                    WHERE resident_id = ?
                    LIMIT 1
                ");


            $old_parents_stmt->bind_param(
                "i",
                $resident_id
            );


            $old_parents_stmt->execute();


            $old_parents =
                $old_parents_stmt
                    ->get_result()
                    ->fetch_assoc();


            /*
            |--------------------------------------------------------------------------
            | Load Existing Children
            |--------------------------------------------------------------------------
            */

            $old_children = [];


            $old_children_stmt =
                $conn->prepare("
                    SELECT
                        child_name,
                        age
                    FROM resident_children
                    WHERE resident_id = ?
                    ORDER BY child_id ASC
                ");


            $old_children_stmt->bind_param(
                "i",
                $resident_id
            );


            $old_children_stmt->execute();


            $old_children_result =
                $old_children_stmt
                    ->get_result();


            while (
                $old_child =
                    $old_children_result->fetch_assoc()
            ) {

                $old_children[] =
                    $old_child;

            }


            /*
            |--------------------------------------------------------------------------
            | Load Existing References
            |--------------------------------------------------------------------------
            */

            $old_references = [];


            $old_references_stmt =
                $conn->prepare("
                    SELECT
                        reference_name,
                        reference_order
                    FROM resident_references
                    WHERE resident_id = ?
                    ORDER BY reference_order ASC
                ");


            $old_references_stmt->bind_param(
                "i",
                $resident_id
            );


            $old_references_stmt->execute();


            $old_references_result =
                $old_references_stmt
                    ->get_result();


            while (
                $old_reference =
                    $old_references_result->fetch_assoc()
            ) {

                $old_references[] =
                    $old_reference;

            }


            /*
            |--------------------------------------------------------------------------
            | Update Main Resident Record
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            | This updates the existing resident_id.
            | It does NOT create another resident.
            |--------------------------------------------------------------------------
            */

            $update_resident =
                $conn->prepare("
                    UPDATE residents
                    SET
                        first_name = ?,
                        middle_name = ?,
                        last_name = ?,
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


            $update_resident->bind_param(
                "ssssssissssssi",
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
                $resident_id
            );


            if (
                !$update_resident->execute()
            ) {

                throw new Exception(
                    "Unable to update resident information."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | Update Spouse
            |--------------------------------------------------------------------------
            */

            $existing_spouse_stmt =
                $conn->prepare("
                    SELECT spouse_id
                    FROM resident_spouses
                    WHERE resident_id = ?
                    LIMIT 1
                ");


            $existing_spouse_stmt->bind_param(
                "i",
                $resident_id
            );


            $existing_spouse_stmt->execute();


            $existing_spouse =
                $existing_spouse_stmt
                    ->get_result()
                    ->fetch_assoc();


            if (
                $spouse_name !== "" ||
                $spouse_occupation !== "" ||
                $spouse_employer !== ""
            ) {

                if ($existing_spouse) {

                    $spouse_update =
                        $conn->prepare("
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


                    if (
                        !$spouse_update->execute()
                    ) {

                        throw new Exception(
                            "Unable to update spouse information."
                        );

                    }

                }
                else {

                    $spouse_insert =
                        $conn->prepare("
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


                    if (
                        !$spouse_insert->execute()
                    ) {

                        throw new Exception(
                            "Unable to save spouse information."
                        );

                    }
                }

            }
            else {

                if ($existing_spouse) {

                    $spouse_delete =
                        $conn->prepare("
                            DELETE FROM resident_spouses
                            WHERE resident_id = ?
                        ");


                    $spouse_delete->bind_param(
                        "i",
                        $resident_id
                    );


                    if (
                        !$spouse_delete->execute()
                    ) {

                        throw new Exception(
                            "Unable to remove spouse information."
                        );

                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Update Parents
            |--------------------------------------------------------------------------
            */

            $existing_parents_stmt =
                $conn->prepare("
                    SELECT parent_id
                    FROM resident_parents
                    WHERE resident_id = ?
                    LIMIT 1
                ");


            $existing_parents_stmt->bind_param(
                "i",
                $resident_id
            );


            $existing_parents_stmt->execute();


            $existing_parents =
                $existing_parents_stmt
                    ->get_result()
                    ->fetch_assoc();


            if (
                $father_name !== "" ||
                $mother_name !== ""
            ) {

                if ($existing_parents) {

                    $parents_update =
                        $conn->prepare("
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


                    if (
                        !$parents_update->execute()
                    ) {

                        throw new Exception(
                            "Unable to update parent information."
                        );

                    }

                }
                else {

                    $parents_insert =
                        $conn->prepare("
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


                    if (
                        !$parents_insert->execute()
                    ) {

                        throw new Exception(
                            "Unable to save parent information."
                        );

                    }
                }

            }
            else {

                if ($existing_parents) {

                    $parents_delete =
                        $conn->prepare("
                            DELETE FROM resident_parents
                            WHERE resident_id = ?
                        ");


                    $parents_delete->bind_param(
                        "i",
                        $resident_id
                    );


                    if (
                        !$parents_delete->execute()
                    ) {

                        throw new Exception(
                            "Unable to remove parent information."
                        );

                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Update Children
            |--------------------------------------------------------------------------
            |
            | Children are a one-to-many relationship.
            | Replace the resident's child list so editing does not
            | create duplicates.
            |--------------------------------------------------------------------------
            */

            $delete_children =
                $conn->prepare("
                    DELETE FROM resident_children
                    WHERE resident_id = ?
                ");


            $delete_children->bind_param(
                "i",
                $resident_id
            );


            if (
                !$delete_children->execute()
            ) {

                throw new Exception(
                    "Unable to update children information."
                );

            }


            if (
                !empty($clean_children)
            ) {

                $child_insert =
                    $conn->prepare("
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


                    if (
                        !$child_insert->execute()
                    ) {

                        throw new Exception(
                            "Unable to save children information."
                        );

                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Update Character References
            |--------------------------------------------------------------------------
            |
            | Signatures are intentionally preserved.
            | Signature upload is not part of the current scope.
            |--------------------------------------------------------------------------
            */

            $existing_signatures = [];


            $signature_stmt =
                $conn->prepare("
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
                $signature_stmt
                    ->get_result();


            while (
                $signature_row =
                    $signature_result->fetch_assoc()
            ) {

                $existing_signatures[
                    (int)
                    $signature_row["reference_order"]
                ] =
                    $signature_row["signature"];

            }


            $delete_references =
                $conn->prepare("
                    DELETE FROM resident_references
                    WHERE resident_id = ?
                ");


            $delete_references->bind_param(
                "i",
                $resident_id
            );


            if (
                !$delete_references->execute()
            ) {

                throw new Exception(
                    "Unable to update character references."
                );

            }


            $reference_insert =
                $conn->prepare("
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
                    $existing_signatures[
                        $reference_order
                    ] ?? null;


                $reference_insert->bind_param(
                    "issi",
                    $resident_id,
                    $reference["name"],
                    $signature,
                    $reference_order
                );


                if (
                    !$reference_insert->execute()
                ) {

                    throw new Exception(
                        "Unable to save character references."
                    );

                }
            }


            /*
            |--------------------------------------------------------------------------
            | Build Complete Before / After History
            |--------------------------------------------------------------------------
            */

            $old_data = [
                "personal_information" => [
                    "resident_number" =>
                        $old_resident["resident_number"],

                    "first_name" =>
                        $old_resident["first_name"],

                    "middle_name" =>
                        $old_resident["middle_name"],

                    "last_name" =>
                        $old_resident["last_name"],

                    "extension_name" =>
                        $old_resident["extension_name"],

                    "civil_status" =>
                        $old_resident["civil_status"],

                    "birthday" =>
                        $old_resident["birthday"],

                    "age" =>
                        $old_resident["age"],

                    "occupation" =>
                        $old_resident["occupation"],

                    "employer" =>
                        $old_resident["employer"],

                    "employer_address" =>
                        $old_resident["employer_address"],

                    "email" =>
                        $old_resident["email"],

                    "contact_number" =>
                        $old_resident["contact_number"],

                    "address" =>
                        $old_resident["address"]
                ],

                "spouse" =>
                    $old_spouse,

                "parents" =>
                    $old_parents,

                "children" =>
                    $old_children,

                "references" =>
                    $old_references
            ];


            $new_data = [
                "personal_information" => [
                    "resident_number" =>
                        $old_resident["resident_number"],

                    "first_name" =>
                        $first_name,

                    "middle_name" =>
                        $middle_name,

                    "last_name" =>
                        $last_name,

                    "extension_name" =>
                        $extension_name,

                    "civil_status" =>
                        $civil_status,

                    "birthday" =>
                        $birthday,

                    "age" =>
                        $age,

                    "occupation" =>
                        $occupation,

                    "employer" =>
                        $employer,

                    "employer_address" =>
                        $employer_address,

                    "email" =>
                        $email,

                    "contact_number" =>
                        $contact_number,

                    "address" =>
                        $address
                ],

                "spouse" => (
                    $spouse_name !== "" ||
                    $spouse_occupation !== "" ||
                    $spouse_employer !== ""
                )
                    ? [
                        "spouse_name" =>
                            $spouse_name,

                        "occupation" =>
                            $spouse_occupation,

                        "employer" =>
                            $spouse_employer
                    ]
                    : null,

                "parents" => (
                    $father_name !== "" ||
                    $mother_name !== ""
                )
                    ? [
                        "father_name" =>
                            $father_name,

                        "mother_name" =>
                            $mother_name
                    ]
                    : null,

                "children" =>
                    $clean_children,

                "references" =>
                    $clean_references
            ];


            /*
            |--------------------------------------------------------------------------
            | Record Staff Update History
            |--------------------------------------------------------------------------
            */

            $history_section =
                "complete_profile";


            $history_type =
                "staff";


            /*
            |--------------------------------------------------------------------------
            | Get Logged-In Staff ID
            |--------------------------------------------------------------------------
            */

            $staff_id =
                isset(
                    $_SESSION["staff_id"]
                )
                    ? (int)
                        $_SESSION["staff_id"]
                    : null;


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

                throw new Exception(
                    "Unable to record update history."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | Commit Everything
            |--------------------------------------------------------------------------
            */

            $conn->commit();


            $success =
                "Resident information updated successfully.";


            /*
            |--------------------------------------------------------------------------
            | Reload Updated Resident
            |--------------------------------------------------------------------------
            */

            $resident =
                load_resident(
                    $conn,
                    $resident_id
                );

        }
        catch (Throwable $exception) {

            try {

                $conn->rollback();

            }
            catch (Throwable $rollback_exception) {}

            $error =
                "Unable to update resident information. Please check the entered data and try again.";

        }
    }
}


/*
|--------------------------------------------------------------------------
| Process Photo Upload
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["upload_photo"])
) {

    $photo =
        $_FILES["photo"] ?? null;


    if (
        !$photo ||
        $photo["error"] === UPLOAD_ERR_NO_FILE
    ) {

        $photo_error =
            "Please select a photo to upload.";

    }
    elseif (
        $photo["error"] !== UPLOAD_ERR_OK
    ) {

        $photo_error =
            "There was a problem uploading the photo.";

    }
    elseif (
        $photo["size"] >
        5 * 1024 * 1024
    ) {

        $photo_error =
            "Photo must not exceed 5 MB.";

    }
    else {

        $image_info =
            @getimagesize(
                $photo["tmp_name"]
            );


        if (
            $image_info === false
        ) {

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
                    $allowed_types[
                        $image_type
                    ];


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
                        | Get Old Photo
                        |--------------------------------------------------------------------------
                        */

                        $old_photo =
                            $resident["photo"] ?? "";


                        /*
                        |--------------------------------------------------------------------------
                        | Update Resident Photo
                        |--------------------------------------------------------------------------
                        */

                        $photo_update =
                            $conn->prepare("
                                UPDATE residents
                                SET photo = ?
                                WHERE resident_id = ?
                            ");


                        $photo_update->bind_param(
                            "si",
                            $photo_path,
                            $resident_id
                        );


                        if (
                            $photo_update->execute()
                        ) {

                            /*
                            |--------------------------------------------------------------------------
                            | Remove Old Photo
                            |--------------------------------------------------------------------------
                            */

                            if (
                                !empty(
                                    $old_photo
                                )
                            ) {

                                $old_photo_file =
                                    __DIR__ .
                                    "/../" .
                                    ltrim(
                                        $old_photo,
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
                                "Resident photo updated successfully.";


                            /*
                            |--------------------------------------------------------------------------
                            | Record Photo Update History
                            |--------------------------------------------------------------------------
                            */

                            $staff_id =
                                isset(
                                    $_SESSION["staff_id"]
                                )
                                    ? (int)
                                        $_SESSION["staff_id"]
                                    : null;


                            $photo_old_data =
                                json_encode([
                                    "photo" =>
                                        $old_photo
                                ]);


                            $photo_new_data =
                                json_encode([
                                    "photo" =>
                                        $photo_path
                                ]);


                            $photo_history =
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
                                    VALUES (?, 'staff', ?, 'photo', ?, ?)
                                ");


                            $photo_history->bind_param(
                                "iiss",
                                $resident_id,
                                $staff_id,
                                $photo_old_data,
                                $photo_new_data
                            );


                            $photo_history->execute();


                            $resident =
                                load_resident(
                                    $conn,
                                    $resident_id
                                );

                        }
                        else {

                            @unlink(
                                $destination
                            );


                            $photo_error =
                                "Unable to save the resident photo.";

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


$spouse_stmt =
    $conn->prepare("
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

    $spouse =
        $spouse_result;

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


$parents_stmt =
    $conn->prepare("
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

    $parents =
        $parents_result;

}


/*
|--------------------------------------------------------------------------
| Load Children
|--------------------------------------------------------------------------
*/

$children = [];


$children_stmt =
    $conn->prepare("
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
    $children_stmt
        ->get_result();


while (
    $child =
    $children_result->fetch_assoc()
) {

    $children[] =
        $child;

}


/*
|--------------------------------------------------------------------------
| Load Character References
|--------------------------------------------------------------------------
*/

$references = [];


$references_stmt =
    $conn->prepare("
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
    $references_stmt
        ->get_result();


while (
    $reference =
    $references_result->fetch_assoc()
) {

    $references[] =
        $reference;

}


/*
|--------------------------------------------------------------------------
| Always provide two reference fields
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
| Full Name
|--------------------------------------------------------------------------
*/

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
    Edit Resident - <?= e($full_name) ?>
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

/* =========================================================
   Staff Resident Edit
   ========================================================= */

.resident-edit-page {

    display: flex;

    flex-direction: column;

    gap: 24px;

}


.profile-section {

    width: 100%;

}


.profile-section-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 16px;

    margin-bottom: 20px;

}


.profile-section-header h2 {

    margin: 0;

}


.profile-section-description {

    margin: 4px 0 0;

    opacity: .7;

}


.profile-grid {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 18px;

}


.profile-field {

    display: flex;

    flex-direction: column;

    gap: 7px;

}


.profile-field.full {

    grid-column: 1 / -1;

}


.profile-field label {

    font-weight: 700;

}


.profile-field input,
.profile-field select,
.profile-field textarea {

    width: 100%;

    box-sizing: border-box;

}


.profile-field textarea {

    min-height: 90px;

    resize: vertical;

}


.readonly-field {

    background: #eeeeee !important;

    cursor: not-allowed;

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


.action-cell {

    width: 80px;

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


.profile-actions-group {

    display: flex;

    gap: 10px;

    align-items: center;

    flex-wrap: wrap;

}


.photo-layout {

    display: grid;

    grid-template-columns:
        180px minmax(0, 1fr);

    gap: 28px;

    align-items: start;

}


.photo-preview {

    width: 160px;

    height: 200px;

    border: 3px solid #111;

    background: #f5f5f5;

    display: flex;

    justify-content: center;

    align-items: center;

    overflow: hidden;

}


.photo-preview img {

    width: 100%;

    height: 100%;

    object-fit: cover;

}


.photo-placeholder {

    text-align: center;

    opacity: .55;

    font-weight: 700;

    padding: 15px;

}


.photo-upload-form {

    display: flex;

    flex-direction: column;

    gap: 14px;

}


@media (max-width: 800px) {

    .profile-grid {

        grid-template-columns: 1fr;

    }


    .profile-field.full {

        grid-column: auto;

    }


    .photo-layout {

        grid-template-columns: 1fr;

    }

}


@media print {

    .sidebar,
    .topbar,
    nav,
    .screen-only,
    .profile-actions,
    .photo-upload-card {

        display: none !important;

    }


    .container {

        margin: 0 !important;

        padding: 0 !important;

        width: 100% !important;

    }


    .card {

        box-shadow: none !important;

        border: 1px solid #000 !important;

        break-inside: avoid;

    }


    input,
    select,
    textarea {

        border: 0 !important;

        padding: 0 !important;

        background: transparent !important;

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
include __DIR__ . "/../includes/staff_nav.php";
?>


<div class="container">

<?php
include __DIR__ . "/../includes/staff_topbar.php";
?>


<div class="welcome-header screen-only">

    <div>

        <h1>
            Edit Resident
        </h1>

        <p>
            Update the complete information for
            <?= e($full_name) ?>.
        </p>

    </div>

</div>


<div class="resident-edit-page">


<!-- =========================================================
     PERSONAL INFORMATION
     ========================================================= -->

<div class="card profile-section">

    <div class="profile-section-header">

        <div>

            <h2>
                Personal Information
            </h2>

            <p class="profile-section-description">
                Update the resident's complete personal information.
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


    <form
        method="POST"
        id="residentEditForm"
    >

        <input
            type="hidden"
            name="update_resident"
            value="1"
        >


        <input
            type="hidden"
            name="resident_id"
            value="<?= (int) $resident_id ?>"
        >


        <div class="profile-grid">


            <!-- Resident Number -->

            <div class="profile-field">

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

            <div class="profile-field">

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

            <div class="profile-field">

                <label>
                    Last Name
                </label>

                <input
                    type="text"
                    name="last_name"
                    value="<?= e(
                        $resident["last_name"]
                    ) ?>"
                    maxlength="50"
                    required
                >

            </div>


            <!-- First Name -->

            <div class="profile-field">

                <label>
                    First Name
                </label>

                <input
                    type="text"
                    name="first_name"
                    value="<?= e(
                        $resident["first_name"]
                    ) ?>"
                    maxlength="50"
                    required
                >

            </div>


            <!-- Middle Name -->

            <div class="profile-field">

                <label>
                    Middle Name
                </label>

                <input
                    type="text"
                    name="middle_name"
                    value="<?= e(
                        $resident["middle_name"]
                    ) ?>"
                    maxlength="50"
                >

            </div>


            <!-- Extension Name -->

            <div class="profile-field">

                <label>
                    Extension Name
                </label>

                <input
                    type="text"
                    name="extension_name"
                    value="<?= e(
                        $resident["extension_name"]
                    ) ?>"
                    maxlength="20"
                    placeholder="Jr., Sr., III"
                >

            </div>


            <!-- Birthday -->

            <div class="profile-field">

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

            <div class="profile-field">

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
                    Age is automatically calculated from birthday.
                </small>

            </div>


            <!-- Occupation -->

            <div class="profile-field">

                <label>
                    Occupation
                </label>

                <input
                    type="text"
                    name="occupation"
                    value="<?= e(
                        $resident["occupation"]
                    ) ?>"
                    maxlength="100"
                >

            </div>


            <!-- Employer -->

            <div class="profile-field">

                <label>
                    Employer
                </label>

                <input
                    type="text"
                    name="employer"
                    value="<?= e(
                        $resident["employer"]
                    ) ?>"
                    maxlength="100"
                >

            </div>


            <!-- Employer Address -->

            <div class="profile-field full">

                <label>
                    Employer Address
                </label>

                <input
                    type="text"
                    name="employer_address"
                    value="<?= e(
                        $resident["employer_address"]
                    ) ?>"
                    maxlength="150"
                >

            </div>


            <!-- Email -->

            <div class="profile-field">

                <label>
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    value="<?= e(
                        $resident["email"]
                    ) ?>"
                    maxlength="100"
                >

            </div>


            <!-- Contact -->

            <div class="profile-field">

                <label>
                    Contact Number
                </label>

                <input
                    type="text"
                    name="contact_number"
                    value="<?= e(
                        $resident["contact_number"]
                    ) ?>"
                    maxlength="11"
                    inputmode="numeric"
                    pattern="09[0-9]{9}"
                    placeholder="09XXXXXXXXX"
                >

            </div>


            <!-- Address -->

            <div class="profile-field full">

                <label>
                    Address
                </label>

                <textarea
                    name="address"
                    maxlength="150"
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

            <h2>
                Spouse Information
            </h2>

            <p class="profile-section-description">
                Add or update the resident's spouse information.
            </p>

        </div>

    </div>


    <div class="profile-grid">

        <div class="profile-field">

            <label>
                Spouse Name
            </label>

            <input
                type="text"
                name="spouse_name"
                form="residentEditForm"
                value="<?= e(
                    $spouse["spouse_name"]
                ) ?>"
                maxlength="100"
            >

        </div>


        <div class="profile-field">

            <label>
                Occupation
            </label>

            <input
                type="text"
                name="spouse_occupation"
                form="residentEditForm"
                value="<?= e(
                    $spouse["occupation"]
                ) ?>"
                maxlength="100"
            >

        </div>


        <div class="profile-field">

            <label>
                Employer
            </label>

            <input
                type="text"
                name="spouse_employer"
                form="residentEditForm"
                value="<?= e(
                    $spouse["employer"]
                ) ?>"
                maxlength="100"
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

            <h2>
                Children
            </h2>

            <p class="profile-section-description">
                Add, edit, or remove multiple children.
            </p>

        </div>

    </div>


    <div class="children-table-wrapper">

        <table class="children-table">

            <thead>

                <tr>

                    <th>
                        Child Name
                    </th>

                    <th>
                        Age
                    </th>

                    <th class="action-cell">
                        Action
                    </th>

                </tr>

            </thead>


            <tbody id="childrenTableBody">

            <?php if (
                !empty($children)
            ): ?>

                <?php foreach (
                    $children
                    as $child
                ): ?>

                    <tr>

                        <td>

                            <input
                                type="text"
                                name="child_name[]"
                                form="residentEditForm"
                                value="<?= e(
                                    $child["child_name"]
                                ) ?>"
                                maxlength="100"
                            >

                        </td>


                        <td>

                            <input
                                type="number"
                                name="child_age[]"
                                form="residentEditForm"
                                min="0"
                                max="150"
                                value="<?= e(
                                    $child["age"]
                                ) ?>"
                            >

                        </td>


                        <td class="action-cell">

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
                            form="residentEditForm"
                            maxlength="100"
                        >

                    </td>


                    <td>

                        <input
                            type="number"
                            name="child_age[]"
                            form="residentEditForm"
                            min="0"
                            max="150"
                        >

                    </td>


                    <td class="action-cell">

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

            <h2>
                Parents
            </h2>

            <p class="profile-section-description">
                Update the resident's parents.
            </p>

        </div>

    </div>


    <div class="profile-grid">

        <div class="profile-field">

            <label>
                Father's Name
            </label>

            <input
                type="text"
                name="father_name"
                form="residentEditForm"
                value="<?= e(
                    $parents["father_name"]
                ) ?>"
                maxlength="100"
            >

        </div>


        <div class="profile-field">

            <label>
                Mother's Name
            </label>

            <input
                type="text"
                name="mother_name"
                form="residentEditForm"
                value="<?= e(
                    $parents["mother_name"]
                ) ?>"
                maxlength="100"
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

            <h2>
                Character References
            </h2>

            <p class="profile-section-description">
                Two character references are required.
                Signature is currently optional and not edited here.
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
                            form="residentEditForm"
                            value="<?= e(
                                $references[$i]["reference_name"]
                            ) ?>"
                            maxlength="100"
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
                                Existing signature
                            </span>

                        <?php else: ?>

                            <span style="opacity:.6;">
                                None
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
     PHOTO
     ========================================================= -->

<div class="card profile-section photo-upload-card">

    <div class="profile-section-header">

        <div>

            <h2>
                Passport-Size Photo
            </h2>

            <p class="profile-section-description">
                Upload or replace the resident's photo.
            </p>

        </div>

    </div>


    <div class="photo-layout">


        <div>

            <div class="photo-preview">

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
    alt="Resident Photo"
>

                <?php else: ?>

                    <div class="photo-placeholder">
                        No Photo
                    </div>

                <?php endif; ?>

            </div>

        </div>


        <div>

            <?php if (
                $photo_success
            ): ?>

                <div class="success">
                    <?= e(
                        $photo_success
                    ) ?>
                </div>

            <?php endif; ?>


            <?php if (
                $photo_error
            ): ?>

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
                    name="resident_id"
                    value="<?= (int) $resident_id ?>"
                >


                <input
                    type="hidden"
                    name="upload_photo"
                    value="1"
                >


                <div class="profile-field">

                    <label>
                        Select Photo
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


                <small>
                    Maximum 5 MB.
                    JPG, PNG, and WEBP only.
                </small>


                <button
                    type="submit"
                    class="btn"
                >
                    Upload / Replace Photo
                </button>

            </form>

        </div>

    </div>

</div>



<!-- =========================================================
     SAVE ACTIONS
     ========================================================= -->

<div class="card profile-section screen-only">

    <div class="profile-actions">

        <div class="profile-actions-group">

            <button
                type="submit"
                form="residentEditForm"
                class="btn"
                id="saveResidentBtn"
            >
                Save Resident Information
            </button>


            <a
                href="resident_management.php"
                class="btn"
            >
                Back to Residents
            </a>

        </div>


        <div class="profile-actions-group">

            <button
                type="button"
                class="btn"
                id="printResidentBtn"
            >
                Print Profile
            </button>

        </div>

    </div>

</div>


</div>

</div>


<script src="../assets/js/script.js"></script>


<script>

/*
|--------------------------------------------------------------------------
| Staff Resident Edit JavaScript
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
                birthdayInput.value === ""
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
        | Children
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
                                form="residentEditForm"
                                maxlength="100"
                            >

                        </td>

                        <td>

                            <input
                                type="number"
                                name="child_age[]"
                                form="residentEditForm"
                                min="0"
                                max="150"
                            >

                        </td>

                        <td class="action-cell">

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

        }


        /*
        |--------------------------------------------------------------------------
        | Print
        |--------------------------------------------------------------------------
        */

        const printButton =
            document.getElementById(
                "printResidentBtn"
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
        | Form Validation
        |--------------------------------------------------------------------------
        */

        const form =
            document.getElementById(
                "residentEditForm"
            );


        if (form) {

            form.addEventListener(
                "submit",
                function (event) {


                    /*
                    |--------------------------------------------------------------------------
                    | Native Validation
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !form.checkValidity()
                    ) {

                        event.preventDefault();

                        form.reportValidity();

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
                    | Validate Contact Number
                    |--------------------------------------------------------------------------
                    */

                    const contactInput =
                        form.querySelector(
                            'input[name="contact_number"]'
                        );


                    if (
                        contactInput &&
                        contactInput.value.trim() !== ""
                    ) {

                        if (
                            !/^09[0-9]{9}$/.test(
                                contactInput.value.trim()
                            )
                        ) {

                            event.preventDefault();

                            alert(
                                "Contact number must contain exactly 11 digits and start with 09."
                            );

                            contactInput.focus();

                            return;

                        }

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Confirm Save
                    |--------------------------------------------------------------------------
                    */

                    const confirmed =
                        confirm(
                            "Save the changes made to this resident?"
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