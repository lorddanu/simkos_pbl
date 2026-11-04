<section class="card room-detail">
  <div class="room-photo-placeholder detail-photo-placeholder" aria-label="Tempat foto Kamar <?= h($kamarDipilih['nomor_kamar']) ?>"></div>
  <div class="detail-content">
    <h2>Kamar <?= h($kamarDipilih['nomor_kamar']) ?></h2>
    <p class="price">Rp <?= h(number_format((float) $kamarDipilih['harga_bulanan'], 0, ',', '.')) ?>/bulan</p>
    <div class="facility-box">
      <h3>Fasilitas Kamar</h3>
      <div class="facility-list">
        <span class="facility-item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 18v-7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v7M3 14h18M5 18v2m14-2v2M5 9V6a2 2 0 0 1 2-2h3v5"/></svg>Kasur</span>
        <span class="facility-item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4a2 2 0 1 0-2-2M12 4v3l7 6a2 2 0 0 1-1.3 3.5H6.3A2 2 0 0 1 5 13l7-6"/></svg>Gantungan baju</span>
        <span class="facility-item"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="3" width="16" height="18" rx="1"/><path d="M4 12h16M8 7v2m0 6v2"/></svg>Lemari</span>
        <span class="facility-item"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M5 11h14M9 6v2m0 7v2"/></svg>Kulkas (bersama)</span>
        <span class="facility-item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10h18M5 10l2 10m12-10-2 10M8 14h8M6 7h12M8 4v3m8-3v3"/></svg>Meja</span>
        <span class="facility-item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 9a10 10 0 0 1 14 0M8 12a6 6 0 0 1 8 0m-5 3a2 2 0 0 1 2 0m-1 4h.01"/></svg>Wifi</span>
        <span class="facility-item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h16v7H4zM6 9h12M7 6h10M8 3h8M7 19v2m10-2v2"/><circle cx="8" cy="15" r="1"/><circle cx="12" cy="15" r="1"/><circle cx="16" cy="15" r="1"/></svg>Dapur (bersama)</span>
        <span class="facility-item"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2s7 8 7 13a7 7 0 0 1-14 0c0-5 7-13 7-13z"/><path d="M9 16a3 3 0 0 0 3 3"/></svg>Air</span>
      </div>
    </div>
  </div>
</section>
<div class="bottom-action">
  <a class="btn" href="index.php?halaman=ajukan&amp;id=<?= (int) $kamarDipilih['id_kamar'] ?>">Ajukan Sewa Kamar</a>
</div>
