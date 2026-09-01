<?php

namespace App\Http\Middleware;

use App\Services\DataIntegrityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DataIntegrityMiddleware
{
    public function __construct(private DataIntegrityService $dataIntegrityService) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->requiresValidation($request)) {
            return $next($request);
        }

        try {
            $errors = $this->validateRequestData($request);

            if (!empty($errors)) {
                Log::warning('Data integrity validation failed', [
                    'endpoint'    => $request->method() . ' ' . $request->path(),
                    'error_count' => count($errors),
                    'error_types' => array_keys($errors),
                    'ip'          => $request->ip(),
                    'user_id'     => auth()->id(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => __('messages.validation_failed'),
                ], 422);
            }

            return $next($request);

        } catch (\Exception $e) {
            Log::error('Data integrity middleware error', [
                'error'    => $e->getMessage(),
                'endpoint' => $request->method() . ' ' . $request->path(),
                'ip'       => $request->ip(),
                'user_id'  => auth()->id(),
                'trace'    => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('messages.server_error'),
            ], 500);
        }
    }

    private function requiresValidation(Request $request): bool
    {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return false;
        }

        return $request->is('api/bookings*')
            || $request->is('api/trips*')
            || $request->is('api/payments*')
            || $request->is('api/refunds*');
    }

    private function validateRequestData(Request $request): array
    {
        $data   = $request->all();
        $errors = [];

        if (isset($data['amount'])) {
            $errors = array_merge($errors, $this->dataIntegrityService->validateFinancialData($data['amount']));
        }

        if ($request->is('api/bookings*')) {
            $errors = array_merge($errors, $this->dataIntegrityService->validateBookingData($data));
        }

        if (isset($data['booking_uuid'], $data['status_id'])) {
            $errors = array_merge($errors, $this->dataIntegrityService->validateBookingStatusTransition(
                $data['booking_uuid'],
                $data['status_id']
            ));
        }

        return $errors;
    }
}