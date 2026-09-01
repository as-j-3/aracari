<?php

namespace App\Http\Requests\Web\Country;

use Illuminate\Foundation\Http\FormRequest;

class IndexCountryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'   => ['nullable', 'string', 'max:100'],
            'is_active'=> ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}