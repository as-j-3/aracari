@extends('layouts.web')

@section('title', 'نتائج البحث | أراكاري')

@section('content')

{{-- ================================================================== --}}
{{-- 1. SEARCH HEADER BANNER --}}
{{-- ================================================================== --}}
<section class="relative pt-28 pb-12 lg:pt-32 lg:pb-16 overflow-hidden">
  {{-- Background --}}
  <div class="absolute inset-0 bg-gradient-to-b from-[#0c1d14] via-[#163022] to-[#1a3a28]"></div>
  <div class="absolute inset-0 dot-pattern opacity-30"></div>
  <div class="absolute top-10 right-10 w-64 h-64 bg-[#e7bd54]/5 rounded-full blur-3xl"></div>
  <div class="absolute bottom-0 left-20 w-80 h-80 bg-[#316b44]/10 rounded-full blur-3xl"></div>

  <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    {{-- Search Summary --}}
    <div class="text-center mb-8 animate-fade-in-up">
      {{-- Breadcrumb / Back Link --}}
      <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-sm text-white/40 hover:text-[#e7bd54] transition-colors mb-4">
        <i data-lucide="arrow-right" class="w-4 h-4"></i>
        <span>الرئيسية</span>
      </a>

      <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-white mb-3">
        من
        <span class="text-gold-gradient">{{ $filters['from_city_name'] ?? 'جميع المدن' }}</span>
        إلى
        <span class="text-gold-gradient">{{ $filters['to_city_name'] ?? 'جميع المدن' }}</span>
      </h1>

      <div class="inline-flex items-center gap-3 text-white/60 text-sm">
        <div class="flex items-center gap-1.5">
          <i data-lucide="bus" class="w-4 h-4 text-[#e7bd54]/70"></i>
          <span><span class="text-[#e7bd54] font-bold">{{ number_format((int)$trips->total(), 0) }}</span> رحلة متاحة</span>
        </div>
        @if(!empty($filters['departure_date']))
          <span class="text-white/20">|</span>
          <div class="flex items-center gap-1.5">
            <i data-lucide="calendar" class="w-4 h-4 text-[#e7bd54]/70"></i>
            <span>{{ $filters['departure_date'] }}</span>
          </div>
        @endif
      </div>
    </div>

    {{-- Quick Re-search Form --}}
    <div class="max-w-4xl mx-auto animate-fade-in-up animate-delay-200">
      <form action="{{ route('home.search') }}" method="GET" class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-4 sm:p-6">
        <div class="grid sm:grid-cols-3 gap-3 sm:gap-4">
          {{-- From City --}}
          <div>
            <label class="block text-white/40 text-xs font-medium mb-1.5 text-right">
              <i data-lucide="map-pin" class="w-3 h-3 inline-block ml-1 align-middle text-[#e7bd54]/60"></i>
              من مدينة
            </label>
            <select name="from_city" class="w-full bg-white/10 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#e7bd54]/50 focus:border-[#e7bd54]/50 appearance-none cursor-pointer transition-all">
              <option value="" class="bg-[#0c1d14] text-white">اختر مدينة الانطلاق</option>
              @foreach($cities ?? [] as $city)
                <option value="{{ $city->uuid }}" class="bg-[#0c1d14] text-white" {{ (request('from_city') == $city->uuid || ($filters['from_city'] ?? '') == $city->uuid) ? 'selected' : '' }}>{{ $city->name }}</option>
              @endforeach
            </select>
          </div>

          {{-- To City --}}
          <div>
            <label class="block text-white/40 text-xs font-medium mb-1.5 text-right">
              <i data-lucide="map-pin" class="w-3 h-3 inline-block ml-1 align-middle text-[#e7bd54]/60"></i>
              إلى مدينة
            </label>
            <select name="to_city" class="w-full bg-white/10 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#e7bd54]/50 focus:border-[#e7bd54]/50 appearance-none cursor-pointer transition-all">
              <option value="" class="bg-[#0c1d14] text-white">اختر مدينة الوصول</option>
              @foreach($cities ?? [] as $city)
                <option value="{{ $city->uuid }}" class="bg-[#0c1d14] text-white" {{ (request('to_city') == $city->uuid || ($filters['to_city'] ?? '') == $city->uuid) ? 'selected' : '' }}>{{ $city->name }}</option>
              @endforeach
            </select>
          </div>

          {{-- Date + Search Button --}}
          <div class="flex gap-3">
            <div class="flex-1">
              <label class="block text-white/40 text-xs font-medium mb-1.5 text-right">
                <i data-lucide="calendar" class="w-3 h-3 inline-block ml-1 align-middle text-[#e7bd54]/60"></i>
                تاريخ السفر
              </label>
              <input type="date" name="departure_date" value="{{ request('departure_date') ?? ($filters['departure_date'] ?? '') }}" class="w-full bg-white/10 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#e7bd54]/50 focus:border-[#e7bd54]/50 cursor-pointer transition-all">
            </div>
            <div class="flex items-end">
              <button type="submit" class="btn-gold px-5 py-2.5 rounded-xl text-sm flex items-center gap-2 shrink-0">
                <i data-lucide="search" class="w-4 h-4"></i>
                <span class="hidden sm:inline">بحث</span>
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</section>

{{-- ================================================================== --}}
{{-- 2. MAIN CONTENT: FILTERS SIDEBAR + RESULTS --}}
{{-- ================================================================== --}}
<section class="relative py-10 lg:py-14">
  <div class="absolute inset-0 bg-[#f9f5ec]"></div>

  <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 lg:gap-8">

      {{-- ============================================================ --}}
      {{-- LEFT: FILTERS SIDEBAR --}}
      {{-- ============================================================ --}}
      <aside class="lg:col-span-1">
        <div class="lg:sticky lg:top-24">
          {{-- Mobile Filter Toggle --}}
          <button id="filterToggle" class="lg:hidden w-full flex items-center justify-center gap-2 bg-white border border-[#1f442e]/10 rounded-xl px-4 py-3 text-sm font-bold text-[#1f442e] hover:bg-[#1f442e]/5 transition-colors mb-4 shadow-sm">
            <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
            <span>تصفية النتائج</span>
            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-300" id="filterToggleIcon"></i>
          </button>

          <div id="filterPanel" class="hidden lg:block">
            <form action="{{ route('home.search') }}" method="GET" id="filterForm">
              {{-- Preserve existing search params --}}
              @if(!empty($filters['from_city']))
                <input type="hidden" name="from_city" value="{{ $filters['from_city'] }}">
              @endif
              @if(!empty($filters['to_city']))
                <input type="hidden" name="to_city" value="{{ $filters['to_city'] }}">
              @endif
              @if(!empty($filters['departure_date']))
                <input type="hidden" name="departure_date" value="{{ $filters['departure_date'] }}">
              @endif

              <div class="bg-white rounded-2xl border border-[#1f442e]/8 shadow-sm overflow-hidden">
                {{-- Filter Header --}}
                <div class="px-5 py-4 border-b border-[#1f442e]/8 bg-gradient-to-l from-[#1f442e]/5 to-transparent">
                  <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-[#1f442e]/10 flex items-center justify-center">
                      <i data-lucide="filter" class="w-4 h-4 text-[#1f442e]"></i>
                    </div>
                    <h3 class="font-bold text-[#1f442e] text-sm">تصفية النتائج</h3>
                  </div>
                </div>

                {{-- Sort Options --}}
                <div class="px-5 py-4 border-b border-[#1f442e]/6">
                  <h4 class="text-xs font-bold text-[#1f442e]/60 uppercase tracking-wider mb-3">ترتيب حسب</h4>
                  <div class="space-y-2">
                    @php
                      $currentSort = request('sort', '');
                    @endphp
                    <label class="flex items-center gap-2.5 cursor-pointer group">
                      <input type="radio" name="sort" value="departure_asc" {{ $currentSort === 'departure_asc' ? 'checked' : '' }} class="w-3.5 h-3.5 text-[#1f442e] border-[#1f442e]/30 focus:ring-[#1f442e]/20 cursor-pointer">
                      <span class="text-sm text-[#1f442e]/70 group-hover:text-[#1f442e] transition-colors flex items-center gap-1.5">
                        <i data-lucide="arrow-up-narrow-wide" class="w-3.5 h-3.5"></i>
                        وقت الانطلاق (الأقرب)
                      </span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer group">
                      <input type="radio" name="sort" value="departure_desc" {{ $currentSort === 'departure_desc' ? 'checked' : '' }} class="w-3.5 h-3.5 text-[#1f442e] border-[#1f442e]/30 focus:ring-[#1f442e]/20 cursor-pointer">
                      <span class="text-sm text-[#1f442e]/70 group-hover:text-[#1f442e] transition-colors flex items-center gap-1.5">
                        <i data-lucide="arrow-down-narrow-wide" class="w-3.5 h-3.5"></i>
                        وقت الانطلاق (الأبعد)
                      </span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer group">
                      <input type="radio" name="sort" value="price_asc" {{ $currentSort === 'price_asc' ? 'checked' : '' }} class="w-3.5 h-3.5 text-[#1f442e] border-[#1f442e]/30 focus:ring-[#1f442e]/20 cursor-pointer">
                      <span class="text-sm text-[#1f442e]/70 group-hover:text-[#1f442e] transition-colors flex items-center gap-1.5">
                        <i data-lucide="arrow-up-narrow-wide" class="w-3.5 h-3.5"></i>
                        السعر (الأقل)
                      </span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer group">
                      <input type="radio" name="sort" value="price_desc" {{ $currentSort === 'price_desc' ? 'checked' : '' }} class="w-3.5 h-3.5 text-[#1f442e] border-[#1f442e]/30 focus:ring-[#1f442e]/20 cursor-pointer">
                      <span class="text-sm text-[#1f442e]/70 group-hover:text-[#1f442e] transition-colors flex items-center gap-1.5">
                        <i data-lucide="arrow-down-narrow-wide" class="w-3.5 h-3.5"></i>
                        السعر (الأعلى)
                      </span>
                    </label>
                  </div>
                </div>

                {{-- Price Range --}}
                <div class="px-5 py-4 border-b border-[#1f442e]/6">
                  <h4 class="text-xs font-bold text-[#1f442e]/60 uppercase tracking-wider mb-3">نطاق السعر</h4>
                  <div class="flex items-center gap-2">
                    <div class="flex-1">
                      <input type="number" name="min_price" placeholder="من" min="0" value="{{ request('min_price') }}" class="w-full bg-[#f9f5ec] border border-[#1f442e]/10 rounded-lg px-3 py-2 text-sm text-[#1f442e] placeholder-[#1f442e]/30 focus:outline-none focus:ring-2 focus:ring-[#e7bd54]/40 focus:border-[#e7bd54]/40 transition-all">
                    </div>
                    <span class="text-[#1f442e]/30 text-sm">—</span>
                    <div class="flex-1">
                      <input type="number" name="max_price" placeholder="إلى" min="0" value="{{ request('max_price') }}" class="w-full bg-[#f9f5ec] border border-[#1f442e]/10 rounded-lg px-3 py-2 text-sm text-[#1f442e] placeholder-[#1f442e]/30 focus:outline-none focus:ring-2 focus:ring-[#e7bd54]/40 focus:border-[#e7bd54]/40 transition-all">
                    </div>
                  </div>
                </div>

                {{-- Provider Filter --}}
                
                @if($providers && $providers->count() > 0)
                <div class="px-5 py-4 border-b border-[#1f442e]/6">
                  <h4 class="text-xs font-bold text-[#1f442e]/60 uppercase tracking-wider mb-3">مزود الخدمة</h4>
                  <div class="space-y-2 max-h-40 overflow-y-auto">
                    @php $selectedProvider = (int) request('provider', 0); @endphp
                    <label class="flex items-center gap-2.5 cursor-pointer group">
                      <input type="radio" name="provider" value="" {{ $selectedProvider === 0 ? 'checked' : '' }} class="w-3.5 h-3.5 text-[#1f442e] border-[#1f442e]/30 focus:ring-[#1f442e]/20 cursor-pointer">
                      <span class="text-sm text-[#1f442e]/70 group-hover:text-[#1f442e] transition-colors">الكل</span>
                    </label>
                    @foreach($providers as $prov)
                      <label class="flex items-center gap-2.5 cursor-pointer group">
                        <input type="radio" name="provider" value="{{ $prov->id }}" {{ $selectedProvider === $prov->id ? 'checked' : '' }} class="w-3.5 h-3.5 text-[#1f442e] border-[#1f442e]/30 focus:ring-[#1f442e]/20 cursor-pointer">
                        <span class="text-sm text-[#1f442e]/70 group-hover:text-[#1f442e] transition-colors truncate">
                          {{ $prov->providerService ? $prov->providerService->name : $prov->name }}
                        </span>
                      </label>
                    @endforeach
                  </div>
                </div>
                @endif

                {{-- Vehicle Type Checkboxes --}}
                @if($vehicleTypes && $vehicleTypes->count() > 0)
                <div class="px-5 py-4 border-b border-[#1f442e]/6">
                  <h4 class="text-xs font-bold text-[#1f442e]/60 uppercase tracking-wider mb-3">نوع المركبة</h4>
                  <div class="space-y-2">
                    @php
                      $selectedTypes = request('vehicle_types', []);
                      if (!is_array($selectedTypes)) $selectedTypes = [$selectedTypes];
                    @endphp
                    @foreach($vehicleTypes as $vt)
                      <label class="flex items-center gap-2.5 cursor-pointer group">
                        <input type="checkbox" name="vehicle_types[]" value="{{ $vt->id }}" {{ in_array($vt->id, $selectedTypes) ? 'checked' : '' }} class="w-3.5 h-3.5 rounded text-[#1f442e] border-[#1f442e]/30 focus:ring-[#1f442e]/20 cursor-pointer">
                        <span class="text-sm text-[#1f442e]/70 group-hover:text-[#1f442e] transition-colors">{{ $vt->type_name }}</span>
                      </label>
                    @endforeach
                  </div>
                </div>
                @endif

                {{-- Action Buttons --}}
                <div class="px-5 py-4 space-y-2.5">
                  <button type="submit" class="w-full btn-gold py-3 rounded-xl text-sm font-bold flex items-center justify-center gap-2">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    بحث
                  </button>
                  <a href="{{ route('home.search') }}" class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-medium text-[#1f442e]/50 hover:text-[#1f442e] hover:bg-[#1f442e]/5 transition-all">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    إعادة تعيين الفلاتر
                  </a>
                </div>
              </div>
            </form>
          </div>
        </div>
      </aside>

      {{-- ============================================================ --}}
      {{-- RIGHT: RESULTS --}}
      {{-- ============================================================ --}}
      <main class="lg:col-span-3">
        {{-- Results Header --}}
        <div class="flex items-center justify-between mb-5">
          <p class="text-sm text-[#1f442e]/50">
            عرض
            <span class="font-bold text-[#1f442e]">{{ number_format((int)$trips->count(), 0) }}</span>
            من
            <span class="font-bold text-[#1f442e]">{{ number_format((int)$trips->total(), 0) }}</span>
            رحلة
          </p>
          {{-- Mobile Sort Dropdown --}}
          <div class="lg:hidden">
            <select onchange="window.location.href=this.value" class="bg-white border border-[#1f442e]/10 rounded-lg px-3 py-2 text-sm text-[#1f442e] focus:outline-none focus:ring-2 focus:ring-[#e7bd54]/40 appearance-none cursor-pointer">
              <option value="{{ Request::fullUrlWithQuery(['sort' => 'departure_asc']) }}" {{ request('sort') === 'departure_asc' ? 'selected' : '' }}>وقت الانطلاق</option>
              <option value="{{ Request::fullUrlWithQuery(['sort' => 'price_asc']) }}" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>السعر الأقل</option>
              <option value="{{ Request::fullUrlWithQuery(['sort' => 'price_desc']) }}" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>السعر الأعلى</option>
            </select>
          </div>
        </div>

        {{-- Results List --}}
        @if($trips->count() > 0)
          <div class="space-y-4">
            @foreach($trips as $index => $trip)
              @php
                $hasDiscount = $trip->discount_percentage > 0;
                $displayPrice = $hasDiscount
                  ? ($trip->price_for_adult * (1 - $trip->discount_percentage / 100))
                  : $trip->price_for_adult;

                $durationMinutes = $trip->arrival_time->diffInMinutes($trip->departure_time);
                $durationHours = intdiv($durationMinutes, 60);
                $durationMins = $durationMinutes % 60;
                $durationText = $durationHours > 0
                  ? ($durationHours . 'س' . ($durationMins > 0 ? ' ' . $durationMins . 'د' : ''))
                  : ($durationMins . ' دقيقة');

                $totalSeats = $trip->total_seats ?? ($trip->vehicle ? $trip->vehicle->seats_count : 0);
                $availableSeats = $trip->available_seats;
                $seatsLow = $totalSeats > 0 && $availableSeats <= ($totalSeats * 0.2);
                $seatsCritical = $totalSeats > 0 && $availableSeats <= 3;

                $levelName = $trip->levelTrip ? $trip->levelTrip->level_name : '';
                $vehicleTypeName = ($trip->vehicle && $trip->vehicle->vehicleType) ? $trip->vehicle->vehicleType->type_name : '';
              @endphp

              {{-- Trip Card --}}
              <a href="{{ route('home.show', $trip->uuid) }}" class="block group {{ $index % 2 === 0 ? 'bg-white' : 'bg-[#f9f5ec]' }} rounded-2xl border border-[#1f442e]/6 hover:border-[#e7bd54]/30 shadow-sm hover:shadow-lg hover:shadow-[#1f442e]/8 transition-all duration-300 hover:-translate-y-0.5 overflow-hidden">

                {{-- Top Badges Row --}}
                <div class="flex items-center gap-2 px-5 pt-4 pb-2 flex-wrap">
                  {{-- Discount Badge --}}
                  @if($hasDiscount)
                    <span class="inline-flex items-center gap-1 bg-red-50 text-red-600 text-xs font-bold px-2.5 py-1 rounded-lg">
                      <i data-lucide="percent" class="w-3 h-3"></i>
                      خصم {{ number_format($trip->discount_percentage, 0) }}%
                    </span>
                  @endif

                  {{-- Level Badge --}}
                  @if($levelName)
                    <span class="inline-flex items-center gap-1 bg-[#e7bd54]/10 text-[#d49920] text-xs font-bold px-2.5 py-1 rounded-lg">
                      <i data-lucide="star" class="w-3 h-3"></i>
                      {{ $levelName }}
                    </span>
                  @endif

                  {{-- Vehicle Type Badge --}}
                  @if($vehicleTypeName)
                    <span class="inline-flex items-center gap-1 bg-[#1f442e]/5 text-[#1f442e]/60 text-xs font-medium px-2.5 py-1 rounded-lg">
                      <i data-lucide="bus" class="w-3 h-3"></i>
                      {{ $vehicleTypeName }}
                    </span>
                  @endif
                </div>

                {{-- Main Content Row --}}
                <div class="px-5 pb-5">
                  <div class="flex items-start gap-4 sm:gap-6">

                    {{-- Route Timeline (center column) --}}
                    <div class="flex-1 min-w-0">
                      <div class="flex items-stretch gap-3 sm:gap-4">

                        {{-- Departure Info --}}
                        <div class="flex flex-col items-center sm:items-end shrink-0 min-w-[70px]">
                          <span class="text-lg sm:text-xl font-black text-[#1f442e] leading-none">{{ $trip->departure_time->format('h:i A') }}</span>
                          <span class="text-xs text-[#1f442e]/45 mt-1 font-medium">{{ $trip->departureCity->name }}</span>
                        </div>

                        {{-- Timeline Dots + Line --}}
                        <div class="flex flex-col items-center justify-center py-1 shrink-0">
                          <div class="w-3 h-3 rounded-full bg-[#1f442e] ring-4 ring-[#1f442e]/10 shrink-0"></div>
                          <div class="w-px flex-1 bg-gradient-to-b from-[#1f442e] via-[#1f442e]/30 to-[#e7bd54] my-1 min-h-[20px]"></div>
                          <div class="relative shrink-0">
                            <i data-lucide="bus" class="w-5 h-5 text-[#e7bd54]"></i>
                          </div>
                          <div class="w-px flex-1 bg-gradient-to-b from-[#e7bd54] via-[#e7bd54]/30 to-[#1f442e] my-1 min-h-[20px]"></div>
                          <div class="w-3 h-3 rounded-full bg-[#e7bd54] ring-4 ring-[#e7bd54]/10 shrink-0"></div>
                        </div>

                        {{-- Arrival Info --}}
                        <div class="flex flex-col items-center sm:items-start shrink-0 min-w-[70px]">
                          <span class="text-lg sm:text-xl font-black text-[#1f442e] leading-none">{{ $trip->arrival_time->format('h:i A') }}</span>
                          <span class="text-xs text-[#1f442e]/45 mt-1 font-medium">{{ $trip->destinationCity->name }}</span>
                        </div>

                        {{-- Duration Pill --}}
                        <div class="hidden sm:flex items-center self-center shrink-0">
                          <span class="inline-flex items-center gap-1.5 bg-[#1f442e]/5 text-[#1f442e]/50 text-xs font-medium px-3 py-1.5 rounded-full">
                            <i data-lucide="clock" class="w-3 h-3"></i>
                            {{ $durationText }}
                          </span>
                        </div>
                      </div>

                      {{-- Date Row (Mobile visible) --}}
                      <div class="flex items-center gap-4 mt-3 pt-3 border-t border-[#1f442e]/5">
                        <span class="flex items-center gap-1.5 text-xs text-[#1f442e]/45 font-medium">
                          <i data-lucide="calendar" class="w-3 h-3"></i>
                          {{ $trip->departure_time->locale('ar')->translatedFormat('d F Y') }}
                        </span>
                        <span class="sm:hidden flex items-center gap-1.5 text-xs text-[#1f442e]/45 font-medium">
                          <i data-lucide="clock" class="w-3 h-3"></i>
                          {{ $durationText }}
                        </span>
                      </div>
                    </div>

                    {{-- Price & Action (right side) --}}
                    <div class="flex flex-col items-center gap-2 shrink-0 text-center min-w-[100px] sm:min-w-[120px] pt-1">
                      {{-- Old Price (if discount) --}}
                      @if($hasDiscount)
                        <span class="text-xs text-[#1f442e]/35 line-through">{{ number_format((int)$trip->price_for_adult, 0) }}</span>
                      @endif

                      {{-- Current Price --}}
                      <div class="text-xl sm:text-2xl font-black text-[#1f442e] leading-none">
                        {{ number_format((int)$displayPrice, 0) }}
                      </div>
                      <span class="text-[10px] text-[#1f442e]/35 font-medium">{{ $currencies[$baseCurrencyId ?? 0]->name ?? 'ر.ي' }}</span>

                      {{-- Seats Badge --}}
                      @if($availableSeats > 0)
                        <span class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-lg mt-1 {{ $seatsCritical ? 'bg-red-50 text-red-600' : ($seatsLow ? 'bg-amber-50 text-amber-600' : 'bg-[#1f442e]/5 text-[#1f442e]/50') }}">
                          <i data-lucide="armchair" class="w-3 h-3"></i>
                          {{ $availableSeats }} مقعد
                        </span>
                      @else
                        <span class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-lg bg-gray-100 text-gray-400 mt-1">
                          <i data-lucide="armchair" class="w-3 h-3"></i>
                          مكتمل
                        </span>
                      @endif

                      {{-- CTA on hover --}}
                      <div class="hidden sm:block mt-1">
                        <span class="inline-flex items-center gap-1.5 btn-gold px-4 py-2 rounded-xl text-xs font-bold opacity-0 group-hover:opacity-100 transition-all duration-300 translate-y-2 group-hover:translate-y-0">
                          <span>عرض التفاصيل</span>
                          <i data-lucide="arrow-left" class="w-3 h-3"></i>
                        </span>
                      </div>
                    </div>

                  </div>
                </div>
              </a>
            @endforeach
          </div>

          {{-- Pagination --}}
          <div class="mt-10 flex items-center justify-center">
            {{ $trips->appends(request()->query())->links() }}
          </div>

        @else

          {{-- ================================================================== --}}
          {{-- EMPTY STATE --}}
          {{-- ================================================================== --}}
          <div class="bg-white rounded-2xl border border-[#1f442e]/6 shadow-sm py-16 px-8 text-center">
            <div class="w-20 h-20 rounded-full bg-[#1f442e]/5 flex items-center justify-center mx-auto mb-6">
              <i data-lucide="search-x" class="w-9 h-9 text-[#1f442e]/30"></i>
            </div>
            <h3 class="text-xl font-bold text-[#1f442e] mb-3">لا توجد رحلات متاحة</h3>
            <p class="text-sm text-[#1f442e]/50 max-w-md mx-auto mb-8 leading-relaxed">
              لم نتمكن من العثور على رحلات تطابق معايير البحث الخاصة بك.
              جرب تغيير تاريخ السفر أو المدن أو إزالة بعض الفلاتر.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
              <a href="{{ route('home') }}" class="btn-gold px-6 py-3 rounded-xl text-sm font-bold flex items-center gap-2">
                <i data-lucide="home" class="w-4 h-4"></i>
                العودة للرئيسية
              </a>
              <button onclick="document.getElementById('filterForm').reset(); document.getElementById('filterForm').submit();" class="px-6 py-3 rounded-xl text-sm font-medium border border-[#1f442e]/10 text-[#1f442e]/60 hover:text-[#1f442e] hover:bg-[#1f442e]/5 transition-all flex items-center gap-2">
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                إعادة تعيين البحث
              </button>
            </div>
          </div>
        @endif
      </main>

    </div>
  </div>
</section>

@endsection

{{-- ================================================================== --}}
{{-- PAGE-SPECIFIC STYLES --}}
{{-- ================================================================== --}}
@push('styles')
<style>
  {{-- Pagination styling --}}
  .pagination {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    justify-content: center;
  }
  .pagination .page-link,
  .pagination a,
  .pagination span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 12px;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 600;
    transition: all 0.2s;
    border: 1px solid transparent;
  }
  .pagination a,
  .pagination .page-link:not(.disabled):not(.active) {
    color: #1f442e;
    background: white;
    border-color: rgba(31, 68, 46, 0.08);
  }
  .pagination a:hover,
  .pagination .page-link:not(.disabled):not(.active):hover {
    background: #1f442e;
    color: white;
    border-color: #1f442e;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(31, 68, 46, 0.15);
  }
  .pagination .active,
  .pagination .page-item.active .page-link {
    background: linear-gradient(135deg, #e7bd54, #d49920);
    color: #0c1d14;
    font-weight: 700;
    border-color: transparent;
    box-shadow: 0 4px 12px rgba(227, 189, 84, 0.3);
  }
  .pagination .disabled,
  .pagination .page-item.disabled .page-link {
    color: rgba(31, 68, 46, 0.25);
    background: rgba(31, 68, 46, 0.03);
    cursor: not-allowed;
    border-color: transparent;
  }
</style>
@endpush

{{-- ================================================================== --}}
{{-- PAGE-SPECIFIC SCRIPTS --}}
{{-- ================================================================== --}}
@push('scripts')
<script>
  // Re-initialize lucide icons for dynamically rendered content
  lucide.createIcons();

  // Mobile filter toggle
  const filterToggle = document.getElementById('filterToggle');
  const filterPanel = document.getElementById('filterPanel');
  const filterToggleIcon = document.getElementById('filterToggleIcon');

  if (filterToggle && filterPanel) {
    filterToggle.addEventListener('click', () => {
      const isHidden = filterPanel.classList.contains('hidden');
      filterPanel.classList.toggle('hidden');
      if (filterToggleIcon) {
        filterToggleIcon.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
      }
    });
  }

  // Auto-submit filter form on sort radio change
  document.querySelectorAll('#filterForm input[name="sort"]').forEach(radio => {
    radio.addEventListener('change', () => {
      document.getElementById('filterForm').submit();
    });
  });

  // Auto-submit filter form on provider radio change
  document.querySelectorAll('#filterForm input[name="provider"]').forEach(radio => {
    radio.addEventListener('change', () => {
      document.getElementById('filterForm').submit();
    });
  });
</script>
@endpush
