<?php

namespace App\Http\Middleware;

use App\Models\ProviderBranch;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class BranchPermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->deniedResponse('غير مصرح لك', 401);
        }

        if (!$user->hasPermission($permission)) {
            $this->logFailedAttempt($request, $user, 'permission_denied', $permission);
            return $this->deniedResponse('لا تملك الصلاحية المطلوبة', 403);
        }

        if (!$this->requiresBranchAccess($permission)) {
            return $next($request);
        }

        $branchParam = $request->route('branch');

        if (!$branchParam) {
            Log::warning('Branch permission check failed: missing branch', [
                'user_id'    => $user->id,
                'permission' => $permission,
                'endpoint'   => $request->method() . ' ' . $request->path(),
            ]);
            return $this->deniedResponse('يجب تحديد الفرع', 400);
        }

        if ($branchParam instanceof ProviderBranch) {
            $branchId = $branchParam->id;
        } else {
            $branchId = $this->resolveBranchId($branchParam);
            if (!$branchId) {
                return $this->deniedResponse('الفرع المحدد غير موجود', 404);
            }
        }

        if ($user->isProviderOwner()) {
            return $next($request);
        }

        if (!$user->hasAccessToBranch($branchId)) {
            $this->logFailedAttempt($request, $user, 'branch_access_denied', $permission, $branchId);
            return $this->deniedResponse('لا تملك صلاحية الوصول لهذا الفرع', 403);
        }

        return $next($request);
    }

    private function resolveBranchId($branchParam): ?int
    {
        if (is_numeric($branchParam)) {
            $branch = ProviderBranch::where('id', $branchParam)->value('id');
            return $branch ? (int) $branch : null;
        }

        if (is_string($branchParam)) {
            $branch = ProviderBranch::where('uuid', $branchParam)->value('id');
            return $branch ? (int) $branch : null;
        }

        return null;
    }

    private function requiresBranchAccess(string $permission): bool
    {
        $branchSpecificPermissions = [
            'provider:branches:bookings:view',
            'provider:branches:bookings:create',
            'provider:branches:bookings:cancel',
            'provider:branches:trips:view',
            'provider:branches:trips:create',
            'provider:branches:trips:edit',
            'provider:branches:trips:delete',
            'provider:branches:staff:add',
            'provider:branches:staff:remove',
            'provider:branches:reports:view',
        ];

        return in_array($permission, $branchSpecificPermissions);
    }

    private function deniedResponse(string $message, int $code): Response
    {
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $code);
        }

        abort($code, $message);
    }

    private function logFailedAttempt(Request $request, $user, string $reason, string $permission, ?int $branchId = null): void
    {
        $data = [
            'user_id'    => $user->id,
            'permission' => $permission,
            'reason'     => $reason,
            'endpoint'   => $request->method() . ' ' . $request->path(),
            'ip'         => $request->ip(),
        ];

        if ($branchId) {
            $data['branch_id'] = $branchId;
        }

        Log::warning('Branch permission check failed', $data);
    }
}