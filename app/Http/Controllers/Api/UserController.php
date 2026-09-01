<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Constants\UserAction;
use App\Http\Requests\Api\User\CreateUserRequest;
use App\Http\Requests\Api\User\UpdateUserRequest;
use App\Http\Requests\Api\User\SearchUserRequest;
use App\Http\Requests\Api\User\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\ProfileResource;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use App\Traits\ApiResponse;
use App\Services\CurrencyService;
use App\Services\Wallet\WalletService;
class UserController extends Controller
{
    use ApiResponse;

    protected UserService $userService;
    protected CurrencyService $currencyService;
    protected WalletService $walletService;

    public function __construct(UserService $userService, CurrencyService $currencyService, WalletService $walletService)
    {
        $this->userService = $userService;
        $this->currencyService = $currencyService;
        $this->walletService = $walletService; 
    }


    public function index(Request $request): JsonResponse
    {
        if (!auth()->user()?->isAdmin()) {
            return $this->error('غير مصرح لك بعرض قائمة المستخدمين', 403);
        }
        $filters = $request->only(['search', 'type_code', 'status_id', 'is_approved']);
        $perPage = $request->get('per_page', 15);
        $users = $this->userService->getUsers($filters, $perPage);

        return $this->paginated($users, UserResource::class, 'تم جلب المستخدمين بنجاح');
    }

 
    public function show(Request $request, string $uuid): JsonResponse
    {
        $user = $this->userService->getUserByUuid($uuid);

        if (!$user) {
            return $this->notFound('المستخدم غير موجود');
        }

        $isAdmin = $request->user()->isAdmin();

        if (!$isAdmin && $user->id !== $request->user()->id) {
            return $this->error('غير مصرح لك بعرض بيانات هذا المستخدم', 403);
        }

        return $this->success(new UserResource($user), 'تم استرجاع بيانات المستخدم بنجاح');
    }


    public function store(CreateUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['is_admin_creation'] = ($request->user() && $request->user()->hasPermission('users:create'));

        $result = $this->userService->createUser($data);

        if (!$result['success']) {
            return $this->error($result['message'], 400);
        }

        return $this->success(new UserResource($result['data']), $result['message'], 201);
    }


    public function updateByUuid(UpdateUserRequest $request, string $uuid): JsonResponse
    {
        $user = $this->userService->getUserByUuid($uuid);
        if (!$user) {
            return $this->notFound('المستخدم غير موجود');
        }

        $data = $request->validated();
        $is_admin = $request->user()->isAdmin() || $request->user()->hasPermission('users:edit');
        $data['is_admin_action'] = $is_admin;
        
        // If not admin and trying to update someone else, block
        if (!$is_admin && $user->id !== $request->user()->id) {
            return $this->error('غير مصرح لك بتعديل بيانات هذا المستخدم', 403);
        }

        $result = $this->userService->updateUser($user->id, $data, $request->user()->id);

        if (!$result['success']) {
            return $this->error($result['message'], 400);
        }

        return $this->success(new UserResource($result['data']), $result['message']);
    }


    public function destroyByUuid(Request $request, string $uuid): JsonResponse
    {
        $user = $this->userService->getUserByUuid($uuid);
        if (!$user) {
            return $this->notFound('المستخدم غير موجود');
        }

        $is_admin = $request->user()->isAdmin() || $request->user()->hasPermission('users:delete');
        if (!$is_admin && $user->id !== $request->user()->id) {
            return $this->error('غير مصرح لك بحذف هذا المستخدم', 403);
        }

        $result = $this->userService->deleteUser($user->id, $request->user()->id);

        return $result['success']
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], 400);
    }


    public function approveByUuid(Request $request, string $uuid): JsonResponse
    {
        if (!auth()->user()?->isAdmin()) {
            return $this->error('غير مصرح لك بالموافقة على المستخدمين', 403);
        }
        $user = $this->userService->getUserByUuid($uuid);
        if (!$user) {
            return $this->notFound('المستخدم غير موجود');
        }

        $result = $this->userService->approveUser($user->id, $request->user()->id);

        return $result['success']
            ? $this->success(new UserResource($result['data']), $result['message'])
            : $this->error($result['message'], 400);
    }


    public function statistics(): JsonResponse
    {
        if (!auth()->user()?->isAdmin()) {
            return $this->error('غير مصرح لك بعرض إحصائيات المستخدمين', 403);
        }
        $statistics = $this->userService->getUserStatistics();
        return $this->success($statistics, 'تم جلب الإحصائيات بنجاح');
    }


    public function wallet(Request $request): JsonResponse
    {
        $user = $request->user();
        $currencyService = app(\App\Services\CurrencyService::class);
        $baseCurrencyId = $currencyService->baseCurrencyId();
        
        $walletService = app(\App\Services\Wallet\WalletService::class);
        $walletAccount = $walletService->getOrCreateAccount($user->id, $baseCurrencyId);
        
        return $this->success([
            'balance' => (float) $walletAccount->balance,
            'currency' => $walletAccount->currency->code ?? 'SAR',
            'is_enabled' => $walletService->isWalletEnabled(),
            'is_payment_enabled' => $walletService->isWalletPaymentEnabled(),
            'last_updated' => $walletAccount->updated_at?->toISOString()
        ], 'تم جلب رصيد المحفظة');
    }


    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();
        $currencyService = $this->currencyService;
        $baseCurrencyId = $currencyService->baseCurrencyId();
        $walletService = $this->walletService;
        $walletAccount = $walletService->getOrCreateAccount($user->id, $baseCurrencyId);

        $transactions = \App\Models\WalletTransaction::where('wallet_account_id', $walletAccount->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->paginated($transactions, \App\Http\Resources\TransactionResource::class, 'تم جلب حركات المحفظة');
    }


    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        if (!Hash::check($data['password'], $user->password)) {
            return $this->error(
                'كلمة المرور غير صحيحة.',
                ['action' => UserAction::PROFILE_WRONG_PASSWORD],
                403
            );
        }

        if ($data['phone_number'] !== $user->phone_number) {
            return $this->error(
                'رقم الهاتف غير مطابق لرقمك المسجل.',
                ['action' => UserAction::PROFILE_WRONG_PHONE],
                403
            );
        }

        $updateData = ['name' => $data['name']];

        $result = $this->userService->updateProfile($user->id, $updateData);

        if (!$result['success']) {
            return $this->error($result['message'], 400);
        }

        $freshUser = $this->userService->getUserById($user->id);

        return $this->success(
            new ProfileResource($freshUser->load(['status:id,status_code', 'userType'])),
            $result['message'],
            200
        );
    }

    public function showMe(Request $request): JsonResponse
    {
        return $this->success(
            new ProfileResource($request->user()->load(['status:id,status_code', 'userType'])),
            'تم جلب الملف الشخصي'
        );
    }
}
