<?php $__env->startSection('title', 'أركاري | سياسات الإلغاء'); ?>
<?php $__env->startSection('header_title', 'سياسات الإلغاء والشروط'); ?>

<?php $__env->startSection('content'); ?>

<?php
    // الإحصائيات
    $totalPolicies   = $stats['total'];
    $cancellationPolicies = $stats['cancellation'];
    $activeRules     = $stats['active_rules'];

    // خريطة أنواع السياسات
    $typeMap = [
        'cancellation' => ['label' => 'إلغاء',    'bg' => 'bg-rose-50 text-rose-700 border-rose-200',        'dot' => 'bg-rose-500'],
        'booking'      => ['label' => 'حجز',     'bg' => 'bg-blue-50 text-blue-700 border-blue-200',        'dot' => 'bg-blue-500'],
        'other'        => ['label' => 'أخرى',    'bg' => 'bg-slate-100 text-slate-600 border-slate-200',    'dot' => 'bg-slate-400'],
    ];

    // التحقق من الصلاحيات
    $currentUser = auth()->user();
    $providerId = $currentUser?->user_parent ?: $currentUser?->id;
    $canCreate = $currentUser && $currentUser->hasPermission('provider:policies:create');
    $canEdit   = $currentUser && $currentUser->hasPermission('provider:policies:edit');
    $canDelete = $currentUser && $currentUser->hasPermission('provider:policies:delete');
?>

<div class="space-y-6 font-cairo text-right" dir="rtl">

  
  <?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['icon' => 'shield-check','title' => 'السياسات','count' => $policies->total(),'countLabel' => 'سياسة','description' => 'إنشاء وإدارة سياسات الإلغاء والشروط لرحلاتك','descriptionIcon' => 'shield-check']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'shield-check','title' => 'السياسات','count' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($policies->total()),'countLabel' => 'سياسة','description' => 'إنشاء وإدارة سياسات الإلغاء والشروط لرحلاتك','descriptionIcon' => 'shield-check']); ?>
    <?php if($canCreate): ?>
      <button
          type="button"
          data-create-modal="policy-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إنشاء سياسة جديدة
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

  
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 lg:gap-6">
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $totalPolicies,'label' => 'إجمالي السياسات','icon' => 'shield-check','variant' => 'slate','badge' => 'سياسة','badgeIcon' => 'shield-check']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalPolicies),'label' => 'إجمالي السياسات','icon' => 'shield-check','variant' => 'slate','badge' => 'سياسة','badgeIcon' => 'shield-check']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $cancellationPolicies,'label' => 'سياسات الإلغاء','icon' => 'ban','variant' => 'rose','badge' => 'إلغاء','badgeIcon' => 'ban']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($cancellationPolicies),'label' => 'سياسات الإلغاء','icon' => 'ban','variant' => 'rose','badge' => 'إلغاء','badgeIcon' => 'ban']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $activeRules,'label' => 'قواعد نشطة','icon' => 'list-checks','variant' => 'amber','badge' => 'قاعدة','badgeIcon' => 'list-checks']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($activeRules),'label' => 'قواعد نشطة','icon' => 'list-checks','variant' => 'amber','badge' => 'قاعدة','badgeIcon' => 'list-checks']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.data-table-card','data' => ['searchable' => true,'searchMode' => 'server','searchPlaceholder' => 'ابحث عن سياسة...','footerInfo' => 'عرض ' . $policies->count() . ' من أصل ' . $policies->total() . ' سياسة']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('data-table-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['searchable' => true,'searchMode' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('server'),'searchPlaceholder' => 'ابحث عن سياسة...','footerInfo' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('عرض ' . $policies->count() . ' من أصل ' . $policies->total() . ' سياسة')]); ?>
     <?php $__env->slot('thead', null, []); ?> 
      <th class="px-5 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الاسم</th>
      <th class="px-5 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">النوع</th>
      <th class="px-5 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">القواعد</th>
      <th class="px-5 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الرحلات</th>
      <th class="px-5 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">المصدر</th>
      <th class="px-5 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
     <?php $__env->endSlot(); ?>

     <?php $__env->slot('rows', null, []); ?> 
      <?php $__empty_1 = true; $__currentLoopData = $policies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $policy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $typeInfo = $typeMap[$policy->type] ?? $typeMap['other'];
          $isGlobal = $policy->created_by === null || $policy->created_by !== $providerId;
          $isOwner  = $policy->created_by !== null && ($policy->created_by === $currentUser->id || $policy->created_by === $providerId);
        ?>
        <tr class="hover:bg-slate-50/50 transition-colors group <?php echo e($isGlobal ? 'opacity-75' : ''); ?>">

          
          <td data-searchable class="px-5 py-4">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-lg <?php echo e($isGlobal ? 'bg-slate-100' : 'bg-gradient-to-br from-teal-500/20 to-teal-600/10 border border-teal-100'); ?> flex items-center justify-center flex-shrink-0">
                <i data-lucide="<?php echo e($isGlobal ? 'globe' : 'shield-check'); ?>" class="w-4 h-4 <?php echo e($isGlobal ? 'text-slate-400' : 'text-teal-600'); ?>"></i>
              </div>
              <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                  <span class="font-bold text-slate-800 text-sm truncate max-w-[180px]"><?php echo e($policy->name); ?></span>
                  <?php if($isGlobal): ?>
                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 border border-slate-200">عامة</span>
                  <?php else: ?>
                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-teal-50 text-teal-600 border border-teal-200">خاصة</span>
                  <?php endif; ?>
                </div>
                <?php if($policy->description): ?>
                  <div class="text-[10px] text-slate-400 truncate max-w-[200px] mt-0.5"><?php echo e($policy->description); ?></div>
                <?php endif; ?>
              </div>
            </div>
          </td>

          
          <td class="px-5 py-4 text-center">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold <?php echo e($typeInfo['bg']); ?> border">
              <span class="w-1.5 h-1.5 rounded-full <?php echo e($typeInfo['dot']); ?>"></span>
              <?php echo e($typeInfo['label']); ?>

            </span>
          </td>

          
          <td class="px-5 py-4 text-center">
            <?php if($policy->rules->count() > 0): ?>
              <span class="inline-flex items-center gap-1 text-xs font-bold text-slate-700">
                <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-700 border border-amber-100 flex items-center justify-center text-[11px] font-bold">
                  <?php echo e($policy->rules->count()); ?>

                </span>
                قاعدة
              </span>
            <?php else: ?>
              <span class="text-[11px] text-slate-400">—</span>
            <?php endif; ?>
          </td>

          
          <td class="px-5 py-4 text-center">
            <span class="inline-flex items-center gap-1 text-xs font-bold text-slate-700">
              <span class="w-7 h-7 rounded-lg bg-blue-50 text-blue-700 border border-blue-100 flex items-center justify-center text-[11px] font-bold">
                <?php echo e($policy->trips_count ?? 0); ?>

              </span>
              رحلة
            </span>
          </td>

          
          <td class="px-5 py-4 text-center">
            <span class="text-[11px] text-slate-500">
              <?php echo e($policy->creator?->name ?? 'النظام'); ?>

            </span>
          </td>

          
          <td class="px-5 py-4 text-center">
            <div class="flex items-center justify-center gap-1.5">
              
              <?php if (isset($component)) { $__componentOriginald4c6978101b1c254eb70511d3c21c03f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald4c6978101b1c254eb70511d3c21c03f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.action-button','data' => ['variant' => 'view','dataViewModal' => 'policy-details-modal','dataViewJson' => ''.e(json_encode(['uuid' => $policy->uuid])).'','ariaLabel' => 'تفاصيل ' . $policy->name]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('action-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'view','data-view-modal' => 'policy-details-modal','data-view-json' => ''.e(json_encode(['uuid' => $policy->uuid])).'','ariaLabel' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('تفاصيل ' . $policy->name)]); ?>
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
              
              <?php if($canEdit && $isOwner): ?>
                <?php if (isset($component)) { $__componentOriginald4c6978101b1c254eb70511d3c21c03f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald4c6978101b1c254eb70511d3c21c03f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.action-button','data' => ['variant' => 'edit','dataEditModal' => 'policy-form-modal','dataEditJson' => ''.e(json_encode([
                        'uuid'        => $policy->uuid,
                        'name'        => $policy->name,
                        'description' => $policy->description,
                        'type'        => $policy->type,
                        'rules'       => $policy->rules->map(fn($r) => ['id' => $r->id, 'from_hours' => $r->from_hours, 'to_hours' => $r->to_hours, 'fee_percentage' => $r->fee_percentage]),
                    ], JSON_UNESCAPED_UNICODE)).'','ariaLabel' => 'تعديل ' . $policy->name]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('action-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'edit','data-edit-modal' => 'policy-form-modal','data-edit-json' => ''.e(json_encode([
                        'uuid'        => $policy->uuid,
                        'name'        => $policy->name,
                        'description' => $policy->description,
                        'type'        => $policy->type,
                        'rules'       => $policy->rules->map(fn($r) => ['id' => $r->id, 'from_hours' => $r->from_hours, 'to_hours' => $r->to_hours, 'fee_percentage' => $r->fee_percentage]),
                    ], JSON_UNESCAPED_UNICODE)).'','ariaLabel' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('تعديل ' . $policy->name)]); ?>
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
              
              <?php if($canDelete && $isOwner): ?>
                <?php if (isset($component)) { $__componentOriginald4c6978101b1c254eb70511d3c21c03f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald4c6978101b1c254eb70511d3c21c03f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.action-button','data' => ['variant' => 'delete','dataDeleteUrl' => ''.e(route('provider.policies.destroy', $policy->uuid)).'','dataDeleteLabel' => ''.e($policy->name).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('action-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'delete','data-delete-url' => ''.e(route('provider.policies.destroy', $policy->uuid)).'','data-delete-label' => ''.e($policy->name).'']); ?>
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
            </div>
          </td>
        </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <?php if (isset($component)) { $__componentOriginal074a021b9d42f490272b5eefda63257c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal074a021b9d42f490272b5eefda63257c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty-state','data' => ['icon' => 'shield-check','title' => request('search') ? 'لا توجد نتائج مطابقة' : 'لا توجد سياسات','message' => request('search') ? 'جرّب تعديل كلمات البحث' : 'لم تقم بإنشاء أي سياسة بعد. أنشئ سياسة إلغاء خاصة برحلاتك','colspan' => 6]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'shield-check','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('search') ? 'لا توجد نتائج مطابقة' : 'لا توجد سياسات'),'message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('search') ? 'جرّب تعديل كلمات البحث' : 'لم تقم بإنشاء أي سياسة بعد. أنشئ سياسة إلغاء خاصة برحلاتك'),'colspan' => 6]); ?>
          <?php if(!request('search') && $canCreate): ?>
            <button
                type="button"
                data-create-modal="policy-form-modal"
                class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
            >
              <i data-lucide="plus" class="w-4 h-4"></i>
              إنشاء سياسة جديدة
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pagination','data' => ['paginator' => $policies]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pagination'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($policies)]); ?>
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
<?php if (isset($component)) { $__componentOriginaldf85e6552b95df616d59caebcbf2473d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldf85e6552b95df616d59caebcbf2473d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form-modal','data' => ['id' => 'policy-form-modal','title' => 'إنشاء سياسة جديدة','variant' => 'create','action' => route('provider.policies.store'),'method' => 'POST','successMessage' => 'تم إنشاء السياسة بنجاح']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form-modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'policy-form-modal','title' => 'إنشاء سياسة جديدة','variant' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('create'),'action' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('provider.policies.store')),'method' => 'POST','successMessage' => 'تم إنشاء السياسة بنجاح']); ?>

  
  <?php if (isset($component)) { $__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form-field','data' => ['name' => 'name','label' => 'اسم السياسة','icon' => 'shield-check','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'name','label' => 'اسم السياسة','icon' => 'shield-check','required' => true]); ?>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="shield-check" class="w-4 h-4"></i>
      </div>
      <input
          type="text"
          name="name"
          id="policy_name"
          required
          placeholder="سياسة إلغاء مرنة"
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

  
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="space-y-1.5">
      <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
        <i data-lucide="tag" class="w-3.5 h-3.5"></i>
        نوع السياسة <span class="text-rose-500">*</span>
      </label>
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="tag" class="w-4 h-4"></i>
        </div>
        <select
            name="type"
            id="policy_type"
            required
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-9 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none cursor-pointer"
        >
          <option value="cancellation">إلغاء</option>
          <option value="booking">حجز</option>
          <option value="other">أخرى</option>
        </select>
        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
          <i data-lucide="chevron-down" class="w-4 h-4"></i>
        </div>
      </div>
      <p class="text-[11px] text-rose-600 font-bold" data-error-for="type"></p>
    </div>

    <div class="space-y-1.5">
      <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
        <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
        الوصف
      </label>
      <input
          type="text"
          name="description"
          id="policy_description"
          placeholder="وصف مختصر للسياسة..."
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
      >
    </div>
  </div>

  
  <div id="policy-rules-section">
    <div class="flex items-center justify-between mb-3">
      <h4 class="font-bold text-xs text-[#115128] border-b border-[#eeeef0] pb-2 flex items-center gap-1.5">
        <i data-lucide="list-checks" class="w-3.5 h-3.5"></i>
        قواعد الإلغاء
      </h4>
      <button
          type="button"
          id="policy-add-rule-btn"
          class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 bg-emerald-50 border border-emerald-200 hover:bg-emerald-500 hover:text-white hover:border-emerald-500 transition-all px-2.5 py-1 rounded-lg"
      >
        <i data-lucide="plus" class="w-3 h-3"></i>
        إضافة قاعدة
      </button>
    </div>

    
    <div class="hidden sm:grid sm:grid-cols-[1fr_1fr_1fr_auto] gap-2 mb-2 px-1">
      <span class="text-[10px] font-bold text-slate-400">من (ساعة)</span>
      <span class="text-[10px] font-bold text-slate-400">إلى (ساعة)</span>
      <span class="text-[10px] font-bold text-slate-400">نسبة الرسوم %</span>
      <span class="w-8"></span>
    </div>

    <div id="policy-rules-list" class="space-y-2 max-h-64 overflow-y-auto pr-1">
      
    </div>

    <p id="policy-rules-empty" class="text-[11px] text-slate-400 text-center py-4">اضغط "إضافة قاعدة" لتحديد شروط الإلغاء</p>
  </div>

  
  <div class="bg-teal-50/70 border border-teal-200 rounded-xl p-3">
    <div class="flex items-start gap-2.5">
      <i data-lucide="info" class="w-4 h-4 text-teal-500 flex-shrink-0 mt-0.5"></i>
      <span class="text-[11px] text-teal-600">
        سياسات الإلغاء تحدد نسبة الرسوم المخصومة عند إلغاء الحجز بناءً على الوقت المتبقي قبل موعد الرحلة.
      </span>
    </div>
  </div>

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


<?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['id' => 'policy-details-modal','title' => 'تفاصيل السياسة','maxWidth' => 'max-w-lg']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'policy-details-modal','title' => 'تفاصيل السياسة','maxWidth' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('max-w-lg')]); ?>
  <div id="policy-details-content" class="space-y-4">
    <p class="text-sm text-slate-400 text-center py-6">جارٍ التحميل...</p>
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

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
  // ===== متغيرات قواعد الإلغاء =====
  var policyRules = [];
  var policyRuleCounter = 0;

  // ===== إظهار/إخفاء قسم القواعد حسب النوع =====
  function policyToggleRules() {
    var typeSelect = document.getElementById('policy_type');
    var rulesSection = document.getElementById('policy-rules-section');
    if (typeSelect.value === 'cancellation') {
      rulesSection.classList.remove('hidden');
    } else {
      rulesSection.classList.add('hidden');
    }
  }
  document.getElementById('policy_type').addEventListener('change', policyToggleRules);

  // ===== إضافة/حذف/عرض قواعد الإلغاء =====
  function policyRenderRules() {
    var list = document.getElementById('policy-rules-list');
    var empty = document.getElementById('policy-rules-empty');
    if (policyRules.length === 0) {
      list.innerHTML = '';
      if (empty) empty.style.display = '';
      return;
    }
    if (empty) empty.style.display = 'none';
    var html = '';
    for (var i = 0; i < policyRules.length; i++) {
      var r = policyRules[i];
      html += '<div class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_1fr_auto] gap-2 items-center p-3 bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl">' +
        '<div class="relative group"><div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400"><i data-lucide="clock" class="w-3 h-3"></i></div>' +
        '<input type="number" name="rules[' + i + '][from_hours]" value="' + (r.from_hours || 0) + '" min="0" placeholder="0" dir="ltr" class="w-full bg-white border border-[#e2e2e5] rounded-lg pr-9 pl-2 py-2 text-[13px] font-bold outline-none focus:border-[#115128] focus:ring-2 focus:ring-teal-500/10 text-center" oninput="policyRules[' + i + '].from_hours = parseInt(this.value)||0"></div>' +
        '<div class="relative group"><div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400"><i data-lucide="clock" class="w-3 h-3"></i></div>' +
        '<input type="number" name="rules[' + i + '][to_hours]" value="' + (r.to_hours || 0) + '" min="0" placeholder="24" dir="ltr" class="w-full bg-white border border-[#e2e2e5] rounded-lg pr-9 pl-2 py-2 text-[13px] font-bold outline-none focus:border-[#115128] focus:ring-2 focus:ring-teal-500/10 text-center" oninput="policyRules[' + i + '].to_hours = parseInt(this.value)||0"></div>' +
        '<div class="relative group"><div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400"><i data-lucide="percent" class="w-3 h-3"></i></div>' +
        '<input type="number" name="rules[' + i + '][fee_percentage]" value="' + (r.fee_percentage || 0) + '" min="0" max="100" step="0.1" placeholder="25" dir="ltr" class="w-full bg-white border border-[#e2e2e5] rounded-lg pr-9 pl-2 py-2 text-[13px] font-bold outline-none focus:border-[#115128] focus:ring-2 focus:ring-teal-500/10 text-center" oninput="policyRules[' + i + '].fee_percentage = parseFloat(this.value)||0"></div>' +
        '<button type="button" onclick="policyRemoveRule(' + i + ')" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white border border-rose-200 hover:border-rose-500 transition-all flex items-center justify-center flex-shrink-0" title="حذف القاعدة"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>' +
        '</div>';
    }
    list.innerHTML = html;
    if (window.lucide) window.lucide.createIcons();
  }

  function policyAddRule(fromH, toH, fee) {
    policyRules.push({
      id: null,
      from_hours: fromH !== undefined ? fromH : 0,
      to_hours: toH !== undefined ? toH : 24,
      fee_percentage: fee !== undefined ? fee : 100,
    });
    policyRenderRules();
  }

  function policyRemoveRule(index) {
    policyRules.splice(index, 1);
    policyRenderRules();
  }

  document.getElementById('policy-add-rule-btn').addEventListener('click', function() {
    policyAddRule(0, 24, 100);
  });

  // ===== تبديل الإنشاء/التعديل + ملء الحقول =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    var data = e.detail.data || {};

    // ===== مودال التفاصيل =====
    if (modal.id === 'policy-details-modal') {
      var content = document.getElementById('policy-details-content');
      content.innerHTML = '<p class="text-sm text-slate-400 text-center py-6"><svg class="animate-spin w-5 h-5 inline-block mr-2" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> جارٍ التحميل...</p>';
      fetch('/provider/policies/' + data.uuid + '/json?XTransformPort=3000', { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(res) {
          if (!res.success || !res.data) { content.innerHTML = '<p class="text-sm text-rose-500 text-center py-6">فشل تحميل التفاصيل</p>'; return; }
          var p = res.data;
          var typeLabels = { cancellation: 'إلغاء', booking: 'حجز', other: 'أخرى' };
          var typeColors = { cancellation: 'bg-rose-50 text-rose-700 border-rose-200', booking: 'bg-blue-50 text-blue-700 border-blue-200', other: 'bg-slate-100 text-slate-600 border-slate-200' };
          var html = '<div class="space-y-4">' +
            '<div><p class="text-[10px] text-slate-400 mb-1">الاسم</p><p class="font-bold text-sm text-slate-800">' + (p.name || '') + '</p></div>';
          if (p.description) html += '<div><p class="text-[10px] text-slate-400 mb-1">الوصف</p><p class="text-xs text-slate-600">' + p.description + '</p></div>';
          html += '<div><p class="text-[10px] text-slate-400 mb-1">النوع</p><span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border ' + (typeColors[p.type] || '') + '">' + (typeLabels[p.type] || p.type) + '</span></div>';
          if (p.rules && p.rules.length > 0) {
            html += '<div><p class="text-[10px] font-bold text-[#115128] mb-2 flex items-center gap-1"><i data-lucide="list-checks" class="w-3 h-3"></i> قواعد الإلغاء</p>';
            html += '<div class="space-y-2">';
            for (var i = 0; i < p.rules.length; i++) {
              var rule = p.rules[i];
              var feeColor = rule.fee_percentage >= 80 ? 'text-rose-600' : (rule.fee_percentage >= 50 ? 'text-amber-600' : 'text-emerald-600');
              html += '<div class="flex items-center justify-between bg-[#f3f3f6] rounded-xl px-4 py-3 border border-[#e2e2e5]">' +
                '<div class="flex items-center gap-3 text-xs"><span class="text-slate-500">من <strong class="text-slate-700">' + rule.from_hours + 'س</strong></span><i data-lucide="arrow-left" class="w-3 h-3 text-slate-300"></i><span class="text-slate-500">إلى <strong class="text-slate-700">' + rule.to_hours + 'س</strong></span></div>' +
                '<span class="font-bold text-sm ' + feeColor + '">' + rule.fee_percentage + '%</span>' +
                '</div>';
            }
            html += '</div></div>';
          }
          if (p.trips_count) html += '<div class="text-[11px] text-slate-400">مرتبطة بـ <strong class="text-slate-600">' + p.trips_count + '</strong> رحلة</div>';
          html += '</div>';
          content.innerHTML = html;
          if (window.lucide) window.lucide.createIcons();
        })
        .catch(function() { content.innerHTML = '<p class="text-sm text-rose-500 text-center py-6">خطأ في الاتصال</p>'; });
      return;
    }

    // ===== مودال النموذج =====
    if (modal.id !== 'policy-form-modal') return;
    var form = modal.querySelector('form');
    var isEdit = !!(data.uuid);

    // تبديل رابط + طريقة + شكل المودال
    if (isEdit) {
      var updateUrl = "<?php echo e(route('provider.policies.update', '__UUID__')); ?>".replace('__UUID__', data.uuid);
      form.setAttribute('action', updateUrl);
      var existingMethod = form.querySelector('input[name="_method"]');
      if (existingMethod) existingMethod.value = 'PUT';
      else { var m = document.createElement('input'); m.type = 'hidden'; m.name = '_method'; m.value = 'PUT'; form.appendChild(m); }

      var banner = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (banner) banner.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';
      var titleEl = modal.querySelector('.aracari-modal-panel h2');
      if (titleEl) titleEl.innerHTML = '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل السياسة';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) { badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30'; badge.textContent = 'تحديث'; }
      var subtitle = modal.querySelector('.aracari-modal-panel p');
      if (subtitle) subtitle.textContent = 'تحديث بيانات السياسة';
      form.setAttribute('data-success-message', 'تم تحديث السياسة بنجاح');
    } else {
      form.setAttribute('action', "<?php echo e(route('provider.policies.store')); ?>");
      var m = form.querySelector('input[name="_method"]');
      if (m) m.remove();

      var banner = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (banner) banner.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
      var titleEl = modal.querySelector('.aracari-modal-panel h2');
      if (titleEl) titleEl.innerHTML = '<i data-lucide="plus-circle" class="w-5 h-5"></i> إنشاء سياسة جديدة';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) { badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30'; badge.textContent = 'جديد'; }
      var subtitle = modal.querySelector('.aracari-modal-panel p');
      if (subtitle) subtitle.textContent = 'أنشئ سياسة إلغاء جديدة لرحلاتك';
      form.setAttribute('data-success-message', 'تم إنشاء السياسة بنجاح');
    }

    // ملء الحقول
    var nameEl = document.getElementById('policy_name');
    var typeEl = document.getElementById('policy_type');
    var descEl = document.getElementById('policy_description');
    if (nameEl) nameEl.value = isEdit ? (data.name || '') : '';
    if (typeEl) typeEl.value = isEdit ? (data.type || 'cancellation') : 'cancellation';
    if (descEl) descEl.value = isEdit ? (data.description || '') : '';

    // ملء القواعد
    policyRules = [];
    policyRuleCounter = 0;
    if (isEdit && Array.isArray(data.rules)) {
      for (var i = 0; i < data.rules.length; i++) {
        policyRules.push({
          id: data.rules[i].id || null,
          from_hours: data.rules[i].from_hours || 0,
          to_hours: data.rules[i].to_hours || 0,
          fee_percentage: data.rules[i].fee_percentage || 0,
        });
      }
    }
    policyToggleRules();
    policyRenderRules();

    if (window.lucide) window.lucide.createIcons();
  });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.provider', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/provider/policies/index.blade.php ENDPATH**/ ?>