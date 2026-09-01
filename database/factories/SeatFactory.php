<?php

namespace Database\Factories;

use App\Models\Seat;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Seat>
 */
class SeatFactory extends Factory
{
    protected $model = Seat::class;

    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'seat_name' => 'A' . fake()->numberBetween(1, 50),
            'status' => 'available',
        ];
    }
}
