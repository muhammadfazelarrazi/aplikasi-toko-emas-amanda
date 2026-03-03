# Sistem Informasi Toko Emas Amanda

Sistem Informasi Toko Emas Amanda adalah solusi perangkat lunak berbasis web yang dirancang untuk mendigitalisasi seluruh ekosistem operasional pada toko perhiasan. Sistem ini mengintegrasikan manajemen inventaris, otomasi transaksi, hingga layanan purnajual dalam satu platform terpadu.

## Fitur Utama Sistem

Sistem ini dikembangkan dengan fokus pada akurasi data dan kemudahan penggunaan:

* **Autentikasi & Otorisasi:** Sistem keamanan akses menggunakan session untuk membedakan hak akses antara Administrator dan Kasir.
* **Manajemen Inventaris (Master Data):**
  * Pengaturan harga emas harian yang menjadi acuan kalkulasi sistem.
  * Katalog perhiasan lengkap dengan detail berat, kadar, dan kategori barang.
  * Manajemen database pelanggan dan supplier.
* **Otomasi Transaksi (Point of Sale):**
  * **Penjualan:** Kalkulasi otomatis total harga berdasarkan berat emas dan harga harian yang berlaku.
  * **Buyback (Beli Kembali):** Fitur khusus untuk menangani pengembalian barang dari pelanggan dengan potongan harga yang terstandarisasi.
* **Automated Mailer System (Prototype):** Integrasi layanan pengiriman Surat Emas Digital otomatis ke alamat email pelanggan sebagai bukti kepemilikan yang modern.
* **Reporting & Document Generation:**
  * Cetak nota transaksi dan surat emas secara instan.
  * Laporan omzet harian dan bulanan yang dapat diekspor ke format PDF.
* **Quality Assurance:** Validasi logika bisnis melalui pengujian unit otomatis menggunakan PHPUnit.

## Dependensi & Spesifikasi Teknis

Sistem dikembangkan dan diuji pada lingkungan pengembangan berikut:

* **Runtime:** PHP Versi 8.0.30.
* **Database:** MySQL/MariaDB (menggunakan mesin InnoDB).
* **Web Server:** Laragon (Recommended) atau XAMPP.
* **Package Manager:** Composer (untuk manajemen library vendor).
* **Library Pihak Ketiga:**
  * **dompdf/dompdf:** Untuk mesin render dokumen PDF.
  * **phpmailer/phpmailer:** Untuk menangani protokol SMTP pengiriman email.
  * **phpunit/phpunit:** Sebagai framework pengujian unit sistem.

## Panduan Instalasi & Penggunaan

Ikuti langkah-langkah teknis berikut secara berurutan untuk menjalankan sistem di lingkungan lokal Anda:

### 1. Kloning Repositori
Buka terminal atau Git Bash Anda, arahkan ke direktori server lokal (`www` jika menggunakan Laragon, atau `htdocs` jika menggunakan XAMPP), lalu jalankan perintah berikut:
`git clone [URL_REPOSITORI_ANDA]`

### 2. Konfigurasi Basis Data
1. Aktifkan modul Apache dan MySQL pada panel kontrol server lokal Anda.
2. Akses antarmuka pengelolaan database melalui browser di `http://localhost/phpmyadmin`.
3. Buat database baru dengan nama spesifik: `db_toko_emas_amanda`.
4. Pilih database yang baru dibuat, klik tab **Import**, cari dan pilih file `db_toko_emas_amanda.sql` yang berada di dalam folder `docs/` pada repositori, lalu klik **Go** atau **Import**.

### 3. Sinkronisasi Library (Composer)
Buka terminal dan pastikan Anda berada pada root direktori proyek, lalu jalankan perintah:
`composer install`
Perintah ini akan secara otomatis membaca file `composer.json`, membuat direktori `vendor/`, dan mengunduh seluruh library yang diperlukan (dompdf, PHPMailer, PHPUnit).

### 4. Konfigurasi Koneksi Sistem
Buka file `config/database.php` menggunakan editor teks pilihan Anda (misalnya Visual Studio Code). Pastikan variabel konfigurasi koneksi sesuai dengan kredensial server lokal Anda:
* `$host = 'localhost';`
* `$user = 'root';` (Default username)
* `$pass = '';` (Default password kosong untuk XAMPP/Laragon)
* `$db   = 'db_toko_emas_amanda';`

### 5. Menjalankan Aplikasi
Akses aplikasi melalui web browser dengan memasukkan alamat:
`http://localhost/toko_emas_amanda` (pastikan nama folder di URL sesuai dengan nama folder hasil kloning repositori Anda).

**Kredensial Login (Akun Demo):**
Gunakan data berikut untuk masuk ke dalam sistem dan mencoba fitur yang ada:
* **Username:** `admin`
* **Password:** `admin`

## Informasi Khusus: Fitur Surat Emas Digital (Mailer)

Sistem ini memiliki fitur unggulan berupa pengiriman Surat Emas Digital otomatis ke email pelanggan. Namun, dikarenakan status pengembangan saat ini masih dalam tahap Prototipe Lokal (Localhost), harap perhatikan batasan teknis berikut:

1. **Aksesibilitas Tautan:** Tautan (*link*) surat digital berbentuk PDF yang diterima pelanggan melalui email saat ini hanya dapat diakses atau dibuka melalui **perangkat yang sama** dengan server lokal yang sedang berjalan.
2. **Mekanisme Jaringan:** Keterbatasan ini terjadi karena URL file dokumen masih merujuk secara absolut pada `localhost`. Jika email tersebut dibuka di perangkat yang berbeda (misalnya: smartphone pelanggan atau komputer lain), browser tidak akan dapat menemukan server dokumen tersebut.
3. **Pengembangan Mendatang:** Fitur ini akan berfungsi secara universal dan tautan dapat dibuka di perangkat mana saja setelah sistem ini di-deploy atau di-hosting ke layanan server publik.

---
*Dokumentasi teknis lebih rinci mengenai Spesifikasi Kebutuhan Perangkat Lunak (SRS), Diagram Alir Data (DFD), dan Entity Relationship Diagram (ERD) dapat ditemukan secara lengkap pada direktori `docs/`.*