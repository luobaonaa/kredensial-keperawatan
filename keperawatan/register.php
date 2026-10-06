<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf_token();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($username === '' || strlen($username) > 100 || !preg_match('/^[A-Za-z0-9._-]{3,100}$/', $username) || strlen($password) < 10 || strlen($password) > 200 || $confirm_password === '') {
        $error = 'Username minimal 3 karakter dan password harus 10–200 karakter.';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok!';
    } else {
        $check_stmt = mysqli_prepare($conn, 'SELECT id FROM users WHERE username = ?');
        if (!$check_stmt) {
            error_log('Gagal menyiapkan pemeriksaan username: ' . mysqli_error($conn));
            $error = 'Pendaftaran belum dapat diproses. Silakan coba lagi.';
        } elseif (!mysqli_stmt_bind_param($check_stmt, 's', $username) || !mysqli_stmt_execute($check_stmt)) {
            error_log('Gagal memeriksa username: ' . mysqli_stmt_error($check_stmt));
            $error = 'Pendaftaran belum dapat diproses. Silakan coba lagi.';
        } elseif (!mysqli_stmt_store_result($check_stmt)) {
            error_log('Gagal membaca pemeriksaan username: ' . mysqli_stmt_error($check_stmt));
            $error = 'Pendaftaran belum dapat diproses. Silakan coba lagi.';
        } elseif (mysqli_stmt_num_rows($check_stmt) > 0) {
            $error = 'Username sudah terdaftar!';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = mysqli_prepare($conn, 'INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
            $role = 'perawat';

            if (!$insert_stmt) {
                error_log('Gagal menyiapkan pendaftaran: ' . mysqli_error($conn));
                $error = 'Pendaftaran belum dapat diproses. Silakan coba lagi.';
            } elseif (!mysqli_stmt_bind_param($insert_stmt, 'sss', $username, $hashed, $role)) {
                error_log('Gagal mengikat data pendaftaran: ' . mysqli_stmt_error($insert_stmt));
                $error = 'Pendaftaran belum dapat diproses. Silakan coba lagi.';
            } elseif (mysqli_stmt_execute($insert_stmt)) {
            $success = "Registrasi berhasil! Silakan <a href='login.php'>Login</a>";
            $_POST = [];
            } else {
                error_log('Gagal mendaftar: ' . mysqli_stmt_error($insert_stmt));
                $error = 'Pendaftaran belum dapat diproses. Silakan coba lagi.';
            }

            if ($insert_stmt) {
                mysqli_stmt_close($insert_stmt);
            }
        }

        if ($check_stmt) {
            mysqli_stmt_close($check_stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register Perawat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light d-flex align-items-center vh-100">
<div class="container d-flex justify-content-center">
    <div class="card shadow p-4" style="max-width: 400px; width: 100%;">
        <h3 class="text-center mb-3">Register Perawat</h3>
        <?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control" minlength="3" maxlength="100" pattern="[A-Za-z0-9._-]+" value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" minlength="10" maxlength="200" autocomplete="new-password" required>
            </div>
            <div class="mb-3">
                <label>Konfirmasi Password</label>
                <input type="password" name="confirm_password" class="form-control" minlength="10" maxlength="200" autocomplete="new-password" required>
            </div>
            <button type="submit" class="btn btn-success w-100">Daftar</button>
        </form>
        <p class="mt-3 text-center">Sudah punya akun? <a href="login.php">Login</a></p>
    </div>
</div>
</body>
</html>
