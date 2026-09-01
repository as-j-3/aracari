<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('trip_name_id', 20)->nullable();

            $table->foreignId('parent_id')->nullable()->constrained('trips')->nullOnDelete();
            $table->foreignId('created_by_user')->constrained('users')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services')->onDelete('restrict');

            $table->foreignId('type_of_trip_id')->constrained('type_of_trips')->onDelete('restrict');
            $table->foreignId('level_trip_id')->constrained('level_trips')->onDelete('restrict');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('restrict');

            $table->foreignId('departure_city')->constrained('cities')->onDelete('restrict');
            $table->foreignId('destination_city')->constrained('cities')->onDelete('restrict');
            $table->string('pickup_location', 500);
            
            $table->foreignId('pickup_location_id')->nullable()->constrained('city_stations')->onDelete('set null');
            $table->foreignId('arrival_location_id')->nullable()->constrained('city_stations')->onDelete('set null');

            $table->datetime('check_in_time');
            $table->datetime('departure_time');
            $table->datetime('arrival_time');

            $table->decimal('price_for_adult', 10, 2);
            $table->decimal('price_for_child', 10, 2);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('set null');
            $table->decimal('discount_percentage', 5, 2)->nullable()->default(0);
            
            $table->integer('ticket_limit')->nullable();
            $table->integer('available_seats')->notNull()->default(0);

            $table->boolean('is_public')->default(true);
            $table->foreignId('trip_status_id')->constrained('trip_statuses')->onDelete('restrict');
            $table->text('conditions')->nullable();
            $table->string('image', 500)->nullable();

            $table->date('departure_date')
                ->nullable()
                ->storedAs('DATE(departure_time)')
                ->comment('Generated: DATE(departure_time) — for unique constraint');

            $table->softDeletes();
            $table->timestamps();
            
            $table->index(['created_by_user', 'trip_status_id']);
            $table->index(['departure_time', 'trip_status_id']);
            $table->index(['departure_city', 'destination_city']);
            $table->index('trip_name_id');
            
            $table->index(['departure_city', 'destination_city', 'departure_time', 'is_public'], 'idx_trips_search');
            $table->index(['departure_time', 'is_public'], 'idx_trips_departure_public');
            $table->index(['trip_status_id'], 'idx_trips_status');
            $table->index(['is_public', 'departure_time'], 'idx_trips_public_departure');

            $table->unique(['parent_id', 'departure_date'], 'trips_parent_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};