<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
check_mahasiswa();

include '../includes/header.php';
?>

<div class="container">
    <div class="p-5 mb-4 bg-light rounded-3 border dashboard-hero">
        <div class="container-fluid py-3">
            <p class="eyebrow">Ruang Asesi</p>
            <h1 class="display-5 fw-bold">Mulai ujian dari sini</h1>
            <p class="col-md-8 fs-5">Ikuti tiga langkah di bawah. Sistem akan memberi status pada setiap tahap sehingga Anda tahu harus melakukan apa berikutnya.</p>
            <div class="quick-steps">
                <div><b>1</b><span><strong>Ajukan ujian</strong><small>Pilih tahap, topik, dan tanggal ujian.</small></span></div>
                <div><b>2</b><span><strong>Isi form</strong><small>Setelah disetujui, buka menu PK/BP yang dipilih.</small></span></div>
                <div><b>3</b><span><strong>Lihat hasil</strong><small>Pantau status sampai asesor menyelesaikan penilaian.</small></span></div>
            </div>
            <div class="d-flex gap-2 flex-wrap mt-4"><a href="pengajuan.php" class="btn btn-primary">Mulai Ajukan Ujian</a><a href="status.php" class="btn btn-outline-primary">Lihat Status Saya</a></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Belum punya ujian aktif?</h5>
                    <p class="card-text">Silakan lakukan pengajuan tes PK atau BP terlebih dahulu. Setelah disetujui oleh penguji, Anda dapat mengisi form ujian dan mengirim jawaban langsung kepada penguji.</p>
                    <a href="pengajuan.php" class="btn btn-primary">Ajukan Ujian</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Sudah mengirim pengajuan?</h5>
                    <p class="card-text">Pantau progres persetujuan pengajuan, penguji yang ditunjuk, serta status kelulusan tiap tahapan ujian Anda.</p>
                    <a href="status.php" class="btn btn-outline-primary">Cek Status</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
