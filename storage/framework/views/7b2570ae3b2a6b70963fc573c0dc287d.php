<?php $__env->startSection('title', 'أركاري | رحلات الفرع'); ?>
<?php $__env->startSection('header_title', 'رحلات الفرع'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $currentUser = auth()->user();
    $canCreate = $currentUser && $currentUser->hasPermission('provider:branches:trips:create');
    $canEdit   = $currentUser && $currentUser->hasPermission('provider:branches:trips:edit');
    $canDelete = $currentUser && $currentUser->hasPermission('provider:branches:trips:delete');

    $trips->getCollection()->each(function($t) {
        $t->loadMissing([
            'departureCity', 'destinationCity', 'vehicle.vehicleType',
            'tripStatus', 'typeOfTrip', 'levelTrip',
            'amenities', 'departureStation', 'arrivalStation',
        ]);
    });

    $activeTrips   = $trips->getCollection()->filter(fn($t) => $t->departure_time && $t->departure_time->isFuture())->count();
    $completedTrips = $trips->getCollection()->filter(fn($t) => $t->departure_time && $t->departure_time->isPast())->count();

    $statusBadges = [
        'upcoming'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'completed' => 'bg-slate-100 text-slate-600 border-slate-200',
        'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
    ];
    $statusLabels = [
        'upcoming'  => 'قادمة',
        'completed' => 'مكتملة',
        'cancelled' => 'ملغاة',
    ];
    $statusDots = [
        'upcoming'  => 'bg-emerald-500',
        'completed' => 'bg-slate-400',
        'cancelled' => 'bg-rose-500',
    ];
?>

<div class="space-y-6 font-cairo text-right" dir="rtl">

  
  <?php if(session('success')): ?>
  <div id="toast" class="fixed top-24 left-1/2 -translate-x-1/2 z-[100] bg-[#115128] text-white px-6 py-3 rounded-2xl shadow-2xl shadow-[#115128]/30 flex items-center gap-3 animate-fade-in-up">
    <i data-lucide="check-circle" class="w-5 h-5 text-[#e7bd54]"></i>
    <span class="text-sm font-medium"><?php echo e(session('success')); ?></span>
  </div>
  <?php endif; ?>

  <?php if(session('error')): ?>
  <div id="toast" class="fixed top-24 left-1/2 -translate-x-1/2 z-[100] bg-red-600 text-white px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-3 animate-fade-in-up">
    <i data-lucide="x-circle" class="w-5 h-5"></i>
    <span class="text-sm font-medium"><?php echo e(session('error')); ?></span>
  </div>
  <?php endif; ?>

  
  <div class="bg-white rounded-2xl border border-[#e2e2e5] p-4 sm:p-5">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-teal-50 flex items-center justify-center shrink-0">
          <i data-lucide="bus" class="w-6 h-6 text-[#115128]"></i>
        </div>
        <div>
          <h1 class="text-lg font-bold text-slate-800">رحلات الفرع</h1>
          <p class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1.5">
            <i data-lucide="store" class="w-3 h-3"></i>
            <?php echo e($branch->city?->name ?? 'فرع #' . $branch->id); ?>

          </p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <?php if($canCreate): ?>
          <a href="<?php echo e(route('provider.branches.trips.create', $branch)); ?>" class="flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-xs shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300">
            <i data-lucide="plus" class="w-4 h-4"></i>
            إضافة رحلة
          </a>
        <?php endif; ?>
        <a href="<?php echo e(route('provider.branches.show', $branch)); ?>" class="flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 rounded-xl text-slate-600 text-xs font-bold transition-all duration-300">
          <i data-lucide="arrow-right" class="w-4 h-4"></i>
          العودة للفرع
        </a>
      </div>
    </div>
  </div>

  
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 lg:gap-6">
    <div class="bg-white rounded-2xl border border-[#e2e2e5] p-4 sm:p-5">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center shrink-0">
            <i data-lucide="bus" class="w-5 h-5 text-slate-500"></i>
          </div>
          <div>
            <p class="text-[11px] text-slate-400 font-medium">إجمالي الرحلات</p>
            <p class="text-xl font-black text-slate-800 mt-0.5"><?php echo e($trips->total()); ?></p>
          </div>
        </div>
        <span class="text-[9px] font-bold bg-slate-100 text-slate-500 px-2 py-0.5 rounded-lg">رحلة</span>
      </div>
    </div>
    <div class="bg-white rounded-2xl border border-[#e2e2e5] p-4 sm:p-5">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
            <i data-lucide="clock" class="w-5 h-5 text-emerald-600"></i>
          </div>
          <div>
            <p class="text-[11px] text-slate-400 font-medium">رحلات قادمة</p>
            <p class="text-xl font-black text-emerald-700 mt-0.5"><?php echo e($activeTrips); ?></p>
          </div>
        </div>
        <span class="text-[9px] font-bold bg-emerald-50 text-emerald-600 px-2 py-0.5 rounded-lg">قادمة</span>
      </div>
    </div>
    <div class="bg-white rounded-2xl border border-[#e2e2e5] p-4 sm:p-5">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
            <i data-lucide="check-circle" class="w-5 h-5 text-amber-600"></i>
          </div>
          <div>
            <p class="text-[11px] text-slate-400 font-medium">رحلات مكتملة</p>
            <p class="text-xl font-black text-amber-700 mt-0.5"><?php echo e($completedTrips); ?></p>
          </div>
        </div>
        <span class="text-[9px] font-bold bg-amber-50 text-amber-600 px-2 py-0.5 rounded-lg">مكتملة</span>
      </div>
    </div>
  </div>

  
  <div class="bg-white rounded-2xl border border-[#e2e2e5] overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-[#f9f9fc] border-b border-[#e2e2e5]">
            <th class="px-4 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المسار</th>
            <th class="px-4 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المركبة</th>
            <th class="px-4 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الانطلاق</th>
            <th class="px-4 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الوصول</th>
            <th class="px-4 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">السعر</th>
            <th class="px-4 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
            <th class="px-4 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">إجراءات</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#f0f0f3]">
          <?php $__empty_1 = true; $__currentLoopData = $trips; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trip): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $isUpcoming = $trip->departure_time && $trip->departure_time->isFuture();
              $isPast = $trip->departure_time && $trip->departure_time->isPast();
              $statusKey = $isUpcoming ? 'upcoming' : ($isPast ? 'completed' : 'cancelled');
              $canEditThis = $canEdit && $isUpcoming;
              $canDeleteThis = $canDelete && $isUpcoming && !$trip->bookings()->exists();
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
                    <p class="text-[10px] text-slate-400">
                      <?php echo e($trip->typeOfTrip?->type_name ?? ''); ?>

                      <?php if($trip->levelTrip?->level_name): ?>
                        · <?php echo e($trip->levelTrip->level_name); ?>

                      <?php endif; ?>
                    </p>
                  </div>
                </div>
              </td>
              
              <td class="px-4 py-4">
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="truck" class="w-4 h-4 text-slate-400"></i>
                  </div>
                  <div>
                    <p class="text-[12px] font-bold text-slate-700"><?php echo e($trip->vehicle?->name ?? '—'); ?></p>
                    <p class="text-[10px] text-slate-400"><?php echo e($trip->vehicle?->vehicleType?->type_name ?? ''); ?></p>
                  </div>
                </div>
              </td>
              
              <td class="px-4 py-4">
                <p class="text-[12px] font-bold text-slate-700"><?php echo e($trip->departure_time?->format('d/m/Y')); ?></p>
                <p class="text-[11px] text-slate-400"><?php echo e($trip->departure_time?->format('h:i A')); ?></p>
                <?php if($trip->departureStation): ?>
                  <p class="text-[10px] text-slate-300 mt-0.5 flex items-center gap-1 justify-end">
                    <i data-lucide="map-pin" class="w-3 h-3"></i>
                    <?php echo e(Str::limit($trip->departureStation->name, 20)); ?>

                  </p>
                <?php endif; ?>
              </td>
              
              <td class="px-4 py-4">
                <p class="text-[12px] font-bold text-slate-700"><?php echo e($trip->arrival_time?->format('d/m/Y')); ?></p>
                <p class="text-[11px] text-slate-400"><?php echo e($trip->arrival_time?->format('h:i A')); ?></p>
                <?php if($trip->arrivalStation): ?>
                  <p class="text-[10px] text-slate-300 mt-0.5 flex items-center gap-1 justify-end">
                    <i data-lucide="map-pin" class="w-3 h-3"></i>
                    <?php echo e(Str::limit($trip->arrivalStation->name, 20)); ?>

                  </p>
                <?php endif; ?>
              </td>
              
              <td class="px-4 py-4">
                <div class="flex flex-col gap-0.5">
                  <div class="flex items-center gap-1.5">
                    <span class="text-[10px] text-slate-400">بالغ</span>
                    <span class="text-[13px] font-bold text-[#115128]"><?php echo e(number_format($trip->price_for_adult, 2)); ?></span>
                  </div>
                  <?php if($trip->price_for_child && $trip->price_for_child > 0): ?>
                    <div class="flex items-center gap-1.5">
                      <span class="text-[10px] text-slate-400">طفل</span>
                      <span class="text-[11px] font-bold text-slate-500"><?php echo e(number_format($trip->price_for_child, 2)); ?></span>
                    </div>
                  <?php endif; ?>
                  <?php if($trip->discount_percentage && $trip->discount_percentage > 0): ?>
                    <span class="text-[9px] font-bold text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-full w-fit">
                      خصم <?php echo e($trip->discount_percentage); ?>%
                    </span>
                  <?php endif; ?>
                </div>
              </td>
              
              <td class="px-4 py-4">
                <span class="inline-flex items-center gap-1.5 text-[10px] font-bold px-2.5 py-1 rounded-full border <?php echo e($statusBadges[$statusKey]); ?>">
                  <span class="w-1.5 h-1.5 rounded-full <?php echo e($statusDots[$statusKey]); ?>"></span>
                  <?php echo e($statusLabels[$statusKey]); ?>

                </span>
                <?php if(!$trip->is_public): ?>
                  <p class="text-[9px] text-slate-300 mt-1 flex items-center gap-1 justify-end">
                    <i data-lucide="lock" class="w-3 h-3"></i>
                    مخفية
                  </p>
                <?php endif; ?>
              </td>
              
              <td class="px-4 py-4 text-center">
                <div class="flex items-center justify-center gap-1.5">
                  <?php if($canEditThis): ?>
                    <a href="<?php echo e(route('provider.branches.trips.edit', [$branch, $trip])); ?>" class="w-8 h-8 rounded-lg flex items-center justify-center text-[#115128] hover:bg-teal-50 transition-colors" title="تعديل">
                      <i data-lucide="pencil" class="w-4 h-4"></i>
                    </a>
                  <?php endif; ?>
                  <?php if($canDeleteThis): ?>
                    <form action="<?php echo e(route('provider.branches.trips.destroy', [$branch, $trip])); ?>" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه الرحلة؟')">
                      <?php echo csrf_field(); ?>
                      <?php echo method_field('DELETE'); ?>
                      <button type="submit" class="w-8 h-8 rounded-lg flex items-center justify-center text-rose-500 hover:bg-rose-50 transition-colors" title="حذف">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
              <td colspan="7" class="px-4 py-16 text-center">
                <div class="flex flex-col items-center gap-3">
                  <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center">
                    <i data-lucide="bus" class="w-8 h-8 text-slate-300"></i>
                  </div>
                  <p class="text-sm font-bold text-slate-400">لا توجد رحلات حالياً في هذا الفرع</p>
                  <?php if($canCreate): ?>
                    <a href="<?php echo e(route('provider.branches.trips.create', $branch)); ?>" class="mt-2 px-4 py-2 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl text-xs font-bold hover:shadow-lg transition-all duration-300 flex items-center gap-2">
                      <i data-lucide="plus" class="w-4 h-4"></i>
                      إضافة أول رحلة
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    
    <?php if($trips->hasPages()): ?>
      <div class="px-4 py-3 border-t border-[#e2e2e5] flex items-center justify-between">
        <p class="text-[11px] text-slate-400">عرض <?php echo e($trips->firstItem()); ?> - <?php echo e($trips->lastItem()); ?> من <?php echo e($trips->total()); ?></p>
        <?php echo e($trips->links('components::pagination')); ?>

      </div>
    <?php endif; ?>
  </div>

</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>lucide.createIcons();</script>

<?php echo $__env->make('layouts.provider', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/provider/branches/trips/index.blade.php ENDPATH**/ ?>