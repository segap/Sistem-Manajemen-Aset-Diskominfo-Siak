<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetInventorySurvey extends Model
{
    protected $table = 'asset_inventory_surveys';

    protected $fillable = [
        'asset_import_batch_id', 'pemegang_aset_id', 'kategori_aset_id',
        'nama_pegawai_excel', 'unit_kerja_excel', 'nama_barang', 'jumlah',
        'jumlah_excel', 'kondisi_excel', 'catatan_kerusakan', 'nama_sheet', 'baris_excel',
        'status', 'ditindaklanjuti_pada', 'ditindaklanjuti_oleh', 'asset_id',
    ];

    protected function casts(): array
    {
        return ['jumlah' => 'integer', 'baris_excel' => 'integer', 'ditindaklanjuti_pada' => 'datetime'];
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(AssetImportBatch::class, 'asset_import_batch_id');
    }

    public function assetHolder(): BelongsTo
    {
        return $this->belongsTo(AssetHolder::class, 'pemegang_aset_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'kategori_aset_id');
    }

    public function recordedAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}
