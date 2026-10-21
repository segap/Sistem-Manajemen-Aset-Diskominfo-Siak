<?php

namespace App\Http\Requests;

use App\Enums\AssetCondition;
use App\Enums\StockOpnameVerificationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetReportRequest extends FormRequest
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
            'report_type' => ['nullable', Rule::in(['asset', 'stock_opname', 'all'])],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50, 100])],
            'search' => ['nullable', 'string', 'max:150'],
            'kategori_id' => ['nullable', 'integer', 'exists:kategori_aset,id'],
            'kondisi' => ['nullable', Rule::enum(AssetCondition::class)],
            'lifecycle' => ['nullable', Rule::in(['active', 'disposed', 'all'])],
            'tanggal_dari' => ['nullable', 'date'],
            'tanggal_sampai' => ['nullable', 'date', 'after_or_equal:tanggal_dari'],
            'tahun_perolehan_dari' => ['nullable', 'integer', 'digits:4', 'between:1900,'.(date('Y') + 1)],
            'tahun_perolehan_sampai' => ['nullable', 'integer', 'digits:4', 'gte:tahun_perolehan_dari', 'max:'.(date('Y') + 1)],
            'stock_category_id' => ['nullable', 'integer', 'exists:stock_opname_categories,id'],
            'stock_status' => ['nullable', Rule::in(['tersedia', 'menipis', 'habis'])],
            'verification' => ['nullable', Rule::enum(StockOpnameVerificationStatus::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'kategori_id.exists' => 'Kategori aset tidak ditemukan.',
            'kondisi.enum' => 'Kondisi aset tidak valid.',
            'lifecycle.in' => 'Status siklus aset tidak valid.',
            'stock_status.in' => 'Status stok tidak valid.',
            'tanggal_sampai.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
            'tahun_perolehan_sampai.gte' => 'Tahun akhir tidak boleh sebelum tahun awal.',
        ];
    }
}
