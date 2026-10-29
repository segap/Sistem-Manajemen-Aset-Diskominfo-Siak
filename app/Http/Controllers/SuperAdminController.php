<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SuperAdminController extends Controller
{
    public function __invoke(): View
    {
        return view('super-admin.index', [
            'modules' => [
                ['Dashboard', 'Ringkasan kondisi dan aktivitas sistem.', '/dashboard'],
                ['Manajemen Aset', 'Data inventaris, kondisi, stok, detail, dan QR.', '/aset'],
                ['Kategori Aset', 'Pengelompokan master data aset.', '/kategori-aset'],
                ['Peminjaman', 'Pengajuan, persetujuan, serah terima, dan pengembalian.', '/peminjaman'],
                ['Persetujuan', 'Antrean persetujuan terpadu Kasubbag.', '/persetujuan'],
                ['Usulan Pengadaan', 'Usulan dan tahapan verifikasi pengadaan.', '/usulan-pengadaan'],
                ['Penghapusan Aset', 'Usulan dan keputusan penghapusan inventaris.', '/penghapusan-aset'],
                ['Laporan', 'Pratinjau serta ekspor PDF dan Excel.', '/laporan'],
                ['Manajemen User', 'Akun, role, status, dan reset password.', '/users'],
                ['Profil Saya', 'Identitas dan keamanan akun Super Admin.', '/profile'],
            ],
        ]);
    }
}
