<?php

namespace Tests\Feature;

use App\Constants\TripSeatStatus;
use App\Models\Seat;
use App\Models\Trip;
use App\Models\TripSeat;
use App\Models\TripSeatStatus as TripSeatStatusModel;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class TripSeatHoldTest extends TestCase
{
    use RefreshDatabase;

    /** العميل A يستطيع عمل Hold للمقعد لمدة دقيقتين. */
    public function test_user_can_hold_a_seat_for_two_minutes(): void
    {
        $user = User::factory()->create();
        [$trip, $seat] = $this->createTripWithSeat();

        $response = $this->actingAs($user, 'sanctum')->postJson(
            "/api/trips/{$trip->uuid}/hold-seats",
            ['seat_ids' => [$seat->id]]
        );

        $response->assertSuccessful();

        $tripSeat = TripSeat::where('trip_id', $trip->id)
            ->where('seat_id', $seat->id)
            ->firstOrFail();

        // التأكد أن حالة المقعد HELD
        $this->assertSame(
            TripSeatStatus::HELD,
            (int) $tripSeat->trip_seat_status_id
        );

        // التأكد أن المقعد مرتبط بالمستخدم
        $this->assertSame(
            $user->id,
            (int) $tripSeat->user_id
        );

        // التأكد من وجود وقت انتهاء
        $this->assertNotNull($tripSeat->reserved_until);

        // التأكد أن مدة الـ Hold حوالي دقيقتين
        $this->assertTrue($tripSeat->reserved_until->between(
            now()->addMinutes(1)->addSeconds(50),
            now()->addMinutes(2)->addSeconds(10)
        ));
    }

    /** العميل B لا يستطيع أخذ مقعد Held للعميل A قبل انتهاء الدقيقتين. */
    public function test_second_user_cannot_hold_a_seat_held_by_another_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        [$trip, $seat] = $this->createTripWithSeat();

        // المستخدم A يمسك المقعد
        $this->actingAs($userA, 'sanctum')->postJson(
            "/api/trips/{$trip->uuid}/hold-seats",
            ['seat_ids' => [$seat->id]]
        )->assertSuccessful();

        // المستخدم B يحاول أخذ نفس المقعد قبل انتهاء الدقيقتين
        $response = $this->actingAs($userB, 'sanctum')->postJson(
            "/api/trips/{$trip->uuid}/hold-seats",
            ['seat_ids' => [$seat->id]]
        );

        // يجب رفض الطلب
        $response->assertStatus(422);

        // التأكد أن المقعد ما زال مع المستخدم A
        $tripSeat = TripSeat::where('trip_id', $trip->id)
            ->where('seat_id', $seat->id)
            ->firstOrFail();

        $this->assertSame(
            $userA->id,
            (int) $tripSeat->user_id
        );

        $this->assertSame(
            TripSeatStatus::HELD,
            (int) $tripSeat->trip_seat_status_id
        );
    }

    /** العميل نفسه يستطيع الاستمرار في الحجز خلال فترة الـ Hold. */
    public function test_same_user_can_continue_with_his_active_hold(): void
    {
        $user = User::factory()->create();

        [$trip, $seat] = $this->createTripWithSeat();

        // المستخدم يمسك المقعد
        $this->actingAs($user, 'sanctum')->postJson(
            "/api/trips/{$trip->uuid}/hold-seats",
            ['seat_ids' => [$seat->id]]
        )->assertSuccessful();

        // نفس المستخدم يحاول الاستمرار
        $response = $this->actingAs($user, 'sanctum')->postJson(
            "/api/trips/{$trip->uuid}/hold-seats",
            ['seat_ids' => [$seat->id]]
        );

        // يجب أن ينجح
        $response->assertSuccessful();
    }

    /** بعد انتهاء الدقيقتين يستطيع مستخدم آخر أخذ المقعد. */
    public function test_expired_hold_can_be_taken_by_another_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        [$trip, $seat] = $this->createTripWithSeat();

        // المستخدم A يمسك المقعد
        $this->actingAs($userA, 'sanctum')->postJson(
            "/api/trips/{$trip->uuid}/hold-seats",
            ['seat_ids' => [$seat->id]]
        )->assertSuccessful();

        // نحرك الوقت إلى بعد انتهاء الدقيقتين
        Carbon::setTestNow(
            now()->addMinutes(2)->addSecond()
        );

        // المستخدم B يحاول أخذ المقعد
        $response = $this->actingAs($userB, 'sanctum')->postJson(
            "/api/trips/{$trip->uuid}/hold-seats",
            ['seat_ids' => [$seat->id]]
        );

        // يجب أن ينجح لأن الـ Hold انتهى
        $response->assertSuccessful();

        $tripSeat = TripSeat::where('trip_id', $trip->id)
            ->where('seat_id', $seat->id)
            ->firstOrFail();

        // المقعد أصبح Held للمستخدم B
        $this->assertSame(
            TripSeatStatus::HELD,
            (int) $tripSeat->trip_seat_status_id
        );

        $this->assertSame(
            $userB->id,
            (int) $tripSeat->user_id
        );

        // إعادة الوقت للوضع الطبيعي
        Carbon::setTestNow();
    }

    /** المقعد المحجوز BOOKED لا يمكن عمل Hold له. */
    public function test_booked_seat_cannot_be_held(): void
    {
        $user = User::factory()->create();

        [$trip, $seat] = $this->createTripWithSeat();

        // إنشاء المقعد بحالة BOOKED
        TripSeat::create([
            'trip_id' => $trip->id,
            'seat_id' => $seat->id,
            'trip_seat_status_id' => TripSeatStatus::BOOKED,
            'booking_id' => null,
            'user_id' => $user->id,
            'reserved_until' => null,
        ]);

        // محاولة عمل Hold للمقعد المحجوز
        $response = $this->actingAs($user, 'sanctum')->postJson(
            "/api/trips/{$trip->uuid}/hold-seats",
            ['seat_ids' => [$seat->id]]
        );

        // يجب رفض الطلب
        $response->assertStatus(422);
    }

    /**
     * إنشاء رحلة ومقعد لاستخدامهما في الاختبارات.
     */
    private function createTripWithSeat(): array
    {
        $this->createStatuses();

        $vehicle = Vehicle::factory()->create();

        $seat = Seat::factory()->create([
            'vehicle_id' => $vehicle->id,
        ]);

        $trip = Trip::factory()->create([
            'uuid' => (string) Str::uuid(),
            'vehicle_id' => $vehicle->id,
            'departure_time' => now()->addHours(2),
        ]);

        return [$trip, $seat];
    }

    /**
     * إنشاء حالات مقاعد الرحلات.
     */
    private function createStatuses(): void
    {
        $statuses = [
            TripSeatStatus::AVAILABLE => 'available',
            TripSeatStatus::HELD => 'held',
            TripSeatStatus::BOOKED => 'booked',
            TripSeatStatus::BLOCKED => 'blocked',
        ];

        foreach ($statuses as $id => $name) {
            TripSeatStatusModel::firstOrCreate(
                ['id' => $id],
                ['status_name' => $name]
            );
        }
    }
}