
<?php
session_start();

if (isset($_SESSION['id_akun'])) {
    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../penghuni/dashboard.php');
    }
    exit;
}

$error = $_SESSION['error_login'] ?? '';
unset($_SESSION['error_login']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login SIMKOS</title>
    <link rel="stylesheet" href="../assets/cs/login.css">
</head>

<body>

    <main class="auth-wrapper">

        <div class="auth-card">

            <h1>Login SIMKOS</h1>

            <?php if ($error !== ''): ?>
                <div class="error-box">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="proses_login.php" method="POST">

                <div class="field">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Masukkan email"
                        required
                        autocomplete="email"
                    >
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Masukkan password"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" class="btn-auth">
                    Login
                </button>

            </form>

        </div>

        <div class="auth-footer">
            Belum punya akun?
            <a href="daftar.php" class="link">
                Daftar sebagai penghuni
            </a>
        </div>

    </main>

</body>
</html>
