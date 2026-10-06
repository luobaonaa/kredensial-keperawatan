<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/tahap_check.php';
check_mahasiswa();

$file_param = $_GET['file'] ?? '';
if ($file_param === '') {
    http_response_code(404);
    exit('File tidak ditemukan.');
}

$requested = realpath(__DIR__ . '/' . ltrim($file_param, '/'));
$normalizedParam = str_replace('\\', '/', ltrim((string) $file_param, '/'));
$pathParts = explode('/', $normalizedParam);
$requestedStage = strtoupper((string) ($pathParts[1] ?? ''));
$allowedStages = ['PK1', 'PK2', 'PK3', 'BP1', 'BP2', 'BP3'];
if (($pathParts[0] ?? '') !== 'uploads' || !in_array($requestedStage, $allowedStages, true) || !can_access_tahap($conn, (int) $_SESSION['user_id'], $requestedStage)) {
    http_response_code(403);
    exit('Anda tidak memiliki akses ke dokumen ini.');
}
$allowed_base_dirs = array_filter(array_map(
    fn($tahap) => realpath(__DIR__ . '/uploads/' . $tahap),
    ['PK1', 'PK2', 'PK3', 'BP1', 'BP2', 'BP3']
));

$is_allowed = false;
foreach ($allowed_base_dirs as $base_dir) {
    if ($requested !== false && ($requested === $base_dir || strpos($requested, $base_dir . DIRECTORY_SEPARATOR) === 0)) {
        $is_allowed = true;
        break;
    }
}

if (!$is_allowed || !is_file($requested)) {
    http_response_code(404);
    exit('File tidak ditemukan atau tidak diizinkan.');
}

$ext = strtolower(pathinfo($requested, PATHINFO_EXTENSION));
$allowedMime = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'txt' => 'text/plain',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
];
if (!isset($allowedMime[$ext])) {
    http_response_code(404);
    exit('Jenis file tidak diizinkan.');
}
$mime = $allowedMime[$ext];

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . str_replace('"', '', basename($requested)) . '"');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($requested));
readfile($requested);
exit;
