<?php

namespace Tests\Feature;

use App\Enums\AssetCondition;
use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\StockOpnameCategory;
use App\Models\StockOpnameItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetUnitCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_data_creates_one_physical_asset_unit_when_quantity_is_one(): void
    {
        $category = AssetCategory::query()->create([
            'kode_kategori' => 'ELK',
            'nama_kategori' => 'Elektronik',
        ]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->post(route('web.aset.store'), [
                'kategori_aset_id' => $category->id,
                'nama_aset' => 'Laptop',
                'merek_tipe' => 'Asus TUF Gaming',
                'spesifikasi' => 'Unit test asset',
                'jumlah' => 1,
                'tahun_perolehan' => 2026,
                'kondisi' => AssetCondition::Good->value,
                'lokasi_penyimpanan' => 'Ruang Informasi dan Komunikasi Publik',
            ])
            ->assertRedirect();

        $asset = Asset::query()->with('conditionStocks')->sole();

        $this->assertSame(1, $asset->jumlah);
        $this->assertNotEmpty($asset->kode_internal);
        $this->assertNotEmpty($asset->token_publik);
        $this->assertSame('menunggu_verifikasi', $asset->status_verifikasi);
        $this->assertSame(1, (int) $asset->conditionStocks->firstWhere('kondisi', AssetCondition::Good->value)?->jumlah);
    }

    public function test_master_data_creates_distinct_physical_asset_units_for_batch_quantity(): void
    {
        $category = AssetCategory::query()->create([
            'kode_kategori' => 'ELK',
            'nama_kategori' => 'Elektronik',
        ]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->post(route('web.aset.store'), [
                'kategori_aset_id' => $category->id,
                'nama_aset' => 'Laptop',
                'merek_tipe' => 'Asus TUF Gaming',
                'spesifikasi' => 'Unit test asset',
                'jumlah' => 3,
                'tahun_perolehan' => 2026,
                'kondisi' => AssetCondition::Good->value,
                'lokasi_penyimpanan' => 'Ruang Informasi dan Komunikasi Publik',
            ])
            ->assertRedirect();

        $assets = Asset::query()->with('conditionStocks')->orderBy('id')->get();

        $this->assertCount(3, $assets);
        $this->assertEquals([1, 1, 1], $assets->pluck('jumlah')->all());
        $this->assertCount(3, $assets->pluck('kode_internal')->unique());
        $this->assertCount(3, $assets->pluck('token_publik')->unique());
        $this->assertEquals(['menunggu_verifikasi'], $assets->pluck('status_verifikasi')->unique()->values()->all());

        foreach ($assets as $asset) {
            $this->assertSame(1, (int) $asset->conditionStocks->firstWhere('kondisi', AssetCondition::Good->value)?->jumlah);
        }
    }

    public function test_stock_opname_inventory_stays_aggregate_stock_based(): void
    {
        $category = StockOpnameCategory::query()->create([
            'nama_kategori' => 'Alat Kebersihan',
        ]);

        StockOpnameItem::query()->create([
            'stock_opname_category_id' => $category->id,
            'kode_persediaan' => 'WIP/001',
            'kode_internal' => 'WIP/001',
            'nama_barang' => 'Wipol',
            'satuan' => 'Botol',
            'stok' => 12,
            'status_verifikasi' => 'disetujui',
            'is_active' => true,
        ]);

        $this->assertSame(1, StockOpnameItem::query()->count());
        $this->assertSame(12, StockOpnameItem::query()->where('kode_internal', 'WIP/001')->value('stok'));
    }
}
