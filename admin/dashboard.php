<?php
session_start();

if (!isset($_SESSION['id_akun'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak. Halaman ini hanya untuk admin.');
}

$nama = $_SESSION['nama'] ?? 'Admin SIMKOS';

$halamanAktif = 'dashboard';

include __DIR__ . '/../includes/header.php';
?>

<main class="admin-main">

    <!-- DASHBOARD -->
    <section id="dashboard" class="page-section active">

        <!-- STATISTIK -->
        <div class="stats-grid">
            <article class="stat-card">
                <span class="stat-number">5</span>
                <span class="stat-label">Kamar Kosong</span>
            </article>

            <article class="stat-card">
                <span class="stat-number">0</span>
                <span class="stat-label">Menunggu Verifikasi</span>
            </article>

            <article class="stat-card">
                <span class="stat-number">0</span>
                <span class="stat-label">Lunas Bulan Ini</span>
            </article>

            <article class="stat-card">
                <span class="stat-number">0</span>
                <span class="stat-label">Terlambat</span>
            </article>
        </div>

        <!-- AKTIVITAS DAN PERHATIAN -->
        <div class="dashboard-columns">

            <section class="panel">
                <h2 class="section-title">Aktivitas Terbaru</h2>

                <p class="item-description">
                    Belum ada aktivitas.
                </p>
            </section>

            <section class="panel">
                <h2 class="section-title">Kamar Butuh Perhatian</h2>

                <p class="item-description">
                    Belum ada kamar yang perlu diperhatikan.
                </p>
            </section>

        </div>

    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
