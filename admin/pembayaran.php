
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

$halamanAktif = 'pembayaran';

include __DIR__ . '/../includes_admin/header.php';
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
                    <span class="stat-number">0</span>
                    <span class="stat-label">Lunas Bulan Ini</span>
                </article>

                <article class="stat-card">
                    <span class="stat-number">0</span>
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

                <p class="item-description">
                    Belum ada pembayaran yang perlu diverifikasi.
                </p>

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

<?php include __DIR__ . '/../includes_admin/footer.php'; ?>
