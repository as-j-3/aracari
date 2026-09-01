<?php

namespace App\Http\Requests\Web\Trip;

use Illuminate\Foundation\Http\FormRequest;

class StoreTripLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'level_name' => 'required|string|max:100|unique:level_trips,level_name',
        ];
    }

    public function messages(): array
    {
        return [
            'level_name.required' => 'اسم مستوى الرحلة مطلوب',
            'level_name.string' => 'اسم المستوى يجب أن يكون نصاً',
            'level_name.max' => 'اسم المستوى يجب ألا يتجاوز 100 حرفاً',
            'level_name.unique' => 'هذا المستوى موجود بالفعل',
        ];
    }
}
