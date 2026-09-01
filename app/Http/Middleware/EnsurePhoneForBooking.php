<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneForBooking
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $isApi = $request->expectsJson() || $request->is('api/*');

        if (!$user->phone_number) {
            return $isApi
                ? response()->json([
                    'success' => false,
                    'message' => 'يجب إضافة رقم هاتف لإتمام الحجز',
                    'code' => 'PHONE_REQUIRED',
                ], 400)
                : redirect()->route('profile.phone.verify')
                    ->with('warning', 'يجب إضافة وتفعيل رقم الهاتف لإتمام الحجز');
        }

        if (!$user->phone_verified_at) {
            return $isApi
                ? response()->json([
                    'success' => false,
                    'message' => 'يجب تفعيل رقم الهاتف قبل الحجز',
                    'code' => 'PHONE_NOT_VERIFIED',
                ], 400)
                : redirect()->route('profile.phone.verify')
                    ->with('warning', 'يجب تفعيل رقم الهاتف قبل الحجز');
        }

        return $next($request);
    }
}