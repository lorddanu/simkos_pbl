<?php
require __DIR__ . '/../database/database.php';

if (current_user()) { header('Location: index.php'); exit; }

$error = '';
$info = $_SESSION['info'] ?? '';
unset($_SESSION['info']);
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { http_response_code(400); exit('Permintaan tidak valid.'); }
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $st = db()->prepare('SELECT id, password_hash FROM users WHERE email = ?');
    $st->execute([$email]);
    $row = $st->fetch();

    if ($row && password_verify($password, $row['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $row['id'];
        header('Location: index.php');
        exit;
    }
    $error = 'Email atau kata sandi salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk - SIMKOS</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth">
<div class="auth-wrap">
  <form class="auth-card" method="post" novalidate>
    <h1>Masuk ke SIMKOS</h1>
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

    <?php if ($info): ?><p class="ok" role="status"><?= h($info) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="err" role="alert"><?= h($error) ?></p><?php endif; ?>

    <label for="email">Email</label>
    <input type="email" id="email" name="email" placeholder="nama@email.com" value="<?= h($email) ?>" autocomplete="email" required>

    <label for="password">Kata Sandi</label>
    <input type="password" id="password" name="password" placeholder="Masukkan kata sandi" autocomplete="current-password" required>

    <a class="lupa" href="#">Lupa Kata Sandi?</a>
    <button class="btn" type="submit">Masuk</button>
  </form>
  <p class="auth-foot">Belum punya akun? <a href="register.php">Daftar di Sini</a></p>
</div>
</body>
</html>