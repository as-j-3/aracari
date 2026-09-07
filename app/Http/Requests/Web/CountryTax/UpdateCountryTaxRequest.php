<?php

namespace App\Http\Requests\Web\CountryTax;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCountryTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tax_id' => [
                'sometimes',
                'integer',
                'exists:taxes,id',
            ],

            'country_id' => [
                'sometimes',
                'integer',
                'exists:countries,id',
            ],

            'type' => [
                'sometimes',
                'in:percentage,fixed',
            ],

            'rate' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tax_id.required'        => 'حقل الضريبة مطلوب.',
            'tax_id.integer'         => 'حقل الضريبة يجب أن يكون عدد صحيح.',
            'tax_id.exists'          => 'الضريبة المحددة غير موجودة.',

            'country_id.required'    => 'حقل الدولة مطلوب.',
            'country_id.integer'     => 'حقل الدولة يجب أن يكون عدد صحيح.',
            'country_id.exists'      => 'الدولة المحددة غير موجودة.',

            'type.required'        => 'حقل النوع مطلوب.',
            'type.in'              => 'يجب أن يكون النوع: percentage أو fixed.',

            'rate.required_if'     => 'حقل النسبة مطلوب عندما يكون النوع percentage.',
            'rate.numeric'         => 'حقل النسبة يجب أن يكون رقم.',
            'rate.min'             => 'حقل النسبة يجب أن يكون 0 على الأقل.',

            'amount.required_if'   => 'حقل المبلغ مطلوب عندما يكون النوع fixed.',
            'amount.numeric'       => 'حقل المبلغ يجب أن يكون رقم.',
            'amount.min'           => 'حقل المبلغ يجب أن يكون 0 على الأقل.',

            'is_active.required'   => 'حقل النشاط مطلوب.',
            'is_active.boolean'    => 'حقل النشاط يجب أن يكون true أو false.',
        ];
    }
}