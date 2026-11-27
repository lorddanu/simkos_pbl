<?php
require __DIR__ . '/../database/helpers.php';
$user = require_login();
if ($user['role'] !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak.');
}

$pdo = db();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        http_response_code(400);
        exit('Permintaan tidak valid.');
    }
    $idSaran = filter_input(INPUT_POST, 'id_saran', FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? '';
    if ($idSaran && in_array($status, ['belum_dibaca', 'diproses', 'selesai'], true)) {
        $st = $pdo->prepare('UPDATE saran_kritik SET status = ? WHERE id_saran = ?');
        $st->execute([$status, $idSaran]);
        header('Location: laporan.php');
        exit;
    }
    $error = 'Data perubahan status tidak valid.';
}

$jumlahBelumDibaca = (int) $pdo->query("SELECT COUNT(*) FROM saran_kritik WHERE status = 'belum_dibaca'")->fetchColumn();
$totalBulanIni = (int) $pdo->query("SELECT COUNT(*) FROM saran_kritik WHERE tanggal_kirim >= date_trunc('month', CURRENT_DATE)")->fetchColumn();
$laporan = $pdo->query(
    "SELECT sk.id_saran, sk.jenis, sk.judul, sk.kategori, sk.isi, sk.tanggal_kirim, sk.status,
            p.nama, p.kontak, k.nomor_kamar
       FROM saran_kritik sk
       JOIN penghuni p ON p.id_penghuni = sk.id_penghuni
       LEFT JOIN LATERAL (
           SELECT kamar.nomor_kamar
             FROM sewa
             JOIN kamar ON kamar.id_kamar = sewa.id_kamar
            WHERE sewa.id_penghuni = p.id_penghuni
            ORDER BY sewa.tanggal_mulai DESC
            LIMIT 1
       ) k ON TRUE
      ORDER BY sk.tanggal_kirim DESC, sk.id_saran DESC"
)->fetchAll();

$halamanAktif = 'laporan';
include __DIR__ . '/../includes/header.php';
?>

<main class="admin-main laporan-page">
    <section id="laporan" class="page-section active">
        <h1 class="page-title">Saran dan Kritik Admin</h1>
        <?php if ($error): ?><p class="admin-feedback"><?= h($error) ?></p><?php endif; ?>

        <div class="laporan-content">
            <div class="laporan-statistik">
                <article class="laporan-stat-card">
                    <strong><?= $jumlahBelumDibaca ?></strong>
                    <span>Belum dibaca</span>
                </article>
                <article class="laporan-stat-card">
                    <strong><?= $totalBulanIni ?></strong>
                    <span>Total bulan ini</span>
                </article>
            </div>

            <div class="laporan-daftar">
                <?php if (!$laporan): ?>
                    <p class="item-description">Belum ada saran atau kritik dari penghuni.</p>
                <?php else: ?>
                    <?php foreach ($laporan as $item): ?>
                        <?php
                            $statusLabel = match ($item['status']) {
                                'belum_dibaca' => 'Baru',
                                'diproses' => 'Dibaca',
                                'selesai' => 'Selesai',
                                default => ucfirst(str_replace('_', ' ', $item['status']))
                            };
                            $kategoriClass = match ($item['kategori']) {
                                'Pelayanan Admin' => 'kategori-pelayanan',
                                'Kebersihan' => 'kategori-kebersihan',
                                'Lainnya' => 'kategori-lainnya',
                                default => 'kategori-fasilitas'
                            };
                        ?>
                        <article class="laporan-card">
                            <div class="laporan-card-header">
                                <div class="laporan-identitas">
                                    <strong><?= h($item['nama']) ?><?= $item['nomor_kamar'] ? ' • ' . h($item['nomor_kamar']) : '' ?></strong>
                                    <small><?= h(date('j M Y', strtotime($item['tanggal_kirim']))) ?></small>
                                </div>
                                <span class="laporan-status <?= $item['status'] === 'belum_dibaca' ? 'status-baru' : ($item['status'] === 'selesai' ? 'status-selesai' : 'status-dibaca') ?>"><?= h($statusLabel) ?></span>
                            </div>

                            <div class="laporan-pesan">
                                <span class="laporan-kategori <?= $kategoriClass ?>"><?= h($item['kategori'] ?: 'Lainnya') ?></span>
                                <p><?= $item['isi'] !== '' ? nl2br(h($item['isi'])) : 'Tidak ada catatan tambahan.' ?></p>
                            </div>

                            <?php if (!empty($item['kontak'])): ?>
                                <small class="laporan-kontak">Kontak: <?= h($item['kontak']) ?></small>
                            <?php endif; ?>

                            <?php if ($item['status'] !== 'selesai'): ?>
                                <form method="post" class="laporan-actions">
                                    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                                    <input type="hidden" name="id_saran" value="<?= (int) $item['id_saran'] ?>">
                                    <input type="hidden" name="status" value="<?= $item['status'] === 'belum_dibaca' ? 'diproses' : 'selesai' ?>">
                                    <button class="laporan-btn <?= $item['status'] === 'belum_dibaca' ? 'btn-dibaca' : 'btn-selesai' ?>" type="submit">
                                        <?= $item['status'] === 'belum_dibaca' ? 'Tandai Dibaca' : 'Tandai Selesai' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
