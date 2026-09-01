<?php

namespace App\Http\Requests\Api\Trip;

use App\Constants\WeekDays;
use App\Services\ProviderContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Constants\TripStatus;

class CreateTripRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $travelDate = $this->input('travel_date');

        $this->merge([
            'repeat_days' => $this->input('repeat_days', $this->input('days', [])),
            'check_in_time' => $this->combineDateAndTime($travelDate, $this->input('check_in_clock'), $this->input('check_in_time')),
            'departure_time' => $this->combineDateAndTime($travelDate, $this->input('departure_clock'), $this->input('departure_time')),
            'arrival_time' => $this->combineDateAndTime($travelDate, $this->input('arrival_clock'), $this->input('arrival_time'), $this->input('departure_clock')),
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
        'departure_country_id' => 'nullable|exists:countries,uuid',
        'destination_country_id' => 'nullable|exists:countries,uuid',
        'is_recurring' => 'nullable|boolean',
        'is_public' => 'nullable|boolean',
        'departure_city' => 'required|exists:cities,uuid|different:destination_city',  
        'destination_city' => 'required|exists:cities,uuid|different:departure_city',  
        'pickup_location_id' => 'required|exists:city_stations,uuid',
        'arrival_location_id' => 'required|exists:city_stations,uuid',
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
        ];
    }

private function combineDateAndTime(?string $date, ?string $clock, ?string $fallback, ?string $departureClock = null): ?string
{
    if ($date && $clock) {
        if ($departureClock && strtotime($clock) <= strtotime($departureClock)) {
            $date = \Carbon\Carbon::parse($date)->addDay()->format('Y-m-d');
        }
        return $date . ' ' . $clock;
    }

    return $fallback;
}
}
