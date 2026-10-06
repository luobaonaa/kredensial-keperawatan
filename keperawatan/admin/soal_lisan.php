<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/form_ujian.php';
check_penguji(); // Memastikan hanya penguji yang dapat mengakses

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_soal'])) {
    check_csrf_token();
    $jenis_tes = strtoupper(trim((string) ($_POST['jenis_tes'] ?? '')));
    $soal = trim((string) ($_POST['soal'] ?? ''));

    if (!in_array($jenis_tes, ['PK1', 'PK2', 'PK3', 'BP1', 'BP2', 'BP3'], true) || $soal === '' || strlen($soal) > 10000) {
        $msg = 'Tahap atau pertanyaan tidak valid.';
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO soal_lisan (jenis_tes, soal) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $jenis_tes, $soal);
        mysqli_stmt_execute($stmt);
        $msg = "Soal lisan berhasil ditambahkan!";
        mysqli_stmt_close($stmt);
    }
}

$result = mysqli_query($conn, "SELECT * FROM soal_lisan ORDER BY id DESC");
?>
<?php include '../includes/admin_header.php'; ?>
<div class="container">
    <h2>Bank Soal Ujian Lisan (Kerahasiaan Penguji)</h2>
    <?php if($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

    <div class="card p-4 mb-4 shadow-sm">
        <h4>Tambah Soal Lisan</h4>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="mb-3">
                <label>Tahap Ujian</label>
                <select name="jenis_tes" class="form-select" required>
                    <option value="PK1">PK 1</option>
                    <option value="PK2">PK 2</option>
                    <option value="PK3">PK 3</option>
                    <option value="BP1">BP 1</option>
                    <option value="BP2">BP 2</option>
                    <option value="BP3">BP 3</option>
                </select>
            </div>
            <div class="mb-3">
                <label>Pertanyaan Lisan</label>
                <textarea name="soal" class="form-control" rows="3" required></textarea>
            </div>
            <button type="submit" name="tambah_soal" class="btn btn-dark">Simpan Soal Lisan</button>
        </form>
    </div>

    <h4>Daftar Soal Lisan Tersimpan</h4>
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Tahap</th>
                <th>Pertanyaan</th>
                <th>Tanggal Dibuat</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= $row['jenis_tes'] ?></td>
                <td><?= htmlspecialchars($row['soal']) ?></td>
                <td><?= $row['created_at'] ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/admin_footer.php'; ?>