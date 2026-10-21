# Permission Tahap 1

Tahap ini hanya menyediakan struktur dan katalog. Sidebar, route middleware, controller, dan modal Manajemen Role belum memakai permission baru.

## Katalog

Katalog sumber tunggal berada di `App\Support\PermissionCatalog`.

- Setiap menu/submenu memiliki permission `*.view` tersendiri.
- Aksi backend dipisahkan, misalnya `aset.create`, `stock_opname.verify`, `peminjaman.approve`, `laporan_rekonsiliasi_bulanan.upload`, dan `roles.delete`.
- Permission export dipisahkan per modul untuk persiapan pengamanan endpoint `/export/{dataset}/{format}` pada tahap berikutnya.

## Inisialisasi awal

`PermissionInitializer` hanya menambahkan katalog yang belum ada dengan `firstOrCreate`. Nama, grup, dan permission yang sudah tersimpan tidak ditimpa.

Untuk setiap role yang belum memiliki satupun pivot permission, mapping lama ditambahkan dengan `insertOrIgnore`. Role yang sudah memiliki pivot dilewati seluruhnya agar konfigurasi administrator tidak tertimpa. `super_admin` sengaja tidak diberi pivot karena bypass tetap ditangani oleh `PermissionService`. `pimpinan` hanya dipetakan ke `dashboard.view`.

## Batas kompatibilitas

Mapping awal mengikuti route/backend yang diaudit, bukan hanya sidebar. Aturan workflow dan kepemilikan data tetap berada di controller dan belum boleh dihapus ketika permission nanti diaktifkan. Alias role lama `tim_aset_persediaan` dan `aset-persediaan` disiapkan dengan mapping yang sama dengan `timaset_persediaan` apabila role tersebut tersedia.

## Perintah manual setelah backup dan review

Jangan gunakan `php artisan migrate` umum karena ada migration lain yang masih pending. Jalankan hanya setelah persetujuan dan backup terverifikasi:

```powershell
php artisan migrate --path=database/migrations/2026_10_21_000001_create_permissions_table.php
php artisan migrate --path=database/migrations/2026_10_21_000002_create_role_permissions_table.php
php artisan permissions:initialize --force
```

Perintah terakhir menulis katalog dan mapping. Jalankan hanya sekali setelah memeriksa outputnya. Ia tidak menghapus permission atau role yang ada.
