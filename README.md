# Barangay Health Center Survey Management System

IT305 Advance Web Development - Act 5 Set B

## Folder structure

```
barangay_survey/
├── config/
│   └── database.php          database connection settings
├── database/
│   └── barangay_survey.sql   full schema + sample data, import this first
├── assets/
│   ├── css/style.css
│   └── js/script.js
├── includes/
│   ├── functions.php         session handling, login guards, helpers
│   └── staff_nav.php         shared staff navbar with active-page highlighting
├── resident/                 resident-facing pages
├── staff/                    staff-facing pages
└── index.php                 landing page
```

## How to run it in XAMPP

1. Start XAMPP and turn on Apache and MySQL from the XAMPP Control Panel.
2. Copy the whole `barangay_survey` folder into `C:\xampp\htdocs\` (or wherever
   your XAMPP htdocs folder is on Mac/Linux, usually `/Applications/XAMPP/htdocs/`).
3. Open `http://localhost/phpmyadmin` in your browser.
4. Drop the existing `barangay_survey_db` database if you already have an
   older copy, then click "Import" and choose `database/barangay_survey.sql`.
   This creates the database with all tables (including the new
   `resident_name` column on `responses`) and the sample data below.
5. Open `http://localhost/barangay_survey/` in your browser. That's the app.

## Test accounts

Staff login:
- Username: `admin`
- Password: `admin123`

Resident logins (default password for each is the same as their resident number,
and each will be asked to set a new password on first login only):

| Resident Number | Name                  |
|------------------|-----------------------|
| 2026-0001        | Justin Lian Enriquez  |
| 2026-0002        | Cielo Marie Estolloso  |
| 2026-0003        | Mary Pauleen Salvador  |
| 2026-0004        | Kylie Denise Marasigan |
| 2026-0005        | Aaron Gabriel Ranes    |

## What changed in this update

- Added the 5 dummy resident accounts above to the seed data.
- Survey Start Date can no longer be set earlier than today. This is enforced
  with the `min` attribute on the date picker (frontend) and with
  `is_valid_start_date()` in `includes/functions.php` (backend), used in both
  `staff/survey_add.php` and the new `staff/survey_edit.php`. Editing an
  already-active survey without changing its start date is still allowed even
  if that original date is in the past.
- `responses` now has a `resident_name` column, filled in at submission time,
  so admins can identify who answered without an extra join. Short-answer
  responses in `staff/results.php` now show the respondent's name next to
  each answer.
- Added a "View Respondents" button beside the survey dropdown on
  `staff/results.php`. It opens a modal (styled to match the existing card
  theme) listing every resident who answered the selected survey, along with
  their resident number and submission time.
- Change Password is no longer a separate nav item. It's now a section inside
  `resident/profile.php`, alongside the existing profile fields.
  `resident/change_password.php` still exists, but only as the forced
  first-login flow (redirected to straight from login, never linked in any
  menu). Once the password is changed there, `is_first_login` is set to 0 and
  the prompt never appears again on later logins.
- Added `staff/survey_edit.php` so staff can edit a survey's title,
  description, start date, and end date, pre-filled with the current values
  and validated the same way as creating a new survey.
- Removed the Delete Question feature entirely from
  `staff/question_management.php` (the delete link, the delete route, and the
  backend delete logic). The page is now a read-only list of a survey's
  questions.
- Fixed the staff navigation bar. Every staff page previously had a different,
  hand-written set of nav links with no active-page indicator. All staff
  pages now include the same `includes/staff_nav.php` partial, which
  highlights exactly the current page and keeps the same links everywhere.

## Notes for the group

- `config/database.php` is where the database name/user/password live. If your
  MySQL root user has a password set, update it there.
- Every page that needs a login includes `includes/functions.php`, which
  starts the session and has `require_resident_login()` / `require_staff_login()`
  to block access if not logged in.
- Passwords are hashed with PHP's `password_hash()` and checked with
  `password_verify()`, never stored in plain text.
- `results.php` computes tallies and renders them as simple bar charts using
  plain CSS, no external chart library needed.
- `reports.php` and `results.php` both have a Print button that uses the
  browser's print-to-PDF, which covers the "generate printable reports /
  export" requirement without needing an extra library.
- Exporting straight to Excel and a login history report page are still left
  as easy extensions if your group wants to go for the optional points (the
  `login_history` table is already being written to on every login).
