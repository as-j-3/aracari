<?php

namespace App\Http\Requests\Web\Commission;

use Illuminate\Foundation\Http\FormRequest;

class CreateCommissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'commission_type' => 'required|in:fixed,percentage',
            'calculation_level' => 'nullable|in:service,booking',
            'description' => 'nullable|string|max:500',
            'provider_uuid' => 'required|uuid|exists:provider_services,uuid',
            'currency_id' =>   'nullable|exists:currencies,id',
            'value' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    if ($this->commission_type === 'percentage' && $value > 100) {
                        $fail('النسبة المئوية لا يمكن أن تتجاوز 100%');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'commission_type.required' => 'نوع العمولة مطلوب',
            'commission_type.in' => 'نوع العمولة يجب أن يكون ثابت أو نسبة مئوية',
            'calculation_level.in' => 'مستوى الحساب يجب أن يكون خدمة أو حجز',
            'provider_uuid.required' => 'مزود الخدمة مطلوب',
            'provider_uuid.exists' => 'مزود الخدمة المحدد غير موجود',
            'currency_id.exists' => 'العملة المحددة غير موجودة',
            'description.max' => 'الوصف يجب ألا يزيد عن 500 حرف',
            'value.required' => 'قيمة العمولة مطلوبة',
            'value.numeric' => 'قيمة العمولة يجب أن تكون رقمًا',
            'value.min' => 'قيمة العمولة يجب أن تكون صفر أو أكثر',
            'value.max' => 'قيمة العمولة يجب أن لا تزيد عن 100',
        ];
    }
}
