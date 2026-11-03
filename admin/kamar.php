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

// Data kamar sementara
$daftarKamar = [
    ['nomor' => 'A', 'status' => 'Kosong'],
    ['nomor' => 'B', 'status' => 'Menunggu pembayaran'],
    ['nomor' => 'C', 'status' => 'Menunggu pembayaran'],
    ['nomor' => 'D', 'status' => 'Kosong'],
    ['nomor' => 'E', 'status' => 'Terisi']
];

// Inisialisasi jumlah kamar
$totalKamar = count($daftarKamar);
$totalKosong = 0;
$totalTerisi = 0;

// Menghitung jumlah kamar
foreach ($daftarKamar as $kamar) {
    if ($kamar['status'] === 'Kosong') {
        $totalKosong++;
    } elseif ($kamar['status'] === 'Terisi') {
        $totalTerisi++;
    }
}
?>
<?php
$halamanAktif = 'data';

include __DIR__ . '/../includes/header.php';
?>

<main class="admin-main">
    <!-- DATA SIMKOS -->
    <section id="data" class="page-section active">

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
                    <span class="kamar-stat-number">
                        <?= $totalKamar ?>
                    </span>
                    <span>Total Kamar</span>
                </article>

                <article class="kamar-stat-card">
                    <span class="kamar-stat-number">
                        <?= $totalKosong ?>
                    </span>
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

                <article class="kamar-card" data-id="1">
                    <div class="kamar-card-header">
                        <div>
                            <h3>Kamar A</h3>
                            <p>Rp 650.000/bulan</p>
                        </div>
                        <span class="kamar-status kosong">Kosong</span>
                    </div>

                    <div class="kamar-card-actions">
                        <button
                            type="button"
                            class="btn-kamar"
                            data-ubah-kamar
                        >
                            Ubah
                        </button>
                        <button
                            type="button"
                            class="btn-kamar"
                            data-ubah-status
                        >
                            Ubah Status
                        </button>
                    </div>
                </article>

                <article class="kamar-card" data-id="2">
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
                        <button type="button" class="btn-kamar">Ubah</button>
                        <button type="button" class="btn-kamar">Lihat Pembayaran</button>
                    </div>
                </article>

                <article class="kamar-card" data-id="3">
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
                        <button type="button" class="btn-kamar">Ubah</button>
                        <button type="button" class="btn-kamar">Lihat Pembayaran</button>
                    </div>
                </article>

                <article class="kamar-card" data-id="4">
                    <div class="kamar-card-header">
                        <div>
                            <h3>Kamar D</h3>
                            <p>Rp 650.000/bulan</p>
                        </div>
                        <span class="kamar-status kosong">Kosong</span>
                    </div>

                    <div class="kamar-card-actions">
                        <button type="button" class="btn-kamar">Ubah</button>
                        <button type="button" class="btn-kamar">Ubah Status</button>
                    </div>
                </article>

                <article class="kamar-card" data-id="5">
                    <div class="kamar-card-header">
                        <div>
                            <h3>Kamar E</h3>
                            <p>Rp 650.000/bulan</p>
                            <small>Andi Wijaya</small>
                        </div>
                        <span class="kamar-status terisi">Terisi</span>
                    </div>

                    <div class="kamar-card-actions">
                        <button type="button" class="btn-kamar">Ubah</button>
                        <button
                            type="button"
                            class="btn-kamar"
                            data-riwayat-kamar
                        >
                            Riwayat Huni Kamar
                        </button>
                    </div>
                </article>

            </div>
        </div>

        <!-- TAB PENGHUNI -->
        <div id="tab-penghuni" class="data-tab-content" hidden>

            <h2 class="section-title">Pengajuan Sewa Masuk</h2>

            <article class="penghuni-card">
                <div class="penghuni-info">
                    <div>
                        <h3>Rian Saputra</h3>
                        <p>Memilih Kamar B</p>
                        <small>Pengajuan sewa baru</small>
                    </div>
                    <span class="penghuni-status menunggu">Menunggu</span>
                </div>

                <div class="penghuni-actions">
                    <button type="button" class="btn-penghuni btn-tolak">Tolak</button>
                    <button type="button" class="btn-penghuni btn-setujui">Setujui</button>
                </div>
            </article>

            <h2 class="section-title">Calon Penghuni</h2>

            <article class="penghuni-card">
                <div class="penghuni-info">
                    <div>
                        <h3>Dewi Lestari</h3>
                        <p>Kamar C</p>
                        <small>Menunggu proses sewa</small>
                    </div>
                    <span class="penghuni-status menunggu">Calon</span>
                </div>

                <div class="penghuni-actions">
                    <button type="button" class="btn-penghuni btn-detail">
                        Lihat Pembayaran
                    </button>
                </div>
            </article>

            <h2 class="section-title">Penghuni Aktif</h2>

            <article class="penghuni-card">
                <div class="penghuni-info">
                    <div>
                        <h3>Andi Wijaya</h3>
                        <p>Kamar E</p>
                        <small>Status sewa aktif</small>
                    </div>
                    <span class="penghuni-status aktif">Aktif</span>
                </div>

                <div class="penghuni-actions">
                    <button
                        type="button"
                        class="btn-penghuni btn-detail"
                        data-ubah-penghuni
                    >
                        Ubah Data
                    </button>
                    <button
                        type="button"
                        class="btn-penghuni btn-detail"
                        data-riwayat-penghuni
                    >
                        Riwayat Penghuni
                    </button>
                </div>
            </article>

        </div>
    </section>

    <!-- HALAMAN TAMBAH KAMAR -->
    <section id="tambah-kamar" class="page-section">
        <div class="kamar-form-content">
            <h2>Tambah Kamar Baru</h2>

            <form id="formTambahKamar">
                <label for="tambahNomor">Nomor / Nama Kamar</label>
                <input
                    type="text"
                    id="tambahNomor"
                    name="nomor_kamar"
                    placeholder="Kamar F"
                    required
                >

                <label for="tambahHarga">Harga Sewa per Bulan (Rp)</label>
                <input
                    type="number"
                    id="tambahHarga"
                    name="harga_bulanan"
                    placeholder="650000"
                    min="1"
                    required
                >

                <label for="tambahDeskripsi">Deskripsi Kamar</label>
                <textarea
                    id="tambahDeskripsi"
                    name="deskripsi"
                    placeholder="Tuliskan deskripsi kamar"
                ></textarea>

                <label for="tambahFoto">Foto Kamar</label>
                <input
                    type="file"
                    id="tambahFoto"
                    name="foto"
                    accept=".jpg,.jpeg,.png"
                >

                <button type="submit" class="btn-primary">
                    Simpan Kamar
                </button>

                <button
                    type="button"
                    class="btn-secondary"
                    data-kembali-kamar
                >
                    Batal
                </button>
            </form>
        </div>
    </section>

    <!-- HALAMAN UBAH DATA KAMAR -->
    <section id="ubah-kamar" class="page-section">
        <div class="kamar-form-content">
            <h2>Ubah Data Kamar</h2>

            <form id="formUbahKamar">
                <input type="hidden" id="ubahId" name="id_kamar">

                <label for="ubahNomor">Nomor / nama kamar</label>
                <input
                    type="text"
                    id="ubahNomor"
                    name="nomor_kamar"
                    placeholder="Kamar A"
                    required
                >

                <label for="ubahHarga">Harga sewa per bulan (Rp)</label>
                <input
                    type="number"
                    id="ubahHarga"
                    name="harga_bulanan"
                    placeholder="650000"
                    min="1"
                    required
                >

                <label for="ubahDeskripsi">Deskripsi Kamar</label>
                <textarea
                    id="ubahDeskripsi"
                    name="deskripsi"
                    placeholder="Tuliskan deskripsi kamar"
                ></textarea>

                <label for="ubahFoto">Foto Kamar</label>
                <div class="upload-foto">
                    <input
                        type="file"
                        id="ubahFoto"
                        name="foto"
                        accept=".jpg,.jpeg,.png"
                    >

                    <label for="ubahFoto">
                        <strong>Ketuk untuk unggah foto</strong>
                        <small>JPG/PNG, maks 5 MB</small>
                    </label>
                </div>

                <button
                    type="button"
                    class="btn-hapus-kamar"
                    id="btnHapusKamar"
                >
                    Hapus Kamar
                </button>

                <button type="submit" class="btn-primary">
                    Simpan Perubahan
                </button>

                <button
                    type="button"
                    class="btn-secondary"
                    data-kembali-kamar
                >
                    Batal
                </button>
            </form>
        </div>
    </section>

    <!-- POPUP KONFIRMASI HAPUS -->
    <div class="popup-overlay" id="popupHapusKamar" hidden>
        <div class="popup-hapus">
            <h3>Hapus Kamar?</h3>
            <p>Kamar ini akan hilang dari daftar.</p>

            <div class="popup-hapus-actions">
                <button
                    type="button"
                    id="btnBatalHapus"
                >
                    Batal
                </button>

                <button
                    type="button"
                    id="btnKonfirmasiHapus"
                >
                    Hapus
                </button>
            </div>
        </div>
    </div>

    <!-- HALAMAN UBAH STATUS KAMAR -->
    <section id="ubah-status-kamar" class="page-section">
        <div class="kamar-form-content">
            <h2>Ubah Status Kamar</h2>

            <!-- INFORMASI KAMAR -->
            <div class="status-kamar-info">
                <div>
                    <h3 id="statusNomorKamar">Kamar A</h3>
                    <p>Rp 650.000/bulan</p>
                </div>

                <div>
                    <small>Status saat ini</small>
                    <strong id="statusSaatIni">Kosong</strong>
                </div>
            </div>

            <form id="formUbahStatus">
                <input
                    type="hidden"
                    id="statusIdKamar"
                    name="id_kamar"
                >

                <p class="form-label">Pilih Status Baru</p>

                <label class="status-option selected">
                    <input
                        type="radio"
                        name="status"
                        value="Kosong"
                        checked
                    >
                    <span class="status-option-info">
                        <strong>Kosong</strong>
                        <small>Kamar siap dihuni dan tampil di daftar kamar tersedia.</small>
                    </span>
                </label>

                <label class="status-option">
                    <input
                        type="radio"
                        name="status"
                        value="Tidak tersedia"
                    >
                    <span class="status-option-info">
                        <strong>Tidak tersedia</strong>
                        <small>Renovasi atau ditutup sementara, tidak tampil di halaman publik.</small>
                    </span>
                </label>

                <label class="status-option">
                    <input
                        type="radio"
                        name="status"
                        value="Terisi"
                    >
                    <span class="status-option-info">
                        <strong>Terisi</strong>
                        <small>Kamar sedang dihuni oleh penghuni aktif.</small>
                    </span>
                </label>

                <label for="catatanStatus">Catatan (opsional)</label>
                <textarea
                    id="catatanStatus"
                    name="catatan"
                    placeholder="Tuliskan alasan perubahan status"
                ></textarea>

                <small class="form-hint">
                    Perubahan status akan langsung tampil di daftar kamar.
                </small>

                <button type="submit" class="btn-primary">
                    Simpan Status
                </button>

                <button
                    type="button"
                    class="btn-secondary"
                    data-kembali-data
                >
                    Batal
                </button>
            </form>
        </div>
    </section>

    <!-- HALAMAN UBAH DATA PENGHUNI -->
    <section id="ubah-data-penghuni" class="page-section">
        <div class="kamar-form-content">
            <h2>Ubah Data Penghuni</h2>

            <form id="formUbahPenghuni">
                <input
                    type="hidden"
                    id="penghuniId"
                    name="id_penghuni"
                >

                <label for="penghuniNama">Nama Lengkap</label>
                <input
                    type="text"
                    id="penghuniNama"
                    name="nama"
                    placeholder="Nama lengkap penghuni"
                    required
                >

                <label for="penghuniEmail">Email</label>
                <input
                    type="email"
                    id="penghuniEmail"
                    name="email"
                    placeholder="email@example.com"
                    required
                >

                <label for="penghuniTelepon">No. Handphone</label>
                <input
                    type="tel"
                    id="penghuniTelepon"
                    name="no_hp"
                    placeholder="0813-xxxx-xxxx"
                >

                <label for="penghuniKamar">Kamar</label>
                <select
                    id="penghuniKamar"
                    name="id_kamar"
                    required
                >
                    <option value="5" selected>Kamar E</option>
                    <option value="1">Kamar A</option>
                    <option value="2">Kamar B</option>
                    <option value="3">Kamar C</option>
                    <option value="4">Kamar D</option>
                </select>

                <label for="penghuniTanggal">Tanggal Mulai Sewa</label>
                <input
                    type="date"
                    id="penghuniTanggal"
                    name="tanggal_mulai"
                    value="2026-10-10"
                    required
                >

                <label>Status Penghuni</label>
                <div class="status-penghuni-buttons">
                    <label>
                        <input
                            type="radio"
                            name="status_penghuni"
                            value="Aktif"
                            checked
                        >
                        Aktif
                    </label>

                    <label>
                        <input
                            type="radio"
                            name="status_penghuni"
                            value="Nonaktif"
                        >
                        Nonaktif
                    </label>
                </div>

                <small class="form-hint">
                    Perubahan data akan langsung tersimpan di profil penghuni.
                </small>

                <button type="submit" class="btn-primary">
                    Simpan Perubahan
                </button>

                <button
                    type="button"
                    class="btn-secondary"
                    data-kembali-data
                >
                    Batal
                </button>
            </form>
        </div>
    </section>

    <!-- HALAMAN RIWAYAT HUNI KAMAR -->
    <section id="riwayat-huni-kamar" class="page-section">
        <div class="kamar-form-content">
            <h2>Riwayat Huni Kamar E</h2>

            <div class="riwayat-list">
                <article class="riwayat-item">
                    <div>
                        <strong>Andi Wijaya</strong>
                        <small>7 Jun 2026 - Sekarang</small>
                    </div>
                    <span class="riwayat-status aktif">Aktif</span>
                </article>

                <article class="riwayat-item">
                    <div>
                        <strong>Fajar Nugroho</strong>
                        <small>3 Jan 2025 - 31 Mei 2026</small>
                    </div>
                    <span class="riwayat-status selesai">Selesai</span>
                </article>

                <article class="riwayat-item">
                    <div>
                        <strong>Maya Lestari</strong>
                        <small>10 Agu 2024 - 28 Des 2024</small>
                    </div>
                    <span class="riwayat-status selesai">Selesai</span>
                </article>
            </div>

            <button
                type="button"
                class="btn-secondary"
                data-kembali-riwayat="kamar"
            >
                Kembali
            </button>
        </div>
    </section>

    <!-- HALAMAN RIWAYAT PENGHUNI -->
    <section id="riwayat-penghuni" class="page-section">
        <div class="kamar-form-content">
            <h2>Riwayat Penghuni</h2>

            <small class="riwayat-subtitle">
                Riwayat kamar yang ditempati dan riwayat pembayaran
            </small>

            <!-- RIWAYAT KAMAR -->
            <div class="riwayat-pembungkus">
                <h3>Riwayat Kamar</h3>

                <div class="riwayat-kamar-info">
                    <div>
                        <strong>Kamar E</strong>
                        <small>7 Jun 2026 - Sekarang</small>
                    </div>

                    <span class="riwayat-status aktif">Aktif</span>
                </div>

                <div class="garis-riwayat"></div>

                <!-- RIWAYAT PEMBAYARAN -->
                <h3>Riwayat Pembayaran</h3>

                <div class="riwayat-bayar">
                    <div>
                        <strong>November 2026</strong>
                        <small>Rp 650.000 - dibayar 1 November</small>
                    </div>
                    <span>Lunas</span>
                </div>

                <div class="riwayat-bayar">
                    <div>
                        <strong>Oktober 2026</strong>
                        <small>Rp 650.000 - dibayar 2 Oktober</small>
                    </div>
                    <span>Lunas</span>
                </div>

                <div class="riwayat-bayar">
                    <div>
                        <strong>September 2026</strong>
                        <small>Rp 650.000 - dibayar 1 September</small>
                    </div>
                    <span>Lunas</span>
                </div>

                <div class="riwayat-bayar">
                    <div>
                        <strong>Agustus 2026</strong>
                        <small>Rp 650.000 - dibayar 3 Agustus</small>
                    </div>
                    <span>Lunas</span>
                </div>

                <div class="riwayat-bayar">
                    <div>
                        <strong>Juli 2026</strong>
                        <small>Rp 650.000 - dibayar 2 Juli</small>
                    </div>
                    <span>Lunas</span>
                </div>

                <div class="riwayat-bayar">
                    <div>
                        <strong>Juni 2026</strong>
                        <small>Rp 650.000 - dibayar 1 Juni</small>
                    </div>
                    <span>Lunas</span>
                </div>
            </div>

            <button
                type="button"
                class="btn-secondary"
                data-kembali-riwayat="penghuni"
            >
                Kembali
            </button>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>