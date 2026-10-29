<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProcurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kategori_aset_id' => ['required', 'integer', 'exists:kategori_aset,id'],
            'merek_tipe' => ['nullable', 'string', 'max:150'],
            'pemasok' => ['required', 'string', 'max:150'],
            'tanggal_pesan' => ['required', 'date'],
            'perkiraan_tanggal_tiba' => ['nullable', 'date', 'after_or_equal:tanggal_pesan'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'kategori_aset_id.required' => 'Kategori aset wajib dipilih.',
            'pemasok.required' => 'Supplier wajib diisi.',
            'tanggal_pesan.required' => 'Tanggal pemesanan wajib diisi.',
        ];
    }
}
