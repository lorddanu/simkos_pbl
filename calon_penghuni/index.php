<?php
// Controller alur calon penghuni.
require __DIR__ . '/../database/helpers.php';

$user = require_login();
if ($user['role'] !== 'calon_penghuni') {
    header('Location: ../index.php');
    exit;
}

$halamanValid = ['dashboard', 'kamar', 'detail', 'ajukan', 'terkirim'];
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
if (in_array($halaman, ['detail', 'ajukan'], true)) {
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
$nilaiMulai = '';
$nilaiSelesai = '';
$jumlahBulan = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'ajukan') {
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
        $jumlahBulan > 12
    ) {
        $error = 'Periksa kembali tanggal sewa dan jumlah bulan.';
        $halaman = 'ajukan';
    } else {
        // Prototipe: belum ada tabel pengajuan pada skema database.
        $_SESSION['pengajuan_sewa_demo'] = [
            'id_kamar' => $idKamar,
            'nomor_kamar' => $kamarDipilih['nomor_kamar'],
            'tanggal_mulai' => $nilaiMulai,
            'tanggal_selesai' => $nilaiSelesai,
            'bulan' => $jumlahBulan,
            'total' => (float) $kamarDipilih['harga_bulanan'] * $jumlahBulan,
        ];
        header('Location: index.php?halaman=terkirim');
        exit;
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
    'kamar', 'detail' => 'browse',
    'ajukan' => 'form-page',
    default => '',
};

$urlKembali = match ($halaman) {
    'dashboard', 'terkirim' => null,
    'kamar' => 'index.php',
    'detail' => 'index.php?halaman=kamar',
    'ajukan' => 'index.php?halaman=detail&id=' . $idKamar,
};

require __DIR__ . '/views/layout.php';
