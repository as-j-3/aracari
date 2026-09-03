<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use App\Http\Requests\Web\Commission\AdminIndexCommissionRequest;
use App\Http\Requests\Web\Commission\CreateCommissionRequest;
use App\Http\Requests\Web\Commission\UpdateCommissionRequest;
use App\Http\Resources\Admin\CommissionResource;
use App\Models\Commission;
use App\Models\BookingCommission;
use App\Models\Currency;
use App\Models\ProviderService;
use App\Models\Service;
use App\Models\ServiceCommission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CommissionsExport;


class CommissionController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)   
    {
        $this->auditService = $auditService; 
        $this->middleware('require.admin');
    }

    public function index(AdminIndexCommissionRequest $request): JsonResponse
    {
        $query = Commission::with([
            'currency:id,name,symbol,code',
            'serviceCommissions.provider:id,uuid,name',
            'bookingCommissions.provider:id,uuid,name',
        ]);

        if ($search = $request->validated('search')) {
            $safeSearch = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where(function ($q) use ($safeSearch) {
                $q->where('description', 'like', "%{$safeSearch}%")
                  ->orWhere('commission_type', 'like', "%{$safeSearch}%")
                  ->orWhere('calculation_level', 'like', "%{$safeSearch}%");
            });
        }

        if ($type = $request->validated('type')) {
            $query->where('commission_type', $type);
        }

        if ($level = $request->validated('level')) {
            $query->where('calculation_level', $level);
        }

        $commissions = $query->latest()->paginate($request->validated('per_page') ?? 15);

        return $this->success(
            [
                'commissions' => [
                    'data' => CommissionResource::collection($commissions->items()),
                    'current_page' => $commissions->currentPage(),
                    'last_page' => $commissions->lastPage(),
                    'per_page' => $commissions->perPage(),
                    'total' => $commissions->total(),
                ],
                'stats' => [
                    'fixed_count' => Commission::where('commission_type', 'fixed')->count(),
                    'percentage_count' => Commission::where('commission_type', 'percentage')->count(),
                ],
                'filters' => [
                    'commission_types' => [
                        ['value' => 'fixed', 'label' => 'ثابت'],
                        ['value' => 'percentage', 'label' => 'نسبة مئوية'],
                    ],
                    'calculation_levels' => [
                        ['value' => 'service', 'label' => 'خدمة'],
                        ['value' => 'booking', 'label' => 'حجز'],
                    ],
                ],
            ],
            'تم جلب العمولات بنجاح'
        );
    }

    public function providers(): JsonResponse
    {
        $providers = ProviderService::query()
            ->with('typeOfService:id,type_name')
            ->orderBy('name')
            ->get(['id', 'uuid', 'name', 'status', 'type_of_service_id', 'commission_type']);

        return $this->success(
            $providers->map(function (ProviderService $provider) {
                return [
                    'id' => $provider->id,
                    'uuid' => $provider->uuid,
                    'name' => $provider->name,
                    'status' => $provider->status,
                    'service_type' => $provider->typeOfService?->type_name,
                    'commission_type' => $provider->commission_type,
                    'commission_type_label' => $provider->commission_type === 'booking'
                        ? 'مستوى الحجز'
                        : 'مستوى الخدمة',
                ];
            }),
            'تم جلب مزودي الخدمة بنجاح'
        );
    }

    public function currencies(): JsonResponse
    {
        $currencies = Currency::orderBy('name')->get(['id', 'name', 'symbol', 'code']);

        return $this->success(
            $currencies->map(function (Currency $currency) {
                return [
                    'id' => $currency->id,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                    'code' => $currency->code,

                ];
            }),
            'تم جلب العملات بنجاح'
        );
    }

    public function store(CreateCommissionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $provider = ProviderService::where('uuid', $validated['provider_uuid'])->firstOrFail();

        $calculationLevel = $validated['calculation_level']
            ?? $provider->commission_type
            ?? 'service';
        try {
            DB::beginTransaction();

            $commission = Commission::create([
                'currency_id' => $validated['currency_id'] ?? null,
                'commission_type' => $validated['commission_type'],
                'calculation_level' => $calculationLevel,
                'description' => $validated['description'] ?? null,
            ]);

            if ($calculationLevel === 'booking') {
                BookingCommission::create([
                    'provider_id' => $provider->id,
                    'commission_id' => $commission->id,
                    'value' => $validated['value'],
                    'min_amount' => null,
                    'max_amount' => null,
                ]);
            }

            if ($calculationLevel === 'service') {
                $services = Service::query()
                    ->whereHas('branch', function ($query) use ($provider) {
                        $query->where('provider_id', $provider->id);
                    })
                    ->get();

                if ($services->isEmpty()) {
                    DB::rollBack();

                    return $this->error(
                        'لا توجد خدمات مرتبطة بهذا المزود. استخدم مستوى الحجز أو أضف خدمة أولاً.',
                        null,
                        422
                    );
                }

                foreach ($services as $service) {
                    ServiceCommission::create([
                        'provider_id' => $provider->id,
                        'service_id' => $service->id,
                        'commission_id' => $commission->id,
                        'value' => $validated['value'],
                    ]);
                }
            }

            $commissionId = $commission->id;
            $commissionData = $commission->toArray();
            DB::afterCommit(function () use ($commissionId, $commissionData, $provider, $validated) {
                $this->auditService->log(
                    Auth::id(),
                    'create',
                    'commissions',
                    $commissionId,
                    null,
                    array_merge($commissionData, [
                        'provider_id' => $provider->id,
                        'value' => $validated['value'],
                    ])
                );
            });

            DB::commit();

            $commission->load([
                'currency:id,name,symbol,code',
                'serviceCommissions.provider:id,uuid,name',
                'bookingCommissions.provider:id,uuid,name',
            ]);

            return $this->success(
                new CommissionResource($commission),
                'تم إضافة العمولة بنجاح'
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Commission store API failed', [
                'error' => $e->getMessage(),
                'user_id' => auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'فشل إضافة العمولة. يرجى المحاولة مرة أخرى.',
                null,
                500
            );
        }
    }

    public function show(Commission $commission): JsonResponse
    {
        $commission->load([
            'currency:id,name,symbol,code',
            'serviceCommissions.provider:id,uuid,name',
            'serviceCommissions.service:id,name',
            'bookingCommissions.provider:id,uuid,name',
        ]);

        return $this->success(
            new CommissionResource($commission),
            'تم جلب تفاصيل العمولة بنجاح'
        );
    }

    public function update(UpdateCommissionRequest $request, Commission $commission): JsonResponse
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
                    $this->auditService->log(Auth::id(), 'update', 'commissions', $commissionId, $oldSubset, $changes);
                });
            }

            DB::commit();

            $commission->load([
                'currency:id,name,symbol,code',
                'serviceCommissions.provider:id,uuid,name',
                'serviceCommissions.service:id,name',
                'bookingCommissions.provider:id,uuid,name',
            ]);

            return $this->success(
                new CommissionResource($commission),
                'تم تحديث العمولة بنجاح'
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Commission update API failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'فشل تحديث العمولة. يرجى المحاولة مرة أخرى.',
                null,
                500
            );
        }
    }

    public function destroy(Commission $commission): JsonResponse
    {
        if ($commission->bookings()->exists()) {
            return $this->error(
                'لا يمكن حذف هذه العمولة لأنها مرتبطة بحجوزات فعلية',
                null,
                422
            );
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
                $this->auditService->log(Auth::id(), 'delete', 'commissions', $commissionId, $oldData, null);
            });

            DB::commit();

            return $this->success(
                null,
                'تم حذف العمولة بنجاح'
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Commission destroy API failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.',
                null,
                500
            );
        }
    }
    public function export(AdminIndexCommissionRequest $request)
{
    $validated = $request->validated();

    $query = Commission::query();

    if ($search = $validated['search'] ?? null) {
        $safeSearch = str_replace(
            ['%', '_'],
            ['\\%', '\\_'],
            $search
        );

        $query->where(function ($q) use ($safeSearch) {
            $q->where('description', 'like', "%{$safeSearch}%")
              ->orWhere('commission_type', 'like', "%{$safeSearch}%")
              ->orWhere('calculation_level', 'like', "%{$safeSearch}%");
        });
    }

    if ($type = $validated['type'] ?? null) {
        $query->where('commission_type', $type);
    }

    if ($level = $validated['level'] ?? null) {
        $query->where('calculation_level', $level);
    }

    try {
        $fileName = 'commissions_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(
            new CommissionsExport($query),
            $fileName
        );

    } catch (\Throwable $e) {
        Log::error('Commission export failed', [
            'error'   => $e->getMessage(),
            'user_id' => Auth::id(),
            'trace'   => $e->getTraceAsString(),
        ]);

        return $this->error(
            'فشل تصدير ملف العمولات. يرجى المحاولة مرة أخرى.',
            null,
            500
        );
    }
}

    /*
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
                    $this->auditService->log(auth::id(), 'update', 'commissions', $commissionId, $oldSubset, $changes);
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
                $this->auditService->log(auth::id(), 'delete', 'commissions', $commissionId, $oldData, null);
            });
            
            DB::commit(); 
            return redirect()->route('admin.commissions.index')->with('success', 'تم حذف العمولة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Commission destroy failed: ' . $e->getMessage());
            return back()->with('error', 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.');
        }
    }
    */

    /*
        public function export(Request $request): JsonResponse
    {
        $query = Commission::query();

        if ($search = $request->get('search')) {
            $safeSearch = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where(function ($q) use ($safeSearch) {
                $q->where('description', 'like', "%{$safeSearch}%")
                  ->orWhere('commission_type', 'like', "%{$safeSearch}%")
                  ->orWhere('calculation_level', 'like', "%{$safeSearch}%");
            });
        }

        if ($type = $request->get('type')) {
            $query->where('commission_type', $type);
        }
        if ($level = $request->get('level')) {
            $query->where('calculation_level', $level);
        }

      $fileName = 'commissions_' . now()->format('Y-m-d_H-i-s') . '_' . Str::random(6) . '.xlsx';
        $filePath = 'exports/' . $fileName;

        try {
            Excel::store(new CommissionsExport($query), $filePath, 'public');

            $downloadUrl = Storage::disk('public')->url($filePath);
            if (!filter_var($downloadUrl, FILTER_VALIDATE_URL)) {
                $downloadUrl = asset('storage/' . $filePath);
            }

            return $this->success(
                [
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'download_url' => $downloadUrl,
                    'generated_at' => now()->format('Y-m-d H:i:s'),
                ],
                'تم تصدير ملف العمولات بنجاح'
            );
        } catch (\Throwable $e) {
            Log::error('Commission export failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'فشل تصدير ملف العمولات. يرجى المحاولة مرة أخرى.',
                null,
                500
            );
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
    */
}