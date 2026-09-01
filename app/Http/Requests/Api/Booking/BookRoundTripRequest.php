<?php

namespace App\Http\Requests\Api\Booking;

use Illuminate\Foundation\Http\FormRequest;

class BookRoundTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'outbound_trip_uuid' => 'required|exists:trips,uuid',
            'return_trip_uuid' => 'required|exists:trips,uuid|different:outbound_trip_uuid',
            'adults' => 'required|integer|min:1|max:10',
            'children' => 'nullable|integer|min:0|max:5',
            'currency_id' => 'required|integer|exists:currencies,id',
            'passengers' => 'required|array|min:1',
            'passengers.*.name' => 'required|string|min:3|max:255',
            'passengers.*.birthday' => 'required|date',
            'passengers.*.gender_id' => 'nullable|exists:genders,id',
            'passengers.*.phone_number' => 'nullable|string|regex:/^[0-9]+$/|max:20',
            'passengers.*.passport_number' => 'nullable|string|max:50',
            'passengers.*.passport_date_issu' => 'nullable|date',
            'passengers.*.passport_date_expiry' => 'nullable|date|after:passengers.*.passport_date_issu',
            'passengers.*.passport_photo' => 'nullable|string|max:255',
            'passengers.*.passport_place_of_issue' => 'nullable|string|max:255',
            'passengers.*.personal_card_id' => 'nullable|string|max:50',
            'passengers.*.personal_card_id_photo' => 'nullable|string|max:255',
            'passengers.*.personal_card_place_of_issue' => 'nullable|string|max:255',
            'passengers.*.personal_card_date_issu' => 'nullable|date|before_or_equal:today',
            'passengers.*.personal_card_date_expiry' => 'nullable|date|after:passengers.*.personal_card_date_issu',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'outbound_coupon_code' => 'nullable|string|max:50',
            'return_coupon_code' => 'nullable|string|max:50',
            'outbound_travel_date' => 'nullable|date|after_or_equal:today',
            'return_travel_date' => 'nullable|date|after_or_equal:outbound_travel_date',
        ];
    }

    public function messages(): array
    {
        return [
            'outbound_trip_uuid.required' => 'رحلة الذهاب مطلوبة',
            'outbound_trip_uuid.exists' => 'رحلة الذهاب غير موجودة',
            'return_trip_uuid.required' => 'رحلة العودة مطلوبة',
            'currency_id.required' => 'العملة مطلوبة',
            'currency_id.exists' => 'العملة المختارة غير موجودة',
            'return_trip_uuid.exists' => 'رحلة العودة غير موجودة',
            'return_trip_uuid.different' => 'يجب أن تكون رحلة العودة مختلفة عن رحلة الذهاب',
            'adults.required' => 'عدد البالغين مطلوب',
            'adults.min' => 'عدد البالغين يجب أن يكون على الأقل 1',
            'passengers.required' => 'بيانات الركاب مطلوبة',
            'passengers.*.name.required' => 'اسم المسافر مطلوب',
            'passengers.*.birthday.required' => 'تاريخ ميلاد المسافر مطلوب',
            'payment_method_id.required' => 'طريقة الدفع مطلوبة',
            'payment_method_id.exists' => 'طريقة الدفع غير موجودة',
        ];
    }

    public function withValidator($validator)
    {
    $validator->after(function ($validator) {
        
        $outboundTrip = \App\Models\Trip::where('uuid', $this->outbound_trip_uuid)->first();
        $returnTrip = \App\Models\Trip::where('uuid', $this->return_trip_uuid)->first();
        
        if ($outboundTrip && $returnTrip) {
            if ($outboundTrip->destination_city != $returnTrip->departure_city) {
                $validator->errors()->add(
                    'return_trip_uuid',
                    'يجب أن تكون رحلة العودة من نفس مدينة وصول رحلة الذهاب'
                );
            }
            
            if ($outboundTrip->departure_city != $returnTrip->destination_city) {
                $validator->errors()->add(
                    'return_trip_uuid',
                    'يجب أن تكون رحلة العودة إلى نفس مدينة انطلاق رحلة الذهاب'
                );
            }
        }

        $adults = $this->input('adults', 0);
        $children = $this->input('children', 0);
        $totalPassengers = $adults + $children;
        $passengersCount = count($this->input('passengers', []));

        if ($totalPassengers !== $passengersCount) {
            $validator->errors()->add('passengers_count', 'عدد المسافرين يجب أن يساوي مجموع البالغين والأطفال');
        }
    });
    }
}