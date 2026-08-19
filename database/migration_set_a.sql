-- =========================================================
-- Migration: Act 5 - Set A
-- "Development of a Web-Based Resident Personal Information
-- Updating System for a Barangay Health Center"
--
-- Run this ONLY if you already imported barangay_survey.sql
-- before this feature was added (i.e. your `residents` table
-- does not yet have a `middle_name` column). If you are
-- setting up the database for the first time, just import
-- barangay_survey.sql — it already includes these fields.
--
-- Import in phpMyAdmin (XAMPP) the same way as the main file.
-- =========================================================

USE barangay_survey_db;

ALTER TABLE residents
    ADD COLUMN middle_name VARCHAR(50) AFTER last_name,
    ADD COLUMN extension_name VARCHAR(10) AFTER middle_name,
    ADD COLUMN civil_status ENUM('Single','Married','Widowed','Separated','Divorced') DEFAULT NULL AFTER extension_name,
    ADD COLUMN birthday DATE DEFAULT NULL AFTER address,
    ADD COLUMN age INT DEFAULT NULL AFTER birthday,
    ADD COLUMN occupation VARCHAR(100) AFTER age,
    ADD COLUMN employer VARCHAR(100) AFTER occupation,
    ADD COLUMN employer_address VARCHAR(150) AFTER employer,
    ADD COLUMN father_name VARCHAR(100) AFTER employer_address,
    ADD COLUMN mother_name VARCHAR(100) AFTER father_name,
    ADD COLUMN spouse_name VARCHAR(100) AFTER mother_name,
    ADD COLUMN spouse_occupation VARCHAR(100) AFTER spouse_name,
    ADD COLUMN spouse_employer VARCHAR(100) AFTER spouse_occupation,
    ADD COLUMN reference1_name VARCHAR(100) AFTER spouse_employer,
    ADD COLUMN reference1_signature VARCHAR(100) AFTER reference1_name,
    ADD COLUMN reference2_name VARCHAR(100) AFTER reference1_signature,
    ADD COLUMN reference2_signature VARCHAR(100) AFTER reference2_name,
    ADD COLUMN photo VARCHAR(255) DEFAULT NULL AFTER reference2_signature,
    ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

CREATE TABLE IF NOT EXISTS resident_children (
    child_id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,
    child_name VARCHAR(100) NOT NULL,
    age INT DEFAULT NULL,
    FOREIGN KEY (resident_id) REFERENCES residents(resident_id) ON DELETE CASCADE
);
