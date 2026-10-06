<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/form_ujian.php';
check_penguji();

$search = trim((string) ($_GET['q'] ?? ''));
$like = '%' . $search . '%';
$rows = form_query($conn, "SELECT u.id, u.username, u.created_at, p.jenis_tes, p.topik, p.tanggal_tes, p.status AS status_pengajuan, p.tanggal_pengajuan, h.status AS status_hasil, h.tanggal_upload, h.tanggal_selesai FROM users u LEFT JOIN pengajuan p ON p.user_id = u.id LEFT JOIN hasil_tes h ON h.user_id = u.id AND h.jenis_tes = p.jenis_tes WHERE u.role IN ('perawat', 'mahasiswa') AND (? = '' OR u.username LIKE ? OR p.jenis_tes LIKE ? OR p.topik LIKE ? OR p.status LIKE ? OR h.status LIKE ?) ORDER BY u.username, p.id DESC, h.id DESC", 'ssssss', [$search, $like, $like, $like, $like, $like]);
include '../includes/admin_header.php';
?>
<main class="container form-page">
    <section class="card form-title-card">
        <p class="eyebrow">Informasi dan histori</p>
        <h1>Data Perawat</h1>
        <p>Lihat identitas perawat, pengajuan ujian, dan riwayat hasilnya.</p>
        <form method="get" class="d-flex gap-2 mt-3">
            <input class="form-control" name="q" value="<?= form_escape($search) ?>" placeholder="Cari nama, tahap, topik, atau status..." aria-label="Cari perawat">
            <button class="btn btn-primary" type="submit">Cari</button>
            <?php if ($search !== ''): ?><a class="btn btn-outline-secondary" href="mahasiswa.php">Reset</a><?php endif; ?>
        </form>
    </section>
    <section class="card question-card mt-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><h2 class="h4 mb-0">Riwayat Perawat</h2><span class="badge bg-primary"><?= mysqli_num_rows($rows) ?> data</span></div>
        <?php if (!mysqli_num_rows($rows)): ?><p class="mb-0 text-muted">Data tidak ditemukan.</p><?php else: ?>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Perawat</th><th>Tahap</th><th>Topik</th><th>Tanggal ujian</th><th>Pengajuan</th><th>Hasil</th><th>Terakhir diperbarui</th></tr></thead><tbody>
        <?php while ($row = mysqli_fetch_assoc($rows)): ?><tr><td><strong><?= form_escape($row['username']) ?></strong><br><small class="text-muted">Terdaftar <?= form_escape(date('d/m/Y', strtotime($row['created_at']))) ?></small></td><td><?= form_escape($row['jenis_tes'] ?? '-') ?></td><td><?= form_escape($row['topik'] ?? 'Umum') ?></td><td><?= !empty($row['tanggal_tes']) ? form_escape(date('d/m/Y', strtotime($row['tanggal_tes']))) : '-' ?></td><td><span class="badge text-bg-<?= $row['status_pengajuan'] === 'Diterima' ? 'success' : ($row['status_pengajuan'] === 'Ditolak' ? 'danger' : 'warning') ?>"><?= form_escape($row['status_pengajuan'] ?? 'Belum mengajukan') ?></span></td><td><?= $row['status_hasil'] ? '<span class="badge text-bg-' . ($row['status_hasil'] === 'Selesai' ? 'success' : 'secondary') . '">' . form_escape($row['status_hasil']) . '</span>' : '-' ?></td><td><?= $row['tanggal_selesai'] ? form_escape(date('d/m/Y H:i', strtotime($row['tanggal_selesai']))) : ($row['tanggal_upload'] ? form_escape(date('d/m/Y H:i', strtotime($row['tanggal_upload']))) : '-') ?></td></tr><?php endwhile; ?>
        </tbody></table></div>
        <?php endif; ?>
    </section>
</main>
<?php include '../includes/admin_footer.php'; ?>