<?php
require_once __DIR__ . '/database/database.php';

try {
    $conn = getConnection();
    echo "✅ Berhasil terhubung ke database PostgreSQL!<br>";

    $stmt = $conn->query("SELECT COUNT(*) AS total FROM kamar");
    $row = $stmt->fetch();
    echo "Jumlah data di tabel kamar: " . $row['total'];
} catch (Exception $e) {
    echo "❌ Gagal konek: " . $e->getMessage();
}