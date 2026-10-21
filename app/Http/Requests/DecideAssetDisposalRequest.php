<?php

namespace App\Http\Requests;

use App\Enums\AssetDisposalStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideAssetDisposalRequest extends FormRequest
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
                AssetDisposalStatus::Approved->value,
                AssetDisposalStatus::Rejected->value,
            ])],
            'alasan_penolakan' => [
                Rule::requiredIf($this->input('decision') === AssetDisposalStatus::Rejected->value),
                'nullable', 'string', 'max:3000',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'decision.required' => 'Keputusan wajib dipilih.',
            'decision.in' => 'Keputusan tidak valid.',
            'alasan_penolakan.required' => 'Alasan penolakan wajib diisi.',
        ];
    }
}
