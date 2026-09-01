<?php

namespace App\Http\Requests\Web\DigitalPocket;

use Illuminate\Foundation\Http\FormRequest;

class IndexDigitalPocketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'      => ['nullable', 'string', 'max:100'],
            'currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
            'per_page'    => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}