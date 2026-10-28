<h2 class="t">Ajukan Sewa Kamar</h2>
<p class="s">Kamar <?= h($kamarDipilih['nomor_kamar']) ?></p>
<?php if ($error): ?><p class="err" role="alert"><?= h($error) ?></p><?php endif; ?>
<form method="post" action="index.php?halaman=ajukan&amp;id=<?= (int) $kamarDipilih['id_kamar'] ?>" class="apply-card" id="form-sewa">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <input type="hidden" name="aksi" value="ajukan">
  <input type="hidden" name="id_kamar" value="<?= (int) $kamarDipilih['id_kamar'] ?>">
  <strong>Rp <?= h(number_format((float) $kamarDipilih['harga_bulanan'], 0, ',', '.')) ?><small>/bulan</small></strong>
  <label for="tanggal_mulai">Tanggal Mulai Sewa</label>
  <input type="date" id="tanggal_mulai" name="tanggal_mulai" min="<?= date('Y-m-d') ?>" value="<?= h($nilaiMulai) ?>" required>
  <label for="tanggal_selesai">Tanggal Akhir Sewa</label>
  <input type="date" id="tanggal_selesai" name="tanggal_selesai" min="<?= date('Y-m-d') ?>" value="<?= h($nilaiSelesai) ?>" required>
  <label for="bulan">Jumlah Bulan yang Ingin Disewa</label>
  <select id="bulan" name="bulan"><?php for ($i = 1; $i <= 12; $i++): ?><option value="<?= $i ?>" <?= $jumlahBulan === $i ? 'selected' : '' ?>><?= $i ?> bulan</option><?php endfor; ?></select>
  <div class="apply-total"><span>Total tagihan</span><strong id="total">Rp <?= h(number_format((float) $kamarDipilih['harga_bulanan'] * $jumlahBulan, 0, ',', '.')) ?></strong></div>
</form>
<div class="bottom-action"><button class="btn" type="submit" form="form-sewa">Kirim Pengajuan</button></div>
