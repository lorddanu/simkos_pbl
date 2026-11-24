-- Jalankan satu kali melalui pgAdmin pada database SIMKOS.
-- Menambahkan kolom deskripsi dan foto tanpa menghapus data kamar lama.
ALTER TABLE kamar
    ADD COLUMN IF NOT EXISTS deskripsi TEXT,
    ADD COLUMN IF NOT EXISTS foto_kamar VARCHAR(255);

-- Folder uploads/kamar dibuat otomatis oleh PHP saat foto pertama diunggah.
