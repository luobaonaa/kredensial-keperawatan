<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/form_ujian.php';
check_penguji();

$pesan = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi'])) {
    check_csrf_token();
    $pengajuan_id = (int) $_POST['pengajuan_id'];
    $status = $_POST['aksi'];
    $penguji_id = (int) ($_POST['penguji_id'] ?? 0);

    if ($status === 'Hapus' && $pengajuan_id > 0) {
        try {
            mysqli_begin_transaction($conn);
            form_query($conn, 'DELETE FROM hasil_tes WHERE id IN (SELECT hasil_id FROM jawaban_form WHERE form_id IN (SELECT id FROM form_ujian WHERE pengajuan_id = ?))', 'i', [$pengajuan_id]);
            form_query($conn, 'DELETE j FROM jawaban_form j JOIN form_ujian f ON f.id = j.form_id WHERE f.pengajuan_id = ?', 'i', [$pengajuan_id]);
            form_query($conn, 'DELETE FROM form_ujian WHERE pengajuan_id = ?', 'i', [$pengajuan_id]);
            form_query($conn, 'DELETE FROM pengajuan WHERE id = ?', 'i', [$pengajuan_id]);
            mysqli_commit($conn);
            $pesan = 'Pengajuan berhasil dihapus.';
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            error_log('Pengajuan gagal dihapus: ' . $e->getMessage());
            $error = 'Pengajuan gagal dihapus. Silakan coba lagi.';
        }
    } elseif (!in_array($status, ['Diterima', 'Ditolak'], true) || $pengajuan_id < 1 || $penguji_id < 1) {
        $error = 'Data pengajuan tidak valid.';
    } elseif (!mysqli_num_rows(form_query($conn, "SELECT id FROM users WHERE id = ? AND role = 'penguji'", 'i', [$penguji_id]))) {
        $error = 'Penguji yang dipilih tidak valid.';
    } elseif ($status === 'Ditolak') {
        $stmt = mysqli_prepare($conn, "UPDATE pengajuan SET status = 'Ditolak', penguji_id = ? WHERE id = ? AND status = 'Menunggu'");
        mysqli_stmt_bind_param($stmt, 'ii', $penguji_id, $pengajuan_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $pesan = 'Pengajuan ditolak.';
    } else {
        try {
            mysqli_begin_transaction($conn);
            $stmt = mysqli_prepare($conn, "SELECT jenis_tes, topik FROM pengajuan WHERE id = ? AND status = 'Menunggu' FOR UPDATE");
            mysqli_stmt_bind_param($stmt, 'i', $pengajuan_id);
            mysqli_stmt_execute($stmt);
            $pengajuan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
            if (!$pengajuan) throw new RuntimeException('Pengajuan tidak ditemukan.');
            $stmt = mysqli_prepare($conn, 'SELECT judul, deskripsi, pertanyaan FROM bank_soal WHERE jenis_tes = ? AND topik = ?');
            $bank = null;
            if (!empty($pengajuan['topik'])) {
                mysqli_stmt_bind_param($stmt, 'ss', $pengajuan['jenis_tes'], $pengajuan['topik']);
                mysqli_stmt_execute($stmt);
                $bank = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            }
            mysqli_stmt_close($stmt);
            if (!$bank && in_array($pengajuan['jenis_tes'], ['PK2', 'PK3'], true)) {
                $genericQuestions = file_get_contents(__DIR__ . '/../database/pk2_questions.json');
                if ($genericQuestions === false || json_decode($genericQuestions, true) === null) throw new RuntimeException('Form umum PK2/PK3 belum tersedia.');
                $bank = [
                    'judul' => 'Form Asesmen ' . $pengajuan['jenis_tes'] . ' Umum',
                    'deskripsi' => 'Lengkapi data asesi, unit kompetensi, metode asesmen, dan keputusan asesor berdasarkan berkas asesmen kompetensi.',
                    'pertanyaan' => $genericQuestions,
                ];
            }
            if (!$bank) throw new RuntimeException('Bank soal untuk ' . $pengajuan['jenis_tes'] . ' - ' . ($pengajuan['topik'] ?? '-') . ' belum tersedia.');
            $stmt = mysqli_prepare($conn, "UPDATE pengajuan SET status = 'Diterima', penguji_id = ?, tanggal_diterima = NOW() WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'ii', $penguji_id, $pengajuan_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $stmt = mysqli_prepare($conn, 'INSERT INTO form_ujian (pengajuan_id, judul, deskripsi, pertanyaan) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE judul = VALUES(judul), deskripsi = VALUES(deskripsi), pertanyaan = VALUES(pertanyaan)');
            mysqli_stmt_bind_param($stmt, 'isss', $pengajuan_id, $bank['judul'], $bank['deskripsi'], $bank['pertanyaan']);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            mysqli_commit($conn);
            $pesan = 'Pengajuan disetujui. Form dari bank soal langsung dipasang ke perawat.';
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            error_log('Pengajuan gagal diproses: ' . $e->getMessage());
            $error = 'Pengajuan gagal diproses. Periksa ketersediaan bank soal lalu coba lagi.';
        }
    }
}

// Ambil semua daftar pengajuan
$query = "SELECT p.*, u.username as nama_mahasiswa FROM pengajuan p JOIN users u ON p.user_id = u.id ORDER BY p.id DESC";
$result = mysqli_query($conn, $query);

// Ambil daftar penguji
$penguji_list = mysqli_query($conn, "SELECT id, username FROM users WHERE role = 'penguji'");
$pengujis = mysqli_fetch_all($penguji_list, MYSQLI_ASSOC);
?>
<?php include '../includes/admin_header.php'; ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Kelola Pengajuan Perawat</h2>
    </div>
    <?php if ($pesan): ?><div class="alert alert-success mt-3"><?= htmlspecialchars($pesan) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <table class="table table-bordered table-striped mt-3">
        <thead class="table-dark">
            <tr>
                <th>Perawat</th>
                <th>Jenis Tes</th>
                <th>Topik</th>
                <th>Tanggal Ujian</th>
                <th>Diajukan Pada</th>
                <th>Status</th>
                <th>Penguji Ditunjuk</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= htmlspecialchars($row['nama_mahasiswa']) ?></td>
                <td><?= $row['jenis_tes'] ?></td>
                <td><?= htmlspecialchars($row['topik'] ?? '-') ?></td>
                <td><?= !empty($row['tanggal_tes']) ? date('d/m/Y', strtotime($row['tanggal_tes'])) : '-' ?></td>
                <td><?= $row['tanggal_pengajuan'] ?></td>
                <td><span class="badge bg-<?= $row['status'] == 'Diterima' ? 'success' : ($row['status'] == 'Ditolak' ? 'danger' : 'warning') ?>"><?= $row['status'] ?></span></td>
                    <td>
                        <select name="penguji_id" form="pengajuan-form-<?= (int) $row['id'] ?>" class="form-select form-select-sm" required>
                            <?php foreach($pengujis as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $row['penguji_id'] == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['username'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <form method="POST" id="pengajuan-form-<?= (int) $row['id'] ?>" class="pengajuan-action-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="pengajuan_id" value="<?= $row['id'] ?>">
                        <button type="submit" name="aksi" value="Diterima" class="btn btn-sm btn-success">Setujui</button>
                        <button type="submit" name="aksi" value="Ditolak" class="btn btn-sm btn-danger">Tolak</button>
                        <button type="submit" name="aksi" value="Hapus" class="btn btn-sm btn-outline-danger" formnovalidate onclick="return confirm('Hapus pengajuan ini beserta form dan hasil terkait?')">Hapus</button>
                        </form>
                    </td>
            </tr>
            <?php endwhile; ?>
            <?php if (!mysqli_num_rows($result)): ?><tr><td colspan="8" class="text-center text-muted py-4">Belum ada pengajuan masuk.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/admin_footer.php'; ?>
