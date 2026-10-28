<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SIMKOS - Calon Penghuni</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/calon_penghuni.css">
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
    <form method="post" action="../auth/logout.php">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <button class="icon akun-icon akun-logout" type="submit" aria-label="Keluar dari akun" title="Keluar">
        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="9" r="3"/><path d="M5.7 19c.8-3 3.2-4.5 6.3-4.5 3.1 0 5.5 1.5 6.3 4.5"/></svg>
      </button>
    </form>
  </header>
  <main class="calon-main <?= h($mainClass) ?>">
    <?php require __DIR__ . '/screens/' . $halaman . '.php'; ?>
  </main>
</div>
<?php if ($halaman === 'ajukan' && $kamarDipilih): ?>
<script>
const hargaKamar = <?= json_encode((float) $kamarDipilih['harga_bulanan']) ?>;
const pilihanBulan = document.getElementById('bulan');
const totalTagihan = document.getElementById('total');
function perbaruiTotal(){ totalTagihan.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(hargaKamar * Number(pilihanBulan.value)); }
pilihanBulan.addEventListener('change', perbaruiTotal);
perbaruiTotal();
</script>
<?php endif; ?>
</body>
</html>
