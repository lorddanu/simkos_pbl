<section class="notification-screen">
  <h2>Notifikasi</h2>
  <?php if ($pengajuan && in_array($pengajuan['status'], ['menunggu_pembayaran', 'pembayaran_ditolak'], true)): ?>
    <section class="notification-approved">
      <svg viewBox="0 0 64 64" width="40" height="40" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#78b83f"/><path d="M18 33l9 9 19-20" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <h2><?= $pengajuan['status'] === 'menunggu_pembayaran' ? 'Pengajuan Diterima' : 'Pembayaran Perlu Diajukan Ulang' ?></h2>
      <p><?= $pengajuan['status'] === 'menunggu_pembayaran' ? 'Admin menyetujui pengajuan sewa Anda. Silakan ajukan pembayaran.' : 'Pembayaran sebelumnya ditolak admin. Silakan periksa kembali dan kirim ulang.' ?></p>
      <small>Kamar <?= h($pengajuan['nomor_kamar']) ?></small>
<?php if (($pengajuan['status'] ?? '') === 'menunggu_pembayaran' && $batasPembayaranMs > 0): ?>
<section class="payment-deadline" data-payment-deadline data-deadline-ms="<?= $batasPembayaranMs ?>" data-server-now-ms="<?= $waktuServerMs ?>" data-application-id="<?= (int) $pengajuan['id_pengajuan'] ?>" data-csrf="<?= h(csrf_token()) ?>" data-expire-url="index.php?halaman=pembayaran" data-notification-url="index.php?halaman=notifikasi">
  <span class="payment-deadline-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="13" r="8"></circle><path d="M12 9v4l2.5 1.5M9 2h6M12 2v3"></path></svg></span>
  <div><strong>Batas waktu pembayaran</strong><p>Sisa waktu: <b data-countdown>--:--:--</b></p><small>Ajukan sebelum <?= h($batasPembayaranLabel) ?>. Jika waktu habis, pengajuan dibatalkan.</small><small class="payment-deadline-message" data-deadline-message hidden>Waktu habis. Pengajuan sewa telah dibatalkan.</small></div>
</section>
<?php endif; ?>
      <div class="notification-actions">
        <a class="btn" data-payment-action href="index.php?halaman=pembayaran"><?= $pengajuan['status'] === 'menunggu_pembayaran' ? 'Ajukan Pembayaran' : 'Kirim Ulang Pembayaran' ?></a>
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
  <?php elseif ($pengajuan && $pengajuan['status'] === 'kedaluwarsa'): ?>
    <article class="notification-card notification-expired"><div><h3>Waktu Pengajuan Habis</h3><p>Batas 1 hari untuk mengajukan pembayaran telah berakhir. Pengajuan sewa dibatalkan dan kamar tersedia kembali.</p><strong>Kedaluwarsa</strong></div></article>
  <?php elseif ($pengajuan && $pengajuan['status'] === 'ditolak'): ?>
    <article class="notification-card"><div><h3>Pengajuan Ditolak</h3><p><?= h($pengajuan['catatan_admin'] ?: 'Pengajuan sewa Kamar ' . $pengajuan['nomor_kamar'] . ' belum disetujui. Silakan hubungi admin untuk informasi lebih lanjut.') ?></p></div></article>
  <?php elseif ($pengajuan && $pengajuan['status'] === 'sewa_aktif'): ?>
    <article class="notification-card"><div><h3>Sewa Kamar Aktif</h3><p>Pembayaran Kamar <?= h($pengajuan['nomor_kamar']) ?> telah diverifikasi. Akun Anda sekarang menjadi akun penghuni.</p><strong>Disetujui</strong></div></article>
  <?php else: ?>
    <p class="notification-empty">Belum ada notifikasi. Informasi status pengajuan akan tampil di sini.</p>
  <?php endif; ?>
</section>

<script src="../assets/js/payment_deadline.js?v=2"></script>

