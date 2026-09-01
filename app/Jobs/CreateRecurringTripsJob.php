<?php

namespace App\Jobs;

use App\Models\TripRepeat;
use App\Services\TripChildFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;


class CreateRecurringTripsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $tripRepeatId,
        public string $targetDate
    ) {
    }

    public function handle(TripChildFactory $childFactory): void
    {
        $repeat = TripRepeat::with(['trip.tripAmenities', 'trip.policies', 'trip.coupons', 'repeatedDays'])
            ->find($this->tripRepeatId);

        if (!$repeat || !$repeat->trip || $repeat->status !== 'active') {
            return;
        }

        $trip = $repeat->trip;
        $targetDate = Carbon::parse($this->targetDate)->startOfDay();

        if ($targetDate->lt($repeat->start_date) || $targetDate->gt($repeat->end_date)) {
            return;
        }

        $childFactory->createChildTrip($trip, $targetDate);
    }
}
