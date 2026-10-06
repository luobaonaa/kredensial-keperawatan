<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/banding.php';
require_once '../includes/workflow.php';
check_mahasiswa();
ensure_banding_table($conn);
ensure_workflow_tables($conn);

$userId = (int) $_SESSION['user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_appeal') {
    check_csrf_token();
    $hasilId = (int) ($_POST['hasil_id'] ?? 0);
    $reason = trim((string) ($_POST['alasan'] ?? ''));
    if ($hasilId < 1 || mb_strlen($reason) < 20 || mb_strlen($reason) > 2000) {
        $error = 'Alasan banding harus berisi 20–2.000 karakter.';
    } else {
        $check = mysqli_prepare($conn, "SELECT h.id, h.penguji_id FROM hasil_tes h WHERE h.id = ? AND h.user_id = ? AND h.status = 'Selesai'");
        mysqli_stmt_bind_param($check, 'ii', $hasilId, $userId);
        mysqli_stmt_execute($check);
        $target = mysqli_fetch_assoc(mysqli_stmt_get_result($check));
        if (!$target) {
            $error = 'Hasil penilaian tidak ditemukan atau belum selesai.';
        } else {
            $insert = mysqli_prepare($conn, "INSERT INTO banding_asesmen (hasil_id, user_id, penguji_id, alasan) VALUES (?, ?, ?, ?)");
            $pengujiId = $target['penguji_id'] ? (int) $target['penguji_id'] : null;
            mysqli_stmt_bind_param($insert, 'iiis', $hasilId, $userId, $pengujiId, $reason);
            if (mysqli_stmt_execute($insert)) {
                $appealId = mysqli_insert_id($conn);
                workflow_log($conn, $userId, $userId, $hasilId, null, 'banding_diajukan', 'Asesi mengajukan banding hasil asesmen.', ['banding_id' => $appealId]);
                workflow_notify($conn, $pengujiId, 'banding_baru', 'Banding asesmen baru', ($_SESSION['username'] ?? 'Asesi') . ' mengajukan banding hasil.', 'hasil.php');
                header('Location: status.php?banding=terkirim');
                exit;
            }
            $error = mysqli_errno($conn) === 1062 ? 'Banding untuk hasil ini sudah pernah diajukan.' : 'Banding belum dapat dikirim. Silakan coba lagi.';
        }
    }
}

$stmt = mysqli_prepare($conn, "SELECT p.*, u.username AS nama_penguji, h.id AS hasil_id, h.status AS status_hasil,
        h.catatan AS catatan_penguji, h.penilaian, h.tanggal_selesai,
        b.status AS status_banding, b.alasan AS alasan_banding, b.tanggapan AS tanggapan_banding,
        b.tanggal_pengajuan AS tanggal_banding, b.tanggal_diproses
    FROM pengajuan p
    LEFT JOIN users u ON p.penguji_id = u.id
    LEFT JOIN form_ujian f ON f.pengajuan_id = p.id
    LEFT JOIN jawaban_form j ON j.form_id = f.id
    LEFT JOIN hasil_tes h ON h.id = j.hasil_id
    LEFT JOIN banding_asesmen b ON b.hasil_id = h.id
    WHERE p.user_id = ? ORDER BY p.id DESC");
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

include '../includes/header.php';
?>

<main class="container form-page status-page">
    <?php if (($_GET['banding'] ?? '') === 'terkirim'): ?>
        <div class="save-toast" role="status" aria-live="polite" data-auto-dismiss><span class="save-toast-icon">✓</span><span><strong>Banding berhasil dikirim</strong><small>Asesor akan memeriksa alasan Anda. Statusnya dapat dipantau di halaman ini.</small></span><button type="button" aria-label="Tutup notifikasi" data-dismiss-toast>×</button></div>
    <?php endif; ?>
    <section class="card form-title-card">
        <p class="eyebrow">Ruang Asesi</p>
        <h1>Status dan Hasil Saya</h1>
        <p>Lihat progres pengajuan, hasil akhir, catatan asesor, dan banding dalam satu halaman.</p>
    </section>
    <?php if ($error): ?><div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="status-card-list">
    <?php if (!mysqli_num_rows($result)): ?>
        <section class="card question-card empty-state"><strong>Belum ada pengajuan</strong><span>Mulai dari menu Pengajuan Ujian.</span></section>
    <?php endif; ?>
    <?php while ($row = mysqli_fetch_assoc($result)): $assessmentPayload = json_decode($row['penilaian'] ?? '{}', true) ?: []; $final = $assessmentPayload['final'] ?? []; $itemReviews = $assessmentPayload['items'] ?? []; $displayStatus = ($row['status'] === 'Diterima' && $row['status_hasil'] === 'Selesai') ? 'Selesai' : $row['status']; ?>
        <article class="card assessment-status-card">
            <header>
                <div><span class="stage-pill"><?= htmlspecialchars($row['jenis_tes']) ?></span><h2><?= htmlspecialchars($row['topik'] ?: 'Asesmen Umum') ?></h2><small>Diajukan <?= date('d/m/Y H:i', strtotime($row['tanggal_pengajuan'])) ?></small></div>
                <span class="status-pill status-<?= strtolower(str_replace(' ', '-', $displayStatus)) ?>"><?= htmlspecialchars($displayStatus) ?></span>
            </header>
            <div class="status-meta-grid">
                <div><span>Tanggal ujian</span><strong><?= $row['tanggal_tes'] ? date('d/m/Y', strtotime($row['tanggal_tes'])) : 'Belum ditentukan' ?></strong></div>
                <div><span>Asesor</span><strong><?= htmlspecialchars($final['assessor'] ?? $row['nama_penguji'] ?? 'Belum ditentukan') ?></strong></div>
                <div><span>Nilai akhir</span><strong><?= isset($final['score']) ? (int) $final['score'] . '/100' : 'Belum dinilai' ?></strong></div>
                <div><span>Keputusan</span><strong><?= htmlspecialchars($final['recommendation'] ?? 'Menunggu penilaian') ?></strong></div>
            </div>
            <?php if (!empty($row['catatan_penguji'])): ?><div class="assessor-note"><strong>Catatan asesor</strong><p><?= nl2br(htmlspecialchars($row['catatan_penguji'])) ?></p></div><?php endif; ?>
            <?php $feedbackItems = array_filter($itemReviews, fn($item) => trim((string) ($item['feedback'] ?? '')) !== ''); if ($feedbackItems): ?><details class="item-feedback-summary"><summary>Lihat komentar asesor per jawaban (<?= count($feedbackItems) ?>)</summary><?php foreach ($feedbackItems as $index => $item): ?><article><strong>Jawaban nomor <?= (int) $index + 1 ?> · skor <?= (int) ($item['score'] ?? 0) ?>/100</strong><p><?= nl2br(htmlspecialchars($item['feedback'])) ?></p></article><?php endforeach; ?></details><?php endif; ?>

            <?php if ($row['status_hasil'] === 'Selesai'): ?>
                <section class="appeal-panel">
                <?php if ($row['status_banding']): ?>
                    <div class="appeal-heading"><strong>FORM-10 · Banding Asesmen</strong><span class="status-pill status-<?= strtolower($row['status_banding']) ?>"><?= htmlspecialchars($row['status_banding']) ?></span></div>
                    <p><strong>Alasan Anda:</strong> <?= nl2br(htmlspecialchars($row['alasan_banding'])) ?></p>
                    <?php if ($row['tanggapan_banding']): ?><p class="appeal-response"><strong>Tanggapan asesor:</strong> <?= nl2br(htmlspecialchars($row['tanggapan_banding'])) ?></p><?php else: ?><small>Banding sedang menunggu pemeriksaan asesor.</small><?php endif; ?>
                <?php else: ?>
                    <details>
                        <summary>Ajukan banding hasil (opsional)</summary>
                        <form method="post" class="mt-3 appeal-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <input type="hidden" name="action" value="submit_appeal">
                            <input type="hidden" name="hasil_id" value="<?= (int) $row['hasil_id'] ?>">
                            <label class="form-label">Jelaskan bagian hasil yang ingin ditinjau kembali</label>
                            <textarea class="form-control" name="alasan" rows="3" minlength="20" maxlength="2000" required placeholder="Contoh: Saya ingin jawaban nomor 3 ditinjau ulang karena..."></textarea>
                            <button class="btn btn-outline-primary mt-2" type="submit">Kirim Banding</button>
                        </form>
                    </details>
                <?php endif; ?>
                </section>
            <?php endif; ?>
            <?php if ($row['hasil_id']): $activityResult = mysqli_prepare($conn, 'SELECT description, created_at FROM aktivitas_asesmen WHERE hasil_id = ? ORDER BY id DESC LIMIT 10'); mysqli_stmt_bind_param($activityResult, 'i', $row['hasil_id']); mysqli_stmt_execute($activityResult); $activities = mysqli_stmt_get_result($activityResult); if (mysqli_num_rows($activities)): ?><details class="activity-timeline"><summary>Riwayat proses asesmen</summary><?php while ($activity = mysqli_fetch_assoc($activities)): ?><div><span></span><p><strong><?= htmlspecialchars($activity['description']) ?></strong><small><?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?></small></p></div><?php endwhile; ?></details><?php endif; endif; ?>
        </article>
    <?php endwhile; ?>
    </div>
</main>
<script>
document.querySelectorAll('.appeal-form').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm('Kirim banding hasil ini kepada asesor?')) event.preventDefault();
}));
document.querySelectorAll('[data-dismiss-toast]').forEach(button => button.addEventListener('click', () => button.closest('.save-toast')?.remove()));
const toast = document.querySelector('[data-auto-dismiss]');
if (toast) window.setTimeout(() => toast.classList.add('is-hiding'), 6000);
</script>
<?php include '../includes/footer.php'; ?>
