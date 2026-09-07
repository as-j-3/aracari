<?php

namespace App\Http\Requests\Api\Booking;

use Illuminate\Foundation\Http\FormRequest;

class InitiateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'adults'                                    => 'required|integer|min:1|max:10',
            'children'                                  => 'nullable|integer|min:0|max:5',
            'currency_id'                               => 'nullable|integer|exists:currencies,id',
            'departure_date'                            => 'nullable|date',
            'coupon_code'                               => 'nullable|string|max:50|exists:coupons,code',
            'passengers'                                => 'required|array|min:1',
            'passengers.*.name'                         => 'required|string|min:3|max:255',
            'passengers.*.birthday'                     => 'required|date',
            'passengers.*.seat_id'                      => 'nullable|integer|exists:seats,id',
            'passengers.*.gender_id'                    => 'nullable|exists:genders,id',
            'passengers.*.phone_number'                 => 'nullable|string|regex:/^[0-9]+$/|max:20',
            'passengers.*.personal_card_id'             => 'nullable|string|max:50',
            'passengers.*.personal_card_id_photo'       => 'nullable|string|max:255',
            'passengers.*.personal_card_place_of_issue' => 'nullable|string|max:255',
            'passengers.*.personal_card_date_issu'      => 'nullable|date|before_or_equal:today',
            'passengers.*.personal_card_date_expiry'    => 'nullable|date|after:passengers.*.personal_card_date_issu',
            'passengers.*.passport_number'              => 'nullable|string|max:50',
            'passengers.*.passport_photo'               => 'nullable|string|max:255',
            'passengers.*.passport_date_issu'           => 'nullable|date',
            'passengers.*.passport_date_expiry'         => 'nullable|date|after:passengers.*.passport_date_issu',
            'passengers.*.passport_place_of_issue'      => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'adults.required'                        => 'عدد البالغين مطلوب',
            'adults.integer'                         => 'عدد البالغين يجب أن يكون رقماً صحيحاً',
            'adults.min'                             => 'يجب أن يكون هناك بالغ واحد على الأقل',
            'adults.max'                             => 'الحد الأقصى للبالغين هو 10',
            'children.integer'                       => 'عدد الأطفال يجب أن يكون رقماً صحيحاً',
            'children.min'                           => 'عدد الأطفال لا يمكن أن يكون أقل من 0',
            'currency_id.exists'                     => 'العملة المختارة غير موجودة',
            'coupon_code.exists'                     => 'كود الكوبون غير موجود أو غير صالح',
            'passengers.required'                    => 'بيانات المسافرين مطلوبة',
            'passengers.min'                         => 'يجب إدخال بيانات مسافر واحد على الأقل',
            'passengers.*.name.required'             => 'اسم المسافر مطلوب',
            'passengers.*.name.min'                  => 'اسم المسافر يجب أن يكون 3 أحرف على الأقل',
            'passengers.*.birthday.required'         => 'تاريخ ميلاد المسافر مطلوب',
            'passengers.*.birthday.date'             => 'تاريخ الميلاد يجب أن يكون تاريخاً صحيحاً',
            'passengers.*.seat_id.exists'            => 'المقعد المختار للمسافر غير موجود',
            'passengers.*.gender_id.exists'          => 'الجنس المحدد للمسافر غير موجود',
            'passengers.*.phone_number.regex'        => 'رقم جوال المسافر يجب أن يحتوي على أرقام فقط',
            'passengers.*.passport_date_expiry.after'=> 'تاريخ انتهاء الجواز يجب أن يكون بعد تاريخ الإصدار',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $adults = $this->input('adults', 0);
            $children = $this->input('children', 0);
            $totalPassengers = $adults + $children;
            $passengersCount = count($this->input('passengers', []));

            if ($totalPassengers !== $passengersCount) {
                $validator->errors()->add('passengers_count', 'عدد المسافرين يجب أن يساوي مجموع البالغين والأطفال');
            }

            $tripUuid = $this->route('tripUuid');
            if ($tripUuid) {
                $trip = \App\Models\Trip::where('uuid', $tripUuid)->first();

                if (! $trip) {
                    $validator->errors()->add('trip', 'الرحلة غير موجودة');
                    return;
                }

                if ($trip->departure_time <= now()) {
                    $validator->errors()->add('trip', 'الرحلة قد بدأت بالفعل - لا يمكن حجزها');
                    return;
                }

                $bookingDeadline = $trip->departure_time->subHours(2);
                if (now() > $bookingDeadline) {
                    $validator->errors()->add('trip', 'انتهى وقت حجز هذه الرحلة');
                }
            }
        });
    }
}
