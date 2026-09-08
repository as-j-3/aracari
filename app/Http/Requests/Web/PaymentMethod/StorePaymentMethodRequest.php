<?php

namespace App\Http\Requests\Web\PaymentMethod;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'method_name' => 'required|string|max:100|unique:payment_methods,method_name',
            "provider_id" => 'nullable|exists:provider_services,id',
            "country_id" => 'nullable|exists:countries,id',
            "currency_id" => 'nullable|exists:currencies,id',
        ];
    }

    public function messages(): array
    {
        return [
            'method_name.required' => 'اسم وسيلة الدفع مطلوب',
            'method_name.string' => 'اسم الوسيلة يجب أن يكون نصاً',
            'method_name.max' => 'اسم الوسيلة يجب ألا يتجاوز 100 حرفاً',
            'method_name.unique' => 'هذه الوسيلة موجودة بالفعل',
            "provider_id.exists" => "مزود الخدمة غير موجود",
            "country_id.exists" => "الدولة غير موجودة",
            "currency_id.exists" => "العملة غير موجودة",
        ];
    }
}
