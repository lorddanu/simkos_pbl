-- Migrasi akun bersama untuk admin, penghuni, dan calon penghuni.
-- Jalankan setelah sql/schema_simkoa.sql. Aman dijalankan ulang.

CREATE TABLE IF NOT EXISTS akun (
    id_akun SERIAL PRIMARY KEY,
    id_penghuni INT UNIQUE REFERENCES penghuni(id_penghuni) ON DELETE CASCADE,
    nama VARCHAR(100),
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'calon_penghuni',
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

ALTER TABLE akun ADD COLUMN IF NOT EXISTS id_penghuni INT;
ALTER TABLE akun ADD COLUMN IF NOT EXISTS nama VARCHAR(100);
ALTER TABLE akun ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT NOW();
ALTER TABLE akun ALTER COLUMN role TYPE VARCHAR(20);
ALTER TABLE akun ALTER COLUMN role SET DEFAULT 'calon_penghuni';

ALTER TABLE akun DROP CONSTRAINT IF EXISTS akun_role_check;
ALTER TABLE akun ADD CONSTRAINT akun_role_check
    CHECK (role IN ('admin', 'penghuni', 'calon_penghuni'));

DO $migration$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'akun_id_penghuni_fkey'
    ) THEN
        ALTER TABLE akun ADD CONSTRAINT akun_id_penghuni_fkey
            FOREIGN KEY (id_penghuni) REFERENCES penghuni(id_penghuni) ON DELETE CASCADE;
    END IF;
END
$migration$;

CREATE UNIQUE INDEX IF NOT EXISTS idx_akun_id_penghuni_unique
    ON akun(id_penghuni) WHERE id_penghuni IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_akun_email ON akun(email);

-- Hubungkan akun non-admin lama ke tabel penghuni jika belum memiliki relasi.
DO $migration$
DECLARE
    r RECORD;
    v_id_penghuni INT;
BEGIN
    FOR r IN
        SELECT id_akun, nama
          FROM akun
         WHERE role <> 'admin' AND id_penghuni IS NULL
    LOOP
        INSERT INTO penghuni (nama)
        VALUES (COALESCE(NULLIF(r.nama, ''), 'Penghuni'))
        RETURNING id_penghuni INTO v_id_penghuni;

        UPDATE akun SET id_penghuni = v_id_penghuni WHERE id_akun = r.id_akun;
    END LOOP;
END
$migration$;

UPDATE akun a
   SET role = 'calon_penghuni'
 WHERE a.role = 'penghuni'
   AND NOT EXISTS (
       SELECT 1 FROM sewa s
        WHERE s.id_penghuni = a.id_penghuni
          AND (s.tanggal_selesai IS NULL OR s.tanggal_selesai >= CURRENT_DATE)
   );

ALTER TABLE keluhan ADD COLUMN IF NOT EXISTS kategori VARCHAR(30);
ALTER TABLE keluhan ADD COLUMN IF NOT EXISTS tanggal_kirim TIMESTAMP NOT NULL DEFAULT NOW();
ALTER TABLE pembayaran ADD COLUMN IF NOT EXISTS periode VARCHAR(7);
