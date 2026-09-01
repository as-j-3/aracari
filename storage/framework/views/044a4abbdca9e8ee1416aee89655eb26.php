<?php $__env->startSection('title', 'أركاري | إدارة المستخدمين'); ?>
<?php $__env->startSection('header_title', 'إدارة مستخدمين النظام'); ?>

<?php $__env->startSection('content'); ?>

<?php
    // Fetch relations needed for form selects (controller index() doesn't pass them)
    $statuses = \App\Models\UserStatus::all();
    $types = \App\Models\TypeOfUser::all();
    $roles = \App\Models\Role::orderBy('name')->get();

    // Local stats from paginator collection
    $totalUsers    = $users->total();
    $approvedUsers = $users->getCollection()->filter(fn($u) => $u->is_approved)->count();
    $pendingUsers  = $users->getCollection()->filter(fn($u) => !$u->is_approved)->count();
    $recentUsers   = $users->getCollection()->filter(fn($u) => $u->created_at && $u->created_at->gte(now()->subDays(30)))->count();

    // Eager-load relations to prevent N+1 queries in the table loop
    $users->getCollection()->loadCount(['trips', 'bookings']);
    $users->getCollection()->load(['status', 'roles', 'providerService']);

    // Permission checks (real authorization, not just route existence)
    $user        = auth()->user();
    $canView     = $user && $user->hasPermission('users:view');
    $canCreate   = $user && $user->hasPermission('users:create');
    $canEdit     = $user && $user->hasPermission('users:edit');
    $canDelete   = $user && $user->hasPermission('users:delete');
    $canApprove  = $user && $user->hasPermission('users:approve');
?>

<div class="space-y-6 font-cairo text-right" dir="rtl">

  
  <?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['icon' => 'users','title' => 'المستخدمون','count' => $totalUsers,'countLabel' => 'حساب','description' => 'عرض وإدارة قاعدة بيانات المستخدمين، صلاحياتهم، وحالة اعتماد الحسابات والمزودين.','descriptionIcon' => 'users']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'users','title' => 'المستخدمون','count' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalUsers),'countLabel' => 'حساب','description' => 'عرض وإدارة قاعدة بيانات المستخدمين، صلاحياتهم، وحالة اعتماد الحسابات والمزودين.','descriptionIcon' => 'users']); ?>
    <?php if($canCreate): ?>
      <button
          type="button"
          data-create-modal="user-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إضافة مستخدم جديد
      </button>
    <?php endif; ?>
   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e)): ?>
<?php $attributes = $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e; ?>
<?php unset($__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e)): ?>
<?php $component = $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e; ?>
<?php unset($__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e); ?>
<?php endif; ?>

  
  <div id="aracari-refreshable" class="space-y-6">

  
  <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 lg:gap-6">
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $totalUsers,'label' => 'إجمالي الحسابات','icon' => 'users','variant' => 'slate','badge' => '+12%','badgeIcon' => 'trending-up','caption' => 'عن الشهر الماضي']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalUsers),'label' => 'إجمالي الحسابات','icon' => 'users','variant' => 'slate','badge' => '+12%','badgeIcon' => 'trending-up','caption' => 'عن الشهر الماضي']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $approvedUsers,'label' => 'معتمدة','icon' => 'shield-check','variant' => 'emerald','badge' => 'نشطة','badgeIcon' => 'check-circle']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($approvedUsers),'label' => 'معتمدة','icon' => 'shield-check','variant' => 'emerald','badge' => 'نشطة','badgeIcon' => 'check-circle']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $pendingUsers,'label' => 'بانتظار الموافقة','icon' => 'hourglass','variant' => 'amber','badge' => 'قيد الانتظار','badgeIcon' => 'hourglass']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($pendingUsers),'label' => 'بانتظار الموافقة','icon' => 'hourglass','variant' => 'amber','badge' => 'قيد الانتظار','badgeIcon' => 'hourglass']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $recentUsers,'label' => 'تسجيلات حديثة','icon' => 'sparkles','variant' => 'blue','badge' => 'هذا الشهر','badgeIcon' => 'sparkles']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($recentUsers),'label' => 'تسجيلات حديثة','icon' => 'sparkles','variant' => 'blue','badge' => 'هذا الشهر','badgeIcon' => 'sparkles']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
  </div>

  
  <?php if (isset($component)) { $__componentOriginal90c131072f947a512cfcd740e0e498fa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal90c131072f947a512cfcd740e0e498fa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.data-table-card','data' => ['searchable' => true,'searchMode' => 'server','searchPlaceholder' => 'ابحث بالاسم، البريد، أو الجوال...','footerInfo' => 'عرض ' . $users->count() . ' من أصل ' . $users->total() . ' حساب']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('data-table-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['searchable' => true,'searchMode' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('server'),'searchPlaceholder' => 'ابحث بالاسم، البريد، أو الجوال...','footerInfo' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('عرض ' . $users->count() . ' من أصل ' . $users->total() . ' حساب')]); ?>
     <?php $__env->slot('thead', null, []); ?> 
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المستخدم</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">التسجيل والاتصال</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الدور الوظيفي</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة والأمان</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
     <?php $__env->endSlot(); ?>

     <?php $__env->slot('rows', null, []); ?> 
      <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          // Role badge class — match against multiple needles via a foreach loop
          // (PHP 8.2 rejects arrays passed to str_contains(); loop over needles instead)
          $roleName = (string)($user->roles->first()?->name ?? $user->user_type_name ?? '');
          $roleBadgeClass = 'bg-slate-100/80 text-slate-700 border-slate-200/50'; // default
          $roleGroups = [
              ['needles' => ['مسؤول نظام', 'مدير نظام', 'admin'],         'class' => 'bg-rose-100/80 text-rose-700 border-rose-200/50'],
              ['needles' => ['مزود خدمة', 'provider'],                     'class' => 'bg-indigo-100/80 text-indigo-700 border-indigo-200/50'],
              ['needles' => ['مدير فرع', 'branch_manager'],                'class' => 'bg-blue-100/80 text-blue-700 border-blue-200/50'],
              ['needles' => ['مسؤول حجز', 'booking_agent'],                'class' => 'bg-amber-100/80 text-amber-700 border-amber-200/50'],
              ['needles' => ['عميل', 'customer'],                          'class' => 'bg-teal-100/80 text-teal-700 border-teal-200/50'],
          ];
          foreach ($roleGroups as $group) {
              foreach ($group['needles'] as $needle) {
                  if (str_contains($roleName, (string) $needle)) {
                      $roleBadgeClass = $group['class'];
                      break 2;
                  }
              }
          }

          // Status badge
          $isTrashed = $user->deleted_at !== null;
          $statusCode = $user->status?->status_code;
          $statusBadge = match(true) {
              $isTrashed => ['bg' => 'bg-rose-50 text-rose-700 border-rose-200', 'text' => 'محذوف', 'dot' => 'bg-rose-500'],
              $statusCode === 'active' => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'text' => 'نشط', 'dot' => 'bg-emerald-500'],
              $statusCode === 'inactive' => ['bg' => 'bg-slate-50 text-slate-700 border-slate-200', 'text' => 'غير نشط', 'dot' => 'bg-slate-400'],
              $statusCode === 'pending' => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'text' => 'قيد الانتظار', 'dot' => 'bg-amber-500'],
              $statusCode === 'suspended' => ['bg' => 'bg-rose-50 text-rose-700 border-rose-200', 'text' => 'موقوف', 'dot' => 'bg-rose-500'],
              default => ['bg' => 'bg-slate-50 text-slate-700 border-slate-200', 'text' => 'غير محدد', 'dot' => 'bg-slate-400'],
          };

          // Approval badge
          $approvalBadge = $user->is_approved
              ? ['bg' => 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20', 'text' => 'معتمد من الإدارة']
              : ['bg' => 'bg-amber-500/10 text-amber-600 border-amber-500/20', 'text' => 'قيد المراجعة'];

          // Phone verified badge
          $phoneBadge = $user->phone_verified_at
              ? ['bg' => 'bg-blue-500/10 text-blue-600 border-blue-500/20', 'text' => 'هاتف مفعل']
              : ['bg' => 'bg-slate-500/10 text-slate-500 border-slate-500/20', 'text' => 'هاتف غير مفعل'];

          // Avatar initial
          $initial = mb_substr($user->name, 0, 1, 'UTF-8');
        ?>

        <tr class="hover:bg-slate-50/50 transition-colors group">

          
          <td data-searchable class="px-6 py-4">
            <div class="flex items-center gap-3">
              <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-600 to-teal-700 text-white flex items-center justify-center font-black text-lg shadow-md shadow-emerald-500/10 group-hover:scale-105 transition-transform duration-300">
                <?php echo e($initial); ?>

              </div>
              <div>
                <div class="font-bold text-slate-800 text-sm"><?php echo e($user->name); ?></div>
                <div class="text-[10px] font-bold text-slate-400 flex items-center gap-1 mt-0.5">
                  <i data-lucide="mail" class="w-3 h-3 text-slate-400"></i>
                  <span><?php echo e($user->email ?? 'لا يوجد بريد مسجل'); ?></span>
                </div>
              </div>
            </div>
          </td>

          
          <td data-searchable class="px-6 py-4">
            <div class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
              <i data-lucide="phone" class="w-3.5 h-3.5 text-slate-400"></i>
              <span dir="ltr"><?php echo e($user->phone_number); ?></span>
            </div>
            <div class="text-[10px] font-bold text-slate-400 flex items-center gap-1.5 mt-1">
              <i data-lucide="calendar" class="w-3 h-3 text-slate-400"></i>
              <span><?php echo e(optional($user->created_at)->translatedFormat('j F، Y') ?? '—'); ?></span>
            </div>
          </td>

          
          <td class="px-6 py-4 text-center">
            <div class="flex flex-col items-center gap-1.5">
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-bold border <?php echo e($roleBadgeClass); ?>">
                <i data-lucide="shield" class="w-3 h-3"></i>
                <?php echo e($roleName); ?>

              </span>
              
              <?php if($user->isProvider() && $user->user_parent === null && !$user->providerService): ?>
                <a href="<?php echo e(route('admin.provider-services.index')); ?>" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[8px] font-bold border bg-amber-500/10 text-amber-600 border-amber-500/30 hover:bg-amber-500/20 transition-colors cursor-pointer" title="انقر لإضافة خدمة">
                  <i data-lucide="alert-triangle" class="w-2.5 h-2.5"></i>
                  <span>لم تُضف خدمة بعد</span>
                </a>
              <?php endif; ?>
            </div>
          </td>

          
          <td class="px-6 py-4">
            <div class="flex flex-col items-center gap-1.5">
              
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9px] font-bold border <?php echo e($statusBadge['bg']); ?>">
                <span class="w-1.5 h-1.5 rounded-full <?php echo e($statusBadge['dot']); ?>"></span>
                <span><?php echo e($statusBadge['text']); ?></span>
              </span>
              
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[8px] font-bold border <?php echo e($approvalBadge['bg']); ?>">
                <i data-lucide="check" class="w-2.5 h-2.5"></i>
                <span><?php echo e($approvalBadge['text']); ?></span>
              </span>
              
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[8px] font-bold border <?php echo e($phoneBadge['bg']); ?>">
                <i data-lucide="phone" class="w-2.5 h-2.5"></i>
                <span><?php echo e($phoneBadge['text']); ?></span>
              </span>
            </div>
          </td>

          
          <td class="px-6 py-4 text-center">
            <div class="flex items-center justify-center gap-1.5">
              <?php if($canView): ?>
                <?php if (isset($component)) { $__componentOriginald4c6978101b1c254eb70511d3c21c03f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald4c6978101b1c254eb70511d3c21c03f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.action-button','data' => ['variant' => 'view','dataViewModal' => 'user-details-modal','dataViewJson' => ''.e(json_encode([
                        'uuid' => $user->uuid,
                        'name' => $user->name,
                        'phone_number' => $user->phone_number,
                        'email' => $user->email,
                        'type_name' => $user->user_type_name,
                        'role_name' => $user->roles->first()?->name ?? $user->user_type_name ?? '—',
                        'status_name' => $user->status?->status_name ?? '—',
                        'status_code' => $user->status?->status_code ?? '',
                        'is_approved' => (int) $user->is_approved,
                        'phone_verified_at' => $user->phone_verified_at ? $user->phone_verified_at->translatedFormat('j F، Y') : null,
                        'is_phone_verified' => $user->phone_verified_at ? 1 : 0,
                        'created_at' => optional($user->created_at)->translatedFormat('j F، Y'),
                        'is_provider' => $user->isProvider() ? 1 : 0,
                        'company_name' => $user->providerService?->name ?? ($user->name ?? '---'),
                        'cr_number' => $user->providerService?->cr_number ?? '---',
                        'born_country' => $user->providerService?->born_country ?? 'اليمن',
                        'website' => $user->providerService?->website ?? '',
                        'trips_count' => $user->trips_count ?? 0,
                        'bookings_count' => $user->bookings_count ?? 0,
                        'is_trashed' => $isTrashed ? 1 : 0,
                    ], JSON_UNESCAPED_UNICODE)).'','ariaLabel' => 'عرض ' . $user->name]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('action-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'view','data-view-modal' => 'user-details-modal','data-view-json' => ''.e(json_encode([
                        'uuid' => $user->uuid,
                        'name' => $user->name,
                        'phone_number' => $user->phone_number,
                        'email' => $user->email,
                        'type_name' => $user->user_type_name,
                        'role_name' => $user->roles->first()?->name ?? $user->user_type_name ?? '—',
                        'status_name' => $user->status?->status_name ?? '—',
                        'status_code' => $user->status?->status_code ?? '',
                        'is_approved' => (int) $user->is_approved,
                        'phone_verified_at' => $user->phone_verified_at ? $user->phone_verified_at->translatedFormat('j F، Y') : null,
                        'is_phone_verified' => $user->phone_verified_at ? 1 : 0,
                        'created_at' => optional($user->created_at)->translatedFormat('j F، Y'),
                        'is_provider' => $user->isProvider() ? 1 : 0,
                        'company_name' => $user->providerService?->name ?? ($user->name ?? '---'),
                        'cr_number' => $user->providerService?->cr_number ?? '---',
                        'born_country' => $user->providerService?->born_country ?? 'اليمن',
                        'website' => $user->providerService?->website ?? '',
                        'trips_count' => $user->trips_count ?? 0,
                        'bookings_count' => $user->bookings_count ?? 0,
                        'is_trashed' => $isTrashed ? 1 : 0,
                    ], JSON_UNESCAPED_UNICODE)).'','ariaLabel' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('عرض ' . $user->name)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald4c6978101b1c254eb70511d3c21c03f)): ?>
<?php $attributes = $__attributesOriginald4c6978101b1c254eb70511d3c21c03f; ?>
<?php unset($__attributesOriginald4c6978101b1c254eb70511d3c21c03f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald4c6978101b1c254eb70511d3c21c03f)): ?>
<?php $component = $__componentOriginald4c6978101b1c254eb70511d3c21c03f; ?>
<?php unset($__componentOriginald4c6978101b1c254eb70511d3c21c03f); ?>
<?php endif; ?>
              <?php endif; ?>
              <?php if($canEdit && !$isTrashed): ?>
                <?php if (isset($component)) { $__componentOriginald4c6978101b1c254eb70511d3c21c03f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald4c6978101b1c254eb70511d3c21c03f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.action-button','data' => ['variant' => 'edit','dataEditModal' => 'user-form-modal','dataEditJson' => ''.e(json_encode([
                        'uuid' => $user->uuid,
                        'name' => $user->name,
                        'phone_number' => $user->phone_number,
                        'country_code' => $user->country_code ?? '967',
                        'email' => $user->email,
                        'status_id' => $user->status_id,
                        'type_user_code' => $user->type_user_code,
                        'is_approved' => $user->is_approved,
                        'role_id' => $user->roles->first()?->id,
                    ], JSON_UNESCAPED_UNICODE)).'','ariaLabel' => 'تعديل ' . $user->name]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('action-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'edit','data-edit-modal' => 'user-form-modal','data-edit-json' => ''.e(json_encode([
                        'uuid' => $user->uuid,
                        'name' => $user->name,
                        'phone_number' => $user->phone_number,
                        'country_code' => $user->country_code ?? '967',
                        'email' => $user->email,
                        'status_id' => $user->status_id,
                        'type_user_code' => $user->type_user_code,
                        'is_approved' => $user->is_approved,
                        'role_id' => $user->roles->first()?->id,
                    ], JSON_UNESCAPED_UNICODE)).'','ariaLabel' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('تعديل ' . $user->name)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald4c6978101b1c254eb70511d3c21c03f)): ?>
<?php $attributes = $__attributesOriginald4c6978101b1c254eb70511d3c21c03f; ?>
<?php unset($__attributesOriginald4c6978101b1c254eb70511d3c21c03f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald4c6978101b1c254eb70511d3c21c03f)): ?>
<?php $component = $__componentOriginald4c6978101b1c254eb70511d3c21c03f; ?>
<?php unset($__componentOriginald4c6978101b1c254eb70511d3c21c03f); ?>
<?php endif; ?>
              <?php endif; ?>
              <?php if($canDelete): ?>
                <?php if(!$isTrashed): ?>
                  <?php if (isset($component)) { $__componentOriginald4c6978101b1c254eb70511d3c21c03f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald4c6978101b1c254eb70511d3c21c03f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.action-button','data' => ['variant' => 'delete','dataDeleteUrl' => ''.e(route('admin.users.destroy', $user->uuid)).'','dataDeleteLabel' => ''.e($user->name).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('action-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'delete','data-delete-url' => ''.e(route('admin.users.destroy', $user->uuid)).'','data-delete-label' => ''.e($user->name).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald4c6978101b1c254eb70511d3c21c03f)): ?>
<?php $attributes = $__attributesOriginald4c6978101b1c254eb70511d3c21c03f; ?>
<?php unset($__attributesOriginald4c6978101b1c254eb70511d3c21c03f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald4c6978101b1c254eb70511d3c21c03f)): ?>
<?php $component = $__componentOriginald4c6978101b1c254eb70511d3c21c03f; ?>
<?php unset($__componentOriginald4c6978101b1c254eb70511d3c21c03f); ?>
<?php endif; ?>
                <?php else: ?>
                  
                  <button
                      type="button"
                      data-restore-url="<?php echo e(route('admin.users.restore', $user->uuid)); ?>"
                      data-restore-label="<?php echo e($user->name); ?>"
                      class="w-9 h-9 rounded-xl bg-white border border-emerald-200 text-emerald-600 hover:bg-emerald-50 hover:border-emerald-300 transition-all duration-300 shadow-sm flex items-center justify-center"
                      title="استعادة المستخدم"
                      aria-label="استعادة <?php echo e($user->name); ?>"
                  >
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                  </button>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </td>

        </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <?php if (isset($component)) { $__componentOriginal074a021b9d42f490272b5eefda63257c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal074a021b9d42f490272b5eefda63257c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty-state','data' => ['icon' => 'users','title' => request('search') ? 'لا توجد نتائج مطابقة' : 'لا توجد حسابات مسجلة','message' => request('search') ? 'جرّب تعديل كلمات البحث' : 'أضف مستخدم جديد لتتمكن من إدارة الحسابات والصلاحيات','colspan' => 5]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'users','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('search') ? 'لا توجد نتائج مطابقة' : 'لا توجد حسابات مسجلة'),'message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('search') ? 'جرّب تعديل كلمات البحث' : 'أضف مستخدم جديد لتتمكن من إدارة الحسابات والصلاحيات'),'colspan' => 5]); ?>
          <?php if(!request('search') && $canCreate): ?>
            <button
                type="button"
                data-create-modal="user-form-modal"
                class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
            >
              <i data-lucide="plus" class="w-4 h-4"></i>
              إضافة مستخدم جديد
            </button>
          <?php endif; ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal074a021b9d42f490272b5eefda63257c)): ?>
<?php $attributes = $__attributesOriginal074a021b9d42f490272b5eefda63257c; ?>
<?php unset($__attributesOriginal074a021b9d42f490272b5eefda63257c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal074a021b9d42f490272b5eefda63257c)): ?>
<?php $component = $__componentOriginal074a021b9d42f490272b5eefda63257c; ?>
<?php unset($__componentOriginal074a021b9d42f490272b5eefda63257c); ?>
<?php endif; ?>
      <?php endif; ?>
     <?php $__env->endSlot(); ?>

     <?php $__env->slot('footer', null, []); ?> 
      <?php if (isset($component)) { $__componentOriginal41032d87daf360242eb88dbda6c75ed1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal41032d87daf360242eb88dbda6c75ed1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pagination','data' => ['paginator' => $users]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pagination'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($users)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal41032d87daf360242eb88dbda6c75ed1)): ?>
<?php $attributes = $__attributesOriginal41032d87daf360242eb88dbda6c75ed1; ?>
<?php unset($__attributesOriginal41032d87daf360242eb88dbda6c75ed1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal41032d87daf360242eb88dbda6c75ed1)): ?>
<?php $component = $__componentOriginal41032d87daf360242eb88dbda6c75ed1; ?>
<?php unset($__componentOriginal41032d87daf360242eb88dbda6c75ed1); ?>
<?php endif; ?>
     <?php $__env->endSlot(); ?>
   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal90c131072f947a512cfcd740e0e498fa)): ?>
<?php $attributes = $__attributesOriginal90c131072f947a512cfcd740e0e498fa; ?>
<?php unset($__attributesOriginal90c131072f947a512cfcd740e0e498fa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal90c131072f947a512cfcd740e0e498fa)): ?>
<?php $component = $__componentOriginal90c131072f947a512cfcd740e0e498fa; ?>
<?php unset($__componentOriginal90c131072f947a512cfcd740e0e498fa); ?>
<?php endif; ?>

  </div>

</div>


<?php if (isset($component)) { $__componentOriginaldf85e6552b95df616d59caebcbf2473d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldf85e6552b95df616d59caebcbf2473d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form-modal','data' => ['id' => 'user-form-modal','title' => 'إضافة مستخدم جديد','variant' => 'create','action' => route('admin.users.store'),'method' => 'POST','successMessage' => 'تم حفظ المستخدم بنجاح']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form-modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'user-form-modal','title' => 'إضافة مستخدم جديد','variant' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('create'),'action' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.users.store')),'method' => 'POST','successMessage' => 'تم حفظ المستخدم بنجاح']); ?>
  
  <?php if (isset($component)) { $__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form-field','data' => ['name' => 'name','label' => 'الاسم الكامل','icon' => 'user','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'name','label' => 'الاسم الكامل','icon' => 'user','required' => true]); ?>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="user" class="w-4 h-4"></i>
      </div>
      <input
          type="text"
          name="name"
          id="name"
          required
          placeholder="مثال: أحمد محمد"
          value="<?php echo e(old('name')); ?>"
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
      >
    </div>
   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d)): ?>
<?php $attributes = $__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d; ?>
<?php unset($__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d)): ?>
<?php $component = $__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d; ?>
<?php unset($__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d); ?>
<?php endif; ?>

  
  <div class="space-y-1.5">
    <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
      <i data-lucide="phone" class="w-3.5 h-3.5"></i>
      رقم الهاتف الجوال <span class="text-rose-500">*</span>
    </label>
    <div class="flex gap-2">
      
      <div class="relative group">
        <select
            name="country_code"
            id="country_code"
            required
            class="w-28 bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-2 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-center appearance-none cursor-pointer"
        >
          <option value="">الدولة</option>
          <?php $__currentLoopData = \App\Constants\CountryCodes::all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $code => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($code); ?>" <?php echo e(old('country_code') == $code ? 'selected' : ''); ?>>(+<?php echo e($code); ?>)</option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <div class="absolute inset-y-0 left-2 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
        </div>
      </div>
      
      <div class="relative group flex-1">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="phone" class="w-4 h-4"></i>
        </div>
        <input
            type="tel"
            name="phone_number"
            id="phone_number"
            required
            dir="ltr"
            placeholder="7XXXXXXXX"
            value="<?php echo e(old('phone_number')); ?>"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left font-mono"
        >
      </div>
    </div>
    <p class="text-[10px] text-slate-400">أدخل الرقم بدون رمز الدولة (سيتم إضافته تلقائياً)</p>
    <p class="text-[11px] text-rose-600 font-bold" data-error-for="phone_number"></p>
    <p class="text-[11px] text-rose-600 font-bold" data-error-for="country_code"></p>
  </div>

  
  <?php if (isset($component)) { $__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form-field','data' => ['name' => 'email','label' => 'البريد الإلكتروني','icon' => 'mail']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'email','label' => 'البريد الإلكتروني','icon' => 'mail']); ?>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="mail" class="w-4 h-4"></i>
      </div>
      <input
          type="email"
          name="email"
          id="email"
          dir="ltr"
          placeholder="email@example.com"
          value="<?php echo e(old('email')); ?>"
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left"
      >
    </div>
   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d)): ?>
<?php $attributes = $__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d; ?>
<?php unset($__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d)): ?>
<?php $component = $__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d; ?>
<?php unset($__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d); ?>
<?php endif; ?>

  
  <?php if (isset($component)) { $__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form-field','data' => ['name' => 'password','label' => 'كلمة المرور','icon' => 'lock','required' => true,'id' => 'password-field']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'password','label' => 'كلمة المرور','icon' => 'lock','required' => true,'id' => 'password-field']); ?>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="lock" class="w-4 h-4"></i>
      </div>
      <input
          type="password"
          name="password"
          id="password"
          required
          placeholder="الحد الأدنى 8 أحرف"
          data-edit-optional
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
      >
    </div>
   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d)): ?>
<?php $attributes = $__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d; ?>
<?php unset($__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d)): ?>
<?php $component = $__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d; ?>
<?php unset($__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d); ?>
<?php endif; ?>

  
  <?php if (isset($component)) { $__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form-field','data' => ['name' => 'password_confirmation','label' => 'تأكيد كلمة المرور','icon' => 'lock','required' => true,'id' => 'password-confirmation-field']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'password_confirmation','label' => 'تأكيد كلمة المرور','icon' => 'lock','required' => true,'id' => 'password-confirmation-field']); ?>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="lock" class="w-4 h-4"></i>
      </div>
      <input
          type="password"
          name="password_confirmation"
          id="password_confirmation"
          required
          placeholder="أعد إدخال كلمة المرور"
          data-edit-optional
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
      >
    </div>
   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d)): ?>
<?php $attributes = $__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d; ?>
<?php unset($__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d)): ?>
<?php $component = $__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d; ?>
<?php unset($__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d); ?>
<?php endif; ?>

  
  <?php if (isset($component)) { $__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form-field','data' => ['name' => 'status_id','label' => 'حالة الحساب','icon' => 'activity','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'status_id','label' => 'حالة الحساب','icon' => 'activity','required' => true]); ?>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="activity" class="w-4 h-4"></i>
      </div>
      <select
          name="status_id"
          id="status_id"
          required
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none"
      >
        <option value="">اختر الحالة</option>
        <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($s->id); ?>" <?php echo e((old('status_id') == $s->id) ? 'selected' : ''); ?>><?php echo e($s->status_name); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
    </div>
   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d)): ?>
<?php $attributes = $__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d; ?>
<?php unset($__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d)): ?>
<?php $component = $__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d; ?>
<?php unset($__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d); ?>
<?php endif; ?>

  
  <?php if (isset($component)) { $__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form-field','data' => ['name' => 'type_user_code','label' => 'نوع المستخدم','icon' => 'user-cog','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'type_user_code','label' => 'نوع المستخدم','icon' => 'user-cog','required' => true]); ?>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="user-cog" class="w-4 h-4"></i>
      </div>
      <select
          name="type_user_code"
          id="type_user_code"
          required
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none"
      >
        <option value="">اختر النوع</option>
        <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($t->id); ?>" <?php echo e((old('type_user_code') == $t->id) ? 'selected' : ''); ?>><?php echo e($t->type_name); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
    </div>
   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d)): ?>
<?php $attributes = $__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d; ?>
<?php unset($__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d)): ?>
<?php $component = $__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d; ?>
<?php unset($__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d); ?>
<?php endif; ?>

  
  <?php if (isset($component)) { $__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form-field','data' => ['name' => 'role_id','label' => 'الدور الوظيفي','icon' => 'shield','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'role_id','label' => 'الدور الوظيفي','icon' => 'shield','required' => true]); ?>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="shield" class="w-4 h-4"></i>
      </div>
      <select
          name="role_id"
          id="role_id"
          required
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none"
      >
        <option value="">اختر الدور</option>
        <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($r->id); ?>" <?php echo e((old('role_id') == $r->id) ? 'selected' : ''); ?>><?php echo e($r->name); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
    </div>
   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d)): ?>
<?php $attributes = $__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d; ?>
<?php unset($__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d)): ?>
<?php $component = $__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d; ?>
<?php unset($__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d); ?>
<?php endif; ?>

  
  <?php if (isset($component)) { $__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form-field','data' => ['name' => 'is_approved','label' => 'اعتماد الحساب','icon' => 'check-circle']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'is_approved','label' => 'اعتماد الحساب','icon' => 'check-circle']); ?>
    <label class="flex items-center gap-3 cursor-pointer select-none">
      <div class="relative">
        <input
            type="checkbox"
            name="is_approved"
            id="is_approved"
            value="1"
            class="sr-only peer"
        >
        <div class="w-11 h-6 bg-slate-200 peer-focus:ring-4 peer-focus:ring-teal-500/10 rounded-full peer peer-checked:bg-teal-500 transition-all"></div>
        <div class="absolute top-0.5 right-0.5 bg-white w-5 h-5 rounded-full shadow peer-checked:translate-x-[-20px] transition-all"></div>
      </div>
      <span class="text-xs font-bold text-slate-600">اعتماد الحساب وتفعيله</span>
    </label>
   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d)): ?>
<?php $attributes = $__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d; ?>
<?php unset($__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d)): ?>
<?php $component = $__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d; ?>
<?php unset($__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d); ?>
<?php endif; ?>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldf85e6552b95df616d59caebcbf2473d)): ?>
<?php $attributes = $__attributesOriginaldf85e6552b95df616d59caebcbf2473d; ?>
<?php unset($__attributesOriginaldf85e6552b95df616d59caebcbf2473d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldf85e6552b95df616d59caebcbf2473d)): ?>
<?php $component = $__componentOriginaldf85e6552b95df616d59caebcbf2473d; ?>
<?php unset($__componentOriginaldf85e6552b95df616d59caebcbf2473d); ?>
<?php endif; ?>



<div
    id="user-details-modal"
    data-aracari-modal
    class="hidden fixed inset-0 z-[80] overflow-y-auto font-cairo"
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
>
  
  <div class="aracari-modal-overlay fixed inset-0 bg-[#1a1c1e]/60 backdrop-blur-sm opacity-0 transition-opacity duration-200"></div>

  
  <div class="flex min-h-full items-center justify-center p-4">
    <div
        class="aracari-modal-panel relative bg-white rounded-3xl max-w-4xl w-full shadow-2xl overflow-hidden border border-[#eeeef0] flex flex-col max-h-[90vh] opacity-0 scale-95 translate-y-4 transition-all duration-200"
        dir="rtl"
    >

      
      <div class="relative p-6 bg-gradient-to-r from-teal-900 via-[#1b3d24] to-emerald-950 text-white flex flex-col justify-end min-h-48">
        
        <div class="absolute top-4 left-4">
          <button
              type="button"
              data-close-modal
              class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur text-white flex items-center justify-center transition-all"
              aria-label="إغلاق"
          >
            <i data-lucide="x" class="w-5 h-5"></i>
          </button>
        </div>

        <div class="relative z-10 flex flex-col md:flex-row items-center md:items-end gap-6 text-center md:text-right">
          
          <div class="relative group">
            <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-0.5 shadow-lg">
              <div class="w-full h-full rounded-[14px] bg-white flex items-center justify-center font-black text-4xl text-teal-700 shadow-inner">
                <span data-preview="name" data-preview-default="U">U</span>
              </div>
            </div>
            
            <div id="user-details-approval-badge" class="absolute -bottom-1 -left-1 w-7 h-7 bg-amber-500 border-2 border-white rounded-xl flex items-center justify-center text-white shadow">
              <i data-lucide="hourglass" class="w-4 h-4"></i>
            </div>
          </div>

          
          <div class="space-y-1.5 flex-1">
            <div class="flex flex-wrap items-center justify-center md:justify-start gap-2">
              <h2 class="text-xl font-extrabold tracking-tight" data-preview="name" data-preview-default="—">—</h2>
              <span id="user-details-role-badge" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black border bg-slate-100/80 text-slate-700 border-slate-200/50">
                <i data-lucide="shield" class="w-3 h-3"></i>
                <span data-preview="role_name" data-preview-default="—">—</span>
              </span>
              <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-white/10 text-emerald-200 text-[10px] font-bold">
                <i data-lucide="user-cog" class="w-3 h-3"></i>
                <span data-preview="type_name" data-preview-default="—">—</span>
              </span>
            </div>

            <p class="text-xs text-emerald-200/80 font-medium flex items-center justify-center md:justify-start gap-1.5">
              <i data-lucide="mail" class="w-3.5 h-3.5"></i>
              <span data-preview="email" data-preview-default="لا يوجد بريد مسجل">لا يوجد بريد مسجل</span>
            </p>

            <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 pt-2">
              <span class="bg-white/10 text-white text-[10px] font-bold px-2.5 py-1 rounded-lg flex items-center gap-1">
                <i data-lucide="phone" class="w-3.5 h-3.5 text-emerald-400"></i>
                <span dir="ltr" data-preview="phone_number" data-preview-default="—">—</span>
              </span>
              <span class="bg-white/10 text-white text-[10px] font-bold px-2.5 py-1 rounded-lg flex items-center gap-1">
                <i data-lucide="calendar" class="w-3.5 h-3.5 text-amber-400"></i>
                <span>انضم: <span data-preview="created_at" data-preview-default="—">—</span></span>
              </span>
            </div>
          </div>
        </div>
      </div>

      
      <div class="p-6 flex-1 overflow-y-auto space-y-6 md:space-y-0 md:grid md:grid-cols-3 md:gap-6 text-right scrollbar-thin">

        
        <div class="space-y-5 md:col-span-1">

          
          <div class="bg-slate-50/50 p-5 rounded-2xl border border-gray-200 space-y-4">
            <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-gray-100 pb-2">
              <i data-lucide="sliders-horizontal" class="w-4 h-4 text-teal-600"></i>
              <span>حالة الحساب والأمان</span>
            </h4>

            <div class="space-y-3">
              
              <div class="flex justify-between items-center p-3 bg-white border border-gray-150 rounded-xl">
                <span class="text-xs font-bold text-slate-500">حالة التفعيل:</span>
                <span id="user-details-activation-badge" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold border bg-emerald-50 text-emerald-700 border-emerald-200">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                  <span data-preview="status_name" data-preview-default="—">—</span>
                </span>
              </div>

              
              <div class="flex justify-between items-center p-3 bg-white border border-gray-150 rounded-xl">
                <span class="text-xs font-bold text-slate-500">اعتماد الإدارة:</span>
                <span id="user-details-approval-text" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold border bg-amber-50 text-amber-700 border-amber-200">
                  <i data-lucide="hourglass" class="w-3 h-3"></i>
                  <span>قيد المراجعة</span>
                </span>
              </div>

              
              <div class="flex justify-between items-center p-3 bg-white border border-gray-150 rounded-xl">
                <span class="text-xs font-bold text-slate-500">رقم الهاتف:</span>
                <span id="user-details-phone-badge" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold border bg-amber-50 text-amber-700 border-amber-200">
                  <i data-lucide="phone" class="w-3 h-3"></i>
                  <span>غير مفعل</span>
                </span>
              </div>
            </div>
          </div>

          
          <div class="space-y-2 pt-1">
            <span class="text-[10px] font-black text-slate-400 block px-1">إجراءات التحكم والاعتماد</span>

            
            <?php if($canApprove): ?>
              <form
                  data-aracari-ajax
                  data-success-message="تم اعتماد الحساب بنجاح"
                  class="hidden"
                  id="user-details-approve-form"
              >
                <?php echo csrf_field(); ?>
                <button
                    type="submit"
                    class="w-full py-2.5 bg-gradient-to-r from-emerald-600 to-teal-700 text-white text-xs font-bold rounded-xl shadow hover:shadow-md transition-all flex items-center justify-center gap-1.5"
                >
                  <i data-lucide="check-circle" class="w-4 h-4"></i>
                  <span>اعتماد الحساب الآن</span>
                </button>
              </form>
            <?php endif; ?>

            
            <form
                data-aracari-ajax
                data-success-message="تم تفعيل رقم الهاتف بنجاح"
                class="hidden"
                id="user-details-verify-phone-form"
            >
              <?php echo csrf_field(); ?>
              <button
                  type="submit"
                  class="w-full py-2.5 bg-gradient-to-r from-blue-600 to-indigo-700 text-white text-xs font-bold rounded-xl shadow hover:shadow-md transition-all flex items-center justify-center gap-1.5"
              >
                <i data-lucide="phone" class="w-4 h-4"></i>
                <span>تفعيل رقم الهاتف</span>
              </button>
            </form>

            
            <?php if($canEdit): ?>
              <button
                  type="button"
                  id="user-details-toggle-btn"
                  class="w-full py-2.5 text-xs font-bold rounded-xl border flex items-center justify-center gap-1.5 transition-all bg-rose-50 border-rose-200 text-rose-700 hover:bg-rose-100"
              >
                <i data-lucide="toggle-left" class="w-4 h-4"></i>
                <span id="user-details-toggle-label">إيقاف/تجميد الحساب</span>
              </button>
            <?php endif; ?>

            
            <?php if($canDelete): ?>
              <button
                  type="button"
                  id="user-details-restore-btn"
                  class="hidden w-full py-2.5 text-xs font-bold rounded-xl border flex items-center justify-center gap-1.5 transition-all bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100"
              >
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                <span>استعادة الحساب المحذوف</span>
              </button>
            <?php endif; ?>

            
            <?php if($canDelete): ?>
              <button
                  type="button"
                  id="user-details-delete-btn"
                  class="w-full py-2.5 bg-rose-600 text-white hover:bg-rose-700 text-xs font-bold rounded-xl shadow transition-all flex items-center justify-center gap-1.5"
              >
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                <span>حذف المستخدم نهائياً</span>
              </button>
            <?php endif; ?>
          </div>
        </div>

        
        <div class="space-y-5 md:col-span-2">

          
          <div id="user-details-company-section" class="hidden bg-[#fffbeb] p-5 rounded-2xl border border-amber-200 space-y-3">
            <h4 class="font-bold text-xs text-amber-950 uppercase tracking-wider flex items-center gap-1.5 border-b border-amber-200/50 pb-2">
              <i data-lucide="building" class="w-4 h-4 text-amber-700"></i>
              <span>بيانات الشركة والترخيص</span>
            </h4>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 text-xs text-amber-900">
              <div>
                <span class="text-amber-800/60 block font-bold mb-0.5">الاسم التجاري:</span>
                <span class="font-extrabold text-amber-950" data-preview="company_name" data-preview-default="---">---</span>
              </div>
              <div>
                <span class="text-amber-800/60 block font-bold mb-0.5">رقم السجل التجاري:</span>
                <span class="font-mono font-extrabold text-amber-950" data-preview="cr_number" data-preview-default="---">---</span>
              </div>
              <div>
                <span class="text-amber-800/60 block font-bold mb-0.5">بلد المنشأ:</span>
                <span class="font-extrabold text-amber-950" data-preview="born_country" data-preview-default="اليمن">اليمن</span>
              </div>
              <div id="user-details-website-wrap" class="hidden">
                <span class="text-amber-800/60 block font-bold mb-0.5">الموقع الإلكتروني:</span>
                <span class="text-amber-600 font-mono font-black break-all" data-preview="website" data-preview-default="">—</span>
              </div>
            </div>
          </div>

          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            
            <div class="bg-white border border-gray-200 p-4 rounded-2xl shadow-sm flex items-center gap-4 hover:shadow transition-shadow">
              <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                <i data-lucide="check-circle" class="w-6 h-6"></i>
              </div>
              <div>
                <div class="text-xl font-black text-gray-900" data-preview="trips_count" data-preview-default="0">0</div>
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">رحلة منشأة</div>
              </div>
            </div>

            
            <div class="bg-white border border-gray-200 p-4 rounded-2xl shadow-sm flex items-center gap-4 hover:shadow transition-shadow">
              <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <i data-lucide="bookmark" class="w-6 h-6"></i>
              </div>
              <div>
                <div class="text-xl font-black text-gray-900" data-preview="bookings_count" data-preview-default="0">0</div>
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">حجز مسجل</div>
              </div>
            </div>
          </div>

          
          <div class="bg-slate-50/50 border border-slate-200/70 rounded-2xl p-4">
            <div class="flex flex-wrap items-center gap-4 text-[10px] font-bold text-slate-400">
              <span class="flex items-center gap-1.5">
                <i data-lucide="fingerprint" class="w-3.5 h-3.5"></i>
                UUID: <span class="font-mono text-slate-600" data-preview="uuid" data-preview-default="—">—</span>
              </span>
              <span class="flex items-center gap-1.5">
                <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                تاريخ التسجيل: <span class="text-slate-600" data-preview="created_at" data-preview-default="—">—</span>
              </span>
              <span class="flex items-center gap-1.5">
                <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                تفعيل الهاتف: <span class="text-slate-600" data-preview="phone_verified_at" data-preview-default="غير مفعل">غير مفعل</span>
              </span>
            </div>
          </div>
        </div>

      </div>

      
      <div class="p-4 border-t border-[#eeeef0] bg-[#f9f9fc] flex justify-end">
        <button
            type="button"
            data-close-modal
            class="px-5 py-2 bg-slate-700 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow transition-all active:scale-95"
        >
          إغلاق الملف الشخصي
        </button>
      </div>

      
      <input type="hidden" id="user-details-uuid-hidden" value="">
    </div>
  </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
  // ===== User form modal: dynamic action + title + variant switching =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'user-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.uuid);

    // Handle password fields required toggle
    var passwordFields = modal.querySelectorAll('[data-edit-optional]');
    passwordFields.forEach(function(f) {
      if (isEdit) {
        f.removeAttribute('required');
      } else {
        f.setAttribute('required', '');
      }
    });

    if (isEdit) {
      // edit mode → PUT to update route
      var updateUrl = "<?php echo e(route('admin.users.update', '__UUID__')); ?>".replace('__UUID__', data.uuid);
      form.setAttribute('action', updateUrl);
      var existingMethod = form.querySelector('input[name="_method"]');
      if (existingMethod) existingMethod.value = 'PUT';
      else {
        var m = document.createElement('input');
        m.type = 'hidden'; m.name = '_method'; m.value = 'PUT';
        m.setAttribute('data-dynamic', 'true');
        form.appendChild(m);
      }
      // update banner to amber (edit variant)
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل المستخدم';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }
      // success message
      form.setAttribute('data-success-message', 'تم تحديث المستخدم بنجاح');
    } else {
      // create mode → POST to store route
      form.setAttribute('action', "<?php echo e(route('admin.users.store')); ?>");
      var m = form.querySelector('input[name="_method"]');
      if (m) m.remove();
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة مستخدم جديد';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30';
        badge.textContent = 'جديد';
      }
      form.setAttribute('data-success-message', 'تم حفظ المستخدم بنجاح');
    }
    // re-render icons (we injected new <i data-lucide>)
    if (window.lucide) window.lucide.createIcons();
  });

  // ===== User details modal: dynamic content + action buttons =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'user-details-modal') return;
    var data = e.detail.data || {};
    var uuid = data.uuid || '';

    // 1. Carry uuid in hidden input (for toggle/edit button)
    var uuidHidden = document.getElementById('user-details-uuid-hidden');
    if (uuidHidden) uuidHidden.value = uuid;

    // 2. Avatar initial (first letter of name)
    var nameVal = (data.name || '').trim();
    var avatarInitial = nameVal ? nameVal.charAt(0) : 'U';
    var avatarSpan = modal.querySelector('.w-24.h-24 span[data-preview="name"]');
    if (avatarSpan) avatarSpan.textContent = avatarInitial;

    // 3. Approval badge on avatar (✓ emerald معتمد / ⏳ amber قيد المراجعة)
    var approved = !!(data.is_approved == 1 || data.is_approved === true || data.is_approved === '1');
    var approvalAvatarBadge = document.getElementById('user-details-approval-badge');
    if (approvalAvatarBadge) {
      approvalAvatarBadge.className = 'absolute -bottom-1 -left-1 w-7 h-7 border-2 border-white rounded-xl flex items-center justify-center text-white shadow ' +
        (approved ? 'bg-emerald-500' : 'bg-amber-500');
      approvalAvatarBadge.innerHTML = approved
        ? '<i data-lucide="check" class="w-4 h-4 stroke-[3]"></i>'
        : '<i data-lucide="hourglass" class="w-4 h-4"></i>';
      approvalAvatarBadge.title = approved ? 'حساب معتمد' : 'قيد المراجعة';
    }

    // 4. Approval text in status card
    var approvalTextEl = document.getElementById('user-details-approval-text');
    if (approvalTextEl) {
      approvalTextEl.className = 'inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold border ' +
        (approved ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200');
      approvalTextEl.innerHTML = (approved
        ? '<i data-lucide="check" class="w-3 h-3"></i><span>حساب معتمد</span>'
        : '<i data-lucide="hourglass" class="w-3 h-3"></i><span>قيد المراجعة</span>');
    }

    // 5. Phone verification badge
    var phoneVerified = !!(data.is_phone_verified == 1 || data.is_phone_verified === true);
    var phoneBadge = document.getElementById('user-details-phone-badge');
    if (phoneBadge) {
      phoneBadge.className = 'inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold border ' +
        (phoneVerified ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-amber-50 text-amber-700 border-amber-200');
      phoneBadge.innerHTML = (phoneVerified
        ? '<i data-lucide="phone" class="w-3 h-3"></i><span>مفعل ومؤكد</span>'
        : '<i data-lucide="phone" class="w-3 h-3"></i><span>غير مفعل</span>');
    }

    // 6. Activation status badge (نشط / موقوف / ...)
    var statusCode = (data.status_code || '').toLowerCase();
    var isSuspended = (statusCode === 'suspended' || statusCode === 'inactive' || (data.status_name || '').indexOf('موقوف') !== -1);
    var activationBadge = document.getElementById('user-details-activation-badge');
    if (activationBadge) {
      activationBadge.className = 'inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold border ' +
        (isSuspended ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200');
      activationBadge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full ' + (isSuspended ? 'bg-rose-500' : 'bg-emerald-500') + '"></span>' +
        '<span>' + (data.status_name || '—') + '</span>';
    }

    // 7. Action buttons visibility + URLs
    var isTrashed = !!(data.is_trashed == 1 || data.is_trashed === true);
    var approveForm = document.getElementById('user-details-approve-form');
    var verifyPhoneForm = document.getElementById('user-details-verify-phone-form');
    var toggleLabel = document.getElementById('user-details-toggle-label');
    var toggleBtn = document.getElementById('user-details-toggle-btn');
    var restoreBtn = document.getElementById('user-details-restore-btn');
    var deleteBtn = document.getElementById('user-details-delete-btn');

    // Hide approve / verify / toggle actions for trashed users (no point)
    if (isTrashed) {
      if (approveForm) approveForm.classList.add('hidden');
      if (verifyPhoneForm) verifyPhoneForm.classList.add('hidden');
      if (toggleBtn) toggleBtn.classList.add('hidden');
      // Show restore button for trashed users
      if (restoreBtn) restoreBtn.classList.remove('hidden');
    } else {
      if (toggleBtn) toggleBtn.classList.remove('hidden');
      if (restoreBtn) restoreBtn.classList.add('hidden');

      // 7a. Approve form (only if NOT approved)
      if (approveForm) {
        if (!approved && uuid) {
          approveForm.classList.remove('hidden');
          approveForm.setAttribute('action', "<?php echo e(route('admin.users.approve', '__UUID__')); ?>".replace('__UUID__', uuid));
        } else {
          approveForm.classList.add('hidden');
        }
      }

      // 7b. Verify phone form (only if NOT verified)
      if (verifyPhoneForm) {
        if (!phoneVerified && uuid) {
          verifyPhoneForm.classList.remove('hidden');
          verifyPhoneForm.setAttribute('action', "<?php echo e(route('admin.users.verify-phone', '__UUID__')); ?>".replace('__UUID__', uuid));
        } else {
          verifyPhoneForm.classList.add('hidden');
        }
      }

      // 7c. Toggle button label (إيقاف if active / استعادة if suspended)
      if (toggleLabel && toggleBtn) {
        // remove old listeners by cloning
        var newToggleBtn = toggleBtn.cloneNode(true);
        toggleBtn.parentNode.replaceChild(newToggleBtn, toggleBtn);
        var toggleLabel2 = newToggleBtn.querySelector('#user-details-toggle-label');
        if (isSuspended) {
          if (toggleLabel2) toggleLabel2.textContent = 'استعادة وتفعيل الحساب';
          newToggleBtn.className = 'w-full py-2.5 text-xs font-bold rounded-xl border flex items-center justify-center gap-1.5 transition-all bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100';
        } else {
          if (toggleLabel2) toggleLabel2.textContent = 'إيقاف/تجميد الحساب';
          newToggleBtn.className = 'w-full py-2.5 text-xs font-bold rounded-xl border flex items-center justify-center gap-1.5 transition-all bg-rose-50 border-rose-200 text-rose-700 hover:bg-rose-100';
        }
        // Toggle button opens the edit modal so admin can change status
        newToggleBtn.addEventListener('click', function() {
          if (window.Aracari) {
            window.Aracari.openModal('user-form-modal', data);
          }
        });
      }
    }

    // 7d. Restore button → direct AJAX POST (only for trashed users)
    if (restoreBtn) {
      var newRestoreBtn = restoreBtn.cloneNode(true);
      restoreBtn.parentNode.replaceChild(newRestoreBtn, restoreBtn);
      if (isTrashed && uuid) {
        var restoreUrl = "<?php echo e(route('admin.users.restore', '__UUID__')); ?>".replace('__UUID__', uuid);
        newRestoreBtn.addEventListener('click', function() {
          var btn = newRestoreBtn;
          var origHtml = btn.innerHTML;
          btn.disabled = true;
          btn.innerHTML = '<svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> جارٍ الاستعادة...';
          var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
          fetch(restoreUrl, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': token,
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
          })
            .then(function(res) {
              var ct = res.headers.get('content-type') || '';
              if (ct.indexOf('application/json') !== -1) return res.json();
              return { success: true, message: 'تمت الاستعادة بنجاح' };
            })
            .then(function(j) {
              btn.disabled = false;
              btn.innerHTML = origHtml;
              if (window.lucide) window.lucide.createIcons();
              if (j && j.success === false) {
                if (window.Aracari) window.Aracari.toast(j.message || 'تعذرت الاستعادة', 'error');
                return;
              }
              if (window.Aracari) window.Aracari.toast(j && j.message ? j.message : 'تمت الاستعادة بنجاح', 'success');
              // Close details modal and refresh
              if (window.Aracari) window.Aracari.closeModal('user-details-modal');
              if (document.getElementById('aracari-refreshable')) {
                if (window.Aracari) window.Aracari.refreshTableCard();
              } else {
                setTimeout(function() { window.location.reload(); }, 600);
              }
            })
            .catch(function(err) {
              btn.disabled = false;
              btn.innerHTML = origHtml;
              if (window.lucide) window.lucide.createIcons();
              if (window.Aracari) window.Aracari.toast('حدث خطأ أثناء الاستعادة', 'error');
            });
        });
      } else {
        newRestoreBtn.classList.add('hidden');
      }
    }

    // 7e. Delete button → open global delete-confirm modal
    if (deleteBtn) {
      // remove old listeners by cloning
      var newDeleteBtn = deleteBtn.cloneNode(true);
      deleteBtn.parentNode.replaceChild(newDeleteBtn, deleteBtn);
      if (uuid) {
        var deleteUrl = "<?php echo e(route('admin.users.destroy', '__UUID__')); ?>".replace('__UUID__', uuid);
        newDeleteBtn.addEventListener('click', function() {
          if (window.Aracari && window.Aracari.openDeleteConfirm) {
            window.Aracari.openDeleteConfirm(deleteUrl, data.name || 'هذا المستخدم');
          }
        });
      }
    }

    // 8. Company section visibility (only for providers)
    var isProvider = !!(data.is_provider == 1 || data.is_provider === true);
    var companySection = document.getElementById('user-details-company-section');
    if (companySection) {
      if (isProvider) {
        companySection.classList.remove('hidden');
      } else {
        companySection.classList.add('hidden');
      }
    }

    // 9. Website field visibility (only if website is non-empty)
    var websiteWrap = document.getElementById('user-details-website-wrap');
    if (websiteWrap) {
      if (data.website && String(data.website).trim()) {
        websiteWrap.classList.remove('hidden');
      } else {
        websiteWrap.classList.add('hidden');
      }
    }

    // re-render lucide icons (we injected new <i data-lucide> elements)
    if (window.lucide) window.lucide.createIcons();
  });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/admin/users/index.blade.php ENDPATH**/ ?>