<?php

namespace App\Http\Requests\Web\PaymentMethod;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('payment_method') ?? $this->route('id');
        return [
            'method_name' => 'required|string|max:100|unique:payment_methods,method_name,' . $id,
        ];
    }

    public function messages(): array
    {
        return [
            'method_name.required' => 'اسم وسيلة الدفع مطلوب',
            'method_name.string' => 'اسم الوسيلة يجب أن يكون نصاً',
            'method_name.max' => 'اسم الوسيلة يجب ألا يتجاوز 100 حرفاً',
            'method_name.unique' => 'اسم الوسيلة مستخدم بالفعل',
        ];
    }
}
