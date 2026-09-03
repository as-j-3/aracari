<?php

namespace App\Services\Auth;

use App\Constants\UserStatus;
use App\Models\LoginActivity;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\OtpService;
use App\Services\ResetPasswordService;
use App\Services\Auth\UnifiedOtpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordChangedMail;
use App\Mail\AccountActivatedMail;
use Illuminate\Support\Facades\DB;

class AuthService
{
    protected UserRepositoryInterface $userRepository;
    protected VerificationService $verificationService;
    protected ResetPasswordService $resetPasswordService;
    protected OtpService $otpService;
    protected UnifiedOtpService $unifiedOtpService;

    public function __construct(
        UserRepositoryInterface $userRepository,
        VerificationService $verificationService,
        ResetPasswordService $resetPasswordService,
        OtpService $otpService,
        UnifiedOtpService $unifiedOtpService
    ) {
        $this->userRepository = $userRepository;
        $this->verificationService = $verificationService;
        $this->resetPasswordService = $resetPasswordService;
        $this->otpService = $otpService;
        $this->unifiedOtpService = $unifiedOtpService;
    }

    public function login(array $credentials): array
    {
        $identifier = $credentials['phone_number'] ?? $credentials['email'] ?? 'unknown';
        $deviceId = $credentials['device_id'] ?? null;

        if (RateLimiter::tooManyAttempts('login_failures:' . request()->ip(), 5)) {
            return [
                'success' => false,
                'message' => 'تم حظر محاولات الدخول مؤقتًا، حاول لاحقًا',
                'code' => 429,
            ];
        }

        $user = !empty($credentials['phone_number'])
            ? $this->userRepository->findByPhoneNumber($credentials['phone_number'])
            : $this->userRepository->findByEmail($credentials['email'] ?? '');

        if (!$user) {
            RateLimiter::hit('login_failures:' . request()->ip(), \App\Constants\NumericConstants::CACHE_TTL_FIVE_MINUTES);

            return [
                'success' => false,
                'message' => 'بيانات الدخول غير صحيحة',
                'code' => 401,
            ];
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            $this->recordLoginActivity($user, $identifier, 'failed');
            RateLimiter::hit('login_failures:' . request()->ip(), \App\Constants\NumericConstants::CACHE_TTL_FIVE_MINUTES);

            return [
                'success' => false,
                'message' => 'بيانات الدخول غير صحيحة',
                'code' => 401,
            ];
        }

        if ($user->status_id === UserStatus::PENDING) {
            $this->recordLoginActivity($user, $identifier, 'failed');
            $this->verificationService->sendOtp($user);

            return [
                'success' => false,
                'message' => 'حسابك يحتاج إلى تفعيل. تم إرسال رمز التحقق.',
                'needs_otp' => true,
                'uuid' => $user->uuid,
                'code' => 401,
            ];
        }

        if ($user->status_id === UserStatus::SUSPENDED) {
            $this->recordLoginActivity($user, $identifier, 'failed');

            return [
                'success' => false,
                'message' => 'بيانات الدخول غير صحيحة',
                'code' => 401,
            ];
        }

        if (!$user->is_approved) {
            $this->recordLoginActivity($user, $identifier, 'failed');
            return [
                'success' => false,
                'message' => 'بيانات الدخول غير صحيحة',
                'code' => 401,
            ];
        }

        $token = $this->issueAccessToken($user, $deviceId);
        RateLimiter::clear('login_failures:' . request()->ip());
        $this->recordLoginActivity($user, $identifier, 'success');

        return [
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'data' => [
                'user' => $user->load(['status:id,status_code', 'userType']),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'device_id' => $deviceId,
            ],
        ];
    }

    public function attemptProviderAppLogin(array $data): array
    {
        $identifier = $data['phone_number'] ?? $data['email'] ?? 'unknown';
        $password = $data['password'];
        $deviceId = $data['device_id'] ?? null;

        if (RateLimiter::tooManyAttempts('login_failures:' . request()->ip(), 5)) {
            return [
                'success' => false,
                'message' => 'تم حظر محاولات الدخول مؤقتًا. يرجى المحاولة بعد 5 دقائق.',
                'code' => 429,
            ];
        }

        $user = null;
        if (!empty($data['phone_number'])) {
            $user = $this->userRepository->findByPhoneNumber($data['phone_number']);
        } else {
            $user = $this->userRepository->findByEmail($data['email'] ?? '');
        }

        if (!$user || !Hash::check($password, $user->password)) {
            $this->recordLoginActivity($user, $identifier, 'failed');
            RateLimiter::hit('login_failures:' . request()->ip(), 300);
            return ['success' => false, 'message' => 'بيانات الدخول غير صحيحة.', 'code' => 401];
        }

        if ($user->status_id === UserStatus::SUSPENDED || !$user->is_approved) {
            $this->recordLoginActivity($user, $identifier, 'failed');
            RateLimiter::hit('login_failures:' . request()->ip(), 300);
            return ['success' => false, 'message' => 'بيانات الدخول غير صحيحة.', 'code' => 401];
        }

        if (!$user->isAdmin() && !$user->isProvider() && !$user->isProviderStaff()) {
            return ['success' => false, 'message' => 'هذا التطبيق مخصص للمزودين وموظفيهم فقط.', 'code' => 403];
        }

        if (!$user->hasPermission('bookings:board')) {
            return ['success' => false, 'message' => 'ليس لديك صلاحية استخدام تطبيق الصعود.', 'code' => 403];
        }

        $token = $this->issueAccessToken($user, $deviceId);
        RateLimiter::clear('login_failures:' . request()->ip());
        $this->recordLoginActivity($user, $identifier, 'success');

        return [
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'data' => [
                'user' => $user->load(['status:id,status_code', 'userType']),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'device_id' => $deviceId,
            ],
        ];
    }

    public function forgotPassword(string $identifier): array
    {
        $user = $this->findUserByIdentifier($identifier);
        if (!$user) {
            return [
                'success' => true,
                'message' => 'إذا كان الحساب مسجلًا لدينا، فستتلقى كود التحقق قريبًا',
                'uuid' => null,
            ];
        }

        return $this->requestPasswordResetForUser($user, $identifier);
    }

    public function resolvePasswordResetIdentifier(array $data): string
    {
        return (string) ($data['uuid'] ?? $data['phone_number'] ?? $data['email'] ?? '');
    }

    public function requestPasswordResetForUser(User $user, string $identifier): array
    {
        try {
            $normalizedIdentifier = strtolower(trim($identifier));
            $rateLimitKey = 'password_reset_request:' . sha1($normalizedIdentifier);
            $deliveryTarget = match (true) {
                !empty($user->email) && strcasecmp($normalizedIdentifier, $user->email) === 0 => $user->email,
                !empty($user->phone_number) && trim($identifier) === $user->phone_number => $user->phone_number,
                default => $user->email ?: $user->phone_number,
            };

            if (!$deliveryTarget) {
                return [
                    'success' => false,
                    'message' => 'لا يمكن إرسال رمز إعادة التعيين لعدم توفر وسيلة تواصل صالحة',
                    'code' => 400,
                ];
            }

            if (RateLimiter::tooManyAttempts($rateLimitKey, 2)) {
                return [
                    'success' => false,
                    'message' => 'تم طلب رمز إعادة التعيين مؤخرًا، يرجى الانتظار قليلًا قبل إعادة المحاولة',
                    'code' => 429,
                ];
            }

            $existingUuid = $this->resetPasswordService->getActiveResetUuidByUserId($user->id);
            if ($existingUuid && $this->resetPasswordService->getResetRequest($existingUuid)) {
                return [
                    'success' => true,
                    'message' => 'إذا كان الحساب مسجلًا لدينا، فستتلقى كود التحقق قريبًا',
                ];
            }

            $uuid = $this->resetPasswordService->generateResetUuid();

            $this->resetPasswordService->storeResetRequest($uuid, $user->id, $deliveryTarget);

            $unifiedOtp = $this->unifiedOtpService;
            $result = $unifiedOtp->sendOtpWithCode($deliveryTarget, 'password_reset');
            if (!$result['success']) {
                return [
                    'success' => false,
                    'message' => $result['message'] ?? 'تم تجاوز الحد المسموح لطلب الرموز. يرجى المحاولة لاحقاً.',
                ];
            }

            RateLimiter::hit($rateLimitKey, \App\Constants\NumericConstants::CACHE_TTL_FIVE_MINUTES);

            return [
                'success' => true,
                'message' => 'إذا كان الحساب مسجلًا لدينا، فستتلقى كود التحقق قريبًا',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to request password reset for user: ' . $e->getMessage(), [
                'identifier' => $identifier,
                'user_id' => $user->id,
            ]);

            return [
                'success' => false,
                'message' => 'فشل إرسال الكود: ' . $e->getMessage(),
            ];
        }
    }

    public function resetPassword(string $identifier, string $code, string $newPassword): array
    {
        $user = $this->findUserByIdentifier($identifier);
        if (!$user) {
            return ['success' => false, 'message' => 'رمز التحقق غير صحيح أو منتهي الصلاحية'];
        }

        $uuid = $this->resetPasswordService->getActiveResetUuidByUserId($user->id);
        if (!$uuid) {
            return ['success' => false, 'message' => 'لا يوجد طلب إعادة تعيين نشط. يرجى طلب كود جديد.'];
        }

        $resetData = $this->resetPasswordService->getResetRequest($uuid);
        if (!$resetData) {
            return ['success' => false, 'message' => 'رمز التحقق غير صحيح أو منتهي الصلاحية'];
        }

        $deliveryTarget = $resetData['delivery_target'] ?? null;
        $verification = $this->unifiedOtpService->verifyOtp($deliveryTarget, $code, 'password_reset');

        if (!$verification['valid']) {
            return ['success' => false, 'message' => $verification['message'] ?? 'رمز التحقق غير صحيح أو منتهي الصلاحية'];
        }

        try {
            DB::transaction(function () use ($user, $newPassword, $uuid) {
                $user->update(['password' => Hash::make($newPassword)]);
                $user->tokens()->delete();
                $this->resetPasswordService->deleteResetRequest($uuid);
            });
        } catch (\Exception $e) {
            Log::error('Failed to reset password in transaction: ' . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ أثناء إعادة تعيين كلمة المرور.'];
        }

        if ($user->email) {
            Mail::mailer('verify')->send(new PasswordChangedMail($user));
        }

        return ['success' => true, 'message' => 'تم إعادة تعيين كلمة المرور بنجاح، يمكنك الآن تسجيل الدخول'];
    }

    public function changePassword(User $user, string $oldPassword, string $newPassword): array
    {
        if (Auth::id() !== $user->id) {
            Log::warning('Unauthorized password change attempt', [
                'auth_user' => Auth::id(),
                'target_user' => $user->id,
            ]);
            return ['success' => false, 'message' => 'غير مصرح'];
        }

        if (!Hash::check($oldPassword, $user->password)) {
            return ['success' => false, 'message' => 'كلمة المرور الحالية غير صحيحة'];
        }

        try {
            DB::transaction(function () use ($user, $newPassword) {
                $user->update(['password' => Hash::make($newPassword)]);
                $user->tokens()->delete();
            });
        } catch (\Exception $e) {
            Log::error('Failed to change password in transaction: ' . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ أثناء تغيير كلمة المرور.'];
        }

        if ($user->email) {
            Mail::mailer('verify')->send(new \App\Mail\PasswordChangedMail($user));
        }

        return ['success' => true, 'message' => 'تم تغيير كلمة المرور بنجاح'];
    }

    public function logout(): void
    {
        Auth::user()?->currentAccessToken()?->delete();
    }

    public function issueAccessToken(User $user, ?string $deviceId): string
    {
        $deviceId = $deviceId ?: 'unknown-device';
        $ability = 'device:' . $deviceId;

        $user->tokens()
            ->where('name', 'auth_token:' . $deviceId)
            ->delete();

        return $user->createToken('auth_token:' . $deviceId, [$ability])->plainTextToken;
    }

    public function recordLoginActivity(?User $user, string $identifier, string $status): void
    {
        try {
            LoginActivity::create([
                'user_id' => $user ? $user->id : null,
                'identifier' => $identifier,
                'status' => $status,
                'user_agent' => request()->userAgent() ?? 'Unknown',
                'ip_address' => request()->ip() ?? 'Unknown',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record login activity: ' . $e->getMessage());
        }
    }

    public function register(array $userData): array
    {
        try {
            $user = $this->userRepository->create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'phone_number' => $userData['phone_number'],
                'password' => Hash::make($userData['password']),
                'type_user_code' => $userData['type_user_code'],
                'status_id' => UserStatus::PENDING,
            ]);

            Log::info('User registered successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'phone' => $user->phone_number,
                'type' => $userData['type_user_code'],
            ]);

            return [
                'success' => true,
                'message' => 'تم إنشاء الحساب بنجاح',
                'user' => $user,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'فشل إنشاء الحساب: ' . $e->getMessage(),
            ];
        }
    }

    public function directRegisterUser(array $data): array
    {
        try {
            $user = $this->userRepository->create([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
                'password' => Hash::make($data['password']),
                'type_user_code' => $data['type_user_code'] ?? \App\Constants\UserTypes::PROVIDER,
                'status_id' => UserStatus::ACTIVE,
                'is_approved' => true,
                'phone_verified_at' => now(),
            ]);

            $token = $this->issueAccessToken($user, $data['device_id'] ?? null);

            Log::info('Direct user registered successfully without OTP', [
                'user_id' => $user->id,
                'phone' => $user->phone_number,
                'type' => $user->type_user_code,
            ]);

            return [
                'success' => true,
                'message' => 'تم إنشاء الحساب وتفعيله بنجاح',
                'data' => [
                    'user' => $user->load(['status:id,status_code', 'userType']),
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'device_id' => $data['device_id'] ?? null,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Direct register failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'فشل إنشاء الحساب: ' . $e->getMessage(),
                'code' => 500,
            ];
        }
    }

    public function verifyPasswordResetOtp(string $identifier, string $code): array
    {
        try {
            $user = $this->findUserByIdentifier($identifier);
            if (!$user) {
                return ['success' => false, 'message' => 'رمز التحقق غير صحيح أو منتهي الصلاحية'];
            }

            $uuid = $this->resetPasswordService->getActiveResetUuidByUserId($user->id);
            if (!$uuid) {
                return [
                    'success' => false,
                    'message' => 'لا يوجد طلب إعادة تعيين كلمة مرور نشط لهذا المستخدم',
                ];
            }

            $resetData = $this->resetPasswordService->getResetRequest($uuid);
            if (!$resetData) {
                return [
                    'success' => false,
                    'message' => 'طلب إعادة تعيين كلمة المرور منتهي الصلاحية',
                ];
            }

            $deliveryTarget = $resetData['delivery_target'] ?? null;
            $unifiedOtp = $this->unifiedOtpService;
            $verification = $unifiedOtp->verifyOtp($deliveryTarget, $code, 'password_reset');

            if ($verification['valid']) {
                $resetToken = $this->resetPasswordService->generateResetToken();
                $this->resetPasswordService->storeResetToken($resetToken, $resetData['user_id'], $uuid);

                $this->resetPasswordService->deleteResetRequest($uuid);

                return [
                    'success' => true,
                    'message' => 'تم التحقق من الكود بنجاح',
                    'reset_token' => $resetToken,
                    'uuid' => $uuid,
                ];
            }

            return [
                'success' => false,
                'message' => $verification['message'],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to verify password reset OTP: ' . $e->getMessage(), [
                'identifier' => $identifier,
            ]);

            return [
                'success' => false,
                'message' => 'فشل التحقق من الكود: ' . $e->getMessage(),
            ];
        }
    }

    public function resetPasswordWithToken(string $identifier, string $resetToken, string $password): array
    {
        try {
            $user = $this->findUserByIdentifier($identifier);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'رمز إعادة التعيين غير صالح أو منتهي الصلاحية',
                ];
            }

            $tokenData = $this->resetPasswordService->getResetToken($resetToken);

            if (!$tokenData) {
                return [
                    'success' => false,
                    'message' => 'رمز إعادة التعيين غير صالح أو منتهي الصلاحية',
                ];
            }

            if ($tokenData['user_id'] !== $user->id) {
                return [
                    'success' => false,
                    'message' => 'رمز إعادة التعيين غير صالح أو منتهي الصلاحية',
                ];
            }

            $this->resetPasswordService->deleteResetToken($resetToken);

            if (!$this->resetPasswordService->resetPassword($tokenData['user_id'], $password)) {
                return [
                    'success' => false,
                    'message' => 'فشل تحديث كلمة المرور',
                ];
            }

            User::find($tokenData['user_id'])->tokens()->delete();

            if ($user->email) {
                Mail::mailer('verify')->send(new PasswordChangedMail($user));
            }

            return [
                'success' => true,
                'message' => 'تم تغيير كلمة المرور بنجاح',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to reset password: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'فشل تغيير كلمة المرور',
            ];
        }
    }

    public function webLogin(string $identity, string $password): array
    {
        if (RateLimiter::tooManyAttempts('login_failures:' . request()->ip(), 5)) {
            return [
                'success' => false,
                'message' => 'تم حظر محاولات الدخول مؤقتاً بسبب المحاولات المتكررة. يرجى المحاولة بعد 5 دقائق.',
                'code' => 429,
            ];
        }

        $user = null;
        if (filter_var($identity, FILTER_VALIDATE_EMAIL)) {
            $user = $this->userRepository->findByEmail($identity);
        } else {
            $user = $this->userRepository->findByPhoneNumber($identity);
        }

        if (!$user) {
            $this->recordLoginActivity(null, $identity, 'failed');
            RateLimiter::hit('login_failures:' . request()->ip(), 300);
            return ['success' => false, 'message' => 'بيانات الدخول غير صحيحة.'];
        }

        if (!Hash::check($password, $user->password)) {
            $this->recordLoginActivity($user, $identity, 'failed');
            RateLimiter::hit('login_failures:' . request()->ip(), 300);
            return ['success' => false, 'message' => 'بيانات الدخول غير صحيحة.'];
        }

        if ($user->status_id === UserStatus::PENDING) {
            $this->recordLoginActivity($user, $identity, 'failed');
            $this->verificationService->sendOtp($user);
            return [
                'success' => false,
                'needs_otp' => true,
                'uuid' => $user->uuid,
                'message' => 'حسابك يحتاج إلى تفعيل. تم إرسال رمز التحقق.',
            ];
        }

        if ($user->status_id === UserStatus::SUSPENDED || !$user->is_approved) {
            $this->recordLoginActivity($user, $identity, 'failed');
            RateLimiter::hit('login_failures:' . request()->ip(), 300);
            return ['success' => false, 'message' => 'بيانات الدخول غير صحيحة.'];
        }

        RateLimiter::clear('login_failures:' . request()->ip());
        $this->recordLoginActivity($user, $identity, 'success');

        return ['success' => true, 'user' => $user];
    }

    public function checkUserApproval(string $uuid): array
    {
        $user = $this->userRepository->findByUuid($uuid);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'حدث خطأ. يرجى المحاولة مرة أخرى.',
            ];
        }

        if ($user->is_approved) {
            return [
                'success' => true,
                'approved' => true,
                'message' => 'حسابك مفعل بالفعل!',
            ];
        }

        return [
            'success' => true,
            'approved' => false,
            'user' => [
                'uuid' => $user->uuid,
                'name' => $user->name,
            ],
        ];
    }

    private function findUserByIdentifier(string $identifier): ?User
    {
        $trimmedIdentifier = trim($identifier);

        if (str_contains($trimmedIdentifier, '@')) {
            return $this->userRepository->findByEmail($trimmedIdentifier);
        }

        if (preg_match('/^[0-9a-fA-F-]{36}$/', $trimmedIdentifier)) {
            return $this->userRepository->findByUuid($trimmedIdentifier);
        }

        return $this->userRepository->findByPhoneNumber($trimmedIdentifier);
    }

    public function resolveRegistrationState(?string $phone, ?string $email): array
    {
        $identifier = $phone ?? $email;

        if (!$identifier) {
            return ['state' => null, 'user' => null];
        }

        $user = $this->findUserByIdentifier($identifier);

        if (!$user) {
            return ['state' => null, 'user' => null];
        }

        if ($user->is_approved && $user->status_id === UserStatus::ACTIVE) {
            return ['state' => UserStatus::ACTIVE, 'user' => $user];
        }

        return ['state' => UserStatus::PENDING, 'user' => $user];
    }

    public function verifyAccountOtp(User $user, string $code): array
    {
        $identifier = $user->phone_number ?: $user->email;
        $result = $this->unifiedOtpService->verifyOtp($identifier, $code, 'verify_account');

        if (!$result['valid']) {
            return ['success' => false, 'message' => $result['message'] ?? 'رمز التحقق غير صحيح'];
        }

        $user->is_approved = true;
        $user->save();

        return ['success' => true, 'message' => 'تم التحقق بنجاح، الآن أدخل كلمة المرور', 'user' => $user];
    }

    public function activatePassword(User $user, string $password): array
    {
        if (!$user->is_approved || !$user->isPending()) {
            return ['success' => false, 'message' => 'حالة الحساب غير صالحة لتفعيل كلمة المرور'];
        }

        if ($user->password) {
            return ['success' => false, 'message' => 'كلمة المرور تم تعيينها بالفعل'];
        }

        try {
            DB::transaction(function () use ($user, $password) {
                $user->password = Hash::make($password);
                $user->status_id = UserStatus::ACTIVE;
                $user->save();

                if ($user->email) {
                    Mail::mailer('verify')->send(new AccountActivatedMail($user));
                }
            });

            return ['success' => true, 'message' => 'تم تفعيل الحساب بنجاح', 'user' => $user];
        } catch (\Exception $e) {
            Log::error('Failed to activate password: ' . $e->getMessage());
            return ['success' => false, 'message' => 'حدث خطأ أثناء تفعيل الحساب'];
        }
    }
}
