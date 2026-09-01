<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserVerification;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Constants\UserStatus;
use Illuminate\Support\Facades\Mail;
use App\Mail\AccountActivatedMail;

/**
 * Service for OTP generation and verification
 * 
 * Handles OTP operations with clean, maintainable code
 * Follows SOLID principles with proper separation of concerns
 */
class VerificationService
{
    protected UserRepositoryInterface $userRepository;
    protected UnifiedOtpService $unifiedOtpService;
    // OTP configuration constants
    private const OTP_LENGTH = 6;
    private const DEFAULT_EXPIRY_MINUTES = 10;
    private const RATE_LIMIT_MINUTES = 1;

    public function __construct(UserRepositoryInterface $userRepository, UnifiedOtpService $unifiedOtpService)
    {
        $this->userRepository = $userRepository;
        $this->unifiedOtpService = $unifiedOtpService;
    }

    /**
     * Generate and send OTP to user
     * 
     * @param User $user
     * @param string $purpose (verify_account, reset_password)
     * @return array
     */
    public function sendOtp(User $user, string $purpose = 'verify_account', ?string $preferredChannel = null, ?string $phoneNumber = null): array
    {
        $code = $this->generateOtpCode();

        return DB::transaction(function () use ($user, $code, $purpose, $preferredChannel,$phoneNumber ) {
            try {
                $this->deleteOldVerifications($user->id, $purpose);
                $this->createVerificationRecord($user, $code, $purpose);
                    $identifier = $preferredChannel === 'phone'
                        ? ($phoneNumber ?: $user->phone_number)
                        : ($user->email ?: $user->phone_number);
                    if ($identifier) {
                         $otpResult = $this->unifiedOtpService->sendOtpWithCode($identifier, $purpose);
                    // Check if rate limited
                    if ($otpResult === false) {
                        return [
                            'success' => false,
                            'code' => null,
                            'message' => 'تم تجاوز الحد المسموح لطلب الرموز. يرجى المحاولة بعد 10 دقائق.',
                        ];
                    }
                    if (!$otpResult['success']) {
                        return [
                            'success' => false,
                            'code' => null,
                            'message' => $otpResult['message'] ?? 'فشل إرسال رمز التحقق. يرجى المحاولة لاحقاً.',
                        ];
                    }
                }

                return [
                    'success' => true,
                    'message' => 'تم إرسال رمز التحقق بنجاح',
                ];
            } catch (\Exception $e) {
                Log::error("Failed to send OTP: " . $e->getMessage());
                return [
                    'success' => false,
                    'code' => null,
                    'message' => 'فشل إرسال رمز التحقق، يرجى المحاولة لاحقاً',
                ];
            }
        });
    }

    /**
     * Resend OTP with rate limiting check
     */
    public function resendOtp(string $uuid, string $purpose = 'verify_account', ?string $preferredChannel = null): array
    {
        $user = $this->getUserByUuid($uuid);
        
        if (!$user) {
            return $this->createResponse(false, 'إذا كان الحساب موجوداً، سيتم إرسال رمز التحقق');
        }
    
        if ($purpose === 'verify_account' && $user->is_approved && $user->status_id === UserStatus::ACTIVE) {
            return $this->createResponse(false, 'حسابك مفعل بالفعل. يرجى تسجيل الدخول.');
        }
    
        if (!$this->canResendOtp($user->id, $purpose)) {
            return $this->createResponse(false, 'يرجى الانتظار دقيقة واحدة قبل محاولة طلب كود جديد');
        }
    
        $result = $this->sendOtp($user, $purpose, $preferredChannel);
        
        $success = $result['success'] ?? false;
        $message = $success 
            ? 'تم إعادة إرسال كود التحقق بنجاح' 
            : ($result['message'] ?? 'فشل إرسال كود التحقق، يرجى المحاولة لاحقاً');
    
        return $this->createResponse($success, $message);
    }

    /**
     * Verify OTP code
     */
    public function verifyOtp(string $uuid, string $code, string $purpose = 'verify_account'): array
    {

        $user = $this->getUserByUuid($uuid);
        if (!$user) {
            return $this->createResponse(false, 'رمز التحقق غير صحيح أو منتهي الصلاحية');
        }

        $verification = $this->getVerification($user->id, $purpose);
        if (!$verification) {
            return $this->createResponse(false, 'رمز التحقق غير صحيح أو منتهي الصلاحية');
        }

        $validationResult = $this->validateVerification($verification, $code);
        if (!$validationResult['valid']) {
            return $this->createResponse(false, 'رمز التحقق غير صحيح أو منتهي الصلاحية');
        }

        $this->processVerificationSuccess($verification, $user, $purpose);

        return [
            'success' => true,
            'message' => 'تم التحقق بنجاح',
            'user' => $user
        ];
    }

    // ==================== Private Helper Methods ====================

    /**
     * Generate random OTP code
     */
    private function generateOtpCode(): string
    {
        // Generate 6-digit OTP code
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get user by UUID with error handling
     */
    private function getUserByUuid(string $uuid): ?User
    {
        return $this->userRepository->findByUuid($uuid);
    }

    /**
     * Get verification record for user
     */
    private function getVerification(int $userId, string $purpose): ?UserVerification
    {
        return UserVerification::where('user_id', $userId)
            ->where('purpose', $purpose)
            ->first();
    }

    /**
     * Delete old verification records
     */
    private function deleteOldVerifications(int $userId, string $purpose): void
    {
        UserVerification::where('user_id', $userId)
            ->where('purpose', $purpose)
            ->delete();
    }

    /**
     * Create new verification record
     */
    private function createVerificationRecord(User $user, string $code, string $purpose): void
    {
        UserVerification::create([
            'user_id' => $user->id,
            'code' => Hash::make($code),
            'channel' => $user->phone_number ? 'sms' : 'email', // Default for now
            'purpose' => $purpose,
            'expires_at' => Carbon::now()->addMinutes(
                config('auth.otp_expiry_minutes', self::DEFAULT_EXPIRY_MINUTES)
            ),
            'attempts' => 0,
        ]);
    }


    /**
     * Check if OTP can be resent (rate limiting)
     */
    private function canResendOtp(int $userId, string $purpose): bool
    {
        $lastVerification = $this->getVerification($userId, $purpose);
        
        if (!$lastVerification) {
            return true;
        }

        return $lastVerification->created_at->addMinute()->isPast();
    }

    /**
     * Validate verification record and code
     */
    private function validateVerification(UserVerification $verification, string $code): array
    {
        //  أزيل OTP من الـ logs (كان تسرب أمني)
        \Log::info('Verification validation', [
            'verification_id' => $verification->id,
            'expires_at' => $verification->expires_at,
            'is_past' => $verification->expires_at->isPast(),
            'code_match' => Hash::check($code, $verification->code),
        ]);

        // Check if already used
        if ($verification->verified_at) {
            return ['valid' => false, 'message' => 'تم استخدام هذا الكود من قبل، يرجى طلب كود جديد'];
        }

        // Check if expired
        if ($verification->expires_at->isPast()) {
            return ['valid' => false, 'message' => 'انتهت صلاحية الكود، يرجى طلب كود جديد'];
        }

        // Check if code matches
        if (!Hash::check($code, $verification->code)) {
            $verification->increment('attempts');
            
            if ($verification->attempts >= 5) {
                $verification->delete(); // Invalidate after 5 attempts
                return ['valid' => false, 'message' => 'تم استنفاد محاولات التحقق، يرجى طلب كود جديد'];
            }
            
            return ['valid' => false, 'message' => 'الكود غير صحيح، تبقى لك ' . (5 - $verification->attempts) . ' خطأ'];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Process successful verification
     */
    private function processVerificationSuccess(UserVerification $verification, User $user, string $purpose): void
    {
        DB::transaction(function () use ($verification, $user, $purpose) {
            // Mark verification as used
            $verification->verified_at = Carbon::now();
            $verification->save();

            // Handle purpose-specific actions
            if ($purpose === 'verify_account') {
                $this->handleAccountVerification($user);
            }
            $verification->delete();
        });
    }

    /**
     * Handle account verification (set is_approved = true)
     */
    private function handleAccountVerification(User $user): void
    {
        $this->userRepository->update($user->id, [
            'is_approved' => true, // Only approve, don't change status
            'status_id' => UserStatus::ACTIVE,
            'phone_verified_at' => $user->phone_number ? now() : null,
        ]);
        
        // Refresh user model after update
        $user->refresh();

        
         if ($user->email) {
        Mail::mailer('verify')->send(new \App\Mail\AccountActivatedMail($user));
    }
    }

    /**
     * Create standardized response array
     */
    private function createResponse(bool $success, string $message): array
    {
        return [
            'success' => $success,
            'message' => $message
        ];
    }
}