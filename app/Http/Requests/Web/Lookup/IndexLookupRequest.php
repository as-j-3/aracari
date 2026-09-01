<?php

namespace App\Http\Requests\Web\Lookup;

use Illuminate\Foundation\Http\FormRequest;

class IndexLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'   => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}