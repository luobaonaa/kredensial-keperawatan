<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/banding.php';
check_penguji();
ensure_banding_table($conn);

// Statistik Sederhana
$total_menunggu = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT p.id) as total FROM pengajuan p JOIN users u ON u.id = p.user_id WHERE p.status='Menunggu'"))['total'];
$total_diterima = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT p.id) as total FROM pengajuan p JOIN users u ON u.id = p.user_id LEFT JOIN form_ujian f ON f.pengajuan_id = p.id LEFT JOIN jawaban_form j ON j.form_id = f.id LEFT JOIN hasil_tes h ON h.id = j.hasil_id WHERE p.status='Diterima' AND (h.id IS NULL OR h.status <> 'Selesai')"))['total'];
$total_selesai = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT h.id) as total FROM hasil_tes h JOIN users u ON u.id = h.user_id JOIN jawaban_form j ON j.hasil_id = h.id WHERE h.status='Selesai'"))['total'];
$total_mhs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role IN ('perawat', 'mahasiswa')"))['total'];
$total_banding = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM banding_asesmen WHERE status='Menunggu'"))['total'];
?>
<?php include '../includes/admin_header.php'; ?>

<div class="container mt-4">
    <section class="card form-title-card mb-4">
        <p class="eyebrow">Ruang Asesor</p>
        <h1 class="h2">Apa yang perlu dikerjakan?</h1>
        <p class="text-muted">Mulai dari pengajuan masuk, tunggu asesi mengirim jawaban, lalu selesaikan penilaiannya.</p>
        <div class="quick-steps">
            <div><b>1</b><span><strong>Setujui pengajuan</strong><small>Pilih asesor dan pasang form ujian.</small></span></div>
            <div><b>2</b><span><strong>Tunggu jawaban</strong><small>Asesi mengisi dan mengirim form kepada asesor.</small></span></div>
            <div><b>3</b><span><strong>Nilai dan selesaikan</strong><small>Periksa nilai otomatis, koreksi, lalu simpan hasil final.</small></span></div>
        </div>
        <div class="d-flex gap-2 flex-wrap mt-4"><a href="pengajuan.php" class="btn btn-primary">Buka Pengajuan Masuk</a><a href="hasil.php?status=Belum+Selesai" class="btn btn-outline-primary">Buka Penilaian Asesi</a></div>
    </section>

    <?php if ($total_banding > 0): ?><div class="alert alert-warning d-flex justify-content-between align-items-center gap-3"><span><strong><?= (int) $total_banding ?> banding baru</strong> menunggu keputusan asesor.</span><a class="btn btn-sm btn-dark" href="hasil.php">Periksa Banding</a></div><?php endif; ?>
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Menunggu Persetujuan</h5>
                    <p class="card-text fs-3 font-weight-bold"><?= $total_menunggu ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Tes Diterima / Berjalan</h5>
                    <p class="card-text fs-3 font-weight-bold"><?= $total_diterima ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Tes Selesai</h5>
                    <p class="card-text fs-3 font-weight-bold"><?= $total_selesai ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Perawat</h5>
                    <p class="card-text fs-3 font-weight-bold"><?= $total_mhs ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/admin_footer.php'; ?>
