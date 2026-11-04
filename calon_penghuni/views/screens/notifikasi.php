<section class="notification-screen">
  <h2>Notifikasi</h2>
  <?php if ($pengajuan && ($pengajuan['status'] ?? '') === 'diterima'): ?>
    <section class="notification-approved">
      <svg viewBox="0 0 64 64" width="40" height="40" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#78b83f"/><path d="M18 33l9 9 19-20" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <h2>Pengajuan Diterima</h2>
      <p>Status persetujuan diterima. Silakan ajukan pembayaran.</p>
      <small>Kamar <?= h($pengajuan['nomor_kamar']) ?></small>
      <div class="notification-actions">
        <a class="btn" href="index.php?halaman=pembayaran">Ajukan Pembayaran</a>
        <a class="btn alt" href="index.php">Kembali ke Beranda</a>
      </div>
    </section>
  <?php elseif ($pengajuan): ?>
    <?php $sisaDetik = max(0, (int) ($pengajuan['persetujuan_pada'] ?? time()) - time()); ?>
    <article class="notification-card">
      <span class="notification-card-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M4 5h16v12H8l-4 3z"/><path d="M8 9h8M8 13h5"/></svg>
      </span>
      <div>
        <h3>Pengajuan sewa terkirim</h3>
        <p>Pengajuan sewa Kamar <?= h($pengajuan['nomor_kamar']) ?> sedang menunggu persetujuan.</p>
        <strong>Menunggu Persetujuan</strong>
      </div>
    </article>
    <script>window.setTimeout(() => window.location.reload(), <?= $sisaDetik * 1000 ?>);</script>
  <?php else: ?>
    <p class="notification-empty">Belum ada notifikasi. Informasi status pengajuan akan tampil di sini.</p>
  <?php endif; ?>
</section>
