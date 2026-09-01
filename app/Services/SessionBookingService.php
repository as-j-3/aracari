<?php

namespace App\Services;

use App\Services\AuditService;
use App\Constants\NumericConstants;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

/**
 * Service for managing booking sessions and recovery mechanisms
 * 
 * Handles session preservation during password resets and other interruptions
 * Provides secure recovery mechanisms for abandoned booking sessions
 */
class SessionBookingService
{
    /**
     * Store booking session data with expiration
     *
     * @param array $bookingData The booking data to preserve
     * @return string Unique session identifier
     */
    public function storeBookingSession(array $bookingData): string
    {
        $sessionId = (string) Str::uuid();

        
        Cache::put("booking_session_{$sessionId}", [
            'data' => $bookingData,
            'created_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ], NumericConstants::CACHE_TTL_ONE_HOUR); // 1 hour expiration

        return $sessionId;
    }

    /**
     * Retrieve booking session data if valid
     *
     * @param string $sessionId The session identifier to retrieve
     * @return array|null The session data or null if expired
     */
    public function getBookingSession(string $sessionId): ?array
    {
        $sessionData = Cache::get("booking_session_{$sessionId}");
        
        if (!$sessionData) {
            return null;
        }

        return $sessionData;
    }

    /**
     * Handle password reset during active booking session
     *
     * @param string $sessionId The current booking session ID
     * @return array Result with recovery token for post-reset continuation
     */
    public function handlePasswordResetDuringBooking(string $sessionId): array
    {
        $sessionData = $this->getBookingSession($sessionId);
        
        if (!$sessionData) {
            return [
                'success' => false,
                'message' => 'Booking session expired'
            ];
        }

        AuditService::log(
            'PASSWORD_RESET_DURING_BOOKING',
            'User initiated password reset during active booking session',
            [
                'session_id' => $sessionId,
                'booking_data' => $sessionData['data'],
                'ip_address' => request()->ip()
            ]
        );

        Cache::put("booking_recovery_{$sessionId}", [
            'original_session' => $sessionData,
            'password_reset_at' => now(),
            'requires_recovery' => true
        ], NumericConstants::CACHE_TTL_TWO_HOURS); // 2 hours for recovery

        return [
            'success' => true,
            'message' => 'Booking session preserved for recovery after password reset',
            'recovery_token' => $sessionId
        ];
    }

    /**
     * Recover booking session after password reset completion
     *
     * @param string $recoveryToken The recovery token from password reset
     * @return array|null The original booking data or null if invalid
     */
    public function recoverBookingSession(string $recoveryToken): ?array
    {
        $recoveryData = Cache::get("booking_recovery_{$recoveryToken}");
        
        if (!$recoveryData || !$recoveryData['requires_recovery']) {
            return null;
        }

        if ($recoveryData['password_reset_at']->diffInMinutes(now()) > NumericConstants::TIME_LIMIT_TWO_HOURS) {
            return null;
        }

        AuditService::log(
            'BOOKING_SESSION_RECOVERED',
            'User recovered booking session after password reset',
            [
                'recovery_token' => $recoveryToken,
                'original_session_time' => $recoveryData['original_session']['created_at'],
                'recovery_time' => now()
            ]
        );

        return $recoveryData['original_session']['data'];
    }

    /**
     * Clear booking session and recovery data
     *
     * @param string $sessionId The session ID to clear
     */
    public function clearBookingSession(string $sessionId): void
    {
        Cache::forget("booking_session_{$sessionId}");
        Cache::forget("booking_recovery_{$sessionId}");
    }

    /**
     * Validate session freshness and security
     *
     * @param string $sessionId The session ID to validate
     * @return bool True if session is valid and secure
     */
    public function isSessionValid(string $sessionId): bool
    {
        $sessionData = Cache::get("booking_session_{$sessionId}");
        
        if (!$sessionData) {
            return false;
        }

        return $sessionData['created_at']->diffInMinutes(now()) < NumericConstants::TIME_LIMIT_ONE_HOUR;
    }
}
