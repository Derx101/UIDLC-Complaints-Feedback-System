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
- MySQL 8+ or compatible MySQL-protocol service
- PDO MySQL and mbstring PHP extensions
- HTML, CSS and JavaScript
- XAMPP for local development
- Vercel Container runtime for hosted demonstration

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

The project uses Vercel's Container runtime with FrankenPHP. Vercel container instances are stateless, so the application uses a MySQL-backed PHP session table when deployed there. A durable remote MySQL-compatible database is required; a database running on a developer's laptop or inside the container is not suitable.

### Suggested no-cost database for a small demonstration

TiDB Cloud Starter offers a MySQL-compatible endpoint with a free monthly allowance. The documented allowance is 5 GiB of row storage, 5 GiB of columnar storage and 50 million request units per instance, with up to five eligible instances per organisation. The instance can be set with a zero spending limit to remain within the free quota; after the quota is reached, new connections can be blocked until the quota resets or the limit is changed. Check the provider's current terms before creating it.

### Create the hosted database

1. Create a TiDB Cloud account and create a **MySQL-compatible Starter** instance. Choose a region close to the Vercel deployment.
2. In the instance's **Connect** dialog, select the public connection method and note the host, port, username prefix and password. TiDB Cloud Starter's public connection requires TLS. The normal MySQL port for TiDB Cloud Starter is `4000`.
3. Open the TiDB SQL editor or connect with a MySQL-compatible client. Use the instance's prefixed administrator username to create the application database:
   ```sql
   CREATE DATABASE uidlc_feedback CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. Select `uidlc_feedback` and execute the contents of `app/database.sql`. This creates `users`, `complaints`, `status_history` and `uidlc_sessions`.
5. Create a dedicated application database user and grant it only the permissions required to operate the application. Use TiDB's required instance username prefix. Do not use the root account for the deployed application.
6. Keep the database host, port, database name, prefixed application username and generated password for the Vercel environment-variable step.

### Required Vercel environment variables

Set these in Vercel Project Settings → Environment Variables for Production (and Preview if you will test previews):

| Variable | Value |
|---|---|
| `UIDLC_DB_HOST` | TiDB Cloud host from the Connect dialog |
| `UIDLC_DB_PORT` | `4000` for TiDB Cloud Starter |
| `UIDLC_DB_NAME` | `uidlc_feedback` |
| `UIDLC_DB_USER` | Prefixed least-privilege application username |
| `UIDLC_DB_PASSWORD` | Application database password (mark sensitive/encrypted) |
| `UIDLC_DB_CHARSET` | `utf8mb4` |
| `UIDLC_SESSION_DRIVER` | `database` |
| `UIDLC_SESSION_LIFETIME` | Optional session lifetime in seconds; default `1440` |

The hosted PHP configuration enables TLS certificate verification using the container's system CA bundle. If a database provider requires a separate CA bundle, set `UIDLC_DB_SSL_CA` to the CA certificate path. `UIDLC_DB_DSN` can optionally provide a complete PDO MySQL DSN; username and password are still configured separately.

Never commit database credentials to GitHub or paste them into chat.

### Administrator account

The application has no public administrator registration page and no web-accessible administrator-creation helper. Create the first administrator with a controlled SQL procedure using a password hash generated with PHP's `password_hash()`. Do not commit the password, password hash, or database records to GitHub. Use a new production-only password; never reuse the local demonstration password.

## Security and deployment limitations

This repository is an academic source-code release, not a production security certification. Before institutional use:

- Use HTTPS and secure cookie settings (the hosted configuration marks cookies secure).
- Use a dedicated least-privilege database account and a strong secret password.
- Require verified TLS for the database connection.
- Add login throttling/rate limiting, monitoring, backups and tested recovery.
- Conduct independent security tests, including cross-account isolation, CSRF rejection, invalid input, session fixation and administrator access control.
- Define retention, deletion, access logging, privacy notices and incident response.
- Obtain institutional approval and determine a lawful basis before processing any real student information.

Use synthetic records only. Never upload student names, emails, complaint narratives, database dumps, session files, credentials, API keys or private institutional information to this public repository.

## Verification status

Browser demonstrations documented for the academic project include student registration/login, complaint and feedback submission, reference generation, administrator review, status updates, student-visible responses and status history. PHP syntax checks are not a substitute for live verification. Cross-account isolation and negative security tests remain separate live-test items unless independently executed and recorded.

## Academic context

Developed as a Group 41 CSC 476 academic project at the Department of Computer Science, University of Ibadan. The case-study context does not imply University of Ibadan or UIDLC endorsement, adoption or approval.
