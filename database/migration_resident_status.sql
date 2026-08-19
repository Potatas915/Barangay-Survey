-- =========================================================
-- Migration: Resident Archive Status
--
-- Adds a `status` column to `residents` so a resident can be
-- archived from Resident Management instead of being deleted
-- outright. Archived residents are hidden from the active list
-- and can no longer log in to the resident portal, but their
-- record (survey responses, update history, etc.) is kept and
-- can be restored at any time.
--
-- Run this ONLY if you already imported barangay_survey.sql
-- before this feature was added (i.e. your `residents` table
-- does not yet have a `status` column). If you are setting up
-- the database for the first time, just import
-- barangay_survey.sql — it already includes this column.
--
-- Import in phpMyAdmin (XAMPP) the same way as the main file.
-- =========================================================

USE barangay_survey_db;

ALTER TABLE residents
    ADD COLUMN status ENUM('active', 'archived') NOT NULL DEFAULT 'active' AFTER photo;
