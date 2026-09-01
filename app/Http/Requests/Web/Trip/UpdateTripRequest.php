<?php

namespace App\Http\Requests\Web\Trip;

use App\Constants\WeekDays;
use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class UpdateTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasPermission('trips:edit') || auth()->user()->isProvider());
    }

    /**
     * جلب الرحلة الحالية مرة واحدة (cached)
     * تجنب تكرار الـ queries في كل closure
     */
    private ?\App\Models\Trip $cachedTrip = null;

    private function getCurrentTrip(): ?\App\Models\Trip
    {
        if ($this->cachedTrip === null) {
            $uuid = $this->route('uuid');
            $this->cachedTrip = $uuid ? \App\Models\Trip::where('uuid', $uuid)->first() : null;
        }
        return $this->cachedTrip;
    }

    protected function prepareForValidation(): void
    {
        $trip = $this->getCurrentTrip();

        if ($vehicleUuid = $this->input('vehicle_id')) {
            $vehicle = \App\Models\Vehicle::where('uuid', $vehicleUuid)->first();
        } else {
            $vehicle = $trip?->vehicle;
        }

        if ($vehicle) {
            $this->merge(['max_ticket_limit' => $vehicle->seats_count]);
        }

        if ($trip) {
            $travelDate = $this->input('travel_date', $trip->travel_date);
            $checkInClock = $this->input('check_in_clock', $trip->check_in_time->format('H:i'));
            $departureClock = $this->input('departure_clock', $trip->departure_time->format('H:i'));
            $arrivalClock = $this->input('arrival_clock', $trip->arrival_time->format('H:i'));

            $times = $this->buildTripTimes($travelDate, $checkInClock, $departureClock, $arrivalClock);

            $this->merge($times);
        }
    }

    public function rules(): array
    {
        return [
            'service_id' => 'sometimes|exists:services,id',
            'vehicle_id' => 'nullable|exists:vehicles,uuid',
            'is_recurring' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
            'ticket_limit' => 'nullable|integer|min:1|max:' . ($this->input('max_ticket_limit') ?: 100),
            'available_seats' => 'prohibited',
            'type_of_trip_id' => 'sometimes|exists:type_of_trips,id',
            'level_trip_id' => 'sometimes|exists:level_trips,id',
            'departure_country_id' => 'sometimes|uuid|exists:countries,uuid',
            'destination_country_id' => 'sometimes|uuid|exists:countries,uuid',
            'departure_city' => [
                'sometimes', 'uuid', 'exists:cities,uuid',
                function ($attribute, $value, $fail) {
                    $trip = $this->getCurrentTrip();
                    $city = $value ? \App\Models\City::where('uuid', $value)->first() : $trip?->departureCity;
                    $countryUuid = $this->input('departure_country_id');
                    $country = $countryUuid ? \App\Models\Country::where('uuid', $countryUuid)->first() : $trip?->departureCity?->country;
                    if ($city && $country && $city->country_id !== $country->id) {
                        $fail('مدينة الانطلاق المختارة لا تنتمي إلى الدولة المحددة.');
                    }
                },
            ],
            'destination_city' => [
                'sometimes', 'uuid', 'exists:cities,uuid', 'different:departure_city',
                function ($attribute, $value, $fail) {
                    $trip = $this->getCurrentTrip();
                    $city = $value ? \App\Models\City::where('uuid', $value)->first() : $trip?->destinationCity;
                    $countryUuid = $this->input('destination_country_id');
                    $country = $countryUuid ? \App\Models\Country::where('uuid', $countryUuid)->first() : $trip?->destinationCity?->country;
                    if ($city && $country && $city->country_id !== $country->id) {
                        $fail('مدينة الوصول المختارة لا تنتمي إلى الدولة المحددة.');
                    }
                },
            ],
            'pickup_location_id' => [
                'nullable', 'uuid', 'exists:city_stations,uuid',
                function ($attribute, $value, $fail) {
                    $trip = $this->getCurrentTrip();
                    $station = $value ? \App\Models\CityStation::where('uuid', $value)->first() : $trip?->pickupLocation;
                    $cityUuid = $this->input('departure_city');
                    $city = $cityUuid ? \App\Models\City::where('uuid', $cityUuid)->first() : $trip?->departureCity;
                    if ($station && $city && $station->city_id !== $city->id) {
                        $fail('محطة الانطلاق المختارة لا تنتمي إلى مدينة الانطلاق.');
                    }
                },
            ],
            'arrival_location_id' => [
                'nullable', 'uuid', 'exists:city_stations,uuid',
                function ($attribute, $value, $fail) {
                    $trip = $this->getCurrentTrip();
                    $station = $value ? \App\Models\CityStation::where('uuid', $value)->first() : $trip?->arrivalLocation;
                    $cityUuid = $this->input('destination_city');
                    $city = $cityUuid ? \App\Models\City::where('uuid', $cityUuid)->first() : $trip?->destinationCity;
                    if ($station && $city && $station->city_id !== $city->id) {
                        $fail('محطة الوصول المختارة لا تنتمي إلى مدينة الوصول.');
                    }
                },
            ],
            'check_in_time' => 'sometimes|date',
            'departure_time' => 'sometimes|date|after:check_in_time',
            'arrival_time' => 'sometimes|date|after:departure_time',
            'travel_date' => [
                'sometimes',
                'date',
                function ($attribute, $value, $fail) {
                    if (!$value) return;
                    $trip = $this->getCurrentTrip();
                    if ($trip) {
                        if ($trip->departure_time < now()) {
                            $fail('لا يمكن تعديل تاريخ رحلة منتهية');
                            return;
                        }
                        $currentDate = $trip->departure_time->format('Y-m-d');
                        if ($value !== $currentDate && $value < now()->format('Y-m-d')) {
                            $fail('تاريخ الرحلة يجب أن يكون اليوم أو في المستقبل');
                            return;
                        }
                    }
                }
            ],
            'price_for_adult' => 'sometimes|numeric|min:0',
            'price_for_child' => 'sometimes|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'conditions' => 'nullable|string',
            'policy_id' => 'nullable|exists:policies,uuid',
            'amenities' => 'nullable|array',
            'amenities.*' => 'exists:amenities,uuid',
            'coupons' => [
                'nullable', 'array',
                function ($attribute, $value, $fail) {
                    if (!$this->has('coupons')) return;

                    $trip = $this->getCurrentTrip();
                    if (!$trip) return;

                    // 🔑 FIX: Compare submitted coupons with current coupons
                    // Only block if coupons actually CHANGED
                    $currentCoupons = $trip->coupons()->pluck('uuid')->sort()->values()->toArray();
                    $submittedCoupons = collect($value ?? [])->filter()->sort()->values()->toArray();

                    // If coupons haven't changed → allow (no restriction needed)
                    if ($currentCoupons === $submittedCoupons) return;

                    // Coupons ARE being changed → apply restrictions
                    if ($trip->bookings()->exists()) {
                        $fail('لا يمكن تعديل كوبونات رحلة بها حجوزات');
                        return;
                    }

                    if ($trip->departure_time < now()) {
                        $fail('لا يمكن تعديل كوبونات رحلة منتهية');
                        return;
                    }
                }
            ],
            'end_date_repeat' => 'nullable|date|after_or_equal:departure_time',
            'days' => 'nullable|array',
            'days.*' => 'nullable|string|in:' . implode(',', WeekDays::databaseValues()),
            'coupons.*' => 'exists:coupons,uuid',
            'cancellation_policy' => 'nullable|string',
            'cancellation_fee' => 'nullable|numeric|min:0',
            'cancellation_deadline_hours' => 'nullable|integer|min:0',
            'repeat_days' => 'nullable|array',
            'repeat_days.*' => 'nullable|string|in:' . implode(',', WeekDays::databaseValues()),
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'waypoints' => 'nullable|array',
            'waypoints.*.city_uuid' => [
                'required', 'uuid', 'exists:cities,uuid',
                function ($attribute, $value, $fail) {
                    $tripModel = $this->getCurrentTrip();
                    $departureCityUuid = $this->input('departure_city') ?? $tripModel?->departure_city;
                    $destinationCityUuid = $this->input('destination_city') ?? $tripModel?->destination_city;

                    if ($value === $departureCityUuid || $value === $destinationCityUuid) {
                        $fail('مدينة المرور لا يمكن أن تكون هي نفسها مدينة الانطلاق أو الوصول.');
                        return;
                    }

                    $departureCountryId = \App\Models\City::where('uuid', $departureCityUuid)->value('country_id');
                    $destinationCountryId = \App\Models\City::where('uuid', $destinationCityUuid)->value('country_id');
                    $stopCity = \App\Models\City::where('uuid', $value)->first();

                    if ($stopCity && !in_array($stopCity->country_id, [$departureCountryId, $destinationCountryId])) {
                        $fail('مدينة المرور يجب أن تنتمي إلى دولة الانطلاق أو دولة الوصول.');
                    }
                }
            ],
            'waypoints.*.city_station_uuid' => [
                'nullable', 'uuid', 'exists:city_stations,uuid',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1];
                    $cityUuid = $this->input("waypoints.{$index}.city_uuid");
                    if ($value && $cityUuid) {
                        $city = \App\Models\City::where('uuid', $cityUuid)->first();
                        $station = \App\Models\CityStation::where('uuid', $value)->first();
                        if ($city && $station && $station->city_id !== $city->id) {
                            $fail('محطة المرور المختارة لا تنتمي إلى مدينة المرور المحددة.');
                        }
                    }
                }
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_id.exists' => 'المركبة غير موجودة',
            'type_of_trip_id.exists' => 'نوع الرحلة غير موجود',
            'level_trip_id.exists' => 'مستوى الرحلة غير موجود',
            'departure_city.exists' => 'مدينة الانطلاق غير موجودة',
            'destination_city.exists' => 'مدينة الوصول غير موجودة',
            'destination_city.different' => 'مدينة الوصول يجب أن تختلف عن مدينة الانطلاق',
            'check_in_time.date' => 'وقت تسجيل الدخول يجب أن يكون تاريخاً صحيحاً',
            'departure_time.after' => 'وقت الانطلاق يجب أن يكون بعد وقت تسجيل الدخول',
            'arrival_time.after' => 'وقت الوصول يجب أن يكون بعد وقت الانطلاق',
            'travel_date.after_or_equal' => 'تاريخ الرحلة يجب أن يكون اليوم أو في المستقبل',
            'price_for_adult.min' => 'يجب أن يكون السعر أكبر من أو يساوي 0',
            'price_for_child.min' => 'يجب أن يكون السعر أكبر من أو يساوي 0',
            'discount_percentage.numeric' => 'نسبة الخصم يجب أن تكون رقماً',
            'discount_percentage.max' => 'نسبة الخصم يجب أن تكون 100 كحد أقصى',
            'amenities.array' => 'يجب أن تكون الخدمات الإضافية مصفوفة',
            'amenities.*.exists' => 'إحدى الخدمات الإضافية غير موجودة',
            'coupons.array' => 'يجب أن تكون الكوبونات المسموحة مصفوفة',
            'coupons.*.exists' => 'إحدى الكوبونات المسموحة غير موجودة',
            'cancellation_policy.string' => 'سياسة الإلغاء يجب أن تكون نصاً',
            'cancellation_fee.numeric' => 'رسوم الإلغاء يجب أن تكون رقماً',
            'cancellation_fee.min' => 'رسوم الإلغاء يجب أن تكون أكبر من أو تساوي 0',
            'cancellation_deadline_hours.integer' => 'مدة الإلغاء يجب أن تكون عدداً صحيحاً',
            'cancellation_deadline_hours.min' => 'مدة الإلغاء يجب أن تكون أكبر من أو تساوي 0',
            'repeat_days.array' => 'يجب أن تكون أيام التكرار مصفوفة',
            'repeat_days.*.string' => 'يوم التكرار يجب أن يكون نصاً',
            'repeat_days.*.in' => 'يوم التكرار يجب أن يكون من بين الأيام المسموحة',
            'end_date_repeat.date' => 'تاريخ انتهاء التكرار يجب أن يكون تاريخاً صحيحاً',
            'ticket_limit.integer' => 'عدد التذاكر يجب أن يكون رقماً صحيحاً',
            'ticket_limit.min' => 'عدد التذاكر يجب أن يكون 1 على الأقل',
            'ticket_limit.max' => 'عدد التذاكر لا يمكن أن يتجاوز سعة المركبة',
            'available_seats.prohibited' => 'لا يمكن تحديد المقاعد المتاحة عند تعديل الرحلة',
            'image.image' => 'يجب أن يكون الملف صورة',
            'image.mimes' => 'يجب أن تكون الصورة من نوع jpg, jpeg, png, webp',
            'image.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',
        ];
    }

    private function buildTripTimes(
        string $travelDate,
        ?string $checkInClock,
        ?string $departureClock,
        ?string $arrivalClock
    ): array {
        if (!$travelDate || !$checkInClock || !$departureClock || !$arrivalClock) {
            return [];
        }

        $checkIn = Carbon::parse("{$travelDate} {$checkInClock}");
        $departure = Carbon::parse("{$travelDate} {$departureClock}");

        if ($departure->lt($checkIn)) {
            $departure->addDay();
        }

        $arrival = Carbon::parse($departure->format('Y-m-d') . ' ' . $arrivalClock);

        if ($arrival->lt($departure)) {
            $arrival->addDay();
        }

        return [
            'check_in_time'  => $checkIn->format('Y-m-d H:i:s'),
            'departure_time' => $departure->format('Y-m-d H:i:s'),
            'arrival_time'   => $arrival->format('Y-m-d H:i:s'),
        ];
    }
}
