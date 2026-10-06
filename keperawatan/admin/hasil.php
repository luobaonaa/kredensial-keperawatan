<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/form_ujian.php';
require_once '../includes/banding.php';
require_once '../includes/workflow.php';
check_penguji();
ensure_banding_table($conn);
ensure_workflow_tables($conn);

$assessorNames = [
    'Ns. Retno Susanti, S.Kep, M.Kep',
    'Ns. Tri Handayani, S. Kep',
    'Ns. Hardono, S.Kep, M.Kep',
    'Tri Asih, SST',
];
$answerKeyPath = __DIR__ . '/../database/answer_keys.json';
$answerKeyBank = is_file($answerKeyPath) ? (json_decode((string) file_get_contents($answerKeyPath), true) ?: []) : [];
$error = '';
$search = trim((string) ($_GET['q'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$searchLike = '%' . $search . '%';

function score_normalize($value): string
{
    $text = is_array($value) ? implode(' ', array_filter($value, 'is_scalar')) : (string) $value;
    $text = strtolower(trim($text));
    return trim((string) preg_replace('/[^\pL\pN%]+/u', ' ', $text));
}

function score_answer($answer, array $key): array
{
    $text = score_normalize($answer);
    $expected = score_normalize($key['answer'] ?? '');
    $keywords = array_values(array_filter(array_map('score_normalize', $key['keywords'] ?? [])));
    $stopWords = ['yang', 'dan', 'dengan', 'untuk', 'pada', 'dalam', 'adalah', 'atau', 'pasien', 'perawat', 'dilakukan', 'setelah', 'sebelum'];
    $matched = array_values(array_filter($keywords, function ($phrase) use ($text, $stopWords) {
        if ($phrase === '') return false;
        if (str_contains($text, $phrase)) return true;
        $tokens = array_values(array_unique(array_filter(explode(' ', $phrase), fn($token) => strlen($token) > 2 && !in_array($token, $stopWords, true))));
        if (!$tokens) return false;
        $hits = count(array_filter($tokens, fn($token) => str_contains(' ' . $text . ' ', ' ' . $token . ' ')));
        $requiredHits = count($tokens) <= 4 ? 1 : (int) ceil(count($tokens) * 0.4);
        return $hits >= max(1, $requiredHits);
    }));
    $minimum = max(1, (int) ($key['min_keywords'] ?? count($keywords) ?: 1));
    $score = $keywords ? min(100, (int) round(count($matched) / $minimum * 100)) : 0;
    if ($expected !== '') $score = str_contains($text, $expected) ? 100 : $score;
    if ($text === '') $score = 0;
    return [
        'score' => $score,
        'matched' => $matched,
        'status' => $score >= 70 ? 'Sesuai' : ($score > 0 ? 'Sebagian sesuai' : 'Belum sesuai'),
    ];
}

function build_review(array $row, array $bank): array
{
    $payload = json_decode($row['jawaban'] ?? '{}', true) ?: [];
    $answers = $payload['jawaban_tulis'] ?? (array_is_list($payload) ? $payload : []);
    $questions = json_decode($row['pertanyaan'] ?? '[]', true) ?: [];
    $topicName = trim((string) ($row['topik'] ?? ''));
    $topicKeys = $bank[$row['jenis_tes']][$topicName] ?? $bank[$row['jenis_tes']]['Umum'] ?? [];
    $items = [];
    $weightedScore = 0;
    $totalWeight = 0;
    foreach ($questions as $index => $question) {
        $key = $topicKeys['questions'][(string) $index] ?? [];
        $answer = $answers[$index] ?? '';
        $review = $key ? score_answer($answer, $key) : null;
        $weight = max(1, (int) ($key['weight'] ?? 1));
        if ($review) {
            $weightedScore += $review['score'] * $weight;
            $totalWeight += $weight;
        }
        $items[] = [
            'question' => is_array($question) ? ($question['text'] ?? '') : (string) $question,
            'answer' => is_array($answer) ? implode(', ', array_filter($answer, 'is_scalar')) : (string) $answer,
            'key' => $key,
            'review' => $review,
        ];
    }
    $score = $totalWeight ? (int) round($weightedScore / $totalWeight) : null;
    return [
        'items' => $items,
        'score' => $score,
        'reviewed' => $totalWeight,
        'key_ready' => ($topicKeys['status'] ?? '') === 'siap_dinilai',
        'recommendation' => $score === null ? 'Perlu penilaian manual' : ($score >= 75 ? 'Kompeten' : 'Belum Kompeten'),
    ];
}

function build_forms_04_10(string $assessor, int $score, string $recommendation, string $notes, ?string $savedAt = null, string $appealStatus = 'Tidak diajukan', string $appealSummary = ''): array
{
    $completedAt = $savedAt ?: date('c');
    return [
        'generated_at' => $completedAt,
        'items' => [
            ['code' => 'FORM-04', 'title' => 'Persetujuan Asesmen', 'status' => 'Tercatat otomatis', 'summary' => 'Persetujuan mengikuti pengajuan dan formulir asesmen yang telah dikirim asesi.'],
            ['code' => 'FORM-05', 'title' => 'Konsultasi Pra-Asesmen', 'status' => 'Tercatat otomatis', 'summary' => 'Data pra-asesmen mengikuti jenis, topik, dan jadwal pengajuan yang telah disetujui.'],
            ['code' => 'FORM-06', 'title' => 'Pelaksanaan Asesmen', 'status' => 'Tercatat otomatis', 'summary' => 'Pelaksanaan tercatat dari jawaban asesi dan pemeriksaan otomatis pada sistem.'],
            ['code' => 'FORM-07', 'title' => 'Keputusan dan Rekomendasi', 'status' => $recommendation, 'summary' => 'Nilai akhir ' . $score . '/100 oleh ' . $assessor . '.'],
            ['code' => 'FORM-08', 'title' => 'Umpan Balik Asesmen', 'status' => $notes !== '' ? 'Tercatat' : 'Tidak ada catatan tambahan', 'summary' => $notes !== '' ? $notes : 'Hasil dan rekomendasi akhir tersedia untuk asesi.'],
            ['code' => 'FORM-09', 'title' => 'Kaji Ulang Pelaksanaan', 'status' => 'Selesai otomatis', 'summary' => 'Kelengkapan jawaban, nilai, dan keputusan akhir telah diperiksa sistem.'],
            ['code' => 'FORM-10', 'title' => 'Banding Asesmen', 'status' => $appealStatus, 'summary' => $appealSummary !== '' ? $appealSummary : 'Asesi belum mengajukan banding.'],
        ],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf_token();
    if (($_POST['action'] ?? '') === 'resolve_appeal') {
        $appealId = (int) ($_POST['appeal_id'] ?? 0);
        $appealStatus = trim((string) ($_POST['appeal_status'] ?? ''));
        $response = trim((string) ($_POST['tanggapan'] ?? ''));
        if ($appealId < 1 || !in_array($appealStatus, ['Diterima', 'Ditolak'], true) || mb_strlen($response) < 10 || mb_strlen($response) > 2000) {
            $error = 'Pilih keputusan banding dan isi tanggapan minimal 10 karakter.';
        } else {
            $appealStmt = form_query($conn, 'SELECT id, user_id, hasil_id FROM banding_asesmen WHERE id = ?', 'i', [$appealId]);
            $appealTarget = mysqli_fetch_assoc($appealStmt);
            if (!$appealTarget) {
                $error = 'Data banding tidak ditemukan.';
            } else {
                $pengujiId = (int) $_SESSION['user_id'];
                form_query($conn, 'UPDATE banding_asesmen SET status = ?, tanggapan = ?, penguji_id = ?, tanggal_diproses = NOW() WHERE id = ?', 'ssii', [$appealStatus, $response, $pengujiId, $appealId]);
                workflow_log($conn, $pengujiId, (int) $appealTarget['user_id'], (int) $appealTarget['hasil_id'], null, 'banding_diproses', 'Asesor memproses banding dengan keputusan ' . $appealStatus . '.');
                workflow_notify($conn, (int) $appealTarget['user_id'], 'banding_diproses', 'Banding sudah diproses', 'Banding Anda telah ' . strtolower($appealStatus) . ' oleh asesor.', 'status.php');
                header('Location: hasil.php?banding=tersimpan&status=' . urlencode($statusFilter) . '&q=' . urlencode($search));
                exit;
            }
        }
    } else {
    $hasilId = (int) ($_POST['hasil_id'] ?? 0);
    $status = 'Selesai';
    $assessor = trim((string) ($_POST['assessor'] ?? ''));
    $score = filter_var($_POST['final_score'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
    $recommendation = trim((string) ($_POST['recommendation'] ?? ''));
    $notes = trim((string) ($_POST['catatan'] ?? ''));
    $itemScores = is_array($_POST['item_score'] ?? null) ? $_POST['item_score'] : [];
    $itemFeedback = is_array($_POST['item_feedback'] ?? null) ? $_POST['item_feedback'] : [];
    $manualItems = [];
    $itemsValid = true;
    foreach ($itemScores as $index => $itemScore) {
        $validatedScore = filter_var($itemScore, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
        $feedback = trim((string) ($itemFeedback[$index] ?? ''));
        if ($validatedScore === false || mb_strlen($feedback) > 2000) { $itemsValid = false; break; }
        $manualItems[(string) $index] = ['score' => $validatedScore, 'feedback' => $feedback];
    }
    if ($hasilId < 1 || !in_array($assessor, $assessorNames, true) || $score === false || !$itemsValid || !in_array($recommendation, ['Kompeten', 'Belum Kompeten', 'Penilaian Lanjutan'], true) || strlen($notes) > 10000) {
        $error = 'Periksa nama asesor, nilai, rekomendasi, dan catatan sebelum menyimpan.';
    } else {
        $target = mysqli_fetch_assoc(form_query($conn, 'SELECT h.id, h.user_id, b.status AS status_banding, b.tanggapan FROM hasil_tes h LEFT JOIN banding_asesmen b ON b.hasil_id = h.id WHERE h.id = ?', 'i', [$hasilId]));
        if (!$target) {
            $error = 'Data hasil tidak ditemukan.';
        } else {
            $savedAt = date('c');
            $assessment = json_encode([
                'final' => [
                    'assessor' => $assessor,
                    'score' => $score,
                    'recommendation' => $recommendation,
                    'saved_at' => $savedAt,
                ],
                'items' => $manualItems,
                'forms_04_10' => build_forms_04_10($assessor, $score, $recommendation, $notes, $savedAt, $target['status_banding'] ?? 'Tidak diajukan', $target['tanggapan'] ?? ''),
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            form_query($conn, "UPDATE hasil_tes SET status = ?, catatan = ?, penilaian = ?, tanggal_selesai = CASE WHEN ? = 'Selesai' THEN NOW() ELSE NULL END WHERE id = ?", 'ssssi', [$status, $notes, $assessment, $status, $hasilId]);
            workflow_log($conn, (int) $_SESSION['user_id'], (int) $target['user_id'], $hasilId, null, 'penilaian_selesai', 'Asesor menyimpan penilaian akhir.', ['score' => $score, 'recommendation' => $recommendation]);
            workflow_notify($conn, (int) $target['user_id'], 'hasil_selesai', 'Hasil asesmen sudah tersedia', 'Nilai akhir dan catatan asesor sudah dapat dilihat.', 'status.php');
            header('Location: hasil.php?tersimpan=1&aksi=selesai&hasil=' . $hasilId . '&status=' . urlencode($statusFilter) . '&q=' . urlencode($search));
            exit;
        }
    }
    }
}

$result = form_query($conn, "SELECT h.*, u.username AS nama_perawat, f.judul, f.pertanyaan, p.topik, j.jawaban, j.created_at AS dikirim, b.id AS banding_id, b.status AS status_banding, b.alasan AS alasan_banding, b.tanggapan AS tanggapan_banding, b.tanggal_pengajuan AS tanggal_banding FROM hasil_tes h JOIN users u ON u.id = h.user_id LEFT JOIN jawaban_form j ON j.hasil_id = h.id LEFT JOIN form_ujian f ON f.id = j.form_id LEFT JOIN pengajuan p ON p.id = f.pengajuan_id LEFT JOIN banding_asesmen b ON b.hasil_id = h.id WHERE (? = '' OR u.username LIKE ? OR h.jenis_tes LIKE ? OR p.topik LIKE ? OR h.status LIKE ?) AND (? = '' OR h.status = ?) ORDER BY CASE WHEN b.status = 'Menunggu' THEN 0 WHEN h.status = 'Belum Selesai' THEN 1 ELSE 2 END, h.id DESC", 'sssssss', [$search, $searchLike, $searchLike, $searchLike, $searchLike, $statusFilter, $statusFilter]);
$summaryResult = mysqli_query($conn, 'SELECT h.status, COUNT(DISTINCT h.id) AS total FROM hasil_tes h JOIN users u ON u.id = h.user_id JOIN jawaban_form j ON j.hasil_id = h.id GROUP BY h.status');
$summary = ['Belum Selesai' => 0, 'Selesai' => 0];
while ($summaryRow = mysqli_fetch_assoc($summaryResult)) $summary[$summaryRow['status']] = (int) $summaryRow['total'];
include '../includes/admin_header.php';
?>
<main class="container form-page assessor-page">
    <?php if (isset($_GET['tersimpan'])): ?>
        <div class="save-toast" role="status" aria-live="polite" data-auto-dismiss>
            <span class="save-toast-icon" aria-hidden="true">✓</span>
            <span><strong><?= ($_GET['aksi'] ?? '') === 'selesai' ? 'Penilaian selesai dan tersimpan' : 'Draft penilaian tersimpan' ?></strong><small><?= ($_GET['aksi'] ?? '') === 'selesai' ? 'Status asesi sudah berubah menjadi Selesai.' : 'Anda dapat melanjutkan penilaian ini kapan saja.' ?></small></span>
            <button type="button" aria-label="Tutup notifikasi" data-dismiss-toast>×</button>
        </div>
    <?php endif; ?>
    <?php if (($_GET['banding'] ?? '') === 'tersimpan'): ?>
        <div class="save-toast" role="status" aria-live="polite" data-auto-dismiss><span class="save-toast-icon">✓</span><span><strong>Banding sudah diproses</strong><small>Keputusan dan tanggapan langsung terlihat oleh asesi.</small></span><button type="button" aria-label="Tutup notifikasi" data-dismiss-toast>×</button></div>
    <?php endif; ?>
    <section class="card form-title-card">
        <p class="eyebrow">Ruang kerja penguji</p>
        <h1>Penilaian Asesi</h1>
        <p>Pilih asesi yang berstatus <strong>Belum Selesai</strong>, periksa nilai otomatis, lalu simpan keputusan akhir.</p>
        <div class="quick-steps" aria-label="Cara memberi penilaian">
            <div><b>1</b><span><strong>Buka jawaban asesi</strong><small>Klik nama asesi pada daftar di bawah.</small></span></div>
            <div><b>2</b><span><strong>Periksa nilai otomatis</strong><small>Baca jawaban dan kunci, lalu koreksi nilai bila perlu.</small></span></div>
            <div><b>3</b><span><strong>Simpan keputusan</strong><small>Klik “Simpan & Selesaikan Penilaian” jika sudah final.</small></span></div>
        </div>
        <form method="get" class="d-flex gap-2 flex-wrap mt-3">
            <input class="form-control flex-grow-1" name="q" value="<?= form_escape($search) ?>" placeholder="Cari nama user atau topik...">
            <select class="form-select" name="status" style="max-width:190px"><option value="">Semua status</option><option value="Belum Selesai" <?= $statusFilter === 'Belum Selesai' ? 'selected' : '' ?>>Belum selesai</option><option value="Selesai" <?= $statusFilter === 'Selesai' ? 'selected' : '' ?>>Selesai</option></select>
            <button class="btn btn-primary">Cari</button>
        </form>
    </section>
    <div class="row g-3 mb-4">
        <div class="col-md-6"><section class="card question-card py-3"><span class="text-muted">Perlu dinilai</span><strong class="fs-3 text-warning"><?= $summary['Belum Selesai'] ?></strong></section></div>
        <div class="col-md-6"><section class="card question-card py-3"><span class="text-muted">Selesai</span><strong class="fs-3 text-success"><?= $summary['Selesai'] ?></strong></section></div>
    </div>
    <?php if ($error): ?><div class="alert alert-danger"><?= form_escape($error) ?></div><?php endif; ?>
    <?php if (!mysqli_num_rows($result)): ?><section class="card question-card">Tidak ada hasil yang sesuai pencarian.</section><?php endif; ?>
    <?php while ($row = mysqli_fetch_assoc($result)): $review = build_review($row, $answerKeyBank); $saved = json_decode($row['penilaian'] ?? '{}', true) ?: []; $savedFinal = $saved['final'] ?? []; $finalScore = isset($savedFinal['score']) ? (int) $savedFinal['score'] : ($review['score'] ?? 0); $finalRecommendation = $savedFinal['recommendation'] ?? $review['recommendation']; $selectedAssessor = $savedFinal['assessor'] ?? $assessorNames[0]; $forms0410 = build_forms_04_10($selectedAssessor, $finalScore, $finalRecommendation, (string) ($row['catatan'] ?? ''), $savedFinal['saved_at'] ?? null, $row['status_banding'] ?? 'Tidak diajukan', $row['tanggapan_banding'] ?? ($row['status_banding'] === 'Menunggu' ? 'Menunggu pemeriksaan asesor.' : '')); ?>
    <details class="card question-card mb-3 result-item" <?= $row['status'] !== 'Selesai' || $row['status_banding'] === 'Menunggu' ? 'open' : '' ?>>
        <summary class="result-summary"><span><strong><?= form_escape($row['nama_perawat']) ?></strong><small class="d-block text-muted"><?= form_escape($row['jenis_tes']) ?> · <?= form_escape($row['topik'] ?? 'Umum') ?> · <?= form_escape($row['dikirim'] ?? 'Belum dikirim') ?></small></span><span class="result-badges"><?php if ($row['status_banding'] === 'Menunggu'): ?><span class="badge bg-danger">Banding Baru</span><?php endif; ?><span class="badge bg-<?= $row['status'] === 'Selesai' ? 'success' : 'warning' ?>"><?= form_escape($row['status']) ?></span></span></summary>
        <div class="result-body">
            <div class="score-overview">
                <div><span>Nilai otomatis</span><strong><?= $review['score'] === null ? '—' : $review['score'] ?></strong></div>
                <div><span>Rekomendasi</span><strong><?= form_escape($review['recommendation']) ?></strong></div>
                <div><span>Kunci</span><strong><?= $review['key_ready'] ? 'Siap' : 'Belum lengkap' ?></strong></div>
            </div>
            <?php if ($row['banding_id']): ?>
            <section class="admin-appeal-panel <?= $row['status_banding'] === 'Menunggu' ? 'is-pending' : '' ?>">
                <div class="appeal-heading"><div><strong>FORM-10 · Banding dari asesi</strong><small>Dikirim <?= form_escape($row['tanggal_banding']) ?></small></div><span class="status-pill status-<?= strtolower($row['status_banding']) ?>"><?= form_escape($row['status_banding']) ?></span></div>
                <p><strong>Alasan:</strong> <?= nl2br(form_escape($row['alasan_banding'])) ?></p>
                <?php if ($row['status_banding'] === 'Menunggu'): ?>
                <form method="post" class="appeal-resolution-form">
                    <input type="hidden" name="csrf_token" value="<?= form_escape(csrf_token()) ?>">
                    <input type="hidden" name="action" value="resolve_appeal">
                    <input type="hidden" name="appeal_id" value="<?= (int) $row['banding_id'] ?>">
                    <div class="row g-2"><div class="col-md-4"><label class="form-label">Keputusan banding</label><select class="form-select" name="appeal_status" required><option value="">Pilih keputusan</option><option value="Diterima">Diterima untuk ditinjau</option><option value="Ditolak">Ditolak</option></select></div><div class="col-md-8"><label class="form-label">Tanggapan untuk asesi</label><textarea class="form-control" name="tanggapan" rows="2" minlength="10" maxlength="2000" required placeholder="Jelaskan keputusan secara singkat dan jelas."></textarea></div></div>
                    <button class="btn btn-primary mt-2" type="submit">Simpan Keputusan Banding</button>
                </form>
                <?php else: ?><p class="appeal-response"><strong>Tanggapan:</strong> <?= nl2br(form_escape($row['tanggapan_banding'] ?: 'Tidak ada tanggapan.')) ?></p><?php endif; ?>
            </section>
            <?php endif; ?>
            <h2 class="h4 mt-4"><?= form_escape($row['judul'] ?? 'Jawaban user') ?></h2>
            <form method="post" class="assessment-submit-form">
                <input type="hidden" name="csrf_token" value="<?= form_escape(csrf_token()) ?>">
                <input type="hidden" name="hasil_id" value="<?= (int) $row['id'] ?>">
            <div class="answer-review-list">
            <?php foreach ($review['items'] as $index => $item): ?>
                <article class="response-item compact-review">
                    <h3 class="question-label"><?= ($index + 1) ?>. <?= form_escape($item['question']) ?></h3>
                    <p><strong>Jawaban user:</strong> <?= form_escape($item['answer'] !== '' ? $item['answer'] : '(kosong)') ?></p>
                    <?php if ($item['review']): ?>
                        <div class="ai-answer-review"><strong><?= form_escape($item['review']['status']) ?></strong> · <?= (int) $item['review']['score'] ?>/100
                        <?php if (!empty($item['key']['answer'])): ?><details><summary>Lihat indikator &amp; kunci jawaban</summary><p><?= nl2br(form_escape($item['key']['indicator'] ?? $item['key']['answer'])) ?></p></details><?php endif; ?></div>
                    <?php else: ?><div class="ai-answer-review">Kunci untuk soal ini belum tersedia. Nilai dapat dikoreksi manual di bawah.</div><?php endif; ?>
                    <?php $savedItem = $saved['items'][(string) $index] ?? []; $itemScore = isset($savedItem['score']) ? (int) $savedItem['score'] : (int) ($item['review']['score'] ?? 0); ?>
                    <div class="manual-item-review"><label>Skor asesor<input class="form-control item-score-input" type="number" name="item_score[<?= $index ?>]" min="0" max="100" value="<?= $itemScore ?>" required></label><label>Komentar untuk jawaban ini<textarea class="form-control" name="item_feedback[<?= $index ?>]" rows="2" maxlength="2000" placeholder="Opsional: koreksi atau saran singkat."><?= form_escape($savedItem['feedback'] ?? '') ?></textarea></label></div>
                </article>
            <?php endforeach; ?>
            </div>
            <div class="final-assessment-form">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Nama asesi</label><input class="form-control" value="<?= form_escape($row['nama_perawat']) ?>" readonly></div>
                    <div class="col-md-6"><label class="form-label">Nama asesor</label><select class="form-select" name="assessor" required><?php foreach ($assessorNames as $name): ?><option value="<?= form_escape($name) ?>" <?= $selectedAssessor === $name ? 'selected' : '' ?>><?= form_escape($name) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-4"><label class="form-label">Nilai akhir (0–100)</label><input class="form-control" type="number" name="final_score" min="0" max="100" value="<?= $finalScore ?>" required></div>
                    <div class="col-md-8"><label class="form-label">Keputusan</label><select class="form-select" name="recommendation"><option <?= $finalRecommendation === 'Kompeten' ? 'selected' : '' ?>>Kompeten</option><option <?= $finalRecommendation === 'Belum Kompeten' ? 'selected' : '' ?>>Belum Kompeten</option><option <?= $finalRecommendation === 'Penilaian Lanjutan' ? 'selected' : '' ?>>Penilaian Lanjutan</option></select></div>
                    <div class="col-12"><label class="form-label">Catatan penguji</label><textarea class="form-control" name="catatan" rows="3" maxlength="10000" placeholder="Opsional: tulis catatan akhir atau bagian yang perlu diperbaiki."><?= form_escape($row['catatan'] ?? '') ?></textarea></div>
                </div>
                <p class="save-help">Setelah disimpan, nilai masuk ke hasil asesi dan status otomatis berubah menjadi <strong>Selesai</strong>.</p>
                <div class="assessment-save-actions"><button class="btn btn-primary" type="submit" name="save_action" value="complete">Simpan Penilaian</button></div>
            </div>
            </form>
            <details class="forms-admin-summary" <?= $row['status'] === 'Selesai' ? 'open' : '' ?>>
                <summary><span><strong>FORM 04–10</strong><small>Terisi otomatis untuk <?= form_escape($row['jenis_tes']) ?> · tidak perlu diketik ulang</small></span><span class="forms-ready-badge"><?= $row['status'] === 'Selesai' ? 'Lengkap' : 'Akan dilengkapi saat disimpan' ?></span></summary>
                <div class="forms-admin-list">
                    <?php foreach (($forms0410['items'] ?? []) as $formItem): ?>
                    <article>
                        <span class="form-code"><?= form_escape($formItem['code'] ?? '') ?></span>
                        <div><strong><?= form_escape($formItem['title'] ?? '') ?></strong><small><?= form_escape($formItem['summary'] ?? '') ?></small></div>
                        <span class="form-status"><?= form_escape($formItem['status'] ?? '') ?></span>
                    </article>
                    <?php endforeach; ?>
                </div>
            </details>
            <?php $activityResult = form_query($conn, 'SELECT description, created_at FROM aktivitas_asesmen WHERE hasil_id = ? ORDER BY id DESC LIMIT 12', 'i', [(int) $row['id']]); if (mysqli_num_rows($activityResult)): ?><details class="activity-timeline"><summary>Riwayat aktivitas</summary><?php while ($activity = mysqli_fetch_assoc($activityResult)): ?><div><span></span><p><strong><?= form_escape($activity['description']) ?></strong><small><?= form_escape(date('d/m/Y H:i', strtotime($activity['created_at']))) ?></small></p></div><?php endwhile; ?></details><?php endif; ?>
        </div>
    </details>
    <?php endwhile; ?>
</main>
<script>
document.querySelectorAll('.assessment-submit-form').forEach(form => {
    form.querySelectorAll('.item-score-input').forEach(input => input.addEventListener('input', () => {
        const scores = [...form.querySelectorAll('.item-score-input')].map(field => Number(field.value)).filter(value => Number.isFinite(value));
        const finalField = form.querySelector('[name="final_score"]');
        if (finalField && scores.length) finalField.value = Math.round(scores.reduce((sum, value) => sum + value, 0) / scores.length);
    }));
    form.addEventListener('submit', event => {
        const submitter = event.submitter;
        if (!submitter) return;
        if (!window.confirm('Simpan penilaian ini? Nilai dan keputusan akan disimpan, lalu status asesi berubah menjadi Selesai.')) {
            event.preventDefault();
            return;
        }
        form.querySelectorAll('button[type="submit"]').forEach(button => button.disabled = true);
        submitter.textContent = 'Sedang menyimpan...';
    });
});
document.querySelectorAll('[data-dismiss-toast]').forEach(button => button.addEventListener('click', () => button.closest('.save-toast')?.remove()));
const successToast = document.querySelector('[data-auto-dismiss]');
if (successToast) window.setTimeout(() => successToast.classList.add('is-hiding'), 6000);
</script>
<?php include '../includes/admin_footer.php'; ?>
