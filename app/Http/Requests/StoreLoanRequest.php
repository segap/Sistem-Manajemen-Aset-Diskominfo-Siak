<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.group_key' => ['required', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'tanggal_pinjam' => ['required', 'date', 'after_or_equal:today'],
            'tanggal_kembali' => ['required', 'date', 'after_or_equal:tanggal_pinjam'],
            'alasan' => ['required', 'string', 'max:2000'],
            'surat_peminjaman' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'items.required' => 'Minimal satu barang yang akan dipinjam wajib dipilih.',
            'items.array' => 'Daftar barang tidak valid.',
            'items.min' => 'Minimal satu barang yang akan dipinjam wajib dipilih.',
            'items.*.group_key.required' => 'Barang yang akan dipinjam wajib dipilih.',
            'items.*.quantity.required' => 'Jumlah barang wajib diisi.',
            'items.*.quantity.integer' => 'Jumlah barang harus berupa bilangan bulat.',
            'items.*.quantity.min' => 'Jumlah barang minimal 1 unit.',
            'items.*.quantity.max' => 'Jumlah barang terlalu besar.',
            'tanggal_pinjam.after_or_equal' => 'Tanggal peminjaman tidak boleh sebelum hari ini.',
            'tanggal_kembali.after_or_equal' => 'Tanggal pengembalian tidak boleh sebelum tanggal peminjaman.',
            'alasan.required' => 'Alasan peminjaman wajib diisi.',
            'surat_peminjaman.file' => 'Surat peminjaman harus berupa file.',
            'surat_peminjaman.mimes' => 'Surat peminjaman harus berformat PDF, JPG, JPEG, atau PNG.',
            'surat_peminjaman.max' => 'Ukuran surat peminjaman maksimal 5 MB.',
        ];
    }
}
