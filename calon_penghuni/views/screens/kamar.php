<?php if ($error): ?><p class="err" role="alert"><?= h($error) ?></p><?php endif; ?>

<?php if (!$kamarKosong): ?>
    <p class="empty-state">Belum ada kamar kosong saat ini.</p>
<?php else: foreach ($kamarKosong as $kamar): ?>

    <a href="index.php?halaman=detail&amp;id=<?= (int) $kamar['id_kamar'] ?>" class="card kamar-card">

        <?php if (!empty($kamar['foto_kamar'])): ?>
            <img
                src="../<?= h($kamar['foto_kamar']) ?>"
                alt="Foto Kamar <?= h($kamar['nomor_kamar']) ?>"
                class="room-photo"
            >
        <?php else: ?>
            <div class="room-photo-placeholder">
                Foto belum tersedia
            </div>
        <?php endif; ?>

        <div class="kamar-info">
            <h2>Kamar <?= h($kamar['nomor_kamar']) ?></h2>
            <p>Rp <?= h(number_format((float) $kamar['harga_bulanan'], 0, ',', '.')) ?>/bulan</p>
            <p>Siap Huni</p>
        </div>

    </a>

<?php endforeach; endif; ?>