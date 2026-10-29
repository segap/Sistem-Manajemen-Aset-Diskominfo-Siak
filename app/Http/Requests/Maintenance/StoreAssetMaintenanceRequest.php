<?php

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAssetMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('pemeliharaan.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'maintenance_unit' => ['required', 'string', 'max:80'],
            'keluhan' => ['required', 'string', 'max:3000'],
            'catatan_pengajuan' => ['nullable', 'string', 'max:3000'],
            'foto_sebelum' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'video_sebelum' => ['nullable', 'file', 'mimes:mp4,mov,webm', 'max:20480'],
            'link_drive_dokumentasi' => ['nullable', 'url', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'foto_sebelum.max' => 'gambar/file lebih dari 2mb',
            'video_sebelum.max' => 'video lebih dari 20mb',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $url = $this->input('link_drive_dokumentasi');
            if (blank($url)) {
                return;
            }

            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

            if ($scheme !== 'https' || ! in_array($host, ['drive.google.com', 'docs.google.com'], true)) {
                $validator->errors()->add('link_drive_dokumentasi', 'Link dokumentasi harus berupa URL HTTPS Google Drive/Google Docs.');
            }
        });
    }
}
