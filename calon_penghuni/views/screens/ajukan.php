<div class="upload-heading">
  <h2>Unggah Bukti Pembayaran</h2>
  <p>Kamar <?= h($kamarDipilih['nomor_kamar']) ?></p>
</div>
<?php if ($error): ?><p class="err" role="alert"><?= h($error) ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data" action="index.php?halaman=ajukan&amp;id=<?= (int) $kamarDipilih['id_kamar'] ?>" class="upload-form" id="form-sewa">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <input type="hidden" name="aksi" value="kirim_bukti">
  <input type="hidden" name="id_kamar" value="<?= (int) $kamarDipilih['id_kamar'] ?>">
  <section class="payment-card">
    <strong class="monthly-price">Rp <?= h(number_format((float) $kamarDipilih['harga_bulanan'], 0, ',', '.')) ?><small>/bulan</small></strong>
    <div class="date-row">
      <label>Tanggal Mulai Sewa<input type="date" name="tanggal_mulai" min="<?= date('Y-m-d') ?>" value="<?= h($nilaiMulai) ?>" required></label>
      <label>Tanggal Akhir Sewa<input type="date" name="tanggal_selesai" min="<?= date('Y-m-d') ?>" value="<?= h($nilaiSelesai) ?>" required></label>
    </div>
    <small class="hint">Pilih tanggal penyewaan Anda.</small>
  </section>
  <section class="duration-card">
    <label for="bulan">Jumlah Bulan yang Ingin Disewa</label>
    <div class="stepper"><button type="button" id="kurangi-bulan" aria-label="Kurangi bulan">−</button><output id="bulan-label">1</output><button type="button" id="tambah-bulan" aria-label="Tambah bulan">+</button></div>
    <input type="hidden" id="bulan" name="bulan" value="<?= $jumlahBulan ?>">
  </section>
  <section class="cost-card">
    <div class="cost-row"><span>Sewa Kamar</span><span>Rp <?= h(number_format((float) $kamarDipilih['harga_bulanan'] * $jumlahBulan, 2, ',', '.')) ?></span></div>
    <hr>
    <div class="cost-row total-row"><strong>Total Tagihan</strong><strong id="total">Rp <?= h(number_format((float) $kamarDipilih['harga_bulanan'] * $jumlahBulan, 2, ',', '.')) ?></strong></div>
    <small>Transfer sesuai total tagihan agar verifikasi dapat diproses.</small>
  </section>
  <a class="btn payment-link" id="link-metode" href="index.php?halaman=metode&amp;id=<?= (int) $kamarDipilih['id_kamar'] ?>">Metode Pembayaran yang Tersedia <span aria-hidden="true">›</span></a>
  <label class="field-label" for="bukti">Bukti Verifikasi (opsional untuk simulasi)</label>
  <label class="proof-picker" for="bukti"><strong id="proof-label">Ketuk untuk unggah foto</strong><small>Boleh dilewati · JPG, PNG, WebP maksimal 5 MB</small></label>
  <input class="proof-input" type="file" id="bukti" name="bukti" accept="image/jpeg,image/png,image/webp">
  <label class="field-label" for="catatan">Catatan (opsional)</label>
  <textarea class="note-input" id="catatan" name="catatan" placeholder="Transfer dari rekening an. orang tua, dll."></textarea>
  <div class="bottom-action"><button class="btn" type="submit">Kirim untuk Diverifikasi</button></div>
</form>
<script>
const hargaKamar = <?= json_encode((float) $kamarDipilih['harga_bulanan']) ?>;
const idKamar = <?= (int) $kamarDipilih['id_kamar'] ?>;
const bulanInput = document.getElementById('bulan');
const bulanLabel = document.getElementById('bulan-label');
const totalTagihan = document.getElementById('total');
function perbaruiTagihan(delta){
  const baru = Math.max(1, Math.min(12, Number(bulanInput.value) + delta));
  bulanInput.value = baru; bulanLabel.value = baru;
  const nominal = hargaKamar * baru;
  totalTagihan.textContent = 'Rp ' + new Intl.NumberFormat('id-ID', {minimumFractionDigits:2}).format(nominal);
  document.querySelector('.cost-row span:last-child').textContent = totalTagihan.textContent;
  const mulai = document.querySelector('[name="tanggal_mulai"]').value;
  const selesai = document.querySelector('[name="tanggal_selesai"]').value;
  document.getElementById('link-metode').href = 'index.php?halaman=metode&id=' + idKamar + '&mulai=' + encodeURIComponent(mulai) + '&selesai=' + encodeURIComponent(selesai) + '&bulan=' + baru;
}
document.getElementById('kurangi-bulan').addEventListener('click', () => perbaruiTagihan(-1));
document.getElementById('tambah-bulan').addEventListener('click', () => perbaruiTagihan(1));
document.querySelector('[name="tanggal_mulai"]').addEventListener('change', () => perbaruiTagihan(0));
document.querySelector('[name="tanggal_selesai"]').addEventListener('change', () => perbaruiTagihan(0));
document.getElementById('bukti').addEventListener('change', function(){
  document.getElementById('proof-label').textContent = this.files[0]?.name || 'Ketuk untuk unggah foto';
});
perbaruiTagihan(0);
</script>
