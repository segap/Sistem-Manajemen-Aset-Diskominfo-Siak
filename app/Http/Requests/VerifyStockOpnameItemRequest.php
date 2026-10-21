<?php

namespace App\Http\Requests;

use App\Enums\StockOpnameVerificationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyStockOpnameItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in([
                StockOpnameVerificationStatus::Approved->value,
                StockOpnameVerificationStatus::Rejected->value,
            ])],
            'catatan_verifikasi' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
