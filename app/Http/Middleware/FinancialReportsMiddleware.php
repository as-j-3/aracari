<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Constants\UserRoles;
class FinancialReportsMiddleware
{

    private const ALLOWED_ROLES = [
        UserRoles::ADMIN,
        UserRoles::FINANCE_MANAGER,
    ];



    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->deniedResponse($request, 'UNAUTHENTICATED');
        }

        $hasAllowedRole = $user->roles()->whereIn('name', self::ALLOWED_ROLES)->exists();
        
        if (!$hasAllowedRole) {
            $this->logAccessAttempt($request, $user, false, 'invalid_role');
            return $this->deniedResponse($request);
        }

        if (!$user->hasPermission('financial_reports:view')) {
            $this->logAccessAttempt($request, $user, false, 'insufficient_permission');
            return $this->deniedResponse($request);
        }

        $this->logAccessAttempt($request, $user, true);

        return $next($request);
    }


    private function deniedResponse(Request $request, ?string $internalCode = null): Response
    {
        $message = 'غير مصرح لك بالوصول إلى هذه المنطقة';

        if ($this->isApiRequest($request)) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 403);
        }

        abort(403, $message);
    }

    private function logAccessAttempt(Request $request, $user, bool $success, ?string $failureReason = null): void
    {
        try {
            $action = $success ? 'FINANCIAL_REPORTS_ACCESS_GRANTED' : 'FINANCIAL_REPORTS_ACCESS_DENIED';

            $metadata = [
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url'        => $request->fullUrl(),
                'method'     => $request->method(),
                'success'    => $success,
            ];

            if (!$success && $failureReason) {
                $metadata['failure_reason'] = $failureReason;
            }

            app(\App\Services\AuditService::class)->log(
                $user->id,                    
                $action,               
                'financial_reports',         
                $user->id,                   
                null,                        
                $metadata                     
            );
        } catch (\Exception $e) {
            \Log::error('Failed to log financial reports access', [
                'user_id' => $user->id ?? null,
                'error'   => $e->getMessage(),
            ]);
        }
    }


    private function isApiRequest(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }
}