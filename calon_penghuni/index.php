<?php
// Dashboard calon penghuni yang belum memiliki kamar.
require __DIR__ . '/../database/helpers.php';
$user = require_login();

if ($user['role'] !== 'calon_penghuni') {
    header('Location: ../index.php');
    exit;
}

$halaman = $_GET['halaman'] ?? 'dashboard';
$kamarKosong = [];
if ($halaman === 'kamar') {
    $st = db()->query("SELECT nomor_kamar, harga_bulanan FROM kamar WHERE status = 'kosong' ORDER BY nomor_kamar");
    $kamarKosong = $st->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SIMKOS - Calon Penghuni</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.calon-body{background:#fff;padding:0;min-height:100vh;align-items:stretch}
.calon-body .phone{width:100%;max-width:440px;height:100vh;height:100dvh;max-height:none;min-height:100vh;border-radius:0;box-shadow:none}
.calon-header{height:112px;min-height:112px;padding:58px 24px 4px;border-radius:0 0 12px 12px}
.calon-header h1{font-size:30px}
.akun-icon{width:34px;height:34px;flex:0 0 34px}
.akun-logout{margin:0;padding:0;border:0;background:transparent;color:#fff;cursor:pointer}
.calon-main{position:relative;gap:12px;padding:0 24px;justify-content:center}
.calon-card{text-align:center;padding:64px 34px 30px;border-radius:16px;transform:translateY(-55px)}
.calon-card h2{font-size:15px;margin:0 0 42px}
.calon-card p{font-size:12px;line-height:1.25;margin:0 0 24px}
.calon-card .btn{background:var(--pd);font-size:14px;padding:10px;border-radius:10px}
.kamar-card{padding:16px;border-radius:14px}
.kamar-card h2{margin:0 0 4px;font-size:15px}
.kamar-card p{margin:0;font-size:13px}
.empty-state{text-align:center;color:#5a4fb0;font-size:13px;margin:auto 0}
</style>
</head>
<body class="calon-body">
<div class="phone">
  <header class="calon-header">
    <?php if ($halaman === 'kamar'): ?><a class="icon" href="index.php" aria-label="Kembali ke dashboard"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M13 8l-4 4 4 4"/></svg></a><?php endif; ?>
    <h1>SIMKOS</h1>
    <?php if ($halaman === 'dashboard'): ?><form method="post" action="../auth/logout.php"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><button class="icon akun-icon akun-logout" type="submit" aria-label="Keluar dari akun" title="Keluar"><svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="9" r="3"/><path d="M5.7 19c.8-3 3.2-4.5 6.3-4.5s5.5 1.5 6.3 4.5"/></svg></button></form><?php endif; ?>
  </header>
  <main class="calon-main">
    <?php if ($halaman === 'kamar'): ?>
      <?php if (!$kamarKosong): ?>
        <p class="empty-state">Belum ada kamar kosong saat ini.</p>
      <?php else: foreach ($kamarKosong as $kamar): ?>
        <article class="card kamar-card">
          <h2>Kamar <?= h($kamar['nomor_kamar']) ?></h2>
          <p>Rp <?= h(number_format((float) $kamar['harga_bulanan'], 0, ',', '.')) ?>/bulan</p>
          <p>Siap Huni</p>
        </article>
      <?php endforeach; endif; ?>
    <?php else: ?>
      <section class="card calon-card">
        <h2>Anda Belum memiliki kamar.</h2>
        <p>Anda belum terdaftar sebagai penghuni kos manapun.<br>Cari kamar kosong dan hubungi admin untuk survei.</p>
        <a class="btn" href="index.php?halaman=kamar">Lihat Kamar Kosong</a>
      </section>
    <?php endif; ?>
    <?php if ($halaman === 'kamar'): ?><form method="post" action="../auth/logout.php">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <button class="btn alt" type="submit">Keluar (<?= h($user['nama'] ?? $user['email']) ?>)</button>
    </form><?php endif; ?>
  </main>
</div>
</body>
</html>
