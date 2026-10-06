<?php
header('Location: admin/soal.php');
exit;
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/form_ujian.php';
check_penguji();
$error = '';
$penguji_id = (int) $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    form_check_token();
    $pengajuan_id = (int) ($_POST['pengajuan_id'] ?? 0);
    $judul = is_string($_POST['judul'] ?? null) ? trim($_POST['judul']) : '';
    $deskripsi = is_string($_POST['deskripsi'] ?? null) ? trim($_POST['deskripsi']) : '';
    $question_texts = $_POST['pertanyaan'] ?? [];
    $question_types = $_POST['tipe'] ?? [];
    $question_options = $_POST['opsi'] ?? [];
    $questions = [];
    if (is_array($question_texts) && is_array($question_types) && is_array($question_options)) {
        foreach ($question_texts as $index => $text) {
            $text = is_string($text) ? trim($text) : '';
            $type = $question_types[$index] ?? 'essay';
            $options = is_string($question_options[$index] ?? null) ? array_values(array_filter(array_map('trim', preg_split('/\R/', $question_options[$index])))) : [];
            if ($text !== '') $questions[] = ['type' => in_array($type, ['essay', 'single', 'multiple', 'checklist'], true) ? $type : 'essay', 'text' => $text, 'options' => $options];
        }
    }
    $accepted = mysqli_fetch_assoc(form_query($conn, "SELECT id FROM pengajuan WHERE id = ? AND penguji_id = ? AND status = 'Diterima'", 'ii', [$pengajuan_id, $penguji_id]));
    if (!$accepted) {
        $error = 'Pilih pengajuan yang diterima dan ditugaskan kepada Anda.';
    } elseif ($judul === '' || strlen($judul) > 200 || strlen($deskripsi) > 10000 || count($questions) < 1 || count($questions) > 50 || max(array_map(fn($q) => strlen($q['text']), $questions)) > 5000 || array_filter($questions, fn($q) => $q['type'] !== 'essay' && count($q['options']) < 2)) {
        $error = 'Isi judul (maksimal 200 karakter) dan 1–50 pertanyaan (maksimal 5.000 karakter per pertanyaan).';
    } else {
        $existing = mysqli_fetch_assoc(form_query($conn, 'SELECT id FROM form_ujian WHERE pengajuan_id = ?', 'i', [$pengajuan_id]));
        if ($existing) {
            $error = 'Form untuk pengajuan ini sudah diterbitkan.';
        } else {
            try {
                form_query($conn, 'INSERT INTO form_ujian (pengajuan_id, judul, deskripsi, pertanyaan) VALUES (?, ?, ?, ?)', 'isss', [$pengajuan_id, $judul, $deskripsi, json_encode($questions, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
                header('Location: soal.php?terkirim=1');
                exit;
            } catch (Throwable $e) {
                $error = 'Form belum tersimpan. Muat ulang halaman dan coba kembali.';
            }
        }
    }
}
$accepted = form_query($conn, "SELECT p.*, u.username FROM pengajuan p JOIN users u ON u.id = p.user_id LEFT JOIN form_ujian f ON f.pengajuan_id = p.id WHERE p.status = 'Diterima' AND p.penguji_id = ? AND f.id IS NULL ORDER BY p.id DESC", 'i', [$penguji_id]);
$forms = form_query($conn, 'SELECT f.*, p.jenis_tes, u.username, j.id AS jawaban_id FROM form_ujian f JOIN pengajuan p ON p.id = f.pengajuan_id JOIN users u ON u.id = p.user_id LEFT JOIN jawaban_form j ON j.form_id = f.id WHERE p.penguji_id = ? ORDER BY f.id DESC', 'i', [$penguji_id]);
include '../includes/admin_header.php';
?>
<main class="container form-page">
    <section class="card form-title-card"><p class="eyebrow">Kelola soal</p><h1>Buat form ujian</h1><p>Susun pertanyaan untuk perawat. Semua pertanyaan wajib dijawab sebelum form dikirim.</p></section>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= form_escape($error) ?></div><?php endif; ?>
    <?php if (isset($_GET['terkirim'])): ?><div class="alert alert-success" role="status">Form berhasil diterbitkan dan siap diisi perawat.</div><?php endif; ?>
    <?php if (mysqli_num_rows($accepted)): ?>
    <form method="post" class="form-stack">
        <input type="hidden" name="token" value="<?= form_escape(form_token()) ?>">
        <section class="card question-card">
            <label for="pengajuan" class="form-label">Penerima form <span class="text-danger">*</span></label>
            <select id="pengajuan" name="pengajuan_id" class="form-select mb-3" required><option value="">Pilih perawat dan tahap</option>
                <?php while ($row = mysqli_fetch_assoc($accepted)): ?><option value="<?= (int) $row['id'] ?>" <?= (int) ($_POST['pengajuan_id'] ?? 0) === (int) $row['id'] ? 'selected' : '' ?>><?= form_escape($row['username'] . ' · ' . $row['jenis_tes'] . ' · ' . ($row['topik'] ?? '-')) ?></option><?php endwhile; ?>
            </select>
            <label for="judul" class="form-label">Judul form <span class="text-danger">*</span></label>
            <input id="judul" name="judul" class="form-control mb-3" maxlength="200" required value="<?= form_escape($_POST['judul'] ?? '') ?>" placeholder="Contoh: Ujian Kompetensi PK1">
            <label for="deskripsi" class="form-label">Petunjuk pengerjaan</label>
            <textarea id="deskripsi" name="deskripsi" class="form-control" rows="3" maxlength="10000"><?= form_escape($_POST['deskripsi'] ?? '') ?></textarea>
        </section>
        <div id="questions" class="form-stack">
            <?php foreach (($questions ?? [['type' => 'essay', 'text' => '', 'options' => []]]) ?: [['type' => 'essay', 'text' => '', 'options' => []]] as $index => $question): ?>
            <section class="card question-card"><label class="form-label" for="question-<?= $index ?>">Pertanyaan <span class="question-number"><?= $index + 1 ?></span> <span class="text-danger">*</span></label><textarea id="question-<?= $index ?>" name="pertanyaan[]" class="form-control mb-3" rows="3" maxlength="5000" required><?= form_escape($question['text'] ?? '') ?></textarea><label class="form-label">Jenis jawaban</label><select name="tipe[]" class="form-select mb-3"><option value="essay" <?= ($question['type'] ?? '') === 'essay' ? 'selected' : '' ?>>Uraian</option><option value="single" <?= ($question['type'] ?? '') === 'single' ? 'selected' : '' ?>>Pilih satu jawaban</option><option value="multiple" <?= ($question['type'] ?? '') === 'multiple' ? 'selected' : '' ?>>Pilih beberapa jawaban</option><option value="checklist" <?= ($question['type'] ?? '') === 'checklist' ? 'selected' : '' ?>>Checklist Ya Tidak</option></select><label class="form-label">Pilihan atau baris checklist</label><textarea name="opsi[]" class="form-control" rows="3" placeholder="Satu pilihan atau kriteria per baris"><?= form_escape(implode("\n", $question['options'] ?? [])) ?></textarea><small class="text-muted">Untuk uraian, bagian ini boleh kosong. Checklist otomatis menyediakan pilihan Ya, Tidak, dan catatan.</small><button type="button" class="btn btn-sm btn-outline-danger mt-3 align-self-start remove-question">Hapus pertanyaan</button></section>
            <?php endforeach; ?>
        </div>
        <div class="d-flex gap-2 flex-wrap"><button type="button" id="add-question" class="btn btn-outline-primary">+ Tambah pertanyaan</button><button class="btn btn-primary" type="submit">Terbitkan form</button></div>
    </form>
    <?php else: ?><div class="card question-card">Belum ada pengajuan baru yang diterima dan ditugaskan kepada Anda.</div><?php endif; ?>
    <h2 class="mt-5">Form yang diterbitkan</h2>
    <?php if (!mysqli_num_rows($forms)): ?><p>Belum ada form yang diterbitkan.</p><?php endif; ?>
    <?php while ($row = mysqli_fetch_assoc($forms)): ?>
        <section class="card question-card mb-3"><h3><?= form_escape($row['judul']) ?></h3><p><?= form_escape($row['username'] . ' · ' . $row['jenis_tes']) ?> <span class="badge bg-<?= $row['jawaban_id'] ? 'success' : 'secondary' ?>"><?= $row['jawaban_id'] ? 'Sudah dijawab' : 'Menunggu jawaban' ?></span></p><details><summary>Lihat pertanyaan</summary><ol><?php foreach (json_decode($row['pertanyaan'], true) as $q): ?><li class="answer-text"><?= form_escape(is_array($q) ? $q['text'] : $q) ?></li><?php endforeach; ?></ol></details><?php if ($row['jawaban_id']): ?><a href="hasil.php" class="mt-3">Lihat jawaban dan beri penilaian</a><?php endif; ?></section>
    <?php endwhile; ?>
</main>
<script>
const questions = document.getElementById('questions');
if (questions) {
    let nextId = questions.children.length;
    document.getElementById('add-question').addEventListener('click', () => {
        if (questions.children.length >= 50) return;
        const card = questions.firstElementChild.cloneNode(true);
        const field = card.querySelector('textarea');
        field.value = '';
        field.id = 'question-' + nextId++;
        card.querySelector('label').htmlFor = field.id;
        questions.appendChild(card);
        renumber();
        field.focus();
    });
    questions.addEventListener('click', event => {
        if (event.target.classList.contains('remove-question') && questions.children.length > 1) {
            event.target.closest('section').remove();
            renumber();
        }
    });
    function renumber() {
        questions.querySelectorAll('.question-number').forEach((label, index) => label.textContent = index + 1);
        questions.querySelectorAll('.remove-question').forEach(button => button.disabled = questions.children.length === 1);
        document.getElementById('add-question').disabled = questions.children.length >= 50;
    }
    renumber();
}
</script>
<?php include '../includes/admin_footer.php'; ?>
