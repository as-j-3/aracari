<?php

namespace App\Http\Middleware;

use App\Constants\UserStatus as UserStatusConstant;
use App\Models\LoginActivity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $isApi = $this->isApiRequest($request);

        if (!$request->user()) {
            return $this->buildResponse($request, $isApi, 
                'غير مصرح لك بالوصول', 401, 'login');
        }

        $user = $request->user();
        $user->loadMissing('status');

        if (!$this->isAccountActive($user)) {
            $this->logFailedAttempt($request, $user, 'account_inactive');
            return $this->buildResponse($request, $isApi, 
                'غير مصرح لك بالوصول', 403, 'home');
        }

        if (!$user->is_approved) {
            $this->logFailedAttempt($request, $user, 'account_not_approved');
            return $this->buildResponse($request, $isApi, 
                'غير مصرح لك بالوصول', 403, 'home');
        }

        if (!$user->hasPermission($permission)) {
            $this->logFailedAttempt($request, $user, "permission_denied: $permission");

            Log::warning('Access denied: insufficient permissions', [
                'user_id' => $user->id,
                'endpoint' => $request->method() . ' ' . $request->path(),
                'ip' => $request->ip(),
            ]);

            return $this->buildResponse($request, $isApi, 
                'غير مصرح لك بالوصول', 403, 'home');
        }

        return $next($request);
    }

    private function isApiRequest(Request $request): bool
    {
        return $request->is('api/*') 
            || ($request->expectsJson() && !$request->acceptsHtml());
    }

    private function isAccountActive($user): bool
    {
        return $user->status_id === UserStatusConstant::ACTIVE 
            && ($user->status?->status_code ?? null) === 'active';
    }

    private function buildResponse(Request $request, bool $isApi, string $message, int $code, string $redirectRoute): Response
    {
        if ($isApi) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $code);
        }

        try {
            return redirect()->route($redirectRoute)->with('error', $message);
        } catch (\Exception) {
            return redirect()->back()->with('error', $message);
        }
    }


    private function logFailedAttempt(Request $request, $user, string $reason): void
    {
        try {
            LoginActivity::create([
                'user_id'     => $user->id,
                'identifier'  => $user->phone_number ?? $user->email,
                'status'      => 'failed',
                'reason'      => $reason,
                'user_agent'  => $request->userAgent(),
                'ip_address'  => $request->ip(),
                'attempted_at'=> now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log login activity', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}