
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SIMKOS</title>
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css?v=admin-profile-2">
</head>
<body class="admin-app <?= ($halamanAktif ?? '') === 'dashboard' ? 'admin-dashboard' : '' ?>">
<header class="admin-header">
    <a class="admin-back-button" href="<?= ($halamanAktif ?? '') === 'dashboard' ? '../index.php' : 'dashboard.php' ?>" aria-label="Kembali">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="m13 8-4 4 4 4"></path></svg>
</a>
    <div class="brand">SIMKOS</div>

    <div class="admin-header-actions">
        <a class="admin-notification-button" href="kamar.php?tab=penghuni#pengajuan-masuk" aria-label="Notifikasi pengajuan sewa" title="Notifikasi pengajuan sewa">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m4 7 8 6 8-6"></path></svg>
        </a>
    <details class="profile-menu">
        <summary class="profile-button" aria-label="Menu profil">
            <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="none"><circle cx="12" cy="12" r="11" fill="#fff"></circle><circle cx="12" cy="8.5" r="3.2" fill="#7165d1"></circle><path d="M5.5 19c.7-3 3-4.7 6.5-4.7s5.8 1.7 6.5 4.7" fill="#7165d1"></path></svg>
        </summary>

        <div class="profile-dropdown" id="profileDropdown">
            <div class="profile-name">
                Admin SIMKOS
            </div>

            <form action="../auth/logout.php" method="post" class="admin-logout-form">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="logout-link">Logout</button>
            </form>
        </div>
    </details>
    </div>
</header>
