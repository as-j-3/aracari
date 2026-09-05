<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Services\Auth\VerificationService;
use Illuminate\Support\Facades\Log;
use App\Services\AuditService;
use App\Constants\UserStatus;
use App\Constants\NumericConstants;
use App\Services\FileUploadService;
use App\Constants\TripStatus as TripStatusConst;
use App\Constants\BookingStatus as BookingStatusConst;

class UserService
{
    protected UserRepositoryInterface $userRepository;
    protected VerificationService $verificationService;
    protected AuditService $auditService;
    protected FileUploadService $fileUploadService;

    public function __construct(
        UserRepositoryInterface $userRepository,
        VerificationService $verificationService,
        AuditService $auditService,
        FileUploadService $fileUploadService
    ) {
        $this->userRepository = $userRepository;
        $this->verificationService = $verificationService;
        $this->auditService = $auditService;
        $this->fileUploadService = $fileUploadService;
    }

    public function getAllUsers(int $perPage = NumericConstants::DEFAULT_PER_PAGE)
    {
        return $this->userRepository->getUsers([], $perPage);
    }

    public function getUsers(array $filters = [], int $perPage = NumericConstants::DEFAULT_PER_PAGE)
    {
        return $this->userRepository->getUsers($filters, $perPage);
    }

    public function getUserById(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    public function getUserByUuid(string $uuid): ?User
    {
        return $this->userRepository->findByUuid($uuid);
    }

    public function createUser(array $data): array
    {
        try {
            unset($data['allow_type_assignment']);
            
            if (!($data['skip_password'] ?? false)) {
                $data['password'] = Hash::make($data['password']);
            } else {
                $data['password'] = null;
                unset($data['password_confirmation']);
            }
            
            $data['is_approved'] = $data['is_approved'] ?? false;
            
            if (!($data['is_admin_creation'] ?? false) && !($data['allow_type_assignment'] ?? false)) {
                $customerType = \App\Models\TypeOfUser::where('type_user_code', 'customer')->first();
                $data['type_user_code'] = $customerType ? $customerType->id : 1;
            } elseif (empty($data['type_user_code'])) {
                $customerType = \App\Models\TypeOfUser::where('type_user_code', 'customer')->first();
                $data['type_user_code'] = $customerType ? $customerType->id : 1;
            }

            if (!isset($data['status_id'])) {
                $pendingStatus = \App\Models\UserStatus::where('status_code', 'pending')->first();
                $data['status_id'] = $pendingStatus ? $pendingStatus->id : UserStatus::PENDING;
            }
            
            DB::beginTransaction();
            
            $user = $this->userRepository->create($data);

            if (empty($data['role_id']) && $user->isCustomer()) {
                $customerRole = \App\Models\Role::where('name', 'customer')->first();
                if ($customerRole) {
                    $user->roles()->attach($customerRole->id);
                    $user->clearPermissionCache();
                }
            }
            
            if (!empty($data['role_id'])) {
                $user->roles()->sync([$data['role_id']]);
                $user->clearPermissionCache();
            }
            
            $this->auditService->log($data['is_admin_creation'] ? auth()->id() : null, 'create', 'users', $user->id, null, $user->toArray());

            DB::commit();

            DB::afterCommit(function () use ($user) {
                $this->verificationService->sendOtp($user);
            });

            // إرجاع flag يحدد إذا المستخدم مزود خدمة لتوجيه الأدمن
            $isProvider = $user->isProvider();

            return [
                'success' => true,
                'is_provider' => $isProvider,
                'message' => $isProvider
                    ? 'تم إنشاء حساب المزود بنجاح. يرجى إضافة خدمة المزود من شاشة إدارة خدمات المزودين.'
                    : 'تم إنشاء الحساب بنجاح، يرجى تفعيل الحساب عبر الكود المرسل',
                'data' => $user->load(['status:id,status_code', 'userType'])
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create user: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            
            return [
                'success' => false,
                'data' => null,
                'message' => 'حدث خطأ أثناء إنشاء الحساب، يرجى المحاولة لاحقاً',
            ];
        }
    }

    public function updateUser(int $id, array $data, ?int $performingUserId = null): array
    {
        try {
            $user = $this->userRepository->findById($id);
            
            if (!$user) {
                return ['success' => false, 'data' => null, 'message' => 'المستخدم غير موجود'];
            }

            if ($performingUserId && $id === $performingUserId) {
                if (isset($data['status_id']) && (int)$data['status_id'] !== (int)$user->status_id) {
                    return ['success' => false, 'message' => 'لا يمكنك تغيير حالة حسابك الشخصي'];
                }
                if (isset($data['type_user_code']) && (int)$data['type_user_code'] !== (int)$user->type_user_code) {
                    return ['success' => false, 'message' => 'لا يمكنك تغيير نوع حسابك الشخصي'];
                }
            }
            
            if (!empty($data['phone_number']) && $data['phone_number'] !== $user->phone_number) {
                $data['phone_verified_at'] = null;
            }
            
            if (!($data['is_admin_action'] ?? false)) {
                unset($data['type_user_code'], $data['is_approved'], $data['status_id']);
            }
            
            if (array_key_exists('is_approved', $data)) {
                $data['is_approved'] = filter_var($data['is_approved'], FILTER_VALIDATE_BOOLEAN);
            } elseif (isset($data['status_id'])) {
                $data['is_approved'] = ((int)$data['status_id'] === UserStatus::ACTIVE);
            }

            if (isset($data['password']) && $data['password'] !== '') {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }
            
            $oldValues = $this->filterSensitiveData($user, $user->getAttributes());
            
            DB::beginTransaction();
            
            $updated = $this->userRepository->update($id, $data);

            if ($updated) {
                if (isset($data['role_id'])) {
                    $user->roles()->sync([$data['role_id']]);
                    $user->clearPermissionCache();
                }

                $freshUser = $this->userRepository->findById($id);
                $newValues = $this->filterSensitiveData($freshUser, $freshUser->getAttributes());
                
                $changes = array_diff_assoc($newValues, $oldValues);
                if (!empty($changes)) {
                    $this->auditService->log(
                        $performingUserId ?? auth()->id(),
                        'update',
                        'users',
                        $id,
                        array_intersect_key($oldValues, $changes),
                        $changes
                    );
                }
            }
            
            DB::commit();
            
            return [
                'success' => $updated,
                'data' => $this->userRepository->findById($id),
                'message' => $updated ? 'تم تحديث بيانات المستخدم بنجاح' : 'فشل تحديث بيانات المستخدم'
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update user: ' . $e->getMessage(), ['user_id' => $id, 'trace' => $e->getTraceAsString()]);
            
            return [
                'success' => false,
                'data' => null,
                'message' => 'حدث خطأ أثناء تحديث بيانات المستخدم، يرجى المحاولة لاحقاً'
            ];
        }
    }

    public function deleteUser(int $id, ?int $performingUserId = null): array
    {
        try {
            if ($performingUserId && $id === $performingUserId) {
                return ['success' => false, 'message' => 'لا يمكنك حذف حسابك الشخصي من هنا'];
            }

            $user = $this->userRepository->findById($id);
            if (!$user) {
                return ['success' => false, 'data' => null, 'message' => 'المستخدم غير موجود'];
            }
            
            $hasActiveReservations = $user->reservations()
                ->whereHas('trip', fn($q) => $q->where('departure_time', '>', now()))
                ->where('booking_status_id', '!=', BookingStatusConst::CANCELLED)
                ->exists();
                
            if ($hasActiveReservations) {
                return ['success' => false, 'data' => null, 'message' => 'لا يمكن حذف المستخدم لأنه لديه حجوزات نشطة'];
            }
            
            $hasActiveTrips = $user->trips()
                ->where('departure_time', '>', now())
                ->where('trip_status_id', '!=', TripStatusConst::CANCELLED)
                ->exists();
                
            if ($hasActiveTrips) {
                return ['success' => false, 'data' => null, 'message' => 'لا يمكن حذف المستخدم لأنه لديه رحلات نشطة'];
            }
            
            DB::beginTransaction();

            $userData = $user->toArray();

            $user->update(['is_approved' => false, 'status_id' => UserStatus::INACTIVE]);
            $deleted = $this->userRepository->delete($id);
            $user->tokens()->delete();

            DB::afterCommit(function () use ($performingUserId, $id, $userData) {
                $this->auditService->log($performingUserId, 'delete', 'users', $id, $userData, null);
            });

            DB::commit();
            
            return [
                'success' => $deleted,
                'data' => null,
                'message' => $deleted ? 'تم حذف المستخدم بنجاح' : 'فشل حذف المستخدم'
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete user: ' . $e->getMessage(), ['user_id' => $id, 'trace' => $e->getTraceAsString()]);
            
            return [
                'success' => false,
                'data' => null,
                'message' => 'حدث خطأ أثناء حذف المستخدم، يرجى المحاولة لاحقاً'
            ];
        }
    }

    public function restoreUser(int $id, ?int $performingUserId = null): array
    {
        try {
            $user = User::withTrashed()->find($id);
            if (!$user) {
                return ['success' => false, 'data' => null, 'message' => 'المستخدم غير موجود'];
            }
            
            if (!$user->trashed()) {
                return ['success' => false, 'data' => null, 'message' => 'المستخدم غير محذوف'];
            }
            
            DB::beginTransaction();
            
            $user->update(['is_approved' => true, 'status_id' => UserStatus::ACTIVE]);
            $user->restore();
            
            $this->auditService->log($performingUserId, 'restore', 'users', $id, null, $user->toArray());
            
            DB::commit();
            
            return [
                'success' => true,
                'data' => $user,
                'message' => 'تم استعادة المستخدم بنجاح'
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to restore user: ' . $e->getMessage(), ['user_id' => $id, 'trace' => $e->getTraceAsString()]);
            
            return [
                'success' => false,
                'data' => null,
                'message' => 'حدث خطأ أثناء استعادة المستخدم، يرجى المحاولة لاحقاً'
            ];
        }
    }

    public function approveUser(int $id, ?int $performingUserId = null): array
    {
        try {
            $user = $this->userRepository->findById($id);
            if (!$user) {
                return ['success' => false, 'data' => null, 'message' => 'المستخدم غير موجود'];
            }
            
            if ($user->is_approved) {
                return ['success' => false, 'data' => null, 'message' => 'المستخدم مفعل بالفعل'];
            }
            
            DB::beginTransaction();
            
            if ($user->trashed()) {
                $user->restore();
            }
            
            $approved = $this->userRepository->approve($id);
            if ($approved) {
                $user->update(['status_id' => UserStatus::ACTIVE]);

                DB::afterCommit(function () use ($performingUserId, $id, $user) {
                    $this->auditService->log($performingUserId, 'approve', 'users', $id, null, $user->fresh()->toArray());
                });
            }

            DB::commit();
            
            return [
                'success' => $approved,
                'data' => $this->userRepository->findById($id),
                'message' => $approved ? 'تم تفعيل الحساب بنجاح' : 'فشل تفعيل الحساب'
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve user: ' . $e->getMessage(), ['user_id' => $id, 'trace' => $e->getTraceAsString()]);
            
            return [
                'success' => false,
                'data' => null,
                'message' => 'حدث خطأ أثناء تفعيل الحساب، يرجى المحاولة لاحقاً'
            ];
        }
    }

    public function getUserStatistics(): array
    {
        return $this->userRepository->getStatistics();
    }

    public function searchUsers(string $search, int $perPage = NumericConstants::DEFAULT_PER_PAGE)
    {
        return $this->userRepository->search($search, $perPage);
    }


    private function filterSensitiveData(User $user, array $data): array
    {
        $hiddenFields = $user->getHidden();
        foreach ($hiddenFields as $field) {
            unset($data[$field]);
        }
        return $data;
    }

    public function updateProfile(int $userId, array $data): array
    {
        try {
            $user = $this->userRepository->findById($userId);
            if (!$user) {
                return ['success' => false, 'message' => 'المستخدم غير موجود.'];
            }

            $oldValues = $this->filterSensitiveData($user, $user->getAttributes());
            $phoneChanged = !empty($data['phone_number']) && $data['phone_number'] !== $user->phone_number;

            $updateData = [
                'name' => $data['name'] ?? $user->name,
                'email' => $data['email'] ?? $user->email,
                'phone_number' => $data['phone_number'] ?? $user->phone_number,
            ];
            
            if (isset($data['profile_photo'])) {
                $updateData['profile_photo'] = $data['profile_photo'];
            }
            
            if ($phoneChanged) {
                $updateData['phone_verified_at'] = null;
            }
            
            if (!empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }

            DB::beginTransaction();
            $this->userRepository->update($userId, $updateData);

            $freshUser = $this->userRepository->findById($userId);
            $newValues = $this->filterSensitiveData($freshUser, $freshUser->getAttributes());
            $changes = array_diff_assoc($newValues, $oldValues);
            
            if (!empty($changes)) {
                $this->auditService->log(
                    $userId,
                    'update',
                    'users',
                    $userId,
                    array_intersect_key($oldValues, $changes),
                    $changes
                );
            }
            
            DB::commit();

            return [
                'success' => true,
                'message' => 'تم تحديث بياناتك الشخصية بنجاح',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update user profile: ' . $e->getMessage(), [
                'user_id' => $userId,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث البيانات الشخصية، يرجى المحاولة لاحقاً'
            ];
        }
    }
}
