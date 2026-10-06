<?php

function ensure_banding_table(mysqli $conn): void
{
    static $ready = false;
    if ($ready) return;
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS banding_asesmen (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        hasil_id INT NOT NULL UNIQUE,
        user_id INT NOT NULL,
        penguji_id INT DEFAULT NULL,
        alasan TEXT NOT NULL,
        status ENUM('Menunggu','Diterima','Ditolak') NOT NULL DEFAULT 'Menunggu',
        tanggapan TEXT DEFAULT NULL,
        tanggal_pengajuan TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        tanggal_diproses DATETIME DEFAULT NULL,
        INDEX idx_banding_user (user_id, status),
        INDEX idx_banding_penguji (penguji_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    if (mysqli_errno($conn)) throw new RuntimeException('Penyimpanan banding belum dapat disiapkan.');
    $ready = true;
}

function assessment_final_data(?string $json): array
{
    $payload = json_decode($json ?: '{}', true);
    return is_array($payload) && is_array($payload['final'] ?? null) ? $payload['final'] : [];
}

