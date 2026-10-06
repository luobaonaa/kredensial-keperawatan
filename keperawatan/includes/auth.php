<?php
$requestIsHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) === '443');
// Security headers shared by authenticated and public pages that include this file.
if (!headers_sent()) {
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net");
    if ($requestIsHttps) header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => $requestIsHttps,
        'cookie_samesite' => 'Lax',
    ]);
}

if (isset($_SESSION['user_id'])) {
    $now = time();
    if (isset($_SESSION['last_activity']) && $now - (int) $_SESSION['last_activity'] > 1800) {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['last_activity'] = $now;
    if (!isset($_SESSION['last_regenerated']) || $now - (int) $_SESSION['last_regenerated'] > 900) {
        session_regenerate_id(true);
        $_SESSION['last_regenerated'] = $now;
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function check_csrf_token(): void
{
    if (!is_string($_POST['csrf_token'] ?? null) || !hash_equals(csrf_token(), $_POST['csrf_token'])) {
        http_response_code(403);
        exit('Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.');
    }
}

function check_mahasiswa() {
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['perawat', 'mahasiswa'], true)) {
        header("Location: ../login.php");
        exit();
    }
}

function check_penguji() {
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'penguji') {
        header("Location: ../admin/login.php");
        exit();
    }
}

function login_is_rate_limited(): bool
{
    $now = time();
    $attempts = array_values(array_filter($_SESSION['login_failures'] ?? [], fn($time) => is_int($time) && $time > $now - 900));
    $_SESSION['login_failures'] = $attempts;
    return count($attempts) >= 5;
}

function record_failed_login(): void
{
    $_SESSION['login_failures'][] = time();
}

function clear_failed_logins(): void
{
    unset($_SESSION['login_failures']);
}
?>
