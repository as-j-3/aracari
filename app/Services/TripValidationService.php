<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\TripReservation;
use Illuminate\Support\Facades\DB;
use App\Constants\NumericConstants;
use App\Constants\BookingStatus as BookingStatusConst;
use App\Constants\TripStatus as TripStatusConst;

class TripValidationService
{
    /**
     * Validate trip creation or modification data
     * 
     * This method validates trip data for creation or modification,
     * ensuring business rules and data integrity.
     * 
     * Business logic:
     * - Departure and destination cities must be different
     * - Arrival time must be after departure time
     * - Check-in time must be before departure time
     * - Check-in time cannot be too early (max 3 hours before)
     * - Prices must be positive (adult) or non-negative (child)
     * - Requested seats cannot exceed vehicle capacity
     * 
     * @param array $tripData Trip data to validate
     * @return array Validation result with validity status and error messages
     */
    public function validateTripCreation(array $tripData): array
    {
        $errors = [];
        
        // 1. التحقق من أن نقطة الانطلاق ليست نفسها نقطة الوصول
        if (isset($tripData['departure_city']) && isset($tripData['destination_city'])) {
            if ($tripData['departure_city'] == $tripData['destination_city']) {
                $errors[] = 'نقطة الانطلاق لا يمكن أن تكون نفسها نقطة الوصول';
            }
        }
        
        // 2. التحقق من أن وقت الوصول بعد وقت الانطلاق
        if (isset($tripData['departure_time']) && isset($tripData['arrival_time'])) {
            $departure = \Carbon\Carbon::parse($tripData['departure_time']);
            $arrival = \Carbon\Carbon::parse($tripData['arrival_time']);
            
            if ($arrival->lte($departure)) {
                $errors[] = 'وقت الوصول يجب أن يكون بعد وقت الانطلاق';
            }
            
            // التحقق من أن وقت Check-in قبل وقت الانطلاق
            if (isset($tripData['check_in_time'])) {
                $checkIn = \Carbon\Carbon::parse($tripData['check_in_time']);
                if ($checkIn->gte($departure)) {
                    $errors[] = 'وقت Check-in يجب أن يكون قبل وقت الانطلاق';
                }
                
                // التحقق من أن وقت Check-in ليس مبكراً جداً
                $timeDiff = $departure->diffInMinutes($checkIn);
                if ($timeDiff > NumericConstants::TIME_LIMIT_THREE_HOURS) { // أكثر من 3 ساعات
                    $errors[] = 'وقت Check-in مبكر جداً عن وقت الانطلاق';
                }
            }
        }
        
        // 3. التحقق من أن الأسعار موجبة
        if (isset($tripData['price_for_adult']) && $tripData['price_for_adult'] <= 0) {
            $errors[] = 'سعر البالغ يجب أن يكون موجباً';
        }
        
        if (isset($tripData['price_for_child']) && $tripData['price_for_child'] < 0) {
            $errors[] = 'سعر الطفل لا يمكن أن يكون سالباً';
        }
        
        // 4. التحقق من أن عدد المقاعد المطلوب لا يتجاوز سعة المركبة
        if (isset($tripData['vehicle_id']) && isset($tripData['requested_seats'])) {
            $vehicle = \App\Models\Vehicle::find($tripData['vehicle_id']);
            if ($vehicle && $tripData['requested_seats'] > $vehicle->seats()->count()) {
                $errors[] = 'عدد المقاعد المطلوب لا يمكن أن يتجاوز سعة المركبة';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
    
    /**
     * Validate trip modification or cancellation feasibility
     * 
     * This method checks if a trip can be modified or cancelled based on
     * active bookings and time constraints.
     * 
     * Business logic:
     * - Checks for active bookings and passenger count
     * - Validates departure time changes (minimum 2 hours from now)
     * - Prevents route changes if passengers exist
     * - Prevents vehicle changes if new capacity is insufficient
     * - Prevents modification if trip has already started
     * 
     * @param Trip $trip Trip to validate
     * @param array $newData New trip data for modification
     * @return array Validation result with modification feasibility, warnings, and errors
     */
    public function validateTripModification(Trip $trip, array $newData = []): array
    {
        $warnings = [];
        $errors = [];
        
        // التحقق من وجود حجوزات نشطة
        $activeBookings = TripReservation::where('trip_id', $trip->id)
            ->whereHas('booking', function ($query) {
                $query->where('booking_status_id', '!=', BookingStatusConst::CANCELLED);
            })
            ->withCount('tickets')
            ->get();
        
        $totalPassengers = $activeBookings->sum('tickets_count');
        
        if ($totalPassengers > 0) {
            $warnings[] = "يوجد {$totalPassengers} مسافر حاجزين في هذه الرحلة";
            
            // التحقق من أن التعديل لا يؤثر على المسافرين
            if (isset($newData['departure_time'])) {
                $newDeparture = \Carbon\Carbon::parse($newData['departure_time']);
                $oldDeparture = \Carbon\Carbon::parse($trip->departure_time);
                
                if ($newDeparture->lt(now()->addHours(2))) {
                    $errors[] = 'لا يمكن تعديل الرحلة إلى وقت أقل من ساعتين من الآن';
                }
                
                if ($newDeparture->lt($oldDeparture)) {
                    $warnings[] = 'تقديم وقت الرحلة قد يؤثر على المسافرين الحاليين';
                }
            }
            
            // التحقق من تغيير المسار
            if (isset($newData['departure_city']) && $newData['departure_city'] != $trip->departure_city) {
                $errors[] = 'لا يمكن تغيير مدينة الانطلاق لوجود مسافرين';
            }
            
            if (isset($newData['destination_city']) && $newData['destination_city'] != $trip->destination_city) {
                $errors[] = 'لا يمكن تغيير وجهة الرحلة لوجود مسافرين';
            }
            
            // التحقق من تقليل عدد مقاعد المركبة
            if (isset($newData['vehicle_id']) && $newData['vehicle_id'] != $trip->vehicle_id) {
                $newVehicle = \App\Models\Vehicle::find($newData['vehicle_id']);
                if ($newVehicle) {
                    $bookedSeats = count($trip->getBookedSeatIds());
                    if ($newVehicle->seats()->count() < $bookedSeats) {
                        $errors[] = 'لا يمكن تغيير المركبة لأن المقاعد المحجوزة أكثر من مقاعد المركبة الجديدة';
                    }
                }
            }
        }
        
        // التحقق من أن الرحلة لم تبدأ بعد
        if ($trip->departure_time <= now()) {
            $errors[] = 'لا يمكن تعديل رحلة قد بدأت أو انتهت';
        }
        
        return [
            'can_modify' => empty($errors),
            'warnings' => $warnings,
            'errors' => $errors,
            'affected_passengers' => $totalPassengers,
        ];
    }
    
    /**
     * Create notifications for affected passengers after trip modification
     * 
     * This method sends notifications to all passengers affected by
     * trip modifications to keep them informed of changes.
     * 
     * Business logic:
     * - Identifies all affected passengers from active bookings
     * - Creates notification records for each user
     * - Logs modification details for audit trail
     * - Supports future push notification integration
     * 
     * @param Trip $trip Modified trip
     * @param array $changes Array of changes made to the trip
     * @param int $modifiedBy ID of the user who modified the trip
     * @return void
     */
    public function createPassengerNotifications(Trip $trip, array $changes, int $modifiedBy): void
    {
        $passengers = TripReservation::where('trip_id', $trip->id)
            ->whereHas('booking', function ($query) {
                $query->where('booking_status_id', '!=', BookingStatusConst::CANCELLED);
            })
            ->with('booking.user')
            ->get()
            ->pluck('booking.user')
            ->filter()
            ->unique('id');
        
        foreach ($passengers as $user) {
            // إنشاء إشعار للمستخدم
            \App\Models\Notification::create([
                'user_id' => $user->id,
                'title' => 'تم تعديل رحلتك',
                'message' => "تم تعديل الرحلة {$trip->uuid}. يرجى مراجعة التفاصيل.",
                'type' => 'trip_modification',
                'data' => json_encode([
                    'trip_uuid' => $trip->uuid,
                    'changes' => $changes,
                    'modified_by' => $modifiedBy,
                ]),
                'read_at' => null,
            ]);
            
            // إرسال إشعار فوري إذا كان ممكناً
            // $this->notificationService->sendPushNotification($user, $notification);
        }
        
        // تسجيل التعديل
        \Log::info('Trip modified with passenger notifications', [
            'trip_id' => $trip->id,
            'trip_uuid' => $trip->uuid,
            'changes' => $changes,
            'affected_passengers' => $passengers->count(),
            'modified_by' => $modifiedBy,
        ]);
    }
    
    /**
     * Validate vehicle availability to prevent scheduling conflicts
     * 
     * This method checks if a vehicle is available for a new trip
     * by detecting time conflicts with existing trips.
     * 
     * Business logic:
     * - Detects overlapping trip schedules
     * - Excludes current trip from conflict check (for modifications)
     * - Supports three conflict scenarios:
     *   - New trip starts during existing trip
     *   - New trip ends during existing trip
     *   - New trip fully contains existing trip
     * 
     * @param array $tripData Trip data including vehicle_id and times
     * @param int|null $excludeTripId Trip ID to exclude from conflict check
     * @return array Validation result with availability status and conflict message
     */
    public function validateVehicleAvailability(array $tripData, ?int $excludeTripId = null): array
    {
        if (!isset($tripData['vehicle_id']) || !isset($tripData['departure_time']) || !isset($tripData['arrival_time'])) {
            return ['valid' => true];
        }
        
        $conflictingTrips = Trip::where('vehicle_id', $tripData['vehicle_id'])
            ->where('trip_status_id', '!=', TripStatusConst::CANCELLED)
            ->when($excludeTripId, function ($query, $excludeTripId) {
                return $query->where('id', '!=', $excludeTripId);
            })
            ->where(function ($query) use ($tripData) {
                $newDeparture = \Carbon\Carbon::parse($tripData['departure_time']);
                $newArrival = \Carbon\Carbon::parse($tripData['arrival_time']);
                
                $query->where(function ($q) use ($newDeparture, $newArrival) {
                    // الرحلة الحالية تبدأ خلال رحلة أخرى
                    $q->where('departure_time', '<=', $newDeparture)
                      ->where('arrival_time', '>=', $newDeparture);
                })->orWhere(function ($q) use ($newDeparture, $newArrival) {
                    // الرحلة الحالية تنتهي خلال رحلة أخرى
                    $q->where('departure_time', '<=', $newArrival)
                      ->where('arrival_time', '>=', $newArrival);
                })->orWhere(function ($q) use ($newDeparture, $newArrival) {
                    // الرحلة الحالية تغطي رحلة أخرى بالكامل
                    $q->where('departure_time', '>=', $newDeparture)
                      ->where('arrival_time', '<=', $newArrival);
                });
            })
            ->exists();
        
        if ($conflictingTrips) {
            return [
                'valid' => false,
                'message' => 'المركبة محجوزة في رحلة أخرى خلال نفس الفترة الزمنية',
            ];
        }
        
        return ['valid' => true];
    }

    /**
     * Validate duplicate trip for same provider
     * 
     * This method checks if the provider already has a trip with the same
     * route (departure_city, destination_city) at the same time.
     * 
     * Business logic:
     * - Checks for trips with same provider, same cities, similar time
     * - Uses 30-minute buffer to detect near-duplicate trips
     * - Excludes cancelled trips
     * 
     * @param array $tripData Trip data including created_by_user, cities, and departure_time
     * @param int|null $excludeTripId Trip ID to exclude from check (for updates)
     * @return array Validation result with success flag and message
     */
    public function validateDuplicateTrip(array $tripData, ?int $excludeTripId = null): array
    {
        if (!isset($tripData['created_by_user']) || !isset($tripData['departure_city']) || 
            !isset($tripData['destination_city']) || !isset($tripData['departure_time'])) {
            return ['success' => true];
        }
        
        $query = Trip::where('created_by_user', $tripData['created_by_user'])
            ->where('departure_city', $tripData['departure_city'])
            ->where('destination_city', $tripData['destination_city'])
            ->where('trip_status_id', '!=', TripStatusConst::CANCELLED)
            //  Exclude soft deleted trips from duplicate check
            ->whereNull('deleted_at')
            ->where(function ($q) use ($tripData) {
                $newDeparture = \Carbon\Carbon::parse($tripData['departure_time']);
                $timeBuffer = 30; // 30 minutes buffer
                
                // Check for trips within 30 minutes of the new departure time
                $q->whereBetween('departure_time', [
                    $newDeparture->copy()->subMinutes($timeBuffer),
                    $newDeparture->copy()->addMinutes($timeBuffer)
                ]);
            });
        
        if ($excludeTripId) {
            $query->where('id', '!=', $excludeTripId);
        }
        
        $existingTrip = $query->first();
        
        if ($existingTrip) {
            return [
                'success' => false,
                'message' => 'يوجد رحلة أخرى لنفس المزود بين نفس المدن في نفس الوقت تقريباً (رحلة: ' . $existingTrip->uuid . ')'
            ];
        }
        
        return ['success' => true];
    }

    /**
     * Validate vehicle conflict with standardized return format
     * 
     * Wrapper around validateVehicleAvailability with success/message format
     * 
     * @param int $vehicleId Vehicle ID
     * @param string $departureTime Departure time
     * @param string $arrivalTime Arrival time
     * @param int|null $excludeTripId Trip ID to exclude
     * @return array Validation result with success flag and message
     */
    public function validateVehicleConflict(int $vehicleId, string $departureTime, string $arrivalTime, ?int $excludeTripId = null): array
    {
        $result = $this->validateVehicleAvailability([
            'vehicle_id' => $vehicleId,
            'departure_time' => $departureTime,
            'arrival_time' => $arrivalTime
        ], $excludeTripId);

        if (!$result['valid']) {
            return [
                'success' => false,
                'message' => $result['message'] ?? 'الباص محجوز لرحلة أخرى خلال نفس الفترة الزمنية'
            ];
        }

        return ['success' => true];
    }
}
