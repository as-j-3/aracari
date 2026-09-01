<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BranchService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    use ApiResponse;

    protected BranchService $branchService;


    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
    }

 
    public function index(): JsonResponse
    {
        $isAdmin = auth()->user()->isAdmin();
        $providerId = auth()->user()->providerService?->id;

        $branches = $this->branchService->getAllBranchesWithUserCount($isAdmin ? null : $providerId);

        return $this->success($branches, 'تم جلب الفروع بنجاح');
    }

    public function show(int $id): JsonResponse
    {
        $isAdmin = auth()->user()->isAdmin();
        $providerId = auth()->user()->providerService?->id;

        $users = $this->branchService->getBranchUsers($id, $isAdmin ? null : $providerId);

        if ($users->isEmpty()) {
            return $this->notFound('الفرع غير موجود أو لا يوجد مستخدمين');
        }

        return $this->success($users, 'تم جلب مستخدمي الفرع بنجاح');
    }


    public function assignUser(Request $request): JsonResponse
    {
    if (!auth()->user()->isAdmin() && !auth()->user()->hasPermission('branches:edit')) {
        return $this->error('غير مصرح لك', 403);
    }

    $data = $request->validate([
        'user_id' => 'required|exists:users,id',
        'branch_id' => 'required|exists:provider_branches,id',
        'role' => 'in:manager,staff',
    ]);

    $targetUser = \App\Models\User::find($data['user_id']);
    $currentProviderId = auth()->user()->providerService?->id;
    $targetProviderId = $targetUser->providerService?->id;

    if (!auth()->user()->isAdmin()) {
        if (!$currentProviderId || $targetProviderId !== $currentProviderId) {
            return $this->error('المستخدم لا ينتمي لمزود الخدمة الخاص بك', 403);
        }

        $branch = \App\Models\ProviderBranch::find($data['branch_id']);
        if (!$branch || $branch->provider_id !== $currentProviderId) {
            return $this->error('الفرع لا ينتمي لمزود الخدمة الخاص بك', 403);
        }
    }

    $result = $this->branchService->assignUserToBranch(
        $data['user_id'],
        $data['branch_id'],
        $data['role'] ?? 'staff'
    );

    if (!$result['success']) {
        return $this->error($result['message'], 400);
    }

    return $this->success($result['data'], $result['message']);
}



    public function removeUser(Request $request): JsonResponse
    {
        if (!auth()->user()->isAdmin() && !auth()->user()->hasPermission('branches:edit')) {
            return $this->error('غير مصرح لك', 403);
        }

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:provider_branches,id',
        ]);

        $targetUser = \App\Models\User::find($data['user_id']);
        $currentProviderId = auth()->user()->providerService?->id;
        $targetProviderId = $targetUser->providerService?->id;

        if (!auth()->user()->isAdmin()) {
            if (!$currentProviderId || $targetProviderId !== $currentProviderId) {
                return $this->error('المستخدم لا ينتمي لمزود الخدمة الخاص بك', 403);
            }

            $branch = \App\Models\ProviderBranch::find($data['branch_id']);
            if (!$branch || $branch->provider_id !== $currentProviderId) {
                return $this->error('الفرع لا ينتمي لمزود الخدمة الخاص بك', 403);
            }
        }

        $result = $this->branchService->removeUserFromBranch(
            $data['user_id'],
            $data['branch_id']
        );

        if (!$result['success']) {
            return $this->error($result['message'], 400);
        }

        return $this->success(null, $result['message']);
    }



    public function updateUserRole(Request $request): JsonResponse
    {
        if (!auth()->user()->isAdmin() && !auth()->user()->hasPermission('branches:edit')) {
            return $this->error('غير مصرح لك', 403);
        }

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:provider_branches,id',
            'role' => 'required|in:manager,staff',
        ]);

        $targetUser = \App\Models\User::find($data['user_id']);
        $currentProviderId = auth()->user()->providerService?->id;
        $targetProviderId = $targetUser->providerService?->id;

        if (!auth()->user()->isAdmin()) {
            if (!$currentProviderId || $targetProviderId !== $currentProviderId) {
                return $this->error('المستخدم لا ينتمي لمزود الخدمة الخاص بك', 403);
            }

            $branch = \App\Models\ProviderBranch::find($data['branch_id']);
            if (!$branch || $branch->provider_id !== $currentProviderId) {
                return $this->error('الفرع لا ينتمي لمزود الخدمة الخاص بك', 403);
            }
        }

        $result = $this->branchService->updateUserRoleInBranch(
            $data['user_id'],
            $data['branch_id'],
            $data['role'],
            auth()->id() 
        );

        if (!$result['success']) {
            return $this->error($result['message'], 400);
        }

        return $this->success($result['data'], $result['message']);
    }



    public function getUserBranches(int $userId): JsonResponse
    {
        $isAdmin = auth()->user()->isAdmin();

        if (!$isAdmin && $userId !== auth()->id()) {
            return $this->error('غير مصرح لك بعرض فروع المستخدمين الآخرين', 403);
        }

        $branches = $this->branchService->getUserBranches($userId);

        return $this->success($branches, 'تم جلب فروع المستخدم بنجاح');
    }

    public function checkAccess(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:provider_branches,id',
        ]);

        $isAdmin = auth()->user()->isAdmin();

        if (!$isAdmin && $data['user_id'] !== auth()->id()) {
            return $this->error('غير مصرح لك بالتحقق من صلاحيات المستخدمين الآخرين', 403);
        }

        $hasAccess = $this->branchService->canUserAccessBranch(
            $data['user_id'],
            $data['branch_id']
        );

        return $this->success(['has_access' => $hasAccess], 'تم التحقق من الصلاحية');
    }
}
