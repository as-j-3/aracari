<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserVerification;
use App\Services\Notification\EmailService;
use App\Services\Notification\SmsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class UnifiedOtpService
{
    private const OTP_TTL_SECONDS = 600;
    private const RATE_LIMIT_SECONDS = 60;
    private const GENERATION_RATE_LIMIT_MINUTES = 10;
    private const MAX_GENERATION_ATTEMPTS = 3;

    private EmailService $emailService;
    private SmsService $smsService;

    public function __construct(EmailService $emailService, SmsService $smsService)
    {
        $this->emailService = $emailService;
        $this->smsService = $smsService;
    }

    public function generateOtp(string $identifier, string $purpose = 'default'): string|false
    {
        $lock = Cache::lock('otp_gen_lock:' . sha1($identifier), 10);

        if (!$lock->get()) {
            Log::warning('Concurrent OTP generation blocked', ['identifier' => $identifier]);
            return false;
        }

        try {
            if ($this->isGenerationRateLimited($identifier)) {
                Log::warning('OTP generation rate limited', [
                    'identifier' => $identifier,
                    'purpose' => $purpose,
                ]);
                return false;
            }

            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            Cache::put($this->getOtpCacheKey($identifier, $purpose), [
                'otp' => Hash::make($otp),
                'attempts' => 0,
                'created_at' => now(),
            ], self::OTP_TTL_SECONDS);

            $this->storeOtpInDatabase($identifier, $otp, $purpose);
            $this->trackGenerationAttempt($identifier);

            return $otp;
        } finally {
            $lock->release();
        }
    }

    public function sendOtp(string $identifier, string $purpose = 'default'): bool
    {
        try {
            $otp = $this->generateOtp($identifier, $purpose);

            if ($otp === false) {
                return false;
            }

            $channel = $this->determineChannel($identifier);
            $sent = $this->sendViaChannel($identifier, $otp, $channel, $purpose);

            if ($sent) {
                Log::info('OTP sent successfully', [
                    'identifier' => $identifier,
                    'purpose' => $purpose,
                    'channel' => $channel,
                ]);
            }

            return $sent;
        } catch (\Exception $e) {
            Log::error('Failed to send OTP', [
                'identifier' => $identifier,
                'purpose' => $purpose,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendOtpWithCode(string $identifier, string $purpose = 'default'): array
    {
        try {
            $otp = $this->generateOtp($identifier, $purpose);

            if ($otp === false) {
                return [
                    'success' => false,
                    'code' => null,
                    'message' => 'تم تجاوز الحد المسموح لطلب الرموز. يرجى المحاولة لاحقاً.',
                ];
            }

            $channel = $this->determineChannel($identifier);
            $sent = $this->sendViaChannel($identifier, $otp, $channel, $purpose);

            if ($sent) {
                Log::info('OTP sent successfully', [
                    'identifier' => $identifier,
                    'purpose' => $purpose,
                    'channel' => $channel,
                ]);

                return [
                    'success' => true,
                    'message' => 'تم إرسال رمز التحقق',
                ];
            }

            return [
                'success' => false,
                'message' => 'فشل إرسال رمز التحقق. يرجى التحقق من رقم الهاتف والمحاولة لاحقاً.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'code' => null,
                'message' => 'فشل إرسال رمز التحقق. يرجى المحاولة لاحقاً.',
            ];
        }
    }

    public function verifyOtp(string $identifier, string $code, string $purpose = 'default'): array
    {
        $ip = request()->ip();

        if (RateLimiter::tooManyAttempts('otp_verify:' . $ip, 20)) {
            return ['valid' => false, 'message' => 'محظور مؤقتاً، حاول لاحقاً'];
        }

        if (Cache::has('otp_lockout:' . sha1($identifier))) {
            return ['valid' => false, 'message' => 'تم تجميد المحاولات. يرجى الانتظار 15 دقيقة.'];
        }

        $cacheKey = $this->getOtpCacheKey($identifier, $purpose);
        $otpData = Cache::get($cacheKey);

        if (!$otpData) {
            RateLimiter::hit('otp_verify:' . $ip, 900);
            return ['valid' => false, 'message' => 'انتهت صلاحية الكود، يرجى طلب كود جديد'];
        }

        if ($otpData['attempts'] >= 5) {
            Cache::forget($cacheKey);
            Cache::put('otp_lockout:' . sha1($identifier), true, 900);
            RateLimiter::hit('otp_verify:' . $ip, 900);
            return ['valid' => false, 'message' => 'تم استنفاد المحاولات. تم تجميد الحساب لمدة 15 دقيقة.'];
        }

        if (!Hash::check($code, $otpData['otp'])) {
            $otpData['attempts']++;
            Cache::put($cacheKey, $otpData, self::OTP_TTL_SECONDS);
            RateLimiter::hit('otp_verify:' . $ip, 900);

            $remaining = 5 - $otpData['attempts'];
            return ['valid' => false, 'message' => "الكود غير صحيح، تبقى لك {$remaining} محاولات"];
        }

        Cache::forget($cacheKey);
        Cache::forget('otp_lockout:' . sha1($identifier));
        RateLimiter::clear('otp_verify:' . $ip);

        $user = $this->findUserByIdentifier($identifier);
        if ($user) {
            UserVerification::where('user_id', $user->id)
                ->where('purpose', $purpose)
                ->whereNull('verified_at')
                ->update(['verified_at' => now()]);
        }

        return ['valid' => true, 'message' => 'تم التحقق بنجاح'];
    }

    public function canResendOtp(string $identifier, string $purpose = 'default'): bool
    {
        $cacheKey = $this->getRateLimitKey($identifier, $purpose);
        return !Cache::has($cacheKey);
    }

    public function setResendRateLimit(string $identifier, string $purpose = 'default'): void
    {
        $cacheKey = $this->getRateLimitKey($identifier, $purpose);
        Cache::put($cacheKey, true, self::RATE_LIMIT_SECONDS);
    }

    public function clearOtp(string $identifier, string $purpose = 'default'): void
    {
        Cache::forget($this->getOtpCacheKey($identifier, $purpose));
    }

    private function storeOtpInDatabase(string $identifier, string $otp, string $purpose): void
    {
        try {
            $user = $this->findUserByIdentifier($identifier);

            if (!$user) {
                Log::warning('User not found for OTP storage', ['identifier' => $identifier]);
                return;
            }

            UserVerification::where('user_id', $user->id)
                ->where('purpose', $purpose)
                ->delete();

            UserVerification::create([
                'user_id' => $user->id,
                'code' => Hash::make($otp),
                'channel' => $this->determineChannel($identifier),
                'purpose' => $purpose,
                'expires_at' => now()->addMinutes(10),
                'attempts' => 0,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store OTP in database', [
                'identifier' => $identifier,
                'purpose' => $purpose,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function findUserByIdentifier(string $identifier): ?User
    {
        if (str_contains($identifier, '@')) {
            return User::where('email', $identifier)->first();
        }

        return User::where('phone_number', $identifier)->first();
    }

    private function getOtpCacheKey(string $identifier, string $purpose): string
    {
        return 'otp:' . $purpose . ':' . sha1($identifier);
    }

    private function getRateLimitKey(string $identifier, string $purpose): string
    {
        return 'otp_rate_limit:' . $purpose . ':' . sha1($identifier);
    }

    private function isGenerationRateLimited(string $identifier): bool
    {
        $key = $this->getGenerationAttemptsKey($identifier);
        $attempts = Cache::get($key, []);
        $now = now();
        $windowMinutes = self::GENERATION_RATE_LIMIT_MINUTES;

        $recentAttempts = array_filter($attempts, function ($timestamp) use ($now, $windowMinutes) {
            return $now->diffInMinutes($timestamp) < $windowMinutes;
        });

        Cache::put($key, $recentAttempts, now()->addMinutes($windowMinutes));

        return count($recentAttempts) >= self::MAX_GENERATION_ATTEMPTS;
    }

    private function trackGenerationAttempt(string $identifier): void
    {
        $key = $this->getGenerationAttemptsKey($identifier);
        $attempts = Cache::get($key, []);

        $attempts[] = now();

        Cache::put($key, $attempts, now()->addMinutes(self::GENERATION_RATE_LIMIT_MINUTES));
    }

    private function getGenerationAttemptsKey(string $identifier): string
    {
        return 'otp_gen_attempts:' . sha1($identifier);
    }

    private function determineChannel(string $identifier): string
    {
        return str_contains($identifier, '@') ? 'email' : 'sms';
    }

    private function sendViaChannel(string $identifier, string $otp, string $channel, string $purpose): bool
    {
        try {
            switch ($channel) {
                case 'email':
                    return $this->emailService->sendOtp($identifier, $otp, $purpose);
                case 'sms':
                    return $this->smsService->sendOtp($identifier, $otp, $purpose);
                default:
                    Log::warning('Unknown OTP channel', ['channel' => $channel]);
                    return false;
            }
        } catch (\Exception $e) {
            Log::error('Failed to send OTP via ' . $channel, [
                'identifier' => $identifier,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
