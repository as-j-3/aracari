<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FCMService
{
    private string $projectId;
    private int $maxRetries = 3;
    private int $timeout = 10; 
    private int $retryDelay = 100; 

    public function __construct()
    {
        //  معرف المشروع من .env
        $this->projectId = config('services.firebase.project_id', 'aracari-app');
    }

    /**
     * Send a push notification to a specific device.
     *
     * @param string $deviceToken The FCM token of the target device
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data for navigation (type, id, etc.)
     * @return bool
     */
    public function sendNotification(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        //  التحقق من صحة الـ Token
        if (empty($deviceToken) || strlen($deviceToken) < 20) {
            Log::warning('Invalid FCM token provided', [
                'token_length' => strlen($deviceToken),
            ]);
            return false;
        }

        $data = $this->sanitizeData($data);
        $title = $this->sanitizeString($title);
        $body = $this->sanitizeString($body);

        $attempts = 0;
        while ($attempts < $this->maxRetries) {
            try {
                $firebase = app(FirebaseService::class);
                $accessToken = $firebase->getAccessToken();

                $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

                $payload = [
                    'message' => [
                        'token' => $deviceToken,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'android' => [
                            'notification' => [
                                'notification_priority' => 'PRIORITY_MAX',
                                'sound' => 'default',
                            ],
                        ],
                        'apns' => [
                            'payload' => [
                                'aps' => [
                                    'content_available' => true,
                                ],
                            ],
                        ],
                        'data' => [
                            'id' => $data['id'],
                            'title' => $data['title'],
                            'body' => $data['body'],
                            'navigate_to' => $data['navigate_to'] ?? 'notification',
                            'is_seen' => $data['is_seen'] ?? 'false',
                            'sent_date' => $data['sent_date'] ?? now()->toDateString(),
                        ],
                    ],
                ];

                //  إضافة timeout و retry
                $response = Http::timeout($this->timeout)
                    ->retry($this->maxRetries, $this->retryDelay)
                    ->withToken($accessToken)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, $payload);

                if ($response->successful()) {
                    Log::info('FCM Notification sent successfully', [
                        'token_prefix' => $this->maskToken($deviceToken), //  إخفاء الـ Token
                        'title' => $title,
                    ]);
                    return true;
                }

                //  معالجة أخطاء Firebase
                $errorData = $response->json();
                
                //  تسجيل الخطأ مع إخفاء الـ Token
                Log::error('FCM Notification failed', [
                    'error' => $errorData['error']['message'] ?? 'Unknown error',
                    'status' => $errorData['error']['status'] ?? null,
                    'token_prefix' => $this->maskToken($deviceToken), //  إخفاء الـ Token
                ]);

                //  إذا كان الـ Token غير صالح، نحذفه من قاعدة البيانات
                if (isset($errorData['error']['status'])) {
                    $status = $errorData['error']['status'];
                    if (in_array($status, ['UNREGISTERED', 'INVALID_ARGUMENT', 'NOT_FOUND'])) {
                        $this->deleteInvalidToken($deviceToken);
                        Log::info('Deleted invalid FCM token', [
                            'token_prefix' => $this->maskToken($deviceToken),
                            'reason' => $status,
                        ]);
                        return false;
                    }
                }

                $attempts++;
                if ($attempts < $this->maxRetries) {
                    usleep($this->retryDelay * 1000); // تحويل إلى ميكروثانية
                }

            } catch (\Exception $e) {
                Log::error('FCM Exception', [
                    'error' => $e->getMessage(),
                    'token_prefix' => $this->maskToken($deviceToken), //  إخفاء الـ Token
                ]);
                $attempts++;
            }
        }

        return false;
    }

    /**
     * Delete invalid/expired token from database.
     *
     * @param string $deviceToken
     * @return void
     */
    private function deleteInvalidToken(string $deviceToken): void
    {
        try {
            \App\Models\DeviceToken::where('fcm_token', $deviceToken)->delete();
        } catch (\Exception $e) {
            Log::error('Failed to delete invalid token', [
                'error' => $e->getMessage(),
                'token_prefix' => $this->maskToken($deviceToken),
            ]);
        }
    }

    /**
     * Mask FCM token for secure logging.
     * Shows only first 10 and last 10 characters.
     *
     * @param string $token
     * @return string
     */
    private function maskToken(string $token): string
    {
        if (strlen($token) <= 20) {
            return substr($token, 0, 5) . '...';
        }
        return substr($token, 0, 10) . '...' . substr($token, -10);
    }

    /**
     * Sanitize string data for security.
     *
     * @param string $input
     * @return string
     */
    private function sanitizeString(string $input): string
    {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize array data recursively.
     *
     * @param array $data
     * @return array
     */
    private function sanitizeData(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }
}