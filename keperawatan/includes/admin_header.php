<?php
$profile_user = [];
if (isset($conn, $_SESSION['user_id'])) {
    $profile_stmt = mysqli_prepare($conn, 'SELECT nama, gelar, foto_profil FROM users WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($profile_stmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($profile_stmt);
    $profile_user = mysqli_fetch_assoc(mysqli_stmt_get_result($profile_stmt)) ?: [];
}
$profile_name = trim($profile_user['nama'] ?? '') ?: ($_SESSION['username'] ?? 'Penguji');
$profile_title = trim($profile_user['gelar'] ?? '');
$profile_initial = strtoupper(substr($profile_name, 0, 1));
$notification_count = 0;
if (isset($conn, $_SESSION['user_id'])) { require_once __DIR__ . '/workflow.php'; $notification_count = workflow_unread_count($conn, (int) $_SESSION['user_id']); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rumah Sakit Cinta Kasih Tzu Chi Cengkareng - Panel Penguji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="dashboard.php">Rumah Sakit Cinta Kasih Tzu Chi Cengkareng</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNavbar">
            <div class="navbar-nav me-auto">
                <a class="nav-link" href="dashboard.php">Home</a>
                <a class="nav-link" href="pengajuan.php">Pengajuan Masuk</a>
                <a class="nav-link" href="hasil.php">Penilaian &amp; Hasil</a>
                <a class="nav-link" href="soal.php">Bank Soal</a>
                <a class="nav-link" href="soal_lisan.php">Soal Lisan</a>
                <a class="nav-link" href="template_docs.php">Pusat Dokumen</a>
                <a class="nav-link" href="mahasiswa.php">Data Perawat</a>
                <a class="nav-link notification-link" href="notifikasi.php">Notifikasi<?php if ($notification_count): ?><span><?= min(99, $notification_count) ?></span><?php endif; ?></a>
            </div>
            <button class="profile-trigger" type="button" data-bs-toggle="modal" data-bs-target="#profileModal" aria-label="Buka profil <?= htmlspecialchars($profile_name) ?>">
                <?php if (!empty($profile_user['foto_profil'])): ?>
                    <img src="../uploads/profil/<?= htmlspecialchars($profile_user['foto_profil']) ?>" alt="Foto <?= htmlspecialchars($profile_name) ?>">
                <?php else: ?>
                    <span><?= htmlspecialchars($profile_initial) ?></span>
                <?php endif; ?>
                <span class="profile-trigger-info">
                    <strong><?= htmlspecialchars($profile_name) ?></strong>
                    <?php if ($profile_title): ?><small><?= htmlspecialchars($profile_title) ?></small><?php endif; ?>
                </span>
            </button>
            <a href="../logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content profile-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="profileModalLabel">Profil Saya</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="profile-summary">
                    <div class="profile-avatar profile-avatar-large">
                        <?php if (!empty($profile_user['foto_profil'])): ?>
                            <img src="../uploads/profil/<?= htmlspecialchars($profile_user['foto_profil']) ?>" alt="Foto <?= htmlspecialchars($profile_name) ?>">
                        <?php else: ?>
                            <span><?= htmlspecialchars($profile_initial) ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h4><?= htmlspecialchars($profile_name) ?></h4>
                        <p><?= htmlspecialchars($profile_title ?: 'Gelar belum diisi') ?></p>
                    </div>
                </div>
                <form method="post" action="profil.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3">
                        <label for="admin_profile_photo" class="form-label">Foto profil</label>
                        <input class="form-control" type="file" id="admin_profile_photo" name="foto_profil" accept="image/jpeg,image/png,image/webp">
                        <small class="text-muted">JPG, PNG, atau WEBP, maksimal 2 MB.</small>
                    </div>
                    <div class="mb-3">
                        <label for="admin_profile_name" class="form-label">Nama</label>
                        <input class="form-control" type="text" id="admin_profile_name" name="nama" value="<?= htmlspecialchars($profile_name) ?>" required maxlength="150">
                    </div>
                    <div class="mb-4">
                        <label for="admin_profile_title" class="form-label">Gelar</label>
                        <input class="form-control" type="text" id="admin_profile_title" name="gelar" value="<?= htmlspecialchars($profile_title) ?>" maxlength="100" placeholder="Contoh: Ns., M.Kep.">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </div>
</div>
