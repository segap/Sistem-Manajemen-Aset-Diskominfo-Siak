<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockOpnameItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stock_opname_category_id' => ['required', 'integer', 'exists:stock_opname_categories,id'],
            'kode_barang_id' => ['nullable', 'integer', 'exists:kode_barang,id'],
            'nama_barang' => ['required', 'string', 'max:150'],
            'merek_tipe' => ['nullable', 'string', 'max:150'],
            'satuan' => ['required', 'string', 'max:40'],
            'stok' => ['required', 'integer', 'min:0', 'max:1000000'],
            'lokasi_penyimpanan' => ['nullable', 'string', 'max:150'],
            'keterangan' => ['nullable', 'string', 'max:3000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama_barang' => trim((string) $this->input('nama_barang')),
            'satuan' => trim((string) $this->input('satuan')),
        ]);
    }
}
