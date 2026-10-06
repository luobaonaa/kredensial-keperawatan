<?php
$legacyStage = strtoupper(trim((string) ($_GET['tahap'] ?? '')));
$legacyTarget = in_array($legacyStage, ['PK1', 'PK2', 'PK3', 'BP1', 'BP2', 'BP3'], true)
    ? 'mahasiswa/tahap.php?tahap=' . rawurlencode($legacyStage)
    : 'mahasiswa/dashboard.php';
header('Location: ' . $legacyTarget);
exit;
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/tahap_check.php';
require_once '../includes/form_ujian.php';
check_mahasiswa();
$tahap = strtoupper(trim($_GET['tahap'] ?? ''));
if (!in_array($tahap, ['PK1', 'PK2', 'PK3', 'BP1', 'BP2', 'BP3'], true)) {
    http_response_code(404);
    exit('Tahap ujian tidak ditemukan.');
}
$user_id = (int) $_SESSION['user_id'];
$pengajuan = mysqli_fetch_assoc(form_query($conn, "SELECT p.*, u.username AS nama_penguji FROM pengajuan p LEFT JOIN users u ON u.id = p.penguji_id WHERE p.user_id = ? AND p.jenis_tes = ? AND p.status = 'Diterima' ORDER BY p.id DESC LIMIT 1", 'is', [$user_id, $tahap]));
if (!can_access_tahap($conn, $user_id, $tahap) || !$pengajuan) {
    http_response_code(403);
    include '../includes/header.php';
    echo '<main class="container form-page"><section class="card form-title-card"><h1>Akses belum tersedia</h1><p>Selesaikan tahap sebelumnya dan tunggu persetujuan pengajuan dari penguji.</p><a href="status.php">Lihat status pengajuan</a></section></main>';
    include '../includes/footer.php';
    exit;
}
$form = mysqli_fetch_assoc(form_query($conn, 'SELECT * FROM form_ujian WHERE pengajuan_id = ?', 'i', [(int) $pengajuan['id']]));
$questions = $form ? json_decode($form['pertanyaan'], true) : [];
$questions = array_map(fn($q) => is_array($q) ? $q : ['type' => 'essay', 'text' => $q, 'options' => []], is_array($questions) ? $questions : []);
$response = $form ? mysqli_fetch_assoc(form_query($conn, 'SELECT j.*, h.status, h.catatan FROM jawaban_form j JOIN hasil_tes h ON h.id = j.hasil_id WHERE j.form_id = ?', 'i', [(int) $form['id']])) : null;
$error = '';
$answers = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    form_check_token();
    if (!$form || (int) ($_POST['form_id'] ?? 0) !== (int) $form['id']) {
        $error = 'Form telah berubah atau belum tersedia. Muat ulang halaman.';
    } elseif ($response) {
        $error = 'Jawaban untuk form ini sudah dikirim.';
    } else {
        $posted = $_POST['jawaban'] ?? [];
        foreach ($questions as $index => $question) {
            $value = is_array($posted) ? ($posted[$index] ?? null) : null;
            $answers[$index] = is_array($value) ? array_map(fn($v) => is_string($v) ? trim($v) : '', $value) : (is_string($value) ? trim($value) : '');
            if ($answers[$index] === '' || $answers[$index] === [] || (is_string($answers[$index]) && strlen($answers[$index]) > 20000)) $error = 'Isi semua jawaban sebelum mengirim form.';
        }
        if (!$error) {
            mysqli_begin_transaction($conn);
            try {
                form_query($conn, "INSERT INTO hasil_tes (user_id, jenis_tes, file_name, file_path, status, penguji_id) VALUES (?, ?, '', '', 'Belum Selesai', ?)", 'isi', [$user_id, $tahap, (int) $pengajuan['penguji_id']]);
                $hasil_id = mysqli_insert_id($conn);
                form_query($conn, 'INSERT INTO jawaban_form (form_id, hasil_id, jawaban) VALUES (?, ?, ?)', 'iis', [(int) $form['id'], $hasil_id, json_encode($answers, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
                mysqli_commit($conn);
                header('Location: tahap.php?tahap=' . urlencode($tahap) . '&terkirim=1');
                exit;
            } catch (Throwable $e) {
                mysqli_rollback($conn);
                $error = 'Jawaban belum tersimpan atau sudah pernah dikirim. Muat ulang halaman untuk melihat status.';
            }
        }
    }
}
include '../includes/header.php';
?>
<main class="container form-page">
    <section class="card form-title-card">
        <p class="eyebrow">Ujian Keperawatan · <?= form_escape($tahap) ?></p>
        <h1><?= form_escape($form['judul'] ?? 'Form belum tersedia') ?></h1>
        <p class="answer-text"><?= form_escape($form['deskripsi'] ?? 'Penguji belum menerbitkan form untuk pengajuan ini.') ?></p>
        <div class="form-identity"><span>Perawat <strong><?= form_escape($_SESSION['username']) ?></strong></span><span>Penguji <strong><?= form_escape($pengajuan['nama_penguji'] ?? '-') ?></strong></span></div>
        <?php if ($form && !$response): ?><p class="text-danger mt-3 mb-0">* Wajib diisi</p><?php endif; ?>
    </section>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= form_escape($error) ?></div><?php endif; ?>
    <?php if ($response): ?>
        <section class="card question-card mb-3"><h2>Jawaban sudah terkirim</h2><p>Dikirim pada <?= form_escape($response['created_at']) ?>. Status: <strong><?= form_escape($response['status']) ?></strong>.</p><?php if ($response['catatan']): ?><p class="answer-text">Catatan penguji: <?= form_escape($response['catatan']) ?></p><?php endif; ?><a href="status.php">Lihat status ujian</a></section>
        <?php $saved = json_decode($response['jawaban'], true); foreach ($questions as $index => $question): ?>
            <section class="card question-card mb-3"><h2 class="question-label"><?= $index + 1 ?>. <?= form_escape($question['text']) ?></h2><p class="answer-text mb-0"><?= form_escape(is_array($saved[$index] ?? null) ? implode(', ', $saved[$index]) : ($saved[$index] ?? '')) ?></p></section>
        <?php endforeach; ?>
    <?php elseif ($form): ?>
        <form method="post" class="form-stack" id="exam-form">
            <input type="hidden" name="token" value="<?= form_escape(form_token()) ?>">
            <input type="hidden" name="form_id" value="<?= (int) $form['id'] ?>">
            <?php foreach ($questions as $index => $question): ?>
                <section class="card question-card"><label class="question-label" for="answer-<?= $index ?>"><?= $index + 1 ?>. <?= form_escape($question['text']) ?> <span class="text-danger">*</span></label><?php if ($question['type'] === 'essay'): ?><textarea id="answer-<?= $index ?>" name="jawaban[<?= $index ?>]" rows="5" class="form-control answer-input" placeholder="Tulis jawaban Anda" maxlength="20000" required><?= form_escape($answers[$index] ?? '') ?></textarea><?php elseif ($question['type'] === 'checklist'): ?><div class="checklist-table"><table class="table"><thead><tr><th>Kriteria</th><th>Ya</th><th>Tidak</th><th>Catatan</th></tr></thead><tbody><?php foreach ($question['options'] as $rowIndex => $option): ?><tr><td><?= form_escape($option) ?></td><td><input type="radio" name="jawaban[<?= $index ?>][<?= $rowIndex ?>]" value="<?= form_escape($option) ?>: Ya" required></td><td><input type="radio" name="jawaban[<?= $index ?>][<?= $rowIndex ?>]" value="<?= form_escape($option) ?>: Tidak"></td><td><input class="form-control form-control-sm" name="jawaban[<?= $index ?>][catatan_<?= $rowIndex ?>]" placeholder="Catatan"></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="option-list"><?php foreach ($question['options'] as $option): ?><label class="option-item"><input type="<?= $question['type'] === 'single' ? 'radio' : 'checkbox' ?>" name="jawaban[<?= $index ?>]<?= $question['type'] === 'multiple' ? '[]' : '' ?>" value="<?= form_escape($option) ?>" <?= $question['type'] === 'single' ? 'required' : '' ?>> <?= form_escape($option) ?></label><?php endforeach; ?></div><?php endif; ?></section>
            <?php endforeach; ?>
            <div class="form-submit-bar"><span id="answer-progress" aria-live="polite">0 dari <?= count($questions) ?> pertanyaan terisi</span><button type="submit" class="btn btn-primary">Kirim jawaban</button></div>
        </form>
    <?php endif; ?>
</main>
<script>
const examForm = document.getElementById('exam-form');
if (examForm) {
    const fields = [...examForm.querySelectorAll('.answer-input')];
    const update = () => document.getElementById('answer-progress').textContent = fields.filter(field => field.value.trim()).length + ' dari ' + fields.length + ' pertanyaan terisi';
    examForm.addEventListener('input', update);
    examForm.addEventListener('submit', () => {
        examForm.querySelector('button[type="submit"]').disabled = true;
    });
    update();
}
</script>
<?php include '../includes/footer.php'; ?>
