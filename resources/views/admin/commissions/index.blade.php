@extends('layouts.admin')

@section('title', 'أركاري | إدارة العمولات')
@section('header_title', 'إدارة العمولات')

@section('content')

@php
    $typeMeta = [
        'fixed'      => ['label' => 'ثابت', 'bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'icon' => 'banknote'],
        'percentage' => ['label' => 'نسبة مئوية', 'bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200', 'icon' => 'percent'],
    ];
    $levelMeta = [
        'service' => ['label' => 'خدمة', 'icon' => 'building-2'],
        'booking' => ['label' => 'حجز', 'icon' => 'calendar-days'],
    ];
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="percent"
      title="إدارة العمولات"
      :count="$commissions->total()"
      countLabel="عمولة"
      description="إدارة أنواع العمولات (ثابتة / نسبة مئوية) على مستوى الخدمات والحجوزات"
      descriptionIcon="info"
  >
    <div class="flex items-center gap-3">
      {{-- Excel Export --}}
      <a href="{{ route('admin.commissions.export', request()->query()) }}" class="px-4 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2">
        <i data-lucide="download" class="w-4 h-4"></i>
        <span class="hidden sm:inline">تصدير Excel</span>
      </a>
      {{-- Add New --}}
      <button
          type="button"
          data-create-modal="commission-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus-circle" class="w-4 h-4"></i>
        <span class="hidden sm:inline">إضافة عمولة جديدة</span>
      </button>
    </div>
  </x-page-header>

  {{-- ===== 4 Stat Cards ===== --}}
  <div id="aracari-refreshable">
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    {{-- Total — slate --}}
    <x-stat-card
        :value="$commissions->total()"
        label="إجمالي العمولات"
        icon="percent"
        variant="slate"
        badge="مسجّلة"
        badgeIcon="trending-up"
    />
    {{-- Fixed — emerald --}}
    <x-stat-card
        :value="$fixedCount"
        label="عمولات ثابتة"
        icon="banknote"
        variant="emerald"
        badge="ثابت"
        badgeIcon="banknote"
    />
    {{-- Percentage — blue --}}
    <x-stat-card
        :value="$percentageCount"
        label="عمولات نسبية"
        icon="percent"
        variant="blue"
        badge="نسبة"
        badgeIcon="percent"
    />
    {{-- Total value — amber --}}
    <x-stat-card
        :value="number_format($totalValue)"
        label="إجمالي القيم"
        icon="bar-chart-3"
        variant="amber"
        badge="إجمالي"
        badgeIcon="bar-chart-3"
    />
  </div>

  {{-- ===== Data Table ===== --}}
  <x-data-table-card
      :searchable="true"
      :searchMode="'server'"
      searchPlaceholder="ابحث بالوصف أو المزود أو رقم العمولة..."
      :searchValue="request('search')"
      :footerInfo="'عرض ' . $commissions->count() . ' من أصل ' . $commissions->total() . ' عمولة'"
  >
    <x-slot name="filters">
      {{-- Type Filter --}}
      <form id="aracari-type-filter-form" class="relative md:w-44 flex-1">
        <select
            name="type"
            id="aracari-type-filter"
            class="w-full h-11 bg-white/70 border border-slate-200 rounded-xl px-3 pr-8 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all appearance-none cursor-pointer"
        >
          <option value="">جميع الأنواع</option>
          <option value="fixed" @selected(request('type') === 'fixed')>ثابت</option>
          <option value="percentage" @selected(request('type') === 'percentage')>نسبة مئوية</option>
        </select>
        <i data-lucide="tag" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
        <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
      </form>

      {{-- Level Filter --}}
      <form id="aracari-level-filter-form" class="relative md:w-44 flex-1">
        <select
            name="level"
            id="aracari-level-filter"
            class="w-full h-11 bg-white/70 border border-slate-200 rounded-xl px-3 pr-8 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all appearance-none cursor-pointer"
        >
          <option value="">جميع المستويات</option>
          <option value="service" @selected(request('level') === 'service')">خدمة</option>
          <option value="booking" @selected(request('level') === 'booking')">حجز</option>
        </select>
        <i data-lucide="layers" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
        <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
      </form>
    </x-slot>

    <x-slot name="thead">
      <tr>
        <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
        <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">نوع العمولة</th>
        <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">المستوى</th>
        <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الوصف</th>
        <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">القيمة</th>
        <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الخدمات</th>
        <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحجوزات</th>
        <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
      </tr>
    </x-slot>

    <x-slot name="rows">
      @forelse($commissions as $index => $commission)
        @php
            $typeInfo = $typeMeta[$commission->commission_type] ?? ['label' => $commission->commission_type, 'bg' => 'bg-slate-50', 'text' => 'text-slate-700', 'border' => 'border-slate-200', 'icon' => 'circle'];
            $levelInfo = $levelMeta[$commission->calculation_level] ?? ['label' => $commission->calculation_level, 'icon' => 'circle'];
            $serviceCount = $commission->serviceCommissions->count();
            $bookingCount = $commission->bookingCommissions->count();

            // Determine a representative value for display
            $firstService = $commission->serviceCommissions->first();
            $firstBooking = $commission->bookingCommissions->first();
            $displayValue = $firstService ? $firstService->value : ($firstBooking ? $firstBooking->value : null);
        @endphp
        <tr class="hover:bg-slate-50/50 transition-colors group">

          {{-- # Row Number --}}
          <td class="px-6 py-3.5">
            <span class="font-mono font-bold text-sm text-slate-500">{{ str_pad(($commissions->firstItem() + $index), 4, '0', STR_PAD_LEFT) }}</span>
          </td>

          {{-- Type Badge --}}
          <td class="px-6 py-3.5 text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border {{ $typeInfo['bg'] }} {{ $typeInfo['text'] }} {{ $typeInfo['border'] }}">
              <i data-lucide="{{ $typeInfo['icon'] }}" class="w-3.5 h-3.5"></i>
              {{ $typeInfo['label'] }}
            </span>
          </td>

          {{-- Level Badge --}}
          <td class="px-6 py-3.5 text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border bg-slate-50 text-slate-700 border-slate-200">
              <i data-lucide="{{ $levelInfo['icon'] }}" class="w-3.5 h-3.5"></i>
              {{ $levelInfo['label'] }}
            </span>
          </td>

          {{-- Description --}}
          <td class="px-6 py-3.5">
            <p class="text-sm text-slate-700 line-clamp-2 max-w-xs">{{ $commission->description ?? '—' }}</p>
          </td>

          {{-- Value --}}
          <td class="px-6 py-3.5 text-center">
            @if($displayValue !== null)
              <span class="font-black text-teal-600 text-sm">
                {{ number_format((float) $displayValue) }}
                @if($commission->commission_type === 'percentage')
                  <span class="text-[10px] font-bold text-slate-400">%</span>
                @else
                  <span class="text-[10px] font-bold text-emerald-500">ريال</span>
                @endif
              </span>
            @else
              <span class="text-sm text-slate-400">—</span>
            @endif
          </td>

          {{-- Services Count --}}
          <td class="px-6 py-3.5 text-center">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold border {{ $serviceCount > 0 ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-slate-50 text-slate-400 border-slate-200' }}">
              <i data-lucide="building-2" class="w-3 h-3"></i>
              {{ $serviceCount }}
            </span>
          </td>

          {{-- Bookings Count --}}
          <td class="px-6 py-3.5 text-center">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold border {{ $bookingCount > 0 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-slate-50 text-slate-400 border-slate-200' }}">
              <i data-lucide="calendar-days" class="w-3 h-3"></i>
              {{ $bookingCount }}
            </span>
          </td>

          {{-- Actions --}}
          <td class="px-6 py-3.5 text-center">
            <div class="flex items-center justify-center gap-1.5">
              {{-- View Details --}}
              <x-action-button
                  variant="view"
                  data-view-modal="commission-details-modal"
                  data-view-json="{{ json_encode([
                      'uuid'            => $commission->uuid,
                      'commission_type' => $commission->commission_type,
                      'calculation_level'=> $commission->calculation_level,
                      'description'     => $commission->description,
                      'display_value'   => $displayValue,
                      'service_count'   => $serviceCount,
                      'booking_count'   => $bookingCount,
                      'created_at'      => $commission->created_at?->format('Y-m-d H:i:s'),
                      'updated_at'      => $commission->updated_at?->diffForHumans(),
                      'service_commissions' => $commission->serviceCommissions->map(fn($sc) => [
                          'provider_name'    => $sc->provider->name ?? '-',
                          'provider_cr'      => $sc->provider->cr_number ?? '',
                          'service_name'     => $sc->service->name ?? '-',
                          'value'            => $sc->value,
                          'min_amount'       => $sc->min_amount,
                          'max_amount'       => $sc->max_amount,
                      ])->toArray(),
                      'booking_commissions' => $commission->bookingCommissions->map(fn($bc) => [
                          'provider_name'    => $bc->provider->name ?? '-',
                          'provider_cr'      => $bc->provider->cr_number ?? '',
                          'value'            => $bc->value,
                          'min_amount'       => $bc->min_amount,
                          'max_amount'       => $bc->max_amount,
                      ])->toArray(),
                  ], JSON_UNESCAPED_UNICODE) }}"
                  :ariaLabel="'عرض عمولة'"
              />
              {{-- Edit --}}
              <x-action-button
                  variant="edit"
                  data-edit-modal="commission-form-modal"
                  data-edit-json="{{ json_encode([
                      'uuid'            => $commission->uuid,
                      'commission_type' => $commission->commission_type,
                      'calculation_level'=> $commission->calculation_level,
                      'description'     => $commission->description,
                      'display_value'   => $displayValue,
                      'service_count'   => $serviceCount,
                      'booking_count'   => $bookingCount,
                      'created_at'      => $commission->created_at?->format('Y-m-d H:i:s'),
                      'updated_at'      => $commission->updated_at?->diffForHumans(),
                  ], JSON_UNESCAPED_UNICODE) }}"
                  :ariaLabel="'تعديل عمولة'"
              />
              {{-- Delete --}}
              <x-action-button
                  variant="delete"
                  data-delete-url="{{ route('admin.commissions.destroy', $commission) }}"
                  data-delete-label="{{ $commission->description ?? $commission->uuid }}"
              />
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state
            icon="percent"
            :title="(request('search') || request('type') || request('level')) ? 'لا توجد نتائج مطابقة' : 'لا توجد عمولات مسجلة'"
            :message="(request('search') || request('type') || request('level')) ? 'جرّب تعديل كلمات البحث أو الفلتر' : 'ستظهر العمولات هنا فور إضافتها'"
            :colspan="8"
        >
          @if(!request('search') && !request('type') && !request('level'))
            <button
                type="button"
                data-create-modal="commission-form-modal"
                class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
            >
              <i data-lucide="plus-circle" class="w-4 h-4"></i>
              إضافة عمولة جديدة
            </button>
          @endif
        </x-empty-state>
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$commissions" />
    </x-slot>
  </x-data-table-card>
  </div>{{-- end #aracari-refreshable --}}

</div>

{{-- ======================================================================= --}}
{{-- ===== Commission Form Modal (Create + Edit) ===== --}}
{{-- ======================================================================= --}}
<x-form-modal
    id="commission-form-modal"
    title="إضافة عمولة جديدة"
    :variant="'create'"
    :action="route('admin.commissions.store')"
    method="POST"
    successMessage="تم حفظ العمولة بنجاح"
    maxWidth="max-w-2xl"
>

  {{-- القسم 1: معلومات العمولة الأساسية --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs flex items-center gap-1.5" id="commission-section-header" style="color: #115128;">
        <i data-lucide="percent" class="w-3.5 h-3.5"></i>
        معلومات العمولة الأساسية
      </h4>
      <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border text-rose-600 bg-rose-50 border-rose-200" id="commission-info-badge">مطلوب</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      {{-- نوع العمولة --}}
      <x-form-field name="commission_type" label="نوع العمولة" icon="tag" required>
        <div class="relative">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
            <i data-lucide="tag" class="w-4 h-4"></i>
          </div>
          <select
              name="commission_type"
              id="commission-type"
              required
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all appearance-none cursor-pointer text-right"
          >
            <option value="">اختر نوع العمولة</option>
            <option value="fixed" {{ old('commission_type') == 'fixed' ? 'selected' : '' }}>ثابت</option>
            <option value="percentage" {{ old('commission_type') == 'percentage' ? 'selected' : '' }}>نسبة مئوية</option>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
        </div>
      </x-form-field>

      {{-- مستوى الحساب --}}
      <x-form-field name="calculation_level" label="مستوى الحساب" icon="layers" required>
        <div class="relative">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
            <i data-lucide="layers" class="w-4 h-4"></i>
          </div>
          <select
              name="calculation_level"
              id="commission-level"
              required
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all appearance-none cursor-pointer text-right"
          >
            <option value="">اختر مستوى الحساب</option>
            <option value="service" {{ old('calculation_level') == 'service' ? 'selected' : '' }}>مستوى الخدمة</option>
            <option value="booking" {{ old('calculation_level') == 'booking' ? 'selected' : '' }}>مستوى الحجز</option>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
        </div>
      </x-form-field>

      {{-- القيمة — full width --}}
      <div class="space-y-1.5 md:col-span-2">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="commission-value">
          <i data-lucide="banknote" class="w-3.5 h-3.5"></i>
          القيمة <span class="text-rose-500">*</span>
        </label>
        <div class="relative">
          <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="banknote" class="w-4 h-4"></i>
          </div>
          <input
              type="number"
              name="value"
              id="commission-value"
              step="0.01"
              min="0"
              dir="ltr"
              value="{{ old('value') }}"
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-3 pl-10 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left"
              placeholder="مثال: 5.00"
          >
        </div>
        <p class="text-[10px] text-slate-500 leading-relaxed flex items-center gap-1">
          <i data-lucide="info" class="w-3 h-3 text-slate-400"></i>
          للنسبة المئوية: 5 = 5%. للثابت: المبلغ بالريال
        </p>
      </div>
    </div>
  </div>

  {{-- القسم 2: الوصف --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs flex items-center gap-1.5 text-purple-600">
        <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
        الوصف
      </h4>
      <span class="text-[9px] font-bold text-purple-500 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded-full">اختياري</span>
    </div>

    <x-form-field name="description" label="وصف العمولة" icon="file-text">
      <textarea
          name="description"
          id="commission-description"
          rows="4"
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl p-3 text-[13px] font-medium outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all resize-none text-right"
          placeholder="أدخل وصفاً مختصراً للعمولة (اختياري)..."
      >{{ old('description') }}</textarea>
    </x-form-field>
  </div>

  {{-- القسم 3: معلومات إضافية (للتعديل فقط) --}}
  <div class="space-y-4" id="commission-extra-info" style="display:none;">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs flex items-center gap-1.5 text-slate-600">
        <i data-lucide="info" class="w-3.5 h-3.5"></i>
        معلومات إضافية
      </h4>
      <span class="text-[9px] font-bold text-slate-500 bg-slate-50 border border-slate-200 px-2 py-0.5 rounded-full">للقراءة فقط</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div class="flex items-center gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
        <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">تاريخ الإنشاء:</span>
        <span class="text-xs font-mono text-slate-600" dir="ltr" id="commission-info-created">—</span>
      </div>
      <div class="flex items-center gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
        <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-slate-400"></i>
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">آخر تحديث:</span>
        <span class="text-xs font-bold text-slate-600" id="commission-info-updated">—</span>
      </div>
    </div>
  </div>

  {{-- Actions --}}
  <x-slot name="actions">
    <button
        type="button"
        data-close-modal
        class="flex-1 bg-[#f3f3f6] hover:bg-[#eeeef0] text-gray-600 font-bold py-3 px-4 rounded-xl text-xs transition-all flex items-center justify-center gap-2"
    >
      <i data-lucide="x" class="w-4 h-4"></i>
      <span id="commission-cancel-text">إعادة تعيين</span>
    </button>
    <button
        type="submit"
        class="flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95"
        id="commission-submit-btn"
        style="background: linear-gradient(to right, #115128, #0f3d20);"
    >
      <i data-lucide="save" class="w-4 h-4"></i>
      <span id="commission-submit-text">حفظ العمولة</span>
    </button>
  </x-slot>
</x-form-modal>

{{-- ======================================================================= --}}
{{-- ===== Commission Details Modal (View) ===== --}}
{{-- ======================================================================= --}}
<x-modal
    id="commission-details-modal"
    title="تفاصيل العمولة"
    variant="view"
    :maxWidth="'max-w-3xl'"
    data-no-reset
>
  <div class="space-y-5">
    {{-- معلومات العمولة --}}
    <div class="bg-white border border-[#eeeef0] rounded-2xl p-4 sm:p-5 shadow-sm">
      <div class="flex items-center justify-between border-b border-[#eeeef0] pb-3 mb-4">
        <h4 class="font-bold text-sm text-slate-800 flex items-center gap-2">
          <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center text-white shadow-md">
            <i data-lucide="info" class="w-4 h-4"></i>
          </div>
          معلومات العمولة
        </h4>
        <span class="text-[9px] font-bold text-teal-600 bg-teal-50 border border-teal-200 px-2 py-0.5 rounded-full">معلومات أساسية</span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        {{-- رقم العمولة --}}
        <div class="flex items-center justify-between gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
            <i data-lucide="hash" class="w-3.5 h-3.5"></i>
            رقم العمولة
          </span>
          <span class="text-xs font-mono text-slate-600" dir="ltr" id="details-uuid">—</span>
        </div>

        {{-- نوع العمولة --}}
        <div class="flex items-center justify-between gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
            <i data-lucide="percent" class="w-3.5 h-3.5"></i>
            نوع العمولة
          </span>
          <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border" id="details-type-badge">—</span>
        </div>

        {{-- مستوى الحساب --}}
        <div class="flex items-center justify-between gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
            <i data-lucide="layers" class="w-3.5 h-3.5"></i>
            مستوى الحساب
          </span>
          <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border bg-slate-100 text-slate-700 border-slate-200" id="details-level-badge">—</span>
        </div>

        {{-- القيمة --}}
        <div class="flex items-center justify-between gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
            <i data-lucide="banknote" class="w-3.5 h-3.5"></i>
            القيمة
          </span>
          <span class="text-sm font-black text-slate-800" dir="ltr" id="details-value">—</span>
        </div>

        {{-- تاريخ الإنشاء --}}
        <div class="flex items-center justify-between gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
            تاريخ الإنشاء
          </span>
          <span class="text-xs font-mono text-slate-600" dir="ltr" id="details-created">—</span>
        </div>

        {{-- الوصف — full width --}}
        <div class="md:col-span-2 flex items-start justify-between gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1 flex-shrink-0">
            <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
            الوصف
          </span>
          <span class="text-xs text-slate-600 leading-relaxed text-left" id="details-description">—</span>
        </div>
      </div>
    </div>

    {{-- الخدمات المرتبطة (مخفي افتراضياً) --}}
    <div class="bg-white border border-[#eeeef0] rounded-2xl p-4 sm:p-5 shadow-sm" id="details-services-card" style="display:none;">
      <div class="flex items-center justify-between border-b border-[#eeeef0] pb-3 mb-3">
        <h4 class="font-bold text-sm text-slate-800 flex items-center gap-2">
          <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center text-white shadow-md">
            <i data-lucide="building-2" class="w-4 h-4"></i>
          </div>
          الخدمات المرتبطة
        </h4>
        <span class="text-[9px] font-bold text-teal-600 bg-teal-50 border border-teal-200 px-2 py-0.5 rounded-full" id="details-services-count">0 خدمة</span>
      </div>

      <div class="overflow-x-auto min-w-0 -mx-2" id="details-services-table-wrap">
        <table class="w-full min-w-[640px]">
          <thead class="bg-slate-50/70 border-b border-slate-200/70">
            <tr>
              <th class="px-3 py-2.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
              <th class="px-3 py-2.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المزود</th>
              <th class="px-3 py-2.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الخدمة</th>
              <th class="px-3 py-2.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحد الأدنى</th>
              <th class="px-3 py-2.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحد الأعلى</th>
              <th class="px-3 py-2.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">القيمة</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100" id="details-services-tbody">
          </tbody>
        </table>
      </div>

      {{-- Empty state for services --}}
      <div class="text-center py-8 text-slate-400" id="details-services-empty" style="display:none;">
        <i data-lucide="building-2" class="w-8 h-8 mx-auto mb-1.5 opacity-50"></i>
        <p class="text-xs font-bold">لا توجد خدمات مرتبطة</p>
      </div>
    </div>

    {{-- الحجوزات المرتبطة (مخفي افتراضياً) --}}
    <div class="bg-white border border-[#eeeef0] rounded-2xl p-4 sm:p-5 shadow-sm" id="details-bookings-card" style="display:none;">
      <div class="flex items-center justify-between border-b border-[#eeeef0] pb-3 mb-3">
        <h4 class="font-bold text-sm text-slate-800 flex items-center gap-2">
          <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center text-white shadow-md">
            <i data-lucide="calendar-days" class="w-4 h-4"></i>
          </div>
          الحجوزات المرتبطة
        </h4>
        <span class="text-[9px] font-bold text-amber-600 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full" id="details-bookings-count">0 حجز</span>
      </div>

      <div class="overflow-x-auto min-w-0 -mx-2" id="details-bookings-table-wrap">
        <table class="w-full min-w-[640px]">
          <thead class="bg-slate-50/70 border-b border-slate-200/70">
            <tr>
              <th class="px-3 py-2.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
              <th class="px-3 py-2.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المزود</th>
              <th class="px-3 py-2.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحد الأدنى</th>
              <th class="px-3 py-2.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحد الأعلى</th>
              <th class="px-3 py-2.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">القيمة</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100" id="details-bookings-tbody">
          </tbody>
        </table>
      </div>

      {{-- Empty state for bookings --}}
      <div class="text-center py-8 text-slate-400" id="details-bookings-empty" style="display:none;">
        <i data-lucide="calendar-days" class="w-8 h-8 mx-auto mb-1.5 opacity-50"></i>
        <p class="text-xs font-bold">لا توجد حجوزات مرتبطة</p>
      </div>
    </div>
  </div>

  {{-- Footer actions --}}
  <x-slot name="actions">
    <button
        type="button"
        data-close-modal
        class="flex-1 bg-[#f3f3f6] hover:bg-[#eeeef0] text-gray-600 font-bold py-3 px-4 rounded-xl text-xs transition-all flex items-center justify-center gap-2"
    >
      <i data-lucide="x" class="w-4 h-4"></i>
      إغلاق
    </button>
    <button
        type="button"
        id="details-edit-btn"
        class="flex-[2] bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-lg shadow-amber-500/20 flex items-center justify-center gap-2"
    >
      <i data-lucide="pencil" class="w-4 h-4"></i>
      تعديل
    </button>
  </x-slot>
</x-modal>

<x-delete-confirm />

@endsection

@push('scripts')
<script>
  // ===== Type filter: redirect with type param =====
  (function() {
    var typeSelect = document.getElementById('aracari-type-filter');
    if (!typeSelect) return;
    typeSelect.addEventListener('change', function() {
      var params = new URLSearchParams(window.location.search);
      var search = params.get('search');
      var level  = params.get('level');
      var url = window.location.pathname;
      var queryParts = [];
      if (this.value) queryParts.push('type=' + encodeURIComponent(this.value));
      if (level) queryParts.push('level=' + encodeURIComponent(level));
      if (search) queryParts.push('search=' + encodeURIComponent(search));
      if (queryParts.length > 0) url += '?' + queryParts.join('&');
      window.location.href = url;
    });
  })();

  // ===== Level filter: redirect with level param =====
  (function() {
    var levelSelect = document.getElementById('aracari-level-filter');
    if (!levelSelect) return;
    levelSelect.addEventListener('change', function() {
      var params = new URLSearchParams(window.location.search);
      var search = params.get('search');
      var type   = params.get('type');
      var url = window.location.pathname;
      var queryParts = [];
      if (this.value) queryParts.push('level=' + encodeURIComponent(this.value));
      if (type) queryParts.push('type=' + encodeURIComponent(type));
      if (search) queryParts.push('search=' + encodeURIComponent(search));
      if (queryParts.length > 0) url += '?' + queryParts.join('&');
      window.location.href = url;
    });
  })();

  // ===== Commission form modal: dynamic action + title + variant switching =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'commission-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.uuid);

    if (isEdit) {
      // ---- EDIT MODE ----
      var updateUrl = "{{ route('admin.commissions.update', '__UUID__') }}".replace('__UUID__', data.uuid);
      form.setAttribute('action', updateUrl);
      var existingMethod = form.querySelector('input[name="_method"]');
      if (existingMethod) existingMethod.value = 'PUT';
      else {
        var m = document.createElement('input');
        m.type = 'hidden'; m.name = '_method'; m.value = 'PUT';
        m.setAttribute('data-dynamic', 'true');
        form.appendChild(m);
      }

      // Banner → amber
      var banner = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (banner) banner.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';

      // Title
      var titleEl = modal.querySelector('.aracari-modal-panel h2');
      if (titleEl) titleEl.innerHTML = '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل العمولة';

      // Badge
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }

      // Subtitle: UUID
      var subtitleEl = modal.querySelector('.aracari-modal-panel .relative.z-10 p');
      if (subtitleEl) {
        subtitleEl.innerHTML = '<span class="inline-block text-xs text-white/80 bg-slate-900/40 border border-white/10 px-2 py-0.5 rounded-full max-w-[260px] truncate align-middle" dir="ltr">#' + (data.uuid ? data.uuid.substring(0, 12) : '') + '</span>';
        subtitleEl.style.display = '';
      }

      form.setAttribute('data-success-message', 'تم تحديث العمولة بنجاح');

      // Info badge
      var infoBadge = modal.querySelector('#commission-info-badge');
      if (infoBadge) {
        infoBadge.textContent = 'قابل للتعديل';
        infoBadge.className = 'text-[9px] font-bold px-2 py-0.5 rounded-full border text-amber-600 bg-amber-50 border-amber-200';
      }

      // Section header
      var sectionHeader = modal.querySelector('#commission-section-header');
      if (sectionHeader) sectionHeader.style.color = '#b45309';

      // Show extra info
      var extraInfo = modal.querySelector('#commission-extra-info');
      if (extraInfo) extraInfo.style.display = '';
      var infoCreated = modal.querySelector('#commission-info-created');
      if (infoCreated) infoCreated.textContent = data.created_at || '—';
      var infoUpdated = modal.querySelector('#commission-info-updated');
      if (infoUpdated) infoUpdated.textContent = data.updated_at || '—';

      // Fill form fields
      var typeSelect = modal.querySelector('#commission-type');
      if (typeSelect) typeSelect.value = data.commission_type || '';
      var levelSelect = modal.querySelector('#commission-level');
      if (levelSelect) levelSelect.value = data.calculation_level || '';
      var valueInput = modal.querySelector('#commission-value');
      if (valueInput) valueInput.value = data.display_value || '';
      var descInput = modal.querySelector('#commission-description');
      if (descInput) descInput.value = data.description || '';

      // Cancel text
      var cancelText = modal.querySelector('#commission-cancel-text');
      if (cancelText) cancelText.textContent = 'إلغاء';

      // Submit button → amber
      var submitBtn = modal.querySelector('#commission-submit-btn');
      if (submitBtn) {
        submitBtn.className = 'flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95 bg-amber-600 hover:bg-amber-700';
      }
      var submitText = modal.querySelector('#commission-submit-text');
      if (submitText) submitText.textContent = 'حفظ التعديلات';

    } else {
      // ---- CREATE MODE ----
      form.setAttribute('action', "{{ route('admin.commissions.store') }}");
      var m = form.querySelector('input[name="_method"]');
      if (m) m.remove();

      // Banner → teal
      var banner = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (banner) banner.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';

      // Title
      var titleEl = modal.querySelector('.aracari-modal-panel h2');
      if (titleEl) titleEl.innerHTML = '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة عمولة جديدة';

      // Badge
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30';
        badge.textContent = 'جديد';
      }

      // Subtitle
      var subtitleEl = modal.querySelector('.aracari-modal-panel .relative.z-10 p');
      if (subtitleEl) {
        subtitleEl.textContent = 'تحديد نوع العمولة (ثابت / نسبة مئوية) ومستوى الحساب (خدمة / حجز)';
        subtitleEl.style.display = '';
      }

      form.setAttribute('data-success-message', 'تم حفظ العمولة بنجاح');

      // Info badge
      var infoBadge = modal.querySelector('#commission-info-badge');
      if (infoBadge) {
        infoBadge.textContent = 'مطلوب';
        infoBadge.className = 'text-[9px] font-bold px-2 py-0.5 rounded-full border text-rose-600 bg-rose-50 border-rose-200';
      }

      // Section header
      var sectionHeader = modal.querySelector('#commission-section-header');
      if (sectionHeader) sectionHeader.style.color = '#115128';

      // Hide extra info
      var extraInfo = modal.querySelector('#commission-extra-info');
      if (extraInfo) extraInfo.style.display = 'none';

      // Reset form fields
      var typeSelect = modal.querySelector('#commission-type');
      if (typeSelect) typeSelect.value = '';
      var levelSelect = modal.querySelector('#commission-level');
      if (levelSelect) levelSelect.value = '';
      var valueInput = modal.querySelector('#commission-value');
      if (valueInput) valueInput.value = '';
      var descInput = modal.querySelector('#commission-description');
      if (descInput) descInput.value = '';

      // Cancel text
      var cancelText = modal.querySelector('#commission-cancel-text');
      if (cancelText) cancelText.textContent = 'إعادة تعيين';

      // Submit button → teal
      var submitBtn = modal.querySelector('#commission-submit-btn');
      if (submitBtn) {
        submitBtn.className = 'flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95';
        submitBtn.style.background = 'linear-gradient(to right, #115128, #0f3d20)';
      }
      var submitText = modal.querySelector('#commission-submit-text');
      if (submitText) submitText.textContent = 'حفظ العمولة';
    }

    // Re-render lucide icons
    if (window.lucide) window.lucide.createIcons();
  });

  // ===== Commission details modal: populate data =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'commission-details-modal') return;
    var data = e.detail.data || {};

    // UUID
    var uuidEl = modal.querySelector('#details-uuid');
    if (uuidEl) uuidEl.textContent = data.uuid || '—';

    // Type badge
    var typeBadge = modal.querySelector('#details-type-badge');
    if (typeBadge) {
      if (data.commission_type === 'fixed') {
        typeBadge.className = 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border bg-emerald-50 text-emerald-700 border-emerald-200';
        typeBadge.innerHTML = '<i data-lucide="banknote" class="w-2.5 h-2.5"></i> ثابت';
      } else if (data.commission_type === 'percentage') {
        typeBadge.className = 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border bg-blue-50 text-blue-700 border-blue-200';
        typeBadge.innerHTML = '<i data-lucide="percent" class="w-2.5 h-2.5"></i> نسبة مئوية';
      } else {
        typeBadge.textContent = data.commission_type || '—';
      }
    }

    // Level badge
    var levelBadge = modal.querySelector('#details-level-badge');
    if (levelBadge) {
      if (data.calculation_level === 'service') {
        levelBadge.innerHTML = '<i data-lucide="building-2" class="w-2.5 h-2.5"></i> خدمة';
      } else if (data.calculation_level === 'booking') {
        levelBadge.innerHTML = '<i data-lucide="calendar-days" class="w-2.5 h-2.5"></i> حجز';
      } else {
        levelBadge.textContent = data.calculation_level || '—';
      }
    }

    // Value
    var valueEl = modal.querySelector('#details-value');
    if (valueEl) {
      var val = data.display_value !== null && data.display_value !== undefined ? Number(data.display_value).toLocaleString('en-US', {maximumFractionDigits: 2}) : '—';
      var suffix = data.commission_type === 'percentage' ? '<span class="text-blue-600">%</span>' : '';
      valueEl.innerHTML = val + suffix;
    }

    // Created at
    var createdEl = modal.querySelector('#details-created');
    if (createdEl) createdEl.textContent = data.created_at || '—';

    // Description
    var descEl = modal.querySelector('#details-description');
    if (descEl) descEl.textContent = data.description || 'لا يوجد وصف';

    // Services card
    var servicesCard = modal.querySelector('#details-services-card');
    var bookingsCard = modal.querySelector('#details-bookings-card');
    var servicesTbody = modal.querySelector('#details-services-tbody');
    var servicesEmpty = modal.querySelector('#details-services-empty');
    var servicesTableWrap = modal.querySelector('#details-services-table-wrap');
    var servicesCount = modal.querySelector('#details-services-count');
    var bookingsTbody = modal.querySelector('#details-bookings-tbody');
    var bookingsEmpty = modal.querySelector('#details-bookings-empty');
    var bookingsTableWrap = modal.querySelector('#details-bookings-table-wrap');
    var bookingsCount = modal.querySelector('#details-bookings-count');

    var serviceCommissions = data.service_commissions || [];
    var bookingCommissions = data.booking_commissions || [];

    // Show services card if level === 'service'
    if (servicesCard) {
      if (data.calculation_level === 'service') {
        servicesCard.style.display = '';
        if (servicesCount) servicesCount.textContent = serviceCommissions.length + ' خدمة';
        if (serviceCommissions.length > 0) {
          if (servicesTbody) {
            servicesTbody.innerHTML = serviceCommissions.map(function(sc, idx) {
              var valStr = Number(sc.value).toLocaleString('en-US', {maximumFractionDigits: 2});
              var suffix = data.commission_type === 'percentage' ? '%' : '';
              var minStr = sc.min_amount ? Number(sc.min_amount).toLocaleString('en-US', {maximumFractionDigits: 2}) : '∞';
              var maxStr = sc.max_amount ? Number(sc.max_amount).toLocaleString('en-US', {maximumFractionDigits: 2}) : '∞';
              return '<tr class="hover:bg-slate-50/50 transition-colors">' +
                '<td class="px-3 py-2.5"><span class="font-mono font-black text-slate-400 text-xs">' + String(idx + 1).padStart(3, '0') + '</span></td>' +
                '<td class="px-3 py-2.5"><div class="flex items-center gap-2"><i data-lucide="building-2" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0"></i><div><div class="text-xs font-bold text-slate-700">' + (sc.provider_name || '-') + '</div>' + (sc.provider_cr ? '<div class="text-[10px] font-mono text-slate-400" dir="ltr">' + sc.provider_cr + '</div>' : '') + '</div></div></td>' +
                '<td class="px-3 py-2.5"><span class="text-xs font-bold text-slate-700">' + (sc.service_name || '-') + '</span></td>' +
                '<td class="px-3 py-2.5 text-center"><span class="text-xs font-mono text-slate-600" dir="ltr">' + minStr + '</span></td>' +
                '<td class="px-3 py-2.5 text-center"><span class="text-xs font-mono text-slate-600" dir="ltr">' + maxStr + '</span></td>' +
                '<td class="px-3 py-2.5 text-center"><span class="text-sm font-black text-slate-800" dir="ltr">' + valStr + suffix + '</span></td>' +
                '</tr>';
            }).join('');
          }
          if (servicesEmpty) servicesEmpty.style.display = 'none';
          if (servicesTableWrap) servicesTableWrap.style.display = '';
        } else {
          if (servicesTbody) servicesTbody.innerHTML = '';
          if (servicesEmpty) servicesEmpty.style.display = '';
          if (servicesTableWrap) servicesTableWrap.style.display = 'none';
        }
      } else {
        servicesCard.style.display = 'none';
      }
    }

    // Show bookings card if level === 'booking'
    if (bookingsCard) {
      if (data.calculation_level === 'booking') {
        bookingsCard.style.display = '';
        if (bookingsCount) bookingsCount.textContent = bookingCommissions.length + ' حجز';
        if (bookingCommissions.length > 0) {
          if (bookingsTbody) {
            bookingsTbody.innerHTML = bookingCommissions.map(function(bc, idx) {
              var valStr = Number(bc.value).toLocaleString('en-US', {maximumFractionDigits: 2});
              var suffix = data.commission_type === 'percentage' ? '%' : '';
              var minStr = bc.min_amount ? Number(bc.min_amount).toLocaleString('en-US', {maximumFractionDigits: 2}) : '∞';
              var maxStr = bc.max_amount ? Number(bc.max_amount).toLocaleString('en-US', {maximumFractionDigits: 2}) : '∞';
              return '<tr class="hover:bg-slate-50/50 transition-colors">' +
                '<td class="px-3 py-2.5"><span class="font-mono font-black text-slate-400 text-xs">' + String(idx + 1).padStart(3, '0') + '</span></td>' +
                '<td class="px-3 py-2.5"><div class="flex items-center gap-2"><i data-lucide="building-2" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0"></i><div><div class="text-xs font-bold text-slate-700">' + (bc.provider_name || '-') + '</div>' + (bc.provider_cr ? '<div class="text-[10px] font-mono text-slate-400" dir="ltr">' + bc.provider_cr + '</div>' : '') + '</div></div></td>' +
                '<td class="px-3 py-2.5 text-center"><span class="text-xs font-mono text-slate-600" dir="ltr">' + minStr + '</span></td>' +
                '<td class="px-3 py-2.5 text-center"><span class="text-xs font-mono text-slate-600" dir="ltr">' + maxStr + '</span></td>' +
                '<td class="px-3 py-2.5 text-center"><span class="text-sm font-black text-slate-800" dir="ltr">' + valStr + suffix + '</span></td>' +
                '</tr>';
            }).join('');
          }
          if (bookingsEmpty) bookingsEmpty.style.display = 'none';
          if (bookingsTableWrap) bookingsTableWrap.style.display = '';
        } else {
          if (bookingsTbody) bookingsTbody.innerHTML = '';
          if (bookingsEmpty) bookingsEmpty.style.display = '';
          if (bookingsTableWrap) bookingsTableWrap.style.display = 'none';
        }
      } else {
        bookingsCard.style.display = 'none';
      }
    }

    // Edit button in details modal → open edit modal
    var editBtn = modal.querySelector('#details-edit-btn');
    if (editBtn) {
      editBtn.onclick = function() {
        // Close details modal
        var closeBtn = modal.querySelector('[data-close-modal]');
        if (closeBtn) closeBtn.click();

        // Open edit modal with same data
        setTimeout(function() {
          var formModal = document.getElementById('commission-form-modal');
          if (formModal) {
            formModal.dispatchEvent(new CustomEvent('aracari:modal-open', {
              detail: { data: data },
              bubbles: true
            }));
          }
        }, 200);
      };
    }

    // Re-render lucide icons
    if (window.lucide) window.lucide.createIcons();
  });
</script>
@endpush

