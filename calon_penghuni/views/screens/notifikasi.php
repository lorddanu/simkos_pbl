<section class="notification-screen">
  <h2>Notifikasi</h2>
  <?php if ($pengajuan): ?>
    <article class="notification-card">
      <span class="notification-card-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M4 5h16v12H8l-4 3z"/><path d="M8 9h8M8 13h5"/></svg>
      </span>
      <div>
        <h3>Pengajuan sewa terkirim</h3>
        <p>Pengajuan sewa Kamar <?= h($pengajuan['nomor_kamar']) ?> sedang menunggu persetujuan admin.</p>
        <strong>Menunggu Persetujuan</strong>
      </div>
    </article>
  <?php else: ?>
    <p class="notification-empty">Belum ada notifikasi. Informasi status pengajuan akan tampil di sini.</p>
  <?php endif; ?>
</section>
