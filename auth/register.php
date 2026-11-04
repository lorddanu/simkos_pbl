<?php
require __DIR__ . '/../database/helpers.php';

if (current_user()) { header('Location: ../index.php'); exit; }

$error = '';
$nama = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { http_response_code(400); exit('Permintaan tidak valid.'); }
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';
    $setuju = isset($_POST['setuju']);

    if ($nama === '' || $email === '' || $password === '') {
        $error = 'Semua kolom wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email belum benar.';
    } elseif (strlen($password) < 8) {
        $error = 'Kata sandi minimal 8 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi kata sandi tidak sama.';
    } elseif (!$setuju) {
        $error = 'Centang persetujuan Persyaratan & Ketentuan dulu.';
    } else {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $st = $pdo->prepare('INSERT INTO penghuni (nama) VALUES (?) RETURNING id_penghuni');
            $st->execute([$nama]);
            $idPenghuni = (int) $st->fetchColumn();

            $st = $pdo->prepare("INSERT INTO akun (id_penghuni, email, password_hash, role) VALUES (?, ?, ?, 'calon_penghuni')");
            $st->execute([$idPenghuni, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $pdo->commit();

            $_SESSION['info'] = 'Akun berhasil dibuat. Silakan masuk.';
            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = ($e->getCode() === '23505')
                ? 'Email ini sudah terdaftar. Silakan masuk.'
                : 'Pendaftaran gagal. Coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Buat Akun - SIMKOS</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth">
<div class="auth-wrap">
  <form class="auth-card" method="post" novalidate>
    <h1>Buat Akun SIMKOS</h1>
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

    <?php if ($error): ?><p class="err" role="alert"><?= h($error) ?></p><?php endif; ?>

    <label for="nama">Nama Lengkap</label>
    <input type="text" id="nama" name="nama" placeholder="Nama kamu" value="<?= h($nama) ?>" autocomplete="name" required>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" placeholder="nama@email.com" value="<?= h($email) ?>" autocomplete="email" required>

    <label for="password">Kata Sandi</label>
    <input type="password" id="password" name="password" placeholder="Minimal 8 karakter" autocomplete="new-password" required>

    <label for="konfirmasi">Konfirmasi Kata Sandi</label>
    <input type="password" id="konfirmasi" name="konfirmasi" placeholder="Ulangi kata sandi" autocomplete="new-password" required>

    <label class="cek"><input type="checkbox" name="setuju"> Saya setuju dengan Persyaratan &amp; Ketentuan dan Kebijakan Privasi SIMKOS.</label>

    <button class="btn" type="submit">Daftar</button>
  </form>
  <p class="auth-foot">Sudah punya akun? <a href="login.php">Masuk di Sini</a></p>
</div>
</body>
</html>
