<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAssetDisposalRequest extends FormRequest
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
            'aset_id' => ['required', 'integer', 'exists:aset,id'],
            'catatan' => ['nullable', 'string', 'max:3000'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'aset_id.required' => 'Aset yang akan dihapus wajib dipilih.',
            'aset_id.exists' => 'Data aset tidak ditemukan.',
            'document.mimes' => 'Dokumen harus berformat PDF, JPG, JPEG, atau PNG.',
            'document.max' => 'Ukuran dokumen maksimal 10 MB.',
        ];
    }
}
