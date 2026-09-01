<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Country;
use App\Models\LevelTrip;
use App\Models\ProviderBranch;
use App\Models\ProviderService;
use App\Models\Service;
use App\Models\Trip;
use App\Models\TripStatus;
use App\Models\TypeOfService;
use App\Models\TypeOfTrip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trip>
 */
class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition(): array
    {
        $country = Country::firstOrCreate(['id' => 1], ['name' => 'اليمن']);
        $depCity = City::firstOrCreate(['id' => 1], ['country_id' => $country->id, 'name' => 'صنعاء']);
        $destCity = City::firstOrCreate(['id' => 2], ['country_id' => $country->id, 'name' => 'عدن']);

        $typeOfTrip = TypeOfTrip::firstOrCreate(['id' => 1], ['type_name' => 'ذهاب فقط']);
        $levelTrip = LevelTrip::firstOrCreate(['id' => 1], ['level_name' => 'فاخر']);
        $tripStatus = TripStatus::firstOrCreate(['id' => 1], ['status_name' => 'مجدولة']);

        $serviceId = $this->createDefaultService();

        return [
            'uuid' => (string) Str::uuid(),
            'created_by_user' => User::factory(),
            'service_id' => $serviceId,
            'type_of_trip_id' => $typeOfTrip->id,
            'level_trip_id' => $levelTrip->id,
            'vehicle_id' => Vehicle::factory(),
            'departure_city' => $depCity->id,
            'destination_city' => $destCity->id,
            'pickup_location' => 'محطة صنعاء المركزية',
            'check_in_time' => now()->addHour(),
            'departure_time' => now()->addHours(2),
            'arrival_time' => now()->addHours(10),
            'price_for_adult' => 100.00,
            'price_for_child' => 50.00,
            'ticket_limit' => 40,
            'available_seats' => 40,
            'is_public' => true,
            'trip_status_id' => $tripStatus->id,
        ];
    }

    private function createDefaultService(): int
    {
        $typeOfService = TypeOfService::firstOrCreate(['id' => 1], ['type_name' => 'نقل بري']);
        
        $providerUser = User::factory()->create();
        
        $providerService = ProviderService::firstOrCreate(
            ['user_id' => $providerUser->id],
            [
                'uuid' => (string) Str::uuid(),
                'type_of_service_id' => $typeOfService->id,
                'name' => 'شركة النقل الوطنية',
                'cr_number' => 'CR-' . Str::random(8),
            ]
        );

        $branch = ProviderBranch::firstOrCreate(
            ['provider_id' => $providerService->id],
            [
                'uuid' => (string) Str::uuid(),
                'address' => 'صنعاء الرئيسي',
            ]
        );

        $service = Service::firstOrCreate(
            ['branch_id' => $branch->id]
        );

        return $service->id;
    }
}
