<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\Provider\StoreProviderStaffRequest;
use App\Http\Requests\Provider\UpdateProviderStaffRequest;
use App\Services\AuditService;
use App\Services\ProviderStaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ProviderStaffController extends Controller
{
    protected AuditService $auditService;
    protected ProviderStaffService $providerStaffService;

    public function __construct(AuditService $auditService, ProviderStaffService $providerStaffService)
    {
        $this->auditService = $auditService;
        $this->providerStaffService = $providerStaffService;
    }

    public function index(): View
    {
        $staffMembers = $this->providerStaffService->getStaffMembers(auth()->user());
        return view('provider.staff.index', compact('staffMembers'));
    }


    public function create(): View
    {
        $user = auth()->user();
        $branches = $user->getAccessibleBranches();
        $roles = $this->providerStaffService->getAllowedRoles($user);

        return view('provider.staff.create', compact('branches', 'roles'));
    }


    public function store(StoreProviderStaffRequest $request): RedirectResponse
    {
        $creator = auth()->user();
        $validated = $request->validated();

        try {
            $result = $this->providerStaffService->createStaff($creator, $validated);

            if (!$result['success']) {
                return back()->withInput()->with('error', $result['message']);
            }

            $this->auditService->log(
                auth()->id(),
                'create',
                'users',
                $result['user']->id,
                null,
                [
                    'uuid' => $result['user']->uuid,
                    'name' => $result['user']->name,
                    'email' => $result['user']->email,
                    'role' => $validated['role'],
                    'branch_ids' => $validated['branch_ids'] ?? [],
                    'type' => 'provider_staff',
                ]
            );

            return redirect()->route('provider.staff.index')
                ->with('success', $result['message']);
        } catch (\Exception $e) {
            Log::error('Provider staff store failed: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'حدث خطأ أثناء إضافة الموظف. يرجى المحاولة مرة أخرى.');
        }
    }


    public function edit(string $uuid): View|RedirectResponse
    {
        $currentUser = auth()->user();
        $providerOwnerId = $this->providerStaffService->getProviderOwnerId($currentUser);

        $staff = User::where('uuid', $uuid)
            ->where('user_parent', $providerOwnerId)
            ->with(['branches', 'roles'])
            ->first();

        if (!$staff) {
            return redirect()->route('provider.staff.index')
                ->with('error', 'الموظف غير موجود أو لا ينتمي لك.');
        }

        $branches = $currentUser->getAccessibleBranches();
        $roles = $this->providerStaffService->getAllowedRoles($currentUser);
        $userBranchIds = $staff->branches->pluck('id')->toArray();

        return view('provider.staff.edit', compact('staff', 'branches', 'roles', 'userBranchIds'));
    }


    public function update(UpdateProviderStaffRequest $request, string $uuid): RedirectResponse
    {
        $currentUser = auth()->user();
        $providerOwnerId = $this->providerStaffService->getProviderOwnerId($currentUser);

        $staff = User::where('uuid', $uuid)
            ->where('user_parent', $providerOwnerId)
            ->first();

        if (!$staff) {
            return redirect()->route('provider.staff.index')
                ->with('error', 'الموظف غير موجود أو لا ينتمي لك.');
        }

        $validated = $request->validated();

        try {
            $result = $this->providerStaffService->updateStaff($currentUser, $staff, $validated);

            if (!$result['success']) {
                return back()->withInput()->with('error', $result['message']);
            }

            $this->auditService->log(
                auth()->id(),
                'update',
                'users',
                $staff->id,
                $result['old_data'],
                $result['new_data']
            );

            return redirect()->route('provider.staff.index')
                ->with('success', $result['message']);
        } catch (\Exception $e) {
            Log::error('Provider staff update failed: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث الموظف. يرجى المحاولة مرة أخرى.');
        }
    }


    public function destroy(string $uuid): RedirectResponse
    {
        $currentUser = auth()->user();
        $providerOwnerId = $this->providerStaffService->getProviderOwnerId($currentUser);

        $staff = User::where('uuid', $uuid)
            ->where('user_parent', $providerOwnerId)
            ->first();

        if (!$staff) {
            return redirect()->route('provider.staff.index')
                ->with('error', 'الموظف غير موجود أو لا ينتمي لك.');
        }

        try {
            $result = $this->providerStaffService->deleteStaff($currentUser, $staff);

            if (!$result['success']) {
                return back()->with('error', $result['message']);
            }

            $this->auditService->log(
                auth()->id(),
                'delete',
                'users',
                $staff->id,
                $result['old_data'],
                null
            );

            return redirect()->route('provider.staff.index')
                ->with('success', $result['message']);
        } catch (\Exception $e) {
            Log::error('Provider staff destroy failed: ' . $e->getMessage());
            return back()->with('error', 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.');
        }
    }
}
