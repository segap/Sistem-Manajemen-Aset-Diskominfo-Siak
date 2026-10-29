<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\ProcurementProposal;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProcurementProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $proposal = $this->route('procurementProposal');

        if (! $user || ! $proposal instanceof ProcurementProposal) {
            return false;
        }

        return $user->role === UserRole::SuperAdmin->value
            || ($user->role === UserRole::AdminBidang->value && $proposal->pengusul_id === $user->id);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $proposal = $this->route('procurementProposal');

        if ($proposal instanceof ProcurementProposal && $proposal->details()->exists()) {
            return [
                'tahun_anggaran' => ['required', 'integer', 'between:2000,2100'],
                'jenis_belanja' => ['required', Rule::in(array_keys(ProcurementProposal::spendingTypes()))],
                'tanggal_pengajuan' => ['required', 'date'],
                'keterangan_umum' => ['nullable', 'string', 'max:5000'],
                'details' => ['required', 'array', 'min:1', 'max:100'],
                'details.*.id' => ['nullable', 'integer', 'distinct'],
                'details.*.nama_barang' => ['required', 'string', 'max:150'],
                'details.*.spesifikasi' => ['required', 'string', 'max:5000'],
                'details.*.jumlah' => ['required', 'integer', 'min:1', 'max:1000000'],
                'details.*.estimasi_harga_satuan' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
                'details.*.link_produk' => ['nullable', 'url:http,https', 'max:2048'],
                'details.*.keterangan' => ['nullable', 'string', 'max:5000'],
                'details.*.penanggung_jawab_id' => ['nullable', 'integer', 'exists:users,id'],
            ];
        }

        return [
            'jenis_belanja' => ['required', Rule::in(array_keys(ProcurementProposal::spendingTypes()))],
            'nama_barang' => ['required', 'string', 'max:150'],
            'spesifikasi' => ['required', 'string', 'max:5000'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:1000000'],
            'estimasi_harga_satuan' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'alasan_kebutuhan' => ['required', 'string', 'max:5000'],
            'pengguna_barang_id' => ['required', 'integer', 'exists:users,id'],
            'penanggung_jawab_id' => ['required', 'integer', 'exists:users,id'],
            'url_referensi_gambar' => ['nullable', 'url:http,https', 'max:2048'],
            'url_ekatalog' => ['nullable', 'url:http,https', 'max:2048'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $proposal = $this->route('procurementProposal');

        if ($proposal instanceof ProcurementProposal && $proposal->details()->exists()) {
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

            return;
        }

        $this->merge(['nama_barang' => trim((string) $this->input('nama_barang'))]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'jenis_belanja.required' => 'Jenis belanja wajib dipilih.',
            'jenis_belanja.in' => 'Jenis belanja tidak valid.',
            'details.required' => 'Tambahkan minimal satu barang.',
            'details.min' => 'Tambahkan minimal satu barang.',
            'details.*.nama_barang.required' => 'Nama barang wajib diisi.',
            'details.*.spesifikasi.required' => 'Spesifikasi barang wajib diisi.',
            'details.*.jumlah.required' => 'Jumlah barang wajib diisi.',
            'details.*.jumlah.min' => 'Jumlah barang minimal 1.',
            'details.*.estimasi_harga_satuan.required' => 'Harga satuan wajib diisi.',
            'nama_barang.required' => 'Nama barang wajib diisi.',
            'spesifikasi.required' => 'Spesifikasi barang wajib diisi.',
            'jumlah.required' => 'Jumlah barang wajib diisi.',
            'jumlah.min' => 'Jumlah barang minimal 1.',
            'estimasi_harga_satuan.required' => 'Estimasi harga satuan wajib diisi.',
            'alasan_kebutuhan.required' => 'Justifikasi teknis wajib diisi.',
            'pengguna_barang_id.exists' => 'Pengguna barang tidak ditemukan.',
            'penanggung_jawab_id.exists' => 'Penanggung jawab tidak ditemukan.',
            'photo.image' => 'Berkas foto harus berupa gambar.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
        ];
    }
}
