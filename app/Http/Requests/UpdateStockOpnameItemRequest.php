<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockOpnameItemRequest extends FormRequest
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
            'lokasi_penyimpanan' => ['nullable', 'string', 'max:150'],
            'keterangan' => ['nullable', 'string', 'max:3000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama_barang' => trim((string) $this->input('nama_barang')),
            'satuan' => trim((string) $this->input('satuan')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function messages(): array
    {
        return [
            'nama_barang.required' => 'Nama barang wajib diisi.',
        ];
    }
}
