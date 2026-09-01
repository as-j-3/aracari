<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class SmsService
{
    private ?string $apiKey;
    private string $sender;

    public function __construct()
    {
    }

    public function sendOtp(string $phoneNumber, string $otp, string $purpose = 'default'): bool
    {
        try {
            $message = $this->getOtpMessage($otp, $purpose);
            return $this->sendSms($phoneNumber, $message);
        } catch (\Exception $e) {
            Log::error('Failed to send OTP SMS', [
                'phone' => $phoneNumber,
                'purpose' => $purpose,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendSms(string $phoneNumber, string $message): bool
    {
        try {
            $phoneNumber = $this->normalizePhoneNumber($phoneNumber);

            $twilio = new Client(
                config('services.twilio.sid'),
                config('services.twilio.auth_token')
            );

            $twilio->messages->create(
                $phoneNumber,
                [
                    'from' => config('services.twilio.phone_number'),
                    'body' => $message,
                ]
            );

            Log::info('SMS sent via Twilio', [
                'phone' => substr($phoneNumber, 0, 6) . '****',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send SMS via Twilio', [
                'phone' => substr($phoneNumber, 0, 6) . '****',
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('رقم الهاتف غير صالح أو لا يمكن استقبال الرسائل.');
        }
    }

    private function getOtpMessage(string $otp, string $purpose): string
    {
        return match ($purpose) {
            'verify_account' => "كود التحقق لحسابك في Aracari: {$otp}. صالح لمدة 10 دقائق.",
            'reset_password' => "كود إعادة تعيين كلمة المرور: {$otp}. صالح لمدة 10 دقائق.",
            'login' => "كود تسجيل الدخول: {$otp}. صالح لمدة 10 دقائق.",
            default => "كود التحقق: {$otp}. صالح لمدة 10 دقائق.",
        };
    }

    public function validatePhoneNumber(string $phoneNumber): bool
    {
        return preg_match('/^\+?[0-9]{10,15}$/', $phoneNumber);
    }

    public function normalizePhoneNumber(string $phoneNumber): string
    {
        $normalized = preg_replace('/[^0-9+]/', '', $phoneNumber);

        if (!str_starts_with($normalized, '+')) {
            $normalized = '+' . $normalized;
        }

        return $normalized;
    }
}