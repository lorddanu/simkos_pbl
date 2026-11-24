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
// Endpoint timer mengubah pengajuan menjadi kedaluwarsa secara aman.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'expire_pengajuan') {
    header('Content-Type: application/json; charset=utf-8');
    if (!csrf_ok()) {
        http_response_code(400);
        echo json_encode(['ok' => false]);
        exit;
    }
    $idExpire = filter_input(INPUT_POST, 'id_pengajuan', FILTER_VALIDATE_INT) ?: 0;
    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare(
            "SELECT status,
                    (status = 'menunggu_pembayaran' AND (batas_pembayaran IS NULL OR batas_pembayaran <= NOW())) AS waktu_habis,
                    (status = 'pembayaran_ditolak' AND (batas_upload_ulang IS NULL OR batas_upload_ulang <= NOW())) AS unggah_ulang_habis
               FROM pengajuan_sewa
              WHERE id_pengajuan = ? AND id_penghuni = ? FOR UPDATE"
        );
        $st->execute([$idExpire, (int) $user['id_penghuni']]);
        $expireRow = $st->fetch();
        $alreadyExpired = $expireRow && $expireRow['status'] === 'kedaluwarsa';
        $statusBerlaku = $expireRow && in_array(
            $expireRow['status'],
            ['menunggu_pembayaran', 'pembayaran_ditolak'],
            true
        );

        $waktuHabis = $expireRow && (
            ($expireRow['status'] === 'menunggu_pembayaran'
                && in_array($expireRow['waktu_habis'], [true, 1, '1', 't'], true)) ||
            ($expireRow['status'] === 'pembayaran_ditolak'
                && in_array($expireRow['unggah_ulang_habis'], [true, 1, '1', 't'], true))
        );

        $expired = $alreadyExpired || ($statusBerlaku && $waktuHabis);

        if ($statusBerlaku && $waktuHabis) {
            demo_track_before_change('pengajuan_sewa', $idExpire);

            $st = $pdo->prepare(
                "UPDATE pengajuan_sewa
                    SET status = 'kedaluwarsa', diperbarui_pada = NOW()
                WHERE id_pengajuan = ?"
            );

            $st->execute([$idExpire]);
        }

        $expired = $alreadyExpired || $deadlinePassed;
        $pdo->commit();
        echo json_encode(['ok' => true, 'expired' => (bool) $expired]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Gagal memproses batas pembayaran: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['ok' => false]);
    }
    exit;
}

// Request berikutnya juga memberlakukan kedaluwarsa jika halaman sebelumnya ditinggalkan.
$stExpire = $pdo->prepare(
    "SELECT id_pengajuan FROM pengajuan_sewa
      WHERE id_penghuni = ? AND (
        (status = 'menunggu_pembayaran' AND (batas_pembayaran IS NULL OR batas_pembayaran <= NOW()))
        OR (status = 'pembayaran_ditolak' AND (batas_upload_ulang IS NULL OR batas_upload_ulang <= NOW()))
      )"
);
$stExpire->execute([(int) ($user['id_penghuni'] ?? 0)]);
foreach ($stExpire->fetchAll(PDO::FETCH_COLUMN) as $expiredId) {
    demo_track_before_change('pengajuan_sewa', (int) $expiredId);
}
$stExpire = $pdo->prepare(
    "UPDATE pengajuan_sewa SET status = 'kedaluwarsa', diperbarui_pada = NOW()
      WHERE id_penghuni = ? AND (
        (status = 'menunggu_pembayaran' AND batas_pembayaran IS NOT NULL AND batas_pembayaran <= NOW())
        OR (status = 'pembayaran_ditolak' AND batas_upload_ulang IS NOT NULL AND batas_upload_ulang <= NOW())
      )"
);
$stExpire->execute([(int) ($user['id_penghuni'] ?? 0)]);
$kamarKosong = [];
if ($halaman === 'kamar') {
    $kamarKosong = $pdo->query(
        "SELECT k.id_kamar, k.nomor_kamar, k.harga_bulanan, k.foto_kamar
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
        "SELECT id_kamar, nomor_kamar, harga_bulanan, foto_kamar
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
        $st = $pdo->prepare('INSERT INTO keluhan (id_penghuni, isi_keluhan) VALUES (?, ?) RETURNING id_keluhan');
        $st->execute([(int) $user['id_penghuni'], $isiKeluhan]);
        demo_track_insert('keluhan', (int) $st->fetchColumn());
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
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING id_pengajuan"
            );
            $st->execute([
                (int) $user['id_penghuni'], $idKamar, $nilaiMulai, $nilaiSelesai,
                $jumlahBulan, $hargaBulanan, $hargaBulanan * $jumlahBulan,
                trim($_POST['catatan'] ?? ''),
            ]);
            demo_track_insert('pengajuan_sewa', (int) $st->fetchColumn());
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
        "SELECT ps.id_pengajuan, ps.id_kamar, k.nomor_kamar, ps.total, ps.tanggal_mulai, ps.status, ps.batas_pembayaran, ps.batas_upload_ulang
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
    if (!$adaBukti) {
        $error = 'Bukti transfer wajib diunggah sebelum pembayaran dikirim.';
        $halaman = 'pembayaran';
    }
    $mimeBukti = ($adaBukti && $fileBukti['error'] === UPLOAD_ERR_OK)
        ? (new finfo(FILEINFO_MIME_TYPE))->file($fileBukti['tmp_name'])
        : null;
    $ekstensiBukti = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mimeBukti] ?? null;

    if ($error === '' && $adaBukti && (
        $fileBukti['error'] !== UPLOAD_ERR_OK ||
        $fileBukti['size'] > 5 * 1024 * 1024 ||
        $ekstensiBukti === null
    )) {
        $error = 'Bukti harus berupa JPG, PNG, atau WebP dengan ukuran maksimal 5 MB.';
        $halaman = 'pembayaran';
    } else {
        $namaBukti = null;
        $folderBukti = __DIR__ . '/uploads';

        if ($error === '') {
            try {
                $pdo->beginTransaction();
                $st = $pdo->prepare(
                    "SELECT status,
                            (status = 'menunggu_pembayaran' AND (batas_pembayaran IS NULL OR batas_pembayaran <= NOW())) AS waktu_habis,
                            (status = 'pembayaran_ditolak' AND (batas_upload_ulang IS NULL OR batas_upload_ulang <= NOW())) AS unggah_ulang_habis
                       FROM pengajuan_sewa WHERE id_pengajuan = ? AND id_penghuni = ? FOR UPDATE"
                );
                $st->execute([$idPengajuanBayar, (int) $user['id_penghuni']]);
                $barisStatus = $st->fetch();
                $statusSaatIni = $barisStatus['status'] ?? null;
                if (!in_array($statusSaatIni, ['menunggu_pembayaran', 'pembayaran_ditolak'], true)) {
                    $pdo->rollBack();
                    header('Location: index.php?halaman=notifikasi');
                    exit;
                }

                $deadlineHabis = ($statusSaatIni === 'menunggu_pembayaran' && in_array($barisStatus['waktu_habis'] ?? false, [true, 1, '1', 't'], true))
                    || ($statusSaatIni === 'pembayaran_ditolak' && in_array($barisStatus['unggah_ulang_habis'] ?? false, [true, 1, '1', 't'], true));
                if ($deadlineHabis) {
                    demo_track_before_change('pengajuan_sewa', $idPengajuanBayar);
                    $pdo->prepare("UPDATE pengajuan_sewa SET status = 'kedaluwarsa', diperbarui_pada = NOW() WHERE id_pengajuan = ?")->execute([$idPengajuanBayar]);
                    $pdo->commit();
                    header('Location: index.php?halaman=notifikasi');
                    exit;
                }

                if (!is_dir($folderBukti) && !mkdir($folderBukti, 0755, true) && !is_dir($folderBukti)) {
                    throw new RuntimeException('Folder penyimpanan bukti tidak dapat dibuat.');
                }
                $namaBukti = bin2hex(random_bytes(12)) . '.' . $ekstensiBukti;
                if (!move_uploaded_file($fileBukti['tmp_name'], $folderBukti . '/' . $namaBukti)) {
                    throw new RuntimeException('Bukti pembayaran gagal disimpan.');
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
                demo_track_insert('pembayaran', $idPembayaranBaru, $namaBukti ? $folderBukti . '/' . $namaBukti : null);

                demo_track_before_change('pengajuan_sewa', $idPengajuanBayar);
                $st = $pdo->prepare(
                    "UPDATE pengajuan_sewa
                        SET status = 'menunggu_verifikasi', batas_pembayaran = NULL, batas_upload_ulang = NULL, notifikasi_dibaca = FALSE, diperbarui_pada = NOW()
                      WHERE id_pengajuan = ?"
                );
                $st->execute([$idPengajuanBayar]);
                $pdo->commit();

                header('Location: index.php?halaman=pembayaran_terkirim&id=' . $idPembayaranBaru);
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                if ($namaBukti !== null && is_file($folderBukti . '/' . $namaBukti)) {
                    @unlink($folderBukti . '/' . $namaBukti);
                }
                throw $e;
            }
        }
        $halaman = 'pembayaran';
    }
}

$pengajuan = null;
if (!empty($user['id_penghuni'])) {
        $st = $pdo->prepare(
            'SELECT ps.*, k.nomor_kamar,
                    (EXTRACT(EPOCH FROM (ps.batas_pembayaran AT TIME ZONE \'Asia/Jakarta\')) * 1000)::BIGINT AS batas_pembayaran_ms,
                    (EXTRACT(EPOCH FROM (ps.batas_upload_ulang AT TIME ZONE \'Asia/Jakarta\')) * 1000)::BIGINT AS batas_upload_ulang_ms,
                    (EXTRACT(EPOCH FROM NOW()) * 1000)::BIGINT AS db_now_ms
            FROM pengajuan_sewa ps JOIN kamar k USING (id_kamar)
            WHERE ps.id_penghuni = ?
            ORDER BY ps.dibuat_pada DESC, ps.id_pengajuan DESC LIMIT 1'
        );
    $st->execute([(int) $user['id_penghuni']]);
    $pengajuan = $st->fetch() ?: null;
}

$batasPembayaranMs = (int) ($pengajuan['batas_pembayaran_ms'] ?? 0);
$batasUploadUlangMs = (int) ($pengajuan['batas_upload_ulang_ms'] ?? 0);
$batasAktifMs = ($pengajuan['status'] ?? '') === 'pembayaran_ditolak' ? $batasUploadUlangMs : $batasPembayaranMs;
$batasAktifLabel = '';
if ($batasAktifMs > 0) {
    $tanggalAktif = (new DateTimeImmutable('@' . (int) floor($batasAktifMs / 1000)))->setTimezone(new DateTimeZone('Asia/Jakarta'));
    $batasAktifLabel = $tanggalAktif->format('d M Y, H:i') . ' WIB';
}
$waktuServerMs = (int) ($pengajuan['db_now_ms'] ?? 0);
$batasPembayaranLabel = '';
if ($batasPembayaranMs > 0) {
    $tanggalBatas = (new DateTimeImmutable('@' . (int) floor($batasPembayaranMs / 1000)))->setTimezone(new DateTimeZone('Asia/Jakarta'));
    $batasPembayaranLabel = $tanggalBatas->format('d M Y, H:i') . ' WIB';
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
        "SELECT p.*, k.nomor_kamar, p.jumlah AS total
           FROM pembayaran p JOIN pengajuan_sewa ps ON ps.id_pengajuan = p.id_pengajuan_sewa
           JOIN kamar k ON k.id_kamar = ps.id_kamar
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
    demo_track_before_change('pengajuan_sewa', (int) $pengajuan['id_pengajuan']);
    $st = $pdo->prepare('UPDATE pengajuan_sewa SET notifikasi_dibaca = TRUE WHERE id_pengajuan = ?');
    $st->execute([$pengajuan['id_pengajuan']]);
    $pengajuan['notifikasi_dibaca'] = true;
}
$adaNotifikasiBaru = $pengajuan && !$pengajuan['notifikasi_dibaca'];

$mainClass = match ($halaman) {
    'dashboard' => 'dashboard-page',
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
