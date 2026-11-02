<?php
$namaBulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$tanggalMulai = new DateTime($pengajuan['tanggal_mulai']);
?>
<section class="confirm">
  <svg viewBox="0 0 64 64" width="40" height="40" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#78b83f"/><path d="M18 33l9 9 19-20" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></svg>
  <h2>Pengajuan Dikirim</h2>
  <p>Admin akan memberikan pesan notifikasi ketika sudah disetujui.</p>
  <div class="facility-box confirm-info">
    <div><span>Kamar</span><span>Kamar <?= h($pengajuan['nomor_kamar']) ?></span></div>
    <div><span>Mulai sewa</span><span><?= h($tanggalMulai->format('j')) ?> <?= h($namaBulan[(int) $tanggalMulai->format('n')]) ?> <?= h($tanggalMulai->format('Y')) ?></span></div>
    <div><span>Status</span><strong class="pending-status">Menunggu Persetujuan</strong></div>
  </div>
</section>
<div class="bottom-action"><a class="btn" href="index.php">Kembali ke Beranda</a></div>
