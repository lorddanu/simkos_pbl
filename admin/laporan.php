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
            p.nama, p.kontak
       FROM saran_kritik sk
       JOIN penghuni p ON p.id_penghuni = sk.id_penghuni
      ORDER BY sk.tanggal_kirim DESC, sk.id_saran DESC"
)->fetchAll();

$halamanAktif = 'laporan';
include __DIR__ . '/../includes/header.php';
?>

<main class="admin-main">
    <section id="laporan" class="page-section active">
        <h1 class="page-title">Laporan Saran dan Kritik</h1>
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
                        <article class="laporan-card">
                            <div class="laporan-card-header">
                                <strong><?= h($item['kategori'] ?: (ucfirst($item['jenis']) . ': ' . $item['judul'])) ?></strong>
                                <span class="status pending"><?= h(ucfirst(str_replace('_', ' ', $item['status']))) ?></span>
                            </div>
                            <p><strong>Penghuni:</strong> <?= h($item['nama']) ?></p>
                            <?php if (!empty($item['kontak'])): ?>
                                <p><strong>Kontak:</strong> <?= h($item['kontak']) ?></p>
                            <?php endif; ?>
                            <p><?= nl2br(h($item['isi'])) ?></p>
                            <small><?= h(date('d-m-Y H:i', strtotime($item['tanggal_kirim']))) ?></small>
                            <form method="post" class="admin-application-actions">
                                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="id_saran" value="<?= (int) $item['id_saran'] ?>">
                                <select name="status" aria-label="Status laporan">
                                    <option value="belum_dibaca" <?= $item['status'] === 'belum_dibaca' ? 'selected' : '' ?>>Belum dibaca</option>
                                    <option value="diproses" <?= $item['status'] === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                                    <option value="selesai" <?= $item['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                </select>
                                <button class="btn-primary" type="submit">Simpan Status</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
