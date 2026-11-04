<?php
require __DIR__ . '/../database/helpers.php';
$user = require_login();
if ($user['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak.');
}
$nama = $user['nama'] ?? 'Admin SIMKOS';
$pdo = db();
$pesanAdmin = $_SESSION['flash_admin'] ?? '';
unset($_SESSION['flash_admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_pengajuan'])) {
    if (!csrf_ok()) {
        http_response_code(400);
        exit('Permintaan tidak valid.');
    }

    $idPengajuan = filter_input(INPUT_POST, 'id_pengajuan', FILTER_VALIDATE_INT) ?: 0;
    $aksiPengajuan = $_POST['aksi_pengajuan'];
    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare(
            "SELECT ps.id_pengajuan, ps.id_kamar, ps.status, k.status AS status_kamar
               FROM pengajuan_sewa ps JOIN kamar k USING (id_kamar)
              WHERE ps.id_pengajuan = ? FOR UPDATE OF ps, k"
        );
        $st->execute([$idPengajuan]);
        $rowPengajuan = $st->fetch();

        if (!$rowPengajuan || $rowPengajuan['status'] !== 'menunggu_persetujuan') {
            throw new RuntimeException('Pengajuan sudah diproses atau tidak ditemukan.');
        }
        if ($aksiPengajuan === 'setujui') {
            if ($rowPengajuan['status_kamar'] !== 'kosong') {
                throw new RuntimeException('Kamar sudah tidak tersedia.');
            }
            demo_track_before_change('pengajuan_sewa', $idPengajuan);
            $st = $pdo->prepare(
                "UPDATE pengajuan_sewa
                    SET status = 'menunggu_pembayaran', ditinjau_oleh = ?, ditinjau_pada = NOW(),
                        catatan_admin = NULL, notifikasi_dibaca = FALSE, diperbarui_pada = NOW()
                  WHERE id_pengajuan = ?"
            );
            $st->execute([(int) $user['id_akun'], $idPengajuan]);
            $_SESSION['flash_admin'] = 'Pengajuan disetujui. Calon penghuni dapat mengajukan pembayaran.';
        } elseif ($aksiPengajuan === 'tolak') {
            $alasan = trim($_POST['catatan_admin'] ?? '');
            demo_track_before_change('pengajuan_sewa', $idPengajuan);
            $st = $pdo->prepare(
                "UPDATE pengajuan_sewa
                    SET status = 'ditolak', ditinjau_oleh = ?, ditinjau_pada = NOW(),
                        catatan_admin = ?, notifikasi_dibaca = FALSE, diperbarui_pada = NOW()
                  WHERE id_pengajuan = ?"
            );
            $st->execute([(int) $user['id_akun'], $alasan !== '' ? $alasan : null, $idPengajuan]);
            $_SESSION['flash_admin'] = 'Pengajuan ditolak dan calon penghuni diberi notifikasi.';
        } else {
            throw new RuntimeException('Aksi pengajuan tidak dikenal.');
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash_admin'] = $e instanceof RuntimeException ? $e->getMessage() : 'Pengajuan gagal diproses.';
        if (!$e instanceof RuntimeException) error_log($e->getMessage());
    }
    header('Location: kamar.php?tab=penghuni');
    exit;
}

$kamarList = $pdo->query('SELECT id_kamar, nomor_kamar, status, harga_bulanan FROM kamar ORDER BY nomor_kamar')->fetchAll();
$totalKamar = count($kamarList);
$jumlahKamarKosong = count(array_filter($kamarList, static fn($k) => $k['status'] === 'kosong'));
$st = $pdo->query(
    "SELECT ps.*, p.nama, a.email, k.nomor_kamar
       FROM pengajuan_sewa ps
       JOIN penghuni p USING (id_penghuni)
       JOIN akun a USING (id_penghuni)
       JOIN kamar k USING (id_kamar)
      WHERE ps.status = 'menunggu_persetujuan'
      ORDER BY ps.dibuat_pada"
);
$pengajuanMasuk = $st->fetchAll();
$calonPenghuni = $pdo->query(
    "SELECT p.nama, a.email, a.created_at
       FROM akun a JOIN penghuni p USING (id_penghuni)
      WHERE a.role = 'calon_penghuni'
      ORDER BY a.created_at DESC"
)->fetchAll();
$penghuniAktif = $pdo->query(
    "SELECT p.id_penghuni, p.nama, a.email, k.nomor_kamar, s.tanggal_mulai
       FROM sewa s JOIN penghuni p USING (id_penghuni)
       JOIN akun a USING (id_penghuni) JOIN kamar k USING (id_kamar)
      WHERE s.tanggal_selesai IS NULL OR s.tanggal_selesai >= CURRENT_DATE
      ORDER BY p.nama"
)->fetchAll();

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
                    <span class="kamar-stat-number"><?= $totalKamar ?></span>
                    <span>Total Kamar</span>
                </article>

                <article class="kamar-stat-card">
                    <span class="kamar-stat-number"><?= $jumlahKamarKosong ?></span>
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
                <?php if (!$kamarList): ?><p>Belum ada data kamar.</p><?php endif; ?>
                <?php foreach ($kamarList as $kamar): ?>
                    <article class="kamar-card" data-id="<?= (int) $kamar['id_kamar'] ?>">
                        <div class="kamar-card-header">
                            <div><h3><?= h($kamar['nomor_kamar']) ?></h3><p>Rp <?= h(number_format((float) $kamar['harga_bulanan'], 0, ',', '.')) ?>/bulan</p></div>
                            <strong class="kamar-status <?= h($kamar['status']) ?>"><?= h(ucfirst($kamar['status'])) ?></strong>
                        </div>
                        <div class="kamar-card-actions"><button type="button" class="btn-kamar" data-ubah-status>Ubah Status</button><button type="button" class="btn-kamar" data-riwayat-kamar>Riwayat</button></div>
                    </article>
                <?php endforeach; ?>
            </div>

        </div>

        <!-- TAB PENGHUNI -->
        <div id="tab-penghuni" class="data-tab-content" hidden>

            <?php if ($pesanAdmin): ?><p class="admin-feedback" role="status"><?= h($pesanAdmin) ?></p><?php endif; ?>
            <h2 class="section-title">Pengajuan Sewa Masuk</h2>
            <?php if (!$pengajuanMasuk): ?><p>Belum ada pengajuan sewa masuk.</p><?php endif; ?>
            <?php foreach ($pengajuanMasuk as $pengajuan): ?>
                <article class="admin-application-card">
                    <div><strong><?= h($pengajuan['nama']) ?> · Kamar <?= h($pengajuan['nomor_kamar']) ?></strong><small><?= h($pengajuan['email']) ?></small></div>
                    <p><?= h($pengajuan['tanggal_mulai']) ?> sampai <?= h($pengajuan['tanggal_selesai']) ?> · <?= (int) $pengajuan['jumlah_bulan'] ?> bulan</p>
                    <p>Total: Rp <?= h(number_format((float) $pengajuan['total'], 0, ',', '.')) ?></p>
                    <?php if (!empty($pengajuan['catatan'])): ?><p><?= h($pengajuan['catatan']) ?></p><?php endif; ?>
                    <form method="post" class="admin-application-actions">
                        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="id_pengajuan" value="<?= (int) $pengajuan['id_pengajuan'] ?>">
                        <button class="btn-primary" name="aksi_pengajuan" value="setujui">Setujui</button>
                        <input type="text" name="catatan_admin" maxlength="500" placeholder="Alasan penolakan (opsional)">
                        <button class="btn-secondary" name="aksi_pengajuan" value="tolak">Tolak</button>
                    </form>
                </article>
            <?php endforeach; ?>

            <h2 class="section-title">Calon Penghuni</h2>
            <p>Belum ada calon penghuni.</p>

            <h2 class="section-title">Penghuni Aktif</h2>
            <?php if (!$penghuniAktif): ?><p>Belum ada penghuni aktif.</p><?php endif; ?>
            <?php foreach ($penghuniAktif as $aktif): ?><article class="admin-application-card"><strong><?= h($aktif['nama']) ?> · Kamar <?= h($aktif['nomor_kamar']) ?></strong><small><?= h($aktif['email']) ?> · Mulai <?= h($aktif['tanggal_mulai']) ?></small></article><?php endforeach; ?>

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
