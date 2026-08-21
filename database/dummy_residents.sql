USE barangay_survey_db;

SET FOREIGN_KEY_CHECKS = 0;

-- =========================================================
-- BARANGAY SURVEY - DUMMY DATA GENERATOR
--
-- Existing residents:
--   2026-0001 through 2026-0005
--
-- New dummy residents:
--   2026-0006 through 2026-0105
--
-- Total new residents: 100
--
-- Also creates:
--   3 additional surveys
--   Survey questions
--   Survey choices
--   100+ survey responses
--   Survey result data
--   Resident children
--
-- DOES NOT DELETE EXISTING DATA
-- =========================================================


-- =========================================================
-- 1. CREATE 100 DUMMY RESIDENTS
-- =========================================================

DROP TEMPORARY TABLE IF EXISTS dummy_numbers;

CREATE TEMPORARY TABLE dummy_numbers (
    num INT PRIMARY KEY
);

INSERT INTO dummy_numbers (num)
WITH RECURSIVE numbers AS (
    SELECT 6 AS num

    UNION ALL

    SELECT num + 1
    FROM numbers
    WHERE num < 105
)
SELECT num
FROM numbers;


INSERT INTO residents (
    resident_number,
    first_name,
    last_name,
    middle_name,
    extension_name,
    gender,
    civil_status,
    email,
    contact_number,
    address,
    birthday,
    age,
    occupation,
    employer,
    employer_address,
    father_name,
    mother_name,
    spouse_name,
    spouse_occupation,
    spouse_employer,
    reference1_name,
    reference1_signature,
    reference2_name,
    reference2_signature,
    photo,
    status,
    password,
    is_first_login
)

SELECT

    CONCAT(
        '2026-',
        LPAD(num, 4, '0')
    ) AS resident_number,

    CASE MOD(num, 20)

        WHEN 0 THEN 'Juan'
        WHEN 1 THEN 'Maria'
        WHEN 2 THEN 'John'
        WHEN 3 THEN 'Angela'
        WHEN 4 THEN 'Mark'
        WHEN 5 THEN 'Catherine'
        WHEN 6 THEN 'Daniel'
        WHEN 7 THEN 'Sofia'
        WHEN 8 THEN 'Michael'
        WHEN 9 THEN 'Andrea'
        WHEN 10 THEN 'Joshua'
        WHEN 11 THEN 'Nicole'
        WHEN 12 THEN 'Christian'
        WHEN 13 THEN 'Patricia'
        WHEN 14 THEN 'Kevin'
        WHEN 15 THEN 'Jasmine'
        WHEN 16 THEN 'Gabriel'
        WHEN 17 THEN 'Bianca'
        WHEN 18 THEN 'Rafael'
        ELSE 'Camille'

    END AS first_name,


    CASE MOD(num, 25)

        WHEN 0 THEN 'Santos'
        WHEN 1 THEN 'Reyes'
        WHEN 2 THEN 'Garcia'
        WHEN 3 THEN 'Dela Cruz'
        WHEN 4 THEN 'Mendoza'
        WHEN 5 THEN 'Bautista'
        WHEN 6 THEN 'Flores'
        WHEN 7 THEN 'Navarro'
        WHEN 8 THEN 'Castillo'
        WHEN 9 THEN 'Torres'
        WHEN 10 THEN 'Ramos'
        WHEN 11 THEN 'Aquino'
        WHEN 12 THEN 'Villanueva'
        WHEN 13 THEN 'Cruz'
        WHEN 14 THEN 'Rivera'
        WHEN 15 THEN 'Gonzales'
        WHEN 16 THEN 'Fernandez'
        WHEN 17 THEN 'Dizon'
        WHEN 18 THEN 'Manalo'
        WHEN 19 THEN 'Mercado'
        WHEN 20 THEN 'Del Rosario'
        WHEN 21 THEN 'Santiago'
        WHEN 22 THEN 'Salazar'
        WHEN 23 THEN 'Domingo'
        ELSE 'Valdez'

    END AS last_name,


    CASE MOD(num, 15)

        WHEN 0 THEN 'Garcia'
        WHEN 1 THEN 'Santos'
        WHEN 2 THEN 'Reyes'
        WHEN 3 THEN 'Cruz'
        WHEN 4 THEN 'Mendoza'
        WHEN 5 THEN 'Flores'
        WHEN 6 THEN 'Ramos'
        WHEN 7 THEN 'Bautista'
        WHEN 8 THEN 'Navarro'
        WHEN 9 THEN 'Aquino'
        WHEN 10 THEN 'Torres'
        WHEN 11 THEN 'Castillo'
        WHEN 12 THEN 'Rivera'
        WHEN 13 THEN 'Dizon'
        ELSE 'Villanueva'

    END AS middle_name,


    CASE

        WHEN MOD(num, 50) = 0 THEN 'Jr.'
        WHEN MOD(num, 75) = 0 THEN 'Sr.'
        ELSE NULL

    END AS extension_name,


    CASE

        WHEN MOD(num, 2) = 0
            THEN 'Male'

        ELSE 'Female'

    END AS gender,


    CASE MOD(num, 5)

        WHEN 0 THEN 'Single'
        WHEN 1 THEN 'Married'
        WHEN 2 THEN 'Single'
        WHEN 3 THEN 'Married'
        ELSE 'Widowed'

    END AS civil_status,


    CONCAT(
        LOWER(
            CASE MOD(num, 20)

                WHEN 0 THEN 'juan'
                WHEN 1 THEN 'maria'
                WHEN 2 THEN 'john'
                WHEN 3 THEN 'angela'
                WHEN 4 THEN 'mark'
                WHEN 5 THEN 'catherine'
                WHEN 6 THEN 'daniel'
                WHEN 7 THEN 'sofia'
                WHEN 8 THEN 'michael'
                WHEN 9 THEN 'andrea'
                WHEN 10 THEN 'joshua'
                WHEN 11 THEN 'nicole'
                WHEN 12 THEN 'christian'
                WHEN 13 THEN 'patricia'
                WHEN 14 THEN 'kevin'
                WHEN 15 THEN 'jasmine'
                WHEN 16 THEN 'gabriel'
                WHEN 17 THEN 'bianca'
                WHEN 18 THEN 'rafael'
                ELSE 'camille'

            END
        ),
        num,
        '@example.com'
    ) AS email,


    CONCAT(
        '09',
        LPAD(
            100000000 + num,
            9,
            '0'
        )
    ) AS contact_number,


    CASE MOD(num, 10)

        WHEN 0 THEN 'Poblacion, Bocaue, Bulacan'
        WHEN 1 THEN 'Lolomboy, Bocaue, Bulacan'
        WHEN 2 THEN 'Sulucan, Bocaue, Bulacan'
        WHEN 3 THEN 'Tambubong, Bocaue, Bulacan'
        WHEN 4 THEN 'Taal, Bocaue, Bulacan'
        WHEN 5 THEN 'Wakas, Bocaue, Bulacan'
        WHEN 6 THEN 'Santa Maria, Bulacan'
        WHEN 7 THEN 'Malolos, Bulacan'
        WHEN 8 THEN 'Meycauayan, Bulacan'
        ELSE 'Guiguinto, Bulacan'

    END AS address,


    DATE_ADD(
        '1970-01-01',
        INTERVAL MOD(num * 137, 17500) DAY
    ) AS birthday,


    2026 -
    YEAR(
        DATE_ADD(
            '1970-01-01',
            INTERVAL MOD(num * 137, 17500) DAY
        )
    ) AS age,


    CASE MOD(num, 12)

        WHEN 0 THEN 'Teacher'
        WHEN 1 THEN 'Nurse'
        WHEN 2 THEN 'Driver'
        WHEN 3 THEN 'Farmer'
        WHEN 4 THEN 'Vendor'
        WHEN 5 THEN 'Carpenter'
        WHEN 6 THEN 'Electrician'
        WHEN 7 THEN 'Business Owner'
        WHEN 8 THEN 'Government Employee'
        WHEN 9 THEN 'Construction Worker'
        WHEN 10 THEN 'Student'
        ELSE 'Freelancer'

    END AS occupation,


    CASE MOD(num, 10)

        WHEN 0 THEN 'Bulacan State University'
        WHEN 1 THEN 'Bocaue Municipal Office'
        WHEN 2 THEN 'Private Company'
        WHEN 3 THEN 'Local Business'
        WHEN 4 THEN 'Barangay Office'
        WHEN 5 THEN 'Self-Employed'
        WHEN 6 THEN 'Construction Company'
        WHEN 7 THEN 'Public School'
        WHEN 8 THEN 'Health Center'
        ELSE 'Freelance'

    END AS employer,


    CASE MOD(num, 5)

        WHEN 0 THEN 'Bocaue, Bulacan'
        WHEN 1 THEN 'Malolos, Bulacan'
        WHEN 2 THEN 'Meycauayan, Bulacan'
        WHEN 3 THEN 'Guiguinto, Bulacan'
        ELSE 'Santa Maria, Bulacan'

    END AS employer_address,


    CONCAT(
        'Pedro ',
        CASE MOD(num, 10)

            WHEN 0 THEN 'Santos'
            WHEN 1 THEN 'Reyes'
            WHEN 2 THEN 'Garcia'
            WHEN 3 THEN 'Cruz'
            WHEN 4 THEN 'Mendoza'
            WHEN 5 THEN 'Flores'
            WHEN 6 THEN 'Ramos'
            WHEN 7 THEN 'Bautista'
            WHEN 8 THEN 'Navarro'
            ELSE 'Aquino'

        END
    ) AS father_name,


    CONCAT(
        'Elena ',
        CASE MOD(num, 10)

            WHEN 0 THEN 'Santos'
            WHEN 1 THEN 'Reyes'
            WHEN 2 THEN 'Garcia'
            WHEN 3 THEN 'Cruz'
            WHEN 4 THEN 'Mendoza'
            WHEN 5 THEN 'Flores'
            WHEN 6 THEN 'Ramos'
            WHEN 7 THEN 'Bautista'
            WHEN 8 THEN 'Navarro'
            ELSE 'Aquino'

        END
    ) AS mother_name,


    CASE

        WHEN MOD(num, 5) IN (1, 3)

        THEN CONCAT(
            'Spouse ',
            CASE MOD(num, 20)

                WHEN 0 THEN 'Santos'
                WHEN 1 THEN 'Reyes'
                WHEN 2 THEN 'Garcia'
                WHEN 3 THEN 'Cruz'
                WHEN 4 THEN 'Mendoza'
                WHEN 5 THEN 'Flores'
                WHEN 6 THEN 'Ramos'
                WHEN 7 THEN 'Bautista'
                WHEN 8 THEN 'Navarro'
                WHEN 9 THEN 'Aquino'
                WHEN 10 THEN 'Torres'
                WHEN 11 THEN 'Castillo'
                WHEN 12 THEN 'Rivera'
                WHEN 13 THEN 'Dizon'
                WHEN 14 THEN 'Villanueva'
                WHEN 15 THEN 'Mercado'
                WHEN 16 THEN 'Santiago'
                WHEN 17 THEN 'Salazar'
                WHEN 18 THEN 'Domingo'
                ELSE 'Valdez'

            END
        )

        ELSE NULL

    END AS spouse_name,


    CASE

        WHEN MOD(num, 5) IN (1, 3)

        THEN CASE MOD(num, 4)

            WHEN 0 THEN 'Teacher'
            WHEN 1 THEN 'Business Owner'
            WHEN 2 THEN 'Driver'
            ELSE 'Government Employee'

        END

        ELSE NULL

    END AS spouse_occupation,


    CASE

        WHEN MOD(num, 5) IN (1, 3)

        THEN 'Local Company'

        ELSE NULL

    END AS spouse_employer,


    CONCAT(
        'Reference One ',
        num
    ) AS reference1_name,


    CONCAT(
        'Signature Reference One ',
        num
    ) AS reference1_signature,


    CONCAT(
        'Reference Two ',
        num
    ) AS reference2_name,


    CONCAT(
        'Signature Reference Two ',
        num
    ) AS reference2_signature,


    NULL AS photo,


    CASE

        WHEN MOD(num, 17) = 0 THEN 'archived'
        ELSE 'active'

    END AS status,


    -- Existing bcrypt hash used as a safe dummy password value.
    -- These dummy records are primarily intended for testing
    -- filters, reports, and survey functionality.

    '$2b$10$mGrOfVoC0/ORly7Z4iHH0OPlksjcOmNqPP9hh4vkrXXPJnQfq4LMm'
        AS password,


    1 AS is_first_login

FROM dummy_numbers dn

WHERE NOT EXISTS (

    SELECT 1

    FROM residents r

    WHERE r.resident_number =
        CONCAT(
            '2026-',
            LPAD(dn.num, 4, '0')
        )

);


-- =========================================================
-- 2. CREATE CHILDREN FOR DUMMY RESIDENTS
-- =========================================================

INSERT INTO resident_children (
    resident_id,
    child_name,
    age
)

SELECT
    r.resident_id,

    CONCAT(
        'Child ',
        r.resident_number,
        ' - ',
        n.child_no
    ),

    CASE n.child_no

        WHEN 1 THEN MOD(r.resident_id, 16) + 1
        WHEN 2 THEN MOD(r.resident_id + 5, 13) + 1
        ELSE MOD(r.resident_id + 8, 10) + 1

    END

FROM residents r

JOIN (
    SELECT 1 AS child_no
    UNION ALL
    SELECT 2
    UNION ALL
    SELECT 3
) n

WHERE r.resident_number BETWEEN
    '2026-0006'
    AND
    '2026-0105'

AND (
    (MOD(r.resident_id, 3) = 0 AND n.child_no <= 3)
    OR
    (MOD(r.resident_id, 3) = 1 AND n.child_no <= 2)
    OR
    (MOD(r.resident_id, 3) = 2 AND n.child_no = 1)
)

AND NOT EXISTS (

    SELECT 1

    FROM resident_children rc

    WHERE rc.resident_id = r.resident_id

    AND rc.child_name =
        CONCAT(
            'Child ',
            r.resident_number,
            ' - ',
            n.child_no
        )

);


-- =========================================================
-- 3. CREATE SURVEY #2
-- =========================================================

INSERT INTO surveys (
    title,
    description,
    created_by,
    start_date,
    end_date,
    status
)

SELECT
    'Community Health Needs Assessment',
    'Survey about the health needs and common health concerns of barangay residents.',
    1,
    '2026-07-01',
    '2026-12-31',
    'active'

WHERE NOT EXISTS (

    SELECT 1
    FROM surveys
    WHERE title =
        'Community Health Needs Assessment'

);


-- =========================================================
-- 4. CREATE SURVEY #3
-- =========================================================

INSERT INTO surveys (
    title,
    description,
    created_by,
    start_date,
    end_date,
    status
)

SELECT
    'Barangay Health Center Accessibility Survey',
    'Survey measuring how accessible and convenient the barangay health center is for residents.',
    1,
    '2026-07-15',
    '2026-12-31',
    'active'

WHERE NOT EXISTS (

    SELECT 1
    FROM surveys
    WHERE title =
        'Barangay Health Center Accessibility Survey'

);


-- =========================================================
-- 5. SURVEY #2 QUESTIONS
-- =========================================================

INSERT INTO survey_questions (
    survey_id,
    question_text,
    question_type,
    is_required,
    question_order
)

SELECT
    survey_id,
    'What health concern do you experience most often?',
    'multiple_choice',
    1,
    1

FROM surveys s

WHERE s.title =
    'Community Health Needs Assessment'

AND NOT EXISTS (

    SELECT 1
    FROM survey_questions sq

    WHERE sq.survey_id = s.survey_id
    AND sq.question_order = 1

);


INSERT INTO survey_questions (
    survey_id,
    question_text,
    question_type,
    is_required,
    question_order
)

SELECT
    survey_id,
    'How often do you visit the barangay health center?',
    'multiple_choice',
    1,
    2

FROM surveys s

WHERE s.title =
    'Community Health Needs Assessment'

AND NOT EXISTS (

    SELECT 1
    FROM survey_questions sq

    WHERE sq.survey_id = s.survey_id
    AND sq.question_order = 2

);


INSERT INTO survey_questions (
    survey_id,
    question_text,
    question_type,
    is_required,
    question_order
)

SELECT
    survey_id,
    'How would you rate the availability of health services?',
    'rating',
    1,
    3

FROM surveys s

WHERE s.title =
    'Community Health Needs Assessment'

AND NOT EXISTS (

    SELECT 1
    FROM survey_questions sq

    WHERE sq.survey_id = s.survey_id
    AND sq.question_order = 3

);


-- =========================================================
-- 6. SURVEY #3 QUESTIONS
-- =========================================================

INSERT INTO survey_questions (
    survey_id,
    question_text,
    question_type,
    is_required,
    question_order
)

SELECT
    survey_id,
    'Is the health center conveniently located for you?',
    'yes_no',
    1,
    1

FROM surveys s

WHERE s.title =
    'Barangay Health Center Accessibility Survey'

AND NOT EXISTS (

    SELECT 1
    FROM survey_questions sq

    WHERE sq.survey_id = s.survey_id
    AND sq.question_order = 1

);


INSERT INTO survey_questions (
    survey_id,
    question_text,
    question_type,
    is_required,
    question_order
)

SELECT
    survey_id,
    'How long does it usually take you to reach the health center?',
    'multiple_choice',
    1,
    2

FROM surveys s

WHERE s.title =
    'Barangay Health Center Accessibility Survey'

AND NOT EXISTS (

    SELECT 1
    FROM survey_questions sq

    WHERE sq.survey_id = s.survey_id
    AND sq.question_order = 2

);


INSERT INTO survey_questions (
    survey_id,
    question_text,
    question_type,
    is_required,
    question_order
)

SELECT
    survey_id,
    'How satisfied are you with the accessibility of the health center?',
    'rating',
    1,
    3

FROM surveys s

WHERE s.title =
    'Barangay Health Center Accessibility Survey'

AND NOT EXISTS (

    SELECT 1
    FROM survey_questions sq

    WHERE sq.survey_id = s.survey_id
    AND sq.question_order = 3

);


-- =========================================================
-- 7. SURVEY #2 CHOICES
-- =========================================================

INSERT INTO survey_choices (
    question_id,
    choice_text,
    choice_order
)

SELECT
    sq.question_id,
    c.choice_text,
    c.choice_order

FROM survey_questions sq

JOIN (
    SELECT 'Hypertension' AS choice_text, 1 AS choice_order
    UNION ALL
    SELECT 'Diabetes', 2
    UNION ALL
    SELECT 'Respiratory Problems', 3
    UNION ALL
    SELECT 'Maternal Health', 4
    UNION ALL
    SELECT 'General Check-up', 5
) c

WHERE sq.question_order = 1

AND sq.survey_id = (

    SELECT survey_id
    FROM surveys
    WHERE title =
        'Community Health Needs Assessment'

)

AND NOT EXISTS (

    SELECT 1
    FROM survey_choices sc
    WHERE sc.question_id = sq.question_id
    AND sc.choice_order = c.choice_order

);


INSERT INTO survey_choices (
    question_id,
    choice_text,
    choice_order
)

SELECT
    sq.question_id,
    c.choice_text,
    c.choice_order

FROM survey_questions sq

JOIN (
    SELECT 'Weekly' AS choice_text, 1 AS choice_order
    UNION ALL
    SELECT 'Monthly', 2
    UNION ALL
    SELECT 'Every Few Months', 3
    UNION ALL
    SELECT 'Rarely', 4
    UNION ALL
    SELECT 'Never', 5
) c

WHERE sq.question_order = 2

AND sq.survey_id = (

    SELECT survey_id
    FROM surveys
    WHERE title =
        'Community Health Needs Assessment'

)

AND NOT EXISTS (

    SELECT 1
    FROM survey_choices sc
    WHERE sc.question_id = sq.question_id
    AND sc.choice_order = c.choice_order

);


INSERT INTO survey_choices (
    question_id,
    choice_text,
    choice_order
)

SELECT
    sq.question_id,
    c.choice_text,
    c.choice_order

FROM survey_questions sq

JOIN (
    SELECT '1 - Very Poor' AS choice_text, 1 AS choice_order
    UNION ALL
    SELECT '2 - Poor', 2
    UNION ALL
    SELECT '3 - Fair', 3
    UNION ALL
    SELECT '4 - Good', 4
    UNION ALL
    SELECT '5 - Excellent', 5
) c

WHERE sq.question_order = 3

AND sq.survey_id = (

    SELECT survey_id
    FROM surveys
    WHERE title =
        'Community Health Needs Assessment'

)

AND NOT EXISTS (

    SELECT 1
    FROM survey_choices sc
    WHERE sc.question_id = sq.question_id
    AND sc.choice_order = c.choice_order

);


-- =========================================================
-- 8. SURVEY #3 CHOICES
-- =========================================================

INSERT INTO survey_choices (
    question_id,
    choice_text,
    choice_order
)

SELECT
    sq.question_id,
    c.choice_text,
    c.choice_order

FROM survey_questions sq

JOIN (
    SELECT 'Yes' AS choice_text, 1 AS choice_order
    UNION ALL
    SELECT 'No', 2
) c

WHERE sq.question_order = 1

AND sq.survey_id = (

    SELECT survey_id
    FROM surveys
    WHERE title =
        'Barangay Health Center Accessibility Survey'

)

AND NOT EXISTS (

    SELECT 1
    FROM survey_choices sc
    WHERE sc.question_id = sq.question_id
    AND sc.choice_order = c.choice_order

);


INSERT INTO survey_choices (
    question_id,
    choice_text,
    choice_order
)

SELECT
    sq.question_id,
    c.choice_text,
    c.choice_order

FROM survey_questions sq

JOIN (
    SELECT 'Less than 10 minutes' AS choice_text, 1 AS choice_order
    UNION ALL
    SELECT '10-20 minutes', 2
    UNION ALL
    SELECT '21-30 minutes', 3
    UNION ALL
    SELECT '31-60 minutes', 4
    UNION ALL
    SELECT 'More than 1 hour', 5
) c

WHERE sq.question_order = 2

AND sq.survey_id = (

    SELECT survey_id
    FROM surveys
    WHERE title =
        'Barangay Health Center Accessibility Survey'

)

AND NOT EXISTS (

    SELECT 1
    FROM survey_choices sc
    WHERE sc.question_id = sq.question_id
    AND sc.choice_order = c.choice_order

);


INSERT INTO survey_choices (
    question_id,
    choice_text,
    choice_order
)

SELECT
    sq.question_id,
    c.choice_text,
    c.choice_order

FROM survey_questions sq

JOIN (
    SELECT '1 - Very Dissatisfied' AS choice_text, 1 AS choice_order
    UNION ALL
    SELECT '2 - Dissatisfied', 2
    UNION ALL
    SELECT '3 - Neutral', 3
    UNION ALL
    SELECT '4 - Satisfied', 4
    UNION ALL
    SELECT '5 - Very Satisfied', 5
) c

WHERE sq.question_order = 3

AND sq.survey_id = (

    SELECT survey_id
    FROM surveys
    WHERE title =
        'Barangay Health Center Accessibility Survey'

)

AND NOT EXISTS (

    SELECT 1
    FROM survey_choices sc
    WHERE sc.question_id = sq.question_id
    AND sc.choice_order = c.choice_order

);


-- =========================================================
-- 9. CREATE RESPONSES FOR SURVEY #2
-- =========================================================

INSERT INTO responses (
    survey_id,
    resident_id,
    resident_name,
    submitted_at
)

SELECT

    s.survey_id,

    r.resident_id,

    CONCAT(
        r.first_name,
        ' ',
        r.last_name
    ),

    DATE_ADD(
        '2026-08-01 08:00:00',
        INTERVAL MOD(r.resident_id * 37, 18 * 24 * 60) MINUTE
    )

FROM residents r

CROSS JOIN (

    SELECT survey_id
    FROM surveys
    WHERE title =
        'Community Health Needs Assessment'

) s

WHERE r.resident_number BETWEEN
    '2026-0006'
    AND
    '2026-0105'

AND NOT EXISTS (

    SELECT 1
    FROM responses x

    WHERE x.survey_id = s.survey_id
    AND x.resident_id = r.resident_id

);


-- =========================================================
-- 10. CREATE RESPONSES FOR SURVEY #3
-- =========================================================

INSERT INTO responses (
    survey_id,
    resident_id,
    resident_name,
    submitted_at
)

SELECT

    s.survey_id,

    r.resident_id,

    CONCAT(
        r.first_name,
        ' ',
        r.last_name
    ),

    DATE_ADD(
        '2026-08-05 08:00:00',
        INTERVAL MOD(r.resident_id * 43, 18 * 24 * 60) MINUTE
    )

FROM residents r

CROSS JOIN (

    SELECT survey_id
    FROM surveys
    WHERE title =
        'Barangay Health Center Accessibility Survey'

) s

WHERE r.resident_number BETWEEN
    '2026-0006'
    AND
    '2026-0105'

AND NOT EXISTS (

    SELECT 1
    FROM responses x

    WHERE x.survey_id = s.survey_id
    AND x.resident_id = r.resident_id

);


-- =========================================================
-- 11. SURVEY #2 RESULTS
-- =========================================================

-- QUESTION 1: COMMON HEALTH CONCERN

INSERT INTO survey_results (
    response_id,
    question_id,
    choice_id
)

SELECT

    rp.response_id,

    sq.question_id,

    sc.choice_id

FROM responses rp

JOIN residents r
    ON r.resident_id = rp.resident_id

JOIN survey_questions sq
    ON sq.survey_id = rp.survey_id
    AND sq.question_order = 1

JOIN survey_choices sc
    ON sc.question_id = sq.question_id

WHERE rp.survey_id = (
    SELECT survey_id
    FROM surveys
    WHERE title =
        'Community Health Needs Assessment'
)

AND sc.choice_order =
    CASE MOD(r.resident_id, 5)

        WHEN 0 THEN 1
        WHEN 1 THEN 2
        WHEN 2 THEN 3
        WHEN 3 THEN 4
        ELSE 5

    END

AND NOT EXISTS (

    SELECT 1
    FROM survey_results sr

    WHERE sr.response_id = rp.response_id
    AND sr.question_id = sq.question_id

);


-- QUESTION 2: VISIT FREQUENCY

INSERT INTO survey_results (
    response_id,
    question_id,
    choice_id
)

SELECT

    rp.response_id,

    sq.question_id,

    sc.choice_id

FROM responses rp

JOIN residents r
    ON r.resident_id = rp.resident_id

JOIN survey_questions sq
    ON sq.survey_id = rp.survey_id
    AND sq.question_order = 2

JOIN survey_choices sc
    ON sc.question_id = sq.question_id

WHERE rp.survey_id = (
    SELECT survey_id
    FROM surveys
    WHERE title =
        'Community Health Needs Assessment'
)

AND sc.choice_order =
    CASE MOD(r.resident_id, 5)

        WHEN 0 THEN 1
        WHEN 1 THEN 2
        WHEN 2 THEN 3
        WHEN 3 THEN 4
        ELSE 5

    END

AND NOT EXISTS (

    SELECT 1
    FROM survey_results sr

    WHERE sr.response_id = rp.response_id
    AND sr.question_id = sq.question_id

);


-- QUESTION 3: SERVICE AVAILABILITY RATING

INSERT INTO survey_results (
    response_id,
    question_id,
    choice_id
)

SELECT

    rp.response_id,

    sq.question_id,

    sc.choice_id

FROM responses rp

JOIN residents r
    ON r.resident_id = rp.resident_id

JOIN survey_questions sq
    ON sq.survey_id = rp.survey_id
    AND sq.question_order = 3

JOIN survey_choices sc
    ON sc.question_id = sq.question_id

WHERE rp.survey_id = (
    SELECT survey_id
    FROM surveys
    WHERE title =
        'Community Health Needs Assessment'
)

AND sc.choice_order =
    CASE MOD(r.resident_id, 5)

        WHEN 0 THEN 5
        WHEN 1 THEN 4
        WHEN 2 THEN 4
        WHEN 3 THEN 3
        ELSE 2

    END

AND NOT EXISTS (

    SELECT 1
    FROM survey_results sr

    WHERE sr.response_id = rp.response_id
    AND sr.question_id = sq.question_id

);


-- =========================================================
-- 12. SURVEY #3 RESULTS
-- =========================================================

-- QUESTION 1: LOCATION

INSERT INTO survey_results (
    response_id,
    question_id,
    choice_id
)

SELECT

    rp.response_id,

    sq.question_id,

    sc.choice_id

FROM responses rp

JOIN residents r
    ON r.resident_id = rp.resident_id

JOIN survey_questions sq
    ON sq.survey_id = rp.survey_id
    AND sq.question_order = 1

JOIN survey_choices sc
    ON sc.question_id = sq.question_id

WHERE rp.survey_id = (
    SELECT survey_id
    FROM surveys
    WHERE title =
        'Barangay Health Center Accessibility Survey'
)

AND sc.choice_order =
    CASE

        WHEN MOD(r.resident_id, 4) = 0
            THEN 1

        ELSE 2

    END

AND NOT EXISTS (

    SELECT 1
    FROM survey_results sr

    WHERE sr.response_id = rp.response_id
    AND sr.question_id = sq.question_id

);


-- QUESTION 2: TRAVEL TIME

INSERT INTO survey_results (
    response_id,
    question_id,
    choice_id
)

SELECT

    rp.response_id,

    sq.question_id,

    sc.choice_id

FROM responses rp

JOIN residents r
    ON r.resident_id = rp.resident_id

JOIN survey_questions sq
    ON sq.survey_id = rp.survey_id
    AND sq.question_order = 2

JOIN survey_choices sc
    ON sc.question_id = sq.question_id

WHERE rp.survey_id = (
    SELECT survey_id
    FROM surveys
    WHERE title =
        'Barangay Health Center Accessibility Survey'
)

AND sc.choice_order =
    MOD(r.resident_id, 5) + 1

AND NOT EXISTS (

    SELECT 1
    FROM survey_results sr

    WHERE sr.response_id = rp.response_id
    AND sr.question_id = sq.question_id

);


-- QUESTION 3: ACCESSIBILITY SATISFACTION

INSERT INTO survey_results (
    response_id,
    question_id,
    choice_id
)

SELECT

    rp.response_id,

    sq.question_id,

    sc.choice_id

FROM responses rp

JOIN residents r
    ON r.resident_id = rp.resident_id

JOIN survey_questions sq
    ON sq.survey_id = rp.survey_id
    AND sq.question_order = 3

JOIN survey_choices sc
    ON sc.question_id = sq.question_id

WHERE rp.survey_id = (
    SELECT survey_id
    FROM surveys
    WHERE title =
        'Barangay Health Center Accessibility Survey'
)

AND sc.choice_order =
    CASE MOD(r.resident_id, 5)

        WHEN 0 THEN 5
        WHEN 1 THEN 4
        WHEN 2 THEN 4
        WHEN 3 THEN 3
        ELSE 2

    END

AND NOT EXISTS (

    SELECT 1
    FROM survey_results sr

    WHERE sr.response_id = rp.response_id
    AND sr.question_id = sq.question_id

);


-- =========================================================
-- 13. VERIFY DATA
-- =========================================================

SELECT
    COUNT(*) AS total_residents
FROM residents;


SELECT
    gender,
    COUNT(*) AS total
FROM residents
GROUP BY gender;


SELECT
    civil_status,
    COUNT(*) AS total
FROM residents
GROUP BY civil_status;


SELECT
    status,
    COUNT(*) AS total
FROM residents
GROUP BY status;


SELECT
    occupation,
    COUNT(*) AS total
FROM residents
GROUP BY occupation
ORDER BY total DESC;


SELECT
    s.title,
    COUNT(rp.response_id) AS total_responses
FROM surveys s
LEFT JOIN responses rp
    ON rp.survey_id = s.survey_id
GROUP BY s.survey_id, s.title
ORDER BY s.survey_id;


SELECT
    COUNT(*) AS total_survey_results
FROM survey_results;


-- =========================================================
-- CLEANUP
-- =========================================================

DROP TEMPORARY TABLE IF EXISTS dummy_numbers;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- END
-- =========================================================