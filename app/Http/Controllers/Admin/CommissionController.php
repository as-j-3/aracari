<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Commission\AdminIndexCommissionRequest;
use App\Http\Requests\Web\Commission\CreateCommissionRequest;
use App\Http\Requests\Web\Commission\UpdateCommissionRequest;
use App\Models\Commission;
use App\Models\BookingCommission;
use App\Models\ServiceCommission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CommissionsExport;
use App\Services\AuditService;

class CommissionController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)   
    {
        $this->auditService = $auditService; 
        $this->middleware('require.admin');
    }

    public function index(AdminIndexCommissionRequest $request): View
    {
        $query = Commission::with([
            'serviceCommissions.provider:id,name',
            'serviceCommissions.service:id,name',
            'bookingCommissions.provider:id,name',
        ]);

        if ($search = $request->validated('search')) {
            $safeSearch = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where(function ($q) use ($safeSearch) {
                $q->where('description', 'like', "%{$safeSearch}%")
                  ->orWhere('commission_type', 'like', "%{$safeSearch}%")
                  ->orWhere('calculation_level', 'like', "%{$safeSearch}%");
            });
        }

        if ($type = $request->validated('type')) $query->where('commission_type', $type);
        if ($level = $request->validated('level')) $query->where('calculation_level', $level);

        $commissions = $query->latest()->paginate(15);

        $fixedCount = \App\Models\Commission::where('commission_type', 'fixed')->count();
        $percentageCount = \App\Models\Commission::where('commission_type', 'percentage')->count();

        // Total value: sum first value from serviceCommissions or bookingCommissions
        $totalValue = \App\Models\ServiceCommission::whereIn('commission_id', $commissions->pluck('id'))->sum('value')
            + \App\Models\BookingCommission::whereIn('commission_id', $commissions->pluck('id'))->sum('value');

        return view('admin.commissions.index', compact('commissions', 'fixedCount', 'percentageCount', 'totalValue'));
    }

    public function create(): View
    {
        return view('admin.commissions.create');
    }

    public function store(CreateCommissionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        DB::beginTransaction();
        try {
            $value = $validated['value'] ?? null;
            unset($validated['value'], $validated['apply_to_all_providers']);
            
            $commission = Commission::create($validated);

            if ($value) {
                if ($validated['calculation_level'] === 'booking') {
                    $providerIds = \App\Models\ProviderService::limit(100)->pluck('id')->toArray();
                    $data = array_map(fn($id) => [
                        'provider_id' => $id,
                        'commission_id' => $commission->id,
                        'value' => $value,
                        'min_amount' => null,
                        'max_amount' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ], $providerIds);
                    
                    // تقسيم البيانات لتجنب أخطاء الـ MySQL (max_allowed_packet)
                    foreach (array_chunk($data, 500) as $chunk) {
                        BookingCommission::insert($chunk);
                    }
                }
                
                if ($validated['calculation_level'] === 'service') {
                    $services = \App\Models\Service::with('branch:id,provider_id')->limit(100)->get();
                    $data = [];
                    foreach ($services as $service) {
                        if (!$service->branch) {
                            throw new \Exception('Service has no branch');
                        }
                        $data[] = [
                            'provider_id' => $service->branch->provider_id,
                            'service_id' => $service->id,
                            'commission_id' => $commission->id,
                            'value' => $value,
                            'min_amount' => null,
                            'max_amount' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    foreach (array_chunk($data, 500) as $chunk) {
                        ServiceCommission::insert($chunk);
                    }
                }
            }

            $commissionId = $commission->id;
            $commissionData = $commission->toArray();
            DB::afterCommit(function () use ($commissionId, $commissionData) {
                $this->auditService->log(auth()->id(), 'create', 'commissions', $commissionId, null, $commissionData);
            });

            DB::commit();
            return redirect()->route('admin.commissions.index')->with('success', 'تم إضافة العمولة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Commission store failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'فشل إضافة العمولة. يرجى المحاولة مرة أخرى.');
        }
    }

    public function show(Commission $commission): View
    {
        $commission->load(['serviceCommissions.provider', 'serviceCommissions.service', 'bookingCommissions.provider']);
        return view('admin.commissions.show', compact('commission'));
    }

    public function edit(Commission $commission): View
    {
        return view('admin.commissions.edit', compact('commission'));
    }

    public function update(UpdateCommissionRequest $request, Commission $commission): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $oldData = $commission->toArray();
            $validated = $request->validated();
            
            if (isset($validated['value'])) {
                if ($commission->calculation_level === 'booking') {
                    BookingCommission::where('commission_id', $commission->id)->update(['value' => $validated['value']]);
                }
                if ($commission->calculation_level === 'service') {
                    ServiceCommission::where('commission_id', $commission->id)->update(['value' => $validated['value']]);
                }
            }

            unset($validated['value'], $validated['apply_to_all_providers']);
            $commission->update($validated);

            $newData = $commission->fresh()->toArray();
            $changes = array_diff_assoc($newData, $oldData);
            if (!empty($changes)) {
                $commissionId = $commission->id;
                $oldSubset = array_intersect_key($oldData, $changes);
                DB::afterCommit(function () use ($commissionId, $oldSubset, $changes) {
                    $this->auditService->log(auth()->id(), 'update', 'commissions', $commissionId, $oldSubset, $changes);
                });
            }
            
            DB::commit();
            return redirect()->route('admin.commissions.index')->with('success', 'تم تحديث العمولة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Commission update failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'فشل تحديث العمولة. يرجى المحاولة مرة أخرى.');
        }
    }

    public function destroy(Commission $commission): RedirectResponse
    {
        if ($commission->bookings()->exists()) {
            return redirect()->route('admin.commissions.index')
                ->with('error', 'لا يمكن حذف هذه العمولة لأنها مرتبطة بحجوزات فعلية');
        }

        DB::beginTransaction();
        try {
            // حذف التوزيعات المرتبطة (لأنها مجرد توزيعات وليست حجوزات)
            $commission->serviceCommissions()->delete();
            $commission->bookingCommissions()->delete();

            $oldData = $commission->toArray();
            $commissionId = $commission->id;
            $commission->delete();

            DB::afterCommit(function () use ($commissionId, $oldData) {
                $this->auditService->log(auth()->id(), 'delete', 'commissions', $commissionId, $oldData, null);
            });
            
            DB::commit();
            return redirect()->route('admin.commissions.index')->with('success', 'تم حذف العمولة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Commission destroy failed: ' . $e->getMessage());
            return back()->with('error', 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.');
        }
    }

    public function export(Request $request)
    {
        $query = Commission::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('commission_type', 'like', "%{$search}%")
                  ->orWhere('calculation_level', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('type')) $query->where('commission_type', $type);
        if ($level = $request->get('level')) $query->where('calculation_level', $level);

        return Excel::download(new CommissionsExport($query), 'commissions_' . date('Y-m-d_H-i-s') . '.xlsx');
    }
}