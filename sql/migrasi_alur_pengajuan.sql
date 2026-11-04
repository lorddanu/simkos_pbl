-- Alur pengajuan sewa dan pembayaran yang dapat diproses admin.
-- Jalankan setelah schema_simkoa.sql dan tambahan_penghuni.sql.

CREATE TABLE IF NOT EXISTS pengajuan_sewa (
    id_pengajuan SERIAL PRIMARY KEY,
    id_penghuni INT NOT NULL REFERENCES penghuni(id_penghuni) ON DELETE RESTRICT,
    id_kamar INT NOT NULL REFERENCES kamar(id_kamar) ON DELETE RESTRICT,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    jumlah_bulan SMALLINT NOT NULL CHECK (jumlah_bulan BETWEEN 1 AND 12),
    harga_bulanan NUMERIC(10,2) NOT NULL CHECK (harga_bulanan >= 0),
    total NUMERIC(12,2) NOT NULL CHECK (total >= 0),
    catatan TEXT,
    status VARCHAR(30) NOT NULL DEFAULT 'menunggu_persetujuan'
        CHECK (status IN (
            'menunggu_persetujuan', 'ditolak', 'menunggu_pembayaran',
            'menunggu_verifikasi', 'pembayaran_ditolak', 'sewa_aktif'
        )),
    catatan_admin TEXT,
    ditinjau_oleh INT REFERENCES akun(id_akun) ON DELETE SET NULL,
    ditinjau_pada TIMESTAMP,
    notifikasi_dibaca BOOLEAN NOT NULL DEFAULT FALSE,
    dibuat_pada TIMESTAMP NOT NULL DEFAULT NOW(),
    diperbarui_pada TIMESTAMP NOT NULL DEFAULT NOW(),
    CHECK (tanggal_selesai > tanggal_mulai)
);

CREATE INDEX IF NOT EXISTS idx_pengajuan_penghuni
    ON pengajuan_sewa(id_penghuni, dibuat_pada DESC);
CREATE INDEX IF NOT EXISTS idx_pengajuan_status
    ON pengajuan_sewa(status, dibuat_pada);

CREATE UNIQUE INDEX IF NOT EXISTS idx_pengajuan_kamar_aktif
    ON pengajuan_sewa(id_kamar)
    WHERE status IN ('menunggu_persetujuan', 'menunggu_pembayaran', 'menunggu_verifikasi', 'pembayaran_ditolak');
CREATE UNIQUE INDEX IF NOT EXISTS idx_pengajuan_penghuni_aktif
    ON pengajuan_sewa(id_penghuni)
    WHERE status IN ('menunggu_persetujuan', 'menunggu_pembayaran', 'menunggu_verifikasi', 'pembayaran_ditolak');

-- Pembayaran calon penghuni mengacu ke pengajuan karena sewa baru dibuat setelah pembayaran diterima.
ALTER TABLE pembayaran ALTER COLUMN id_sewa DROP NOT NULL;
ALTER TABLE pembayaran ADD COLUMN IF NOT EXISTS id_pengajuan_sewa INT
    REFERENCES pengajuan_sewa(id_pengajuan) ON DELETE RESTRICT;
ALTER TABLE pembayaran ADD COLUMN IF NOT EXISTS catatan TEXT;
CREATE INDEX IF NOT EXISTS idx_pembayaran_pengajuan
    ON pembayaran(id_pengajuan_sewa, id_pembayaran DESC);

DO $migration$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'pembayaran_sumber_check'
    ) THEN
        ALTER TABLE pembayaran ADD CONSTRAINT pembayaran_sumber_check
            CHECK ((id_sewa IS NOT NULL AND id_pengajuan_sewa IS NULL)
                OR (id_sewa IS NULL AND id_pengajuan_sewa IS NOT NULL));
    END IF;
END
$migration$;

-- Status kamar yang boleh dikelola admin.
ALTER TABLE kamar ALTER COLUMN status TYPE VARCHAR(20);
ALTER TABLE kamar DROP CONSTRAINT IF EXISTS kamar_status_check;
ALTER TABLE kamar ADD CONSTRAINT kamar_status_check
    CHECK (status IN ('kosong', 'terisi', 'tidak tersedia'));
