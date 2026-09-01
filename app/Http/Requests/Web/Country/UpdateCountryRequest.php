<?php

namespace App\Http\Requests\Web\Country;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCountryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $countryId = $this->route('country')?->id ?? $this->route('id');
        return [
            'name' => 'sometimes|string|max:255|unique:countries,name,' . $countryId,
            'iso_code' => 'sometimes|string|size:2|unique:countries,iso_code,' . $countryId,
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'اسم الدولة يجب أن يكون نصاً',
            'name.max' => 'اسم الدولة يجب ألا يتجاوز 255 حرفاً',
            'name.unique' => 'هذه الدولة موجودة بالفعل',
            'iso_code.string' => 'كود ISO يجب أن يكون نصاً',
            'iso_code.size' => 'كود ISO يجب أن يكون حرفين (مثال: YE)',
            'iso_code.unique' => 'هذا الكود موجود بالفعل',
        ];
    }
}
