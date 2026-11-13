# UIDLC Students' Complaints and Feedback Management System

A PHP/MySQL academic prototype for submitting, reviewing and tracking student complaints and feedback in a distance-learning context.

> **Academic prototype only.** This is not an official University of Ibadan or UIDLC service. It is not connected to UIDLC production systems, student records, identity systems, learning platforms or payment services. Do not deploy it with real student data without institutional authorisation and an independent security, privacy and operational review.

## Features

- Student registration and sign-in
- Student dashboard and personal submission records
- Complaint and feedback submissions with unique reference codes
- Administrator-only complaint queue
- Status updates and written responses
- Student- and administrator-visible status history
- Duplicate/no-change update prevention
- Password hashing, CSRF tokens, prepared SQL statements and HTML output escaping

## Stack

- PHP 8.3 (FrankenPHP runtime for Vercel)
- MySQL 8+ or compatible MariaDB
- PDO MySQL and mbstring PHP extensions
- HTML, CSS and JavaScript
- XAMPP for local development

## Repository layout

- `app/` — application source and database schema
- `Dockerfile.vercel` — PHP/FrankenPHP container entrypoint for Vercel
- `deploy/Caddyfile` — HTTP server configuration
- `vercel.json` — selects Vercel's Container runtime

## Local XAMPP setup

1. Install XAMPP with Apache, PHP and MySQL/MariaDB.
2. Clone the repository:
   ```bash
   git clone https://github.com/Derx101/UIDLC-Complaints-Feedback-System.git
   ```
3. Copy the repository's `app/` contents into `C:\xampp\htdocs\uidlc-feedback`.
4. Start Apache and MySQL in XAMPP.
5. In phpMyAdmin, create/select an empty database named `uidlc_feedback`, then import `app/database.sql`.
6. Local defaults in `app/config.php` are `127.0.0.1`, database `uidlc_feedback`, username `root`, and a blank password. These defaults are for an isolated local XAMPP installation only.
7. Open `http://localhost/uidlc-feedback/` and register a synthetic student account.

Local PHP sessions use PHP's file session handler. The production database session handler is selected only when `UIDLC_SESSION_DRIVER=database` is set.

## Vercel deployment

The project uses Vercel's Container runtime with FrankenPHP. Vercel container instances are stateless, so the application uses a MySQL-backed PHP session table when deployed there. A durable remote MySQL/MariaDB database is required; a database running on a developer's laptop or inside the container is not suitable.

### Required production environment variables

Set these in Vercel Project Settings → Environment Variables (Production and Preview as needed):

| Variable | Purpose |
|---|---|
| `UIDLC_DB_HOST` | Managed MySQL host |
| `UIDLC_DB_PORT` | Database port, normally `3306` |
| `UIDLC_DB_NAME` | Database/schema name |
| `UIDLC_DB_USER` | Dedicated database user (not `root`) |
| `UIDLC_DB_PASSWORD` | Database password (mark sensitive/encrypted) |
| `UIDLC_DB_CHARSET` | `utf8mb4` |
| `UIDLC_SESSION_DRIVER` | Must be `database` |
| `UIDLC_SESSION_LIFETIME` | Optional session lifetime in seconds; default `1440` |

Alternatively, `UIDLC_DB_DSN` may provide a complete PDO MySQL DSN; the username and password are still supplied separately. Never commit credentials to GitHub or paste them into chat.

### Database initialization

1. Provision a managed MySQL/MariaDB database in a region reasonably close to the Vercel deployment.
2. Create a dedicated database/schema and a least-privilege application user.
3. Allow network access from the Vercel deployment according to the provider's current connection guidance. If the database requires IP allowlisting, account for Vercel's variable outbound IPs; do not assume a fixed IP.
4. Import `app/database.sql` into the newly created empty database. This creates `users`, `complaints`, `status_history` and `uidlc_sessions` tables.
5. Add the environment variables above to Vercel. Do not put the production database dump or real student records in this repository.

### Administrator account

The application has no public administrator registration page and no web-accessible administrator-creation helper. Create the first administrator locally through a controlled database procedure after generating a password hash with PHP's `password_hash()`. Do not commit the password, password hash, or database records to GitHub. Use a new production-only password; never reuse the local demonstration password.

## Security and deployment limitations

This repository is an academic source-code release, not a production security certification. Before institutional use:

- Use HTTPS and secure cookie settings (the hosted configuration marks cookies secure).
- Use a dedicated least-privilege database account and a strong secret password.
- Enable provider-side encrypted connections where supported and configure TLS verification for the database.
- Add login throttling/rate limiting, monitoring, backups and tested recovery.
- Conduct independent security tests, including cross-account isolation, CSRF rejection, invalid input, session fixation and administrator access control.
- Define retention, deletion, access logging, privacy notices and incident response.
- Obtain institutional approval and determine a lawful basis before processing any real student information.

Use synthetic records only. Never upload student names, emails, complaint narratives, database dumps, session files, credentials, API keys or private institutional information to this public repository.

## Verification status

Browser demonstrations documented for the academic project include student registration/login, complaint and feedback submission, reference generation, administrator review, status updates, student-visible responses and status history. PHP syntax checks are not a substitute for live verification. Cross-account isolation and negative security tests remain separate live-test items unless independently executed and recorded.

## Academic context

Developed as a Group 41 CSC 476 academic project at the Department of Computer Science, University of Ibadan. The case-study context does not imply University of Ibadan or UIDLC endorsement, adoption or approval.
