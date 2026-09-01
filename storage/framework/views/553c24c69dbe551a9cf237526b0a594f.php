<?php $__env->startSection('title', 'أركاري | الحجوزات'); ?>
<?php $__env->startSection('header_title', 'الحجوزات'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6 font-cairo text-right" dir="rtl">

  
  <div class="bg-white rounded-2xl border border-[#e2e2e5] p-4">
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">

      
      <form method="GET" action="<?php echo e(route('provider.bookings.index')); ?>" class="flex items-center gap-2 flex-1">
        <div class="relative flex-1 max-w-xs">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="filter" class="w-4 h-4"></i>
          </div>
          <select
            name="branch"
            onchange="this.form.submit()"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all appearance-none cursor-pointer"
          >
            <option value="">كل الفروع</option>
            <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($branch->id); ?>" <?php echo e($selectedBranch == $branch->id ? 'selected' : ''); ?>>
                <?php echo e($branch->city?->name ?? 'فرع #' . $branch->id); ?>

              </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </div>
        <?php if($selectedBranch): ?>
          <a href="<?php echo e(route('provider.bookings.index')); ?>" class="text-[11px] text-rose-500 hover:text-rose-700 font-bold flex items-center gap-1 whitespace-nowrap">
            <i data-lucide="x" class="w-3 h-3"></i>
            إزالة الفلتر
          </a>
        <?php endif; ?>
      </form>

      
      <div class="flex items-center gap-2">
        <span class="text-[10px] text-slate-400 flex items-center gap-1">
          <i data-lucide="info" class="w-3 h-3"></i>
          عرض الحجوزات من جميع الفروع — لإنشاء حجز ادخل الفرع
        </span>
      </div>
    </div>
  </div>

  
  <div class="bg-white rounded-2xl border border-[#e2e2e5] overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-[#f9f9fc] border-b border-[#e2e2e5]">
            <th class="px-4 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">رقم الحجز</th>
            <th class="px-4 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">المسافر</th>
            <th class="px-4 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">الرحلة</th>
            <th class="px-4 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">الفرع</th>
            <th class="px-4 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">التاريخ</th>
            <th class="px-4 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
            <th class="px-4 py-3 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">إجراءات</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#f0f0f3]">
          <?php $__empty_1 = true; $__currentLoopData = $bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $firstReservation = $booking->tripReservations->first();
              $trip = $firstReservation?->trip;
              $branch = $trip?->service?->branch;
            ?>
            <tr class="hover:bg-slate-50/50 transition-colors">
              <td class="px-4 py-3">
                <span class="font-bold text-slate-800 text-[13px] font-mono"><?php echo e($booking->uuid ? substr($booking->uuid, 0, 8) : '#' . $booking->id); ?></span>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="user" class="w-4 h-4 text-slate-500"></i>
                  </div>
                  <span class="text-[13px] font-bold text-slate-700"><?php echo e($booking->user?->name ?? '—'); ?></span>
                </div>
              </td>
              <td class="px-4 py-3">
                <?php if($trip): ?>
                  <div class="flex items-center gap-1.5 text-[12px]">
                    <span class="font-bold text-slate-700"><?php echo e($trip->departureCity?->name ?? '—'); ?></span>
                    <i data-lucide="arrow-left" class="w-3 h-3 text-slate-300"></i>
                    <span class="font-bold text-slate-700"><?php echo e($trip->destinationCity?->name ?? '—'); ?></span>
                  </div>
                  <p class="text-[10px] text-slate-400 mt-0.5"><?php echo e($trip->departure_time?->format('d/m/Y h:i A')); ?></p>
                <?php else: ?>
                  <span class="text-[12px] text-slate-400">—</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center gap-1 text-[11px] font-bold bg-slate-100 text-slate-600 px-2 py-1 rounded-lg">
                  <i data-lucide="store" class="w-3 h-3"></i>
                  <?php echo e($branch?->city?->name ?? '—'); ?>

                </span>
              </td>
              <td class="px-4 py-3">
                <p class="text-[12px] font-bold text-slate-700"><?php echo e($booking->created_at?->format('d/m/Y')); ?></p>
                <p class="text-[11px] text-slate-400"><?php echo e($booking->created_at?->format('h:i A')); ?></p>
              </td>
              <td class="px-4 py-3">
                <?php
                  $statusName = $booking->status?->status_name ?? 'غير معروف';
                  $statusColor = match($statusName) {
                    'confirmed', 'مؤكد' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                    'pending', 'معلق' => 'bg-amber-100 text-amber-700 border-amber-200',
                    'cancelled', 'ملغي' => 'bg-rose-100 text-rose-700 border-rose-200',
                    default => 'bg-slate-100 text-slate-600 border-slate-200',
                  };
                ?>
                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full border <?php echo e($statusColor); ?>">
                  <?php echo e($statusName); ?>

                </span>
              </td>
              <td class="px-4 py-3 text-center">
                <?php if($branch): ?>
                  <div class="flex items-center justify-center gap-1">
                    <a href="<?php echo e(route('provider.branches.bookings.show', [$branch, $booking])); ?>"
                       class="w-8 h-8 rounded-lg flex items-center justify-center text-[#115128] hover:bg-teal-50 transition-colors" title="عرض">
                      <i data-lucide="eye" class="w-4 h-4"></i>
                    </a>
                  </div>
                <?php else: ?>
                  <span class="text-[11px] text-slate-400">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
              <td colspan="7" class="px-4 py-12 text-center">
                <div class="flex flex-col items-center gap-3">
                  <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center">
                    <i data-lucide="ticket" class="w-8 h-8 text-slate-300"></i>
                  </div>
                  <p class="text-sm font-bold text-slate-400">لا توجد حجوزات حالياً</p>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    
    <?php if($bookings->hasPages()): ?>
      <div class="px-4 py-3 border-t border-[#e2e2e5] flex items-center justify-between">
        <p class="text-[11px] text-slate-400">عرض <?php echo e($bookings->firstItem()); ?> - <?php echo e($bookings->lastItem()); ?> من <?php echo e($bookings->total()); ?></p>
        <?php echo e($bookings->links('components::pagination')); ?>

      </div>
    <?php endif; ?>
  </div>

</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>lucide.createIcons();</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.provider', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/provider/bookings/index.blade.php ENDPATH**/ ?>