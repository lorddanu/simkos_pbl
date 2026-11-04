<?php
$namaBulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$tanggalUnggah = new DateTime($pembayaran['tanggal_upload'] ?? 'now');
$nominalPembayaran = 'Rp' . number_format((float) $pembayaran['total'], 2, ',', '.');
?>
<section class="confirm payment-receipt">
  <svg viewBox="0 0 64 64" width="40" height="40" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#78b83f"/><path d="M18 33l9 9 19-20" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></svg>
  <h2>Bukti Terkirim</h2>
  <p>Bukti transfer Anda sudah diterima dan sedang menunggu diperiksa oleh admin. Anda akan mendapat notifikasi setelah statusnya diperbarui.</p>
  <div class="facility-box confirm-info payment-receipt-info">
    <div><span>Nominal</span><span><?= h($nominalPembayaran) ?></span></div>
    <div><span>Tanggal Unggah</span><span><?= h($tanggalUnggah->format('j')) ?> <?= h($namaBulan[(int) $tanggalUnggah->format('n')]) ?> <?= h($tanggalUnggah->format('Y')) ?></span></div>
    <div><span>Status</span><span>Menunggu verifikasi</span></div>
    <hr>
    <strong><span>Total Payment</span><span><?= h($nominalPembayaran) ?></span></strong>
  </div>
</section>
<div class="bottom-action"><a class="btn" href="index.php">Kembali ke Beranda</a></div>
