<?php $__env->startSection('title', 'حجوزاتي'); ?>

<?php $__env->startSection('content'); ?>

<div class="min-h-screen pt-28 pb-16 px-4 sm:px-6">
<div class="max-w-5xl mx-auto">

  
  <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8 animate-fade-in-up">
    <div class="flex items-center gap-4">
      <div class="w-14 h-14 rounded-2xl bg-[#1f442e] flex items-center justify-center shrink-0">
        <i data-lucide="ticket" class="w-7 h-7 text-[#e7bd54]"></i>
      </div>
      <div>
        <h1 class="text-2xl font-bold text-[#1f442e]">حجوزاتي</h1>
        <p class="text-sm text-slate-400 mt-0.5"><?php echo e($bookings->count()); ?> حجز</p>
      </div>
    </div>
    <a href="<?php echo e(route('home.search')); ?>" class="btn-gold px-5 py-2.5 rounded-xl text-sm flex items-center gap-2 shrink-0">
      <i data-lucide="plus" class="w-4 h-4"></i>
      حجز جديد
    </a>
  </div>

  
  <?php if(session('success')): ?>
  <div class="mb-6 bg-emerald-50 border border-emerald-100 text-emerald-700 px-5 py-3 rounded-2xl text-sm font-medium flex items-center gap-3 animate-fade-in-up">
    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0"></i>
    <?php echo e(session('success')); ?>

  </div>
  <?php endif; ?>
  <?php if(session('error')): ?>
  <div class="mb-6 bg-red-50 border border-red-100 text-red-700 px-5 py-3 rounded-2xl text-sm font-medium flex items-center gap-3 animate-fade-in-up">
    <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 shrink-0"></i>
    <?php echo e(session('error')); ?>

  </div>
  <?php endif; ?>

  
  <?php if($bookings->count() > 0): ?>

    <div class="space-y-4">
      <?php $__currentLoopData = $bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
          $trip = $booking->trip;
          $departure = $trip?->departureCity?->name ?? '---';
          $destination = $trip?->destinationCity?->name ?? '---';
          $departureTime = $trip?->departure_time;
          $isCancelled = in_array($booking->booking_status_id, [
            App\Constants\BookingStatus::CANCELLED,
            App\Constants\BookingStatus::CANCELLED_BY_USER,
            App\Constants\BookingStatus::CANCELLED_BY_ADMIN,
          ]);

          $stCfg = [
            App\Constants\BookingStatus::CONFIRMED => ['bg-emerald-50 text-emerald-600 border-emerald-100', 'check-circle', 'مؤكد'],
            App\Constants\BookingStatus::PENDING   => ['bg-amber-50 text-amber-600 border-amber-100', 'clock', 'قيد الانتظار'],
            App\Constants\BookingStatus::CANCELLED => ['bg-red-50 text-red-500 border-red-100', 'x-circle', 'ملغي'],
            App\Constants\BookingStatus::CANCELLED_BY_USER => ['bg-red-50 text-red-500 border-red-100', 'x-circle', 'ملغي'],
            App\Constants\BookingStatus::CANCELLED_BY_ADMIN => ['bg-red-50 text-red-500 border-red-100', 'shield-x', 'ملغي (إدارة)'],
            App\Constants\BookingStatus::COMPLETED => ['bg-slate-100 text-slate-500 border-slate-200', 'check-check', 'مكتمل'],
            App\Constants\BookingStatus::PAYMENT_FAILED => ['bg-red-50 text-red-500 border-red-100', 'alert-triangle', 'فشل الدفع'],
          ];
          $st = $stCfg[$booking->booking_status_id] ?? ['bg-slate-100 text-slate-500 border-slate-200', 'help-circle', $booking->status->status_name ?? 'غير محدد'];

          $ticketsCount = $booking->tripReservations->sum(fn($r) => $r->tickets->count());

          $payIcon = match($booking->payment->payment_method_id ?? 0) {
            1 => 'banknote', 2 => 'credit-card', 3 => 'landmark', 4 => 'wallet', default => 'credit-card'
          };
        ?>

        <a href="<?php echo e(route('bookings.show', $booking->uuid)); ?>" class="block group">
          <div class="bg-white rounded-3xl border border-[#1f442e]/5 shadow-sm hover:shadow-lg hover:shadow-[#1f442e]/5 transition-all duration-300 overflow-hidden animate-fade-in-up <?php echo e($isCancelled ? 'opacity-50' : ''); ?>" style="animation-delay: <?php echo e($idx * 50); ?>ms">

            <div class="p-5 sm:p-6">
              <div class="flex items-start gap-4">

                
                <div class="flex-1 min-w-0">

                  
                  <div class="flex items-center gap-3 mb-4">
                    <div class="flex items-center gap-2 flex-1 min-w-0">
                      <div class="w-8 h-8 rounded-lg bg-[#1f442e] text-white flex items-center justify-center text-xs font-bold shrink-0">
                        <?php echo e(mb_substr($departure, 0, 1)); ?>

                      </div>
                      <div class="flex items-center gap-2 flex-1 min-w-0">
                        <span class="text-sm font-bold text-[#1f442e] truncate"><?php echo e($departure); ?></span>
                        <div class="flex items-center gap-1 shrink-0">
                          <div class="w-4 h-px bg-[#1f442e]/20"></div>
                          <i data-lucide="bus" class="w-3.5 h-3.5 text-[#e7bd54]"></i>
                          <div class="w-4 h-px bg-[#1f442e]/20"></div>
                        </div>
                        <span class="text-sm font-bold text-[#1f442e] truncate"><?php echo e($destination); ?></span>
                        <div class="w-8 h-8 rounded-lg bg-[#438656]/10 text-[#438656] flex items-center justify-center text-xs font-bold shrink-0">
                          <?php echo e(mb_substr($destination, 0, 1)); ?>

                        </div>
                      </div>
                    </div>
                  </div>

                  
                  <div class="flex flex-wrap items-center gap-2">
                    <?php if($departureTime): ?>
                      <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#f9f5ec] rounded-lg text-[10px] font-bold text-[#1f442e]/60">
                        <i data-lucide="calendar" class="w-3 h-3"></i>
                        <?php echo e(\Carbon\Carbon::parse($departureTime)->format('d M Y')); ?>

                      </span>
                    <?php endif; ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#f9f5ec] rounded-lg text-[10px] font-bold text-[#1f442e]/60">
                      <i data-lucide="users" class="w-3 h-3"></i>
                      <?php echo e($ticketsCount); ?> تذكرة
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#f9f5ec] rounded-lg text-[10px] font-bold text-[#1f442e]/60">
                      <i data-lucide="<?php echo e($payIcon); ?>" class="w-3 h-3"></i>
                      <?php echo e($booking->payment->paymentMethod->method_name ?? '---'); ?>

                    </span>
                    <span class="text-[9px] text-slate-300 font-mono">
                      <?php echo e(\Illuminate\Support\Str::substr($booking->uuid, 0, 8)); ?>

                    </span>
                  </div>
                </div>

                
                <div class="flex flex-col items-end gap-3 shrink-0">

                  
                  <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-[10px] font-bold <?php echo e($st[0]); ?>">
                    <i data-lucide="<?php echo e($st[1]); ?>" class="w-3.5 h-3.5"></i>
                    <?php echo e($st[2]); ?>

                  </span>

                  
                  <div class="text-left">
                    <p class="text-lg font-bold text-[#1f442e] leading-tight">
                      <?php echo e(number_format($booking->payment->payed_amount ?? $booking->amount, 2)); ?>

                    </p>
                    <p class="text-[10px] text-slate-400 font-medium">
                      <?php echo e($booking->currency->code ?? 'YER'); ?>

                    </p>
                  </div>

                  
                  <div class="w-8 h-8 rounded-lg bg-[#f9f5ec] flex items-center justify-center text-[#1f442e]/30 group-hover:bg-[#1f442e] group-hover:text-[#e7bd54] transition-all duration-300">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                  </div>

                </div>

              </div>
            </div>

          </div>
        </a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    
    <?php if($bookings->hasPages()): ?>
      <div class="mt-8 flex items-center justify-center gap-1">
        <?php echo e($bookings->links()); ?>

      </div>
    <?php endif; ?>

  <?php else: ?>

    
    <div class="bg-white rounded-3xl border border-[#1f442e]/5 shadow-sm p-12 sm:p-16 text-center animate-fade-in-up">
      <div class="w-20 h-20 rounded-3xl bg-[#f9f5ec] flex items-center justify-center mx-auto mb-6">
        <i data-lucide="ticket" class="w-10 h-10 text-[#1f442e]/20"></i>
      </div>
      <h3 class="text-lg font-bold text-[#1f442e] mb-2">لا توجد حجوزات</h3>
      <p class="text-sm text-slate-400 mb-8 max-w-sm mx-auto">لم تقم بأي حجز بعد. ابدأ الآن بالبحث عن رحلة وحجز مقعدك.</p>
      <a href="<?php echo e(route('home.search')); ?>" class="btn-gold inline-flex items-center gap-2 px-8 py-3 rounded-xl text-sm">
        <i data-lucide="search" class="w-4 h-4"></i>
        بحث عن رحلات
      </a>
    </div>

  <?php endif; ?>

</div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>lucide.createIcons();</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.web', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/user/bookings.blade.php ENDPATH**/ ?>