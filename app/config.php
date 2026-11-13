<?php

declare(strict_types=1);

// Defaults are for an isolated local XAMPP installation only.
// Override these values with environment variables in shared/hosted environments.
$host = getenv('UIDLC_DB_HOST') ?: '127.0.0.1';
$db = getenv('UIDLC_DB_NAME') ?: 'uidlc_feedback';
$user = getenv('UIDLC_DB_USER') ?: 'root';
$pass = getenv('UIDLC_DB_PASSWORD');
$pass = $pass === false ? '' : $pass;
$charset = getenv('UIDLC_DB_CHARSET') ?: 'utf8mb4';

// Session cookie settings for local development and HTTPS deployment.
$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$db};charset={$charset}",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    error_log('UIDLC database connection error: ' . $exception->getMessage());
    http_response_code(500);
    exit('Database connection failed. Check the local database configuration.');
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function require_admin(): void
{
    require_login();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Access denied.');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function verify_csrf(): void
{
    $sessionToken = $_SESSION['csrf'] ?? '';
    $postedToken = $_POST['csrf'] ?? '';

    if (!is_string($sessionToken) || !is_string($postedToken) || !hash_equals($sessionToken, $postedToken)) {
        http_response_code(419);
        exit('Invalid request token. Refresh the page and try again.');
    }
}

function status_labels(): array
{
    return ['Submitted', 'Under Review', 'In Progress', 'Resolved', 'Closed'];
}
