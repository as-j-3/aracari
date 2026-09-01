<?php

namespace App\Http\Requests\Web\City;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $cityId = $this->route('city')?->id ?? $this->route('id');
        return [
            'country_id' => 'sometimes|exists:countries,id',
            'name' => 'sometimes|string|max:255|unique:cities,name,' . $cityId,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'country_id.exists' => 'الدولة غير موجودة',
            'name.string' => 'اسم المدينة يجب أن يكون نصاً',
            'name.max' => 'اسم المدينة يجب ألا يتجاوز 255 حرفاً',
            'name.unique' => 'هذه المدينة موجودة بالفعل',
            'image.image' => 'يجب أن يكون الملف صورة',
            'image.mimes' => 'يجب أن تكون الصورة من نوع jpg, jpeg, png, webp',
            'image.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',
        ];
    }
}
