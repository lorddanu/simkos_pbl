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

require_once __DIR__ . '/../database/database.php';

include __DIR__ . '/../includes_admin/header.php';

$bulanDipilih = trim($_GET['bulan'] ?? '');
if (!preg_match('/^\d{4}-\d{2}$/', $bulanDipilih)) {
    $bulanDipilih = '';
}

function formatRupiah(float $nominal): string
{
    if ($nominal >= 1000000) {
        $jutaan = $nominal / 1000000;
        return 'Rp ' . rtrim(rtrim(number_format($jutaan, 2, ',', '.'), '0'), ',') . 'jt';
    }

    return 'Rp ' . number_format($nominal, 0, ',', '.');
}

$laporanError = null;
$laporanRows = [];
$totalPendapatan = 0.0;
$jumlahLunas = 0;
$bulanSebelumnya = [];
$namaBulan = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember',
];

try {
    $conn = getConnection();

    if ($bulanDipilih === '') {
        $stmtBulanTerbaru = $conn->query(
            "SELECT to_char(tanggal_bayar, 'YYYY-MM') AS bulan
             FROM pembayaran
             WHERE status_verifikasi = 'diterima'
             ORDER BY tanggal_bayar DESC
             LIMIT 1"
        );
        $bulanTerbaru = $stmtBulanTerbaru->fetchColumn();
        $bulanDipilih = $bulanTerbaru ?: date('Y-m');
    }

    $bulanAwal = new DateTimeImmutable($bulanDipilih . '-01');
    $bulanBerikutnya = $bulanAwal->modify('+1 month');
    $bulanTerakhir = $bulanBerikutnya->modify('-1 day');

    $stmt = $conn->prepare(
        "SELECT
            s.id_sewa,
            h.nama AS nama_penghuni,
            k.nomor_kamar,
            COALESCE(SUM(p.jumlah) FILTER (
                WHERE p.status_verifikasi = 'diterima'
            ), 0) AS uang_masuk
         FROM sewa s
         JOIN penghuni h ON h.id_penghuni = s.id_penghuni
         JOIN kamar k ON k.id_kamar = s.id_kamar
         LEFT JOIN pembayaran p
            ON p.id_sewa = s.id_sewa
           AND p.tanggal_bayar >= :bulan_awal
           AND p.tanggal_bayar < :bulan_berikutnya
         WHERE s.tanggal_mulai <= :bulan_akhir
           AND (s.tanggal_selesai IS NULL OR s.tanggal_selesai >= :bulan_awal_sewa)
         GROUP BY s.id_sewa, h.nama, k.nomor_kamar
         ORDER BY k.nomor_kamar, h.nama"
    );
    $stmt->execute([
        'bulan_awal' => $bulanAwal->format('Y-m-d'),
        'bulan_berikutnya' => $bulanBerikutnya->format('Y-m-d'),
        'bulan_akhir' => $bulanTerakhir->format('Y-m-d'),
        'bulan_awal_sewa' => $bulanAwal->format('Y-m-d'),
    ]);
    $laporanRows = $stmt->fetchAll();

    foreach ($laporanRows as $row) {
        $uangMasuk = (float) $row['uang_masuk'];
        $totalPendapatan += $uangMasuk;
        if ($uangMasuk > 0) {
            $jumlahLunas++;
        }
    }

    for ($offset = 1; $offset <= 3; $offset++) {
        $bulanRingkasan = $bulanAwal->modify("-{$offset} month");
        $berikutnyaRingkasan = $bulanRingkasan->modify('+1 month');

        $stmtRingkasan = $conn->prepare(
            "SELECT
                COALESCE(SUM(jumlah), 0) AS total,
                COUNT(DISTINCT id_sewa) AS kamar_lunas
             FROM pembayaran
             WHERE status_verifikasi = 'diterima'
               AND tanggal_bayar >= :bulan_awal
               AND tanggal_bayar < :bulan_berikutnya"
        );
        $stmtRingkasan->execute([
            'bulan_awal' => $bulanRingkasan->format('Y-m-d'),
            'bulan_berikutnya' => $berikutnyaRingkasan->format('Y-m-d'),
        ]);
        $ringkasan = $stmtRingkasan->fetch();

        $bulanSebelumnya[] = [
            'value' => $bulanRingkasan->format('Y-m'),
            'label' => $namaBulan[(int) $bulanRingkasan->format('n')] . ' ' . $bulanRingkasan->format('Y'),
            'total' => (float) $ringkasan['total'],
            'kamar_lunas' => (int) $ringkasan['kamar_lunas'],
        ];
    }

    $bulanDipilihLabel = $namaBulan[(int) $bulanAwal->format('n')] . ' ' . $bulanAwal->format('Y');
} catch (PDOException $e) {
    error_log($e->getMessage());
    $laporanError = 'Laporan pembayaran gagal dimuat dari database.';
    $bulanAwal = new DateTimeImmutable(date('Y-m') . '-01');
    $bulanDipilih = $bulanAwal->format('Y-m');
    $bulanDipilihLabel = $namaBulan[(int) $bulanAwal->format('n')] . ' ' . $bulanAwal->format('Y');
}
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

            <div class="month-navigation">
                <a
                    class="month-arrow"
                    href="?bulan=<?= htmlspecialchars($bulanAwal->modify('-1 month')->format('Y-m')) ?>#laporan-pembayaran"
                    aria-label="Bulan sebelumnya"
                >
                    &#8249;
                </a>

                <label class="month-selector" for="bulanLaporan">
                    <span aria-hidden="true">&#128197;</span>
                    <input
                        type="month"
                        id="bulanLaporan"
                        name="bulan"
                        value="<?= htmlspecialchars($bulanDipilih) ?>"
                    >
                </label>

                <a
                    class="month-arrow"
                    href="?bulan=<?= htmlspecialchars($bulanAwal->modify('+1 month')->format('Y-m')) ?>#laporan-pembayaran"
                    aria-label="Bulan berikutnya"
                >
                    &#8250;
                </a>
            </div>

            <div class="stats-grid report-stats">
                <article class="stat-card">
                    <span class="stat-number"><?= htmlspecialchars(formatRupiah($totalPendapatan)) ?></span>
                    <span class="stat-label">Pendapatan diterima</span>
                </article>

                <article class="stat-card">
                    <span class="stat-number"><?= $jumlahLunas ?></span>
                    <span class="stat-label">Lunas</span>
                </article>
            </div>

            <button
                type="button"
                id="downloadReport"
                class="action-button report-download"
            >
                Unduh Laporan
            </button>

            <?php if ($laporanError): ?>
                <section class="panel payment-report-panel">
                    <p class="item-description"><?= htmlspecialchars($laporanError) ?></p>
                </section>
            <?php else: ?>
                <section class="payment-report-panel">
                    <div class="payment-report-scroll">
                        <table class="payment-report-table">
                            <thead>
                                <tr>
                                    <th>Nama Penghuni</th>
                                    <th>Kamar</th>
                                    <th>Status Pembayaran</th>
                                    <th>Uang Masuk</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($laporanRows)): ?>
                                    <tr>
                                        <td colspan="4" class="payment-report-empty">
                                            Belum ada data pembayaran pada bulan ini.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($laporanRows as $row): ?>
                                        <?php
                                        $uangMasuk = (float) $row['uang_masuk'];
                                        $sudahBayar = $uangMasuk > 0;
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['nama_penghuni']) ?></td>
                                            <td><?= htmlspecialchars($row['nomor_kamar']) ?></td>
                                            <td>
                                                <span class="payment-status <?= $sudahBayar ? 'paid' : 'unpaid' ?>">
                                                    <?= $sudahBayar ? 'Sudah Bayar' : 'Belum Bayar' ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars(formatRupiah($uangMasuk)) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3"><strong>Total Pendapatan</strong></td>
                                    <td><strong><?= htmlspecialchars(formatRupiah($totalPendapatan)) ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>
            <?php endif; ?>

            <section class="previous-report-section">
                <h2>Laporan Bulan Sebelumnya</h2>

                <div class="previous-report-list">
                    <?php foreach ($bulanSebelumnya as $ringkasan): ?>
                        <a
                            class="previous-report-card"
                            href="?bulan=<?= htmlspecialchars($ringkasan['value']) ?>#laporan-pembayaran"
                        >
                            <div>
                                <strong><?= htmlspecialchars($ringkasan['label']) ?></strong>
                                <span><?= $ringkasan['kamar_lunas'] ?> kamar lunas</span>
                            </div>
                            <div class="previous-report-total">
                                <?= htmlspecialchars(formatRupiah($ringkasan['total'])) ?>
                                <span aria-hidden="true">&#8250;</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

        </div>

    </section>

</main>

<?php include __DIR__ . '/../includes_admin/footer.php'; ?>
