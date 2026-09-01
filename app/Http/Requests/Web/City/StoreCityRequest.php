<?php

namespace App\Http\Requests\Web\City;

use Illuminate\Foundation\Http\FormRequest;

class StoreCityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // الصلاحية في الراوت
    }

    public function rules(): array
    {
        return [
            'country_id' => 'required|exists:countries,id',
            'name' => 'required|string|max:255|unique:cities,name',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'country_id.required' => 'الدولة مطلوبة',
            'country_id.exists' => 'الدولة غير موجودة',
            'name.required' => 'اسم المدينة مطلوب',
            'name.string' => 'اسم المدينة يجب أن يكون نصاً',
            'name.max' => 'اسم المدينة يجب ألا يتجاوز 255 حرفاً',
            'name.unique' => 'هذه المدينة موجودة بالفعل',
            'image.image' => 'يجب أن يكون الملف صورة',
            'image.mimes' => 'يجب أن تكون الصورة من نوع jpg, jpeg, png, webp',
            'image.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',

        ];
    }
}
