<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ProviderBranch\IndexProviderBranchRequest;
use App\Models\ProviderBranch;
use App\Models\ProviderService;
use App\Services\ProviderContext;
use Illuminate\View\View;


class ProviderBranchController extends Controller
{
    private ProviderContext $providerContext;

    public function __construct(ProviderContext $providerContext)
    {
        $this->providerContext = $providerContext;
        $this->middleware('auth');
    }


    private function getUserContext(): array
    {
        $user = auth()->user();
        $providerServiceId = $this->providerContext->providerServiceIdFor($user);
        $isAdmin = $user?->isAdmin() ?? false;

        return [$user, $providerServiceId, $isAdmin];
    }


    public function index(IndexProviderBranchRequest $request): View
    {
        [$user, $providerServiceId, $isAdmin] = $this->getUserContext();

        $query = ProviderBranch::with(['city', 'country', 'provider.typeOfService', 'provider.user', 'branchUsers.user'])
            ->withCount(['branchUsers', 'trips']);

        if (!$isAdmin) {
            $query->where('provider_id', $providerServiceId);
        }

        if ($search = $request->get('search')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(function ($q) use ($escaped) {
                $q->where('phone_number', 'like', "%{$escaped}%")
                  ->orWhere('address', 'like', "%{$escaped}%")
                  ->orWhereHas('city', fn($q) => $q->where('name', 'like', "%{$escaped}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $branches = $query->latest()->paginate($request->get('per_page', 15))->withQueryString();
        $providers = $isAdmin
            ? ProviderService::orderBy('name')->pluck('name', 'id')
            : [];

        return view('admin.provider_branches.index', compact('branches', 'providers', 'isAdmin', 'providerServiceId'));
    }

    public function show(string $uuid): View
    {
        [$user, $providerServiceId, $isAdmin] = $this->getUserContext();

        $branch = ProviderBranch::with(['city', 'country', 'provider', 'service'])
            ->where('uuid', $uuid)->firstOrFail();

        return view('provider_branches.show', compact('branch'));
    }
}
