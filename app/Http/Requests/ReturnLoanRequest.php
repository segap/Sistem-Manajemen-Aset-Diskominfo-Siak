<?php

namespace App\Http\Requests;

use App\Enums\AssetCondition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReturnLoanRequest extends FormRequest
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
            'details' => ['required', 'array', 'min:1'],
            'details.*.kondisi_kembali' => ['required', Rule::enum(AssetCondition::class)],
            'details.*.catatan_pengembalian' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'details.required' => 'Data pengembalian barang wajib diisi.',
            'details.*.kondisi_kembali.required' => 'Kondisi barang saat dikembalikan wajib dipilih.',
            'details.*.kondisi_kembali' => 'Kondisi pengembalian tidak valid.',
        ];
    }
}
