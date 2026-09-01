<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $perPage = $request->integer('per_page', 20);

        $notifications = Notification::query()
            ->where('notifiable_type', \App\Models\User::class)
            ->where('notifiable_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $notifications->through(function ($notification) {
            $data = is_string($notification->data) ? json_decode($notification->data, true) : $notification->data;
            return [
                'id'        => $notification->id,
                'type'      => $notification->type,
                'title'     => $data['title'] ?? '',
                'body'      => $data['body'] ?? '',
                'screen'    => $data['screen'] ?? null,
                'read_at'   => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $notifications,
        ]);
    }


    public function unreadCount(): JsonResponse
    {
        $count = Notification::query()
            ->where('notifiable_type', \App\Models\User::class)
            ->where('notifiable_id', Auth::id())
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'data'    => ['count' => $count],
        ]);
    }

    public function markRead(string $id): JsonResponse
    {
        $notification = Notification::query()
            ->where('id', $id)
            ->where('notifiable_type', \App\Models\User::class)
            ->where('notifiable_id', Auth::id())
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'الإشعار غير موجود.',
            ], 404);
        }

        $notification->update([
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تعليم الإشعار كمقروء.',
        ]);
    }


    public function markAllRead(): JsonResponse
    {
        $updated = Notification::query()
            ->where('notifiable_type', \App\Models\User::class)
            ->where('notifiable_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "تم تعليم {$updated} إشعار كمقروء.",
        ]);
    }


    public function destroy(string $id): JsonResponse
    {
        $deleted = Notification::query()
            ->where('id', $id)
            ->where('notifiable_type', \App\Models\User::class)
            ->where('notifiable_id', Auth::id())
            ->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'الإشعار غير موجود.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الإشعار.',
        ]);
    }
}
