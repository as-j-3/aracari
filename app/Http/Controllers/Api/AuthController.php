<?php

namespace App\Http\Controllers\Api;

use App\Constants\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ActivatePasswordRequest;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\PasswordResetIdentifierRequest;
use App\Http\Requests\Api\Auth\PasswordResetOtpRequest;
use App\Http\Requests\Api\Auth\PasswordResetRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Requests\Api\Auth\ResendOtpRequest;
use App\Http\Requests\Api\Auth\VerifyOtpRequest;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use App\Services\Auth\VerificationService;
use App\Services\Auth\UnifiedOtpService;
use App\Services\UserService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\AccountActivatedMail;
use App\Constants\UserAction;
class AuthController extends Controller
{
    use ApiResponse;

    protected AuthService $authService;
    protected VerificationService $verificationService;
    protected UnifiedOtpService $unifiedOtpService;
    protected UserService $userService;


    public function __construct(
        AuthService $authService,
        VerificationService $verificationService,
        UnifiedOtpService $unifiedOtpService,
        UserService $userService
    ) {
        $this->authService = $authService;
        $this->verificationService = $verificationService;
        $this->unifiedOtpService = $unifiedOtpService;
        $this->userService = $userService;
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        if ($request->has('password') || $request->has('password_confirmation')) {
            return $this->error('لا يمكن تعيين كلمة المرور في الخطوة الأولى من التسجيل. يرجى إكمال التحقق أولًا.', 422);
        }

        $data = $request->validated();

         $fullPhone = $data['phone_number'] ?? null;

        $registration  = $this->authService->resolveRegistrationState($fullPhone, $data['email'] ?? null);

        if ($registration['state'] === UserStatus::ACTIVE) {
            return $this->error('هذا الرقم مسجل بالفعل. يرجى تسجيل الدخول.', [
                'action' => UserAction::REGISTER_ALREADY_EXISTS,
            ], 409);
        }

        if ($registration['state'] === UserStatus::PENDING) {
            return $this->error('حسابك يحتاج تفعيل.', [
                'action' => UserAction::REGISTER_PENDING_ACTIVATION,
            ], 403); 
        }

        $result = $this->userService->createUser([
            'phone_number' => $fullPhone,
            'email' => $data['email'] ?? null,
            'name' => $data['name'],
            'company_name' => $data['company_name'] ?? null,
            'cr_number' => $data['cr_number'] ?? null,
            'type_of_service_id' => $data['type_of_service_id'] ?? null,
            'is_admin_creation' => false,
            'skip_password' => true,
        ]);

        return $result['success']
            ? $this->success([
                'action' => UserAction::REGISTER_OTP_SENT,
                'uuid' => $result['data']->uuid,
            ], $result['message'], 201)
            : $this->error($result['message'], 400);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();


        $result = $this->authService->login($data);

        if (!$result['success']) {
            if (!empty($result['needs_otp'])) {
                return $this->error($result['message'], [
                    'needs_otp' => true,
                    'uuid' => $result['uuid'] ?? null,
                ], $result['code'] ?? 403);
            }

            return $this->error($result['message'], null, $result['code'] ?? 401);
        }

        if (isset($result['data']['user'])) {
            $result['data']['user'] = new UserResource($result['data']['user']);
        }

        return $this->success($result['data'], $result['message']);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $user = $request->validated()['user'];
        $code = $request->validated()['code'];

        $result = $this->authService->verifyAccountOtp($user, $code);

        if (!$result['success']) {
            return $this->error($result['message'], 400);
        }

        return $this->success([
            'user' => new UserResource($result['user']->load(['status:id,status_code', 'userType'])),
            'needs_password' => true,
        ], $result['message']);
    }


    public function activatePassword(ActivatePasswordRequest $request): JsonResponse
    {
        $user = $request->validated()['user'];
        $password = $request->validated()['password'];

        $result = $this->authService->activatePassword($user, $password);

        if (!$result['success']) {
            return $this->error($result['message'], 400);
        }

        $token = $this->authService->issueAccessToken($result['user'], $request->device_id ?? null);

        return $this->success([
            'user' => new UserResource($result['user']->load(['status:id,status_code', 'userType'])),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'device_id' => $request->device_id,
        ], $result['message']);
    }


    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        $user = $request->validated()['user'];
        $purpose = $request->route()->defaults['purpose'] ?? $request->input('purpose') ?? 'verify_account';

        $channel = $request->phone_number ? 'phone' : null;
        $result = $this->verificationService->resendOtp($user->uuid, $purpose,$channel);

        return $result['success']
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], 400);
    }


    public function forgotPassword(PasswordResetIdentifierRequest $request): JsonResponse
    {
        $identifier = $request->resolvedIdentifier();
        $user = $request->resolvedUser();

        $message = 'إذا كان الحساب مسجلًا لدينا، فستتلقى كود التحقق قريبًا';

        if ($user) {
            $this->authService->requestPasswordResetForUser($user, $identifier);
        }

        return $this->success([
            'message' => $message,
        ], $message);
    }


    public function verifyPasswordResetOtp(PasswordResetOtpRequest $request): JsonResponse
    {
        $identifier = $request->resolvedIdentifier();
        $code = $request->validated()['code'];

        $result = $this->authService->verifyPasswordResetOtp($identifier, $code);

        return $result['success']
            ? $this->success([
                'reset_token' => $result['reset_token'],
                'message' => 'يمكنك الآن إدخال كلمة المرور الجديدة',
            ], 'تم التحقق من الكود بنجاح')
            : $this->error($result['message'], 400);
    }


    public function resetPassword(PasswordResetRequest $request): JsonResponse
    {
        $identifier = $request->resolvedIdentifier();
        $validated = $request->validated();

        $result = $this->authService->resetPasswordWithToken(
            $identifier,
            $validated['reset_token'],
            $validated['password']
        );

        return $result['success']
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], $result['code'] ?? 400);
    }


    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'old_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
        ]);

        $result = $this->authService->changePassword($request->user(), $request->old_password, $request->password);

        return $result['success']
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], 400);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout();

        return $this->success(null, 'تم تسجيل الخروج بنجاح');
    }
}
