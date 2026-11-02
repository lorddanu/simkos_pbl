<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SIMKOS - Calon Penghuni</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/calon_penghuni.css?v=2">
</head>
<body class="calon-body">
<div class="phone">
  <header class="calon-header">
    <?php if ($urlKembali !== null): ?>
      <a class="icon" href="<?= h($urlKembali) ?>" aria-label="Kembali">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M13 8l-4 4 4 4"/></svg>
      </a>
    <?php endif; ?>
    <h1>SIMKOS</h1>
    <div class="header-actions" style="display:flex;align-items:center;gap:14px;margin-left:auto;flex:0 0 auto">
      <a class="header-action" href="index.php?halaman=notifikasi" aria-label="Notifikasi" title="Notifikasi" style="display:grid;place-items:center;width:28px;height:34px;flex:none;color:#fff;text-decoration:none">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>
        <?php if (!empty($_SESSION['pengajuan_sewa_demo']) && empty($_SESSION['notifikasi_dilihat'])): ?><span class="notification-dot" aria-label="Ada notifikasi baru"></span><?php endif; ?>
      </a>
      <form method="post" action="../auth/logout.php">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <button class="icon akun-icon akun-logout" type="submit" aria-label="Keluar dari akun" title="Keluar">
          <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="9" r="3"/><path d="M5.7 19c.8-3 3.2-4.5 6.3-4.5 3.1 0 5.5 1.5 6.3 4.5"/></svg>
        </button>
      </form>
    </div>
  </header>
  <main class="calon-main <?= h($mainClass) ?>">
    <?php require __DIR__ . '/screens/' . $halaman . '.php'; ?>
  </main>
  <?php if (in_array($halaman, ['ajukan', 'keluhan', 'keluhan_terkirim'], true)): ?>
  <nav class="calon-nav" aria-label="Menu calon penghuni">
    <a href="index.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 9-8 9 8v10H3z"/></svg>Dashboard</a>
    <a href="<?= h($urlNavigasi) ?>" <?= $halaman === 'ajukan' ? 'aria-current="page"' : '' ?>><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="12" cy="12" r="3"/></svg>Unggah</a>
    <a href="index.php?halaman=keluhan" <?= in_array($halaman, ['keluhan', 'keluhan_terkirim'], true) ? 'aria-current="page"' : '' ?>><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v12H8l-4 4z"/></svg>Keluhan</a>
  </nav>
  <?php endif; ?>
</div>
</body>
</html>
