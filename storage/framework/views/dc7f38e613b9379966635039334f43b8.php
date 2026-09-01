<?php $__env->startSection('title', ($trip->trip_name ?? ($trip->departureCity->name . ' → ' . $trip->destinationCity->name)) . ' | أراكاري'); ?>

<?php $__env->startSection('content'); ?>

<?php
  // ── Calculations ──
  $hasDiscount = $trip->discount_percentage > 0;
  $displayPriceAdult = $hasDiscount ? ($trip->price_for_adult * (1 - $trip->discount_percentage / 100)) : $trip->price_for_adult;
  $displayPriceChild = $hasDiscount ? ($trip->price_for_child * (1 - $trip->discount_percentage / 100)) : $trip->price_for_child;

  $durationMinutes = $trip->arrival_time->diffInMinutes($trip->departure_time);
  $durationHours = intdiv($durationMinutes, 60);
  $durationMins = $durationMinutes % 60;
  $durationText = $durationHours > 0 ? ($durationHours . ' ساعة' . ($durationMins > 0 ? ' و ' . $durationMins . ' دقيقة' : '')) : ($durationMins . ' دقيقة');

  $totalSeats = $trip->total_seats ?? ($trip->vehicle->seats_count ?? 0);
  $availableSeats = $trip->available_seats;
  $seatsPercentage = $totalSeats > 0 ? round(($availableSeats / $totalSeats) * 100) : 0;
  $seatBarColor = $seatsPercentage > 50 ? 'bg-[#e7bd54]' : ($seatsPercentage > 20 ? 'bg-amber-500' : 'bg-red-500');

  // ── Vehicle ──
  $vehicleName = $trip->vehicle ? $trip->vehicle->name : '';
  $vehicleModel = $trip->vehicle ? $trip->vehicle->model : '';
  $vehicleTypeName = ($trip->vehicle && $trip->vehicle->vehicleType) ? $trip->vehicle->vehicleType->type_name : '';

  // ── Trip meta ──
  $levelName = $trip->levelTrip ? $trip->levelTrip->level_name : '';
  $typeTripName = $trip->typeOfTrip ? $trip->typeOfTrip->type_name : '';
  $departureStationName = $trip->departureStation ? $trip->departureStation->name : '';
  $arrivalStationName = $trip->arrivalStation ? $trip->arrivalStation->name : '';

  // ── Stopping stations ──
  $stoppingStations = $trip->stoppingStations ?? collect([]);

  // ── Recurrence ──
  $recurringDays = $trip->recurring_days ?? [];
  $isRecurring = is_array($recurringDays) && count($recurringDays) > 0;
  $dayLabels = [
    'sat' => 'السبت', 'sun' => 'الأحد', 'mon' => 'الاثنين',
    'tue' => 'الثلاثاء', 'wed' => 'الأربعاء', 'thu' => 'الخميس', 'fri' => 'الجمعة',
  ];

  // ── Provider ──
  $providerName = $trip->user ? $trip->user->name : '';
  $providerBrand = ($trip->user && $trip->user->providerService) ? $trip->user->providerService->name : '';
?>




<section class="relative bg-[#f9f5ec] pt-24 pb-4">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <nav class="flex items-center gap-2 text-xs font-semibold" aria-label="Breadcrumb">
      <a href="<?php echo e(route('home')); ?>" class="flex items-center gap-1.5 text-[#1f442e]/50 hover:text-[#d49920] transition-colors">
        <i data-lucide="home" class="w-3.5 h-3.5"></i>
        <span>الرئيسية</span>
      </a>
      <i data-lucide="chevron-left" class="w-3 h-3 text-[#1f442e]/20"></i>
      <a href="<?php echo e(route('home.search', ['from_city' => $trip->departureCity->uuid, 'to_city' => $trip->destinationCity->uuid])); ?>" class="text-[#1f442e]/50 hover:text-[#d49920] transition-colors">
        نتائج البحث
      </a>
      <i data-lucide="chevron-left" class="w-3 h-3 text-[#1f442e]/20"></i>
      <span class="text-[#1f442e]/90">تفاصيل الرحلة</span>
    </nav>
  </div>
</section>




<section class="relative overflow-hidden">
  <div class="absolute inset-0 bg-gradient-to-br from-[#0c1d14] via-[#163022] to-[#1f442e]"></div>
  <div class="absolute inset-0 dot-pattern opacity-20 pointer-events-none"></div>
  <div class="absolute top-10 right-10 w-72 h-72 bg-[#e7bd54]/5 rounded-full blur-3xl"></div>
  <div class="absolute bottom-10 left-20 w-80 h-80 bg-[#316b44]/10 rounded-full blur-3xl"></div>

  <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-16">
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 lg:gap-12 items-center">

      
      <div class="lg:col-span-2 order-2 lg:order-1">
        <div class="relative group">
          <?php if($trip->image_url): ?>
            <img src="<?php echo e($trip->image_url); ?>" alt="<?php echo e($trip->departureCity->name); ?> - <?php echo e($trip->destinationCity->name); ?>"
                 class="w-full rounded-2xl shadow-2xl shadow-black/30 object-cover aspect-[4/3] group-hover:scale-[1.02] transition-transform duration-700" loading="eager">
          <?php elseif($trip->vehicle && $trip->vehicle->outside_photo): ?>
            <img src="<?php echo e($trip->vehicle->outside_photo); ?>" alt="<?php echo e($vehicleName); ?>"
                 class="w-full rounded-2xl shadow-2xl shadow-black/30 object-cover aspect-[4/3] group-hover:scale-[1.02] transition-transform duration-700" loading="eager">
          <?php else: ?>
            <div class="w-full rounded-2xl aspect-[4/3] bg-gradient-to-br from-[#1f442e] to-[#0c1d14] border border-white/10 flex items-center justify-center shadow-2xl shadow-black/30 overflow-hidden relative">
              <div class="absolute inset-0 bg-gradient-to-br from-[#316b44]/20 to-transparent"></div>
              <div class="relative flex flex-col items-center gap-4">
                <div class="w-20 h-20 rounded-2xl bg-white/5 backdrop-blur border border-white/10 flex items-center justify-center">
                  <i data-lucide="bus" class="w-10 h-10 text-[#e7bd54]"></i>
                </div>
                <span class="text-white/30 text-sm font-bold"><?php echo e($vehicleTypeName); ?></span>
              </div>
            </div>
          <?php endif; ?>

          <?php if($hasDiscount): ?>
            <div class="absolute top-4 right-4 z-10">
              <span class="px-4 py-2 bg-gradient-to-r from-[#e7bd54] to-[#d49920] text-[#0c1d14] text-sm font-black rounded-full shadow-lg shadow-[#e7bd54]/30 flex items-center gap-1.5">
                <i data-lucide="tag" class="w-4 h-4"></i>
                خصم <?php echo e(number_format($trip->discount_percentage, 0)); ?>%
              </span>
            </div>
          <?php endif; ?>
        </div>

        <?php if($trip->vehicle && $trip->vehicle->inside_photo): ?>
          <div class="mt-4">
            <img src="<?php echo e($trip->vehicle->inside_photo); ?>" alt="داخلية <?php echo e($vehicleName); ?>"
                 class="w-full rounded-2xl shadow-lg object-cover aspect-[16/9] border border-white/10 cursor-pointer hover:scale-[1.02] transition-transform duration-500" loading="lazy">
          </div>
        <?php endif; ?>
      </div>

      
      <div class="lg:col-span-3 order-1 lg:order-2 space-y-4 text-right">

        
        <?php if($trip->trip_name): ?>
          <h1 class="text-2xl sm:text-3xl font-black text-white leading-tight"><?php echo e($trip->trip_name); ?></h1>
        <?php endif; ?>

        
        <div class="bg-white/5 backdrop-blur border border-white/10 rounded-2xl p-4">
          <div class="flex items-center gap-4 sm:gap-6">
            <div class="flex-1">
              <div class="text-lg sm:text-xl font-black text-white"><?php echo e($trip->departureCity->name); ?></div>
              <?php if($departureStationName): ?>
                <div class="text-[11px] text-white/40 font-medium mt-0.5"><?php echo e($departureStationName); ?></div>
              <?php endif; ?>
            </div>
            <div class="flex flex-col items-center gap-1 flex-shrink-0">
              <div class="w-16 sm:w-28 flex items-center gap-1.5">
                <div class="h-[2px] flex-1 bg-gradient-to-r from-transparent to-[#e7bd54]/60 rounded-full"></div>
                <div class="w-7 h-7 bg-[#e7bd54]/10 rounded-full border border-[#e7bd54]/40 flex items-center justify-center">
                  <i data-lucide="bus" class="w-3.5 h-3.5 text-[#e7bd54]"></i>
                </div>
                <div class="h-[2px] flex-1 bg-gradient-to-l from-transparent to-[#e7bd54]/60 rounded-full"></div>
              </div>
              <span class="text-[9px] text-white/30 font-semibold"><?php echo e($durationText); ?></span>
            </div>
            <div class="flex-1 text-left">
              <div class="text-lg sm:text-xl font-black text-white"><?php echo e($trip->destinationCity->name); ?></div>
              <?php if($arrivalStationName): ?>
                <div class="text-[11px] text-white/40 font-medium mt-0.5"><?php echo e($arrivalStationName); ?></div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        
        <div class="flex flex-wrap items-center gap-2">
          <?php if($typeTripName): ?>
            <span class="px-2.5 py-1 bg-teal-500/15 text-teal-300 text-[10px] font-bold rounded-lg border border-teal-500/20"><?php echo e($typeTripName); ?></span>
          <?php endif; ?>
          <?php if($levelName): ?>
            <span class="px-2.5 py-1 bg-[#e7bd54]/10 text-[#e7bd54] text-[10px] font-bold rounded-lg border border-[#e7bd54]/15 flex items-center gap-1">
              <i data-lucide="award" class="w-3 h-3"></i><?php echo e($levelName); ?>

            </span>
          <?php endif; ?>
          <?php if($vehicleTypeName): ?>
            <span class="px-2.5 py-1 bg-white/5 text-white/60 text-[10px] font-bold rounded-lg border border-white/10 flex items-center gap-1">
              <i data-lucide="bus" class="w-3 h-3 text-[#e7bd54]/60"></i><?php echo e($vehicleTypeName); ?>

            </span>
          <?php endif; ?>
          <span class="text-[10px] text-white/40 font-semibold flex items-center gap-1">
            <i data-lucide="calendar" class="w-3 h-3"></i><?php echo e($trip->departure_time->locale('ar')->translatedFormat('d F Y')); ?>

          </span>
          <?php if($stoppingStations->count() > 0): ?>
            <span class="text-[10px] text-white/30 font-semibold"><?php echo e($stoppingStations->count()); ?> محطات عبور</span>
          <?php else: ?>
            <span class="text-[10px] text-white/30 font-semibold">مباشرة</span>
          <?php endif; ?>
        </div>

        
        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2.5">
          <div class="bg-white/5 border border-white/8 rounded-xl px-3 py-2.5 text-center">
            <div class="text-[9px] text-white/35 font-bold mb-0.5">المغادرة</div>
            <div class="text-base font-black text-white"><?php echo e($trip->departure_time->format('h:i A')); ?></div>
          </div>
          <div class="bg-white/5 border border-white/8 rounded-xl px-3 py-2.5 text-center">
            <div class="text-[9px] text-white/35 font-bold mb-0.5">الوصول</div>
            <div class="text-base font-black text-white"><?php echo e($trip->arrival_time->format('h:i A')); ?></div>
          </div>
          <?php if($trip->check_in_time): ?>
          <div class="bg-white/5 border border-white/8 rounded-xl px-3 py-2.5 text-center">
            <div class="text-[9px] text-white/35 font-bold mb-0.5">التسجيل</div>
            <div class="text-base font-black text-white"><?php echo e($trip->check_in_time->format('h:i A')); ?></div>
          </div>
          <?php endif; ?>
          <div class="bg-white/5 border border-white/8 rounded-xl px-3 py-2.5 text-center">
            <div class="text-[9px] text-white/35 font-bold mb-0.5">المقاعد</div>
            <div class="text-base font-black text-white"><?php echo e($availableSeats); ?><span class="text-white/30 font-bold text-xs">/<?php echo e($totalSeats); ?></span></div>
          </div>
        </div>

        
        <?php if($providerName || $providerBrand): ?>
          <div class="flex items-center gap-2.5 pt-0.5">
            <?php if($trip->user && $trip->user->providerService && $trip->user->providerService->brand_photo): ?>
              <img src="<?php echo e(Storage::url($trip->user->providerService->brand_photo)); ?>" alt="" class="w-8 h-8 rounded-lg object-cover border border-white/10">
            <?php else: ?>
              <div class="w-8 h-8 rounded-lg bg-white/10 border border-white/10 flex items-center justify-center">
                <i data-lucide="building-2" class="w-4 h-4 text-[#e7bd54]/60"></i>
              </div>
            <?php endif; ?>
            <div>
              <div class="text-[9px] text-white/35 font-semibold">مشغل الرحلة</div>
              <div class="text-xs text-white/80 font-bold"><?php echo e($providerBrand ?: $providerName); ?></div>
            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</section>




<section class="relative bg-[#f9f5ec] py-10 md:py-14">
  <div class="absolute inset-0 dot-pattern opacity-40 pointer-events-none"></div>
  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8">

      
      
      
      <div class="lg:col-span-8 space-y-6">

        
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-[#eeeef0]">
          <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-gray-100 pb-3 mb-5">
            <i data-lucide="map" class="w-4 h-4 text-emerald-600"></i>
            <span>خريطة ومخطط سير الرحلة التفصيلي</span>
          </h4>

          <div class="relative pr-6 mr-2 py-2 space-y-5">
            
            <div class="absolute top-4 bottom-4 right-[9px] w-0.5 bg-gradient-to-b from-emerald-500 <?php if($stoppingStations->count() > 0): ?> via-purple-400 <?php endif; ?> to-rose-500"></div>

            
            <div class="relative flex items-start gap-4">
              <span class="absolute right-[-22px] top-1.5 w-5 h-5 rounded-full bg-white border-4 border-emerald-500 z-10"></span>
              <div class="space-y-0.5">
                <h5 class="font-bold text-sm text-gray-800">الانطلاق: <?php echo e($trip->departureCity->name); ?></h5>
                <?php if($departureStationName): ?>
                  <p class="text-xs text-slate-500"><?php echo e($departureStationName); ?></p>
                <?php endif; ?>
                <p class="text-[10px] text-emerald-700 font-bold font-mono">
                  <?php echo e($trip->departure_time->format('d/m/Y')); ?> <?php echo e($trip->departure_time->format('h:i A')); ?>

                </p>
              </div>
            </div>

            
            <?php $__currentLoopData = $stoppingStations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $station): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <div class="relative flex items-start gap-4">
                <span class="absolute right-[-20px] top-1.5 w-4 h-4 rounded-full bg-white border-4 border-purple-400 z-10"></span>
                <div class="space-y-0.5">
                  <h5 class="font-bold text-sm text-gray-700">محطة عبور: <?php echo e($station->station_name ?? $station->name ?? ''); ?></h5>
                  <?php if($station->city_name ?? $station->city ?? ''): ?>
                    <p class="text-[10px] text-purple-600 font-bold"><?php echo e($station->city_name ?? $station->city); ?></p>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            
            <div class="relative flex items-start gap-4">
              <span class="absolute right-[-22px] top-1.5 w-5 h-5 rounded-full bg-white border-4 border-rose-500 z-10"></span>
              <div class="space-y-0.5">
                <h5 class="font-bold text-sm text-gray-800">الوصول النهائي: <?php echo e($trip->destinationCity->name); ?></h5>
                <?php if($arrivalStationName): ?>
                  <p class="text-xs text-slate-500"><?php echo e($arrivalStationName); ?></p>
                <?php endif; ?>
                <p class="text-[10px] text-rose-700 font-bold font-mono">
                  <?php echo e($trip->arrival_time->format('d/m/Y')); ?> <?php echo e($trip->arrival_time->format('h:i A')); ?>

                </p>
              </div>
            </div>
          </div>

          
          <div class="mt-5 flex items-center gap-3 bg-emerald-50/50 rounded-xl p-3.5 border border-emerald-100">
            <i data-lucide="timer" class="w-4 h-4 text-emerald-600 flex-shrink-0"></i>
            <span class="text-gray-500 text-xs font-medium">مدة الرحلة المتوقعة:</span>
            <span class="text-gray-800 font-bold text-xs"><?php echo e($durationText); ?></span>
          </div>
        </div>

        
        <div class="p-5 bg-white border border-[#eeeef0] rounded-2xl shadow-sm">
          <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1 mb-4">
            <i data-lucide="settings-2" class="w-4 h-4 text-emerald-600"></i>
            <span>تفاصيل الرحلة</span>
          </h4>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            
            <div class="space-y-2">
              <div class="text-[10px] text-gray-400 font-bold uppercase">الأسعار</div>
              <div class="flex justify-between items-center text-xs">
                <span class="text-gray-500">بالغ:</span>
                <span class="font-black text-gray-800 trip-price" data-base-price="<?php echo e($displayPriceAdult); ?>"><?php echo e(number_format((int)$displayPriceAdult, 0)); ?></span>
              </div>
              <div class="flex justify-between items-center text-xs">
                <span class="text-gray-500">طفل:</span>
                <span class="font-black text-gray-800 trip-price" data-base-price="<?php echo e($displayPriceChild); ?>"><?php echo e(number_format((int)$displayPriceChild, 0)); ?></span>
              </div>
              <?php if($hasDiscount): ?>
                <div class="bg-red-50 px-2 py-1 rounded text-[10px] text-red-600 font-bold">خصم <?php echo e(number_format($trip->discount_percentage, 0)); ?>%</div>
              <?php endif; ?>
              <?php if($trip->ticket_limit): ?>
                <div class="text-[10px] text-gray-400">حد الحجز: <?php echo e($trip->ticket_limit); ?> تذكرة</div>
              <?php endif; ?>
            </div>

            
            <div class="space-y-2">
              <div class="text-[10px] text-gray-400 font-bold uppercase">المقاعد</div>
              <div class="flex justify-between items-center text-xs">
                <span class="text-gray-500">متاح:</span>
                <span class="font-black text-emerald-700"><?php echo e($availableSeats); ?> / <?php echo e($totalSeats); ?></span>
              </div>
              <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-700 <?php echo e($seatBarColor); ?>" style="width: <?php echo e((100 - $seatsPercentage)); ?>%"></div>
              </div>
              <span class="text-[10px] text-gray-400"><?php echo e($seatsPercentage); ?>% شاغرة</span>
            </div>

            
            <div class="space-y-2">
              <div class="text-[10px] text-gray-400 font-bold uppercase">المركبة</div>
              <?php if($vehicleName): ?>
                <div class="text-xs text-gray-800 font-bold"><?php echo e($vehicleName); ?></div>
              <?php endif; ?>
              <?php if($vehicleTypeName): ?>
                <div class="text-[11px] text-gray-500"><?php echo e($vehicleTypeName); ?></div>
              <?php endif; ?>
              <?php if($vehicleModel): ?>
                <div class="text-[11px] text-gray-400"><?php echo e($vehicleModel); ?></div>
              <?php endif; ?>
              <?php if(!$vehicleName && !$vehicleTypeName && !$vehicleModel): ?>
                <div class="text-[11px] text-gray-400">غير محددة</div>
              <?php endif; ?>
            </div>
          </div>

          
          <?php if($trip->amenities && $trip->amenities->count() > 0): ?>
            <div class="mt-4 pt-3 border-t border-gray-50">
              <div class="text-[10px] text-gray-400 font-bold uppercase mb-2">الخدمات</div>
              <div class="flex flex-wrap gap-1.5">
                <?php $__currentLoopData = $trip->amenities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $amenity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <span class="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded border border-emerald-100"><?php echo e($amenity->name); ?></span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </div>
            </div>
          <?php endif; ?>
        </div>

        
        <div class="p-5 bg-white border border-[#eeeef0] rounded-2xl shadow-sm space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            
            <div class="space-y-2">
              <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1">
                <i data-lucide="shield-check" class="w-4 h-4 text-amber-600"></i>
                <span>سياسة الإلغاء <?php echo e($trip->policy ? '(' . $trip->policy->policy_name . ')' : ''); ?></span>
              </h4>
              <?php if($trip->policy && $trip->policy->description): ?>
                <div class="text-xs text-gray-600 font-medium leading-relaxed whitespace-pre-line"><?php echo e($trip->policy->description); ?></div>
              <?php else: ?>
                <ul class="space-y-1 text-xs text-gray-500 font-medium list-disc list-inside">
                  <li>إلغاء مجاني قبل 24 ساعة من المغادرة.</li>
                  <li>خصم 20% في اليوم الأخير قبل المغادرة.</li>
                  <li>عدم الحضور يجعل التذكرة غير قابلة للاسترداد.</li>
                </ul>
              <?php endif; ?>
            </div>

            
            <div class="space-y-2">
              <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1">
                <i data-lucide="calendar-clock" class="w-4 h-4 text-emerald-600"></i>
                <span>الجدولة</span>
              </h4>
              <?php if($isRecurring): ?>
                <div class="flex flex-wrap gap-1">
                  <?php $__currentLoopData = $recurringDays; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <span class="bg-[#235b32]/10 text-[#235b32] text-[10px] font-bold px-2 py-0.5 rounded"><?php echo e($dayLabels[strtolower($day)] ?? $day); ?></span>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <?php if($trip->recurring_end_date): ?>
                  <span class="text-[10px] text-gray-400 font-semibold">ينتهي: <?php echo e($trip->recurring_end_date->locale('ar')->translatedFormat('d F Y')); ?></span>
                <?php endif; ?>
              <?php else: ?>
                <span class="text-xs text-gray-400">رحلة فردية غير مكررة</span>
              <?php endif; ?>
            </div>
          </div>

          
          <?php if($trip->coupons && $trip->coupons->count() > 0): ?>
            <div class="pt-3 border-t border-gray-50">
              <div class="text-[10px] text-gray-400 font-bold uppercase mb-1.5">كوبونات متاحة</div>
              <div class="flex flex-wrap gap-1.5">
                <?php $__currentLoopData = $trip->coupons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $coupon): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <span class="bg-[#e7bd54]/10 text-[#d49920] text-[10px] font-bold px-2 py-0.5 rounded border border-[#e7bd54]/20"><?php echo e($coupon->code); ?></span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </div>
            </div>
          <?php endif; ?>
        </div>

        
        <?php if($trip->conditions): ?>
          <div class="bg-white rounded-2xl p-5 shadow-sm border border-[#eeeef0]">
            <button onclick="toggleConditions()" class="flex items-center gap-3 w-full text-right cursor-pointer">
              <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5 flex-1">
                <i data-lucide="info" class="w-4 h-4 text-amber-600"></i>
                <span>إرشادات للركاب</span>
              </h4>
              <i data-lucide="chevron-down" id="conditions-chevron" class="w-4 h-4 text-slate-400 transition-transform duration-300"></i>
            </button>
            <div id="conditions-content" class="hidden mt-3 text-xs text-gray-600 font-medium leading-relaxed whitespace-pre-line bg-gray-50 rounded-xl p-3.5 border border-gray-200">
              <?php echo e($trip->conditions); ?>

            </div>
          </div>
        <?php endif; ?>

      </div>

      
      
      
      <div class="lg:col-span-4 space-y-6">

        
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-[#eeeef0] lg:sticky lg:top-24 z-20" id="pricing-card">

          
          <div class="space-y-3 mb-4">
            
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <i data-lucide="user" class="w-3.5 h-3.5 text-gray-400"></i>
                <span class="text-gray-500 text-xs font-semibold">تذكرة بالغ</span>
                <?php if($hasDiscount): ?>
                  <span class="px-1.5 py-0.5 bg-red-50 text-red-500 text-[9px] font-bold rounded">-<?php echo e(number_format($trip->discount_percentage, 0)); ?>%</span>
                <?php endif; ?>
              </div>
              <div class="flex items-baseline gap-1">
                <?php if($hasDiscount): ?>
                  <span class="text-xs text-gray-300 line-through trip-price-original" data-base-price="<?php echo e($trip->price_for_adult); ?>"><?php echo e(number_format((int)$trip->price_for_adult, 0)); ?></span>
                <?php endif; ?>
                <span class="text-lg font-black text-gray-900 trip-price" data-base-price="<?php echo e($displayPriceAdult); ?>"><?php echo e(number_format((int)$displayPriceAdult, 0)); ?></span>
                <span class="text-[10px] text-gray-400 font-bold trip-currency-symbol"><?php echo e($baseCurrency->symbol ?? ''); ?></span>
              </div>
            </div>

            
            <div class="border-t border-gray-50"></div>

            
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <i data-lucide="baby" class="w-3.5 h-3.5 text-amber-400"></i>
                <span class="text-gray-500 text-xs font-semibold">تذكرة طفل</span>
              </div>
              <div class="flex items-baseline gap-1">
                <?php if($hasDiscount): ?>
                  <span class="text-xs text-gray-300 line-through trip-price-original" data-base-price="<?php echo e($trip->price_for_child); ?>"><?php echo e(number_format((int)$trip->price_for_child, 0)); ?></span>
                <?php endif; ?>
                <span class="text-lg font-black text-gray-900 trip-price" data-base-price="<?php echo e($displayPriceChild); ?>"><?php echo e(number_format((int)$displayPriceChild, 0)); ?></span>
                <span class="text-[10px] text-gray-400 font-bold trip-currency-symbol"><?php echo e($baseCurrency->symbol ?? ''); ?></span>
              </div>
            </div>
          </div>

          
          <div class="flex items-center gap-3 mb-4 px-1">
            <i data-lucide="armchair" class="w-3.5 h-3.5 text-gray-400 flex-shrink-0"></i>
            <div class="flex-1">
              <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-700 <?php echo e($seatBarColor); ?>" style="width: <?php echo e($seatsPercentage); ?>%"></div>
              </div>
            </div>
            <span class="text-[11px] font-bold text-gray-500 flex-shrink-0"><?php echo e($availableSeats); ?><span class="text-gray-300 font-semibold">/<?php echo e($totalSeats); ?></span></span>
          </div>

          
          <?php if(auth()->guard()->check()): ?>
            <?php if(isset($walletBalance) && $walletBalance > 0): ?>
              <div class="flex items-center gap-2 mb-4 px-1">
                <i data-lucide="wallet" class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0"></i>
                <span class="text-[11px] text-gray-400 font-semibold">رصيد المحفظة:</span>
                <span class="text-sm font-black text-emerald-700"><?php echo e(number_format($walletBalance, 2)); ?></span>
              </div>
            <?php endif; ?>
          <?php endif; ?>

          
          <?php if(auth()->guard()->check()): ?>
            <button onclick="handleBooking()" class="w-full bg-gradient-to-l from-[#e7bd54] to-[#d49920] hover:from-[#d49920] hover:to-[#b87d18] text-[#0c1d14] font-bold text-sm rounded-xl shadow-lg shadow-[#e7bd54]/20 flex items-center justify-center gap-2 px-5 py-3.5 transition-all active:scale-[0.98] cursor-pointer">
              <i data-lucide="ticket" class="w-4 h-4"></i>
              <span>احجز الآن</span>
              <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </button>
            <a href="<?php echo e(route('bookings.round-trip.create', $trip->uuid)); ?>" class="w-full mt-2 bg-white border border-[#e7bd54]/30 text-[#d49920] font-bold text-xs rounded-xl flex items-center justify-center gap-2 px-5 py-2.5 hover:bg-[#e7bd54]/5 hover:border-[#e7bd54] transition-all">
              <i data-lucide="repeat" class="w-3.5 h-3.5"></i>
              <span>ذهاب وعودة</span>
            </a>
            <p class="text-center text-gray-300 text-[9px] font-semibold mt-2">
              <i data-lucide="shield-check" class="w-3 h-3 inline-block -mt-0.5"></i>حجز آمن — الأسعار شاملة الضريبة
            </p>
          <?php else: ?>
            <a href="<?php echo e(route('login')); ?>" class="w-full bg-gradient-to-l from-[#e7bd54] to-[#d49920] hover:from-[#d49920] hover:to-[#b87d18] text-[#0c1d14] font-bold text-sm rounded-xl shadow-lg shadow-[#e7bd54]/20 flex items-center justify-center gap-2 px-5 py-3.5 transition-all">
              <i data-lucide="log-in" class="w-4 h-4"></i>
              <span>سجّل دخولك للحجز</span>
            </a>
          <?php endif; ?>

          
          <?php if(count($currencies) > 1): ?>
          <div class="mt-4 pt-3 border-t border-gray-50">
            <div class="flex flex-wrap gap-1.5" id="currency-pricing">
              <?php $__currentLoopData = $currencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $curr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <button onclick="setCurrency(<?php echo e($curr['id']); ?>)" data-currency-id="<?php echo e($curr['id']); ?>"
                  class="curr-btn-pricing px-2.5 py-1 rounded-md text-[10px] font-bold transition-all cursor-pointer
                    <?php echo e($curr['id'] === $baseCurrencyId
                       ? 'bg-[#1f442e] text-white'
                       : 'text-gray-400 hover:text-gray-700 hover:bg-gray-50'); ?>">
                  <?php echo e($curr['code']); ?>

                </button>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
          </div>
          <?php endif; ?>
        </div>

        
        <?php if($similarTrips && $similarTrips->count() > 0): ?>
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-[#eeeef0]">
            <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-gray-100 pb-3 mb-5">
              <i data-lucide="repeat" class="w-4 h-4 text-emerald-600"></i>
              <span>رحلات مشابهة</span>
            </h4>
            <div class="space-y-3 max-h-96 overflow-y-auto scrollbar-thin">
              <?php $__currentLoopData = $similarTrips->take(5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $similar): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                  $sHasDiscount = ($similar->discount_percentage ?? 0) > 0;
                  $sDisplayPrice = $sHasDiscount ? ($similar->price_for_adult * (1 - $similar->discount_percentage / 100)) : $similar->price_for_adult;
                ?>
                <a href="<?php echo e(route('home.show', $similar->uuid)); ?>" class="block bg-[#f9f9fc] rounded-xl p-4 border border-gray-100 hover:border-emerald-200 hover:shadow-sm transition-all duration-300 group">
                  <div class="flex items-center gap-3 mb-3">
                    <div class="flex-1">
                      <div class="flex items-center gap-2 text-sm font-bold text-gray-800 group-hover:text-emerald-700 transition-colors">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-gray-400"></i>
                        <span><?php echo e($similar->departureCity->name); ?> → <?php echo e($similar->destinationCity->name); ?></span>
                      </div>
                      <div class="text-[10px] text-gray-400 mt-1"><?php echo e($similar->departure_time->format('h:i A')); ?> — <?php echo e($similar->departure_time->locale('ar')->translatedFormat('d F Y')); ?></div>
                    </div>
                    <?php if($sHasDiscount): ?>
                      <span class="px-2 py-0.5 bg-[#e7bd54]/15 text-[#d49920] text-[10px] font-bold rounded-full flex-shrink-0">-<?php echo e(number_format($similar->discount_percentage, 0)); ?>%</span>
                    <?php endif; ?>
                  </div>
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5 text-xs text-gray-400">
                      <i data-lucide="armchair" class="w-3.5 h-3.5"></i>
                      <span><?php echo e($similar->available_seats); ?> مقعد</span>
                    </div>
                    <div class="flex items-baseline gap-1">
                      <span class="text-base font-black text-gray-900 trip-price" data-base-price="<?php echo e($sDisplayPrice); ?>"><?php echo e(number_format((int)$sDisplayPrice, 0)); ?></span>
                      <span class="text-[10px] text-gray-500 font-bold trip-currency-symbol"><?php echo e($baseCurrency->symbol ?? ''); ?></span>
                    </div>
                  </div>
                </a>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</section>




<div class="lg:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-xl border-t border-gray-200" id="mobile-booking-bar">
  <div class="px-4 py-3 flex items-center gap-3">
    <div class="flex-1 text-right">
      <div class="text-gray-400 text-[10px] font-semibold">السعر يبدأ من</div>
      <div class="flex items-baseline gap-1">
        <span class="text-xl font-black text-gray-900 trip-price" data-base-price="<?php echo e($displayPriceAdult); ?>"><?php echo e(number_format((int)$displayPriceAdult, 0)); ?></span>
        <span class="text-xs text-gray-500 font-bold trip-currency-symbol"><?php echo e($baseCurrency->symbol ?? ''); ?></span>
      </div>
    </div>
    <?php if(auth()->guard()->check()): ?>
      <button onclick="handleBooking()" class="bg-gradient-to-l from-[#e7bd54] to-[#d49920] text-[#0c1d14] font-bold text-sm rounded-xl shadow-lg flex items-center gap-2 px-5 py-3 flex-shrink-0 cursor-pointer">
        <i data-lucide="ticket" class="w-4 h-4"></i>
        <span>احجز الآن</span>
      </button>
    <?php else: ?>
      <a href="<?php echo e(route('login')); ?>" class="bg-gradient-to-l from-[#e7bd54] to-[#d49920] text-[#0c1d14] font-bold text-sm rounded-xl shadow-lg flex items-center gap-2 px-5 py-3 flex-shrink-0">
        <i data-lucide="log-in" class="w-4 h-4"></i>
        <span>سجّل دخولك</span>
      </a>
    <?php endif; ?>
  </div>
</div>

<?php $__env->stopSection(); ?>




<?php $__env->startPush('styles'); ?>
<style>
  @keyframes slideUp {
    from { transform: translateY(100%); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
  }
  .slide-up { animation: slideUp 0.4s ease-out; }
  #mobile-booking-bar { animation: slideUp 0.4s ease-out; }

  @media (max-width: 1023px) {
    body { padding-bottom: 72px; }
  }

  select option {
    color: #1f442e;
    background: #f9f5ec;
  }
</style>
<?php $__env->stopPush(); ?>




<?php $__env->startPush('scripts'); ?>
<script>
  if (typeof lucide !== 'undefined') lucide.createIcons();

  // ─── Currency data ───────────────────────────────────────────────────
  let currencies      = <?php echo json_encode($currencies, 15, 512) ?>;
  let baseCurrencyId   = <?php echo e($baseCurrencyId); ?>;
  let currentCurrencyId = <?php echo e($baseCurrencyId); ?>;

  function getCurrency(id) {
    return currencies.find(c => c.id === id) || currencies.find(c => c.id === baseCurrencyId);
  }

  function formatPrice(basePrice, currencyId) {
    const curr = getCurrency(currencyId);
    if (!curr) return { formatted: '0', symbol: '' };

    const converted = basePrice * (curr.exchange_rate || curr.rate_to_base || 1);
    const decimals  = converted >= 1 ? 0 : 2;
    const formatted = new Intl.NumberFormat('en-US', { maximumFractionDigits: decimals }).format(converted);
    const baseCurr  = getCurrency(baseCurrencyId);
    const symbol    = (baseCurr && curr.id !== baseCurrencyId) ? curr.code : (baseCurr ? (baseCurr.symbol || baseCurr.code) : curr.code);
    return { formatted, symbol };
  }

  function updateAllPrices() {
    const curr = getCurrency(currentCurrencyId);
    if (!curr) return;

    document.querySelectorAll('.trip-price').forEach(el => {
      const basePrice = parseFloat(el.getAttribute('data-base-price'));
      if (isNaN(basePrice)) return;
      el.textContent = formatPrice(basePrice, currentCurrencyId).formatted;
    });

    document.querySelectorAll('.trip-price-original').forEach(el => {
      const basePrice = parseFloat(el.getAttribute('data-base-price'));
      if (isNaN(basePrice)) return;
      el.textContent = formatPrice(basePrice, currentCurrencyId).formatted;
    });

    document.querySelectorAll('.trip-currency-symbol').forEach(el => {
      el.textContent = curr.code;
    });
  }

  function setCurrency(id) {
    currentCurrencyId = id;
    const container = document.getElementById('currency-pricing');
    if (container) {
      container.querySelectorAll('.curr-btn-pricing').forEach(btn => {
        const btnId = parseInt(btn.getAttribute('data-currency-id'));
        if (btnId === id) {
          btn.className = 'curr-btn-pricing px-2.5 py-1 rounded-md text-[10px] font-bold transition-all cursor-pointer bg-[#1f442e] text-white';
        } else {
          btn.className = 'curr-btn-pricing px-2.5 py-1 rounded-md text-[10px] font-bold transition-all cursor-pointer text-gray-400 hover:text-gray-700 hover:bg-gray-50';
        }
      });
    }
    updateAllPrices();
  }

  // ─── Conditions toggle ──────────────────────────────────────────────
  function toggleConditions() {
    const content = document.getElementById('conditions-content');
    const chevron = document.getElementById('conditions-chevron');
    if (!content || !chevron) return;
    content.classList.toggle('hidden');
    chevron.style.transform = content.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
  }

  // ─── Booking handler ────────────────────────────────────────────────
  function handleBooking() {
    window.location.href = '<?php echo e(route('bookings.book', $trip->uuid)); ?>';
  }

  // ─── Toast notification ─────────────────────────────────────────────
  function showToast(message, type = 'info') {
    const existing = document.getElementById('aracari-toast');
    if (existing) existing.remove();

    const colors = {
      info:    'from-[#1f442e] to-[#0c1d14]',
      success: 'from-[#316b44] to-[#1f442e]',
      warning: 'from-[#e7bd54] to-[#d49920]',
      error:   'from-red-600 to-red-500',
    };
    const iconColors = {
      info: 'text-[#e7bd54]', success: 'text-white', warning: 'text-[#0c1d14]', error: 'text-white',
    };
    const icons = {
      info: 'info', success: 'check-circle', warning: 'alert-triangle', error: 'x-circle',
    };

    const toast = document.createElement('div');
    toast.id = 'aracari-toast';
    toast.className = 'fixed top-24 left-1/2 -translate-x-1/2 z-[999] bg-gradient-to-r ' + colors[type] + ' text-white px-6 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 font-semibold text-sm slide-up';
    toast.innerHTML = '<i data-lucide="' + icons[type] + '" class="w-5 h-5 flex-shrink-0 ' + iconColors[type] + '"></i><span>' + message + '</span>';
    document.body.appendChild(toast);
    if (typeof lucide !== 'undefined') lucide.createIcons();

    setTimeout(() => {
      toast.style.transition = 'opacity 0.3s, transform 0.3s';
      toast.style.opacity = '0';
      toast.style.transform = 'translate(-50%, -20px)';
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }

  // ─── Mobile bar visibility ──────────────────────────────────────────
  function handleMobileBar() {
    const bar = document.getElementById('mobile-booking-bar');
    if (!bar) return;
    bar.style.display = (window.innerWidth >= 1024) ? 'none' : 'block';
  }

  document.addEventListener('DOMContentLoaded', handleMobileBar);
  window.addEventListener('resize', handleMobileBar);
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.web', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/home/trip_details.blade.php ENDPATH**/ ?>