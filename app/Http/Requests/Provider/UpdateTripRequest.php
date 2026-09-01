<?php

namespace App\Http\Requests\Provider;

use App\Constants\WeekDays;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Constants\TripStatus;
use App\Models\Coupon;

class UpdateTripRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $travelDate = $this->input('travel_date');

        $this->merge([
            'repeat_days' => $this->input('repeat_days', $this->input('days', [])),
            'check_in_time' => $this->combineDateAndTime($travelDate, $this->input('check_in_clock'), $this->input('check_in_clock')),
            'departure_time' => $this->combineDateAndTime($travelDate, $this->input('departure_clock'), $this->input('departure_time')),
            'arrival_time' => $this->combineDateAndTime($travelDate, $this->input('arrival_clock'), $this->input('arrival_time')),
        ]);

        if (!$this->boolean('is_recurring')) {
            $this->merge([
                'repeat_days' => [],
                'end_date_repeat' => null,
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'travel_date' => 'sometimes|date|after_or_equal:today',
            'check_in_clock' => 'sometimes|date_format:H:i',
            'departure_clock' => 'sometimes|date_format:H:i',
            'arrival_clock' => 'sometimes|date_format:H:i',

            'from_city_id' => 'sometimes|exists:cities,uuid|different:to_city_id',
            'to_city_id' => 'sometimes|exists:cities,uuid|different:from_city_id',

            'vehicle_id' => [
                'sometimes',
                Rule::exists('vehicles', 'uuid')
                    ->where(function ($query) {
                        $user = auth()->user();
                        $query->where('user_id', $user->id)
                              ->orWhere('user_id', $user->user_parent);
                    }),
            ],
            'type_of_trip_id' => 'sometimes|exists:type_of_trips,id',
            'level_trip_id' => 'sometimes|exists:level_trips,id',

            'trip_status_id' => [
                'sometimes',
                'exists:trip_statuses,id',
                Rule::in([TripStatus::SCHEDULED, TripStatus::CANCELLED]),
            ],

            'pickup_location_id' => 'sometimes|exists:city_stations,uuid',
            'arrival_location_id' => 'sometimes|exists:city_stations,uuid|different:pickup_location_id',

            'price_for_adult' => 'sometimes|numeric|min:0',
            'price_for_child' => 'sometimes|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',

            'conditions' => 'nullable|string',
            'is_public' => 'nullable|boolean',

            'waypoint_cities' => 'nullable|array',
            'waypoint_cities.*' => 'exists:cities,uuid',

            'amenities' => 'nullable|array',
            'amenities.*' => 'exists:amenities,uuid',
            'policy_id' => [
                'nullable',
                Rule::exists('policies', 'uuid')
                    ->where(function ($query) {
                        $user = auth()->user();
                        $query->where('created_by', $user->id)
                              ->orWhereNull('created_by');
                    }),
            ],
            'coupons' => 'nullable|array',
            'coupons.*' => [
                'exists:coupons,uuid',
                function ($attribute, $value, $fail) {
                    $user = auth()->user();
                    $coupon = Coupon::where('uuid', $value)->first();
                    if ($coupon && $coupon->user_id !== $user->id && $coupon->user_id !== $user->user_parent) {
                        $fail('أحد الكوبونات المحددة لا يتبع لمزودك.');
                    }
                },
            ],

            'is_recurring' => 'nullable|boolean',
            'days' => 'nullable|array',
            'days.*' => 'nullable|string|in:' . implode(',', WeekDays::databaseValues()),
            'repeat_days' => 'nullable|array',
            'repeat_days.*' => 'nullable|string|in:' . implode(',', WeekDays::databaseValues()),
            'end_date_repeat' => 'nullable|date|after_or_equal:departure_time',
        ];
    }

    public function messages(): array
    {
        return [
            'travel_date.date' => 'تاريخ الرحلة غير صالح',
            'travel_date.after_or_equal' => 'تاريخ الرحلة يجب أن يكون اليوم أو بعده',
            'check_in_clock.date_format' => 'صيغة وقت الحضور غير صحيحة',
            'departure_clock.date_format' => 'صيغة وقت الانطلاق غير صحيحة',
            'arrival_clock.date_format' => 'صيغة وقت الوصول غير صحيحة',

            'from_city_id.exists' => 'مدينة الانطلاق غير موجودة',
            'to_city_id.exists' => 'مدينة الوصول غير موجودة',
            'to_city_id.different' => 'مدينة الوصول يجب أن تختلف عن مدينة الانطلاق',

            'vehicle_id.exists' => 'المركبة غير موجودة أو لا تتبع لمزودك',
            'type_of_trip_id.exists' => 'نوع الرحلة غير موجود',
            'level_trip_id.exists' => 'مستوى الرحلة غير موجود',
            'trip_status_id.exists' => 'حالة الرحلة غير موجودة',
            'trip_status_id.in' => 'الحالة المحددة غير مسموحة',

            'pickup_location_id.exists' => 'محطة الانطلاق غير موجودة',
            'arrival_location_id.exists' => 'محطة الوصول غير موجودة',
            'arrival_location_id.different' => 'محطة الوصول يجب أن تختلف عن محطة الانطلاق',

            'price_for_adult.numeric' => 'سعر البالغ يجب أن يكون رقماً',
            'price_for_adult.min' => 'سعر البالغ يجب أن يكون أكبر من أو يساوي 0',
            'price_for_child.numeric' => 'سعر الطفل يجب أن يكون رقماً',
            'price_for_child.min' => 'سعر الطفل يجب أن يكون أكبر من أو يساوي 0',
            'discount_percentage.numeric' => 'نسبة الخصم يجب أن تكون رقماً',
            'discount_percentage.max' => 'نسبة الخصم يجب ألا تتجاوز 100',

            'amenities.array' => 'الخدمات الإضافية يجب أن تكون مصفوفة',
            'amenities.*.exists' => 'إحدى الخدمات الإضافية غير موجودة',
            'policy_id.exists' => 'سياسة الإلغاء غير موجودة أو لا تتبع لمزودك',
            'coupons.array' => 'الكوبونات يجب أن تكون مصفوفة',
            'coupons.*.exists' => 'أحد الكوبونات غير موجود أو لا يتبع لمزودك',

            'end_date_repeat.after_or_equal' => 'تاريخ توقف التكرار يجب أن يكون بعد وقت الانطلاق',
        ];
    }

    private function combineDateAndTime(?string $date, ?string $clock, ?string $fallback): ?string
    {
        if ($date && $clock) {
            return $date . ' ' . $clock;
        }
        return $fallback;
    }
}