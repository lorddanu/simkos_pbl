<?php if ($error): ?><p class="err" role="alert"><?= h($error) ?></p><?php endif; ?>
<?php if (!$kamarKosong): ?>
  <p class="empty-state">Belum ada kamar kosong saat ini.</p>
<?php else: foreach ($kamarKosong as $kamar): ?>
  <a href="index.php?halaman=detail&amp;id=<?= (int) $kamar['id_kamar'] ?>" class="card kamar-card">
    <div class="room-photo-placeholder" aria-label="Tempat foto Kamar <?= h($kamar['nomor_kamar']) ?>"></div>
    <div class="kamar-info">
      <h2>Kamar <?= h($kamar['nomor_kamar']) ?></h2>
      <p>Rp <?= h(number_format((float) $kamar['harga_bulanan'], 0, ',', '.')) ?>/bulan</p>
      <p>Siap Huni</p>
    </div>
  </a>
<?php endforeach; endif; ?>
