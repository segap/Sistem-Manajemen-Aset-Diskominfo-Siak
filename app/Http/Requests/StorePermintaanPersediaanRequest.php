<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermintaanPersediaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asal_permintaan' => ['required', Rule::in(['sendiri', 'pegawai_lain'])],
            'pemegang_aset_id' => [
                'nullable',
                'integer',
                'required_if:asal_permintaan,pegawai_lain',
                Rule::exists('pemegang_aset', 'id'),
            ],
            'keperluan' => ['nullable', 'string', 'max:3000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.persediaan_id' => ['required', 'integer', 'exists:stock_opname_items,id'],
            'items.*.jumlah_diminta' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.justifikasi_kebutuhan' => ['required', 'string', 'max:3000'],
        ];
    }

    public function messages(): array
    {
        return [
            'asal_permintaan.required' => 'Asal permintaan wajib dipilih.',
            'asal_permintaan.in' => 'Asal permintaan tidak valid.',
            'pemegang_aset_id.required_if' => 'Pegawai pemohon wajib dipilih.',
            'pemegang_aset_id.exists' => 'Pegawai pemohon tidak ditemukan.',
            'items.required' => 'Minimal pilih satu barang persediaan.',
            'items.*.jumlah_diminta.min' => 'Jumlah permintaan harus lebih dari 0.',
            'items.*.justifikasi_kebutuhan.required' => 'Justifikasi kebutuhan wajib diisi untuk setiap barang.',
            'items.*.justifikasi_kebutuhan.max' => 'Justifikasi kebutuhan maksimal 3000 karakter.',
        ];
    }
}
