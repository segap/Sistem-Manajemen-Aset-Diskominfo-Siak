<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProcurementRequest extends FormRequest
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
            'usulan_pengadaan_id' => ['required', 'integer', 'exists:usulan_pengadaan,id', 'unique:pengadaan,usulan_pengadaan_id'],
            'kategori_aset_id' => ['required', 'integer', 'exists:kategori_aset,id'],
            'nama_barang' => ['required', 'string', 'max:150'],
            'merek_tipe' => ['nullable', 'string', 'max:150'],
            'spesifikasi' => ['required', 'string', 'max:5000'],
            'jumlah_dipesan' => ['required', 'integer', 'min:1', 'max:1000000'],
            'harga_satuan' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'pemasok' => ['required', 'string', 'max:150'],
            'tanggal_pesan' => ['required', 'date'],
            'perkiraan_tanggal_tiba' => ['nullable', 'date', 'after_or_equal:tanggal_pesan'],
            'alasan_pengadaan' => ['required', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama_barang' => trim((string) $this->input('nama_barang')),
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'usulan_pengadaan_id.unique' => 'Usulan ini sudah diproses menjadi pengadaan.',
            'usulan_pengadaan_id.required' => 'Pilih usulan pengadaan yang sudah disetujui.',
            'kategori_aset_id.required' => 'Kategori aset wajib dipilih.',
            'nama_barang.required' => 'Nama barang wajib diisi.',
            'spesifikasi.required' => 'Spesifikasi barang wajib diisi.',
            'jumlah_dipesan.required' => 'Jumlah pesanan wajib diisi.',
            'harga_satuan.required' => 'Harga satuan wajib diisi.',
            'pemasok.required' => 'Supplier wajib diisi.',
            'tanggal_pesan.required' => 'Tanggal pemesanan wajib diisi.',
            'alasan_pengadaan.required' => 'Alasan pengadaan wajib diisi.',
        ];
    }
}
