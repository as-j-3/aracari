<?php

namespace App\Http\Requests\Web\Passenger;

use Illuminate\Foundation\Http\FormRequest;

class IndexPassengerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'    => ['nullable', 'string', 'max:100'],
            'gender_id' => ['nullable', 'integer', 'exists:genders,id'],
            'min_age'   => ['nullable', 'integer', 'min:0', 'max:120'],
            'max_age'   => ['nullable', 'integer', 'min:0', 'max:120', 'gte:min_age'],
            'per_page'  => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'max_age.gte' => 'الحد الأقصى للعمر يجب أن يكون أكبر من الحد الأدنى.',
        ];
    }
}