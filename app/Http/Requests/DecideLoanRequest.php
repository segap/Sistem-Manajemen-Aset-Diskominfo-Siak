<?php

namespace App\Http\Requests;

use App\Enums\LoanStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideLoanRequest extends FormRequest
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
                LoanStatus::VerifiedByAssetTeam->value,
                LoanStatus::Approved->value,
                LoanStatus::Rejected->value,
            ])],
            'alasan_penolakan' => [
                Rule::requiredIf($this->input('decision') === LoanStatus::Rejected->value),
                'nullable', 'string', 'max:2000',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'decision.required' => 'Keputusan wajib dipilih.',
            'decision.in' => 'Keputusan hanya dapat berupa disetujui atau ditolak.',
            'alasan_penolakan.required' => 'Alasan penolakan wajib diisi.',
        ];
    }
}
