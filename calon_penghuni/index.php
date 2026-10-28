<?php
// Controller alur calon penghuni.
require __DIR__ . '/../database/helpers.php';

$user = require_login();
if ($user['role'] !== 'calon_penghuni') {
    header('Location: ../index.php');
    exit;
}

$halamanValid = ['dashboard', 'kamar', 'detail', 'ajukan', 'metode', 'keluhan', 'keluhan_terkirim', 'terkirim'];
$halaman = $_GET['halaman'] ?? 'dashboard';
if (!in_array($halaman, $halamanValid, true)) {
    $halaman = 'dashboard';
}

$pdo = db();
$kamarKosong = [];
if ($halaman === 'kamar') {
    $kamarKosong = $pdo->query(
        "SELECT id_kamar, nomor_kamar, harga_bulanan
           FROM kamar
          WHERE status = 'kosong'
          ORDER BY nomor_kamar"
    )->fetchAll();
}

$idKamar = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$kamarDipilih = null;
if (in_array($halaman, ['detail', 'ajukan', 'metode'], true)) {
    $st = $pdo->prepare(
        "SELECT id_kamar, nomor_kamar, harga_bulanan
           FROM kamar
          WHERE id_kamar = ? AND status = 'kosong'"
    );
    $st->execute([$idKamar]);
    $kamarDipilih = $st->fetch() ?: null;
    if (!$kamarDipilih) {
        header('Location: index.php?halaman=kamar');
        exit;
    }
}

$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);
$nilaiMulai = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['mulai'] ?? '') ? $_GET['mulai'] : '';
$nilaiSelesai = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['selesai'] ?? '') ? $_GET['selesai'] : '';
$jumlahBulan = filter_input(INPUT_GET, 'bulan', FILTER_VALIDATE_INT) ?: 1;
$jumlahBulan = max(1, min(12, $jumlahBulan));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'kirim_keluhan') {
    if (!csrf_ok()) {
        http_response_code(400);
        exit('Permintaan tidak valid.');
    }
    $isiKeluhan = trim($_POST['isi_keluhan'] ?? '');
    if ($isiKeluhan === '') {
        $error = 'Tulis keluhan atau masukan terlebih dahulu.';
        $halaman = 'keluhan';
    } elseif (empty($user['id_penghuni'])) {
        $error = 'Akun belum terhubung ke data penghuni.';
        $halaman = 'keluhan';
    } else {
        $st = $pdo->prepare('INSERT INTO keluhan (id_penghuni, isi_keluhan) VALUES (?, ?)');
        $st->execute([(int) $user['id_penghuni'], $isiKeluhan]);
        header('Location: index.php?halaman=keluhan_terkirim');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'kirim_bukti') {
    if (!csrf_ok()) {
        http_response_code(400);
        exit('Permintaan tidak valid.');
    }

    $idKamar = filter_input(INPUT_POST, 'id_kamar', FILTER_VALIDATE_INT) ?: 0;
    $nilaiMulai = trim($_POST['tanggal_mulai'] ?? '');
    $nilaiSelesai = trim($_POST['tanggal_selesai'] ?? '');
    $jumlahBulan = filter_input(INPUT_POST, 'bulan', FILTER_VALIDATE_INT) ?: 0;

    $st = $pdo->prepare(
        "SELECT id_kamar, nomor_kamar, harga_bulanan
           FROM kamar
          WHERE id_kamar = ? AND status = 'kosong'"
    );
    $st->execute([$idKamar]);
    $kamarDipilih = $st->fetch() ?: null;

    $tanggalMulai = DateTime::createFromFormat('!Y-m-d', $nilaiMulai);
    $tanggalSelesai = DateTime::createFromFormat('!Y-m-d', $nilaiSelesai);
    $tanggalHariIni = new DateTime('today');
    $bukti = $_FILES['bukti'] ?? null;
    $adaBukti = $bukti && $bukti['error'] !== UPLOAD_ERR_NO_FILE;
    $mimeBukti = ($adaBukti && $bukti['error'] === UPLOAD_ERR_OK)
        ? (new finfo(FILEINFO_MIME_TYPE))->file($bukti['tmp_name'])
        : null;
    $ekstensiBukti = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mimeBukti] ?? null;
    $buktiValid = !$adaBukti || (
        $bukti['error'] === UPLOAD_ERR_OK &&
        $bukti['size'] <= 5 * 1024 * 1024 &&
        $ekstensiBukti !== null
    );

    if (!$kamarDipilih) {
        $_SESSION['flash_error'] = 'Kamar sudah tidak tersedia. Silakan pilih kamar lain.';
        header('Location: index.php?halaman=kamar');
        exit;
    } elseif (
        !$tanggalMulai ||
        !$tanggalSelesai ||
        $tanggalMulai->format('Y-m-d') !== $nilaiMulai ||
        $tanggalSelesai->format('Y-m-d') !== $nilaiSelesai ||
        $tanggalMulai < $tanggalHariIni ||
        $tanggalSelesai <= $tanggalMulai ||
        $jumlahBulan < 1 ||
        $jumlahBulan > 12 ||
        !$buktiValid
    ) {
        $error = 'Periksa tanggal dan durasi sewa. Jika mengunggah bukti, gunakan JPG, PNG, atau WebP maksimal 5 MB.';
        $halaman = 'ajukan';
    } else {
        $namaBukti = null;
        if ($adaBukti) {
            $namaBukti = bin2hex(random_bytes(12)) . '.' . $ekstensiBukti;
            $folderBukti = __DIR__ . '/uploads';
            if (!is_dir($folderBukti) && !mkdir($folderBukti, 0755, true) && !is_dir($folderBukti)) {
                $error = 'Folder penyimpanan bukti tidak dapat dibuat.';
            } elseif (!move_uploaded_file($bukti['tmp_name'], $folderBukti . '/' . $namaBukti)) {
                $error = 'Bukti pembayaran gagal disimpan. Coba unggah lagi.';
            }
        }

        if ($error === '') {
            // Metadata pengajuan masih di sesi sampai tabel pengajuan tersedia.
            $_SESSION['pengajuan_sewa_demo'] = [
                'id_kamar' => $idKamar,
                'nomor_kamar' => $kamarDipilih['nomor_kamar'],
                'tanggal_mulai' => $nilaiMulai,
                'tanggal_selesai' => $nilaiSelesai,
                'bulan' => $jumlahBulan,
                'total' => (float) $kamarDipilih['harga_bulanan'] * $jumlahBulan,
                'bukti' => $namaBukti,
                'catatan' => trim($_POST['catatan'] ?? ''),
                'tanggal_kirim' => date('Y-m-d'),
            ];
            header('Location: index.php?halaman=terkirim');
            exit;
        }
    }
}

if ($halaman === 'terkirim') {
    $pengajuan = $_SESSION['pengajuan_sewa_demo'] ?? null;
    if (!$pengajuan) {
        header('Location: index.php');
        exit;
    }
} else {
    $pengajuan = null;
}

$mainClass = match ($halaman) {
    'kamar' => 'browse',
    'detail' => 'detail-page',
    'ajukan', 'metode', 'keluhan' => 'form-page',
    'terkirim' => 'confirm-page',
    default => '',
};

$urlKembali = match ($halaman) {
    'dashboard', 'terkirim' => null,
    'kamar' => 'index.php',
    'detail' => 'index.php?halaman=kamar',
    'ajukan' => 'index.php?halaman=detail&id=' . $idKamar,
    'metode' => 'index.php?halaman=ajukan&id=' . $idKamar . '&mulai=' . rawurlencode($nilaiMulai) . '&selesai=' . rawurlencode($nilaiSelesai) . '&bulan=' . $jumlahBulan,
    'keluhan' => 'index.php',
    'keluhan_terkirim' => 'index.php',
};

$urlNavigasi = match ($halaman) {
    'dashboard', 'terkirim' => 'index.php',
    'metode' => 'index.php?halaman=ajukan&id=' . $idKamar . '&mulai=' . rawurlencode($nilaiMulai) . '&selesai=' . rawurlencode($nilaiSelesai) . '&bulan=' . $jumlahBulan,
    default => 'index.php?halaman=ajukan&id=' . $idKamar . '&mulai=' . rawurlencode($nilaiMulai) . '&selesai=' . rawurlencode($nilaiSelesai) . '&bulan=' . $jumlahBulan,
};

require __DIR__ . '/views/layout.php';
