<?php
// Fungsi bantu untuk login, session, dan keamanan form.
// Koneksi database memakai getConnection() dari database/database.php (milik kelompok).
require_once __DIR__ . '/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = getConnection();
    }
    return $pdo;
}

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    $_SESSION['csrf'] = $_SESSION['csrf'] ?? bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function csrf_ok(): bool
{
    return hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '');
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) return null;
    $st = db()->prepare(
        "SELECT a.id_akun, a.email,
                CASE
                    WHEN a.role = 'admin' THEN 'admin'
                    WHEN EXISTS (
                        SELECT 1 FROM sewa s
                         WHERE s.id_penghuni = a.id_penghuni
                           AND (s.tanggal_selesai IS NULL OR s.tanggal_selesai >= CURRENT_DATE)
                    ) THEN 'penghuni'
                    ELSE 'calon_penghuni'
                END AS role,
                a.id_penghuni, p.nama
           FROM akun a LEFT JOIN penghuni p ON p.id_penghuni = a.id_penghuni
          WHERE a.id_akun = ?"
    );
    $st->execute([$_SESSION['user_id']]);
    return $st->fetch() ?: null;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        // dipanggil dari folder satu tingkat di bawah root (auth/, penghuni/)
        header('Location: ' . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/') . '/auth/login.php');
        exit;
    }
    return $u;
}
