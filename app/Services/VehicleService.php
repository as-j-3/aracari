<?php

namespace App\Services;

use App\Constants\BookingStatus;
use App\Constants\NumericConstants;
use App\Models\Vehicle;
use App\Models\Seat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class VehicleService
{
    public function createVehicleWithSeats(array $data): Vehicle
    {
        return DB::transaction(function() use ($data) {
            $vehicle = Vehicle::create([
                'name' => $data['name'],
                'vehicle_type_id' => $data['vehicle_type_id'],
                'model' => $data['model'],
                'outside_photo' => $data['outside_photo'] ?? null,
                'inside_photo' => $data['inside_photo'] ?? null,
                'seats_count' => $data['seats_count'],
                'status' => $data['status'],
                'user_id' => $data['user_id'] ?? auth()->id(),
                'uuid' => Str::uuid(),
            ]);
            
            $this->createSeatsForVehicle($vehicle, $data);
            
            Log::info('Vehicle created successfully', ['vehicle_id' => $vehicle->id]);
            return $vehicle;
        });
    }
    
    public function updateVehicleWithSeats(Vehicle $vehicle, array $data): Vehicle
    {
        return DB::transaction(function() use ($vehicle, $data) {
            $seatOption = $data['seat_option'] ?? NumericConstants::SEAT_OPTION_KEEP;

            if ($seatOption !== NumericConstants::SEAT_OPTION_KEEP) {
                $this->validateNoActiveBookings($vehicle);
            }

            $updateData = [
                'name' => $data['name'],
                'vehicle_type_id' => $data['vehicle_type_id'],
                'model' => $data['model'],
                'outside_photo' => $data['outside_photo'] ?? $vehicle->outside_photo,
                'inside_photo' => $data['inside_photo'] ?? $vehicle->inside_photo,
                'status' => $data['status'] ?? $vehicle->status,
            ];

            if ($seatOption !== NumericConstants::SEAT_OPTION_KEEP && isset($data['seats_count'])) {
                $updateData['seats_count'] = $data['seats_count'];
            }

            $vehicle->update($updateData);

            if ($seatOption !== NumericConstants::SEAT_OPTION_KEEP) {
                $this->updateVehicleSeats($vehicle, $data, $seatOption);
            }

            Log::info('Vehicle updated successfully', ['vehicle_id' => $vehicle->id]);
            return $vehicle->fresh()->load('seats');
        });
    }
    
    private function createSeatsForVehicle(Vehicle $vehicle, array $data): void
    {
        $genType = $data['seat_generation_type'] ?? NumericConstants::SEAT_GEN_AUTO;
        
        if ($genType === NumericConstants::SEAT_GEN_AUTO) {
            $prefix = $data['seat_prefix'] ?? 'seat';
            $this->createAutoSeats($vehicle, $prefix);
        } else {
            $this->createManualSeats($vehicle, $data['seat_names'] ?? []);
        }
    }
    
    private function createAutoSeats(Vehicle $vehicle, ?string $prefix = null): void
    {
        $seatPrefix = $prefix ?? 'seat';
        $seats = [];

        if ($vehicle->seats_count > 0) {
            for ($i = 1; $i <= $vehicle->seats_count; $i++) {
                $seats[] = [
                    'vehicle_id' => $vehicle->id,
                    'seat_name' => $seatPrefix . $i,
                    'status' => NumericConstants::SEAT_STATUS_ACTIVE,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            Seat::insert($seats);
        }
    }
    
    private function createManualSeats(Vehicle $vehicle, array $seatNames): void
    {
        $this->validateSeatNames($seatNames, $vehicle->seats_count);
        
        $seats = array_map(fn($name) => [
            'vehicle_id' => $vehicle->id,
            'seat_name' => trim($name),
            'status' => NumericConstants::SEAT_STATUS_ACTIVE,
            'created_at' => now(),
            'updated_at' => now(),
        ], $seatNames);
        
        if (!empty($seats)) {
            Seat::insert($seats);
        }
    }
    
    private function updateVehicleSeats(Vehicle $vehicle, array $data, string $seatOption): void
    {
        // أرشفة المقاعد القديمة
        $vehicle->seats()->where('status', NumericConstants::SEAT_STATUS_ACTIVE)
            ->update(['status' => NumericConstants::SEAT_STATUS_ARCHIVED, 'updated_at' => now()]);

        // إنشاء مقاعد جديدة
        if ($seatOption === NumericConstants::SEAT_OPTION_REGENERATE) {
            $prefix = $data['seat_prefix'] ?? 'seat';
            $this->createAutoSeats($vehicle, $prefix);
        } elseif ($seatOption === NumericConstants::SEAT_OPTION_MANUAL && isset($data['seat_names'])) {
            $this->createManualSeats($vehicle, $data['seat_names']);
        }
    }
    
    private function validateSeatNames(array $seatNames, int $capacity): void
    {
        if (count($seatNames) > $capacity) {
            throw ValidationException::withMessages([
                'seat_names' => 'عدد المقاعد يتجاوز سعة المركبة'
            ]);
        }

        $trimmedNames = [];
        foreach ($seatNames as $name) {
            $trimmedName = trim($name);
            
            if (empty($trimmedName)) {
                throw ValidationException::withMessages([
                    'seat_names' => 'اسم المقعد لا يمكن أن يكون فارغاً'
                ]);
            }
            
            if (strlen($trimmedName) > 50) {
                throw ValidationException::withMessages([
                    'seat_names' => 'اسم المقعد "' . $trimmedName . '" طويل جداً (الحد الأقصى 50 حرف)'
                ]);
            }
            
            $trimmedNames[] = strtolower($trimmedName);
        }

        $duplicates = array_count_values($trimmedNames);
        foreach ($duplicates as $name => $count) {
            if ($count > 1) {
                throw ValidationException::withMessages([
                    'seat_names' => 'اسم المقعد "' . $name . '" مكرر'
                ]);
            }
        }
        
        $reservedNames = ['admin', 'system', 'null', 'undefined', 'root', 'test', 'driver'];
        foreach ($trimmedNames as $name) {
            if (in_array($name, $reservedNames)) {
                throw ValidationException::withMessages([
                    'seat_names' => 'اسم المقعد "' . $name . '" محجوز للنظام'
                ]);
            }
        }
    }
    
     private function validateNoActiveBookings(Vehicle $vehicle): void
    {
        $hasActiveBookings = $vehicle->trips()
            ->where('departure_time', '>', now())
            ->whereHas('tripReservations', function($query) {
                $query->where('status', BookingStatus::RESERVATION_ACTIVE);
            })
            ->exists();

        if ($hasActiveBookings) {
            throw ValidationException::withMessages([
                'seat_option' => 'لا يمكن تعديل المقاعد لوجود حجوزات نشطة في رحلات مستقبلية'
            ]);
        }

        // التحقق من الحجوزات المؤكدة في الرحلات الحالية
        $hasCurrentBookings = $vehicle->trips()
            ->where('departure_time', '<=', now())
            ->where('arrival_time', '>', now())
            ->whereHas('tripReservations', function($query) {
                $query->where('status', BookingStatus::RESERVATION_ACTIVE);
            })
            ->exists();

        if ($hasCurrentBookings) {
            throw ValidationException::withMessages([
                'seat_option' => 'لا يمكن تعديل المقاعد لوجود رحلة حالية بها حجوزات مرتبطة'
            ]);
        }
    }
}