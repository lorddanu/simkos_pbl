<?php
// Dashboard calon penghuni yang belum memiliki kamar.
require __DIR__ . '/../database/helpers.php';
$user = require_login();

if ($user['role'] !== 'calon_penghuni') {
    header('Location: ../index.php');
    exit;
}

$halaman = $_GET['halaman'] ?? 'dashboard';
if (!in_array($halaman, ['dashboard', 'kamar', 'detail', 'ajukan', 'terkirim'], true)) $halaman = 'dashboard';
$pdo = db();
$kamarKosong = $pdo->query("SELECT id_kamar, nomor_kamar, harga_bulanan FROM kamar WHERE status = 'kosong' ORDER BY nomor_kamar")->fetchAll();
$idKamar = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$kamarDipilih = null;
if (in_array($halaman, ['detail', 'ajukan'], true)) {
    $st = $pdo->prepare("SELECT id_kamar, nomor_kamar, harga_bulanan FROM kamar WHERE id_kamar = ? AND status = 'kosong'");
    $st->execute([$idKamar]);
    $kamarDipilih = $st->fetch() ?: null;
    if (!$kamarDipilih) $halaman = 'kamar';
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'ajukan') {
    if (!csrf_ok()) { http_response_code(400); exit('Permintaan tidak valid.'); }
    $idKamarPost = filter_input(INPUT_POST, 'id_kamar', FILTER_VALIDATE_INT) ?: 0;
    $mulai = $_POST['tanggal_mulai'] ?? '';
    $selesai = $_POST['tanggal_selesai'] ?? '';
    $bulan = filter_input(INPUT_POST, 'bulan', FILTER_VALIDATE_INT) ?: 0;
    $st = $pdo->prepare("SELECT id_kamar, nomor_kamar, harga_bulanan FROM kamar WHERE id_kamar = ? AND status = 'kosong'");
    $st->execute([$idKamarPost]);
    $kamarDipilih = $st->fetch() ?: null;
    $tanggalMulai = DateTime::createFromFormat('Y-m-d', $mulai);
    $tanggalSelesai = DateTime::createFromFormat('Y-m-d', $selesai);
    if (!$kamarDipilih) {
        $error = 'Kamar sudah tidak tersedia. Silakan pilih kamar lain.';
        $halaman = 'kamar';
    } elseif (!$tanggalMulai || !$tanggalSelesai || $tanggalMulai < new DateTime('today') || $tanggalSelesai <= $tanggalMulai || $bulan < 1 || $bulan > 24) {
        $error = 'Tanggal sewa atau jumlah bulan belum valid.';
        $halaman = 'ajukan';
    } else {
        $_SESSION['pengajuan_sewa_demo'] = [
            'nomor_kamar' => $kamarDipilih['nomor_kamar'],
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => $selesai,
            'bulan' => $bulan,
            'total' => (float) $kamarDipilih['harga_bulanan'] * $bulan,
        ];
        $halaman = 'terkirim';
    }
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
.calon-body .phone{width:100%;max-width:440px;height:min(100vh,956px);height:min(100dvh,956px);max-height:none;min-height:0;border-radius:0;box-shadow:none}
.calon-header{height:112px;min-height:112px;padding:58px 24px 4px;border-radius:0 0 12px 12px}
.calon-header h1{font-size:30px}
.akun-icon{width:34px;height:34px;flex:0 0 34px}
.akun-logout{margin:0;padding:0;border:0;background:transparent;color:#fff;cursor:pointer}
.calon-main{position:relative;gap:12px;padding:0 24px;justify-content:center}
.calon-card{text-align:center;padding:64px 34px 30px;border-radius:16px;transform:translateY(-55px)}
.calon-card h2{font-size:15px;margin:0 0 42px}
.calon-card p{font-size:12px;line-height:1.25;margin:0 0 24px}
.calon-card .btn{background:var(--pd);font-size:14px;padding:10px;border-radius:10px}
.calon-main.browse{justify-content:flex-start;overflow-y:auto;padding-top:22px;padding-bottom:22px}
.kamar-card{padding:0;border-radius:14px;overflow:hidden;flex:none}
.room-photo-placeholder{display:block;width:100%;height:170px;background:#f2f0ff;border-bottom:1px dashed #aaa2ef}
.detail-photo-placeholder{height:176px}
.kamar-info{padding:10px 12px 14px}
.kamar-card h2{margin:0 0 4px;font-size:15px}
.kamar-card p{margin:0;font-size:13px}
.room-detail{padding:0 0 16px;overflow:hidden}
.room-detail .detail-photo{height:176px}
.detail-content{padding:12px 16px}
.detail-content h2{color:var(--p);font-size:17px;margin:0 0 2px}
.detail-content .price{color:var(--p);margin-bottom:12px}
.facility-list{display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:12px;margin-top:8px}
.facility-box{background:var(--p);color:white;border-radius:12px;padding:14px;margin-top:8px}
.facility-box h3{font-size:15px;margin:0 0 8px}
.bottom-action{margin-top:auto;padding:0 18px 18px}
.apply-card{background:var(--p);color:white;padding:14px;border-radius:14px}
.apply-card label{color:white;font-size:11px}
.apply-card input,.apply-card select{background:#f2f0ff;border:1px solid #aaa2ef;border-radius:8px;padding:8px;font:400 12px Poppins,sans-serif;width:100%;margin:4px 0 10px}
.apply-total{display:flex;justify-content:space-between;border-top:1px solid rgba(255,255,255,.6);margin-top:8px;padding-top:10px;font-size:13px}
.confirm{text-align:center;padding:32px 10px}
.confirm h2{color:var(--p);font-size:17px;margin:10px 0 4px}
.confirm p{font-size:12px;color:#5a4fb0}
.calon-main.form-page{justify-content:flex-start;padding-top:14px}
.empty-state{text-align:center;color:#5a4fb0;font-size:13px;margin:auto 0}
</style>
</head>
<body class="calon-body">
<div class="phone">
  <header class="calon-header">
    <?php if ($halaman !== 'dashboard'): ?><a class="icon" href="index.php?halaman=<?= $halaman === 'kamar' ? 'dashboard' : ($halaman === 'detail' ? 'kamar' : 'detail&id=' . (int) $idKamar) ?>" aria-label="Kembali"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M13 8l-4 4 4 4"/></svg></a><?php endif; ?>
    <h1>SIMKOS</h1>
    <form method="post" action="../auth/logout.php"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><button class="icon akun-icon akun-logout" type="submit" aria-label="Keluar dari akun" title="Keluar"><svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="9" r="3"/><path d="M5.7 19c.8-3 3.2-4.5 6.3-4.5 3.1 0 5.5 1.5 6.3 4.5"/></svg></button></form>
  </header>
  <main class="calon-main <?= in_array($halaman, ['kamar','detail'], true) ? 'browse' : ($halaman === 'ajukan' ? 'form-page' : '') ?>">
    <?php if ($halaman === 'kamar'): ?>
      <?php if (!$kamarKosong): ?>
        <p class="empty-state">Belum ada kamar kosong saat ini.</p>
      <?php else: foreach ($kamarKosong as $kamar): ?>
        <a href="index.php?halaman=detail&id=<?= (int) $kamar['id_kamar'] ?>" class="card kamar-card" style="color:inherit;text-decoration:none"><div class="room-photo-placeholder" aria-label="Tempat foto Kamar <?= h($kamar['nomor_kamar']) ?>"></div><div class="kamar-info"><h2>Kamar <?= h($kamar['nomor_kamar']) ?></h2><p>Rp <?= h(number_format((float) $kamar['harga_bulanan'], 0, ',', '.')) ?>/bulan</p><p>Siap Huni</p></div></a>
      <?php endforeach; endif; ?>
    <?php elseif ($halaman === 'detail' && $kamarDipilih): ?>
      <section class="card room-detail"><div class="room-photo-placeholder detail-photo-placeholder" aria-label="Tempat foto Kamar <?= h($kamarDipilih['nomor_kamar']) ?>"></div><div class="detail-content"><h2>Kamar <?= h($kamarDipilih['nomor_kamar']) ?></h2><p class="price">Rp <?= h(number_format((float) $kamarDipilih['harga_bulanan'], 0, ',', '.')) ?>/bulan</p><div class="facility-box"><h3>Fasilitas Kamar</h3><div class="facility-list"><span>▰ Kasur</span><span>♧ Gantungan Baju</span><span>▣ Lemari</span><span>▣ Kulkas (bersama)</span><span>▰ Meja</span><span>◉ Wifi</span><span>♨ Dapur (bersama)</span><span>♢ Air</span></div></div></div></section>
      <div class="bottom-action"><a class="btn" href="index.php?halaman=ajukan&id=<?= (int) $kamarDipilih['id_kamar'] ?>">Ajukan Sewa Kamar</a></div>
    <?php elseif ($halaman === 'ajukan' && $kamarDipilih): ?>
      <h2 class="t">Ajukan Sewa Kamar</h2>
      <p class="s">Kamar <?= h($kamarDipilih['nomor_kamar']) ?></p>
      <form method="post" class="apply-card" id="form-sewa">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="aksi" value="ajukan"><input type="hidden" name="id_kamar" value="<?= (int) $kamarDipilih['id_kamar'] ?>">
        <strong>Rp <?= h(number_format((float) $kamarDipilih['harga_bulanan'], 0, ',', '.')) ?><small>/bulan</small></strong>
        <?php if ($error): ?><p class="err"><?= h($error) ?></p><?php endif; ?>
        <label for="tanggal_mulai">Tanggal Mulai Sewa</label><input type="date" id="tanggal_mulai" name="tanggal_mulai" min="<?= date('Y-m-d') ?>" required>
        <label for="tanggal_selesai">Tanggal Akhir Sewa</label><input type="date" id="tanggal_selesai" name="tanggal_selesai" required>
        <label for="bulan">Jumlah Bulan yang Ingin Disewa</label><select id="bulan" name="bulan"><?php for ($i=1;$i<=12;$i++): ?><option value="<?= $i ?>"><?= $i ?> bulan</option><?php endfor; ?></select>
        <div class="apply-total"><span>Total tagihan</span><strong id="total">Rp <?= h(number_format((float) $kamarDipilih['harga_bulanan'], 0, ',', '.')) ?></strong></div>
      </form>
      <div class="bottom-action"><button class="btn" type="submit" form="form-sewa">Kirim Pengajuan</button></div>
    <?php elseif ($halaman === 'terkirim' && !empty($_SESSION['pengajuan_sewa_demo'])): $pengajuan = $_SESSION['pengajuan_sewa_demo']; ?>
      <section class="confirm"><svg viewBox="0 0 64 64" width="64" height="64"><circle cx="32" cy="32" r="30" fill="#78b83f"/><path d="M18 33l9 9 19-20" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></svg><h2>Pengajuan Terkirim</h2><p>Pengajuan sewa Kamar <?= h($pengajuan['nomor_kamar']) ?> sedang menunggu konfirmasi admin.</p><div class="facility-box" style="text-align:left"><p>Mulai sewa: <?= h($pengajuan['tanggal_mulai']) ?></p><p>Akhir sewa: <?= h($pengajuan['tanggal_selesai']) ?></p><p>Status: Menunggu persetujuan</p><hr><strong>Total: Rp <?= h(number_format($pengajuan['total'], 0, ',', '.')) ?></strong></div></section>
      <div class="bottom-action"><a class="btn" href="index.php">Kembali ke Beranda</a></div>
    <?php else: ?>
      <section class="card calon-card">
        <h2>Anda Belum memiliki kamar.</h2>
        <p>Anda belum terdaftar sebagai penghuni kos manapun.<br>Cari kamar kosong dan hubungi admin untuk survei.</p>
        <a class="btn" href="index.php?halaman=kamar">Lihat Kamar Kosong</a>
      </section>
    <?php endif; ?>
  </main>
</div>
<?php if ($halaman === 'ajukan' && $kamarDipilih): ?><script>
const hargaKamar = <?= json_encode((float) $kamarDipilih['harga_bulanan']) ?>;
const pilihanBulan = document.getElementById('bulan');
const totalTagihan = document.getElementById('total');
function perbaruiTotal(){ totalTagihan.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(hargaKamar * Number(pilihanBulan.value)); }
pilihanBulan.addEventListener('change', perbaruiTotal); perbaruiTotal();
</script><?php endif; ?>
</body>
</html>
