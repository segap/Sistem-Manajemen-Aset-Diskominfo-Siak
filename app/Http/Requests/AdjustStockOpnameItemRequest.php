<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AdjustStockOpnameItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stok_fisik' => ['required', 'integer', 'min:0', 'max:1000000'],
            'catatan' => ['nullable', 'string', 'max:3000'],
            'jenis' => ['required', Rule::in(['stock_opname_fisik'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $item = $this->route('stockOpnameItem');
            $systemStock = (int) ($item?->stok ?? 0);
            $physicalStock = $this->integer('stok_fisik');

            if ($physicalStock !== $systemStock && blank($this->input('catatan'))) {
                $validator->errors()->add('catatan', 'Catatan wajib diisi jika stok fisik berbeda dengan stok sistem.');
            }
        });
    }
}
