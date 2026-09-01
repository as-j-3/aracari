<?php

namespace App\Http\Requests\Api\Trip;

use App\Constants\WeekDays;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->hasPermission('trips:edit') || auth()->user()->isProvider());
    }

    public function rules(): array
    {
        return [
            'service_id' => 'sometimes|exists:services,id',
            'vehicle_id' => 'sometimes|exists:vehicles,uuid',
            'is_recurring' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
            'type_of_trip_id' => 'sometimes|exists:type_of_trips,id',
            'level_trip_id' => 'sometimes|exists:level_trips,id',
            'departure_city' => 'sometimes|exists:cities,uuid',
            'destination_city' => 'sometimes|exists:cities,uuid|different:departure_city',
            'pickup_location_id' => 'nullable|exists:city_stations,uuid',
            'arrival_location_id' => 'nullable|exists:city_stations,uuid',
            'check_in_time' => 'sometimes|date',
            'departure_time' => 'sometimes|date|after:check_in_time',
            'arrival_time' => 'sometimes|date|after:departure_time',
            'travel_date' => [
                'sometimes',
                'date',
                function ($attribute, $value, $fail) {
                    if (!$value) {
                        return; 
                    }
                    
                    $trip = $this->route('uuid');
                    if ($trip) {
                        $tripModel = \App\Models\Trip::where('uuid', $trip)->first();
                        
                        if ($tripModel) {
                            if ($tripModel->departure_time < now()) {
                                $fail('لا يمكن تعديل تاريخ رحلة منتهية');
                                return;
                            }
                            
                            $currentDate = $tripModel->departure_time->format('Y-m-d');
                            if ($value !== $currentDate && $value < now()->format('Y-m-d')) {
                                $fail('تاريخ الرحلة يجب أن يكون اليوم أو في المستقبل');
                                return;
                            }
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
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    if (!$value) {
                        return;
                    }
                    
                    $trip = $this->route('uuid');
                    if ($trip) {
                        $tripModel = \App\Models\Trip::where('uuid', $trip)->first();
                        
                        if ($tripModel) {
                            $hasBookings = $tripModel->bookings()->exists();
                            
                            if ($hasBookings) {
                                $fail('لا يمكن تعديل كوبونات رحلة بها حجوزات مؤكدة');
                                return;
                            }
                            
                            if ($tripModel->departure_time < now()) {
                                $fail('لا يمكن تعديل كوبونات رحلة منتهية');
                                return;
                            }
                        }
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
        ];
    }
}
