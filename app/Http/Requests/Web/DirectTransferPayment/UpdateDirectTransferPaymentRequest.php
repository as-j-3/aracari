<?php

namespace App\Http\Requests\Web\DirectTransferPayment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDirectTransferPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method_id' => 'sometimes|exists:payment_methods,id',
            'name' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|max:50',
            'bank_name' => 'sometimes|string|max:255',
            'bank_account_number' => 'sometimes|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method_id.exists' => 'طريقة الدفع غير موجودة',
            'name.string' => 'الاسم يجب أن يكون نصاً',
            'name.max' => 'الاسم يجب ألا يتجاوز 255 حرفاً',
            'phone_number.string' => 'رقم الهاتف يجب أن يكون نصاً',
            'phone_number.max' => 'رقم الهاتف يجب ألا يتجاوز 50 حرفاً',
            'bank_name.string' => 'اسم البنك يجب أن يكون نصاً',
            'bank_name.max' => 'اسم البنك يجب ألا يتجاوز 255 حرفاً',
            'bank_account_number.string' => 'رقم الحساب البنكي يجب أن يكون نصاً',
            'bank_account_number.max' => 'رقم الحساب البنكي يجب ألا يتجاوز 100 حرفاً',
        ];
    }
}