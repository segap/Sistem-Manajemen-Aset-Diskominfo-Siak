<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProcurementProposalRequest extends FormRequest
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
            'nama_barang' => ['required', 'string', 'max:150'],
            'spesifikasi' => ['required', 'string', 'max:5000'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:1000000'],
            'estimasi_harga_satuan' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'alasan_kebutuhan' => ['required', 'string', 'max:5000'],
            'pengguna_barang_id' => ['required', 'integer', 'exists:users,id'],
            'penanggung_jawab_id' => ['required', 'integer', 'exists:users,id'],
            'url_referensi_gambar' => ['nullable', 'url:http,https', 'max:2048'],
            'url_ekatalog' => ['nullable', 'url:http,https', 'max:2048'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['nama_barang' => trim((string) $this->input('nama_barang'))]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nama_barang.required' => 'Nama barang wajib diisi.',
            'spesifikasi.required' => 'Spesifikasi barang wajib diisi.',
            'jumlah.required' => 'Jumlah barang wajib diisi.',
            'jumlah.min' => 'Jumlah barang minimal 1.',
            'estimasi_harga_satuan.required' => 'Estimasi harga satuan wajib diisi.',
            'alasan_kebutuhan.required' => 'Justifikasi teknis wajib diisi.',
            'pengguna_barang_id.exists' => 'Pengguna barang tidak ditemukan.',
            'penanggung_jawab_id.exists' => 'Penanggung jawab tidak ditemukan.',
            'photo.required' => 'Foto barang wajib diunggah.',
            'photo.image' => 'Berkas foto harus berupa gambar.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
        ];
    }
}
