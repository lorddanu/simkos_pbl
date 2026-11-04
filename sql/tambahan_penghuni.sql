-- Tempel di bagian AKHIR schema_simkoa.sql (PostgreSQL).

-- 7 tabel akun (untuk login)
CREATE TABLE akun (
    id_akun       SERIAL PRIMARY KEY,
    id_penghuni   INT UNIQUE
                  REFERENCES penghuni(id_penghuni)
                  ON DELETE CASCADE,          -- NULL untuk akun admin
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          VARCHAR(20) NOT NULL DEFAULT 'calon_penghuni'
                  CHECK (role IN ('calon_penghuni', 'penghuni', 'admin')),
    created_at    TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Migrasi untuk database yang sudah memiliki tabel akun versi sebelumnya.
ALTER TABLE akun ALTER COLUMN role TYPE VARCHAR(20);
ALTER TABLE akun DROP CONSTRAINT IF EXISTS akun_role_check;
UPDATE akun SET role = 'calon_penghuni' WHERE role NOT IN ('calon_penghuni', 'penghuni', 'admin');
ALTER TABLE akun ALTER COLUMN role SET DEFAULT 'calon_penghuni';
ALTER TABLE akun ADD CONSTRAINT akun_role_check
    CHECK (role IN ('calon_penghuni', 'penghuni', 'admin'));
UPDATE akun a
   SET role = 'calon_penghuni'
 WHERE a.role = 'penghuni'
   AND NOT EXISTS (
       SELECT 1 FROM sewa s
        WHERE s.id_penghuni = a.id_penghuni
          AND (s.tanggal_selesai IS NULL OR s.tanggal_selesai >= CURRENT_DATE)
   );

-- Tambahan kolom (perlu disepakati kelompok)
-- Form Saran & Kritik punya pilihan kategori
ALTER TABLE keluhan ADD COLUMN IF NOT EXISTS kategori VARCHAR(30);
ALTER TABLE keluhan ADD COLUMN IF NOT EXISTS tanggal_kirim TIMESTAMP NOT NULL DEFAULT NOW();
-- Penanda bulan tagihan, format 'YYYY-MM' (contoh '2026-11')
ALTER TABLE pembayaran ADD COLUMN IF NOT EXISTS periode VARCHAR(7);
CREATE INDEX IF NOT EXISTS idx_akun_email ON akun(email);
