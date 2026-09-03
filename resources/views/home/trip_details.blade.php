@extends('layouts.web')

@section('title', ($trip->trip_name ?? ($trip->departureCity->name . ' → ' . $trip->destinationCity->name)) . ' | أراكاري')

@section('content')

{{-- ================================================================== --}}
{{-- Blade Calculations --}}
{{-- ================================================================== --}}
@php
  // Discount
  $hasDiscount = $trip->discount_percentage > 0;
  $displayPriceAdult = $hasDiscount ? ($trip->price_for_adult * (1 - $trip->discount_percentage / 100)) : $trip->price_for_adult;
  $displayPriceChild = $hasDiscount ? ($trip->price_for_child * (1 - $trip->discount_percentage / 100)) : $trip->price_for_child;

  // Duration
  $durationMinutes = $trip->arrival_time->diffInMinutes($trip->departure_time);
  $durationHours = intdiv($durationMinutes, 60);
  $durationMins = $durationMinutes % 60;
  $durationText = $durationHours > 0 ? ($durationHours . ' ساعة' . ($durationMins > 0 ? ' و ' . $durationMins . ' دقيقة' : '')) : ($durationMins . ' دقيقة');

  // Seats
  $totalSeats = $trip->total_seats ?? $trip->vehicle->seats_count ?? 0;
  $availableSeats = $trip->available_seats;
  $seatsPercentage = $totalSeats > 0 ? round(($availableSeats / $totalSeats) * 100) : 0;

  // Vehicle
  $levelName = $trip->levelTrip ? $trip->levelTrip->level_name : '';
  $vehicleName = $trip->vehicle ? $trip->vehicle->name : '';
  $vehicleModel = $trip->vehicle ? $trip->vehicle->model : '';
  $vehicleTypeName = ($trip->vehicle && $trip->vehicle->vehicleType) ? $trip->vehicle->vehicleType->type_name : '';

  // Stations
  $departureStationName = $trip->departureStation ? $trip->departureStation->name : '';
  $arrivalStationName = $trip->arrivalStation ? $trip->arrivalStation->name : '';

  // Seat bar colour
  $seatBarColor = $seatsPercentage > 50 ? 'bg-[#e7bd54]' : ($seatsPercentage > 20 ? 'bg-amber-500' : 'bg-red-500');
@endphp

{{-- ================================================================== --}}
{{-- 1. BREADCRUMB --}}
{{-- ================================================================== --}}
<section class="relative bg-[#f9f5ec] pt-24 pb-4">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <nav class="flex items-center gap-2 text-xs font-semibold" aria-label="Breadcrumb">
      <a href="{{ route('home') }}" class="flex items-center gap-1.5 text-[#1f442e]/50 hover:text-[#d49920] transition-colors">
        <i data-lucide="home" class="w-3.5 h-3.5"></i>
        <span>الرئيسية</span>
      </a>
      <i data-lucide="chevron-left" class="w-3 h-3 text-[#1f442e]/20"></i>
      <a href="{{ route('home.search', ['from_city' => $trip->departureCity->uuid, 'to_city' => $trip->destinationCity->uuid]) }}" class="text-[#1f442e]/50 hover:text-[#d49920] transition-colors">
        نتائج البحث
      </a>
      <i data-lucide="chevron-left" class="w-3 h-3 text-[#1f442e]/20"></i>
      <span class="text-[#1f442e]/90">تفاصيل الرحلة</span>
    </nav>
  </div>
</section>

{{-- ================================================================== --}}
{{-- 2. HERO SECTION — dark gradient background --}}
{{-- ================================================================== --}}
<section class="relative overflow-hidden">
  {{-- Background layers --}}
  <div class="absolute inset-0 bg-gradient-to-br from-[#0c1d14] via-[#163022] to-[#1f442e]"></div>
  <div class="absolute inset-0 dot-pattern opacity-20 pointer-events-none"></div>
  <div class="absolute top-10 right-10 w-72 h-72 bg-[#e7bd54]/5 rounded-full blur-3xl"></div>
  <div class="absolute bottom-10 left-20 w-80 h-80 bg-[#316b44]/10 rounded-full blur-3xl"></div>

  <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-16">
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 lg:gap-12 items-center">

      {{-- 2-A. Hero Image / Vehicle Photo --}}
      <div class="lg:col-span-2 order-2 lg:order-1">
        <div class="relative group">
          @if($trip->image_url)
            <img src="{{ $trip->image_url }}" alt="{{ $trip->departureCity->name }} - {{ $trip->destinationCity->name }}"
                 class="w-full rounded-2xl shadow-2xl shadow-black/30 object-cover aspect-[4/3] group-hover:scale-[1.02] transition-transform duration-700" loading="eager">
          @elseif($trip->vehicle && $trip->vehicle->outside_photo)
            <img src="{{ $trip->vehicle->outside_photo }}" alt="{{ $vehicleName }}"
                 class="w-full rounded-2xl shadow-2xl shadow-black/30 object-cover aspect-[4/3] group-hover:scale-[1.02] transition-transform duration-700" loading="eager">
          @else
            <div class="w-full rounded-2xl aspect-[4/3] bg-gradient-to-br from-[#1f442e] to-[#0c1d14] border border-white/10 flex items-center justify-center shadow-2xl shadow-black/30 overflow-hidden relative">
              <div class="absolute inset-0 bg-gradient-to-br from-[#316b44]/20 to-transparent"></div>
              <div class="relative flex flex-col items-center gap-4">
                <div class="w-20 h-20 rounded-2xl bg-white/5 backdrop-blur border border-white/10 flex items-center justify-center">
                  <i data-lucide="bus" class="w-10 h-10 text-[#e7bd54]"></i>
                </div>
                <span class="text-white/30 text-sm font-bold">{{ $vehicleTypeName }}</span>
              </div>
            </div>
          @endif

          {{-- Discount badge --}}
          @if($hasDiscount)
            <div class="absolute top-4 right-4 z-10">
              <span class="px-4 py-2 bg-gradient-to-r from-[#e7bd54] to-[#d49920] text-[#0c1d14] text-sm font-black rounded-full shadow-lg shadow-[#e7bd54]/30 flex items-center gap-1.5">
                <i data-lucide="tag" class="w-4 h-4"></i>
                خصم {{ number_format($trip->discount_percentage, 0) }}%
              </span>
            </div>
          @endif
        </div>

        {{-- Inside vehicle photo --}}
        @if($trip->vehicle && $trip->vehicle->inside_photo)
          <div class="mt-4">
            <img src="{{ $trip->vehicle->inside_photo }}" alt="داخلية {{ $vehicleName }}"
                 class="w-full rounded-2xl shadow-lg object-cover aspect-[16/9] border border-white/10 cursor-pointer hover:scale-[1.02] transition-transform duration-500" loading="lazy">
          </div>
        @endif
      </div>

      {{-- 2-B. Hero Content --}}
      <div class="lg:col-span-3 order-1 lg:order-2 space-y-6 text-right">

        {{-- Badge row --}}
        <div class="flex flex-wrap items-center gap-3">
          @if($levelName)
            <span class="px-3 py-1.5 bg-[#e7bd54]/15 text-[#e7bd54] text-xs font-bold rounded-full flex items-center gap-1.5 border border-[#e7bd54]/20">
              <i data-lucide="award" class="w-3.5 h-3.5"></i>
              {{ $levelName }}
            </span>
          @endif
          @if($vehicleTypeName)
            <span class="glass px-3 py-1.5 text-white/80 text-xs font-bold rounded-full flex items-center gap-1.5">
              <i data-lucide="bus" class="w-3.5 h-3.5 text-[#e7bd54]/70"></i>
              {{ $vehicleTypeName }}
            </span>
          @endif
          <span class="glass px-3 py-1.5 text-white/70 text-xs font-bold rounded-full flex items-center gap-1.5">
            <i data-lucide="calendar" class="w-3.5 h-3.5 text-[#e7bd54]/70"></i>
            {{ $trip->departure_time->locale('ar')->translatedFormat('d F Y') }}
          </span>
        </div>

        {{-- Trip name --}}
        @if($trip->trip_name)
          <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-tight">
            {{ $trip->trip_name }}
          </h1>
        @endif

        {{-- Route display --}}
        <div class="flex items-center gap-4 sm:gap-8 py-4">
          {{-- Departure --}}
          <div class="flex-1">
            <div class="text-xl sm:text-3xl font-black text-white">{{ $trip->departureCity->name }}</div>
            <div class="text-xs text-[#e7bd54]/70 font-semibold mt-1">محطة الانطلاق</div>
          </div>

          {{-- Center connector --}}
          <div class="flex flex-col items-center gap-2 flex-shrink-0">
            <span class="text-[10px] text-white/30 font-semibold">{{ $durationText }}</span>
            <div class="w-24 sm:w-40 flex items-center gap-1.5">
              <div class="h-[2px] flex-1 bg-gradient-to-r from-[#1f442e] to-[#e7bd54] rounded-full"></div>
              <div class="w-8 h-8 sm:w-10 sm:h-10 bg-[#0c1d14] rounded-full border-2 border-[#e7bd54]/60 flex items-center justify-center shadow-lg shadow-black/20">
                <i data-lucide="bus" class="w-4 h-4 sm:w-5 sm:h-5 text-[#e7bd54]"></i>
              </div>
              <div class="h-[2px] flex-1 bg-gradient-to-l from-[#1f442e] to-[#e7bd54] rounded-full"></div>
            </div>
            <span class="text-[10px] text-white/30 font-semibold">رحلة مباشرة</span>
          </div>

          {{-- Destination --}}
          <div class="flex-1 text-left">
            <div class="text-xl sm:text-3xl font-black text-white">{{ $trip->destinationCity->name }}</div>
            <div class="text-xs text-[#e7bd54] font-semibold mt-1">محطة الوصول</div>
          </div>
        </div>

        {{-- Time & Seats cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="glass rounded-2xl p-4 text-center">
            <div class="flex items-center justify-center gap-2 text-[#e7bd54]/70 mb-1">
              <i data-lucide="clock" class="w-4 h-4"></i>
              <span class="text-[10px] font-bold tracking-wider">وقت المغادرة</span>
            </div>
            <div class="text-2xl font-black text-white">{{ $trip->departure_time->format('h:i A') }}</div>
          </div>
          <div class="glass rounded-2xl p-4 text-center">
            <div class="flex items-center justify-center gap-2 text-[#e7bd54]/70 mb-1">
              <i data-lucide="clock" class="w-4 h-4"></i>
              <span class="text-[10px] font-bold tracking-wider">وقت الوصول</span>
            </div>
            <div class="text-2xl font-black text-white">{{ $trip->arrival_time->format('h:i A') }}</div>
          </div>
          <div class="glass rounded-2xl p-4">
            <div class="flex items-center justify-center gap-2 text-[#e7bd54]/70 mb-2">
              <i data-lucide="armchair" class="w-4 h-4"></i>
              <span class="text-[10px] font-bold tracking-wider">المقاعد المتاحة</span>
            </div>
            <div class="flex items-center justify-center gap-2 mb-2">
              <span class="text-2xl font-black text-white">{{ $availableSeats }}</span>
              <span class="text-white/40 text-sm">/ {{ $totalSeats }}</span>
            </div>
            <div class="w-full h-2 bg-white/10 rounded-full overflow-hidden">
              <div class="h-full rounded-full transition-all duration-500 {{ $seatBarColor }}" style="width: {{ $seatsPercentage }}%"></div>
            </div>
          </div>
        </div>

        {{-- Price (desktop only) --}}
        <div class="hidden lg:block">
          <div class="inline-flex items-center gap-4 glass rounded-2xl px-6 py-4">
            {{-- Adult --}}
            <div class="text-right">
              <div class="text-white/40 text-xs font-semibold mb-1">سعر التذكرة للبالغ</div>
              <div class="flex items-baseline gap-2">
                @if($hasDiscount)
                  <span class="text-3xl font-black text-[#e7bd54] trip-price" data-base-price="{{ $displayPriceAdult }}">{{ number_format((int)$displayPriceAdult, 0) }}</span>
                  <span class="text-sm text-white/50 font-bold trip-currency-symbol">{{ $baseCurrency->symbol ?? '' }}</span>
                  <span class="text-sm text-white/30 line-through trip-price-original" data-base-price="{{ $trip->price_for_adult }}">{{ number_format((int)$trip->price_for_adult, 0) }}</span>
                @else
                  <span class="text-3xl font-black text-[#e7bd54] trip-price" data-base-price="{{ $trip->price_for_adult }}">{{ number_format((int)$trip->price_for_adult, 0) }}</span>
                  <span class="text-sm text-white/50 font-bold trip-currency-symbol">{{ $baseCurrency->symbol ?? '' }}</span>
                @endif
              </div>
            </div>
            <div class="w-px h-12 bg-white/10"></div>
            {{-- Child --}}
            <div class="text-right">
              <div class="text-white/40 text-xs font-semibold mb-1">سعر الأطفال</div>
              <div class="flex items-baseline gap-1">
                <span class="text-xl font-black text-white trip-price" data-base-price="{{ $displayPriceChild }}">{{ number_format((int)$displayPriceChild, 0) }}</span>
                <span class="text-xs text-white/50 font-bold trip-currency-symbol">{{ $baseCurrency->symbol ?? '' }}</span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>

{{-- ================================================================== --}}
{{-- 3. MAIN CONTENT — cream background --}}
{{-- ================================================================== --}}
<section class="relative bg-[#f9f5ec] py-10 md:py-14">
  <div class="absolute inset-0 dot-pattern opacity-40 pointer-events-none"></div>

  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8">

      {{-- ============================================================== --}}
      {{-- LEFT COLUMN (8/12): Trip Details Cards --}}
      {{-- ============================================================== --}}
      <div class="lg:col-span-8 space-y-6">

        {{-- ── Card: Route Details ── --}}
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-[#1f442e]/5">
          {{-- Card header --}}
          <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-[#1f442e] flex items-center justify-center">
              <i data-lucide="route" class="w-5 h-5 text-[#e7bd54]"></i>
            </div>
            <h2 class="text-lg font-black text-[#0c1d14]">تفاصيل المسار</h2>
          </div>

          {{-- Info grid --}}
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Departure City --}}
            <div class="flex items-center gap-4 bg-[#f9f5ec] rounded-xl p-4">
              <div class="w-10 h-10 rounded-xl bg-[#1f442e]/10 flex items-center justify-center flex-shrink-0">
                <i data-lucide="map-pin" class="w-5 h-5 text-[#1f442e]"></i>
              </div>
              <div class="text-right min-w-0">
                <div class="text-[#1f442e]/50 text-xs font-semibold">مدينة المغادرة</div>
                <div class="text-[#0c1d14] font-bold text-base truncate">{{ $trip->departureCity->name }}</div>
                @if($departureStationName)
                  <div class="text-[#1f442e]/40 text-xs mt-0.5 truncate">{{ $departureStationName }}</div>
                @endif
              </div>
            </div>

            {{-- Destination City --}}
            <div class="flex items-center gap-4 bg-[#f9f5ec] rounded-xl p-4">
              <div class="w-10 h-10 rounded-xl bg-[#e7bd54]/15 flex items-center justify-center flex-shrink-0">
                <i data-lucide="map-pin" class="w-5 h-5 text-[#d49920]"></i>
              </div>
              <div class="text-right min-w-0">
                <div class="text-[#1f442e]/50 text-xs font-semibold">مدينة الوصول</div>
                <div class="text-[#0c1d14] font-bold text-base truncate">{{ $trip->destinationCity->name }}</div>
                @if($arrivalStationName)
                  <div class="text-[#1f442e]/40 text-xs mt-0.5 truncate">{{ $arrivalStationName }}</div>
                @endif
              </div>
            </div>

            {{-- Departure time --}}
            <div class="flex items-center gap-4 bg-[#f9f5ec] rounded-xl p-4">
              <div class="w-10 h-10 rounded-xl bg-[#1f442e]/10 flex items-center justify-center flex-shrink-0">
                <i data-lucide="log-out" class="w-5 h-5 text-[#1f442e]"></i>
              </div>
              <div class="text-right">
                <div class="text-[#1f442e]/50 text-xs font-semibold">وقت المغادرة</div>
                <div class="text-[#0c1d14] font-bold text-base">{{ $trip->departure_time->format('h:i A') }} — {{ $trip->departure_time->locale('ar')->translatedFormat('d F Y') }}</div>
              </div>
            </div>

            {{-- Arrival time --}}
            <div class="flex items-center gap-4 bg-[#f9f5ec] rounded-xl p-4">
              <div class="w-10 h-10 rounded-xl bg-[#e7bd54]/15 flex items-center justify-center flex-shrink-0">
                <i data-lucide="log-in" class="w-5 h-5 text-[#d49920]"></i>
              </div>
              <div class="text-right">
                <div class="text-[#1f442e]/50 text-xs font-semibold">وقت الوصول</div>
                <div class="text-[#0c1d14] font-bold text-base">{{ $trip->arrival_time->format('h:i A') }} — {{ $trip->arrival_time->locale('ar')->translatedFormat('d F Y') }}</div>
              </div>
            </div>
          </div>

          {{-- Duration highlight bar --}}
          <div class="mt-5 flex items-center gap-3 bg-gradient-to-l from-[#e7bd54]/10 to-[#1f442e]/5 rounded-xl p-4 border border-[#e7bd54]/10">
            <i data-lucide="timer" class="w-5 h-5 text-[#d49920] flex-shrink-0"></i>
            <div class="text-right">
              <span class="text-[#1f442e]/60 text-sm">مدة الرحلة المتوقعة: </span>
              <span class="text-[#0c1d14] font-bold text-sm">{{ $durationText }}</span>
            </div>
          </div>
        </div>

        {{-- ── Card: Vehicle Info ── --}}
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-[#1f442e]/5">
          <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-[#1f442e] flex items-center justify-center">
              <i data-lucide="bus" class="w-5 h-5 text-[#e7bd54]"></i>
            </div>
            <h2 class="text-lg font-black text-[#0c1d14]">معلومات المركبة</h2>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @if($vehicleName)
              <div class="text-center bg-[#f9f5ec] rounded-xl p-5">
                <div class="w-12 h-12 mx-auto rounded-xl bg-[#1f442e]/10 flex items-center justify-center mb-3">
                  <i data-lucide="car" class="w-5 h-5 text-[#1f442e]"></i>
                </div>
                <div class="text-[#1f442e]/50 text-xs font-semibold mb-1">اسم المركبة</div>
                <div class="text-[#0c1d14] font-bold text-base">{{ $vehicleName }}</div>
              </div>
            @endif

            @if($vehicleTypeName)
              <div class="text-center bg-[#f9f5ec] rounded-xl p-5">
                <div class="w-12 h-12 mx-auto rounded-xl bg-[#e7bd54]/15 flex items-center justify-center mb-3">
                  <i data-lucide="bus" class="w-5 h-5 text-[#d49920]"></i>
                </div>
                <div class="text-[#1f442e]/50 text-xs font-semibold mb-1">نوع المركبة</div>
                <div class="text-[#0c1d14] font-bold text-base">{{ $vehicleTypeName }}</div>
              </div>
            @endif

            @if($vehicleModel)
              <div class="text-center bg-[#f9f5ec] rounded-xl p-5">
                <div class="w-12 h-12 mx-auto rounded-xl bg-[#1f442e]/10 flex items-center justify-center mb-3">
                  <i data-lucide="settings" class="w-5 h-5 text-[#1f442e]"></i>
                </div>
                <div class="text-[#1f442e]/50 text-xs font-semibold mb-1">الموديل</div>
                <div class="text-[#0c1d14] font-bold text-base">{{ $vehicleModel }}</div>
              </div>
            @endif
          </div>

          {{-- Capacity row --}}
          <div class="mt-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-[#f9f5ec] rounded-xl p-4">
            <div class="flex items-center gap-3">
              <i data-lucide="users" class="w-5 h-5 text-[#1f442e]"></i>
              <span class="text-[#1f442e]/60 text-sm font-semibold">السعة الكلية:</span>
              <span class="text-[#0c1d14] font-bold text-sm">{{ $totalSeats }} مقعد</span>
            </div>
            @if($trip->ticket_limit)
              <div class="flex items-center gap-3">
                <i data-lucide="ticket" class="w-5 h-5 text-[#d49920]"></i>
                <span class="text-[#1f442e]/60 text-sm font-semibold">حد الحجز:</span>
                <span class="text-[#0c1d14] font-bold text-sm">{{ $trip->ticket_limit }} تذكرة</span>
              </div>
            @endif
          </div>
        </div>

        {{-- ── Card: Amenities ── --}}
        @if($trip->amenities && $trip->amenities->count() > 0)
          <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-[#1f442e]/5">
            <div class="flex items-center gap-3 mb-6">
              <div class="w-10 h-10 rounded-xl bg-[#1f442e] flex items-center justify-center">
                <i data-lucide="sparkles" class="w-5 h-5 text-[#e7bd54]"></i>
              </div>
              <h2 class="text-lg font-black text-[#0c1d14]">الخدمات والمرافق</h2>
              <span class="mr-auto px-3 py-1 bg-[#e7bd54]/15 text-[#d49920] text-xs font-bold rounded-full">{{ $trip->amenities->count() }} خدمة</span>
            </div>

            <div class="flex flex-wrap gap-3">
              @foreach($trip->amenities as $amenity)
                <div class="flex items-center gap-2.5 bg-[#f9f5ec] rounded-xl px-4 py-3 border border-[#1f442e]/5 hover:border-[#e7bd54]/30 hover:shadow-sm transition-all duration-300 group">
                  <div class="w-8 h-8 rounded-lg bg-[#1f442e]/10 flex items-center justify-center group-hover:bg-[#e7bd54]/15 transition-colors flex-shrink-0">
                    <i data-lucide="check-circle" class="w-4 h-4 text-[#1f442e] group-hover:text-[#d49920] transition-colors"></i>
                  </div>
                  <span class="text-[#0c1d14] text-sm font-semibold">{{ $amenity->name }}</span>
                </div>
              @endforeach
            </div>
          </div>
        @endif

        {{-- ── Card: Conditions (collapsible) ── --}}
        @if($trip->conditions)
          <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-[#1f442e]/5">
            <button onclick="toggleConditions()" class="flex items-center gap-3 w-full text-right cursor-pointer">
              <div class="w-10 h-10 rounded-xl bg-[#e7bd54]/15 flex items-center justify-center">
                <i data-lucide="info" class="w-5 h-5 text-[#d49920]"></i>
              </div>
              <h2 class="text-lg font-black text-[#0c1d14] flex-1">شروط وأحكام الرحلة</h2>
              <i data-lucide="chevron-down" id="conditions-chevron" class="w-5 h-5 text-[#1f442e]/40 transition-transform duration-300"></i>
            </button>
            <div id="conditions-content" class="hidden mt-5 text-[#1f442e]/70 text-sm leading-relaxed whitespace-pre-line bg-[#f9f5ec] rounded-xl p-5 border border-[#1f442e]/5">
              {{ $trip->conditions }}
            </div>
          </div>
        @endif

        {{-- ── Card: Previous Passengers ── --}}
        @auth
          @if($previousPassengers && $previousPassengers->count() > 0)
            <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-[#1f442e]/5">
              <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-[#1f442e] flex items-center justify-center">
                  <i data-lucide="users" class="w-5 h-5 text-[#e7bd54]"></i>
                </div>
                <h2 class="text-lg font-black text-[#0c1d14]">مسافرون سابقون على هذه الرحلة</h2>
              </div>
              <div class="flex flex-wrap gap-2">
                @foreach($previousPassengers->take(10) as $passenger)
                  <div class="flex items-center gap-2 bg-[#f9f5ec] rounded-full px-4 py-2">
                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-[#1f442e] to-[#0c1d14] flex items-center justify-center text-white text-xs font-bold">
                      {{ mb_substr($passenger->name ?? 'م', 0, 1) }}
                    </div>
                    <span class="text-[#1f442e]/70 text-xs font-semibold">{{ $passenger->name }}</span>
                  </div>
                @endforeach
              </div>
            </div>
          @endif
        @endauth

      </div>

      {{-- ============================================================== --}}
      {{-- RIGHT COLUMN (4/12): Pricing & Sidebar --}}
      {{-- ============================================================== --}}
      <div class="lg:col-span-4 space-y-6">

        {{-- ── Booking Summary Card (sticky) ── --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-[#1f442e]/5 lg:sticky lg:top-24 z-20" id="pricing-card">
          {{-- Card header --}}
          <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-[#e7bd54]/15 flex items-center justify-center">
              <i data-lucide="credit-card" class="w-5 h-5 text-[#d49920]"></i>
            </div>
            <h2 class="text-lg font-black text-[#0c1d14]">ملخص الحجز</h2>
          </div>

          {{-- Adult price --}}
          <div class="bg-[#f9f5ec] rounded-xl p-5 mb-4">
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center gap-2">
                <i data-lucide="user" class="w-4 h-4 text-[#1f442e]"></i>
                <span class="text-[#1f442e]/60 text-sm font-semibold">تذكرة بالغ</span>
              </div>
              @if($hasDiscount)
                <span class="px-2 py-0.5 bg-[#e7bd54]/15 text-[#d49920] text-[10px] font-bold rounded-full">خصم {{ number_format($trip->discount_percentage, 0) }}%</span>
              @endif
            </div>
            <div class="flex items-baseline gap-2">
              @if($hasDiscount)
                <div class="flex items-baseline gap-2">
                  <span class="text-3xl font-black text-[#0c1d14] trip-price" data-base-price="{{ $displayPriceAdult }}">{{ number_format((int)$displayPriceAdult, 0) }}</span>
                  <span class="text-sm text-[#1f442e]/50 font-bold trip-currency-symbol">{{ $baseCurrency->symbol ?? '' }}</span>
                </div>
                <div class="flex items-baseline gap-1 mr-2">
                  <span class="text-sm text-[#1f442e]/30 line-through trip-price-original" data-base-price="{{ $trip->price_for_adult }}">{{ number_format((int)$trip->price_for_adult, 0) }}</span>
                  <span class="text-[10px] text-[#1f442e]/20 trip-currency-symbol">{{ $baseCurrency->symbol ?? '' }}</span>
                </div>
              @else
                <span class="text-3xl font-black text-[#0c1d14] trip-price" data-base-price="{{ $trip->price_for_adult }}">{{ number_format((int)$trip->price_for_adult, 0) }}</span>
                <span class="text-sm text-[#1f442e]/50 font-bold trip-currency-symbol">{{ $baseCurrency->symbol ?? '' }}</span>
              @endif
            </div>
          </div>

          {{-- Child price --}}
          <div class="bg-[#f9f5ec] rounded-xl p-5 mb-4">
            <div class="flex items-center gap-2 mb-2">
              <i data-lucide="baby" class="w-4 h-4 text-[#d49920]"></i>
              <span class="text-[#1f442e]/60 text-sm font-semibold">تذكرة طفل</span>
            </div>
            <div class="flex items-baseline gap-2">
              <span class="text-2xl font-black text-[#0c1d14] trip-price" data-base-price="{{ $displayPriceChild }}">{{ number_format((int)$displayPriceChild, 0) }}</span>
              <span class="text-sm text-[#1f442e]/50 font-bold trip-currency-symbol">{{ $baseCurrency->symbol ?? '' }}</span>
              @if($hasDiscount)
                <div class="flex items-baseline gap-1 mr-2">
                  <span class="text-xs text-[#1f442e]/30 line-through trip-price-original" data-base-price="{{ $trip->price_for_child }}">{{ number_format((int)$trip->price_for_child, 0) }}</span>
                  <span class="text-[10px] text-[#1f442e]/20 trip-currency-symbol">{{ $baseCurrency->symbol ?? '' }}</span>
                </div>
              @endif
            </div>
          </div>

          {{-- Available seats --}}
          <div class="bg-[#f9f5ec] rounded-xl p-5 mb-6">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <i data-lucide="armchair" class="w-4 h-4 text-[#1f442e]"></i>
                <span class="text-[#1f442e]/60 text-sm font-semibold">المقاعد المتاحة</span>
              </div>
              <span class="text-[#0c1d14] font-bold text-sm">{{ $availableSeats }} / {{ $totalSeats }}</span>
            </div>
            <div class="w-full h-3 bg-[#1f442e]/10 rounded-full overflow-hidden">
              <div class="h-full rounded-full transition-all duration-700 {{ $seatBarColor }}" style="width: {{ $seatsPercentage }}%"></div>
            </div>
            <div class="text-center mt-2">
              <span class="text-[10px] text-[#1f442e]/30 font-semibold">{{ $seatsPercentage }}% مقاعد شاغرة</span>
            </div>
          </div>

          {{-- Wallet balance --}}
          @auth
            @if(isset($walletBalance))
              <div class="bg-[#1f442e]/5 rounded-xl p-4 mb-6 border border-[#1f442e]/10">
                <div class="flex items-center gap-2 mb-1">
                  <i data-lucide="wallet" class="w-4 h-4 text-[#1f442e]"></i>
                  <span class="text-[#1f442e] text-xs font-bold">رصيد محفظتك</span>
                </div>
                <div class="text-xl font-black text-[#0c1d14]">{{ number_format($walletBalance, 2) }}</div>
              </div>
            @endif
          @endauth

          {{-- Booking CTA --}}
          <div class="space-y-3">
            @auth
              <button onclick="handleBooking()" class="w-full btn-gold flex items-center justify-center gap-3 px-6 py-4 rounded-xl text-base cursor-pointer">
                <i data-lucide="ticket" class="w-5 h-5"></i>
                <span>احجز الآن</span>
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
              </button>
              <p class="text-center text-[#1f442e]/30 text-[10px] font-semibold">
                <i data-lucide="shield-check" class="w-3 h-3 inline-block -mt-0.5"></i>
                حجز آمن ومضمون — الأسعار شاملة الضريبة
              </p>
            @else
              <a href="{{ route('login') }}" class="w-full btn-gold flex items-center justify-center gap-3 px-6 py-4 rounded-xl text-base">
                <i data-lucide="log-in" class="w-5 h-5"></i>
                <span>سجّل دخولك لحجز هذه الرحلة</span>
              </a>
              <p class="text-center text-[#1f442e]/30 text-[10px] font-semibold">
                يجب عليك تسجيل الدخول أولاً لإتمام عملية الحجز
              </p>
            @endauth
          </div>

          {{-- Currency switcher --}}
          <div class="mt-6 pt-5 border-t border-[#1f442e]/10">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-bold text-[#1f442e]/50">تحويل العملة:</span>
            </div>
            <div class="flex flex-wrap gap-2" id="currency-pricing">
              @foreach($currencies as $curr)
                <button onclick="setCurrency({{ $curr['id'] }})" data-currency-id="{{ $curr['id'] }}"
                  class="curr-btn-pricing px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer
                    {{ $curr['id'] === $baseCurrencyId
                       ? 'bg-[#1f442e] text-white shadow-md'
                       : 'text-[#1f442e]/60 hover:text-[#0c1d14] hover:bg-[#1f442e]/5 border border-[#1f442e]/10'
                    }}">
                  {{ $curr['code'] }}
                </button>
              @endforeach
            </div>
          </div>
        </div>

        {{-- ── Coupon Codes ── --}}
        @if($trip->coupons && $trip->coupons->count() > 0)
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-[#1f442e]/5">
            <div class="flex items-center gap-3 mb-5">
              <div class="w-10 h-10 rounded-xl bg-[#e7bd54]/15 flex items-center justify-center">
                <i data-lucide="tag" class="w-5 h-5 text-[#d49920]"></i>
              </div>
              <h2 class="text-lg font-black text-[#0c1d14]">أكواد الخصم المتاحة</h2>
            </div>

            <div class="space-y-3 max-h-64 overflow-y-auto scrollbar-thin">
              @foreach($trip->coupons as $coupon)
                <div class="bg-[#f9f5ec] rounded-xl p-4 border border-dashed border-[#e7bd54]/40">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-bold text-[#d49920] font-mono">{{ $coupon->code ?? 'N/A' }}</span>
                    @if($coupon->discount_percentage ?? false)
                      <span class="px-2 py-0.5 bg-[#e7bd54]/15 text-[#d49920] text-[10px] font-bold rounded-full">-{{ number_format($coupon->discount_percentage, 0) }}%</span>
                    @endif
                  </div>
                  @if($coupon->description ?? false)
                    <p class="text-[#1f442e]/40 text-xs leading-relaxed">{{ $coupon->description }}</p>
                  @endif
                  @if($coupon->expires_at ?? false)
                    <div class="flex items-center gap-1 mt-2 text-[#1f442e]/30 text-[10px]">
                      <i data-lucide="clock" class="w-3 h-3"></i>
                      <span>صالح حتى {{ $coupon->expires_at->locale('ar')->translatedFormat('d F Y') }}</span>
                    </div>
                  @endif
                </div>
              @endforeach
            </div>
          </div>
        @endif

        {{-- ── Similar Trips (sidebar) ── --}}
        @if($similarTrips && $similarTrips->count() > 0)
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-[#1f442e]/5">
            <div class="flex items-center gap-3 mb-5">
              <div class="w-10 h-10 rounded-xl bg-[#1f442e] flex items-center justify-center">
                <i data-lucide="repeat" class="w-5 h-5 text-[#e7bd54]"></i>
              </div>
              <h2 class="text-lg font-black text-[#0c1d14]">رحلات مشابهة</h2>
            </div>

            <div class="space-y-3 max-h-96 overflow-y-auto scrollbar-thin">
              @foreach($similarTrips->take(5) as $similar)
                @php
                  $sHasDiscount = $similar->discount_percentage > 0;
                  $sDisplayPrice = $sHasDiscount ? ($similar->price_for_adult * (1 - $similar->discount_percentage / 100)) : $similar->price_for_adult;
                @endphp
                <a href="{{ route('home.show', $similar->uuid) }}" class="block bg-[#f9f5ec] rounded-xl p-4 border border-[#1f442e]/5 hover:border-[#e7bd54]/30 hover:shadow-sm transition-all duration-300 group">
                  <div class="flex items-center gap-3 mb-3">
                    <div class="flex-1">
                      <div class="flex items-center gap-2 text-sm font-bold text-[#0c1d14] group-hover:text-[#d49920] transition-colors">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-[#1f442e]"></i>
                        <span>{{ $similar->departureCity->name }} → {{ $similar->destinationCity->name }}</span>
                      </div>
                      <div class="text-[10px] text-[#1f442e]/40 mt-1">{{ $similar->departure_time->format('h:i A') }} — {{ $similar->departure_time->locale('ar')->translatedFormat('d F Y') }}</div>
                    </div>
                    @if($sHasDiscount)
                      <span class="px-2 py-0.5 bg-[#e7bd54]/15 text-[#d49920] text-[10px] font-bold rounded-full flex-shrink-0">-{{ number_format($similar->discount_percentage, 0) }}%</span>
                    @endif
                  </div>
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5 text-xs text-[#1f442e]/40">
                      <i data-lucide="armchair" class="w-3.5 h-3.5"></i>
                      <span>{{ $similar->available_seats }} مقعد</span>
                    </div>
                    <div class="flex items-baseline gap-1">
                      <span class="text-base font-black text-[#0c1d14] trip-price" data-base-price="{{ $sDisplayPrice }}">{{ number_format((int)$sDisplayPrice, 0) }}</span>
                      <span class="text-[10px] text-[#1f442e]/50 font-bold trip-currency-symbol">{{ $baseCurrency->symbol ?? '' }}</span>
                    </div>
                  </div>
                </a>
              @endforeach
            </div>
          </div>
        @endif

      </div>{{-- / RIGHT COLUMN --}}
    </div>
  </div>
</section>

{{-- ================================================================== --}}
{{-- 4. MOBILE STICKY BOOKING BAR --}}
{{-- ================================================================== --}}
<div class="lg:hidden fixed bottom-0 left-0 right-0 z-50 bg-[#f9f5ec]/95 backdrop-blur-xl border-t border-[#1f442e]/10" id="mobile-booking-bar">
  <div class="px-4 py-3 flex items-center gap-3">
    <div class="flex-1 text-right">
      <div class="text-[#1f442e]/40 text-[10px] font-semibold">السعر يبدأ من</div>
      <div class="flex items-baseline gap-1">
        <span class="text-xl font-black text-[#0c1d14] trip-price" data-base-price="{{ $displayPriceAdult }}">{{ number_format((int)$displayPriceAdult, 0) }}</span>
        <span class="text-xs text-[#1f442e]/50 font-bold trip-currency-symbol">{{ $baseCurrency->symbol ?? '' }}</span>
      </div>
    </div>
    @auth
      <button onclick="handleBooking()" class="btn-gold flex items-center gap-2 px-5 py-3 rounded-xl font-bold text-sm flex-shrink-0 cursor-pointer">
        <i data-lucide="ticket" class="w-4 h-4"></i>
        <span>احجز الآن</span>
      </button>
    @else
      <a href="{{ route('login') }}" class="btn-gold flex items-center gap-2 px-5 py-3 rounded-xl font-bold text-sm flex-shrink-0">
        <i data-lucide="log-in" class="w-4 h-4"></i>
        <span>سجّل دخولك</span>
      </a>
    @endauth
  </div>
</div>

@endsection

{{-- ================================================================== --}}
{{-- PAGE-SPECIFIC STYLES --}}
{{-- ================================================================== --}}
@push('styles')
<style>
  @keyframes slideUp {
    from { transform: translateY(100%); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
  }
  .slide-up { animation: slideUp 0.4s ease-out; }
  #mobile-booking-bar { animation: slideUp 0.4s ease-out; }

  /* Bottom padding on mobile for sticky bar */
  @media (max-width: 1023px) {
    body { padding-bottom: 72px; }
  }

  select option {
    color: #1f442e;
    background: #f9f5ec;
  }
</style>
@endpush

{{-- ================================================================== --}}
{{-- PAGE-SPECIFIC SCRIPTS --}}
{{-- ================================================================== --}}
@push('scripts')
<script>
  // Re-init lucide for dynamically added icons
  if (typeof lucide !== 'undefined') lucide.createIcons();

  // ─── Currency data ───────────────────────────────────────────────────
  let currencies     = @json($currencies);
  let baseCurrencyId  = {{ $baseCurrencyId }};
  let currentCurrencyId = {{ $baseCurrencyId }};

  function getCurrency(id) {
    return currencies.find(c => c.id === id) || currencies.find(c => c.id === baseCurrencyId);
  }

  function formatPrice(basePrice, currencyId) {
    const curr = getCurrency(currencyId);
    if (!curr) return { formatted: '0', symbol: '' };

    const converted = basePrice * curr.rate_to_base;
    const decimals  = curr.rate_to_base >= 1 ? 0 : 2;
    const formatted = new Intl.NumberFormat('en-US', { maximumFractionDigits: decimals }).format(converted);

    const baseCurr = getCurrency(baseCurrencyId);
    let symbol = (baseCurr && curr.id !== baseCurrencyId) ? curr.code : (baseCurr ? (baseCurr.symbol || baseCurr.code) : curr.code);

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
          btn.className = 'curr-btn-pricing px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer bg-[#1f442e] text-white shadow-md';
        } else {
          btn.className = 'curr-btn-pricing px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer text-[#1f442e]/60 hover:text-[#0c1d14] hover:bg-[#1f442e]/5 border border-[#1f442e]/10';
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
    showToast('صفحة الحجز قيد التطوير — قريباً!', 'info');
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
      info:    'text-[#e7bd54]',
      success: 'text-white',
      warning: 'text-[#0c1d14]',
      error:   'text-white',
    };
    const icons = {
      info:    'info',
      success: 'check-circle',
      warning: 'alert-triangle',
      error:   'x-circle',
    };

    const toast = document.createElement('div');
    toast.id = 'aracari-toast';
    toast.className = 'fixed top-24 left-1/2 -translate-x-1/2 z-[999] bg-gradient-to-r ' + colors[type] + ' text-white px-6 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 font-semibold text-sm slide-up';
    toast.innerHTML = '<i data-lucide="' + icons[type] + '" class="w-5 h-5 flex-shrink-0 ' + iconColors[type] + '"></i><span>' + message + '</span>';

    document.body.appendChild(toast);
    if (typeof lucide !== 'undefined') lucide.createIcons();

    setTimeout(() => {
      toast.style.transition = 'opacity 0.3s, transform 0.3s';
      toast.style.opacity    = '0';
      toast.style.transform  = 'translate(-50%, -20px)';
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
@endpush
