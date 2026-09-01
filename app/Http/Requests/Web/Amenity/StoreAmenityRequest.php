<?php

namespace App\Http\Requests\Web\Amenity;

use Illuminate\Foundation\Http\FormRequest;

class StoreAmenityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:amenities,name',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الخدمة مطلوب',
            'name.string' => 'اسم الخدمة يجب أن يكون نصاً',
            'name.max' => 'اسم الخدمة يجب ألا يتجاوز 100 حرفاً',
            'name.unique' => 'هذه الخدمة موجودة بالفعل',
        ];
    }
}
