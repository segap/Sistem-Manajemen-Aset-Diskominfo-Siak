<?php

namespace App\Http\Requests;

use App\Enums\AssetCondition;
use App\Models\AssetHolder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventorySurveyAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kategori_aset_id' => ['required', 'integer', 'exists:kategori_aset,id'],
            'pemegang_aset_id' => [
                'nullable',
                'integer',
                'exists:pemegang_aset,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (blank($value)) {
                        return;
                    }

                    if (! AssetHolder::query()->whereKey((int) $value)->where('is_active', true)->exists()) {
                        $fail('Pemegang aset yang dipilih tidak aktif.');
                    }
                },
            ],
            'tahun_perolehan' => ['nullable', 'integer', 'digits:4', 'between:1900,'.(date('Y') + 1)],
            'kondisi' => ['required', Rule::enum(AssetCondition::class)],
            'spesifikasi' => ['nullable', 'string', 'max:5000'],
            'catatan_kondisi' => ['nullable', 'string', 'max:5000'],
            'confirm_duplicate' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pemegang_aset_id' => $this->filled('pemegang_aset_id') ? $this->input('pemegang_aset_id') : null,
            'confirm_duplicate' => $this->boolean('confirm_duplicate'),
        ]);
    }
}
