
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
                    'INSERT INTO sewa (id_kamar, id_penghuni, id_pengajuan, tanggal_mulai, tanggal_selesai)
                     VALUES (?, ?, ?, ?, ?) RETURNING id_sewa'
                );
                $st->execute([
                    $pengajuanBayar['id_kamar'], $pengajuanBayar['id_penghuni'], $pengajuanBayar['id_pengajuan'],
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
                $pdo->prepare("UPDATE pengajuan_sewa SET status = 'sewa_aktif', batas_upload_ulang = NULL, ditinjau_oleh = ?, ditinjau_pada = NOW(), notifikasi_dibaca = FALSE, diperbarui_pada = NOW() WHERE id_pengajuan = ?")
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
                $pdo->prepare("UPDATE pengajuan_sewa SET status = 'pembayaran_ditolak', batas_upload_ulang = NOW() + INTERVAL '30 minutes', catatan_admin = ?, ditinjau_oleh = ?, ditinjau_pada = NOW(), notifikasi_dibaca = FALSE, diperbarui_pada = NOW() WHERE id_pengajuan = ?")
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
            k.nomor_kamar AS nomor_kamar,
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


/* Data laporan pembayaran: hanya pembayaran yang sudah diterima. */
$tahunSekarang = (int) date('Y');
$tahunDipilih = filter_input(INPUT_GET, 'tahun', FILTER_VALIDATE_INT) ?: $tahunSekarang;
if ($tahunDipilih < 2000 || $tahunDipilih > $tahunSekarang) $tahunDipilih = $tahunSekarang;

$bulanDipilih = trim($_GET['bulan'] ?? date('Y-m'));
if (!preg_match('/^\\d{4}-(0[1-9]|1[0-2])$/', $bulanDipilih)) $bulanDipilih = date('Y-m');
if ($bulanDipilih > date('Y-m')) $bulanDipilih = date('Y-m');

$awalBulan = $bulanDipilih . '-01';
$awalBulanBerikut = (new DateTimeImmutable($awalBulan))->modify('+1 month')->format('Y-m-d');
$awalTahun = sprintf('%04d-01-01', $tahunDipilih);
$awalTahunBerikut = sprintf('%04d-01-01', $tahunDipilih + 1);

$sqlLaporan = "SELECT p.id_pembayaran, p.jumlah, p.tanggal_upload,
                      COALESCE(ph.nama, a.nama, 'Penghuni') AS nama_penghuni,
                      k.nomor_kamar, p.status_verifikasi
                 FROM pembayaran p
                 LEFT JOIN sewa s ON s.id_sewa = p.id_sewa
                 LEFT JOIN pengajuan_sewa ps ON ps.id_pengajuan = p.id_pengajuan_sewa
                 LEFT JOIN penghuni ph ON ph.id_penghuni = COALESCE(s.id_penghuni, ps.id_penghuni)
                 LEFT JOIN akun a ON a.id_penghuni = ph.id_penghuni
                 LEFT JOIN kamar k ON k.id_kamar = COALESCE(s.id_kamar, ps.id_kamar)
                WHERE p.status_verifikasi = 'diterima'
                  AND p.tanggal_upload >= ? AND p.tanggal_upload < ?
                ORDER BY p.tanggal_upload ASC, p.id_pembayaran ASC";
$stLaporanBulan = $pdo->prepare($sqlLaporan);
$stLaporanBulan->execute([$awalBulan, $awalBulanBerikut]);
$laporanBulan = $stLaporanBulan->fetchAll();
$totalBulan = array_sum(array_map(static fn($r) => (float) $r['jumlah'], $laporanBulan));

$stLaporanTahun = $pdo->prepare($sqlLaporan);
$stLaporanTahun->execute([$awalTahun, $awalTahunBerikut]);
$laporanTahun = $stLaporanTahun->fetchAll();
$totalTahun = array_sum(array_map(static fn($r) => (float) $r['jumlah'], $laporanTahun));

$ringkasanBulanan = [];
foreach ($laporanTahun as $row) {
    $kunciBulan = date('Y-m', strtotime($row['tanggal_upload']));
    if (!isset($ringkasanBulanan[$kunciBulan])) $ringkasanBulanan[$kunciBulan] = ['total' => 0, 'jumlah' => 0];
    $ringkasanBulanan[$kunciBulan]['total'] += (float) $row['jumlah'];
    $ringkasanBulanan[$kunciBulan]['jumlah']++;
}

/* Ekspor CSV untuk laporan bulanan maupun tahunan. */
$jenisUnduhan = $_GET['unduh'] ?? '';
if (in_array($jenisUnduhan, ['bulan', 'tahun'], true)) {
    $dataEkspor = $jenisUnduhan === 'tahun' ? $laporanTahun : $laporanBulan;
    $periodeEkspor = $jenisUnduhan === 'tahun' ? (string) $tahunDipilih : $bulanDipilih;
    $namaFile = 'laporan-pembayaran-' . $periodeEkspor . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $namaFile . '"');
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['Nama Penghuni', 'Nomor Kamar', 'Tanggal Pembayaran', 'Nominal', 'Status']);
    foreach ($dataEkspor as $row) {
        fputcsv($output, [
            $row['nama_penghuni'],
            $row['nomor_kamar'] ?? '-',
            $row['tanggal_upload'],
            (float) $row['jumlah'],
            'Diterima'
        ]);
    }
    fputcsv($output, []);
    fputcsv($output, ['Total Pendapatan', '', '', $jenisUnduhan === 'tahun' ? $totalTahun : $totalBulan, '']);
    fclose($output);
    exit;
}

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

            <h2 class="section-title">Laporan Pembayaran</h2>
            <div class="report-filter">
                <form method="get" class="report-filter-form">
                    <label for="bulanLaporan">Laporan bulanan</label>
                    <input type="month" id="bulanLaporan" name="bulan" value="<?= h($bulanDipilih) ?>" max="<?= h(date('Y-m')) ?>">
                    <button type="submit" class="action-button btn-proof">Tampilkan</button>
                </form>
                <a class="action-button btn-approve report-download-link" href="pembayaran.php?unduh=bulan&amp;bulan=<?= h($bulanDipilih) ?>">Unduh bulan ini</a>
            </div>

            <div class="stats-grid report-stats">
                <article class="stat-card">
                    <span class="stat-number">Rp <?= h(number_format($totalBulan, 0, ',', '.')) ?></span>
                    <span class="stat-label">Pendapatan diterima · <?= h($bulanDipilih) ?></span>
                </article>
                <article class="stat-card">
                    <span class="stat-number"><?= count($laporanBulan) ?></span>
                    <span class="stat-label">Pembayaran diterima</span>
                </article>
            </div>

            <section class="panel payment-report-panel">
                <h2 class="section-title">Riwayat Pembayaran Bulan Ini</h2>
                <?php if (!$laporanBulan): ?>
                    <p class="item-description">Belum ada pembayaran diterima pada periode ini.</p>
                <?php else: ?>
                    <?php foreach ($laporanBulan as $row): ?>
                        <article class="activity-item">
                            <div>
                                <strong><?= h($row['nama_penghuni']) ?> · <?= h($row['nomor_kamar'] ?? 'Kamar -') ?></strong>
                                <span class="item-description"><?= h(date('d-m-Y', strtotime($row['tanggal_upload']))) ?> · Rp <?= h(number_format((float) $row['jumlah'], 0, ',', '.')) ?></span>
                            </div>
                            <span class="status paid">Diterima</span>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="payment-report-total"><strong>Total</strong><strong>Rp <?= h(number_format($totalBulan, 0, ',', '.')) ?></strong></div>
            </section>

            <section class="previous-report-section">
                <h2 class="section-title">Laporan Bulan Sebelumnya</h2>
                <?php if (!$ringkasanBulanan): ?>
                    <p class="item-description">Belum ada riwayat pembayaran pada tahun ini.</p>
                <?php else: ?>
                    <?php foreach (array_reverse($ringkasanBulanan, true) as $bulan => $ringkasan): ?>
                        <?php if ($bulan !== $bulanDipilih): ?>
                            <article class="previous-report-card">
                                <div><strong><?= h(date('F Y', strtotime($bulan . '-01'))) ?></strong><span><?= (int) $ringkasan['jumlah'] ?> pembayaran</span></div>
                                <div class="previous-report-total">
                                    <strong>Rp <?= h(number_format($ringkasan['total'], 0, ',', '.')) ?></strong>
                                    <a href="pembayaran.php?unduh=bulan&amp;bulan=<?= h($bulan) ?>" class="report-download-link">Unduh</a>
                                </div>
                            </article>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <section class="previous-report-section">
                <h2 class="section-title">Laporan Tahunan</h2>
                <form method="get" class="report-filter-form report-year-form">
                    <label for="tahunLaporan">Pilih tahun</label>
                    <select id="tahunLaporan" name="tahun">
                        <?php for ($tahun = $tahunSekarang; $tahun >= $tahunSekarang - 5; $tahun--): ?>
                            <option value="<?= $tahun ?>" <?= $tahunDipilih === $tahun ? 'selected' : '' ?>><?= $tahun ?></option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" class="action-button btn-proof">Tampilkan</button>
                    <a class="action-button btn-approve report-download-link" href="pembayaran.php?unduh=tahun&amp;tahun=<?= $tahunDipilih ?>">Unduh tahunan</a>
                </form>
                <div class="payment-report-total"><strong>Total pendapatan <?= $tahunDipilih ?></strong><strong>Rp <?= h(number_format($totalTahun, 0, ',', '.')) ?></strong></div>
            </section>

        </div>

    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
