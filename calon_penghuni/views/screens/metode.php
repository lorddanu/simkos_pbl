<section class="method-screen">
  <div class="cost-card"><div class="cost-row"><span>Sewa Kamar</span><strong>Rp <?= h(number_format((float) $pengajuan['total'], 2, ',', '.')) ?></strong></div><hr><div class="cost-row total-row"><strong>Total Tagihan</strong><strong>Rp <?= h(number_format((float) $pengajuan['total'], 2, ',', '.')) ?></strong></div><small>Transfer sesuai Total Tagihan agar verifikasi lebih cepat diproses admin.</small></div>
  <h3>Metode Pembayaran</h3>
  <article class="method-option"><span class="method-mark bca">BCA</span><div><strong>Bank Central Asia (BCA)</strong><b>8808 0812 3456 7890</b></div></article>
  <article class="method-option qris-option"><span class="method-mark qris">QRIS</span><div><strong>QRIS</strong><div class="qris-placeholder">QRIS</div></div></article>
  <div class="bottom-action"><a class="btn" href="index.php?halaman=pembayaran">Kembali ke Unggah Bukti</a></div>
</section>
