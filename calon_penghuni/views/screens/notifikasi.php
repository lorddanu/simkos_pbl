<section class="notification-screen">
  <h2>Notifikasi</h2>
  <?php if ($pengajuan && in_array($pengajuan['status'], ['menunggu_pembayaran', 'pembayaran_ditolak'], true)): ?>
    <section class="notification-approved">
      <svg viewBox="0 0 64 64" width="40" height="40" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#78b83f"/><path d="M18 33l9 9 19-20" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <h2><?= $pengajuan['status'] === 'menunggu_pembayaran' ? 'Pengajuan Diterima' : 'Pembayaran Perlu Diajukan Ulang' ?></h2>
      <p><?= $pengajuan['status'] === 'menunggu_pembayaran' ? 'Admin menyetujui pengajuan sewa Anda. Silakan ajukan pembayaran.' : 'Pembayaran sebelumnya ditolak admin. Silakan periksa kembali dan kirim ulang.' ?></p>
      <small>Kamar <?= h($pengajuan['nomor_kamar']) ?></small>
      <div class="notification-actions">
        <a class="btn" href="index.php?halaman=pembayaran"><?= $pengajuan['status'] === 'menunggu_pembayaran' ? 'Ajukan Pembayaran' : 'Kirim Ulang Pembayaran' ?></a>
        <a class="btn alt" href="index.php">Kembali ke Beranda</a>
      </div>
    </section>
  <?php elseif ($pengajuan && $pengajuan['status'] === 'menunggu_persetujuan'): ?>
    <article class="notification-card">
      <span class="notification-card-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M4 5h16v12H8l-4 3z"/><path d="M8 9h8M8 13h5"/></svg>
      </span>
      <div>
        <h3>Pengajuan sewa terkirim</h3>
        <p>Pengajuan sewa Kamar <?= h($pengajuan['nomor_kamar']) ?> sedang menunggu keputusan admin.</p>
        <strong>Menunggu Persetujuan</strong>
      </div>
    </article>
  <?php elseif ($pengajuan && $pengajuan['status'] === 'menunggu_verifikasi'): ?>
    <article class="notification-card">
      <span class="notification-card-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 5h16v12H8l-4 3z"/><path d="M8 9h8M8 13h5"/></svg></span>
      <div><h3>Pembayaran menunggu verifikasi</h3><p>Pembayaran Kamar <?= h($pengajuan['nomor_kamar']) ?> sedang diperiksa admin.</p><strong>Menunggu Verifikasi</strong></div>
    </article>
  <?php elseif ($pengajuan && $pengajuan['status'] === 'ditolak'): ?>
    <article class="notification-card"><div><h3>Pengajuan Ditolak</h3><p><?= h($pengajuan['catatan_admin'] ?: 'Pengajuan sewa Kamar ' . $pengajuan['nomor_kamar'] . ' belum disetujui. Silakan hubungi admin untuk informasi lebih lanjut.') ?></p></div></article>
  <?php elseif ($pengajuan && $pengajuan['status'] === 'sewa_aktif'): ?>
    <article class="notification-card"><div><h3>Sewa Kamar Aktif</h3><p>Pembayaran Kamar <?= h($pengajuan['nomor_kamar']) ?> telah diverifikasi. Akun Anda sekarang menjadi akun penghuni.</p><strong>Disetujui</strong></div></article>
  <?php else: ?>
    <p class="notification-empty">Belum ada notifikasi. Informasi status pengajuan akan tampil di sini.</p>
  <?php endif; ?>
</section>
