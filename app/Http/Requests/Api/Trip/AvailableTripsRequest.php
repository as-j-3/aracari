<?php

namespace App\Http\Requests\Api\Trip;

use Illuminate\Foundation\Http\FormRequest;

class AvailableTripsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_city'       => 'nullable|exists:cities,uuid',
            'to_city'         => 'nullable|exists:cities,uuid',
            'departure_date'  => 'nullable|date|after_or_equal:today', 
            'min_price'       => 'nullable|numeric|min:0',
            'max_price'       => 'nullable|numeric|min:0',
            'type_of_trip_id' => 'nullable|exists:type_of_trips,id',
            'level_trip_id'   => 'nullable|exists:level_trips,id',
            'passengers'      => 'nullable|integer|min:1|max:15',
            'per_page'        => 'nullable|integer|min:1|max:100',
            'provider_id'     => 'nullable|exists:users,uuid',
            'departure_location' => 'nullable|string|max:500',
            'arrival_location'  => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'from_city.exists'          => 'مدينة الانطلاق غير موجودة',
            'to_city.exists'            => 'مدينة الوصول غير موجودة',
            'departure_date.date'       => 'تاريخ الانطلاق يجب أن يكون تاريخاً صحيحاً',
            'departure_date.after_or_equal' => 'تاريخ الانطلاق يجب أن يكون اليوم أو في المستقبل',
            'min_price.numeric'         => 'الحد الأدنى للسعر يجب أن يكون رقماً',
            'max_price.numeric'         => 'الحد الأقصى للسعر يجب أن يكون رقماً',
            'type_of_trip_id.exists'    => 'نوع الرحلة غير موجود',
            'level_trip_id.exists'      => 'مستوى الرحلة غير موجود',
            'passengers.integer'        => 'عدد المسافرين يجب أن يكون رقماً صحيحاً', 
            'passengers.min'            => 'عدد المسافرين يجب أن يكون 1 على الأقل', 
            'passengers.max'            => 'عدد المسافرين لا يمكن أن يتجاوز 15', 
            'per_page.integer'          => 'عدد العناصر في الصفحة يجب أن يكون رقماً صحيحاً',
            'per_page.max'              => 'الحد الأقصى لعدد العناصر في الصفحة هو 100',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $minPrice = $this->input('min_price');
            $maxPrice = $this->input('max_price');
            
            if ($minPrice && $maxPrice && $minPrice > $maxPrice) {
                $validator->errors()->add('max_price', 'الحد الأقصى للسعر يجب أن يكون أكبر من أو يساوي الحد الأدنى');
            }
        });
    }
}