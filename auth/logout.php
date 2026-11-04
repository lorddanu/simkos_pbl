<?php
require __DIR__ . '/../database/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok()) {
    // Keep the demo rollback log while allowing role switching in the same browser.
    unset($_SESSION['user_id'], $_SESSION['info'], $_SESSION['error'], $_SESSION['flash_admin'], $_SESSION['flash_error']);
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
    session_regenerate_id(true);
}
header('Location: login.php');
exit;
