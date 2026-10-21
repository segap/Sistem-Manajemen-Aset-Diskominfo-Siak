<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kode_kategori' => [
                'required', 'string', 'max:30', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('kategori_aset', 'kode_kategori')->ignore($this->route('asset_category')),
            ],
            'nama_kategori' => [
                'required', 'string', 'max:100',
                Rule::unique('kategori_aset', 'nama_kategori')->ignore($this->route('asset_category')),
            ],
            'ikon' => ['nullable', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'kode_kategori' => strtoupper(trim((string) $this->input('kode_kategori'))),
            'nama_kategori' => trim((string) $this->input('nama_kategori')),
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'kode_kategori.required' => 'Kode kategori wajib diisi.',
            'kode_kategori.regex' => 'Kode kategori hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'kode_kategori.unique' => 'Kode kategori sudah digunakan.',
            'nama_kategori.required' => 'Nama kategori wajib diisi.',
            'nama_kategori.unique' => 'Nama kategori sudah digunakan.',
        ];
    }
}
