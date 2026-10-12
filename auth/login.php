
<?php
session_start();

require_once __DIR__ . '/../database/database.php';

if (isset($_SESSION['id_akun'])) {
    header("Location: ../includes/dashboard.php");
    exit;
}

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

$success = $_SESSION['register_success'] ?? '';
unset($_SESSION['register_success']);

?>



<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - SIMKOS</title>
    <link rel="stylesheet" href="../assets/cs/login.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <h1>Masuk ke SIMKOS</h1>

        <?php if (!empty($success)): ?>
            <div class="success-box">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error-box">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="proses_login.php">
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="nama@email.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="field">
                <label for="password">Kata Sandi</label>
                <input type="password" id="password" name="password" placeholder="••••••••••">
            </div>

            <div class="forgot-row">
                <a href="/auth/lupa-sandi.php" class="link">Lupa Kata Sandi?</a>
            </div>

            <button type="submit" class="btn-auth">Masuk →</button>
        </form>
    </div>

    <div class="auth-footer">
        Belum punya akun? <a href="/auth/daftar.php" class="link">Daftar di Sini</a>
    </div>
</div>
</body>
</html>