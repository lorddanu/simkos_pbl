
<?php
session_start();

if (!isset($_SESSION['id_akun'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak.');
}

$nama = $_SESSION['nama'] ?? 'Admin SIMKOS';

$pembayaran = [
    [
        'nama' => 'Dewi Lestari',
        'kamar' => 'Kamar B',
        'nominal' => 'Rp 650.000'
    ],
    [
        'nama' => 'Rian Saputra',
        'kamar' => 'Kamar C',
        'nominal' => 'Rp 650.000'
    ],
];

$aktivitas = [
    [
        'nama' => 'Dewi Lestari',
        'kegiatan' => 'Mengunggah bukti',
        'waktu' => '09:12',
        'status' => 'Menunggu verifikasi',
        'kelas' => 'pending'
    ],
    [
        'nama' => 'Rian Saputra',
        'kegiatan' => 'Mengunggah bukti',
        'waktu' => '08:40',
        'status' => 'Menunggu verifikasi',
        'kelas' => 'pending'
    ],
    [
        'nama' => 'Pembayaran Andi Wijaya',
        'kegiatan' => '',
        'waktu' => 'Kemarin',
        'status' => 'Lunas',
        'kelas' => 'paid'
    ],
];


?>

<?php
$halamanAktif = 'pembayaran';

include __DIR__ . '/../includes/header.php';
?>

<main class="admin-main">
        <!-- PEMBAYARAN -->
    <section id="pembayaran" class="page-section active">

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

            <div class="stats-grid">
                <article class="stat-card">
                    <span class="stat-number">1</span>
                    <span class="stat-label">Lunas bulan ini</span>
                </article>

                <article class="stat-card">
                    <span class="stat-number">2</span>
                    <span class="stat-label">Menunggu Verifikasi</span>
                </article>
            </div>

            <section class="panel">
                <h2 class="section-title">Aktivitas Terbaru</h2>

                <?php foreach ($aktivitas as $item): ?>
                    <div class="activity-item">
                        <div>
                            <strong>
                                <?= htmlspecialchars($item['nama']) ?>
                            </strong>

                            <span class="item-description">
                                <?= htmlspecialchars($item['waktu']) ?>
                            </span>
                        </div>

                        <span class="status <?= htmlspecialchars($item['kelas']) ?>">
                            <?= htmlspecialchars($item['status']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </section>

            <?php foreach ($pembayaran as $item): ?>
                <article class="payment-card">
                    <div class="payment-info">
                        <div>
                            <strong>
                                <?= htmlspecialchars($item['nama']) ?>
                            </strong>

                            <span class="item-description">
                                <?= htmlspecialchars($item['kamar']) ?>
                            </span>
                        </div>

                        <strong>
                            <?= htmlspecialchars($item['nominal']) ?>
                        </strong>
                    </div>

                    <div class="payment-actions">
                        <button
                            type="button"
                            class="action-button btn-proof"
                            onclick="alert('Fitur bukti pembayaran akan dihubungkan ke database.')"
                        >
                            Lihat Bukti
                        </button>

                        <button
                            type="button"
                            class="action-button btn-approve"
                            onclick="alert('Fitur verifikasi akan dihubungkan ke backend.')"
                        >
                            Sah
                        </button>

                        <button
                            type="button"
                            class="action-button btn-reject"
                            onclick="alert('Fitur penolakan akan dihubungkan ke backend.')"
                        >
                            Tolak
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>

        </div>

        <!-- TAB LAPORAN -->
        <div id="laporan-pembayaran" class="payment-tab" hidden>

            <div class="stats-grid">
                <article class="stat-card">
                    <span class="stat-number">Rp 650.000</span>
                    <span class="stat-label">Total Pembayaran Diterima</span>
                </article>

                <article class="stat-card">
                    <span class="stat-number">2</span>
                    <span class="stat-label">Menunggu Verifikasi</span>
                </article>
            </div>

            <!-- Tombol berada tepat di bawah statistik -->
            <div class="report-actions">
                <button
                    type="button"
                    id="downloadReport"
                    class="action-button btn-approve"
                >
                    Unduh Laporan
                </button>
            </div>

            <section class="panel">
                <h2 class="section-title">Riwayat Pembayaran</h2>

                <div class="activity-item">
                    <div>
                        <strong>Andi Wijaya</strong>
                        <span class="item-description">Kamar A · Oktober 2026</span>
                        <span class="item-description">Rp 650.000</span>
                    </div>
                    <span class="status paid">Lunas</span>
                </div>

                <div class="activity-item">
                    <div>
                        <strong>Dewi Lestari</strong>
                        <span class="item-description">Kamar B · Oktober 2026</span>
                        <span class="item-description">Rp 650.000</span>
                    </div>
                    <span class="status pending">Menunggu</span>
                </div>

                <div class="activity-item">
                    <div>
                        <strong>Rian Saputra</strong>
                        <span class="item-description">Kamar C · Oktober 2026</span>
                        <span class="item-description">Rp 650.000</span>
                    </div>
                    <span class="status pending">Menunggu</span>
                </div>
            </section>

        </div>


    </section>

<?php include '../includes/footer.php'; ?>
