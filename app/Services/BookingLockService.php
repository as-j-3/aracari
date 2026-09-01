<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\TripReservation;
use App\Repositories\Contracts\TripRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Booking Lock Service
 *
 * يمنع overbooking عبر:
 * 1. قفل صفوف الرحلات في DB (lockForUpdate) داخل transaction
 * 2. cache advisory lock كطبقة إضافية
 * 3. تحرير الأقفال بعد commit/rollback
 *
 * مهم: يجب استدعاء lockTrips() داخل DB::transaction() الموجود في BookingService
 */
class BookingLockService
{
    private const CACHE_LOCK_TIMEOUT_SECONDS = 30;  //  مخفّض من 600 (10 دقائق طويلة جداً)
    private const MAX_RETRY_ATTEMPTS = 3;
    private const RETRY_BASE_DELAY_MICROSECONDS = 100000;  // 100ms

    public function __construct(private TripRepositoryInterface $tripRepository) {}

    /**
     * قفل الرحلات للتحقق من توفر المقاعد
     *
     * يجب استدعاؤها داخل DB::transaction()
     *
     * @param array $trips الرحلات المراد قفلها
     * @param int $requestedSeatsCount عدد المقاعد المطلوبة
     * @return array الرحلات المقفلة
     * @throws \RuntimeException عند فشل القفل أو عدم توفر مقاعد
     */
    public function lockTrips(array $trips, int $requestedSeatsCount): array
    {
        $lockedTrips = [];
        $cacheLockKeys = [];

        try {
            foreach ($trips as $trip) {
                $cacheLockKey = $this->getTripCacheLockKey($trip->id);

                // 1. اكتساب cache advisory lock
                if (!$this->acquireCacheLock($cacheLockKey)) {
                    throw new \RuntimeException("الرحلة {$trip->uuid} مشغولة - يرجى المحاولة بعد لحظات");
                }
                $cacheLockKeys[] = $cacheLockKey;

                // 2. قفل صف الرحلة في DB والتحقق من المقاعد
                $lockedTrip = $this->lockTripAndVerifySeats($trip->id, $requestedSeatsCount);

                if (!$lockedTrip) {
                    throw new \RuntimeException("فشل في قفل الرحلة {$trip->uuid} - المقاعد غير كافية");
                }

                $lockedTrips[] = $lockedTrip;
            }

            return $lockedTrips;

        } catch (\Exception $e) {
            // حرر كل أقفال cache عند الفشل
            $this->releaseCacheLocks($cacheLockKeys);
            throw $e;
        }
    }

    /**
     * قفل رحلة واحدة والتحقق من توفر المقاعد
     *
     * @param int $tripId معرف الرحلة
     * @param int $requestedSeatsCount عدد المقاعد المطلوبة
     * @return Trip|null الرحلة المقفلة أو null
     */
    private function lockTripAndVerifySeats(int $tripId, int $requestedSeatsCount): ?Trip
    {
        $attempts = 0;

        while ($attempts < self::MAX_RETRY_ATTEMPTS) {
            //  lockForUpdate يعمل فقط داخل transaction
            $lockedTrip = $this->tripRepository->lockForUpdate($tripId);

            if ($lockedTrip && $lockedTrip->vehicle) {
                $totalSeats = $lockedTrip->vehicle->seats()->count();
                $bookedSeatIds = $lockedTrip->getBookedSeatIds();
                $bookedSeatsCount = count($bookedSeatIds);
                $availableSeatsCount = $totalSeats - $bookedSeatsCount;

                if ($availableSeatsCount >= $requestedSeatsCount) {
                    return $lockedTrip;
                }
            }

            $attempts++;
            if ($attempts < self::MAX_RETRY_ATTEMPTS) {
                usleep(self::RETRY_BASE_DELAY_MICROSECONDS * $attempts);
            }
        }

        Log::warning('Trip lock failed after retries', [
            'trip_id' => $tripId,
            'requested_seats' => $requestedSeatsCount,
            'attempts' => $attempts,
        ]);

        return null;
    }

    /**
     * التحقق النهائي من توفر المقاعد
     *
     * يجب استدعاؤها داخل نفس transaction قبل تأكيد الحجز
     *
     * @param array $trips الرحلات
     * @param int $bookingId معرف الحجز
     * @return bool true إذا كانت المقاعد متوفرة
     */
    public function validateFinalAvailability(array $trips, int $bookingId): bool
    {
        foreach ($trips as $trip) {
            $lockedTrip = $this->tripRepository->lockForUpdate($trip->id);

            if (!$lockedTrip || !$lockedTrip->vehicle) {
                return false;
            }

            $totalSeats = $lockedTrip->vehicle->seats()->count();
            $bookedSeatIds = $lockedTrip->getBookedSeatIds();
            $availableSeatsCount = $totalSeats - count($bookedSeatIds);

            // عدد المقاعد المستخدمة في هذا الحجز تحديداً
            $usedSeatsForThisBooking = TripReservation::where('booking_id', $bookingId)
                ->where('trip_id', $trip->id)
                ->withCount('tickets')
                ->get()
                ->sum('tickets_count');

            if ($availableSeatsCount < $usedSeatsForThisBooking) {
                Log::warning('Final availability check failed', [
                    'trip_id' => $trip->id,
                    'available' => $availableSeatsCount,
                    'needed' => $usedSeatsForThisBooking,
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * تحرير أقفال cache للرحلات بعد اكتمال الحجز
     *
     * @param array $trips الرحلات
     * @return void
     */
    public function releaseTripLocks(array $trips): void
    {
        foreach ($trips as $trip) {
            $cacheLockKey = $this->getTripCacheLockKey($trip->id);
            $this->releaseCacheLock($cacheLockKey);
        }
    }

    /**
     * اكتساب cache advisory lock لرحلة
     */
    private function acquireCacheLock(string $cacheLockKey): bool
    {
        return Cache::add($cacheLockKey, now()->timestamp, self::CACHE_LOCK_TIMEOUT_SECONDS);
    }

    /**
     * تحرير cache lock واحد
     */
    private function releaseCacheLock(string $cacheLockKey): void
    {
        Cache::forget($cacheLockKey);
    }

    /**
     * تحرير عدة cache locks
     */
    private function releaseCacheLocks(array $cacheLockKeys): void
    {
        foreach ($cacheLockKeys as $cacheLockKey) {
            $this->releaseCacheLock($cacheLockKey);
        }
    }

    /**
     * مفتاح cache lock للرحلة
     */
    private function getTripCacheLockKey(int $tripId): string
    {
        return 'trip_booking_lock:' . $tripId;
    }
}
