<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
check_mahasiswa();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    check_csrf_token();
    $jenis_tes = strtoupper(trim((string) ($_POST['jenis_tes'] ?? '')));
    $allowedStages = ['PK1', 'PK2', 'PK3', 'BP1', 'BP2', 'BP3'];
    $allowedTopics = ['Cairan Elektrolit', 'Eliminasi Urine', 'Infeksi Nosokomial', 'Komunikasi', 'Memindahkan Pasien', 'Oksigenasi', 'Pemberian Obat', 'Pemeriksaan Diagnostik', 'Pengkajian', 'Transfusi Darah', 'Verbed', 'Mengelola Nyeri dengan Bantuan Obat'];
    $topik = $jenis_tes === 'PK1' ? trim((string) ($_POST['topik'] ?? '')) : null;
    $tanggal_tes = $_POST['tanggal_tes'] ?? '';
    $user_id = $_SESSION['user_id'];

    $date = DateTime::createFromFormat('!Y-m-d', $tanggal_tes);
    $dateValid = $date && $date->format('Y-m-d') === $tanggal_tes && $tanggal_tes >= date('Y-m-d');
    if (!in_array($jenis_tes, $allowedStages, true) || ($jenis_tes === 'PK1' && !in_array($topik, $allowedTopics, true)) || !$dateValid) {
        $msg = 'Jenis ujian, topik, atau tanggal ujian tidak valid.';
    } else {
        try {
            $duplicate = mysqli_prepare($conn, "SELECT id FROM pengajuan WHERE user_id = ? AND jenis_tes = ? AND status IN ('Menunggu', 'Diterima') LIMIT 1");
            mysqli_stmt_bind_param($duplicate, 'is', $user_id, $jenis_tes);
            mysqli_stmt_execute($duplicate);
            $duplicateResult = mysqli_stmt_get_result($duplicate);
            $hasDuplicate = $duplicateResult && mysqli_num_rows($duplicateResult) > 0;
            mysqli_stmt_close($duplicate);
            if ($hasDuplicate) {
                $msg = 'Masih ada pengajuan aktif untuk tahap ujian tersebut.';
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO pengajuan (user_id, jenis_tes, topik, tanggal_tes, status) VALUES (?, ?, ?, ?, 'Menunggu')");
                if (!$stmt) throw new RuntimeException('Database belum siap: ' . mysqli_error($conn));
                mysqli_stmt_bind_param($stmt, "isss", $user_id, $jenis_tes, $topik, $tanggal_tes);
                if (!mysqli_stmt_execute($stmt)) throw new RuntimeException(mysqli_stmt_error($stmt));
                mysqli_stmt_close($stmt);
                header('Location: pengajuan.php?terkirim=1');
                exit;
            }
        } catch (Throwable $e) {
            $msg = 'Pengajuan gagal disimpan. Pastikan database sudah diperbarui, lalu coba lagi.';
            error_log('Pengajuan gagal: ' . $e->getMessage());
        }
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="container mt-5">
    <?php if (isset($_GET['terkirim'])): ?>
        <div class="save-toast" role="status" aria-live="polite" data-auto-dismiss>
            <span class="save-toast-icon" aria-hidden="true">✓</span>
            <span><strong>Pengajuan berhasil dikirim</strong><small>Pengajuan sudah masuk ke asesor. Pantau persetujuannya di halaman Status Ujian.</small></span>
            <button type="button" aria-label="Tutup notifikasi" data-dismiss-toast>×</button>
        </div>
    <?php endif; ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><p class="eyebrow mb-1">Langkah pertama</p><h2 class="mb-0">Ajukan Ujian</h2><p class="text-muted mt-2 mb-0">Pilih tahap, topik, dan tanggal. Setelah dikirim, tunggu persetujuan asesor.</p></div>
    </div>
    <?php if($msg): ?><div class="alert alert-info"><?= $msg ?></div><?php endif; ?>
    <form method="POST" class="card p-4 shadow-sm" id="request-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="mb-3">
            <label>Jenis Tes</label>
            <select name="jenis_tes" id="jenis_tes" class="form-select" required onchange="toggleTopik(this.value)">
                <option value="PK1">PK 1</option>
                <option value="PK2">PK 2</option>
                <option value="PK3">PK 3</option>
                <option value="BP1">BP 1</option>
                <option value="BP2">BP 2</option>
                <option value="BP3">BP 3</option>
            </select>
        </div>
        <div class="mb-3" id="topik_wrapper">
            <label>Topik PK 1</label>
            <select name="topik" class="form-select">
                <option value="Cairan Elektrolit">Cairan Elektrolit</option>
                <option value="Eliminasi Urine">Eliminasi Urine</option>
                <option value="Infeksi Nosokomial">Infeksi Nosokomial</option>
                <option value="Komunikasi">Komunikasi</option>
                <option value="Memindahkan Pasien">Memindahkan Pasien</option>
                <option value="Oksigenasi">Oksigenasi</option>
                <option value="Pemberian Obat">Pemberian Obat</option>
                <option value="Pemeriksaan Diagnostik">Pemeriksaan Diagnostik</option>
                <option value="Pengkajian">Pengkajian</option>
                <option value="Transfusi Darah">Transfusi Darah</option>
                <option value="Verbed">Verbed</option>
                <option value="Mengelola Nyeri dengan Bantuan Obat">Mengelola Nyeri dengan Bantuan Obat</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="tanggal_tes">Tanggal ingin diuji</label>
            <input type="date" name="tanggal_tes" id="tanggal_tes" class="form-control" min="<?= date('Y-m-d') ?>" required>
            <small class="text-muted">Pilih tanggal pelaksanaan ujian yang Anda inginkan.</small>
        </div>
        <button type="submit" class="btn btn-primary">Kirim Pengajuan ke Asesor</button>
    </form>
</div>
<script>
function toggleTopik(val) {
    const wrapper = document.getElementById('topik_wrapper');
    const select = wrapper.querySelector('select');
    const enabled = val === 'PK1';
    wrapper.style.display = enabled ? 'block' : 'none';
    select.disabled = !enabled;
}
toggleTopik(document.getElementById('jenis_tes').value);
document.getElementById('request-form').addEventListener('submit', event => {
    if (!window.confirm('Kirim pengajuan ujian ini kepada asesor?')) {
        event.preventDefault();
        return;
    }
    const button = event.currentTarget.querySelector('button[type="submit"]');
    button.disabled = true;
    button.textContent = 'Sedang mengirim...';
});
document.querySelectorAll('[data-dismiss-toast]').forEach(button => button.addEventListener('click', () => button.closest('.save-toast')?.remove()));
const successToast = document.querySelector('[data-auto-dismiss]');
if (successToast) window.setTimeout(() => successToast.classList.add('is-hiding'), 6000);
</script>

<?php include '../includes/footer.php'; ?>
