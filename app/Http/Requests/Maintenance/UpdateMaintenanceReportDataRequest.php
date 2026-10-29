<?php

namespace App\Http\Requests\Maintenance;

use App\Enums\AssetMaintenanceType;
use App\Enums\MaintenanceFinalResult;
use App\Enums\MaintenanceMainFunctionStatus;
use App\Enums\MaintenanceTestResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceReportDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->hasPermission('pemeliharaan.report.update') ?? false)
            && $this->user()?->role !== 'admin_bidang';
    }

    public function rules(): array
    {
        return [
            'nomor_berita_acara' => ['required', 'string', 'max:100'],
            'tanggal_berita_acara' => ['required', 'date'],
            'lokasi_pelaksanaan' => ['required', 'string', 'max:255'],
            'dasar_pelaksanaan' => ['nullable', 'string', 'max:3000'],
            'jenis_pemeliharaan' => ['required', Rule::enum(AssetMaintenanceType::class)],
            'jenis_pemeliharaan_lainnya' => ['required_if:jenis_pemeliharaan,'.AssetMaintenanceType::Other->value, 'nullable', 'string', 'max:255'],
            'nama_pelaksana' => ['required', 'string', 'max:255'],
            'identitas_pelaksana' => ['nullable', 'string', 'max:255'],
            'komponen_material' => ['nullable', 'string', 'max:3000'],
            'fungsi_utama' => ['required', Rule::enum(MaintenanceMainFunctionStatus::class)],
            'hasil_pengujian' => ['required', Rule::enum(MaintenanceTestResult::class)],
            'hasil_akhir_pemeliharaan' => ['required', Rule::enum(MaintenanceFinalResult::class)],
            'tempat_ttd' => ['required', 'string', 'max:150'],
            'jabatan_penanggung_jawab' => ['required', 'string', 'max:255'],
            'nama_penanggung_jawab' => ['required', 'string', 'max:255'],
            'nip_penanggung_jawab' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'nomor_berita_acara.required' => 'Nomor Berita Acara wajib diisi.',
            'tanggal_berita_acara.required' => 'Tanggal Berita Acara wajib diisi.',
            'lokasi_pelaksanaan.required' => 'Lokasi pelaksanaan wajib diisi.',
            'tempat_ttd.required' => 'Tempat tanda tangan wajib diisi.',
            'jabatan_penanggung_jawab.required' => 'Jabatan penanggung jawab wajib diisi.',
            'nama_penanggung_jawab.required' => 'Nama penanggung jawab wajib diisi.',
            'nip_penanggung_jawab.required' => 'NIP penanggung jawab wajib diisi.',
            'jenis_pemeliharaan.required' => 'Jenis pemeliharaan wajib dipilih.',
            'jenis_pemeliharaan_lainnya.required_if' => 'Keterangan jenis pemeliharaan wajib diisi jika memilih Lainnya.',
            'nama_pelaksana.required' => 'Pelaksana/teknisi wajib diisi.',
            'fungsi_utama.required' => 'Fungsi utama wajib dipilih.',
            'hasil_pengujian.required' => 'Hasil pengujian wajib dipilih.',
            'hasil_akhir_pemeliharaan.required' => 'Hasil akhir pemeliharaan wajib dipilih.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (
                $this->input('hasil_akhir_pemeliharaan') === MaintenanceFinalResult::Good->value
                && (
                    $this->input('fungsi_utama') !== MaintenanceMainFunctionStatus::Functional->value
                    || $this->input('hasil_pengujian') !== MaintenanceTestResult::Passed->value
                )
            ) {
                $validator->errors()->add('hasil_akhir_pemeliharaan', 'Jika hasil akhir berfungsi dengan baik, fungsi utama harus Berfungsi dan hasil pengujian harus Lulus.');
            }
        });
    }
}
