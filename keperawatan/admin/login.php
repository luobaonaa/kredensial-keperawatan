<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    check_csrf_token();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (login_is_rate_limited()) {
        http_response_code(429);
        $error = 'Terlalu banyak percobaan login. Tunggu 15 menit lalu coba lagi.';
    } elseif ($username === '' || strlen($username) > 100 || strlen($password) > 200) {
        record_failed_login();
        $error = 'Akun penguji tidak ditemukan atau password salah!';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, password, role FROM users WHERE username = ? AND role = 'penguji' LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        if ($row && password_verify($password, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $row['role'];
            $_SESSION['last_activity'] = time();
            $_SESSION['last_regenerated'] = time();
            unset($_SESSION['csrf_token']);
            clear_failed_logins();
            header("Location: dashboard.php");
            exit();
        }
        record_failed_login();
        $error = "Akun penguji tidak ditemukan atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Penguji - Sistem Keperawatan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-dark d-flex align-items-center vh-100">
<div class="container d-flex justify-content-center">
    <div class="card shadow p-4" style="max-width: 400px; width: 100%;">
        <h3 class="text-center mb-3">Login Penguji</h3>
        <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        <form method="POST" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="mb-3">
                <label>Username Penguji</label>
                <input type="text" name="username" class="form-control" maxlength="100" autocomplete="username" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" maxlength="200" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn-dark w-100">Login Penguji</button>
        </form>
        <p class="mt-3 text-center text-muted">Akun penguji dibuat oleh penguji yang sudah berwenang.</p>
    </div>
</div>
</body>
</html>
