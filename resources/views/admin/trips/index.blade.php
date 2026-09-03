@extends('layouts.admin')

@section('title', 'أركاري | الرحلات')
@section('header_title', 'الرحلات')

@section('content')

@php
    $totalTrips  = $trips->total();
    $activeTrips = $trips->getCollection()->filter(fn($t) => in_array($t->trip_status_id, [\App\Constants\TripStatus::SCHEDULED, \App\Constants\TripStatus::IN_PROGRESS]))->count();
    $todayTrips  = $trips->getCollection()->filter(fn($t) => $t->departure_time && $t->departure_time->isToday())->count();

    $occupancySum = 0; $occupancyCount = 0;
    foreach ($trips->getCollection() as $t) {
        $total = $t->total_seats ?? ($t->ticket_limit ?? 0);
        $available = $t->available_seats ?? 0;
        if ($total > 0) { $occupancySum += (($total - $available) / $total) * 100; $occupancyCount++; }
    }
    $occupancyPercent = $occupancyCount > 0 ? round($occupancySum / $occupancyCount) : 0;

    $cities = \App\Models\City::orderBy('name')->get();

    $trips->getCollection()->each(function($t) {
        $t->loadMissing([
            'waypoints.city', 'policies.rules', 'amenities',
            'repeats.repeatedDays', 'departureCity.country',
            'destinationCity.country', 'departureStation', 'arrivalStation',
            'currency', 'typeOfTrip', 'levelTrip', 'user',
        ]);
    });
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
    icon="bus"
    title="الرحلات"
    :count="$totalTrips"
    countLabel="رحلة"
    description="الإطلاع على الرحلات والمسارات والمقاعد"
    descriptionIcon="bus"
  />

  <div id="aracari-refreshable" class="space-y-6">

  {{-- ===== Stat Cards ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    <x-stat-card :value="$totalTrips" label="إجمالي الرحلات" icon="bus" variant="slate" badge="متاحة" badgeIcon="check-circle" />
    <x-stat-card :value="$activeTrips" label="رحلات نشطة" icon="play-circle" variant="emerald" badge="جارية" badgeIcon="activity" />
    <x-stat-card :value="$todayTrips" label="رحلات اليوم" icon="calendar" variant="blue" badge="اليوم" badgeIcon="clock" />
    <x-stat-card :value="$occupancyPercent . '%'" label="نسبة الإشغال" icon="trending-up" variant="amber" :badge="$occupancyPercent > 70 ? 'ممتاز' : 'جيد'" badgeIcon="trending-up" />
  </div>

  {{-- ===== Data Table ===== --}}
  <x-data-table-card :searchable="true" :searchMode="'server'" searchPlaceholder="ابحث عن رحلة..." :footerInfo="'عرض ' . $trips->count() . ' من أصل ' . $trips->total() . ' رحلة'">

    <x-slot name="filters">
      <select name="status" form="aracari-search-form" class="h-11 bg-white/70 border border-slate-200 rounded-2xl px-4 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all appearance-none cursor-pointer text-right w-full sm:w-auto">
        <option value="" {{ !request('status') ? 'selected' : '' }}>كل الحالات</option>
        <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>مجدولة</option>
        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>جارية</option>
        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>مكتملة</option>
        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ملغاة</option>
      </select>
      <select name="city" form="aracari-search-form" class="h-11 bg-white/70 border border-slate-200 rounded-2xl px-4 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all appearance-none cursor-pointer text-right w-full sm:w-auto">
        <option value="" {{ !request('city') ? 'selected' : '' }}>كل المدن</option>
        @foreach($cities as $city)
          <option value="{{ $city->id }}" {{ request('city') == $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
        @endforeach
      </select>
      <input type="date" name="date" form="aracari-search-form" value="{{ request('date') }}" class="h-11 bg-white/70 border border-slate-200 rounded-2xl px-4 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all w-full sm:w-auto">
    </x-slot>

    <x-slot name="thead">
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المركبة</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">مزود الخدمة</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المسار</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الانطلاق</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">المقاعد</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">السعر</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">عرض</th>
    </x-slot>

    <x-slot name="rows">
      @forelse($trips as $trip)
        @php
          $seatTotal = $trip->total_seats ?? ($trip->ticket_limit ?? 0);
          $seatAvailable = $trip->available_seats ?? 0;
          $seatBooked = max(0, $seatTotal - $seatAvailable);
          $seatPercent = $seatTotal > 0 ? min(100, round(($seatBooked / $seatTotal) * 100)) : 0;
          $seatColor = $seatPercent >= 90 ? 'from-rose-400 to-rose-500' : ($seatPercent >= 50 ? 'from-amber-400 to-amber-500' : 'from-emerald-400 to-teal-500');

          $isDeleted = $trip->trashed();
          if ($isDeleted) { $statusVariant = 'rose'; $statusLabel = 'محذوف'; }
          else {
              $statusName = $trip->tripStatus?->status_name ?? '';
              $statusVariant = match(true) {
                  in_array($trip->trip_status_id, [\App\Constants\TripStatus::SCHEDULED]) || mb_strtolower($statusName) === 'scheduled' || $statusName === 'جدولة' => 'emerald',
                  in_array($trip->trip_status_id, [\App\Constants\TripStatus::IN_PROGRESS]) || mb_strtolower($statusName) === 'in_progress' || $statusName === 'جارية' => 'blue',
                  in_array($trip->trip_status_id, [\App\Constants\TripStatus::COMPLETED]) || mb_strtolower($statusName) === 'completed' || $statusName === 'مكتملة' => 'slate',
                  in_array($trip->trip_status_id, [\App\Constants\TripStatus::CANCELLED]) || mb_strtolower($statusName) === 'cancelled' || $statusName === 'ملغاة' => 'rose',
                  default => 'slate',
              };
              $statusLabel = $statusName ?: 'غير محدد';
          }
          $statusStyles = ['emerald'=>'bg-emerald-50 text-emerald-700 border-emerald-200','blue'=>'bg-blue-50 text-blue-700 border-blue-200','slate'=>'bg-slate-50 text-slate-600 border-slate-200','rose'=>'bg-rose-50 text-rose-700 border-rose-200'];
          $statusClasses = $statusStyles[$statusVariant] ?? $statusStyles['slate'];

          $vehicleName = $trip->vehicle?->name ?: ($trip->vehicle?->model ?: '—');
          $vehicleType = $trip->vehicle?->vehicleType?->type_name ?? '';
          $levelName = $trip->levelTrip?->level_name ?? 'درجة سياحية';
          $providerName = $trip->vehicle?->user?->providerService?->name ?? $trip->user?->name ?? '—';
          $providerInitial = mb_substr($providerName, 0, 1);
          $currencySymbol = $baseCurrency?->symbol ?? 'ر.ي';

          $waypointNames = $trip->waypoints->map(fn($w) => $w->city?->name ?? '—')->filter(fn($n) => $n !== '—')->values()->toArray();
          $amenityNames = $trip->amenities->pluck('name')->toArray();
          $policyNames = $trip->policies->pluck('name')->toArray();
          $policyRules = [];
          foreach ($trip->policies as $p) { foreach ($p->rules as $r) { $policyRules[] = $r->rule_text ?? $r->description ?? ''; } }
          $couponCodes = $trip->coupons->pluck('code')->toArray();

          $tripRepeat = $trip->repeats->first();
          $recurrenceEnabled = (bool) $tripRepeat;
          $arabicWeekDays = ['السبت', 'الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'];
          $recurrenceDays = $tripRepeat ? $tripRepeat->repeatedDays->pluck('day_of_week')->map(fn($d) => $arabicWeekDays[$d] ?? $d)->toArray() : [];

          $modalData = [
              'trip_name_id'     => $trip->trip_name_id ?? '',
              'from_city'        => $trip->departureCity?->name ?? '—',
              'from_country'      => $trip->departureCity?->country?->name ?? '',
              'to_city'          => $trip->destinationCity?->name ?? '—',
              'to_country'       => $trip->destinationCity?->country?->name ?? '',
              'from_station'     => $trip->departureStation?->name ?? '',
              'to_station'       => $trip->arrivalStation?->name ?? '',
              'status_name'      => $statusLabel,
              'departure_time'   => $trip->departure_time?->format('H:i') ?? '—',
              'departure_date'   => $trip->departure_time?->translatedFormat('j F، Y') ?? '—',
              'arrival_time'     => $trip->arrival_time?->format('H:i') ?? '—',
              'arrival_date'     => $trip->arrival_time?->translatedFormat('j F، Y') ?? '—',
              'check_in_time'    => $trip->check_in_time?->format('H:i') ?? '—',
              'vehicle_name'     => $vehicleName,
              'vehicle_model'    => $trip->vehicle?->model ?? '',
              'level_name'       => $levelName,
              'provider_name'    => $providerName,
              'adult_price'      => number_format($trip->price_for_adult, 2) . ' ' . $currencySymbol,
              'child_price'      => number_format($trip->price_for_child, 2) . ' ' . $currencySymbol,
              'discount'         => $trip->discount_percentage > 0 ? number_format($trip->discount_percentage, 0) . '%' : '—',
              'total_seats'      => (string) $seatTotal,
              'available_seats'  => (string) $seatAvailable,
              'booked_seats'     => (string) $seatBooked,
              'conditions'       => $trip->conditions ?? '',
              'is_public'        => $trip->is_public ? 'نعم' : 'لا',
              'type_of_trip'     => $trip->typeOfTrip?->type_name ?? '',
              'currency_name'    => $trip->currency?->name ?? ($baseCurrency?->name ?? 'ريال يمني'),
              'created_at'       => optional($trip->created_at)->translatedFormat('j F، Y الساعة H:i') ?? '—',
              'created_by'       => $trip->user?->name ?? '—',
              'waypoints'        => $waypointNames,
              'amenities'        => $amenityNames,
              'policy_names'     => $policyNames,
              'policy_rules'     => $policyRules,
              'coupon_codes'     => $couponCodes,
              'recurrence_enabled' => $recurrenceEnabled ? 'نعم' : 'لا',
              'recurrence_days'  => $recurrenceDays,
              'recurrence_end_date' => $tripRepeat?->end_date?->translatedFormat('j F، Y') ?? '',
          ];
        @endphp

        <tr class="hover:bg-slate-50/50 transition-colors group {{ $isDeleted ? 'opacity-60' : '' }}">
          <td class="px-6 py-4"><span class="font-mono font-black text-slate-400 text-sm">{{ $trip->trip_name_id ?? \Illuminate\Support\Str::substr($trip->uuid ?? '', 0, 8) }}</span></td>
          <td data-searchable class="px-6 py-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500/20 to-teal-600/10 flex items-center justify-center border border-teal-100 flex-shrink-0"><i data-lucide="bus" class="w-5 h-5 text-teal-600"></i></div>
              <div><div class="font-bold text-slate-800 text-sm">{{ $vehicleName }}</div><div class="text-[10px] font-medium text-slate-400">{{ $vehicleType ? $vehicleType . ' · ' : '' }}{{ $levelName }}</div></div>
            </div>
          </td>
          <td data-searchable class="px-6 py-4">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 text-xs font-bold shadow-sm flex-shrink-0">{{ $providerInitial }}</div>
              <div><div class="font-bold text-slate-700 text-xs">{{ $providerName }}</div><div class="text-[9px] text-slate-400">مزود خدمة</div></div>
            </div>
          </td>
          <td data-searchable class="px-6 py-4">
            <div class="flex items-center gap-2 text-sm font-bold text-slate-700">
              <span class="text-teal-600">{{ $trip->departureCity?->name ?? '—' }}</span>
              <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-slate-300 flex-shrink-0"></i>
              <span class="text-amber-600">{{ $trip->destinationCity?->name ?? '—' }}</span>
            </div>
          </td>
          <td class="px-6 py-4 text-center">
            <p class="font-bold text-slate-800 text-sm font-mono">{{ $trip->departure_time?->format('H:i') ?? '—' }}</p>
            <p class="text-[10px] text-slate-400 font-medium">{{ $trip->departure_time?->translatedFormat('j F، Y') ?? '—' }}</p>
          </td>
          <td class="px-6 py-4">
            <div class="flex flex-col items-center gap-1.5">
              <div class="w-20 h-1.5 rounded-full bg-slate-100 overflow-hidden"><div class="h-full rounded-full bg-gradient-to-r {{ $seatColor }} transition-all duration-500" style="width: {{ $seatPercent }}%"></div></div>
              <span class="text-[10px] font-bold text-slate-500">{{ $seatBooked }} / {{ $seatTotal }}</span>
            </div>
          </td>
          <td class="px-6 py-4"><span class="font-mono font-black text-slate-800 text-base">{{ number_format($trip->price_for_adult, 2) }}<span class="text-[10px] font-bold text-slate-400 mr-1">{{ $currencySymbol }}</span></span></td>
          <td class="px-6 py-4 text-center"><span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-[10px] font-bold border {{ $statusClasses }}">{{ $statusLabel }}</span></td>
          <td class="px-6 py-4 text-center">
            <div class="flex items-center justify-center gap-1.5">
              <x-action-button variant="view" data-view-modal="trip-details-modal" data-view-json="{{ json_encode($modalData, JSON_UNESCAPED_UNICODE) }}" />
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state icon="bus" :title="request('search') || request('status') || request('city') || request('date') ? 'لا توجد نتائج مطابقة' : 'لا توجد رحلات مسجلة'" :message="request('search') || request('status') || request('city') || request('date') ? 'جرّب تعديل خيارات البحث' : 'سيظهر هنا الرحلات عندما يقوم المزودون بإضافتها'" :colspan="9" />
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$trips" />
    </x-slot>
  </x-data-table-card>

  </div>
</div>

{{-- ========================================================== --}}
{{-- ===== MODAL: Trip Details (View Only) ===== --}}
{{-- ========================================================== --}}
<x-modal id="trip-details-modal" title="تفاصيل الرحلة" variant="view" icon="bus" :static="true" maxWidth="max-w-3xl">

  {{-- 1. Route Timeline --}}
  <div class="space-y-4 bg-slate-50 p-5 rounded-2xl border border-[#eeeef0]">
    <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-100 pb-2">
      <i data-lucide="map" class="w-4 h-4 text-emerald-600"></i>
      <span>مخطط سير الرحلة التفصيلي</span>
    </h4>
    <div class="relative pr-6 mr-2 py-2 space-y-5">
      <div class="absolute top-4 bottom-4 right-[9px] w-0.5 bg-gradient-to-b from-emerald-500 via-purple-400 to-rose-500"></div>
      {{-- Start --}}
      <div class="relative flex items-start gap-4">
        <span class="absolute right-[-22px] top-1.5 w-5 h-5 rounded-full bg-white border-4 border-emerald-500 z-10"></span>
        <div class="space-y-0.5">
          <h5 class="font-bold text-sm text-slate-800">الانطلاق: <span data-preview="from_city">—</span> <span class="text-[10px] text-slate-400">(<span data-preview="from_country"></span>)</span></h5>
          <p class="text-xs text-slate-500" data-preview="from_station"></p>
          <p class="text-[10px] text-emerald-700 font-bold font-mono">تاريخ المغادرة: <span data-preview="departure_date">—</span> <span data-preview="departure_time">—</span></p>
        </div>
      </div>
      {{-- Waypoints (dynamic) --}}
      <div id="detail-waypoints-list"></div>
      {{-- End --}}
      <div class="relative flex items-start gap-4">
        <span class="absolute right-[-22px] top-1.5 w-5 h-5 rounded-full bg-white border-4 border-rose-500 z-10"></span>
        <div class="space-y-0.5">
          <h5 class="font-bold text-sm text-slate-800">الوصول النهائي: <span data-preview="to_city">—</span> <span class="text-[10px] text-slate-400">(<span data-preview="to_country"></span>)</span></h5>
          <p class="text-xs text-slate-500" data-preview="to_station"></p>
          <p class="text-[10px] text-rose-700 font-bold font-mono">الوصول المتوقع: <span data-preview="arrival_date">—</span> <span data-preview="arrival_time">—</span></p>
        </div>
      </div>
    </div>
  </div>

  {{-- 2. Pricing & Vehicle --}}
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="p-5 bg-white border border-[#eeeef0] rounded-2xl shadow-sm space-y-3">
      <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1"><i data-lucide="dollar-sign" class="w-4 h-4 text-emerald-600"></i><span>بيانات الأسعار والمقاعد</span></h4>
      <div class="space-y-2.5">
        <div class="flex justify-between items-center py-1.5 border-b border-slate-50 text-xs"><span class="text-slate-500 font-medium">سعر تذكرة البالغ:</span><span class="font-black text-slate-800" data-preview="adult_price">—</span></div>
        <div class="flex justify-between items-center py-1.5 border-b border-slate-50 text-xs"><span class="text-slate-500 font-medium">سعر تذكرة الطفل:</span><span class="font-black text-slate-800" data-preview="child_price">—</span></div>
        <div class="flex justify-between items-center py-1.5 border-b border-slate-50 text-xs" id="detail-discount-row" style="display:none;"><span>خصم ترويجي نشط:</span><span class="font-bold text-rose-700" data-preview="discount">—</span></div>
        <div class="flex justify-between items-center py-1.5 text-xs"><span class="text-slate-500 font-medium">حالة المقاعد:</span><span class="font-black text-emerald-700"><span data-preview="booked_seats">0</span> محجوز من أصل <span data-preview="total_seats">0</span></span></div>
      </div>
    </div>
    <div class="p-5 bg-white border border-[#eeeef0] rounded-2xl shadow-sm space-y-3">
      <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1"><i data-lucide="sparkles" class="w-4 h-4 text-emerald-600"></i><span>الخدمات والمركبة</span></h4>
      <div class="space-y-2.5">
        <div class="flex justify-between items-center py-1 border-b border-slate-50 text-xs"><span class="text-slate-500 font-medium">المركبة:</span><span class="font-black text-slate-800" data-preview="vehicle_name">—</span></div>
        <div class="flex justify-between items-center py-1 border-b border-slate-50 text-xs"><span class="text-slate-500 font-medium">الموديل:</span><span class="font-black text-slate-800" data-preview="vehicle_model">—</span></div>
        <div class="flex justify-between items-center py-1 border-b border-slate-50 text-xs"><span class="text-slate-500 font-medium">درجة الرحلة:</span><span class="font-black text-slate-800" data-preview="level_name">—</span></div>
        <div class="pt-1">
          <span class="text-[10px] font-bold text-slate-400 block mb-1.5">الخدمات المشمولة:</span>
          <div class="flex flex-wrap gap-1.5" id="detail-amenities-list"><span class="text-xs text-slate-400">لا توجد خدمات</span></div>
        </div>
      </div>
    </div>
  </div>

  {{-- 3. Policy & Coupons/Recurrence --}}
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="p-5 bg-amber-50/40 border border-amber-100 rounded-2xl space-y-3">
      <h4 class="font-bold text-xs text-amber-900 uppercase tracking-wider flex items-center gap-1"><i data-lucide="shield-check" class="w-4 h-4 text-amber-700"></i><span>سياسة الإلغاء المعتمدة</span></h4>
      <div id="detail-policies-list"><p class="text-xs text-amber-800 font-medium">لم يتم تحديد سياسة</p></div>
    </div>
    <div class="p-5 bg-white border border-[#eeeef0] rounded-2xl shadow-sm space-y-3">
      <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1"><i data-lucide="tag" class="w-4 h-4 text-purple-600"></i><span>الكوبونات والجدولة</span></h4>
      <div class="space-y-2 text-xs">
        <div>
          <span class="text-slate-400 font-bold block mb-1">الكوبونات النشطة:</span>
          <div class="flex flex-wrap gap-1" id="detail-coupons-list"><span class="text-slate-400 text-xs font-semibold">لا يسمح بتطبيق كوبونات</span></div>
        </div>
        <div class="pt-2 border-t border-slate-50">
          <span class="text-slate-400 font-bold block mb-1">التكرار الدوري:</span>
          <div id="detail-recurrence-info"><span class="text-slate-400 text-[11px] font-semibold">رحلة فردية</span></div>
        </div>
      </div>
    </div>
  </div>

  {{-- 4. Conditions --}}
  <div id="detail-conditions-section" style="display:none;">
    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs font-medium text-slate-600 leading-relaxed">
      <span class="font-bold text-slate-800 block">إرشادات للركاب:</span>
      <p data-preview="conditions"></p>
    </div>
  </div>

  {{-- 5. Metadata --}}
  <div class="p-4 bg-slate-50 rounded-2xl border border-[#eeeef0]">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-[10px]">
      <div><span class="text-slate-400 font-bold block">نوع الرحلة</span><span class="text-slate-700 font-bold" data-preview="type_of_trip">—</span></div>
      <div><span class="text-slate-400 font-bold block">العملة</span><span class="text-slate-700 font-bold" data-preview="currency_name">—</span></div>
      <div><span class="text-slate-400 font-bold block">راحة عامة</span><span class="text-slate-700 font-bold" data-preview="is_public">—</span></div>
      <div><span class="text-slate-400 font-bold block">أُنشئت بواسطة</span><span class="text-slate-700 font-bold" data-preview="created_by">—</span></div>
      <div><span class="text-slate-400 font-bold block">تسجيل الدخول</span><span class="text-slate-700 font-bold" data-preview="check_in_time">—</span></div>
      <div class="md:col-span-3"><span class="text-slate-400 font-bold block">تاريخ الإنشاء</span><span class="text-slate-700 font-bold" data-preview="created_at">—</span></div>
    </div>
  </div>

</x-modal>

@endsection

@push('scripts')
<script>
// ===== Trip details modal: rich data =====
document.addEventListener('aracari:modal-open', function(e) {
  var modal = e.target;
  if (modal.id !== 'trip-details-modal') return;
  var data = e.detail.data || {};

  // Change banner to emerald for trip details
  var b = modal.querySelector('.aracari-modal-panel .relative.h-24');
  if (b) b.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-emerald-900 to-teal-950';
  var t = modal.querySelector('.aracari-modal-panel h2');
  if (t) t.innerHTML = '<i data-lucide="bus" class="w-5 h-5"></i> تفاصيل مسار ومكونات الرحلة ' + (data.trip_name_id || '—');
  var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span[class*="rounded-full"]');
  if (badge) { badge.textContent = 'عرض'; badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30'; }

  // Waypoints
  var wpList = document.getElementById('detail-waypoints-list');
  if (wpList) {
    wpList.innerHTML = '';
    if (data.waypoints && data.waypoints.length > 0) {
      data.waypoints.forEach(function(city) {
        var div = document.createElement('div');
        div.className = 'relative flex items-start gap-4';
        div.innerHTML = '<span class="absolute right-[-20px] top-1.5 w-4 h-4 rounded-full bg-white border-4 border-purple-400 z-10"></span><div class="space-y-0.5"><h5 class="font-bold text-sm text-slate-700">مدينة مرور: ' + city + '</h5><p class="text-[10px] text-purple-600 font-bold">محطة عبور ترانزيت</p></div>';
        wpList.appendChild(div);
      });
    }
  }

  // Amenities
  var amList = document.getElementById('detail-amenities-list');
  if (amList) {
    amList.innerHTML = '';
    if (data.amenities && data.amenities.length > 0) {
      data.amenities.forEach(function(name) {
        var span = document.createElement('span');
        span.className = 'bg-emerald-50 text-emerald-800 text-[10px] font-bold px-2 py-1 rounded-md border border-emerald-100';
        span.textContent = name;
        amList.appendChild(span);
      });
    } else { amList.innerHTML = '<span class="text-xs text-slate-400">لا توجد خدمات</span>'; }
  }

  // Discount
  var discRow = document.getElementById('detail-discount-row');
  if (discRow) { discRow.style.display = (data.discount && data.discount !== '—' && data.discount !== '0%') ? '' : 'none'; }

  // Policies
  var polDiv = document.getElementById('detail-policies-list');
  if (polDiv) {
    if (data.policy_names && data.policy_names.length > 0) {
      var html = '';
      data.policy_names.forEach(function(name) { html += '<div class="mb-2"><span class="block font-bold text-xs text-amber-900">' + name + '</span></div>'; });
      if (data.policy_rules && data.policy_rules.length > 0) {
        html += '<div class="mt-1"><ul class="list-disc list-inside space-y-0.5 pr-1.5 text-[11px] text-amber-800 font-medium">';
        data.policy_rules.forEach(function(r) { html += '<li>' + r + '</li>'; });
        html += '</ul></div>';
      }
      polDiv.innerHTML = html;
    } else { polDiv.innerHTML = '<p class="text-xs text-amber-800 font-medium">لم يتم تحديد سياسة</p>'; }
  }

  // Coupons
  var cDiv = document.getElementById('detail-coupons-list');
  if (cDiv) {
    if (data.coupon_codes && data.coupon_codes.length > 0) {
      cDiv.innerHTML = '';
      data.coupon_codes.forEach(function(code) {
        var span = document.createElement('span');
        span.className = 'bg-purple-50 text-purple-800 border border-purple-100 font-mono font-black text-[11px] px-2.5 py-0.5 rounded-lg';
        span.textContent = code;
        cDiv.appendChild(span);
      });
    } else { cDiv.innerHTML = '<span class="text-slate-400 text-xs font-semibold">لا يسمح بتطبيق كوبونات</span>'; }
  }

  // Recurrence
  var rDiv = document.getElementById('detail-recurrence-info');
  if (rDiv) {
    if (data.recurrence_enabled === 'نعم') {
      var daysHtml = '';
      if (data.recurrence_days && data.recurrence_days.length > 0) {
        data.recurrence_days.forEach(function(d) { daysHtml += '<span class="bg-[#235b32]/10 text-[#235b32] text-[10px] font-bold px-2 py-0.5 rounded">' + d + '</span> '; });
      }
      rDiv.innerHTML = '<span class="text-emerald-700 font-bold text-[10px] block">✓ التكرار مفعّل — الأيام:</span><div class="flex flex-wrap gap-1 mt-1">' + daysHtml + '</div>' + (data.recurrence_end_date ? '<span class="text-[9px] text-slate-400 font-semibold block mt-1">ينتهي: ' + data.recurrence_end_date + '</span>' : '');
    } else { rDiv.innerHTML = '<span class="text-slate-400 text-[11px] font-semibold">رحلة فردية غير مكررة</span>'; }
  }

  // Conditions
  var condSection = document.getElementById('detail-conditions-section');
  if (condSection) { condSection.style.display = data.conditions && data.conditions.trim() ? '' : 'none'; }

  if (window.lucide) window.lucide.createIcons();
});
</script>
@endpush
