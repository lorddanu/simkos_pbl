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
                    <span class="kamar-stat-number">-</span>
                    <span>Total Kamar</span>
                </article>

                <article class="kamar-stat-card">
                    <span class="kamar-stat-number">-</span>
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
                <p>Belum ada data kamar.</p>
            </div>

        </div>

        <!-- TAB PENGHUNI -->
        <div id="tab-penghuni" class="data-tab-content" hidden>

            <h2 class="section-title">Pengajuan Sewa Masuk</h2>
            <p>Belum ada pengajuan sewa masuk.</p>

            <h2 class="section-title">Calon Penghuni</h2>
            <p>Belum ada calon penghuni.</p>

            <h2 class="section-title">Penghuni Aktif</h2>
            <p>Belum ada penghuni aktif.</p>

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
                    placeholder="Masukkan nomor atau nama kamar"
                    required
                >

                <label for="tambahHarga">Harga Sewa per Bulan (Rp)</label>
                <input
                    type="number"
                    id="tambahHarga"
                    name="harga_bulanan"
                    placeholder="Masukkan harga sewa"
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

                <input
                    type="hidden"
                    id="ubahId"
                    name="id_kamar"
                >

                <label for="ubahNomor">Nomor / Nama Kamar</label>
                <input
                    type="text"
                    id="ubahNomor"
                    name="nomor_kamar"
                    placeholder="Masukkan nomor atau nama kamar"
                    required
                >

                <label for="ubahHarga">Harga Sewa per Bulan (Rp)</label>
                <input
                    type="number"
                    id="ubahHarga"
                    name="harga_bulanan"
                    placeholder="Masukkan harga sewa"
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
                    <h3 id="statusNomorKamar">-</h3>
                    <p>-</p>
                </div>

                <div>
                    <small>Status saat ini</small>
                    <strong id="statusSaatIni">-</strong>
                </div>
            </div>

            <form id="formUbahStatus">

                <input
                    type="hidden"
                    id="statusIdKamar"
                    name="id_kamar"
                >

                <p class="form-label">Pilih Status Baru</p>

                <label class="status-option">
                    <input
                        type="radio"
                        name="status"
                        value="Kosong"
                    >

                    <span class="status-option-info">
                        <strong>Kosong</strong>
                        <small>
                            Kamar siap dihuni dan tampil di daftar kamar tersedia.
                        </small>
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
                        <small>
                            Renovasi atau ditutup sementara, tidak tampil di halaman publik.
                        </small>
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
                        <small>
                            Kamar sedang dihuni oleh penghuni aktif.
                        </small>
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
                    placeholder="Email penghuni"
                    required
                >

                <label for="penghuniTelepon">No. Handphone</label>
                <input
                    type="tel"
                    id="penghuniTelepon"
                    name="no_hp"
                    placeholder="Nomor handphone"
                >

                <label for="penghuniKamar">Kamar</label>
                <select
                    id="penghuniKamar"
                    name="id_kamar"
                    required
                >
                    <option value="" selected disabled>
                        Pilih kamar
                    </option>
                </select>

                <label for="penghuniTanggal">Tanggal Mulai Sewa</label>
                <input
                    type="date"
                    id="penghuniTanggal"
                    name="tanggal_mulai"
                    required
                >

                <label>Status Penghuni</label>

                <div class="status-penghuni-buttons">
                    <label>
                        <input
                            type="radio"
                            name="status_penghuni"
                            value="Aktif"
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

            <h2>Riwayat Huni Kamar</h2>

            <div class="riwayat-list">
                <p>Belum ada riwayat huni kamar.</p>
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

                <p>Belum ada riwayat kamar.</p>

                <div class="garis-riwayat"></div>

                <!-- RIWAYAT PEMBAYARAN -->
                <h3>Riwayat Pembayaran</h3>

                <p>Belum ada riwayat pembayaran.</p>

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