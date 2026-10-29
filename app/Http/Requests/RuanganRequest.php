<?php

namespace App\Http\Requests;

use App\Enums\RuanganStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RuanganRequest extends FormRequest
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
            'kode_ruangan' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9.\/_-]+$/',
                Rule::unique('ruangan', 'kode_ruangan')->ignore($this->route('ruangan')),
            ],
            'kode_lokasi_id' => ['nullable', 'integer', 'exists:kode_lokasi,id'],
            'nama_ruangan' => ['required', 'string', 'max:150'],
            'pemegang_aset_id' => [
                'nullable',
                'integer',
                Rule::exists('pemegang_aset', 'id')->where('is_active', true),
            ],
            'keterangan' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', Rule::enum(RuanganStatus::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'kode_ruangan' => strtoupper(trim((string) $this->input('kode_ruangan'))),
            'nama_ruangan' => trim((string) $this->input('nama_ruangan')),
            'kode_lokasi_id' => blank($this->input('kode_lokasi_id')) ? null : $this->input('kode_lokasi_id'),
            'pemegang_aset_id' => blank($this->input('pemegang_aset_id')) ? null : $this->input('pemegang_aset_id'),
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'kode_ruangan.required' => 'Kode ruangan wajib diisi.',
            'kode_ruangan.unique' => 'Kode ruangan sudah digunakan.',
            'kode_ruangan.regex' => 'Kode ruangan hanya boleh huruf besar, angka, titik, garis miring, underscore, dan strip.',
            'kode_lokasi_id.exists' => 'Kode lokasi master tidak ditemukan.',
            'nama_ruangan.required' => 'Nama ruangan wajib diisi.',
            'pemegang_aset_id.exists' => 'Data pegawai tidak ditemukan atau tidak aktif.',
            'status.required' => 'Status ruangan wajib dipilih.',
            'status.enum' => 'Status ruangan tidak valid.',
        ];
    }
}
