<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ProviderAuthLoginRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProviderAuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AuthService $authService
    ) {}


public function register(\App\Http\Requests\Api\Auth\DirectRegisterRequest $request): JsonResponse
{
    try {
        $data = $request->validated();
        $data['type_user_code'] = $data['type_user_code'] ?? \App\Constants\UserTypes::PROVIDER;

        $result = $this->authService->directRegisterUser($data);

        if (!$result['success']) {
            return $this->error(
                $result['message'],
                null,
                $result['code'] ?? 400
            );
        }

        $user = $result['data']['user'] ?? null;

        return $this->success(
            [
                'user_id' => $user?->id,
                'name' => $user?->name,
                'phone_number' => $user?->phone_number,
                'is_approved' => $user?->is_approved,
                'status' => $user?->status?->status_code,
                'user_type' => $user?->userType?->type_name,
            ],
            $result['message'],
            201
        );

    } catch (\Exception $e) {
        Log::error('Provider direct register failed: ' . $e->getMessage());

        return $this->error(
            'حدث خطأ أثناء إنشاء حساب المزود. يرجى المحاولة مرة أخرى.',
            null,
            500
        );
    }
}

    public function login(ProviderAuthLoginRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            if (!empty($data['country_code']) && !empty($data['phone_number'])) {
                $data['phone_number'] = '+' . $data['country_code'] . ltrim($data['phone_number'], '0');
                unset($data['country_code']);
            }

            $result = $this->authService->attemptProviderAppLogin($data);

            if (!$result['success']) {
                return $this->error($result['message'], $result['code'] ?? 401);
            }

            if (isset($result['data']['user'])) {
                $result['data']['user'] = new UserResource($result['data']['user']);
            }

            return $this->success($result['data'], $result['message']);
            
        } catch (\Exception $e) {
            Log::error('Provider App Login failed: ' . $e->getMessage());
            return $this->error('حدث خطأ أثناء تسجيل الدخول. يرجى المحاولة مرة أخرى.', 500);
        }
    }


    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout();
        return $this->success(null, 'تم تسجيل الخروج بنجاح');
    }


    public function getProfile(Request $request): JsonResponse
    {
        $user = $request->user()->load(['status:id,status_code', 'userType']);
        return $this->success(['user' => new UserResource($user)], 'تم جلب البيانات بنجاح');
    }
}