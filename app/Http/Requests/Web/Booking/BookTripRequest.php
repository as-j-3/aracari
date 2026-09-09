<?php

namespace App\Http\Requests\Web\Booking;

use Illuminate\Foundation\Http\FormRequest;

class BookTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'adults'    => 'required|integer|min:1|max:10',
            'children'  => 'nullable|integer|min:0|max:5',
            'currency_id' => 'required|integer|exists:currencies,id',
            'payment_method_id' => 'nullable|integer|exists:payment_methods,id',
            'stripe_token' => 'nullable|string|required_if:payment_method_id,2',
            'coupon_code' => 'nullable|string|max:50',
            'passengers' => 'required|array|min:1',
            'passengers.*.name' => 'required|string|min:3|max:255',
            'passengers.*.birthday'  => 'required|date',
            'passengers.*.passport_number' => 'nullable|string|max:50',
            'passengers.*.passport_date_issu' => 'nullable|date',
            'passengers.*.passport_date_expiry' => 'nullable|date|after:passengers.*.passport_date_issu',
            'passengers.*.gender_id' => 'nullable|exists:genders,id',
            'passengers.*.phone_number' => 'nullable|string|max:20',
            'passengers.*.passport_country_place_of_issue' => 'nullable|integer|exists:countries,id',
            'passengers.*.passport_city_place_of_issue' => 'nullable|integer|exists:cities,id',
            //'passengers.*.seat_id' => 'required|integer|exists:seats,id',
            'passengers.*.personal_card_id' => 'nullable|string|max:50',
            'passengers.*.personal_card_id_photo' => 'nullable|image|max:2048',
            'passengers.*.passport_photo' => 'nullable|image|max:2048',
            'passengers.*.personal_country_place_of_issue' => 'nullable|integer|exists:countries,id',
            'passengers.*.personal_city_place_of_issue' => 'nullable|integer|exists:cities,id',
            'passengers.*.personal_card_date_issu' => 'nullable|date|before_or_equal:today',
            'passengers.*.personal_card_date_expiry' => 'nullable|date|after:passengers.*.personal_card_date_issu',

        ];
    }

    public function messages(): array
    {
        return [
            'adults.required'   => 'عدد البالغين مطلوب',
            'adults.integer'     => 'عدد البالغين يجب أن يكون رقماً صحيحاً',
            'currency_id.required' => 'العملة مطلوبة',
            'currency_id.exists' => 'العملة المختارة غير موجودة',

            'adults.min'         => 'يجب أن يكون هناك بالغ واحد على الأقل',
            'adults.max'         => 'الحد الأقصى للبالغين هو 10',
            'children.integer'   => 'عدد الأطفال يجب أن يكون رقماً صحيحاً',
            'children.min'       => 'عدد الأطفال لا يمكن أن يكون أقل من 0',
            'coupon_code.exists' => 'كود الكوبون غير موجود أو غير صالح',
            'payment_method_id.exists' => 'وسيلة الدفع المختارة غير موجودة',
            'passengers.required' => 'بيانات المسافرين مطلوبة',
            'passengers.min' => 'يجب إدخال بيانات مسافر واحد على الأقل',
            'passengers.*.name.required' => 'اسم المسافر مطلوب',
            'passengers.*.birthday.required'  => 'تاريخ ميلاد المسافر مطلوب',
            'passengers.*.birthday.date'      => 'تاريخ الميلاد يجب أن يكون تاريخاً صحيحاً',
            'passengers.*.gender_id.exists'   => 'الجنس المحدد للمسافر غير موجود',
            'passengers.*.passport_date_expiry.after' => 'تاريخ انتهاء الجواز يجب أن يكون بعد تاريخ الإصدار',
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
                
                if (!$trip) {
                    $validator->errors()->add('trip', 'الرحلة غير موجودة');
                    return;
                }
                
                if ($trip->departure_time <= now()) {
                    $validator->errors()->add('trip', 'الرحلة قد بدأت بالفعل - لا يمكن حجزها');
                }
                
                $totalSeats = $trip->vehicle?->seats()->count() ?? 0;
                $bookedSeats = count($trip->getBookedSeatIds());
                $availableSeats = $totalSeats - $bookedSeats;
                
                if ($availableSeats < $totalPassengers) {
                    $validator->errors()->add('seats', 'المقاعد المتاحة غير كافية');
                }
            }

            $paymentMethodId = $this->input('payment_method_id');
            if ($paymentMethodId) {
                $paymentMethod = \App\Models\PaymentMethod::find($paymentMethodId);
                if (!$paymentMethod) {
                    $validator->errors()->add('payment_method_id', 'طريقة الدفع المختارة غير متاحة');
                }
            }

            // Coupon validation: only check exists if code is provided
            $couponCode = $this->input('coupon_code');
            if ($couponCode && !\App\Models\Coupon::where('code', $couponCode)->exists()) {
                $validator->errors()->add('coupon_code', 'كود الخصم غير موجود أو غير صالح');
            }

            // التحقق من أن مدينة الإصدار تنتمي إلى الدولة المختارة لكل مسافر
            foreach ($this->input('passengers', []) as $index => $passenger) {
                $pairs = [
                    ['personal_country_place_of_issue', 'personal_city_place_of_issue'],
                    ['passport_country_place_of_issue', 'passport_city_place_of_issue'],
                ];

                foreach ($pairs as [$countryField, $cityField]) {
                    $countryId = $passenger[$countryField] ?? null;
                    $cityId = $passenger[$cityField] ?? null;

                    if ($countryId && $cityId) {
                        $city = \App\Models\City::find($cityId);
                        if (! $city || (int) $city->country_id !== (int) $countryId) {
                            $validator->errors()->add("passengers.$index.$cityField", 'المدينة المحددة لا تنتمي إلى الدولة المختارة');
                        }
                    }
                }
            }
        });
    }
}
