<?php
$namaBulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$tanggalKirim = new DateTime($pengajuan['tanggal_kirim'] ?? 'today');
$nominal = 'Rp' . number_format((float) $pengajuan['total'], 2, ',', '.');
?>
<section class="confirm">
  <svg viewBox="0 0 64 64" width="48" height="48" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#78b83f"/><path d="M18 33l9 9 19-20" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></svg>
  <h2><?= !empty($pengajuan['bukti']) ? 'Bukti Terkirim' : 'Pengajuan Terkirim' ?></h2>
  <p><?= !empty($pengajuan['bukti'])
      ? 'Bukti transfer Anda sudah diterima dan sedang menunggu diperiksa oleh admin. Anda akan mendapat notifikasi setelah statusnya diperbarui.'
      : 'Pengajuan sewa Kamar ' . h($pengajuan['nomor_kamar']) . ' sudah diterima dan sedang menunggu diperiksa oleh admin.' ?></p>
  <div class="facility-box confirm-info">
    <div><span>Nominal</span><span><?= h($nominal) ?></span></div>
    <div><span>Tanggal Unggah</span><span><?= h($tanggalKirim->format('j')) ?> <?= h($namaBulan[(int) $tanggalKirim->format('n')]) ?> <?= h($tanggalKirim->format('Y')) ?></span></div>
    <div><span>Status</span><span>Menunggu verifikasi</span></div>
    <hr>
    <strong><span>Total Payment</span><span><?= h($nominal) ?></span></strong>
  </div>
</section>
<div class="bottom-action"><a class="btn" href="index.php">Kembali ke Beranda</a></div>
