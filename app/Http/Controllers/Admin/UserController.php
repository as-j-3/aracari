<?php

namespace App\Http\Controllers\Admin;

use App\Constants\CountryCodes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\User\CreateUserRequest;
use App\Http\Requests\Web\User\UpdateUserRequest;
use App\Http\Resources\Admin\UserResource;
use App\Models\Passenger;
use App\Models\Role;
use App\Models\TypeOfService;
use App\Models\TypeOfUser;
use App\Models\User;
use App\Models\UserStatus;
use App\Services\AuditService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    protected UserService $userService;

    protected AuditService $auditService;

    public function __construct(UserService $userService, AuditService $auditService)
    {
        $this->userService = $userService;
        $this->auditService = $auditService;
        $this->middleware('require.admin');
    }

    public function index(Request $request): JsonResponse
    {
        /*
        // الكود السابق (Web View):
        if (!auth()->user()?->isAdmin()) {
        abort(403);
        }
        $perPage = $request->get('per_page', 15);
        $users = $this->userService->getAllUsers($perPage);

        return view('admin.users.index', compact('users'));
        */

        // كود الـ API:
        if (! auth()->user()?->isAdmin()) {
            return $this->error('غير مصرح لك', null, 403);
        }
        $perPage = $request->get('per_page', 15);
        $users = $this->userService->getAllUsers($perPage);

        $statuses = cache()->remember('user-statuses-list', 3600, fn () => UserStatus::all(['id', 'status_name', 'status_code']));
        $types = cache()->remember('type-of-users-list', 3600, fn () => TypeOfUser::all(['id', 'type_name', 'type_user_code']));
        $roles = cache()->remember('roles-list', 3600, fn () => Role::orderBy('name')->get(['id', 'name']));

        return $this->success([
            'users' => UserResource::collection($users)->response()->getData(true),
            'statuses' => $statuses,
            'type_of_users' => $types,
            'roles' => $roles,
        ], 'تم جلب بيانات المستخدمين بنجاح');
    }

    /*
    * دالة create خاصة بعرض صفحة الـ HTML (تم تركها كما هي دون تحويل)
    */
    public function create(): View
    {
        $statuses = cache()->remember('user-statuses-list', 3600, fn () => UserStatus::all());
        $types = cache()->remember('type-of-users-list', 3600, fn () => TypeOfUser::all());
        $roles = cache()->remember('roles-list', 3600, fn () => Role::orderBy('name')->get());
        $serviceTypes = cache()->remember('type-of-services-list', 3600, fn () => TypeOfService::orderBy('type_name')->get());
        $countries = CountryCodes::all();

        return view('admin.users.create', compact('statuses', 'types', 'roles', 'serviceTypes', 'countries'));
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        /*
        // الكود السابق (Web Redirect):
        $data = $request->validated();

        $isAdmin = auth()->user()->isAdmin();
        $data['is_admin_creation'] = $isAdmin;

        if (!$isAdmin) {
            $data['is_approved'] = false;
        }

        $result = $this->userService->createUser($data);

        if ($request->wantsJson()) {
            if ($result['success']) {
                return response()->json(['success' => true, 'message' => $result['message']]);
            }
            return response()->json(['success' => false, 'message' => $result['message']], 422);
        }

        if ($result['success']) {
            if (!empty($result['is_provider'])) {
                return redirect()
                    ->route('admin.provider-services.create')
                    ->with('success', $result['message']);
            }

            return redirect()
                ->route('admin.users.index')
                ->with('success', $result['message']);
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', $result['message']);
        */

        // كود الـ API:
        $data = $request->validated();

        $isAdmin = auth()->user()->isAdmin();
        $data['is_admin_creation'] = $isAdmin;

        if (! $isAdmin) {
            $data['is_approved'] = false;
        }

        $result = $this->userService->createUser($data);

        if ($result['success']) {
            $user = $result['data'] ?? null;

            $responseData = $user
                ? new UserResource(
                    $user->load(['status', 'userType', 'roles'])
                )
                : null;

            return $this->success(
                $responseData,
                $result['message'],
                201
            );
        }

        return $this->error(
            $result['message'],
            null,
            422
        );
    }

    public function show(string $uuid): JsonResponse
    {
        /*
        // الكود السابق (Web View):
        $user = $this->userService->getUserByUuid($uuid);

        if (!$user) {
            abort(404, 'User not found');
        }

        $currentUser = auth()->user();
        if (!$currentUser->isAdmin() && $currentUser->id !== $user->id) {
            abort(403, 'غير مصرح لك بعرض بيانات هذا المستخدم');
        }

        return view('admin.users.show', compact('user'));
        */

        // كود الـ API:
        $user = $this->userService->getUserByUuid($uuid);

        if (! $user) {
            return $this->error('المستخدم غير موجود', null, 404);
        }

        $currentUser = auth()->user();
        if (! $currentUser->isAdmin() && $currentUser->id !== $user->id) {
            return $this->error('غير مصرح لك بعرض بيانات هذا المستخدم', null, 403);
        }

        $user->load(['status', 'userType', 'roles', 'parent', 'providerService']);

        return $this->success(new UserResource($user), 'تم جلب بيانات المستخدم بنجاح');
    }

    /*
    * دالة edit خاصة بعرض صفحة الـ HTML (تم تركها كما هي دون تحويل)
    */
    public function edit(string $uuid): View
    {
        if (! auth()->user()?->isAdmin()) {
            abort(403);
        }
        $user = $this->userService->getUserByUuid($uuid);

        if (! $user) {
            abort(404, 'User not found');
        }

        $user->loadMissing('roles');

        $statuses = cache()->remember('user-statuses-list', 3600, fn () => UserStatus::all());
        $types = cache()->remember('type-of-users-list', 3600, fn () => TypeOfUser::all());
        $roles = cache()->remember('roles-list', 3600, fn () => Role::orderBy('name')->get());
        $serviceTypes = cache()->remember('type-of-services-list', 3600, fn () => TypeOfService::orderBy('type_name')->get());
        $countries = CountryCodes::all();

        return view('admin.users.edit', compact('user', 'statuses', 'types', 'roles', 'serviceTypes', 'countries'));
    }

    public function update(UpdateUserRequest $request, string $uuid): JsonResponse
    {
        /*
        // الكود السابق (Web Redirect):
        $user = $this->userService->getUserByUuid($uuid);

        if (!$user) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'المستخدم غير موجود'], 404);
            }
            abort(404, 'User not found');
        }

        $data = $request->validated();
        $data['is_admin_action'] = (auth()->check() && auth()->user()->hasPermission('users:edit'));

        $result = $this->userService->updateUser($user->id, $data, auth()->id());

        if ($request->wantsJson()) {
            if ($result['success']) {
                return response()->json(['success' => true, 'message' => $result['message']]);
            }
            return response()->json(['success' => false, 'message' => $result['message']], 422);
        }

        if ($result['success']) {
            return redirect()
                ->route('admin.users.index')
                ->with('success', $result['message']);
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', $result['message']);
        */

        // كود الـ API:
        $user = $this->userService->getUserByUuid($uuid);

        if (! $user) {
            return $this->error('المستخدم غير موجود', null, 404);
        }

        $data = $request->validated();
        $data['is_admin_action'] = (auth()->check() && (auth()->user()->isAdmin() || auth()->user()->hasPermission('users:edit')));

        $result = $this->userService->updateUser($user->id, $data, auth()->id());

        if ($result['success']) {
            $freshUser = $this->userService->getUserByUuid($uuid);
            $freshUser?->load(['status', 'userType', 'roles']);

            return $this->success(
                $freshUser ? new UserResource($freshUser) : null,
                $result['message']
            );
        }

        return $this->error($result['message'], null, 422);
    }

    public function destroy(Request $request, string $uuid): JsonResponse
    {
        /*
        // الكود السابق (Web Redirect):
        $user = $this->userService->getUserByUuid($uuid);

        if (!$user) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'المستخدم غير موجود'], 404);
            }
            abort(404, 'User not found');
        }

        $result = $this->userService->deleteUser($user->id, auth()->id());

        if ($request->wantsJson()) {
            if ($result['success']) {
                return response()->json(['success' => true, 'message' => $result['message']]);
            }
            return response()->json(['success' => false, 'message' => $result['message']], 422);
        }

        if ($result['success']) {
            return redirect()
                ->route('admin.users.index')
                ->with('success', $result['message']);
        }

        return redirect()
            ->back()
            ->with('error', $result['message']);
        */

        // كود الـ API:
        $user = $this->userService->getUserByUuid($uuid);

        if (! $user) {
            return $this->error('المستخدم غير موجود', null, 404);
        }

        $result = $this->userService->deleteUser($user->id, auth()->id());

        if ($result['success']) {
            return $this->success(null, $result['message']);
        }

        return $this->error($result['message'], null, 422);
    }

    public function restore(Request $request, string $uuid): JsonResponse
    {
        /*
        // الكود السابق (Web Redirect):
        $user = \App\Models\User::withTrashed()->where('uuid', $uuid)->first();

        if (!$user) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'المستخدم غير موجود'], 404);
            }
            abort(404, 'User not found');
        }

        $result = $this->userService->restoreUser($user->id, auth()->id());

        if ($request->wantsJson()) {
            if ($result['success']) {
                return response()->json(['success' => true, 'message' => $result['message']]);
            }
            return response()->json(['success' => false, 'message' => $result['message']], 422);
        }

        if ($result['success']) {
            return redirect()
                ->route('admin.users.index')
                ->with('success', $result['message']);
        }

        return redirect()
            ->back()
            ->with('error', $result['message']);
        */

        // كود الـ API:
        $user = User::withTrashed()->where('uuid', $uuid)->first();

        if (! $user) {
            return $this->error('المستخدم غير موجود', null, 404);
        }

        $result = $this->userService->restoreUser($user->id, auth()->id());

        if ($result['success']) {
            $freshUser = $this->userService->getUserByUuid($uuid);

            return $this->success(
                $freshUser ? new UserResource($freshUser->load(['status', 'userType', 'roles'])) : null,
                $result['message']
            );
        }

        return $this->error($result['message'], null, 422);
    }

    public function approve(Request $request, string $uuid): JsonResponse
    {
        /*
        // الكود السابق (Web Redirect):
        $user = $this->userService->getUserByUuid($uuid);

        if (!$user) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'المستخدم غير موجود'], 404);
            }
            abort(404, 'User not found');
        }

        $result = $this->userService->approveUser($user->id, auth()->id());

        if ($request->wantsJson()) {
            if ($result['success']) {
                return response()->json(['success' => true, 'message' => $result['message']]);
            }
            return response()->json(['success' => false, 'message' => $result['message']], 422);
        }

        if ($result['success']) {
            return redirect()
                ->back()
                ->with('success', $result['message']);
        }

        return redirect()
            ->back()
            ->with('error', $result['message']);
        */

        // كود الـ API:
        $user = $this->userService->getUserByUuid($uuid);

        if (! $user) {
            return $this->error('المستخدم غير موجود', null, 404);
        }

        $result = $this->userService->approveUser($user->id, auth()->id());

        if ($result['success']) {
            $freshUser = $this->userService->getUserByUuid($uuid);

            return $this->success(
                $freshUser ? new UserResource($freshUser->load(['status', 'userType', 'roles'])) : null,
                $result['message']
            );
        }

        return $this->error($result['message'], null, 422);
    }

    public function search(Request $request): JsonResponse
    {
        /*
        // الكود السابق (Web View):
        $search = $request->get('search');
        $perPage = $request->get('per_page', 15);
        $users = $this->userService->searchUsers($search, $perPage);

        return view('admin.users.search', compact('users', 'search'));
        */

        // كود الـ API:
        $search = (string) $request->get('search', '');
        $perPage = (int) $request->get('per_page', 15);
        $users = $this->userService->searchUsers($search, $perPage);

        return $this->success(UserResource::collection($users), 'تم البحث بنجاح');
    }

    public function statistics(): JsonResponse
    {
        /*
        // الكود السابق:
        if (!auth()->user()?->isAdmin()) {
        abort(403);
        }
        $statistics = $this->userService->getUserStatistics();

        return response()->json([
            'success' => true,
            'data' => $statistics,
            'message' => 'User statistics retrieved successfully'
        ]);
        */

        // كود الـ API:
        if (! auth()->user()?->isAdmin()) {
            return $this->error('غير مصرح لك', null, 403);
        }
        $statistics = $this->userService->getUserStatistics();

        return $this->success($statistics, 'تم جلب إحصائيات المستخدمين بنجاح');
    }

    public function verifyPhone(Request $request, string $uuid): JsonResponse
    {
        /*
        // الكود السابق (Web Redirect):
        $user = $this->userService->getUserByUuid($uuid);
        if (!$user) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'المستخدم غير موجود'], 404);
            }
            abort(404);
        }

        try {
            DB::beginTransaction();
            $oldData = $user->toArray();
            $user->update(['phone_verified_at' => now()]);
            $userId = $user->id;
            DB::afterCommit(function () use ($userId, $oldData) {
                $this->auditService->log(
                    auth()->id(),
                    'verify_phone',
                    'users',
                    $userId,
                    $oldData,
                    ['phone_verified_at' => now()->toDateTimeString()]
                );
            });
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'تعذر إتمام تفعيل رقم الهاتف. يرجى المحاولة لاحقاً.'], 422);
            }
            return back()->with('error', 'تعذر إتمام تفعيل رقم الهاتف. يرجى المحاولة لاحقاً.');
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم تفعيل رقم الهاتف بنجاح']);
        }

        return back()->with('success', 'تم تفعيل رقم الهاتف بنجاح');
        */

        // كود الـ API:
        $user = $this->userService->getUserByUuid($uuid);
        if (! $user) {
            return $this->error('المستخدم غير موجود', null, 404);
        }

        try {
            DB::beginTransaction();
            $oldData = $user->toArray();
            $user->update(['phone_verified_at' => now()]);
            $userId = $user->id;
            DB::afterCommit(function () use ($userId, $oldData) {
                $this->auditService->log(
                    auth()->id(),
                    'verify_phone',
                    'users',
                    $userId,
                    $oldData,
                    ['phone_verified_at' => now()->toDateTimeString()]
                );
            });
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('تعذر إتمام تفعيل رقم الهاتف. يرجى المحاولة لاحقاً.', null, 422);
        }

        $user->load(['status', 'userType', 'roles']);

        return $this->success(new UserResource($user->fresh()), 'تم تفعيل رقم الهاتف بنجاح');
    }

   
}
