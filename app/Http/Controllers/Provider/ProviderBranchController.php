<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\Branch\StoreBranchRequest;
use App\Http\Requests\Provider\Branch\UpdateBranchRequest;
use App\Http\Requests\Provider\Branch\IndexBranchRequest;
use App\Http\Requests\Provider\Branch\AddBranchStaffRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\ProviderBranch;
use App\Models\User;
use App\Models\BranchUser;
use App\Models\Service;   
use App\Models\Trip;      
use App\Constants\BranchStatus;
use App\Constants\UserStatus;
use App\Constants\UserTypes;
use App\Services\ProviderContext;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Database\Eloquent\Builder;

class ProviderBranchController extends Controller
{
    public function __construct(
        private ProviderContext $providerContext,
        private AuditService $auditService
    ) {
        $this->middleware('auth');
    }


    public function index(IndexBranchRequest $request): View
    {
        $providerServiceId = $this->getProviderServiceId();

        $query = ProviderBranch::where('provider_id', $providerServiceId)
            ->with(['city', 'country']);

        if ($search = $request->get('search')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(function (Builder $q) use ($escaped) {
                $q->where('address', 'like', "%{$escaped}%")
                  ->orWhere('phone_number', 'like', "%{$escaped}%")
                  ->orWhereHas('city', fn($sq) => $sq->where('name', 'like', "%{$escaped}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $branches = $query
            ->withCount(['branchUsers', 'trips'])
            ->latest()
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        $cities = $this->getCities();

        return view('provider.branches.index', compact('branches', 'cities'));
    }

    public function create(): View
    {
        $this->authorize('create', ProviderBranch::class);

        $cities = $this->getCities();
        $countries = $this->getCountries();

        return view('provider.branches.create', compact('cities', 'countries'));
    }

    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $this->authorize('create', ProviderBranch::class);

        $providerServiceId = $this->getProviderServiceId();
        $validated = $request->validated();
        $validated['provider_id'] = $providerServiceId;
        $validated['status'] = BranchStatus::ACTIVE;

        try {
            DB::beginTransaction();

            $branch = ProviderBranch::create($validated);

            DB::afterCommit(function () use ($branch) {
                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'provider_branches',
                    $branch->id,
                    null,
                    $branch->fresh()->toArray()
                );
            });

            DB::commit();

            return redirect()
                ->route('provider.branches.show', $branch->uuid)
                ->with('success', 'تم إنشاء الفرع بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Provider branch store failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withInput()
                ->with('error', 'فشل إنشاء الفرع. يرجى المحاولة مرة أخرى.');
        }
    }


    public function show(ProviderBranch $branch): View
    {
        $this->authorize('access', $branch);

        $branch->load([
            'city', 'country',
            'branchUsers.user.roles',
        ]);

        $stats = cache()->remember(
            "branch_stats_{$branch->id}",
            300,
            function () use ($branch) {
                $activeTrips = Trip::whereHas('service', fn($q) => $q->where('branch_id', $branch->id))
                    ->where('departure_time', '>=', now())
                    ->count();
                    
                $totalTrips = Trip::whereHas('service', fn($q) => $q->where('branch_id', $branch->id))
                    ->count();

                return [
                    'staff_count'   => $branch->branchUsers()->count(),
                    'active_trips'  => $activeTrips,
                    'total_trips'   => $totalTrips,
                ];
            }
        );

        $providerUserId = $this->providerContext->providerUserIdFor(auth()->user());
        $availableStaff = User::where('status_id', UserStatus::ACTIVE)
            ->where('type_user_code', UserTypes::PROVIDER)
            ->where(function ($q) use ($providerUserId) {
                $q->where('id', $providerUserId)
                  ->orWhere('user_parent', $providerUserId);
            })
            ->whereDoesntHave('branches', fn($q) => $q->where('branch_id', $branch->id))
            ->orderBy('name')
            ->get(['id', 'name', 'uuid']);

        return view('provider.branches.show', compact('branch', 'stats', 'availableStaff'));
    }

    public function edit(ProviderBranch $branch): View
    {
        $this->authorize('update', $branch);

        $cities = $this->getCities();
        $countries = $this->getCountries();

        return view('provider.branches.edit', compact('branch', 'cities', 'countries'));
    }

    public function update(UpdateBranchRequest $request, ProviderBranch $branch): RedirectResponse
    {
        $this->authorize('update', $branch);

        $oldData = $branch->toArray();
        $validated = $request->validated();
        unset($validated['provider_id']);

        try {
            DB::beginTransaction();

            $branch->update($validated);

            DB::afterCommit(function () use ($branch, $oldData) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'provider_branches',
                    $branch->id,
                    $oldData,
                    $branch->fresh()->toArray()
                );

                cache()->forget("branch_stats_{$branch->id}");
            });

            DB::commit();

            return redirect()
                ->route('provider.branches.show', $branch->uuid)
                ->with('success', 'تم تحديث الفرع بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Provider branch update failed', [
                'error' => $e->getMessage(),
                'id' => $branch->id,
            ]);
            return back()->withInput()
                ->with('error', 'فشل تحديث الفرع. يرجى المحاولة مرة أخرى.');
        }
    }

    public function destroy(ProviderBranch $branch): RedirectResponse
    {
        $this->authorize('delete', $branch);

        $activeTrips = Trip::whereHas('service', fn($q) => $q->where('branch_id', $branch->id))
            ->where('departure_time', '>=', now())
            ->count();
            
        if ($activeTrips > 0) {
            return back()->with('error', 'لا يمكن حذف الفرع لوجود رحلات قادمة مرتبطة به');
        }

        $branchId = $branch->id;
        $oldData = $branch->toArray();

        try {
            DB::beginTransaction();

            $branch->delete();

            DB::afterCommit(function () use ($branchId, $oldData) {
                $this->auditService->log(
                    auth()->id(),
                    'delete',
                    'provider_branches',
                    $branchId,
                    $oldData,
                    null
                );

                cache()->forget("branch_stats_{$branchId}");
            });

            DB::commit();

            return redirect()
                ->route('provider.branches.index')
                ->with('success', 'تم حذف الفرع بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Provider branch destroy failed', [
                'error' => $e->getMessage(),
                'id' => $branch->id,
            ]);
            return back()
                ->with('error', 'فشل حذف الفرع. يرجى المحاولة مرة أخرى.');
        }
    }

    public function toggleStatus(ProviderBranch $branch): RedirectResponse
    {
        $this->authorize('update', $branch);

        $oldStatus = $branch->status;
        $newStatus = BranchStatus::toggle($oldStatus);

        try {
            DB::beginTransaction();

            $branch->status = $newStatus;
            $branch->save();

            DB::afterCommit(function () use ($branch, $oldStatus, $newStatus) {
                $this->auditService->log(
                    auth()->id(),
                    'toggle-status',
                    'provider_branches',
                    $branch->id,
                    ['status' => $oldStatus],
                    ['status' => $newStatus]
                );

                cache()->forget("branch_stats_{$branch->id}");
            });

            DB::commit();

            $label = $newStatus === BranchStatus::ACTIVE ? 'تفعيل' : 'إيقاف';
            return back()->with('success', "تم {$label} الفرع بنجاح");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Branch toggle status failed', [
                'error' => $e->getMessage(),
                'id' => $branch->id,
            ]);
            return back()->with('error', 'فشل تغيير حالة الفرع.');
        }
    }

    public function addStaff(AddBranchStaffRequest $request, ProviderBranch $branch): RedirectResponse
    {
        $this->authorize('access', $branch);

        $validated = $request->validated();
        $userId = $validated['user_id'];

        $targetUser = User::find($userId);
        if (!$targetUser || !$targetUser->isProvider()) {
            return back()->with('error', 'يمكن إضافة المزودين فقط إلى الفروع');
        }

        $providerUserId = $this->providerContext->providerUserIdFor(auth()->user());
        $isOwner = $targetUser->id === $providerUserId;
        $isStaffOfProvider = $targetUser->user_parent === $providerUserId;

        if (!$isOwner && !$isStaffOfProvider) {
            return back()->with('error', 'غير مصرح لك بإضافة مستخدمين من مزود آخر');
        }

        if (BranchUser::where('user_id', $userId)->where('branch_id', $branch->id)->exists()) {
            return back()->with('error', 'المستخدم مرتبط بالفعل بهذا الفرع');
        }

        try {
            DB::beginTransaction();

            $branchUser = BranchUser::create([
                'user_id' => $userId,
                'branch_id' => $branch->id,
            ]);

            DB::afterCommit(function () use ($branch, $branchUser) {
                $this->auditService->log(
                    auth()->id(),
                    'staff-added',
                    'provider_branches',
                    $branch->id,
                    null,
                    ['user_id' => $branchUser->user_id]
                );

                cache()->forget("branch_stats_{$branch->id}");
            });

            DB::commit();

            return back()->with('success', 'تم إضافة الموظف إلى الفرع بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Add staff to branch failed', [
                'error' => $e->getMessage(),
                'branch' => $branch->id,
                'user' => $userId,
            ]);
            return back()->with('error', 'فشل إضافة الموظف.');
        }
    }

    public function removeStaff(ProviderBranch $branch, int $userId): RedirectResponse
    {
        $this->authorize('access', $branch);

        $branchUser = BranchUser::where('branch_id', $branch->id)
            ->where('user_id', $userId)
            ->first();

        if (!$branchUser) {
            return back()->with('error', 'المستخدم غير مرتبط بهذا الفرع');
        }

        try {
            DB::beginTransaction();

            $branchUser->delete();

            DB::afterCommit(function () use ($branch, $userId) {
                $this->auditService->log(
                    auth()->id(),
                    'staff-removed',
                    'provider_branches',
                    $branch->id,
                    ['user_id' => $userId],
                    null
                );

                cache()->forget("branch_stats_{$branch->id}");
            });

            DB::commit();

            return back()->with('success', 'تم إزالة الموظف من الفرع بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Remove staff from branch failed', [
                'error' => $e->getMessage(),
                'branch' => $branch->id,
                'user' => $userId,
            ]);
            return back()->with('error', 'فشل إزالة الموظف.');
        }
    }

    private function getProviderServiceId(): int
    {
        $providerServiceId = $this->providerContext->providerServiceIdFor(auth()->user());

        if (!$providerServiceId) {
            abort(403, 'لا يوجد مزود خدمة مرتبط بحسابك');
        }

        return $providerServiceId;
    }

    private function getCities()
    {
        return cache()->remember('cities-list', 3600, function () {
            return City::with('country')->orderBy('name')->get(['id', 'name', 'country_id']);
        });
    }

    private function getCountries()
    {
        return cache()->remember('countries-list', 3600, function () {
            return Country::orderBy('name')->get(['id', 'name']);
        });
    }
}