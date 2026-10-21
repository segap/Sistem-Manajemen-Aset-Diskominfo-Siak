<?php

namespace App\Http\Requests;

use App\Enums\AssetCondition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAssetConditionRequest extends FormRequest
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
            'ke_kondisi' => ['required', Rule::enum(AssetCondition::class)],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'dokumentasi_foto' => ['nullable', 'array', 'max:10'],
            'dokumentasi_foto.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'dokumentasi_video' => ['nullable', 'file', 'mimes:mp4,mov,webm', 'max:20480'],
            'google_drive_url' => [
                'nullable',
                'url',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $host = strtolower((string) parse_url((string) $value, PHP_URL_HOST));

                    if ($host !== '' && ! in_array($host, ['drive.google.com', 'docs.google.com'], true)) {
                        $fail('Link Google Drive harus berasal dari drive.google.com atau docs.google.com.');
                    }
                },
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $photos = array_filter($this->file('dokumentasi_foto', []));
            $hasVideo = $this->hasFile('dokumentasi_video');

            if (count($photos) === 0 && ! $hasVideo) {
                $validator->errors()->add('dokumentasi_foto', 'Dokumentasi wajib diisi minimal 1 foto atau 1 video.');
            }
        });
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'ke_kondisi.required' => 'Kondisi baru wajib dipilih.',
            'ke_kondisi.enum' => 'Kondisi baru tidak valid.',
            'catatan.max' => 'Catatan maksimal 1000 karakter.',
            'dokumentasi_foto.array' => 'Dokumentasi foto tidak valid.',
            'dokumentasi_foto.max' => 'Maksimal 10 foto dokumentasi.',
            'dokumentasi_foto.*.image' => 'Dokumentasi foto harus berupa gambar.',
            'dokumentasi_foto.*.mimes' => 'Foto dokumentasi harus berformat JPG, JPEG, PNG, atau WEBP.',
            'dokumentasi_foto.*.max' => 'Ukuran setiap foto dokumentasi maksimal 2 MB.',
            'dokumentasi_video.file' => 'Video dokumentasi tidak valid.',
            'dokumentasi_video.mimes' => 'Video dokumentasi harus berformat MP4, MOV, atau WEBM.',
            'dokumentasi_video.max' => 'Ukuran video dokumentasi maksimal 20 MB.',
            'google_drive_url.url' => 'Link Google Drive harus berupa URL yang valid.',
            'google_drive_url.max' => 'Link Google Drive maksimal 2048 karakter.',
        ];
    }
}
