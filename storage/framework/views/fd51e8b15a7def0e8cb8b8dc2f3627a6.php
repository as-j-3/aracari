<?php $__env->startSection('title', 'نتائج البحث | أراكاري'); ?>

<?php $__env->startSection('content'); ?>


<section class="relative pt-28 pb-10 lg:pt-32 lg:pb-14 overflow-hidden">
  <div class="absolute inset-0 bg-gradient-to-b from-[#0c1d14] via-[#163022] to-[#1a3a28]"></div>
  <div class="absolute inset-0 dot-pattern opacity-30"></div>
  <div class="absolute top-10 right-10 w-64 h-64 bg-[#e7bd54]/5 rounded-full blur-3xl"></div>
  <div class="absolute bottom-0 left-20 w-80 h-80 bg-[#316b44]/10 rounded-full blur-3xl"></div>

  <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <div class="text-center mb-6 animate-fade-in-up">
      <a href="<?php echo e(route('home')); ?>" class="inline-flex items-center gap-1.5 text-sm text-white/40 hover:text-[#e7bd54] transition-colors mb-4">
        <i data-lucide="arrow-right" class="w-4 h-4"></i>
        <span>الرئيسية</span>
      </a>
      <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-white mb-2">
        من <span class="text-gold-gradient"><?php echo e($filters['from_city_name'] ?? 'جميع المدن'); ?></span>
        إلى <span class="text-gold-gradient"><?php echo e($filters['to_city_name'] ?? 'جميع المدن'); ?></span>
      </h1>
      <div class="inline-flex items-center gap-3 text-white/60 text-sm">
        <span class="flex items-center gap-1.5"><i data-lucide="bus" class="w-4 h-4 text-[#e7bd54]/70"></i><span class="text-[#e7bd54] font-bold"><?php echo e(number_format((int)$trips->total(), 0)); ?></span> رحلة</span>
        <?php if(!empty($filters['departure_date'])): ?>
          <span class="text-white/20">|</span>
          <span class="flex items-center gap-1.5"><i data-lucide="calendar" class="w-4 h-4 text-[#e7bd54]/70"></i><?php echo e($filters['departure_date']); ?></span>
        <?php endif; ?>
      </div>
    </div>

    
    <form action="<?php echo e(route('home.search')); ?>" method="GET" class="max-w-4xl mx-auto animate-fade-in-up animate-delay-200">
      <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-4 sm:p-5">
        <div class="grid sm:grid-cols-3 gap-3 sm:gap-4">
          <div>
            <label class="block text-white/40 text-xs font-medium mb-1.5"><i data-lucide="map-pin" class="w-3 h-3 inline-block ml-1 align-middle text-[#e7bd54]/60"></i>من مدينة</label>
            <select name="from_city" class="w-full bg-white/10 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#e7bd54]/50 appearance-none cursor-pointer transition-all">
              <option value="" class="bg-[#0c1d14] text-white">اختر مدينة الانطلاق</option>
              <?php $__currentLoopData = $cities ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($city->uuid); ?>" class="bg-[#0c1d14] text-white" <?php echo e(($filters['from_city'] ?? '') == $city->uuid ? 'selected' : ''); ?>><?php echo e($city->name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </div>
          <div>
            <label class="block text-white/40 text-xs font-medium mb-1.5"><i data-lucide="map-pin" class="w-3 h-3 inline-block ml-1 align-middle text-[#e7bd54]/60"></i>إلى مدينة</label>
            <select name="to_city" class="w-full bg-white/10 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#e7bd54]/50 appearance-none cursor-pointer transition-all">
              <option value="" class="bg-[#0c1d14] text-white">اختر مدينة الوصول</option>
              <?php $__currentLoopData = $cities ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($city->uuid); ?>" class="bg-[#0c1d14] text-white" <?php echo e(($filters['to_city'] ?? '') == $city->uuid ? 'selected' : ''); ?>><?php echo e($city->name); ?></option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </div>
          <div class="flex gap-3">
            <div class="flex-1">
              <label class="block text-white/40 text-xs font-medium mb-1.5"><i data-lucide="calendar" class="w-3 h-3 inline-block ml-1 align-middle text-[#e7bd54]/60"></i>تاريخ السفر</label>
              <input type="date" name="departure_date" value="<?php echo e($filters['departure_date'] ?? ''); ?>" class="w-full bg-white/10 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#e7bd54]/50 cursor-pointer transition-all">
            </div>
            <div class="flex items-end">
              <button type="submit" class="btn-gold px-5 py-2.5 rounded-xl text-sm flex items-center gap-2 shrink-0">
                <i data-lucide="search" class="w-4 h-4"></i>
                <span class="hidden sm:inline">بحث</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</section>


<section class="relative py-8 lg:py-12">
  <div class="absolute inset-0 bg-[#f9f5ec]"></div>
  <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 lg:gap-8">

      
      <aside class="lg:col-span-1">
        <div class="lg:sticky lg:top-24">
          <button id="filterToggle" class="lg:hidden w-full flex items-center justify-center gap-2 bg-white border border-[#1f442e]/10 rounded-xl px-4 py-3 text-sm font-bold text-[#1f442e] hover:bg-[#1f442e]/5 transition-colors mb-4 shadow-sm">
            <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
            <span>تصفية النتائج</span>
            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-300" id="filterToggleIcon"></i>
          </button>

          <div id="filterPanel" class="hidden lg:block">
            <form action="<?php echo e(route('home.search')); ?>" method="GET" id="filterForm">
              <?php if(!empty($filters['from_city'])): ?>  <input type="hidden" name="from_city" value="<?php echo e($filters['from_city']); ?>"> <?php endif; ?>
              <?php if(!empty($filters['to_city'])): ?>    <input type="hidden" name="to_city" value="<?php echo e($filters['to_city']); ?>"> <?php endif; ?>
              <?php if(!empty($filters['departure_date'])): ?> <input type="hidden" name="departure_date" value="<?php echo e($filters['departure_date']); ?>"> <?php endif; ?>

              <div class="bg-white rounded-2xl border border-[#1f442e]/8 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-[#1f442e]/8 bg-gradient-to-l from-[#1f442e]/5 to-transparent">
                  <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-[#1f442e]/10 flex items-center justify-center"><i data-lucide="filter" class="w-4 h-4 text-[#1f442e]"></i></div>
                    <h3 class="font-bold text-[#1f442e] text-sm">تصفية النتائج</h3>
                  </div>
                </div>

                
                <div class="px-5 py-4 border-b border-[#1f442e]/6">
                  <h4 class="text-xs font-bold text-[#1f442e]/60 uppercase tracking-wider mb-3">ترتيب حسب</h4>
                  <div class="space-y-2">
                    <?php $currentSort = request('sort', ''); ?>
                    <?php $__currentLoopData = ['departure_asc' => ['وقت الانطلاق (الأقرب)', 'arrow-up-narrow-wide'], 'departure_desc' => ['وقت الانطلاق (الأبعد)', 'arrow-down-narrow-wide'], 'price_asc' => ['السعر (الأقل)', 'trending-up'], 'price_desc' => ['السعر (الأعلى)', 'trending-down']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label class="flex items-center gap-2.5 cursor-pointer group">
                      <input type="radio" name="sort" value="<?php echo e($val); ?>" <?php echo e($currentSort === $val ? 'checked' : ''); ?> class="w-3.5 h-3.5 text-[#1f442e] border-[#1f442e]/30 focus:ring-[#1f442e]/20 cursor-pointer">
                      <span class="text-sm text-[#1f442e]/70 group-hover:text-[#1f442e] transition-colors flex items-center gap-1.5">
                        <i data-lucide="<?php echo e($label[1]); ?>" class="w-3.5 h-3.5"></i><?php echo e($label[0]); ?>

                      </span>
                    </label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                  </div>
                </div>

                
                <div class="px-5 py-4 border-b border-[#1f442e]/6">
                  <h4 class="text-xs font-bold text-[#1f442e]/60 uppercase tracking-wider mb-3">نطاق السعر</h4>
                  <div class="flex items-center gap-2">
                    <input type="number" name="min_price" placeholder="من" min="0" value="<?php echo e(request('min_price')); ?>" class="flex-1 bg-[#f9f5ec] border border-[#1f442e]/10 rounded-lg px-3 py-2 text-sm text-[#1f442e] placeholder-[#1f442e]/30 focus:outline-none focus:ring-2 focus:ring-[#e7bd54]/40 transition-all">
                    <span class="text-[#1f442e]/30 text-sm">—</span>
                    <input type="number" name="max_price" placeholder="إلى" min="0" value="<?php echo e(request('max_price')); ?>" class="flex-1 bg-[#f9f5ec] border border-[#1f442e]/10 rounded-lg px-3 py-2 text-sm text-[#1f442e] placeholder-[#1f442e]/30 focus:outline-none focus:ring-2 focus:ring-[#e7bd54]/40 transition-all">
                  </div>
                </div>

                
                <?php if($providers && $providers->count() > 0): ?>
                <div class="px-5 py-4 border-b border-[#1f442e]/6">
                  <h4 class="text-xs font-bold text-[#1f442e]/60 uppercase tracking-wider mb-3">مزود الخدمة</h4>
                  <div class="space-y-2 max-h-40 overflow-y-auto">
                    <?php $selectedProvider = (int) request('provider', 0); ?>
                    <label class="flex items-center gap-2.5 cursor-pointer group">
                      <input type="radio" name="provider" value="" <?php echo e($selectedProvider === 0 ? 'checked' : ''); ?> class="w-3.5 h-3.5 text-[#1f442e] border-[#1f442e]/30 cursor-pointer">
                      <span class="text-sm text-[#1f442e]/70 group-hover:text-[#1f442e]">الكل</span>
                    </label>
                    <?php $__currentLoopData = $providers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prov): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label class="flex items-center gap-2.5 cursor-pointer group">
                      <input type="radio" name="provider" value="<?php echo e($prov->id); ?>" <?php echo e($selectedProvider === $prov->id ? 'checked' : ''); ?> class="w-3.5 h-3.5 text-[#1f442e] border-[#1f442e]/30 cursor-pointer">
                      <span class="text-sm text-[#1f442e]/70 group-hover:text-[#1f442e] truncate"><?php echo e($prov->providerService ? $prov->providerService->name : $prov->name); ?></span>
                    </label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                  </div>
                </div>
                <?php endif; ?>

                
                <?php if($vehicleTypes && $vehicleTypes->count() > 0): ?>
                <div class="px-5 py-4 border-b border-[#1f442e]/6">
                  <h4 class="text-xs font-bold text-[#1f442e]/60 uppercase tracking-wider mb-3">نوع المركبة</h4>
                  <div class="space-y-2">
                    <?php $selectedTypes = request('vehicle_types', []); if (!is_array($selectedTypes)) $selectedTypes = [$selectedTypes]; ?>
                    <?php $__currentLoopData = $vehicleTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label class="flex items-center gap-2.5 cursor-pointer group">
                      <input type="checkbox" name="vehicle_types[]" value="<?php echo e($vt->id); ?>" <?php echo e(in_array($vt->id, $selectedTypes) ? 'checked' : ''); ?> class="w-3.5 h-3.5 rounded text-[#1f442e] border-[#1f442e]/30 cursor-pointer">
                      <span class="text-sm text-[#1f442e]/70 group-hover:text-[#1f442e]"><?php echo e($vt->type_name); ?></span>
                    </label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                  </div>
                </div>
                <?php endif; ?>

                
                <div class="px-5 py-4 space-y-2.5">
                  <button type="submit" class="w-full btn-gold py-3 rounded-xl text-sm font-bold flex items-center justify-center gap-2">
                    <i data-lucide="search" class="w-4 h-4">بحث</i>
                  </button>
                  <a href="<?php echo e(route('home.search')); ?>" class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-medium text-[#1f442e]/50 hover:text-[#1f442e] hover:bg-[#1f442e]/5 transition-all">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>إعادة تعيين
                  </a>
                </div>
              </div>
            </form>
          </div>
        </div>
      </aside>

      
      <main class="lg:col-span-3">
        <div class="flex items-center justify-between mb-5">
          <p class="text-sm text-[#1f442e]/50">عرض <span class="font-bold text-[#1f442e]"><?php echo e(number_format((int)$trips->count(), 0)); ?></span> من <span class="font-bold text-[#1f442e]"><?php echo e(number_format((int)$trips->total(), 0)); ?></span> رحلة</p>
          <div class="lg:hidden">
            <select onchange="window.location.href=this.value" class="bg-white border border-[#1f442e]/10 rounded-lg px-3 py-2 text-sm text-[#1f442e] focus:outline-none focus:ring-2 focus:ring-[#e7bd54]/40 appearance-none cursor-pointer">
              <option value="<?php echo e(Request::fullUrlWithQuery(['sort' => 'departure_asc'])); ?>" <?php echo e(request('sort') === 'departure_asc' ? 'selected' : ''); ?>>وقت الانطلاق</option>
              <option value="<?php echo e(Request::fullUrlWithQuery(['sort' => 'price_asc'])); ?>" <?php echo e(request('sort') === 'price_asc' ? 'selected' : ''); ?>>السعر الأقل</option>
              <option value="<?php echo e(Request::fullUrlWithQuery(['sort' => 'price_desc'])); ?>" <?php echo e(request('sort') === 'price_desc' ? 'selected' : ''); ?>>السعر الأعلى</option>
            </select>
          </div>
        </div>

        <?php if($trips->count() > 0): ?>
        <div class="space-y-4">
          <?php $__currentLoopData = $trips; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $trip): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php
            $hasDiscount = $trip->discount_percentage > 0;
            $displayPrice = $hasDiscount ? ($trip->price_for_adult * (1 - $trip->discount_percentage / 100)) : $trip->price_for_adult;
            $durMin = $trip->arrival_time->diffInMinutes($trip->departure_time);
            $durH = intdiv($durMin, 60); $durM = $durMin % 60;
            $durText = $durH > 0 ? ($durH . 'س' . ($durM > 0 ? ' ' . $durM . 'د' : '')) : ($durM . 'د');
            $totalSeats = $trip->total_seats ?? ($trip->vehicle ? $trip->vehicle->seats_count : 0);
            $avail = $trip->available_seats;
            $seatsLow = $totalSeats > 0 && $avail <= ($totalSeats * 0.2);
            $seatsCrit = $totalSeats > 0 && $avail <= 3;
            $level = $trip->levelTrip ? $trip->levelTrip->level_name : '';
            $vType = ($trip->vehicle && $trip->vehicle->vehicleType) ? $trip->vehicle->vehicleType->type_name : '';
            $provider = $trip->user ?? null;
            $pService = $provider?->providerService ?? null;
            $amenities = $trip->amenities ? $trip->amenities->take(6) : collect();
          ?>

          
          <div class="bg-white rounded-2xl border border-[#1f442e]/6 shadow-sm hover:shadow-lg hover:shadow-[#1f442e]/8 transition-all duration-300 overflow-hidden animate-fade-in-up" style="animation-delay:<?php echo e($index * 60); ?>ms">

            
            <div class="p-4 sm:p-5">
              <div class="flex items-start gap-4">

                
                <div class="hidden sm:flex flex-col items-center gap-2 w-16 shrink-0 pt-1">
                  <div class="w-12 h-12 rounded-xl bg-[#f9f5ec] border border-[#1f442e]/8 flex items-center justify-center overflow-hidden">
                    <?php if($pService && $pService->brand_photo): ?>
                      <img src="<?php echo e(Storage::url($pService->brand_photo)); ?>" alt="" class="w-full h-full object-cover">
                    <?php else: ?>
                      <i data-lucide="bus" class="w-5 h-5 text-[#1f442e]/40"></i>
                    <?php endif; ?>
                  </div>
                  <span class="text-[10px] text-[#1f442e]/40 text-center font-medium leading-tight line-clamp-2"><?php echo e($pService?->name ?? $provider?->name ?? ''); ?></span>
                </div>

                
                <div class="flex-1 min-w-0">
                  
                  <div class="flex items-center gap-2 mb-3 flex-wrap">
                    <?php if($hasDiscount): ?>
                      <span class="inline-flex items-center gap-1 bg-red-50 text-red-600 text-[10px] font-bold px-2 py-0.5 rounded-md"><i data-lucide="percent" class="w-2.5 h-2.5"></i>خصم <?php echo e(number_format($trip->discount_percentage, 0)); ?>%</span>
                    <?php endif; ?>
                    <?php if($level): ?>
                      <span class="inline-flex items-center gap-1 bg-[#e7bd54]/10 text-[#d49920] text-[10px] font-bold px-2 py-0.5 rounded-md"><i data-lucide="star" class="w-2.5 h-2.5"></i><?php echo e($level); ?></span>
                    <?php endif; ?>
                    <?php if($vType): ?>
                      <span class="inline-flex items-center gap-1 bg-[#1f442e]/5 text-[#1f442e]/50 text-[10px] font-medium px-2 py-0.5 rounded-md"><?php echo e($vType); ?></span>
                    <?php endif; ?>
                    
                    <span class="sm:hidden inline-flex items-center gap-1 text-[10px] text-[#1f442e]/40 font-medium">
                      <?php if($pService && $pService->brand_photo): ?>
                        <img src="<?php echo e(Storage::url($pService->brand_photo)); ?>" alt="" class="w-4 h-4 rounded object-cover">
                      <?php endif; ?>
                      <?php echo e($pService?->name ?? $provider?->name ?? ''); ?>

                    </span>
                  </div>

                  
                  <div class="flex items-center gap-3 sm:gap-4">
                    <div class="shrink-0 min-w-[60px] sm:min-w-[75px]">
                      <span class="text-lg sm:text-xl font-black text-[#1f442e] leading-none block" style="font-family:'Outfit',sans-serif"><?php echo e($trip->departure_time->format('h:i')); ?></span>
                      <span class="text-[10px] text-[#1f442e]/35 font-bold"><?php echo e($trip->departure_time->format('A')); ?></span>
                      <span class="text-xs text-[#1f442e]/60 font-bold mt-1 block"><?php echo e($trip->departureCity->name); ?></span>
                    </div>

                    <div class="flex flex-col items-center shrink-0">
                      <div class="w-2.5 h-2.5 rounded-full bg-[#1f442e] ring-3 ring-[#1f442e]/10"></div>
                      <div class="w-px h-5 bg-gradient-to-b from-[#1f442e] to-[#e7bd54]"></div>
                      <div class="w-7 h-7 rounded-lg bg-[#f9f5ec] border border-[#e7bd54]/30 flex items-center justify-center">
                        <i data-lucide="bus" class="w-3.5 h-3.5 text-[#1f442e]"></i>
                      </div>
                      <div class="w-px h-5 bg-gradient-to-b from-[#e7bd54] to-[#438656]"></div>
                      <div class="w-2.5 h-2.5 rounded-full bg-[#438656] ring-3 ring-[#438656]/10"></div>
                    </div>

                    <div class="shrink-0 min-w-[60px] sm:min-w-[75px]">
                      <span class="text-lg sm:text-xl font-black text-[#1f442e] leading-none block" style="font-family:'Outfit',sans-serif"><?php echo e($trip->arrival_time->format('h:i')); ?></span>
                      <span class="text-[10px] text-[#1f442e]/35 font-bold"><?php echo e($trip->arrival_time->format('A')); ?></span>
                      <span class="text-xs text-[#1f442e]/60 font-bold mt-1 block"><?php echo e($trip->destinationCity->name); ?></span>
                    </div>

                    <div class="hidden sm:flex items-center gap-3 shrink-0 mr-2">
                      <span class="inline-flex items-center gap-1 bg-[#1f442e]/5 text-[#1f442e]/50 text-[10px] font-medium px-2.5 py-1 rounded-full"><i data-lucide="clock" class="w-3 h-3"></i><?php echo e($durText); ?></span>
                      <span class="text-[10px] text-[#1f442e]/30 font-medium"><?php echo e($trip->departure_time->locale('ar')->translatedFormat('d M')); ?></span>
                    </div>
                  </div>

                  
                  <div class="sm:hidden flex items-center gap-3 mt-2.5 pt-2.5 border-t border-[#1f442e]/5">
                    <span class="flex items-center gap-1 text-[10px] text-[#1f442e]/40"><i data-lucide="calendar" class="w-3 h-3"></i><?php echo e($trip->departure_time->locale('ar')->translatedFormat('d F Y')); ?></span>
                    <span class="flex items-center gap-1 text-[10px] text-[#1f442e]/40"><i data-lucide="clock" class="w-3 h-3"></i><?php echo e($durText); ?></span>
                  </div>
                </div>

                
                <div class="flex flex-col items-center gap-1.5 shrink-0 text-center min-w-[90px] sm:min-w-[110px] pt-0.5">
                  <?php if($hasDiscount): ?>
                    <span class="text-[10px] text-[#1f442e]/30 line-through"><?php echo e(number_format((int)$trip->price_for_adult, 0)); ?></span>
                  <?php endif; ?>
                  <span class="text-xl sm:text-2xl font-black text-[#1f442e] leading-none" style="font-family:'Outfit',sans-serif"><?php echo e(number_format((int)$displayPrice, 0)); ?></span>
                  <span class="text-[9px] text-[#1f442e]/30 font-medium"><?php echo e($currencies[$baseCurrencyId ?? 0]->name ?? ''); ?></span>
                  <?php if($avail > 0): ?>
                    <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-md mt-0.5 <?php echo e($seatsCrit ? 'bg-red-50 text-red-600' : ($seatsLow ? 'bg-amber-50 text-amber-600' : 'bg-[#1f442e]/5 text-[#1f442e]/40')); ?>">
                      <i data-lucide="armchair" class="w-3 h-3"></i><?php echo e($avail); ?> مقعد
                    </span>
                  <?php else: ?>
                    <span class="text-[10px] font-bold text-gray-400 mt-0.5">مكتمل</span>
                  <?php endif; ?>
                </div>
              </div>

              
              <button onclick="this.closest('.rounded-2xl').querySelector('.card-expand').classList.toggle('hidden'); this.querySelector('.expand-icon').classList.toggle('rotate-180')" class="w-full flex items-center justify-center gap-1.5 mt-3 pt-3 border-t border-[#1f442e]/5 text-[11px] text-[#1f442e]/35 hover:text-[#1f442e]/60 font-medium transition-colors">
                <span>التفاصيل والخدمات</span>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5 expand-icon transition-transform duration-300"></i>
              </button>
            </div>

            
            <div class="card-expand hidden border-t border-[#1f442e]/5">
              <div class="p-4 sm:p-5 bg-[#f9f5ec]/50">

                
                <div class="grid sm:grid-cols-3 gap-4 mb-4">
                  <?php if($trip->vehicle): ?>
                  <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-lg bg-[#1f442e]/5 flex items-center justify-center shrink-0"><i data-lucide="truck" class="w-4 h-4 text-[#1f442e]/50"></i></div>
                    <div>
                      <p class="text-[10px] text-[#1f442e]/35 font-bold uppercase tracking-wider mb-0.5">المركبة</p>
                      <p class="text-xs font-bold text-[#1f442e]"><?php echo e($trip->vehicle->name ?? ''); ?></p>
                      <?php if($vType): ?><p class="text-[10px] text-[#1f442e]/40 mt-0.5"><?php echo e($vType); ?></p><?php endif; ?>
                    </div>
                  </div>
                  <?php endif; ?>
                  <?php if($trip->departureStation): ?>
                  <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-lg bg-[#1f442e]/5 flex items-center justify-center shrink-0"><i data-lucide="map-pin" class="w-4 h-4 text-[#1f442e]/50"></i></div>
                    <div>
                      <p class="text-[10px] text-[#1f442e]/35 font-bold uppercase tracking-wider mb-0.5">محطة الانطلاق</p>
                      <p class="text-xs font-bold text-[#1f442e]"><?php echo e($trip->departureStation->station_name); ?></p>
                    </div>
                  </div>
                  <?php endif; ?>
                  <?php if($trip->arrivalStation): ?>
                  <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-lg bg-[#1f442e]/5 flex items-center justify-center shrink-0"><i data-lucide="map-pin" class="w-4 h-4 text-[#1f442e]/50"></i></div>
                    <div>
                      <p class="text-[10px] text-[#1f442e]/35 font-bold uppercase tracking-wider mb-0.5">محطة الوصول</p>
                      <p class="text-xs font-bold text-[#1f442e]"><?php echo e($trip->arrivalStation->station_name); ?></p>
                    </div>
                  </div>
                  <?php endif; ?>
                </div>

                
                <?php if($amenities->count() > 0): ?>
                <div class="mb-4">
                  <p class="text-[10px] text-[#1f442e]/35 font-bold uppercase tracking-wider mb-2.5 flex items-center gap-1.5"><i data-lucide="sparkles" class="w-3 h-3"></i>الخدمات المتاحة</p>
                  <div class="flex flex-wrap gap-2">
                    <?php $__currentLoopData = $amenities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $amenity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <span class="inline-flex items-center gap-1.5 bg-white border border-[#1f442e]/8 text-[#1f442e]/60 text-[10px] font-medium px-2.5 py-1.5 rounded-lg">
                      <i data-lucide="check" class="w-3 h-3 text-[#438656]"></i>
                      <?php echo e($amenity->amenity_name ?? $amenity->name ?? ''); ?>

                    </span>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                  </div>
                </div>
                <?php endif; ?>

                
                <div class="flex flex-col sm:flex-row items-center gap-3 pt-3 border-t border-[#1f442e]/8">
                  <a href="<?php echo e(route('home.show', $trip->uuid)); ?>" class="w-full sm:w-auto btn-gold px-8 py-3 rounded-xl text-sm font-bold flex items-center justify-center gap-2">
                    <span>عرض التفاصيل والحجز</span>
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                  </a>
                  <span class="text-[10px] text-[#1f442e]/30"><?php echo e($trip->departure_time->locale('ar')->translatedFormat('l d F Y — h:i A')); ?></span>
                </div>

              </div>
            </div>
          </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        
        <div class="mt-8 flex items-center justify-center"><?php echo e($trips->appends(request()->query())->links()); ?></div>

        <?php else: ?>
        
        <div class="bg-white rounded-2xl border border-[#1f442e]/6 shadow-sm py-16 px-8 text-center">
          <div class="w-20 h-20 rounded-full bg-[#1f442e]/5 flex items-center justify-center mx-auto mb-6"><i data-lucide="search-x" class="w-9 h-9 text-[#1f442e]/30"></i></div>
          <h3 class="text-xl font-bold text-[#1f442e] mb-3">لا توجد رحلات متاحة</h3>
          <p class="text-sm text-[#1f442e]/50 max-w-md mx-auto mb-8 leading-relaxed">لم نتمكن من العثور على رحلات تطابق معايير البحث. جرب تغيير التاريخ أو المدن أو إزالة بعض الفلاتر.</p>
          <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="<?php echo e(route('home')); ?>" class="btn-gold px-6 py-3 rounded-xl text-sm font-bold flex items-center gap-2"><i data-lucide="home" class="w-4 h-4"></i>الرئيسية</a>
            <button onclick="document.getElementById('filterForm').reset(); document.getElementById('filterForm').submit();" class="px-6 py-3 rounded-xl text-sm font-medium border border-[#1f442e]/10 text-[#1f442e]/60 hover:text-[#1f442e] hover:bg-[#1f442e]/5 transition-all flex items-center gap-2"><i data-lucide="rotate-ccw" class="w-4 h-4"></i>إعادة تعيين</button>
          </div>
        </div>
        <?php endif; ?>
      </main>
    </div>
  </div>
</section>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
  .pagination { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: center; }
  .pagination .page-link, .pagination a, .pagination span { display: inline-flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; padding: 0 12px; border-radius: 12px; font-size: 0.875rem; font-weight: 600; transition: all 0.2s; border: 1px solid transparent; }
  .pagination a, .pagination .page-link:not(.disabled):not(.active) { color: #1f442e; background: white; border-color: rgba(31,68,46,0.08); }
  .pagination a:hover, .pagination .page-link:not(.disabled):not(.active):hover { background: #1f442e; color: white; border-color: #1f442e; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(31,68,46,0.15); }
  .pagination .active, .pagination .page-item.active .page-link { background: linear-gradient(135deg, #e7bd54, #d49920); color: #0c1d14; font-weight: 700; border-color: transparent; box-shadow: 0 4px 12px rgba(227,189,84,0.3); }
  .pagination .disabled, .pagination .page-item.disabled .page-link { color: rgba(31,68,46,0.25); background: rgba(31,68,46,0.03); cursor: not-allowed; }
  .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
lucide.createIcons();

const filterToggle = document.getElementById('filterToggle');
const filterPanel = document.getElementById('filterPanel');
const filterToggleIcon = document.getElementById('filterToggleIcon');
if (filterToggle && filterPanel) {
  filterToggle.addEventListener('click', () => {
    const h = filterPanel.classList.contains('hidden');
    filterPanel.classList.toggle('hidden');
    if (filterToggleIcon) filterToggleIcon.style.transform = h ? 'rotate(180deg)' : 'rotate(0deg)';
  });
}

document.querySelectorAll('#filterForm input[name="sort"]').forEach(r => r.addEventListener('change', () => document.getElementById('filterForm').submit()));
document.querySelectorAll('#filterForm input[name="provider"]').forEach(r => r.addEventListener('change', () => document.getElementById('filterForm').submit()));
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.web', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/home/search_results.blade.php ENDPATH**/ ?>