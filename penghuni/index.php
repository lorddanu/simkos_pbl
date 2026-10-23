<?php
// SIMKOS - Penghuni terdaftar (prototype PHP native, tanpa database)
// Jalankan: php -S localhost:8000 di dalam folder ini, lalu buka http://localhost:8000
require __DIR__ . '/db.php';
$user = require_login(); // wajib login, kalau belum diarahkan ke login.php

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Data contoh. Nanti ganti dengan data dari database (tabel kamar & pembayaran).
$kamar = $user['kamar'] ? ['nama' => $user['kamar'], 'status' => 'Aktif'] : null; // null = belum punya kamar
$tagihan = ['bulan' => 'November 2026', 'jumlah' => 650000, 'jatuh_tempo' => '15 Nov 2026'];

csrf_token();
$_SESSION['bukti'] = $_SESSION['bukti'] ?? null; // nama file bukti yang diunggah
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

function rupiah(int $n): string { return 'Rp ' . number_format($n, 0, ',', '.') . ',00'; }
function go(string $page): never { header('Location: index.php?page=' . $page); exit; }

// ---------- Proses form (POST) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Permintaan tidak valid.');
    }
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'unggah') {
        $f = $_FILES['bukti'] ?? null;
        if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
            $_SESSION['error'] = 'Pilih foto bukti pembayaran dulu.';
            go('upload');
        }
        if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 2 * 1024 * 1024) {
            $_SESSION['error'] = 'Gagal mengunggah. Ukuran foto maksimal 2 MB.';
            go('upload');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
        if (!$ext) {
            $_SESSION['error'] = 'Format harus JPG, PNG, atau WebP.';
            go('upload');
        }
        $nama = bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], $uploadDir . $nama)) {
            $_SESSION['error'] = 'Foto tidak bisa disimpan. Coba lagi.';
            go('upload');
        }
        $_SESSION['bukti'] = $nama;
        go('proof');
    }

    if ($aksi === 'saran') {
        $kategori = $_POST['kategori'] ?? '';
        $catatan = trim($_POST['catatan'] ?? '');
        // TODO: simpan $kategori dan $catatan ke database untuk dibaca admin.
        go('sent');
    }

    if ($aksi === 'reset') {
        if ($_SESSION['bukti']) @unlink($uploadDir . $_SESSION['bukti']);
        $_SESSION['bukti'] = null;
        go('dash');
    }
    go('dash');
}

// ---------- Tentukan halaman (GET) ----------
$sudahUnggah = $_SESSION['bukti'] !== null;
$page = $_GET['page'] ?? 'dash';
if (!in_array($page, ['dash', 'upload', 'proof', 'feedback', 'sent'], true)) $page = 'dash';
if ($page === 'upload' && $sudahUnggah) $page = 'proof';   // sudah unggah -> lihat bukti
if ($page === 'proof' && !$sudahUnggah) $page = 'upload';  // belum unggah -> form unggah

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
<link rel="stylesheet" href="style.css">
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
  <?php if ($page === 'dash' && !$kamar): ?>
    <div class="card">
      <h2 class="big">Halo, <?= h($user['nama']) ?></h2>
      <small>Kamu belum terdaftar di kamar mana pun. Hubungi admin/pemilik kos untuk mendapatkan kamar.</small>
    </div>
    <form method="post" action="logout.php">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <button class="btn alt" type="submit">Log out</button>
    </form>

  <?php elseif ($page === 'dash'): ?>
    <div class="card">
      <h2 class="big">Kamar Saya</h2>
      <div class="kamar"><span><?= h($kamar['nama']) ?></span><span class="aktif"><?= h($kamar['status']) ?></span></div>
    </div>

    <?php if (!$sudahUnggah): ?>
    <div class="card warn">
      <h2>Pengingat</h2>
      <small>Pembayaran November jatuh tempo <?= h($tagihan['jatuh_tempo']) ?></small>
    </div>
    <?php endif; ?>

    <div class="card">
      <h2>Status pembayaran bulan ini</h2>
      <div class="row">
        <span><b><?= h($tagihan['bulan']) ?></b><br><?= rupiah($tagihan['jumlah']) ?></span>
        <span class="badge <?= $sudahUnggah ? 'wait' : '' ?>"><?= $sudahUnggah ? 'Menunggu verifikasi' : 'Belum dibayar' ?></span>
      </div>
    </div>

    <?php if ($sudahUnggah): ?>
      <a class="btn alt" href="index.php?page=proof">Lihat bukti</a>
    <?php else: ?>
      <a class="btn" href="index.php?page=upload">Unggah bukti pembayaran</a>
    <?php endif; ?>

    <div class="card">
      <h2>Saran dan Kritik</h2>
      <small>Sampaikan masukan untuk admin/pemilik kos</small>
      <a class="btn inv" href="index.php?page=feedback">Ketik Saran dan Kritik</a>
    </div>

    <form method="post" action="logout.php">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <button class="btn alt" type="submit">Log out (<?= h($user['nama']) ?>)</button>
    </form>

  <?php elseif ($page === 'upload'): ?>
    <h2 class="t">Unggah bukti pembayaran</h2>
    <p class="s">Pembayaran <?= h($tagihan['bulan']) ?> - <?= rupiah($tagihan['jumlah']) ?></p>
    <form method="post" enctype="multipart/form-data" class="form">
      <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
      <input type="hidden" name="aksi" value="unggah">
      <label for="bukti">Foto bukti transfer (JPG, PNG, WebP, maks. 2 MB)</label>
      <input type="file" id="bukti" name="bukti" accept="image/jpeg,image/png,image/webp">
      <img id="pratinjau" alt="" hidden>
      <?php if ($error): ?><p class="err" role="alert"><?= h($error) ?></p><?php endif; ?>
      <div class="spacer"></div>
      <button class="btn" type="submit">Kirim bukti</button>
    </form>
    <script>
      // Pratinjau foto sebelum dikirim (opsional)
      document.getElementById('bukti').addEventListener('change', function (e) {
        var f = e.target.files[0], img = document.getElementById('pratinjau');
        if (!f) { img.hidden = true; return; }
        img.src = URL.createObjectURL(f); img.alt = 'Pratinjau bukti pembayaran'; img.hidden = false;
      });
    </script>

  <?php elseif ($page === 'proof'): ?>
    <h2 class="t">Bukti pembayaran</h2>
    <p class="s"><?= h($tagihan['bulan']) ?> - Menunggu verifikasi</p>
    <div class="proof"><img src="uploads/<?= h($_SESSION['bukti']) ?>" alt="Bukti pembayaran yang diunggah"></div>
    <div class="spacer"></div>
    <a class="btn" href="index.php?page=dash">Kembali ke Dashboard</a>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
      <button class="btn alt" name="aksi" value="reset" type="submit">Ulangi demo (hapus bukti)</button>
    </form>

  <?php elseif ($page === 'feedback'): ?>
    <h2 class="t">Saran &amp; Kritik</h2>
    <p class="s">Masukan Anda akan dikirim langsung ke admin/pemilik kos</p>
    <form method="post" class="form">
      <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
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