<?php

namespace App\Http\Requests\Web\Currency;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:3', \Illuminate\Validation\Rule::unique('currencies')->ignore($this->currency->id)],
            'name' => ['required', 'string', 'max:100', \Illuminate\Validation\Rule::unique('currencies')->ignore($this->currency->id)],
            'symbol' => 'required|string|max:10',
            'price_to_yr' => 'required|numeric|min:0.0001|max:999999',
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
            'price_to_yr.required' => 'سعر الصرف مقابل الريال اليمني مطلوب',
        ];
    }
}