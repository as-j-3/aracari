<?php

namespace App\Http\Requests\Provider\Branch;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'phone_number' => ['sometimes', 'string', 'max:20'],
            'country_id'   => ['sometimes', 'integer', 'exists:countries,id'],
            'city_id'      => ['sometimes', 'integer', 'exists:cities,id'],
            'address'      => ['sometimes', 'string', 'max:500'],
            'latitude'     => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'    => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}