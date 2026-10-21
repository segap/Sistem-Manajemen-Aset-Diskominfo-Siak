<?php

namespace App\Http\Requests;

use App\Enums\AssetCondition;
use App\Enums\ProcurementStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyIncomingProcurementRequest extends FormRequest
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
            'decision' => ['required', Rule::in([
                ProcurementStatus::Verified->value,
                ProcurementStatus::Rejected->value,
            ])],
            'kondisi' => [
                Rule::requiredIf($this->input('decision') === ProcurementStatus::Verified->value),
                'nullable', Rule::enum(AssetCondition::class),
            ],
            'lokasi_penyimpanan' => [
                Rule::requiredIf($this->input('decision') === ProcurementStatus::Verified->value),
                'nullable', 'string', 'max:150',
            ],
            'catatan_verifikasi' => ['nullable', 'string', 'max:2000'],
            'alasan_penolakan' => [
                Rule::requiredIf($this->input('decision') === ProcurementStatus::Rejected->value),
                'nullable', 'string', 'max:2000',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'decision.required' => 'Keputusan verifikasi wajib dipilih.',
            'kondisi.required' => 'Kondisi barang wajib dipilih.',
            'lokasi_penyimpanan.required' => 'Lokasi penyimpanan wajib diisi.',
            'alasan_penolakan.required' => 'Alasan penolakan wajib diisi.',
        ];
    }
}
