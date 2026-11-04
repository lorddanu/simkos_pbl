<?php
// Controller alur calon penghuni.
require __DIR__ . '/../database/helpers.php';

$user = require_login();
if ($user['role'] !== 'calon_penghuni') {
    header('Location: ../index.php');
    exit;
}

$halamanValid = ['dashboard', 'kamar', 'detail', 'ajukan', 'pembayaran', 'metode', 'pembayaran_terkirim', 'keluhan', 'keluhan_terkirim', 'terkirim', 'notifikasi'];
$halaman = $_GET['halaman'] ?? 'dashboard';
if (!in_array($halaman, $halamanValid, true)) {
    $halaman = 'dashboard';
}

$pdo = db();
$kamarKosong = [];
if ($halaman === 'kamar') {
    $kamarKosong = $pdo->query(
        "SELECT k.id_kamar, k.nomor_kamar, k.harga_bulanan
           FROM kamar k
          WHERE k.status = 'kosong'
            AND NOT EXISTS (
                SELECT 1 FROM pengajuan_sewa ps
                 WHERE ps.id_kamar = k.id_kamar
                   AND ps.status IN ('menunggu_persetujuan', 'menunggu_pembayaran', 'menunggu_verifikasi', 'pembayaran_ditolak')
            )
          ORDER BY k.nomor_kamar"
    )->fetchAll();
}

$idKamar = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$kamarDipilih = null;
if (in_array($halaman, ['detail', 'ajukan'], true)) {
    $st = $pdo->prepare(
        "SELECT id_kamar, nomor_kamar, harga_bulanan
           FROM kamar
          WHERE id_kamar = ? AND status = 'kosong'
            AND NOT EXISTS (
                SELECT 1 FROM pengajuan_sewa ps
                 WHERE ps.id_kamar = kamar.id_kamar
                   AND ps.status IN ('menunggu_persetujuan', 'menunggu_pembayaran', 'menunggu_verifikasi', 'pembayaran_ditolak')
            )"
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'kirim_pengajuan') {
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
        $error = 'Periksa kembali tanggal dan durasi sewa.';
        $halaman = 'ajukan';
    } elseif (empty($user['id_penghuni'])) {
        $error = 'Data calon penghuni tidak terhubung ke akun.';
        $halaman = 'ajukan';
    } else {
        $hargaBulanan = (float) $kamarDipilih['harga_bulanan'];
        try {
            $st = $pdo->prepare(
                "INSERT INTO pengajuan_sewa
                    (id_penghuni, id_kamar, tanggal_mulai, tanggal_selesai, jumlah_bulan, harga_bulanan, total, catatan)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $st->execute([
                (int) $user['id_penghuni'], $idKamar, $nilaiMulai, $nilaiSelesai,
                $jumlahBulan, $hargaBulanan, $hargaBulanan * $jumlahBulan,
                trim($_POST['catatan'] ?? ''),
            ]);
            header('Location: index.php?halaman=terkirim');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                $error = 'Anda atau kamar ini sudah memiliki pengajuan aktif.';
            } else {
                throw $e;
            }
            $halaman = 'ajukan';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'kirim_pembayaran') {
    if (!csrf_ok()) {
        http_response_code(400);
        exit('Permintaan tidak valid.');
    }

    $idPengajuanBayar = filter_input(INPUT_POST, 'id_pengajuan', FILTER_VALIDATE_INT) ?: 0;
    $st = $pdo->prepare(
        "SELECT id_pengajuan, id_kamar, nomor_kamar, total, tanggal_mulai, status
           FROM pengajuan_sewa ps JOIN kamar k USING (id_kamar)
          WHERE id_pengajuan = ? AND id_penghuni = ?
            AND ps.status IN ('menunggu_pembayaran', 'pembayaran_ditolak')"
    );
    $st->execute([$idPengajuanBayar, (int) $user['id_penghuni']]);
    $pengajuanPembayaran = $st->fetch() ?: null;
    if (!$pengajuanPembayaran) {
        header('Location: index.php?halaman=notifikasi');
        exit;
    }

    $fileBukti = $_FILES['bukti'] ?? null;
    $adaBukti = $fileBukti && $fileBukti['error'] !== UPLOAD_ERR_NO_FILE;
    $mimeBukti = ($adaBukti && $fileBukti['error'] === UPLOAD_ERR_OK)
        ? (new finfo(FILEINFO_MIME_TYPE))->file($fileBukti['tmp_name'])
        : null;
    $ekstensiBukti = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mimeBukti] ?? null;

    if ($adaBukti && (
        $fileBukti['error'] !== UPLOAD_ERR_OK ||
        $fileBukti['size'] > 5 * 1024 * 1024 ||
        $ekstensiBukti === null
    )) {
        $error = 'Bukti harus berupa JPG, PNG, atau WebP dengan ukuran maksimal 5 MB.';
        $halaman = 'pembayaran';
    } else {
        $namaBukti = null;
        if ($adaBukti) {
            $folderBukti = __DIR__ . '/uploads';
            if (!is_dir($folderBukti) && !mkdir($folderBukti, 0755, true) && !is_dir($folderBukti)) {
                $error = 'Folder penyimpanan bukti tidak dapat dibuat.';
            } else {
                $namaBukti = bin2hex(random_bytes(12)) . '.' . $ekstensiBukti;
                if (!move_uploaded_file($fileBukti['tmp_name'], $folderBukti . '/' . $namaBukti)) {
                    $error = 'Bukti pembayaran gagal disimpan.';
                }
            }
        }

        if ($error === '') {
            try {
                $pdo->beginTransaction();
                $st = $pdo->prepare(
                    "SELECT status FROM pengajuan_sewa WHERE id_pengajuan = ? AND id_penghuni = ? FOR UPDATE"
                );
                $st->execute([$idPengajuanBayar, (int) $user['id_penghuni']]);
                $statusSaatIni = $st->fetchColumn();
                if (!in_array($statusSaatIni, ['menunggu_pembayaran', 'pembayaran_ditolak'], true)) {
                    $pdo->rollBack();
                    header('Location: index.php?halaman=notifikasi');
                    exit;
                }

                $st = $pdo->prepare(
                    "INSERT INTO pembayaran
                        (id_sewa, id_pengajuan_sewa, jumlah, tanggal_bayar, file_bukti, tanggal_upload, periode, catatan)
                     VALUES (NULL, ?, ?, CURRENT_DATE, ?, NOW(), ?, ?) RETURNING id_pembayaran"
                );
                $st->execute([
                    $idPengajuanBayar,
                    $pengajuanPembayaran['total'],
                    $namaBukti,
                    substr($pengajuanPembayaran['tanggal_mulai'], 0, 7),
                    trim($_POST['catatan'] ?? ''),
                ]);
                $idPembayaranBaru = (int) $st->fetchColumn();

                $st = $pdo->prepare(
                    "UPDATE pengajuan_sewa
                        SET status = 'menunggu_verifikasi', notifikasi_dibaca = FALSE, diperbarui_pada = NOW()
                      WHERE id_pengajuan = ?"
                );
                $st->execute([$idPengajuanBayar]);
                $pdo->commit();

                header('Location: index.php?halaman=pembayaran_terkirim&id=' . $idPembayaranBaru);
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }
        }
        $halaman = 'pembayaran';
    }
}

$pengajuan = null;
if (!empty($user['id_penghuni'])) {
    $st = $pdo->prepare(
        'SELECT ps.*, k.nomor_kamar
           FROM pengajuan_sewa ps JOIN kamar k USING (id_kamar)
          WHERE ps.id_penghuni = ?
          ORDER BY ps.dibuat_pada DESC, ps.id_pengajuan DESC LIMIT 1'
    );
    $st->execute([(int) $user['id_penghuni']]);
    $pengajuan = $st->fetch() ?: null;
}

if (in_array($halaman, ['terkirim', 'pembayaran', 'metode', 'pembayaran_terkirim'], true) && !$pengajuan) {
    header('Location: index.php');
    exit;
}
if (in_array($halaman, ['pembayaran', 'metode'], true) &&
    !in_array($pengajuan['status'] ?? '', ['menunggu_pembayaran', 'pembayaran_ditolak'], true)) {
    header('Location: index.php?halaman=notifikasi');
    exit;
}

$pembayaran = null;
if ($halaman === 'pembayaran_terkirim' && $pengajuan) {
    $idPembayaran = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
    $st = $pdo->prepare(
        "SELECT p.*, ps.nomor_kamar, p.jumlah AS total
           FROM pembayaran p JOIN pengajuan_sewa ps ON ps.id_pengajuan = p.id_pengajuan_sewa
          WHERE p.id_pembayaran = ? AND ps.id_pengajuan = ? AND ps.id_penghuni = ?"
    );
    $st->execute([$idPembayaran, $pengajuan['id_pengajuan'], (int) $user['id_penghuni']]);
    $pembayaran = $st->fetch() ?: null;
    if (!$pembayaran) {
        header('Location: index.php?halaman=notifikasi');
        exit;
    }
}

if ($halaman === 'notifikasi' && $pengajuan) {
    $st = $pdo->prepare('UPDATE pengajuan_sewa SET notifikasi_dibaca = TRUE WHERE id_pengajuan = ?');
    $st->execute([$pengajuan['id_pengajuan']]);
    $pengajuan['notifikasi_dibaca'] = true;
}
$adaNotifikasiBaru = $pengajuan && !$pengajuan['notifikasi_dibaca'];

$mainClass = match ($halaman) {
    'kamar' => 'browse',
    'detail' => 'detail-page',
    'ajukan', 'keluhan', 'notifikasi' => 'form-page',
    'pembayaran' => 'payment-upload-page',
    'metode' => 'payment-method-page',
    'terkirim' => 'confirm-page',
    'pembayaran_terkirim' => 'confirm-page payment-receipt-page',
    default => '',
};

$urlKembali = match ($halaman) {
    'dashboard', 'terkirim', 'notifikasi', 'metode', 'pembayaran_terkirim' => null,
    'kamar' => 'index.php',
    'detail' => 'index.php?halaman=kamar',
    'ajukan' => 'index.php?halaman=detail&id=' . $idKamar,
    'pembayaran' => 'index.php?halaman=notifikasi',
    'keluhan' => 'index.php',
    'keluhan_terkirim' => 'index.php',
};

$urlNavigasi = match ($halaman) {
    'dashboard', 'terkirim', 'metode', 'pembayaran_terkirim' => 'index.php',
    'pembayaran' => 'index.php?halaman=pembayaran',
    default => 'index.php?halaman=ajukan&id=' . $idKamar . '&mulai=' . rawurlencode($nilaiMulai) . '&selesai=' . rawurlencode($nilaiSelesai) . '&bulan=' . $jumlahBulan,
};

require __DIR__ . '/views/layout.php';
