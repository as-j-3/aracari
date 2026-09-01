<?php

namespace App\Services;

use App\Constants\TripStatus;
use App\Models\Trip;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;

class ReturnTripSearchService
{

    private const MAX_RESULTS = 30;


    private const DAYOFWEEK_MAP = "'sunday','monday','tuesday','wednesday','thursday','friday','saturday'";


    private const EAGER_LOADS = [
        'departureCity:id,name',
        'destinationCity:id,name',
        'vehicle.vehicleType:id,type_name',
        'user.providerService:id,name,brand_photo',
    ];


    public function search(Trip $outboundTrip, Carbon $targetDate): Collection
    {
        $minDeparture = $outboundTrip->arrival_time;
        $isSameDay = $targetDate->eq(Carbon::parse($outboundTrip->arrival_time)->startOfDay());

        return Trip::with(self::EAGER_LOADS)
            ->where('is_public', true)
            ->whereIn('trip_status_id', [TripStatus::SCHEDULED])
            ->where('departure_city', $outboundTrip->destination_city)
            ->where('destination_city', $outboundTrip->departure_city)
            ->where('available_seats', '>', 0)
            ->whereDate('departure_time', $targetDate)
            ->when($isSameDay, fn ($q) => $q->where('departure_time', '>', $minDeparture))
            ->where(fn ($q) => $this->instanceClassificationFilter($q))
            ->orderBy('departure_time')
            ->limit(self::MAX_RESULTS)
            ->get();
    }


    public function minSearchDate(Trip $outboundTrip): string
    {
        return Carbon::parse($outboundTrip->arrival_time)->startOfDay()->toDateString();
    }

    private function instanceClassificationFilter(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->where(function (\Illuminate\Database\Eloquent\Builder $q) {
            $q->whereNull('parent_id')
              ->whereDoesntHave('repeats', fn ($rq) => $rq->where('status', 'active'))

              ->orWhere(function (\Illuminate\Database\Eloquent\Builder $oq) {
                  $oq->whereNotNull('parent_id')
                      ->whereHas('parentTrip.repeats', function (\Illuminate\Database\Eloquent\Builder $rq) {
                          $rq->where('status', 'active')
                             ->whereRaw('trip_repeats.start_date <= DATE(trips.departure_time)')
                             ->whereRaw('trip_repeats.end_date >= DATE(trips.departure_time)')
                             ->whereHas('repeatedDays', function (\Illuminate\Database\Eloquent\Builder $dq) {
                                 $dq->whereRaw(
                                     'repeated_trip_days.day_of_week = ELT(DAYOFWEEK(trips.departure_time), ' . self::DAYOFWEEK_MAP . ')'
                                 );
                             });
                      });
              });
        });
    }
}
