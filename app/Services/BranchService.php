<?php

namespace App\Services;

use App\Models\ProviderBranch;
use App\Models\BranchUser;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Collection;

class BranchService
{
    /**
     * جلب الفرع المناسب بناءً على الرحلة
     */
    public function getBranchByTrip(Trip $trip): ?ProviderBranch
    {
        // جلب أول فرع لمزود الخدمة
        return $trip->user->providerService()
            ->with('providerBranches')
            ->first()
            ?->providerBranches
            ->first();
    }

    /**
     * تعيين مستخدم لفرع معين
     */
    public function assignUserToBranch(int $userId, int $branchId, string $role = 'staff'): array
    {
        // التحقق من وجود المستخدم والفرع
        $user = User::find($userId);
        $branch = ProviderBranch::find($branchId);

        if (!$user) {
            return ['success' => false, 'message' => 'المستخدم غير موجود'];
        }

        if (!$branch) {
            return ['success' => false, 'message' => 'الفرع غير موجود'];
        }

        // التحقق من الربط المسبق
        $existing = BranchUser::where('user_id', $userId)
            ->where('branch_id', $branchId)
            ->first();

        if ($existing) {
            return ['success' => false, 'message' => 'المستخدم مرتبط بالفرع بالفعل'];
        }

        // إنشاء الربط الجديد (بدون role - يتم الحصول عليه من UserRole)
        BranchUser::create([
            'user_id' => $userId,
            'branch_id' => $branchId,
        ]);

        return [
            'success' => true, 
            'message' => 'تم ربط المستخدم بالفرع بنجاح',
            'data' => [
                'user_id' => $userId,
                'branch_id' => $branchId,
                'role' => $user->userRole?->name ?? 'غير محدد', // الحصول على الدور من UserRole
                'branch_name' => $branch->address,
                'user_name' => $user->name,
            ]
        ];
    }

    /**
     * جلب المستخدمين المرتبطين بفرع معين
     *
     * @param int $branchId Branch ID
     * @param int|null $providerId Filter by provider ID (null for all branches)
     * @return Collection Branch users
     */
    public function getBranchUsers(int $branchId, ?int $providerId = null): Collection
    {
        $branch = ProviderBranch::find($branchId);

        if (!$branch) {
            return collect();
        }

        if ($providerId && $branch->provider_id !== $providerId) {
            return collect();
        }

        return BranchUser::where('branch_id', $branchId)
            ->with('user.userRole')
            ->get()
            ->map(function ($branchUser) {
                return [
                    'id' => $branchUser->user->id,
                    'name' => $branchUser->user->name,
                    'email' => $branchUser->user->email,
                    'role' => $branchUser->user->userRole?->name ?? 'غير محدد',
                    'assigned_at' => $branchUser->created_at,
                ];
            });
    }

    /**
     * فك ربط مستخدم من فرع
     */
    public function removeUserFromBranch(int $userId, int $branchId): array
    {
        $branchUser = BranchUser::where('user_id', $userId)
            ->where('branch_id', $branchId)
            ->first();

        if (!$branchUser) {
            return ['success' => false, 'message' => 'الربط غير موجود'];
        }

        $branchUser->delete();

        return ['success' => true, 'message' => 'تم فك ربط المستخدم من الفرع بنجاح'];
    }

    /**
     * تحديث دور المستخدم في الفرع (تم تعديله للعمل مع UserRole)
     */
    public function updateUserRoleInBranch(int $userId, int $branchId, string $role, ?int $actorId = null): array
    {
        $branchUser = BranchUser::where('user_id', $userId)
            ->where('branch_id', $branchId)
            ->first();

        if (!$branchUser) {
            return ['success' => false, 'message' => 'الربط غير موجود'];
        }

        // تحديث دور المستخدم في نظام UserRole
        $user = User::find($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'المستخدم غير موجود'];
        }

        // Find the role model
        $roleModel = \App\Models\Role::where('name', $role)->first();
        if (!$roleModel) {
            return ['success' => false, 'message' => 'الدور المحدد غير موجود'];
        }

        // Update user's role via pivot table with proper audit
        DB::transaction(function () use ($user, $roleModel, $branchUser, $role) {
            // Remove existing roles from user for this branch context
            $user->roles()->wherePivot('granted_by', $branchUser->branch->provider_id ?? null)->detach();
            
            // Attach new role
            $user->roles()->attach($roleModel->id, [
                'granted_by' => $actorId ?? $branchUser->branch->provider_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update branch_user record if role column exists
            if (in_array('role', \Schema::getColumnListing('branch_users'))) {
                $branchUser->update(['role' => $role, 'updated_at' => now()]);
            }
        });

        return [
            'success' => true, 
            'message' => 'تم تحديث دور المستخدم بنجاح',
            'role' => $role,
            'user_id' => $userId,
            'branch_id' => $branchId,
        ];
    }

    /**
     * جلب فروع المستخدم
     */
    public function getUserBranches(int $userId): Collection
    {
        return BranchUser::where('user_id', $userId)
            ->with('branch.user.userRole')
            ->get()
            ->map(function ($branchUser) {
                return [
                    'id' => $branchUser->branch->id,
                    'address' => $branchUser->branch->address,
                    'phone_number' => $branchUser->branch->phone_number,
                    'role' => $branchUser->user->userRole?->name ?? 'غير محدد', // الحصول على الدور من UserRole
                    'assigned_at' => $branchUser->created_at,
                ];
            });
    }

    /**
     * التحقق مما إذا كان المستخدم يمكنه الوصول إلى فرع معين
     */
    public function canUserAccessBranch(int $userId, int $branchId): bool
    {
        return BranchUser::where('user_id', $userId)
            ->where('branch_id', $branchId)
            ->exists();
    }

    /**
     * جلب جميع الفروع مع عدد المستخدمين
     *
     * @param int|null $providerId Filter by provider ID (null for all branches)
     * @return Collection Branches with user counts
     */
    public function getAllBranchesWithUserCount(?int $providerId = null): Collection
    {
        $query = ProviderBranch::with(['branchUsers.user.userRole', 'provider']);

        if ($providerId) {
            $query->where('provider_id', $providerId);
        }

        return $query->get()
            ->map(function ($branch) {
                return [
                    'id' => $branch->id,
                    'address' => $branch->address,
                    'phone_number' => $branch->phone_number,
                    'status' => $branch->status,
                    'provider_name' => $branch->provider->name,
                    'users_count' => $branch->branchUsers->count(),
                    'users' => $branch->branchUsers->map(function ($branchUser) {
                        return [
                            'id' => $branchUser->user->id,
                            'name' => $branchUser->user->name,
                            'role' => $branchUser->user->userRole?->name ?? 'غير محدد', // الحصول على الدور من UserRole
                        ];
                    }),
                ];
            });
    }
}
