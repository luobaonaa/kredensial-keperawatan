<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/tahap_check.php';
require_once '../includes/form_ujian.php';
require_once '../includes/word_template.php';
require_once '../includes/workflow.php';
check_mahasiswa();
ensure_workflow_tables($conn);
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
$templateSet = assessment_template_set($tahap, (string) ($pengajuan['topik'] ?? ''));
$questions = $form ? json_decode($form['pertanyaan'], true) : [];
$questions = array_map(fn($q) => is_array($q) ? $q : ['type' => 'essay', 'text' => $q, 'options' => []], is_array($questions) ? $questions : []);
$response = $form ? mysqli_fetch_assoc(form_query($conn, 'SELECT j.*, h.status, h.catatan FROM jawaban_form j JOIN hasil_tes h ON h.id = j.hasil_id WHERE j.form_id = ?', 'i', [(int) $form['id']])) : null;
$error = '';
$answers = [];
$selfAnswers = [];
$savedPayload = $response ? (json_decode($response['jawaban'], true) ?: []) : [];
if ($response) {
    $answers = $savedPayload['jawaban_tulis'] ?? (array_is_list($savedPayload) ? $savedPayload : []);
    $selfAnswers = $savedPayload['asesmen_mandiri'] ?? [];
}
$draft = $form ? mysqli_fetch_assoc(form_query($conn, 'SELECT payload, updated_at FROM draft_jawaban WHERE user_id = ? AND form_id = ?', 'ii', [$user_id, (int) $form['id']])) : null;
if ($draft && (!$response || ($response['status'] ?? '') !== 'Selesai')) {
    $draftPayload = json_decode($draft['payload'], true) ?: [];
    $answers = $draftPayload['jawaban_tulis'] ?? $answers;
    $selfAnswers = $draftPayload['asesmen_mandiri'] ?? $selfAnswers;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    form_check_token();
    if (!$form || (int) ($_POST['form_id'] ?? 0) !== (int) $form['id']) {
        $error = 'Form telah berubah atau belum tersedia. Muat ulang halaman.';
    } elseif ($response && ($response['status'] ?? '') === 'Selesai') {
        $error = 'Penilaian sudah selesai. Hubungi penguji jika perlu mengajukan revisi.';
    } else {
        $posted = $_POST['jawaban'] ?? [];
        foreach ($questions as $index => $question) {
            $value = is_array($posted) ? ($posted[$index] ?? null) : null;
            $answers[$index] = is_array($value) ? array_map(fn($v) => is_string($v) ? trim($v) : '', $value) : (is_string($value) ? trim($value) : '');
            if ($answers[$index] === '' || $answers[$index] === [] || (is_string($answers[$index]) && strlen($answers[$index]) > 20000)) $error = 'Isi semua jawaban sebelum mengirim form.';
        }
        $postedSelf = $_POST['mandiri'] ?? [];
        if (!is_array($postedSelf)) $postedSelf = [];
        $expectedSelfItems = 0;
        if ($templateSet) {
            foreach (array_slice(word_form_rows($templateSet['self'], 2), 2) as $sourceRow) {
                $sourceCells = word_unique_row($sourceRow);
                if ($sourceCells && count($sourceCells) !== 1 && !str_starts_with($sourceCells[0], 'Elemen Kompetensi')) $expectedSelfItems++;
            }
        }
        for ($index = 0; $index < $expectedSelfItems; $index++) {
            $item = $postedSelf[$index] ?? [];
            $nilai = is_array($item) ? ($item['nilai'] ?? '') : '';
            if (!in_array($nilai, ['K', 'BK'], true)) $error = 'Isi seluruh penilaian mandiri K atau BK.';
            $selfAnswers[$index] = ['nilai' => $nilai];
        }
        if ($templateSet && count($selfAnswers) !== $expectedSelfItems) $error = 'Isi asesmen mandiri sebelum mengirim form.';
        if (!$error) {
            mysqli_begin_transaction($conn);
            try {
                $payload = ['asesmen_mandiri' => $selfAnswers, 'jawaban_tulis' => $answers];
                $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                if ($response) {
                    $hasil_id = (int) $response['hasil_id'];
                    form_query($conn, 'UPDATE jawaban_form SET jawaban = ?, created_at = NOW() WHERE id = ?', 'si', [$payloadJson, (int) $response['id']]);
                    form_query($conn, "UPDATE hasil_tes SET status = 'Belum Selesai', tanggal_selesai = NULL WHERE id = ?", 'i', [(int) $response['hasil_id']]);
                } else {
                    form_query($conn, "INSERT INTO hasil_tes (user_id, jenis_tes, file_name, file_path, status, penguji_id) VALUES (?, ?, '', '', 'Belum Selesai', ?)", 'isi', [$user_id, $tahap, (int) $pengajuan['penguji_id']]);
                    $hasil_id = mysqli_insert_id($conn);
                    form_query($conn, 'INSERT INTO jawaban_form (form_id, hasil_id, jawaban) VALUES (?, ?, ?)', 'iis', [(int) $form['id'], $hasil_id, $payloadJson]);
                }
                form_query($conn, 'DELETE FROM draft_jawaban WHERE user_id = ? AND form_id = ?', 'ii', [$user_id, (int) $form['id']]);
                workflow_log($conn, $user_id, $user_id, $hasil_id, (int) $pengajuan['id'], 'jawaban_dikirim', 'Asesi mengirim jawaban kepada asesor.', ['jenis_tes' => $tahap]);
                workflow_notify($conn, (int) $pengajuan['penguji_id'], 'jawaban_masuk', 'Jawaban asesi masuk', ($_SESSION['username'] ?? 'Asesi') . ' telah mengirim jawaban ' . $tahap . '.', 'hasil.php?status=Belum+Selesai');
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
    <?php if (isset($_GET['terkirim'])): ?>
        <div class="save-toast" role="status" aria-live="polite" data-auto-dismiss>
            <span class="save-toast-icon" aria-hidden="true">✓</span>
            <span><strong>Jawaban berhasil dikirim</strong><small>Terkirim <?= date('d/m/Y H:i') ?> dan sudah dapat dilihat oleh asesor.</small></span>
            <button type="button" aria-label="Tutup notifikasi" data-dismiss-toast>×</button>
        </div>
    <?php endif; ?>
    <section class="card form-title-card">
        <p class="eyebrow">Ujian Keperawatan · <?= form_escape($tahap) ?></p>
        <h1><?= form_escape($form['judul'] ?? 'Form belum tersedia') ?></h1>
        <?php if ($form && $templateSet): ?>
            <p class="answer-text">Form ini terdiri dari asesmen mandiri dan pertanyaan tertulis untuk unit kompetensi <?= form_escape($templateSet['unit_code']) ?> <?= form_escape($templateSet['unit_title']) ?>.</p>
        <?php else: ?>
            <p class="answer-text"><?= form_escape($form['deskripsi'] ?? 'Penguji belum menerbitkan form untuk pengajuan ini.') ?></p>
        <?php endif; ?>
        <div class="form-identity"><span>Perawat <strong><?= form_escape($_SESSION['username']) ?></strong></span><span>Penguji <strong><?= form_escape($pengajuan['nama_penguji'] ?? '-') ?></strong></span></div>
        <?php if ($form && (!$response || ($response['status'] ?? '') !== 'Selesai')): ?>
            <div class="quick-steps" aria-label="Cara mengerjakan">
                <div><b>1</b><span><strong>Isi semua bagian</strong><small>Jawab sesuai kondisi dan pengetahuan Anda.</small></span></div>
                <div><b>2</b><span><strong>Periksa jawaban</strong><small>Pastikan tidak ada jawaban yang kosong.</small></span></div>
                <div><b>3</b><span><strong>Kirim ke asesor</strong><small>Tunggu notifikasi bahwa jawaban berhasil dikirim.</small></span></div>
            </div>
        <?php endif; ?>
        <?php if ($form && !$response): ?><p class="text-danger mt-3 mb-0">* Wajib diisi</p><?php endif; ?>
    </section>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= form_escape($error) ?></div><?php endif; ?>
    <?php if ($response): ?>
        <section class="submission-status <?= ($response['status'] ?? '') === 'Selesai' ? 'is-complete' : '' ?>">
            <span class="submission-status-icon" aria-hidden="true"><?= ($response['status'] ?? '') === 'Selesai' ? '✓' : 'i' ?></span>
            <span><strong><?= ($response['status'] ?? '') === 'Selesai' ? 'Penilaian sudah selesai' : 'Jawaban sudah terkirim' ?></strong><small><?= ($response['status'] ?? '') === 'Selesai' ? 'Form telah dikunci. Hasil dan catatan asesor dapat dilihat di bawah.' : 'Anda masih dapat memperbarui jawaban sampai asesor menyelesaikan penilaian.' ?></small><?php if ($response['catatan']): ?><small><b>Catatan asesor:</b> <?= form_escape($response['catatan']) ?></small><?php endif; ?></span>
        </section>
    <?php endif; ?>
    <?php if ($form && (!$response || ($response['status'] ?? '') !== 'Selesai')): ?>
        <form method="post" class="form-stack" id="exam-form" novalidate>
            <input type="hidden" name="token" value="<?= form_escape(form_token()) ?>">
            <input type="hidden" name="form_id" value="<?= (int) $form['id'] ?>">
            <div class="autosave-status" id="autosave-status" role="status"><span class="autosave-dot"></span><span><?= $draft ? 'Draft dipulihkan · terakhir disimpan ' . form_escape(date('H:i:s', strtotime($draft['updated_at']))) : 'Autosave aktif · perubahan akan disimpan otomatis' ?></span></div>
            <?php if ($templateSet): ?>
                <div class="form-wizard" id="form-wizard">
                <div class="wizard-progress"><span class="wizard-progress-bar" id="wizard-progress-bar"></span></div><p class="wizard-counter" id="wizard-counter">Halaman 1 dari 3</p>
                <div class="wizard-step" data-form-step>
                <section class="card question-card instruction-card">
                    <p class="eyebrow">FORM-02 ASESMEN MANDIRI</p>
                    <h2>Petunjuk</h2>
                    <div class="instruction-list"><?php render_self_instructions($templateSet['self']); ?></div>
                    <?php if (!empty($templateSet['reference'])): ?><details class="reference-details"><summary>Lihat acuan Unit Kompetensi <?= form_escape($templateSet['unit_code']) ?></summary><?php render_word_template($templateSet['reference'], true); ?></details><?php endif; ?>
                </section>
                <div class="wizard-actions justify-content-end"><button type="button" class="btn btn-primary" data-next>Berikutnya</button></div></div>
                <div class="wizard-step" data-form-step hidden>
                <section class="card question-card assessment-section"><h2>FORM- 02 ASESMEN MANDIRI</h2><?php render_self_assessment($templateSet['self'], $selfAnswers); ?></section>
                <div class="wizard-actions"><button type="button" class="btn btn-outline-secondary" data-prev>Kembali</button><button type="button" class="btn btn-primary" data-next>Berikutnya</button></div></div>
                <div class="wizard-step" data-form-step hidden>
                <section class="card question-card section-divider"><h2>FORM- 03 C. Instrumen Pertanyaan Tertulis</h2><h3>LEMBAR SOAL</h3><p>SOAL :</p></section>
            <?php endif; ?>
            <?php foreach ($questions as $index => $question): ?>
                <?php $questionText = $question['text']; preg_match('/^(\d+(?:\.\d+)*)\s+(.*)$/su', $questionText, $questionParts); ?>
                <section class="card question-card"><label class="question-label" for="answer-<?= $index ?>"><?php if ($questionParts): ?><span class="question-code"><?= form_escape($questionParts[1]) ?></span> <?= form_escape($questionParts[2]) ?><?php else: ?><?= form_escape($questionText) ?><?php endif; ?> <span class="text-danger">*</span></label><?php if ($question['type'] === 'essay'): ?><textarea id="answer-<?= $index ?>" name="jawaban[<?= $index ?>]" rows="5" class="form-control answer-input" placeholder="Tulis jawaban Anda" maxlength="20000" required><?= form_escape($answers[$index] ?? '') ?></textarea><?php elseif ($question['type'] === 'checklist'): ?><div class="checklist-table"><table class="table"><thead><tr><th>Kriteria</th><th>Ya</th><th>Tidak</th><th>Catatan</th></tr></thead><tbody><?php foreach ($question['options'] as $rowIndex => $option): $rowAnswer = is_array($answers[$index] ?? null) ? ($answers[$index][$rowIndex] ?? '') : ''; ?><tr><td><?= form_escape($option) ?></td><td><input type="radio" name="jawaban[<?= $index ?>][<?= $rowIndex ?>]" value="<?= form_escape($option) ?>: Ya" <?= $rowAnswer === $option . ': Ya' ? 'checked' : '' ?> required></td><td><input type="radio" name="jawaban[<?= $index ?>][<?= $rowIndex ?>]" value="<?= form_escape($option) ?>: Tidak" <?= $rowAnswer === $option . ': Tidak' ? 'checked' : '' ?>></td><td><input class="form-control form-control-sm" name="jawaban[<?= $index ?>][catatan_<?= $rowIndex ?>]" value="<?= form_escape(is_array($answers[$index] ?? null) ? ($answers[$index]['catatan_' . $rowIndex] ?? '') : '') ?>" placeholder="Catatan"></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="option-list"><?php foreach ($question['options'] as $option): $savedAnswer = $answers[$index] ?? null; $optionChecked = is_array($savedAnswer) ? in_array($option, $savedAnswer, true) : $savedAnswer === $option; ?><label class="option-item"><input type="<?= $question['type'] === 'single' ? 'radio' : 'checkbox' ?>" name="jawaban[<?= $index ?>]<?= $question['type'] === 'multiple' ? '[]' : '' ?>" value="<?= form_escape($option) ?>" <?= $optionChecked ? 'checked' : '' ?> <?= $question['type'] === 'single' ? 'required' : '' ?>> <?= form_escape($option) ?></label><?php endforeach; ?></div><?php endif; ?></section>
            <?php endforeach; ?>
            <div class="form-submit-bar"><span id="answer-progress" aria-live="polite">0 dari <?= count($questions) ?> pertanyaan terisi</span><button type="submit" class="btn btn-primary" id="submit-answers"><?= $response ? 'Kirim perubahan' : 'Kirim jawaban ke asesor' ?></button></div>
            <?php if ($templateSet): ?><div class="wizard-actions"><button type="button" class="btn btn-outline-secondary" data-prev>Kembali</button></div></div></div><?php endif; ?>
        </form>
        <dialog id="submit-review-dialog" class="submit-review-dialog"><form method="dialog"><header><div><p class="eyebrow">Periksa sebelum dikirim</p><h2>Ringkasan Jawaban</h2></div><button value="cancel" class="dialog-close" aria-label="Tutup">×</button></header><p>Pastikan jawaban sudah benar. Setelah dikirim, asesor dapat langsung melihatnya.</p><div id="submit-review-list" class="submit-review-list"></div><footer><button value="cancel" class="btn btn-outline-secondary">Kembali Periksa</button><button value="confirm" class="btn btn-primary" id="confirm-submit">Ya, Kirim ke Asesor</button></footer></form></dialog>
    <?php endif; ?>
</main>
<script>
const examForm = document.getElementById('exam-form');
if (examForm) {
    const answerCards = [...examForm.querySelectorAll('.question-card')].filter(card => card.querySelector('[name^="jawaban["]'));
    const cardIsAnswered = card => {
        const fields = [...card.querySelectorAll('[name^="jawaban["]')];
        if (fields.some(field => field.type === 'radio')) return fields.filter(field => field.required).every(required => card.querySelector(`input[name="${CSS.escape(required.name)}"]:checked`));
        if (fields.some(field => field.type === 'checkbox')) return fields.some(field => field.checked);
        return fields.some(field => field.value.trim());
    };
    const update = () => document.getElementById('answer-progress').textContent = answerCards.filter(cardIsAnswered).length + ' dari ' + answerCards.length + ' pertanyaan terisi';
    examForm.addEventListener('input', update);
    const validateStep = step => {
        step.querySelectorAll('textarea[required], input[required]').forEach(field => {
            if (field.matches('textarea') || field.type === 'text') field.setCustomValidity(field.value.trim() ? '' : 'Jawaban ini wajib diisi.');
        });
        step.querySelectorAll('.invalid-question').forEach(card => card.classList.remove('invalid-question'));
        const invalidFields = [...step.querySelectorAll(':invalid')];
        const invalidCards = [...new Set(invalidFields.map(field => field.closest('.self-assessment-item, .question-card')).filter(Boolean))];
        let alert = step.querySelector('[data-validation-alert]');
        if (!alert) {
            alert = document.createElement('div');
            alert.className = 'alert alert-danger validation-alert';
            alert.setAttribute('data-validation-alert', '');
            step.prepend(alert);
        }
        if (!invalidCards.length) {
            alert.hidden = true;
            return true;
        }
        invalidCards.forEach(card => card.classList.add('invalid-question'));
        const numbers = [...new Set(invalidCards.map(card => card.querySelector('.question-code, .source-number')?.textContent.trim()).filter(Boolean))];
        alert.textContent = numbers.length ? `Masih ada jawaban yang kosong pada nomor: ${numbers.join(', ')}.` : 'Masih ada jawaban yang kosong pada halaman ini.';
        alert.hidden = false;
        alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
    };
    examForm.addEventListener('input', event => {
        const card = event.target.closest('.self-assessment-item, .question-card');
        if (card && [...card.querySelectorAll(':required')].every(field => field.checkValidity())) card.classList.remove('invalid-question');
    });
    examForm.addEventListener('change', event => {
        const card = event.target.closest('.self-assessment-item, .question-card');
        if (card && [...card.querySelectorAll(':required')].every(field => field.checkValidity())) card.classList.remove('invalid-question');
    });
    let finalSubmitConfirmed = false;
    const reviewDialog = document.getElementById('submit-review-dialog');
    const reviewList = document.getElementById('submit-review-list');
    examForm.addEventListener('submit', event => {
        if (finalSubmitConfirmed) {
            const submitButton = examForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Sedang mengirim...';
            return;
        }
        const stepsToCheck = [...examForm.querySelectorAll('[data-form-step]')];
        const invalidStepIndex = stepsToCheck.findIndex(step => !validateStep(step));
        if (invalidStepIndex !== -1) {
            event.preventDefault();
            if (typeof window.showStep === 'function') window.showStep(invalidStepIndex);
            return;
        }
        event.preventDefault();
        reviewList.innerHTML = '';
        answerCards.forEach((card, index) => {
            const label = card.querySelector('.question-label')?.textContent.trim().replace('*', '') || `Pertanyaan ${index + 1}`;
            const selected = [...card.querySelectorAll('[name^="jawaban["]')].filter(field => (['radio','checkbox'].includes(field.type) ? field.checked : field.value.trim())).map(field => field.value.trim()).filter(Boolean);
            const item = document.createElement('article');
            const heading = document.createElement('strong'); heading.textContent = `${index + 1}. ${label}`;
            const answer = document.createElement('p'); answer.textContent = selected.join(' · ') || '(belum diisi)';
            item.append(heading, answer); reviewList.append(item);
        });
        reviewDialog.showModal();
    });
    document.getElementById('confirm-submit').addEventListener('click', () => {
        finalSubmitConfirmed = true;
        reviewDialog.close();
        examForm.requestSubmit();
    });
    update();

    const autosaveStatus = document.getElementById('autosave-status');
    let autosaveTimer;
    let autosaveController;
    const autosave = () => {
        autosaveController?.abort();
        autosaveController = new AbortController();
        autosaveStatus.classList.add('is-saving');
        autosaveStatus.querySelector('span:last-child').textContent = 'Menyimpan draft...';
        fetch('autosave.php', { method: 'POST', body: new FormData(examForm), credentials: 'same-origin', signal: autosaveController.signal })
            .then(response => response.ok ? response.json() : Promise.reject())
            .then(data => { autosaveStatus.classList.remove('is-saving','is-error'); autosaveStatus.querySelector('span:last-child').textContent = `Draft tersimpan pukul ${data.saved_at}`; })
            .catch(error => { if (error.name === 'AbortError') return; autosaveStatus.classList.remove('is-saving'); autosaveStatus.classList.add('is-error'); autosaveStatus.querySelector('span:last-child').textContent = 'Draft belum tersimpan · periksa koneksi'; });
    };
    const scheduleAutosave = () => { clearTimeout(autosaveTimer); autosaveTimer = setTimeout(autosave, 1200); };
    examForm.addEventListener('input', scheduleAutosave);
    examForm.addEventListener('change', scheduleAutosave);

    const wizard = document.getElementById('form-wizard');
    if (wizard) {
        const steps = [...wizard.querySelectorAll('[data-form-step]')];
        let currentStep = 0;
        window.showStep = index => {
            currentStep = Math.max(0, Math.min(index, steps.length - 1));
            steps.forEach((step, stepIndex) => step.hidden = stepIndex !== currentStep);
            document.getElementById('wizard-counter').textContent = `Halaman ${currentStep + 1} dari ${steps.length}`;
            document.getElementById('wizard-progress-bar').style.width = `${((currentStep + 1) / steps.length) * 100}%`;
            window.scrollTo({ top: examForm.offsetTop - 90, behavior: 'smooth' });
        };
        wizard.addEventListener('click', event => {
            if (event.target.closest('[data-prev]')) window.showStep(currentStep - 1);
            if (event.target.closest('[data-next]')) {
                if (validateStep(steps[currentStep])) {
                    if (currentStep >= steps.length - 1) {
                        if (!wizard.querySelector('.wizard-complete')) {
                            const done = document.createElement('div');
                            done.className = 'wizard-complete';
                            done.innerHTML = '<strong>✓ Pengisian form selesai</strong><span>Periksa kembali jawabanmu, lalu klik tombol “Kirim jawaban” untuk menyelesaikan pengumpulan.</span>';
                            wizard.prepend(done);
                        }
                        const button = event.target.closest('[data-next]');
                        button.textContent = 'Selesai';
                        button.disabled = true;
                        wizard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } else window.showStep(currentStep + 1);
                }
            }
        });
        window.showStep(0);
    }
}

document.querySelectorAll('[data-dismiss-toast]').forEach(button => button.addEventListener('click', () => button.closest('.save-toast')?.remove()));
const successToast = document.querySelector('[data-auto-dismiss]');
if (successToast) window.setTimeout(() => successToast.classList.add('is-hiding'), 6000);
</script>
<?php include '../includes/footer.php'; ?>
