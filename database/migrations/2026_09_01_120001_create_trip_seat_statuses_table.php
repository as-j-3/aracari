<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trip_seat_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('status_name', 50)->unique();
            $table->timestamps();
        });

        // زراعة الحالات الأساسية (1: available, 2: held, 3: booked, 4: blocked)
        DB::table('trip_seat_statuses')->insert([
            ['id' => 1, 'status_name' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'status_name' => 'held', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'status_name' => 'booked', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'status_name' => 'blocked', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_seat_statuses');
    }
};
