<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        // ضمان وجود نوع المستخدم وحالته في قاعدة البيانات مسبقاً
        DB::table('type_of_users')->insertOrIgnore([
            'id' => 1,
            'type_name' => 'عميل',
            'type_user_code' => 'customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_statuses')->insertOrIgnore([
            'id' => 1,
            'status_name' => 'مفعل',
            'status_code' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'uuid' => (string) Str::uuid(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone_number' => fake()->unique()->numerify('96777#######'),
            'password' => bcrypt('password'),
            'type_user_code' => 1,
            'status_id' => 1,
            'is_approved' => true,
        ];
    }
}
