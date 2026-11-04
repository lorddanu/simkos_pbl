
<?php
require __DIR__ . '/../database/helpers.php';
$user = require_login();
if ($user['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak.');
}
$nama = $user['nama'] ?? 'Admin SIMKOS';
$pdo = db();
$pesanAdmin = $_SESSION['flash_admin'] ?? '';
unset($_SESSION['flash_admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi_pembayaran'] ?? '') !== '') {
    if (!csrf_ok()) {
        http_response_code(400);
        exit('Permintaan tidak valid.');
    }
    $idPembayaran = filter_input(INPUT_POST, 'id_pembayaran', FILTER_VALIDATE_INT) ?: 0;
    $aksiPembayaran = $_POST['aksi_pembayaran'];
    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare(
            "SELECT id_pembayaran, id_sewa, id_pengajuan_sewa, status_verifikasi
               FROM pembayaran WHERE id_pembayaran = ? FOR UPDATE"
        );
        $st->execute([$idPembayaran]);
        $pembayaran = $st->fetch();
        if (!$pembayaran || $pembayaran['status_verifikasi'] !== 'menunggu') {
            throw new RuntimeException('Pembayaran sudah diproses atau tidak ditemukan.');
        }

        if ($pembayaran['id_pengajuan_sewa'] !== null) {
            $st = $pdo->prepare('SELECT * FROM pengajuan_sewa WHERE id_pengajuan = ? FOR UPDATE');
            $st->execute([$pembayaran['id_pengajuan_sewa']]);
            $pengajuanBayar = $st->fetch();
            if (!$pengajuanBayar || $pengajuanBayar['status'] !== 'menunggu_verifikasi') {
                throw new RuntimeException('Status pengajuan tidak lagi menunggu verifikasi.');
            }

            if ($aksiPembayaran === 'terima') {
                $st = $pdo->prepare('SELECT status FROM kamar WHERE id_kamar = ? FOR UPDATE');
                $st->execute([$pengajuanBayar['id_kamar']]);
                if ($st->fetchColumn() !== 'kosong') {
                    throw new RuntimeException('Kamar tidak lagi tersedia; pembayaran belum dapat diterima.');
                }
                $st = $pdo->prepare(
                    'INSERT INTO sewa (id_kamar, id_penghuni, tanggal_mulai, tanggal_selesai)
                     VALUES (?, ?, ?, ?) RETURNING id_sewa'
                );
                $st->execute([
                    $pengajuanBayar['id_kamar'], $pengajuanBayar['id_penghuni'],
                    $pengajuanBayar['tanggal_mulai'], $pengajuanBayar['tanggal_selesai'],
                ]);
                demo_track_insert('sewa', (int) $st->fetchColumn());
                demo_track_before_change('kamar', (int) $pengajuanBayar['id_kamar']);
                $pdo->prepare("UPDATE kamar SET status = 'terisi' WHERE id_kamar = ?")
                    ->execute([$pengajuanBayar['id_kamar']]);
                $akunSt = $pdo->prepare('SELECT id_akun FROM akun WHERE id_penghuni = ?');
                $akunSt->execute([$pengajuanBayar['id_penghuni']]);
                $idAkunCalon = (int) $akunSt->fetchColumn();
                demo_track_before_change('akun', $idAkunCalon);
                $pdo->prepare("UPDATE akun SET role = 'penghuni' WHERE id_penghuni = ?")
                    ->execute([$pengajuanBayar['id_penghuni']]);
                demo_track_before_change('pengajuan_sewa', (int) $pengajuanBayar['id_pengajuan']);
                $pdo->prepare("UPDATE pengajuan_sewa SET status = 'sewa_aktif', ditinjau_oleh = ?, ditinjau_pada = NOW(), notifikasi_dibaca = FALSE, diperbarui_pada = NOW() WHERE id_pengajuan = ?")
                    ->execute([(int) $user['id_akun'], $pengajuanBayar['id_pengajuan']]);
                demo_track_before_change('pembayaran', $idPembayaran);
                $pdo->prepare("UPDATE pembayaran SET status_verifikasi = 'diterima' WHERE id_pembayaran = ?")
                    ->execute([$idPembayaran]);
                $_SESSION['flash_admin'] = 'Pembayaran diterima. Kamar dan sewa penghuni kini aktif.';
            } elseif ($aksiPembayaran === 'tolak') {
                $alasan = trim($_POST['catatan_admin'] ?? '');
                demo_track_before_change('pembayaran', $idPembayaran);
                $pdo->prepare("UPDATE pembayaran SET status_verifikasi = 'ditolak' WHERE id_pembayaran = ?")
                    ->execute([$idPembayaran]);
                demo_track_before_change('pengajuan_sewa', (int) $pengajuanBayar['id_pengajuan']);
                $pdo->prepare("UPDATE pengajuan_sewa SET status = 'pembayaran_ditolak', catatan_admin = ?, ditinjau_oleh = ?, ditinjau_pada = NOW(), notifikasi_dibaca = FALSE, diperbarui_pada = NOW() WHERE id_pengajuan = ?")
                    ->execute([$alasan !== '' ? $alasan : null, (int) $user['id_akun'], $pengajuanBayar['id_pengajuan']]);
                $_SESSION['flash_admin'] = 'Pembayaran ditolak. Calon penghuni dapat mengajukan pembayaran kembali.';
            } else {
                throw new RuntimeException('Aksi pembayaran tidak dikenal.');
            }
        } else {
            if (!in_array($aksiPembayaran, ['terima', 'tolak'], true)) {
                throw new RuntimeException('Aksi pembayaran tidak dikenal.');
            }
            $statusBaru = $aksiPembayaran === 'terima' ? 'diterima' : 'ditolak';
            demo_track_before_change('pembayaran', $idPembayaran);
            $pdo->prepare('UPDATE pembayaran SET status_verifikasi = ? WHERE id_pembayaran = ?')
                ->execute([$statusBaru, $idPembayaran]);
            $_SESSION['flash_admin'] = 'Status pembayaran penghuni berhasil diperbarui.';
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash_admin'] = $e instanceof RuntimeException ? $e->getMessage() : 'Verifikasi pembayaran gagal.';
        if (!$e instanceof RuntimeException) error_log($e->getMessage());
    }
    header('Location: pembayaran.php');
    exit;
}

$jumlahMenunggu = (int) $pdo->query("SELECT COUNT(*) FROM pembayaran WHERE status_verifikasi = 'menunggu'")->fetchColumn();
$jumlahLunasBulanIni = (int) $pdo->query("SELECT COUNT(*) FROM pembayaran WHERE status_verifikasi = 'diterima' AND tanggal_upload >= date_trunc('month', CURRENT_DATE)")->fetchColumn();
$st = $pdo->query(
    "SELECT p.id_pembayaran, p.id_sewa, p.id_pengajuan_sewa, p.jumlah, p.file_bukti, p.tanggal_upload,
            COALESCE(ps.id_penghuni, s.id_penghuni) AS id_penghuni,
            COALESCE(ps.id_kamar, s.id_kamar) AS id_kamar,
            COALESCE(ps.nomor_kamar, k.nomor_kamar) AS nomor_kamar,
            COALESCE(ph.nama, a.nama, 'Penghuni') AS nama, a.email,
            p.catatan, ps.status AS status_pengajuan
       FROM pembayaran p
       LEFT JOIN pengajuan_sewa ps ON ps.id_pengajuan = p.id_pengajuan_sewa
       LEFT JOIN sewa s ON s.id_sewa = p.id_sewa
       LEFT JOIN penghuni ph ON ph.id_penghuni = COALESCE(ps.id_penghuni, s.id_penghuni)
       LEFT JOIN akun a ON a.id_penghuni = ph.id_penghuni
       LEFT JOIN kamar k ON k.id_kamar = COALESCE(ps.id_kamar, s.id_kamar)
      WHERE p.status_verifikasi = 'menunggu'
      ORDER BY p.tanggal_upload, p.id_pembayaran"
);
$pembayaranMenunggu = $st->fetchAll();

$halamanAktif = 'pembayaran';

include __DIR__ . '/../includes/header.php';
?>

<main class="admin-main">

    <!-- HALAMAN PEMBAYARAN -->
    <section id="pembayaran" class="page-section active">

        <!-- NAVIGASI TAB -->
        <div class="tabs">
            <button
                class="tab-button active"
                type="button"
                data-tab="verifikasi"
            >
                Verifikasi
            </button>

            <button
                class="tab-button"
                type="button"
                data-tab="laporan-pembayaran"
            >
                Laporan
            </button>
        </div>

        <!-- TAB VERIFIKASI -->
        <div id="verifikasi" class="payment-tab">

            <!-- STATISTIK VERIFIKASI -->
            <div class="stats-grid">

                <article class="stat-card">
                    <span class="stat-number"><?= $jumlahLunasBulanIni ?></span>
                    <span class="stat-label">Lunas Bulan Ini</span>
                </article>

                <article class="stat-card">
                    <span class="stat-number"><?= $jumlahMenunggu ?></span>
                    <span class="stat-label">Menunggu Verifikasi</span>
                </article>

            </div>

            <!-- AKTIVITAS TERBARU -->
            <section class="panel">
                <h2 class="section-title">Aktivitas Terbaru</h2>

                <p class="item-description">
                    Belum ada aktivitas pembayaran.
                </p>
            </section>

            <!-- DAFTAR PEMBAYARAN -->
            <div class="payment-list">

                <?php if ($pesanAdmin): ?><p class="admin-feedback" role="status"><?= h($pesanAdmin) ?></p><?php endif; ?>
                <?php if (!$pembayaranMenunggu): ?><p class="item-description">Belum ada pembayaran yang perlu diverifikasi.</p><?php endif; ?>
                <?php foreach ($pembayaranMenunggu as $item): ?>
                    <article class="admin-record-card">
                        <div><strong><?= h($item['nama']) ?> · Kamar <?= h($item['nomor_kamar'] ?? '-') ?></strong><small><?= h($item['email'] ?? '') ?></small></div>
                        <p>Nominal: Rp <?= h(number_format((float) $item['jumlah'], 0, ',', '.')) ?></p>
                        <p>Tanggal unggah: <?= h($item['tanggal_upload'] ?? '-') ?></p>
                        <?php if (!empty($item['file_bukti'])): ?><a href="../calon_penghuni/uploads/<?= rawurlencode(basename($item['file_bukti'])) ?>" target="_blank" rel="noopener">Lihat bukti pembayaran</a><?php else: ?><small>Tidak ada file bukti (simulasi).</small><?php endif; ?>
                        <form method="post" class="admin-application-actions">
                            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="id_pembayaran" value="<?= (int) $item['id_pembayaran'] ?>">
                            <button class="btn-primary" name="aksi_pembayaran" value="terima">Verifikasi / Terima</button>
                            <input type="text" name="catatan_admin" maxlength="500" placeholder="Alasan penolakan (opsional)">
                            <button class="btn-secondary" name="aksi_pembayaran" value="tolak">Tolak</button>
                        </form>
                    </article>
                <?php endforeach; ?>

            </div>

        </div>

        <!-- TAB LAPORAN PEMBAYARAN -->
        <div id="laporan-pembayaran" class="payment-tab" hidden>

            <!-- STATISTIK LAPORAN -->
            <div class="stats-grid">

                <article class="stat-card">
                    <span class="stat-number">Rp.0</span>
                    <span class="stat-label">
                        Total Pembayaran Diterima
                    </span>
                </article>

                <article class="stat-card">
                    <span class="stat-number">0</span>
                    <span class="stat-label">
                        Menunggu Verifikasi
                    </span>
                </article>

            </div>

            <!-- TOMBOL UNDUH LAPORAN -->
            <div class="report-actions">
                <button
                    type="button"
                    id="downloadReport"
                    class="action-button btn-approve"
                >
                    Unduh Laporan
                </button>
            </div>

            <!-- RIWAYAT PEMBAYARAN -->
            <section class="panel">

                <h2 class="section-title">
                    Riwayat Pembayaran
                </h2>

                <p class="item-description">
                    Belum ada riwayat pembayaran.
                </p>

            </section>

        </div>

    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
