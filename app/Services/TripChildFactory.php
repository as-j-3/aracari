<?php

namespace App\Services;

use App\Models\Trip;
use Illuminate\Support\Carbon;
use Illuminate\Support\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * المصدر الوحيد (Single Source of Truth) لإنشاء رحلات Children من Parent.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * لماذا هذا السيرفيس؟
 * ═══════════════════════════════════════════════════════════════════════════
 * كان لدينا 3 أماكن تنشئ Child Trip بنفس المنطق المكرر:
 *   1. TripService::createChildTripsForRepetition()
 *   2. CreateRecurringTripsJob::handle()
 *   3. BookingService::spawnTripFromParent()
 *
 * أي تعديل في حقل أو علاقة كان يجب تطبيقه في 3 أماكن.
 * هذا السيرفيس يوحّد المنطق في مكان واحد.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * المسؤوليات:
 * ═══════════════════════════════════════════════════════════════════════════
 * ✅ حساب الأوقات الجديدة (check_in, departure, arrival) مع الحفاظ على الإزاحة
 * ✅ حساب available_seats الابتدائي (min(ticket_limit, vehicle_capacity))
 * ✅ نسخ جميع الحقول من Parent إلى Child
 * ✅ نسخ العلاقات (amenities, policies, coupons)
 * ✅ حماية Race Condition: DB::transaction + lockForUpdate + errno 1062 fallback
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * ما ليس من مسؤولياته:
 * ═══════════════════════════════════════════════════════════════════════════
 * ✖ التحقق من صلاحية التاريخ ضمن جدول التكرار (مسؤولية BookingService)
 * ✖ التحقق من تكرار الرحلة أو تعارض المركبة (مسؤولية TripValidationService)
 * ✖ التكرار على مجموعة أيام (مسؤولية TripService — يمرّ على كل يوم ويستدعي هذا)
 * ✖ التحقق من صلاحية المستخدم (مسؤولية الكونترولر)
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * حماية Race Condition (3 طبقات):
 * ═══════════════════════════════════════════════════════════════════════════
 * Layer 1: lockForUpdate داخل transaction (يمنع قراءة+كتابة متزامنة)
 * Layer 2: Unique Constraint (parent_id, departure_date) على مستوى DB
 * Layer 3: errno 1062 fallback — إذا اخترقنا الطبقتين، نحاول جلب الموجود
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * الحقول المنسوخة:
 * ═══════════════════════════════════════════════════════════════════════════
 * الهوية:       uuid (جديد), created_by_user, service_id, parent_id,
 *               trip_name_id (جديد لكل child)
 * المركبة:     vehicle_id, type_of_trip_id, level_trip_id
 * الرؤية:       is_public, trip_status_id
 * المسار:       departure_city, destination_city,
 *               pickup_location_id, arrival_location_id
 * الأوقات:     check_in_time, departure_time, arrival_time (معاد حسابها)
 * التسعير:     price_for_adult, price_for_child, currency_id,
 *               discount_percentage
 * المقاعد:     ticket_limit, available_seats (محسوب)
 * أخرى:        conditions, image
 *
 * الحقول التي لا تُنسخ (بقصد):
 *   ✖ id — فريد لكل trip
 *   ✖ repeats — Child ليس له جدول تكرار خاص
 *   ✖ tripReservations — لا حجوزات على الطفل الجديد
 *   ✖ waypoints — لا تُنسخ (كل instance قد يحتاج waypoints مختلفة)
 */
class TripChildFactory
{
    /**
     * إنشاء رحلة Child واحدة من Parent لتاريخ محدد.
     *
     * Idempotent: إذا وُجد Child لنفس Parent + نفس التاريخ، يُرجعه بدلاً من إنشاء جديد.
     *
     * @param Trip $parent الرحلة الأصلية (القالب)
     * @param Carbon $targetDate التاريخ المطلوب (startOfDay)
     * @return Trip الرحلة المنشأة أو الموجودة
     * @throws \Exception إذا فشل الإنشاء لسبب غير تكرار
     */
    public function createChildTrip(Trip $parent, Carbon $targetDate): Trip
    {
        return DB::transaction(function () use ($parent, $targetDate): Trip {
            // ── Layer 1: Application-level lock ──
            $alreadyCreated = Trip::where('parent_id', $parent->id)
                ->whereDate('departure_time', $targetDate)
                ->lockForUpdate()
                ->first();

            if ($alreadyCreated) {
                Log::info('TripChildFactory: Child already exists, returning existing', [
                    'parent_id' => $parent->id,
                    'target_date' => $targetDate->toDateString(),
                    'existing_child_id' => $alreadyCreated->id,
                ]);
                return $alreadyCreated;
            }

            // ── حساب الأوقات الجديدة ──
            $times = $this->calculateTripTimes($parent, $targetDate);

            // ── حساب available_seats الابتدائي ──
            $initialAvailableSeats = $this->calculateInitialAvailableSeats($parent);

            // ── Layer 2: DB-level (Unique Constraint + Layer 3: errno 1062 fallback) ──
            try {
                $clone = Trip::create($this->buildChildAttributes(
                    $parent,
                    $times['check_in'],
                    $times['departure'],
                    $times['arrival'],
                    $initialAvailableSeats
                ));
            } catch (\Illuminate\Database\QueryException $e) {
                // errno 1062 = Duplicate entry (MySQL/MariaDB unique constraint)
                if (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062) {
                    Log::warning('TripChildFactory: Duplicate entry caught (errno 1062), fetching existing', [
                        'parent_id' => $parent->id,
                        'target_date' => $targetDate->toDateString(),
                    ]);

                    $alreadyCreated = Trip::where('parent_id', $parent->id)
                        ->whereDate('departure_time', $targetDate)
                        ->lockForUpdate()
                        ->first();

                    if ($alreadyCreated) {
                        return $alreadyCreated;
                    }
                }
                throw $e;
            }

            // ── نسخ العلاقات ──
            $this->copyRelationships($parent, $clone);

            Log::info('TripChildFactory: Child trip created', [
                'parent_id' => $parent->id,
                'child_id' => $clone->id,
                'child_uuid' => $clone->uuid,
                'target_date' => $targetDate->toDateString(),
            ]);

            return $clone;
        });
    }

    /**
     * حساب الأوقات الجديدة مع الحفاظ على إزاحة Parent.
     */
    private function calculateTripTimes(Trip $parent, Carbon $targetDate): array
    {
        $baseCheckIn = Carbon::parse($parent->check_in_time);
        $baseDeparture = Carbon::parse($parent->departure_time);
        $baseArrival = Carbon::parse($parent->arrival_time);

        $newDeparture = $targetDate->copy()->setTime(
            $baseDeparture->hour,
            $baseDeparture->minute,
            $baseDeparture->second
        );

        $newCheckIn = $newDeparture->copy()->subSeconds(
            $baseDeparture->diffInSeconds($baseCheckIn, false) * -1
        );

        $newArrival = $newDeparture->copy()->addSeconds(
            $baseDeparture->diffInSeconds($baseArrival)
        );

        return [
            'check_in'  => $newCheckIn,
            'departure' => $newDeparture,
            'arrival'   => $newArrival,
        ];
    }

    /**
     * حساب available_seats الابتدائي للطفل.
     *
     * لا نأخذ القيمة من parent.available_seats لأنها قد تكون نُقصت
     * من حجوزات سابقة على تاريخ آخر.
     *
     * القاعدة:
     *   إذا ticket_limit محدد → min(ticket_limit, vehicle seats)
     *   إذا لم يُحدد → vehicle seats (الكامل)
     */
    private function calculateInitialAvailableSeats(Trip $parent): int
    {
        $vehicleCapacity = $parent->vehicle ? (int) $parent->vehicle->seats_count : 0;

        return ($parent->ticket_limit !== null)
            ? min((int) $parent->ticket_limit, $vehicleCapacity)
            : $vehicleCapacity;
    }

    /**
     * بناء مصفوفة الحقول المنسوخة من Parent إلى Child.
     */
    private function buildChildAttributes(
        Trip $parent,
        Carbon $newCheckIn,
        Carbon $newDeparture,
        Carbon $newArrival,
        int $initialAvailableSeats
    ): array {
        return [
            // ── الهوية والانتماء ──
            'uuid'                => (string) Str::uuid(),
            'created_by_user'     => $parent->created_by_user,
            'service_id'          => $parent->service_id,
            'parent_id'           => $parent->id,
            'trip_name_id'        => Trip::generateTripNameId($parent->trip_name_id ?: 'TR'),

            // ── المركبة والمستوى ──
            'vehicle_id'          => $parent->vehicle_id,
            'type_of_trip_id'     => $parent->type_of_trip_id,
            'level_trip_id'       => $parent->level_trip_id,

            // ── الرؤية والحالة ──
            'is_public'           => $parent->is_public,
            'trip_status_id'      => $parent->trip_status_id,

            // ── المسار ──
            'departure_city'      => $parent->departure_city,
            'destination_city'    => $parent->destination_city,
            'pickup_location_id'  => $parent->pickup_location_id,
            'arrival_location_id' => $parent->arrival_location_id,

            // ── الأوقات (معاد حسابها) ──
            'check_in_time'       => $newCheckIn,
            'departure_time'      => $newDeparture,
            'arrival_time'        => $newArrival,

            // ── التسعير والعملة ──
            'price_for_adult'     => $parent->price_for_adult,
            'price_for_child'     => $parent->price_for_child,
            'currency_id'         => $parent->currency_id,
            'discount_percentage' => $parent->discount_percentage,

            // ── المقاعد ──
            'ticket_limit'        => $parent->ticket_limit,
            'available_seats'     => $initialAvailableSeats,

            // ── أخرى ──
            'conditions'          => $parent->conditions,
            'image'               => $parent->image,
        ];
    }

    /**
     * نسخ العلاقات من Parent إلى Child.
     *
     * 1. Amenities — نسخ pivot records
     * 2. Policies — مرجع ديناميكي عبر trip_policies (sync policy_id فقط)
     * 3. Coupons — مرجع ديناميكي عبر coupon_trips (sync coupon_id فقط)
     */
    private function copyRelationships(Trip $parent, Trip $child): void
    {
        // 1. وسائل الراحة (Amenities)
        foreach ($parent->tripAmenities as $amenity) {
            $child->tripAmenities()->create([
                'amenity_id' => $amenity->amenity_id,
            ]);
        }

        // 2. السياسات (Policies)
        $policyIds = $parent->policies()->pluck('policies.id')->toArray();
        if (!empty($policyIds)) {
            $child->policies()->sync($policyIds);
        }

        // 3. الكوبونات (Coupons)
        $couponIds = $parent->coupons()->pluck('coupons.id')->toArray();
        if (!empty($couponIds)) {
            $child->coupons()->sync($couponIds);
        }
    }
}
