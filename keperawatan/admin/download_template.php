<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
check_penguji();

$relative = str_replace('\\', '/', trim((string) ($_GET['file'] ?? '')));
if ($relative === '' || str_contains($relative, "\0")) {
    http_response_code(404);
    exit('Dokumen tidak ditemukan.');
}

$root = realpath(__DIR__ . '/../template_docs');
$requested = $root === false ? false : realpath($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative));
if ($root === false || $requested === false || !is_file($requested) || !str_starts_with($requested, $root . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    exit('Dokumen tidak ditemukan.');
}

$extension = strtolower(pathinfo($requested, PATHINFO_EXTENSION));
$allowedMime = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'txt' => 'text/plain; charset=UTF-8',
];
if (!isset($allowedMime[$extension])) {
    http_response_code(404);
    exit('Jenis dokumen tidak diizinkan.');
}

header('Content-Type: ' . $allowedMime[$extension]);
header('Content-Disposition: attachment; filename="' . str_replace('"', '', basename($requested)) . '"');
header('Content-Length: ' . filesize($requested));
header('X-Content-Type-Options: nosniff');
readfile($requested);
exit;
