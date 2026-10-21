<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReceiveProcurementRequest extends FormRequest
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
            'jumlah_diterima' => ['required', 'integer', 'min:1'],
            'tanggal_penerimaan' => ['required', 'date', 'before_or_equal:now'],
            'catatan_penerimaan' => ['nullable', 'string', 'max:2000'],
            'bukti_penerimaan' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'jumlah_diterima.required' => 'Jumlah barang diterima wajib diisi.',
            'jumlah_diterima.min' => 'Jumlah barang diterima minimal 1.',
            'tanggal_penerimaan.required' => 'Waktu penerimaan wajib diisi.',
            'tanggal_penerimaan.before_or_equal' => 'Waktu penerimaan tidak boleh melewati waktu sekarang.',
            'bukti_penerimaan.required' => 'Foto bukti barang diterima wajib diupload atau diambil dari kamera.',
            'bukti_penerimaan.image' => 'Bukti penerimaan harus berupa gambar.',
            'bukti_penerimaan.mimes' => 'Bukti penerimaan harus berformat JPG, PNG, atau WEBP.',
            'bukti_penerimaan.max' => 'gambar/file lebih dari 2mb',
        ];
    }
}
