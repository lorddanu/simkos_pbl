
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SIMKOS</title>
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css?v=admin-mobile-2">
</head>
<body class="admin-app <?= ($halamanAktif ?? '') === 'dashboard' ? 'admin-dashboard' : '' ?>">
<header class="admin-header">
    <div class="brand">SIMKOS</div>

    <div class="profile-menu">
        <button
            type="button"
            class="profile-button"
            id="profileButton"
            aria-label="Menu profil"
            aria-expanded="false"
            aria-controls="profileDropdown"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="10"></circle>
                <circle cx="12" cy="8" r="4"></circle>
                <path d="M5 20a7 7 0 0 1 14 0"></path>
            </svg>
        </button>

        <div class="profile-dropdown" id="profileDropdown" hidden>
            <div class="profile-name">
                <?= htmlspecialchars($nama) ?>
            </div>

            <form action="../auth/logout.php" method="post" class="admin-logout-form">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="logout-link">Logout</button>
            </form>
        </div>
    </div>
</header>
