<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'root';
$dbname = getenv('DB_NAME') ?: 'db_keperawatan';
$port = (int) (getenv('DB_PORT') ?: 3306);

try {
    $conn = mysqli_connect($host, $user, $pass, $dbname, $port);
    mysqli_set_charset($conn, 'utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(503);
    exit('Koneksi database gagal. Periksa konfigurasi database dan pastikan file SQL sudah diimpor.');
}
