
<header class="admin-navbar">
    <div class="navbar-title">
        <h1>Dashboard Admin</h1>
    </div>

    <div class="profile-menu">
        <button type="button"
                id="profileButton"
                aria-expanded="false">
            <?= htmlspecialchars($nama ?? 'Admin SIMKOS') ?>
        </button>

        <div id="profileDropdown" hidden>
            <a href="../auth/logout.php">Logout</a>
        </div>
    </div>
</header>
