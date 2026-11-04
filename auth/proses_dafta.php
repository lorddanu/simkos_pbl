
<?php
session_start();

require_once '../database/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: daftar.php');
    exit;
}

$nama = trim($_POST['nama'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$konfirmasi = $_POST['konfirmasi'] ?? '';
$setuju = $_POST['setuju'] ?? '';

if ($nama === '' || $email === '' || $password === '') {
    $_SESSION['error_daftar'] = 'Semua kolom wajib diisi.';
    header('Location: daftar.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_daftar'] = 'Format email tidak valid.';
    header('Location: daftar.php');
    exit;
}

if (strlen($password) < 8) {
    $_SESSION['error_daftar'] = 'Password minimal 8 karakter.';
    header('Location: daftar.php');
    exit;
}

if ($password !== $konfirmasi) {
    $_SESSION['error_daftar'] = 'Konfirmasi password tidak cocok.';
    header('Location: daftar.php');
    exit;
}

if ($setuju !== '1') {
    $_SESSION['error_daftar'] = 'Kamu harus menyetujui ketentuan.';
    header('Location: daftar.php');
    exit;
}

try {
    $pdo = getConnection();

    $cek = $pdo->prepare(
        'SELECT id_akun FROM akun WHERE email = :email LIMIT 1'
    );
    $cek->execute(['email' => $email]);

    if ($cek->fetch()) {
        $_SESSION['error_daftar'] = 'Email sudah terdaftar.';
        header('Location: daftar.php');
        exit;
    }

    $hashPassword = password_hash($password, PASSWORD_DEFAULT);

    $sql = 'INSERT INTO akun (nama, email, password, role)
            VALUES (:nama, :email, :password, :role)';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'nama' => $nama,
        'email' => $email,
        'password' => $hashPassword,
        'role' => 'penghuni'
    ]);

    header('Location: daftar.php?sukses=1');
    exit;
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Pendaftaran gagal karena terjadi kesalahan database.');
}
