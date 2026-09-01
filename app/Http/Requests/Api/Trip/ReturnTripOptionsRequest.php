<?php

namespace App\Http\Requests\Api\Trip;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReturnTripOptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'outbound_trip_uuid' => ['required', 'string', 'exists:trips,uuid'],
            'date'               => ['required', 'string', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'outbound_trip_uuid.required' => 'رحلة الذهاب مطلوبة',
            'outbound_trip_uuid.exists'   => 'رحلة الذهاب غير موجودة',
            'date.required'                => 'تاريخ العودة مطلوب',
            'date.after_or_equal'          => 'تاريخ العودة يجب أن يكون اليوم أو بعد',
        ];
    }
}
