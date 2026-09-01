<?php $__env->startSection('title', 'إتمام الحجز | أراكاري'); ?>

<?php $__env->startSection('content'); ?>

<?php
  // ── Trip Calculations ──
  $hasDiscount = ($trip->discount_percentage ?? 0) > 0;
  $displayPriceAdult = $hasDiscount ? ($trip->price_for_adult * (1 - $trip->discount_percentage / 100)) : $trip->price_for_adult;
  $displayPriceChild = $hasDiscount ? ($trip->price_for_child * (1 - $trip->discount_percentage / 100)) : $trip->price_for_child;

  $durationMinutes = $trip->arrival_time->diffInMinutes($trip->departure_time);
  $durationHours = intdiv($durationMinutes, 60);
  $durationMins = $durationMinutes % 60;
  $durationText = $durationHours > 0 ? ($durationHours . ' ساعة' . ($durationMins > 0 ? ' و ' . $durationMins . ' دقيقة' : '')) : ($durationMins . ' دقيقة');

  $totalSeats = $trip->total_seats ?? ($trip->vehicle->seats_count ?? 0);
  $availableSeats = $trip->available_seats;

  $vehicleTypeName = ($trip->vehicle && $trip->vehicle->vehicleType) ? $trip->vehicle->vehicleType->type_name : '';
  $departureStationName = $trip->departureStation ? $trip->departureStation->name : '';
  $arrivalStationName = $trip->arrivalStation ? $trip->arrivalStation->name : '';
  $providerName = $trip->user ? $trip->user->name : '';
  $providerBrand = ($trip->user && $trip->user->providerService) ? $trip->user->providerService->name : '';

  // Ticket limit
  $ticketLimit = $trip->ticket_limit ?? $totalSeats;

  // Wallet feature
  $walletEnabled = true; // Could be from settings

  // Previous passengers
  $prevPassengers = $previousPassengers ?? collect([]);

  // Round trip mode
  $isRoundTrip = $isRoundTrip ?? false;
  $returnTrips = $returnTrips ?? collect([]);
?>




<section class="relative bg-[#f9f5ec] pt-24 pb-4">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <nav class="flex items-center gap-2 text-xs font-semibold mb-4" aria-label="Breadcrumb">
      <a href="<?php echo e(route('home')); ?>" class="flex items-center gap-1.5 text-[#1f442e]/50 hover:text-[#d49920] transition-colors">
        <i data-lucide="home" class="w-3.5 h-3.5"></i>
        <span>الرئيسية</span>
      </a>
      <i data-lucide="chevron-left" class="w-3 h-3 text-[#1f442e]/20"></i>
      <a href="<?php echo e(route('home.show', $trip->uuid)); ?>" class="text-[#1f442e]/50 hover:text-[#d49920] transition-colors">
        تفاصيل الرحلة
      </a>
      <i data-lucide="chevron-left" class="w-3 h-3 text-[#1f442e]/20"></i>
      <span class="text-[#1f442e]/90">إتمام الحجز</span>
    </nav>

    
    <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-[#eeeef0]">
      <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
        
        <div class="flex items-center gap-3 flex-1 min-w-0">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#1f442e] to-[#0c1d14] flex items-center justify-center flex-shrink-0">
            <i data-lucide="bus" class="w-5 h-5 text-[#e7bd54]"></i>
          </div>
          <div class="min-w-0">
            <div class="flex items-center gap-2 text-sm font-black text-gray-800">
              <span><?php echo e($trip->departureCity->name); ?></span>
              <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-[#e7bd54] flex-shrink-0"></i>
              <span><?php echo e($trip->destinationCity->name); ?></span>
            </div>
            <div class="text-[10px] text-gray-400 font-semibold mt-0.5">
              <?php echo e($trip->departure_time->locale('ar')->translatedFormat('d F Y')); ?> · <?php echo e($trip->departure_time->format('h:i A')); ?>

              <?php if($departureStationName): ?> · <?php echo e($departureStationName); ?> <?php endif; ?>
            </div>
          </div>
        </div>
        
        <div class="flex items-center gap-2 flex-shrink-0 flex-wrap">
          <span class="px-2.5 py-1 bg-[#f9f9fc] text-gray-500 text-[10px] font-bold rounded-lg flex items-center gap-1">
            <i data-lucide="timer" class="w-3 h-3"></i> <?php echo e($durationText); ?>

          </span>
          <span class="px-2.5 py-1 bg-[#f9f9fc] text-gray-500 text-[10px] font-bold rounded-lg flex items-center gap-1">
            <i data-lucide="armchair" class="w-3 h-3"></i> <?php echo e($availableSeats); ?> مقعد
          </span>
          <?php if($providerBrand || $providerName): ?>
          <span class="px-2.5 py-1 bg-[#1f442e]/5 text-[#1f442e] text-[10px] font-bold rounded-lg">
            <?php echo e($providerBrand ?: $providerName); ?>

          </span>
          <?php endif; ?>
        </div>
        
      </div>
    </div>
  </div>
</section>
<section class="relative bg-[#f9f5ec]">
    <?php echo $__env->make('partials.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

</section>




<section class="relative bg-[#f9f5ec] pb-16">
  <div class="absolute inset-0 dot-pattern opacity-30 pointer-events-none"></div>
  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">

    <form id="booking-form" action="<?php echo e($isRoundTrip ? route('bookings.round-trip') : route('bookings.store', $trip->uuid)); ?>" method="POST" novalidate>
      <?php echo csrf_field(); ?>
      <input type="hidden" name="currency_id" id="currency_id" value="<?php echo e($baseCurrencyId); ?>">
      <?php if($isRoundTrip): ?>
        <input type="hidden" name="outbound_trip_uuid" value="<?php echo e($trip->uuid); ?>">
        <input type="hidden" name="return_trip_uuid" id="return-trip-uuid" value="">
      <?php endif; ?>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8">

        
        
        
        <div class="lg:col-span-8 space-y-6">

          
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-[#eeeef0]">
            <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-gray-100 pb-3 mb-6">
              <span class="w-6 h-6 rounded-lg bg-[#1f442e] text-white text-[10px] font-black flex items-center justify-center">1</span>
              <span>عدد المسافرين</span>
            </h4>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              
              <div class="bg-[#f9f9fc] rounded-xl p-5 border border-gray-100">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                      <i data-lucide="user" class="w-5 h-5 text-emerald-600"></i>
                    </div>
                    <div>
                      <div class="text-sm font-bold text-gray-800">البالغين</div>
                      <div class="text-[10px] text-gray-400 font-semibold">سن 12 وما فوق</div>
                    </div>
                  </div>
                  <div class="flex items-center gap-3">
                    <button type="button" onclick="adjustCount('adults', -1)" class="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center hover:bg-red-50 hover:border-red-200 hover:text-red-500 transition-all text-gray-500 font-bold text-lg cursor-pointer">−</button>
                    <input type="number" name="adults" id="adults-count" value="1" min="1" max="<?php echo e(min($ticketLimit, 10)); ?>"
                           class="w-12 text-center text-xl font-black text-gray-900 bg-transparent border-0 outline-none" dir="ltr" readonly>
                    <button type="button" onclick="adjustCount('adults', 1)" class="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-600 transition-all text-gray-500 font-bold text-lg cursor-pointer">+</button>
                  </div>
                </div>
                <div class="mt-3 text-xs text-gray-400 font-medium">
                  <span class="font-black text-gray-600 trip-price" data-base-price="<?php echo e($displayPriceAdult); ?>"><?php echo e(number_format((int)$displayPriceAdult, 0)); ?></span>
                  <span class="trip-currency-symbol"><?php echo e($baseCurrency->symbol ?? ''); ?></span>
                  / للفرد
                  <?php if($hasDiscount): ?>
                    <span class="text-red-400 line-through mr-1 text-[10px] trip-price-original" data-base-price="<?php echo e($trip->price_for_adult); ?>"><?php echo e(number_format((int)$trip->price_for_adult, 0)); ?></span>
                  <?php endif; ?>
                </div>
              </div>

              
              <div class="bg-[#f9f9fc] rounded-xl p-5 border border-gray-100">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                      <i data-lucide="baby" class="w-5 h-5 text-amber-500"></i>
                    </div>
                    <div>
                      <div class="text-sm font-bold text-gray-800">الأطفال</div>
                      <div class="text-[10px] text-gray-400 font-semibold">أقل من 12 سنة</div>
                    </div>
                  </div>
                  <div class="flex items-center gap-3">
                    <button type="button" onclick="adjustCount('children', -1)" class="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center hover:bg-red-50 hover:border-red-200 hover:text-red-500 transition-all text-gray-500 font-bold text-lg cursor-pointer">−</button>
                    <input type="number" name="children" id="children-count" value="0" min="0" max="<?php echo e(min($ticketLimit, 5)); ?>"
                           class="w-12 text-center text-xl font-black text-gray-900 bg-transparent border-0 outline-none" dir="ltr" readonly>
                    <button type="button" onclick="adjustCount('children', 1)" class="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-600 transition-all text-gray-500 font-bold text-lg cursor-pointer">+</button>
                  </div>
                </div>
                <div class="mt-3 text-xs text-gray-400 font-medium">
                  <span class="font-black text-gray-600 trip-price" data-base-price="<?php echo e($displayPriceChild); ?>"><?php echo e(number_format((int)$displayPriceChild, 0)); ?></span>
                  <span class="trip-currency-symbol"><?php echo e($baseCurrency->symbol ?? ''); ?></span>
                  / للفرد
                </div>
              </div>
            </div>

            
            <div class="mt-4 flex items-center gap-2 text-xs text-amber-600 bg-amber-50 rounded-lg p-3 border border-amber-100" id="seats-warning" style="display:none;">
              <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i>
              <span>عدد المقاعد المتاحة هو <strong><?php echo e($availableSeats); ?></strong> مقعد فقط</span>
            </div>
          </div>

          <?php if($isRoundTrip): ?>
          
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-[#eeeef0]">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-5">
              <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                <span class="w-6 h-6 rounded-lg bg-[#e7bd54] text-[#0c1d14] text-[10px] font-black flex items-center justify-center">←</span>
                <span>رحلة العودة</span>
              </h4>
              <span class="text-[10px] text-gray-400 font-semibold"><?php echo e($trip->destinationCity->name); ?> → <?php echo e($trip->departureCity->name); ?></span>
            </div>

            
            <div class="mb-5">
              <label class="block text-xs font-bold text-gray-600 mb-2">
                <i data-lucide="calendar" class="w-3.5 h-3.5 inline-block ml-1 text-[#1f442e]/60"></i>
                تاريخ العودة
              </label>
              <input type="date" id="return-date-picker"
                     min="<?php echo e($defaultReturnDate ? $defaultReturnDate->format('Y-m-d') : now()->format('Y-m-d')); ?>"
                     value="<?php echo e($defaultReturnDate ? $defaultReturnDate->format('Y-m-d') : now()->format('Y-m-d')); ?>"
                     name="return_travel_date"
                     class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-gray-200 bg-[#f9f9fc] text-sm font-bold text-gray-700 focus:border-[#e7bd54] focus:ring-2 focus:ring-[#e7bd54]/20 outline-none transition-all cursor-pointer">
              <p class="text-[10px] text-gray-400 mt-1.5 font-medium">
                <i data-lucide="info" class="w-3 h-3 inline-block ml-0.5"></i>
                <?php echo e($trip->arrival_time->locale('ar')->translatedFormat('d F Y')); ?>

              </p>
            </div>

            
            <div id="return-trips-loading" class="hidden">
              <div class="space-y-3">
                <?php for($i = 0; $i < 3; $i++): ?>
                <div class="animate-pulse flex items-center gap-4 bg-[#f9f9fc] rounded-xl p-4">
                  <div class="flex-1 space-y-2">
                    <div class="h-4 bg-gray-200 rounded w-48"></div>
                    <div class="h-3 bg-gray-100 rounded w-32"></div>
                  </div>
                  <div class="h-6 bg-gray-200 rounded w-16"></div>
                </div>
                <?php endfor; ?>
              </div>
            </div>

            
            <div id="return-trips-list" class="space-y-2.5 max-h-80 overflow-y-auto scrollbar-thin">
              <?php if($returnTrips->count() > 0): ?>
                <?php $__currentLoopData = $returnTrips; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <?php
                    $rtHasDiscount = ($rt->discount_percentage ?? 0) > 0;
                    $rtPriceAdult = $rtHasDiscount ? ($rt->price_for_adult * (1 - $rt->discount_percentage / 100)) : $rt->price_for_adult;
                    $rtPriceChild = $rtHasDiscount ? ($rt->price_for_child * (1 - $rt->discount_percentage / 100)) : $rt->price_for_child;
                    $rtVehicleType = ($rt->vehicle && $rt->vehicle->vehicleType) ? $rt->vehicle->vehicleType->type_name : '';
                    $rtProvider = ($rt->user && $rt->user->providerService) ? $rt->user->providerService->name : '';
                    $rtIsSameProvider = isset($trip->user_id) && $trip->user_id === $rt->user_id;
                  ?>
                  <label class="return-trip-option flex items-center gap-4 bg-[#f9f9fc] rounded-xl p-4 border-2 border-transparent cursor-pointer transition-all hover:border-gray-200">
                    <input type="radio" name="_return_trip" value="<?php echo e($rt->uuid); ?>"
                           data-price-adult="<?php echo e((float)$rtPriceAdult); ?>"
                           data-price-child="<?php echo e((float)$rtPriceChild); ?>"
                           data-vehicle-type="<?php echo e($rtVehicleType); ?>"
                           data-provider="<?php echo e($rtProvider); ?>"
                           data-same-provider="<?php echo e($rtIsSameProvider ? '1' : '0'); ?>"
                           data-departure-time="<?php echo e($rt->departure_time->format('h:i A')); ?>"
                           data-departure-date="<?php echo e($rt->departure_time->format('d/m/Y')); ?>"
                           data-seats="<?php echo e($rt->available_seats); ?>"
                           data-recurring="<?php echo e($rt->parent_id !== null ? '1' : '0'); ?>"
                           class="return-trip-radio hidden"
                           <?php echo e($loop->first ? 'checked' : ''); ?>>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-2 text-sm font-bold text-gray-800">
                        <span><?php echo e($rt->departure_time->format('h:i A')); ?></span>
                        <i data-lucide="arrow-left" class="w-3 h-3 text-[#e7bd54] flex-shrink-0"></i>
                        <span><?php echo e($rt->arrival_time->format('h:i A')); ?></span>
                        <?php if($rtHasDiscount): ?>
                          <span class="px-1.5 py-0.5 bg-red-50 text-red-500 text-[9px] font-bold rounded">-<?php echo e(number_format($rt->discount_percentage, 0)); ?>%</span>
                        <?php endif; ?>
                        <?php if($rtIsSameProvider): ?>
                          <span class="px-1.5 py-0.5 bg-[#1f442e]/10 text-[#1f442e] text-[9px] font-bold rounded">نفس المزود</span>
                        <?php endif; ?>
                      </div>
                      <div class="text-[10px] text-gray-400 font-semibold mt-0.5 flex items-center gap-1.5">
                        <?php echo e($rt->departure_time->locale('ar')->translatedFormat('d F Y')); ?>

                        <?php if($rtVehicleType): ?> · <?php echo e($rtVehicleType); ?> <?php endif; ?>
                        <?php if($rtProvider): ?> · <?php echo e($rtProvider); ?> <?php endif; ?>
                        <?php if($rt->parent_id !== null): ?>
                          <span class="px-1 py-0.5 bg-amber-50 text-amber-600 text-[8px] font-bold rounded">متكررة</span>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="text-left flex-shrink-0">
                      <div class="text-base font-black text-gray-800"><?php echo e(number_format((int)$rtPriceAdult, 0)); ?></div>
                      <div class="text-[9px] text-gray-400"><?php echo e($baseCurrency->symbol ?? ''); ?>/بالغ</div>
                    </div>
                    <div class="w-5 h-5 rounded-full border-2 border-gray-300 flex items-center justify-center flex-shrink-0 transition-colors return-trip-circle">
                      <div class="w-2.5 h-2.5 rounded-full bg-[#e7bd54] scale-0 transition-transform return-trip-dot"></div>
                    </div>
                  </label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              <?php else: ?>
                <div class="flex flex-col items-center gap-3 py-8 text-center">
                  <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center">
                    <i data-lucide="calendar-x" class="w-7 h-7 text-gray-300"></i>
                  </div>
                  <div class="text-sm text-gray-500 font-bold">لا توجد رحلات عودة في هذا التاريخ</div>
                  <div class="text-xs text-gray-400">جرب تاريخ آخر أو تأكد من وجود رحلات من <?php echo e($trip->destinationCity->name); ?> إلى <?php echo e($trip->departureCity->name); ?></div>
                </div>
              <?php endif; ?>
            </div>

            
            <p id="return-trip-empty-error" class="hidden text-xs text-red-500 font-bold mt-3">
              <i data-lucide="alert-circle" class="w-3.5 h-3.5 inline-block ml-1"></i>
              يرجى اختيار رحلة العودة
            </p>
          </div>
          <?php endif; ?>

          
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-[#eeeef0]">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-6">
              <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                <span class="w-6 h-6 rounded-lg bg-[#1f442e] text-white text-[10px] font-black flex items-center justify-center">2</span>
                <span>بيانات المسافرين</span>
              </h4>
              <?php if($prevPassengers->count() > 0): ?>
                <button type="button" onclick="togglePrevPassengers()" class="text-[10px] font-bold text-[#d49920] hover:text-[#b87d18] transition-colors flex items-center gap-1 cursor-pointer">
                  <i data-lucide="users" class="w-3.5 h-3.5"></i>
                  اختيار سريع من مسافرين سابقين
                </button>
              <?php endif; ?>
            </div>

            
            <?php if($prevPassengers->count() > 0): ?>
            <div id="prev-passengers-panel" class="hidden mb-6">
              <div class="bg-[#1f442e]/5 rounded-xl p-4 border border-[#1f442e]/10">
                <p class="text-[10px] text-[#1f442e]/70 font-semibold mb-3">اختر مسافر سابق لتعبئة البيانات تلقائياً:</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto scrollbar-thin">
                  <?php $__currentLoopData = $prevPassengers->take(10); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <button type="button" onclick="fillPassengerFromPrev(this, <?php echo e(json_encode([
                      'name' => $pp->full_name,
                      'birthday' => $pp->birthday ? $pp->birthday->format('Y-m-d') : '',
                      'gender_id' => $pp->gender_id ?? '',
                      'phone_number' => $pp->phone_number ?? '',
                    ])); ?>)"
                      class="prev-passenger-btn flex items-center gap-3 bg-white rounded-lg p-3 border border-gray-100 hover:border-[#e7bd54] hover:bg-[#e7bd54]/5 transition-all text-right cursor-pointer">
                      <div class="w-8 h-8 rounded-full bg-[#1f442e]/10 flex items-center justify-center flex-shrink-0">
                        <i data-lucide="user" class="w-4 h-4 text-[#1f442e]/50"></i>
                      </div>
                      <div class="min-w-0">
                        <div class="text-xs font-bold text-gray-800 truncate"><?php echo e($pp->full_name); ?></div>
                        <div class="text-[10px] text-gray-400"><?php echo e($pp->phone_number ?? 'بدون رقم'); ?></div>
                      </div>
                    </button>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
              </div>
            </div>
            <?php endif; ?>

            
            <div id="passengers-container" class="space-y-4">
              
            </div>
          </div>

          
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-[#eeeef0]">
            <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-gray-100 pb-3 mb-6">
              <span class="w-6 h-6 rounded-lg bg-[#1f442e] text-white text-[10px] font-black flex items-center justify-center">3</span>
              <span>طريقة الدفع</span>
            </h4>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="payment-methods-grid">
              
              <label class="payment-method-label relative flex items-center gap-4 bg-[#f9f9fc] rounded-xl p-4 border-2 border-transparent cursor-pointer transition-all hover:border-gray-200 has-[:checked]:border-[#1f442e] has-[:checked]:bg-[#1f442e]/5">
                <input type="radio" name="payment_method_id" value="1" class="peer hidden" checked>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
                  <i data-lucide="banknote" class="w-6 h-6 text-emerald-600"></i>
                </div>
                <div class="flex-1">
                  <div class="text-sm font-bold text-gray-800">نقداً</div>
                  <div class="text-[10px] text-gray-400 font-medium">ادفع عند محطة الانطلاق</div>
                </div>
                <div class="w-5 h-5 rounded-full border-2 border-gray-300 peer-checked:border-[#1f442e] flex items-center justify-center transition-colors">
                  <div class="w-2.5 h-2.5 rounded-full bg-[#1f442e] scale-0 peer-checked:scale-100 transition-transform"></div>
                </div>
              </label>

              
              <label class="payment-method-label relative flex items-center gap-4 bg-[#f9f9fc] rounded-xl p-4 border-2 border-transparent cursor-pointer transition-all hover:border-gray-200 has-[:checked]:border-[#1f442e] has-[:checked]:bg-[#1f442e]/5">
                <input type="radio" name="payment_method_id" value="2" class="peer hidden">
                <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                  <i data-lucide="credit-card" class="w-6 h-6 text-blue-600"></i>
                </div>
                <div class="flex-1">
                  <div class="text-sm font-bold text-gray-800">بطاقة ائتمان</div>
                  <div class="text-[10px] text-gray-400 font-medium">Visa, Mastercard</div>
                </div>
                <div class="w-5 h-5 rounded-full border-2 border-gray-300 peer-checked:border-[#1f442e] flex items-center justify-center transition-colors">
                  <div class="w-2.5 h-2.5 rounded-full bg-[#1f442e] scale-0 peer-checked:scale-100 transition-transform"></div>
                </div>
              </label>

              
              <label class="payment-method-label relative flex items-center gap-4 bg-[#f9f9fc] rounded-xl p-4 border-2 border-transparent cursor-pointer transition-all hover:border-gray-200 has-[:checked]:border-[#1f442e] has-[:checked]:bg-[#1f442e]/5">
                <input type="radio" name="payment_method_id" value="3" class="peer hidden">
                <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center flex-shrink-0">
                  <i data-lucide="landmark" class="w-6 h-6 text-purple-600"></i>
                </div>
                <div class="flex-1">
                  <div class="text-sm font-bold text-gray-800">تحويل بنكي</div>
                  <div class="text-[10px] text-gray-400 font-medium">تحويل مباشر إلى الحساب</div>
                </div>
                <div class="w-5 h-5 rounded-full border-2 border-gray-300 peer-checked:border-[#1f442e] flex items-center justify-center transition-colors">
                  <div class="w-2.5 h-2.5 rounded-full bg-[#1f442e] scale-0 peer-checked:scale-100 transition-transform"></div>
                </div>
              </label>

              
              <?php if($walletEnabled && isset($walletBalance)): ?>
              <label class="payment-method-label relative flex items-center gap-4 bg-[#f9f9fc] rounded-xl p-4 border-2 border-transparent cursor-pointer transition-all hover:border-gray-200 has-[:checked]:border-[#1f442e] has-[:checked]:bg-[#1f442e]/5" id="wallet-payment-label">
                <input type="radio" name="payment_method_id" value="4" class="peer hidden" id="wallet-radio">
                <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center flex-shrink-0">
                  <i data-lucide="wallet" class="w-6 h-6 text-amber-600"></i>
                </div>
                <div class="flex-1">
                  <div class="text-sm font-bold text-gray-800">المحفظة الرقمية</div>
                  <div class="text-[10px] text-gray-400 font-medium">رصيد متاح: <strong class="text-emerald-600"><?php echo e(number_format($walletBalance, 2)); ?></strong> <?php echo e($baseCurrency->symbol ?? $baseCurrency->code ?? ''); ?></div>
                </div>
                <div class="w-5 h-5 rounded-full border-2 border-gray-300 peer-checked:border-[#1f442e] flex items-center justify-center transition-colors">
                  <div class="w-2.5 h-2.5 rounded-full bg-[#1f442e] scale-0 peer-checked:scale-100 transition-transform"></div>
                </div>
              </label>
              <?php endif; ?>
            </div>

            
            <div id="wallet-warning" class="hidden mt-4 flex items-center gap-2 text-xs text-red-600 bg-red-50 rounded-lg p-3 border border-red-100">
              <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
              <span>رصيد المحفظة غير كافٍ لإتمام هذه العملية</span>
            </div>
          </div>

          
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-[#eeeef0]">
            <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-gray-100 pb-3 mb-5">
              <span class="w-6 h-6 rounded-lg bg-[#1f442e] text-white text-[10px] font-black flex items-center justify-center">4</span>
              <span>كود الخصم</span>
              <span class="text-[9px] text-slate-300 font-normal normal-case tracking-normal">(اختياري)</span>
            </h4>

            <div class="flex gap-3">
              <div class="flex-1 relative">
                <i data-lucide="tag" class="w-4 h-4 text-gray-300 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input type="text" name="coupon_code" id="coupon-code-input"
                       placeholder="أدخل كود الخصم هنا..."
                       class="w-full h-12 pr-11 pl-4 bg-[#f9f9fc] border border-gray-200 rounded-xl text-sm font-bold text-gray-800 placeholder:text-gray-300 focus:ring-2 focus:ring-[#e7bd54]/30 focus:border-[#e7bd54] transition-all outline-none" dir="ltr">
              </div>
              <button type="button" onclick="applyCoupon()" id="coupon-apply-btn"
                      class="px-6 h-12 bg-[#f9f9fc] border border-gray-200 text-gray-600 font-bold text-sm rounded-xl hover:bg-[#1f442e]/5 hover:border-[#1f442e]/20 hover:text-[#1f442e] transition-all flex items-center gap-2 cursor-pointer">
                <i data-lucide="check" class="w-4 h-4"></i>
                <span>تطبيق</span>
              </button>
            </div>

            
            <div id="coupon-success" class="hidden mt-3 flex items-center gap-2 text-xs text-emerald-700 bg-emerald-50 rounded-lg p-3 border border-emerald-100">
              <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
              <span id="coupon-success-text"></span>
              <button type="button" onclick="removeCoupon()" class="mr-auto text-[10px] font-bold text-red-500 hover:text-red-700 flex items-center gap-1 cursor-pointer">
                <i data-lucide="x" class="w-3 h-3"></i> إزالة
              </button>
            </div>
            <div id="coupon-error" class="hidden mt-3 flex items-center gap-2 text-xs text-red-600 bg-red-50 rounded-lg p-3 border border-red-100">
              <i data-lucide="x-circle" class="w-4 h-4 flex-shrink-0"></i>
              <span id="coupon-error-text"></span>
            </div>
          </div>

          
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-[#eeeef0]">
            <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-gray-100 pb-3 mb-5">
              <span class="w-6 h-6 rounded-lg bg-[#1f442e] text-white text-[10px] font-black flex items-center justify-center">5</span>
              <span>ملاحظات إضافية</span>
              <span class="text-[9px] text-slate-300 font-normal normal-case tracking-normal">(اختياري)</span>
            </h4>
            <textarea name="note" rows="3" placeholder="أي ملاحظات خاصة بالحجز..."
                      class="w-full bg-[#f9f9fc] border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium text-gray-700 placeholder:text-gray-300 focus:ring-2 focus:ring-[#e7bd54]/30 focus:border-[#e7bd54] transition-all outline-none resize-none"></textarea>
          </div>

          
          <div class="bg-amber-50/40 border border-amber-100 rounded-2xl p-5">
            <label class="flex items-start gap-3 cursor-pointer">
              <input type="checkbox" id="terms-checkbox" class="mt-1 w-4 h-4 rounded border-amber-300 text-[#1f442e] focus:ring-[#1f442e]/30 cursor-pointer">
              <span class="text-xs text-amber-800 font-medium leading-relaxed">
                أوافق على <a href="#" class="font-bold text-[#1f442e] underline underline-offset-2 hover:text-[#d49920]">شروط وأحكام</a> الإستخدام و
                <a href="#" class="font-bold text-[#1f442e] underline underline-offset-2 hover:text-[#d49920]">سياسة الإلغاء</a> الخاصة بالرحلة.
              </span>
            </label>
          </div>

        </div>

        
        
        
        <div class="lg:col-span-4">
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-[#eeeef0] lg:sticky lg:top-24 z-20" id="price-summary-card">

            <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-gray-100 pb-3 mb-5">
              <i data-lucide="receipt" class="w-4 h-4 text-emerald-600"></i>
              <span>ملخص الأسعار</span>
            </h4>

            
            <div class="bg-gradient-to-br from-[#0c1d14] to-[#1f442e] rounded-xl p-4 mb-1 text-white">
              <div class="flex items-center gap-2 text-sm font-black mb-1">
                <span class="text-[9px] text-[#e7bd54] font-bold">ذهاب</span>
                <span><?php echo e($trip->departureCity->name); ?></span>
                <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-[#e7bd54]"></i>
                <span><?php echo e($trip->destinationCity->name); ?></span>
              </div>
              <div class="flex items-center gap-3 text-[10px] text-white/50 font-medium">
                <span><?php echo e($trip->departure_time->format('d/m/Y')); ?></span>
                <span>·</span>
                <span><?php echo e($trip->departure_time->format('h:i A')); ?></span>
                <span>·</span>
                <span><?php echo e($vehicleTypeName); ?></span>
              </div>
            </div>
            <?php if($isRoundTrip): ?>
            <div id="return-trip-summary-card" class="bg-gradient-to-br from-[#1f442e] to-[#0c1d14] rounded-xl p-4 mb-5 text-white">
              <div class="flex items-center gap-2 text-sm font-black mb-1">
                <span class="text-[9px] text-[#e7bd54] font-bold">عودة</span>
                <span><?php echo e($trip->destinationCity->name); ?></span>
                <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-[#e7bd54]"></i>
                <span><?php echo e($trip->departureCity->name); ?></span>
              </div>
              <div class="flex items-center gap-3 text-[10px] text-white/50 font-medium" id="return-trip-meta">
                <span id="return-meta-date">—</span>
                <span>·</span>
                <span id="return-meta-time">—</span>
                <span>·</span>
                <span id="return-meta-vehicle">—</span>
              </div>
            </div>
            <?php else: ?>
            <div class="mb-5"></div>
            <?php endif; ?>

            
            <div class="space-y-3 mb-5">
              
              <div class="flex justify-between items-center text-sm">
                <span class="text-gray-500 font-medium">
                  <span id="adults-label">1</span> × بالغ
                </span>
                <span class="font-bold text-gray-800">
                  <span id="adults-total" class="trip-price" data-base-price="<?php echo e($displayPriceAdult); ?>"><?php echo e(number_format((int)$displayPriceAdult, 0)); ?></span>
                  <span class="trip-currency-symbol text-xs text-gray-400"><?php echo e($baseCurrency->symbol ?? ''); ?></span>
                </span>
              </div>

              
              <div id="children-line" class="flex justify-between items-center text-sm hidden">
                <span class="text-gray-500 font-medium">
                  <span id="children-label">0</span> × طفل
                </span>
                <span class="font-bold text-gray-800">
                  <span id="children-total" class="trip-price" data-base-price="<?php echo e($displayPriceChild); ?>">0</span>
                  <span class="trip-currency-symbol text-xs text-gray-400"><?php echo e($baseCurrency->symbol ?? ''); ?></span>
                </span>
              </div>

              
              <div class="border-t border-gray-100 pt-3 flex justify-between items-center text-sm">
                <span class="text-gray-500 font-medium">المجموع الفرعي</span>
                <span class="font-bold text-gray-800">
                  <span id="subtotal"><?php echo e(number_format((int)$displayPriceAdult, 0)); ?></span>
                  <span class="trip-currency-symbol text-xs text-gray-400"><?php echo e($baseCurrency->symbol ?? ''); ?></span>
                </span>
              </div>

              <?php if($isRoundTrip): ?>
              
              <div id="return-price-line" class="flex justify-between items-center text-sm">
                <span class="text-gray-500 font-medium">
                  <span class="text-[9px] text-[#e7bd54] font-bold">عودة</span>
                  <span id="return-adults-label">1</span> × بالغ
                </span>
                <span class="font-bold text-gray-800">
                  <span id="return-adults-total">0</span>
                  <span class="trip-currency-symbol text-xs text-gray-400"><?php echo e($baseCurrency->symbol ?? ''); ?></span>
                </span>
              </div>
              <div id="return-children-line" class="flex justify-between items-center text-sm hidden">
                <span class="text-gray-500 font-medium">
                  <span class="text-[9px] text-[#e7bd54] font-bold">عودة</span>
                  <span id="return-children-label">0</span> × طفل
                </span>
                <span class="font-bold text-gray-800">
                  <span id="return-children-total">0</span>
                  <span class="trip-currency-symbol text-xs text-gray-400"><?php echo e($baseCurrency->symbol ?? ''); ?></span>
                </span>
              </div>
              <?php endif; ?>

              
              <div id="coupon-discount-line" class="hidden flex justify-between items-center text-sm">
                <span class="text-emerald-600 font-medium flex items-center gap-1">
                  <i data-lucide="tag" class="w-3.5 h-3.5"></i>
                  <span>خصم الكوبون</span>
                </span>
                <span class="font-bold text-emerald-600">
                  − <span id="coupon-discount-amount">0</span>
                  <span class="trip-currency-symbol text-xs"><?php echo e($baseCurrency->symbol ?? ''); ?></span>
                </span>
              </div>

              
              <?php if($hasDiscount): ?>
              <div class="flex justify-between items-center text-sm bg-red-50 -mx-2 px-2 py-1.5 rounded-lg">
                <span class="text-red-600 font-medium text-xs">خصم ترويجي <?php echo e(number_format($trip->discount_percentage, 0)); ?>%</span>
                <span class="font-bold text-red-600 text-xs">
                  متضمن بالسعر
                </span>
              </div>
              <?php endif; ?>
            </div>

            
            <div class="bg-gradient-to-l from-[#f9f5ec] to-[#f5f0e3] rounded-xl p-5 mb-5">
              <div class="flex justify-between items-center">
                <span class="text-sm font-bold text-[#1f442e]">الإجمالي المطلوب</span>
                <div class="flex items-baseline gap-1.5">
                  <span id="grand-total" class="text-3xl font-black text-[#1f442e] trip-price" data-base-price="<?php echo e($displayPriceAdult); ?>"><?php echo e(number_format((int)$displayPriceAdult, 0)); ?></span>
                  <span class="text-sm font-bold text-[#1f442e]/60 trip-currency-symbol"><?php echo e($baseCurrency->symbol ?? ''); ?></span>
                </div>
              </div>
              <div id="passengers-total-label" class="text-[10px] text-[#1f442e]/40 font-semibold mt-1">إجمالي 1 تذكرة</div>
            </div>

            
            <div class="mb-5">
              <div class="flex items-center gap-2 mb-2.5">
                <i data-lucide="repeat" class="w-3.5 h-3.5 text-gray-400"></i>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">تحويل العملة</span>
              </div>
              <div class="flex flex-wrap gap-1.5" id="currency-booking">
                <?php $__currentLoopData = $currencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $curr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <button type="button" onclick="setCurrency(<?php echo e($curr['id']); ?>)" data-currency-id="<?php echo e($curr['id']); ?>"
                    class="curr-btn-booking px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all cursor-pointer
                      <?php echo e($curr['id'] === $baseCurrencyId
                         ? 'bg-[#1f442e] text-white shadow-md'
                         : 'text-gray-500 hover:text-gray-800 hover:bg-gray-100 border border-gray-200'); ?>">
                    <?php echo e($curr['code']); ?>

                  </button>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </div>
            </div>

            
            <?php if(isset($walletBalance) && $walletBalance > 0): ?>
            <div class="bg-emerald-50/50 rounded-xl p-4 mb-5 border border-emerald-100 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <i data-lucide="wallet" class="w-4 h-4 text-emerald-600"></i>
                <span class="text-emerald-800 text-xs font-bold">رصيد المحفظة</span>
              </div>
              <span class="text-sm font-black text-gray-900"><?php echo e(number_format($walletBalance, 2)); ?> <?php echo e($baseCurrency->symbol ?? ''); ?></span>
            </div>
            <?php endif; ?>

            
            <button type="submit" id="submit-booking-btn"
                    class="w-full bg-gradient-to-l from-[#e7bd54] to-[#d49920] hover:from-[#d49920] hover:to-[#b87d18] text-[#0c1d14] font-bold text-sm rounded-xl shadow-lg shadow-[#e7bd54]/20 flex items-center justify-center gap-2.5 px-6 py-4 transition-all active:scale-[0.98] cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
              <i data-lucide="shield-check" class="w-5 h-5"></i>
              <span>تأكيد الحجز والدفع</span>
            </button>

            
            <div class="mt-4 flex items-center justify-center gap-4 text-[10px] text-gray-400 font-semibold">
              <span class="flex items-center gap-1"><i data-lucide="lock" class="w-3 h-3"></i> دفع آمن</span>
              <span class="flex items-center gap-1"><i data-lucide="shield" class="w-3 h-3"></i> حماية البيانات</span>
              <span class="flex items-center gap-1"><i data-lucide="headphones" class="w-3 h-3"></i> دعم 24/7</span>
            </div>
          </div>
        </div>

      </div>
    </form>

  </div>
</section>




<div class="lg:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-xl border-t border-gray-200 shadow-[0_-4px_20px_rgba(0,0,0,0.08)]" id="mobile-booking-bar">
  <div class="px-4 py-3 flex items-center gap-3">
    <div class="flex-1 text-right">
      <div class="text-gray-400 text-[10px] font-semibold">الإجمالي</div>
      <div class="flex items-baseline gap-1">
        <span class="text-xl font-black text-gray-900 trip-price" id="mobile-grand-total" data-base-price="<?php echo e($displayPriceAdult); ?>"><?php echo e(number_format((int)$displayPriceAdult, 0)); ?></span>
        <span class="text-xs text-gray-500 font-bold trip-currency-symbol"><?php echo e($baseCurrency->symbol ?? ''); ?></span>
      </div>
      <div class="text-[9px] text-gray-400" id="mobile-passengers-label">1 تذكرة</div>
    </div>
    <button type="button" id="mobile-submit-booking-btn" onclick="scrollToTopAndSubmit()"
            class="bg-gradient-to-l from-[#e7bd54] to-[#d49920] text-[#0c1d14] font-bold text-sm rounded-xl shadow-lg flex items-center gap-2 px-5 py-3.5 flex-shrink-0 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
      <i data-lucide="shield-check" class="w-4 h-4"></i>
      <span>تأكيد الحجز</span>
    </button>
  </div>
</div>

<?php $__env->stopSection(); ?>




<?php $__env->startPush('styles'); ?>
<style>
  @keyframes slideUp {
    from { transform: translateY(100%); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
  }
  @keyframes fadeInUp {
    from { transform: translateY(12px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
  }
  .slide-up { animation: slideUp 0.4s ease-out; }
  #mobile-booking-bar { animation: slideUp 0.4s ease-out; }
  .fade-in-up { animation: fadeInUp 0.3s ease-out; }

  @media (max-width: 1023px) {
    body { padding-bottom: 80px; }
  }

  /* Custom scrollbar */
  .scrollbar-thin::-webkit-scrollbar { width: 4px; }
  .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
  .scrollbar-thin::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 999px; }
  .scrollbar-thin::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

  /* Payment method radio visual state */
  .payment-method-label:has(input:checked) .peer + div + div + div,
  .payment-method-label:has(input:checked) > div:last-child > div {
    transform: scale(1) !important;
  }

  /* Fix for Tailwind has-[] not always working with radio */
  .payment-method-label input:checked ~ div:last-child div {
    transform: scale(1) !important;
    background-color: #1f442e !important;
  }
  .payment-method-label input:checked ~ div:first-of-type,
  .payment-method-label:has(input:checked) {
    border-color: #1f442e !important;
    background-color: rgba(31,68,46,0.03) !important;
  }

  /* Passenger card numbering */
  .passenger-card { counter-increment: passenger; }
  .passenger-card .passenger-number::before { content: counter(passenger); }

  /* Input focus states */
  input[type="text"]:focus, input[type="tel"]:focus, input[type="date"]:focus,
  input[type="number"]:focus, select:focus, textarea:focus {
    border-color: #e7bd54 !important;
    box-shadow: 0 0 0 3px rgba(231,189,84,0.15) !important;
  }

  /* Number input hide arrows */
  input[type="number"]::-webkit-outer-spin-button,
  input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none; margin: 0;
  }
  input[type="number"] { -moz-appearance: textfield; }

  select option {
    color: #1f442e;
    background: #f9f5ec;
  }

  /* Glass card */
  .glass {
    background: rgba(255,255,255,0.08);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.12);
  }

  /* Dot pattern */
  .dot-pattern {
    background-image: radial-gradient(rgba(0,0,0,0.04) 1px, transparent 1px);
    background-size: 24px 24px;
  }

  /* Accordion transition */
  .id-section {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.35s ease, opacity 0.3s ease, margin 0.3s ease;
    opacity: 0;
    margin-top: 0;
  }
  .id-section.open {
    max-height: 600px;
    opacity: 1;
    margin-top: 1rem;
  }
</style>
<?php $__env->stopPush(); ?>




<?php $__env->startPush('scripts'); ?>
<script>
if (typeof lucide !== 'undefined') lucide.createIcons();

// ─── Config ────────────────────────────────────────────────────────
const CONFIG = {
  maxAdults: <?php echo e(min($ticketLimit, 10)); ?>,
  maxChildren: <?php echo e(min($ticketLimit, 5)); ?>,
  availableSeats: <?php echo e($availableSeats); ?>,
  priceAdult: <?php echo e((float)$displayPriceAdult); ?>,
  priceChild: <?php echo e((float)$displayPriceChild); ?>,
  walletBalance: <?php echo e(isset($walletBalance) ? (float)$walletBalance : 0); ?>,
  tripUuid: '<?php echo e($trip->uuid); ?>',
};

let couponApplied = false;
let couponDiscountPercent = 0;
let couponDiscountFixed = 0;
let couponMaxDiscount = 0;
let couponType = 'percentage';

// ─── Currency ──────────────────────────────────────────────────────
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
  document.getElementById('currency_id').value = id;
  const container = document.getElementById('currency-booking');
  if (container) {
    container.querySelectorAll('.curr-btn-booking').forEach(btn => {
      const btnId = parseInt(btn.getAttribute('data-currency-id'));
      if (btnId === id) {
        btn.className = 'curr-btn-booking px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all cursor-pointer bg-[#1f442e] text-white shadow-md';
      } else {
        btn.className = 'curr-btn-booking px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all cursor-pointer text-gray-500 hover:text-gray-800 hover:bg-gray-100 border border-gray-200';
      }
    });
  }
  updateAllPrices();
  recalculate();
}

// ─── Passenger Count ───────────────────────────────────────────────
function adjustCount(type, delta) {
  const input = document.getElementById(type + '-count');
  let val = parseInt(input.value) || 0;
  const max = type === 'adults' ? CONFIG.maxAdults : CONFIG.maxChildren;
  const min = type === 'adults' ? 1 : 0;
  val = Math.min(max, Math.max(min, val + delta));
  input.value = val;
  updatePassengerForms();
  recalculate();
  checkSeatsWarning();
}

function checkSeatsWarning() {
  const adults = parseInt(document.getElementById('adults-count').value) || 0;
  const children = parseInt(document.getElementById('children-count').value) || 0;
  const total = adults + children;
  const warning = document.getElementById('seats-warning');
  if (total > CONFIG.availableSeats) {
    warning.style.display = 'flex';
  } else {
    warning.style.display = 'none';
  }
}

// ─── Generate Passenger Forms ──────────────────────────────────────
let passengerFormCounter = 0;

function generatePassengerCard(index, totalAdults) {
  const isChild = index >= totalAdults;
  const label = isChild ? 'طفل' : 'بالغ';
  const labelColor = isChild ? 'text-amber-600 bg-amber-50 border-amber-100' : 'text-emerald-700 bg-emerald-50 border-emerald-100';
  const iconColor = isChild ? 'text-amber-500' : 'text-emerald-600';
  const icon = isChild ? 'baby' : 'user';

  return `
  <div class="passenger-card fade-in-up bg-[#f9f9fc] rounded-2xl border border-gray-100 overflow-hidden" data-passenger-index="${index}">
    <div class="flex items-center justify-between p-4 border-b border-gray-100">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg ${labelColor} border flex items-center justify-center">
          <span class="text-xs font-black">${index + 1}</span>
        </div>
        <div class="flex items-center gap-2">
          <i data-lucide="${icon}" class="w-4 h-4 ${iconColor}"></i>
          <span class="text-sm font-bold text-gray-800">المسافر ${index + 1}</span>
          <span class="text-[10px] font-bold ${labelColor} px-2 py-0.5 rounded-full border">${label}</span>
        </div>
      </div>
      <button type="button" onclick="toggleIdSection(${index})" class="text-[10px] font-bold text-[#d49920] hover:text-[#b87d18] transition-colors flex items-center gap-1 cursor-pointer">
        <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
        بيانات الهوية/الجواز
        <i data-lucide="chevron-down" class="w-3 h-3 transition-transform duration-300" id="id-chevron-${index}"></i>
      </button>
    </div>
    <div class="p-4 space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-[11px] font-bold text-gray-500 mb-1.5">الاسم الكامل <span class="text-red-400">*</span></label>
          <input type="text" name="passengers[${index}][name]" required
                 class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm font-medium text-gray-800 placeholder:text-gray-300 focus:ring-2 focus:ring-[#e7bd54]/30 transition-all outline-none"
                 placeholder="أدخل الاسم الكامل">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-gray-500 mb-1.5">تاريخ الميلاد <span class="text-red-400">*</span></label>
          <input type="date" name="passengers[${index}][birthday]" required
                 class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm font-medium text-gray-800 focus:ring-2 focus:ring-[#e7bd54]/30 transition-all outline-none">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-gray-500 mb-1.5">الجنس</label>
          <select name="passengers[${index}][gender_id]"
                  class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm font-medium text-gray-800 focus:ring-2 focus:ring-[#e7bd54]/30 transition-all outline-none">
            <option value="">اختر الجنس...</option>
            <?php $__currentLoopData = $genders ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $gender): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($gender->id); ?>"><?php echo e($gender->name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </div>
        <div>
          <label class="block text-[11px] font-bold text-gray-500 mb-1.5">رقم الجوال</label>
          <input type="tel" name="passengers[${index}][phone_number]" dir="ltr"
                 class="w-full h-11 bg-white border border-gray-200 rounded-xl px-4 text-sm font-medium text-gray-800 placeholder:text-gray-300 focus:ring-2 focus:ring-[#e7bd54]/30 transition-all outline-none"
                 placeholder="7XXXXXXXX">
        </div>
      </div>

      
      <div class="id-section" id="id-section-${index}">
        <div class="bg-white rounded-xl p-4 border border-gray-200 space-y-4">
          <div class="flex items-center gap-2 mb-1">
            <i data-lucide="id-card" class="w-4 h-4 text-purple-500"></i>
            <span class="text-xs font-bold text-purple-700">بيانات الهوية الشخصية</span>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-[11px] font-bold text-gray-500 mb-1.5">رقم الهوية</label>
              <input type="text" name="passengers[${index}][personal_card_id]" dir="ltr"
                     class="w-full h-11 bg-[#f9f9fc] border border-gray-200 rounded-xl px-4 text-sm font-medium text-gray-800 placeholder:text-gray-300 focus:ring-2 focus:ring-[#e7bd54]/30 transition-all outline-none"
                     placeholder="رقم بطاقة الهوية">
            </div>
            <div>
              <label class="block text-[11px] font-bold text-gray-500 mb-1.5">مكان الإصدار</label>
              <input type="text" name="passengers[${index}][personal_card_place_of_issue]"
                     class="w-full h-11 bg-[#f9f9fc] border border-gray-200 rounded-xl px-4 text-sm font-medium text-gray-800 placeholder:text-gray-300 focus:ring-2 focus:ring-[#e7bd54]/30 transition-all outline-none"
                     placeholder="المدينة / البلد">
            </div>
            <div>
              <label class="block text-[11px] font-bold text-gray-500 mb-1.5">تاريخ إصدار الهوية</label>
              <input type="date" name="passengers[${index}][personal_card_date_issu]"
                     class="w-full h-11 bg-[#f9f9fc] border border-gray-200 rounded-xl px-4 text-sm font-medium text-gray-800 focus:ring-2 focus:ring-[#e7bd54]/30 transition-all outline-none">
            </div>
            <div>
              <label class="block text-[11px] font-bold text-gray-500 mb-1.5">تاريخ انتهاء الهوية</label>
              <input type="date" name="passengers[${index}][personal_card_date_expiry]"
                     class="w-full h-11 bg-[#f9f9fc] border border-gray-200 rounded-xl px-4 text-sm font-medium text-gray-800 focus:ring-2 focus:ring-[#e7bd54]/30 transition-all outline-none">
            </div>
          </div>

          <div class="border-t border-gray-100 pt-4 mt-4">
            <div class="flex items-center gap-2 mb-3">
              <i data-lucide="plane" class="w-4 h-4 text-blue-500"></i>
              <span class="text-xs font-bold text-blue-700">بيانات الجواز (اختياري)</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-[11px] font-bold text-gray-500 mb-1.5">رقم الجواز</label>
                <input type="text" name="passengers[${index}][passport_number]" dir="ltr"
                       class="w-full h-11 bg-[#f9f9fc] border border-gray-200 rounded-xl px-4 text-sm font-medium text-gray-800 placeholder:text-gray-300 focus:ring-2 focus:ring-[#e7bd54]/30 transition-all outline-none"
                       placeholder="رقم الجواز">
              </div>
              <div>
                <label class="block text-[11px] font-bold text-gray-500 mb-1.5">مكان الإصدار</label>
                <input type="text" name="passengers[${index}][passport_place_of_issue]"
                       class="w-full h-11 bg-[#f9f9fc] border border-gray-200 rounded-xl px-4 text-sm font-medium text-gray-800 placeholder:text-gray-300 focus:ring-2 focus:ring-[#e7bd54]/30 transition-all outline-none"
                       placeholder="المدينة / البلد">
              </div>
              <div>
                <label class="block text-[11px] font-bold text-gray-500 mb-1.5">تاريخ إصدار الجواز</label>
                <input type="date" name="passengers[${index}][passport_date_issu]"
                       class="w-full h-11 bg-[#f9f9fc] border border-gray-200 rounded-xl px-4 text-sm font-medium text-gray-800 focus:ring-2 focus:ring-[#e7bd54]/30 transition-all outline-none">
              </div>
              <div>
                <label class="block text-[11px] font-bold text-gray-500 mb-1.5">تاريخ انتهاء الجواز</label>
                <input type="date" name="passengers[${index}][passport_date_expiry]"
                       class="w-full h-11 bg-[#f9f9fc] border border-gray-200 rounded-xl px-4 text-sm font-medium text-gray-800 focus:ring-2 focus:ring-[#e7bd54]/30 transition-all outline-none">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  `;
}

function updatePassengerForms() {
  const adults = parseInt(document.getElementById('adults-count').value) || 1;
  const children = parseInt(document.getElementById('children-count').value) || 0;
  const total = adults + children;
  const container = document.getElementById('passengers-container');

  // Preserve existing form data
  const existingData = [];
  container.querySelectorAll('.passenger-card').forEach(card => {
    const idx = card.getAttribute('data-passenger-index');
    const inputs = card.querySelectorAll('input, select');
    const data = {};
    inputs.forEach(inp => {
      if (inp.name) data[inp.name] = inp.value;
    });
    existingData[idx] = data;
  });

  container.innerHTML = '';
  for (let i = 0; i < total; i++) {
    container.insertAdjacentHTML('beforeend', generatePassengerCard(i, adults));

    // Restore previous data if exists
    if (existingData[i]) {
      const newCard = container.querySelector(`[data-passenger-index="${i}"]`);
      if (newCard) {
        Object.entries(existingData[i]).forEach(([name, value]) => {
          const inp = newCard.querySelector(`[name="${name}"]`);
          if (inp) inp.value = value;
        });
      }
    }
  }

  if (typeof lucide !== 'undefined') lucide.createIcons();
}

// ─── ID Section Toggle ────────────────────────────────────────────
function toggleIdSection(index) {
  const section = document.getElementById('id-section-' + index);
  const chevron = document.getElementById('id-chevron-' + index);
  if (!section) return;
  section.classList.toggle('open');
  if (chevron) {
    chevron.style.transform = section.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0deg)';
  }
}

// ─── Previous Passengers ───────────────────────────────────────────
function togglePrevPassengers() {
  const panel = document.getElementById('prev-passengers-panel');
  if (panel) panel.classList.toggle('hidden');
}

let nextFillTarget = 0;
function fillPassengerFromPrev(btn, data) {
  const container = document.getElementById('passengers-container');
  const cards = container.querySelectorAll('.passenger-card');
  const targetIndex = nextFillTarget % cards.length;
  const card = cards[targetIndex];
  if (!card) return;

  // Highlight the card briefly
  card.style.boxShadow = '0 0 0 2px #e7bd54';
  setTimeout(() => { card.style.boxShadow = ''; }, 1500);

  // Fill fields
  if (data.name) {
    const nameInput = card.querySelector('input[name$="][name]"]') || card.querySelector('input[name*="[name]"]');
    if (nameInput) nameInput.value = data.name;
  }
  if (data.birthday) {
    const bdayInput = card.querySelector('input[name*="[birthday]"]');
    if (bdayInput) bdayInput.value = data.birthday;
  }
  if (data.gender_id) {
    const genderSelect = card.querySelector('select[name*="[gender_id]"]');
    if (genderSelect) genderSelect.value = data.gender_id;
  }
  if (data.phone_number) {
    const phoneInput = card.querySelector('input[name*="[phone_number]"]');
    if (phoneInput) phoneInput.value = data.phone_number;
  }

  nextFillTarget++;

  // Highlight the clicked button
  document.querySelectorAll('.prev-passenger-btn').forEach(b => b.classList.remove('border-[#e7bd54]', 'bg-[#e7bd54]/10'));
  btn.classList.add('border-[#e7bd54]', 'bg-[#e7bd54]/10');

  showToast('تم تعبئة بيانات المسافر #' + (targetIndex + 1), 'success');
}

// ─── Coupon ────────────────────────────────────────────────────────
function applyCoupon() {
  const input = document.getElementById('coupon-code-input');
  const code = input.value.trim();
  if (!code) return;

  const btn = document.getElementById('coupon-apply-btn');
  btn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> جارٍ التحقق...';
  if (typeof lucide !== 'undefined') lucide.createIcons();

  // Simulated validation (in production, call API)
  setTimeout(() => {
    // This should be an AJAX call to validate the coupon
    // For now, we'll show a placeholder response
    btn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i><span>تطبيق</span>';
    if (typeof lucide !== 'undefined') lucide.createIcons();

    // TODO: Replace with actual AJAX call:
    // fetch(`/api/coupons/validate?code=${encodeURIComponent(code)}&trip_uuid=${CONFIG.tripUuid}&amount=${getCurrentSubtotal()}`)
    //   .then(r => r.json())
    //   .then(data => { ... })

    showCouponError('سيتم التحقق من الكوبون عند تأكيد الحجز');
  }, 800);
}

function removeCoupon() {
  couponApplied = false;
  couponDiscountPercent = 0;
  couponDiscountFixed = 0;
  document.getElementById('coupon-code-input').value = '';
  document.getElementById('coupon-success').classList.add('hidden');
  recalculate();
}

function showCouponSuccess(message) {
  couponApplied = true;
  document.getElementById('coupon-success-text').textContent = message;
  document.getElementById('coupon-success').classList.remove('hidden');
  document.getElementById('coupon-error').classList.add('hidden');
}

function showCouponError(message) {
  document.getElementById('coupon-error-text').textContent = message;
  document.getElementById('coupon-error').classList.remove('hidden');
  document.getElementById('coupon-success').classList.add('hidden');
}

// ─── Price Calculation ─────────────────────────────────────────────
function getCurrentSubtotal() {
  const adults = parseInt(document.getElementById('adults-count').value) || 0;
  const children = parseInt(document.getElementById('children-count').value) || 0;
  return (adults * CONFIG.priceAdult) + (children * CONFIG.priceChild);
}

function recalculate() {
  const adults = parseInt(document.getElementById('adults-count').value) || 0;
  const children = parseInt(document.getElementById('children-count').value) || 0;
  const total = adults + children;

  const adultsTotal = adults * CONFIG.priceAdult;
  const childrenTotal = children * CONFIG.priceChild;
  const subtotal = adultsTotal + childrenTotal;

  // Update labels
  document.getElementById('adults-label').textContent = adults;
  document.getElementById('children-label').textContent = children;

  // Update totals
  const adultsTotalEl = document.getElementById('adults-total');
  if (adultsTotalEl) adultsTotalEl.textContent = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(adultsTotal);

  const childrenTotalEl = document.getElementById('children-total');
  if (childrenTotalEl) childrenTotalEl.textContent = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(childrenTotal);

  // Children line visibility
  const childrenLine = document.getElementById('children-line');
  if (childrenLine) childrenLine.classList.toggle('hidden', children === 0);

  // Subtotal
  const subtotalEl = document.getElementById('subtotal');
  if (subtotalEl) subtotalEl.textContent = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(subtotal);

  // Coupon discount
  let discount = 0;
  if (couponApplied) {
    if (couponType === 'percentage') {
      discount = subtotal * (couponDiscountPercent / 100);
      if (couponMaxDiscount > 0) discount = Math.min(discount, couponMaxDiscount);
    } else {
      discount = couponDiscountFixed;
    }
    discount = Math.min(discount, subtotal);
  }

  const couponLine = document.getElementById('coupon-discount-line');
  if (couponLine) {
    couponLine.classList.toggle('hidden', !couponApplied || discount === 0);
  }
  const couponAmountEl = document.getElementById('coupon-discount-amount');
  if (couponAmountEl) couponAmountEl.textContent = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(discount);

  const grandTotal = Math.max(0, subtotal - discount);

  // Update grand total
  const grandTotalEl = document.getElementById('grand-total');
  if (grandTotalEl) {
    grandTotalEl.textContent = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(grandTotal);
    grandTotalEl.setAttribute('data-base-price', grandTotal);
  }

  // Mobile total
  const mobileTotalEl = document.getElementById('mobile-grand-total');
  if (mobileTotalEl) {
    mobileTotalEl.textContent = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(grandTotal);
    mobileTotalEl.setAttribute('data-base-price', grandTotal);
  }

  // Passengers label
  const label = total === 1 ? 'تذكرة واحدة' : total + ' تذاكر';
  const passengersLabelEl = document.getElementById('passengers-total-label');
  if (passengersLabelEl) passengersLabelEl.textContent = 'إجمالي ' + label;

  const mobileLabelEl = document.getElementById('mobile-passengers-label');
  if (mobileLabelEl) mobileLabelEl.textContent = label;

  // Wallet warning
  checkWalletBalance(grandTotal);

  // Re-apply currency conversion
  updateAllPrices();
}

function checkWalletBalance(amount) {
  const walletRadio = document.getElementById('wallet-radio');
  const walletWarning = document.getElementById('wallet-warning');
  if (!walletRadio || !walletWarning) return;

  if (CONFIG.walletBalance < amount) {
    if (walletRadio.checked) {
      walletWarning.classList.remove('hidden');
    }
  } else {
    walletWarning.classList.add('hidden');
  }
}

// ─── Payment Method Radio Visual State ─────────────────────────────
document.querySelectorAll('.payment-method-label input[type="radio"]').forEach(radio => {
  radio.addEventListener('change', () => {
    // Reset all labels
    document.querySelectorAll('.payment-method-label').forEach(label => {
      label.style.borderColor = 'transparent';
      label.style.backgroundColor = '#f9f9fc';
      const dot = label.querySelector('div:last-child > div');
      if (dot) dot.style.transform = 'scale(0)';
    });
    // Activate selected
    const label = radio.closest('.payment-method-label');
    if (label) {
      label.style.borderColor = '#1f442e';
      label.style.backgroundColor = 'rgba(31,68,46,0.03)';
      const dot = label.querySelector('div:last-child > div');
      if (dot) dot.style.transform = 'scale(1)';
    }

    // Check wallet balance when selecting wallet
    if (radio.value === '4') {
      const total = getCurrentSubtotal();
      checkWalletBalance(total);
    } else {
      const walletWarning = document.getElementById('wallet-warning');
      if (walletWarning) walletWarning.classList.add('hidden');
    }
  });
});

// Initialize default checked state
const defaultChecked = document.querySelector('.payment-method-label input[type="radio"]:checked');
if (defaultChecked) defaultChecked.dispatchEvent(new Event('change'));

// ─── Button state helpers ─────────────────────────────────────
const ORIGINAL_BTN_HTML = '<i data-lucide="shield-check" class="w-5 h-5"></i> <span>تأكيد الحجز والدفع</span>';

function resetSubmitButtons() {
  const btn = document.getElementById('submit-booking-btn');
  if (btn) {
    btn.disabled = false;
    btn.innerHTML = ORIGINAL_BTN_HTML;
    if (typeof lucide !== 'undefined') lucide.createIcons();
  }
  const mobileBtn = document.getElementById('mobile-submit-booking-btn');
  if (mobileBtn) {
    mobileBtn.disabled = false;
  }
}

function setButtonsLoading() {
  const btn = document.getElementById('submit-booking-btn');
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader" class="w-5 h-5 animate-spin"></i> جارٍ معالجة الحجز...';
    if (typeof lucide !== 'undefined') lucide.createIcons();
  }
  const mobileBtn = document.getElementById('mobile-submit-booking-btn');
  if (mobileBtn) {
    mobileBtn.disabled = true;
  }
}

// ─── bfcache recovery: reset buttons when page is restored from cache ───
window.addEventListener('pageshow', function(event) {
  if (event.persisted) {
    resetSubmitButtons();
  }
});

// ─── Form Submission ───────────────────────────────────────────────
document.getElementById('booking-form').addEventListener('submit', function(e) {
  const terms = document.getElementById('terms-checkbox');
  if (terms && !terms.checked) {
    e.preventDefault();
    resetSubmitButtons();
    showToast('يجب الموافقة على الشروط والأحكام أولاً', 'warning');
    terms.scrollIntoView({ behavior: 'smooth', block: 'center' });
    return;
  }

  // Validate passenger forms
  const nameInputs = document.querySelectorAll('.passenger-card input[name*="[name]"]');
  let hasEmpty = false;
  nameInputs.forEach(inp => {
    if (!inp.value.trim()) {
      hasEmpty = true;
      inp.style.borderColor = '#ef4444';
      inp.parentElement.querySelector('label')?.classList.add('text-red-500');
    } else {
      inp.style.borderColor = '';
    }
  });
  if (hasEmpty) {
    e.preventDefault();
    resetSubmitButtons();
    showToast('يرجى تعبئة أسماء جميع المسافرين', 'error');
    const firstEmpty = document.querySelector('.passenger-card input[name*="[name]"][style*="border-color"]');
    if (firstEmpty) firstEmpty.scrollIntoView({ behavior: 'smooth', block: 'center' });
    return;
  }

  // All client-side validation passed — show loading state
  setButtonsLoading();
});

function scrollToTopAndSubmit() {
  // Validate terms first
  const terms = document.getElementById('terms-checkbox');
  if (terms && !terms.checked) {
    showToast('يجب الموافقة على الشروط والأحكام أولاً', 'warning');
    if (terms) terms.scrollIntoView({ behavior: 'smooth', block: 'center' });
    return;
  }

  // Validate passenger names
  const nameInputs = document.querySelectorAll('.passenger-card input[name*="[name]"]');
  let hasEmpty = false;
  nameInputs.forEach(inp => {
    if (!inp.value.trim()) {
      hasEmpty = true;
      inp.style.borderColor = '#ef4444';
      inp.parentElement.querySelector('label')?.classList.add('text-red-500');
    } else {
      inp.style.borderColor = '';
    }
  });
  if (hasEmpty) {
    showToast('يرجى تعبئة أسماء جميع المسافرين', 'error');
    const firstEmpty = document.querySelector('.passenger-card input[name*="[name]"][style*="border-color"]');
    if (firstEmpty) firstEmpty.scrollIntoView({ behavior: 'smooth', block: 'center' });
    return;
  }

  // Round-trip: check return trip selected
  if (IS_ROUND_TRIP) {
    const uuid = document.getElementById('return-trip-uuid')?.value;
    if (!uuid) {
      const err = document.getElementById('return-trip-empty-error');
      if (err) err.classList.remove('hidden');
      err?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      showToast('يرجى اختيار رحلة العودة', 'warning');
      return;
    }
  }

  // All valid — submit the form (submit event listener handles button state)
  try {
    document.getElementById('booking-form').submit();
  } catch(err) {
    resetSubmitButtons();
  }
}

// ─── Toast ─────────────────────────────────────────────────────────
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

// ─── Mobile bar visibility ─────────────────────────────────────────
function handleMobileBar() {
  const bar = document.getElementById('mobile-booking-bar');
  if (!bar) return;
  bar.style.display = (window.innerWidth >= 1024) ? 'none' : 'block';
}

// ─── Round Trip Logic ─────────────────────────────────────────────
const IS_ROUND_TRIP = <?php echo e($isRoundTrip ? 'true' : 'false'); ?>;
const OUTBOUND_UUID = '<?php echo e($trip->uuid ?? ""); ?>';
const OUTBOUND_PRICE_ADULT = <?php echo e((float)$displayPriceAdult); ?>;
const OUTBOUND_PRICE_CHILD = <?php echo e((float)$displayPriceChild); ?>;
let returnPriceAdult = 0;
let returnPriceChild = 0;
let selectedReturnUuid = '';
let isLoadingReturnTrips = false;

// ─── Date Picker: fetch return trips when date changes ───
function initReturnDatePicker() {
  if (!IS_ROUND_TRIP) return;
  const picker = document.getElementById('return-date-picker');
  if (!picker) return;
  picker.addEventListener('change', function() {
    fetchReturnTrips(this.value);
  });
}

// ─── AJAX: fetch return trips from SERVER only ───
// Security: Web endpoint (auth + CSRF protected), not public API.
// JS never validates trip validity. Server returns authoritative data.
// Only outbound_trip_uuid + date are sent. Prices/seats/provider all come from server.
function fetchReturnTrips(date) {
  if (!date || isLoadingReturnTrips) return;
  isLoadingReturnTrips = true;

  const listEl = document.getElementById('return-trips-list');
  const loadingEl = document.getElementById('return-trips-loading');
  const errorEl = document.getElementById('return-trip-empty-error');
  if (errorEl) errorEl.classList.add('hidden');
  if (loadingEl) loadingEl.classList.remove('hidden');
  if (listEl) listEl.innerHTML = '';

  // Reset return pricing until server responds
  returnPriceAdult = 0;
  returnPriceChild = 0;
  selectedReturnUuid = '';
  const hiddenInput = document.getElementById('return-trip-uuid');
  if (hiddenInput) hiddenInput.value = '';
  recalculate();

  const params = new URLSearchParams({
    outbound_trip_uuid: OUTBOUND_UUID,
    date: date
  });

  const endpoint = '<?php echo e(route("bookings.search-return-trips")); ?>';
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

  fetch(endpoint + '?' + params.toString(), {
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': csrfToken,
      'Accept': 'application/json',
    }
  })
    .then(r => {
      // 401 = session expired → redirect to login
      if (r.status === 401) { window.location.href = '<?php echo e(route("login")); ?>'; return null; }
      // 419 = CSRF expired → reload page to get fresh token
      if (r.status === 419) { window.location.reload(); return null; }
      return r.json();
    })
    .then(res => {
      if (!res) return;
      if (loadingEl) loadingEl.classList.add('hidden');
      if (!res.success || !res.data || !res.data.trips || res.data.trips.length === 0) {
        renderEmptyReturnTrips();
        return;
      }
      renderReturnTrips(res.data.trips);
    })
    .catch(() => {
      if (loadingEl) loadingEl.classList.add('hidden');
      renderEmptyReturnTrips();
    })
    .finally(() => { isLoadingReturnTrips = false; });
}

// ─── Render return trips from server data ───
function renderReturnTrips(trips) {
  const listEl = document.getElementById('return-trips-list');
  if (!listEl) return;

  const currencySymbol = document.querySelector('.trip-currency-symbol')?.textContent || '';
  listEl.innerHTML = trips.map((t, i) => `
    <label class="return-trip-option flex items-center gap-4 bg-[#f9f9fc] rounded-xl p-4 border-2 border-transparent cursor-pointer transition-all hover:border-gray-200">
      <input type="radio" name="_return_trip" value="${t.uuid}"
             data-price-adult="${t.price_adult}"
             data-price-child="${t.price_child}"
             data-vehicle-type="${t.vehicle_type || ''}"
             data-provider="${t.provider_name || ''}"
             data-same-provider="${t.is_same_provider ? '1' : '0'}"
             data-departure-time="${t.departure_time}"
             data-departure-date="${t.departure_date}"
             data-seats="${t.available_seats}"
             data-recurring="${t.is_recurring ? '1' : '0'}"
             class="return-trip-radio hidden"
             ${i === 0 ? 'checked' : ''}>
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 text-sm font-bold text-gray-800">
          <span>${t.departure_time}</span>
          <i data-lucide="arrow-left" class="w-3 h-3 text-[#e7bd54] flex-shrink-0"></i>
          <span>${t.arrival_time}</span>
          ${t.discount > 0 ? '<span class="px-1.5 py-0.5 bg-red-50 text-red-500 text-[9px] font-bold rounded">-' + Math.round(t.discount) + '%</span>' : ''}
          ${t.is_same_provider ? '<span class="px-1.5 py-0.5 bg-[#1f442e]/10 text-[#1f442e] text-[9px] font-bold rounded">نفس المزود</span>' : ''}
        </div>
        <div class="text-[10px] text-gray-400 font-semibold mt-0.5 flex items-center gap-1.5">
          ${t.departure_date}
          ${t.vehicle_type ? ' · ' + t.vehicle_type : ''}
          ${t.provider_name ? ' · ' + t.provider_name : ''}
          ${t.is_recurring ? '<span class="px-1 py-0.5 bg-amber-50 text-amber-600 text-[8px] font-bold rounded">متكررة</span>' : ''}
        </div>
      </div>
      <div class="text-left flex-shrink-0">
          <div class="text-base font-black text-gray-800">${new Intl.NumberFormat('en-US', {maximumFractionDigits: 0}).format(t.price_adult)}</div>
          <div class="text-[9px] text-gray-400">${currencySymbol}/بالغ</div>
      </div>
      <div class="w-5 h-5 rounded-full border-2 border-gray-300 flex items-center justify-center flex-shrink-0 transition-colors return-trip-circle">
        <div class="w-2.5 h-2.5 rounded-full bg-[#e7bd54] scale-0 transition-transform return-trip-dot"></div>
      </div>
    </label>
  `).join('');

  // Re-init radio listeners and select first
  initReturnTripSelector();
  if (typeof lucide !== 'undefined') lucide.createIcons();
}

function renderEmptyReturnTrips() {
  const listEl = document.getElementById('return-trips-list');
  if (!listEl) return;
  listEl.innerHTML = '\n    <div class="flex flex-col items-center gap-3 py-8 text-center">\n      <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center">\n        <i data-lucide="calendar-x" class="w-7 h-7 text-gray-300"></i>\n      </div>\n      <div class="text-sm text-gray-500 font-bold">لا توجد رحلات عودة في هذا التاريخ</div>\n      <div class="text-xs text-gray-400">جرب تاريخ آخر</div>\n    </div>';
  if (typeof lucide !== 'undefined') lucide.createIcons();
}

// ─── Radio selector: pure UI, no validation ───
function initReturnTripSelector() {
  if (!IS_ROUND_TRIP) return;
  const radios = document.querySelectorAll('.return-trip-radio');
  radios.forEach(radio => {
    radio.addEventListener('change', function() {
      // All prices come from server data attributes. JS never calculates validity.
      returnPriceAdult = parseFloat(this.dataset.priceAdult) || 0;
      returnPriceChild = parseFloat(this.dataset.priceChild) || 0;
      selectedReturnUuid = this.value;

      const hiddenInput = document.getElementById('return-trip-uuid');
      if (hiddenInput) hiddenInput.value = this.value;

      // Visual state only
      document.querySelectorAll('.return-trip-option').forEach(opt => {
        const r = opt.querySelector('.return-trip-radio');
        const circle = opt.querySelector('.return-trip-circle');
        const dot = opt.querySelector('.return-trip-dot');
        if (r && r.checked) {
          opt.classList.add('border-[#e7bd54]', 'bg-[#e7bd54]/5');
          opt.classList.remove('border-transparent');
          if (circle) circle.classList.add('border-[#e7bd54]');
          if (dot) dot.classList.replace('scale-0', 'scale-100');
        } else {
          opt.classList.remove('border-[#e7bd54]', 'bg-[#e7bd54]/5');
          opt.classList.add('border-transparent');
          if (circle) circle.classList.remove('border-[#e7bd54]');
          if (dot) { dot.classList.remove('scale-100'); dot.classList.add('scale-0'); }
        }
      });

      // Update sidebar meta
      const metaDate = document.getElementById('return-meta-date');
      const metaTime = document.getElementById('return-meta-time');
      const metaVehicle = document.getElementById('return-meta-vehicle');
      if (metaDate) metaDate.textContent = this.dataset.departureDate;
      if (metaTime) metaTime.textContent = this.dataset.departureTime;
      if (metaVehicle) metaVehicle.textContent = this.dataset.vehicleType || '—';

      recalculate();
    });
    if (radio.checked) radio.dispatchEvent(new Event('change'));
  });
}

// ─── Price display: display only, server recalculates on POST ───
const _origRecalculate = window.recalculate;
window.recalculate = function() {
  if (_origRecalculate) _origRecalculate();
  if (!IS_ROUND_TRIP) return;

  const adults = parseInt(document.getElementById('adults-count')?.value || 1);
  const children = parseInt(document.getElementById('children-count')?.value || 0);
  const curr = getCurrency(currentCurrencyId);
  if (!curr) return;
  const rate = curr.exchange_rate || curr.rate_to_base || 1;
  const decimals = 0;
  const fmt = (n) => new Intl.NumberFormat('en-US', {maximumFractionDigits: decimals}).format(n);

  const hasReturnTrip = selectedReturnUuid !== '';
  const returnAdultsEl = document.getElementById('return-adults-total');
  const returnChildrenEl = document.getElementById('return-children-total');
  const returnChildrenLine = document.getElementById('return-children-line');
  const returnPriceLine = document.getElementById('return-price-line');
  const returnAdultsLabel = document.getElementById('return-adults-label');
  const returnChildrenLabel = document.getElementById('return-children-label');

  if (hasReturnTrip) {
    // Return trip: prices from server data only
    const returnAdultsTotal = adults * returnPriceAdult * rate;
    const returnChildrenTotal = children * returnPriceChild * rate;

    if (returnPriceLine) returnPriceLine.classList.remove('hidden');
    if (returnAdultsEl) returnAdultsEl.textContent = fmt(returnAdultsTotal);
    if (returnAdultsLabel) returnAdultsLabel.textContent = adults;
    if (children > 0 && returnChildrenLine) {
      returnChildrenLine.classList.remove('hidden');
      if (returnChildrenEl) returnChildrenEl.textContent = fmt(returnChildrenTotal);
      if (returnChildrenLabel) returnChildrenLabel.textContent = children;
    } else if (returnChildrenLine) {
      returnChildrenLine.classList.add('hidden');
    }

    // Grand total = outbound + return
    const outboundAdults = adults * OUTBOUND_PRICE_ADULT * rate;
    const outboundChildren = children * OUTBOUND_PRICE_CHILD * rate;
    const total = outboundAdults + outboundChildren + returnAdultsTotal + returnChildrenTotal;

    const grandTotalEl = document.getElementById('grand-total');
    const subtotalEl = document.getElementById('subtotal');
    if (grandTotalEl) grandTotalEl.textContent = fmt(total);
    if (subtotalEl) subtotalEl.textContent = fmt(outboundAdults + outboundChildren);

    const mobileTotal = document.getElementById('mobile-grand-total');
    if (mobileTotal) mobileTotal.textContent = fmt(total);

    const totalTickets = adults + children;
    const totalLabel = document.getElementById('passengers-total-label');
    if (totalLabel) totalLabel.textContent = 'إجمالي ' + (totalTickets * 2) + ' تذكرة (' + totalTickets + ' ذهاب + ' + totalTickets + ' عودة)';
    const mobileLabel = document.getElementById('mobile-passengers-label');
    if (mobileLabel) mobileLabel.textContent = (totalTickets * 2) + ' تذكرة (ذهاب وعودة)';
  } else {
    // No return trip selected — show outbound only
    if (returnPriceLine) returnPriceLine.classList.add('hidden');
    if (returnChildrenLine) returnChildrenLine.classList.add('hidden');
    // Reset sidebar meta to default
    const metaDate = document.getElementById('return-meta-date');
    const metaTime = document.getElementById('return-meta-time');
    const metaVehicle = document.getElementById('return-meta-vehicle');
    if (metaDate) metaDate.textContent = '—';
    if (metaTime) metaTime.textContent = '—';
    if (metaVehicle) metaVehicle.textContent = '—';
  }
};

// ─── Submit guard: ensure return trip is selected ───
const form = document.getElementById('booking-form');
if (form && IS_ROUND_TRIP) {
  form.addEventListener('submit', function(e) {
    const uuid = document.getElementById('return-trip-uuid')?.value;
    if (!uuid) {
      e.preventDefault();
      e.stopImmediatePropagation();
      resetSubmitButtons();
      const err = document.getElementById('return-trip-empty-error');
      if (err) err.classList.remove('hidden');
      err?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      showToast('يرجى اختيار رحلة العودة', 'warning');
      return;
    }
  });
}

// ─── Init ──────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function() {
  handleMobileBar();
  window.addEventListener('resize', handleMobileBar);
  updatePassengerForms();
  initReturnDatePicker();
  initReturnTripSelector();
  recalculate();
  if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.web', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/home/booking.blade.php ENDPATH**/ ?>