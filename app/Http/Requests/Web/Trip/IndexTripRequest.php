<?php

namespace App\Http\Requests\Web\Trip;

use Illuminate\Foundation\Http\FormRequest;

class IndexTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'   => ['nullable', 'string', 'max:100'],
            'status'   => ['nullable', 'string'],
            'city'     => ['nullable'],
            'date'     => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
