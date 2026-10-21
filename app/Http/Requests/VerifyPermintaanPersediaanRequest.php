<?php

namespace App\Http\Requests;

use App\Enums\PermintaanPersediaanStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyPermintaanPersediaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in([PermintaanPersediaanStatus::Approved->value, PermintaanPersediaanStatus::Rejected->value])],
            'catatan' => ['nullable', 'string', 'max:3000', 'required_if:decision,'.PermintaanPersediaanStatus::Rejected->value],
            'items' => ['required_if:decision,'.PermintaanPersediaanStatus::Approved->value, 'array'],
            'items.*.jumlah_disetujui' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'catatan.required_if' => 'Alasan penolakan wajib diisi.',
            'items.*.jumlah_disetujui.min' => 'Jumlah disetujui harus lebih dari 0.',
        ];
    }
}
