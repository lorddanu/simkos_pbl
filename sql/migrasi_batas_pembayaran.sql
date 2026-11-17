-- Batas 30 menit untuk pembayaran setelah pengajuan sewa disetujui.
-- Jalankan setelah migrasi_alur_pengajuan.sql. Aman dijalankan berulang.
BEGIN;
ALTER TABLE pengajuan_sewa ADD COLUMN IF NOT EXISTS batas_pembayaran TIMESTAMPTZ;
ALTER TABLE pengajuan_sewa DROP CONSTRAINT IF EXISTS pengajuan_sewa_status_check;
ALTER TABLE pengajuan_sewa ADD CONSTRAINT pengajuan_sewa_status_check
    CHECK (status IN (
        'menunggu_persetujuan', 'ditolak', 'menunggu_pembayaran',
        'menunggu_verifikasi', 'pembayaran_ditolak', 'sewa_aktif', 'kedaluwarsa'
    ));
CREATE INDEX IF NOT EXISTS idx_pengajuan_batas_pembayaran
    ON pengajuan_sewa(batas_pembayaran) WHERE status = 'menunggu_pembayaran';
UPDATE pengajuan_sewa
   SET batas_pembayaran = COALESCE(ditinjau_pada, NOW()) + INTERVAL '30 minutes'
 WHERE status = 'menunggu_pembayaran' AND batas_pembayaran IS NULL;
COMMIT;