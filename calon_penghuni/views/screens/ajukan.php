<div class="upload-heading">
  <h2>Ajukan Sewa Kamar</h2>
  <p>Kamar <?= h($kamarDipilih['nomor_kamar']) ?></p>
</div>
<?php if ($error): ?><p class="err" role="alert"><?= h($error) ?></p><?php endif; ?>
<form method="post" action="index.php?halaman=ajukan&amp;id=<?= (int) $kamarDipilih['id_kamar'] ?>" class="upload-form" id="form-sewa">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <input type="hidden" name="aksi" value="kirim_pengajuan">
  <input type="hidden" name="id_kamar" value="<?= (int) $kamarDipilih['id_kamar'] ?>">
  <section class="payment-card">
    <strong class="monthly-price">Rp <?= h(number_format((float) $kamarDipilih['harga_bulanan'], 0, ',', '.')) ?><small>/bulan</small></strong>
    <div class="date-row">
      <label>Tanggal Mulai Sewa<input type="date" id="tanggal_mulai" name="tanggal_mulai" min="<?= date('Y-m-d') ?>" value="<?= h($nilaiMulai) ?>" required></label>
      <label>Tanggal Akhir Sewa<input type="date" id="tanggal_selesai" name="tanggal_selesai" min="<?= date('Y-m-d') ?>" value="<?= h($nilaiSelesai) ?>" required></label>
    </div>
    <small class="hint">Pilih tanggal penyewaan Anda.</small>
  </section>
  <section class="duration-card">
    <label for="bulan">Jumlah Bulan yang Ingin Disewa</label>
    <div class="stepper"><button type="button" id="kurangi-bulan" aria-label="Kurangi bulan">−</button><output id="bulan-label">1</output><button type="button" id="tambah-bulan" aria-label="Tambah bulan">+</button></div>
    <input type="hidden" id="bulan" name="bulan" value="<?= $jumlahBulan ?>">
  </section>
  <label class="field-label" for="catatan">Catatan (opsional)</label>
  <textarea class="note-input" id="catatan" name="catatan" placeholder="Transfer dari rekening an. orang tua, dll."></textarea>
  <div class="bottom-action"><button class="btn" type="submit">Kirim untuk Diverifikasi</button></div>
</form>
<script>
const bulanInput = document.getElementById('bulan');
const bulanLabel = document.getElementById('bulan-label');
const tanggalMulai = document.getElementById('tanggal_mulai');
const tanggalAkhir = document.getElementById('tanggal_selesai');
let bulanAcuan = Number(bulanInput.value) || 1;
let tanggalAkhirAcuan = tanggalAkhir.value;

function tambahBulan(tanggal, jumlah) {
  if (!tanggal) return '';
  const [tahun, bulan, hari] = tanggal.split('-').map(Number);
  const bulanTotal = (tahun * 12) + (bulan - 1) + jumlah;
  const tahunTujuan = Math.floor(bulanTotal / 12);
  const bulanTujuan = ((bulanTotal % 12) + 12) % 12;
  const hariTerakhir = new Date(Date.UTC(tahunTujuan, bulanTujuan + 1, 0)).getUTCDate();
  const hariTujuan = Math.min(hari, hariTerakhir);
  return `${tahunTujuan}-${String(bulanTujuan + 1).padStart(2, '0')}-${String(hariTujuan).padStart(2, '0')}`;
}

function perbaruiTagihan(delta) {
  const sebelumnya = Number(bulanInput.value) || 1;
  const baru = Math.max(1, Math.min(12, sebelumnya + delta));
  bulanInput.value = baru;
  bulanLabel.value = baru;

  if (tanggalAkhirAcuan) {
    tanggalAkhir.value = tambahBulan(tanggalAkhirAcuan, baru - bulanAcuan);
  }
}

document.getElementById('kurangi-bulan').addEventListener('click', () => perbaruiTagihan(-1));
document.getElementById('tambah-bulan').addEventListener('click', () => perbaruiTagihan(1));

tanggalAkhir.addEventListener('change', () => {
  tanggalAkhirAcuan = tanggalAkhir.value;
  bulanAcuan = Number(bulanInput.value) || 1;
});

tanggalMulai.addEventListener('change', () => {
  if (!tanggalMulai.value) return;
  tanggalAkhir.min = tanggalMulai.value;
  tanggalAkhir.value = tambahBulan(tanggalMulai.value, Number(bulanInput.value) || 1);
  tanggalAkhirAcuan = tanggalAkhir.value;
  bulanAcuan = Number(bulanInput.value) || 1;
});
</script>
