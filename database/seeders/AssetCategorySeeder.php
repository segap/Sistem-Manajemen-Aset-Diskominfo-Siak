<?php

namespace Database\Seeders;

use App\Models\AssetCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssetCategorySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['kode_kategori' => 'LPT', 'nama_kategori' => 'Laptop', 'ikon' => 'laptop', 'deskripsi' => 'Perangkat laptop operasional kantor.'],
            ['kode_kategori' => 'KMR', 'nama_kategori' => 'Kamera', 'ikon' => 'camera', 'deskripsi' => 'Peralatan dokumentasi foto dan video.'],
            ['kode_kategori' => 'MJA', 'nama_kategori' => 'Meja', 'ikon' => 'table', 'deskripsi' => 'Meja kerja dan meja rapat.'],
            ['kode_kategori' => 'KRS', 'nama_kategori' => 'Kursi', 'ikon' => 'chair', 'deskripsi' => 'Kursi kerja dan kursi rapat.'],
            ['kode_kategori' => 'PRN', 'nama_kategori' => 'Printer', 'ikon' => 'printer', 'deskripsi' => 'Perangkat cetak dokumen.'],
            ['kode_kategori' => 'JRG', 'nama_kategori' => 'Jaringan', 'ikon' => 'network', 'deskripsi' => 'Perangkat pendukung jaringan dan konektivitas.'],
        ];

        foreach ($categories as $category) {
            $assetCategory = AssetCategory::query()
                ->where('kode_kategori', $category['kode_kategori'])
                ->orWhere('nama_kategori', $category['nama_kategori'])
                ->first();

            if ($assetCategory) {
                $assetCategory->update([
                    'ikon' => $assetCategory->ikon ?: $category['ikon'],
                    'deskripsi' => $assetCategory->deskripsi ?: $category['deskripsi'],
                ]);

                continue;
            }

            AssetCategory::query()->create($category);
        }
    }
}
