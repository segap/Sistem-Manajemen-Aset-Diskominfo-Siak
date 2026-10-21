# SIMIVA - Sistem Informasi Manajemen Aset

SIMIVA adalah Sistem Informasi Manajemen Aset berbasis Laravel 12 dengan tampilan Laravel Blade, Tailwind CSS, dan JavaScript ringan melalui Vite.

## Cara menjalankan

Jika project baru pertama kali dipakai, jalankan:

```bash
composer install
```

```bash
npm install
```

```bash
copy .env.example .env
```

```bash
php artisan key:generate
```

Pastikan database MySQL `manajemen_aset` sudah dibuat di phpMyAdmin, lalu jalankan:

```bash
php artisan migrate --seed
```

File `.env` berisi konfigurasi lokal dan tidak perlu dibagikan. Jika project dipindah ke komputer lain, gunakan `.env.example` lalu sesuaikan nama database, username, dan password MySQL.

Pastikan link storage publik tersedia:

```bash
php artisan storage:link
```

```bash
npm run build
```

Jalankan dari folder utama project:

```bash
php artisan serve
```

Lalu buka:

```text
http://127.0.0.1:8000
```

Jika memakai file bantuan Windows, cukup klik:

```text
run-project.bat
```

Untuk setup awal otomatis di Windows, bisa klik:

```text
setup-project.bat
```

File tersebut akan menjalankan install dependency, migrate, seed akun awal, dan build tampilan.

## Akun awal

```text
Super Admin
Email    : SuperAdmin@gmail.com
Password : 123

Admin
Email    : segap@gmail.com
Password : 123
```

## Perintah penting

Build tampilan production:

```bash
npm run build
```

Cek kualitas kode tampilan:

```bash
npm run lint
```

Cek test Laravel:

```bash
php artisan test
```

Jika test menampilkan error `could not find driver` pada koneksi `sqlite`, aktifkan extension `pdo_sqlite` dan `sqlite3` di konfigurasi PHP. Aplikasi utama tetap dapat berjalan memakai MySQL sesuai konfigurasi `.env`.

Migrasi database:

```bash
php artisan migrate
```

Isi data awal:

```bash
php artisan db:seed
```

## Struktur project

Seluruh aplikasi sudah berada dalam satu folder Laravel:

```text
app/              Backend Laravel
database/         Migration, factory, dan seeder
resources/js/     JavaScript global Blade
resources/views/  Halaman Laravel Blade
routes/           Route web dan API
public/build/     Hasil build Vite
```

Tidak perlu menjalankan server Vite terpisah untuk penggunaan biasa. Setelah `npm run build`, aplikasi bisa dibuka cukup dengan `php artisan serve`.

Route halaman seperti `/dashboard`, `/aset`, dan `/usulan-pengadaan` sudah diarahkan ke controller dan view Laravel Blade. Jadi halaman tetap aman saat dibuka langsung atau di-refresh dari browser.

Jika foto pengadaan atau file publik tidak muncul, jalankan ulang `php artisan storage:link`.

## Optimasi saat siap dipakai

Untuk penggunaan lokal biasa, cukup jalankan `php artisan serve`.

Jika aplikasi sudah siap dipakai dan konfigurasi tidak akan sering berubah, cache Laravel dapat dibuat dengan:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Jika setelah itu ada perubahan `.env`, route, atau tampilan Blade, bersihkan cache dengan:

```bash
php artisan optimize:clear
```

## Import data aset

Template import tersedia dari halaman Manajemen Data Barang. Header utama yang disarankan:

```text
kategori, kode_internal_opsional, nama_aset, merek_tipe, spesifikasi, jumlah, tahun_perolehan, kondisi, pemasok, lokasi
```

Beberapa variasi header umum juga dikenali, misalnya `nama_barang`, `kode_barang`, `merk`, `qty`, `stok`, `tahun_pengadaan`, `supplier`, dan `lokasi_penyimpanan`.

Jika kategori pada Excel belum tersedia di sistem, kategori akan dibuat otomatis dari nama yang diisi.
