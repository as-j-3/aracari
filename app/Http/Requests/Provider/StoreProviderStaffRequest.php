<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\ProviderStaffService;

class StoreProviderStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        $creator = auth()->user();
        $providerStaffService = app(ProviderStaffService::class);
        $allowedRoles = $providerStaffService->getAllowedRoles($creator);
        $allowedRoleNames = $allowedRoles->pluck('name')->toArray();
        $accessibleBranchIds = $creator->getAccessibleBranches()->pluck('id')->toArray();

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|unique:users,phone_number',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', 'string', 'in:' . implode(',', $allowedRoleNames)],
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => ['exists:provider_branches,id', 'in:' . implode(',', $accessibleBranchIds)],
        ];
    }
}