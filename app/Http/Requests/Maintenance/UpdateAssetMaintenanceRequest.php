<?php

namespace App\Http\Requests\Maintenance;

use App\Enums\AssetCondition;
use App\Enums\AssetMaintenancePriority;
use App\Enums\AssetMaintenanceType;
use App\Enums\MaintenanceFinalResult;
use App\Enums\MaintenanceMainFunctionStatus;
use App\Enums\MaintenanceTestResult;
use App\Models\AssetMaintenance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAssetMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $maintenance = $this->route('assetMaintenance');

        if (! $user?->hasPermission('pemeliharaan.update')) {
            return false;
        }

        return $user->role !== 'admin_bidang'
            || ($maintenance instanceof AssetMaintenance && $maintenance->pemohon_id === $user->id);
    }

    public function rules(): array
    {
        return [
            'keluhan' => ['required', 'string', 'max:3000'],
            'catatan_pengajuan' => ['nullable', 'string', 'max:3000'],
            'prioritas' => ['nullable', Rule::enum(AssetMaintenancePriority::class)],
            'catatan_verifikasi' => ['nullable', 'string', 'max:3000'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date'],
            'penyedia_servis' => ['nullable', 'string', 'max:255'],
            'jenis_pemeliharaan' => ['nullable', Rule::enum(AssetMaintenanceType::class)],
            'jenis_pemeliharaan_lainnya' => ['required_if:jenis_pemeliharaan,'.AssetMaintenanceType::Other->value, 'nullable', 'string', 'max:255'],
            'nama_pelaksana' => ['nullable', 'string', 'max:255'],
            'identitas_pelaksana' => ['nullable', 'string', 'max:255'],
            'komponen_material' => ['nullable', 'string', 'max:3000'],
            'tindakan_servis' => ['nullable', 'string', 'max:3000'],
            'biaya_servis' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'fungsi_utama' => ['nullable', Rule::enum(MaintenanceMainFunctionStatus::class)],
            'hasil_pengujian' => ['nullable', Rule::enum(MaintenanceTestResult::class)],
            'hasil_akhir_pemeliharaan' => ['nullable', Rule::enum(MaintenanceFinalResult::class)],
            'kondisi_setelah' => ['nullable', Rule::in([
                AssetCondition::Good->value,
                AssetCondition::LightDamage->value,
                AssetCondition::MediumDamage->value,
                AssetCondition::HeavyDamage->value,
            ])],
            'catatan_hasil' => ['nullable', 'string', 'max:3000'],
            'nomor_berita_acara' => ['nullable', 'string', 'max:100'],
            'tanggal_berita_acara' => ['nullable', 'date'],
            'lokasi_pelaksanaan' => ['nullable', 'string', 'max:255'],
            'dasar_pelaksanaan' => ['nullable', 'string', 'max:3000'],
            'tempat_ttd' => ['nullable', 'string', 'max:150'],
            'jabatan_penanggung_jawab' => ['nullable', 'string', 'max:255'],
            'nama_penanggung_jawab' => ['nullable', 'string', 'max:255'],
            'nip_penanggung_jawab' => ['nullable', 'string', 'max:100'],
            'foto_sebelum' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'foto_setelah' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'invoice' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'dokumentasi_servis' => ['nullable', 'array', 'max:5'],
            'dokumentasi_servis.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'video_dokumentasi' => ['nullable', 'file', 'mimes:mp4,mov,webm', 'max:20480'],
            'link_drive_dokumentasi' => ['nullable', 'url', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'foto_sebelum.max' => 'gambar/file lebih dari 2mb',
            'foto_setelah.max' => 'gambar/file lebih dari 2mb',
            'invoice.max' => 'gambar/file lebih dari 2mb',
            'dokumentasi_servis.*.max' => 'gambar/file lebih dari 2mb',
            'video_dokumentasi.max' => 'video lebih dari 20mb',
            'jenis_pemeliharaan_lainnya.required_if' => 'Keterangan jenis pemeliharaan wajib diisi jika memilih Lainnya.',
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
