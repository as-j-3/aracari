@extends('layouts.admin')

@section('title', 'أركاري | المسافرون')
@section('header_title', 'إدارة المسافرين')

@section('content')

@php
    $user      = auth()->user();
    $canCreate = $user && $user->hasPermission('passengers:create');
    $canEdit   = $user && $user->hasPermission('passengers:edit');
    $canDelete = $user && $user->hasPermission('passengers:delete');

    // إحصائيات سريعة
    $totalCount   = $passengers->total();
    $malesCount   = 0;
    $femalesCount = 0;
    $ageSum       = 0;
    $ageCount     = 0;
    foreach ($passengers->items() as $p) {
        $genderName = $p->gender ? mb_strtolower($p->gender->name) : '';
        if (mb_strpos($genderName, 'ذك') !== false) $malesCount++;
        elseif (mb_strpos($genderName, 'أنث') !== false) $femalesCount++;
        $ageSum += ($p->age ?? 0);
        $ageCount++;
    }
    $averageAge = $ageCount > 0 ? round($ageSum / $ageCount, 1) : 0;

    // فلاتر مفعّلة
    $activeGender = request('gender_id');
    $activeMinAge = request('min_age');
    $activeMaxAge = request('max_age');
    $hasFilters = $activeGender || $activeMinAge || $activeMaxAge;

    // وظيفة مساعدة لألوان الجنس
    $genderColor = function($genderName) {
        $name = mb_strtolower($genderName ?? '');
        if (mb_strpos($name, 'ذك') !== false) return ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200'];
        if (mb_strpos($name, 'أنث') !== false) return ['bg' => 'bg-pink-50', 'text' => 'text-pink-700', 'border' => 'border-pink-200'];
        return ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'border' => 'border-slate-200'];
    };

    // قائمة رموز الدول الشائعة (للحقل المنسدل)
    $commonCountryCodes = [
        '966' => '🇸🇦 السعودية +966',
        '971' => '🇦🇪 الإمارات +971',
        '965' => '🇰🇼 الكويت +965',
        '974' => '🇶🇦 قطر +974',
        '968' => '🇴🇲 عُمان +968',
        '973' => '🇧🇭 البحرين +973',
        '970' => '🇵🇸 فلسطين +970',
        '962' => '🇯🇴 الأردن +962',
        '961' => '🇱🇧 لبنان +961',
        '964' => '🇮🇶 العراق +964',
        '963' => '🇸🇾 سوريا +963',
        '20'  => '🇪🇬 مصر +20',
        '212' => '🇲🇦 المغرب +212',
        '216' => '🇹🇳 تونس +216',
        '213' => '🇩🇿 الجزائر +213',
        '218' => '🇱🇾 ليبيا +218',
        '249' => '🇸🇩 السودان +249',
        '967' => '🇾🇪 اليمن +967',
        '251' => '🇪🇹 إثيوبيا +251',
        '90'  => '🇹🇷 تركيا +90',
    ];
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="users"
      title="إدارة المسافرين"
      :count="$totalCount"
      countLabel="مسافر"
      description="قاعدة بيانات المسافرين المسجلين في النظام مع بطاقاتهم الشخصية وجوازات السفر"
      descriptionIcon="credit-card"
  >
    @if($canCreate)
      <button
          type="button"
          data-create-modal="passenger-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-slate-800 to-slate-900 text-white rounded-xl font-bold text-sm shadow-lg shadow-slate-900/20 hover:shadow-slate-900/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="user-plus" class="w-4 h-4"></i>
        إضافة مسافر جديد
      </button>
    @endif
  </x-page-header>

  {{-- ===== Stat Cards (4) ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    {{-- إجمالي المسافرين --}}
    <x-stat-card
        icon="users"
        :label="'إجمالي المسافرين'"
        :value="$totalCount"
        :gradient="'from-slate-900 to-slate-800'"
        :accentColor="'blue'"
    />
    {{-- ذكور --}}
    <x-stat-card
        icon="user"
        :label="'ذكور'"
        :value="$malesCount"
        :gradient="'from-blue-900/90 to-blue-800/90'"
        :accentColor="'blue'"
        :badge="'ذكر'"
    />
    {{-- إناث --}}
    <x-stat-card
        icon="user"
        :label="'إناث'"
        :value="$femalesCount"
        :gradient="'from-pink-900/90 to-pink-800/90'"
        :accentColor="'pink'"
        :badge="'أنثى'"
    />
    {{-- متوسط العمر --}}
    <x-stat-card
        icon="bar-chart-3"
        :label="'متوسط العمر'"
        :value="$averageAge . ' سنة'"
        :gradient="'from-amber-900/90 to-amber-800/90'"
        :accentColor="'amber'"
        :isMono="true"
    />
  </div>

  {{-- ===== Refreshable region ===== --}}
  <div id="aracari-refreshable" class="space-y-6">

    {{-- ===== Data Table Card ===== --}}
    <x-data-table-card
        :searchable="true"
        :searchMode="'server'"
        searchPlaceholder="ابحث بالاسم، رقم الهاتف، رقم البطاقة أو الجواز..."
        :footerInfo="'عرض ' . $passengers->count() . ' من أصل ' . $passengers->total() . ' مسافر'"
    >
      {{-- فلتر الجنس والعمر --}}
      <x-slot name="filters">
        <div class="flex items-center gap-3 flex-wrap">
          {{-- فلتر الجنس --}}
          <select
              id="passenger-gender-filter"
              onchange="window.location.href = '{{ request()->url() }}' + buildPassengerFilters(this.value)"
              class="h-11 bg-white/70 border border-slate-200 rounded-xl px-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition-all appearance-none cursor-pointer min-w-[140px]"
          >
            <option value="">الجنس</option>
            @foreach($genders as $g)
              <option value="{{ $g->id }}" {{ (string) $activeGender === (string) $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
            @endforeach
          </select>

          {{-- فلتر العمر الأدنى --}}
          <input
              type="number"
              id="passenger-min-age"
              placeholder="العمر من"
              min="0"
              max="120"
              value="{{ $activeMinAge }}"
              dir="ltr"
              class="h-11 w-24 bg-white/70 border border-slate-200 rounded-xl px-3 text-sm text-slate-700 font-mono focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition-all text-center"
              onchange="window.location.href = '{{ request()->url() }}' + buildPassengerFilters(null, this.value)"
          >

          {{-- فلتر العمر الأقصى --}}
          <input
              type="number"
              id="passenger-max-age"
              placeholder="إلى"
              min="0"
              max="120"
              value="{{ $activeMaxAge }}"
              dir="ltr"
              class="h-11 w-24 bg-white/70 border border-slate-200 rounded-xl px-3 text-sm text-slate-700 font-mono focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition-all text-center"
              onchange="window.location.href = '{{ request()->url() }}' + buildPassengerFilters(null, null, this.value)"
          >

          @if($hasFilters)
            <a
                href="{{ request()->url() }}"
                class="h-11 px-4 bg-rose-50/80 border border-rose-200 text-rose-600 rounded-xl font-bold text-sm hover:bg-rose-500 hover:text-white hover:border-rose-500 transition-all flex items-center gap-1.5 flex-shrink-0"
            >
              <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
              <span class="hidden sm:inline">إعادة تعيين</span>
            </a>
          @endif
        </div>
      </x-slot>

      <x-slot name="thead">
        <th class="px-5 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider w-12">#</th>
        <th class="px-5 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الاسم ورقم الهاتف</th>
        <th class="px-5 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الجنس</th>
        <th class="px-5 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">العمر</th>
        <th class="px-5 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">رقم البطاقة</th>
        <th class="px-5 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">رقم الجواز</th>
        <th class="px-5 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحجوزات</th>
        <th class="px-5 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
      </x-slot>

      <x-slot name="rows">
        @forelse($passengers as $index => $passenger)
          @php
              $gc = $genderColor($passenger->gender ? $passenger->gender->name : null);
          @endphp
          <tr class="hover:bg-slate-50/50 transition-colors group">
            {{-- # --}}
            <td class="px-5 py-4">
              <span class="text-xs font-bold text-slate-400">{{ $passengers->firstItem() + $index }}</span>
            </td>

            {{-- الاسم + الهاتف --}}
            <td data-searchable class="px-5 py-4">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-slate-800 to-slate-900 flex items-center justify-center shadow-md flex-shrink-0">
                  <i data-lucide="user" class="w-4 h-4 text-white"></i>
                </div>
                <div class="min-w-0">
                  <p class="font-bold text-slate-800 text-sm truncate max-w-[200px]">{{ $passenger->full_name }}</p>
                  <p class="text-[11px] text-slate-400 font-mono mt-0.5" dir="ltr">{{ $passenger->phone_number }}</p>
                </div>
              </div>
            </td>

            {{-- الجنس --}}
            <td class="px-5 py-4 text-center">
              @if($passenger->gender)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $gc['bg'] }} {{ $gc['text'] }} {{ $gc['border'] }}">
                  {{ $passenger->gender->name }}
                </span>
              @else
                <span class="text-[11px] text-slate-400">—</span>
              @endif
            </td>

            {{-- العمر --}}
            <td class="px-5 py-4 text-center">
              <span class="text-sm font-black text-slate-700 font-mono">{{ $passenger->age }}</span>
              <span class="text-[10px] text-slate-400 mr-0.5">سنة</span>
            </td>

            {{-- رقم البطاقة --}}
            <td data-searchable class="px-5 py-4">
              @if($passenger->personal_card_id)
                <span class="font-mono text-xs font-bold text-slate-600 tracking-wide" dir="ltr">{{ $passenger->personal_card_id }}</span>
              @else
                <span class="text-xs text-slate-300">—</span>
              @endif
            </td>

            {{-- رقم الجواز --}}
            <td data-searchable class="px-5 py-4">
              @if($passenger->passport_number)
                <span class="font-mono text-xs font-bold text-slate-600 tracking-wide" dir="ltr">{{ $passenger->passport_number }}</span>
              @else
                <span class="text-xs text-slate-300">—</span>
              @endif
            </td>

            {{-- الحجوزات --}}
            <td class="px-5 py-4 text-center">
              <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-50 border border-slate-200">
                <i data-lucide="ticket" class="w-3 h-3 text-slate-400"></i>
                <span class="text-xs font-bold text-slate-700">{{ $passenger->trip_reservations_count }}</span>
              </div>
            </td>

            {{-- الإجراءات --}}
            <td class="px-5 py-4 text-center">
              <div class="flex items-center justify-center gap-1.5">
                @if($canEdit)
                  <x-action-button
                      variant="edit"
                      data-edit-modal="passenger-form-modal"
                      data-edit-json="{{ json_encode([
                          'uuid'                          => $passenger->uuid,
                          'full_name'                     => $passenger->full_name,
                          'gender_id'                     => $passenger->gender_id,
                          'birthday'                      => $passenger->birthday ? $passenger->birthday->format('Y-m-d') : '',
                          'phone_number'                  => $passenger->phone_number,
                          'country_code'                  => '',
                          'personal_card_id'              => $passenger->personal_card_id,
                          'personal_card_place_of_issue'  => $passenger->personal_card_place_of_issue,
                          'personal_card_date_issu'       => $passenger->personal_card_date_issu ? $passenger->personal_card_date_issu->format('Y-m-d') : '',
                          'personal_card_date_expiry'     => $passenger->personal_card_date_expiry ? $passenger->personal_card_date_expiry->format('Y-m-d') : '',
                          'passport_number'               => $passenger->passport_number,
                          'passport_place_of_issue'       => $passenger->passport_place_of_issue,
                          'passport_date_issu'            => $passenger->passport_date_issu ? $passenger->passport_date_issu->format('Y-m-d') : '',
                          'passport_date_expiry'          => $passenger->passport_date_expiry ? $passenger->passport_date_expiry->format('Y-m-d') : '',
                          'existing_card_photo_url'       => $passenger->personal_card_id_photo_url,
                          'existing_passport_photo_url'   => $passenger->passport_photo_url,
                          'age'                           => $passenger->age,
                          'reservations_count'            => $passenger->trip_reservations_count,
                      ], JSON_UNESCAPED_UNICODE) }}"
                      :ariaLabel="'تعديل ' . $passenger->full_name"
                  />
                @endif
                @if($canDelete)
                  <x-action-button
                      variant="delete"
                      data-delete-url="{{ route('admin.passengers.destroy', $passenger) }}"
                      data-delete-label="$passenger->full_name"
                  />
                @endif
              </div>
            </td>
          </tr>
        @empty
          <x-empty-state
              icon="users"
              :title="request('search') ? 'لا توجد نتائج مطابقة' : 'لا يوجد مسافرون مسجلون'"
              :message="request('search') ? 'جرّب تعديل كلمات البحث أو الفلاتر' : 'ابدأ بإضافة المسافرين الأولين إلى النظام'"
              :colspan="8"
          >
            @if(!request('search') && !$hasFilters && $canCreate)
              <button
                  type="button"
                  data-create-modal="passenger-form-modal"
                  class="inline-flex items-center gap-2 bg-slate-800 hover:bg-slate-900 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
              >
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                إضافة مسافر جديد
              </button>
            @endif
            @if(request('search') || $hasFilters)
              <a href="{{ request()->url() }}" class="mt-3 text-blue-600 hover:text-blue-700 inline-flex items-center gap-1 font-bold text-sm">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                إعادة تعيين الفلاتر
              </a>
            @endif
          </x-empty-state>
        @endforelse
      </x-slot>

      <x-slot name="footer">
        <x-pagination :paginator="$passengers" />
      </x-slot>
    </x-data-table-card>

  </div>{{-- end #aracari-refreshable -->}

</div>

{{-- ===== Passenger Form Modal (Create + Edit) ===== --}}
<x-form-modal
    id="passenger-form-modal"
    title="إضافة مسافر جديد"
    :variant="'create'"
    :action="route('admin.passengers.store')"
    method="POST"
    successMessage="تم حفظ المسافر بنجاح"
    maxWidth="max-w-2xl"
>

  {{-- القسم 1: المعلومات الشخصية --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs text-blue-600 flex items-center gap-1.5">
        <i data-lucide="user" class="w-3.5 h-3.5"></i>
        المعلومات الشخصية
      </h4>
      <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border text-rose-600 bg-rose-50 border-rose-200" id="pax-info-badge">مطلوب</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      {{-- الاسم الكامل --}}
      <div class="md:col-span-2">
        <label class="block text-xs font-bold text-slate-600 mb-1.5">الاسم الكامل <span class="text-rose-500">*</span></label>
        <div class="relative group">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-600 transition-colors">
            <i data-lucide="user" class="w-4 h-4"></i>
          </div>
          <input
              type="text"
              name="full_name"
              id="pax-full-name"
              required
              maxlength="255"
              value="{{ old('full_name') }}"
              placeholder="أدخل الاسم الكامل للمسافر"
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all"
          >
        </div>
      </div>

      {{-- الجنس --}}
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1.5">الجنس</label>
        <select
            name="gender_id"
            id="pax-gender"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all appearance-none cursor-pointer"
        >
          <option value="">اختر الجنس...</option>
          @foreach($genders as $g)
            <option value="{{ $g->id }}" {{ old('gender_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
          @endforeach
        </select>
      </div>

      {{-- تاريخ الميلاد --}}
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1.5">تاريخ الميلاد <span class="text-rose-500">*</span></label>
        <div class="relative group">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-600 transition-colors">
            <i data-lucide="calendar" class="w-4 h-4"></i>
          </div>
          <input
              type="date"
              name="birthday"
              id="pax-birthday"
              required
              value="{{ old('birthday') }}"
              max="{{ now()->format('Y-m-d') }}"
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold font-mono outline-none focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all"
          >
        </div>
      </div>

      {{-- رمز الدولة + رقم الهاتف --}}
      <div class="md:col-span-2">
        <label class="block text-xs font-bold text-slate-600 mb-1.5">رقم الهاتف <span class="text-rose-500">*</span></label>
        <div class="flex gap-2">
          <select
              name="country_code"
              id="pax-country-code"
              class="w-44 bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-2 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all appearance-none cursor-pointer"
          >
            <option value="">رمز الدولة</option>
            @foreach($commonCountryCodes as $code => $label)
              <option value="{{ $code }}" {{ old('country_code') == $code ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
          <div class="relative group flex-1">
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-600 transition-colors">
              <i data-lucide="phone" class="w-4 h-4"></i>
            </div>
            <input
                type="text"
                name="phone_number"
                id="pax-phone-number"
                required
                placeholder="مثال: 501234567"
                dir="ltr"
                class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold font-mono outline-none focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all text-left"
            >
          </div>
        </div>
        <p class="text-[10px] text-slate-400 leading-relaxed mt-1.5">اختر رمز الدولة ثم أدخل الرقم بدون +</p>
      </div>
    </div>
  </div>

  {{-- القسم 2: بطاقة الهوية --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs text-emerald-600 flex items-center gap-1.5">
        <i data-lucide="credit-card" class="w-3.5 h-3.5"></i>
        بطاقة الهوية
      </h4>
      <span class="text-[9px] font-bold text-emerald-500 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">اختياري</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      {{-- رقم البطاقة --}}
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1.5">رقم البطاقة</label>
        <input
            type="text"
            name="personal_card_id"
            id="pax-card-id"
            maxlength="50"
            value="{{ old('personal_card_id') }}"
            placeholder="رقم الهوية الشخصية"
            dir="ltr"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 py-2.5 text-[13px] font-bold font-mono outline-none focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all text-left"
        >
      </div>

      {{-- مكان الإصدار --}}
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1.5">مكان الإصدار</label>
        <input
            type="text"
            name="personal_card_place_of_issue"
            id="pax-card-place"
            maxlength="255"
            value="{{ old('personal_card_place_of_issue') }}"
            placeholder="المدينة / الدولة"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all"
        >
      </div>

      {{-- تاريخ الإصدار --}}
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1.5">تاريخ الإصدار</label>
        <input
            type="date"
            name="personal_card_date_issu"
            id="pax-card-issu"
            value="{{ old('personal_card_date_issu') }}"
            max="{{ now()->format('Y-m-d') }}"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 py-2.5 text-[13px] font-bold font-mono outline-none focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all"
        >
      </div>

      {{-- تاريخ الانتهاء --}}
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1.5">تاريخ الانتهاء</label>
        <input
            type="date"
            name="personal_card_date_expiry"
            id="pax-card-expiry"
            value="{{ old('personal_card_date_expiry') }}"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 py-2.5 text-[13px] font-bold font-mono outline-none focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all"
        >
      </div>

      {{-- صورة البطاقة --}}
      <div class="md:col-span-2">
        <label class="block text-xs font-bold text-slate-600 mb-1.5">صورة البطاقة الشخصية</label>
        <div class="flex items-start gap-3">
          <div class="relative flex-1">
            <input
                type="file"
                name="personal_card_id_photo"
                id="pax-card-photo-input"
                accept=".jpg,.jpeg,.png,.webp"
                class="hidden"
                onchange="paxPreviewFile(this, 'pax-card-photo-preview', 'pax-card-photo-placeholder')"
            >
            <button
                type="button"
                onclick="document.getElementById('pax-card-photo-input').click()"
                class="w-full border-2 border-dashed border-slate-200 hover:border-blue-400 rounded-xl p-4 text-center transition-all hover:bg-blue-50/30"
            >
              <div id="pax-card-photo-placeholder">
                <i data-lucide="upload" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
                <p class="text-xs font-bold text-slate-400">اضغط لرفع صورة البطاقة</p>
                <p class="text-[10px] text-slate-300 mt-1">JPG, PNG, WebP — حد أقصى 2MB</p>
              </div>
              <img id="pax-card-photo-preview" src="" alt="" class="hidden max-h-32 mx-auto rounded-lg object-cover">
            </button>
          </div>
          <div id="pax-card-photo-existing" class="hidden">
            <p class="text-[10px] font-bold text-slate-400 mb-1">الصورة الحالية:</p>
            <img id="pax-card-photo-existing-img" src="" alt="" class="w-24 h-24 object-cover rounded-xl border border-slate-200">
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- القسم 3: جواز السفر --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs text-amber-600 flex items-center gap-1.5">
        <i data-lucide="book-open" class="w-3.5 h-3.5"></i>
        جواز السفر
      </h4>
      <span class="text-[9px] font-bold text-amber-500 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">اختياري</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      {{-- رقم الجواز --}}
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1.5">رقم الجواز</label>
        <input
            type="text"
            name="passport_number"
            id="pax-passport-number"
            maxlength="50"
            value="{{ old('passport_number') }}"
            placeholder="رقم جواز السفر"
            dir="ltr"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 py-2.5 text-[13px] font-bold font-mono outline-none focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all text-left"
        >
      </div>

      {{-- مكان الإصدار --}}
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1.5">مكان الإصدار</label>
        <input
            type="text"
            name="passport_place_of_issue"
            id="pax-passport-place"
            maxlength="255"
            value="{{ old('passport_place_of_issue') }}"
            placeholder="المدينة / الدولة"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all"
        >
      </div>

      {{-- تاريخ الإصدار --}}
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1.5">تاريخ الإصدار</label>
        <input
            type="date"
            name="passport_date_issu"
            id="pax-passport-issu"
            value="{{ old('passport_date_issu') }}"
            max="{{ now()->format('Y-m-d') }}"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 py-2.5 text-[13px] font-bold font-mono outline-none focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all"
        >
      </div>

      {{-- تاريخ الانتهاء --}}
      <div>
        <label class="block text-xs font-bold text-slate-600 mb-1.5">تاريخ الانتهاء</label>
        <input
            type="date"
            name="passport_date_expiry"
            id="pax-passport-expiry"
            value="{{ old('passport_date_expiry') }}"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 py-2.5 text-[13px] font-bold font-mono outline-none focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all"
        >
      </div>

      {{-- صورة الجواز --}}
      <div class="md:col-span-2">
        <label class="block text-xs font-bold text-slate-600 mb-1.5">صورة جواز السفر</label>
        <div class="flex items-start gap-3">
          <div class="relative flex-1">
            <input
                type="file"
                name="passport_photo"
                id="pax-passport-photo-input"
                accept=".jpg,.jpeg,.png,.webp"
                class="hidden"
                onchange="paxPreviewFile(this, 'pax-passport-photo-preview', 'pax-passport-photo-placeholder')"
            >
            <button
                type="button"
                onclick="document.getElementById('pax-passport-photo-input').click()"
                class="w-full border-2 border-dashed border-slate-200 hover:border-amber-400 rounded-xl p-4 text-center transition-all hover:bg-amber-50/30"
            >
              <div id="pax-passport-photo-placeholder">
                <i data-lucide="upload" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
                <p class="text-xs font-bold text-slate-400">اضغط لرفع صورة الجواز</p>
                <p class="text-[10px] text-slate-300 mt-1">JPG, PNG, WebP — حد أقصى 2MB</p>
              </div>
              <img id="pax-passport-photo-preview" src="" alt="" class="hidden max-h-32 mx-auto rounded-lg object-cover">
            </button>
          </div>
          <div id="pax-passport-photo-existing" class="hidden">
            <p class="text-[10px] font-bold text-slate-400 mb-1">الصورة الحالية:</p>
            <img id="pax-passport-photo-existing-img" src="" alt="" class="w-24 h-24 object-cover rounded-xl border border-slate-200">
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- القسم 4: معاينة حية --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs text-purple-600 flex items-center gap-1.5">
        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
        معاينة المسافر
      </h4>
      <span class="text-[9px] font-bold text-purple-600 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded-full">مباشر</span>
    </div>
    <div class="bg-[#f9f9fc] border border-slate-200/70 rounded-2xl p-4">
      <div class="flex items-start gap-3">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-slate-800 to-slate-900 flex items-center justify-center shadow-lg shadow-slate-900/20 flex-shrink-0" id="pax-preview-icon">
          <i data-lucide="user" class="w-6 h-6 text-white"></i>
        </div>
        <div class="flex-1 min-w-0">
          <p class="font-bold text-slate-800 text-sm" id="pax-preview-name">لم يُدخل اسم بعد</p>
          <div class="flex items-center gap-3 mt-1.5 flex-wrap">
            <span class="text-[11px] text-slate-400 font-mono" dir="ltr" id="pax-preview-phone">—</span>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200" id="pax-preview-gender">—</span>
            <span class="text-[11px] text-slate-500 font-bold" id="pax-preview-age">— سنة</span>
          </div>
          <div class="flex items-center gap-4 mt-2">
            <span class="text-[10px] text-slate-400">بطاقة: <span class="font-mono font-bold text-slate-600" dir="ltr" id="pax-preview-card">—</span></span>
            <span class="text-[10px] text-slate-400">جواز: <span class="font-mono font-bold text-slate-600" dir="ltr" id="pax-preview-passport">—</span></span>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- القسم 5: معلومات إضافية (للتعديل فقط) --}}
  <div class="space-y-4" id="pax-extra-info" style="display:none;">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs text-blue-600 flex items-center gap-1.5">
        <i data-lucide="info" class="w-3.5 h-3.5"></i>
        معلومات إضافية
      </h4>
      <span class="text-[9px] font-bold text-blue-500 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-full">للقراءة فقط</span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
      <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-3 text-center">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-center gap-1">
          <i data-lucide="ticket" class="w-3 h-3"></i>
          الحجوزات
        </p>
        <p class="font-bold text-slate-700 text-xs mt-1.5" id="pax-info-reservations">0 حجز</p>
      </div>
      <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-3 text-center">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-center gap-1">
          <i data-lucide="calendar" class="w-3 h-3"></i>
          العمر
        </p>
        <p class="font-bold text-slate-700 text-xs mt-1.5" id="pax-info-age">— سنة</p>
      </div>
      <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-3 text-center">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-center gap-1">
          <i data-lucide="hash" class="w-3 h-3"></i>
          UUID
        </p>
        <p class="font-bold text-slate-700 text-xs mt-1.5 font-mono truncate" dir="ltr" id="pax-info-uuid">—</p>
      </div>
    </div>
  </div>

  {{-- Actions slot --}}
  <x-slot name="actions">
    <button
        type="button"
        data-close-modal
        class="flex-1 bg-[#f3f3f6] hover:bg-[#eeeef0] text-gray-600 font-bold py-3 px-4 rounded-xl text-xs transition-all flex items-center justify-center gap-2"
    >
      <i data-lucide="x" class="w-4 h-4"></i>
      <span id="pax-cancel-text">إعادة تعيين</span>
    </button>
    <button
        type="submit"
        class="flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95 bg-[#115128] hover:bg-emerald-950"
        id="pax-submit-btn"
    >
      <i data-lucide="save" class="w-4 h-4"></i>
      <span id="pax-submit-text">حفظ المسافر</span>
    </button>
  </x-slot>
</x-form-modal>

@endsection
@push('scripts')
<script>
  // ===== Filter URL builder =====
  function buildPassengerFilters(genderId, minAge, maxAge) {
    var params = new URLSearchParams(window.location.search);
    if (genderId !== null && genderId !== undefined) {
      if (genderId) params.set('gender_id', genderId); else params.delete('gender_id');
    }
    if (minAge !== null && minAge !== undefined) {
      if (minAge) params.set('min_age', minAge); else params.delete('min_age');
    }
    if (maxAge !== null && maxAge !== undefined) {
      if (maxAge) params.set('max_age', maxAge); else params.delete('max_age');
    }
    // keep search
    var s = params.toString();
    return s ? '?' + s : '';
  }

  // ===== File upload preview =====
  function paxPreviewFile(input, previewId, placeholderId) {
    var preview = document.getElementById(previewId);
    var placeholder = document.getElementById(placeholderId);
    if (!input.files || !input.files[0]) {
      preview.classList.add('hidden');
      preview.src = '';
      placeholder.classList.remove('hidden');
      return;
    }
    var reader = new FileReader();
    reader.onload = function(e) {
      preview.src = e.target.result;
      preview.classList.remove('hidden');
      placeholder.classList.add('hidden');
    };
    reader.readAsDataURL(input.files[0]);
  }

  function paxResetFilePreviews() {
    ['pax-card-photo-preview', 'pax-passport-photo-preview'].forEach(function(id) {
      var el = document.getElementById(id);
      if (el) { el.src = ''; el.classList.add('hidden'); }
    });
    ['pax-card-photo-placeholder', 'pax-passport-photo-placeholder'].forEach(function(id) {
      var el = document.getElementById(id);
      if (el) el.classList.remove('hidden');
    });
    // reset file inputs
    ['pax-card-photo-input', 'pax-passport-photo-input'].forEach(function(id) {
      var el = document.getElementById(id);
      if (el) el.value = '';
    });
    // hide existing photos
    ['pax-card-photo-existing', 'pax-passport-photo-existing'].forEach(function(id) {
      var el = document.getElementById(id);
      if (el) el.classList.add('hidden');
    });
  }

  // ===== Passenger form modal: dynamic action + title + variant switching =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'passenger-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.uuid);

    // Reset file previews
    paxResetFilePreviews();

    if (isEdit) {
      // edit mode → PUT to update route
      var updateUrl = "{{ route('admin.passengers.update', '__UUID__') }}".replace('__UUID__', data.uuid);
      form.setAttribute('action', updateUrl);
      var existingMethod = form.querySelector('input[name="_method"]');
      if (existingMethod) existingMethod.value = 'PUT';
      else {
        var m = document.createElement('input');
        m.type = 'hidden'; m.name = '_method'; m.value = 'PUT';
        m.setAttribute('data-dynamic', 'true');
        form.appendChild(m);
      }
      // update banner to amber
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل بيانات المسافر';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }
      form.setAttribute('data-success-message', 'تم تحديث بيانات المسافر بنجاح');
      // subtitle
      var subtitleEl = modal.querySelector('.aracari-modal-panel .relative.z-10 p');
      if (subtitleEl) {
        subtitleEl.innerHTML = '<span class="inline-block text-xs text-white/80 bg-slate-900/40 border border-white/10 px-2 py-0.5 rounded-full max-w-[260px] truncate align-middle">' + escapeHtml(data.full_name || '') + '</span>';
        subtitleEl.style.display = '';
      }
      // info badge
      var infoBadge = modal.querySelector('#pax-info-badge');
      if (infoBadge) {
        infoBadge.textContent = 'قابل للتعديل';
        infoBadge.className = 'text-[9px] font-bold px-2 py-0.5 rounded-full border text-amber-600 bg-amber-50 border-amber-200';
        infoBadge.closest('div').querySelector('h4').className = 'font-bold text-xs text-amber-600 flex items-center gap-1.5';
      }
      // preview icon: amber
      var previewIcon = modal.querySelector('#pax-preview-icon');
      if (previewIcon) previewIcon.className = 'w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/20 flex-shrink-0';
      // show extra info
      var extraInfo = modal.querySelector('#pax-extra-info');
      if (extraInfo) extraInfo.style.display = '';
      var infoReservations = modal.querySelector('#pax-info-reservations');
      if (infoReservations) infoReservations.textContent = (data.reservations_count || 0) + ' حجز';
      var infoAge = modal.querySelector('#pax-info-age');
      if (infoAge) infoAge.textContent = (data.age || '—') + ' سنة';
      var infoUuid = modal.querySelector('#pax-info-uuid');
      if (infoUuid) infoUuid.textContent = data.uuid || '—';
      // cancel text
      var cancelText = modal.querySelector('#pax-cancel-text');
      if (cancelText) cancelText.textContent = 'إلغاء';
      // submit button style
      var submitBtn = modal.querySelector('#pax-submit-btn');
      if (submitBtn) submitBtn.className = 'flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95 bg-amber-600 hover:bg-amber-700';
      var submitText = modal.querySelector('#pax-submit-text');
      if (submitText) submitText.textContent = 'حفظ التعديلات';

      // Show existing photos
      if (data.existing_card_photo_url) {
        var existingCard = modal.querySelector('#pax-card-photo-existing');
        var existingCardImg = modal.querySelector('#pax-card-photo-existing-img');
        if (existingCard && existingCardImg) {
          existingCardImg.src = data.existing_card_photo_url;
          existingCard.classList.remove('hidden');
        }
      }
      if (data.existing_passport_photo_url) {
        var existingPassport = modal.querySelector('#pax-passport-photo-existing');
        var existingPassportImg = modal.querySelector('#pax-passport-photo-existing-img');
        if (existingPassport && existingPassportImg) {
          existingPassportImg.src = data.existing_passport_photo_url;
          existingPassport.classList.remove('hidden');
        }
      }
    } else {
      // create mode → POST to store route
      form.setAttribute('action', "{{ route('admin.passengers.store') }}");
      var m = form.querySelector('input[name="_method"]');
      if (m && m.getAttribute('data-dynamic') === 'true') m.remove();
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-slate-900 to-slate-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="user-plus" class="w-5 h-5"></i> إضافة مسافر جديد';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-blue-500/20 text-blue-300 border-blue-500/30';
        badge.textContent = 'جديد';
      }
      form.setAttribute('data-success-message', 'تم حفظ المسافر بنجاح');
      // subtitle
      var subtitleEl = modal.querySelector('.aracari-modal-panel .relative.z-10 p');
      if (subtitleEl) {
        subtitleEl.textContent = 'إنشاء مسافر جديد مع بيانات الهوية';
        subtitleEl.style.display = '';
      }
      // info badge
      var infoBadge = modal.querySelector('#pax-info-badge');
      if (infoBadge) {
        infoBadge.textContent = 'مطلوب';
        infoBadge.className = 'text-[9px] font-bold px-2 py-0.5 rounded-full border text-rose-600 bg-rose-50 border-rose-200';
        infoBadge.closest('div').querySelector('h4').className = 'font-bold text-xs text-blue-600 flex items-center gap-1.5';
      }
      // preview icon: slate
      var previewIcon = modal.querySelector('#pax-preview-icon');
      if (previewIcon) previewIcon.className = 'w-12 h-12 rounded-2xl bg-gradient-to-br from-slate-800 to-slate-900 flex items-center justify-center shadow-lg shadow-slate-900/20 flex-shrink-0';
      // hide extra info
      var extraInfo = modal.querySelector('#pax-extra-info');
      if (extraInfo) extraInfo.style.display = 'none';
      // cancel text
      var cancelText = modal.querySelector('#pax-cancel-text');
      if (cancelText) cancelText.textContent = 'إعادة تعيين';
      // submit button style
      var submitBtn = modal.querySelector('#pax-submit-btn');
      if (submitBtn) submitBtn.className = 'flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95 bg-[#115128] hover:bg-emerald-950';
      var submitText = modal.querySelector('#pax-submit-text');
      if (submitText) submitText.textContent = 'حفظ المسافر';
    }
    if (window.lucide) window.lucide.createIcons();

    // sync live preview
    syncPassengerPreview(modal);
  });

  function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  // ===== Live preview sync =====
  function syncPassengerPreview(modal) {
    var nameInput    = modal.querySelector('#pax-full-name');
    var phoneInput   = modal.querySelector('#pax-phone-number');
    var genderSelect = modal.querySelector('#pax-gender');
    var birthdayInput = modal.querySelector('#pax-birthday');
    var cardIdInput  = modal.querySelector('#pax-card-id');
    var passportInput = modal.querySelector('#pax-passport-number');

    var previewName    = modal.querySelector('#pax-preview-name');
    var previewPhone   = modal.querySelector('#pax-preview-phone');
    var previewGender  = modal.querySelector('#pax-preview-gender');
    var previewAge     = modal.querySelector('#pax-preview-age');
    var previewCard    = modal.querySelector('#pax-preview-card');
    var previewPassport = modal.querySelector('#pax-preview-passport');

    if (previewName) {
      previewName.textContent = nameInput && nameInput.value.trim() ? nameInput.value.trim() : 'لم يُدخل اسم بعد';
    }
    if (previewPhone) {
      var cc = modal.querySelector('#pax-country-code');
      var phone = phoneInput ? phoneInput.value.trim() : '';
      if (cc && cc.value && phone) {
        previewPhone.textContent = '+' + cc.value + ' ' + phone;
      } else if (phone) {
        previewPhone.textContent = phone;
      } else {
        previewPhone.textContent = '—';
      }
    }
    if (previewGender && genderSelect) {
      var opt = genderSelect.options[genderSelect.selectedIndex];
      previewGender.textContent = opt && opt.value ? opt.text : '—';
    }
    if (previewAge && birthdayInput) {
      if (birthdayInput.value) {
        var today = new Date();
        var birth = new Date(birthdayInput.value);
        var age = today.getFullYear() - birth.getFullYear();
        var m = today.getMonth() - birth.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
        previewAge.textContent = age + ' سنة';
      } else {
        previewAge.textContent = '— سنة';
      }
    }
    if (previewCard) {
      previewCard.textContent = cardIdInput && cardIdInput.value.trim() ? cardIdInput.value.trim() : '—';
    }
    if (previewPassport) {
      previewPassport.textContent = passportInput && passportInput.value.trim() ? passportInput.value.trim() : '—';
    }
  }

  (function() {
    var nameInput = document.getElementById('pax-full-name');
    var phoneInput = document.getElementById('pax-phone-number');
    var ccInput = document.getElementById('pax-country-code');
    var genderSelect = document.getElementById('pax-gender');
    var birthdayInput = document.getElementById('pax-birthday');
    var cardIdInput = document.getElementById('pax-card-id');
    var passportInput = document.getElementById('pax-passport-number');
    if (!nameInput) return;
    var modal = document.getElementById('passenger-form-modal');
    nameInput.addEventListener('input', function() { syncPassengerPreview(modal); });
    if (phoneInput) phoneInput.addEventListener('input', function() { syncPassengerPreview(modal); });
    if (ccInput) ccInput.addEventListener('change', function() { syncPassengerPreview(modal); });
    if (genderSelect) genderSelect.addEventListener('change', function() { syncPassengerPreview(modal); });
    if (birthdayInput) birthdayInput.addEventListener('change', function() { syncPassengerPreview(modal); });
    if (cardIdInput) cardIdInput.addEventListener('input', function() { syncPassengerPreview(modal); });
    if (passportInput) passportInput.addEventListener('input', function() { syncPassengerPreview(modal); });
  })();
</script>
@endpush
