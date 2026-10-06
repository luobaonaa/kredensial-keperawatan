<?php
header('Location: admin/hasil.php');
exit;
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/form_ujian.php';
check_penguji();
$penguji_id = (int) $_SESSION['user_id'];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    form_check_token();
    $hasil_id = (int) ($_POST['hasil_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $catatan = is_string($_POST['catatan'] ?? null) ? trim($_POST['catatan']) : '';
    if (!in_array($status, ['Belum Selesai', 'Selesai'], true) || strlen($catatan) > 10000) {
        $error = 'Status atau catatan tidak valid.';
    } else {
        form_query($conn, "UPDATE hasil_tes SET status = ?, catatan = ?, tanggal_selesai = CASE WHEN ? = 'Selesai' THEN NOW() ELSE NULL END WHERE id = ? AND penguji_id = ?", 'sssii', [$status, $catatan, $status, $hasil_id, $penguji_id]);
        if (mysqli_affected_rows($conn)) {
            header('Location: hasil.php?tersimpan=1');
            exit;
        }
        $error = 'Tidak ada perubahan atau hasil tidak ditugaskan kepada Anda.';
    }
}
$result = form_query($conn, 'SELECT h.*, u.username AS nama_perawat, f.judul, f.pertanyaan, j.jawaban, j.created_at AS dikirim FROM hasil_tes h JOIN users u ON u.id = h.user_id LEFT JOIN jawaban_form j ON j.hasil_id = h.id LEFT JOIN form_ujian f ON f.id = j.form_id WHERE h.penguji_id = ? ORDER BY h.id DESC', 'i', [$penguji_id]);
include '../includes/admin_header.php';
?>
<main class="container form-page">
    <section class="card form-title-card"><p class="eyebrow">Respons perawat</p><h1>Penilaian hasil ujian</h1><p>Baca jawaban perawat, berikan catatan, dan tentukan status penyelesaian ujian.</p></section>
    <?php if ($error): ?><div class="alert alert-danger"><?= form_escape($error) ?></div><?php endif; ?>
    <?php if (isset($_GET['tersimpan'])): ?><div class="alert alert-success" role="status">Penilaian berhasil disimpan.</div><?php endif; ?>
    <?php if (!mysqli_num_rows($result)): ?><section class="card question-card">Belum ada jawaban yang masuk untuk Anda.</section><?php endif; ?>
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
    <section class="card question-card mb-4">
        <p class="eyebrow"><?= form_escape($row['jenis_tes']) ?> · <?= form_escape($row['nama_perawat']) ?></p>
        <h2><?= form_escape($row['judul'] ?? 'Hasil ujian sebelumnya') ?></h2>
        <p><span class="badge bg-<?= $row['status'] === 'Selesai' ? 'success' : 'secondary' ?>"><?= form_escape($row['status']) ?></span> <?= form_escape($row['dikirim'] ?? '') ?></p>
        <?php if ($row['jawaban'] !== null): ?>
        <details class="mb-4"><summary>Lihat jawaban perawat</summary>
            <?php $answers = json_decode($row['jawaban'], true); foreach (json_decode($row['pertanyaan'], true) as $index => $question): ?>
            <div class="response-item"><h3 class="question-label"><?= $index + 1 ?>. <?= form_escape(is_array($question) ? $question['text'] : $question) ?></h3><p class="answer-text mb-0"><?= form_escape(is_array($answers[$index] ?? null) ? implode(', ', array_filter($answers[$index])) : ($answers[$index] ?? '')) ?></p></div>
            <?php endforeach; ?>
        </details>
        <?php elseif (strpos($row['file_path'] ?? '', '../uploads/') === 0): ?>
            <a href="<?= form_escape($row['file_path']) ?>" class="mb-3" download>Unduh jawaban lama</a>
        <?php endif; ?>
        <form method="post" class="form-stack">
            <input type="hidden" name="token" value="<?= form_escape(form_token()) ?>">
            <input type="hidden" name="hasil_id" value="<?= (int) $row['id'] ?>">
            <div><label for="catatan-<?= (int) $row['id'] ?>" class="form-label">Catatan penguji</label><textarea id="catatan-<?= (int) $row['id'] ?>" name="catatan" class="form-control" rows="3" maxlength="10000"><?= form_escape($row['catatan'] ?? '') ?></textarea></div>
            <div><label for="status-<?= (int) $row['id'] ?>" class="form-label">Status ujian</label><select id="status-<?= (int) $row['id'] ?>" name="status" class="form-select"><option value="Belum Selesai" <?= $row['status'] !== 'Selesai' ? 'selected' : '' ?>>Belum Selesai</option><option value="Selesai" <?= $row['status'] === 'Selesai' ? 'selected' : '' ?>>Selesai</option></select></div>
            <button type="submit" class="btn btn-primary align-self-start">Simpan penilaian</button>
        </form>
    </section>
    <?php endwhile; ?>
</main>
<?php include '../includes/admin_footer.php'; ?>
