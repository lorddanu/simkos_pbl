<?php
// Pintu masuk aplikasi SIMKOS.
// Belum login -> halaman login. Sudah login -> dashboard penghuni.
session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: penghuni/index.php');
} else {
    header('Location: auth/login.php');
}
exit;