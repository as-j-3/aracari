<?php

namespace App\Services;

use App\Models\User;
use App\Constants\NumericConstants;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetPasswordService
{
    /**
     * Generate unique reset UUID
     */
    public function generateResetUuid(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Store reset request with expiry
     */
    public function storeResetRequest(string $uuid, int $userId, string $deliveryTarget): void
    {
        Cache::put("reset_password:{$uuid}", [
            'user_id' => $userId,
            'delivery_target' => $deliveryTarget,
            'created_at' => now(),
        ], NumericConstants::CACHE_TTL_FIVE_MINUTES); // 5 minutes

        Cache::put("reset_password_user:{$userId}", $uuid, NumericConstants::CACHE_TTL_FIVE_MINUTES);
    }

    /**
     * Get reset request data
     */
    public function getResetRequest(string $uuid): ?array
    {
        return Cache::get("reset_password:{$uuid}");
    }

    /**
     * Delete reset request
     */
    public function deleteResetRequest(string $uuid): void
    {
        $resetData = $this->getResetRequest($uuid);
        if (!empty($resetData['user_id'])) {
            Cache::forget("reset_password_user:{$resetData['user_id']}");
        }

        Cache::forget("reset_password:{$uuid}");
    }

    /**
     * Get an active reset UUID by user id.
     */
    public function getActiveResetUuidByUserId(int $userId): ?string
    {
        return Cache::get("reset_password_user:{$userId}");
    }

    /**
     * Generate reset token
     */
    public function generateResetToken(): string
    {
        return Str::random(60);
    }

    /**
     * Store reset token with expiry
     */
    public function storeResetToken(string $resetToken, int $userId, string $uuid): void
    {
        Cache::put("reset_token:{$resetToken}", [
            'user_id' => $userId,
            'uuid' => $uuid,
            'created_at' => now(),
        ], NumericConstants::CACHE_TTL_FIVE_MINUTES); // 5 minutes
        
        // Store active reset request for user
        Cache::put("reset_password_user:{$userId}", $uuid, NumericConstants::CACHE_TTL_FIVE_MINUTES);
    }

    /**
     * Get reset token data
     */
    public function getResetToken(string $resetToken): ?array
    {
        return Cache::get("reset_token:{$resetToken}");
    }

    /**
     * Delete reset token
     */
    public function deleteResetToken(string $resetToken): void
    {
        $tokenData = Cache::get("reset_token:{$resetToken}");
        
        if ($tokenData) {
            // Remove active reset request for user
            Cache::forget("reset_password_user:{$tokenData['user_id']}");
        }
        
        Cache::forget("reset_token:{$resetToken}");
    }

    
    /**
     * Reset user password
     */
    public function resetPassword(int $userId, string $password): bool
    {
        $user = User::find($userId);
        
        if (!$user) {
            return false;
        }

        $user->update([
            'password' => Hash::make($password),
        ]);

        // Logout all sessions
        $user->tokens()->delete();

        return true;
    }

    /**
     * Validate reset token and UUID match
     */
    public function validateResetToken(string $resetToken, string $uuid): bool
    {
        $tokenData = $this->getResetToken($resetToken);
        
        return $tokenData && $tokenData['uuid'] === $uuid;
    }
}
