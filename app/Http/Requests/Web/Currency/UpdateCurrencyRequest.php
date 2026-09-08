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
        $currencyId = $this->route('currency');
        $currencyId = $currencyId instanceof \App\Models\Currency
            ? $currencyId->id
            : (int) $currencyId;

        return [
            'code' => ['required', 'string', 'size:3', \Illuminate\Validation\Rule::unique('currencies')->ignore($currencyId)],
            'name' => ['required', 'string', 'max:100', \Illuminate\Validation\Rule::unique('currencies')->ignore($currencyId)],
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