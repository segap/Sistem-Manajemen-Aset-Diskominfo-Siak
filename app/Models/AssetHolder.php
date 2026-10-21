<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetHolder extends Model
{
    protected $table = 'pemegang_aset';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'nama_pegawai',
        'nip',
        'email',
        'unit_kerja',
        'jabatan',
        'nomor_hp',
        'foto_path',
        'nik',
        'ktp_path',
        'ktp_nama_file',
        'ktp_mime',
        'is_active',
    ];

    /** @var list<string> */
    protected $hidden = [
        'nik',
        'ktp_path',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'pemegang_aset_id');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
