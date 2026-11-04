-- Akun demo untuk presentasi (PostgreSQL). Jalankan SETELAH schema + tambahan_penghuni.sql.
-- Aman dijalankan berulang (tidak membuat data ganda).
--
--   penghuni@simkos.test / penghuni123   -> penghuni terdaftar (Kamar A, ada sewa aktif)
--   calon@simkos.test    / penghuni123   -> penghuni belum terdaftar (tanpa sewa)

DO $demo$
DECLARE
    v_kamar INT;
    v_penghuni INT;
BEGIN
    -- Kamar demo kosong agar dapat dilihat oleh akun calon penghuni.
    -- Kamar yang sudah ada tidak diubah statusnya.
    INSERT INTO kamar (nomor_kamar, status, harga_bulanan)
    VALUES ('B', 'kosong', 650000), ('C', 'kosong', 650000)
    ON CONFLICT (nomor_kamar) DO NOTHING;

    IF NOT EXISTS (SELECT 1 FROM akun WHERE email = 'penghuni@simkos.test') THEN
        INSERT INTO kamar (nomor_kamar, status, harga_bulanan)
        VALUES ('A', 'terisi', 650000)
        ON CONFLICT (nomor_kamar) DO UPDATE SET status = 'terisi'
        RETURNING id_kamar INTO v_kamar;

        INSERT INTO penghuni (nama, kontak) VALUES ('Penghuni Demo', '081234567890')
        RETURNING id_penghuni INTO v_penghuni;

        INSERT INTO sewa (id_kamar, id_penghuni, tanggal_mulai)
        VALUES (v_kamar, v_penghuni, CURRENT_DATE - 60);

        INSERT INTO akun (id_penghuni, email, password_hash, role)
        VALUES (v_penghuni, 'penghuni@simkos.test', '$2y$10$Cf0SZneLmJWfV/v.bt6vuutPTC3yZPGDCekqDp6JBE0fRvBMk0swq', 'penghuni');
    END IF;

    IF NOT EXISTS (SELECT 1 FROM akun WHERE email = 'calon@simkos.test') THEN
        INSERT INTO penghuni (nama) VALUES ('Calon Penghuni')
        RETURNING id_penghuni INTO v_penghuni;

        INSERT INTO akun (id_penghuni, email, password_hash, role)
        VALUES (v_penghuni, 'calon@simkos.test', '$2y$10$Cf0SZneLmJWfV/v.bt6vuutPTC3yZPGDCekqDp6JBE0fRvBMk0swq', 'calon_penghuni');
    END IF;
END
$demo$;
