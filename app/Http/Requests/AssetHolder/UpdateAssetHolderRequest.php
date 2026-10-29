<?php

namespace App\Http\Requests\AssetHolder;

use App\Enums\WorkUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetHolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'super_admin', 'tim_aset'], true);
    }

    public function rules(): array
    {
        $assetHolder = $this->route('assetHolder');

        return [
            'nama_pegawai' => ['required', 'string', 'max:150'],
            'nip' => ['nullable', 'string', 'regex:/^[0-9]+$/', 'max:30', Rule::unique('pemegang_aset', 'nip')->ignore($assetHolder)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('pemegang_aset', 'email')->ignore($assetHolder)],
            'unit_kerja' => ['required', Rule::enum(WorkUnit::class)],
            'jabatan' => ['required', 'string', 'max:150'],
            'nomor_hp' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\\-\\s]+$/'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'nik' => ['nullable', 'digits:16'],
            'dokumen_ktp' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama_pegawai' => trim((string) $this->input('nama_pegawai')),
            'email' => filled($this->input('email')) ? strtolower(trim((string) $this->input('email'))) : null,
            'nip' => filled($this->input('nip')) ? trim((string) $this->input('nip')) : null,
            'nomor_hp' => filled($this->input('nomor_hp')) ? trim((string) $this->input('nomor_hp')) : null,
            'alamat' => filled($this->input('alamat')) ? trim((string) $this->input('alamat')) : null,
            'nik' => filled($this->input('nik')) ? trim((string) $this->input('nik')) : null,
        ]);
    }

    public function messages(): array
    {
        return (new StoreAssetHolderRequest())->messages();
    }
}
