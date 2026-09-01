<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class OtpService
{
    private const OTP_TTL_SECONDS = 300; // 5 minutes
    private const MAX_GENERATION_ATTEMPTS = 3; // Max OTP generations per 10 minutes
    private const MAX_VERIFICATION_ATTEMPTS = 5; // Max failed verification attempts
    private const GENERATION_WINDOW_MINUTES = 10;
    private const VERIFICATION_LOCKOUT_MINUTES = 30;

    /**
     * Generate OTP with rate limiting
     * 
     * Security: Prevents OTP generation spam by tracking attempts per phone number.
     * Limits: 3 OTPs per 10 minutes per phone number.
     * 
     * @param string $phoneNumber Phone number to generate OTP for
     * @return string|false OTP code or false if rate limited
     */
    public function generateOtp(string $phoneNumber): string|false
    {
        // Check generation rate limit
        if ($this->isGenerationRateLimited($phoneNumber)) {
            Log::warning('OTP generation rate limited', [
                'phone_number' => $phoneNumber,
            ]);
            return false;
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        //  خزّن OTP كـ hash (وليس plaintext) لتقليل خطر تسرب cache
        Cache::put($this->getCacheKey($phoneNumber), Hash::make($otp), self::OTP_TTL_SECONDS);
        
        // Track generation attempt
        $this->trackGenerationAttempt($phoneNumber);

        return $otp;
    }

    /**
     * Send OTP with logging
     * 
     * @param string $phoneNumber Phone number to send OTP to
     * @param string $otp OTP code to send
     * @param string $purpose Purpose of OTP (e.g., 'verify_account', 'reset_password')
     * @return bool Success status
     */
    public function sendOtp(string $phoneNumber, string $otp, string $purpose = 'default'): bool
    {
        // Keep the OTP cached before delegating to an external provider.
        //  خزّن hash بدل plaintext
        Cache::put($this->getCacheKey($phoneNumber), Hash::make($otp), self::OTP_TTL_SECONDS);

        Log::info('OTP queued for delivery', [
            'phone_number' => $phoneNumber,
            'purpose' => $purpose,
        ]);

        return true;
    }

    /**
     * Verify OTP with rate limiting and attempt tracking
     * 
     * Security: Tracks failed verification attempts and locks out after 5 failures.
     * Lockout: 30 minutes after 5 failed attempts.
     * 
     * @param string $phoneNumber Phone number to verify OTP for
     * @param string $code OTP code provided by user
     * @return bool True if OTP is valid, false otherwise
     */
    public function verifyOtp(string $phoneNumber, string $code): bool
    {
        // Check if phone is locked out due to failed attempts
        if ($this->isVerificationLockedOut($phoneNumber)) {
            Log::warning('OTP verification blocked - phone locked out', [
                'phone_number' => $phoneNumber,
            ]);
            return false;
        }

        $cacheKey = $this->getCacheKey($phoneNumber);
        $storedOtpHash = Cache::get($cacheKey);

        //  تحقق عبر Hash::check بدل المقارنة المباشرة
        if (!$storedOtpHash || !Hash::check($code, $storedOtpHash)) {
            $this->trackFailedVerificationAttempt($phoneNumber);
            return false;
        }

        // Valid OTP - clear tracking and OTP
        $this->clearVerificationAttempts($phoneNumber);
        Cache::forget($cacheKey);

        return true;
    }

    /**
     * Check remaining verification attempts
     * 
     * @param string $phoneNumber Phone number to check
     * @return array Status with remaining attempts and lockout info
     */
    public function getVerificationStatus(string $phoneNumber): array
    {
        $attempts = $this->getVerificationAttempts($phoneNumber);
        $remaining = max(0, self::MAX_VERIFICATION_ATTEMPTS - $attempts);
        
        return [
            'remaining_attempts' => $remaining,
            'is_locked_out' => $this->isVerificationLockedOut($phoneNumber),
            'lockout_expires_at' => $this->getLockoutExpiry($phoneNumber),
        ];
    }

    /**
     * Check if generation is rate limited
     * 
     * @param string $phoneNumber Phone number to check
     * @return bool True if rate limited
     */
    private function isGenerationRateLimited(string $phoneNumber): bool
    {
        $attempts = Cache::get($this->getGenerationAttemptsKey($phoneNumber), 0);
        return $attempts >= self::MAX_GENERATION_ATTEMPTS;
    }

    /**
     * Track OTP generation attempt
     * 
     * @param string $phoneNumber Phone number to track
     */
    private function trackGenerationAttempt(string $phoneNumber): void
    {
        $key = $this->getGenerationAttemptsKey($phoneNumber);
        $attempts = Cache::get($key, 0);
        
        // Increment with sliding window
        $ttl = now()->addMinutes(self::GENERATION_WINDOW_MINUTES);
        Cache::put($key, $attempts + 1, $ttl);
    }

    /**
     * Check if phone is locked out from verification
     * 
     * @param string $phoneNumber Phone number to check
     * @return bool True if locked out
     */
    private function isVerificationLockedOut(string $phoneNumber): bool
    {
        return $this->getVerificationAttempts($phoneNumber) >= self::MAX_VERIFICATION_ATTEMPTS;
    }

    /**
     * Track failed verification attempt
     * 
     * @param string $phoneNumber Phone number to track
     */
        private function trackFailedVerificationAttempt(string $phoneNumber): void
        {
            $key = $this->getVerificationAttemptsKey($phoneNumber);
            
            // استخدم increment الذري
            $attempts = Cache::increment($key, 1, now()->addMinutes(self::VERIFICATION_LOCKOUT_MINUTES));
            
            Log::warning('OTP verification failed - attempt tracked', [
                'phone_number' => $phoneNumber,
                'attempt' => $attempts,
                'max_attempts' => self::MAX_VERIFICATION_ATTEMPTS,
            ]);
        }


    /**
     * Get verification attempts count
     * 
     * @param string $phoneNumber Phone number to check
     * @return int Number of failed attempts
     */
    private function getVerificationAttempts(string $phoneNumber): int
    {
        return Cache::get($this->getVerificationAttemptsKey($phoneNumber), 0);
    }

    /**
     * Clear verification attempts (on successful verification)
     * 
     * @param string $phoneNumber Phone number to clear
     */
    private function clearVerificationAttempts(string $phoneNumber): void
    {
        Cache::forget($this->getVerificationAttemptsKey($phoneNumber));
    }

    /**
     * Get lockout expiry time
     * 
     * @param string $phoneNumber Phone number to check
     * @return string|null Lockout expiry timestamp or null
     */
    private function getLockoutExpiry(string $phoneNumber): ?string
    {
        if (!$this->isVerificationLockedOut($phoneNumber)) {
            return null;
        }
        
        // Approximate expiry based on lockout window
        return now()->addMinutes(self::VERIFICATION_LOCKOUT_MINUTES)->toDateTimeString();
    }

    /**
     * Cache key for OTP code
     * 
     * @param string $phoneNumber Phone number
     * @return string Cache key
     */
    private function getCacheKey(string $phoneNumber): string
    {
        return 'otp_phone:' . sha1($phoneNumber);
    }

    /**
     * Cache key for generation attempts tracking
     * 
     * @param string $phoneNumber Phone number
     * @return string Cache key
     */
    private function getGenerationAttemptsKey(string $phoneNumber): string
    {
        return 'otp_gen_attempts:' . sha1($phoneNumber);
    }

    /**
     * Cache key for verification attempts tracking
     * 
     * @param string $phoneNumber Phone number
     * @return string Cache key
     */
    private function getVerificationAttemptsKey(string $phoneNumber): string
    {
        return 'otp_verify_attempts:' . sha1($phoneNumber);
    }
}
