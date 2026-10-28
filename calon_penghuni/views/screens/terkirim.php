<section class="confirm">
  <svg viewBox="0 0 64 64" width="64" height="64"><circle cx="32" cy="32" r="30" fill="#78b83f"/><path d="M18 33l9 9 19-20" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></svg>
  <h2>Pengajuan Terkirim</h2>
  <p>Pengajuan sewa Kamar <?= h($pengajuan['nomor_kamar']) ?> sedang menunggu konfirmasi admin.</p>
  <div class="facility-box confirm-info">
    <p>Mulai sewa: <?= h($pengajuan['tanggal_mulai']) ?></p>
    <p>Akhir sewa: <?= h($pengajuan['tanggal_selesai']) ?></p>
    <p>Status: Menunggu persetujuan</p>
    <hr>
    <strong>Total: Rp <?= h(number_format($pengajuan['total'], 0, ',', '.')) ?></strong>
  </div>
</section>
<div class="bottom-action"><a class="btn" href="index.php">Kembali ke Beranda</a></div>
