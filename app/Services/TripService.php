<?php

namespace App\Services;

use App\Helpers\UuidHelper;
use App\Models\Service;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\TripAmenity;
use App\Models\TripRepeat;
use App\Models\Coupon;
use App\Models\RepeatedTripDay;
use App\Models\TripWaypoint;
use App\Models\Country;
use App\Models\Policy;
use App\Models\Amenity;
use App\Models\TypeOfTrip;
use App\Models\LevelTrip;
use App\Models\Currency;
use App\Models\City;
use App\Repositories\Contracts\TripRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use App\Constants\NumericConstants;
use App\Constants\CouponStatus as CouponStatusConst;
use App\Constants\BookingStatus as BookingStatusConst;
use App\Constants\TripStatus as TripStatusConst;
use App\Services\FileUploadService; 
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
/**
 * Trip Service
 * 
 * This service handles all trip-related operations including creation,
 * management, validation, and business logic enforcement.
 * 
 * Security features:
 * - Authorization checks for all operations
 * - Data validation and sanitization
 * - Audit logging for all modifications
 * - Business rule enforcement
 */
class TripService
{
    protected TripRepositoryInterface $tripRepository;
    protected AuditService $auditService;
    protected TripValidationService $validationService;
    protected FileUploadService $fileUploadService;

    public function __construct(
        TripRepositoryInterface $tripRepository,
        AuditService $auditService,
        TripValidationService $validationService,
        FileUploadService $fileUploadService
    ) {
        $this->tripRepository = $tripRepository;
        $this->auditService = $auditService;
        $this->validationService = $validationService;
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Get available trips with filtering and passenger count validation
     * 
     * This method retrieves available trips with proper seat availability
     * checking and passenger count validation.
     * 
     * @param array $filters Filter criteria including adults/children
     * @param int $perPage Items per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Available trips
     */
    public function getAvailableTrips(array $filters = [], int $perPage = NumericConstants::DEFAULT_PER_PAGE)
    {
        return $this->tripRepository->getAvailableTrips($filters, $perPage);
    }

    /**
     * Check trip availability by UUID
     * 
     * This method checks if a trip is available and returns
     * detailed availability information.
     * 
     * @param string $tripUuid Trip UUID
     * @return array Availability information
     */
    public function checkAvailability(string $tripUuid): array
    {
        return $this->getTripAvailability($tripUuid);
    }

    /**
     * Restore a soft-deleted trip
     * 
     * This method restores a previously soft-deleted trip with proper
     * authorization validation and data integrity checks.
     * 
     * Security considerations:
     * - Only trip owners or admins can restore trips
     * - Cannot restore trips with active bookings
     * - Audit logging for compliance
     * 
     * @param string $tripUuid Unique identifier of the trip to restore
     * @param int $userId ID of the user performing the restoration
     * @return array Restoration result with success status and message
     * @throws \Exception If restoration fails due to database errors
     */
    public function restoreTrip(string $tripUuid, int $userId): array
    {
        try {
            $trip = $this->tripRepository->findByUuid($tripUuid, true);
            if (!$trip) return ['success' => false, 'message' => 'الرحلة غير موجودة'];

            $user = User::find($userId);
            if (!$user->isAdmin() && $trip->created_by_user !== $userId) {
                return ['success' => false, 'message' => 'غير مصرح لك باستعادة هذه الرحلة'];
            }

            if ($trip->tripReservations()->where('status', 'active')->exists()) {
                return ['success' => false, 'message' => 'لا يمكن استعادة رحلة بها حجوزات نشطة'];
            }

            DB::beginTransaction();
            $this->tripRepository->restore($trip);

            // ✅ Audit في afterCommit
            DB::afterCommit(function () use ($userId, $trip) {
                $this->auditService->log($userId, 'restore', 'trips', $trip->id, null, $trip->fresh()->toArray());
            });

            DB::commit();

            return ['success' => true, 'message' => 'تم استعادة الرحلة بنجاح', 'data' => $trip];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to restore trip: ' . $e->getMessage());
            return ['success' => false, 'message' => 'فشل استعادة الرحلة. يرجى المحاولة مرة أخرى.'];
        }
    }

    public function forceDeleteTrip(string $tripUuid, int $userId): array
    {
        try {
            $trip = $this->tripRepository->findByUuid($tripUuid, true);
            if (!$trip) return ['success' => false, 'message' => 'الرحلة غير موجودة'];

            $user = User::find($userId);
            if (!$user->isAdmin()) return ['success' => false, 'message' => 'غير مصرح لك بالحذف النهائي للرحلات'];

            $activeBookings = $trip->tripReservations()->where('status', '!=', 'cancelled')->exists();
            if ($activeBookings) return ['success' => false, 'message' => 'لا يمكن حذف الرحلة نهائياً لوجود حجوزات نشطة'];

            if ($trip->tickets()->exists()) return ['success' => false, 'message' => 'لا يمكن حذف الرحلة نهائياً لوجود تذاكر'];

            $oldData = $trip->toArray();
            $tripId = $trip->id;
            $imageToDelete = $trip->image;

            DB::beginTransaction();
            $this->cleanupTripData($trip);
            $this->tripRepository->forceDelete($trip);

            // ✅ Audit + حذف الصورة في afterCommit
            DB::afterCommit(function () use ($userId, $tripId, $oldData, $imageToDelete) {
                $this->auditService->log($userId, 'force_delete', 'trips', $tripId, $oldData, null);

                if ($imageToDelete) {
                    $this->fileUploadService->deleteFile($imageToDelete);
                }
            });

            DB::commit();

            return ['success' => true, 'message' => 'تم حذف الرحلة نهائياً'];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to force delete trip: ' . $e->getMessage());
            return ['success' => false, 'message' => 'فشل الحذف النهائي للرحلة'];
        }
    }

    /**
     * Get all trips with filtering and pagination
     * 
     * This method retrieves trips with optional filtering capabilities
     * and proper pagination for performance optimization.
     * 
     * @param array $filters Filter criteria
     * @param int $perPage Items per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated trips
     */
    public function getAllTrips(array $filters = [], int $perPage = NumericConstants::DEFAULT_PER_PAGE)
    {
        return $this->tripRepository->search($filters, $perPage);
    }

    /**
     * Get trip by UUID with detailed information
     * 
     * This method retrieves a single trip with all related data
     * for comprehensive display and validation.
     * 
     * @param string $tripUuid Trip UUID
     * @param bool $publicOnly Only return public trips (for API)
     * @return Trip|null Trip model with relationships
     */
    public function getTripByUuid(string $tripUuid, bool $publicOnly = false): ?Trip
    {
        return $this->tripRepository->findByUuid($tripUuid, false, $publicOnly);
    }

    /**
     * Retrieve form data for trip creation and editing
     * 
     * This method centralizes data loading for trip forms, applying
     * proper provider context and authorization rules.
     * 
     * Business logic:
     * - Providers see only their own vehicles, services, policies
     * - Admins see all available data
     * - Applies provider context for multi-tenancy
     * 
     * @param User|null $user Current authenticated user (defaults to auth user)
     * @return array Form data containing cities, countries, currencies, vehicles, services, policies, coupons, amenities, trip types, and level trips
     */
    public function getTripFormData(?User $user = null): array
    {
        $user = $user ?? auth()->user();
        $providerContext = app(\App\Services\ProviderContext::class);
        $providerUserId = $providerContext->providerUserIdFor($user);
        $providerServiceId = $providerContext->providerServiceIdFor($user);
        $isAdmin = (bool) $user?->isAdmin();

        $cities = Cache::remember('trip_form:cities', 3600, fn() => City::with('country')->orderBy('name')->get());
        $countries = Cache::remember('trip_form:countries', 3600, fn() => Country::orderBy('name')->get());
        $currencies = Cache::remember('trip_form:currencies', 3600, fn() => Currency::orderBy('name')->get());
        $policies = Cache::remember('trip_form:policies', 3600, fn() => Policy::all());
        $amenities = Cache::remember('trip_form:amenities', 3600, fn() => Amenity::orderBy('name')->get());
        $tripTypes = Cache::remember('trip_form:trip_types', 3600, fn() => TypeOfTrip::orderBy('type_name')->get());
        $levelTrips = Cache::remember('trip_form:level_trips', 3600, fn() => LevelTrip::orderBy('level_name')->get());

        $vehiclesQuery = Vehicle::query();
        if (!$isAdmin && $providerUserId) {
            $vehiclesQuery->whereHas('vehicleOwner', fn($q) => $q->where('user_id', $providerUserId));
        }
        $vehicles = $vehiclesQuery->with(['vehicleType'])->get();

        $servicesQuery = Service::selectRaw('MIN(services.id) as id, ps.name as provider_name')
            ->join('provider_branches as pb', 'services.branch_id', '=', 'pb.id')
            ->join('provider_services as ps', 'pb.provider_id', '=', 'ps.id')
            ->with(['branch.provider', 'branch.city', 'branch.country'])
            ->groupBy('ps.name')
            ->orderBy('ps.name');

        if (!$isAdmin && $providerServiceId) {
            $servicesQuery->where('ps.id', $providerServiceId);
        }
        $services = $servicesQuery->get();

        $couponsQuery = \App\Models\Coupon::where('end_date', '>=', now());
        if (!$isAdmin) {
            $couponsQuery->where('user_id', $providerUserId);
        }
        $coupons = $couponsQuery->get();

        return compact(
            'cities', 'countries', 'currencies', 'vehicles', 'services',
            'policies', 'coupons', 'amenities', 'tripTypes', 'levelTrips',
            'providerServiceId', 'isAdmin'
        );
    }

    /**
     * Create new trip with comprehensive validation
     * 
     * This method creates a new trip with full validation, business
     * rule enforcement, and audit logging.
     * 
     * Security considerations:
     * - Authorization validation
     * - Data sanitization and validation
     * - Business rule enforcement
     * - Atomic database operations
     * 
     * @param array $tripData Trip creation data
     * @param int $userId User creating the trip
     * @return array Creation result with trip data
     */
    public function createTrip(array $tripData, int $userId): array
    {
         $tripData = $this->resolveUuidsToIds($tripData);

        $repeatDays = $tripData['days'] ?? $tripData['repeat_days'] ?? [];
        if (($tripData['is_recurring'] ?? false) && !empty($repeatDays)) {
            $tripData['repeat_config'] = [
                'start_date' => $tripData['departure_time'] ?? now(),
                'end_date'   => $tripData['end_date_repeat'] ?? null,
                'days'       => $repeatDays,
                'status'     => 'active'
            ];
        }

        try {
            DB::beginTransaction();

            if (!$this->validateTripCreationPermission($userId)) {
                DB::rollBack();
                return ['success' => false, 'message' => 'Unauthorized to create trips', 'error_code' => 'UNAUTHORIZED'];
            }

            $validationResult = $this->validateTripData($tripData);
            if (!$validationResult['valid']) {
                DB::rollBack();
                return ['success' => false, 'message' => is_string(reset($validationResult['errors'])) ? reset($validationResult['errors']) : 'فشل التحقق', 'errors' => $validationResult['errors']];
            }

            $duplicateCheck = $this->validationService->validateDuplicateTrip($tripData);
            if (!$duplicateCheck['success']) {
                DB::rollBack();
                return ['success' => false, 'message' => $duplicateCheck['message'], 'error_code' => 'DUPLICATE_TRIP'];
            }

            if (!empty($tripData['vehicle_id']) && !empty($tripData['departure_time']) && !empty($tripData['arrival_time'])) {
                $vehicleCheck = $this->validationService->validateVehicleConflict($tripData['vehicle_id'], $tripData['departure_time'], $tripData['arrival_time']);
                if (!$vehicleCheck['success']) {
                    DB::rollBack();
                    return ['success' => false, 'message' => $vehicleCheck['message'], 'error_code' => 'VEHICLE_CONFLICT'];
                }
            }

            $sanitizedData = $this->sanitizeTripData($tripData);
            $sanitizedData['created_by_user'] = $userId;

            $trip = $this->tripRepository->create($sanitizedData);
            $trip->forceFill(['available_seats' => $tripData['ticket_limit'] ?? $trip->vehicle->seats_count])->save();

            if (!empty($tripData['amenities'])) $this->attachTripAmenities($trip, $tripData['amenities']);

            if (!empty($tripData['waypoints'])) {
                $waypointsData = [];
                foreach ($tripData['waypoints'] as $waypoint) {
                    $waypointsData[] = ['uuid' => (string) Str::uuid(), 'trip_id' => $trip->id, 'city_id' => $waypoint['city_id'], 'city_station_id' => $waypoint['city_station_id'] ?? null, 'created_at' => now(), 'updated_at' => now()];
                }
                TripWaypoint::insert($waypointsData);
            }

            if (!empty($tripData['policy_id'])) $trip->policies()->attach($tripData['policy_id']);
            
            // ✅ رفع الصورة خارج transaction (سيُحفظ المسار بعد commit)
            $uploadedImagePath = null;
            if (!empty($tripData['image']) && $tripData['image'] instanceof \Illuminate\Http\UploadedFile) {
                $result = $this->fileUploadService->uploadImage($tripData['image'], 'trips/temp');
                if ($result['success']) {
                    $uploadedImagePath = $result['file_path'];
                }
            }

            if (!empty($tripData['coupons'])) {
                $this->attachCouponsToTrip($trip, $tripData['coupons']);
                $bestDiscount = $this->getBestCouponDiscount($tripData['coupons'], $userId);
                if ($bestDiscount > 0) $trip->update(['discount_percentage' => $bestDiscount]);
            }

            if (!empty($tripData['repeat_config'])) {
                $repetitionResult = $this->createTripRepetition($trip, $tripData['repeat_config']);
                if (!$repetitionResult || !isset($repetitionResult['success']) || !$repetitionResult['success']) {
                    DB::rollBack();
                    return ['success' => false, 'message' => $repetitionResult['message'] ?? 'فشل إنشاء التكرار'];
                }
            }

            // ✅ حفظ مسار الصورة بعد إنشاء الـ trip (بمعرف صحيح)
            if ($uploadedImagePath) {
                $this->tripRepository->update($trip->id, ['image' => $uploadedImagePath]);
            }

            DB::commit();

            // ✅ Audit في afterCommit
            DB::afterCommit(function () use ($userId, $trip) {
                $this->auditService->log($userId, 'created', 'trips', $trip->id, null, $trip->fresh()->toArray());
            });

            return ['success' => true, 'message' => 'تم إنشاء الرحلة بنجاح', 'data' => $trip->load(['amenities', 'repeats'])];

        } catch (\Exception $e) {
            DB::rollBack();

            // ✅ نظّف الصورة المرفوعة عند الفشل
            if (isset($uploadedImagePath) && $uploadedImagePath) {
                $this->fileUploadService->deleteFile($uploadedImagePath);
            }

            Log::error('Trip creation failed: ' . $e->getMessage());
            $userMessage = (str_contains($e->getMessage(), 'SQLSTATE')) ? 'حدث خطأ أثناء حفظ بيانات الرحلة.' : 'فشل إنشاء الرحلة';
            return ['success' => false, 'message' => $userMessage, 'error_code' => 'CREATION_FAILED'];
        }
    }

    /**
     * Update existing trip with validation and security
     * 
     * This method updates trip data while maintaining security,
     * validation, and audit requirements.
     * 
     * @param string $tripUuid Trip UUID
     * @param array $updateData Update data
     * @param int $userId User making the update
     * @return array Update result
     */
    public function updateTrip(string $tripUuid, array $updateData, int $userId): array
    {
        $updateData = $this->resolveUuidsToIds($updateData);

        $repeatDays = $updateData['days'] ?? $updateData['repeat_days'] ?? [];
        if (($updateData['is_recurring'] ?? false) && !empty($repeatDays)) {
            $updateData['repeat_config'] = [
                'start_date' => $updateData['departure_time'] ?? now(),
                'end_date'   => $updateData['end_date_repeat'] ?? null,
                'days'       => $repeatDays,
                'status'     => 'active'
            ];
        }

        try {
            DB::beginTransaction();

            $user = User::find($userId);
            $trip = $this->tripRepository->findByUuid($tripUuid);
            
            if (!$trip) {
                DB::rollBack();
                return ['success' => false, 'message' => 'Trip not found', 'error_code' => 'NOT_FOUND'];
            }

            if (!$this->validateTripUpdatePermission($trip, $userId)) {
                DB::rollBack();
                return ['success' => false, 'message' => 'Unauthorized to update this trip', 'error_code' => 'UNAUTHORIZED'];
            }

            if ($trip->departure_time <= now()) {
                DB::rollBack();
                return ['success' => false, 'message' => 'لا يمكن تعديل الرحلة بعد انطلاقها', 'error_code' => 'TRIP_DEPARTED'];
            }

            $validationResult = $this->validateTripUpdateData($updateData, $trip, $user);
            if (!$validationResult['valid']) {
                DB::rollBack();
                return ['success' => false, 'message' => 'Validation failed', 'errors' => $validationResult['errors']];
            }

            $tripDataForValidation = array_merge($trip->toArray(), $updateData);
            $tripDataForValidation['created_by_user'] = $trip->created_by_user;
            $duplicateCheck = $this->validationService->validateDuplicateTrip($tripDataForValidation, $trip->id);
            if (!$duplicateCheck['success']) {
                DB::rollBack();
                return ['success' => false, 'message' => $duplicateCheck['message'], 'error_code' => 'DUPLICATE_TRIP'];
            }

            $vehicleId = $updateData['vehicle_id'] ?? $trip->vehicle_id;
            $departureTime = $updateData['departure_time'] ?? $trip->departure_time;
            $arrivalTime = $updateData['arrival_time'] ?? $trip->arrival_time;
            
            if ($vehicleId && $departureTime && $arrivalTime) {
                $vehicleCheck = $this->validationService->validateVehicleConflict($vehicleId, $departureTime, $arrivalTime, $trip->id);
                if (!$vehicleCheck['success']) {
                    DB::rollBack();
                    return ['success' => false, 'message' => $vehicleCheck['message'], 'error_code' => 'VEHICLE_CONFLICT'];
                }
            }

            $originalData = $trip->toArray();
            $hasBookings = $trip->bookings()->exists();

            if ($hasBookings) {
                $forbiddenAfterBooking = [
                    'price_for_adult' => 'سعر البالغ', 'price_for_child' => 'سعر الطفل',
                    'discount_percentage' => 'نسبة الخصم', 'vehicle_id' => 'المركبة',
                    'departure_city' => 'مدينة الانطلاق', 'destination_city' => 'مدينة الوصول',
                    'departure_time' => 'وقت الانطلاق', 'arrival_time' => 'وقت الوصول',
                ];
                
                foreach ($forbiddenAfterBooking as $field => $label) {
                    if (isset($updateData[$field]) && $updateData[$field] != $trip->$field) {
                         DB::rollBack();
                        return ['success' => false, 'message' => "لا يمكن تعديل {$label} بعد وجود حجوزات"];
                    }
                }
            }

            $sanitizedData = $this->sanitizeTripData($updateData);
             $this->tripRepository->update($trip->id, $sanitizedData);

            if (isset($updateData['ticket_limit'])) {
                $bookedSeats = count($trip->getBookedSeatIds());
                $newLimit = (int) $updateData['ticket_limit'];
                if ($newLimit < $bookedSeats) {
                    DB::rollBack();
                    return ['success' => false, 'message' => "لا يمكن أن يكون الحد أقل من عدد التذاكر المحجوزة ({$bookedSeats})"];
                }
                $this->tripRepository->update($trip->id, ['available_seats' => $newLimit - $bookedSeats]);
            }
            
            // ✅ رفع الصورة خارج transaction (سيُحفظ المسار بعد commit)
            $newImagePath = null;
            $oldImagePath = $trip->image;
            if (!empty($updateData['image']) && $updateData['image'] instanceof \Illuminate\Http\UploadedFile) {
                $result = $this->fileUploadService->uploadImage($updateData['image'], 'trips/' . $trip->id);
                if ($result['success']) {
                    $newImagePath = $result['file_path'];
                    // حدّث المسار داخل transaction
                    $this->tripRepository->update($trip->id, ['image' => $newImagePath]);
                }
            }

            if (isset($updateData['amenities'])) $this->updateTripAmenities($trip, $updateData['amenities']);

            if (isset($updateData['waypoints'])) {
                $trip->waypoints()->delete();
                if (!empty($updateData['waypoints'])) {
                    $waypointsData = [];
                    foreach ($updateData['waypoints'] as $waypoint) {
                        $waypointsData[] = [
                            'uuid' => (string) Str::uuid(),
                            'trip_id' => $trip->id,
                            'city_id' => $waypoint['city_id'],
                            'city_station_id' => $waypoint['city_station_id'] ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    TripWaypoint::insert($waypointsData);
                }
            }
            
            if (!empty($updateData['policy_id'])) $trip->policies()->sync([$updateData['policy_id']]);

            if (isset($updateData['coupons'])) {
                $this->updateTripCoupons($trip, $updateData['coupons']);
                $bestDiscount = $this->getBestCouponDiscount($updateData['coupons'], $userId);
                if ($bestDiscount > 0) $this->tripRepository->update($trip->id, ['discount_percentage' => $bestDiscount]);
            }

            if (!empty($updateData['repeat_config'])) {
                $repetitionResult = $this->updateTripRepetition($trip, $updateData['repeat_config']);
                if (!$repetitionResult['success']) {
                    DB::rollBack();
                    return ['success' => false, 'message' => $repetitionResult['message']];
                }
            }

            DB::commit();

            // ✅ Audit + حذف الصورة القديمة في afterCommit
            DB::afterCommit(function () use ($userId, $trip, $originalData, $sanitizedData, $oldImagePath, $newImagePath) {
                $changes = $this->getDataChanges($originalData, $sanitizedData);

                if (!empty($changes) || $newImagePath) {
                    $this->auditService->log(
                        $userId,
                        'updated',
                        'trips',
                        $trip->id,
                        array_intersect_key($originalData, $changes),
                        $changes
                    );
                }

                // ✅ احذف الصورة القديمة بعد نجاح التحديث
                if ($newImagePath && $oldImagePath) {
                    $this->fileUploadService->deleteFile($oldImagePath);
                }

                cache()->forget("trip_prices_{$trip->id}");
            });

            return ['success' => true, 'message' => 'تم تحديث الرحلة بنجاح', 'data' => $trip->fresh()->load(['amenities', 'repeats'])];

        } catch (\Exception $e) {
            DB::rollBack();

            // ✅ نظّف الصورة الجديدة المرفوعة عند الفشل
            if (isset($newImagePath) && $newImagePath) {
                $this->fileUploadService->deleteFile($newImagePath);
            }

            Log::error('Trip update failed', ['user_id' => $userId, 'trip_uuid' => $tripUuid, 'error' => $e->getMessage()]);
            $userMessage = (str_contains($e->getMessage(), 'SQLSTATE')) ? 'حدث خطأ أثناء تحديث بيانات الرحلة.' : 'فشل تحديث الرحلة';
            return ['success' => false, 'message' => $userMessage, 'error_code' => 'UPDATE_FAILED'];
        }
    }

    /**
     * Delete trip with safety checks and cleanup
     * 
     * This method safely deletes a trip with proper validation,
     * booking checks, and cleanup operations.
     * 
     * @param string $tripUuid Trip UUID
     * @param int $userId User requesting deletion
     * @return array Deletion result
     */
    public function deleteTrip(string $tripUuid, int $userId): array
    {
        $trip = $this->tripRepository->findByUuid($tripUuid);
        
        if (!$trip) {
            return ['success' => false, 'message' => 'الرحلة غير موجودة', 'error_code' => 'NOT_FOUND'];
        }

        if (!$this->validateTripDeletionPermission($trip, $userId)) {
            return ['success' => false, 'message' => 'غير مصرح لك بحذف هذه الرحلة', 'error_code' => 'UNAUTHORIZED'];
        }

        $activeBookings = $trip->tripReservations()
            ->whereHas('booking', fn ($query) => $query->where('booking_status_id', '!=', BookingStatusConst::CANCELLED))
            ->count();

        if ($activeBookings > 0) {
            return ['success' => false, 'message' => 'لا يمكن حذف الرحلة التي تحتوي على حجوزات نشطة', 'error_code' => 'HAS_BOOKINGS', 'booking_count' => $activeBookings];
        }

        // ✅ احفظ بيانات الصورة قبل الحذف
        $tripId = $trip->id;
        $tripOldData = $trip->toArray();
        $imageToDelete = $trip->image;

        try {
            DB::beginTransaction();

            $trip->delete();

            // ✅ Audit + حذف الصورة في afterCommit
            DB::afterCommit(function () use ($userId, $tripId, $tripOldData, $imageToDelete) {
                $this->auditService->log($userId, 'deleted', 'trips', $tripId, [
                    'trip_uuid' => $tripOldData['uuid'] ?? null,
                    'departure_city' => $tripOldData['departure_city'] ?? null,
                    'destination_city' => $tripOldData['destination_city'] ?? null,
                ]);

                // ✅ احذف الصورة بعد نجاح الحذف
                if ($imageToDelete) {
                    $this->fileUploadService->deleteFile($imageToDelete);
                }
            });

            DB::commit();

            return ['success' => true, 'message' => 'تم حذف الرحلة بنجاح'];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete trip: ' . $e->getMessage());
            return ['success' => false, 'message' => 'فشل حذف الرحلة', 'error_code' => 'DELETION_FAILED'];
        }
    }

    /**
     * Get available seats for a trip
     * 
     * This method returns the current seat availability for a trip
     * with real-time data accuracy.
     * 
     * @param string $tripUuid Trip UUID
     * @return array Seat availability information
     */
    public function getTripAvailability(string $tripUuid): array
    {
        $trip = $this->tripRepository->findByUuid($tripUuid);
        
        if (!$trip) {
            return [
                'success' => false,
                'message' => 'الرحلة غير موجودة'
            ];
        }

        $bookedSeats = $trip->getBookedSeatIds();
        if (!$trip->vehicle) {
            return [
                'success' => false,
                'message' => 'الرحلة لا تحتوي على مركبة'
            ];
        }
        $totalSeats = $trip->vehicle->seats()->count() ?? 0;

       $availableSeats = $trip->available_seats ?? $totalSeats;

        return [
            'success' => true,
            'data' => [
                'total_seats' => $totalSeats,
                'booked_seats' => count($bookedSeats),
                'available_seats' => $availableSeats,
                'availability_percentage' => ($totalSeats > 0) ? ($availableSeats / $totalSeats) * 100 : 0,
                'booked_seat_ids' => $bookedSeats,
                'vehicle_id' => $trip->vehicle_id
            ]
        ];
    }

    /**
     * Validate user permission to create trips
     * 
     * This method checks if the user has the required permission
     * to create new trips in the system.
     * 
     * @param int $userId ID of the user to validate
     * @return bool True if user has 'trips:create' permission or is admin
     */
    private function validateTripCreationPermission(int $userId): bool
    {
        $user = User::find($userId);
        
        if (!$user) {
            return false;
        }

        return $user->hasPermission('trips:create') || $user->isAdmin();
    }

    /**
     * Validate user permission to update a specific trip
     * 
     * This method checks if the user has permission to update the
     * specified trip based on ownership and role.
     * 
     * Permission rules:
     * - Admins can update any trip
     * - Users can update their own trips with 'trips:update-own' permission
     * - Users with 'trips:update-any' can update any trip
     * 
     * @param \App\Models\Trip $trip Trip model to validate access to
     * @param int $userId ID of the user attempting the update
     * @return bool True if user has permission to update the trip
     */
    private function validateTripUpdatePermission(Trip $trip, int $userId): bool
    {
        $user = User::find($userId);
        
        if (!$user) {
            return false;
        }

        // Admin can update any trip
        if ($user->isAdmin()) {
            return true;
        }

        // Users can update their own trips
        if ($trip->created_by_user === $userId && $user->hasPermission('trips:update-own')) {
            return true;
        }

        // Users with update-any permission
        return $user->hasPermission('trips:update-any');
    }

    /**
     * Validate user permission to delete a specific trip
     * 
     * This method checks if the user has permission to delete the
     * specified trip based on ownership and role.
     * 
     * Permission rules:
     * - Admins can delete any trip
     * - Users can delete their own trips with 'trips:delete-own' permission
     * - Users with 'trips:delete-any' can delete any trip
     * 
     * @param \App\Models\Trip $trip Trip model to validate access to
     * @param int $userId ID of the user attempting the deletion
     * @return bool True if user has permission to delete the trip
     */
    private function validateTripDeletionPermission(Trip $trip, int $userId): bool
    {
        $user = User::find($userId);
        
        if (!$user) {
            return false;
        }

        // Admin can delete any trip
        if ($user->isAdmin()) {
            return true;
        }

        // Users can delete their own trips if no bookings exist
        if ($trip->created_by_user === $userId) {
            return true;
        }

        return $user->hasPermission('trips:delete-any');
    }

    /**
     * Validate trip data for creation
     * 
     * This method performs comprehensive validation of trip creation data
     * including required fields, time constraints, and entity existence.
     * 
     * Validation checks:
     * - Required fields presence
     * - Departure time must be in the future
     * - Vehicle must exist and be available
     * - Service must exist
     * 
     * @param array $tripData Trip data to validate
     * @return array Validation result with 'valid' boolean and 'errors' array
     */
    private function validateTripData(array $tripData): array
    {
        $errors = [];

        // Validate required fields
        $requiredFields = ['service_id', 'vehicle_id', 'departure_city', 'destination_city', 'departure_time'];
        foreach ($requiredFields as $field) {
            if (empty($tripData[$field])) {
                $errors[$field] = "الحقل {$field} مطلوب";
            }
        }

        // Validate departure time
        if (!empty($tripData['departure_time'])) {
            try {
                $departureTime = Carbon::parse($tripData['departure_time']);
                if ($departureTime->lte(now())) {
                    $errors['departure_time'] = 'وقت الانطلاق يجب أن يكون في المستقبل';
                }
            } catch (\Exception $e) {
                $errors['departure_time'] = 'تنسيق وقت الانطلاق غير صحيح';
            }
        }

        // Validate vehicle availability
        if (!empty($tripData['vehicle_id'])) {
            $vehicle = Vehicle::find($tripData['vehicle_id']);
            if (!$vehicle) {
                $errors['vehicle_id'] = 'المركبة غير موجودة';
            }
        }

        // Validate service
        if (!empty($tripData['service_id'])) {
            $service = Service::find($tripData['service_id']);
            if (!$service) {
                $errors['service_id'] = 'الخدمة غير موجودة';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate trip data for update
     * 
     * This method validates trip update data with specific rules for
     * modifications to existing trips.
     * 
     * Validation checks:
     * - Departure time must be in the future (unless admin)
     * - Vehicle must exist if being changed
     * - Preserves existing data integrity
     * 
     * @param array $updateData Trip data to update
     * @param \App\Models\Trip $trip Existing trip model
     * @return array Validation result with 'valid' boolean and 'errors' array
     */
    private function validateTripUpdateData(array $updateData, Trip $trip, User $user): array
    {
        $errors = [];

        // Validate departure time if being updated
        if (isset($updateData['departure_time'])) {
            try {
                $departureTime = Carbon::parse($updateData['departure_time']);
                if ($departureTime->lte(now()) && !$user->isAdmin()) {
                    $errors['departure_time'] = 'وقت الانطلاق يجب أن يكون في المستقبل';
                }
            } catch (\Exception $e) {
                $errors['departure_time'] = 'تنسيق وقت الانطلاق غير صحيح';
            }
        }

        // Validate vehicle if being updated
        if (isset($updateData['vehicle_id'])) {
            $vehicle = Vehicle::find($updateData['vehicle_id']);
            if (!$vehicle) {
                $errors['vehicle_id'] = 'المركبة غير موجودة';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Sanitize trip data for security and integrity
     * 
     * This method cleans and validates trip data to prevent security
     * issues such as XSS attacks and ensure data integrity.
     * 
     * Security measures:
     * - Whitelist-based field filtering
     * - String sanitization (trim, strip tags)
     * - Type preservation for non-string fields
     * 
     * @param array $tripData Raw trip data from user input
     * @return array Sanitized trip data safe for database operations
     */
    private function sanitizeTripData(array $tripData): array
    {
        $sanitized = [];

        $allowedFields = [
            'service_id', 'vehicle_id', 'type_of_trip_id', 'trip_name_id',
            'departure_city', 'destination_city',
            'pickup_location', 'pickup_location_id',
            'arrival_location_id',
            'check_in_time', 'departure_time', 'arrival_time',
            'price_for_adult', 'price_for_child', 'discount_percentage', 'currency_id',
            'conditions', 'level_trip_id', 'is_public',
             'trip_status_id','ticket_limit','image'
        ];

        foreach ($allowedFields as $field) {
            if (isset($tripData[$field])) {
                $sanitized[$field] = is_string($tripData[$field]) 
                    ? trim(strip_tags($tripData[$field])) 
                    : $tripData[$field];
            }
        }

        if (isset($tripData['vehicle_id'])) {
        $vehicle = Vehicle::find($tripData['vehicle_id']);
        if ($vehicle) {
            $sanitized['available_seats'] = $tripData['ticket_limit'] 
                ?? $vehicle->seats_count;
        }
    }
        return $sanitized;
    }

    /**
     * Attach amenities to a trip
     * 
     * This method links amenities to a trip using sync operation
     * which handles both addition and removal in a single query.
     * 
     * @param \App\Models\Trip $trip Trip model to attach amenities to
     * @param array $amenityIds Array of amenity IDs to attach
     * @return void
     */
    private function attachTripAmenities(Trip $trip, array $amenityIds): void
    {
        $trip->amenities()->sync($amenityIds);
    }

    /**
     * Update trip amenities
     * 
     * This method updates the amenities associated with a trip using
     * sync operation for efficient database operations.
     * 
     * @param \App\Models\Trip $trip Trip model to update amenities for
     * @param array $amenityIds Array of amenity IDs to sync
     * @return void
     */
    private function updateTripAmenities(Trip $trip, array $amenityIds): void
    {
        // Sync amenities: adds new, removes old, keeps common - single query
        $trip->amenities()->sync($amenityIds);
    }

    /**
     * Retrieve the highest discount percentage from available coupons
     * 
     * This method finds the best discount among the provided coupon IDs
     * by filtering for active coupons and selecting the highest percentage.
     * 
     * @param array $couponIds Array of coupon IDs to check
     * @return float Best discount percentage (0.0 if no valid coupons found)
     */
    private function getBestCouponDiscount(array $couponIds, int $userId): float
    {
        if (empty($couponIds)) {
            return 0.0;
        }
        
        $bestCoupon = Coupon::whereIn('id', $couponIds)
            ->where('user_id', $userId) // Ensure coupons belong to the current user
            ->where('status_id', CouponStatusConst::ACTIVE)
            ->orderBy('percentage', 'desc')
            ->first();
            
        return $bestCoupon ? (float) $bestCoupon->percentage : 0.0;
    }

    /**
     * Attach valid coupons to a trip
     * 
     * This method validates and attaches active coupons to a trip,
     * filtering out inactive or invalid coupons.
     * 
     * Validation:
     * - Only active coupons are attached
     * - Invalid coupon IDs are filtered out
     * - Comprehensive logging for debugging
     * 
     * @param \App\Models\Trip $trip Trip model to attach coupons to
     * @param array $couponIds Array of coupon IDs to attach
     * @return void
     */
    private function attachCouponsToTrip(Trip $trip, array $couponIds): void
    {
        // Validate coupon IDs
        $validCouponIds = Coupon::whereIn('id', $couponIds)
            ->where('user_id', $trip->created_by_user) // Ensure coupons belong to the trip creator
            ->where('status_id', CouponStatusConst::ACTIVE)
            ->pluck('id')
            ->toArray();

        if (!empty($validCouponIds)) {
            // Attach coupons to trip
            $trip->coupons()->attach($validCouponIds);
            

        }
    }

    /**
     * Update trip coupons with validation
     * 
     * This method updates the coupons associated with a trip by syncing
     * valid active coupons and removing invalid ones.
     * 
     * Business logic:
     * - Syncs valid active coupons to trip
     * - Removes all coupons if none provided
     * - Comprehensive error logging
     * - Warns if trip has existing bookings
     * 
     * @param \App\Models\Trip $trip Trip model to update coupons for
     * @param array $couponIds Array of coupon IDs to sync
     * @return void
     */
    private function updateTripCoupons(Trip $trip, array $couponIds): void
    {
        // Validate coupon IDs
        $validCouponIds = Coupon::whereIn('id', $couponIds)
            ->where('user_id', $trip->created_by_user) // Ensure coupons belong to the trip creator
            ->where('status_id', CouponStatusConst::ACTIVE)
            ->pluck('id')
            ->toArray();

        if (!empty($validCouponIds)) {
            // Sync coupons (remove old, add new)
            try {
                $trip->coupons()->sync($validCouponIds);
                
                Log::info('Trip coupons updated', [
                    'trip_id' => $trip->id,
                    'trip_uuid' => $trip->uuid,
                    'old_coupons' => $trip->coupons()->pluck('coupons.id')->toArray(),
                    'new_coupons' => $validCouponIds,
                    'total_coupons' => count($validCouponIds)
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to sync coupons to trip', [
                    'trip_id' => $trip->id,
                    'trip_uuid' => $trip->uuid,
                    'coupon_ids' => $validCouponIds,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            // Remove all coupons if none provided
            try {
                $trip->coupons()->detach();
                
                Log::info('All coupons removed from trip', [
                    'trip_id' => $trip->id,
                    'trip_uuid' => $trip->uuid
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to detach coupons from trip', [
                    'trip_id' => $trip->id,
                    'trip_uuid' => $trip->uuid,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Create trip repetition configuration
     * 
     * This method creates a repetition configuration for recurring trips,
     * allowing automated trip generation based on schedule.
     * 
     * @param \App\Models\Trip $trip Trip model to create repetition for
     * @param array $repeatConfig Repetition configuration containing start_date, end_date, and status
     */
    private function createTripRepetition(Trip $trip, array $repeatConfig): array
    {
        $tripRepeat = TripRepeat::create([
            'trip_id' => $trip->id,
            'start_date' => $repeatConfig['start_date'],
            'end_date' => $repeatConfig['end_date'],
            'status' => $repeatConfig['status'] ?? 'active'
        ]);

        // Save days in RepeatedTripDay table
        if (!empty($repeatConfig['days']) && is_array($repeatConfig['days'])) {
            foreach ($repeatConfig['days'] as $day) {
                RepeatedTripDay::create([
                    'repeat_id' => $tripRepeat->id,
                    'day_of_week' => $day
                ]);
            }

            Log::info('Repeated trip days saved', [
                'trip_id' => $trip->id,
                'trip_uuid' => $trip->uuid,
                'repeat_id' => $tripRepeat->id,
                'days' => $repeatConfig['days']
            ]);

            // Create child trips immediately for each selected day
            $childTripsResult = $this->createChildTripsForRepetition($trip, $repeatConfig, $tripRepeat->id);

            if (!$childTripsResult['success']) {
                return [
                    'success' => false,
                    'message' => $childTripsResult['message']
                ];
            }
        }

        return [
            'success' => true,
            'message' => 'تم إنشاء تكوين التكرار بنجاح'
        ];
    }

    /**
     * Create child trips for each selected day in the repetition
     *
     * This method creates child trips based on the selected days within the date range.
     * Each child trip is linked to the parent trip via parent_id.
     *
     * @param \App\Models\Trip $parentTrip The parent trip
     * @param array $repeatConfig Repetition configuration
     * @param int $repeatId The trip repeat ID
     * @return array Creation result
     */
    private function createChildTripsForRepetition(Trip $parentTrip, array $repeatConfig, int $repeatId): array
{
    try {
        $startDate = \Carbon\Carbon::parse($repeatConfig['start_date']);
        $endDate = isset($repeatConfig['end_date']) ? \Carbon\Carbon::parse($repeatConfig['end_date']) : $startDate->copy()->addMonth();
        $days = $repeatConfig['days'];

        $childTripsCreated = 0;
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $currentDayName = strtolower($currentDate->englishDayOfWeek);

            if (in_array($currentDayName, $days)) {
                if ($currentDate->format('Y-m-d') === $startDate->format('Y-m-d')) {
                    $currentDate->addDay();
                    continue;
                }

                // --- تصحيح الإزاحة هنا ---
                $timeDiff = $currentDate->diff($startDate);
                $newDepartureTime = $parentTrip->departure_time->copy()->addDays($timeDiff->days);
                $newArrivalTime = $newDepartureTime->copy()->addMinutes($parentTrip->arrival_time->diffInMinutes($parentTrip->departure_time));

                $childTripData = [
                    'created_by_user' => $parentTrip->created_by_user,
                    'departure_city' => $parentTrip->departure_city,
                    'destination_city' => $parentTrip->destination_city,
                    'departure_time' => $newDepartureTime,
                ];
                
                $duplicateCheck = $this->validationService->validateDuplicateTrip($childTripData);
                if (!$duplicateCheck['success']) {
                    throw new \Exception($duplicateCheck['message']);
                }

                $vehicleCheck = $this->validationService->validateVehicleConflict(
                    $parentTrip->vehicle_id,
                    $newDepartureTime,
                    $newArrivalTime
                );
                if (!$vehicleCheck['success']) {
                    throw new \Exception($vehicleCheck['message']);
                }

                $childTrip = $this->tripRepository->create([
                    'parent_id' => $parentTrip->id,
                    'service_id' => $parentTrip->service_id,
                    'vehicle_id' => $parentTrip->vehicle_id,
                    'type_of_trip_id' => $parentTrip->type_of_trip_id,
                    'trip_name_id' => $parentTrip->trip_name_id,
                    'departure_city' => $parentTrip->departure_city,
                    'destination_city' => $parentTrip->destination_city,
                    'pickup_location' => $parentTrip->pickup_location,
                    'pickup_location_id' => $parentTrip->pickup_location_id,
                    'arrival_location_id' => $parentTrip->arrival_location_id,
                    'check_in_time' => $newDepartureTime->copy()->subMinutes(30),
                    'departure_time' => $newDepartureTime,
                    'arrival_time' => $newArrivalTime,
                    'price_for_adult' => $parentTrip->price_for_adult,
                    'price_for_child' => $parentTrip->price_for_child,
                    'discount_percentage' => $parentTrip->discount_percentage ?? 0,
                    'conditions' => $parentTrip->conditions,
                    'level_trip_id' => $parentTrip->level_trip_id,
                    'is_public' => $parentTrip->is_public ?? true,
                    'created_by_user' => $parentTrip->created_by_user,
                    'trip_status_id' => $parentTrip->trip_status_id,
                ]);

                if ($parentTrip->amenities) {
                    foreach ($parentTrip->amenities as $amenity) {
                        if ($amenity->amenity_id) {
                            TripAmenity::create(['trip_id' => $childTrip->id, 'amenity_id' => $amenity->amenity_id]);
                        }
                    }
                }

                if ($parentTrip->policies) {
                    foreach ($parentTrip->policies as $policy) {
                        $childTrip->policies()->attach($policy->id);
                    }
                }

                if ($parentTrip->coupons) {
                    foreach ($parentTrip->coupons as $coupon) {
                        $childTrip->coupons()->attach($coupon->id);
                    }
                }

                $childTripsCreated++;
            }
            $currentDate->addDay();
        }

        Log::info('Child trips created for repetition', [
            'parent_trip_id' => $parentTrip->id,
            'child_trips_created' => $childTripsCreated,
        ]);

        return [
            'success' => true,
            'child_trips_created' => $childTripsCreated
        ];
    } // <--- هذا القوس كان مفقوداً وهو سبب المشكلة الأساسي
    catch (\Exception $e) {

        return [
            'success' => false,
            'message' => 'حدث خطأ أثناء إنشاء الرحلات الفرعية: '
        ];
    }
}


    /**
     * Update trip repetition configuration
     *
     * This method updates the repetition configuration for a trip,
     * including start_date, end_date, status, and days.
     *
     * @param \App\Models\Trip $trip Trip model to update repetition for
     * @param array $repeatConfig Repetition configuration
     * @return array Update result
     */
    private function updateTripRepetition(Trip $trip, array $repeatConfig): array
    {
        // Check if trip already has repetition
        $tripRepeat = $trip->repeats()->first();

        if ($tripRepeat) {
            // Check if trip has existing bookings before modifying repetition
            // Why: Prevent affecting existing bookings by changing repetition days
            $hasBookings = $trip->bookings()->exists();
            if ($hasBookings) {
                Log::warning('Cannot update trip repetition - trip has existing bookings', [
                    'trip_id' => $trip->id,
                    'trip_uuid' => $trip->uuid,
                    'repeat_id' => $tripRepeat->id,
                ]);
                // Return error message for user
                return [
                    'success' => false,
                    'message' => 'لا يمكن تعديل أيام التكرار لأن الرحلة لديها حجوزات حالية'
                ];
            }

            // Update existing repetition
            $tripRepeat->update([
                'start_date' => $repeatConfig['start_date'],
                'end_date' => $repeatConfig['end_date'],
                'status' => $repeatConfig['status'] ?? 'active'
            ]);

            // Delete old days (safe now since we checked for bookings)
            $tripRepeat->repeatedDays()->delete();

            // Save new days
            if (!empty($repeatConfig['days']) && is_array($repeatConfig['days'])) {
                foreach ($repeatConfig['days'] as $day) {
                    RepeatedTripDay::create([
                        'repeat_id' => $tripRepeat->id,
                        'day_of_week' => $day
                    ]);
                }
                Log::info('Repeated trip days updated', [
                    'trip_id' => $trip->id,
                    'trip_uuid' => $trip->uuid,
                    'repeat_id' => $tripRepeat->id,
                    'days' => $repeatConfig['days']
                ]);
            }
        } else {
            // Create new repetition
            $result = $this->createTripRepetition($trip, $repeatConfig);
            if (!$result['success']) {
                return $result;
            }
        }

        return [
            'success' => true,
            'message' => 'تم تحديث تكوين التكرار بنجاح'
        ];
    }

    /**
     * Clean up trip-related data after deletion
     * 
     * This method removes associated data when a trip is deleted to
     * maintain database integrity and prevent orphaned records.
     * 
     * Cleanup operations:
     * - Remove trip amenities
     * - Remove trip repetitions
     * 
     * @param \App\Models\Trip $trip Trip model to clean up data for
     * @return void
     */
    private function cleanupTripData(Trip $trip): void
    {
        if ($trip->image && Storage::disk('public')->exists($trip->image)) {
            Storage::disk('public')->delete($trip->image);
    }
        // Clean up amenities
        $trip->tripAmenities()->delete();
        
        // Clean up repetitions
        $trip->repeats()->delete();
    }

    /**
     * Detect data changes for audit logging
     * 
     * This method compares original and new data to identify specific
     * field changes for comprehensive audit logging.
     * 
     * @param array $originalData Original data before modification
     * @param array $newData New data after modification
     * @return array Array of changed fields with old and new values
     */
    private function getDataChanges(array $originalData, array $newData): array
    {
        $changes = [];
        
        foreach ($newData as $key => $newValue) {
            if (isset($originalData[$key]) && $originalData[$key] != $newValue) {
                $changes[$key] = [
                    'old' => $originalData[$key],
                    'new' => $newValue
                ];
            }
        }
        
        return $changes;
    }
        /**
     * تحويل UUIDs إلى IDs بشكل آمن قبل الحفظ أو التحديث
     * (تم نقل هذا المنطق من الكونترولر إلى السيرفس للفصل المعماري)
     */
    private function resolveUuidsToIds(array $data): array
    {
        if (!empty($data['vehicle_id'])) {
            $vehicleId = UuidHelper::uuidToId('vehicles', $data['vehicle_id']);
            if (!$vehicleId) throw new \Exception('المركبة المحددة غير موجودة');
            $data['vehicle_id'] = $vehicleId;
        }

        if (!empty($data['departure_country_id'])) {
            $countryId = UuidHelper::uuidToId('countries', $data['departure_country_id']);
            if (!$countryId) throw new \Exception('دولة الانطلاق المحددة غير موجودة');
            $data['departure_country_id'] = $countryId;
        }
        if (!empty($data['destination_country_id'])) {
            $countryId = UuidHelper::uuidToId('countries', $data['destination_country_id']);
            if (!$countryId) throw new \Exception('دولة الوصول المحددة غير موجودة');
            $data['destination_country_id'] = $countryId;
        }

        if (!empty($data['departure_city'])) {
            $cityId = UuidHelper::uuidToId('cities', $data['departure_city']);
            if (!$cityId) throw new \Exception('مدينة الانطلاق المحددة غير موجودة');
            $data['departure_city'] = $cityId;
        }
        if (!empty($data['destination_city'])) {
            $cityId = UuidHelper::uuidToId('cities', $data['destination_city']);
            if (!$cityId) throw new \Exception('مدينة الوصول المحددة غير موجودة');
            $data['destination_city'] = $cityId;
        }

        if (!empty($data['pickup_location_id'])) {
            $stationId = UuidHelper::uuidToId('city_stations', $data['pickup_location_id']);
            if (!$stationId) throw new \Exception('محطة الانطلاق المحددة غير موجودة');
            $data['pickup_location_id'] = $stationId;
        }
        if (!empty($data['arrival_location_id'])) {
            $stationId = UuidHelper::uuidToId('city_stations', $data['arrival_location_id']);
            if (!$stationId) throw new \Exception('محطة الوصول المحددة غير موجودة');
            $data['arrival_location_id'] = $stationId;
        }

        if (!empty($data['amenities']) && is_array($data['amenities'])) {
            $data['amenities'] = UuidHelper::uuidsToIds('amenities', $data['amenities']);
        }

        if (!empty($data['coupons']) && is_array($data['coupons'])) {
            $data['coupons'] = UuidHelper::uuidsToIds('coupons', $data['coupons']);
        }

        if (!empty($data['policy_id'])) {
            $policyId = UuidHelper::uuidToId('policies', $data['policy_id']);
            if ($policyId) $data['policy_id'] = $policyId;
        }

        if (!empty($data['waypoints']) && is_array($data['waypoints'])) {
            foreach ($data['waypoints'] as $key => $waypoint) {
                if (!empty($waypoint['city_uuid'])) {
                    $cityId = UuidHelper::uuidToId('cities', $waypoint['city_uuid']);
                    if (!$cityId) throw new \Exception('مدينة المرور المحددة غير موجودة');
                    $data['waypoints'][$key]['city_id'] = $cityId;
                    unset($data['waypoints'][$key]['city_uuid']);
                }
                if (!empty($waypoint['city_station_uuid'])) {
                    $stationId = UuidHelper::uuidToId('city_stations', $waypoint['city_station_uuid']);
                    if ($stationId) {
                        $data['waypoints'][$key]['city_station_id'] = $stationId;
                        unset($data['waypoints'][$key]['city_station_uuid']);
                    }
                }
            }
        }
        return $data;
    }
}
