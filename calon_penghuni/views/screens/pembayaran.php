<div class="upload-heading">
  <h2>Unggah Bukti Pembayaran</h2>
  <p>Kamar <?= h($pengajuan['nomor_kamar']) ?></p>
</div>
<?php if ($error): ?><p class="err" role="alert"><?= h($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data" action="index.php?halaman=pembayaran" class="upload-form payment-upload-form">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <input type="hidden" name="aksi" value="kirim_pembayaran">
  <section class="cost-card">
    <div class="cost-row"><span>Sewa Kamar</span><span>Rp <?= h(number_format((float) $pengajuan['total'], 2, ',', '.')) ?></span></div>
    <hr>
    <div class="cost-row total-row"><strong>Total Tagihan</strong><strong>Rp <?= h(number_format((float) $pengajuan['total'], 2, ',', '.')) ?></strong></div>
    <small>Transfer sesuai Total Tagihan agar verifikasi lebih cepat diproses admin.</small>
  </section>
  <a class="btn payment-link" href="index.php?halaman=metode">Metode Pembayaran yang Tersedia <span aria-hidden="true">›</span></a>
  <label class="field-label" for="bukti">Bukti Verifikasi (opsional untuk simulasi)</label>
  <label class="proof-picker" for="bukti"><strong id="proof-label">Ketuk untuk unggah foto</strong><small>Boleh dilewati · JPG, PNG, WebP maksimal 5 MB</small></label>
  <input class="proof-input" type="file" id="bukti" name="bukti" accept="image/jpeg,image/png,image/webp">
  <label class="field-label" for="catatan-bayar">Catatan (opsional)</label>
  <textarea class="note-input" id="catatan-bayar" name="catatan" placeholder="Transfer dari rekening an. orang tua, dll."></textarea>
  <div class="bottom-action"><button class="btn" type="submit">Kirim untuk Diverifikasi</button></div>
</form>
<script>
document.getElementById('bukti').addEventListener('change', function(){
  document.getElementById('proof-label').textContent = this.files[0]?.name || 'Ketuk untuk unggah foto';
});
</script>
