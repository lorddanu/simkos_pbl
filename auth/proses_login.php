<?php
session_start();
require_once __DIR__ . '/../database/database.php';

$pdo = getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $_SESSION['login_error'] = 'Email dan kata sandi wajib diisi.';
    header("Location: login.php");
    exit;
}

try {
    $sql = "SELECT id_akun, nama, email, password_hash, role
            FROM akun
            WHERE email = :email";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);

    $akun = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($akun && password_verify($password, $akun['password_hash'])) {
        session_regenerate_id(true);

        $_SESSION['id_akun'] = $akun['id_akun'];
        $_SESSION['nama'] = $akun['nama'];
        $_SESSION['role'] = $akun['role'];

        header("Location: ../includes/dashboard.php");
        exit;
    }

    $_SESSION['login_error'] = 'Email atau kata sandi salah.';
    header("Location: login.php");
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['login_error'] = 'Terjadi kesalahan. Silakan coba lagi.';
    header("Location: login.php");
    exit;
}
?>