
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
?>

<?php
$halamanAktif = 'laporan';

include __DIR__ . '/../includes/header.php';
?>

<main class="admin-main">
    <!-- HALAMAN LAPORAN -->
    <section id="laporan" class="page-section active">
    <div class="laporan-content">

        <!-- RINGKASAN -->
        <div class="laporan-statistik">
            <article class="laporan-stat-card">
                <strong>1</strong>
                <span>Belum dibaca</span>
            </article>

            <article class="laporan-stat-card">
                <strong>2</strong>
                <span>Total bulan ini</span>
            </article>
        </div>

        <!-- SARAN DAN KRITIK PERTAMA -->
        <article class="laporan-card">

            <div class="laporan-card-header">
                <div>
                    <strong>Andi Wijaya · Kamar E3</strong>
                    <small>2 Okt 2026</small>
                </div>

                <span class="laporan-status status-baru">Baru</span>
            </div>

            <div class="laporan-pesan">
                <span class="laporan-kategori">Fasilitas</span>

                <p>
                    Lampu koridor lantai 2 sering mati,
                    mohon diganti.
                </p>
            </div>

            <div class="laporan-actions">
                <button type="button"
                        class="laporan-btn btn-balas">
                    Balas
                </button>

                <button type="button"
                        class="laporan-btn btn-dibaca">
                    Tandai Dibaca
                </button>
            </div>

        </article>

        <!-- SARAN DAN KRITIK KEDUA -->
        <article class="laporan-card">

            <div class="laporan-card-header">
                <div>
                    <strong>Andi Wijaya · Kamar E3</strong>
                    <small>28 Sep 2026</small>
                </div>

                <span class="laporan-status status-dibaca">
                    Dibaca
                </span>
            </div>

            <div class="laporan-pesan">
                <span class="laporan-kategori kategori-pelayanan">
                    Pelayanan
                </span>

                <p>
                    Respon admin sudah cepat, terima kasih.
                </p>
            </div>

        </article>
    </div>
    </section>
</main>

<?php include '../includes/footer.php'; ?>
