<?php

namespace App\Http\Requests\Web\CountryTax;

use Illuminate\Foundation\Http\FormRequest;

class IndexCountryTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'tax_id' => ['nullable', 'integer', 'exists:taxes,id'],
            'type' => ['nullable', 'in:percentage,fixed'],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.string' => 'حقل البحث يجب أن يكون نصًا.',
            'search.max'    => 'حقل البحث يجب ألا يتجاوز 255 حرفًا.',

            'country_id.integer' => 'حقل الدولة يجب أن يكون عددًا صحيحًا.',
            'country_id.exists'  => 'الدولة المحددة غير موجودة.',

            'tax_id.integer' => 'حقل الضريبة يجب أن يكون عددًا صحيحًا.',
            'tax_id.exists'  => 'الضريبة المحددة غير موجودة.',

            'type.in' => 'يجب أن يكون النوع: percentage أو fixed.',

            'is_active.boolean' => 'حقل النشاط يجب أن يكون true أو false.',

            'per_page.integer' => 'حقل لكل صفحة يجب أن يكون عددًا صحيحًا.',
            'per_page.min'     => 'حقل لكل صفحة يجب أن يكون 1 على الأقل.',
            'per_page.max'     => 'حقل لكل صفحة يجب ألا يتجاوز 100.',
        ];
    }
}