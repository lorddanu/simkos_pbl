-- =====================================================
-- DATABASE SIMKOS
-- Sistem Informasi Manajemen Kos
-- PostgreSQL
-- =====================================================

-- 1 enum status pembayaran
CREATE TYPE status_pembayaran AS ENUM (
    'menunggu',
    'diterima',
    'ditolak'
);

-- 2 tabel kamar
CREATE TABLE kamar (
    id_kamar SERIAL PRIMARY KEY,
    nomor_kamar VARCHAR(10) NOT NULL UNIQUE,
    status VARCHAR(10) NOT NULL DEFAULT 'kosong' CHECK (status IN ('kosong', 'terisi')),
    harga_bulanan  NUMERIC(10,2) NOT NULL CHECK (harga_bulanan >= 0)
);

-- 3 tabel penghuni
CREATE TABLE penghuni (
    id_penghuni  SERIAL PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    kontak VARCHAR(20),
    status VARCHAR(10) NOT NULL DEFAULT 'aktif' CHECK (status IN ('aktif', 'nonaktif'))
);

-- 4 tabel sewa
CREATE TABLE sewa (
    id_sewa SERIAL PRIMARY KEY,
    id_kamar INT NOT NULL
             REFERENCES kamar(id_kamar)
             ON DELETE RESTRICT,
    id_penghuni INT NOT NULL
                REFERENCES penghuni(id_penghuni)
                ON DELETE RESTRICT,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE,
    CHECK (
        tanggal_selesai IS NULL
        OR tanggal_selesai >= tanggal_mulai
    )
);

-- 5 tabel pembayaran
CREATE TABLE pembayaran (
    id_pembayaran SERIAL PRIMARY KEY,
    id_sewa INT NOT NULL
            REFERENCES sewa(id_sewa)
            ON DELETE RESTRICT,
    jumlah NUMERIC(10,2) NOT null CHECK (jumlah >= 0),
    tanggal_bayar DATE NOT NULL,
    file_bukti VARCHAR(255),
    tanggal_upload TIMESTAMP,
    status_verifikasi status_pembayaran NOT null DEFAULT 'menunggu'
);

-- 6 tabel keluhan
CREATE TABLE keluhan (
    id_keluhan SERIAL PRIMARY KEY,
    id_penghuni INT NOT NULL
                REFERENCES penghuni(id_penghuni)
                ON DELETE CASCADE,
    isi_keluhan TEXT NOT NULL,
    status VARCHAR(15) NOT NULL DEFAULT 'baru'
           CHECK ( status IN ('baru', 'diproses', 'selesai'))
);

-- index
CREATE INDEX idx_kamar_status ON kamar(status);
CREATE INDEX idx_sewa_kamar ON sewa(id_kamar);
CREATE INDEX idx_sewa_penghuni ON sewa(id_penghuni);
CREATE INDEX idx_pembayaran_sewa ON pembayaran(id_sewa);
CREATE INDEX idx_pembayaran_status ON pembayaran(status_verifikasi);