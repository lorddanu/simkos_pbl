-- Tambahkan kolom foto tanpa menghapus data kamar yang sudah ada.
ALTER TABLE kamar
ADD COLUMN IF NOT EXISTS foto_kamar VARCHAR(255);

-- Folder uploads/kamar harus dapat ditulis oleh web server.
