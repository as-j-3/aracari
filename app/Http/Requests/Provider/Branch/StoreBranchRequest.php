<?php

namespace App\Http\Requests\Provider\Branch;

use Illuminate\Foundation\Http\FormRequest;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'phone_number' => ['required', 'string', 'max:20'],
            'country_id'   => ['required', 'integer', 'exists:countries,id'],
            'city_id'      => ['required', 'integer', 'exists:cities,id'],
            'address'      => ['required', 'string', 'max:500'],
            'latitude'     => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'    => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.required' => 'رقم الهاتف مطلوب.',
            'country_id.required'   => 'الدولة مطلوبة.',
            'city_id.required'      => 'المدينة مطلوبة.',
            'address.required'      => 'العنوان مطلوب.',
        ];
    }
}