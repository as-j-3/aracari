<?php

namespace App\Http\Requests\Api\Payment;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserDirectPaymentInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'booking_uuid' => [
                'required',
                'uuid',
                'exists:bookings,uuid',
            ],

            'direct_transfer_payment_id' => [
                'required',
                'integer',
                'exists:direct_transfer_payments,id',
            ],

            'receipt_number' => [
                'nullable',
                'string',
                'max:255',
            ],

            'sender_name' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_uuid.required' => 'معرف الحجز مطلوب',

            'booking_uuid.uuid' => 'معرف الحجز غير صالح',

            'booking_uuid.exists' => 'الحجز غير موجود',

            'direct_transfer_payment_id.required' => 'طريقة التحويل المباشر مطلوبة',

            'direct_transfer_payment_id.exists' => 'طريقة التحويل المباشر غير موجودة',

            'receipt_number.string' => 'رقم الإيصال يجب أن يكون نصاً',

            'receipt_number.max' => 'رقم الإيصال طويل جداً',

            'sender_name.required' => 'اسم المرسل مطلوب',

            'sender_name.string' => 'اسم المرسل يجب أن يكون نصاً',

            'sender_name.max' => 'اسم المرسل طويل جداً',
        ];
    }
}
