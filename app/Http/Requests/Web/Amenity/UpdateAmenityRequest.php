<?php

namespace App\Http\Requests\Web\Amenity;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAmenityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $amenityId = $this->route('amenity')?->id ?? $this->route('id');
        return [
            'name' => 'sometimes|string|max:100|unique:amenities,name,' . $amenityId,
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'اسم الخدمة يجب أن يكون نصاً',
            'name.max' => 'اسم الخدمة يجب ألا يتجاوز 100 حرفاً',
            'name.unique' => 'هذه الخدمة موجودة بالفعل',
        ];
    }
}
