<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeviceTokenController extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'fcm_token' => [
                'required', 
                'string', 
                'min:100',
                'max:500',
                'regex:/^[a-zA-Z0-9:_-]+$/' 
            ],
            'device_type' => 'required|in:android,ios',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            DB::beginTransaction();

            DeviceToken::where('fcm_token', $request->fcm_token)
                ->where('user_id', '!=', $user->id)
                ->delete();

            $deviceToken = DeviceToken::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'fcm_token'    => $request->fcm_token,
                    'device_type'  => $request->device_type,
                    'last_used_at' => now(),
                ]
            );

            DeviceToken::where('user_id', $user->id)
                ->where('id', '!=', $deviceToken->id)
                ->where('last_used_at', '<', now()->subDays(30))
                ->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث رمز الجهاز بنجاح.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'فشل تحديث رمز الجهاز.',
            ], 500);
        }
    }
}