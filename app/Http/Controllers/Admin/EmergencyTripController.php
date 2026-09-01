<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\EmergencyTripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class EmergencyTripController extends Controller
{
    protected EmergencyTripService $emergencyTripService;

    public function __construct(EmergencyTripService $emergencyTripService)
    {
        $this->emergencyTripService = $emergencyTripService;
        $this->middleware('require.admin');
    }

    public function index(): View
    {
        $activeTrips = Trip::with(['departureCity', 'destinationCity', 'vehicle'])
            ->where('departure_time', '>=', now())
            ->where('is_public', true)
            ->latest('departure_time')
            ->paginate(20);

        return view('admin.emergency_trips', compact('activeTrips'));
    }

    public function emergencyCancel(Request $request, string $uuid): JsonResponse
    {
        $trip = Trip::with(['bookings.user', 'bookings.passengers'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        $validated = $request->validate([
            'reason' => 'required|string|min:10|max:500',
            'notify_passengers' => 'boolean',
            'refund_policy' => 'required|in:full,partial,none'
        ]);

        try {
            $result = $this->emergencyTripService->emergencyCancel($trip, $validated);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Emergency cancel failed: ' . $e->getMessage(), [
                'trip_id' => $uuid,
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ تقني أثناء الإلغاء الطارئ. يرجى مراجعة سجلات النظام.'
            ], 500);
        }
    }

    public function emergencyReschedule(Request $request, string $uuid): JsonResponse
    {
        $trip = Trip::with(['bookings.user', 'bookings.passengers'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        $validated = $request->validate([
            'new_departure_time' => 'required|date|after:now',
            'reason' => 'required|string|min:10|max:500',
            'notify_passengers' => 'boolean',
            'auto_refund_conflicts' => 'boolean'
        ]);

        try {
            $result = $this->emergencyTripService->emergencyReschedule($trip, $validated);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Emergency reschedule failed: ' . $e->getMessage(), [
                'trip_id' => $uuid,
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ تقني أثناء إعادة الجدولة الطارئة. يرجى مراجعة سجلات النظام.'
            ], 500);
        }
    }
}