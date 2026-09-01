<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceModeMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->manualBypassEnabled()) {
            return $next($request);
        }

        $settings = Schema::hasTable('settings')
            ? Cache::rememberForever('system_settings', function () {
                return Setting::query()->pluck('value', 'key');
            })
            : collect();

        $isApi = $this->isApiRequest($request);
        $isWeb = !$isApi;

        if ($isWeb && ($settings['maintenance_mode'] ?? 'off') === 'on') {
            if (!$this->shouldBypass($request, $settings)) {
                return response()->view('errors.maintenance', [
                    'maintenanceTitle'   => $settings['maintenance_title'] ?? 'نعود إليكم قريباً',
                    'maintenanceContent' => $settings['maintenance_content'] ?? 'نعمل حالياً على تحسين التجربة.',
                ], 503);
            }
        }

        if ($isApi && ($settings['api_maintenance_mode'] ?? 'off') === 'on') {
            if (!$this->shouldBypass($request, $settings)) {
                return response()->json([
                    'success' => false,
                    'maintenance' => true,
                    'message' => $settings['api_maintenance_message'] ?? 'النظام في وضع الصيانة. يرجى المحاولة لاحقاً.',
                ], 503);
            }
        }

        return $next($request);
    }


    private function isApiRequest(Request $request): bool
    {
        if ($request->is('api/*')) {
            return true;
        }

        if ($request->expectsJson() && $request->header('Accept') === 'application/json') {
            return true;
        }

        if ($request->header('X-Requested-With') === 'com.your.app') {
            return true;
        }

        return false;
    }


    private function shouldBypass(Request $request, $settings): bool
    {
        if (auth()->check() && auth()->user()->isAdmin()) {
            return true;
        }

        if ($request->isMethod('GET') && ($request->is('login') || $request->routeIs('login'))) {
            return true;
        }

        if ($request->isMethod('POST') && $request->is('login')) {
            return true;
        }

        return false;
    }



    private function manualBypassEnabled(): bool
    {
        return env('MAINTENANCE_BYPASS', false) === true;
    }
}