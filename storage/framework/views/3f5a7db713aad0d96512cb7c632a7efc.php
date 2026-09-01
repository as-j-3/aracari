<?php $__env->startSection('title', 'أركاري | لوحة التحكم'); ?>
<?php $__env->startSection('header_title', 'لوحة التحكم العامة'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6 font-cairo text-right" dir="rtl">

  
  <?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['icon' => 'layout-dashboard','title' => 'لوحة التحكم','description' => 'نظرة عامة على نشاط النظام والرحلات والحجوزات','descriptionIcon' => 'activity']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'layout-dashboard','title' => 'لوحة التحكم','description' => 'نظرة عامة على نشاط النظام والرحلات والحجوزات','descriptionIcon' => 'activity']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $totalTrips,'label' => 'إجمالي الرحلات','icon' => 'bus','variant' => 'slate','badge' => 'رحلة','badgeIcon' => 'bus']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalTrips),'label' => 'إجمالي الرحلات','icon' => 'bus','variant' => 'slate','badge' => 'رحلة','badgeIcon' => 'bus']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $activeTrips,'label' => 'رحلات نشطة','icon' => 'clock','variant' => 'emerald','badge' => 'نشطة','badgeIcon' => 'check-circle']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($activeTrips),'label' => 'رحلات نشطة','icon' => 'clock','variant' => 'emerald','badge' => 'نشطة','badgeIcon' => 'check-circle']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $todaysBookings,'label' => 'حجوزات اليوم','icon' => 'calendar-check','variant' => 'blue','badge' => 'اليوم','badgeIcon' => 'calendar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($todaysBookings),'label' => 'حجوزات اليوم','icon' => 'calendar-check','variant' => 'blue','badge' => 'اليوم','badgeIcon' => 'calendar']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => number_format($totalRevenue),'label' => 'الإيرادات','icon' => 'banknote','variant' => 'amber','badge' => 'إجمالي','badgeIcon' => 'trending-up']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($totalRevenue)),'label' => 'الإيرادات','icon' => 'banknote','variant' => 'amber','badge' => 'إجمالي','badgeIcon' => 'trending-up']); ?>
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

  
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6">

    
    <div class="lg:col-span-2 bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-3xl shadow-xl shadow-slate-200/20 flex flex-col overflow-hidden">
      <div class="p-5 border-b border-slate-200/70 bg-white/40 backdrop-blur flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500/20 to-sky-600/10 flex items-center justify-center border border-sky-100">
            <i data-lucide="route" class="w-4 h-4 text-sky-600"></i>
          </div>
          <h3 class="font-bold text-sm text-slate-800">الرحلات القادمة</h3>
        </div>
        <?php if(Route::has('admin.trips.index')): ?>
          <a href="<?php echo e(route('admin.trips.index')); ?>" class="text-[11px] font-bold text-teal-600 hover:text-teal-700 bg-teal-50/80 border border-teal-200 px-3 py-1.5 rounded-lg transition-all flex items-center gap-1">
            <span>عرض الكل</span>
            <i data-lucide="arrow-left" class="w-3 h-3"></i>
          </a>
        <?php endif; ?>
      </div>

      <div class="flex-1 overflow-x-auto">
        <table class="w-full min-w-[500px]">
          <thead>
            <tr class="bg-slate-50/70 border-b border-slate-200/70">
              <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">رقم الرحلة</th>
              <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المسار</th>
              <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">وقت المغادرة</th>
              <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">المقاعد</th>
              <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراء</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php $__empty_1 = true; $__currentLoopData = $upcomingTrips; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trip): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <?php
                $bookedSeats = $trip['booked_seats'] ?? 0;
                $totalSeats  = $trip['total_seats'] ?? 0;
                $seatRatio   = $totalSeats > 0 ? ($bookedSeats / $totalSeats) * 100 : 0;
                if ($seatRatio >= 90) {
                    $seatClass = 'bg-rose-50 text-rose-700 border-rose-200';
                    $seatLabel = 'مكتمل';
                } elseif ($seatRatio >= 50) {
                    $seatClass = 'bg-amber-50 text-amber-600 border-amber-200';
                    $seatLabel = 'متوسط';
                } else {
                    $seatClass = 'bg-sky-50 text-sky-700 border-sky-200';
                    $seatLabel = 'متاح';
                }
                $depDate = $trip['departure_date'] ?? null;
                $arabicDate = '—';
                if ($depDate) {
                    try {
                        $arabicDate = \Carbon\Carbon::parse($depDate)->translatedFormat('j F، Y');
                    } catch (\Throwable $e) {
                        $arabicDate = $depDate;
                    }
                }
              ?>
              <tr class="hover:bg-slate-50/50 transition-colors">
                <td class="px-5 py-3.5">
                  <span class="font-mono font-bold text-slate-600 text-xs" dir="ltr"><?php echo e(\Illuminate\Support\Str::substr($trip['uuid'] ?? '', 0, 8)); ?></span>
                </td>
                <td class="px-5 py-3.5">
                  <div class="flex items-center gap-1.5">
                    <span class="text-xs font-bold text-slate-700"><?php echo e($trip['origin'] ?? '—'); ?></span>
                    <i data-lucide="arrow-left-right" class="w-3 h-3 text-slate-400"></i>
                    <span class="text-xs font-bold text-slate-700"><?php echo e($trip['destination'] ?? '—'); ?></span>
                  </div>
                </td>
                <td class="px-5 py-3.5">
                  <div class="flex flex-col">
                    <span class="text-xs font-bold text-slate-700"><?php echo e($arabicDate); ?></span>
                    <span class="font-mono text-[10px] text-slate-400" dir="ltr"><?php echo e($trip['departure_time'] ?? ''); ?></span>
                  </div>
                </td>
                <td class="px-5 py-3.5 text-center">
                  <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold border <?php echo e($seatClass); ?>">
                    <i data-lucide="armchair" class="w-3 h-3"></i>
                    <?php echo e($bookedSeats); ?>/<?php echo e($totalSeats); ?>

                  </span>
                </td>
                <td class="px-5 py-3.5 text-center">
                  <a href="<?php echo e(Route::has('admin.trips.show') ? route('admin.trips.show', $trip['uuid'] ?? '') : route('admin.trips.index')); ?>" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-sky-50 text-sky-700 text-[10px] font-bold border border-sky-200 hover:bg-sky-100 transition-all">
                    <i data-lucide="eye" class="w-3 h-3"></i>
                    عرض
                  </a>
                </td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr>
                <td colspan="5" class="px-5 py-12 text-center">
                  <div class="flex flex-col items-center gap-3">
                    <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center">
                      <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                    </div>
                    <div>
                      <p class="text-sm font-bold text-slate-600">لا توجد رحلات قادمة</p>
                      <p class="text-xs text-slate-400 mt-1">ستظهر الرحلات القادمة هنا تلقائياً</p>
                    </div>
                  </div>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    
    <div class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-3xl shadow-xl shadow-slate-200/20 flex flex-col overflow-hidden">
      <div class="p-5 border-b border-slate-200/70 bg-white/40 backdrop-blur">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500/20 to-emerald-600/10 flex items-center justify-center border border-emerald-100">
            <i data-lucide="smartphone" class="w-4 h-4 text-emerald-600"></i>
          </div>
          <h3 class="font-bold text-sm text-slate-800">أحدث الحجوزات</h3>
        </div>
      </div>

      <div class="p-4 flex-1 flex flex-col">
        <div class="space-y-3 flex-1">
          <?php $__empty_1 = true; $__currentLoopData = $recentBookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $statusKey   = $booking['status'] ?? 'pending';
              $statusLabel = $booking['status_label'] ?? 'قيد الانتظار';
              $statusBadge = match($statusKey) {
                'paid'      => 'text-emerald-700 bg-emerald-50 border-emerald-200',
                'pending'   => 'text-amber-700 bg-amber-50 border-amber-200',
                'cancelled' => 'text-rose-700 bg-rose-50 border-rose-200',
                default     => 'text-amber-700 bg-amber-50 border-amber-200',
              };
              $statusIcon = match($statusKey) {
                'paid'      => 'check-circle',
                'pending'   => 'clock',
                'cancelled' => 'x-circle',
                default     => 'clock',
              };
            ?>
            <div class="p-3 rounded-xl bg-slate-50/50 border border-slate-200/70 hover:border-slate-300 transition-all">
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-800"><?php echo e($booking['user_name'] ?? 'عميل'); ?></span>
                <span class="text-[9px] font-bold text-slate-400"><?php echo e($booking['time_ago'] ?? ''); ?></span>
              </div>
              <p class="text-[11px] text-slate-500 font-medium mb-2.5"><?php echo e($booking['trip_route'] ?? '—'); ?></p>
              <div class="flex items-center justify-between">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border <?php echo e($statusBadge); ?>">
                  <i data-lucide="<?php echo e($statusIcon); ?>" class="w-2.5 h-2.5"></i>
                  <?php echo e($statusLabel); ?>

                </span>
              </div>
            </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="flex flex-col items-center justify-center py-12 gap-3">
              <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center">
                <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
              </div>
              <div class="text-center">
                <p class="text-sm font-bold text-slate-600">لا توجد حجوزات حديثة</p>
                <p class="text-xs text-slate-400 mt-1">ستظهر الحجوزات الجديدة هنا</p>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <?php if(Route::has('admin.bookings.index')): ?>
          <a href="<?php echo e(route('admin.bookings.index')); ?>" class="w-full py-2.5 text-xs font-bold text-teal-600 hover:bg-teal-50 rounded-xl border border-teal-200 transition-all text-center block mt-3 flex items-center justify-center gap-1.5">
            <span>عرض كل الحجوزات</span>
            <i data-lucide="arrow-left" class="w-3 h-3"></i>
          </a>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
  if (window.lucide) window.lucide.createIcons();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/admin/dashboard.blade.php ENDPATH**/ ?>