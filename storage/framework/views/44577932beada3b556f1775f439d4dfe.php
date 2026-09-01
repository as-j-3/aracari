<?php $__env->startSection('title', 'أركاري | الرحلات'); ?>
<?php $__env->startSection('header_title', 'الرحلات'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $currentUser = auth()->user();
    $canCreate = $currentUser && $currentUser->hasPermission('provider:branches:trips:create');
    $canEdit   = $currentUser && $currentUser->hasPermission('provider:branches:trips:edit');
    $canDelete = $currentUser && $currentUser->hasPermission('provider:branches:trips:delete');

    $trips->getCollection()->each(function($t) {
        $t->loadMissing([
            'departureCity', 'destinationCity', 'vehicle.vehicleType',
            'service.branch', 'tripStatus', 'typeOfTrip', 'levelTrip',
            'amenities', 'coupons', 'departureStation', 'arrivalStation',
            'waypoints.city', 'repeats.repeatedDays', 'policies.rules',
        ]);
    });
?>

<div class="space-y-6 font-cairo text-right" dir="rtl">

  
  <?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['icon' => 'bus','title' => 'رحلات المزود','count' => $stats['total'],'countLabel' => 'رحلة','description' => 'إدارة رحلات جميع الفروع','descriptionIcon' => 'route']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'bus','title' => 'رحلات المزود','count' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['total']),'countLabel' => 'رحلة','description' => 'إدارة رحلات جميع الفروع','descriptionIcon' => 'route']); ?>
    <?php if($canCreate && $selectedBranch): ?>
      <?php
        $selectedBranchModel = \App\Models\ProviderBranch::find($selectedBranch);
      ?>
      <button
          type="button"
          data-create-modal="trip-form-modal"
          data-branch-uuid="<?php echo e($selectedBranchModel?->uuid ?? ''); ?>"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إضافة رحلة
      </button>
    <?php elseif($canCreate): ?>
      <button
          type="button"
          disabled
          title="اختر فرعاً أولاً من الفلتر"
          class="px-5 py-2.5 bg-slate-200 text-slate-400 rounded-xl font-bold text-sm cursor-not-allowed flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إضافة رحلة
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $stats['total'],'label' => 'إجمالي الرحلات','icon' => 'bus','variant' => 'slate','badge' => 'رحلة','badgeIcon' => 'bus']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['total']),'label' => 'إجمالي الرحلات','icon' => 'bus','variant' => 'slate','badge' => 'رحلة','badgeIcon' => 'bus']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $stats['upcoming'],'label' => 'رحلات قادمة','icon' => 'clock','variant' => 'emerald','badge' => 'قادمة','badgeIcon' => 'clock']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['upcoming']),'label' => 'رحلات قادمة','icon' => 'clock','variant' => 'emerald','badge' => 'قادمة','badgeIcon' => 'clock']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $stats['completed'],'label' => 'رحلات مكتملة','icon' => 'check-circle','variant' => 'amber','badge' => 'مكتملة','badgeIcon' => 'check-circle']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['completed']),'label' => 'رحلات مكتملة','icon' => 'check-circle','variant' => 'amber','badge' => 'مكتملة','badgeIcon' => 'check-circle']); ?>
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

  
  <div class="bg-white rounded-2xl border border-[#e2e2e5] p-4">
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
      <form method="GET" action="<?php echo e(route('provider.trips.index')); ?>" class="flex items-center gap-2 flex-1">
        <div class="relative flex-1 max-w-xs">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="filter" class="w-4 h-4"></i>
          </div>
          <select
            name="branch"
            id="branch-filter"
            onchange="this.form.submit()"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all appearance-none cursor-pointer"
          >
            <option value="">كل الفروع</option>
            <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($branch->id); ?>" data-uuid="<?php echo e($branch->uuid); ?>" <?php echo e($selectedBranch == $branch->id ? 'selected' : ''); ?>>
                <?php echo e($branch->city?->name ?? 'فرع #' . $branch->id); ?>

              </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </div>
        <?php if($selectedBranch): ?>
          <a href="<?php echo e(route('provider.trips.index')); ?>" class="text-[11px] text-rose-500 hover:text-rose-700 font-bold flex items-center gap-1 whitespace-nowrap">
            <i data-lucide="x" class="w-3 h-3"></i>
            إزالة الفلتر
          </a>
        <?php endif; ?>
      </form>
      <?php if($selectedBranch): ?>
        <span class="text-[10px] font-bold bg-teal-100 text-teal-700 border border-teal-200 px-2.5 py-1 rounded-full whitespace-nowrap">
          <i data-lucide="store" class="w-3 h-3 inline ml-1"></i>
          فلتر: الفرع المحدد
        </span>
      <?php endif; ?>
    </div>
  </div>

  
  <?php if (isset($component)) { $__componentOriginal90c131072f947a512cfcd740e0e498fa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal90c131072f947a512cfcd740e0e498fa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.data-table-card','data' => ['searchable' => false,'footerInfo' => 'عرض ' . $trips->count() . ' من أصل ' . $trips->total() . ' سجل']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('data-table-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['searchable' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'footerInfo' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('عرض ' . $trips->count() . ' من أصل ' . $trips->total() . ' سجل')]); ?>
     <?php $__env->slot('thead', null, []); ?> 
      <th class="px-4 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المسار</th>
      <th class="px-4 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الفرع</th>
      <th class="px-4 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المركبة</th>
      <th class="px-4 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الانطلاق</th>
      <th class="px-4 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">السعر</th>
      <th class="px-4 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
      <th class="px-4 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">إجراءات</th>
     <?php $__env->endSlot(); ?>

     <?php $__env->slot('rows', null, []); ?> 
      <?php $__empty_1 = true; $__currentLoopData = $trips; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trip): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $isUpcoming = $trip->departure_time && $trip->departure_time->isFuture();
          $statusColor = $isUpcoming ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200';
          $statusText = $isUpcoming ? 'قادمة' : 'مكتملة';
          $statusDot = $isUpcoming ? 'bg-emerald-500' : 'bg-slate-400';
          $tripBranch = $trip->service?->branch;
          $canEditThis = $canEdit && $isUpcoming;
          $canDeleteThis = $canDelete && $isUpcoming && !$trip->bookings()->exists();

          $tripRepeat = $trip->repeats->first();
          $recurrenceEnabled = (bool) $tripRepeat;
          $recurrenceDays = $tripRepeat ? $tripRepeat->repeatedDays->pluck('day_of_week')->toArray() : [];
          $recurrenceEndDate = $tripRepeat?->end_date?->format('Y-m-d') ?? '';

          $editJson = json_encode([
              'uuid'               => $trip->uuid,
              'branch_uuid'        => $tripBranch?->uuid ?? '',
              'travel_date'        => $trip->departure_time?->format('Y-m-d'),
              'check_in_clock'     => $trip->check_in_time?->format('H:i'),
              'departure_clock'    => $trip->departure_time?->format('H:i'),
              'arrival_clock'      => $trip->arrival_time?->format('H:i'),
              'from_country_id'    => $trip->departureCity?->country_id ?? '',
              'to_country_id'      => $trip->destinationCity?->country_id ?? '',
              'from_city_id'       => $trip->departureCity?->uuid ?? '',
              'to_city_id'         => $trip->destinationCity?->uuid ?? '',
              'pickup_location_id' => $trip->departureStation?->uuid ?? '',
              'arrival_location_id'=> $trip->arrivalStation?->uuid ?? '',
              'vehicle_id'         => $trip->vehicle?->uuid ?? '',
              'type_of_trip_id'    => $trip->type_of_trip_id ?? '',
              'level_trip_id'      => $trip->level_trip_id ?? '',
              'trip_status_id'     => $trip->trip_status_id ?? '',
              'price_for_adult'    => $trip->price_for_adult,
              'price_for_child'    => $trip->price_for_child,
              'discount_percentage'=> $trip->discount_percentage ?? 0,
              'conditions'         => $trip->conditions ?? '',
              'is_public'          => $trip->is_public ? '1' : '0',
              'trip_name_id'       => $trip->trip_name_id ?? '',
              'policy_id'          => $trip->policies?->pluck('uuid')->first() ?? '',
              'amenities'          => $trip->amenities?->pluck('uuid')->toArray() ?? [],
              'coupons'            => $trip->coupons?->pluck('uuid')->toArray() ?? [],
              'waypoint_cities'    => $trip->waypoints?->pluck('city.uuid')->toArray() ?? [],
              'is_recurring'       => $recurrenceEnabled,
              'days'               => $recurrenceDays,
              'end_date_repeat'    => $recurrenceEndDate,
          ], JSON_UNESCAPED_UNICODE);
        ?>
        <tr class="hover:bg-slate-50/50 transition-colors">
          
          <td class="px-4 py-4">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-lg bg-[#115128]/10 flex items-center justify-center flex-shrink-0">
                <i data-lucide="route" class="w-4 h-4 text-[#115128]"></i>
              </div>
              <div>
                <p class="text-[13px] font-bold text-slate-800">
                  <?php echo e($trip->departureCity?->name ?? '—'); ?>

                  <i data-lucide="arrow-left" class="w-3 h-3 text-slate-300 inline mx-1"></i>
                  <?php echo e($trip->destinationCity?->name ?? '—'); ?>

                </p>
                <p class="text-[10px] text-slate-400"><?php echo e($trip->typeOfTrip?->type_name ?? ''); ?> <?php echo e($trip->levelTrip?->level_name ? '· ' . $trip->levelTrip->level_name : ''); ?></p>
              </div>
            </div>
          </td>
          
          <td class="px-4 py-4">
            <span class="inline-flex items-center gap-1 text-[11px] font-bold bg-slate-100 text-slate-600 px-2 py-1 rounded-lg">
              <i data-lucide="store" class="w-3 h-3"></i>
              <?php echo e($tripBranch?->city?->name ?? '—'); ?>

            </span>
          </td>
          
          <td class="px-4 py-4">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-teal-500/20 to-teal-600/10 flex items-center justify-center border border-teal-100 flex-shrink-0">
                <i data-lucide="bus" class="w-4 h-4 text-teal-600"></i>
              </div>
              <div>
                <span class="text-[12px] font-bold text-slate-700"><?php echo e($trip->vehicle?->plate_number ?? '—'); ?></span>
                <p class="text-[10px] text-slate-400"><?php echo e($trip->vehicle?->vehicleType?->type_name ?? ''); ?></p>
              </div>
            </div>
          </td>
          
          <td class="px-4 py-4">
            <p class="text-[12px] font-bold text-slate-700 font-mono"><?php echo e($trip->departure_time?->format('H:i') ?? '—'); ?></p>
            <p class="text-[11px] text-slate-400"><?php echo e($trip->departure_time?->format('d/m/Y') ?? '—'); ?></p>
          </td>
          
          <td class="px-4 py-4">
            <span class="text-[13px] font-extrabold text-[#115128]"><?php echo e(number_format($trip->price_for_adult, 0)); ?></span>
            <p class="text-[10px] text-slate-400"><?php echo e($trip->currency_code ?? ''); ?></p>
          </td>
          
          <td class="px-4 py-4 text-center">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border <?php echo e($statusColor); ?>">
              <span class="w-1.5 h-1.5 rounded-full <?php echo e($statusDot); ?>"></span>
              <?php echo e($statusText); ?>

            </span>
          </td>
          
          <td class="px-4 py-4 text-center">
            <div class="flex items-center justify-center gap-1.5">
              <?php if($canEditThis): ?>
                <?php if (isset($component)) { $__componentOriginald4c6978101b1c254eb70511d3c21c03f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald4c6978101b1c254eb70511d3c21c03f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.action-button','data' => ['variant' => 'edit','dataEditModal' => 'trip-form-modal','dataEditJson' => ''.e($editJson).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('action-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'edit','data-edit-modal' => 'trip-form-modal','data-edit-json' => ''.e($editJson).'']); ?>
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
              <?php if($canDeleteThis): ?>
                <?php if (isset($component)) { $__componentOriginald4c6978101b1c254eb70511d3c21c03f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald4c6978101b1c254eb70511d3c21c03f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.action-button','data' => ['variant' => 'delete','dataDeleteUrl' => ''.e(route('provider.branches.trips.destroy', [$tripBranch, $trip])).'','dataDeleteLabel' => ''.e($trip->departureCity?->name . ' → ' . $trip->destinationCity?->name).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('action-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'delete','data-delete-url' => ''.e(route('provider.branches.trips.destroy', [$tripBranch, $trip])).'','data-delete-label' => ''.e($trip->departureCity?->name . ' → ' . $trip->destinationCity?->name).'']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty-state','data' => ['icon' => 'bus','title' => $selectedBranch ? 'لا توجد رحلات في هذا الفرع' : 'لا توجد رحلات','message' => $selectedBranch ? 'أضف رحلة جديدة لهذا الفرع' : 'اختر فرعاً وأضف رحلة جديدة','colspan' => 7]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'bus','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($selectedBranch ? 'لا توجد رحلات في هذا الفرع' : 'لا توجد رحلات'),'message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($selectedBranch ? 'أضف رحلة جديدة لهذا الفرع' : 'اختر فرعاً وأضف رحلة جديدة'),'colspan' => 7]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pagination','data' => ['paginator' => $trips]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pagination'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($trips)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form-modal','data' => ['id' => 'trip-form-modal','title' => 'إضافة رحلة جديدة','action' => '#','method' => 'POST','successMessage' => 'تم إنشاء الرحلة بنجاح','maxWidth' => 'max-w-4xl']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form-modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'trip-form-modal','title' => 'إضافة رحلة جديدة','action' => '#','method' => 'POST','successMessage' => 'تم إنشاء الرحلة بنجاح','maxWidth' => 'max-w-4xl']); ?>

  <div class="space-y-6">

    
    <div class="space-y-4">
      <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
        <h4 class="font-bold text-xs flex items-center gap-1.5 text-[#115128]">
          <i data-lucide="info" class="w-3.5 h-3.5"></i>
          بيانات الرحلة الأساسية
        </h4>
        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border text-rose-600 bg-rose-50 border-rose-200">مطلوب</span>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        
        <div>
          <label class="block text-[11px] font-bold text-gray-500 mb-1.5">المركبة الناقلة</label>
          <select name="vehicle_id" required class="w-full bg-[#f3f3f6] border border-[#e2e2e5] px-3 py-2 text-[13px] rounded-lg outline-none cursor-pointer focus:bg-white text-right">
            <option value="">اختر المركبة</option>
            <?php $__currentLoopData = $vehicles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($v->uuid); ?>"><?php echo e($v->plate_number); ?> (<?php echo e($v->vehicleType?->type_name ?? ''); ?>)</option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </div>
        
        <div>
          <label class="block text-[11px] font-bold text-gray-500 mb-1.5">مستوى الخدمة والدرجة</label>
          <select name="level_trip_id" required class="w-full bg-[#f3f3f6] border border-[#e2e2e5] px-3 py-2 text-[13px] rounded-lg outline-none cursor-pointer focus:bg-white text-right">
            <option value="">اختر الدرجة</option>
            <?php $__currentLoopData = $levelTrips; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($l->id); ?>"><?php echo e($l->level_name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </div>
        
        <div>
          <label class="block text-[11px] font-bold text-gray-500 mb-1.5">نوع الرحلة</label>
          <select name="type_of_trip_id" required class="w-full bg-[#f3f3f6] border border-[#e2e2e5] px-3 py-2 text-[13px] rounded-lg outline-none cursor-pointer focus:bg-white text-right">
            <option value="">اختر النوع</option>
            <?php $__currentLoopData = $typeOfTrips; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($t->id); ?>"><?php echo e($t->type_name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </div>
        
        <div id="trip-status-wrapper" style="display:none;">
          <label class="block text-[11px] font-bold text-gray-500 mb-1.5">حالة الرحلة</label>
          <select name="trip_status_id" disabled class="w-full bg-[#f3f3f6] border border-[#e2e2e5] px-3 py-2 text-[13px] rounded-lg outline-none cursor-pointer focus:bg-white text-right" id="f_trip_status_id">
            <?php $__currentLoopData = $tripStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ts): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($ts->id); ?>"><?php echo e($ts->status_name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </div>
        
        <div>
          <label class="block text-[11px] font-bold text-gray-500 mb-1.5">نشر الرحلة</label>
          <div class="flex items-center gap-3 bg-[#f3f3f6] border border-[#e2e2e5] rounded-lg px-3 py-2">
            <div class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-gray-800">
              <input type="radio" name="is_public" value="1" checked class="text-[#115128] focus:ring-[#115128] w-3.5 h-3.5">
              <span>عام</span>
            </div>
            <div class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-gray-800">
              <input type="radio" name="is_public" value="0" class="text-[#115128] focus:ring-[#115128] w-3.5 h-3.5">
              <span>خاص</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    
    <div class="space-y-4">
      <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
        <h4 class="font-bold text-xs flex items-center gap-1.5 text-[#115128]"><i data-lucide="map-pin" class="w-3.5 h-3.5"></i> محطات الانطلاق والوصول</h4>
        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border text-rose-600 bg-rose-50 border-rose-200">مطلوب</span>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-[#f9f9fc] p-5 rounded-xl border border-[#eeeef0]">
        
        <div class="space-y-3 border-l border-[#eeeef0] pl-4 md:pl-6">
          <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span><span class="font-bold text-xs text-emerald-800">محطة الانطلاق</span></div>
          <div>
            <label class="block text-[10px] font-bold text-gray-400 mb-1">دولة الانطلاق</label>
            <select id="f_from_country_id" class="w-full bg-white border border-[#e2e2e5] px-2.5 py-1.5 text-xs rounded-md font-bold text-right appearance-none cursor-pointer">
              <option value="">اختر الدولة</option>
              <?php $__currentLoopData = $countries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $country): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($country->id); ?>"><?php echo e($country->name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-gray-400 mb-1">مدينة الانطلاق</label>
            <select name="from_city_id" required class="w-full bg-white border border-[#e2e2e5] px-2.5 py-1.5 text-xs rounded-md font-bold text-right appearance-none cursor-pointer" id="f_from_city_id">
              <option value="">اختر الدولة أولاً</option>
            </select>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-gray-400 mb-1">محطة الوقوف الدقيقة</label>
            <select name="pickup_location_id" required class="w-full bg-white border border-[#e2e2e5] px-2.5 py-1.5 text-xs rounded-md font-bold text-right appearance-none cursor-pointer" id="f_pickup_location_id">
              <option value="">اختر المدينة أولاً</option>
            </select>
          </div>
        </div>
        
        <div class="space-y-3">
          <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span><span class="font-bold text-xs text-rose-800">محطة الوصول</span></div>
          <div>
            <label class="block text-[10px] font-bold text-gray-400 mb-1">دولة الوصول</label>
            <select id="f_to_country_id" class="w-full bg-white border border-[#e2e2e5] px-2.5 py-1.5 text-xs rounded-md font-bold text-right appearance-none cursor-pointer">
              <option value="">اختر الدولة</option>
              <?php $__currentLoopData = $countries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $country): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($country->id); ?>"><?php echo e($country->name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-gray-400 mb-1">مدينة الوصول</label>
            <select name="to_city_id" required class="w-full bg-white border border-[#e2e2e5] px-2.5 py-1.5 text-xs rounded-md font-bold text-right appearance-none cursor-pointer" id="f_to_city_id">
              <option value="">اختر الدولة أولاً</option>
            </select>
          </div>
          <div>
            <label class="block text-[10px] font-bold text-gray-400 mb-1">محطة التنزيل الدقيقة</label>
            <select name="arrival_location_id" required class="w-full bg-white border border-[#e2e2e5] px-2.5 py-1.5 text-xs rounded-md font-bold text-right appearance-none cursor-pointer" id="f_arrival_location_id">
              <option value="">اختر المدينة أولاً</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    
    <div class="space-y-4">
      <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
        <h4 class="font-bold text-xs flex items-center gap-1.5 text-purple-600"><i data-lucide="star" class="w-3.5 h-3.5"></i> مدن المرور والمحطات الوسطية <span class="text-gray-400 font-normal">(اختياري)</span></h4>
        <button type="button" id="add-waypoint-btn" class="text-xs font-bold text-purple-600 hover:bg-purple-50 border border-purple-200 px-3 py-1 rounded-lg flex items-center gap-1 transition-all">
          <i data-lucide="plus" class="w-3.5 h-3.5"></i> إضافة مدينة مرور
        </button>
      </div>
      <div id="waypoints-container">
        <div class="border border-dashed border-gray-200 rounded-xl py-6 text-center text-gray-400 text-xs font-medium" id="waypoints-empty">لا يوجد أي مدن مرور مضافة حالياً. انقر على الزر بالأعلى لإضافة محطات مرور.</div>
      </div>
    </div>

    
    <div class="space-y-4">
      <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
        <h4 class="font-bold text-xs flex items-center gap-1.5 text-[#115128]"><i data-lucide="clock" class="w-3.5 h-3.5"></i> أوقات وتفاصيل جدول الرحلة</h4>
        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border text-rose-600 bg-rose-50 border-rose-200">مطلوب</span>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label class="block text-[11px] font-bold text-gray-500 mb-1.5">تاريخ الرحلة</label>
          <input type="date" name="travel_date" required min="<?php echo e(\Carbon\Carbon::today()->format('Y-m-d')); ?>" class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-lg px-3 py-2 text-[13px] font-bold outline-none focus:bg-white text-right">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-gray-500 mb-1.5">وقت تسجيل الدخول (Check-in)</label>
          <input type="time" name="check_in_clock" required class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-lg px-3 py-2 text-[13px] font-bold outline-none focus:bg-white text-right">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-gray-500 mb-1.5">وقت الانطلاق</label>
          <input type="time" name="departure_clock" required class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-lg px-3 py-2 text-[13px] font-bold outline-none focus:bg-white text-right">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-gray-500 mb-1.5">وقت الوصول المتوقع</label>
          <input type="time" name="arrival_clock" required class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-lg px-3 py-2 text-[13px] font-bold outline-none focus:bg-white text-right">
        </div>
      </div>
    </div>

    
    <div class="space-y-4">
      <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
        <h4 class="font-bold text-xs flex items-center gap-1.5 text-emerald-600"><i data-lucide="dollar-sign" class="w-3.5 h-3.5"></i> أسعار التذاكر</h4>
        <span class="text-[9px] font-bold text-emerald-600 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">مطلوب</span>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div><label class="block text-[11px] font-bold text-gray-500 mb-1.5">سعر تذكرة البالغ</label><input type="number" name="price_for_adult" required step="0.01" min="0" placeholder="0.00" dir="ltr" class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-lg px-3 py-2 text-[13px] font-bold outline-none focus:bg-white text-left"></div>
        <div><label class="block text-[11px] font-bold text-gray-500 mb-1.5">سعر تذكرة الطفل</label><input type="number" name="price_for_child" required step="0.01" min="0" placeholder="0.00" dir="ltr" class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-lg px-3 py-2 text-[13px] font-bold outline-none focus:bg-white text-left"></div>
        <div><label class="block text-[11px] font-bold text-gray-500 mb-1.5">نسبة الخصم الترويجي (%)</label><input type="number" name="discount_percentage" min="0" max="100" step="0.1" placeholder="0" dir="ltr" class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-lg px-3 py-2 text-[13px] font-bold outline-none focus:bg-white text-left"></div>
      </div>
    </div>

    
    <div class="space-y-4">
      <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
        <h4 class="font-bold text-xs flex items-center gap-1.5 text-[#115128]"><i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> تكرار وجدولة الرحلة الدورية <span class="text-gray-400 font-normal">(اختياري)</span></h4>
      </div>
      <div class="bg-[#f9f9fc] p-5 rounded-xl border border-[#eeeef0] space-y-3">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold text-gray-700">تفعيل الجدولة الأسبوعية التلقائية:</span>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" id="f_is_recurring" class="sr-only peer">
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#235b32]"></div>
          </label>
        </div>
        <div id="recurrence-fields" style="display:none;" class="space-y-3 pt-3 border-t border-[#eeeef0]">
          <span class="block text-[11px] font-bold text-gray-400">تكرار توليد الرحلة تلقائياً في الأيام المحددة:</span>
          <div class="flex flex-wrap gap-2 justify-start" id="recurrence-days-container">
            <button type="button" class="recurrence-day-btn px-3 py-1.5 rounded text-xs font-semibold border transition-all bg-white text-gray-600 border-[#e2e2e5] hover:bg-[#f3f3f6]" data-day="0">السبت</button>
            <button type="button" class="recurrence-day-btn px-3 py-1.5 rounded text-xs font-semibold border transition-all bg-white text-gray-600 border-[#e2e2e5] hover:bg-[#f3f3f6]" data-day="1">الأحد</button>
            <button type="button" class="recurrence-day-btn px-3 py-1.5 rounded text-xs font-semibold border transition-all bg-white text-gray-600 border-[#e2e2e5] hover:bg-[#f3f3f6]" data-day="2">الإثنين</button>
            <button type="button" class="recurrence-day-btn px-3 py-1.5 rounded text-xs font-semibold border transition-all bg-white text-gray-600 border-[#e2e2e5] hover:bg-[#f3f3f6]" data-day="3">الثلاثاء</button>
            <button type="button" class="recurrence-day-btn px-3 py-1.5 rounded text-xs font-semibold border transition-all bg-white text-gray-600 border-[#e2e2e5] hover:bg-[#f3f3f6]" data-day="4">الأربعاء</button>
            <button type="button" class="recurrence-day-btn px-3 py-1.5 rounded text-xs font-semibold border transition-all bg-white text-gray-600 border-[#e2e2e5] hover:bg-[#f3f3f6]" data-day="5">الخميس</button>
            <button type="button" class="recurrence-day-btn px-3 py-1.5 rounded text-xs font-semibold border transition-all bg-white text-gray-600 border-[#e2e2e5] hover:bg-[#f3f3f6]" data-day="6">الجمعة</button>
          </div>
          <div class="max-w-xs">
            <label class="block text-[11px] font-bold text-gray-500 mb-1">تاريخ نهاية جدولة التكرار</label>
            <input type="date" name="end_date_repeat" id="f_end_date_repeat" class="w-full bg-white border border-[#e2e2e5] px-2.5 py-1.5 text-xs rounded outline-none font-bold text-right">
          </div>
        </div>
      </div>
    </div>

    

    
    <div class="space-y-4">
      <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
        <h4 class="font-bold text-xs flex items-center gap-1.5 text-amber-600"><i data-lucide="shield-check" class="w-3.5 h-3.5"></i> سياسات إلغاء وتعديل الحجز</h4>
      </div>
      <?php if($policies->isNotEmpty()): ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3" id="policies-container">
        <?php $__currentLoopData = $policies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $policy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="trip-policy-card p-3 rounded-xl border flex flex-col gap-1.5 cursor-pointer transition-all bg-white border-gray-100 hover:bg-gray-50 text-gray-700" data-policy-id="<?php echo e($policy->uuid); ?>">
            <div class="flex items-center gap-2 w-full justify-between">
              <span class="font-bold text-xs"><?php echo e($policy->name); ?></span>
              <input type="radio" name="policy_id" value="<?php echo e($policy->uuid); ?>" class="w-4 h-4 text-amber-600 border-gray-300 focus:ring-amber-500 trip-policy-checkbox">
            </div>
            <span class="text-[10px] text-gray-400 font-medium"><?php echo e($policy->description ?? ''); ?></span>
            <?php if($policy->rules && $policy->rules->count() > 0): ?>
              <ul class="text-[9px] text-gray-500 font-medium list-disc list-inside space-y-0.5 mt-1">
                <?php $__currentLoopData = $policy->rules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rule): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <li><?php echo e($rule->rule_text ?? $rule->description ?? ''); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </ul>
            <?php endif; ?>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
      <?php else: ?>
        <div class="border border-dashed border-gray-200 rounded-xl py-4 text-center text-gray-400 text-xs font-medium">لا توجد سياسات متاحة</div>
      <?php endif; ?>
    </div>

    
    <div class="space-y-4">
      <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
        <h4 class="font-bold text-xs flex items-center gap-1.5 text-emerald-600"><i data-lucide="tag" class="w-3.5 h-3.5"></i> كوبونات الخصم المسموح بتطبيقها</h4>
      </div>
      <?php if($coupons->isNotEmpty()): ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3" id="coupons-container">
        <?php $__currentLoopData = $coupons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $coupon): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="trip-coupon-card p-3 rounded-xl border flex flex-col gap-1 cursor-pointer transition-all bg-white border-gray-100 hover:bg-gray-50 text-gray-700" data-coupon-id="<?php echo e($coupon->uuid); ?>">
            <div class="flex items-center gap-2 w-full justify-between">
              <span class="font-mono font-black text-sm"><?php echo e($coupon->code); ?></span>
              <input type="checkbox" name="coupons[]" value="<?php echo e($coupon->uuid); ?>" class="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500 trip-coupon-checkbox">
            </div>
            <span class="text-[10px] text-gray-400 font-semibold"><?php echo e($coupon->description ?? ''); ?></span>
            <span class="text-[10px] text-emerald-700 font-bold">خصم <?php echo e($coupon->discount_percentage ?? $coupon->percentage ?? 0); ?>%</span>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
      <?php else: ?>
        <div class="border border-dashed border-gray-200 rounded-xl py-4 text-center text-gray-400 text-xs font-medium">لا توجد كوبونات نشطة حالياً</div>
      <?php endif; ?>
    </div>

    
    <div class="space-y-4">
      <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
        <h4 class="font-bold text-xs flex items-center gap-1.5 text-[#115128]"><i data-lucide="sparkles" class="w-3.5 h-3.5"></i> الميزات والخدمات الترفيهية المشمولة</h4>
      </div>
      <?php if($amenities->isNotEmpty()): ?>
      <div class="flex flex-wrap gap-2" id="amenities-container">
        <?php $__currentLoopData = $amenities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $amenity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="trip-amenity-item px-3 py-1.5 rounded-full text-[11px] font-bold border transition-all bg-white text-gray-500 border-gray-200 hover:bg-gray-50 cursor-pointer select-none" data-amenity-id="<?php echo e($amenity->uuid); ?>">
            <input type="checkbox" name="amenities[]" value="<?php echo e($amenity->uuid); ?>" class="sr-only trip-amenity-checkbox">
            <span><?php echo e($amenity->name); ?></span>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
      <?php else: ?>
        <div class="border border-dashed border-gray-200 rounded-xl py-4 text-center text-gray-400 text-xs font-medium">لا توجد خدمات متاحة</div>
      <?php endif; ?>
    </div>

    
    <div class="space-y-4">
      <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
        <h4 class="font-bold text-xs flex items-center gap-1.5 text-slate-600"><i data-lucide="scroll-text" class="w-3.5 h-3.5"></i> ملاحظات وإرشادات إضافية</h4>
        <span class="text-[9px] font-bold text-slate-500 bg-slate-50 border border-slate-200 px-2 py-0.5 rounded-full">اختياري</span>
      </div>
      <textarea name="conditions" rows="3" placeholder="مثال: يرجى إحضار بطاقة الهوية الوطنية..." class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl p-3 text-xs outline-none focus:bg-white focus:border-[#115128] leading-relaxed font-semibold text-gray-700 resize-none"></textarea>
    </div>

    
    <div class="space-y-4" id="trip-extra-info" style="display:none;">
      <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
        <h4 class="font-bold text-xs flex items-center gap-1.5 text-slate-600"><i data-lucide="info" class="w-3.5 h-3.5"></i> معلومات إضافية</h4>
      </div>
      <div class="p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
        <div class="flex items-center gap-2">
          <i data-lucide="hash" class="w-3.5 h-3.5 text-slate-400"></i>
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">رقم الرحلة:</span>
          <span class="text-xs font-mono text-slate-600" id="trip-info-name-id">—</span>
        </div>
      </div>
    </div>

    
    <div id="trip-create-hint" class="bg-teal-50/70 border border-teal-200 rounded-xl p-3">
      <div class="flex items-start gap-2.5">
        <i data-lucide="info" class="w-4 h-4 text-teal-500 flex-shrink-0 mt-0.5"></i>
        <span class="text-[11px] text-teal-600">الرحلة ستُضاف تلقائياً للفرع المحدد في الفلتر.</span>
      </div>
    </div>

  </div>

  
   <?php $__env->slot('actions', null, []); ?> 
    <button type="submit" class="flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95" id="trip-submit-btn" style="background: linear-gradient(to right, #115128, #0d3d1e);">
      <i data-lucide="save" class="w-4 h-4"></i>
      <span id="trip-submit-text">حفظ الرحلة</span>
    </button>
   <?php $__env->endSlot(); ?>
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

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function() {
  // ===== خريطة الفروع: id → uuid =====
  var branchIdToUuid = {
    <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      '<?php echo e($b->id); ?>': '<?php echo e($b->uuid); ?>',
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  };

  // ===== المسارات الأساسية للمودال =====
  var tripBaseUrl = '<?php echo e(route('provider.branches.trips.store', '__BRANCH_UUID__')); ?>';
  var tripUpdateBase = '<?php echo e(route('provider.branches.trips.update', ['__BRANCH_UUID__', '__TRIP_UUID__'])); ?>';

  // ===== City Station Cascading (Country → City → Station) =====
  // خريطة: countryId → [{uuid, name, id}]
  var citiesByCountry = {};
  <?php $__currentLoopData = $cities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    var cid = <?php echo e($city->country_id); ?>;
    if (!citiesByCountry[cid]) citiesByCountry[cid] = [];
    citiesByCountry[cid].push({uuid: '<?php echo e($city->uuid); ?>', name: <?php echo json_encode($city->name, 15, 512) ?>, id: <?php echo e($city->id); ?>});
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

  // خريطة: cityId → [{uuid, name}]
  var stationsMap = {};
  <?php $__currentLoopData = $cityStations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    stationsMap[<?php echo e($st->city_id); ?>] = stationsMap[<?php echo e($st->city_id); ?>] || [];
    stationsMap[<?php echo e($st->city_id); ?>].push({uuid: <?php echo json_encode($st->uuid, 15, 512) ?>, name: <?php echo json_encode($st->name, 15, 512) ?>});
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

  // خريطة UUID المدينة ← ID
  var cityUuidToId = {};
  <?php $__currentLoopData = $cities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    cityUuidToId['<?php echo e($city->uuid); ?>'] = <?php echo e($city->id); ?>;
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

  function filterCities(countryId, citySelect) {
    citySelect.innerHTML = '<option value="">اختر المدينة</option>';
    var list = citiesByCountry[countryId] || [];
    list.forEach(function(c) {
      var opt = document.createElement('option');
      opt.value = c.uuid;
      opt.textContent = c.name;
      citySelect.appendChild(opt);
    });
  }

  function filterStations(cityUuid, stationSelect) {
    stationSelect.innerHTML = '<option value="">اختر المحطة</option>';
    var cityId = cityUuidToId[cityUuid];
    if (!cityId) return;
    var list = stationsMap[cityId] || [];
    list.forEach(function(st) {
      var opt = document.createElement('option');
      opt.value = st.uuid;
      opt.textContent = st.name;
      stationSelect.appendChild(opt);
    });
  }

  var fromCountry = document.getElementById('f_from_country_id');
  var fromCity = document.getElementById('f_from_city_id');
  var fromStation = document.getElementById('f_pickup_location_id');
  var toCountry = document.getElementById('f_to_country_id');
  var toCity = document.getElementById('f_to_city_id');
  var toStation = document.getElementById('f_arrival_location_id');

  // FROM cascade
  if (fromCountry) fromCountry.addEventListener('change', function() {
    filterCities(this.value, fromCity);
    fromStation.innerHTML = '<option value="">اختر المدينة أولاً</option>';
  });
  if (fromCity && fromStation) fromCity.addEventListener('change', function() { filterStations(this.value, fromStation); });

  // TO cascade
  if (toCountry) toCountry.addEventListener('change', function() {
    filterCities(this.value, toCity);
    toStation.innerHTML = '<option value="">اختر المدينة أولاً</option>';
  });
  if (toCity && toStation) toCity.addEventListener('change', function() { filterStations(this.value, toStation); });

  // ===== Waypoints (DOM creation, safe) =====
  var wpIdx = 0;
  var wpContainer = document.getElementById('waypoints-container');
  var wpEmpty = document.getElementById('waypoints-empty');

  document.getElementById('add-waypoint-btn').addEventListener('click', function() {
    if (wpEmpty) wpEmpty.style.display = 'none';
    wpIdx++;
    var row = document.createElement('div');
    row.className = 'flex items-center gap-2 bg-[#f9f9fc] p-3 rounded-xl border border-gray-100 mb-2';
    row.setAttribute('data-waypoint-row', '');
    var numSpan = document.createElement('span');
    numSpan.className = 'text-xs font-black text-gray-400';
    numSpan.textContent = wpIdx + '.';
    row.appendChild(numSpan);
    var sel = document.createElement('select');
    sel.name = 'waypoint_cities[]';
    sel.className = 'bg-white border border-[#e2e2e5] px-2 py-1.5 text-xs rounded-lg flex-1 font-bold outline-none text-right';
    var defOpt = document.createElement('option');
    defOpt.value = ''; defOpt.textContent = '-- اختر مدينة المرور --';
    sel.appendChild(defOpt);
    <?php $__currentLoopData = $cities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      var opt = document.createElement('option');
      opt.value = '<?php echo e($city->uuid); ?>';
      opt.textContent = <?php echo json_encode($city->name, 15, 512) ?>;
      sel.appendChild(opt);
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    row.appendChild(sel);
    var delBtn = document.createElement('button');
    delBtn.type = 'button';
    delBtn.className = 'w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 text-red-500 flex items-center justify-center transition-all shadow-sm active:scale-90';
    delBtn.title = 'حذف';
    delBtn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';
    delBtn.addEventListener('click', function() { row.remove(); if (!wpContainer.querySelector('[data-waypoint-row]') && wpEmpty) wpEmpty.style.display = ''; });
    row.appendChild(delBtn);
    wpContainer.appendChild(row);
  });

  // ===== Recurrence Toggle =====
  document.getElementById('f_is_recurring').addEventListener('change', function() {
    document.getElementById('recurrence-fields').style.display = this.checked ? '' : 'none';
  });

  // Recurrence day buttons (toggle via class only, no checkbox)
  var selectedDays = {};
  document.querySelectorAll('.recurrence-day-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var day = this.getAttribute('data-day');
      if (selectedDays[day]) {
        delete selectedDays[day];
        this.className = 'recurrence-day-btn px-3 py-1.5 rounded text-xs font-semibold border transition-all bg-white text-gray-600 border-[#e2e2e5] hover:bg-[#f3f3f6]';
      } else {
        selectedDays[day] = true;
        this.className = 'recurrence-day-btn px-3 py-1.5 rounded text-xs font-semibold border transition-all bg-[#235b32] text-white border-[#235b32] shadow-sm font-bold';
      }
    });
  });

  // ===== Policy card clicks (radio behavior: only one at a time) =====
  document.querySelectorAll('.trip-policy-card').forEach(function(card) {
    card.addEventListener('click', function() {
      var cb = this.querySelector('.trip-policy-checkbox');
      if (!cb) return;
      var wasChecked = cb.checked;
      // Uncheck all first
      document.querySelectorAll('.trip-policy-card').forEach(function(c) {
        var otherCb = c.querySelector('.trip-policy-checkbox');
        if (otherCb) otherCb.checked = false;
        c.className = 'trip-policy-card p-3 rounded-xl border flex flex-col gap-1.5 cursor-pointer transition-all bg-white border-gray-100 hover:bg-gray-50 text-gray-700';
      });
      // Toggle the clicked one (if it was already checked, leave all unchecked)
      if (!wasChecked) {
        cb.checked = true;
        this.className = 'trip-policy-card p-3 rounded-xl border flex flex-col gap-1.5 cursor-pointer transition-all bg-amber-50/50 border-amber-400 text-amber-950';
      }
    });
  });

  // ===== Coupon card clicks =====
  document.querySelectorAll('.trip-coupon-card').forEach(function(card) {
    card.addEventListener('click', function() {
      var cb = this.querySelector('.trip-coupon-checkbox');
      if (!cb) return;
      cb.checked = !cb.checked;
      this.className = cb.checked
        ? 'trip-coupon-card p-3 rounded-xl border flex flex-col gap-1 cursor-pointer transition-all bg-emerald-50/50 border-emerald-400 text-emerald-950'
        : 'trip-coupon-card p-3 rounded-xl border flex flex-col gap-1 cursor-pointer transition-all bg-white border-gray-100 hover:bg-gray-50 text-gray-700';
    });
  });

  // ===== Amenity item clicks =====
  document.querySelectorAll('.trip-amenity-item').forEach(function(item) {
    item.addEventListener('click', function() {
      var cb = this.querySelector('.trip-amenity-checkbox');
      if (!cb) return;
      cb.checked = !cb.checked;
      this.className = cb.checked
        ? 'trip-amenity-item px-3 py-1.5 rounded-full text-[11px] font-bold border transition-all bg-emerald-50 text-emerald-800 border-emerald-200 cursor-pointer select-none'
        : 'trip-amenity-item px-3 py-1.5 rounded-full text-[11px] font-bold border transition-all bg-white text-gray-500 border-gray-200 hover:bg-gray-50 cursor-pointer select-none';
    });
  });

  // ===== Helper: set field value =====
  function setField(name, val) {
    var el = document.querySelector('[name="' + name + '"]');
    if (el) { var v = (val === null || val === undefined) ? '' : String(val); el.value = v; }
  }

  // ===== Helper: set radio value =====
  function setRadio(name, val) {
    var els = document.querySelectorAll('input[name="' + name + '"]');
    els.forEach(function(el) { el.checked = (el.value === String(val)); });
  }

  // ===== Form submit: inject hidden days[] inputs =====
  var tripFormModal = document.getElementById('trip-form-modal');
  if (tripFormModal) {
    var modalForm = tripFormModal.querySelector('form');
    if (modalForm) {
      modalForm.addEventListener('submit', function(e) {
        // Remove previously injected day inputs
        modalForm.querySelectorAll('input[data-injected-day]').forEach(function(el) { el.remove(); });
        // Inject hidden inputs for selected recurrence days
        var dayNames = ['saturday','sunday','monday','tuesday','wednesday','thursday','friday'];
        for (var d in selectedDays) {
          if (selectedDays[d]) {
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'days[]';
            hidden.value = dayNames[parseInt(d)];
            hidden.setAttribute('data-injected-day', 'true');
            modalForm.appendChild(hidden);
          }
        }
      });
    }
  }

  // ===== Trip form modal: open handler =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'trip-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.uuid);

    // تحديد الفرع
    var branchUuid = data.branch_uuid || '';
    if (!isEdit) {
      var filterSelect = document.getElementById('branch-filter');
      if (filterSelect && filterSelect.value) {
        branchUuid = branchIdToUuid[filterSelect.value] || '';
      }
      if (!branchUuid) {
        var createBtn = document.querySelector('[data-create-modal="trip-form-modal"]');
        if (createBtn) branchUuid = createBtn.getAttribute('data-branch-uuid') || '';
      }
    }

    if (isEdit) {
      // ===== وضع التعديل =====
      var updateUrl = tripUpdateBase.replace('__BRANCH_UUID__', branchUuid).replace('__TRIP_UUID__', data.uuid);
      form.setAttribute('action', updateUrl);

      var existingMethod = form.querySelector('input[name="_method"]');
      if (existingMethod) existingMethod.value = 'PUT';
      else {
        var m = document.createElement('input');
        m.type = 'hidden'; m.name = '_method'; m.value = 'PUT';
        m.setAttribute('data-dynamic', 'true');
        form.appendChild(m);
      }

      // شريط تعديل (عنبري)
      var banner = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (banner) banner.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';
      var titleEl = modal.querySelector('.aracari-modal-panel h2');
      if (titleEl) titleEl.innerHTML = '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل بيانات الرحلة الحالية';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span[class*="rounded-full"]');
      if (badge) { badge.textContent = 'تحديث'; badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30'; }

      var submitBtn = document.getElementById('trip-submit-btn');
      if (submitBtn) submitBtn.style.background = 'linear-gradient(to right, #b45309, #92400e)';
      var submitText = document.getElementById('trip-submit-text');
      if (submitText) submitText.textContent = 'حفظ التعديلات';

      var extraInfo = document.getElementById('trip-extra-info');
      if (extraInfo) extraInfo.style.display = '';
      var infoNameId = document.getElementById('trip-info-name-id');
      if (infoNameId) infoNameId.textContent = data.trip_name_id || '—';

      var statusWrapper = document.getElementById('trip-status-wrapper');
      if (statusWrapper) statusWrapper.style.display = '';
      document.getElementById('f_trip_status_id').removeAttribute('disabled');
      setField('trip_status_id', data.trip_status_id);

      var createHint = document.getElementById('trip-create-hint');
      if (createHint) createHint.style.display = 'none';

      // ===== Set fields =====
      setField('vehicle_id', data.vehicle_id);
      setField('level_trip_id', data.level_trip_id);
      setField('type_of_trip_id', data.type_of_trip_id);
      setField('travel_date', data.travel_date);
      setField('check_in_clock', data.check_in_clock);
      setField('departure_clock', data.departure_clock);
      setField('arrival_clock', data.arrival_clock);
      setField('price_for_adult', data.price_for_adult);
      setField('price_for_child', data.price_for_child);
      setField('discount_percentage', data.discount_percentage);
      setField('conditions', data.conditions);

      // is_public radio
      setRadio('is_public', data.is_public);

      // Cascade: country → city → station for edit
      if (data.from_country_id) {
        fromCountry.value = data.from_country_id;
        filterCities(data.from_country_id, fromCity);
        setTimeout(function() {
          if (data.from_city_id) {
            fromCity.value = data.from_city_id;
            filterStations(data.from_city_id, fromStation);
            setTimeout(function() { if (data.pickup_location_id) fromStation.value = data.pickup_location_id; }, 0);
          }
        }, 0);
      }
      if (data.to_country_id) {
        toCountry.value = data.to_country_id;
        filterCities(data.to_country_id, toCity);
        setTimeout(function() {
          if (data.to_city_id) {
            toCity.value = data.to_city_id;
            filterStations(data.to_city_id, toStation);
            setTimeout(function() { if (data.arrival_location_id) toStation.value = data.arrival_location_id; }, 0);
          }
        }, 0);
      }

      // ===== Restore waypoints =====
      // Clear existing
      wpContainer.querySelectorAll('[data-waypoint-row]').forEach(function(r) { r.remove(); });
      var editWaypoints = data.waypoint_cities || [];
      editWaypoints.forEach(function(cityUuid) {
        if (wpEmpty) wpEmpty.style.display = 'none';
        wpIdx++;
        var row = document.createElement('div');
        row.className = 'flex items-center gap-2 bg-[#f9f9fc] p-3 rounded-xl border border-gray-100 mb-2';
        row.setAttribute('data-waypoint-row', '');
        var numSpan = document.createElement('span');
        numSpan.className = 'text-xs font-black text-gray-400';
        numSpan.textContent = wpIdx + '.';
        row.appendChild(numSpan);
        var sel = document.createElement('select');
        sel.name = 'waypoint_cities[]';
        sel.className = 'bg-white border border-[#e2e2e5] px-2 py-1.5 text-xs rounded-lg flex-1 font-bold outline-none text-right';
        var defOpt = document.createElement('option');
        defOpt.value = ''; defOpt.textContent = '-- اختر مدينة المرور --';
        sel.appendChild(defOpt);
        <?php $__currentLoopData = $cities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          var opt = document.createElement('option');
          opt.value = '<?php echo e($city->uuid); ?>';
          opt.textContent = <?php echo json_encode($city->name, 15, 512) ?>;
          sel.appendChild(opt);
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        sel.value = cityUuid;
        row.appendChild(sel);
        var delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.className = 'w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 text-red-500 flex items-center justify-center transition-all shadow-sm active:scale-90';
        delBtn.title = 'حذف';
        delBtn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';
        delBtn.addEventListener('click', function() { row.remove(); if (!wpContainer.querySelector('[data-waypoint-row]') && wpEmpty) wpEmpty.style.display = ''; });
        row.appendChild(delBtn);
        wpContainer.appendChild(row);
      });
      if (editWaypoints.length === 0 && wpEmpty) wpEmpty.style.display = '';

      // ===== Restore recurrence =====
      var recToggle = document.getElementById('f_is_recurring');
      var recFields = document.getElementById('recurrence-fields');
      var isRecurring = !!(data.is_recurring);
      if (recToggle) recToggle.checked = isRecurring;
      if (recFields) recFields.style.display = isRecurring ? '' : 'none';
      setField('end_date_repeat', data.end_date_repeat);

      // Restore selected days
      selectedDays = {};
      var editDays = data.days || [];
      // Map database day names to indices: saturday=0, sunday=1, etc.
      var dayNameToIndex = {'saturday':0,'sunday':1,'monday':2,'tuesday':3,'wednesday':4,'thursday':5,'friday':6};
      document.querySelectorAll('.recurrence-day-btn').forEach(function(btn) {
        var dayIdx = parseInt(btn.getAttribute('data-day'));
        var dayName = dayNameToIndex[dayIdx] !== undefined ? Object.keys(dayNameToIndex)[dayIdx] : '';
        // Check if this day index is in editDays (editDays may contain db names or indices)
        var isSelected = false;
        if (editDays.length > 0) {
          editDays.forEach(function(d) {
            if (String(d) === String(dayIdx) || d === dayName) isSelected = true;
          });
        }
        if (isSelected) {
          selectedDays[dayIdx] = true;
          btn.className = 'recurrence-day-btn px-3 py-1.5 rounded text-xs font-semibold border transition-all bg-[#235b32] text-white border-[#235b32] shadow-sm font-bold';
        } else {
          btn.className = 'recurrence-day-btn px-3 py-1.5 rounded text-xs font-semibold border transition-all bg-white text-gray-600 border-[#e2e2e5] hover:bg-[#f3f3f6]';
        }
      });

      // ===== Restore policy radio (card toggle, only one) =====
      document.querySelectorAll('.trip-policy-card').forEach(function(card) {
        var cb = card.querySelector('.trip-policy-checkbox');
        if (!cb) return;
        var policyUuid = card.getAttribute('data-policy-id');
        var editPolicyId = data.policy_id || '';
        cb.checked = (policyUuid === editPolicyId);
        card.className = cb.checked
          ? 'trip-policy-card p-3 rounded-xl border flex flex-col gap-1.5 cursor-pointer transition-all bg-amber-50/50 border-amber-400 text-amber-950'
          : 'trip-policy-card p-3 rounded-xl border flex flex-col gap-1.5 cursor-pointer transition-all bg-white border-gray-100 hover:bg-gray-50 text-gray-700';
      });

      // ===== Restore coupon checkboxes (card toggle) =====
      document.querySelectorAll('.trip-coupon-card').forEach(function(card) {
        var cb = card.querySelector('.trip-coupon-checkbox');
        if (!cb) return;
        var couponUuid = card.getAttribute('data-coupon-id');
        var editCoupons = data.coupons || [];
        cb.checked = editCoupons.indexOf(couponUuid) !== -1;
        card.className = cb.checked
          ? 'trip-coupon-card p-3 rounded-xl border flex flex-col gap-1 cursor-pointer transition-all bg-emerald-50/50 border-emerald-400 text-emerald-950'
          : 'trip-coupon-card p-3 rounded-xl border flex flex-col gap-1 cursor-pointer transition-all bg-white border-gray-100 hover:bg-gray-50 text-gray-700';
      });

      // ===== Restore amenity checkboxes (pill toggle) =====
      document.querySelectorAll('.trip-amenity-item').forEach(function(item) {
        var cb = item.querySelector('.trip-amenity-checkbox');
        if (!cb) return;
        var amenityUuid = item.getAttribute('data-amenity-id');
        var editAmenities = data.amenities || [];
        cb.checked = editAmenities.indexOf(amenityUuid) !== -1;
        item.className = cb.checked
          ? 'trip-amenity-item px-3 py-1.5 rounded-full text-[11px] font-bold border transition-all bg-emerald-50 text-emerald-800 border-emerald-200 cursor-pointer select-none'
          : 'trip-amenity-item px-3 py-1.5 rounded-full text-[11px] font-bold border transition-all bg-white text-gray-500 border-gray-200 hover:bg-gray-50 cursor-pointer select-none';
      });

    } else {
      // ===== وضع الإنشاء =====
      var storeUrl = tripBaseUrl.replace('__BRANCH_UUID__', branchUuid);
      form.setAttribute('action', storeUrl);
      var dm = form.querySelector('input[data-dynamic="true"]');
      if (dm) dm.remove();

      var banner = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (banner) banner.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
      var titleEl = modal.querySelector('.aracari-modal-panel h2');
      if (titleEl) titleEl.innerHTML = '<i data-lucide="plus-circle" class="w-5 h-5"></i> إنشاء رحلة جديدة بجميع التفاصيل';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span[class*="rounded-full"]');
      if (badge) { badge.textContent = 'جديد'; badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30'; }

      var submitBtn = document.getElementById('trip-submit-btn');
      if (submitBtn) submitBtn.style.background = 'linear-gradient(to right, #115128, #0d3d1e)';
      var submitText = document.getElementById('trip-submit-text');
      if (submitText) submitText.textContent = 'حفظ الرحلة';

      var extraInfo = document.getElementById('trip-extra-info');
      if (extraInfo) extraInfo.style.display = 'none';
      var statusWrapper = document.getElementById('trip-status-wrapper');
      if (statusWrapper) statusWrapper.style.display = 'none';
      var statusSelect = document.getElementById('f_trip_status_id');
      if (statusSelect) { statusSelect.value = ''; statusSelect.setAttribute('disabled', 'disabled'); }

      var createHint = document.getElementById('trip-create-hint');
      if (createHint) createHint.style.display = '';

      // Reset all form fields
      setField('vehicle_id', '');
      setField('level_trip_id', '');
      setField('type_of_trip_id', '');
      setField('travel_date', '');
      setField('check_in_clock', '');
      setField('departure_clock', '');
      setField('arrival_clock', '');
      setField('price_for_adult', '');
      setField('price_for_child', '');
      setField('discount_percentage', '');
      setField('conditions', '');
      setRadio('is_public', '1');

      // Reset cascade selects
      if (fromCountry) fromCountry.value = '';
      if (toCountry) toCountry.value = '';
      if (fromCity) fromCity.innerHTML = '<option value="">اختر الدولة أولاً</option>';
      if (toCity) toCity.innerHTML = '<option value="">اختر الدولة أولاً</option>';
      if (fromStation) fromStation.innerHTML = '<option value="">اختر المدينة أولاً</option>';
      if (toStation) toStation.innerHTML = '<option value="">اختر المدينة أولاً</option>';

      // Reset waypoints
      wpContainer.querySelectorAll('[data-waypoint-row]').forEach(function(r) { r.remove(); });
      if (wpEmpty) wpEmpty.style.display = '';
      wpIdx = 0;

      // Reset recurrence
      selectedDays = {};
      document.querySelectorAll('.recurrence-day-btn').forEach(function(btn) {
        btn.className = 'recurrence-day-btn px-3 py-1.5 rounded text-xs font-semibold border transition-all bg-white text-gray-600 border-[#e2e2e5] hover:bg-[#f3f3f6]';
      });
      var recToggle = document.getElementById('f_is_recurring');
      if (recToggle) recToggle.checked = false;
      document.getElementById('recurrence-fields').style.display = 'none';
      setField('end_date_repeat', '');

      // Reset policy cards
      document.querySelectorAll('.trip-policy-card').forEach(function(card) {
        var cb = card.querySelector('.trip-policy-checkbox');
        if (cb) cb.checked = false;
        card.className = 'trip-policy-card p-3 rounded-xl border flex flex-col gap-1.5 cursor-pointer transition-all bg-white border-gray-100 hover:bg-gray-50 text-gray-700';
      });

      // Reset coupon cards
      document.querySelectorAll('.trip-coupon-card').forEach(function(card) {
        var cb = card.querySelector('.trip-coupon-checkbox');
        if (cb) cb.checked = false;
        card.className = 'trip-coupon-card p-3 rounded-xl border flex flex-col gap-1 cursor-pointer transition-all bg-white border-gray-100 hover:bg-gray-50 text-gray-700';
      });

      // Reset amenity pills
      document.querySelectorAll('.trip-amenity-item').forEach(function(item) {
        var cb = item.querySelector('.trip-amenity-checkbox');
        if (cb) cb.checked = false;
        item.className = 'trip-amenity-item px-3 py-1.5 rounded-full text-[11px] font-bold border transition-all bg-white text-gray-500 border-gray-200 hover:bg-gray-50 cursor-pointer select-none';
      });
    }

    if (window.lucide) window.lucide.createIcons();
  });
})();
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.provider', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/provider/trips/index.blade.php ENDPATH**/ ?>