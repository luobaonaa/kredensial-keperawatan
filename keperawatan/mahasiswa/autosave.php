<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/form_ujian.php';
require_once '../includes/workflow.php';
check_mahasiswa();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok' => false]); exit; }
form_check_token();
ensure_workflow_tables($conn);

$userId = (int) $_SESSION['user_id'];
$formId = (int) ($_POST['form_id'] ?? 0);
if ($formId < 1 || (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 2000000) { http_response_code(422); echo json_encode(['ok' => false, 'message' => 'Data draft tidak valid.']); exit; }
$allowed = mysqli_fetch_assoc(form_query($conn, "SELECT f.id FROM form_ujian f JOIN pengajuan p ON p.id=f.pengajuan_id WHERE f.id=? AND p.user_id=? AND p.status='Diterima'", 'ii', [$formId, $userId]));
if (!$allowed) { http_response_code(403); echo json_encode(['ok' => false, 'message' => 'Form tidak dapat diakses.']); exit; }

$payload = ['asesmen_mandiri' => is_array($_POST['mandiri'] ?? null) ? $_POST['mandiri'] : [], 'jawaban_tulis' => is_array($_POST['jawaban'] ?? null) ? $_POST['jawaban'] : []];
$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
if (strlen($json) > 1500000) { http_response_code(422); echo json_encode(['ok' => false, 'message' => 'Draft terlalu besar.']); exit; }
form_query($conn, 'INSERT INTO draft_jawaban (user_id, form_id, payload) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE payload=VALUES(payload), updated_at=NOW()', 'iis', [$userId, $formId, $json]);
echo json_encode(['ok' => true, 'saved_at' => date('H:i:s')]);

