<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/form_ujian.php';
check_penguji();

$banks = form_query($conn, 'SELECT jenis_tes, topik, judul, deskripsi, pertanyaan FROM bank_soal ORDER BY jenis_tes, topik');
include '../includes/admin_header.php';
?>
<main class="container form-page">
    <section class="card form-title-card">
        <p class="eyebrow">Bank soal tetap</p>
        <h1>Form asesmen berdasarkan Word</h1>
        <p>Soal dikunci sesuai dokumen asesmen. Saat pengajuan disetujui, sistem otomatis memasangkan form yang sesuai tahap dan topiknya ke perawat.</p>
    </section>
    <?php if (!mysqli_num_rows($banks)): ?>
        <section class="card question-card mt-4"><p class="mb-0">Belum ada bank soal yang diimpor.</p></section>
    <?php endif; ?>
    <?php while ($bank = mysqli_fetch_assoc($banks)): ?>
        <?php $questions = json_decode($bank['pertanyaan'], true) ?: []; ?>
        <section class="card question-card mt-4">
            <div class="d-flex justify-content-between gap-3 flex-wrap align-items-start">
                <div><p class="eyebrow mb-1"><?= form_escape($bank['jenis_tes'] . ' · ' . $bank['topik']) ?></p><h2 class="h4 mb-2"><?= form_escape($bank['judul']) ?></h2><p class="mb-0"><?= form_escape($bank['deskripsi']) ?></p></div>
                <span class="badge bg-primary rounded-pill"><?= count($questions) ?> pertanyaan</span>
            </div>
            <details class="mt-3"><summary>Lihat isi form</summary><ol class="mt-3 mb-0"><?php foreach ($questions as $question): ?><li class="answer-text mb-2"><?= form_escape(is_array($question) ? ($question['text'] ?? '') : $question) ?></li><?php endforeach; ?></ol></details>
        </section>
    <?php endwhile; ?>
</main>
<?php include '../includes/admin_footer.php'; ?>
