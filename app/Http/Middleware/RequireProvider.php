<?php

namespace App\Http\Middleware;

use App\Constants\UserTypes;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireProvider
{

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $isProvider = $user->isProvider();
        $isProviderStaff = $user->isProviderStaff();

        if (!$isProvider && !$isProviderStaff) {
            \Illuminate\Support\Facades\Log::warning('Unauthorized provider panel access attempt', [
                'user_id' => $user->id,
                'user_type' => $user->type_user_code,
                'user_parent' => $user->user_parent,
                'ip' => $request->ip(),
                'route' => $request->route()?->getName(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بالوصول للوحة المزود'
                ], 403);
            }

            return redirect()->route('home')
                ->with('error', 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        if ($isProvider && $user->user_parent === null) {
            $providerService = $user->providerService;

            if (!$providerService) {
                \Illuminate\Support\Facades\Log::warning('Provider without service attempted panel access', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'ip' => $request->ip(),
                    'route' => $request->route()?->getName(),
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'حسابك لا يحتوي على خدمة مزود نشطة. يرجى التواصل مع الإدارة.'
                    ], 403);
                }

                auth()->logout();
                return redirect()->route('login')
                    ->with('error', 'حسابك لا يحتوي على خدمة مزود نشطة بعد. يرجى التواصل مع الإدارة لإكمال إعداد حسابك.');
            }

            if ($providerService->status !== 'active') {
                \Illuminate\Support\Facades\Log::warning('Provider with inactive service attempted panel access', [
                    'user_id' => $user->id,
                    'provider_service_id' => $providerService->id,
                    'provider_status' => $providerService->status,
                    'ip' => $request->ip(),
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'خدمة المزود غير مفعلة حالياً. يرجى التواصل مع الإدارة.'
                    ], 403);
                }

                auth()->logout();
                return redirect()->route('login')
                    ->with('error', 'خدمة المزود غير مفعلة حالياً (' . $providerService->status . '). يرجى التواصل مع الإدارة.');
            }
        }

        return $next($request);
    }
}
