<section class="confirm">
  <svg viewBox="0 0 64 64" width="48" height="48" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#78b83f"/><path d="M18 33l9 9 19-20" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></svg>
  <h2>Pembayaran Terkirim</h2>
  <p>Pengajuan pembayaran berhasil dikirim dan menunggu verifikasi.</p>
  <div class="facility-box confirm-info">
    <div><span>Kamar</span><span>Kamar <?= h($pembayaran['nomor_kamar']) ?></span></div>
    <div><span>Nominal</span><span>Rp <?= h(number_format((float) $pembayaran['total'], 2, ',', '.')) ?></span></div>
    <div><span>Status</span><strong class="pending-status">Menunggu Verifikasi</strong></div>
  </div>
</section>
<div class="bottom-action"><a class="btn" href="index.php">Kembali ke Beranda</a></div>
