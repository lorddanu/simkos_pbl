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

$bulanSekarang = new DateTimeImmutable(date('Y-m-01'));
$tahunIni = (int) date('Y');
$rentangBulanAwal = new DateTimeImmutable(($tahunIni - 1) . '-01-01');
$rentangBulanAkhir = new DateTimeImmutable($tahunIni . '-12-01');

$bulanDipilih = trim($_GET['bulan'] ?? '');
if (
    !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulanDipilih)
    || (int) substr($bulanDipilih, 0, 4) < 1900
    || (int) substr($bulanDipilih, 0, 4) > 9999
    || $bulanDipilih < $rentangBulanAwal->format('Y-m')
    || $bulanDipilih > $rentangBulanAkhir->format('Y-m')
) {
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
$tahunTersedia = [$tahunIni - 1, $tahunIni];
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
        $stmtBulanTerbaru = $conn->prepare(
            "SELECT to_char(tanggal_bayar, 'YYYY-MM') AS bulan
             FROM pembayaran
             WHERE status_verifikasi = 'diterima'
               AND tanggal_bayar >= :rentang_awal
               AND tanggal_bayar < :bulan_berikutnya
             ORDER BY tanggal_bayar DESC
             LIMIT 1"
        );
        $stmtBulanTerbaru->execute([
            'rentang_awal' => $rentangBulanAwal->format('Y-m-d'),
            'bulan_berikutnya' => $bulanSekarang->modify('+1 month')->format('Y-m-d'),
        ]);
        $bulanTerbaru = $stmtBulanTerbaru->fetchColumn();
        $bulanDipilih = $bulanTerbaru ?: $bulanSekarang->format('Y-m');
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

    $stmtRiwayat = $conn->prepare(
        "SELECT
            to_char(date_trunc('month', tanggal_bayar), 'YYYY-MM') AS bulan,
            COALESCE(SUM(jumlah), 0) AS total,
            COUNT(DISTINCT s.id_kamar) AS kamar_lunas
         FROM pembayaran p
         JOIN sewa s ON s.id_sewa = p.id_sewa
         WHERE p.status_verifikasi = 'diterima'
           AND p.tanggal_bayar >= :rentang_awal
           AND p.tanggal_bayar < :bulan_dipilih
         GROUP BY date_trunc('month', p.tanggal_bayar)
         ORDER BY date_trunc('month', p.tanggal_bayar) DESC
         LIMIT 12"
    );
    $stmtRiwayat->execute([
        'rentang_awal' => $rentangBulanAwal->format('Y-m-d'),
        'bulan_dipilih' => $bulanAwal->format('Y-m-d'),
    ]);
    foreach ($stmtRiwayat->fetchAll() as $ringkasan) {
        $bulanRingkasan = new DateTimeImmutable($ringkasan['bulan'] . '-01');
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
    $bulanAwal = $bulanSekarang;
    $bulanDipilih = $bulanAwal->format('Y-m');
    $bulanDipilihLabel = $namaBulan[(int) $bulanAwal->format('n')] . ' ' . $bulanAwal->format('Y');
}

$bulanSebelumnyaTersedia = $bulanAwal->modify('-1 month')->format('Y-m') >= $rentangBulanAwal->format('Y-m');
$bulanBerikutnyaTersedia = $bulanAwal->modify('+1 month')->format('Y-m') <= $rentangBulanAkhir->format('Y-m');

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

            <div class="month-picker-container">
            <div class="month-navigation">
                <?php if ($bulanSebelumnyaTersedia): ?>
                <a
                    class="month-arrow"
                    href="?bulan=<?= htmlspecialchars($bulanAwal->modify('-1 month')->format('Y-m')) ?>#laporan-pembayaran"
                    aria-label="Bulan sebelumnya"
                >
                    &#8249;
                </a>
                <?php else: ?>
                    <span class="month-arrow disabled" aria-disabled="true">&#8249;</span>
                <?php endif; ?>

                <button
                    class="month-selector"
                    id="monthPickerToggle"
                    type="button"
                    aria-expanded="false"
                    aria-controls="monthPicker"
                >
                    <span aria-hidden="true">&#128197;</span>
                    <span id="monthPickerLabel"><?= htmlspecialchars($bulanDipilihLabel) ?></span>
                </button>

                <?php if ($bulanBerikutnyaTersedia): ?>
                    <a
                        class="month-arrow"
                        href="?bulan=<?= htmlspecialchars($bulanAwal->modify('+1 month')->format('Y-m')) ?>#laporan-pembayaran"
                        aria-label="Bulan berikutnya"
                    >
                        &#8250;
                    </a>
                <?php else: ?>
                    <span class="month-arrow disabled" aria-disabled="true">&#8250;</span>
                <?php endif; ?>
            </div>

            <section
                class="month-picker"
                id="monthPicker"
                data-selected-month="<?= (int) $bulanAwal->format('n') ?>"
                data-selected-year="<?= htmlspecialchars($bulanAwal->format('Y')) ?>"
                data-range-start="<?= htmlspecialchars($rentangBulanAwal->format('Y-m')) ?>"
                data-range-end="<?= htmlspecialchars($rentangBulanAkhir->format('Y-m')) ?>"
                hidden
                aria-label="Pilih bulan dan tahun"
            >
                <div class="month-picker-heading">
                    <button class="picker-month-heading" id="pickerMonthHeading" type="button">
                        <?= htmlspecialchars($namaBulan[(int) $bulanAwal->format('n')]) ?>
                    </button>
                    <span aria-hidden="true">/</span>
                    <button class="picker-year-heading" id="pickerYearHeading" type="button">
                        <?= htmlspecialchars($bulanAwal->format('Y')) ?>
                    </button>
                    <div class="picker-heading-arrows">
                        <button type="button" id="pickerPrevious" aria-label="Sebelumnya">&#8249;</button>
                        <button type="button" id="pickerNext" aria-label="Berikutnya">&#8250;</button>
                    </div>
                </div>

                <div class="picker-month-grid" id="pickerMonthGrid">
                    <?php foreach ($namaBulan as $nomorBulan => $labelBulan): ?>
                        <?php
                        $bulanOpsi = $bulanAwal->setDate(
                            (int) $bulanAwal->format('Y'),
                            $nomorBulan,
                            1
                        )->format('Y-m');
                        $bulanOpsiTersedia = $bulanOpsi >= $rentangBulanAwal->format('Y-m')
                            && $bulanOpsi <= $rentangBulanAkhir->format('Y-m');
                        ?>
                        <button
                            type="button"
                            class="picker-month-option <?= (int) $bulanAwal->format('n') === $nomorBulan ? 'selected' : '' ?>"
                            data-month="<?= $nomorBulan ?>"
                            data-label="<?= htmlspecialchars(substr($labelBulan, 0, 3)) ?>"
                            <?= $bulanOpsiTersedia ? '' : 'disabled' ?>
                        >
                            <?= htmlspecialchars(substr($labelBulan, 0, 3)) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="picker-year-grid" id="pickerYearGrid" hidden>
                    <?php foreach ($tahunTersedia as $tahun): ?>
                        <button
                            type="button"
                            class="picker-year-option <?= (int) $bulanAwal->format('Y') === $tahun ? 'selected' : '' ?>"
                            data-year="<?= $tahun ?>"
                        >
                            <?= $tahun ?>
                        </button>
                    <?php endforeach; ?>
                    <?php if (empty($tahunTersedia)): ?>
                        <button
                            type="button"
                            class="picker-year-option selected"
                            data-year="<?= htmlspecialchars($bulanAwal->format('Y')) ?>"
                        >
                            <?= htmlspecialchars($bulanAwal->format('Y')) ?>
                        </button>
                    <?php endif; ?>
                </div>
            </section>
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
                    <div class="payment-report-list">
                        <?php if (empty($laporanRows)): ?>
                            <p class="payment-report-empty">
                                Belum ada data pembayaran pada <?= htmlspecialchars($bulanDipilihLabel) ?>.
                            </p>
                        <?php else: ?>
                            <?php foreach ($laporanRows as $row): ?>
                                <?php
                                $uangMasuk = (float) $row['uang_masuk'];
                                $sudahBayar = $uangMasuk > 0;
                                ?>
                                <article class="payment-report-row">
                                    <div class="payment-report-resident">
                                        <strong><?= htmlspecialchars($row['nama_penghuni']) ?></strong>
                                        <small>Kamar <?= htmlspecialchars($row['nomor_kamar']) ?></small>
                                    </div>
                                    <div class="payment-report-detail">
                                        <span class="payment-status <?= $sudahBayar ? 'paid' : 'unpaid' ?>">
                                            <?= $sudahBayar ? 'Lunas' : 'Belum Bayar' ?>
                                        </span>
                                        <span class="payment-report-amount">
                                            Uang Masuk: <?= htmlspecialchars(formatRupiah($uangMasuk)) ?>
                                        </span>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="payment-report-total">
                        <strong>Total Pendapatan</strong>
                        <strong><?= htmlspecialchars(formatRupiah($totalPendapatan)) ?></strong>
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
                    <?php if (empty($bulanSebelumnya)): ?>
                        <p class="previous-report-empty">
                            Belum ada riwayat pembayaran sebelum bulan ini.
                        </p>
                    <?php endif; ?>
                </div>
            </section>

        </div>

    </section>

</main>

<?php include __DIR__ . '/../includes_admin/footer.php'; ?>
