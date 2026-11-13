# UIDLC Students' Complaints and Feedback Management System

A PHP/MySQL web application prototype for submitting, reviewing and tracking student complaints and feedback in a distance-learning context.

> **Academic prototype only.** This project is not an official University of Ibadan or UIDLC service. It is not connected to UIDLC production systems, student records, identity systems, learning platforms or payment services. It must not be deployed for real student data without institutional authorisation and a separate security, privacy and operational review.

## Features

- Student account registration and sign-in
- Student dashboard showing only the authenticated student's submissions
- Complaint and feedback submission with unique reference codes
- Administrator-only complaint queue
- Status updates and responses to students
- Timestamped status-history records visible to students and administrators
- Duplicate/no-change status update prevention
- Password hashing, CSRF tokens, prepared SQL statements and HTML output escaping

## Technology

- PHP 8+
- MySQL 8+ or a compatible MariaDB release
- HTML5, CSS and JavaScript
- PDO for database access
- XAMPP for local development

## Repository structure

```text
.
├── app/
│   ├── admin.php
│   ├── config.php
│   ├── dashboard.php
│   ├── database.sql
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   ├── register.php
│   ├── style.css
│   └── submit.php
├── .gitignore
└── README.md
```

## Local installation with XAMPP

1. Install XAMPP with Apache, PHP and MySQL/MariaDB.
2. Download this repository or clone it:
   ```bash
   git clone https://github.com/Derx101/UIDLC-Complaints-Feedback-System.git
   ```
3. Copy the contents of the repository's `app/` directory into a folder named `uidlc-feedback` inside XAMPP's `htdocs` directory. For a typical Windows installation, this is:
   ```text
   C:\xampp\htdocs\uidlc-feedback
   ```
4. Start Apache and MySQL from the XAMPP Control Panel.
5. For a **new, empty test database only**, open phpMyAdmin, select the Import tab and import `database.sql`. Do not import it over a database that already contains records.
6. Review the database connection values near the top of `app/config.php`. The default values are intended only for a local XAMPP development environment. Use a dedicated database account for any shared or hosted environment.
7. Visit:
   ```text
   http://localhost/uidlc-feedback/
   ```
8. Register a synthetic student account and test the student workflow.

### Administrator account

The application does not include a public administrator-registration page or a web-accessible account-creation helper. Create an administrator account only through a controlled, local administrative procedure. Do not publish administrator passwords, password hashes from real accounts or test credentials in this repository.

## Database

The schema creates three related tables:

- `users`: user identity, password hash and role
- `complaints`: complaint/feedback submission and current status
- `status_history`: status transitions, actor, note and timestamp

Use the SQL file only to initialise a fresh database. Back up an existing database before any schema changes.

## Security and privacy

This repository is published for academic demonstration and source review. The local XAMPP configuration is **not production-ready**. Before any deployment:

- Use HTTPS and secure cookie settings.
- Configure a dedicated least-privilege database account and keep credentials outside source control.
- Set strong environment-specific configuration and secret management.
- Add login rate limiting, monitoring, database backups and tested recovery procedures.
- Define data retention, deletion, access logging and incident-response procedures.
- Conduct independent security testing, including authorisation and cross-account data-isolation tests.
- Obtain institutional approval and establish a lawful basis and privacy notice before processing real student data.

Do not upload real student names, emails, complaint text, database dumps, session files, passwords, API keys or private institutional information to this public repository. Use synthetic test records only.

## Verification

The PHP source files in the supplied application package were checked with PHP's syntax checker. Browser demonstrations documented for the academic project include registration, login, complaint submission, reference-code generation, administrator review, status updates, student-visible responses and status history. Some negative security tests (including cross-account isolation and invalid-input boundary cases) remain subject to separate live verification; code inspection alone is not proof that those tests pass.

## Academic context

Developed as a Group 41 CSC 476 academic project at the Department of Computer Science, University of Ibadan. The case-study context does not imply endorsement, adoption or approval by the University of Ibadan or UIDLC.
