<?php

namespace App\Http\Requests\Web\Vehicle;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_name' => 'required|string|max:100|unique:vehicle_types,type_name',
        ];
    }

    public function messages(): array
    {
        return [
            'type_name.required' => 'اسم النوع مطلوب',
            'type_name.string' => 'اسم النوع يجب أن يكون نصاً',
            'type_name.max' => 'اسم النوع يجب ألا يتجاوز 100 حرفاً',
            'type_name.unique' => 'هذا النوع موجود بالفعل',
        ];
    }
}
