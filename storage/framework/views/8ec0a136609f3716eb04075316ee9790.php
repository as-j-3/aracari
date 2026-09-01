<?php $__env->startSection('title', 'أركاري | لوحة تحكم المزود'); ?>
<?php $__env->startSection('header_title', 'لوحة التحكم'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6 font-cairo text-right" dir="rtl">

    
    <div class="relative overflow-hidden rounded-3xl bg-white/70 backdrop-blur-xl p-6 border border-slate-200/70 shadow-sm">
        
        <div class="absolute top-0 left-0 w-64 h-64 bg-teal-500/5 rounded-full blur-3xl -translate-y-1/2 -translate-x-1/2 pointer-events-none"></div>
        <div class="absolute bottom-0 right-0 w-48 h-48 bg-emerald-500/5 rounded-full blur-3xl translate-y-1/2 translate-x-1/2 pointer-events-none"></div>

        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-teal-500 to-emerald-600 flex items-center justify-center shadow-lg shadow-teal-500/20">
                    <i data-lucide="bar-chart-3" class="w-7 h-7 text-white"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2">
                        لوحة تحكم المزود
                        <span class="text-[10px] font-bold text-teal-600 bg-teal-50 px-2.5 py-1 rounded-full border border-teal-200">
                            نظرة عامة
                        </span>
                    </h2>
                    <p class="text-slate-500 font-medium text-sm flex items-center gap-2 mt-1">
                        <i data-lucide="trending-up" class="w-4 h-4 text-slate-400"></i>
                        نظرة عامة على أداء نشاطك
                    </p>
                </div>
            </div>

            
            <div class="flex items-center gap-2 text-xs text-slate-500 bg-slate-50/70 px-3 py-2 rounded-xl border border-slate-200">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-emerald-500"></i>
                <span>آخر تحديث: <?php echo e(now()->format('H:i')); ?></span>
            </div>
        </div>
    </div>



    
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">

        
        <div class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 to-slate-800 p-6 border border-white/5 hover:border-emerald-500/20 transition-all duration-500 hover:shadow-2xl hover:shadow-emerald-500/10 hover:-translate-y-1">
            <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-emerald-500/10 to-transparent rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10 flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">إجمالي الإيرادات</p>
                    <h3 class="text-3xl font-extrabold text-white mt-1.5 flex items-baseline gap-1">
                        <?php echo e(number_format($totalRevenue, 0, '.', ',')); ?>

                        <span class="text-sm text-slate-400 font-bold">ر.س</span>
                    </h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/20 group-hover:shadow-emerald-500/40 group-hover:scale-110 transition-all duration-300">
                    <i data-lucide="wallet" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        
        <div class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-900 to-blue-800 p-6 border border-white/5 hover:border-blue-500/20 transition-all duration-500 hover:shadow-2xl hover:shadow-blue-500/10 hover:-translate-y-1">
            <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-blue-500/10 to-transparent rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10 flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold text-blue-300/70 uppercase tracking-wider">إجمالي الحجوزات</p>
                    <h3 class="text-3xl font-extrabold text-white mt-1.5"><?php echo e(number_format($totalBookings)); ?></h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/20 group-hover:shadow-blue-500/40 group-hover:scale-110 transition-all duration-300">
                    <i data-lucide="ticket" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        
        <div class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-purple-900 to-purple-800 p-6 border border-white/5 hover:border-purple-500/20 transition-all duration-500 hover:shadow-2xl hover:shadow-purple-500/10 hover:-translate-y-1">
            <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-purple-500/10 to-transparent rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10 flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold text-purple-300/70 uppercase tracking-wider">الرحلات النشطة</p>
                    <h3 class="text-3xl font-extrabold text-white mt-1.5"><?php echo e(number_format($activeTrips)); ?></h3>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="inline-flex items-center gap-1 text-purple-400 text-xs font-bold bg-purple-400/10 px-2.5 py-1 rounded-full border border-purple-400/20">
                            <i data-lucide="bus" class="w-3 h-3"></i>
                            قيد التشغيل
                        </span>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-lg shadow-purple-500/20 group-hover:shadow-purple-500/40 group-hover:scale-110 transition-all duration-300">
                    <i data-lucide="bus" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        
        <div class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-amber-900 to-amber-800 p-6 border border-white/5 hover:border-amber-500/20 transition-all duration-500 hover:shadow-2xl hover:shadow-amber-500/10 hover:-translate-y-1">
            <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-amber-500/10 to-transparent rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10 flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold text-amber-300/70 uppercase tracking-wider">الرحلات المكتملة</p>
                    <h3 class="text-3xl font-extrabold text-white mt-1.5"><?php echo e(number_format($completedTrips)); ?></h3>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="inline-flex items-center gap-1 text-amber-400 text-xs font-bold bg-amber-400/10 px-2.5 py-1 rounded-full border border-amber-400/20">
                            <i data-lucide="check-circle" class="w-3 h-3"></i>
                            مكتملة بنجاح
                        </span>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/20 group-hover:shadow-amber-500/40 group-hover:scale-110 transition-all duration-300">
                    <i data-lucide="check-circle" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>
    </div>



    
    <div class="bg-white/70 backdrop-blur-xl border border-slate-200/70 rounded-3xl overflow-hidden shadow-sm">
        
        <div class="p-5 border-b border-slate-200/70 bg-white/40 backdrop-blur flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center text-white shadow-md">
                    <i data-lucide="trending-up" class="w-4 h-4"></i>
                </div>
                أداء الرحلات
            </h3>
            <span class="text-[11px] text-slate-400 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200">
                (<?php echo e($startDate->translatedFormat('j F، Y')); ?> ← <?php echo e($endDate->translatedFormat('j F، Y')); ?>)
            </span>
        </div>

        <?php if(count($tripPerformance) > 0): ?>

            <div class="overflow-x-auto min-w-0">
                <table class="w-full min-w-[640px]">
                    <thead class="bg-slate-50/70 border-b border-slate-200/70">
                        <tr>
                            <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الرحلة</th>
                            <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">تاريخ المغادرة</th>
                            <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">المقاعد المحجوزة</th>
                            <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">نسبة الامتلاء</th>
                            <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإيرادات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php $__currentLoopData = $tripPerformance; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trip): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                // Determine occupancy bar color
                                $occRate = (float) ($trip->occupancy ?? $trip->occupancy_rate ?? 0);
                                $occColor = $occRate >= 80 ? 'bg-emerald-500' : ($occRate >= 50 ? 'bg-amber-500' : 'bg-rose-500');

                                // Trip route display
                                $departure = $trip->departure_city ?? $trip->departureCity ?? '';
                                $dest      = $trip->destination_city ?? $trip->destinationCity ?? '';
                                $depDate   = $trip->departure_time ?? $trip->departureTime ?? null;
                                if ($depDate instanceof \Carbon\Carbon) {
                                    $depDateStr = $depDate->translatedFormat('j F، Y');
                                } else {
                                    $depDateStr = $depDate ?? '—';
                                }
                                $booked = (int) ($trip->bookings_count ?? $trip->bookedSeats ?? $trip->bookings ?? 0);
                                $total  = (int) ($trip->total_seats ?? $trip->totalSeats ?? 0);
                                $revenue = (float) ($trip->revenue ?? $trip->total_revenue ?? 0);
                            ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-1.5 text-sm font-bold text-slate-800">
                                        <span><?php echo e($departure); ?></span>
                                        <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span><?php echo e($dest); ?></span>
                                    </div>
                                </td>

                                
                                <td class="px-6 py-3.5 text-xs text-slate-600 font-medium" dir="ltr">
                                    <?php echo e($depDateStr); ?>

                                </td>

                                
                                <td class="px-6 py-3.5 text-center">
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-slate-700">
                                        <span class="text-emerald-600"><?php echo e($booked); ?></span>
                                        <span class="text-slate-400">/ <?php echo e($total); ?></span>
                                    </span>
                                </td>

                                
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-slate-200 rounded-full h-2 overflow-hidden min-w-[80px]">
                                            <div class="<?php echo e($occColor); ?> h-2 rounded-full transition-all duration-700"
                                                 style="width: <?php echo e(min($occRate, 100)); ?>%"></div>
                                        </div>
                                        <span class="text-xs font-bold text-slate-600 w-12 text-left">
                                            <?php echo e(number_format($occRate, 1)); ?>%
                                        </span>
                                    </div>
                                </td>

                                
                                <td class="px-6 py-3.5">
                                    <span class="text-sm font-bold text-emerald-600 flex items-center gap-1">
                                        <?php echo e(number_format($revenue, 0, '.', ',')); ?>

                                        <span class="text-[10px] text-slate-400 font-normal">ر.س</span>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="flex flex-col items-center justify-center py-16 text-slate-400">
                <i data-lucide="bus" class="w-12 h-12 mb-3 opacity-40"></i>
                <p class="text-sm font-bold">لا توجد بيانات أداء رحلات في هذه الفترة</p>
            </div>
        <?php endif; ?>
    </div>

    
    <div class="bg-white/70 backdrop-blur-xl border border-slate-200/70 rounded-3xl overflow-hidden shadow-sm">
        
        <div class="p-5 border-b border-slate-200/70 bg-white/40 backdrop-blur flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white shadow-md">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </div>
                آخر الحجوزات
            </h3>
            <?php if(Route::has('provider.branches.index')): ?>
                <a href="<?php echo e(route('provider.branches.index')); ?>" class="text-xs font-bold text-teal-600 hover:text-teal-700 flex items-center gap-1">
                    عرض الكل
                    <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>
                </a>
            <?php endif; ?>
        </div>

        <?php if($recentBookings->count() > 0): ?>
            <div class="overflow-x-auto min-w-0">
                <table class="w-full min-w-[640px]">
                    <thead class="bg-slate-50/70 border-b border-slate-200/70">
                        <tr>
                            <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">رقم الحجز</th>
                            <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المستخدم</th>
                            <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الرحلة</th>
                            <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
                            <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php $__currentLoopData = $recentBookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                // Status badge styling
                                $statusName = $booking->status?->name ?? 'غير محدد';
                                $statusKey  = $booking->status?->key ?? '';
                                if ($statusKey === 'confirmed' || $statusKey === 'completed') {
                                    $statusClasses = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                } elseif ($statusKey === 'cancelled' || $statusKey === 'rejected') {
                                    $statusClasses = 'bg-rose-50 text-rose-700 border-rose-200';
                                } else {
                                    $statusClasses = 'bg-amber-50 text-amber-700 border-amber-200';
                                }

                                // User info
                                $userName = $booking->user?->name ?? 'مستخدم';
                                $userInitial = mb_substr($userName, 0, 1);

                                // Trip route from first reservation
                                $tripRoute = '—';
                                $firstRes = $booking->tripReservations?->first();
                                if ($firstRes?->trip) {
                                    $dep = $firstRes->trip->departureCity?->name ?? '';
                                    $dst = $firstRes->trip->destinationCity?->name ?? '';
                                    if ($dep && $dst) {
                                        $tripRoute = $dep . ' ← ' . $dst;
                                    } elseif ($firstRes->trip->name) {
                                        $tripRoute = $firstRes->trip->name;
                                    }
                                }

                                // Booking amount
                                $amount = (float) ($booking->total_amount ?? $booking->amount ?? $booking->total ?? 0);
                                $bookingRef = $booking->uuid ?? $booking->id ?? '—';
                            ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                
                                <td class="px-6 py-3.5">
                                    <span class="text-xs font-mono font-bold text-slate-800">#<?php echo e($bookingRef); ?></span>
                                </td>

                                
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center text-white font-bold text-xs flex-shrink-0">
                                            <?php echo e($userInitial); ?>

                                        </div>
                                        <span class="text-xs font-bold text-slate-800"><?php echo e($userName); ?></span>
                                    </div>
                                </td>

                                
                                <td class="px-6 py-3.5">
                                    <span class="text-xs text-slate-600 font-medium"><?php echo e($tripRoute); ?></span>
                                </td>

                                
                                <td class="px-6 py-3.5 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold border <?php echo e($statusClasses); ?>">
                                        <?php echo e($statusName); ?>

                                    </span>
                                </td>

                                
                                <td class="px-6 py-3.5">
                                    <span class="text-sm font-bold text-emerald-600 flex items-center gap-1">
                                        <?php echo e(number_format($amount, 0, '.', ',')); ?>

                                        <span class="text-[10px] text-slate-400 font-normal">ر.س</span>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="flex flex-col items-center justify-center py-16 text-slate-400">
                <i data-lucide="ticket" class="w-12 h-12 mb-3 opacity-40"></i>
                <p class="text-sm font-bold">لا توجد حجوزات حديثة</p>
            </div>
        <?php endif; ?>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.provider', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/provider/dashboard.blade.php ENDPATH**/ ?>