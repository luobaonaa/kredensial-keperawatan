<?php
require_once '../config/database.php'; require_once '../includes/auth.php'; require_once '../includes/workflow.php';
check_mahasiswa(); ensure_workflow_tables($conn); $userId=(int)$_SESSION['user_id'];
if (isset($_GET['open'])) { $id=(int)$_GET['open']; $stmt=mysqli_prepare($conn,'SELECT target_url FROM notifikasi WHERE id=? AND recipient_id=?'); mysqli_stmt_bind_param($stmt,'ii',$id,$userId); mysqli_stmt_execute($stmt); $notice=mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)); if ($notice) { $update=mysqli_prepare($conn,'UPDATE notifikasi SET read_at=COALESCE(read_at,NOW()) WHERE id=? AND recipient_id=?'); mysqli_stmt_bind_param($update,'ii',$id,$userId); mysqli_stmt_execute($update); $target=(string)($notice['target_url'] ?: 'dashboard.php'); if (!preg_match('/^[a-zA-Z0-9_?=&+.%\/-]+$/',$target) || str_contains($target,'..')) $target='dashboard.php'; header('Location: '.$target); exit; } }
if ($_SERVER['REQUEST_METHOD']==='POST') { check_csrf_token(); mysqli_query($conn, "UPDATE notifikasi SET read_at=NOW() WHERE recipient_id={$userId} AND read_at IS NULL"); header('Location: notifikasi.php'); exit; }
$notificationResult=mysqli_query($conn, "SELECT * FROM notifikasi WHERE recipient_id={$userId} ORDER BY created_at DESC LIMIT 100");
include '../includes/header.php'; include '../includes/notifikasi_view.php'; include '../includes/footer.php';
