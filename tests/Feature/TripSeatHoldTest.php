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

    /** العميل A يستطيع عمل Hold للمقعد لمدة 5 دقائق. */
    public function test_user_can_hold_a_seat_for_five_minutes(): void
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

        $this->assertSame(TripSeatStatus::HELD, (int) $tripSeat->trip_seat_status_id);
        $this->assertSame($user->id, (int) $tripSeat->user_id);
        $this->assertNotNull($tripSeat->reserved_until);

        $this->assertTrue($tripSeat->reserved_until->between(
            now()->addMinutes(4)->addSeconds(50),
            now()->addMinutes(5)->addSeconds(10)
        ));
    }

    /** العميل B لا يستطيع أخذ مقعد Held للعميل A قبل انتهاء الـ 5 دقائق. */
    public function test_second_user_cannot_hold_a_seat_held_by_another_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        [$trip, $seat] = $this->createTripWithSeat();

        $this->actingAs($userA, 'sanctum')->postJson(
            "/api/trips/{$trip->uuid}/hold-seats",
            ['seat_ids' => [$seat->id]]
        )->assertSuccessful();

        $response = $this->actingAs($userB, 'sanctum')->postJson(
            "/api/trips/{$trip->uuid}/hold-seats",
            ['seat_ids' => [$seat->id]]
        );

        $response->assertStatus(422);

        $tripSeat = TripSeat::where('trip_id', $trip->id)
            ->where('seat_id', $seat->id)
            ->firstOrFail();

        $this->assertSame($userA->id, (int) $tripSeat->user_id);
        $this->assertSame(TripSeatStatus::HELD, (int) $tripSeat->trip_seat_status_id);
    }

    /** العميل نفسه يستطيع الاستمرار في الحجز خلال فترة الـ Hold. */
    public function test_same_user_can_continue_with_his_active_hold(): void
    {
        $user = User::factory()->create();
        [$trip, $seat] = $this->createTripWithSeat();

        $this->actingAs($user, 'sanctum')->postJson(
            "/api/trips/{$trip->uuid}/hold-seats",
            ['seat_ids' => [$seat->id]]
        )->assertSuccessful();

        $response = $this->actingAs($user, 'sanctum')->postJson(
            "/api/trips/{$trip->uuid}/hold-seats",
            ['seat_ids' => [$seat->id]]
        );

        $response->assertSuccessful();
    }

    /** بعد انتهاء الـ Hold يستطيع مستخدم آخر أخذ المقعد. */
    public function test_expired_hold_can_be_taken_by_another_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        [$trip, $seat] = $this->createTripWithSeat();

        $this->actingAs($userA, 'sanctum')->postJson(
            "/api/trips/{$trip->uuid}/hold-seats",
            ['seat_ids' => [$seat->id]]
        )->assertSuccessful();

        Carbon::setTestNow(now()->addMinutes(6));

        $response = $this->actingAs($userB, 'sanctum')->postJson(
            "/api/trips/{$trip->uuid}/hold-seats",
            ['seat_ids' => [$seat->id]]
        );

        $response->assertSuccessful();

        $tripSeat = TripSeat::where('trip_id', $trip->id)
            ->where('seat_id', $seat->id)
            ->firstOrFail();

        $this->assertSame(TripSeatStatus::HELD, (int) $tripSeat->trip_seat_status_id);
        $this->assertSame($userB->id, (int) $tripSeat->user_id);

        Carbon::setTestNow();
    }

    /** المقعد المحجوز BOOKED لا يمكن عمل Hold له. */
    public function test_booked_seat_cannot_be_held(): void
    {
        $user = User::factory()->create();
        [$trip, $seat] = $this->createTripWithSeat();

        TripSeat::create([
            'trip_id' => $trip->id,
            'seat_id' => $seat->id,
            'trip_seat_status_id' => TripSeatStatus::BOOKED,
            'booking_id' => null,
            'user_id' => $user->id,
            'reserved_until' => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson(
            "/api/trips/{$trip->uuid}/hold-seats",
            ['seat_ids' => [$seat->id]]
        );

        $response->assertStatus(422);
    }

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
