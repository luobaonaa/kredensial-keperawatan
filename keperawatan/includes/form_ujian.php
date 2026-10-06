<?php
function form_escape($value): string
{
    return htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
}

function form_query($conn, string $sql, string $types = '', array $values = [])
{
    $stmt = mysqli_prepare($conn, $sql);
    if ($types !== '') mysqli_stmt_bind_param($stmt, $types, ...$values);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

function form_token(): string
{
    if (empty($_SESSION['form_token'])) $_SESSION['form_token'] = bin2hex(random_bytes(32));
    return $_SESSION['form_token'];
}

function form_check_token(): void
{
    $postedToken = $_POST['token'] ?? $_POST['csrf_token'] ?? null;
    $validFormToken = is_string($postedToken) && hash_equals(form_token(), $postedToken);
    $validSessionToken = is_string($postedToken) && !empty($_SESSION['csrf_token']) && hash_equals((string) $_SESSION['csrf_token'], $postedToken);
    if (!$validFormToken && !$validSessionToken) {
        http_response_code(403);
        exit('Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.');
    }
}
