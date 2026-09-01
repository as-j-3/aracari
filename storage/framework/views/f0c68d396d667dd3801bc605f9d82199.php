<?php $__env->startSection('title', 'حسابي'); ?>

<?php $__env->startSection('content'); ?>

<div class="min-h-screen pt-28 pb-16 px-4 sm:px-6">
<div class="max-w-5xl mx-auto">

  
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

  
  <div class="bg-white rounded-3xl border border-[#1f442e]/5 shadow-sm overflow-hidden animate-fade-in-up mb-8">
    <div class="h-28 sm:h-32 bg-gradient-to-l from-[#1f442e] via-[#2d5e40] to-[#1f442e] relative">
      <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 20% 50%, #e7bd54 1px, transparent 1px), radial-gradient(circle at 80% 50%, #e7bd54 1px, transparent 1px); background-size: 40px 40px;"></div>
    </div>
    <div class="px-6 sm:px-8 pb-6 -mt-12 relative">
      <div class="flex flex-col sm:flex-row items-start sm:items-end gap-4">
        
        <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-[#e7bd54] to-[#d49920] flex items-center justify-center shadow-xl shadow-[#e7bd54]/20 ring-4 ring-white shrink-0">
          <span class="text-[#0c1d14] font-black text-3xl"><?php echo e(mb_substr($user->name, 0, 1)); ?></span>
        </div>
        <div class="flex-1 min-w-0 pb-1">
          <h1 class="text-xl sm:text-2xl font-bold text-[#1f442e] truncate"><?php echo e($user->name); ?></h1>
          <div class="flex flex-wrap items-center gap-3 mt-1.5">
            <?php if($user->email): ?>
              <span class="inline-flex items-center gap-1.5 text-xs text-slate-400">
                <i data-lucide="mail" class="w-3.5 h-3.5"></i><?php echo e($user->email); ?>

              </span>
            <?php endif; ?>
            <?php if($user->phone_number): ?>
              <span class="inline-flex items-center gap-1.5 text-xs text-slate-400">
                <i data-lucide="phone" class="w-3.5 h-3.5"></i><?php echo e($user->phone_number); ?>

                <?php if($user->phone_verified_at): ?>
                  <i data-lucide="badge-check" class="w-3.5 h-3.5 text-emerald-500"></i>
                <?php else: ?>
                  <span class="px-1.5 py-0.5 bg-amber-50 text-amber-600 rounded text-[9px] font-bold">غير مفعل</span>
                <?php endif; ?>
              </span>
            <?php endif; ?>
          </div>
        </div>
        <a href="<?php echo e(route('profile.edit')); ?>" class="btn-gold px-5 py-2.5 rounded-xl text-sm flex items-center gap-2 shrink-0">
          <i data-lucide="pen-line" class="w-4 h-4"></i>
          تعديل البيانات
        </a>
      </div>
    </div>
  </div>

  
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-8 animate-fade-in-up animate-delay-100">
    <div class="bg-white rounded-2xl border border-[#1f442e]/5 shadow-sm p-4 sm:p-5">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-10 h-10 rounded-xl bg-[#1f442e] flex items-center justify-center">
          <i data-lucide="ticket" class="w-5 h-5 text-[#e7bd54]"></i>
        </div>
      </div>
      <p class="text-2xl font-bold text-[#1f442e]"><?php echo e($stats['totalBookings']); ?></p>
      <p class="text-[10px] text-slate-400 uppercase tracking-wider font-bold mt-1">إجمالي الحجوزات</p>
    </div>
    <div class="bg-white rounded-2xl border border-[#1f442e]/5 shadow-sm p-4 sm:p-5">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center">
          <i data-lucide="calendar-check" class="w-5 h-5 text-emerald-600"></i>
        </div>
      </div>
      <p class="text-2xl font-bold text-[#1f442e]"><?php echo e($stats['upcomingBookings']); ?></p>
      <p class="text-[10px] text-slate-400 uppercase tracking-wider font-bold mt-1">قادمة / نشطة</p>
    </div>
    <div class="bg-white rounded-2xl border border-[#1f442e]/5 shadow-sm p-4 sm:p-5">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center">
          <i data-lucide="check-check" class="w-5 h-5 text-blue-600"></i>
        </div>
      </div>
      <p class="text-2xl font-bold text-[#1f442e]"><?php echo e($stats['completedBookings']); ?></p>
      <p class="text-[10px] text-slate-400 uppercase tracking-wider font-bold mt-1">مكتملة</p>
    </div>
    <div class="bg-white rounded-2xl border border-[#1f442e]/5 shadow-sm p-4 sm:p-5">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-10 h-10 rounded-xl bg-red-500/10 flex items-center justify-center">
          <i data-lucide="x-circle" class="w-5 h-5 text-red-500"></i>
        </div>
      </div>
      <p class="text-2xl font-bold text-[#1f442e]"><?php echo e($stats['cancelledBookings']); ?></p>
      <p class="text-[10px] text-slate-400 uppercase tracking-wider font-bold mt-1">ملغية</p>
    </div>
  </div>

  
  <div class="mb-8 animate-fade-in-up animate-delay-200">
    <h2 class="text-xs text-slate-400 uppercase tracking-wider font-bold mb-4 flex items-center gap-2">
      <i data-lucide="grid-3x3" class="w-3.5 h-3.5"></i>
      خدماتي السريعة
    </h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">

      
      <a href="<?php echo e(route('bookings')); ?>" class="group bg-white rounded-2xl border border-[#1f442e]/5 shadow-sm p-5 hover:shadow-lg hover:shadow-[#1f442e]/5 transition-all duration-300 hover:-translate-y-0.5">
        <div class="w-12 h-12 rounded-2xl bg-[#1f442e] flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
          <i data-lucide="ticket" class="w-6 h-6 text-[#e7bd54]"></i>
        </div>
        <h3 class="text-sm font-bold text-[#1f442e] mb-1">حجوزاتي</h3>
        <p class="text-[11px] text-slate-400 leading-relaxed">عرض وإدارة جميع حجوزاتك</p>
      </a>

      
      <a href="<?php echo e(route('profile.edit')); ?>" class="group bg-white rounded-2xl border border-[#1f442e]/5 shadow-sm p-5 hover:shadow-lg hover:shadow-[#1f442e]/5 transition-all duration-300 hover:-translate-y-0.5">
        <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
          <i data-lucide="user-pen" class="w-6 h-6 text-emerald-600"></i>
        </div>
        <h3 class="text-sm font-bold text-[#1f442e] mb-1">تعديل البيانات</h3>
        <p class="text-[11px] text-slate-400 leading-relaxed">تحديث الملف الشخصي</p>
      </a>

      
      <a href="<?php echo e(route('wallet.index')); ?>" class="group bg-white rounded-2xl border border-[#1f442e]/5 shadow-sm p-5 hover:shadow-lg hover:shadow-[#1f442e]/5 transition-all duration-300 hover:-translate-y-0.5">
        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
          <i data-lucide="wallet" class="w-6 h-6 text-amber-600"></i>
        </div>
        <h3 class="text-sm font-bold text-[#1f442e] mb-1">محفظتي</h3>
        <p class="text-[11px] text-slate-400 leading-relaxed">رصيدك والمعاملات المالية</p>
      </a>

      
      <?php if(!$user->phone_verified_at): ?>
      <a href="<?php echo e(route('profile.phone.verify')); ?>" class="group bg-white rounded-2xl border border-amber-200 shadow-sm p-5 hover:shadow-lg hover:shadow-amber-100 transition-all duration-300 hover:-translate-y-0.5 relative overflow-hidden">
        <div class="absolute top-2 left-2">
          <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[9px] font-bold">
            <i data-lucide="alert-triangle" class="w-2.5 h-2.5"></i>
            مطلوب
          </span>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
          <i data-lucide="smartphone" class="w-6 h-6 text-amber-600"></i>
        </div>
        <h3 class="text-sm font-bold text-[#1f442e] mb-1">تفعيل الهاتف</h3>
        <p class="text-[11px] text-slate-400 leading-relaxed">أكد رقم هاتفك للحجز</p>
      </a>
      <?php else: ?>
      
      <a href="<?php echo e(route('home.search')); ?>" class="group bg-white rounded-2xl border border-[#1f442e]/5 shadow-sm p-5 hover:shadow-lg hover:shadow-[#1f442e]/5 transition-all duration-300 hover:-translate-y-0.5">
        <div class="w-12 h-12 rounded-2xl bg-blue-500/10 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform duration-300">
          <i data-lucide="search" class="w-6 h-6 text-blue-600"></i>
        </div>
        <h3 class="text-sm font-bold text-[#1f442e] mb-1">بحث رحلات</h3>
        <p class="text-[11px] text-slate-400 leading-relaxed">ابحث عن رحلة جديدة</p>
      </a>
      <?php endif; ?>

    </div>
  </div>

  
  <?php if($recentBookings->count() > 0): ?>
  <div class="animate-fade-in-up animate-delay-300">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xs text-slate-400 uppercase tracking-wider font-bold flex items-center gap-2">
        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
        آخر الحجوزات
      </h2>
      <a href="<?php echo e(route('bookings')); ?>" class="text-xs text-[#1f442e]/60 hover:text-[#1f442e] font-medium flex items-center gap-1 transition-colors">
        عرض الكل
        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
      </a>
    </div>
    <div class="space-y-3">
      <?php $__currentLoopData = $recentBookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
          $trip = $booking->trip;
          $departure = $trip?->departureCity?->name ?? '---';
          $destination = $trip?->destinationCity?->name ?? '---';
          $departureTime = $trip?->departure_time;

          $stCfg = [
            App\Constants\BookingStatus::CONFIRMED => ['bg-emerald-50 text-emerald-600', 'check-circle', 'مؤكد'],
            App\Constants\BookingStatus::PENDING   => ['bg-amber-50 text-amber-600', 'clock', 'قيد الانتظار'],
            App\Constants\BookingStatus::COMPLETED => ['bg-slate-100 text-slate-500', 'check-check', 'مكتمل'],
          ];
          $isCancelled = in_array($booking->booking_status_id, App\Constants\BookingStatus::getCancelledStatuses());
          $st = $stCfg[$booking->booking_status_id] ?? ['bg-red-50 text-red-500', 'x-circle', 'ملغي'];
        ?>

        <a href="<?php echo e(route('bookings.show', $booking->uuid)); ?>" class="block group">
          <div class="bg-white rounded-2xl border border-[#1f442e]/5 shadow-sm hover:shadow-md transition-all duration-300 p-4 <?php echo e($isCancelled ? 'opacity-50' : ''); ?>">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-[#f9f5ec] flex items-center justify-center text-xs font-bold text-[#1f442e]/60 shrink-0">
                <?php echo e(mb_substr($departure, 0, 1)); ?>

              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 text-sm">
                  <span class="font-bold text-[#1f442e] truncate"><?php echo e($departure); ?></span>
                  <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-[#e7bd54] shrink-0"></i>
                  <span class="font-bold text-[#1f442e] truncate"><?php echo e($destination); ?></span>
                </div>
                <?php if($departureTime): ?>
                  <p class="text-[10px] text-slate-400 mt-0.5"><?php echo e(\Carbon\Carbon::parse($departureTime)->format('d M Y - h:i A')); ?></p>
                <?php endif; ?>
              </div>
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold <?php echo e($st[0]); ?> shrink-0">
                <i data-lucide="<?php echo e($st[1]); ?>" class="w-3 h-3"></i><?php echo e($st[2]); ?>

              </span>
              <i data-lucide="chevron-left" class="w-4 h-4 text-slate-300 group-hover:text-[#1f442e] transition-colors shrink-0"></i>
            </div>
          </div>
        </a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  </div>
  <?php endif; ?>

</div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>lucide.createIcons();</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.web', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/user/dashboard.blade.php ENDPATH**/ ?>