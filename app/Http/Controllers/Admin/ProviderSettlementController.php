<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ProviderSettlement\StoreSettlementRequest;
use App\Http\Resources\Admin\ProviderSettlementResource;
use App\Http\Resources\Admin\SettlementHistoryResource;
use App\Models\User;
use App\Constants\UserTypes;
use App\Repositories\Contracts\ProviderSettlementRepositoryInterface;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ProviderSettlementController extends Controller
{
    public function __construct(
        private ProviderSettlementRepositoryInterface $settlementRepository,
        private AuditService $auditService
    ) {
        $this->middleware('require.admin');
    }

    /*
    |--------------------------------------------------------------------------
    | واجهة برمجية (API) - استجابات JSON
    |--------------------------------------------------------------------------
    |
    | تم تحويل الدوال الآتية لتعمل كهيئة API باستخدام success()/error()
    | والمنطق نفسه في دوال الويب القديمة (محفوظة بالأسفل كتعليقات).
    |
    */

    public function index(): JsonResponse
    {
        $providers = $this->settlementRepository->getProvidersWithBalances();

        return $this->success(
            ProviderSettlementResource::collection($providers),
            'تم استرجاع أرصدة المزودين بنجاح'
        );
    }

    public function settle(StoreSettlementRequest $request, int $providerId): JsonResponse
    {
        $validated = $request->validated();

        $provider = User::where('id', $providerId)
            ->where('type_user_code', UserTypes::PROVIDER)
            ->first();

        if (! $provider) {
            return $this->error(
                'المستخدم المحدد ليس مزود خدمة صالح.',
                null,
                404
            );
        }

        $result = $this->settlementRepository->settleProvider(
            providerUserId: $providerId,
            amount: (float) $validated['amount'],
            revenueMethodId: $validated['revenue_method_id'] ?? null,
            actorId: auth()->id(),
            currencyId: (int) $validated['currency_id'],
        );

        if ($result['success']) {
            return $this->success([
                'reference' => $result['reference'],
                'amount' => $result['amount'],
                'new_balance' => $result['new_balance'],
            ], $result['message']);
        }

        return $this->error(
            $result['message'],
            null,
            422
        );
    }

    public function history(\Illuminate\Http\Request $request): JsonResponse
    {
        $settlements = $this->settlementRepository->history(
            $request->only(['provider_id']),
            $request->get('per_page', 20)
        );

        return $this->success(
            SettlementHistoryResource::collection($settlements),
            'تم استرجاع سجل التسويات بنجاح'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | دوال الويب القديمة (محفوظة كتعليقات وليست محذوفة)
    |--------------------------------------------------------------------------
    |
    | كانت تعيد صفحات Blade عبر return view() / redirect().
    | تم استبدالها بنسخ JSON أعلاه. نفس المنطق والتحقق.
    |
    */

    /*
    public function index(): View
    {
        $providers = $this->settlementService->getProvidersWithBalances();
        return view('admin.provider-settlements.index', compact('providers'));
    }

    public function settle(Request $request, int $providerId): RedirectResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'revenue_method_id' => 'nullable|exists:receive_revenue_methods,id',
            'currency_id' => 'required|exists:currencies,id',
        ]);

        $provider = User::where('id', $providerId)->where('type_user_code', UserTypes::PROVIDER)->first();
        if (!$provider) {
            return redirect()->back()->with('error', 'المستخدم المحدد ليس مزود خدمة صالح.')->withInput();
        }

        $result = $this->settlementService->settleProvider(
            providerUserId: $providerId,
            amount: (float) $request->amount,
            revenueMethodId: $request->revenue_method_id ? (int) $request->revenue_method_id : null,
            actorId: auth()->id(),
            currencyId: (int) $request->currency_id,
        );

        if ($result['success']) {
            return redirect()
                ->route('provider-settlements.index')
                ->with('success', $result['message'] . ' - المرجع: ' . $result['reference']);
        }

        return redirect()
            ->back()
            ->with('error', $result['message'])
            ->withInput();
    }

    public function history(Request $request): View
    {
        $query = TransactionReference::where('reference_type', 'provider_settlement')
            ->with(['transactions', 'creator:id,name']);

        if ($request->provider_id) {
            $query->where('reference_id', $request->provider_id);
        }

        $settlements = $query->latest()->paginate(20);

        $providers = User::where('type_user_code', UserTypes::PROVIDER)
            ->pluck('name', 'id');

        return view('admin.provider-settlements.history', compact('settlements', 'providers'));
    }
    */
}
