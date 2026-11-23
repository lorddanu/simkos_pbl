-- Tenggat pembayaran dan unggah ulang bukti pembayaran SIMKOS.
-- Jalankan setelah migrasi_alur_pengajuan.sql.
-- Perubahan ini hanya menyiapkan struktur dan tenggat di database.

BEGIN;

ALTER TABLE pengajuan_sewa
    ADD COLUMN IF NOT EXISTS batas_pembayaran TIMESTAMPTZ,
    ADD COLUMN IF NOT EXISTS batas_upload_ulang TIMESTAMPTZ;

ALTER TABLE pengajuan_sewa DROP CONSTRAINT IF EXISTS pengajuan_sewa_status_check;
ALTER TABLE pengajuan_sewa ADD CONSTRAINT pengajuan_sewa_status_check
    CHECK (status IN (
        'menunggu_persetujuan',
        'ditolak',
        'menunggu_pembayaran',
        'menunggu_verifikasi',
        'pembayaran_ditolak',
        'sewa_aktif',
        'kedaluwarsa'
    ));

CREATE INDEX IF NOT EXISTS idx_pengajuan_batas_pembayaran
    ON pengajuan_sewa(batas_pembayaran)
    WHERE status = 'menunggu_pembayaran';

CREATE INDEX IF NOT EXISTS idx_pengajuan_batas_upload_ulang
    ON pengajuan_sewa(batas_upload_ulang)
    WHERE status = 'pembayaran_ditolak';

-- Sinkronkan tenggat pembayaran yang sebelumnya dibuat 20 detik.
-- Waktu dihitung dari saat admin menyetujui pengajuan.
UPDATE pengajuan_sewa
   SET batas_pembayaran = COALESCE(ditinjau_pada, NOW()) + INTERVAL '24 hours'
 WHERE status = 'menunggu_pembayaran';

-- Status pembayaran ditolak akan diberi batas baru saat admin menolak pembayaran.
-- Tidak mengubah tenggat lama secara otomatis agar tidak menghidupkan kembali pengajuan lama.
UPDATE pengajuan_sewa
   SET batas_upload_ulang = NULL
 WHERE status <> 'pembayaran_ditolak';

COMMIT;
