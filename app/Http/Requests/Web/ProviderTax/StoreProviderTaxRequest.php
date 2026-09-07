<?php

namespace App\Http\Requests\Web\ProviderTax;

use Illuminate\Foundation\Http\FormRequest;

class StoreProviderTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'country_tax_id' => [
                'required',
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

    public function messages(): array
    {
        return [
            'country_tax_id.required' => 'حقل ضريبة الدولة مطلوب.',
            'country_tax_id.integer'  => 'حقل ضريبة الدولة يجب أن يكون عدد صحيح.',
            'country_tax_id.exists'   => 'ضريبة الدولة المحددة غير موجودة.',

            'service_id.integer'      => 'حقل الخدمة يجب أن يكون عدد صحيح.',
            'service_id.exists'       => 'الخدمة المحددة غير موجودة.',

            'is_active.boolean'       => 'حقل النشاط يجب أن يكون true أو false.',
        ];
    }
}
