<?php

namespace App\Http\Requests\Web\Trip;

use App\Constants\WeekDays;
use App\Services\ProviderContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Constants\TripStatus;
use Carbon\Carbon;

class CreateTripRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $travelDate = $this->input('travel_date');

        if ($travelDate) {
            $times = $this->buildTripTimes(
                $travelDate,
                $this->input('check_in_clock'),
                $this->input('departure_clock'),
                $this->input('arrival_clock')
            );

            $this->merge([
                'repeat_days' => $this->input('repeat_days', $this->input('days', [])),
                ...$times,
            ]);
        }

        if (!$this->boolean('is_recurring')) {
            $this->merge([
                'repeat_days' => [],
                'end_date_repeat' => null,
            ]);
        }

        if ($vehicleUuid = $this->input('vehicle_id')) {
            $vehicle = \App\Models\Vehicle::where('uuid', $vehicleUuid)->first();
            if ($vehicle) {
                $this->merge(['max_ticket_limit' => $vehicle->seats_count]);
            }
        }
    }

    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasPermission('trips:create') || auth()->user()->isProvider());
    }

public function rules(): array
{
    $user = $this->user();
    $isAdmin = (bool) $user?->isAdmin();
    $providerContext = app(ProviderContext::class);
    $providerUserId = $providerContext->providerUserIdFor($user);
    $providerServiceId = $providerContext->providerServiceIdFor($user);

    return [
        'service_id' => [
            'nullable',
            Rule::exists('services', 'id')->when(
                !$isAdmin,
                fn ($rule) => $rule->whereExists(function ($query) use ($providerServiceId) {
                    $query->selectRaw('1')
                        ->from('provider_branches')
                        ->whereColumn('provider_branches.id', 'services.branch_id')
                        ->where('provider_branches.provider_id', $providerServiceId ?? -1);
                })
            ),
        ],
        'vehicle_id' => [
            'required',
            Rule::exists('vehicles', 'uuid')->when(
                !$isAdmin,
                fn ($rule) => $rule->where('user_id', $providerUserId ?? -1)
            ),
        ],
        'type_of_trip_id' => 'required|exists:type_of_trips,id',
        'level_trip_id' => 'required|exists:level_trips,id',
        'trip_status_id' => [
            'required',
            'exists:trip_statuses,id',
            Rule::in([TripStatus::SCHEDULED]),
        ],
        'currency_id' => 'nullable|exists:currencies,id',
        'travel_date' => 'required|date|after_or_equal:today',
        'check_in_clock' => 'required|date_format:H:i',
        'departure_clock' => 'required|date_format:H:i',
        'arrival_clock' => 'required|date_format:H:i',
        'departure_country_id' => 'required|uuid|exists:countries,uuid',
        'destination_country_id' => 'required|uuid|exists:countries,uuid',
        'is_recurring' => 'nullable|boolean',
        'is_public' => 'nullable|boolean',
        'ticket_limit' => [
            'nullable',
            'integer',
            'min:1',
            'max:' . ($this->input('max_ticket_limit') ?: 100),
        ],
        'available_seats'=>'prohibited',
        'departure_city' => [
            'required', 'uuid', 'exists:cities,uuid',
            function ($attribute, $value, $fail) {
                $countryUuid = $this->input('departure_country_id');
                if ($value && $countryUuid) {
                    $city = \App\Models\City::where('uuid', $value)->first();
                    $country = \App\Models\Country::where('uuid', $countryUuid)->first();
                    // مقارنة الـ ID الحقيقي في قاعدة البيانات
                    if ($city && $country && $city->country_id !== $country->id) {
                        $fail('مدينة الانطلاق المختارة لا تنتمي إلى الدولة المحددة.');
                    }
                }
            },
        ],
        'destination_city' => [
            'required', 'uuid', 'exists:cities,uuid', 'different:departure_city',
            function ($attribute, $value, $fail) {
                $countryUuid = $this->input('destination_country_id');
                if ($value && $countryUuid) {
                    $city = \App\Models\City::where('uuid', $value)->first();
                    $country = \App\Models\Country::where('uuid', $countryUuid)->first();
                    if ($city && $country && $city->country_id !== $country->id) {
                        $fail('مدينة الوصول المختارة لا تنتمي إلى الدولة المحددة.');
                    }
                }
            },
        ],

        'pickup_location_id' => [
            'required', 'uuid', 'exists:city_stations,uuid',
            function ($attribute, $value, $fail) {
                $cityUuid = $this->input('departure_city');
                if ($value && $cityUuid) {
                    $city = \App\Models\City::where('uuid', $cityUuid)->first();
                    $station = \App\Models\CityStation::where('uuid', $value)->first();
                    if ($city && $station && $station->city_id !== $city->id) {
                        $fail('محطة الانطلاق المختارة لا تنتمي إلى مدينة الانطلاق.');
                    }
                }
            },
        ],
        'arrival_location_id' => [
            'required', 'uuid', 'exists:city_stations,uuid',
            function ($attribute, $value, $fail) {
                $cityUuid = $this->input('destination_city');
                if ($value && $cityUuid) {
                    $city = \App\Models\City::where('uuid', $cityUuid)->first();
                    $station = \App\Models\CityStation::where('uuid', $value)->first();
                    if ($city && $station && $station->city_id !== $city->id) {
                        $fail('محطة الوصول المختارة لا تنتمي إلى مدينة الوصول.');
                    }
                }
            },
        ],        
        'check_in_time' => 'required|date|after:now',
        'departure_time' => 'required|date|after:check_in_time',
        'arrival_time' => 'required|date|after:departure_time',
        'price_for_adult' => 'required|numeric|min:0',
        'price_for_child' => 'required|numeric|min:0',
        'discount_percentage' => 'nullable|numeric|min:0|max:100',
        'conditions' => 'nullable|string',
        'policy_id' => [
            'nullable',
            Rule::exists('policies', 'uuid')->when(  
                !$isAdmin,
                fn ($rule) => $rule->where('created_by', $providerUserId ?? -1)
            ),
        ],
        'amenities' => 'nullable|array',
        'amenities.*' => 'exists:amenities,uuid',  
        'days' => 'nullable|array',
        'days.*' => 'nullable|string|in:' . implode(',', WeekDays::databaseValues()),
        'repeat_days' => 'nullable|array',
        'repeat_days.*' => 'nullable|string|in:' . implode(',', WeekDays::databaseValues()),
        'end_date_repeat' => 'nullable|date|after_or_equal:departure_time',
        'coupons' => 'nullable|array',
        'coupons.*' => 'exists:coupons,uuid',
        'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'waypoints' => 'nullable|array',
        'waypoints.*.city_uuid' => [
            'required',
            'uuid',
            'exists:cities,uuid',
            function ($attribute, $value, $fail) {
                $departureCityUuid = $this->input('departure_city');
                $destinationCityUuid = $this->input('destination_city');
                
                // 1. منع اختيار مدينة الانطلاق أو الوصول كمدن مرور
                if ($value === $departureCityUuid || $value === $destinationCityUuid) {
                    $fail('مدينة المرور لا يمكن أن تكون هي نفسها مدينة الانطلاق أو الوصول.');
                    return;
                }

                // 2. التحقق أن مدينة المرور تنتمي لدولة الانطلاق أو الوصول
                $departureCountryId = \App\Models\City::where('uuid', $departureCityUuid)->value('country_id');
                $destinationCountryId = \App\Models\City::where('uuid', $destinationCityUuid)->value('country_id');
                $stopCity = \App\Models\City::where('uuid', $value)->first();
                
                if ($stopCity && !in_array($stopCity->country_id, [$departureCountryId, $destinationCountryId])) {
                    $fail('مدينة المرور يجب أن تنتمي إلى دولة الانطلاق أو دولة الوصول.');
                }
            }
        ],
        'waypoints.*.city_station_uuid' => [
            'nullable',
            'uuid',
            'exists:city_stations,uuid',
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
            'vehicle_id.required' => 'المركبة مطلوبة',
            'vehicle_id.exists' => 'المركبة غير موجودة',
            'type_of_trip_id.required' => 'نوع الرحلة مطلوب',
            'type_of_trip_id.exists' => 'نوع الرحلة غير موجود',
            'level_trip_id.required' => 'مستوى الرحلة مطلوب',
            'level_trip_id.exists' => 'مستوى الرحلة غير موجود',
            'travel_date.required' => 'تاريخ الرحلة مطلوب',
            'travel_date.after_or_equal' => 'تاريخ الرحلة يجب أن يكون اليوم أو بعده',
            'check_in_clock.required' => 'وقت الحضور مطلوب',
            'departure_clock.required' => 'وقت الانطلاق مطلوب',
            'arrival_clock.required' => 'وقت الوصول المتوقع مطلوب',
            'departure_city.required' => 'مدينة الانطلاق مطلوبة',
            'departure_city.exists' => 'مدينة الانطلاق غير موجودة',
            'destination_city.required' => 'مدينة الوصول مطلوبة',
            'destination_city.exists' => 'مدينة الوصول غير موجودة',
            'destination_city.different' => 'مدينة الوصول يجب أن تختلف عن مدينة الانطلاق',
            'pickup_location_id.required' => 'موقع الانطلاق مطلوب',
            'arrival_location_id.required' => 'موقع الوصول مطلوب',
            'check_in_time.required' => 'وقت الحضور مطلوب',
            'check_in_time.after' => 'وقت الحضور يجب أن يكون في المستقبل',
            'departure_time.required' => 'وقت الانطلاق مطلوب',
            'departure_time.after' => 'وقت الانطلاق يجب أن يكون بعد وقت الحضور',
            'arrival_time.required' => 'وقت الوصول مطلوب',
            'arrival_time.after' => 'وقت الوصول يجب أن يكون بعد وقت الانطلاق',
            'price_for_adult.required' => 'سعر البالغ مطلوب',
            'price_for_adult.min' => 'سعر البالغ يجب أن يكون أكبر من أو يساوي 0',
            'price_for_child.required' => 'سعر الطفل مطلوب',
            'price_for_child.min' => 'سعر الطفل يجب أن يكون أكبر من أو يساوي 0',
            'discount_percentage.numeric' => 'نسبة الخصم يجب أن تكون رقمًا',
            'discount_percentage.max' => 'نسبة الخصم يجب ألا تتجاوز 100',
            'policy_id.exists' => 'سياسة الإلغاء غير موجودة',
            'amenities.array' => 'الخدمات الإضافية يجب أن تكون مصفوفة',
            'amenities.*.exists' => 'إحدى الخدمات الإضافية غير موجودة',
            'currency_id.exists' => 'العملة المختارة غير موجودة',
            'end_date_repeat.after_or_equal' => 'تاريخ توقف التكرار يجب أن يكون بعد وقت الانطلاق أو مساويًا له',
            'ticket_limit.integer' => 'عدد التذاكر يجب أن يكون رقماً صحيحاً',
            'ticket_limit.min' => 'عدد التذاكر يجب أن يكون 1 على الأقل',
            'ticket_limit.max' => 'عدد التذاكر لا يمكن أن يتجاوز سعة المركبة',
            'available_seats.prohibited' => 'لا يمكن تحديد المقاعد المتاحة عند إنشاء الرحلة',
            'image.image' => 'يجب أن يكون الملف صورة',
            'image.mimes' => 'يجب أن تكون الصورة من نوع jpg, jpeg, png, webp',
            'image.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',
            'waypoints.array' => 'نقاط المرور يجب أن تكون مصفوفة',
            'waypoints.*.city_uuid.required' => 'مدينة المرور مطلوبة لكل نقطة',
            'waypoints.*.city_uuid.exists' => 'مدينة المرور غير موجودة لكل نقطة',
            'waypoints.*.city_station_uuid.exists' => 'محطة المرور غير موجودة لكل نقطة',
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
