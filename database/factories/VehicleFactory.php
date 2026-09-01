<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        DB::table('vehicle_types')->insertOrIgnore([
            'id' => 1,
            'uuid' => (string) Str::uuid(),
            'type_name' => 'حافلة VIP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'uuid' => (string) Str::uuid(),
            'name' => 'حافلة الاختبار',
            'user_id' => User::factory(),
            'vehicle_type_id' => 1,
            'model' => 'Mercedes 2024',
            'seats_count' => 40,
            'status' => 'active',
        ];
    }
}
