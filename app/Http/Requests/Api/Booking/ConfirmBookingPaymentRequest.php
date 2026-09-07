<?php

namespace App\Http\Requests\Api\Booking;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmBookingPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'payment_method_id' => 'required|integer|exists:payment_methods,id',
            'stripe_token'      => 'nullable|string|required_if:payment_method_id,2',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method_id.required' => 'وسيلة الدفع مطلوبة',
            'payment_method_id.exists'   => 'وسيلة الدفع المختارة غير موجودة',
            'stripe_token.required_if'   => 'رمز بطاقة الدفع (Stripe Token) مطلوب عند الدفع بالبطاقة',
        ];
    }
}
