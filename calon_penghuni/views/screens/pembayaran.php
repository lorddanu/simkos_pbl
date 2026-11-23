<div class="upload-heading">
  <h2>Unggah Bukti Pembayaran</h2>
  <p>Kamar <?= h($pengajuan['nomor_kamar']) ?></p>
</div>
<?php if ($error): ?><p class="err" role="alert"><?= h($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data" action="index.php?halaman=pembayaran" class="upload-form payment-upload-form">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <input type="hidden" name="aksi" value="kirim_pembayaran">
  <input type="hidden" name="id_pengajuan" value="<?= (int) $pengajuan['id_pengajuan'] ?>">
  <section class="cost-card">
    <div class="cost-row"><span>Sewa Kamar</span><span>Rp <?= h(number_format((float) $pengajuan['total'], 2, ',', '.')) ?></span></div>
    <hr>
    <div class="cost-row total-row"><strong>Total Tagihan</strong><strong>Rp <?= h(number_format((float) $pengajuan['total'], 2, ',', '.')) ?></strong></div>
    <small>Transfer sesuai Total Tagihan agar verifikasi lebih cepat diproses admin.</small>
  </section>
  <a class="btn payment-link" href="index.php?halaman=metode">Metode Pembayaran yang Tersedia <span aria-hidden="true">&rsaquo;</span></a>
  <label class="field-label" for="bukti">Bukti Transfer (wajib)</label>
  <label class="proof-picker" for="bukti"><strong id="proof-label">Ketuk untuk unggah foto</strong><small>Wajib &middot; JPG, PNG, WebP maksimal 5 MB</small></label>
  <input class="proof-input" type="file" id="bukti" name="bukti" required accept="image/jpeg,image/png,image/webp">
  <label class="field-label" for="catatan-bayar">Catatan (opsional)</label>
  <textarea class="note-input" id="catatan-bayar" name="catatan" placeholder="Transfer dari rekening an. orang tua, dll."></textarea>
<?php if (in_array(($pengajuan['status'] ?? ''), ['menunggu_pembayaran', 'pembayaran_ditolak'], true) && $batasAktifMs > 0): ?>
<section class="payment-deadline" data-payment-deadline data-deadline-ms="<?= $batasAktifMs ?>" data-server-now-ms="<?= $waktuServerMs ?>" data-application-id="<?= (int) $pengajuan['id_pengajuan'] ?>" data-csrf="<?= h(csrf_token()) ?>" data-expire-url="index.php?halaman=pembayaran" data-notification-url="index.php?halaman=notifikasi">
  <span class="payment-deadline-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="13" r="8"></circle><path d="M12 9v4l2.5 1.5M9 2h6M12 2v3"></path></svg></span>
  <div><strong>Batas waktu pengiriman bukti</strong><p>Sisa waktu: <b data-countdown>--:--:--</b></p><small>Ajukan sebelum <?= h($batasAktifLabel) ?>. Jika waktu habis, pengajuan dibatalkan.</small><small class="payment-deadline-message" data-deadline-message hidden>Waktu habis. Pengajuan sewa telah dibatalkan.</small></div>
</section>
<?php endif; ?>
    <div class="bottom-action"><button class="btn" type="submit" data-payment-action>Kirim untuk Diverifikasi</button></div>
</form>
<script>
document.getElementById('bukti').addEventListener('change', function(){
  document.getElementById('proof-label').textContent = this.files[0]?.name || 'Ketuk untuk unggah foto';
});
</script>

<script src="../assets/js/payment_deadline.js?v=2"></script>
