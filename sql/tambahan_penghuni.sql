-- Tempel di bagian AKHIR schema_simkoa.sql (PostgreSQL).

-- 7 tabel akun (untuk login)
CREATE TABLE akun (
    id_akun       SERIAL PRIMARY KEY,
    id_penghuni   INT UNIQUE
                  REFERENCES penghuni(id_penghuni)
                  ON DELETE CASCADE,          -- NULL untuk akun admin
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          VARCHAR(10) NOT NULL DEFAULT 'penghuni'
                  CHECK (role IN ('penghuni', 'admin')),
    created_at    TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Tambahan kolom (perlu disepakati kelompok)
-- Form Saran & Kritik punya pilihan kategori
ALTER TABLE keluhan ADD COLUMN IF NOT EXISTS kategori VARCHAR(30);
ALTER TABLE keluhan ADD COLUMN IF NOT EXISTS tanggal_kirim TIMESTAMP NOT NULL DEFAULT NOW();
-- Penanda bulan tagihan, format 'YYYY-MM' (contoh '2026-11')
ALTER TABLE pembayaran ADD COLUMN IF NOT EXISTS periode VARCHAR(7);
CREATE INDEX IF NOT EXISTS idx_akun_email ON akun(email);