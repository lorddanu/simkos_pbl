<?php
require __DIR__ . '/../database/helpers.php';

$user = require_login();
if ($user['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak. Halaman ini hanya untuk admin.');
}

$nama = $user['nama'] ?: 'Admin SIMKOS';
$pdo = db();
$jumlahKamarKosong = (int) $pdo->query(
    "SELECT COUNT(*) FROM kamar k
      WHERE k.status = 'kosong'
        AND NOT EXISTS (
            SELECT 1 FROM pengajuan_sewa ps WHERE ps.id_kamar = k.id_kamar
              AND ps.status IN ('menunggu_persetujuan', 'menunggu_pembayaran', 'menunggu_verifikasi', 'pembayaran_ditolak')
        )"
)->fetchColumn();
$jumlahMenunggu = (int) $pdo->query("SELECT COUNT(*) FROM pembayaran WHERE status_verifikasi = 'menunggu'")->fetchColumn();
$jumlahLunasBulanIni = (int) $pdo->query("SELECT COUNT(*) FROM pembayaran WHERE status_verifikasi = 'diterima' AND tanggal_upload >= date_trunc('month', CURRENT_DATE)")->fetchColumn();
$jumlahTerlambat = (int) $pdo->query(
    "SELECT COUNT(*) FROM sewa s
      WHERE s.tanggal_mulai <= CURRENT_DATE
        AND (s.tanggal_selesai IS NULL OR s.tanggal_selesai >= CURRENT_DATE)
        AND NOT EXISTS (
            SELECT 1 FROM pembayaran p WHERE p.id_sewa = s.id_sewa
              AND p.periode = to_char(CURRENT_DATE, 'YYYY-MM')
              AND p.status_verifikasi IN ('menunggu', 'diterima')
        )"
)->fetchColumn();
$aktivitas = $pdo->query(
    "SELECT COALESCE(ph.nama, a.nama, 'Penghuni') AS nama,
            p.status_verifikasi AS status,
            COALESCE(p.tanggal_upload, p.tanggal_bayar::timestamp) AS waktu
       FROM pembayaran p
       LEFT JOIN sewa s ON s.id_sewa = p.id_sewa
       LEFT JOIN pengajuan_sewa ps ON ps.id_pengajuan = p.id_pengajuan_sewa
       LEFT JOIN penghuni ph ON ph.id_penghuni = COALESCE(s.id_penghuni, ps.id_penghuni)
       LEFT JOIN akun a ON a.id_penghuni = ph.id_penghuni
      ORDER BY COALESCE(p.tanggal_upload, p.tanggal_bayar::timestamp) DESC, p.id_pembayaran DESC
      LIMIT 3"
)->fetchAll();
$kamarKosongPerhatian = $pdo->query(
    "SELECT k.nomor_kamar FROM kamar k
      WHERE k.status = 'kosong'
        AND NOT EXISTS (
            SELECT 1 FROM pengajuan_sewa ps WHERE ps.id_kamar = k.id_kamar
              AND ps.status IN ('menunggu_persetujuan', 'menunggu_pembayaran', 'menunggu_verifikasi', 'pembayaran_ditolak')
        )
      ORDER BY k.nomor_kamar LIMIT 2"
)->fetchAll();
$penghuniTerlambat = $pdo->query(
    "SELECT k.nomor_kamar, COALESCE(p.nama, a.nama, 'Penghuni') AS nama
       FROM sewa s JOIN kamar k USING (id_kamar)
       JOIN penghuni p USING (id_penghuni)
       LEFT JOIN akun a USING (id_penghuni)
      WHERE s.tanggal_mulai <= CURRENT_DATE
        AND (s.tanggal_selesai IS NULL OR s.tanggal_selesai >= CURRENT_DATE)
        AND NOT EXISTS (
            SELECT 1 FROM pembayaran pb WHERE pb.id_sewa = s.id_sewa
              AND pb.periode = to_char(CURRENT_DATE, 'YYYY-MM')
              AND pb.status_verifikasi IN ('menunggu', 'diterima')
        )
      ORDER BY s.tanggal_mulai LIMIT 3"
)->fetchAll();
$halamanAktif = 'dashboard';

include __DIR__ . '/../includes/header.php';
?>

<main class="admin-main admin-dashboard-main">
    <section id="dashboard" class="page-section active">
        <div class="stats-grid">
            <article class="stat-card">
                <span class="stat-number"><?= $jumlahKamarKosong ?></span>
                <span class="stat-label">Kamar Kosong</span>
            </article>
            <article class="stat-card">
                <span class="stat-number"><?= $jumlahMenunggu ?></span>
                <span class="stat-label">Menunggu Verifikasi</span>
            </article>
            <article class="stat-card">
                <span class="stat-number"><?= $jumlahLunasBulanIni ?></span>
                <span class="stat-label">Lunas Bulan Ini</span>
            </article>
            <article class="stat-card">
                <span class="stat-number"><?= $jumlahTerlambat ?></span>
                <span class="stat-label">Terlambat</span>
            </article>
        </div>

        <div class="dashboard-columns">
            <section class="panel">
                <h2 class="section-title">Aktivitas Terbaru</h2>
                <?php if (!$aktivitas): ?><p class="item-description">Belum ada aktivitas pembayaran.</p><?php endif; ?>
                <?php foreach ($aktivitas as $item): ?>
                    <?php
                    $labelAktivitas = match ($item['status']) {
                        'diterima' => 'Lunas',
                        'ditolak' => 'Ditolak',
                        default => 'Menunggu verifikasi',
                    };
                    $kelasAktivitas = match ($item['status']) {
                        'diterima' => 'paid',
                        'ditolak' => 'late',
                        default => 'pending',
                    };
                    $waktu = new DateTimeImmutable($item['waktu']);
                    $hariIni = new DateTimeImmutable('today');
                    $hariAktivitas = $waktu->setTime(0, 0);
                    $labelWaktu = $hariAktivitas == $hariIni
                        ? $waktu->format('H:i')
                        : ($hariAktivitas == $hariIni->modify('-1 day') ? 'Kemarin' : $waktu->format('d M'));
                    ?>
                    <article class="activity-item">
                        <div class="activity-copy">
                            <strong><?= h($item['nama']) ?> <?= $item['status'] === 'diterima' ? 'pembayaran diterima' : 'unggah bukti' ?></strong>
                            <small><?= h($labelWaktu) ?></small>
                        </div>
                        <span class="status <?= h($kelasAktivitas) ?>"><?= h($labelAktivitas) ?></span>
                    </article>
                <?php endforeach; ?>
            </section>
            <section class="panel">
                <h2 class="section-title">Kamar Butuh Perhatian</h2>
                <?php if (!$kamarKosongPerhatian && !$penghuniTerlambat): ?><p class="item-description">Belum ada kamar yang perlu diperhatikan.</p><?php endif; ?>
                <?php foreach ($kamarKosongPerhatian as $kamar): ?>
                    <article class="attention-item">
                        <div class="activity-copy"><strong>Kamar <?= h($kamar['nomor_kamar']) ?></strong><small>Perlu dipasarkan</small></div>
                        <span class="status empty">Kosong</span>
                    </article>
                <?php endforeach; ?>
                <?php foreach ($penghuniTerlambat as $penghuni): ?>
                    <article class="attention-item">
                        <div class="activity-copy"><strong>Kamar <?= h($penghuni['nomor_kamar']) ?></strong><small><?= h($penghuni['nama']) ?> belum ada bukti transfer</small></div>
                        <span class="status late">Terlambat</span>
                    </article>
                <?php endforeach; ?>
            </section>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
