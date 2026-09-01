<?php

namespace App\Http\Requests\Web\Vehicle;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vehicleTypeId = $this->route('vehicle_type')?->id ?? $this->route('id');
        return [
            'type_name' => 'sometimes|string|max:100|unique:vehicle_types,type_name,' . $vehicleTypeId,
        ];
    }

    public function messages(): array
    {
        return [
            'type_name.string' => 'اسم النوع يجب أن يكون نصاً',
            'type_name.max' => 'اسم النوع يجب ألا يتجاوز 100 حرفاً',
            'type_name.unique' => 'هذا النوع موجود بالفعل في سجلاتنا',
        ];
    }
}
