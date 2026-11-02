
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
<!-- 
<?php include __DIR__ . '/../includes/header.php'; ?>

<?php include __DIR__ . '/../includes/navbar.php'; ?> -->

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

    <!-- DASHBOARD -->
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

                        <span class="status <?= htmlspecialchars($item['kelas']) ?>">
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

                        <span class="status <?= htmlspecialchars($item['kelas']) ?>">
                            <?= htmlspecialchars($item['status']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </section>

        </div>
    </section>

    <!-- PEMBAYARAN -->
    <section id="pembayaran" class="page-section">

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

    <!-- DATA SIMKOS -->
    <section id="data" class="page-section">

        <!-- TAB DATA -->
        <div class="data-tabs">
            <button
                type="button"
                class="data-tab active"
                data-data-tab="kamar"
            >
                Kamar
            </button>

            <button
                type="button"
                class="data-tab"
                data-data-tab="penghuni"
            >
                Penghuni
            </button>
        </div>

        <!-- TAB KAMAR -->
        <div id="tab-kamar" class="data-tab-content">

            <!-- RINGKASAN -->
            <div class="kamar-stats">
                <article class="kamar-stat-card">
                    <span class="kamar-stat-number">5</span>
                    <span>Total Kamar</span>
                </article>

                <article class="kamar-stat-card">
                    <span class="kamar-stat-number">2</span>
                    <span>Kosong</span>
                </article>
            </div>

            <!-- TAMBAH KAMAR -->
            <button
                type="button"
                class="btn-tambah-kamar"
                id="btnTambahKamar"
            >
                + Tambah Kamar
            </button>

            <!-- DAFTAR KAMAR -->
            <div class="daftar-kamar">

                <article class="kamar-card">
                    <div class="kamar-card-header">
                        <div>
                            <h3>Kamar A</h3>
                            <p>Rp 650.000/bulan</p>
                        </div>

                        <span class="kamar-status kosong">Kosong</span>
                    </div>

                    <div class="kamar-card-actions">
                        <button type="button" class="btn-kamar">
                            Ubah
                        </button>

                        <button type="button" class="btn-kamar">
                            Ubah Status
                        </button>
                    </div>
                </article>

                <article class="kamar-card">
                    <div class="kamar-card-header">
                        <div>
                            <h3>Kamar B</h3>
                            <p>Rp 650.000/bulan</p>
                            <small>Rian Saputra (calon)</small>
                        </div>

                        <span class="kamar-status menunggu">
                            Menunggu pembayaran
                        </span>
                    </div>

                    <div class="kamar-card-actions">
                        <button type="button" class="btn-kamar">
                            Ubah
                        </button>

                        <button type="button" class="btn-kamar">
                            Lihat Pembayaran
                        </button>
                    </div>
                </article>

                <article class="kamar-card">
                    <div class="kamar-card-header">
                        <div>
                            <h3>Kamar C</h3>
                            <p>Rp 650.000/bulan</p>
                            <small>Dewi Lestari (calon)</small>
                        </div>

                        <span class="kamar-status menunggu">
                            Menunggu pembayaran
                        </span>
                    </div>

                    <div class="kamar-card-actions">
                        <button type="button" class="btn-kamar">
                            Ubah
                        </button>

                        <button type="button" class="btn-kamar">
                            Lihat Pembayaran
                        </button>
                    </div>
                </article>

                <article class="kamar-card">
                    <div class="kamar-card-header">
                        <div>
                            <h3>Kamar D</h3>
                            <p>Rp 650.000/bulan</p>
                        </div>

                        <span class="kamar-status kosong">Kosong</span>
                    </div>

                    <div class="kamar-card-actions">
                        <button type="button" class="btn-kamar">
                            Ubah
                        </button>

                        <button type="button" class="btn-kamar">
                            Ubah Status
                        </button>
                    </div>
                </article>

                <article class="kamar-card">
                    <div class="kamar-card-header">
                        <div>
                            <h3>Kamar E</h3>
                            <p>Rp 650.000/bulan</p>
                            <small>Andi Wijaya</small>
                        </div>

                        <span class="kamar-status terisi">Terisi</span>
                    </div>

                    <div class="kamar-card-actions">
                        <button type="button" class="btn-kamar">
                            Ubah
                        </button>

                        <button type="button" class="btn-kamar">
                            Riwayat Penghuni
                        </button>
                    </div>
                </article>

            </div>
        </div>

        <!-- TAB PENGHUNI: KITA KERJAKAN NANTI -->
        <div id="tab-penghuni" class="data-tab-content" hidden>

            <!-- PENGAJUAN SEWA -->
            <h2 class="section-title">Pengajuan Sewa Masuk</h2>

            <article class="penghuni-card">
                <div class="penghuni-info">
                    <div>
                        <h3>Rian Saputra</h3>
                        <p>Memilih Kamar B</p>
                        <small>Pengajuan sewa baru</small>
                    </div>

                    <span class="penghuni-status menunggu">
                        Menunggu
                    </span>
                </div>

                <div class="penghuni-actions">
                    <button type="button" class="btn-penghuni btn-tolak">
                        Tolak
                    </button>

                    <button type="button" class="btn-penghuni btn-setujui">
                        Setujui
                    </button>
                </div>
            </article>

            <!-- CALON PENGHUNI -->
            <h2 class="section-title">Calon Penghuni</h2>

            <article class="penghuni-card">
                <div class="penghuni-info">
                    <div>
                        <h3>Dewi Lestari</h3>
                        <p>Kamar C</p>
                        <small>Menunggu proses sewa</small>
                    </div>

                    <span class="penghuni-status menunggu">
                        Calon
                    </span>
                </div>

                <div class="penghuni-actions">
                    <button type="button" class="btn-penghuni btn-detail">
                        Lihat Pembayaran
                    </button>
                </div>
            </article>

            <!-- PENGHUNI AKTIF -->
            <h2 class="section-title">Penghuni Aktif</h2>

            <article class="penghuni-card">
                <div class="penghuni-info">
                    <div>
                        <h3>Andi Wijaya</h3>
                        <p>Kamar E</p>
                        <small>Status sewa aktif</small>
                    </div>

                    <span class="penghuni-status aktif">
                        Aktif
                    </span>
                </div>

                <div class="penghuni-actions">
                    <button type="button" class="btn-penghuni btn-detail">
                        Ubah Data
                    </button>

                    <button type="button" class="btn-penghuni btn-detail">
                        Riwayat Penghuni
                    </button>
                </div>
            </article>

        </div>
    </section>

    <!-- HALAMAN LAPORAN -->
    <section id="laporan-pembayaran" class="page-section">
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


    <!-- HALAMAN TAMBAH KAMAR -->
    <section id="tambah-kamar" class="page-section">
        <div class="kamar-form-content">
            <h2>Tambah Kamar Baru</h2>

            <form id="formTambahKamar">
                <label for="tambahNomor">Nomor / nama kamar</label>
                <input type="text" id="tambahNomor"
                    name="nomor_kamar" placeholder="Kamar K" required>

                <label for="tambahHarga">Harga sewa per bulan (Rp)</label>
                <input type="number" id="tambahHarga"
                    name="harga_bulanan" placeholder="650000"
                    min="1" required>

                <label for="tambahDeskripsi">Deskripsi Kamar</label>
                <textarea id="tambahDeskripsi" name="deskripsi"
                        placeholder="Tuliskan deskripsi kamar"></textarea>

                <label for="tambahFoto">Foto Kamar</label>
                <input type="file" id="tambahFoto" name="foto"
                    accept=".jpg,.jpeg,.png">

                <button type="submit" class="btn-primary">
                    Simpan Kamar
                </button>

                <button type="button" class="btn-secondary"
                        data-kembali-kamar>
                    Batal
                </button>
            </form>
        </div>
    </section>

    <!-- HALAMAN UBAH KAMAR -->
    <section id="ubah-kamar" class="page-section">
        <div class="kamar-form-content">
            <h2>Ubah Data Kamar</h2>

            <form id="formUbahKamar">
                <input type="hidden" id="ubahId" name="id_kamar">

                <label for="ubahNomor">Nomor / nama kamar</label>
                <input type="text" id="ubahNomor"
                    name="nomor_kamar" required>

                <label for="ubahHarga">Harga sewa per bulan (Rp)</label>
                <input type="number" id="ubahHarga"
                    name="harga_bulanan" min="1" required>

                <label for="ubahDeskripsi">Deskripsi Kamar</label>
                <textarea id="ubahDeskripsi" name="deskripsi"></textarea>

                <label for="ubahFoto">Foto Kamar</label>
                <input type="file" id="ubahFoto" name="foto"
                    accept=".jpg,.jpeg,.png">

                <button type="submit" class="btn-primary">
                    Simpan Perubahan
                </button>

                <button type="button" class="btn-secondary"
                        data-kembali-kamar>
                    Batal
                </button>
            </form>
        </div>
    </section>

    </div>
    </section>


</main>

<!-- NAVIGASI BAWAH -->
<nav class="bottom-nav" aria-label="Navigasi admin">

    <button
        class="nav-link active"
        type="button"
        data-page="dashboard"
    >
        <span class="nav-icon" href="dashboard.php">⌂</span>
        Dashboard
    </button>

    <button
        class="nav-link"
        type="button"
        data-page="pembayaran"
    >
        <span class="nav-icon">▣</span>
        Pembayaran
    </button>

    <a href="kamar.php"
       class="nav-link <?= $halamanAktif === 'data' ? 'active' : '' ?>">
        <span class="nav-icon">▤</span>
        Data
    </a>

    <button
        class="nav-link"
        type="button"
        data-page="laporan-pembayaran"
    >
        <span class="nav-icon">▧</span>
        Laporan
    </button>

</nav>

<script src="../assets/js/admin_dashboard.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>
