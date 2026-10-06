<?php

function ensure_workflow_tables(mysqli $conn): void
{
    static $ready = false;
    if ($ready) return;
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS draft_jawaban (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, form_id INT NOT NULL, payload LONGTEXT NOT NULL, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uniq_draft_user_form (user_id, form_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS aktivitas_asesmen (id BIGINT AUTO_INCREMENT PRIMARY KEY, actor_id INT NOT NULL, user_id INT DEFAULT NULL, hasil_id INT DEFAULT NULL, pengajuan_id INT DEFAULT NULL, event_type VARCHAR(50) NOT NULL, description VARCHAR(500) NOT NULL, metadata LONGTEXT DEFAULT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_activity_result (hasil_id, created_at), INDEX idx_activity_user (user_id, created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS notifikasi (id BIGINT AUTO_INCREMENT PRIMARY KEY, recipient_id INT NOT NULL, type VARCHAR(50) NOT NULL, title VARCHAR(150) NOT NULL, message VARCHAR(500) NOT NULL, target_url VARCHAR(500) DEFAULT NULL, read_at DATETIME DEFAULT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_notification_recipient (recipient_id, read_at, created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $ready = true;
}

function workflow_log(mysqli $conn, int $actorId, ?int $userId, ?int $hasilId, ?int $pengajuanId, string $type, string $description, array $metadata = []): void
{
    ensure_workflow_tables($conn);
    $meta = $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;
    $stmt = mysqli_prepare($conn, 'INSERT INTO aktivitas_asesmen (actor_id, user_id, hasil_id, pengajuan_id, event_type, description, metadata) VALUES (?, ?, ?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'iiiisss', $actorId, $userId, $hasilId, $pengajuanId, $type, $description, $meta);
    mysqli_stmt_execute($stmt);
}

function workflow_notify(mysqli $conn, ?int $recipientId, string $type, string $title, string $message, string $targetUrl): void
{
    if (!$recipientId) return;
    ensure_workflow_tables($conn);
    $stmt = mysqli_prepare($conn, 'INSERT INTO notifikasi (recipient_id, type, title, message, target_url) VALUES (?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'issss', $recipientId, $type, $title, $message, $targetUrl);
    mysqli_stmt_execute($stmt);
}

function workflow_unread_count(mysqli $conn, int $userId): int
{
    ensure_workflow_tables($conn);
    $stmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS total FROM notifikasi WHERE recipient_id = ? AND read_at IS NULL');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    return (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] ?? 0);
}

