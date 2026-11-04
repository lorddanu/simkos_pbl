
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

$error = $_SESSION['error_daftar'] ?? '';
unset($_SESSION['error_daftar']);

$sukses = isset($_GET['sukses']) && $_GET['sukses'] === '1';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Penghuni - SIMKOS</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>

<body>

    <main class="auth-wrapper">

        <div class="auth-card">

            <h1>Daftar Akun Penghuni</h1>

            <?php if ($sukses): ?>
                <div class="success-box">
                    Pendaftaran berhasil. Silakan login.
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="error-box">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="proses_daftar.php" method="POST">

                <div class="field">
                    <label for="nama">Nama Lengkap</label>
                    <input
                        type="text"
                        id="nama"
                        name="nama"
                        placeholder="Masukkan nama lengkap"
                        maxlength="100"
                        required
                        autocomplete="name"
                    >
                </div>

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
                        placeholder="Minimal 8 karakter"
                        minlength="8"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <div class="field">
                    <label for="konfirmasi">Konfirmasi Password</label>
                    <input
                        type="password"
                        id="konfirmasi"
                        name="konfirmasi"
                        placeholder="Ulangi password"
                        minlength="8"
                        required
                        autocomplete="new-password"
                    >
                </div>

                <div class="checkbox-row">
                    <input
                        type="checkbox"
                        id="setuju"
                        name="setuju"
                        value="1"
                        required
                    >

                    <label for="setuju">
                        Saya menyetujui ketentuan penggunaan sistem SIMKOS.
                    </label>
                </div>

                <button type="submit" class="btn-auth">
                    Daftar Sekarang
                </button>

            </form>

        </div>

        <div class="auth-footer">
            Sudah punya akun?
            <a href="login.php" class="link">Login di sini</a>
        </div>

    </main>

</body>
</html>
