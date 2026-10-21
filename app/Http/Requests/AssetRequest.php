<?php

namespace App\Http\Requests;

use App\Enums\AssetCondition;
use App\Models\AssetHolder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssetRequest extends FormRequest
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
        $asset = $this->route('asset');
        $isEdit = $asset !== null;

        return [
            'kategori_aset_id' => ['required', 'integer', 'exists:kategori_aset,id'],
            'kode_barang_id' => ['nullable', 'integer', 'exists:kode_barang,id'],
            'nama_aset' => ['required', 'string', 'max:150'],
            'merek_tipe' => ['nullable', 'string', 'max:150'],
            'spesifikasi' => [
                Rule::requiredIf(fn (): bool => in_array($this->input('kondisi'), [
                    AssetCondition::LightDamage->value,
                    AssetCondition::MediumDamage->value,
                    AssetCondition::HeavyDamage->value,
                    AssetCondition::Lost->value,
                ], true)),
                'string',
                'max:5000',
            ],
            'jumlah' => $isEdit
                ? ((int) $asset->jumlah === 1 ? ['required', 'integer', 'in:1'] : ['required', 'integer', 'min:1'])
                : ['required', 'integer', 'min:1', 'max:1000000'],
            'tahun_perolehan' => ['nullable', 'integer', 'digits:4', 'between:1900,'.(date('Y') + 1)],
            'kondisi' => ['required', Rule::enum(AssetCondition::class)],
            'pemasok' => ['nullable', 'string', 'max:150'],
            'pemegang_aset_id' => [
                'nullable',
                'integer',
                'exists:pemegang_aset,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (blank($value)) {
                        return;
                    }

                    $asset = $this->route('asset');
                    if ($asset && (int) $asset->pemegang_aset_id === (int) $value) {
                        return;
                    }

                    $isActive = AssetHolder::query()
                        ->whereKey((int) $value)
                        ->where('is_active', true)
                        ->exists();

                    if (! $isActive) {
                        $fail('Pemegang aset yang dipilih tidak aktif.');
                    }
                },
            ],
            'lokasi_penyimpanan' => ['nullable', 'string', 'max:150'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hapus_foto' => ['nullable', 'array'],
            'hapus_foto.*' => ['integer', 'exists:asset_photos,id'],
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
            $asset = $this->route('asset');
            $existingCount = $asset?->photos()->count() ?? 0;
            $deletedCount = $asset
                ? $asset->photos()->whereIn('id', $this->input('hapus_foto', []))->count()
                : 0;
            $newCount = count(array_filter($this->file('photos', [])));

            if ($this->hasFile('foto') && $newCount === 0) {
                $newCount = 1;
            }

            if (($existingCount - $deletedCount + $newCount) > 10) {
                $validator->errors()->add('photos', 'Maksimal 10 foto untuk setiap aset.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama_aset' => trim((string) $this->input('nama_aset')),
            'pemegang_aset_id' => $this->filled('pemegang_aset_id') ? $this->input('pemegang_aset_id') : null,
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'kategori_aset_id.required' => 'Kategori aset wajib dipilih.',
            'kategori_aset_id.exists' => 'Kategori aset tidak ditemukan.',
            'kode_barang_id.exists' => 'Kode barang master tidak ditemukan.',
            'nama_aset.required' => 'Nama barang wajib diisi.',
            'spesifikasi.required' => 'Deskripsi kerusakan/alasan kehilangan wajib diisi untuk kondisi Rusak Ringan, Rusak Sedang, Rusak Berat, atau Hilang.',
            'jumlah.required' => 'Jumlah barang wajib diisi.',
            'jumlah.min' => 'Jumlah barang minimal 1.',
            'jumlah.in' => 'Jumlah aset tidak dapat diubah. Satu record aset harus mewakili satu unit fisik.',
            'kondisi.required' => 'Kondisi barang wajib dipilih.',
            'pemegang_aset_id.exists' => 'Pemegang aset tidak ditemukan.',
            'foto.image' => 'Berkas foto harus berupa gambar.',
            'foto.max' => 'Ukuran foto maksimal 2 MB.',
            'photos.array' => 'Dokumentasi foto tidak valid.',
            'photos.max' => 'Maksimal 10 foto untuk setiap aset.',
            'photos.*.image' => 'Setiap foto harus berupa gambar.',
            'photos.*.mimes' => 'Foto harus berformat JPG, JPEG, PNG, atau WEBP.',
            'photos.*.max' => 'Ukuran setiap foto maksimal 2 MB.',
            'google_drive_url.url' => 'Link Google Drive harus berupa URL yang valid.',
            'google_drive_url.max' => 'Link Google Drive maksimal 2048 karakter.',
        ];
    }
}
