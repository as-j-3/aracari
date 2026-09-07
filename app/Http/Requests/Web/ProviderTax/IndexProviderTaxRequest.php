<?php

namespace App\Http\Requests\Web\ProviderTax;

use Illuminate\Foundation\Http\FormRequest;

class IndexProviderTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],
            'country_tax_id' => [
                'nullable',
                'integer',
                'exists:country_taxes,id',
            ],
            'service_id' => [
                'nullable',
                'integer',
                'exists:services,id',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}
