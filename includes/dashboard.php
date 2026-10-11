
<?php
session_start();

if (!isset($_SESSION['id_akun'])) {
    header("Location: ../auth/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard SIMKOS</title>
    <link rel="stylesheet" href="../assets/login.css">
</head>
<body>
    <h1>Selamat datang di SIMKOS!</h1>

    <p>
        Halo,
        <?= htmlspecialchars($_SESSION['nama']) ?>.
    </p>

    <p>
        Role akun:
        <?= htmlspecialchars($_SESSION['role']) ?>
    </p>

    <a href="../auth/logout.php">Keluar</a>
</body>
</html>