<?php

namespace App\Http\Requests\AssetHolder;

use App\Enums\WorkUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetHolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'super_admin', 'tim_aset'], true);
    }

    public function rules(): array
    {
        return [
            'nama_pegawai' => ['required', 'string', 'max:150'],
            'nip' => ['nullable', 'string', 'regex:/^[0-9]+$/', 'max:30', 'unique:pemegang_aset,nip'],
            'email' => ['nullable', 'email', 'max:255', 'unique:pemegang_aset,email'],
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
        return [
            'nama_pegawai.required' => 'Nama pegawai wajib diisi.',
            'nip.regex' => 'NIP hanya boleh berisi angka.',
            'nip.unique' => 'NIP sudah terdaftar.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'unit_kerja.required' => 'Unit kerja wajib dipilih.',
            'unit_kerja' => 'Unit kerja tidak valid.',
            'jabatan.required' => 'Jabatan wajib diisi.',
            'nomor_hp.regex' => 'Nomor HP hanya boleh berisi angka, spasi, +, atau -.',
            'foto.image' => 'Foto pegawai harus berupa gambar.',
            'foto.mimes' => 'Foto pegawai harus berformat JPG, JPEG, PNG, atau WEBP.',
            'foto.max' => 'Ukuran foto pegawai maksimal 2 MB.',
            'nik.digits' => 'Nomor KTP/NIK harus tepat 16 digit.',
            'dokumen_ktp.mimes' => 'Dokumen KTP harus berformat PDF, JPG, JPEG, atau PNG.',
            'dokumen_ktp.max' => 'Ukuran dokumen KTP maksimal 5 MB.',
        ];
    }
}
