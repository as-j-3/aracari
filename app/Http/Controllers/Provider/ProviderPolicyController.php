<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Policy;
use App\Models\PolicyCancellationRule;
use App\Services\PolicyService;
use App\Services\ProviderContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Http\Requests\Provider\StoreProviderPolicyRequest;
use App\Http\Requests\Provider\UpdateProviderPolicyRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProviderPolicyController extends Controller
{
    public function __construct(
        protected PolicyService $policyService,
        protected ProviderContext $providerContext,
    ) {
    }

    public function index(Request $request): View
    {
        $providerId = $this->providerContext->providerUserIdFor($request->user());

        $policies = Policy::with(['rules', 'creator'])
            ->withCount('trips')
            ->where('created_by', $providerId)
            ->latest()
            ->paginate(15);

        $stats = [
            'total'        => Policy::where('created_by', $providerId)->count(),
            'cancellation' => Policy::where('created_by', $providerId)->where('type', 'cancellation')->count(),
            'active_rules' => PolicyCancellationRule::whereHas('policy', fn($q) => $q->where('created_by', $providerId))->count(),
        ];

        return view('provider.policies.index', compact('policies', 'stats'));
    }


    public function store(StoreProviderPolicyRequest $request): RedirectResponse|JsonResponse
    {

        try {
            DB::beginTransaction();

            $policy = Policy::create([
                'name'        => $request->name,
                'description' => $request->description,
                'type'        => $request->type,
                'created_by'  => $request->user()->id,
            ]);

            if ($request->type === 'cancellation') {
                foreach ($request->rules as $rule) {
                    $policy->rules()->create([
                        'from_hours'    => $rule['from_hours'],
                        'to_hours'      => $rule['to_hours'],
                        'fee_percentage' => $rule['fee_percentage'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء السياسة بنجاح',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Provider policy store failed: ' . $e->getMessage());
            return $this->ajaxError('حدث خطأ أثناء إنشاء السياسة. يرجى المحاولة مرة أخرى.');
        }
    }


    public function update(UpdateProviderPolicyRequest $request, Policy $policy): RedirectResponse|JsonResponse
    {
        if (!$this->canAccessPolicy($policy)) {
            return $this->ajaxError('غير مصرح لك بتعديل هذه السياسة.', 403);
        }

        $providerId = $this->providerContext->providerUserIdFor($request->user());
        if ($policy->created_by !== $providerId) {
            return $this->ajaxError('لا يمكن تعديل سياسة عامة. يمكنك تعديل سياساتك فقط.');
        }
        try {
            DB::beginTransaction();

            $policy->update([
                'name'        => $request->name,
                'description' => $request->description,
                'type'        => $request->type,
            ]);

            if ($request->type === 'cancellation') {
                $rules = $request->rules;
                $keptIds = [];

                foreach ($rules as $rule) {
                    if (!empty($rule['id'])) {
                        $existing = PolicyCancellationRule::find($rule['id']);
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

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث السياسة بنجاح',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Provider policy update failed: ' . $e->getMessage());
            return $this->ajaxError('حدث خطأ أثناء تحديث السياسة. يرجى المحاولة مرة أخرى.');
        }
    }


    public function destroy(Request $request, Policy $policy): RedirectResponse|JsonResponse
    {
        if (!$this->canAccessPolicy($policy)) {
            return $this->ajaxError('غير مصرح لك بحذف هذه السياسة.', 403);
        }

        $providerId = $this->providerContext->providerUserIdFor($request->user());
        if ($policy->created_by !== $providerId) {
            return $this->ajaxError('لا يمكن حذف سياسة عامة. يمكنك حذف سياساتك فقط.');
        }

        if ($policy->trips()->exists()) {
            return $this->ajaxError('لا يمكن حذف السياسة لأنها مرتبطة برحلات. قم بإزالة الارتباط أولاً.');
        }

        try {
            DB::beginTransaction();
            $policy->rules()->delete();
            $policy->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف السياسة بنجاح',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Provider policy destroy failed: ' . $e->getMessage());
            return $this->ajaxError('تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.');
        }
    }


    public function showJson(Policy $policy): JsonResponse
    {
        if (!$this->canAccessPolicy($policy)) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 403);
        }

        $policy->load(['rules', 'trips']);

        return response()->json([
            'success' => true,
            'data' => $policy,
        ]);
    }

    private function canAccessPolicy(Policy $policy): bool
    {
        $user = auth()->user();
        return $policy->created_by === $user->id
            || $policy->created_by === null
            || $policy->created_by === $user->user_parent;
    }

    private function ajaxError(string $message, int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
