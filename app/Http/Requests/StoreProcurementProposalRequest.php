<?php

namespace App\Http\Requests;

use App\Models\ProcurementProposal;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProcurementProposalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('usulan_pengadaan.create') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tahun_anggaran' => ['required', 'integer', 'between:2000,2100'],
            'jenis_belanja' => ['required', Rule::in(array_keys(ProcurementProposal::spendingTypes()))],
            'tanggal_pengajuan' => ['required', 'date'],
            'keterangan_umum' => ['nullable', 'string', 'max:5000'],
            'details' => ['required', 'array', 'min:1', 'max:100'],
            'details.*.nama_barang' => ['required', 'string', 'max:150'],
            'details.*.spesifikasi' => ['required', 'string', 'max:5000'],
            'details.*.jumlah' => ['required', 'integer', 'min:1', 'max:1000000'],
            'details.*.estimasi_harga_satuan' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'details.*.link_produk' => ['nullable', 'url:http,https', 'max:2048'],
            'details.*.keterangan' => ['nullable', 'string', 'max:5000'],
            'details.*.penanggung_jawab_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'keterangan_umum' => trim((string) $this->input('keterangan_umum')) ?: null,
            'details' => collect($this->input('details', []))
                ->map(fn ($detail): array => [
                    ...(is_array($detail) ? $detail : []),
                    'nama_barang' => trim((string) (is_array($detail) ? ($detail['nama_barang'] ?? '') : '')),
                    'spesifikasi' => trim((string) (is_array($detail) ? ($detail['spesifikasi'] ?? '') : '')),
                    'keterangan' => trim((string) (is_array($detail) ? ($detail['keterangan'] ?? '') : '')) ?: null,
                ])->all(),
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'tahun_anggaran.required' => 'Tahun anggaran wajib diisi.',
            'jenis_belanja.required' => 'Jenis belanja wajib dipilih.',
            'jenis_belanja.in' => 'Jenis belanja tidak valid.',
            'tanggal_pengajuan.required' => 'Tanggal pengajuan wajib diisi.',
            'details.required' => 'Tambahkan minimal satu barang.',
            'details.min' => 'Tambahkan minimal satu barang.',
            'details.*.nama_barang.required' => 'Nama barang wajib diisi.',
            'details.*.spesifikasi.required' => 'Spesifikasi barang wajib diisi.',
            'details.*.jumlah.required' => 'Jumlah barang wajib diisi.',
            'details.*.jumlah.min' => 'Jumlah barang minimal 1.',
            'details.*.estimasi_harga_satuan.required' => 'Estimasi harga satuan wajib diisi.',
        ];
    }
}
