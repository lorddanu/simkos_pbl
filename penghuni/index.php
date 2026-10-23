<?php
// SIMKOS - Dashboard penghuni (PostgreSQL)
require __DIR__ . '/../database/helpers.php';
$user = require_login();

const HARI_JATUH_TEMPO = 15; // tagihan jatuh tempo tanggal 15 tiap bulan (sesuaikan)
const BULAN = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
               'Agustus', 'September', 'Oktober', 'November', 'Desember'];

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

function rupiah(int $n): string { return 'Rp ' . number_format($n, 0, ',', '.') . ',00'; }
function go(string $page): never { header('Location: index.php?page=' . $page); exit; }

$pdo = db();
$idPenghuni = $user['id_penghuni'] ? (int) $user['id_penghuni'] : null;

// ---------- Data: sewa aktif -> kamar ----------
$sewa = null;
if ($idPenghuni) {
    $st = $pdo->prepare(
        'SELECT s.id_sewa, k.nomor_kamar, k.harga_bulanan
           FROM sewa s JOIN kamar k ON k.id_kamar = s.id_kamar
          WHERE s.id_penghuni = ?
            AND (s.tanggal_selesai IS NULL OR s.tanggal_selesai >= CURRENT_DATE)
          ORDER BY s.tanggal_mulai DESC LIMIT 1'
    );
    $st->execute([$idPenghuni]);
    $sewa = $st->fetch() ?: null;
}

// ---------- Data: pembayaran bulan ini ----------
$periode = date('Y-m');
$bulanIni = BULAN[(int) date('n')] . ' ' . date('Y');
$jatuhTempo = HARI_JATUH_TEMPO . ' ' . substr(BULAN[(int) date('n')], 0, 3) . ' ' . date('Y');
$bayar = null;
if ($sewa) {
    $st = $pdo->prepare(
        'SELECT id_pembayaran, file_bukti, status_verifikasi
           FROM pembayaran WHERE id_sewa = ? AND periode = ?
          ORDER BY id_pembayaran DESC LIMIT 1'
    );
    $st->execute([$sewa['id_sewa'], $periode]);
    $bayar = $st->fetch() ?: null;
}
$sudahUnggah = $bayar && $bayar['status_verifikasi'] !== 'ditolak'; // ditolak -> boleh unggah ulang
$statusLabel = ['menunggu' => ['Menunggu verifikasi', 'wait'], 'diterima' => ['Lunas', 'ok'], 'ditolak' => ['Ditolak, unggah ulang', 'no']];
[$labelStatus, $kelasStatus] = $bayar ? $statusLabel[$bayar['status_verifikasi']] : ['Belum dibayar', ''];

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

// ---------- Proses form (POST) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { http_response_code(400); exit('Permintaan tidak valid.'); }
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'unggah' && $sewa && !$sudahUnggah) {
        $f = $_FILES['bukti'] ?? null;
        if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
            $_SESSION['error'] = 'Pilih foto bukti pembayaran dulu.'; go('upload');
        }
        if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 2 * 1024 * 1024) {
            $_SESSION['error'] = 'Gagal mengunggah. Ukuran foto maksimal 2 MB.'; go('upload');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
        if (!$ext) {
            $_SESSION['error'] = 'Format harus JPG, PNG, atau WebP.'; go('upload');
        }
        $nama = bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], $uploadDir . $nama)) {
            $_SESSION['error'] = 'Foto tidak bisa disimpan. Coba lagi.'; go('upload');
        }
        $ins = $pdo->prepare(
            'INSERT INTO pembayaran (id_sewa, jumlah, tanggal_bayar, file_bukti, tanggal_upload, periode)
             VALUES (?, ?, CURRENT_DATE, ?, NOW(), ?)'   // status_verifikasi otomatis "menunggu"
        );
        $ins->execute([$sewa['id_sewa'], $sewa['harga_bulanan'], $nama, $periode]);
        go('proof');
    }

    if ($aksi === 'saran' && $idPenghuni) {
        $kategori = $_POST['kategori'] ?? '';
        $catatan = trim($_POST['catatan'] ?? '');
        if (!in_array($kategori, ['Fasilitas Kos', 'Pelayanan Admin', 'Kebersihan', 'Lainnya'], true)) {
            $kategori = 'Lainnya';
        }
        $ins = $pdo->prepare('INSERT INTO keluhan (id_penghuni, isi_keluhan, kategori) VALUES (?, ?, ?)');
        $ins->execute([$idPenghuni, $catatan !== '' ? $catatan : '(tanpa catatan)', $kategori]);
        go('sent');
    }

    // Khusus demo: hapus bukti yang masih "menunggu" agar alur bisa diulang. Hapus blok ini di versi akhir.
    if ($aksi === 'reset' && $bayar && $bayar['status_verifikasi'] === 'menunggu') {
        $pdo->prepare('DELETE FROM pembayaran WHERE id_pembayaran = ?')->execute([$bayar['id_pembayaran']]);
        if ($bayar['file_bukti']) @unlink($uploadDir . basename($bayar['file_bukti']));
        go('dash');
    }
    go('dash');
}

// ---------- Tentukan halaman (GET) ----------
$page = $_GET['page'] ?? 'dash';
if (!in_array($page, ['dash', 'upload', 'proof', 'feedback', 'sent'], true)) $page = 'dash';
if (!$sewa && in_array($page, ['upload', 'proof'], true)) $page = 'dash';
if (!$idPenghuni && in_array($page, ['feedback', 'sent'], true)) $page = 'dash';
if ($page === 'upload' && $sudahUnggah) $page = 'proof';
if ($page === 'proof' && !$sudahUnggah) $page = 'upload';

$navAktif = match ($page) { 'upload', 'proof' => 'pay', 'sent', 'feedback' => 'feedback', default => 'dash' };
$kategoriList = ['Fasilitas Kos', 'Pelayanan Admin', 'Kebersihan', 'Lainnya'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SIMKOS - Penghuni</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="phone">
  <header>
    <?php if ($page !== 'dash'): ?>
      <a class="icon" href="index.php?page=dash" aria-label="Kembali">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M13 8l-4 4 4 4"/></svg>
      </a>
    <?php endif; ?>
    <h1>SIMKOS</h1>
    <span class="icon" aria-label="Akun">
      <svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm0 4a3 3 0 110 6 3 3 0 010-6zm0 13.2a7.2 7.2 0 01-5.6-2.7C6.5 14.8 10 14 12 14s5.5.8 5.6 2.5A7.2 7.2 0 0112 19.2z"/></svg>
    </span>
  </header>

  <main>
  <?php if ($page === 'dash' && !$sewa): ?>
    <div class="card">
      <h2 class="big">Halo, <?= h($user['nama'] ?? $user['email']) ?></h2>
      <small><?= $idPenghuni
          ? 'Kamu belum terdaftar di kamar mana pun. Hubungi admin/pemilik kos untuk mendapatkan kamar.'
          : 'Akun ini bukan akun penghuni.' ?></small>
    </div>
    <?php if ($idPenghuni): ?>
      <a class="btn" href="index.php?page=feedback">Kirim Saran dan Kritik</a>
    <?php endif; ?>
    <form method="post" action="../auth/logout.php">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <button class="btn alt" type="submit">Log out</button>
    </form>

  <?php elseif ($page === 'dash'): ?>
    <div class="card">
      <h2 class="big">Kamar Saya</h2>
      <div class="kamar"><span>Kamar <?= h($sewa['nomor_kamar']) ?></span><span class="aktif">Aktif</span></div>
    </div>

    <?php if (!$sudahUnggah): ?>
    <div class="card warn">
      <h2>Pengingat</h2>
      <small>Pembayaran <?= h(BULAN[(int) date('n')]) ?> jatuh tempo <?= h($jatuhTempo) ?></small>
    </div>
    <?php endif; ?>

    <div class="card">
      <h2>Status pembayaran bulan ini</h2>
      <div class="row">
        <span><b><?= h($bulanIni) ?></b><br><?= rupiah((int) $sewa['harga_bulanan']) ?></span>
        <span class="badge <?= h($kelasStatus) ?>"><?= h($labelStatus) ?></span>
      </div>
    </div>

    <?php if ($sudahUnggah): ?>
      <a class="btn alt" href="index.php?page=proof">Lihat bukti</a>
    <?php else: ?>
      <a class="btn" href="index.php?page=upload"><?= $bayar ? 'Unggah ulang bukti pembayaran' : 'Unggah bukti pembayaran' ?></a>
    <?php endif; ?>

    <div class="card">
      <h2>Saran dan Kritik</h2>
      <small>Sampaikan masukan untuk admin/pemilik kos</small>
      <a class="btn inv" href="index.php?page=feedback">Ketik Saran dan Kritik</a>
    </div>

    <form method="post" action="../auth/logout.php">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <button class="btn alt" type="submit">Log out (<?= h($user['nama'] ?? $user['email']) ?>)</button>
    </form>

  <?php elseif ($page === 'upload'): ?>
    <h2 class="t">Unggah bukti pembayaran</h2>
    <p class="s">Pembayaran <?= h($bulanIni) ?> - <?= rupiah((int) $sewa['harga_bulanan']) ?></p>
    <form method="post" enctype="multipart/form-data" class="form">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="aksi" value="unggah">
      <label for="bukti">Foto bukti transfer (JPG, PNG, WebP, maks. 2 MB)</label>
      <input type="file" id="bukti" name="bukti" accept="image/jpeg,image/png,image/webp">
      <img id="pratinjau" alt="" hidden>
      <?php if ($error): ?><p class="err" role="alert"><?= h($error) ?></p><?php endif; ?>
      <div class="spacer"></div>
      <button class="btn" type="submit">Kirim bukti</button>
    </form>
    <script>
      document.getElementById('bukti').addEventListener('change', function (e) {
        var f = e.target.files[0], img = document.getElementById('pratinjau');
        if (!f) { img.hidden = true; return; }
        img.src = URL.createObjectURL(f); img.alt = 'Pratinjau bukti pembayaran'; img.hidden = false;
      });
    </script>

  <?php elseif ($page === 'proof'): ?>
    <h2 class="t">Bukti pembayaran</h2>
    <p class="s"><?= h($bulanIni) ?> - <?= h($labelStatus) ?></p>
    <div class="proof"><img src="uploads/<?= h(basename($bayar['file_bukti'])) ?>" alt="Bukti pembayaran yang diunggah"></div>
    <div class="spacer"></div>
    <a class="btn" href="index.php?page=dash">Kembali ke Dashboard</a>
    <?php if ($bayar['status_verifikasi'] === 'menunggu'): ?>
    <form method="post"><!-- khusus demo -->
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <button class="btn alt" name="aksi" value="reset" type="submit">Ulangi demo (hapus bukti)</button>
    </form>
    <?php endif; ?>

  <?php elseif ($page === 'feedback'): ?>
    <h2 class="t">Saran &amp; Kritik</h2>
    <p class="s">Masukan Anda akan dikirim langsung ke admin/pemilik kos</p>
    <form method="post" class="form">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="aksi" value="saran">
      <label for="kat">Kategori</label>
      <select id="kat" name="kategori">
        <?php foreach ($kategoriList as $k): ?><option><?= h($k) ?></option><?php endforeach; ?>
      </select>
      <label for="cat">Catatan (opsional)</label>
      <textarea id="cat" name="catatan" placeholder="Tulis saran atau kritikmu di sini..."></textarea>
      <div class="spacer"></div>
      <button class="btn" type="submit">Kirim Masukan</button>
    </form>

  <?php elseif ($page === 'sent'): ?>
    <div class="done">
      <svg viewBox="0 0 64 64" width="64" height="64"><circle cx="32" cy="32" r="30" fill="#2ea043"/><path d="M18 33l9 9 19-20" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <h2 class="t">Masukan Terkirim</h2>
      <p class="s">Pesan Anda telah berhasil terkirim. Kami sangat menghargai waktu dan masukan yang Anda berikan.</p>
      <div class="note">Tim kami akan meninjau masukan Anda dalam 1x24 jam. Jika diperlukan, kami akan menghubungi Anda melalui email.</div>
    </div>
    <div class="spacer"></div>
    <a class="btn" href="index.php?page=dash">Kembali ke Beranda</a>
    <a class="btn alt" href="index.php?page=feedback">Kirim Masukan Lain</a>
  <?php endif; ?>
  </main>

  <nav aria-label="Menu utama">
    <a href="index.php?page=dash" <?= $navAktif === 'dash' ? 'aria-current="page"' : '' ?>>
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11l9-8 9 8v10H3z"/></svg>Dashboard</a>
    <a href="index.php?page=<?= $sudahUnggah ? 'proof' : 'upload' ?>" <?= $navAktif === 'pay' ? 'aria-current="page"' : '' ?>>
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="13" rx="2"/><circle cx="12" cy="12.5" r="3"/></svg>Unggah</a>
    <a href="index.php?page=feedback" <?= $navAktif === 'feedback' ? 'aria-current="page"' : '' ?>>
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v12H8l-4 4z"/></svg>Saran</a>
  </nav>
</div>
</body>
</html>