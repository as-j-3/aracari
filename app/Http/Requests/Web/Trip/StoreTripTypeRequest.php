<?php

namespace App\Http\Requests\Web\Trip;

use Illuminate\Foundation\Http\FormRequest;

class StoreTripTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_name' => 'required|string|max:100|unique:type_of_trips,type_name',
        ];
    }

    public function messages(): array
    {
        return [
            'type_name.required' => 'اسم نوع الرحلة مطلوب',
            'type_name.string' => 'اسم النوع يجب أن يكون نصاً',
            'type_name.max' => 'اسم النوع يجب ألا يتجاوز 100 حرفاً',
            'type_name.unique' => 'هذا النوع موجود بالفعل',
        ];
    }
}
