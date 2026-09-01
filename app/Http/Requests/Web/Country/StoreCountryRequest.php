<?php

namespace App\Http\Requests\Web\Country;

use Illuminate\Foundation\Http\FormRequest;

class StoreCountryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // الصلاحية في الراوت
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:countries,name',
            'iso_code' => 'required|string|size:2|unique:countries,iso_code',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الدولة مطلوب',
            'name.string' => 'اسم الدولة يجب أن يكون نصاً',
            'name.max' => 'اسم الدولة يجب ألا يتجاوز 255 حرفاً',
            'name.unique' => 'هذه الدولة موجودة بالفعل',
            'iso_code.required' => 'كود الدولة ISO مطلوب',
            'iso_code.string' => 'كود ISO يجب أن يكون نصاً',
            'iso_code.size' => 'كود ISO يجب أن يكون حرفين (مثال: YE)',
            'iso_code.unique' => 'هذا الكود موجود بالفعل',
        ];
    }
}
