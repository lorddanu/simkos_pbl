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

/** Track temporary demo writes so a browser refresh can restore the prior DB state. */
function demo_track_insert(string $table, int $id, ?string $uploadedFile = null): void
{
    $tables = ['pengajuan_sewa', 'pembayaran', 'sewa', 'keluhan'];
    if (!in_array($table, $tables, true) || $id < 1) return;

    $_SESSION['_demo_rollback']['inserted'][$table][$id] = true;
    if ($uploadedFile !== null) {
        $_SESSION['_demo_rollback']['files'][$uploadedFile] = true;
    }
}

function demo_track_before_change(string $table, int $id): void
{
    $keys = [
        'pengajuan_sewa' => 'id_pengajuan',
        'pembayaran' => 'id_pembayaran',
        'sewa' => 'id_sewa',
        'kamar' => 'id_kamar',
        'akun' => 'id_akun',
        'keluhan' => 'id_keluhan',
    ];
    if (!isset($keys[$table]) || $id < 1) return;
    if (!empty($_SESSION['_demo_rollback']['inserted'][$table][$id])) return;
    if (isset($_SESSION['_demo_rollback']['before'][$table][$id])) return;

    $sql = 'SELECT * FROM ' . $table . ' WHERE ' . $keys[$table] . ' = ?';
    $st = db()->prepare($sql);
    $st->execute([$id]);
    $row = $st->fetch();
    if ($row) $_SESSION['_demo_rollback']['before'][$table][$id] = $row;
}

function demo_reset_changes(): void
{
    $changes = $_SESSION['_demo_rollback'] ?? [];
    if (!$changes) return;

    $pdo = db();
    $keys = [
        'pengajuan_sewa' => 'id_pengajuan',
        'pembayaran' => 'id_pembayaran',
        'sewa' => 'id_sewa',
        'keluhan' => 'id_keluhan',
        'kamar' => 'id_kamar',
        'akun' => 'id_akun',
    ];
    $pdo->beginTransaction();
    try {
        // Remove demo-created child rows before their parent rows.
        foreach (['pembayaran', 'sewa', 'pengajuan_sewa', 'keluhan'] as $table) {
            foreach (array_keys($changes['inserted'][$table] ?? []) as $id) {
                $st = $pdo->prepare('DELETE FROM ' . $table . ' WHERE ' . $keys[$table] . ' = ?');
                $st->execute([(int) $id]);
            }
        }

        // Restore rows that existed before this browser session changed them.
        foreach (['kamar', 'akun', 'pengajuan_sewa', 'sewa', 'pembayaran', 'keluhan'] as $table) {
            foreach (($changes['before'][$table] ?? []) as $id => $row) {
                $columns = array_keys($row);
                $quotedColumns = array_map(static fn($column) => '"' . str_replace('"', '""', $column) . '"', $columns);
                $values = array_map(static fn($column) => ':' . $column, $columns);
                $updates = [];
                foreach ($columns as $column) {
                    if ($column !== $keys[$table]) $updates[] = '"' . str_replace('"', '""', $column) . '" = EXCLUDED."' . str_replace('"', '""', $column) . '"';
                }
                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $quotedColumns) . ') VALUES (' . implode(', ', $values) . ')'
                    . ' ON CONFLICT (' . $keys[$table] . ') DO UPDATE SET ' . implode(', ', $updates);
                $pdo->prepare($sql)->execute($row);
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }

    $root = dirname(__DIR__);
    foreach (array_keys($changes['files'] ?? []) as $file) {
        $realPath = realpath($file);
        $uploadRoots = [realpath($root . '/calon_penghuni/uploads'), realpath($root . '/penghuni/uploads')];
        foreach ($uploadRoots as $uploadRoot) {
            if ($realPath && $uploadRoot && str_starts_with($realPath, $uploadRoot . DIRECTORY_SEPARATOR) && is_file($realPath)) {
                unlink($realPath);
                break;
            }
        }
    }
    unset($_SESSION['_demo_rollback']);
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
                a.id_penghuni, COALESCE(p.nama, a.nama) AS nama
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
