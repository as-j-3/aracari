<?php

namespace App\Http\Requests\Web\TaxRule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaxRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'tax_id' => [
                'required',
                'exists:taxes,id',
                Rule::unique('tax_rules')
                    ->where('country_id', $this->country_id)
                    ->where('provider_id', $this->provider_id)
                    ->where('service_type_id', $this->service_type_id)
                    ->where('payment_method_id', $this->payment_method_id)
                    ->where('trip_id', $this->trip_id)
                    ->ignore($this->taxRule),
            ],
            'country_id'        => 'nullable|exists:countries,id',
            'provider_id'       => 'nullable|exists:provider_services,id',
            'service_type_id'   => 'nullable|exists:type_of_services,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'trip_id'           => 'nullable|exists:trips,id',
            'min_amount'        => 'nullable|numeric|min:0',
            'max_amount'        => 'nullable|numeric|gte:min_amount',
            'currency_id'       => 'nullable|exists:currencies,id',
            'is_active'         => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'tax_id.required'           => 'الضريبة مطلوبة',
            'tax_id.exists'             => 'الضريبة غير موجودة',
            'tax_id.unique'             => 'توجد قاعدة ضريبية مطابقة بالفعل لنفس التركيبة',
            'max_amount.gte'            => 'الحد الأعلى يجب أن يكون أكبر من الحد الأدنى',
            'min_amount.min'            => 'الحد الأدنى يجب أن يكون رقماً موجبًا',
            'country_id.exists'         => 'الدولة غير موجودة',
            'provider_id.exists'        => 'مزود الخدمة غير موجود',
            'service_type_id.exists'    => 'نوع الخدمة غير موجود',
            'payment_method_id.exists'  => 'طريقة الدفع غير موجودة',
            'trip_id.exists'            => 'الرحلة غير موجودة',
            'currency_id.exists'        => 'العملة غير موجودة',
        ];
    }
}
