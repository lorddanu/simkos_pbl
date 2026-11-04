<?php
$namaBulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$tanggalMulai = new DateTime($pengajuan['tanggal_mulai']);
$statusPengajuan = $pengajuan['status'];
$dapatMengajukanPembayaran = in_array($statusPengajuan, ['menunggu_pembayaran', 'pembayaran_ditolak'], true);
$labelStatus = [
  'menunggu_persetujuan' => 'Menunggu Persetujuan',
  'ditolak' => 'Pengajuan Ditolak',
  'menunggu_pembayaran' => 'Pengajuan Diterima',
  'menunggu_verifikasi' => 'Menunggu Verifikasi Pembayaran',
  'pembayaran_ditolak' => 'Pembayaran Ditolak',
  'sewa_aktif' => 'Sewa Aktif',
][$statusPengajuan] ?? 'Diproses';
$pesanStatus = [
  'menunggu_persetujuan' => 'Pengajuan Anda sedang menunggu keputusan admin.',
  'ditolak' => 'Pengajuan sewa belum dapat disetujui. Silakan hubungi admin untuk informasi lebih lanjut.',
  'menunggu_pembayaran' => 'Pengajuan Anda disetujui. Silakan lanjutkan dengan mengajukan pembayaran.',
  'menunggu_verifikasi' => 'Pembayaran sudah dikirim dan sedang menunggu verifikasi admin.',
  'pembayaran_ditolak' => 'Pembayaran belum dapat diverifikasi. Anda dapat mengirim pengajuan pembayaran kembali.',
  'sewa_aktif' => 'Pembayaran telah diverifikasi dan sewa kamar Anda sudah aktif.',
][$statusPengajuan] ?? 'Status pengajuan sedang diproses.';
?>
<section class="confirm">
  <svg viewBox="0 0 64 64" width="40" height="40" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#78b83f"/><path d="M18 33l9 9 19-20" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></svg>
  <h2><?= h($labelStatus) ?></h2>
  <p><?= h($pesanStatus) ?></p>
  <div class="facility-box confirm-info">
    <div><span>Kamar</span><span>Kamar <?= h($pengajuan['nomor_kamar']) ?></span></div>
    <div><span>Mulai sewa</span><span><?= h($tanggalMulai->format('j')) ?> <?= h($namaBulan[(int) $tanggalMulai->format('n')]) ?> <?= h($tanggalMulai->format('Y')) ?></span></div>
    <div><span>Status</span><strong class="pending-status"><?= h($labelStatus) ?></strong></div>
  </div>
</section>
<div class="bottom-action confirm-actions">
  <?php if ($dapatMengajukanPembayaran): ?><a class="btn" href="index.php?halaman=pembayaran">Ajukan Pembayaran</a><?php endif; ?>
  <a class="btn <?= $dapatMengajukanPembayaran ? 'alt' : '' ?>" href="index.php">Kembali ke Beranda</a>
</div>
