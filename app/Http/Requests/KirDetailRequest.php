<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KirDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin' || $this->user()?->role === 'super_admin';
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'aset_id' => [
                'required',
                'integer',
                Rule::exists('aset', 'id')->where(fn ($query) => $query
                    ->whereNull('tanggal_penghapusan')
                    ->where('status_verifikasi', 'disetujui')),
            ],
            'kode_barang_id' => ['nullable', 'integer', Rule::exists('kode_barang', 'id')],
            'nibar' => ['nullable', 'string', 'max:100'],
            'nomor_register' => ['nullable', 'string', 'max:100'],
            'kode_lokasi' => ['nullable', 'string', 'max:100'],
            'kode_barang' => ['nullable', 'string', 'max:100'],
            'nama_barang' => ['required', 'string', 'max:150'],
            'spesifikasi_nama_barang' => ['nullable', 'string', 'max:5000'],
            'merek_tipe' => ['nullable', 'string', 'max:150'],
            'tahun_perolehan' => ['nullable', 'integer', 'between:1900,'.((int) date('Y') + 1)],
            'jumlah' => ['required', 'integer', 'min:1', 'max:1000000'],
            'satuan' => ['required', 'string', 'max:30'],
            'keterangan' => ['nullable', Rule::in(['B', 'RR', 'RS', 'RB'])],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'aset_id' => 'aset',
            'kode_barang_id' => 'kode barang master',
            'nibar' => 'NIBAR',
            'nomor_register' => 'nomor register',
            'kode_lokasi' => 'kode lokasi',
            'kode_barang' => 'kode barang',
            'nama_barang' => 'nama barang',
            'spesifikasi_nama_barang' => 'spesifikasi nama barang',
            'merek_tipe' => 'merek/tipe',
            'tahun_perolehan' => 'tahun perolehan',
            'jumlah' => 'jumlah',
            'satuan' => 'satuan',
            'keterangan' => 'keterangan',
        ];
    }

    public function messages(): array
    {
        return [
            'aset_id.required' => 'Barang KIR wajib dipilih dari Master Data.',
            'aset_id.exists' => 'Barang KIR harus berasal dari aset terverifikasi di Master Data.',
            'keterangan.in' => 'Keterangan hanya boleh dipilih: B, RR, RS, atau RB.',
        ];
    }
}
