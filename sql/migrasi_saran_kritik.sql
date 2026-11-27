-- Migrasi fitur Saran dan Kritik SIMKOS
-- Jalankan sekali pada database SIMKOS melalui pgAdmin.

CREATE TABLE IF NOT EXISTS saran_kritik (
    id_saran SERIAL PRIMARY KEY,
    id_penghuni INTEGER NOT NULL
        REFERENCES penghuni(id_penghuni)
        ON DELETE CASCADE,
    jenis VARCHAR(10) NOT NULL
        CHECK (jenis IN ('saran', 'kritik')),
    judul VARCHAR(150) NOT NULL,
    isi TEXT NOT NULL,
    tanggal_kirim TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) NOT NULL DEFAULT 'belum_dibaca'
        CHECK (status IN ('belum_dibaca', 'diproses', 'selesai'))
);

CREATE INDEX IF NOT EXISTS idx_saran_kritik_penghuni
    ON saran_kritik(id_penghuni);

CREATE INDEX IF NOT EXISTS idx_saran_kritik_status
    ON saran_kritik(status);

CREATE INDEX IF NOT EXISTS idx_saran_kritik_tanggal
    ON saran_kritik(tanggal_kirim DESC);


-- Tambahan kategori untuk tampilan Saran dan Kritik penghuni.
ALTER TABLE saran_kritik
    ADD COLUMN IF NOT EXISTS kategori VARCHAR(40);

CREATE INDEX IF NOT EXISTS idx_saran_kritik_kategori
    ON saran_kritik(kategori);
