<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../database/helpers.php';

$pdo = db();
$pdo->beginTransaction();
try {
    $st = $pdo->query(
        "UPDATE pengajuan_sewa
            SET status = 'kedaluwarsa', diperbarui_pada = NOW()
          WHERE status = 'menunggu_pembayaran'
            AND batas_pembayaran IS NOT NULL
            AND batas_pembayaran <= NOW()
        RETURNING id_pengajuan"
    );
    $ids = $st->fetchAll(PDO::FETCH_COLUMN);
    $pdo->commit();
    fwrite(STDOUT, count($ids) . " pengajuan kedaluwarsa diproses.\n");
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "Gagal memproses tenggat pembayaran: " . $e->getMessage() . "\n");
    exit(1);
}
