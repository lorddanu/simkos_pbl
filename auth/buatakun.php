
<?php
require_once __DIR__ . '/../database/database.php';
$pdo = getConnection();

$nama = 'Admin SIMKOS';
$email = 'admin@simkos.test';
$password = 'admin123';
$role = 'admin';

$hash = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO akun (nama, email, password_hash, role)
        VALUES (:nama, :email, :password_hash, :role)
        ON CONFLICT (email) DO NOTHING";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'nama' => $nama,
    'email' => $email,
    'password_hash' => $hash,
    'role' => $role
]);

echo "Proses pembuatan akun selesai.";
?>