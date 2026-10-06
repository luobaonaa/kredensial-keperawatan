<?php
$profile_user = [];
$profile_latest_result = null;
if (isset($conn, $_SESSION['user_id'])) {
    $profile_stmt = mysqli_prepare($conn, 'SELECT nama, gelar, foto_profil FROM users WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($profile_stmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($profile_stmt);
    $profile_user = mysqli_fetch_assoc(mysqli_stmt_get_result($profile_stmt)) ?: [];

    $result_stmt = mysqli_prepare($conn, "SELECT jenis_tes, tanggal_selesai FROM hasil_tes WHERE user_id = ? AND status = 'Selesai' ORDER BY COALESCE(tanggal_selesai, tanggal_upload) DESC, id DESC LIMIT 1");
    mysqli_stmt_bind_param($result_stmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($result_stmt);
    $profile_latest_result = mysqli_fetch_assoc(mysqli_stmt_get_result($result_stmt)) ?: null;
}
$profile_name = trim($profile_user['nama'] ?? '') ?: ($_SESSION['username'] ?? 'Perawat');
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
    <title>Rumah Sakit Cinta Kasih Tzu Chi Cengkareng - Sistem Ujian Keperawatan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">Rumah Sakit Cinta Kasih Tzu Chi Cengkareng</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="pengajuan.php">Pengajuan Tes</a></li>
                <li class="nav-item"><a class="nav-link" href="pk1.php">PK 1</a></li>
                <li class="nav-item"><a class="nav-link" href="pk2.php">PK 2</a></li>
                <li class="nav-item"><a class="nav-link" href="pk3.php">PK 3</a></li>
                <li class="nav-item"><a class="nav-link" href="bp1.php">BP 1</a></li>
                <li class="nav-item"><a class="nav-link" href="bp2.php">BP 2</a></li>
                <li class="nav-item"><a class="nav-link" href="bp3.php">BP 3</a></li>
                <li class="nav-item"><a class="nav-link" href="status.php">Status Ujian</a></li>
                <li class="nav-item"><a class="nav-link notification-link" href="notifikasi.php">Notifikasi<?php if ($notification_count): ?><span><?= min(99, $notification_count) ?></span><?php endif; ?></a></li>
            </ul>
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
                        <?php if (!empty($profile_user['foto_profil'])): ?><img src="../uploads/profil/<?= htmlspecialchars($profile_user['foto_profil']) ?>" alt="Foto <?= htmlspecialchars($profile_name) ?>">
                        <?php else: ?><span><?= htmlspecialchars($profile_initial) ?></span><?php endif; ?>
                    </div>
                    <div><h4><?= htmlspecialchars($profile_name) ?></h4><p><?= htmlspecialchars($profile_title ?: 'Gelar belum diisi') ?></p></div>
                </div>
                <div class="profile-result">
                    <span>Hasil ujian lulus terakhir</span>
                    <?php if ($profile_latest_result): ?><strong><?= htmlspecialchars($profile_latest_result['jenis_tes']) ?></strong><small><?= htmlspecialchars($profile_latest_result['tanggal_selesai'] ? date('d F Y', strtotime($profile_latest_result['tanggal_selesai'])) : '-') ?></small>
                    <?php else: ?><strong>Belum ada</strong><small>Belum ada ujian yang dinyatakan selesai.</small><?php endif; ?>
                </div>
                <a href="profil.php" class="btn btn-primary w-100">Ubah Profil</a>
            </div>
        </div>
    </div>
</div>
