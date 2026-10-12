
<?php
session_start();

$errors = [];

require_once __DIR__ . '/../database/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';
    $setuju = isset($_POST['setuju']);

    if ($nama === '') {
        $errors[] = 'Nama lengkap wajib diisi.';
    }

    if ($email === '') {
        $errors[] = 'Email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }

    if ($password === '') {
        $errors[] = 'Kata sandi wajib diisi.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Kata sandi minimal 8 karakter.';
    }

    if ($password !== $konfirmasi) {
        $errors[] = 'Konfirmasi kata sandi tidak cocok.';
    }

    if (!$setuju) {
        $errors[] = 'Kamu harus menyetujui Persyaratan & Ketentuan.';
    }

    if (empty($errors)) {
        try {
            $pdo = getConnection();

            $sql = "SELECT id_akun FROM akun WHERE email = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['email' => $email]);

            if ($stmt->fetch()) {
                $errors[] = 'Email sudah terdaftar. Silakan gunakan email lain.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO akun
                        (nama, email, password_hash, role)
                        VALUES
                        (:nama, :email, :password_hash, :role)";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'nama' => $nama,
                    'email' => $email,
                    'password_hash' => $hash,
                    'role' => 'penghuni'
                ]);

                $_SESSION['register_success'] =
                    'Pendaftaran berhasil! Silakan masuk menggunakan akunmu.';

                header('Location: login.php');
                exit;
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                $errors[] = 'Email sudah terdaftar. Silakan gunakan email lain.';
            } else {
                error_log($e->getMessage());
                $errors[] = 'Pendaftaran gagal karena terjadi kesalahan. Silakan coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - SIMKOS</title>
    <link rel="stylesheet" href="../assets/login.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <h1>Buat Akun SIMKOS</h1>

        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <?= htmlspecialchars(implode(' ', $errors)) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="daftar.php">
            <div class="field">
                <label for="nama">Nama Lengkap</label>
                <input
                    type="text"
                    id="nama"
                    name="nama"
                    placeholder="Nama kamu"
                    value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
                    required
                >
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="nama@email.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                >
            </div>

            <div class="field">
                <label for="password">Kata Sandi</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Minimal 8 karakter"
                    minlength="8"
                    required
                >
            </div>

            <div class="field">
                <label for="konfirmasi">Konfirmasi Kata Sandi</label>
                <input
                    type="password"
                    id="konfirmasi"
                    name="konfirmasi"
                    placeholder="Ulangi kata sandi"
                    minlength="8"
                    required
                >
            </div>

            <div class="checkbox-row">
                <input
                    type="checkbox"
                    id="setuju"
                    name="setuju"
                    <?= isset($_POST['setuju']) ? 'checked' : '' ?>
                >
                <label for="setuju">
                    Saya setuju dengan Persyaratan &amp; Ketentuan dan
                    Kebijakan Privasi SIMKOS.
                </label>
            </div>

            <button type="submit" class="btn-auth">Daftar →</button>
        </form>
    </div>

    <div class="auth-footer">
        Sudah punya akun?
        <a href="login.php" class="link">Masuk di Sini</a>
    </div>
</div>
</body>
</html>