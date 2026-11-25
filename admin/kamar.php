<?php
require __DIR__ . '/../database/helpers.php';
$user = require_login();
if ($user['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak.');
}
$nama = $user['nama'] ?? 'Admin SIMKOS';
$pdo = db();

function validasiFotoKamar(?array $file): ?string {
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) return 'Foto gagal diunggah.';
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) return 'Ukuran foto maksimal 5 MB.';
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return 'File foto tidak valid.';
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || @getimagesize($file['tmp_name']) === false) return 'Foto harus JPG, PNG, atau WebP yang valid.';
    return null;
}
function simpanFotoKamar(array $file): string {
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'][$mime];
    $dir = __DIR__ . '/../uploads/kamar';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) throw new RuntimeException('Folder upload tidak dapat dibuat.');
    $nama = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $nama)) throw new RuntimeException('Foto gagal disimpan.');
    return 'uploads/kamar/' . $nama;
}
function hapusFotoKamar(?string $path): void {
    if (!$path || !preg_match('~^uploads/kamar/[a-f0-9]{32}\.(jpg|png|webp)$~', $path)) return;
    $target = __DIR__ . '/../' . $path;
    if (is_file($target)) @unlink($target);
}

$pesanAdmin = $_SESSION['flash_admin'] ?? '';
unset($_SESSION['flash_admin']);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_tambah_kamar'])) {
    if (!csrf_ok()) { http_response_code(400); exit('Permintaan tidak valid.'); }
    $nomor = trim($_POST['nomor_kamar'] ?? '');
    $harga = filter_var($_POST['harga_bulanan'] ?? null, FILTER_VALIDATE_INT);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $errFoto = validasiFotoKamar($_FILES['foto_kamar'] ?? null);
    if ($nomor === '' || mb_strlen($nomor) > 10 || $harga === false || $harga < 1 || mb_strlen($deskripsi) > 2000 || $errFoto) {
        $_SESSION['flash_admin'] = $errFoto ?: 'Periksa nomor kamar, harga, dan deskripsi (maksimal 2.000 karakter).';
    } else {
        $foto = null;
        try {
            if (!empty($_FILES['foto_kamar']['name'])) $foto = simpanFotoKamar($_FILES['foto_kamar']);
            $st = $pdo->prepare("INSERT INTO kamar (nomor_kamar,status,harga_bulanan,deskripsi,foto_kamar) VALUES (:n,'kosong',:h,:d,:f)");
            $st->execute([':n'=>$nomor, ':h'=>$harga, ':d'=>$deskripsi ?: null, ':f'=>$foto]);
            $_SESSION['flash_admin'] = 'Kamar berhasil ditambahkan.';
        } catch (Throwable $e) {
            hapusFotoKamar($foto);
            if ($e instanceof PDOException && $e->getCode() === '23505') $_SESSION['flash_admin'] = 'Nomor kamar tersebut sudah digunakan.';
            else { error_log($e->getMessage()); $_SESSION['flash_admin'] = 'Kamar gagal disimpan. Jalankan migrasi SQL dan periksa folder upload.'; }
        }
    }
    header('Location: kamar.php?tab=kamar'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_ubah_kamar'])) {
    if (!csrf_ok()) { http_response_code(400); exit('Permintaan tidak valid.'); }
    $id = filter_var($_POST['id_kamar'] ?? null, FILTER_VALIDATE_INT);
    $nomor = trim($_POST['nomor_kamar'] ?? '');
    $harga = filter_var($_POST['harga_bulanan'] ?? null, FILTER_VALIDATE_INT);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $errFoto = validasiFotoKamar($_FILES['foto_kamar'] ?? null);
    if (!$id || $nomor === '' || mb_strlen($nomor) > 10 || $harga === false || $harga < 1 || mb_strlen($deskripsi) > 2000 || $errFoto) {
        $_SESSION['flash_admin'] = $errFoto ?: 'Data kamar tidak valid. Periksa nomor, harga, dan deskripsi.';
    } else {
        $fotoBaru = null;
        try {
            $q = $pdo->prepare('SELECT foto_kamar FROM kamar WHERE id_kamar = ?');
            $q->execute([$id]);
            $fotoLama = $q->fetchColumn();
            if ($fotoLama === false) throw new RuntimeException('Kamar tidak ditemukan.');
            if (!empty($_FILES['foto_kamar']['name'])) $fotoBaru = simpanFotoKamar($_FILES['foto_kamar']);
            $sql = 'UPDATE kamar SET nomor_kamar=:n,harga_bulanan=:h,deskripsi=:d,foto_kamar=COALESCE(:f,foto_kamar) WHERE id_kamar=:id';
            $q = $pdo->prepare($sql);
            $q->execute([':n'=>$nomor, ':h'=>$harga, ':d'=>$deskripsi ?: null, ':f'=>$fotoBaru, ':id'=>$id]);
            if ($fotoBaru) hapusFotoKamar($fotoLama);
            $_SESSION['flash_admin'] = 'Data kamar berhasil diperbarui.';
        } catch (Throwable $e) {
            hapusFotoKamar($fotoBaru);
            if ($e instanceof PDOException && $e->getCode() === '23505') $_SESSION['flash_admin'] = 'Nomor kamar tersebut sudah digunakan.';
            else { error_log($e->getMessage()); $_SESSION['flash_admin'] = 'Data kamar gagal diperbarui. Periksa migrasi SQL dan folder upload.'; }
        }
    }
    header('Location: kamar.php?tab=kamar'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_ubah_status_kamar'])) {
    if (!csrf_ok()) {
        http_response_code(400);
        exit('Permintaan tidak valid.');
    }

    $idKamar = filter_var($_POST['id_kamar'] ?? null, FILTER_VALIDATE_INT);
    $statusInput = trim($_POST['status'] ?? '');
    $statusMap = [
        'Kosong' => 'kosong',
        'Terisi' => 'terisi',
        'Tidak tersedia' => 'tidak tersedia',
    ];
    $statusBaru = $statusMap[$statusInput] ?? null;

    if (!$idKamar || !$statusBaru) {
        $_SESSION['flash_admin'] = 'ID kamar atau status baru tidak valid.';
    } else {
        try {
            $st = $pdo->prepare('UPDATE kamar SET status = :status WHERE id_kamar = :id');
            $st->execute([':status' => $statusBaru, ':id' => $idKamar]);
            $_SESSION['flash_admin'] = $st->rowCount() > 0
                ? 'Status kamar berhasil diperbarui.'
                : 'Status kamar tidak berubah atau kamar tidak ditemukan.';
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $_SESSION['flash_admin'] = 'Status kamar gagal diperbarui. Periksa aturan status pada database.';
        }
    }

    header('Location: kamar.php?tab=kamar');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_hapus_kamar'])) {
    if (!csrf_ok()) {
        http_response_code(400);
        exit('Permintaan tidak valid.');
    }

    $idKamar = filter_var($_POST['id_kamar'] ?? null, FILTER_VALIDATE_INT);
    if (!$idKamar) {
        $_SESSION['flash_admin'] = 'Kamar tidak valid.';
    } else {
        try {
            $cek = $pdo->prepare("SELECT status, foto_kamar FROM kamar WHERE id_kamar = :id FOR UPDATE");
            $cek->execute([':id' => $idKamar]);
            $dataKamarHapus = $cek->fetch(PDO::FETCH_ASSOC);
            if (!$dataKamarHapus) {
                $_SESSION['flash_admin'] = 'Kamar tidak ditemukan.';
            } elseif ($dataKamarHapus['status'] !== 'kosong') {
                $_SESSION['flash_admin'] = 'Hanya kamar berstatus kosong yang dapat dihapus.';
            } else {
                $st = $pdo->prepare('DELETE FROM kamar WHERE id_kamar = :id_kamar');
                $st->execute([':id_kamar' => $idKamar]);
                if ($st->rowCount() > 0) {
                    hapusFotoKamar($dataKamarHapus['foto_kamar']);
                    $_SESSION['flash_admin'] = 'Kamar berhasil dihapus.';
                } else {
                    $_SESSION['flash_admin'] = 'Kamar tidak ditemukan.';
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23503') {
                $_SESSION['flash_admin'] = 'Kamar tidak dapat dihapus karena sudah terhubung dengan pengajuan atau riwayat sewa.';
            } else {
                error_log($e->getMessage());
                $_SESSION['flash_admin'] = 'Kamar gagal dihapus.';
            }
        }
    }

    header('Location: kamar.php?tab=kamar');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_akhiri_sewa'])) {
    if (!csrf_ok()) { http_response_code(400); exit('Permintaan tidak valid.'); }

    $idSewa = filter_var($_POST['id_sewa'] ?? null, FILTER_VALIDATE_INT);
    $tanggalSelesai = trim($_POST['tanggal_selesai'] ?? '');
    $tanggalValid = DateTime::createFromFormat('!Y-m-d', $tanggalSelesai);
    $tanggalHariIni = new DateTime('today');

    if (!$idSewa || !$tanggalValid || $tanggalValid->format('Y-m-d') !== $tanggalSelesai || $tanggalValid > $tanggalHariIni) {
        $_SESSION['flash_admin'] = 'Data pengakhiran sewa tidak valid. Tanggal selesai tidak boleh melewati hari ini.';
    } else {
        try {
            $pdo->beginTransaction();
            $st = $pdo->prepare(
                "SELECT s.id_sewa, s.id_kamar, s.id_penghuni, s.tanggal_mulai, s.tanggal_selesai
                   FROM sewa s
                  WHERE s.id_sewa = ?
                  FOR UPDATE"
            );
            $st->execute([$idSewa]);
            $sewa = $st->fetch(PDO::FETCH_ASSOC);

            if (!$sewa || ($sewa['tanggal_selesai'] !== null && $sewa['tanggal_selesai'] < date('Y-m-d'))) {
                throw new RuntimeException('Data sewa aktif tidak ditemukan atau sudah berakhir.');
            }
            if ($tanggalSelesai < $sewa['tanggal_mulai']) {
                throw new RuntimeException('Tanggal selesai tidak boleh lebih awal dari tanggal mulai sewa.');
            }

            $st = $pdo->prepare("UPDATE sewa SET tanggal_selesai = ? WHERE id_sewa = ?");
            $st->execute([$tanggalSelesai, $idSewa]);

            $st = $pdo->prepare(
                "SELECT COUNT(*) FROM sewa
                  WHERE id_kamar = ?
                    AND (tanggal_selesai IS NULL OR tanggal_selesai >= CURRENT_DATE)
                    AND id_sewa <> ?"
            );
            $st->execute([(int) $sewa['id_kamar'], $idSewa]);
            if ((int) $st->fetchColumn() === 0) {
                $pdo->prepare("UPDATE kamar SET status = 'kosong' WHERE id_kamar = ?")->execute([(int) $sewa['id_kamar']]);
            }

            $st = $pdo->prepare(
                "SELECT COUNT(*) FROM sewa
                  WHERE id_penghuni = ?
                    AND (tanggal_selesai IS NULL OR tanggal_selesai >= CURRENT_DATE)
                    AND id_sewa <> ?"
            );
            $st->execute([(int) $sewa['id_penghuni'], $idSewa]);
            if ((int) $st->fetchColumn() === 0) {
                $pdo->prepare("UPDATE penghuni SET status = 'nonaktif' WHERE id_penghuni = ?")->execute([(int) $sewa['id_penghuni']]);
            }

            $pdo->commit();
            $_SESSION['flash_admin'] = 'Sewa berhasil diakhiri. Riwayat pembayaran dan data penghuni tetap disimpan.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash_admin'] = $e instanceof RuntimeException ? $e->getMessage() : 'Sewa gagal diakhiri.';
            if (!$e instanceof RuntimeException) error_log($e->getMessage());
        }
    }

    header('Location: kamar.php?tab=penghuni');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_pengajuan'])) {
    if (!csrf_ok()) {
        http_response_code(400);
        exit('Permintaan tidak valid.');
    }

    $idPengajuan = filter_input(INPUT_POST, 'id_pengajuan', FILTER_VALIDATE_INT) ?: 0;
    $aksiPengajuan = $_POST['aksi_pengajuan'];
    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare(
            "SELECT ps.id_pengajuan, ps.id_kamar, ps.status, k.status AS status_kamar
               FROM pengajuan_sewa ps JOIN kamar k USING (id_kamar)
              WHERE ps.id_pengajuan = ? FOR UPDATE OF ps, k"
        );
        $st->execute([$idPengajuan]);
        $rowPengajuan = $st->fetch();

        if (!$rowPengajuan || $rowPengajuan['status'] !== 'menunggu_persetujuan') {
            throw new RuntimeException('Pengajuan sudah diproses atau tidak ditemukan.');
        }
        if ($aksiPengajuan === 'setujui') {
            if ($rowPengajuan['status_kamar'] !== 'kosong') {
                throw new RuntimeException('Kamar sudah tidak tersedia.');
            }
            demo_track_before_change('pengajuan_sewa', $idPengajuan);
            $st = $pdo->prepare(
                "UPDATE pengajuan_sewa
                    SET status = 'menunggu_pembayaran', ditinjau_oleh = ?, ditinjau_pada = NOW(),
                        batas_pembayaran = NOW() + INTERVAL '24 hours', batas_upload_ulang = NULL,
                        catatan_admin = NULL, notifikasi_dibaca = FALSE, diperbarui_pada = NOW()
                WHERE id_pengajuan = ?"
            );
            $st->execute([(int) $user['id_akun'], $idPengajuan]);
            $_SESSION['flash_admin'] = 'Pengajuan disetujui. Calon penghuni dapat mengajukan pembayaran.';
        } elseif ($aksiPengajuan === 'tolak') {
            $alasan = trim($_POST['catatan_admin'] ?? '');
            demo_track_before_change('pengajuan_sewa', $idPengajuan);
            $st = $pdo->prepare(
                "UPDATE pengajuan_sewa
                    SET status = 'ditolak', ditinjau_oleh = ?, ditinjau_pada = NOW(),
                        batas_pembayaran = NULL, catatan_admin = ?, notifikasi_dibaca = FALSE, diperbarui_pada = NOW()
                  WHERE id_pengajuan = ?"
            );
            $st->execute([(int) $user['id_akun'], $alasan !== '' ? $alasan : null, $idPengajuan]);
            $_SESSION['flash_admin'] = 'Pengajuan ditolak dan calon penghuni diberi notifikasi.';
        } else {
            throw new RuntimeException('Aksi pengajuan tidak dikenal.');
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash_admin'] = $e instanceof RuntimeException ? $e->getMessage() : 'Pengajuan gagal diproses.';
        if (!$e instanceof RuntimeException) error_log($e->getMessage());
    }
    header('Location: kamar.php?tab=penghuni');
    exit;
}

$kamarList = $pdo->query('SELECT id_kamar, nomor_kamar, status, harga_bulanan, deskripsi, foto_kamar FROM kamar ORDER BY nomor_kamar')->fetchAll();
$totalKamar = count($kamarList);
$jumlahKamarKosong = count(array_filter($kamarList, static fn($k) => $k['status'] === 'kosong'));
$st = $pdo->query(
    "SELECT ps.*, p.nama, a.email, k.nomor_kamar
       FROM pengajuan_sewa ps
       JOIN penghuni p USING (id_penghuni)
       JOIN akun a USING (id_penghuni)
       JOIN kamar k USING (id_kamar)
      WHERE ps.status = 'menunggu_persetujuan'
      ORDER BY ps.dibuat_pada"
);
$pengajuanMasuk = $st->fetchAll();
$calonPenghuni = $pdo->query(
    "SELECT ps.id_pengajuan, ps.id_kamar, ps.status, ps.total, ps.tanggal_mulai,
            ps.tanggal_selesai, p.nama, a.email, k.nomor_kamar
       FROM pengajuan_sewa ps
       JOIN penghuni p USING (id_penghuni)
       JOIN akun a USING (id_penghuni)
       JOIN kamar k USING (id_kamar)
      WHERE ps.status IN ('menunggu_pembayaran', 'menunggu_verifikasi', 'pembayaran_ditolak')
      ORDER BY ps.dibuat_pada DESC"
)->fetchAll();
$calonPerKamar = [];
foreach ($calonPenghuni as $calon) {
    $calonPerKamar[(int) $calon['id_kamar']] = $calon;
}
$riwayatKamar = $pdo->query(
    "SELECT s.id_kamar, p.nama, s.tanggal_mulai, s.tanggal_selesai
       FROM sewa s JOIN penghuni p USING (id_penghuni)
      WHERE s.tanggal_selesai IS NOT NULL AND s.tanggal_selesai < CURRENT_DATE
      ORDER BY s.tanggal_selesai DESC"
)->fetchAll();
$penghuniAktif = $pdo->query(
    "SELECT s.id_sewa, s.id_kamar, p.id_penghuni, p.nama, a.email, k.nomor_kamar, s.tanggal_mulai
       FROM sewa s JOIN penghuni p USING (id_penghuni)
       JOIN akun a USING (id_penghuni) JOIN kamar k USING (id_kamar)
      WHERE s.tanggal_selesai IS NULL OR s.tanggal_selesai >= CURRENT_DATE
      ORDER BY p.nama"
)->fetchAll();

// Ambil seluruh riwayat sewa dan pembayaran, termasuk penghuni yang sudah tidak aktif.
$daftarRiwayatPenghuni = $pdo->query(
    "SELECT DISTINCT p.id_penghuni, p.nama
       FROM penghuni p JOIN sewa s USING (id_penghuni)
       ORDER BY p.nama"
)->fetchAll();

$riwayatSewaPenghuni = $pdo->query(
    "SELECT s.id_penghuni, k.nomor_kamar, s.tanggal_mulai, s.tanggal_selesai
       FROM sewa s
       JOIN kamar k USING (id_kamar)
      ORDER BY s.tanggal_mulai DESC"
)->fetchAll();

$riwayatBayarPenghuni = $pdo->query(
    "SELECT COALESCE(ps.id_penghuni, s.id_penghuni) AS id_penghuni,
            p.id_pembayaran, p.jumlah, p.tanggal_bayar, p.tanggal_upload,
            p.periode, p.file_bukti
       FROM pembayaran p
       LEFT JOIN pengajuan_sewa ps ON ps.id_pengajuan = p.id_pengajuan_sewa
       LEFT JOIN sewa s ON s.id_sewa = p.id_sewa
      WHERE p.status_verifikasi = 'diterima'
        AND COALESCE(ps.id_penghuni, s.id_penghuni) IS NOT NULL
      ORDER BY COALESCE(p.tanggal_bayar, p.tanggal_upload::date) DESC,
               p.id_pembayaran DESC"
)->fetchAll();

$sewaPerPenghuni = [];
foreach ($riwayatSewaPenghuni as $item) {
    $sewaPerPenghuni[(int) $item['id_penghuni']][] = $item;
}
$bayarPerPenghuni = [];
foreach ($riwayatBayarPenghuni as $item) {
    $bayarPerPenghuni[(int) $item['id_penghuni']][] = $item;
}


$halamanAktif = 'data';
$tabPenghuniAktif = ($_GET['tab'] ?? '') === 'penghuni';

include __DIR__ . '/../includes/header.php';
?>

<main class="admin-main">

    <!-- DATA SIMKOS -->
    <section id="data" class="page-section active">

        <?php if ($pesanAdmin): ?><p class="admin-feedback" role="status"><?= h($pesanAdmin) ?></p><?php endif; ?>

        <!-- TAB DATA -->
        <div class="data-tabs">
            <button
                type="button"
                class="data-tab <?= $tabPenghuniAktif ? '' : 'active' ?>"
                data-data-tab="kamar"
            >
                Kamar
            </button>

            <button
                type="button"
                class="data-tab <?= $tabPenghuniAktif ? 'active' : '' ?>"
                data-data-tab="penghuni"
            >
                Penghuni
            </button>
        </div>

        <!-- TAB KAMAR -->
        <div id="tab-kamar" class="data-tab-content" <?= $tabPenghuniAktif ? 'hidden' : '' ?>>

            <!-- RINGKASAN -->
            <div class="kamar-stats">
                <article class="kamar-stat-card">
                    <span class="kamar-stat-number"><?= $totalKamar ?></span>
                    <span>Total Kamar</span>
                </article>

                <article class="kamar-stat-card">
                    <span class="kamar-stat-number"><?= $jumlahKamarKosong ?></span>
                    <span>Kosong</span>
                </article>
            </div>

            <!-- TAMBAH KAMAR -->
            <button
                type="button"
                class="btn-tambah-kamar"
                id="btnTambahKamar"
            >
                + Tambah Kamar
            </button>

            <!-- DAFTAR KAMAR -->
            <div class="daftar-kamar">
                <?php if (!$kamarList): ?><p>Belum ada data kamar.</p><?php endif; ?>
                <?php foreach ($kamarList as $kamar): ?>
                    <article class="kamar-card" data-id="<?= (int) $kamar['id_kamar'] ?>" data-deskripsi="<?= h($kamar['deskripsi'] ?? '') ?>" data-status="<?= h($kamar['status']) ?>">
                        <?php if (!empty($kamar['foto_kamar'])): ?><img class="kamar-card-photo" src="../<?= h($kamar['foto_kamar']) ?>" alt="Foto kamar <?= h($kamar['nomor_kamar']) ?>"><?php endif; ?>
                        <div class="kamar-card-header">
                            <div><h3><?= h($kamar['nomor_kamar']) ?></h3><p>Rp <?= h(number_format((float) $kamar['harga_bulanan'], 0, ',', '.')) ?>/bulan</p></div>
                            <strong class="kamar-status <?= h(str_replace(' ', '-', $kamar['status'])) ?>"><?= h(ucfirst($kamar['status'])) ?></strong>
                        </div>
                        <?php if (!empty($kamar['deskripsi'])): ?><p class="kamar-description"><?= h($kamar['deskripsi']) ?></p><?php endif; ?>
                        <?php if (isset($calonPerKamar[(int) $kamar['id_kamar']])): ?>
                            <small class="kamar-occupant"><?= h($calonPerKamar[(int) $kamar['id_kamar']]['nama']) ?> (calon penghuni)</small>
                        <?php elseif ($kamar['status'] === 'terisi'): ?>
                            <?php
                            $namaPenghuniKamar = array_values(array_filter($penghuniAktif, static fn($p) => $p['nomor_kamar'] === $kamar['nomor_kamar']));
                            ?>
                            <?php if ($namaPenghuniKamar): ?><small class="kamar-occupant"><?= h($namaPenghuniKamar[0]['nama']) ?></small><?php endif; ?>
                        <?php endif; ?>
                        <div class="kamar-card-actions">
                            <button type="button" class="btn-kamar" data-ubah-kamar>Ubah</button>
                            <?php if ($kamar['status'] !== 'terisi'): ?><button type="button" class="btn-kamar" data-ubah-status>Ubah Status</button><?php endif; ?>
                            <?php if (isset($calonPerKamar[(int) $kamar['id_kamar']])): ?><a class="btn-kamar" href="pembayaran.php">Lihat Pembayaran</a><?php elseif ($kamar['status'] === 'terisi'): ?><button type="button" class="btn-kamar" data-riwayat-kamar>Riwayat Penghuni</button><?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

        </div>

        <!-- TAB PENGHUNI -->
        <div id="tab-penghuni" class="data-tab-content" <?= $tabPenghuniAktif ? '' : 'hidden' ?>>>

            <h2 class="section-title" id="pengajuan-masuk">Pengajuan Sewa Masuk</h2>
            <?php if (!$pengajuanMasuk): ?><p>Belum ada pengajuan sewa masuk.</p><?php endif; ?>
            <?php foreach ($pengajuanMasuk as $pengajuan): ?>
                <article class="admin-application-card">
                    <div><strong><?= h($pengajuan['nama']) ?> Â· Kamar <?= h($pengajuan['nomor_kamar']) ?></strong><small><?= h($pengajuan['email']) ?></small></div>
                    <p><?= h($pengajuan['tanggal_mulai']) ?> sampai <?= h($pengajuan['tanggal_selesai']) ?> Â· <?= (int) $pengajuan['jumlah_bulan'] ?> bulan</p>
                    <p>Total: Rp <?= h(number_format((float) $pengajuan['total'], 0, ',', '.')) ?></p>
                    <?php if (!empty($pengajuan['catatan'])): ?><p><?= h($pengajuan['catatan']) ?></p><?php endif; ?>
                    <form method="post" class="admin-application-actions">
                        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="id_pengajuan" value="<?= (int) $pengajuan['id_pengajuan'] ?>">
                        <button class="btn-primary" name="aksi_pengajuan" value="setujui">Setujui</button>
                        <input type="text" name="catatan_admin" maxlength="500" placeholder="Alasan penolakan (opsional)">
                        <button class="btn-secondary" name="aksi_pengajuan" value="tolak">Tolak</button>
                    </form>
                </article>
            <?php endforeach; ?>

            <h2 class="section-title">Calon Penghuni</h2>
            <?php if (!$calonPenghuni): ?><p>Belum ada calon penghuni yang menunggu pembayaran atau verifikasi.</p><?php endif; ?>
            <?php foreach ($calonPenghuni as $calon): ?>
                <article class="admin-application-card">
                    <div><strong><?= h($calon['nama']) ?></strong><span>Kamar <?= h($calon['nomor_kamar']) ?></span></div>
                    <small><?= h($calon['email']) ?></small>
                    <p>Status: <?= h(str_replace('_', ' ', ucfirst($calon['status']))) ?></p>
                    <p>Periode: <?= h($calon['tanggal_mulai']) ?> – <?= h($calon['tanggal_selesai']) ?></p>
                    <p>Total: Rp <?= h(number_format((float) $calon['total'], 0, ',', '.')) ?></p>
                    <a class="btn-kamar" href="pembayaran.php">Lihat Pembayaran</a>
                </article>
            <?php endforeach; ?>

            <h2 class="section-title">Penghuni Aktif</h2>
            <?php if (!$penghuniAktif): ?><p>Belum ada penghuni aktif.</p><?php endif; ?>
            <?php foreach ($penghuniAktif as $aktif): ?>
                <article class="admin-application-card">
                    <div><strong><?= h($aktif['nama']) ?></strong><span>Kamar <?= h($aktif['nomor_kamar']) ?></span></div>
                    <small><?= h($aktif['email']) ?> · Mulai <?= h($aktif['tanggal_mulai']) ?></small>
                    <div class="kamar-card-actions">
                        <a class="btn-kamar" href="penghuni.php">Ubah Data</a>
                        <button type="button" class="btn-kamar" data-riwayat-penghuni data-id-penghuni="<?= (int) $aktif['id_penghuni'] ?>">Riwayat Penghuni</button>
                        <button type="button" class="btn-kamar" data-akhiri-sewa data-id-sewa="<?= (int) $aktif['id_sewa'] ?>" data-nama-penghuni="<?= h($aktif['nama']) ?>" data-nomor-kamar="<?= h($aktif['nomor_kamar']) ?>" data-tanggal-mulai="<?= h($aktif['tanggal_mulai']) ?>">Akhiri Sewa</button>
                    </div>
                </article>
            <?php endforeach; ?>

        </div>

    </section>

    <!-- HALAMAN TAMBAH KAMAR -->
    <section id="tambah-kamar" class="page-section">
        <div class="kamar-form-content">
            <h2>Tambah Kamar Baru</h2>

            <form id="formTambahKamar" method="post" action="kamar.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="aksi_tambah_kamar" value="1">

                <label for="tambahNomor">Nomor / Nama Kamar</label>
                <input
                    type="text"
                    id="tambahNomor"
                    name="nomor_kamar"
                    placeholder="Masukkan nomor atau nama kamar"
                    required
                >

                <label for="tambahHarga">Harga Sewa per Bulan (Rp)</label>
                <input
                    type="number"
                    id="tambahHarga"
                    name="harga_bulanan"
                    placeholder="Masukkan harga sewa"
                    min="1"
                    required
                >

                <label for="tambahDeskripsi">Deskripsi Kamar</label>
                <textarea id="tambahDeskripsi" name="deskripsi" maxlength="2000" placeholder="Tuliskan deskripsi kamar"></textarea>
                <label for="tambahFoto">Foto Kamar</label>
                <input type="file" id="tambahFoto" name="foto_kamar" accept="image/jpeg,image/png,image/webp">
                <small class="form-hint">JPG/PNG/WebP, maksimal 5 MB.</small>


                <button type="submit" class="btn-primary">
                    Simpan Kamar
                </button>

                <button
                    type="button"
                    class="btn-secondary"
                    data-kembali-kamar
                >
                    Batal
                </button>

            </form>
        </div>
    </section>

    <!-- HALAMAN UBAH DATA KAMAR -->
    <section id="ubah-kamar" class="page-section">
        <div class="kamar-form-content">
            <h2>Ubah Data Kamar</h2>

            <form id="formUbahKamar" method="post" action="kamar.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="aksi_ubah_kamar" value="1">

                <input type="hidden" id="ubahId" name="id_kamar">

                <label for="ubahNomor">Nomor / Nama Kamar</label>
                <input
                    type="text"
                    id="ubahNomor"
                    name="nomor_kamar"
                    placeholder="Masukkan nomor atau nama kamar"
                    required
                >

                <label for="ubahHarga">Harga Sewa per Bulan (Rp)</label>
                <input
                    type="number"
                    id="ubahHarga"
                    name="harga_bulanan"
                    placeholder="Masukkan harga sewa"
                    min="1"
                    required
                >

                <label for="ubahDeskripsi">Deskripsi Kamar</label>
                <textarea id="ubahDeskripsi" name="deskripsi" maxlength="2000" placeholder="Deskripsi kamar"></textarea>
                <label for="ubahFoto">Foto Kamar</label>
                <input type="file" id="ubahFoto" name="foto_kamar" accept="image/jpeg,image/png,image/webp">
                <small class="form-hint">Pilih foto baru jika ingin mengganti foto lama.</small>


                <button type="button" class="btn-hapus-kamar" id="btnHapusDariEdit" hidden>Hapus Kamar</button>

                <button type="submit" class="btn-primary">
                    Simpan Perubahan
                </button>

                <button
                    type="button"
                    class="btn-secondary"
                    data-kembali-kamar
                >
                    Batal
                </button>

            </form>
        </div>
    </section>

    <!-- POPUP KONFIRMASI HAPUS -->
    <div class="popup-overlay" id="popupHapusKamar" hidden>
        <div class="popup-hapus">
            <h3>Hapus Kamar?</h3>
            <p id="teksKonfirmasiHapus">Yakin ingin menghapus kamar ini?</p>
            <form method="post" action="kamar.php">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="aksi_hapus_kamar" value="1">
                <input type="hidden" name="id_kamar" id="hapusIdKamar">
                <div class="popup-hapus-actions">
                    <button type="button" id="btnBatalHapus">Batal</button>
                    <button type="submit" id="btnKonfirmasiHapus">Hapus</button>
                </div>
            </form>
        </div>
    </div>

    <!-- HALAMAN UBAH STATUS KAMAR -->
    <section id="ubah-status-kamar" class="page-section">
        <div class="kamar-form-content">

            <h2>Ubah Status Kamar</h2>

            <!-- INFORMASI KAMAR -->
            <div class="status-kamar-info">
                <div>
                    <h3 id="statusNomorKamar">-</h3>
                    <p>-</p>
                </div>

                <div>
                    <small>Status saat ini</small>
                    <strong id="statusSaatIni">-</strong>
                </div>
            </div>

            <form id="formUbahStatus" method="post" action="kamar.php">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="aksi_ubah_status_kamar" value="1">

                <input
                    type="hidden"
                    id="statusIdKamar"
                    name="id_kamar"
                >

                <p class="form-label">Pilih Status Baru</p>

                <label class="status-option">
                    <input
                        type="radio"
                        name="status"
                        value="Kosong"
                    >

                    <span class="status-option-info">
                        <strong>Kosong</strong>
                        <small>
                            Kamar siap dihuni dan tampil di daftar kamar tersedia.
                        </small>
                    </span>
                </label>

                <label class="status-option">
                    <input
                        type="radio"
                        name="status"
                        value="Tidak tersedia"
                    >

                    <span class="status-option-info">
                        <strong>Tidak tersedia</strong>
                        <small>
                            Renovasi atau ditutup sementara, tidak tampil di halaman publik.
                        </small>
                    </span>
                </label>

                <label class="status-option">
                    <input
                        type="radio"
                        name="status"
                        value="Terisi"
                    >

                    <span class="status-option-info">
                        <strong>Terisi</strong>
                        <small>
                            Kamar sedang dihuni oleh penghuni aktif.
                        </small>
                    </span>
                </label>

                <label for="catatanStatus">Catatan (opsional)</label>
                <textarea
                    id="catatanStatus"
                    name="catatan"
                    placeholder="Tuliskan alasan perubahan status"
                ></textarea>

                <small class="form-hint">
                    Perubahan status akan langsung tampil di daftar kamar.
                </small>

                <button type="submit" class="btn-primary">
                    Simpan Status
                </button>

                <button
                    type="button"
                    class="btn-secondary"
                    data-kembali-data
                >
                    Batal
                </button>

            </form>
        </div>
    </section>

    <!-- HALAMAN UBAH DATA PENGHUNI -->
    <section id="ubah-data-penghuni" class="page-section">
        <div class="kamar-form-content">

            <h2>Ubah Data Penghuni</h2>

            <form id="formUbahPenghuni">

                <input
                    type="hidden"
                    id="penghuniId"
                    name="id_penghuni"
                >

                <label for="penghuniNama">Nama Lengkap</label>
                <input
                    type="text"
                    id="penghuniNama"
                    name="nama"
                    placeholder="Nama lengkap penghuni"
                    required
                >

                <label for="penghuniEmail">Email</label>
                <input
                    type="email"
                    id="penghuniEmail"
                    name="email"
                    placeholder="Email penghuni"
                    required
                >

                <label for="penghuniTelepon">No. Handphone</label>
                <input
                    type="tel"
                    id="penghuniTelepon"
                    name="no_hp"
                    placeholder="Nomor handphone"
                >

                <label for="penghuniKamar">Kamar</label>
                <select
                    id="penghuniKamar"
                    name="id_kamar"
                    required
                >
                    <option value="" selected disabled>
                        Pilih kamar
                    </option>
                </select>

                <label for="penghuniTanggal">Tanggal Mulai Sewa</label>
                <input
                    type="date"
                    id="penghuniTanggal"
                    name="tanggal_mulai"
                    required
                >

                <label>Status Penghuni</label>

                <div class="status-penghuni-buttons">
                    <label>
                        <input
                            type="radio"
                            name="status_penghuni"
                            value="Aktif"
                        >
                        Aktif
                    </label>

                    <label>
                        <input
                            type="radio"
                            name="status_penghuni"
                            value="Nonaktif"
                        >
                        Nonaktif
                    </label>
                </div>

                <small class="form-hint">
                    Perubahan data akan langsung tersimpan di profil penghuni.
                </small>

                <button type="submit" class="btn-primary">
                    Simpan Perubahan
                </button>

                <button
                    type="button"
                    class="btn-secondary"
                    data-kembali-data
                >
                    Batal
                </button>

            </form>
        </div>
    </section>

    <!-- HALAMAN RIWAYAT HUNI KAMAR -->
    <section id="riwayat-huni-kamar" class="page-section">
        <div class="kamar-form-content">

            <h2>Riwayat Huni Kamar</h2>

            <div class="riwayat-list" id="daftarRiwayatKamar">
                <?php foreach ($riwayatKamar as $riwayat): ?>
                    <article class="riwayat-item" data-riwayat-room="<?= (int) $riwayat['id_kamar'] ?>" hidden>
                        <strong><?= h($riwayat['nama']) ?></strong>
                        <p>Mulai: <?= h($riwayat['tanggal_mulai']) ?></p>
                        <p>Selesai: <?= h($riwayat['tanggal_selesai']) ?></p>
                    </article>
                <?php endforeach; ?>
                <p id="riwayatKamarKosong">Belum ada mantan penghuni untuk kamar ini.</p>
            </div>

            <button
                type="button"
                class="btn-secondary"
                data-kembali-riwayat="kamar"
            >
                Kembali
            </button>

        </div>
    </section>

    <!-- HALAMAN RIWAYAT PENGHUNI -->
    <section id="riwayat-penghuni" class="page-section">
        <div class="kamar-form-content">

            <h2>Riwayat Penghuni</h2>

            <small class="riwayat-subtitle">
                Riwayat kamar yang ditempati dan riwayat pembayaran
            </small>

            <div class="riwayat-list" id="daftarRiwayatPenghuni">
                <?php foreach ($daftarRiwayatPenghuni as $aktif): ?>
                    <?php $idRiwayat = (int) $aktif['id_penghuni']; ?>
                    <article class="riwayat-pembungkus" data-riwayat-penghuni-id="<?= $idRiwayat ?>" hidden>
                        <h3>Riwayat Kamar</h3>
                        <?php if (empty($sewaPerPenghuni[$idRiwayat])): ?>
                            <p>Belum ada riwayat kamar.</p>
                        <?php else: ?>
                            <?php foreach ($sewaPerPenghuni[$idRiwayat] as $riwayat): ?>
                                <div class="riwayat-item">
                                    <strong>Kamar <?= h($riwayat['nomor_kamar']) ?></strong>
                                    <p>Mulai: <?= h($riwayat['tanggal_mulai']) ?></p>
                                    <p>Selesai: <?= h($riwayat['tanggal_selesai'] ?? 'Masih ditempati') ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div class="garis-riwayat"></div>
                        <h3>Riwayat Pembayaran</h3>
                        <?php if (empty($bayarPerPenghuni[$idRiwayat])): ?>
                            <p>Belum ada riwayat pembayaran yang diterima.</p>
                        <?php else: ?>
                            <?php foreach ($bayarPerPenghuni[$idRiwayat] as $pembayaran): ?>
                                <div class="riwayat-item">
                                    <strong>Rp <?= h(number_format((float) $pembayaran['jumlah'], 0, ',', '.')) ?></strong>
                                    <p>Periode: <?= h($pembayaran['periode'] ?: '-') ?></p>
                                    <p>Tanggal bayar: <?= h($pembayaran['tanggal_bayar'] ?? $pembayaran['tanggal_upload'] ?? '-') ?></p>
                                    <p>Status: Diterima</p>
                                    <?php if (!empty($pembayaran['file_bukti'])): ?>
                                        <a href="../calon_penghuni/uploads/<?= rawurlencode(basename($pembayaran['file_bukti'])) ?>" target="_blank" rel="noopener">Lihat bukti pembayaran</a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
                <?php if (!$daftarRiwayatPenghuni): ?><p>Belum ada riwayat penghuni.</p><?php endif; ?>
            </div>

            <button
                type="button"
                class="btn-secondary"
                data-kembali-riwayat="penghuni"
            >
                Kembali
            </button>

        </div>
    </section>

    <div id="popupAkhiriSewa" class="popup-overlay" hidden>
        <div class="popup-hapus" role="dialog" aria-modal="true" aria-labelledby="judulAkhiriSewa">
            <h2 id="judulAkhiriSewa">Akhiri Sewa</h2>
            <p id="teksKonfirmasiAkhiriSewa"></p>
            <form method="post" action="kamar.php?tab=penghuni">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="aksi_akhiri_sewa" value="1">
                <input type="hidden" name="id_sewa" id="akhiriIdSewa">
                <label for="tanggalSelesaiSewa">Tanggal selesai sewa</label>
                <input type="date" name="tanggal_selesai" id="tanggalSelesaiSewa" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                <div class="popup-hapus-actions">
                    <button type="button" id="btnBatalAkhiriSewa">Batal</button>
                    <button type="submit">Akhiri Sewa</button>
                </div>
            </form>
        </div>
    </div>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

