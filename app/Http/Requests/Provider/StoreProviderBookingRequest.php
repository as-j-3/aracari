<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class StoreProviderBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trip_uuid'             => 'required|string|exists:trips,uuid',
            'currency_id'           => 'required|integer|exists:currencies,id',
            'adults'                => 'required|integer|min:1',
            'children'              => 'required|integer|min:0',
            'payment_method_id'     => 'required|exists:payment_methods,id',
            'passenger_details'     => 'required|array',
            'coupon_code'           => 'nullable|string',
            'outbound_travel_date'  => 'nullable|date|after_or_equal:today',
            'return_travel_date'    => 'nullable|date|after_or_equal:outbound_travel_date',
        ];
    }

    public function messages(): array
    {
        return [
            'trip_uuid.required'              => 'الرحلة مطلوبة',
            'trip_uuid.exists'                => 'الرحلة غير موجودة',
            'currency_id.required'            => 'العملة مطلوبة',
            'currency_id.exists'              => 'العملة غير موجودة',
            'adults.required'                 => 'عدد البالغين مطلوب',
            'adults.integer'                  => 'عدد البالغين يجب أن يكون رقماً صحيحاً',
            'adults.min'                      => 'يجب أن يكون هناك بالغ واحد على الأقل',
            'children.required'               => 'عدد الأطفال مطلوب',
            'children.integer'                => 'عدد الأطفال يجب أن يكون رقماً صحيحاً',
            'children.min'                    => 'عدد الأطفال لا يمكن أن يكون سالباً',
            'payment_method_id.required'      => 'طريقة الدفع مطلوبة',
            'payment_method_id.exists'        => 'طريقة الدفع غير موجودة',
            'passenger_details.required'      => 'تفاصيل المسافرين مطلوبة',
            'passenger_details.array'         => 'تفاصيل المسافرين يجب أن تكون مصفوفة',
            'coupon_code.string'              => 'كود الكوبون يجب أن يكون نصاً',
            'outbound_travel_date.date'       => 'تاريخ السفر غير صالح',
            'outbound_travel_date.after_or_equal' => 'تاريخ السفر يجب أن يكون اليوم أو بعده',
            'return_travel_date.date'         => 'تاريخ العودة غير صالح',
            'return_travel_date.after_or_equal'=> 'تاريخ العودة يجب أن يكون بعد تاريخ السفر',
        ];
    }
}