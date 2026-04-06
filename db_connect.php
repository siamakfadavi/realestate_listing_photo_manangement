<?php
/**
 * Credentials are loaded from a file stored OUTSIDE the web root.
 *
 * Local dev:  /home/siamak/Downloads/db_config_repsmall.php
 * Live server: adjust the path below to point one level above your public_html.
 *              e.g. /home2/siamak65/db_config_repsmall.php
 */
$config_path = dirname(__DIR__) . '/db_config_repsmall.php';

if (!file_exists($config_path)) {
    die("Server configuration error. Please contact the administrator.");
}

require_once $config_path;

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("DB connection failed: " . $e->getMessage());
    die("A server error occurred. Please try again later.");
}

// ── CSRF helpers ────────────────────────────────────────────────────────────
// Session must be started before calling these (each page does that already).

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(string $token): bool {
    return !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

