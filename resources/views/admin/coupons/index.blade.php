@extends('layouts.admin')

@section('title', 'أركاري | كوبونات الخصم')
@section('header_title', 'كوبونات الخصم والحوافز')

@section('content')

@php
    $user      = auth()->user();
    $canCreate = $user && $user->hasPermission('coupons:create');
    $canEdit   = $user && $user->hasPermission('coupons:edit');
    $canDelete = $user && $user->hasPermission('coupons:delete');

    // إحصائيات من قاعدة البيانات
    $allCoupons     = \App\Models\Coupon::all();
    $totalCount     = $allCoupons->count();
    $activeCount    = $allCoupons->where('status_id', \App\Constants\CouponStatus::ACTIVE)->count();
    $expiredCount   = $allCoupons->where('status_id', \App\Constants\CouponStatus::EXPIRED)->count();
    $totalUsed      = 0;
    $totalLimit     = 0;
    foreach ($allCoupons as $c) {
        $totalUsed += $c->used_count;
        $totalLimit += ($c->usage_limit ?: 50);
    }
    $usageRate = $totalLimit > 0 ? round(($totalUsed / $totalLimit) * 100) : 0;

    // فلتر الحالة النشط
    $activeStatusFilter = request('status_id');
    // حالة الكوبونات للفورم
    $couponStatuses = $statuses ?? [];

    // ثوابت الحالة
    $STATUS_ACTIVE   = \App\Constants\CouponStatus::ACTIVE;
    $STATUS_INACTIVE = \App\Constants\CouponStatus::INACTIVE;
    $STATUS_EXPIRED  = \App\Constants\CouponStatus::EXPIRED;
    $STATUS_USED     = \App\Constants\CouponStatus::USED;
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="percent"
      title="كوبونات الخصم والحوافز"
      :count="$totalCount"
      countLabel="كوبون"
      description="إدارة رموز الخصم لزيادة مبيعات الرحلات وتحفيز المسافرين"
      descriptionIcon="percent"
  >
    @if($canCreate)
      <button
          type="button"
          data-create-modal="coupon-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إصدار كوبون جديد
      </button>
    @endif
  </x-page-header>

  {{-- ===== Stat Cards (4) ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    {{-- إجمالي الكوبونات --}}
    <x-stat-card
        icon="percent"
        :label="'إجمالي الكوبونات'"
        :value="$totalCount"
        :gradient="'from-slate-900 to-slate-800'"
        :accentColor="'teal'"
    />
    {{-- كوبونات نشطة --}}
    <x-stat-card
        icon="check-circle"
        :label="'كوبونات نشطة'"
        :value="$activeCount"
        :gradient="'from-emerald-900/90 to-emerald-800/90'"
        :accentColor="'emerald'"
        :badge="'نشطة'"
    />
    {{-- منتهية الصلاحية --}}
    <x-stat-card
        icon="x-circle"
        :label="'منتهية الصلاحية'"
        :value="$expiredCount"
        :gradient="'from-rose-900/90 to-rose-800/90'"
        :accentColor="'rose'"
    />
    {{-- نسبة الاستخدام --}}
    <x-stat-card
        icon="bar-chart-3"
        :label="'نسبة الاستخدام'"
        :value="$usageRate . '%'"
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
        searchPlaceholder="ابحث بكود الكوبون..."
        :footerInfo="'عرض ' . $coupons->count() . ' من أصل ' . $coupons->total() . ' كوبون'"
    >
      {{-- فلتر الحالة (فوق الجدول) --}}
      <x-slot name="filters">
        <div class="flex items-center gap-3">
          <select
              id="coupon-status-filter"
              onchange="window.location.href = '{{ request()->url() }}' + (this.value ? '?status_id=' + this.value : '')"
              class="h-11 bg-white/70 border border-slate-200 rounded-xl px-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all appearance-none cursor-pointer min-w-[160px]"
          >
            <option value="">جميع الكوبونات</option>
            @foreach($couponStatuses as $s)
              <option value="{{ $s->id }}" {{ (string) $activeStatusFilter === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
            @endforeach
          </select>
          @if($activeStatusFilter)
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
        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">كود الكوبون</th>
        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">قيمة الخصم</th>
        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الوصف</th>
        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الصلاحية</th>
        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الاستخدام</th>
        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
      </x-slot>

      <x-slot name="rows">
        @forelse($coupons as $coupon)
          @php
            $isExpired = $coupon->is_expired;
            $isActive  = $coupon->isActive();
            $usedCount = $coupon->used_count;
            $usageLimit = $coupon->usage_limit;
            $usagePct  = $usageLimit ? min(round(($usedCount / $usageLimit) * 100), 100) : 0;
          @endphp
          <tr class="hover:bg-slate-50/50 transition-colors group">
            {{-- كود الكوبون --}}
            <td data-searchable class="px-6 py-4">
              <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gradient-to-r from-slate-900 to-slate-800 text-white font-mono font-black text-sm tracking-widest shadow-lg">
                <i data-lucide="percent" class="w-3 h-3 text-teal-400"></i>
                {{ $coupon->code }}
              </div>
            </td>

            {{-- قيمة الخصم --}}
            <td class="px-6 py-4">
              <span class="font-black text-teal-500 text-xl">{{ number_format((float) $coupon->percentage, 2) }}<span class="text-xs font-bold mr-1 text-slate-400">%</span></span>
            </td>

            {{-- الوصف --}}
            <td class="px-6 py-4 max-w-[200px]">
              <p class="text-[11px] text-slate-500 leading-relaxed truncate">{{ $coupon->description ?? 'لا يوجد وصف' }}</p>
            </td>

            {{-- الصلاحية --}}
            <td class="px-6 py-4">
              <div class="text-xs font-bold mb-1 {{ $isExpired ? 'text-rose-500' : 'text-slate-700' }}" dir="ltr">
                {{ $coupon->end_date ? $coupon->end_date->format('Y/m/d') : 'مفتوح' }}
              </div>
              @if($isExpired)
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-600 text-[10px] font-bold border border-rose-200">
                  <i data-lucide="x-circle" class="w-3 h-3"></i>منتهي
                </span>
              @elseif($isActive)
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-bold border border-emerald-200">
                  <i data-lucide="check-circle" class="w-3 h-3"></i>ساري
                </span>
              @else
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-500 text-[10px] font-bold border border-slate-200">
                  متوقف
                </span>
              @endif
            </td>

            {{-- الاستخدام --}}
            <td class="px-6 py-4 text-center">
              <div class="flex flex-col items-center gap-1">
                <div class="flex items-center gap-1.5">
                  <span class="text-sm font-black text-slate-700">{{ $usedCount }}</span>
                  <span class="text-[10px] text-slate-400">/</span>
                  <span class="text-sm font-bold text-slate-400">{{ $usageLimit ?: '∞' }}</span>
                </div>
                @if($usageLimit)
                  <div class="w-16 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-teal-500 to-teal-400 rounded-full transition-all duration-500" style="width: {{ $usagePct }}%"></div>
                  </div>
                @endif
              </div>
            </td>

            {{-- الإجراءات --}}
            <td class="px-6 py-4 text-center">
              <div class="flex items-center justify-center gap-1.5">
                @if($canEdit)
                  <x-action-button
                      variant="edit"
                      data-edit-modal="coupon-form-modal"
                      data-edit-json="{{ json_encode([
                          'uuid'           => $coupon->uuid,
                          'code'           => $coupon->code,
                          'percentage'     => number_format((float) $coupon->percentage, 2),
                          'status_id'      => $coupon->status_id,
                          'start_date'     => $coupon->start_date ? $coupon->start_date->format('Y-m-d') : '',
                          'end_date'       => $coupon->end_date ? $coupon->end_date->format('Y-m-d') : '',
                          'usage_limit'    => $coupon->usage_limit,
                          'minimum_amount' => $coupon->minimum_amount ? number_format((float) $coupon->minimum_amount, 2) : '',
                          'description'    => $coupon->description,
                          'used_count'     => $usedCount,
                      ], JSON_UNESCAPED_UNICODE) }}"
                      :ariaLabel="'تعديل ' . $coupon->code"
                  />
                @endif
                @if($canDelete)
                  <x-action-button
                      variant="delete"
                      data-delete-url="{{ route('admin.coupons.destroy', $coupon) }}"
                      data-delete-label="{{$coupon->code}}"
                  />
                @endif
              </div>
            </td>
          </tr>
        @empty
          <x-empty-state
              icon="percent"
              :title="request('search') ? 'لا توجد نتائج مطابقة' : 'لا توجد كوبونات مسجلة'"
              :message="request('search') ? 'جرّب تعديل كلمات البحث' : 'قم بإصدار رموز ترويجية جديدة لعملائك'"
              :colspan="6"
          >
            @if(!request('search') && !request('status_id') && $canCreate)
              <button
                  type="button"
                  data-create-modal="coupon-form-modal"
                  class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
              >
                <i data-lucide="plus" class="w-4 h-4"></i>
                إصدار كوبون جديد
              </button>
            @endif
            @if(request('search') || request('status_id'))
              <a href="{{ request()->url() }}" class="mt-3 text-teal-600 hover:text-teal-700 inline-flex items-center gap-1 font-bold text-sm">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                إعادة تعيين الفلاتر
              </a>
            @endif
          </x-empty-state>
        @endforelse
      </x-slot>

      <x-slot name="footer">
        <x-pagination :paginator="$coupons" />
      </x-slot>
    </x-data-table-card>

  </div>{{-- end #aracari-refreshable -->}

</div>

{{-- ===== Coupon Form Modal (Create + Edit) ===== --}}
<x-form-modal
    id="coupon-form-modal"
    title="إصدار كوبون جديد"
    :variant="'create'"
    :action="route('admin.coupons.store')"
    method="POST"
    successMessage="تم حفظ الكوبون بنجاح"
>

  {{-- القسم 1: معلومات الكوبون --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs text-[#115128] flex items-center gap-1.5">
        <i data-lucide="tag" class="w-3.5 h-3.5"></i>
        معلومات الكوبون
      </h4>
      <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border text-rose-600 bg-rose-50 border-rose-200" id="coupon-info-badge">مطلوب</span>
    </div>

    {{-- كود الكوبون --}}
    <x-form-field name="code" label="كود الكوبون" icon="hash" required>
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="hash" class="w-4 h-4"></i>
        </div>
        <input
            type="text"
            name="code"
            id="coupon-code"
            required
            maxlength="50"
            value="{{ old('code') }}"
            placeholder="مثال: SUMMER2025"
            dir="ltr"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold font-mono tracking-widest outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left uppercase"
        >
      </div>
      <p class="text-[10px] text-slate-400 leading-relaxed mt-1.5">
        رمز فريد يُدخله المسافر عند الحجز
      </p>
    </x-form-field>

    {{-- نسبة الخصم --}}
    <x-form-field name="percentage" label="نسبة الخصم" icon="percent" required>
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="percent" class="w-4 h-4"></i>
        </div>
        <input
            type="number"
            name="percentage"
            id="coupon-percentage"
            required
            min="0"
            max="100"
            step="0.01"
            placeholder="مثال: 15"
            dir="ltr"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-10 py-2.5 text-[13px] font-bold font-mono outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left"
        >
        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
          <span class="text-xs font-bold">%</span>
        </div>
      </div>
    </x-form-field>

    {{-- الحالة --}}
    <x-form-field name="status_id" label="الحالة" icon="circle-dot" required>
      <select
          name="status_id"
          id="coupon-status"
          required
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none cursor-pointer"
      >
        <option value="">اختر الحالة...</option>
        @foreach($couponStatuses as $s)
          <option value="{{ $s->id }}" {{ old('status_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
        @endforeach
      </select>
    </x-form-field>
  </div>

  {{-- القسم 2: الصلاحية والقيود --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs text-blue-600 flex items-center gap-1.5">
        <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
        الصلاحية والقيود
      </h4>
      <span class="text-[9px] font-bold text-blue-500 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-full">اختياري</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      {{-- تاريخ البدء --}}
      <x-form-field name="start_date" label="تاريخ البدء" icon="calendar">
        <input
            type="date"
            name="start_date"
            id="coupon-start-date"
            value="{{ old('start_date') }}"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold font-mono outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
        >
      </x-form-field>

      {{-- تاريخ الانتهاء --}}
      <x-form-field name="end_date" label="تاريخ الانتهاء" icon="calendar-x">
        <input
            type="date"
            name="end_date"
            id="coupon-end-date"
            value="{{ old('end_date') }}"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold font-mono outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
        >
      </x-form-field>

      {{-- حد الاستخدام --}}
      <x-form-field name="usage_limit" label="حد الاستخدام" icon="users">
        <input
            type="number"
            name="usage_limit"
            id="coupon-usage-limit"
            min="1"
            placeholder="مثال: 100"
            dir="ltr"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold font-mono outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left"
        >
      </x-form-field>

      {{-- الحد الأدنى للمبلغ --}}
      <x-form-field name="minimum_amount" label="الحد الأدنى للمبلغ" icon="banknote">
        <input
            type="number"
            name="minimum_amount"
            id="coupon-minimum-amount"
            min="0"
            step="0.01"
            placeholder="مثال: 50"
            dir="ltr"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold font-mono outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left"
        >
      </x-form-field>
    </div>
  </div>

  {{-- القسم 3: الوصف --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs text-slate-600 flex items-center gap-1.5">
        <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
        الوصف
      </h4>
    </div>
    <textarea
        name="description"
        id="coupon-description"
        rows="2"
        placeholder="وصف مختصر للكوبون (اختياري)..."
        class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl p-3 text-[13px] font-medium outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all resize-none text-right"
    >{{ old('description') }}</textarea>
  </div>

  {{-- القسم 4: معاينة حية --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs text-purple-600 flex items-center gap-1.5">
        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
        معاينة الكوبون
      </h4>
      <span class="text-[9px] font-bold text-purple-600 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded-full">مباشر</span>
    </div>
    <div class="bg-[#f9f9fc] border border-slate-200/70 rounded-2xl p-4">
      <div class="flex items-start gap-3">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center shadow-lg shadow-teal-500/20 flex-shrink-0" id="coupon-preview-icon">
          <i data-lucide="percent" class="w-6 h-6 text-white"></i>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gradient-to-r from-slate-900 to-slate-800 text-white font-mono font-black text-sm tracking-widest">
              <i data-lucide="percent" class="w-3 h-3 text-teal-400"></i>
              <span id="coupon-preview-code">CODE</span>
            </div>
            <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border bg-teal-50 text-teal-700 border-teal-200" id="coupon-preview-badge">جديد</span>
          </div>
          <div class="flex items-center gap-3 mt-2">
            <span class="font-black text-teal-500 text-lg">(<span id="coupon-preview-percentage">0</span>%)</span>
            <span class="text-[11px] text-slate-400" id="coupon-preview-dates">بدون صلاحية</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- القسم 5: معلومات إضافية (للتعديل فقط) --}}
  <div class="space-y-4" id="coupon-extra-info" style="display:none;">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs text-blue-600 flex items-center gap-1.5">
        <i data-lucide="info" class="w-3.5 h-3.5"></i>
        معلومات إضافية
      </h4>
      <span class="text-[9px] font-bold text-blue-500 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-full">للقراءة فقط</span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-3 text-center">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-center gap-1">
          <i data-lucide="users" class="w-3 h-3"></i>
          الاستخدامات
        </p>
        <p class="font-bold text-slate-700 text-xs mt-1.5" id="coupon-info-used">0 استخدام</p>
      </div>
      <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-3 text-center">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-center gap-1">
          <i data-lucide="hash" class="w-3 h-3"></i>
          UUID
        </p>
        <p class="font-bold text-slate-700 text-xs mt-1.5 font-mono truncate" dir="ltr" id="coupon-info-uuid">—</p>
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
      <span id="coupon-cancel-text">إعادة تعيين</span>
    </button>
    <button
        type="submit"
        class="flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95 bg-[#115128] hover:bg-emerald-950"
        id="coupon-submit-btn"
    >
      <i data-lucide="save" class="w-4 h-4"></i>
      <span id="coupon-submit-text">حفظ الكوبون</span>
    </button>
  </x-slot>
</x-form-modal>

@endsection

@push('scripts')
<script>
  // ===== Coupon form modal: dynamic action + title + variant switching =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'coupon-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.uuid);

    if (isEdit) {
      // edit mode → PUT to update route
      var updateUrl = "{{ route('admin.coupons.update', '__UUID__') }}".replace('__UUID__', data.uuid);
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
        '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل الكوبون';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }
      form.setAttribute('data-success-message', 'تم تحديث الكوبون بنجاح');
      // subtitle
      var subtitleEl = modal.querySelector('.aracari-modal-panel .relative.z-10 p');
      if (subtitleEl) {
        subtitleEl.innerHTML = '<span class="inline-block text-xs text-white/80 bg-slate-900/40 border border-white/10 px-2 py-0.5 rounded-full max-w-[260px] truncate align-middle font-mono" dir="ltr">' + (data.code || '') + '</span>';
        subtitleEl.style.display = '';
      }
      // info badge
      var infoBadge = modal.querySelector('#coupon-info-badge');
      if (infoBadge) {
        infoBadge.textContent = 'قابل للتعديل';
        infoBadge.className = 'text-[9px] font-bold px-2 py-0.5 rounded-full border text-amber-600 bg-amber-50 border-amber-200';
        infoBadge.closest('div').querySelector('h4').className = 'font-bold text-xs text-amber-600 flex items-center gap-1.5';
      }
      // preview: switch to edit mode
      var previewIcon = modal.querySelector('#coupon-preview-icon');
      if (previewIcon) previewIcon.className = 'w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/20 flex-shrink-0';
      var previewBadge = modal.querySelector('#coupon-preview-badge');
      if (previewBadge) { previewBadge.textContent = 'جاري التعديل'; previewBadge.className = 'text-[9px] font-bold px-2 py-0.5 rounded-full border bg-amber-50 text-amber-700 border-amber-200'; }
      // show extra info
      var extraInfo = modal.querySelector('#coupon-extra-info');
      if (extraInfo) extraInfo.style.display = '';
      var infoUsed = modal.querySelector('#coupon-info-used');
      if (infoUsed) infoUsed.textContent = (data.used_count || 0) + ' استخدام';
      var infoUuid = modal.querySelector('#coupon-info-uuid');
      if (infoUuid) infoUuid.textContent = data.uuid || '—';
      // cancel text
      var cancelText = modal.querySelector('#coupon-cancel-text');
      if (cancelText) cancelText.textContent = 'إلغاء';
      // submit button style
      var submitBtn = modal.querySelector('#coupon-submit-btn');
      if (submitBtn) submitBtn.className = 'flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95 bg-amber-600 hover:bg-amber-700';
      var submitText = modal.querySelector('#coupon-submit-text');
      if (submitText) submitText.textContent = 'حفظ التعديلات';
    } else {
      // create mode → POST to store route
      form.setAttribute('action', "{{ route('admin.coupons.store') }}");
      var m = form.querySelector('input[name="_method"]');
      if (m) m.remove();
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="plus-circle" class="w-5 h-5"></i> إصدار كوبون جديد';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30';
        badge.textContent = 'جديد';
      }
      form.setAttribute('data-success-message', 'تم حفظ الكوبون بنجاح');
      // subtitle
      var subtitleEl = modal.querySelector('.aracari-modal-panel .relative.z-10 p');
      if (subtitleEl) {
        subtitleEl.textContent = 'إنشاء كوبون خصم جديد';
        subtitleEl.style.display = '';
      }
      // info badge
      var infoBadge = modal.querySelector('#coupon-info-badge');
      if (infoBadge) {
        infoBadge.textContent = 'مطلوب';
        infoBadge.className = 'text-[9px] font-bold px-2 py-0.5 rounded-full border text-rose-600 bg-rose-50 border-rose-200';
        infoBadge.closest('div').querySelector('h4').className = 'font-bold text-xs text-[#115128] flex items-center gap-1.5';
      }
      // preview: reset to create mode
      var previewIcon = modal.querySelector('#coupon-preview-icon');
      if (previewIcon) previewIcon.className = 'w-12 h-12 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center shadow-lg shadow-teal-500/20 flex-shrink-0';
      var previewBadge = modal.querySelector('#coupon-preview-badge');
      if (previewBadge) { previewBadge.textContent = 'جديد'; previewBadge.className = 'text-[9px] font-bold px-2 py-0.5 rounded-full border bg-teal-50 text-teal-700 border-teal-200'; }
      // hide extra info
      var extraInfo = modal.querySelector('#coupon-extra-info');
      if (extraInfo) extraInfo.style.display = 'none';
      // cancel text
      var cancelText = modal.querySelector('#coupon-cancel-text');
      if (cancelText) cancelText.textContent = 'إعادة تعيين';
      // submit button style
      var submitBtn = modal.querySelector('#coupon-submit-btn');
      if (submitBtn) submitBtn.className = 'flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95 bg-[#115128] hover:bg-emerald-950';
      var submitText = modal.querySelector('#coupon-submit-text');
      if (submitText) submitText.textContent = 'حفظ الكوبون';
    }
    if (window.lucide) window.lucide.createIcons();

    // sync live preview
    syncCouponPreview(modal);
  });

  function syncCouponPreview(modal) {
    var codeInput = modal.querySelector('#coupon-code');
    var pctInput  = modal.querySelector('#coupon-percentage');
    var startInput = modal.querySelector('#coupon-start-date');
    var endInput  = modal.querySelector('#coupon-end-date');
    var previewCode    = modal.querySelector('#coupon-preview-code');
    var previewPct     = modal.querySelector('#coupon-preview-percentage');
    var previewDates   = modal.querySelector('#coupon-preview-dates');
    if (!previewCode) return;
    previewCode.textContent = codeInput && codeInput.value.trim() ? codeInput.value.trim().toUpperCase() : 'CODE';
    previewPct.textContent = pctInput && pctInput.value ? parseFloat(pctInput.value).toFixed(2) : '0';
    var start = startInput && startInput.value ? startInput.value : '';
    var end = endInput && endInput.value ? endInput.value : '';
    if (start && end) {
      previewDates.textContent = start.replace(/-/g, '/') + ' ← → ' + end.replace(/-/g, '/');
    } else if (start) {
      previewDates.textContent = 'من ' + start.replace(/-/g, '/');
    } else if (end) {
      previewDates.textContent = 'حتى ' + end.replace(/-/g, '/');
    } else {
      previewDates.textContent = 'بدون صلاحية';
    }
  }

  (function() {
    var codeInput  = document.getElementById('coupon-code');
    var pctInput   = document.getElementById('coupon-percentage');
    var startInput = document.getElementById('coupon-start-date');
    var endInput   = document.getElementById('coupon-end-date');
    if (!codeInput) return;
    codeInput.addEventListener('input', function() { syncCouponPreview(document.getElementById('coupon-form-modal')); });
    if (pctInput)   pctInput.addEventListener('input', function() { syncCouponPreview(document.getElementById('coupon-form-modal')); });
    if (startInput) startInput.addEventListener('change', function() { syncCouponPreview(document.getElementById('coupon-form-modal')); });
    if (endInput)   endInput.addEventListener('change', function() { syncCouponPreview(document.getElementById('coupon-form-modal')); });
  })();
</script>
@endpush
