<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
check_penguji();

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
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $error = 'Username sudah terdaftar!';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, role) VALUES (?, ?, 'penguji')");
            mysqli_stmt_bind_param($stmt, 'ss', $username, $hashed);

            if (mysqli_stmt_execute($stmt)) {
                $success = "Registrasi penguji berhasil! Silakan <a href='login.php'>Login</a>";
            } else {
                $error = 'Gagal mendaftar!';
            }
        }

        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register Penguji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-dark d-flex align-items-center vh-100">
<div class="container d-flex justify-content-center">
    <div class="card shadow p-4" style="max-width: 400px; width: 100%;">
        <h3 class="text-center mb-3">Register Penguji</h3>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="mb-3">
                <label for="username">Username</label>
                <input id="username" type="text" name="username" class="form-control" minlength="3" maxlength="100" pattern="[A-Za-z0-9._-]+" value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="mb-3">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" class="form-control" minlength="10" maxlength="200" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password">Konfirmasi Password</label>
                <input id="confirm_password" type="password" name="confirm_password" class="form-control" minlength="10" maxlength="200" required>
            </div>
            <button type="submit" class="btn btn-dark w-100">Daftar Penguji</button>
        </form>
        <p class="mt-3 text-center">Sudah punya akun? <a href="login.php">Login Penguji</a></p>
    </div>
</div>
</body>
</html>
