<?php
// Arahkan akun ke halaman sesuai role efektif.
require __DIR__ . '/database/helpers.php';
$user = current_user();

if (!$user) {
    header('Location: auth/login.php');
} elseif ($user['role'] === 'admin') {
    header('Location: admin/dashboard.php');
} elseif ($user['role'] === 'penghuni') {
    header('Location: penghuni/index.php');
} else {
    header('Location: calon_penghuni/index.php');
}
exit;
