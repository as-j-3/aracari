<?php

namespace App\Http\Requests\Web\Currency;

use Illuminate\Foundation\Http\FormRequest;

class StoreCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|size:3|unique:currencies,code',
            'name' => 'required|string|max:100|unique:currencies,name',
            'symbol' => 'required|string|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'كود العملة مطلوب',
            'code.size' => 'كود العملة يجب أن يكون 3 أحرف',
            'code.unique' => 'كود العملة موجود مسبقاً',
            'name.required' => 'اسم العملة مطلوب',
            'name.unique' => 'اسم العملة موجود مسبقاً',
            'symbol.required' => 'رمز العملة مطلوب',

        ];
    }
}