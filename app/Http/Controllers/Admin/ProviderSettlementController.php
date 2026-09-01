<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Constants\UserTypes;
use App\Services\ProviderSettlementService;
use App\Services\AuditService;
use App\Models\TransactionReference;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProviderSettlementController extends Controller
{
    private ProviderSettlementService $settlementService;
    private AuditService $auditService;

    public function __construct(
        ProviderSettlementService $settlementService,
        AuditService $auditService
    ) {
        $this->settlementService = $settlementService;
        $this->auditService = $auditService;
        $this->middleware('require.admin');
    }

 
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
}