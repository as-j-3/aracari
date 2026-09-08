<?php

namespace App\Http\Requests\Web\ProviderSettlement;

use Illuminate\Foundation\Http\FormRequest;

class StoreSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check()
            && auth()->user()->hasPermission('providers:settle');
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'revenue_method_id' => 'nullable|exists:receive_revenue_methods,id',
            'currency_id' => 'required|exists:currencies,id',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'مبلغ التسوية مطلوب',
            'amount.numeric' => 'مبلغ التسوية يجب أن يكون رقماً',
            'amount.min' => 'مبلغ التسوية يجب أن يكون أكبر من صفر',
            'revenue_method_id.exists' => 'طريقة الإيراد غير موجودة',
            'currency_id.required' => 'العملة مطلوبة',
            'currency_id.exists' => 'العملة غير موجودة',
        ];
    }
}
