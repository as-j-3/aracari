<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Policy\AdminIndexPolicyRequest;
use App\Http\Requests\Web\Policy\StorePolicyRequest;
use App\Http\Requests\Web\Policy\UpdatePolicyRequest;
use App\Models\Policy;
use App\Services\AuditService;
use App\Services\PolicyService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PolicyController extends Controller
{
    public function __construct(
        protected PolicyService $policyService,
        protected AuditService $auditService,
    ) {
        $this->middleware('require.admin');
    }

    public function index(AdminIndexPolicyRequest $request): View
    {
        $perPage = $request->validated('per_page', 15);
        $search  = $request->validated('search');
        $type    = $request->validated('type');

        $query = Policy::with(['rules', 'creator'])
            ->withCount('trips');

        if ($search !== null && $search !== '') {
            $safeSearch = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where(function (Builder $q) use ($safeSearch) {
                $q->where('name', 'like', "%{$safeSearch}%")
                  ->orWhere('uuid', 'like', "%{$safeSearch}%")
                  ->orWhere('description', 'like', "%{$safeSearch}%");
            });
        }

        if ($type !== null && $type !== '') {
            $allowedTypes = ['cancellation', 'booking', 'other'];
            if (in_array($type, $allowedTypes, true)) {
                $query->where('type', $type);
            }
        }

        $policies = $query->latest()->paginate($perPage);

        $stats = [
            'total'        => Policy::count(),
            'cancellation' => Policy::where('type', 'cancellation')->count(),
            'booking'      => Policy::where('type', 'booking')->count(),
            'total_rules'  => \App\Models\PolicyCancellationRule::count(),
        ];

        return view('admin.policies.index', compact('policies', 'stats'));
    }

 
    public function store(StorePolicyRequest $request): JsonResponse|RedirectResponse
    {
        try {
            DB::beginTransaction();

            $policy = Policy::create([
                'name'        => $request->validated('name'),
                'description' => $request->validated('description'),
                'type'        => $request->validated('type'),
                'created_by'  => auth()->id(),
            ]);

            if ($request->validated('type') === 'cancellation') {
                $rules = $request->validated('rules', []);
                foreach ($rules as $rule) {
                    $policy->rules()->create([
                        'from_hours'    => $rule['from_hours'],
                        'to_hours'      => $rule['to_hours'],
                        'fee_percentage' => $rule['fee_percentage'],
                    ]);
                }
            }

            $policyId  = $policy->id;
            $policyArr = $policy->load('rules')->toArray();

            DB::afterCommit(function () use ($policyId, $policyArr) {
                $this->auditService->log(auth()->id(), 'create', 'policies', $policyId, null, $policyArr);
            });
            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إنشاء السياسة بنجاح',
                ]);
            }

            return redirect()->route('admin.policies.index')->with('success', 'تم إنشاء السياسة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Policy creation failed: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء إنشاء السياسة. يرجى المحاولة مرة أخرى.',
                    'errors' => ['name' => [$e->getMessage()]],
                ], 422);
            }

            return back()->withInput()->with('error', 'حدث خطأ أثناء إنشاء السياسة. يرجى المحاولة مرة أخرى.');
        }
    }


    public function update(UpdatePolicyRequest $request, string $uuid): JsonResponse|RedirectResponse
    {
        $policy = Policy::where('uuid', $uuid)->first();
        abort_unless($policy, 404, 'السياسة غير موجودة');

        try {
            DB::beginTransaction();
            $oldData = $policy->toArray();

            $policy->update([
                'name'        => $request->validated('name'),
                'description' => $request->validated('description'),
                'type'        => $request->validated('type'),
            ]);

            if ($request->validated('type') === 'cancellation') {
                $rules = $request->validated('rules', []);
                $keptIds = [];

                foreach ($rules as $rule) {
                    if (!empty($rule['id'])) {
                        $existing = \App\Models\PolicyCancellationRule::find($rule['id']);
                        if ($existing && $existing->policy_id === $policy->id) {
                            $existing->update([
                                'from_hours'    => $rule['from_hours'],
                                'to_hours'      => $rule['to_hours'],
                                'fee_percentage' => $rule['fee_percentage'],
                            ]);
                            $keptIds[] = $existing->id;
                        }
                    } else {
                        $newRule = $policy->rules()->create([
                            'from_hours'    => $rule['from_hours'],
                            'to_hours'      => $rule['to_hours'],
                            'fee_percentage' => $rule['fee_percentage'],
                        ]);
                        $keptIds[] = $newRule->id;
                    }
                }

                $policy->rules()->whereNotIn('id', $keptIds)->delete();
            } else {
                $policy->rules()->delete();
            }

            $newData = $policy->fresh()->load('rules')->toArray();
            $changes = array_diff_assoc($newData, $oldData);

            if (!empty($changes)) {
                $policyId   = $policy->id;
                $oldSubset  = array_intersect_key($oldData, $changes);
                DB::afterCommit(function () use ($policyId, $oldSubset, $changes) {
                    $this->auditService->log(
                        auth()->id(),
                        'update',
                        'policies',
                        $policyId,
                        $oldSubset,
                        $changes
                    );
                });
            }

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم تحديث السياسة بنجاح',
                ]);
            }

            return redirect()->route('admin.policies.index')->with('success', 'تم تحديث السياسة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Policy update failed: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء تحديث السياسة. يرجى المحاولة مرة أخرى.',
                    'errors' => ['name' => [$e->getMessage()]],
                ], 422);
            }

            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث السياسة. يرجى المحاولة مرة أخرى.');
        }
    }


    public function destroy(Request $request, string $uuid): JsonResponse|RedirectResponse
    {
        $policy = Policy::where('uuid', $uuid)->first();
        abort_unless($policy, 404, 'السياسة غير موجودة');

        if ($policy->trips()->exists()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف السياسة لأنها مرتبطة برحلات. قم بإزالة الارتباط أولاً.',
                ], 422);
            }

            return back()->with('error', 'لا يمكن حذف السياسة لأنها مرتبطة برحلات. قم بإزالة الارتباط أولاً.');
        }

        try {
            DB::beginTransaction();
            $oldData = $policy->toArray();
            $policyId = $policy->id;

            $policy->rules()->delete();
            $policy->delete();

            DB::afterCommit(function () use ($policyId, $oldData) {
                $this->auditService->log(auth()->id(), 'delete', 'policies', $policyId, $oldData, null);
            });

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حذف السياسة بنجاح',
                ]);
            }

            return redirect()->route('admin.policies.index')->with('success', 'تم حذف السياسة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Policy deletion failed: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.',
                ], 422);
            }

            return back()->with('error', 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.');
        }
    }


    public function calculateCancellationFee(Request $request): JsonResponse
    {
        $request->validate([
            'policy_id'    => 'required|integer|exists:policies,id',
            'hours_before' => 'required|integer|min:0',
        ]);

        $result = $this->policyService->calculateCancellationFee(
            $request->input('policy_id'),
            $request->input('hours_before')
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json($result);
    }


    public function getPoliciesList(Request $request): JsonResponse
    {
        $type = $request->input('type', 'cancellation');
        $allowed = ['cancellation', 'booking', 'other'];
        if (!in_array($type, $allowed, true)) {
            $type = 'cancellation';
        }

        $policies = Policy::where('type', $type)
            ->select('id', 'name', 'description', 'uuid')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $policies,
        ]);
    }

    public function showJson(string $uuid): JsonResponse
    {
        $policy = Policy::with(['rules', 'trips.departureCity', 'trips.destinationCity', 'creator'])
            ->where('uuid', $uuid)
            ->first();

        abort_unless($policy, 404, 'السياسة غير موجودة');

        return response()->json([
            'success' => true,
            'data' => $policy,
        ]);
    }
}
