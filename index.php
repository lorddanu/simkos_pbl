<?php
// Arahkan akun ke halaman sesuai role.
require __DIR__ . '/database/helpers.php';
$user = current_user();

if (!$user) {
    header('Location: auth/login.php');
} elseif ($user['role'] === 'penghuni') {
    header('Location: penghuni/index.php');
} elseif ($user['role'] === 'calon_penghuni') {
    header('Location: calon_penghuni/index.php');
} else {
    http_response_code(403);
    exit('Dashboard untuk role ini belum tersedia.');
}
exit;
