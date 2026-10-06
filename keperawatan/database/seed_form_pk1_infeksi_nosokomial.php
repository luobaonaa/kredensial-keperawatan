<?php
require_once __DIR__ . '/../config/database.php';

$questionFile = __DIR__ . '/infeksi_nosokomial_questions.json';
$pertanyaan = file_get_contents($questionFile);
if ($pertanyaan === false || json_decode($pertanyaan, true) === null) {
    throw new RuntimeException('Daftar pertanyaan Infeksi Nosokomial tidak dapat dibaca.');
}

$jenis_tes = 'PK1';
$topik = 'Infeksi Nosokomial';
$judul = 'Asesmen PK 1 Infeksi Nosokomial';
$deskripsi = 'Asesmen unit kompetensi KES.VK001.003.01 Menerapkan Prinsip Infeksi Nosokomial.';

$bank = mysqli_prepare($conn, 'INSERT INTO bank_soal (jenis_tes, topik, judul, deskripsi, pertanyaan) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE judul = VALUES(judul), deskripsi = VALUES(deskripsi), pertanyaan = VALUES(pertanyaan)');
mysqli_stmt_bind_param($bank, 'sssss', $jenis_tes, $topik, $judul, $deskripsi, $pertanyaan);
mysqli_stmt_execute($bank);
mysqli_stmt_close($bank);

$sync = mysqli_prepare($conn, 'UPDATE form_ujian f JOIN pengajuan p ON p.id = f.pengajuan_id SET f.judul = ?, f.deskripsi = ?, f.pertanyaan = ? WHERE p.jenis_tes = ? AND p.topik = ?');
mysqli_stmt_bind_param($sync, 'sssss', $judul, $deskripsi, $pertanyaan, $jenis_tes, $topik);
mysqli_stmt_execute($sync);
$updated = mysqli_stmt_affected_rows($sync);
mysqli_stmt_close($sync);

echo "Bank soal PK1 Infeksi Nosokomial berhasil disimpan. Form lama yang diperbarui: {$updated}.\n";
