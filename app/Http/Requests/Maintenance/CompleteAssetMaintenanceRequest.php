<?php

namespace App\Http\Requests\Maintenance;

use App\Enums\AssetCondition;
use App\Enums\AssetMaintenanceType;
use App\Enums\MaintenanceFinalResult;
use App\Enums\MaintenanceMainFunctionStatus;
use App\Enums\MaintenanceTestResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CompleteAssetMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['tim_aset', 'super_admin'], true);
    }

    public function rules(): array
    {
        return [
            'penyedia_servis' => ['nullable', 'string', 'max:255'],
            'jenis_pemeliharaan' => ['required', Rule::enum(AssetMaintenanceType::class)],
            'jenis_pemeliharaan_lainnya' => ['required_if:jenis_pemeliharaan,'.AssetMaintenanceType::Other->value, 'nullable', 'string', 'max:255'],
            'nama_pelaksana' => ['required', 'string', 'max:255'],
            'identitas_pelaksana' => ['nullable', 'string', 'max:255'],
            'komponen_material' => ['nullable', 'string', 'max:3000'],
            'tindakan_servis' => ['nullable', 'string', 'max:3000'],
            'biaya_servis' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'fungsi_utama' => ['required', Rule::enum(MaintenanceMainFunctionStatus::class)],
            'hasil_pengujian' => ['required', Rule::enum(MaintenanceTestResult::class)],
            'hasil_akhir_pemeliharaan' => ['required', Rule::enum(MaintenanceFinalResult::class)],
            'nomor_berita_acara' => ['required', 'string', 'max:100'],
            'tanggal_berita_acara' => ['required', 'date'],
            'lokasi_pelaksanaan' => ['required', 'string', 'max:255'],
            'dasar_pelaksanaan' => ['nullable', 'string', 'max:3000'],
            'tempat_ttd' => ['required', 'string', 'max:150'],
            'jabatan_penanggung_jawab' => ['required', 'string', 'max:255'],
            'nama_penanggung_jawab' => ['required', 'string', 'max:255'],
            'nip_penanggung_jawab' => ['required', 'string', 'max:100'],
            'invoice' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'dokumentasi_servis' => ['nullable', 'array', 'max:5'],
            'dokumentasi_servis.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'video_dokumentasi' => ['nullable', 'file', 'mimes:mp4,mov,webm', 'max:20480'],
            'link_drive_dokumentasi' => ['nullable', 'url', 'max:2048'],
            'kondisi_setelah' => ['required', Rule::in([
                AssetCondition::Good->value,
                AssetCondition::LightDamage->value,
                AssetCondition::MediumDamage->value,
                AssetCondition::HeavyDamage->value,
            ])],
            'catatan_hasil' => ['required', 'string', 'max:3000'],
            'foto_setelah' => [
                Rule::requiredIf($this->input('kondisi_setelah') === AssetCondition::Good->value),
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'jenis_pemeliharaan.required' => 'Jenis pemeliharaan wajib dipilih.',
            'jenis_pemeliharaan_lainnya.required_if' => 'Keterangan jenis pemeliharaan wajib diisi jika memilih Lainnya.',
            'nama_pelaksana.required' => 'Pelaksana/teknisi wajib diisi.',
            'fungsi_utama.required' => 'Fungsi utama wajib dipilih.',
            'hasil_pengujian.required' => 'Hasil pengujian wajib dipilih.',
            'hasil_akhir_pemeliharaan.required' => 'Hasil akhir pemeliharaan wajib dipilih.',
            'nomor_berita_acara.required' => 'Nomor Berita Acara wajib diisi.',
            'tanggal_berita_acara.required' => 'Tanggal Berita Acara wajib diisi.',
            'lokasi_pelaksanaan.required' => 'Lokasi pelaksanaan wajib diisi.',
            'tempat_ttd.required' => 'Tempat tanda tangan wajib diisi.',
            'jabatan_penanggung_jawab.required' => 'Jabatan penanggung jawab wajib diisi.',
            'nama_penanggung_jawab.required' => 'Nama penanggung jawab wajib diisi.',
            'nip_penanggung_jawab.required' => 'NIP penanggung jawab wajib diisi.',
            'invoice.max' => 'gambar/file lebih dari 2mb',
            'dokumentasi_servis.*.max' => 'gambar/file lebih dari 2mb',
            'video_dokumentasi.max' => 'video lebih dari 20mb',
            'foto_setelah.max' => 'gambar/file lebih dari 2mb',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (
                $this->input('hasil_akhir_pemeliharaan') === MaintenanceFinalResult::Good->value
                && (
                    $this->input('fungsi_utama') !== MaintenanceMainFunctionStatus::Functional->value
                    || $this->input('hasil_pengujian') !== MaintenanceTestResult::Passed->value
                )
            ) {
                $validator->errors()->add('hasil_akhir_pemeliharaan', 'Jika hasil akhir berfungsi dengan baik, fungsi utama harus Berfungsi dan hasil pengujian harus Lulus.');
            }

            $url = $this->input('link_drive_dokumentasi');
            if (filled($url)) {
                $host = strtolower((string) parse_url($url, PHP_URL_HOST));
                $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

                if ($scheme !== 'https' || ! in_array($host, ['drive.google.com', 'docs.google.com'], true)) {
                    $validator->errors()->add('link_drive_dokumentasi', 'Link dokumentasi harus berupa URL HTTPS Google Drive/Google Docs.');
                }
            }
        });
    }
}
