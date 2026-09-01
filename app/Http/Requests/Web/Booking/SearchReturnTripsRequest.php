<?php

namespace App\Http\Requests\Web\Booking;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Trip;
use Illuminate\Support\Carbon;

/**
 * طلب البحث عن رحلات العودة (Web AJAX)
 *
 * طبقة أمان: هذا الـ Request يُستخدم فقط من صفحة الحجز (محمية بـ auth).
 * CSRF token مطلوب (تلقائي في web routes).
 *
 * ⚠️ الفرق عن Api/Trip/ReturnTripOptionsRequest:
 *   - authorize() يتطلب تسجيل دخول
 *   - date يسمح بنفس اليوم (after_or_equal:today)
 *   - التحقق النهائي في ReturnTripSearchService
 */
class SearchReturnTripsRequest extends FormRequest
{
    /**
     * يجب أن يكون المستخدم مسجلاً (صفحة الحجز محمية بـ auth)
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * قواعد التحقق
     *
     * outbound_trip_uuid: يجب أن يشير لرحلة موجودة + محذوفة ناعماً لا تُحسب
     * date: يجب أن يكون تاريخ صالح (يسمح بنفس اليوم للعودة بعد الوصول)
     */
    public function rules(): array
    {
        return [
            'outbound_trip_uuid' => [
                'required',
                'string',
                'exists:trips,uuid',
            ],
            'date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
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

    /**
     * جلب رحلة الذهاب المحمّلة (مع التحقق الإضافي)
     *
     * @return Trip|null
     */
    public function getOutboundTrip(): ?Trip
    {
        return Trip::where('uuid', $this->outbound_trip_uuid)->first();
    }

    /**
     * جلب التاريخ كـ Carbon object (startOfDay)
     */
    public function getTargetDate(): Carbon
    {
        return Carbon::parse($this->date)->startOfDay();
    }
}
