<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\User\CreateUserRequest;
use App\Http\Requests\Web\User\UpdateUserRequest;
use App\Services\UserService;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Models\TypeOfUser;
use App\Models\UserStatus;
use App\Models\Role;
use App\Models\TypeOfService;
use App\Constants\CountryCodes;

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


    public function index(Request $request): View
    {
        if (!auth()->user()?->isAdmin()) {
        abort(403);
     }
        $perPage = $request->get('per_page', 15);
        $users = $this->userService->getAllUsers($perPage);
        
        return view('admin.users.index', compact('users'));
    }


    public function create(): View
    {
        $statuses = cache()->remember('user-statuses-list', 3600, fn() => UserStatus::all());
        $types = cache()->remember('type-of-users-list', 3600, fn() => TypeOfUser::all());
        $roles = cache()->remember('roles-list', 3600, fn() => Role::orderBy('name')->get());
        $serviceTypes = cache()->remember('type-of-services-list', 3600, fn() => TypeOfService::orderBy('type_name')->get());
        $countries = CountryCodes::all();
        return view('admin.users.create', compact('statuses', 'types', 'roles', 'serviceTypes','countries'));
    }


    public function store(CreateUserRequest $request): RedirectResponse|JsonResponse
    {
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
    }


    public function show(string $uuid): View
    {
        $user = $this->userService->getUserByUuid($uuid);
        
        if (!$user) {
            abort(404, 'User not found');
        }

        $currentUser = auth()->user();
        if (!$currentUser->isAdmin() && $currentUser->id !== $user->id) {
            abort(403, 'غير مصرح لك بعرض بيانات هذا المستخدم');
        }
        
        return view('admin.users.show', compact('user'));
    }


    public function edit(string $uuid): View
    {
        if (!auth()->user()?->isAdmin()) {
        abort(403);
        }
        $user = $this->userService->getUserByUuid($uuid);
        
        if (!$user) {
            abort(404, 'User not found');
        }

        $user->loadMissing('roles');
        
        $statuses = cache()->remember('user-statuses-list', 3600, fn() => UserStatus::all());
        $types = cache()->remember('type-of-users-list', 3600, fn() => TypeOfUser::all());
        $roles = cache()->remember('roles-list', 3600, fn() => Role::orderBy('name')->get());
        $serviceTypes = cache()->remember('type-of-services-list', 3600, fn() => TypeOfService::orderBy('type_name')->get());
        $countries = CountryCodes::all();
        return view('admin.users.edit', compact('user', 'statuses', 'types', 'roles', 'serviceTypes','countries'));
    }


    public function update(UpdateUserRequest $request, string $uuid): RedirectResponse|JsonResponse
    {
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
    }


    public function destroy(Request $request, string $uuid): RedirectResponse|JsonResponse
    {
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
    }


    public function restore(Request $request, string $uuid): RedirectResponse|JsonResponse
    {
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
    }


    public function approve(Request $request, string $uuid): RedirectResponse|JsonResponse
    {
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
    }


    public function search(Request $request): View
    {
        $search = $request->get('search');
        $perPage = $request->get('per_page', 15);
        $users = $this->userService->searchUsers($search, $perPage);
        
        return view('admin.users.search', compact('users', 'search'));
    }


    public function statistics(): JsonResponse
    {
        if (!auth()->user()?->isAdmin()) {
        abort(403);
        }
        $statistics = $this->userService->getUserStatistics();
        
        return response()->json([
            'success' => true,
            'data' => $statistics,
            'message' => 'User statistics retrieved successfully'
        ]);
    }


    public function verifyPhone(Request $request, string $uuid): RedirectResponse|JsonResponse
    {
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
    }
}
