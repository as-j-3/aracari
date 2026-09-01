<?php

namespace App\Services;

use App\Constants\UserTypes; 
use \App\Constants\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log; 

class ProviderStaffService
{

    public function getAllowedRoles(User $user): Collection
    {
        if ($user->isProviderOwner() || $user->hasPermission('provider:staff:create:all')) {
            return Role::whereIn('name', ['provider_staff', 'branch_manager', 'branch_staff'])->get();
        }
        
        if ($user->hasPermission('provider:staff:create:branch')) {
            return Role::where('name', 'branch_staff')->get();
        }
        
        if ($user->hasPermission('provider:staff:create')) {
            return Role::whereIn('name', ['provider_staff', 'branch_manager', 'branch_staff'])->get();
        }
        
        return collect();
    }

    public function getProviderOwnerId(User $user): ?int
    {
        if ($user->isProviderOwner()) return $user->id;
        if ($user->isProviderStaff()) return $user->user_parent;
        return null;
    }

    public function getStaffMembers(User $user): Collection
    {
        if ($user->isAdmin()) {
            return User::where('user_parent', '!=', null)
                ->where('type_user_code', UserTypes::PROVIDER) 
                ->with(['branches', 'roles', 'parent'])
                ->get();
        }
        
        if ($user->isProviderOwner()) {
            return User::where('user_parent', $user->id)
                ->where('type_user_code', UserTypes::PROVIDER) 
                ->with(['branches', 'roles'])
                ->get();
        }
        
        if ($user->isProviderStaff()) {
            $providerOwnerId = $user->user_parent;
            return User::where('user_parent', $providerOwnerId)
                ->where('type_user_code', UserTypes::PROVIDER) 
                ->with(['branches', 'roles'])
                ->get();
        }
        
        return collect();
    }

    public function createStaff(User $creator, array $data): array
    {
        // Validate allowed roles
        $allowedRoles = $this->getAllowedRoles($creator);
        $allowedRoleNames = $allowedRoles->pluck('name')->toArray();
        if (!in_array($data['role'], $allowedRoleNames)) {
            return ['success' => false, 'message' => 'الدور غير مسموح لك'];
        }

        // Validate branches
        if ($data['role'] !== 'provider_staff' && empty($data['branch_ids'])) {
            return ['success' => false, 'message' => 'يجب اختيار فرع واحد على الأقل لهذا الدور'];
        }

        // Validate branch access
        if (!empty($data['branch_ids'])) {
            $accessibleBranchIds = $creator->getAccessibleBranches()->pluck('id')->toArray();
            $invalidBranches = array_diff($data['branch_ids'], $accessibleBranchIds);
            if (!empty($invalidBranches)) {
                return ['success' => false, 'message' => 'عذراً، لا يمكنك إضافة موظفين للفروع المحددة'];
            }
        }

        $providerOwnerId = $creator->isProviderOwner() ? $creator->id : $creator->user_parent;

        try {
            DB::beginTransaction();

            $newUser = User::create([
                'uuid' => (string) Str::uuid(),
                'name' => $data['name'],
                'email' => $data['email'],
                'phone_number' => $data['phone_number'],
                'password' => Hash::make($data['password']),
                'type_user_code' => UserTypes::PROVIDER,
                'user_parent' => $providerOwnerId,
                'status_id' => UserStatus::ACTIVE,
                'is_approved' => true,
            ]);

            $role = Role::where('name', $data['role'])->first();
            if ($role) {
                $newUser->roles()->attach($role->id, ['granted_by' => $creator->id]);
            }

            if (!empty($data['branch_ids'])) {
                $newUser->branches()->attach($data['branch_ids']);
            }

            DB::commit();
            return ['success' => true, 'message' => 'تم إضافة الموظف بنجاح', 'user' => $newUser];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Provider staff create failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ أثناء إضافة الموظف'];
        }
    }

    public function updateStaff(User $editor, User $staff, array $data): array
    {
        if ($staff->id === $editor->id) {
            return ['success' => false, 'message' => 'لا يمكنك تعديل حسابك الخاص'];
        }

        $providerOwnerId = $this->getProviderOwnerId($editor);
        if ($staff->user_parent !== $providerOwnerId) {
            return ['success' => false, 'message' => 'غير مصرح لك بتعديل هذا الموظف'];
        }

        if (!empty($data['role'])) {
            $allowedRoles = $this->getAllowedRoles($editor);
            if (!in_array($data['role'], $allowedRoles->pluck('name')->toArray())) {
                return ['success' => false, 'message' => 'الدور غير مسموح لك'];
            }
        }

        if (isset($data['branch_ids']) && !empty($data['branch_ids'])) {
            $accessibleBranchIds = $editor->getAccessibleBranches()->pluck('id')->toArray();
            $invalidBranches = array_diff($data['branch_ids'], $accessibleBranchIds);
            if (!empty($invalidBranches)) {
                return ['success' => false, 'message' => 'عذراً، لا يمكنك ربط الموظف بالفروع المحددة'];
            }
        }

        try {
            DB::beginTransaction();

            $staff->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone_number' => $data['phone_number'],
            ]);

            if (!empty($data['password'])) {
                $staff->update(['password' => Hash::make($data['password'])]);
            }

            if (!empty($data['role'])) {
                $staff->roles()->detach();
                $role = Role::where('name', $data['role'])->first();
                if ($role) {
                    $staff->roles()->attach($role->id, ['granted_by' => $providerOwnerId]);
                }
            }

            if (isset($data['branch_ids'])) {
                $staff->branches()->sync($data['branch_ids']);
            }

            DB::commit();

            return [
                'success' => true, 
                'message' => 'تم تحديث بيانات الموظف بنجاح',
                'old_data' => $this->sanitizeStaffData($staff->getOriginal()), //  فلتر
                'new_data' => $this->sanitizeStaffData($staff->fresh()->toArray()) //  فلتر
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Provider staff update failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ أثناء تحديث الموظف'];
        }
    }

    public function deleteStaff(User $deleter, User $staff): array
    {
        if ($staff->id === $deleter->id) {
            return ['success' => false, 'message' => 'لا يمكنك حذف حسابك الخاص'];
        }

        if ($staff->isProviderOwner()) {
            return ['success' => false, 'message' => 'لا يمكن حذف مالك المزود'];
        }

        $providerOwnerId = $this->getProviderOwnerId($deleter);
        if ($staff->user_parent !== $providerOwnerId) {
            return ['success' => false, 'message' => 'غير مصرح لك بحذف هذا الموظف'];
        }

        try {
            DB::beginTransaction();

            $oldData = $this->sanitizeStaffData($staff->toArray()); //  فلتر

            $staff->branches()->detach();
            $staff->roles()->detach();
            $staff->delete();

            DB::commit();

            return [
                'success' => true, 
                'message' => 'تم حذف الموظف بنجاح',
                'old_data' => $oldData
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Provider staff delete failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ أثناء حذف الموظف'];
        }
    }

    /**
     *  إخفاء الحقول الحساسة من بيانات الموظف
     * منع تسريب password hash و remember_token
     */
    private function sanitizeStaffData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'remember_token',
            'phone_verified_at',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }
}