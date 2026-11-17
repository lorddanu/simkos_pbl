<?php
require __DIR__ . '/../database/helpers.php';

if (current_user()) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['mulai'])) {
    unset($_SESSION['password_reset']);
}
$error = '';
$notice = $_SESSION['password_reset_notice'] ?? '';
unset($_SESSION['password_reset_notice']);
$email = '';
$reset = $_SESSION['password_reset'] ?? null;

if (is_array($reset) && !empty($reset['expires_at']) && (int) $reset['expires_at'] < time()) {
    unset($_SESSION['password_reset']);
    $reset = null;
    $error = 'Kode OTP sudah kedaluwarsa. Silakan minta kode baru.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        http_response_code(400);
        exit('Permintaan tidak valid.');
    }

    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'kirim_otp') {
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Masukkan alamat email yang valid.';
        } else {
            $st = db()->prepare('SELECT email FROM akun WHERE LOWER(email) = LOWER(?) LIMIT 1');
            $st->execute([$email]);
            $akun = $st->fetch();

            if (!$akun) {
                // Respons sama untuk email yang tidak terdaftar agar akun tidak mudah ditebak.
                $notice = 'Jika email terdaftar, instruksi pemulihan akan tersedia.';
            } else {
                $kode = (string) random_int(100000, 999999);
                $_SESSION['password_reset'] = [
                    'email' => $akun['email'],
                    'otp_hash' => password_hash($kode, PASSWORD_DEFAULT),
                    'demo_code' => $kode,
                    'expires_at' => time() + 600,
                    'resend_after' => time() + 30,
                    'attempts' => 0,
                    'verified_until' => 0,
                ];
                header('Location: lupa_password.php?langkah=otp');
                exit;
            }
        }
    } elseif ($aksi === 'kirim_ulang') {
        $reset = $_SESSION['password_reset'] ?? null;
        if (!is_array($reset) || empty($reset['email']) || (int) ($reset['expires_at'] ?? 0) < time()) {
            unset($_SESSION['password_reset']);
            $reset = null;
            $error = 'Sesi pemulihan sudah berakhir. Masukkan email kembali.';
        } elseif ((int) ($reset['resend_after'] ?? 0) > time()) {
            $error = 'Tunggu beberapa detik sebelum meminta kode baru.';
        } else {
            $kode = (string) random_int(100000, 999999);
            $reset['otp_hash'] = password_hash($kode, PASSWORD_DEFAULT);
            $reset['demo_code'] = $kode;
            $reset['expires_at'] = time() + 600;
            $reset['resend_after'] = time() + 30;
            $reset['attempts'] = 0;
            $reset['verified_until'] = 0;
            $_SESSION['password_reset'] = $reset;
            $_SESSION['password_reset_notice'] = 'Kode OTP simulasi baru berhasil dibuat.';
            header('Location: lupa_password.php?langkah=otp');
            exit;
        }
    } elseif ($aksi === 'ganti_email') {
        unset($_SESSION['password_reset']);
        header('Location: lupa_password.php');
        exit;
    } elseif ($aksi === 'verifikasi_otp') {
        $reset = $_SESSION['password_reset'] ?? null;
        $digits = $_POST['otp_digits'] ?? [];
        $kode = is_array($digits) ? implode('', array_map('strval', $digits)) : trim((string) $digits);

        if (!is_array($reset) || empty($reset['otp_hash']) || (int) ($reset['expires_at'] ?? 0) < time()) {
            unset($_SESSION['password_reset']);
            $reset = null;
            $error = 'Kode OTP sudah kedaluwarsa. Minta kode baru.';
        } elseif ((int) ($reset['attempts'] ?? 0) >= 5) {
            unset($_SESSION['password_reset']);
            $reset = null;
            $error = 'Batas percobaan habis. Silakan mulai pemulihan lagi.';
        } elseif (!preg_match('/^\d{6}$/', $kode) || !password_verify($kode, $reset['otp_hash'])) {
            $reset['attempts'] = (int) ($reset['attempts'] ?? 0) + 1;
            $_SESSION['password_reset'] = $reset;
            $error = 'Kode OTP tidak sesuai. Periksa kembali kode Anda.';
        } else {
            $reset['verified_until'] = time() + 600;
            unset($reset['otp_hash'], $reset['demo_code']);
            $_SESSION['password_reset'] = $reset;
            header('Location: lupa_password.php?langkah=baru');
            exit;
        }
    } elseif ($aksi === 'simpan_password') {
        $reset = $_SESSION['password_reset'] ?? null;
        $password = $_POST['password'] ?? '';
        $konfirmasi = $_POST['konfirmasi'] ?? '';

        if (!is_array($reset) || empty($reset['email']) || (int) ($reset['verified_until'] ?? 0) < time()) {
            unset($_SESSION['password_reset']);
            $reset = null;
            $error = 'Sesi verifikasi sudah berakhir. Silakan ulangi pemulihan kata sandi.';
        } elseif (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            $error = 'Gunakan minimal 8 karakter dengan kombinasi huruf dan angka.';
        } elseif ($password !== $konfirmasi) {
            $error = 'Konfirmasi kata sandi tidak sama.';
        } else {
            $st = db()->prepare('UPDATE akun SET password_hash = ? WHERE email = ?');
            $st->execute([password_hash($password, PASSWORD_DEFAULT), $reset['email']]);
            unset($_SESSION['password_reset']);
            $_SESSION['info'] = 'Kata sandi berhasil diubah. Silakan masuk dengan kata sandi baru.';
            header('Location: login.php');
            exit;
        }
    }
}

$reset = $_SESSION['password_reset'] ?? null;
$langkah = 'email';
if (is_array($reset) && (int) ($reset['verified_until'] ?? 0) >= time()) {
    $langkah = 'baru';
} elseif (is_array($reset) && !empty($reset['otp_hash']) && (int) ($reset['expires_at'] ?? 0) >= time()) {
    $langkah = 'otp';
}
$emailTampil = is_array($reset) ? (string) ($reset['email'] ?? '') : $email;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $langkah === 'email' ? 'Lupa Kata Sandi' : ($langkah === 'otp' ? 'Verifikasi OTP' : 'Kata Sandi Baru') ?> - SIMKOS</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css?v=recovery-mobile-2">
</head>
<body class="auth recovery-auth">
<main class="recovery-wrap">
  <section class="auth-card recovery-card">
    <?php if ($langkah === 'email'): ?>
      <h1>Lupa Kata Sandi</h1>
      <p class="recovery-description">Masukkan email pemulihan Anda. Kode OTP simulasi akan ditampilkan di halaman berikutnya.</p>
      <?php if ($notice): ?><p class="ok" role="status"><?= h($notice) ?></p><?php endif; ?>
      <?php if ($error): ?><p class="err" role="alert"><?= h($error) ?></p><?php endif; ?>
      <form method="post" class="recovery-form">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="aksi" value="kirim_otp">
        <label for="email">Email Pemulihan</label>
        <input type="email" id="email" name="email" value="<?= h($email) ?>" placeholder="nama@email.com" autocomplete="email" required>
        <button class="btn" type="submit">Kirim Kode OTP &rarr;</button>
      </form>

    <?php elseif ($langkah === 'otp'): ?>
      <h1>Verifikasi OTP</h1>
      <p class="recovery-description">Masukkan 6 digit kode OTP untuk <?= h($emailTampil) ?>.</p>
      <p class="recovery-demo-code" role="status">Kode OTP simulasi: <strong><?= h((string) ($reset['demo_code'] ?? '')) ?></strong><small>Kode ini hanya untuk demonstrasi lokal, tidak dikirim melalui email.</small></p>
      <?php if ($notice): ?><p class="ok" role="status"><?= h($notice) ?></p><?php endif; ?>
      <?php if ($error): ?><p class="err" role="alert"><?= h($error) ?></p><?php endif; ?>
      <form method="post" class="recovery-form" id="otp-form">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="aksi" value="verifikasi_otp">
        <div class="otp-inputs" aria-label="Enam digit kode OTP">
          <?php for ($i = 0; $i < 6; $i++): ?>
          <input type="text" name="otp_digits[]" inputmode="numeric" pattern="[0-9]" maxlength="1" aria-label="Digit OTP <?= $i + 1 ?>" autocomplete="<?= $i === 0 ? 'one-time-code' : 'off' ?>" required>
          <?php endfor; ?>
        </div>
        <button class="btn" type="submit">Verifikasi &rarr;</button>
      </form>
      <div class="recovery-resend"><span>Tidak menerima kode?</span>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="aksi" value="kirim_ulang">
          <button type="submit" class="text-button">Kirim ulang</button>
        </form>
      </div>

    <?php else: ?>
      <h1>Kata Sandi Baru</h1>
      <p class="recovery-description">Buat kata sandi baru untuk akun Anda.</p>
      <?php if ($error): ?><p class="err" role="alert"><?= h($error) ?></p><?php endif; ?>
      <form method="post" class="recovery-form">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="aksi" value="simpan_password">
        <label for="password">Kata Sandi Baru</label>
        <input type="password" id="password" name="password" placeholder="Minimal 8 karakter" autocomplete="new-password" required>
        <label for="konfirmasi">Konfirmasi Kata Sandi</label>
        <input type="password" id="konfirmasi" name="konfirmasi" placeholder="Ulangi kata sandi" autocomplete="new-password" required>
        <small class="recovery-hint">Minimal 8 karakter, kombinasi huruf dan angka.</small>
        <button class="btn" type="submit">Simpan Kata Sandi &rarr;</button>
      </form>
    <?php endif; ?>
  </section>
  <?php if (in_array($langkah, ['otp', 'baru'], true)): ?>
  <form method="post" class="recovery-footer-form">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="aksi" value="ganti_email">
    <button type="submit" class="recovery-link">Mulai ulang</button>
  </form>
  <?php else: ?>
  <a class="recovery-link" href="login.php">Kembali ke Masuk</a>
  <?php endif; ?>
</main>
<script>
(() => {
  const boxes = [...document.querySelectorAll('.otp-inputs input')];
  if (!boxes.length) return;
  boxes.forEach((box, index) => {
    box.addEventListener('input', () => {
      box.value = box.value.replace(/\D/g, '').slice(-1);
      if (box.value && boxes[index + 1]) boxes[index + 1].focus();
    });
    box.addEventListener('keydown', (event) => {
      if (event.key === 'Backspace' && !box.value && boxes[index - 1]) boxes[index - 1].focus();
    });
    box.addEventListener('paste', (event) => {
      event.preventDefault();
      const digits = (event.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
      digits.split('').forEach((digit, digitIndex) => { if (boxes[digitIndex]) boxes[digitIndex].value = digit; });
      boxes[Math.min(digits.length, 5)].focus();
    });
  });
})();
</script>
</body>
</html>