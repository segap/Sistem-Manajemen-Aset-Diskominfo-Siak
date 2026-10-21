<?php

namespace App\Http\Requests\Maintenance;

use App\Enums\AssetMaintenancePriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyAssetMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['tim_aset', 'super_admin'], true);
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approve,reject'],
            'prioritas' => ['required_if:decision,approve', 'nullable', Rule::enum(AssetMaintenancePriority::class)],
            'catatan_verifikasi' => ['required_if:decision,reject', 'nullable', 'string', 'max:3000'],
        ];
    }
}
