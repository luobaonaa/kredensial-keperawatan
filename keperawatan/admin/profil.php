<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
check_penguji();

$user_id = (int) $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf_token();
    $nama = trim($_POST['nama'] ?? '');
    $gelar = trim($_POST['gelar'] ?? '');
    $foto_profil = null;

    if ($nama === '' || strlen($nama) > 150 || strlen($gelar) > 100) {
        $error = 'Nama wajib diisi (maksimal 150 karakter) dan gelar maksimal 100 karakter.';
    } elseif (!empty($_FILES['foto_profil']['name'])) {
        $file = $_FILES['foto_profil'];
        $allowed_types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $image_info = $file['error'] === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name']) ? @getimagesize($file['tmp_name']) : false;
        $mime_type = $image_info['mime'] ?? '';
        $pixelCount = $image_info ? ((int) $image_info[0] * (int) $image_info[1]) : 0;

        if ($file['error'] !== UPLOAD_ERR_OK || !$image_info || !isset($allowed_types[$mime_type]) || $file['size'] > 2 * 1024 * 1024 || $pixelCount > 25000000) {
            $error = 'Foto harus berupa JPG, PNG, atau WEBP dengan ukuran maksimal 2 MB.';
        } else {
            $upload_dir = dirname(__DIR__) . '/uploads/profil';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $foto_profil = 'profil_' . $user_id . '_' . bin2hex(random_bytes(8)) . '.' . $allowed_types[$mime_type];
            if (!move_uploaded_file($file['tmp_name'], $upload_dir . '/' . $foto_profil)) {
                $error = 'Foto gagal disimpan.';
            }
        }
    }

    if ($error === '') {
        if ($foto_profil !== null) {
            $stmt = mysqli_prepare($conn, 'UPDATE users SET nama = ?, gelar = ?, foto_profil = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'sssi', $nama, $gelar, $foto_profil, $user_id);
        } else {
            $stmt = mysqli_prepare($conn, 'UPDATE users SET nama = ?, gelar = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'ssi', $nama, $gelar, $user_id);
        }
        mysqli_stmt_execute($stmt);
        $message = 'Profil penguji berhasil diperbarui.';
    }
}

$stmt = mysqli_prepare($conn, 'SELECT username, nama, gelar, foto_profil FROM users WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

include '../includes/admin_header.php';
?>
<div class="container profile-page">
    <div class="profile-page-heading">
        <div>
            <p class="eyebrow">Akun penguji</p>
            <h1>Profil Saya</h1>
            <p>Perbarui foto, nama, dan gelar yang tampil pada panel penguji.</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-primary">Kembali</a>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card profile-form-card">
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="profile-form-preview">
                <div class="profile-avatar profile-avatar-large">
                    <?php if (!empty($user['foto_profil'])): ?>
                        <img src="../uploads/profil/<?= htmlspecialchars($user['foto_profil']) ?>" alt="Foto profil">
                    <?php else: ?>
                        <span><?= htmlspecialchars(strtoupper(substr($user['nama'] ?: $user['username'], 0, 1))) ?></span>
                    <?php endif; ?>
                </div>
                <div>
                    <strong><?= htmlspecialchars($user['nama'] ?: $user['username']) ?></strong>
                    <span><?= htmlspecialchars($user['gelar'] ?: 'Gelar belum diisi') ?></span>
                </div>
            </div>

            <div class="mb-3">
                <label for="foto_profil">Foto profil</label>
                <input class="form-control" type="file" id="foto_profil" name="foto_profil" accept="image/jpeg,image/png,image/webp">
                <small class="text-muted">JPG, PNG, atau WEBP, maksimal 2 MB.</small>
            </div>

            <div class="mb-3">
                <label for="nama">Nama</label>
                <input class="form-control" type="text" id="nama" name="nama" value="<?= htmlspecialchars($user['nama'] ?? '') ?>" required maxlength="150">
            </div>

            <div class="mb-4">
                <label for="gelar">Gelar</label>
                <input class="form-control" type="text" id="gelar" name="gelar" value="<?= htmlspecialchars($user['gelar'] ?? '') ?>" maxlength="100" placeholder="Contoh: Ns., M.Kep.">
            </div>

            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </form>
    </div>
</div>
<?php include '../includes/admin_footer.php'; ?>
