
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

// Data contoh untuk rancangan awal dashboard
$statistik = [
    ['angka' => '3', 'label' => 'Kamar Kosong'],
    ['angka' => '2', 'label' => 'Menunggu Verifikasi'],
    ['angka' => '1', 'label' => 'Lunas bulan ini'],
    ['angka' => '1', 'label' => 'Terlambat'],
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

$perhatian = [
    [
        'kamar' => 'Kamar C',
        'keterangan' => 'Perlu dipasarkan',
        'status' => 'Kosong 1 Okt',
        'kelas' => 'empty'
    ],
    [
        'kamar' => 'Kamar D',
        'keterangan' => 'Belum ada bukti transfer',
        'status' => 'Terlambat 5 hari',
        'kelas' => 'late'
    ],
];

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

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SIMKOS</title>
    <link rel="stylesheet" href="../assets/cs/admin_dashboard.css">
</head>
<body>


<header class="admin-header">
    <div class="brand">SIMKOS</div>

    <div class="profile-menu">
        <button
            type="button"
            class="profile-button"
            id="profileButton"
            aria-label="Menu profil"
            aria-expanded="false"
            aria-controls="profileDropdown"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="8" r="4"></circle>
                <path d="M4.5 21a7.5 7.5 0 0 1 15 0"></path>
            </svg>
        </button>

        <div class="profile-dropdown" id="profileDropdown" hidden>
            <div class="profile-name">
                <?= htmlspecialchars($nama) ?>
            </div>

            <a href="../auth/logout.php" class="logout-link">
                Logout
            </a>
        </div>
    </div>
</header>

<main class="admin-main">

    <section id="dashboard" class="page-section active">

        <div class="stats-grid">
            <?php foreach ($statistik as $item): ?>
                <article class="stat-card">
                    <span class="stat-number">
                        <?= htmlspecialchars($item['angka']) ?>
                    </span>
                    <span class="stat-label">
                        <?= htmlspecialchars($item['label']) ?>
                    </span>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="dashboard-columns">
            <section class="panel">
                <h2 class="section-title">Aktivitas Terbaru</h2>

                <?php foreach ($aktivitas as $item): ?>
                    <div class="activity-item">
                        <div>
                            <strong>
                                <?= htmlspecialchars($item['nama']) ?>
                            </strong>
                            <?php if ($item['kegiatan'] !== ''): ?>
                                <span class="item-description">
                                    <?= htmlspecialchars($item['kegiatan']) ?>
                                </span>
                            <?php endif; ?>
                            <span class="item-description">
                                <?= htmlspecialchars($item['waktu']) ?>
                            </span>
                        </div>

                        <span class="status <?= $item['kelas'] ?>">
                            <?= htmlspecialchars($item['status']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </section>

            <section class="panel">
                <h2 class="section-title">Kamar butuh perhatian</h2>

                <?php foreach ($perhatian as $item): ?>
                    <div class="attention-item">
                        <div>
                            <strong>
                                <?= htmlspecialchars($item['kamar']) ?>
                            </strong>
                            <span class="item-description">
                                <?= htmlspecialchars($item['keterangan']) ?>
                            </span>
                        </div>

                        <span class="status <?= $item['kelas'] ?>">
                            <?= htmlspecialchars($item['status']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </section>
        </div>
    </section>

    <section id="pembayaran" class="page-section">

        <div class="tabs">
            <button class="tab-button active" type="button"
                    data-tab="verifikasi">Verifikasi</button>
            <button class="tab-button" type="button"
                    data-tab="laporan">Laporan</button>
        </div>

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
                            <strong><?= htmlspecialchars($item['nama']) ?></strong>
                            <span class="item-description">
                                <?= htmlspecialchars($item['waktu']) ?>
                            </span>
                        </div>
                        <span class="status <?= $item['kelas'] ?>">
                            <?= htmlspecialchars($item['status']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </section>

            <?php foreach ($pembayaran as $item): ?>
                <article class="payment-card">
                    <div class="payment-info">
                        <div>
                            <strong><?= htmlspecialchars($item['nama']) ?></strong>
                            <span class="item-description">
                                <?= htmlspecialchars($item['kamar']) ?>
                            </span>
                        </div>
                        <strong><?= htmlspecialchars($item['nominal']) ?></strong>
                    </div>

                    <div class="payment-actions">
                        <button type="button" class="action-button btn-proof"
                                onclick="alert('Fitur bukti pembayaran akan dihubungkan ke database.')">
                            Lihat Bukti
                        </button>
                        <button type="button" class="action-button btn-approve"
                                onclick="alert('Fitur verifikasi akan dihubungkan ke backend.')">
                            Sah
                        </button>
                        <button type="button" class="action-button btn-reject"
                                onclick="alert('Fitur penolakan akan dihubungkan ke backend.')">
                            Tolak
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div id="laporan" class="payment-tab" hidden>
            <div class="empty-state">
                Laporan pembayaran akan ditampilkan di sini.
            </div>
        </div>
    </section>

    <section id="data" class="page-section">
        <h1 class="page-title">Data SIMKOS</h1>
        <div class="empty-state">
            Bagian data kamar dan penghuni akan kita buat berikutnya.
        </div>
    </section>

    <section id="laporan" class="page-section">
        <h1 class="page-title">Laporan</h1>
        <div class="empty-state">
            Bagian laporan pembayaran dan penyewaan akan kita buat berikutnya.
        </div>
    </section>

</main>

<nav class="bottom-nav" aria-label="Navigasi admin">
    <button class="nav-link active" type="button" data-page="dashboard">
        <span class="nav-icon">⌂</span>
        Dashboard
    </button>
    <button class="nav-link" type="button" data-page="pembayaran">
        <span class="nav-icon">▣</span>
        Pembayaran
    </button>
    <button class="nav-link" type="button" data-page="data">
        <span class="nav-icon">▤</span>
        Data
    </button>
    <button class="nav-link" type="button" data-page="laporan">
        <span class="nav-icon">▧</span>
        Laporan
    </button>
</nav>

<script src="../assets/js/admin_dashboard.js"></script>

</body>
</html>