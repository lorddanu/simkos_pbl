
<?php
session_start();

require_once '../database/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $_SESSION['error_login'] = 'Email dan password wajib diisi.';
    header('Location: login.php');
    exit;
}

try {
    $pdo = getConnection();

    $sql = 'SELECT id_akun, nama, email, password_hash, role
            FROM akun
            WHERE email = :email
            LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);

    $akun = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$akun || !password_verify($password, $akun['password_hash'])) {
        $_SESSION['error_login'] = 'Email atau password salah.';
        header('Location: login.php');
        exit;
    }

    session_regenerate_id(true);

    $_SESSION['id_akun'] = $akun['id_akun'];
    $_SESSION['nama'] = $akun['nama'];
    $_SESSION['role'] = $akun['role'];

    if ($akun['role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
    } elseif ($akun['role'] === 'penghuni') {
        header('Location: ../penghuni/dashboard.php');
    } else {
        session_unset();
        session_destroy();
        header('Location: login.php');
    }

    exit;
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Terjadi kesalahan saat mengakses database.');
}