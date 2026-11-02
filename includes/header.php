
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SIMKOS</title>
    <link rel="stylesheet" href="../assets/cs/admin_dashboard.css">
</head>
<body>
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
                <circle cx="12" cy="8" r="4"></circle>
                <path d="M4.5 21a7.5 7.5 0 0 1 15 0"></path>
            </svg>
        </button>

        <div class="profile-dropdown" id="profileDropdown" hidden>
            <div class="profile-name">
                <?= htmlspecialchars($nama) ?>
            </div>

            <a href="../auth/logout.php" class="logout-link">
                Logout
            </a>
        </div>
    </div>
</header>
