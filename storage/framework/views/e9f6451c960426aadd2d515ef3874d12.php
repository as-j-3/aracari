<?php $__env->startSection('title', 'أركاري | سجل التدقيق'); ?>
<?php $__env->startSection('header_title', 'سجل التدقيق'); ?>

<?php $__env->startSection('content'); ?>

<?php
    // Permission checks
    $user    = auth()->user();
    $canView = $user && $user->hasPermission('reports:audit');

    if (!$canView) {
        abort(403);
    }

    // Stats (computed from the full dataset, not just current page)
    $totalCount   = \App\Models\AuditLog::count();
    $createdCount = \App\Models\AuditLog::where('action', 'created')->count();
    $updatedCount = \App\Models\AuditLog::where('action', 'updated')->count();
    $deletedCount = \App\Models\AuditLog::where('action', 'deleted')->count();

    // Action → badge style mapping
    $actionStyles = [
        'created' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600', 'border' => 'border-emerald-200', 'dot' => 'bg-emerald-500', 'icon' => 'plus-circle', 'label' => 'إضافة'],
        'updated' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-600', 'border' => 'border-amber-200', 'dot' => 'bg-amber-500', 'icon' => 'pencil', 'label' => 'تعديل'],
        'deleted' => ['bg' => 'bg-rose-50', 'text' => 'text-rose-600', 'border' => 'border-rose-200', 'dot' => 'bg-rose-500', 'icon' => 'trash-2', 'label' => 'حذف'],
        'login'   => ['bg' => 'bg-blue-50', 'text' => 'text-blue-600', 'border' => 'border-blue-200', 'dot' => 'bg-blue-500', 'icon' => 'log-in', 'label' => 'تسجيل دخول'],
        'logout'  => ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'border' => 'border-slate-200', 'dot' => 'bg-slate-400', 'icon' => 'log-out', 'label' => 'تسجيل خروج'],
    ];
    $defaultActionStyle = ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'border' => 'border-slate-200', 'dot' => 'bg-slate-400', 'icon' => 'circle-dot', 'label' => 'أخرى'];

    $getActionStyle = function($action) use ($actionStyles, $defaultActionStyle) {
        return $actionStyles[$action] ?? $defaultActionStyle;
    };

    // Current filter values
    $currentUserId = request('user_id');
    $currentAction = request('action');
    $currentDateFrom = request('date_from');
    $currentDateTo   = request('date_to');

    // Build the URL preserving query params (for reset)
    $currentQueryParams = array_filter(request()->query(), function($v) { return $v !== null && $v !== ''; });
    $hasActiveFilters = count($currentQueryParams) > 0;
?>

<div class="space-y-6 font-cairo text-right" dir="rtl">

  
  <?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['icon' => 'history','title' => 'سجل التدقيق','count' => $auditLogs->total(),'countLabel' => 'عملية','description' => 'تتبع جميع العمليات والتغييرات التي تتم على بيانات النظام','descriptionIcon' => 'shield']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'history','title' => 'سجل التدقيق','count' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($auditLogs->total()),'countLabel' => 'عملية','description' => 'تتبع جميع العمليات والتغييرات التي تتم على بيانات النظام','descriptionIcon' => 'shield']); ?>
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

  
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $totalCount,'label' => 'إجمالي العمليات','icon' => 'activity','variant' => 'slate','badge' => 'في النظام','badgeIcon' => 'trending-up']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalCount),'label' => 'إجمالي العمليات','icon' => 'activity','variant' => 'slate','badge' => 'في النظام','badgeIcon' => 'trending-up']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $createdCount,'label' => 'عمليات الإضافة','icon' => 'plus-circle','variant' => 'emerald','badge' => 'created','badgeIcon' => 'plus-circle']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($createdCount),'label' => 'عمليات الإضافة','icon' => 'plus-circle','variant' => 'emerald','badge' => 'created','badgeIcon' => 'plus-circle']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $updatedCount,'label' => 'عمليات التعديل','icon' => 'pencil','variant' => 'amber','badge' => 'updated','badgeIcon' => 'pencil']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($updatedCount),'label' => 'عمليات التعديل','icon' => 'pencil','variant' => 'amber','badge' => 'updated','badgeIcon' => 'pencil']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $deletedCount,'label' => 'عمليات الحذف','icon' => 'trash-2','variant' => 'rose','badge' => 'deleted','badgeIcon' => 'trash-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($deletedCount),'label' => 'عمليات الحذف','icon' => 'trash-2','variant' => 'rose','badge' => 'deleted','badgeIcon' => 'trash-2']); ?>
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

  
  <div class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-3xl overflow-hidden shadow-xl shadow-slate-200/20">
    <form method="GET" action="<?php echo e(route('admin.reports.audit-logs')); ?>" id="audit-filter-form">
      <div class="p-5 border-b border-slate-200/70 bg-white/40 backdrop-blur">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <i data-lucide="filter" class="w-4 h-4 text-teal-600"></i>
            <h3 class="text-sm font-black text-slate-700">تصفية السجلات</h3>
          </div>
          <?php if($hasActiveFilters): ?>
            <a
                href="<?php echo e(route('admin.reports.audit-logs')); ?>"
                class="flex items-center gap-1.5 text-[11px] font-bold text-rose-500 hover:text-rose-600 transition-colors"
            >
              <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
              مسح الفلاتر
            </a>
          <?php endif; ?>
        </div>
      </div>
      <div class="p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          
          <div>
            <label class="block text-[11px] font-bold text-slate-500 mb-1.5">
              <i data-lucide="user" class="w-3 h-3 inline-block ml-1 text-slate-400"></i>
              المستخدم
            </label>
            <div class="relative group">
              <select
                  name="user_id"
                  class="w-full h-11 bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 pr-10 text-[13px] font-bold text-slate-700 focus:outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all appearance-none cursor-pointer"
              >
                <option value="">جميع المستخدمين</option>
                <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($u->id); ?>" <?php if($currentUserId == $u->id): echo 'selected'; endif; ?>><?php echo e($u->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
              <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none group-focus-within:text-[#115128] transition-colors"></i>
            </div>
          </div>

          
          <div>
            <label class="block text-[11px] font-bold text-slate-500 mb-1.5">
              <i data-lucide="zap" class="w-3 h-3 inline-block ml-1 text-slate-400"></i>
              العملية
            </label>
            <div class="relative group">
              <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
                <i data-lucide="zap" class="w-4 h-4"></i>
              </div>
              <input
                  type="text"
                  name="action"
                  value="<?php echo e($currentAction); ?>"
                  placeholder="مثال: created, updated..."
                  dir="ltr"
                  class="w-full h-11 bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 text-[13px] font-bold text-left placeholder:text-slate-400 focus:outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all"
              >
            </div>
          </div>

          
          <div>
            <label class="block text-[11px] font-bold text-slate-500 mb-1.5">
              <i data-lucide="calendar" class="w-3 h-3 inline-block ml-1 text-slate-400"></i>
              من تاريخ
            </label>
            <div class="relative group">
              <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
                <i data-lucide="calendar" class="w-4 h-4"></i>
              </div>
              <input
                  type="date"
                  name="date_from"
                  value="<?php echo e($currentDateFrom); ?>"
                  dir="ltr"
                  class="w-full h-11 bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 text-[13px] font-bold text-left text-slate-700 focus:outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all"
              >
            </div>
          </div>

          
          <div>
            <label class="block text-[11px] font-bold text-slate-500 mb-1.5">
              <i data-lucide="calendar-check" class="w-3 h-3 inline-block ml-1 text-slate-400"></i>
              إلى تاريخ
            </label>
            <div class="relative group">
              <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
                <i data-lucide="calendar-check" class="w-4 h-4"></i>
              </div>
              <input
                  type="date"
                  name="date_to"
                  value="<?php echo e($currentDateTo); ?>"
                  dir="ltr"
                  class="w-full h-11 bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 text-[13px] font-bold text-left text-slate-700 focus:outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all"
              >
            </div>
          </div>
        </div>

        
        <div class="mt-4 flex items-center gap-3">
          <button
              type="submit"
              class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
          >
            <i data-lucide="search" class="w-4 h-4"></i>
            بحث وتصفية
          </button>
          <?php if($hasActiveFilters): ?>
            <a
                href="<?php echo e(route('admin.reports.audit-logs')); ?>"
                class="px-5 py-2.5 bg-rose-50 border border-rose-200 text-rose-600 rounded-xl font-bold text-sm hover:bg-rose-500 hover:text-white hover:border-rose-500 transition-all duration-300 flex items-center gap-2"
            >
              <i data-lucide="refresh-cw" class="w-4 h-4"></i>
              إعادة تعيين
            </a>
          <?php endif; ?>
        </div>
      </div>
    </form>
  </div>

  
  <div id="aracari-refreshable">
  <?php if (isset($component)) { $__componentOriginal90c131072f947a512cfcd740e0e498fa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal90c131072f947a512cfcd740e0e498fa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.data-table-card','data' => ['searchable' => false,'footerInfo' => 'عرض ' . $auditLogs->count() . ' من أصل ' . $auditLogs->total() . ' سجل']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('data-table-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['searchable' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'footerInfo' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('عرض ' . $auditLogs->count() . ' من أصل ' . $auditLogs->total() . ' سجل')]); ?>
     <?php $__env->slot('thead', null, []); ?> 
      <tr>
        <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المستخدم</th>
        <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">العملية</th>
        <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الجدول</th>
        <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">المعرف</th>
        <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">البيانات</th>
        <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">المعرف الرقمي</th>
        <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الوقت</th>
      </tr>
     <?php $__env->endSlot(); ?>

     <?php $__env->slot('rows', null, []); ?> 
      <?php $__empty_1 = true; $__currentLoopData = $auditLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
            $style   = $getActionStyle($log->action);
            $userName = $log->performer?->name ?? 'نظام';
            $initial  = mb_substr($userName, 0, 1);
            $hasOldData  = $log->old_values && is_array($log->old_values) && count($log->old_values) > 0;
            $hasNewData  = $log->new_values && is_array($log->new_values) && count($log->new_values) > 0;
        ?>
        <tr class="hover:bg-slate-50/50 transition-colors group">

          
          <td class="px-5 py-3.5">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-sm shadow-teal-500/20 group-hover:scale-110 transition-transform duration-300">
                <?php echo e($initial); ?>

              </div>
              <div class="min-w-0">
                <p class="font-bold text-slate-800 text-sm truncate"><?php echo e($userName); ?></p>
                <p class="text-[11px] text-slate-400 font-mono" dir="ltr">ID: <?php echo e($log->user_id ?? '—'); ?></p>
              </div>
            </div>
          </td>

          
          <td class="px-5 py-3.5">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border <?php echo e($style['bg']); ?> <?php echo e($style['text']); ?> <?php echo e($style['border']); ?>">
              <span class="w-1.5 h-1.5 rounded-full <?php echo e($style['dot']); ?>"></span>
              <i data-lucide="<?php echo e($style['icon']); ?>" class="w-3 h-3"></i>
              <?php echo e($style['label']); ?>

            </span>
          </td>

          
          <td class="px-5 py-3.5">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-[11px] font-mono font-bold border border-slate-200">
              <i data-lucide="database" class="w-3 h-3 text-slate-400"></i>
              <?php echo e($log->table_name ?? '—'); ?>

            </span>
          </td>

          
          <td class="px-5 py-3.5 text-center">
            <?php if($log->record_id): ?>
              <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg bg-teal-50 text-teal-700 text-[11px] font-mono font-bold border border-teal-200">
                <?php echo e($log->record_id); ?>

              </span>
            <?php else: ?>
              <span class="text-slate-400 text-xs">—</span>
            <?php endif; ?>
          </td>

          
          <td class="px-5 py-3.5 text-center">
            <div class="flex items-center justify-center gap-1.5">
              <?php if($hasOldData): ?>
                <button
                    type="button"
                    onclick='openJsonModal("<?php echo e($log->action); ?>", "<?php echo e($log->table_name); ?>", "old", <?php echo e(json_encode($log->old_values, JSON_UNESCAPED_UNICODE)); ?>)'
                    class="w-8 h-8 rounded-lg bg-amber-50 border border-amber-200 text-amber-600 hover:bg-amber-100 transition-all flex items-center justify-center shadow-sm"
                    title="البيانات السابقة"
                    aria-label="عرض البيانات السابقة"
                >
                  <i data-lucide="file-minus" class="w-3.5 h-3.5"></i>
                </button>
              <?php endif; ?>
              <?php if($hasNewData): ?>
                <button
                    type="button"
                    onclick='openJsonModal("<?php echo e($log->action); ?>", "<?php echo e($log->table_name); ?>", "new", <?php echo e(json_encode($log->new_values, JSON_UNESCAPED_UNICODE)); ?>)'
                    class="w-8 h-8 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-600 hover:bg-emerald-100 transition-all flex items-center justify-center shadow-sm"
                    title="البيانات الجديدة"
                    aria-label="عرض البيانات الجديدة"
                >
                  <i data-lucide="file-plus" class="w-3.5 h-3.5"></i>
                </button>
              <?php endif; ?>
              <?php if(!$hasOldData && !$hasNewData): ?>
                <span class="text-slate-400 text-xs">—</span>
              <?php endif; ?>
            </div>
          </td>

          
          <td class="px-5 py-3.5 text-center">
            <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg bg-slate-900 text-white text-[11px] font-mono font-black shadow-sm">
              <i data-lucide="hash" class="w-3 h-3 text-teal-400 ml-1"></i>
              <?php echo e($log->id); ?>

            </span>
          </td>

          
          <td class="px-5 py-3.5">
            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700">
              <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0"></i>
              <span dir="ltr"><?php echo e($log->created_at?->format('Y-m-d H:i')); ?></span>
            </div>
          </td>
        </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <?php if (isset($component)) { $__componentOriginal074a021b9d42f490272b5eefda63257c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal074a021b9d42f490272b5eefda63257c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty-state','data' => ['icon' => 'history','title' => $hasActiveFilters ? 'لا توجد نتائج مطابقة' : 'لا توجد سجلات تدقيق','message' => $hasActiveFilters ? 'جرّب تعديل خيارات التصفية' : 'ستظهر عمليات التدقيق هنا فور تسجيل أي نشاط على النظام','colspan' => 7]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'history','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($hasActiveFilters ? 'لا توجد نتائج مطابقة' : 'لا توجد سجلات تدقيق'),'message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($hasActiveFilters ? 'جرّب تعديل خيارات التصفية' : 'ستظهر عمليات التدقيق هنا فور تسجيل أي نشاط على النظام'),'colspan' => 7]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pagination','data' => ['paginator' => $auditLogs]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pagination'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($auditLogs)]); ?>
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
<?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['id' => 'json-viewer-modal','title' => 'عرض البيانات','variant' => 'view','icon' => 'code','maxWidth' => 'max-w-2xl']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'json-viewer-modal','title' => 'عرض البيانات','variant' => 'view','icon' => 'code','maxWidth' => 'max-w-2xl']); ?>
  <div class="space-y-4">
    
    <div class="flex items-center gap-3 flex-wrap">
      <span id="json-modal-action-badge" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border bg-slate-100 text-slate-600 border-slate-200">
        <i data-lucide="zap" class="w-3 h-3"></i>
        <span id="json-modal-action-text"></span>
      </span>
      <span id="json-modal-table-badge" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-[11px] font-mono font-bold border border-slate-200">
        <i data-lucide="database" class="w-3 h-3 text-slate-400"></i>
        <span id="json-modal-table-text"></span>
      </span>
      <span id="json-modal-type-badge" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border">
        <span id="json-modal-type-text"></span>
      </span>
    </div>

    
    <div class="relative">
      <div class="absolute top-3 left-3 z-10">
        <button
            type="button"
            onclick="copyJsonFromModal()"
            class="px-3 py-1.5 bg-teal-50 border border-teal-200 text-teal-600 rounded-lg text-[10px] font-bold hover:bg-teal-100 transition-all flex items-center gap-1.5"
        >
          <i data-lucide="copy" class="w-3 h-3"></i>
          نسخ
        </button>
      </div>
      <pre id="json-modal-content" class="bg-slate-950 text-emerald-400 rounded-2xl p-5 pt-12 text-[12px] font-mono leading-relaxed overflow-x-auto max-h-96 overflow-y-auto scrollbar-thin" dir="ltr"></pre>
    </div>
  </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $attributes = $__attributesOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__attributesOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $component = $__componentOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__componentOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>

<?php $__env->startPush('scripts'); ?>
<script>
  /**
   * Open the JSON viewer modal with prettified data.
   */
  function openJsonModal(action, tableName, type, data) {
    // Set meta info
    document.getElementById('json-modal-action-text').textContent = action || '—';
    document.getElementById('json-modal-table-text').textContent = tableName || '—';

    const typeBadge = document.getElementById('json-modal-type-badge');
    const typeText  = document.getElementById('json-modal-type-text');

    if (type === 'old') {
      typeBadge.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border bg-amber-50 text-amber-600 border-amber-200';
      typeText.textContent = 'البيانات السابقة';
    } else {
      typeBadge.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border bg-emerald-50 text-emerald-600 border-emerald-200';
      typeText.textContent = 'البيانات الجديدة';
    }

    // Prettify and display
    const jsonStr = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
    document.getElementById('json-modal-content').textContent = jsonStr;

    // Store raw for copy
    document.getElementById('json-modal-content').dataset.raw = jsonStr;

    // Open modal
    const modal = document.getElementById('json-viewer-modal');
    if (modal && typeof window.openAracariModal === 'function') {
      window.openAracariModal('json-viewer-modal');
    } else if (modal) {
      modal.classList.remove('hidden');
      modal.setAttribute('aria-hidden', 'false');
      requestAnimationFrame(function() {
        var overlay = modal.querySelector('.aracari-modal-overlay');
        var panel   = modal.querySelector('.aracari-modal-panel');
        if (overlay) overlay.style.opacity = '1';
        if (panel)   { panel.style.opacity = '1'; panel.style.transform = 'scale(1) translateY(0)'; }
      });
    }

    // Re-init lucide icons inside modal
    if (typeof window.lucide !== 'undefined') {
      window.lucide.createIcons();
    }
  }

  /**
   * Copy JSON content to clipboard.
   */
  function copyJsonFromModal() {
    var content = document.getElementById('json-modal-content');
    if (!content) return;

    var text = content.textContent || content.dataset.raw || '';
    navigator.clipboard.writeText(text).then(function() {
      // Brief visual feedback
      var btn = content.parentElement.querySelector('button');
      if (btn) {
        var originalHtml = btn.innerHTML;
        btn.innerHTML = '<span style="display:inline-flex;align-items:center;gap:4px"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> تم النسخ</span>';
        setTimeout(function() { btn.innerHTML = originalHtml; }, 1500);
      }
    });
  }
</script>
<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/admin/audit_logs/index.blade.php ENDPATH**/ ?>