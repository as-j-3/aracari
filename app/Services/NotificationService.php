<?php

namespace App\Services;

use App\Constants\NotificationScreens;
use App\Constants\NotificationTypes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Notification;
use App\Models\DeviceToken;
use App\Models\Booking;
use App\Models\User;

class NotificationService
{
    public function __construct(
        private FCMService $fcmService
    ) {}

    /**
     * إرسال إشعار لمستخدم واحد مع تخزينه في قاعدة البيانات
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        //  1. التحقق من المدخلات
        if (empty($title) || empty($body)) {
            Log::warning('Notification skipped: empty title or body');
            return false;
        }

        
        $notification = Notification::create([
            'id' => (string) Str::uuid(),
            'type' => $data['type'] ?? NotificationTypes::GENERAL,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'title' => $title,
                'body' => $body,
                'screen' => $data['screen'] ?? NotificationScreens::HOME,
                $data,
            ]),
        ]);

        //  3. جلب أجهزة المستخدم النشطة (آخر 30 يوم)
        $deviceTokens = DeviceToken::where('user_id', $user->id)
            ->where('last_used_at', '>=', now()->subDays(30))
            ->get();

        if ($deviceTokens->isEmpty()) {
            Log::info('No active devices for user', ['user_id' => $user->id]);
            return false;
        }

        //  4. إرسال لكل جهاز
        $sent = false;
        foreach ($deviceTokens as $deviceToken) {
            $result = $this->fcmService->sendNotification(
                deviceToken: $deviceToken->fcm_token,
                title: $title,
                body: $body,
                data: [
                    'id' => $notification->id,
                    'title' => $title,
                    'body' => $body,
                    'navigate_to' => $data['screen'] ?? 'notification',
                    'is_seen' => 'false',
                    'sent_date' => now()->toDateString(),
                ]
            );

            if ($result) {
                $sent = true;
            }
        }

        return $sent;
    }

    /**
     * إرسال إشعار لعدة مستخدمين
     */
    public function sendToUsers(array $userIds, string $title, string $body, array $data = []): void
    {
        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            $this->sendToUser($user, $title, $body, $data);
        }
    }
    /**
 * إرسال إشعار حجز (تأكيد أو إلغاء)
 */
    public function sendBookingNotification(User $user, string $type, array $data): void
    {
        $config = match ($type) {
            NotificationTypes::BOOKING_CONFIRMED => [
                'title' => 'تم تأكيد حجزك!',
                'body' => "حجزك رقم {$data['booking_id']} تم تأكيده بنجاح",
                'screen' => NotificationScreens::BOOKING_DETAILS,
            ],
            NotificationTypes::BOOKING_CANCELLED => [
                'title' => 'تم إلغاء حجزك',
                'body' => ($data['refund_amount'] ?? 0) > 0
                    ? "حجزك {$data['booking_id']} تم إلغاؤه. المبلغ المسترد: {$data['refund_amount']} ريال"
                    : "حجزك {$data['booking_id']} تم إلغاؤه. غرامة الإلغاء: {$data['penalty_amount']} ريال",
                'screen' => NotificationScreens::BOOKING_DETAILS,
            ],
            default => null
        };

        if (!$config) return;

        $this->sendToUser($user, $config['title'], $config['body'], [
            'type' => $type,
            'screen' => $config['screen'],
            $data,
        ]);
    }
    public function notifyBookingSafe(Booking $booking, float $amount, string $type, float $penaltyAmount = 0): void
    {
        try {
            $this->sendBookingNotification(
                user: $booking->user,
                type: $type,
                data: ['booking_id' => $booking->id, 'amount' => $amount, 'penalty_amount' => $penaltyAmount]
            );
        } catch (\Exception $e) {
            Log::error('Notification failed: ' . $e->getMessage());
        }

    }
}