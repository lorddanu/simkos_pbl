
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

$halamanAktif = 'laporan';

include __DIR__ . '/../includes_admin/header.php';
?>

<main class="admin-main">

    <!-- HALAMAN LAPORAN -->
    <section id="laporan" class="page-section active">

        <div class="laporan-content">

            <!-- RINGKASAN -->
            <div class="laporan-statistik">

                <article class="laporan-stat-card">
                    <strong>-</strong>
                    <span>Belum dibaca</span>
                </article>

                <article class="laporan-stat-card">
                    <strong>-</strong>
                    <span>Total bulan ini</span>
                </article>

            </div>

            <!-- DAFTAR SARAN DAN KRITIK -->
            <div class="laporan-daftar">

                <p class="item-description">
                    Belum ada saran atau kritik dari penghuni.
                </p>

            </div>

        </div>

    </section>

</main>

<?php include __DIR__ . '/../includes_admin/footer.php'; ?>
