<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddStockOpnameStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jumlah_masuk' => ['required', 'integer', 'min:1', 'max:1000000'],
            'tanggal_masuk' => ['required', 'date'],
            'keterangan' => ['nullable', 'string', 'max:3000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'keterangan' => filled($this->input('keterangan')) ? trim((string) $this->input('keterangan')) : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'jumlah_masuk.required' => 'Jumlah stok masuk wajib diisi.',
            'jumlah_masuk.integer' => 'Jumlah stok masuk harus berupa angka bulat.',
            'jumlah_masuk.min' => 'Jumlah stok masuk minimal 1.',
            'tanggal_masuk.required' => 'Tanggal masuk wajib diisi.',
            'tanggal_masuk.date' => 'Tanggal masuk tidak valid.',
        ];
    }
}
