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
    "SELECT ps.status, ps.diperbarui_pada, p.nama, k.nomor_kamar
       FROM pengajuan_sewa ps JOIN penghuni p USING (id_penghuni) JOIN kamar k USING (id_kamar)
      ORDER BY ps.diperbarui_pada DESC LIMIT 5"
)->fetchAll();
$kamarPerhatian = $pdo->query("SELECT nomor_kamar, status FROM kamar WHERE status = 'tidak tersedia' ORDER BY nomor_kamar")->fetchAll();
$halamanAktif = 'dashboard';

include __DIR__ . '/../includes/header.php';
?>

<main class="admin-main">
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
                <?php if (!$aktivitas): ?><p class="item-description">Belum ada aktivitas.</p><?php endif; ?>
                <?php foreach ($aktivitas as $item): ?>
                    <p class="item-description"><?= h($item['nama']) ?> · Kamar <?= h($item['nomor_kamar']) ?> · <?= h(str_replace('_', ' ', ucfirst($item['status']))) ?></p>
                <?php endforeach; ?>
            </section>
            <section class="panel">
                <h2 class="section-title">Kamar Butuh Perhatian</h2>
                <?php if (!$kamarPerhatian): ?><p class="item-description">Belum ada kamar yang perlu diperhatikan.</p><?php endif; ?>
                <?php foreach ($kamarPerhatian as $kamar): ?><p class="item-description">Kamar <?= h($kamar['nomor_kamar']) ?> · <?= h($kamar['status']) ?></p><?php endforeach; ?>
            </section>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
