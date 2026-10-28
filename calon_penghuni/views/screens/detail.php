<section class="card room-detail">
  <div class="room-photo-placeholder detail-photo-placeholder" aria-label="Tempat foto Kamar <?= h($kamarDipilih['nomor_kamar']) ?>"></div>
  <div class="detail-content">
    <h2>Kamar <?= h($kamarDipilih['nomor_kamar']) ?></h2>
    <p class="price">Rp <?= h(number_format((float) $kamarDipilih['harga_bulanan'], 0, ',', '.')) ?>/bulan</p>
    <div class="facility-box">
      <h3>Fasilitas Kamar</h3>
      <div class="facility-list">
        <span>Kasur</span><span>Gantungan baju</span>
        <span>Lemari</span><span>Kulkas (bersama)</span>
        <span>Meja</span><span>Wifi</span>
        <span>Dapur (bersama)</span><span>Air</span>
      </div>
    </div>
  </div>
</section>
<div class="bottom-action">
  <a class="btn" href="index.php?halaman=ajukan&amp;id=<?= (int) $kamarDipilih['id_kamar'] ?>">Ajukan Sewa Kamar</a>
</div>
