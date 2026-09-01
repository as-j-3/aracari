<?php

namespace App\Http\Requests\Web\ExchangeRate;

use Illuminate\Foundation\Http\FormRequest;

class StoreExchangeRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'to_currency_id'   => ['required', 'integer', 'exists:currencies,id', 'different:from_currency_id'],
            'rate'             => ['required', 'numeric', 'min:0.0001', 'max:999999.9999'],
            'rate_date'        => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_currency_id.exists'    => 'العملة المصدر غير صالحة.',
            'to_currency_id.exists'      => 'العملة الهدف غير صالحة.',
            'to_currency_id.different'   => 'يجب أن تكون العملة الهدف مختلفة عن العملة المصدر.',
            'rate.min'                   => 'قيمة سعر الصرف يجب أن تكون أكبر من صفر.',
            'rate_date.before_or_equal'  => 'لا يمكن إدخال سعر صرف بتاريخ مستقبلي.',
        ];
    }
}