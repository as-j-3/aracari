<?php

namespace App\Http\Controllers\Admin;

use App\Constants\UserRoles;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Role\AssignPermissionsRequest;
use App\Http\Requests\Web\Role\StoreRoleRequest;
use App\Http\Requests\Web\Role\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
        $this->middleware('require.admin');
    }

    public function index(): JsonResponse
    {
        $roles = Role::withCount('users')
            ->orderBy('name')
            ->paginate(20);

        return $this->success(
            $roles,
            'تم جلب الأدوار بنجاح'
        );
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $role = Role::create($request->validated());

            $this->auditService->log(
                auth()->id(),
                'create',
                'roles',
                $role->id,
                null,
                $role->toArray()
            );

            DB::commit();

            return $this->success(
                $role,
                'تم إنشاء الدور بنجاح',
                201
            );

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Role creation API failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'حدث خطأ أثناء إنشاء الدور. يرجى المحاولة مرة أخرى.',
                null,
                500
            );
        }
    }

    public function update(
        UpdateRoleRequest $request,
        Role $role
    ): JsonResponse {
        $validated = $request->validated();

        if (
            in_array($role->name, UserRoles::systemRoles(), true)
            && $validated['name'] !== $role->name
        ) {
            return $this->error(
                'لا يمكن تغيير اسم دور النظام',
                null,
                422
            );
        }

        DB::beginTransaction();

        try {
            $oldData = $role->toArray();

            $role->update($validated);

            $newData = $role->fresh()->toArray();

            $changes = array_diff_assoc($newData, $oldData);

            if (! empty($changes)) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'roles',
                    $role->id,
                    array_intersect_key($oldData, $changes),
                    $changes
                );
            }

            DB::commit();

            return $this->success(
                $role->fresh(),
                'تم تحديث الدور بنجاح'
            );

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Role update API failed', [
                'error' => $e->getMessage(),
                'role_id' => $role->id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'حدث خطأ أثناء تحديث الدور. يرجى المحاولة مرة أخرى.',
                null,
                500
            );
        }
    }

    public function destroy(Role $role): JsonResponse
    {
        if (in_array($role->name, UserRoles::systemRoles(), true)) {
            return $this->error(
                'لا يمكن حذف دور النظام',
                null,
                422
            );
        }

        if ($role->users()->exists()) {
            return $this->error(
                'لا يمكن حذف دور مرتبط بمستخدمين',
                null,
                422
            );
        }

        DB::beginTransaction();

        try {
            $oldData = $role->toArray();

            $role->delete();

            $this->auditService->log(
                auth()->id(),
                'delete',
                'roles',
                $role->id,
                $oldData,
                null
            );

            DB::commit();

            return $this->success(
                null,
                'تم حذف الدور بنجاح'
            );

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Role deletion API failed', [
                'error' => $e->getMessage(),
                'role_id' => $role->id,
                'user_id' => auth()->id(),
            ]);

            return $this->error(
                'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.',
                null,
                500
            );
        }
    }

    public function permissions(Role $role): JsonResponse
    {
        $permissions = Permission::orderBy('module')
            ->orderBy('name')
            ->get();

        $permissionGroups = $permissions
            ->groupBy(fn (Permission $permission) => $permission->module_label)
            ->map(function ($permissions, $module) {
                return [
                    'module' => $module,
                    'permissions' => $permissions->map(function (Permission $permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'description' => $permission->description,
                        ];
                    })->values(),
                ];
            })->values();

        $rolePermissions = $role->permissions
            ->pluck('id')
            ->values();

        return $this->success(
            [
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                ],
                'permissions' => $permissionGroups,
                'assigned_permission_ids' => $rolePermissions,
            ],
            'تم جلب صلاحيات الدور بنجاح'
        );
    }

    public function assignPermissions(
        AssignPermissionsRequest $request,
        Role $role
    ): JsonResponse {
        $validated = $request->validated();
        $user = auth()->user();

        if (
            in_array($role->name, UserRoles::systemRoles(), true)
            && ! $user->hasRole(UserRoles::ADMIN)
        ) {
            return $this->error(
                'لا يمكنك تعديل صلاحيات دور النظام. فقط المدير العام.',
                null,
                403
            );
        }

        if (! $user->hasRole(UserRoles::ADMIN)) {
            $userPermissions = $user
                ->getAllPermissions()
                ->pluck('id')
                ->toArray();

            $invalidPermissions = array_diff(
                $validated['permissions'],
                $userPermissions
            );

            if (! empty($invalidPermissions)) {
                Log::warning('Privilege escalation attempt blocked', [
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'attempted_permissions' => $invalidPermissions,
                ]);

                return $this->error(
                    'لا يمكنك تعيين صلاحيات لا تملكها',
                    null,
                    403
                );
            }
        }

        DB::beginTransaction();

        try {
            $oldPermissions = $role->permissions
                ->pluck('id')
                ->toArray();

            $role->permissions()->sync(
                $validated['permissions']
            );

            $this->auditService->log(
                auth()->id(),
                'assign_permissions',
                'roles',
                $role->id,
                ['permissions' => $oldPermissions],
                ['permissions' => $validated['permissions']]
            );

            DB::commit();

            DB::afterCommit(function () use ($role) {
                $role->users()->chunkById(200, function ($users) {
                    foreach ($users as $user) {
                        $user->clearPermissionCache();
                    }
                });
            });

            return $this->success(
                [
                    'role_id' => $role->id,
                    'permission_ids' => $validated['permissions'],
                ],
                'تم تحديث صلاحيات الدور بنجاح'
            );

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Assign permissions API failed', [
                'error' => $e->getMessage(),
                'role_id' => $role->id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'حدث خطأ أثناء تحديث الصلاحيات. يرجى المحاولة مرة أخرى.',
                null,
                500
            );
        }
    }
    /*

        public function index(): View
        {
            $roles = Role::withCount('users')->orderBy('name')->paginate(20);
            return view('admin.roles.index', compact('roles'));
        }


        public function create(): View
        {
            return view('admin.roles.create');
        }


        public function store(StoreRoleRequest $request): RedirectResponse
        {
            DB::beginTransaction();
            try {
                $role = Role::create($request->validated());

                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'roles',
                    $role->id,
                    null,
                    $role->toArray()
                );

                DB::commit();

                return redirect()->route('admin.roles.index')
                    ->with('success', 'تم إنشاء الدور بنجاح');
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Role creation failed: ' . $e->getMessage());
                return back()->withInput()->with('error', 'حدث خطأ أثناء إنشاء الدور. يرجى المحاولة مرة أخرى.');
            }
        }


        public function edit(Role $role): View
        {
            $role->loadCount('users');
            return view('admin.roles.edit', compact('role'));
        }


        public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
        {
            if (in_array($role->name, UserRoles::systemRoles()) && $request->validated()['name'] !== $role->name) {
                return redirect()->back()
                    ->with('error', 'لا يمكن تغيير اسم دور النظام');
            }

            DB::beginTransaction();
            try {
                $oldData = $role->toArray();
                $role->update($request->validated());
                $newData = $role->fresh()->toArray();

                $changes = array_diff_assoc($newData, $oldData);
                if (!empty($changes)) {
                    $this->auditService->log(
                        auth()->id(),
                        'update',
                        'roles',
                        $role->id,
                        array_intersect_key($oldData, $changes),
                        $changes
                    );
                }

                DB::commit();

                return redirect()->route('admin.roles.index')
                    ->with('success', 'تم تحديث الدور بنجاح');
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Role update failed: ' . $e->getMessage());
                return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث الدور. يرجى المحاولة مرة أخرى.');
            }
        }


        public function destroy(Role $role): RedirectResponse
        {
            if (in_array($role->name, UserRoles::systemRoles())) {
                return redirect()->back()
                    ->with('error', 'لا يمكن حذف دور النظام');
            }

            if ($role->users()->exists()) {
                return redirect()->back()
                    ->with('error', 'لا يمكن حذف دور مرتبط بمستخدمين');
            }

            DB::beginTransaction();
            try {
                $oldData = $role->toArray();

                $role->delete(); // Delete FIRST

                $this->auditService->log(
                    auth()->id(),
                    'delete',
                    'roles',
                    $role->id,
                    $oldData,
                    null
                );

                DB::commit();

                return redirect()->route('admin.roles.index')
                    ->with('success', 'تم حذف الدور بنجاح');
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Role deletion failed: ' . $e->getMessage());
                return back()->with('error', 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.');
            }
        }


        public function permissions(Role $role): View
        {
            $permissions = Permission::orderBy('module')->orderBy('name')->get();

            $permissionGroups = $permissions->groupBy(fn (Permission $permission) => $permission->module_label);
            $rolePermissions = $role->permissions->pluck('id')->toArray();

            return view('admin.roles.permissions', compact('role', 'permissions', 'permissionGroups', 'rolePermissions'));
        }


        public function assignPermissions(AssignPermissionsRequest $request, Role $role): RedirectResponse
        {
            $validated = $request->validated();
            $user = auth()->user();

            if (in_array($role->name, UserRoles::systemRoles()) && !$user->hasRole(UserRoles::ADMIN)) {
                return redirect()->back()
                    ->with('error', 'لا يمكنك تعديل صلاحيات دور النظام. فقط المدير العام.');
            }

            if (!$user->hasRole(UserRoles::ADMIN)) {
                $userPermissions = $user->getAllPermissions()->pluck('id')->toArray();
                $invalidPermissions = array_diff($validated['permissions'], $userPermissions);
                if (!empty($invalidPermissions)) {
                    Log::warning('Privilege escalation attempt blocked', [
                        'user_id' => $user->id,
                        'role_id' => $role->id,
                        'attempted_permissions' => $invalidPermissions,
                    ]);
                    return redirect()->back()
                        ->with('error', 'لا يمكنك تعيين صلاحيات لا تملكها');
                }
            }

            DB::beginTransaction();
            try {
                $oldPermissions = $role->permissions->pluck('id')->toArray();
                $role->permissions()->sync($validated['permissions']);

                $this->auditService->log(
                    auth()->id(),
                    'assign_permissions',
                    'roles',
                    $role->id,
                    ['permissions' => $oldPermissions],
                    ['permissions' => $validated['permissions']]
                );

                DB::commit();

                DB::afterCommit(function () use ($role) {
                    $role->users()->chunkById(200, function ($users) {
                        foreach ($users as $user) {
                            $user->clearPermissionCache();
                        }
                    });
                });

                return redirect()->route('admin.roles.permissions', $role->id)
                    ->with('success', 'تم تحديث صلاحيات الدور بنجاح');
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Assign permissions failed: ' . $e->getMessage());
                return back()->with('error', 'حدث خطأ أثناء تحديث الصلاحيات. يرجى المحاولة مرة أخرى.');
            }
        }
            */
}
