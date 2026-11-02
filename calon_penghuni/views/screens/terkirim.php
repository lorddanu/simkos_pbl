<?php
$namaBulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$tanggalMulai = new DateTime($pengajuan['tanggal_mulai']);
$menungguPersetujuan = ($pengajuan['status'] ?? '') === 'menunggu_persetujuan';
$sisaDetik = max(0, (int) ($pengajuan['persetujuan_pada'] ?? time()) - time());
?>
<section class="confirm">
  <svg viewBox="0 0 64 64" width="40" height="40" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#78b83f"/><path d="M18 33l9 9 19-20" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></svg>
  <h2><?= $menungguPersetujuan ? 'Pengajuan Dikirim' : 'Pengajuan Diterima' ?></h2>
  <p><?= $menungguPersetujuan ? 'Pengajuan Anda sedang menunggu persetujuan.' : 'Status persetujuan diterima. Silakan ajukan pembayaran.' ?></p>
  <div class="facility-box confirm-info">
    <div><span>Kamar</span><span>Kamar <?= h($pengajuan['nomor_kamar']) ?></span></div>
    <div><span>Mulai sewa</span><span><?= h($tanggalMulai->format('j')) ?> <?= h($namaBulan[(int) $tanggalMulai->format('n')]) ?> <?= h($tanggalMulai->format('Y')) ?></span></div>
    <div><span>Status</span><strong class="pending-status"><?= $menungguPersetujuan ? 'Menunggu Persetujuan' : 'Pengajuan Diterima' ?></strong></div>
  </div>
</section>
<?php if ($menungguPersetujuan): ?>
<script>window.setTimeout(() => window.location.reload(), <?= $sisaDetik * 1000 ?>);</script>
<?php endif; ?>
<div class="bottom-action confirm-actions">
  <?php if (!$menungguPersetujuan): ?><a class="btn" href="index.php?halaman=pembayaran">Ajukan Pembayaran</a><?php endif; ?>
  <a class="btn <?= !$menungguPersetujuan ? 'alt' : '' ?>" href="index.php">Kembali ke Beranda</a>
</div>
