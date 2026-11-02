# Panduan Agen dan Kontributor SIMKOS

Panduan ini berlaku untuk seluruh repository. Baca juga [README.md](README.md) dan [DOKUMENTASI_PENGEMBANGAN.md](DOKUMENTASI_PENGEMBANGAN.md) sebelum mengubah alur aplikasi.

## Konteks proyek

- SIMKOS adalah aplikasi PHP dengan PostgreSQL, dijalankan lokal melalui Apache/XAMPP.
- `database/database.php` berisi konfigurasi PDO. Jangan menyalin kredensial lokal ke dokumentasi publik atau menambahkan kredensial produksi ke repository.
- `database/helpers.php` mengelola session, autentikasi, escaping HTML (`h()`), CSRF, dan fungsi koneksi (`db()`). Gunakan helper yang ada.
- `index.php` mengarahkan pengguna login sesuai role efektif. `penghuni/index.php` adalah dashboard penghuni; `calon_penghuni/index.php` mengendalikan alur calon penghuni.
- Role efektif ditentukan dari sewa aktif di database. Admin dikenali oleh helper, tetapi halaman admin belum tersedia.

## Batasan alur yang harus dipertahankan

- Jangan mengubah perilaku role atau rute login tanpa meninjau `database/helpers.php`, `index.php`, dan kedua dashboard role.
- Alur calon penghuni menggunakan controller tunggal di `calon_penghuni/index.php`, layout bersama di `calon_penghuni/views/layout.php`, dan screen di `calon_penghuni/views/screens/`. Screen baru harus dimasukkan ke allowlist `$halamanValid`.
- Gambar kamar memang sengaja berupa kotak kosong/placeholder. Jangan menambahkan foto atau menggantinya kecuali diminta.
- Form pengajuan awal berjudul **Ajukan Sewa Kamar**. Form tersebut menerima tanggal, lama sewa, dan catatan opsional. Bukti pembayaran dan pilihan metode pembayaran tidak berada di form pengajuan awal.
- Label navigasi **Pengajuan** hanya digunakan saat halaman `ajukan`; pertahankan label lain di halaman lain.
- Status pengajuan calon saat ini hanya simulasi berbasis session: dimulai `menunggu_persetujuan`, lalu berubah menjadi `diterima` setelah 20 detik. Perubahan diproses saat halaman `terkirim`/`notifikasi` dimuat; layar menunggu menggunakan reload otomatis. Belum ada tabel pengajuan, persetujuan admin, atau notifikasi permanen.
- Setelah status simulasi diterima, notifikasi menampilkan tombol **Ajukan Pembayaran** yang membuka layar metode demo. Layar metode masih memiliki teks kembali lama dan belum menyelesaikan pembayaran; jangan menganggap pembayaran sudah tercatat.
- Jangan mengganti simulasi menjadi alur admin nyata, memperpendek/memperpanjang jeda, atau mengubah placeholder visual tanpa instruksi pengguna. Jika perubahan itu dibutuhkan untuk pekerjaan baru, jelaskan dampaknya dan sepakati alurnya terlebih dahulu.

## Praktik implementasi

- Buat perubahan sekecil mungkin dan ikuti pola file yang sudah ada. Hindari refactor lintas role untuk permintaan yang hanya menyentuh satu layar.
- Query database harus memakai prepared statement (`PDO::prepare`) untuk nilai dari pengguna. Escape teks dinamis saat ditampilkan menggunakan `h()`.
- Semua form POST perlu token CSRF (`csrf_token()` dan `csrf_ok()`) serta validasi server-side. Jangan mengandalkan validasi browser saja.
- Pertahankan redirect setelah POST agar pengiriman tidak terulang ketika halaman di-refresh.
- CSS umum ada di `assets/css/style.css`; gaya calon penghuni ada di `assets/css/calon_penghuni.css`. Pastikan stylesheet khusus dimuat setelah stylesheet umum. Untuk perubahan visual, periksa tampilan mobile.
- Gunakan URL relatif yang sesuai kedalaman folder. Halaman di `calon_penghuni/` berada satu tingkat di bawah root.
- Jangan mengubah schema atau menjalankan SQL yang menghapus/mengganti data tanpa kebutuhan yang jelas. Urutan setup database dijelaskan di dokumentasi pengembangan.
- Jangan mengklaim notifikasi/email sungguhan: notifikasi calon penghuni sekarang berasal dari data session simulasi.

## Menjalankan dan memeriksa perubahan

1. Konfigurasikan koneksi PostgreSQL di `database/database.php` untuk lingkungan lokal.
2. Ikuti urutan SQL yang tercantum di `DOKUMENTASI_PENGEMBANGAN.md`.
3. Jalankan Apache dan PostgreSQL, lalu buka `http://localhost/simkos_pbl/` jika proyek berada di `C:\xampp\htdocs\simkos_pbl`.
4. Bila source dikerjakan pada folder lain, salin file yang berubah ke folder `htdocs` sebelum memeriksa hasil. Gunakan hard refresh jika perubahan CSS belum muncul.
5. Setelah mengubah PHP/CSS, lakukan pemeriksaan yang relevan dengan perubahan dan laporkan apa yang benar-benar sudah diperiksa. Jangan mengklaim perubahan telah diuji di browser bila belum.

## Lokasi dokumentasi

- Ringkasan pintu masuk: [README.md](README.md)
- Arsitektur, alur role, alur calon penghuni, database, setup, dan backlog: [DOKUMENTASI_PENGEMBANGAN.md](DOKUMENTASI_PENGEMBANGAN.md)
