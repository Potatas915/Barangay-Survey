-- =========================================================
-- Barangay Health Center Survey Management System
-- Database Schema
-- =========================================================
-- Import this file in phpMyAdmin (XAMPP) before running
-- the application.
--
-- Resident Personal Information Updating System
-- based on IT 305 - Advance Web Development Activity 5
-- =========================================================

CREATE DATABASE IF NOT EXISTS barangay_survey_db;
USE barangay_survey_db;

-- =========================================================
-- TABLE: residents
-- =========================================================
-- Stores the main personal information of each resident.
-- =========================================================

CREATE TABLE residents (
    resident_id INT AUTO_INCREMENT PRIMARY KEY,

    resident_number VARCHAR(20) NOT NULL UNIQUE,

    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    extension_name VARCHAR(20),

    civil_status ENUM(
        'Single',
        'Married',
        'Widowed',
        'Separated',
        'Divorced'
    ),

    birthday DATE,
    age INT,

    occupation VARCHAR(100),
    employer VARCHAR(100),
    employer_address VARCHAR(150),

    email VARCHAR(100),
    contact_number VARCHAR(20),
    address VARCHAR(150),

    photo VARCHAR(255),

    password VARCHAR(255) NOT NULL,
    is_first_login TINYINT(1) NOT NULL DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);


-- =========================================================
-- TABLE: resident_parents
-- =========================================================
-- Stores the resident's father's and mother's names.
-- One parent record belongs to one resident.
-- =========================================================

CREATE TABLE resident_parents (
    parent_id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,

    father_name VARCHAR(100),
    mother_name VARCHAR(100),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_parents_resident
        FOREIGN KEY (resident_id)
        REFERENCES residents(resident_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    UNIQUE KEY unique_resident_parents (resident_id)
);


-- =========================================================
-- TABLE: resident_spouses
-- =========================================================
-- Stores spouse information.
-- One resident can have one spouse record.
-- =========================================================

CREATE TABLE resident_spouses (
    spouse_id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,

    spouse_name VARCHAR(100),
    occupation VARCHAR(100),
    employer VARCHAR(100),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_spouse_resident
        FOREIGN KEY (resident_id)
        REFERENCES residents(resident_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    UNIQUE KEY unique_resident_spouse (resident_id)
);


-- =========================================================
-- TABLE: resident_children
-- =========================================================
-- Stores children information.
-- A resident may have multiple children.
-- =========================================================

CREATE TABLE resident_children (
    child_id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,

    child_name VARCHAR(100) NOT NULL,
    age INT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_child_resident
        FOREIGN KEY (resident_id)
        REFERENCES residents(resident_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_children_resident (resident_id)
);


-- =========================================================
-- TABLE: resident_references
-- =========================================================
-- Stores character references.
-- The activity requires two character references.
-- Signature is optional.
-- =========================================================

CREATE TABLE resident_references (
    reference_id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,

    reference_name VARCHAR(100) NOT NULL,
    signature VARCHAR(255),

    reference_order TINYINT NOT NULL DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_reference_resident
        FOREIGN KEY (resident_id)
        REFERENCES residents(resident_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_references_resident (resident_id)
);


-- =========================================================
-- TABLE: resident_update_history
-- =========================================================
-- Records changes made to resident information.
-- This supports the staff requirement to view updated
-- records and provides an audit trail of changes.
-- =========================================================

CREATE TABLE resident_update_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,

    resident_id INT NOT NULL,

    updated_by_type ENUM('resident', 'staff') NOT NULL,
    updated_by_id INT,

    update_section VARCHAR(50) NOT NULL,

    old_data TEXT,
    new_data TEXT,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_update_history_resident
        FOREIGN KEY (resident_id)
        REFERENCES residents(resident_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_update_history_resident (resident_id),
    INDEX idx_update_history_date (updated_at)
);


-- =========================================================
-- TABLE: staff
-- =========================================================

CREATE TABLE staff (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    password VARCHAR(255) NOT NULL,
    role VARCHAR(30) DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =========================================================
-- TABLE: surveys
-- =========================================================

CREATE TABLE surveys (
    survey_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    created_by INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by)
        REFERENCES staff(staff_id)
);


-- =========================================================
-- TABLE: survey_questions
-- =========================================================

CREATE TABLE survey_questions (
    question_id INT AUTO_INCREMENT PRIMARY KEY,
    survey_id INT NOT NULL,
    question_text VARCHAR(255) NOT NULL,

    question_type ENUM(
        'multiple_choice',
        'yes_no',
        'rating',
        'short_answer'
    ) NOT NULL,

    is_required TINYINT(1) DEFAULT 1,
    question_order INT DEFAULT 0,

    FOREIGN KEY (survey_id)
        REFERENCES surveys(survey_id)
        ON DELETE CASCADE
);


-- =========================================================
-- TABLE: survey_choices
-- =========================================================
-- Used for multiple_choice and rating questions.
-- =========================================================

CREATE TABLE survey_choices (
    choice_id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    choice_text VARCHAR(150) NOT NULL,
    choice_order INT DEFAULT 0,

    FOREIGN KEY (question_id)
        REFERENCES survey_questions(question_id)
        ON DELETE CASCADE
);


-- =========================================================
-- TABLE: responses
-- =========================================================
-- One row per resident submission of a survey.
-- =========================================================

CREATE TABLE responses (
    response_id INT AUTO_INCREMENT PRIMARY KEY,

    survey_id INT NOT NULL,
    resident_id INT NOT NULL,

    resident_name VARCHAR(100) NOT NULL,

    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (survey_id)
        REFERENCES surveys(survey_id)
        ON DELETE CASCADE,

    FOREIGN KEY (resident_id)
        REFERENCES residents(resident_id)
        ON DELETE CASCADE,

    UNIQUE KEY unique_submission (survey_id, resident_id)
);


-- =========================================================
-- TABLE: survey_results
-- =========================================================

CREATE TABLE survey_results (
    result_id INT AUTO_INCREMENT PRIMARY KEY,

    response_id INT NOT NULL,
    question_id INT NOT NULL,

    choice_id INT NULL,
    answer_text VARCHAR(255) NULL,

    FOREIGN KEY (response_id)
        REFERENCES responses(response_id)
        ON DELETE CASCADE,

    FOREIGN KEY (question_id)
        REFERENCES survey_questions(question_id)
        ON DELETE CASCADE,

    FOREIGN KEY (choice_id)
        REFERENCES survey_choices(choice_id)
        ON DELETE SET NULL
);


-- =========================================================
-- TABLE: login_history
-- =========================================================

CREATE TABLE login_history (
    log_id INT AUTO_INCREMENT PRIMARY KEY,

    user_type ENUM('resident','staff') NOT NULL,
    user_id INT NOT NULL,

    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =========================================================
-- SAMPLE DATA
-- =========================================================
-- Default staff:
-- username = admin
-- password = admin123
--
-- Default resident password:
-- Resident Number
--
-- Passwords below are already hashed with PHP
-- password_hash().
-- =========================================================


INSERT INTO staff (
    username,
    full_name,
    email,
    password,
    role
) VALUES
(
    'admin',
    'Barangay Health Staff',
    'staff@example.com',
    '$2b$10$mF8MNjFO6F1s.nbadeDc5.h9teXtBeuSGv2zBWKOH/.3AKQPksCsm',
    'admin'
);


INSERT INTO residents (
    resident_number,
    first_name,
    last_name,
    email,
    contact_number,
    address,
    password,
    is_first_login
) VALUES

(
    '2026-0001',
    'Justin Lian',
    'Enriquez',
    'lianjustin91@gmail.com',
    '09752509652',
    'Sulucan, Bocaue, Bulacan',
    '$2b$10$mGrOfVoC0/ORly7Z4iHH0OPlksjcOmNqPP9hh4vkrXXPJnQfq4LMm',
    1
),

(
    '2026-0002',
    'Cielo Marie',
    'Estolloso',
    'cieloestolloso09@gmail.com',
    '09763008362',
    'Iba-Ibayo, Hagonoy, Bulacan',
    '$2b$10$Vd25nNypemf8GlUJX.pAsehs9fdQUS/7oZvtBEyU7dleDslVSinpe',
    1
),

(
    '2026-0003',
    'Mary Pauleen',
    'Salvador',
    'pauleensalvador@gmail.com',
    '09690640080',
    'Malolos, Bulacan',
    '$2b$10$wz1gKG5NezDiO9kCo3cMveQBx1ju78z7M4plgT349Uuazy9oa.Tr.',
    1
),

(
    '2026-0004',
    'Kylie Denise',
    'Marasigan',
    'kyliedenise12@gmail.com',
    '09235476895',
    'San Isidro 1, Paombong, Bulacan',
    '$2b$10$Va7OVF/mb56F6KFD99w5FegKMXJd3g51ohmsvz12JI2AJdqnhN0hu',
    1
),

(
    '2026-0005',
    'Aaron Gabriel',
    'Ranes',
    'aarongabrielranes@gmail.com',
    '09690924629',
    'Malis, Guiguinto, Bulacan',
    '$2b$10$qPNa1js1imq7pYTuQF2AluEX3BwQRYr5BXeZfaAiJbzR55z8.vrw2',
    1
);


-- =========================================================
-- SAMPLE SURVEY
-- =========================================================

INSERT INTO surveys (
    title,
    description,
    created_by,
    start_date,
    end_date,
    status
) VALUES
(
    'Barangay Health Services Feedback',
    'Help us improve our health services by answering this short survey.',
    1,
    '2026-07-01',
    '2026-12-31',
    'active'
);


-- =========================================================
-- SAMPLE SURVEY QUESTIONS
-- =========================================================

INSERT INTO survey_questions (
    survey_id,
    question_text,
    question_type,
    is_required,
    question_order
) VALUES

(
    1,
    'How satisfied are you with the health center services?',
    'rating',
    1,
    1
),

(
    1,
    'Have you availed of a free check-up in the past 6 months?',
    'yes_no',
    1,
    2
),

(
    1,
    'Which service do you use most often?',
    'multiple_choice',
    1,
    3
),

(
    1,
    'Any suggestions to improve our services?',
    'short_answer',
    0,
    4
);


-- =========================================================
-- SAMPLE SURVEY CHOICES
-- =========================================================

INSERT INTO survey_choices (
    question_id,
    choice_text,
    choice_order
) VALUES

(1, '1 - Very Dissatisfied', 1),
(1, '2 - Dissatisfied', 2),
(1, '3 - Neutral', 3),
(1, '4 - Satisfied', 4),
(1, '5 - Very Satisfied', 5),

(2, 'Yes', 1),
(2, 'No', 2),

(3, 'Consultation', 1),
(3, 'Vaccination', 2),
(3, 'Dental Checkup', 3),
(3, 'Maternal Care', 4);