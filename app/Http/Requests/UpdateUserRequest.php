<?php

namespace App\Http\Requests;

use App\Enums\WorkUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'nip' => [
                'sometimes', 'nullable', 'string', 'regex:/^[0-9]+$/',
                Rule::unique('users', 'nip')->ignore($this->route('user')),
            ],
            'email' => [
                'sometimes', 'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
            'role' => ['sometimes', 'required', Rule::exists('roles', 'kode')],
            'work_unit' => ['sometimes', 'required', Rule::enum(WorkUnit::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => trim((string) $this->input('name'))]);
        }

        if ($this->has('email')) {
            $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nip.regex' => 'NIP/NIK hanya boleh berisi angka.',
            'nip.unique' => 'NIP/NIK sudah digunakan.',
            'email.unique' => 'Email sudah digunakan.',
            'work_unit.required' => 'Unit Kerja wajib dipilih.',
            'work_unit.enum' => 'Unit Kerja tidak valid.',
        ];
    }
}
