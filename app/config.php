<?php

declare(strict_types=1);

// Local XAMPP defaults. Set UIDLC_DB_* and UIDLC_SESSION_DRIVER in hosted environments.
$runningOnVercel = getenv('VERCEL') === '1';
$dbHost = getenv('UIDLC_DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('UIDLC_DB_PORT') ?: '3306';
$dbName = getenv('UIDLC_DB_NAME') ?: 'uidlc_feedback';
$dbUser = getenv('UIDLC_DB_USER') ?: 'root';
$dbPassword = getenv('UIDLC_DB_PASSWORD');
$dbPassword = $dbPassword === false ? '' : $dbPassword;
$dbCharset = getenv('UIDLC_DB_CHARSET') ?: 'utf8mb4';
$dbDsn = getenv('UIDLC_DB_DSN') ?: sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $dbHost,
    $dbPort,
    $dbName,
    $dbCharset
);

if ($runningOnVercel && ($dbUser === 'root' || $dbPassword === '')) {
    error_log('UIDLC production database credentials are not configured.');
    http_response_code(500);
    exit('The application database is not configured. Contact the site administrator.');
}

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_PERSISTENT => false,
    PDO::ATTR_TIMEOUT => 10,
];

// TiDB Cloud and other public managed MySQL endpoints require verified TLS in production.
if ($runningOnVercel) {
    $caPath = getenv('UIDLC_DB_SSL_CA') ?: '/etc/ssl/certs/ca-certificates.crt';
    if (!is_file($caPath)) {
        error_log('UIDLC database CA bundle was not found.');
        http_response_code(500);
        exit('The database TLS certificate bundle is not configured.');
    }
    $pdoOptions[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
    $pdoOptions[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
}

try {
    $pdo = new PDO($dbDsn, $dbUser, $dbPassword, $pdoOptions);
} catch (PDOException $exception) {
    error_log('UIDLC database connection error: ' . $exception->getMessage());
    http_response_code(500);
    exit('Database connection failed. Check the server database configuration.');
}

/**
 * Persistent database-backed sessions for stateless container hosting.
 *
 * The handler implements SessionUpdateTimestampHandlerInterface so PHP can
 * validate incoming session IDs when strict mode is enabled. It also refreshes
 * last_activity when session data has not changed. Local XAMPP continues to use
 * PHP's standard file-based session handler.
 *
 * Session writes use atomic INSERT ... ON DUPLICATE KEY UPDATE statements.
 * No connection-scoped advisory lock is used, avoiding reliance on GET_LOCK()
 * support or connection affinity in managed MySQL-compatible services.
 */
final class PdoSessionHandler implements
    SessionHandlerInterface,
    SessionUpdateTimestampHandlerInterface,
    SessionIdInterface
{
    public function __construct(
        private PDO $pdo,
        private int $lifetime = 1440
    ) {
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function create_sid(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function validateId(string $id): bool
    {
        try {
            $statement = $this->pdo->prepare(
                'SELECT 1 FROM uidlc_sessions
                 WHERE session_id = ? AND last_activity >= ?
                 LIMIT 1'
            );
            $statement->execute([$id, time() - $this->lifetime]);
            return $statement->fetchColumn() !== false;
        } catch (Throwable $exception) {
            error_log('UIDLC session ID validation error: ' . $exception->getMessage());
            return false;
        }
    }

    public function read(string $id): string|false
    {
        try {
            $statement = $this->pdo->prepare(
                'SELECT session_data FROM uidlc_sessions
                 WHERE session_id = ? AND last_activity >= ?
                 LIMIT 1'
            );
            $statement->execute([$id, time() - $this->lifetime]);
            $data = $statement->fetchColumn();
            return $data === false ? '' : (string) $data;
        } catch (Throwable $exception) {
            error_log('UIDLC session read error: ' . $exception->getMessage());
            return false;
        }
    }

    public function write(string $id, string $data): bool
    {
        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO uidlc_sessions (session_id, session_data, last_activity)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                     session_data = VALUES(session_data),
                     last_activity = VALUES(last_activity)'
            );
            return $statement->execute([$id, $data, time()]);
        } catch (Throwable $exception) {
            error_log('UIDLC session write error: ' . $exception->getMessage());
            return false;
        }
    }

    public function updateTimestamp(string $id, string $data): bool
    {
        // Reuse the atomic upsert so the row is also repaired if it disappeared
        // between PHP's validateId() and timestamp-update callbacks.
        return $this->write($id, $data);
    }

    public function destroy(string $id): bool
    {
        try {
            $statement = $this->pdo->prepare(
                'DELETE FROM uidlc_sessions WHERE session_id = ?'
            );
            return $statement->execute([$id]);
        } catch (Throwable $exception) {
            error_log('UIDLC session destroy error: ' . $exception->getMessage());
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false
    {
        try {
            $statement = $this->pdo->prepare(
                'DELETE FROM uidlc_sessions WHERE last_activity < ?'
            );
            $statement->execute([time() - $max_lifetime]);
            return $statement->rowCount();
        } catch (Throwable $exception) {
            error_log('UIDLC session cleanup error: ' . $exception->getMessage());
            return false;
        }
    }
}

$isHttps = $runningOnVercel
    || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string) max(300, (int) (getenv('UIDLC_SESSION_LIFETIME') ?: 1440)));

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    $sessionDriver = getenv('UIDLC_SESSION_DRIVER') ?: 'files';
    if ($runningOnVercel && $sessionDriver !== 'database') {
        error_log('UIDLC_SESSION_DRIVER must be database on Vercel.');
        http_response_code(500);
        exit('The application session store is not configured. Contact the site administrator.');
    }
    if ($sessionDriver === 'database') {
        $sessionLifetime = max(300, (int) (getenv('UIDLC_SESSION_LIFETIME') ?: 1440));
        session_set_save_handler(new PdoSessionHandler($pdo, $sessionLifetime), true);
    }

    session_start();
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
