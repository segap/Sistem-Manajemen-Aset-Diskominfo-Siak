<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePermintaanPersediaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
            'items.required' => 'Minimal pilih satu barang persediaan.',
            'items.*.jumlah_diminta.min' => 'Jumlah permintaan harus lebih dari 0.',
            'items.*.justifikasi_kebutuhan.required' => 'Justifikasi kebutuhan wajib diisi untuk setiap barang.',
            'items.*.justifikasi_kebutuhan.max' => 'Justifikasi kebutuhan maksimal 3000 karakter.',
        ];
    }
}
