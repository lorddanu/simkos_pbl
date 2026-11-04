<!-- NAVIGASI BAWAH -->
<nav class="bottom-nav" aria-label="Navigasi admin">

    <a href="dashboard.php"
       class="nav-link <?= ($halamanAktif ?? '') === 'dashboard' ? 'active' : '' ?>">
        <span class="nav-icon">⌂</span>
        Dashboard
    </a>

    <a href="pembayaran.php"
       class="nav-link <?= ($halamanAktif ?? '') === 'pembayaran' ? 'active' : '' ?>">
        <span class="nav-icon">▣</span>
        Pembayaran
    </a>

    <a href="kamar.php"
       class="nav-link <?= ($halamanAktif ?? '') === 'data' ? 'active' : '' ?>">
        <span class="nav-icon">▤</span>
        Data
    </a>

    <a href="laporan.php"
       class="nav-link <?= ($halamanAktif ?? '') === 'laporan' ? 'active' : '' ?>">
        <span class="nav-icon">▧</span>
        Laporan
    </a>

</nav>

<div id="demo-reset-config" hidden data-endpoint="../reset_demo.php" data-home="dashboard.php" data-csrf="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>"></div>
<script src="../assets/js/reset_demo_on_reload.js"></script>
<script src="../assets/js/admin_dashboard.js"></script>

</body>
</html>
