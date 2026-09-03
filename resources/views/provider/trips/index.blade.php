@extends('layouts.provider')

@section('title', 'أركاري | الرحلات')
@section('header_title', 'الرحلات')

@section('content')
@php
    $currentUser = auth()->user();
    $canCreate = $currentUser && $currentUser->hasPermission('provider:branches:trips:create');
    $canEdit   = $currentUser && $currentUser->hasPermission('provider:branches:trips:edit');
    $canDelete = $currentUser && $currentUser->hasPermission('provider:branches:trips:delete');
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== رأس الصفحة ===== --}}
  <x-page-header
      icon="bus"
      title="رحلات المزود"
      :count="$stats['total']"
      countLabel="رحلة"
      description="إدارة رحلات جميع الفروع"
      descriptionIcon="route"
  >
    @if($canCreate && $selectedBranch)
      @php
        $selectedBranchModel = \App\Models\ProviderBranch::find($selectedBranch);
      @endphp
      <button
          type="button"
          data-create-modal="trip-form-modal"
          data-branch-uuid="{{ $selectedBranchModel?->uuid ?? '' }}"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إضافة رحلة
      </button>
    @elseif($canCreate)
      <button
          type="button"
          disabled
          title="اختر فرعاً أولاً من الفلتر"
          class="px-5 py-2.5 bg-slate-200 text-slate-400 rounded-xl font-bold text-sm cursor-not-allowed flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إضافة رحلة
      </button>
    @endif
  </x-page-header>

  {{-- ===== منطقة التحديث ===== --}}
  <div id="aracari-refreshable" class="space-y-6">

  {{-- ===== بطاقات الإحصائيات ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 lg:gap-6">
    <x-stat-card :value="$stats['total']" label="إجمالي الرحلات" icon="bus" variant="slate" badge="رحلة" badgeIcon="bus" />
    <x-stat-card :value="$stats['upcoming']" label="رحلات قادمة" icon="clock" variant="emerald" badge="قادمة" badgeIcon="clock" />
    <x-stat-card :value="$stats['completed']" label="رحلات مكتملة" icon="check-circle" variant="amber" badge="مكتملة" badgeIcon="check-circle" />
  </div>

  {{-- ===== فلتر الفرع ===== --}}
  <div class="bg-white rounded-2xl border border-[#e2e2e5] p-4">
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
      <form method="GET" action="{{ route('admin.provider.trips.index') }}" class="flex items-center gap-2 flex-1">
        <div class="relative flex-1 max-w-xs">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="filter" class="w-4 h-4"></i>
          </div>
          <select
            name="branch"
            id="branch-filter"
            onchange="this.form.submit()"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all appearance-none cursor-pointer"
          >
            <option value="">كل الفروع</option>
            @foreach($branches as $branch)
              <option value="{{ $branch->id }}" data-uuid="{{ $branch->uuid }}" {{ $selectedBranch == $branch->id ? 'selected' : '' }}>
                {{ $branch->city?->name ?? 'فرع #' . $branch->id }}
              </option>
            @endforeach
          </select>
        </div>
        @if($selectedBranch)
          <a href="{{ route('admin.provider.trips.index') }}" class="text-[11px] text-rose-500 hover:text-rose-700 font-bold flex items-center gap-1 whitespace-nowrap">
            <i data-lucide="x" class="w-3 h-3"></i>
            إزالة الفلتر
          </a>
        @endif
      </form>
      @if($selectedBranch)
        <span class="text-[10px] font-bold bg-teal-100 text-teal-700 border border-teal-200 px-2.5 py-1 rounded-full whitespace-nowrap">
          <i data-lucide="store" class="w-3 h-3 inline ml-1"></i>
          فلتر: الفرع المحدد
        </span>
      @endif
    </div>
  </div>

  {{-- ===== جدول الرحلات ===== --}}
  <x-data-table-card :searchable="false" :footerInfo="'عرض ' . $trips->count() . ' من أصل ' . $trips->total() . ' سجل'">
    <x-slot name="thead">
      <th class="px-4 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المسار</th>
      <th class="px-4 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الفرع</th>
      <th class="px-4 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المركبة</th>
      <th class="px-4 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الانطلاق</th>
      <th class="px-4 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">السعر</th>
      <th class="px-4 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
      <th class="px-4 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">إجراءات</th>
    </x-slot>

    <x-slot name="tbody">
      @forelse($trips as $trip)
        @php
          $isUpcoming = $trip->departure_time && $trip->departure_time->isFuture();
          $statusColor = $isUpcoming ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200';
          $statusText = $isUpcoming ? 'قادمة' : 'مكتملة';
          $statusDot = $isUpcoming ? 'bg-emerald-500' : 'bg-slate-400';
          $tripBranch = $trip->service?->branch;
          $canEditThis = $canEdit && $isUpcoming;
          $canDeleteThis = $canDelete && $isUpcoming && !$trip->bookings()->exists();

          $editJson = json_encode([
            'uuid'               => $trip->uuid,
            'branch_uuid'        => $tripBranch?->uuid ?? '',
            'travel_date'        => $trip->departure_time?->format('Y-m-d'),
            'check_in_clock'     => $trip->check_in_time?->format('H:i'),
            'departure_clock'    => $trip->departure_time?->format('H:i'),
            'arrival_clock'      => $trip->arrival_time?->format('H:i'),
            'from_city_id'       => $trip->departureCity?->uuid ?? '',
            'to_city_id'         => $trip->destinationCity?->uuid ?? '',
            'vehicle_id'         => $trip->vehicle?->uuid ?? '',
            'type_of_trip_id'    => $trip->type_of_trip_id ?? '',
            'level_trip_id'      => $trip->level_trip_id ?? '',
            'trip_status_id'     => $trip->trip_status_id ?? '',
            'pickup_location'    => $trip->pickup_location ?? '',
            'arrival_location'   => $trip->arrival_location ?? '',
            'price_for_adult'    => $trip->price_for_adult,
            'price_for_child'    => $trip->price_for_child,
            'discount_percentage'=> $trip->discount_percentage ?? 0,
            'conditions'         => $trip->conditions ?? '',
            'is_public'          => $trip->is_public ?? true,
            'policy_id'          => $trip->policy_id ?? '',
            'amenities'          => $trip->amenities?->pluck('uuid')->toArray() ?? [],
            'coupons'            => $trip->coupons?->pluck('uuid')->toArray() ?? [],
            'is_recurring'       => false,
            'days'               => [],
            'end_date_repeat'    => '',
          ], JSON_UNESCAPED_UNICODE);
        @endphp
        <tr class="hover:bg-slate-50/50 transition-colors">
          {{-- المسار --}}
          <td class="px-4 py-4">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-lg bg-[#115128]/10 flex items-center justify-center flex-shrink-0">
                <i data-lucide="route" class="w-4 h-4 text-[#115128]"></i>
              </div>
              <div>
                <p class="text-[13px] font-bold text-slate-800">
                  {{ $trip->departureCity?->name ?? '—' }}
                  <i data-lucide="arrow-left" class="w-3 h-3 text-slate-300 inline mx-1"></i>
                  {{ $trip->destinationCity?->name ?? '—' }}
                </p>
                <p class="text-[10px] text-slate-400">{{ $trip->typeOfTrip?->type_name ?? '' }} {{ $trip->levelTrip?->level_name ?? '' }}</p>
              </div>
            </div>
          </td>
          {{-- الفرع --}}
          <td class="px-4 py-4">
            <span class="inline-flex items-center gap-1 text-[11px] font-bold bg-slate-100 text-slate-600 px-2 py-1 rounded-lg">
              <i data-lucide="store" class="w-3 h-3"></i>
              {{ $tripBranch?->city?->name ?? '—' }}
            </span>
          </td>
          {{-- المركبة --}}
          <td class="px-4 py-4">
            <span class="text-[12px] font-bold text-slate-600">{{ $trip->vehicle?->plate_number ?? '—' }}</span>
            <p class="text-[10px] text-slate-400">{{ $trip->vehicle?->vehicleType?->name ?? '' }}</p>
          </td>
          {{-- الانطلاق --}}
          <td class="px-4 py-4">
            <p class="text-[12px] font-bold text-slate-700">{{ $trip->departure_time?->format('d/m/Y') }}</p>
            <p class="text-[11px] text-slate-400">{{ $trip->departure_time?->format('h:i A') }}</p>
          </td>
          {{-- السعر --}}
          <td class="px-4 py-4">
            <span class="text-[13px] font-extrabold text-[#115128]">{{ number_format($trip->price_for_adult, 0) }}</span>
            <p class="text-[10px] text-slate-400">{{ $trip->currency_code ?? '' }}</p>
          </td>
          {{-- الحالة --}}
          <td class="px-4 py-4 text-center">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $statusColor }}">
              <span class="w-1.5 h-1.5 rounded-full {{ $statusDot }}"></span>
              {{ $statusText }}
            </span>
          </td>
          {{-- الإجراءات --}}
          <td class="px-4 py-4 text-center">
            <div class="flex items-center justify-center gap-1.5">
              @if($canEditThis)
                <x-action-button
                    variant="edit"
                    data-edit-modal="trip-form-modal"
                    data-edit-json="{{ $editJson }}"
                />
              @endif
              @if($canDeleteThis)
                <x-action-button
                    variant="delete"
                    data-delete-url="{{ route('admin.provider.branches.trips.destroy', [$tripBranch, $trip]) }}"
                    data-delete-label="{{ $trip->departureCity?->name . ' → ' . $trip->destinationCity?->name }}"
                />
              @endif
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state
            icon="bus"
            :title="$selectedBranch ? 'لا توجد رحلات في هذا الفرع' : 'لا توجد رحلات'"
            :message="$selectedBranch ? 'أضف رحلة جديدة لهذا الفرع' : 'اختر فرعاً وأضف رحلة جديدة'"
            :colspan="7"
        />
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$trips" />
    </x-slot>
  </x-data-table-card>

  </div>{{-- نهاية aracari-refreshable -->}

</div>

{{-- ================================================================== --}}
{{-- ===== مودال نموذج الرحلة (إنشاء + تعديل) ===== --}}
{{-- ================================================================== --}}
<x-form-modal
    id="trip-form-modal"
    title="إضافة رحلة جديدة"
    :variant="'create'"
    action="#"
    method="POST"
    successMessage="تم إنشاء الرحلة بنجاح"
>

  <div class="space-y-6">

    {{-- ===== القسم 1: التاريخ والوقت ===== --}}
    <div class="space-y-3">
      <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-gray-100 pb-2">
        <i data-lucide="calendar" class="w-4 h-4 text-teal-600"></i>
        <span>التاريخ والوقت</span>
      </h4>

      {{-- تاريخ الرحلة --}}
      <x-form-field name="travel_date" label="تاريخ الرحلة" icon="calendar" required>
        <input type="date" name="travel_date" id="tf_travel_date" required
          {{ old('travel_date') ? 'value="'.old('travel_date').'"' : '' }}
          min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-4 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all"
        >
      </x-form-field>

      {{-- الأوقات --}}
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-form-field name="check_in_clock" label="وقت الحضور" icon="clock" required>
          <input type="time" name="check_in_clock" id="tf_check_in_clock" required
            {{ old('check_in_clock') ? 'value="'.old('check_in_clock').'"' : '' }}
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-4 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all"
          >
        </x-form-field>
        <x-form-field name="departure_clock" label="وقت الانطلاق" icon="clock" required>
          <input type="time" name="departure_clock" id="tf_departure_clock" required
            {{ old('departure_clock') ? 'value="'.old('departure_clock').'"' : '' }}
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-4 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all"
          >
        </x-form-field>
        <x-form-field name="arrival_clock" label="وقت الوصول المتوقع" icon="clock" required>
          <input type="time" name="arrival_clock" id="tf_arrival_clock" required
            {{ old('arrival_clock') ? 'value="'.old('arrival_clock').'"' : '' }}
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-4 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all"
          >
        </x-form-field>
      </div>
    </div>

    {{-- ===== القسم 2: المسار والمركبة ===== --}}
    <div class="space-y-3">
      <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-gray-100 pb-2">
        <i data-lucide="route" class="w-4 h-4 text-teal-600"></i>
        <span>المسار والمركبة</span>
      </h4>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {{-- مدينة الانطلاق --}}
        <x-form-field name="from_city_id" label="مدينة الانطلاق" icon="map-pin" required>
          <div class="relative group">
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
              <i data-lucide="map-pin" class="w-4 h-4"></i>
            </div>
            <select name="from_city_id" id="tf_from_city" required
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all appearance-none cursor-pointer">
              <option value="">اختر مدينة الانطلاق</option>
              @foreach($cities as $city)
                <option value="{{ $city->uuid }}" {{ old('from_city_id') == $city->uuid ? 'selected' : '' }}>{{ $city->name }}</option>
              @endforeach
            </select>
            <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
              <i data-lucide="chevron-down" class="w-4 h-4"></i>
            </div>
          </div>
        </x-form-field>

        {{-- مدينة الوصول --}}
        <x-form-field name="to_city_id" label="مدينة الوصول" icon="map-pin" required>
          <div class="relative group">
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
              <i data-lucide="map-pin" class="w-4 h-4"></i>
            </div>
            <select name="to_city_id" id="tf_to_city" required
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all appearance-none cursor-pointer">
              <option value="">اختر مدينة الوصول</option>
              @foreach($cities as $city)
                <option value="{{ $city->uuid }}" {{ old('to_city_id') == $city->uuid ? 'selected' : '' }}>{{ $city->name }}</option>
              @endforeach
            </select>
            <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
              <i data-lucide="chevron-down" class="w-4 h-4"></i>
            </div>
          </div>
        </x-form-field>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- المركبة --}}
        <x-form-field name="vehicle_id" label="المركبة" icon="car" required>
          <div class="relative group">
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
              <i data-lucide="car" class="w-4 h-4"></i>
            </div>
            <select name="vehicle_id" id="tf_vehicle" required
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all appearance-none cursor-pointer">
              <option value="">اختر المركبة</option>
              @foreach($vehicles as $v)
                <option value="{{ $v->uuid }}" {{ old('vehicle_id') == $v->uuid ? 'selected' : '' }}>{{ $v->plate_number }} — {{ $v->vehicleType?->name ?? '' }}</option>
              @endforeach
            </select>
            <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
              <i data-lucide="chevron-down" class="w-4 h-4"></i>
            </div>
          </div>
        </x-form-field>

        {{-- نوع الرحلة --}}
        <x-form-field name="type_of_trip_id" label="نوع الرحلة" icon="layers" required>
          <div class="relative group">
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
              <i data-lucide="layers" class="w-4 h-4"></i>
            </div>
            <select name="type_of_trip_id" id="tf_type" required
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all appearance-none cursor-pointer">
              <option value="">اختر النوع</option>
              @foreach($typeOfTrips as $t)
                <option value="{{ $t->id }}" {{ old('type_of_trip_id') == $t->id ? 'selected' : '' }}>{{ $t->type_name }}</option>
              @endforeach
            </select>
            <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
              <i data-lucide="chevron-down" class="w-4 h-4"></i>
            </div>
          </div>
        </x-form-field>

        {{-- مستوى الرحلة --}}
        <x-form-field name="level_trip_id" label="مستوى الرحلة" icon="star" required>
          <div class="relative group">
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
              <i data-lucide="star" class="w-4 h-4"></i>
            </div>
            <select name="level_trip_id" id="tf_level" required
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all appearance-none cursor-pointer">
              <option value="">اختر المستوى</option>
              @foreach($levelTrips as $l)
                <option value="{{ $l->id }}" {{ old('level_trip_id') == $l->id ? 'selected' : '' }}>{{ $l->level_name }}</option>
              @endforeach
            </select>
            <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
              <i data-lucide="chevron-down" class="w-4 h-4"></i>
            </div>
          </div>
        </x-form-field>
      </div>
    </div>

    {{-- ===== القسم 3: مواقع الانطلاق والوصول ===== --}}
    <div class="space-y-3">
      <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-gray-100 pb-2">
        <i data-lucide="navigation" class="w-4 h-4 text-teal-600"></i>
        <span>مواقع الانطلاق والوصول</span>
      </h4>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-form-field name="pickup_location" label="موقع الانطلاق" icon="map-pin" required>
          <div class="relative group">
            <div class="absolute top-3 right-3 pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
              <i data-lucide="map-pin" class="w-4 h-4"></i>
            </div>
            <input type="text" name="pickup_location" id="tf_pickup" required placeholder="مثال: محطة الحافلات الرئيسية"
              value="{{ old('pickup_location') }}"
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all"
            >
          </div>
        </x-form-field>
        <x-form-field name="arrival_location" label="موقع الوصول" icon="flag" required>
          <div class="relative group">
            <div class="absolute top-3 right-3 pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
              <i data-lucide="flag" class="w-4 h-4"></i>
            </div>
            <input type="text" name="arrival_location" id="tf_arrival" required placeholder="مثال: ساحة المدينة"
              value="{{ old('arrival_location') }}"
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all"
            >
          </div>
        </x-form-field>
      </div>
    </div>

    {{-- ===== القسم 4: التسعير ===== --}}
    <div class="space-y-3">
      <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-gray-100 pb-2">
        <i data-lucide="banknote" class="w-4 h-4 text-teal-600"></i>
        <span>التسعير</span>
      </h4>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-form-field name="price_for_adult" label="سعر البالغ" icon="banknote" required>
          <input type="number" name="price_for_adult" id="tf_adult_price" required min="0" step="0.01"
            value="{{ old('price_for_adult') }}"
            placeholder="0.00" dir="ltr"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-4 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left"
          >
        </x-form-field>
        <x-form-field name="price_for_child" label="سعر الطفل" icon="banknote" required>
          <input type="number" name="price_for_child" id="tf_child_price" required min="0" step="0.01"
            value="{{ old('price_for_child') }}"
            placeholder="0.00" dir="ltr"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-4 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left"
          >
        </x-form-field>
        <x-form-field name="discount_percentage" label="نسبة الخصم %" icon="percent">
          <input type="number" name="discount_percentage" id="tf_discount" min="0" max="100" step="0.1"
            value="{{ old('discount_percentage', 0) }}"
            placeholder="0" dir="ltr"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-4 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left"
          >
        </x-form-field>
      </div>
    </div>

    {{-- ===== القسم 5: خيارات إضافية ===== --}}
    <div class="space-y-3">
      <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-gray-100 pb-2">
        <i data-lucide="settings" class="w-4 h-4 text-teal-600"></i>
        <span>خيارات إضافية</span>
      </h4>

      {{-- حالة الرحلة (مخفية في الإنشاء، ظاهرة في التعديل) --}}
      <div id="tf_status_wrapper" class="hidden">
        <div class="relative group">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
            <i data-lucide="circle-dot" class="w-4 h-4"></i>
          </div>
          <select name="trip_status_id" id="tf_status"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all appearance-none cursor-pointer">
            @foreach($tripStatuses as $ts)
              <option value="{{ $ts->id }}">{{ $ts->status_name }}</option>
            @endforeach
          </select>
          <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="chevron-down" class="w-4 h-4"></i>
          </div>
        </div>
      </div>

      {{-- رحلة عامة --}}
      <label class="flex items-center gap-3 p-3 bg-[#f9f9fc] rounded-xl border border-[#eeeef0] cursor-pointer hover:border-teal-200 transition-colors">
        <input type="checkbox" name="is_public" id="tf_is_public" value="1" checked
          class="w-4 h-4 text-teal-600 border-slate-300 rounded focus:ring-teal-500">
        <div>
          <span class="text-[13px] font-bold text-slate-700">رحلة عامة</span>
          <p class="text-[10px] text-slate-400">تظهر للعملاء في البحث والتصفح</p>
        </div>
      </label>

      {{-- الشروط --}}
      <div>
        <label class="block text-[11px] font-bold text-slate-600 mb-1.5 flex items-center gap-1">
          <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
          شروط وأحكام (اختياري)
        </label>
        <textarea name="conditions" id="tf_conditions" rows="2" placeholder="أي شروط خاصة بالرحلة..."
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-4 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all resize-none leading-relaxed"
        >{{ old('conditions') }}</textarea>
      </div>

      {{-- الخدمات الإضافية (Amenities) --}}
      @if($amenities->isNotEmpty())
      <div>
        <label class="block text-[11px] font-bold text-slate-600 mb-2 flex items-center gap-1">
          <i data-lucide="wifi" class="w-3.5 h-3.5"></i>
          الخدمات الإضافية (اختياري)
        </label>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
          @foreach($amenities as $amenity)
            <label class="flex items-center gap-2 p-2.5 bg-white rounded-xl border border-[#e2e2e5] cursor-pointer hover:border-teal-300 hover:bg-teal-50/30 transition-all text-[12px]">
              <input type="checkbox" name="amenities[]" value="{{ $amenity->uuid }}"
                class="w-3.5 h-3.5 text-teal-600 border-slate-300 rounded focus:ring-teal-500">
              <span class="font-bold text-slate-600">{{ $amenity->name }}</span>
            </label>
          @endforeach
        </div>
      </div>
      @endif

      {{-- سياسة الإلغاء + الكوبونات --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {{-- سياسة الإلغاء --}}
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1.5 flex items-center gap-1">
            <i data-lucide="shield" class="w-3.5 h-3.5"></i>
            سياسة الإلغاء (اختياري)
          </label>
          <div class="relative group">
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
              <i data-lucide="shield" class="w-4 h-4"></i>
            </div>
            <select name="policy_id" id="tf_policy"
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all appearance-none cursor-pointer">
              <option value="">بدون سياسة</option>
              @foreach($policies as $pol)
                <option value="{{ $pol->uuid }}" {{ old('policy_id') == $pol->uuid ? 'selected' : '' }}>{{ $pol->name }}</option>
              @endforeach
            </select>
            <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
              <i data-lucide="chevron-down" class="w-4 h-4"></i>
            </div>
          </div>
        </div>

        {{-- الكوبونات --}}
        @if($coupons->isNotEmpty())
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1.5 flex items-center gap-1">
            <i data-lucide="ticket" class="w-3.5 h-3.5"></i>
            الكوبونات (اختياري)
          </label>
          <select name="coupons[]" id="tf_coupons" multiple
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-4 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all">
            @foreach($coupons as $coupon)
              <option value="{{ $coupon->uuid }}">{{ $coupon->code }} — {{ $coupon->discount_percentage }}%</option>
            @endforeach
          </select>
          <p class="text-[10px] text-slate-400 mt-1">اضغط Ctrl+Click لاختيار أكثر من كوبون</p>
        </div>
        @endif
      </div>
    </div>

    {{-- ===== القسم 6: تكرار الرحلة ===== --}}
    <div class="space-y-3">
      <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-gray-100 pb-2">
        <i data-lucide="repeat" class="w-4 h-4 text-teal-600"></i>
        <span>تكرار الرحلة (اختياري)</span>
      </h4>

      <label class="flex items-center gap-3 p-3 bg-[#f9f9fc] rounded-xl border border-[#eeeef0] cursor-pointer hover:border-teal-200 transition-colors">
        <input type="checkbox" name="is_recurring" id="tf_is_recurring" value="1"
          class="w-4 h-4 text-teal-600 border-slate-300 rounded focus:ring-teal-500"
          onchange="document.getElementById('recurring-section').classList.toggle('hidden', !this.checked)">
        <div>
          <span class="text-[13px] font-bold text-slate-700">رحلة متكررة</span>
          <p class="text-[10px] text-slate-400">تكرار الرحلة في أيام محددة تلقائياً</p>
        </div>
      </label>

      <div id="recurring-section" class="hidden space-y-3">
        {{-- أيام التكرار --}}
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-2">أيام التكرار</label>
          <div class="flex flex-wrap gap-2">
            @foreach($weekDays as $dayKey => $dayLabel)
              <label class="flex items-center gap-1.5 px-3 py-2 bg-white rounded-xl border border-[#e2e2e5] cursor-pointer hover:border-teal-300 transition-all text-[12px] font-bold text-slate-600">
                <input type="checkbox" name="days[]" value="{{ $dayKey }}"
                  class="w-3.5 h-3.5 text-teal-600 border-slate-300 rounded focus:ring-teal-500">
                {{ $dayLabel }}
              </label>
            @endforeach
          </div>
        </div>
        {{-- تاريخ التوقف --}}
        <div>
          <label class="block text-[11px] font-bold text-slate-600 mb-1.5 flex items-center gap-1">
            <i data-lucide="calendar-x" class="w-3.5 h-3.5"></i>
            تاريخ توقف التكرار (اختياري)
          </label>
          <input type="date" name="end_date_repeat" id="tf_end_date_repeat"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-4 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all"
          >
        </div>
      </div>
    </div>

    {{-- تنبيه إنشاء --}}
    <div id="tf_create_hint" class="bg-teal-50/70 border border-teal-200 rounded-xl p-3">
      <div class="flex items-start gap-2.5">
        <i data-lucide="info" class="w-4 h-4 text-teal-500 flex-shrink-0 mt-0.5"></i>
        <span class="text-[11px] text-teal-600">الرحلة ستُضاف تلقائياً للفرع المحدد في الفلتر.</span>
      </div>
    </div>

  </div>
</x-form-modal>

@endsection

@push('scripts')
<script>
  // ===== خريطة الفروع: id → uuid =====
  var branchIdToUuid = {
    @foreach($branches as $b)
      '{{ $b->id }}': '{{ $b->uuid }}',
    @endforeach
  };

  // ===== المسار الأساسي للمودال =====
  var tripBaseUrl = '{{ route('admin.provider.branches.trips.store', '__BRANCH_UUID__') }}';
  var tripUpdateBase = '{{ route('admin.provider.branches.trips.update', ['__BRANCH_UUID__', '__TRIP_UUID__']) }}';

  // ===== عند فتح المودال: إنشاء أو تعديل =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'trip-form-modal') return;

    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.uuid);

    // تحديد الفرع
    var branchUuid = data.branch_uuid || '';
    if (!isEdit) {
      var filterSelect = document.getElementById('branch-filter');
      if (filterSelect && filterSelect.value) {
        branchUuid = branchIdToUuid[filterSelect.value] || '';
      }
      // أيضاً من data-branch-uuid على الزر
      if (!branchUuid) {
        var createBtn = document.querySelector('[data-create-modal="trip-form-modal"]');
        if (createBtn) branchUuid = createBtn.getAttribute('data-branch-uuid') || '';
      }
    }

    if (isEdit) {
      // ===== وضع التعديل =====
      var updateUrl = tripUpdateBase.replace('__BRANCH_UUID__', branchUuid).replace('__TRIP_UUID__', data.uuid);
      form.setAttribute('action', updateUrl);

      var existingMethod = form.querySelector('input[name="_method"]');
      if (existingMethod) existingMethod.value = 'PUT';
      else {
        var m = document.createElement('input');
        m.type = 'hidden'; m.name = '_method'; m.value = 'PUT';
        m.setAttribute('data-dynamic', 'true');
        form.appendChild(m);
      }

      // شريط تعديل (عنبري)
      var banner = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (banner) banner.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';
      var titleEl = modal.querySelector('.aracari-modal-panel h2');
      if (titleEl) titleEl.innerHTML = '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل الرحلة';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) { badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30'; badge.textContent = 'تحديث'; }
      var subtitle = modal.querySelector('.aracari-modal-panel p');
      if (subtitle) subtitle.textContent = 'تعديل بيانات الرحلة';
      form.setAttribute('data-success-message', 'تم تحديث الرحلة بنجاح');

      // إظهار حالة الرحلة في التعديل
      document.getElementById('tf_status_wrapper').classList.remove('hidden');
      document.getElementById('tf_create_hint').classList.add('hidden');

    } else {
      // ===== وضع الإنشاء =====
      var storeUrl = tripBaseUrl.replace('__BRANCH_UUID__', branchUuid);
      form.setAttribute('action', storeUrl);
      var m = form.querySelector('input[name="_method"]');
      if (m) m.remove();

      var banner = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (banner) banner.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
      var titleEl = modal.querySelector('.aracari-modal-panel h2');
      if (titleEl) titleEl.innerHTML = '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة رحلة جديدة';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) { badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30'; badge.textContent = 'جديد'; }
      var subtitle = modal.querySelector('.aracari-modal-panel p');
      if (subtitle) subtitle.textContent = 'إنشاء رحلة جديدة للفرع المحدد';
      form.setAttribute('data-success-message', 'تم إنشاء الرحلة بنجاح');

      // إخفاء حالة الرحلة في الإنشاء
      document.getElementById('tf_status_wrapper').classList.add('hidden');
      document.getElementById('tf_create_hint').classList.remove('hidden');
    }

    // ===== ملء الحقول في التعديل =====
    var fields = {
      'tf_travel_date':     data.travel_date || '',
      'tf_check_in_clock':  data.check_in_clock || '',
      'tf_departure_clock': data.departure_clock || '',
      'tf_arrival_clock':   data.arrival_clock || '',
      'tf_from_city':       data.from_city_id || '',
      'tf_to_city':         data.to_city_id || '',
      'tf_vehicle':         data.vehicle_id || '',
      'tf_type':            data.type_of_trip_id || '',
      'tf_level':           data.level_trip_id || '',
      'tf_status':          data.trip_status_id || '1',
      'tf_pickup':          data.pickup_location || '',
      'tf_arrival':         data.arrival_location || '',
      'tf_adult_price':     data.price_for_adult || '',
      'tf_child_price':     data.price_for_child || '',
      'tf_discount':        data.discount_percentage || '0',
      'tf_conditions':      data.conditions || '',
      'tf_policy':          data.policy_id || '',
    };

    for (var id in fields) {
 var el = document.getElementById(id);
      if (el) el.value = fields[id];
    }

    // checkbox is_public
    var pubEl = document.getElementById('tf_is_public');
    if (pubEl) pubEl.checked = data.is_public !== false && data.is_public !== 0;

    // amenities checkboxes
    var amenityChecks = form.querySelectorAll('input[name="amenities[]"]');
    var editAmenities = data.amenities || [];
    amenityChecks.forEach(function(cb) {
      cb.checked = editAmenities.indexOf(cb.value) !== -1;
    });

    // coupons multi-select
    var couponSelect = document.getElementById('tf_coupons');
    if (couponSelect) {
      var editCoupons = data.coupons || [];
      Array.from(couponSelect.options).forEach(function(opt) {
        opt.selected = editCoupons.indexOf(opt.value) !== -1;
      });
    }

    // recurring
    var recurCb = document.getElementById('tf_is_recurring');
    var recurSection = document.getElementById('recurring-section');
    if (recurCb && recurSection) {
      var isRecurring = data.is_recurring || false;
      recurCb.checked = isRecurring;
      recurSection.classList.toggle('hidden', !isRecurring);

      // days checkboxes
      var dayChecks = form.querySelectorAll('input[name="days[]"]');
      var editDays = data.days || [];
      dayChecks.forEach(function(cb) {
        cb.checked = editDays.indexOf(cb.value) !== -1;
      });

      // end date
      var endEl = document.getElementById('tf_end_date_repeat');
      if (endEl) endEl.value = data.end_date_repeat || '';
    }

    // في حالة الإنشاء: مسح الحقول
    if (!isEdit) {
      for (var id in fields) {
        var el = document.getElementById(id);
        if (el && ['tf_travel_date','tf_check_in_clock','tf_departure_clock','tf_arrival_clock','tf_from_city','tf_to_city','tf_vehicle','tf_type','tf_level','tf_pickup','tf_arrival','tf_adult_price','tf_child_price'].indexOf(id) !== -1) {
          el.value = '';
        }
      }
      var discEl = document.getElementById('tf_discount');
      if (discEl) discEl.value = '0';
      var condEl = document.getElementById('tf_conditions');
      if (condEl) condEl.value = '';
      var policyEl = document.getElementById('tf_policy');
      if (policyEl) policyEl.value = '';
      if (pubEl) pubEl.checked = true;
      amenityChecks.forEach(function(cb) { cb.checked = false; });
      if (couponSelect) Array.from(couponSelect.options).forEach(function(o) { o.selected = false; });
      if (recurCb) recurCb.checked = false;
      if (recurSection) recurSection.classList.add('hidden');
      var dayChecks2 = form.querySelectorAll('input[name="days[]"]');
      dayChecks2.forEach(function(cb) { cb.checked = false; });
      var endEl2 = document.getElementById('tf_end_date_repeat');
      if (endEl2) endEl2.value = '';
    }

    // إعادة تصيير أيقونات لوسيد
    if (window.lucide) window.lucide.createIcons();
  });
</script>
@endpush
