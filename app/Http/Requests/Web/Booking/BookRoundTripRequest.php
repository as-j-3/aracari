<?php

namespace App\Http\Requests\Web\Booking;

use Illuminate\Foundation\Http\FormRequest;
use App\Constants\TripStatus;
use Illuminate\Support\Carbon;

/**
 * طلب حجز ذهاب وعودة
 *
 * ═══════════════════════════════════════════════════════════════
 * تبسيط المرحلة 5: UUID دائماً instance حقيقي
 * ═══════════════════════════════════════════════════════════════
 * منذ المرحلة 1-4، بحث رحلات العودة يُرجع instances فقط:
 *   - Normal Trip: instance حقيقي
 *   - Recurring Child: instance حقيقي مع parent_id
 *   - Recurring Parent (قالب): لا يظهر أبداً
 *
 * هذا يعني:
 *   - return_trip_uuid → دائماً instance حقيقي
 *   - departure_time للـ return trip = الوقت الحقيقي (ليس وقت القالب)
 *   - لا حاجة للتحقق من repeats أو حساب effectiveDeparture
 *
 * ⚠️ الحماية الحقيقية النهائية في BookingService::bookTrips()
 *    داخل DB transaction مع lockForUpdate.
 */
class BookRoundTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'outbound_trip_uuid' => 'required|exists:trips,uuid',
            'currency_id' => 'required|integer|exists:currencies,id',
            'return_trip_uuid' => 'required|exists:trips,uuid|different:outbound_trip_uuid',
            'adults' => 'required|integer|min:1|max:10',
            'children' => 'nullable|integer|min:0|max:5',
            'passengers' => 'required|array|min:1',
            'passengers.*.name' => 'required|string|min:3|max:255',
            'passengers.*.birthday' => 'required|date',
            'passengers.*.gender_id' => 'nullable|exists:genders,id',
            'passengers.*.phone_number' => 'nullable|string|max:20',
            'passengers.*.passport_number' => 'nullable|string|max:50',
            'passengers.*.passport_date_issu' => 'nullable|date',
            'passengers.*.passport_date_expiry' => 'nullable|date|after:passengers.*.passport_date_issu',
            'passengers.*.passport_photo' => 'nullable|string|max:255',
            'passengers.*.passport_place_of_issue' => 'nullable|string|max:255',
            'passengers.*.personal_card_id' => 'nullable|string|max:50',
            'passengers.*.personal_card_id_photo' => 'nullable|string|max:255',
            'passengers.*.personal_card_place_of_issue' => 'nullable|string|max:255',
            'passengers.*.personal_card_date_issu' => 'nullable|date|before_or_equal:today',
            'passengers.*.personal_card_date_expiry' => 'nullable|date|after:passengers.*.personal_card_date_issu',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'outbound_coupon_code' => 'nullable|string|max:50',
            'return_coupon_code' => 'nullable|string|max:50',
            'return_travel_date' => 'required|date|after_or_equal:today',
        ];
    }

    public function messages(): array
    {
        return [
            'outbound_trip_uuid.required' => 'رحلة الذهاب مطلوبة',
            'currency_id.required' => 'العملة مطلوبة',
            'currency_id.exists' => 'العملة المختارة غير موجودة',
            'outbound_trip_uuid.exists' => 'رحلة الذهاب غير موجودة',
            'return_trip_uuid.required' => 'رحلة العودة مطلوبة',
            'return_trip_uuid.exists' => 'رحلة العودة غير موجودة',
            'return_trip_uuid.different' => 'يجب أن تكون رحلة العودة مختلفة عن رحلة الذهاب',
            'adults.required' => 'عدد البالغين مطلوب',
            'adults.min' => 'عدد البالغين يجب أن يكون على الأقل 1',
            'passengers.required' => 'بيانات الركاب مطلوبة',
            'passengers.*.name.required' => 'اسم المسافر مطلوب',
            'passengers.*.birthday.required' => 'تاريخ ميلاد المسافر مطلوب',
            'payment_method_id.required' => 'طريقة الدفع مطلوبة',
            'payment_method_id.exists' => 'طريقة الدفع غير موجودة',
            'return_travel_date.required' => 'تاريخ العودة مطلوب',
            'return_travel_date.after_or_equal' => 'تاريخ العودة يجب أن يكون اليوم أو بعده',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $outboundTrip = \App\Models\Trip::where('uuid', $this->outbound_trip_uuid)->first();
            $returnTrip = \App\Models\Trip::where('uuid', $this->return_trip_uuid)->first();

            // early return if trips not found
            if (!$outboundTrip) {
                $validator->errors()->add('outbound_trip_uuid', 'رحلة الذهاب غير موجودة');
                return;
            }
            if (!$returnTrip) {
                $validator->errors()->add('return_trip_uuid', 'رحلة العودة غير موجودة');
                return;
            }

            // ══════════════════════════════════════════════════════════
            // التحقق 1: المسار العكسي
            // ══════════════════════════════════════════════════════════
            if ($outboundTrip->destination_city != $returnTrip->departure_city) {
                $validator->errors()->add(
                    'return_trip_uuid',
                    'يجب أن تكون رحلة العودة من نفس مدينة وصول رحلة الذهاب'
                );
            }
            if ($outboundTrip->departure_city != $returnTrip->destination_city) {
                $validator->errors()->add(
                    'return_trip_uuid',
                    'يجب أن تكون رحلة العودة إلى نفس مدينة انطلاق رحلة الذهاب'
                );
            }

            // ══════════════════════════════════════════════════════════
            // التحقق 2: العودة بعد وصول الذهاب
            // ══════════════════════════════════════════════════════════
            $returnDeparture = Carbon::parse($returnTrip->departure_time);

            if ($returnDeparture->lte(Carbon::parse($outboundTrip->arrival_time))) {
                $validator->errors()->add(
                    'return_trip_uuid',
                    'يجب أن يكون وقت انطلاق رحلة العودة بعد وصول رحلة الذهاب'
                );
            }

            // ══════════════════════════════════════════════════════════
            // التحقق 3: return_travel_date يطابق تاريخ الـ instance
            // ══════════════════════════════════════════════════════════
            // مبسّط: UUID دائماً instance → departure_time هو الوقت الحقيقي
            // return_travel_date يجب أن يساوي DATE(return_trip.departure_time)
            //
            // لماذا هذا مهم؟ دفاع ضد العبث بالتاريخ من العميل.
            // مثال هجومي:
            //   return_travel_date = 25 أغسطس
            //   return_trip_uuid = instance في 30 أغسطس
            // → مرفوض
            if ($this->return_travel_date) {
                $requestedDate = Carbon::parse($this->return_travel_date)->startOfDay();
                $tripActualDate = Carbon::parse($returnTrip->departure_time)->startOfDay();

                if (!$requestedDate->eq($tripActualDate)) {
                    $validator->errors()->add(
                        'return_travel_date',
                        'تاريخ العودة لا يتطابق مع تاريخ انطلاق رحلة العودة المختارة'
                    );
                }
            }

            // ══════════════════════════════════════════════════════════
            // التحقق 4: حالة الرحلتين
            // ══════════════════════════════════════════════════════════
            if (!in_array($outboundTrip->trip_status_id, [TripStatus::SCHEDULED, TripStatus::IN_PROGRESS])) {
                $validator->errors()->add('outbound_trip_uuid', 'رحلة الذهاب غير متاحة للحجز حالياً');
            }
            if ($returnTrip->trip_status_id != TripStatus::SCHEDULED) {
                $validator->errors()->add('return_trip_uuid', 'رحلة العودة غير متاحة للحجز حالياً');
            }

            // ══════════════════════════════════════════════════════════
            // التحقق 5: المقاعد المتاحة
            // ══════════════════════════════════════════════════════════
            $totalPassengers = ($this->adults ?? 1) + ($this->children ?? 0);
            if ($outboundTrip->available_seats < $totalPassengers) {
                $validator->errors()->add('outbound_trip_uuid', 'رحلة الذهاب لا يوجد بها مقاعد كافية');
            }
            if ($returnTrip->available_seats < $totalPassengers) {
                $validator->errors()->add('return_trip_uuid', 'رحلة العودة لا يوجد بها مقاعد كافية');
            }

            // ══════════════════════════════════════════════════════════
            // التحقق 6: الرحلات عامة
            // ══════════════════════════════════════════════════════════
            if (!$outboundTrip->is_public) {
                $validator->errors()->add('outbound_trip_uuid', 'رحلة الذهاب غير متاحة للحجز');
            }
            if (!$returnTrip->is_public) {
                $validator->errors()->add('return_trip_uuid', 'رحلة العودة غير متاحة للحجز');
            }

            // ══════════════════════════════════════════════════════════
            // التحقق 7: عدم انتهاء وقت الحجز (2 ساعة قبل الانطلاق)
            // ══════════════════════════════════════════════════════════
            // مبسّط: departure_time للعودة هو الوقت الحقيقي (instance)
            // لا حاجة لحساب effectiveReturnDeparture من القالب
            $now = now();

            if (Carbon::parse($outboundTrip->departure_time)->lte($now->copy()->addHours(2))) {
                $validator->errors()->add('outbound_trip_uuid', 'انتهى وقت حجز رحلة الذهاب');
            }
            if ($returnDeparture->lte($now->copy()->addHours(2))) {
                $validator->errors()->add('return_trip_uuid', 'انتهى وقت حجز رحلة العودة');
            }

            // ══════════════════════════════════════════════════════════
            // التحقق 8: عدد المسافرين = البالغين + الأطفال
            // ══════════════════════════════════════════════════════════
            $adults = $this->input('adults', 0);
            $children = $this->input('children', 0);
            $totalFromInputs = $adults + $children;
            $passengersCount = count($this->input('passengers', []));

            if ($totalFromInputs !== $passengersCount) {
                $validator->errors()->add('passengers_count', 'عدد المسافرين يجب أن يساوي مجموع البالغين والأطفال');
            }
        });
    }
}
