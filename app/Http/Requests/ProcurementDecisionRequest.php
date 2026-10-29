<?php

namespace App\Http\Requests;

use App\Enums\ProcurementProposalStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcurementDecisionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->routeIs('web.usulan-pengadaan.keputusan-tim-aset')) {
            return $this->user()?->hasPermission('usulan_pengadaan.verify') ?? false;
        }

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
                ProcurementProposalStatus::Verified->value,
                ProcurementProposalStatus::Approved->value,
                ProcurementProposalStatus::Rejected->value,
            ])],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'alasan_penolakan' => [
                Rule::requiredIf($this->input('decision') === ProcurementProposalStatus::Rejected->value),
                'nullable', 'string', 'max:2000',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'decision.required' => 'Keputusan wajib dipilih.',
            'decision.in' => 'Keputusan tidak valid untuk proses pengadaan.',
            'alasan_penolakan.required' => 'Alasan penolakan wajib diisi.',
        ];
    }
}
