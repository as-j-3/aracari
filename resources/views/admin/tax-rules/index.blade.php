@extends('layouts.admin')

@section('title', 'أركاري | القواعد الضريبية')
@section('header_title', 'القواعد الضريبية')

@section('content')

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="gavel"
      title="القواعد الضريبية"
      :count="$taxRules->total()"
      countLabel="قاعدة"
      description="إدارة القواعد الضريبية المطبقة على الرحلات"
      descriptionIcon="info"
  >
    <button
        type="button"
        data-create-modal="tax-rule-form-modal"
        class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
    >
      <i data-lucide="plus-circle" class="w-4 h-4"></i>
      <span class="hidden sm:inline">إضافة قاعدة جديدة</span>
    </button>
  </x-page-header>

  {{-- ===== 4 Stat Cards ===== --}}
  <div id="aracari-refreshable">
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    <x-stat-card :value="$stats['total']" label="إجمالي القواعد" icon="gavel" variant="slate" badge="مسجّلة" badgeIcon="trending-up" />
    <x-stat-card :value="$stats['active']" label="قواعد نشطة" icon="check-circle" variant="emerald" badge="نشط" badgeIcon="check-circle" />
    <x-stat-card :value="$stats['inactive']" label="قواعد غير نشطة" icon="x-circle" variant="rose" badge="غير نشط" badgeIcon="x-circle" />
    <x-stat-card :value="$stats['with_limits']" label="قواعد مع حدود" icon="sliders-horizontal" variant="blue" badge="بحدود" badgeIcon="sliders-horizontal" />
  </div>

  {{-- ===== Data Table ===== --}}
  <x-data-table-card
      :searchable="true"
      :searchMode="'server'"
      searchPlaceholder="ابحث عن قاعدة بالضريبة أو الدولة أو المزود أو الرحلة..."
      :searchValue="request('search')"
      :footerInfo="'عرض ' . $taxRules->count() . ' من أصل ' . $taxRules->total() . ' قاعدة'"
  >
    <x-slot name="filters">
      {{-- Status Filter --}}
      <form id="aracari-status-filter-form" class="relative md:w-44 flex-1">
        <select
            name="status"
            id="aracari-status-filter"
            class="w-full h-11 bg-white/70 border border-slate-200 rounded-xl px-3 pr-8 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all appearance-none cursor-pointer"
        >
          <option value="">جميع الحالات</option>
          <option value="active" @selected(request('status') === 'active')">نشط</option>
          <option value="inactive" @selected(request('status') === 'inactive')">غير نشط</option>
        </select>
        <i data-lucide="check-circle" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
        <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
      </form>
    </x-slot>

    <x-slot name="thead">
      <tr>
        <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
        <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الضريبة</th>
        <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الدولة</th>
        <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المزود</th>
        <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الرحلة</th>
        <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحدود</th>
        <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
        <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
      </tr>
    </x-slot>

    <x-slot name="rows">
      @forelse($taxRules as $index => $rule)
        @php
            $hasLimits = $rule->min_amount !== null || $rule->max_amount !== null;
            $taxName = $rule->tax->name ?? '—';
            $taxType = $rule->tax->type ?? '—';
        @endphp
        <tr class="hover:bg-slate-50/50 transition-colors group">

          {{-- # --}}
          <td class="px-6 py-3.5">
            <span class="font-mono font-black text-slate-400 text-sm">{{ str_pad(($taxRules->firstItem() + $index), 4, '0', STR_PAD_LEFT) }}</span>
          </td>

          {{-- الضريبة --}}
          <td class="px-6 py-3.5">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500/20 to-teal-600/10 flex items-center justify-center border border-teal-100 group-hover:scale-110 transition-transform duration-300">
                <i data-lucide="receipt" class="w-5 h-5 text-teal-600"></i>
              </div>
              <div>
                <div class="font-bold text-slate-800 text-sm">{{ $taxName }}</div>
                <div class="text-[10px] font-bold text-slate-400">
                  {{ $taxType === 'percentage' ? 'نسبة مئوية' : ($taxType === 'fixed' ? 'مبلغ ثابت' : $taxType) }}
                </div>
              </div>
            </div>
          </td>

          {{-- الدولة --}}
          <td class="px-6 py-3.5">
            <div class="flex items-center gap-2">
              <i data-lucide="flag" class="w-4 h-4 text-slate-400 flex-shrink-0"></i>
              <span class="text-sm font-bold {{ $rule->country ? 'text-slate-700' : 'text-slate-400' }}">
                {{ $rule->country ? $rule->country->name : 'الكل' }}
              </span>
            </div>
          </td>

          {{-- المزود --}}
          <td class="px-6 py-3.5">
            <div class="flex items-center gap-2">
              <i data-lucide="building-2" class="w-4 h-4 text-slate-400 flex-shrink-0"></i>
              <span class="text-sm font-bold {{ $rule->provider ? 'text-slate-700' : 'text-slate-400' }}">
                {{ $rule->provider ? $rule->provider->name : 'الكل' }}
              </span>
            </div>
          </td>

          {{-- الرحلة --}}
          <td class="px-6 py-3.5">
            <div class="flex items-center gap-2">
              <i data-lucide="bus" class="w-4 h-4 text-slate-400 flex-shrink-0"></i>
              <span class="text-sm font-bold {{ $rule->trip ? 'text-slate-700' : 'text-slate-400' }}">
                {{ $rule->trip ? ($rule->trip->trip_number ?? 'رحلة #'.$rule->trip_id) : 'الكل' }}
              </span>
            </div>
          </td>

          {{-- الحدود --}}
          <td class="px-6 py-3.5 text-center">
            @if($hasLimits)
              <div class="inline-flex flex-col items-center gap-1">
                <span class="text-xs font-mono font-bold text-slate-600" dir="ltr">
                  {{ $rule->min_amount ? number_format((float) $rule->min_amount) : '0' }} → {{ $rule->max_amount ? number_format((float) $rule->max_amount) : '∞' }}
                </span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 text-[9px] font-bold border border-blue-200">
                  <i data-lucide="sliders-horizontal" class="w-2.5 h-2.5"></i>
                  محدد
                </span>
              </div>
            @else
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-[9px] font-bold border border-slate-200">
                غير محدود
              </span>
            @endif
          </td>

          {{-- الحالة --}}
          <td class="px-6 py-3.5 text-center">
            @if($rule->is_active)
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <i data-lucide="check-circle" class="w-3 h-3"></i>
                نشط
              </span>
            @else
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-rose-50 text-rose-700 text-[10px] font-bold border border-rose-200">
                <i data-lucide="x-circle" class="w-3 h-3"></i>
                غير نشط
              </span>
            @endif
          </td>

          {{-- الإجراءات --}}
          <td class="px-6 py-3.5 text-center">
            <div class="flex items-center justify-center gap-1.5">
              <x-action-button
                  variant="view"
                  data-view-modal="tax-rule-details-modal"
                  data-view-json="{{ json_encode([
                      'id'                => $rule->id,
                      'taxName'           => $taxName,
                      'taxType'           => $taxType,
                      'countryName'       => $rule->country ? $rule->country->name : null,
                      'providerName'      => $rule->provider ? $rule->provider->name : null,
                      'tripName'          => $rule->trip ? ($rule->trip->trip_number ?? null) : null,
                      'serviceTypeName'   => $rule->serviceType ? $rule->serviceType->type_name : null,
                      'paymentMethodName' => $rule->paymentMethod ? $rule->paymentMethod->method_name : null,
                      'currencyCode'      => $rule->currency ? $rule->currency->code : null,
                      'minAmount'         => $rule->min_amount ? (float) $rule->min_amount : null,
                      'maxAmount'         => $rule->max_amount ? (float) $rule->max_amount : null,
                      'isActive'          => $rule->is_active,
                      'createdAt'         => $rule->created_at?->format('Y-m-d H:i:s'),
                      'updatedAt'         => $rule->updated_at?->diffForHumans(),
                  ], JSON_UNESCAPED_UNICODE) }}"
                  :ariaLabel="'عرض القاعدة'"
              />
              <x-action-button
                  variant="edit"
                  data-edit-modal="tax-rule-form-modal"
                  data-edit-json="{{ json_encode([
                      'id'                => $rule->id,
                      'tax_id'            => $rule->tax_id,
                      'taxName'           => $taxName,
                      'country_id'        => $rule->country_id,
                      'countryName'       => $rule->country ? $rule->country->name : null,
                      'provider_id'       => $rule->provider_id,
                      'providerName'      => $rule->provider ? $rule->provider->name : null,
                      'service_type_id'   => $rule->service_type_id,
                      'serviceTypeName'   => $rule->serviceType ? $rule->serviceType->type_name : null,
                      'payment_method_id' => $rule->payment_method_id,
                      'paymentMethodName' => $rule->paymentMethod ? $rule->paymentMethod->method_name : null,
                      'trip_id'           => $rule->trip_id,
                      'tripName'          => $rule->trip ? ($rule->trip->trip_number ?? null) : null,
                      'currency_id'       => $rule->currency_id,
                      'currencyCode'      => $rule->currency ? $rule->currency->code : null,
                      'min_amount'        => $rule->min_amount ? (float) $rule->min_amount : null,
                      'max_amount'        => $rule->max_amount ? (float) $rule->max_amount : null,
                      'is_active'         => $rule->is_active,
                      'createdAt'         => $rule->created_at?->format('Y-m-d H:i:s'),
                      'updatedAt'         => $rule->updated_at?->diffForHumans(),
                  ], JSON_UNESCAPED_UNICODE) }}"
                  :ariaLabel="'تعديل القاعدة'"
              />
              <x-action-button
                  variant="delete"
                  data-delete-url="{{ route('admin.tax-rules.destroy', $rule->id) }}"
                  data-delete-label="{{ $taxName }}"
              />
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state
            icon="gavel"
            :title="(request('search') || request('status')) ? 'لا توجد نتائج مطابقة' : 'لا توجد قواعد ضريبية'"
            :message="(request('search') || request('status')) ? 'جرّب تعديل كلمات البحث أو الفلتر' : 'أضف قواعد ضريبية جديدة لتطبيقها على الرحلات'"
            :colspan="8"
        >
          @if(!request('search') && !request('status'))
            <button
                type="button"
                data-create-modal="tax-rule-form-modal"
                class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
            >
              <i data-lucide="plus-circle" class="w-4 h-4"></i>
              إضافة قاعدة جديدة
            </button>
          @endif
        </x-empty-state>
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$taxRules" />
    </x-slot>
  </x-data-table-card>
  </div>{{-- end #aracari-refreshable --}}

</div>

{{-- ======================================================================= --}}
{{-- ===== Tax Rule Form Modal (Create + Edit) ===== --}}
{{-- ======================================================================= --}}
<x-form-modal
    id="tax-rule-form-modal"
    title="إضافة قاعدة ضريبية جديدة"
    :variant="'create'"
    :action="route('admin.tax-rules.store')"
    method="POST"
    successMessage="تم حفظ القاعدة الضريبية بنجاح"
    maxWidth="max-w-3xl"
>

  {{-- القسم 1: الضريبة --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs flex items-center gap-1.5" style="color: #115128;">
        <i data-lucide="receipt" class="w-3.5 h-3.5"></i>
        الضريبة
      </h4>
      <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border text-rose-600 bg-rose-50 border-rose-200">مطلوب</span>
    </div>
    <div class="space-y-1.5">
      <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="tr-tax">
        <i data-lucide="receipt" class="w-3.5 h-3.5"></i>
        اختر الضريبة <span class="text-rose-500">*</span>
      </label>
      <div class="relative">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
          <i data-lucide="receipt" class="w-4 h-4"></i>
        </div>
        <select
            name="tax_id"
            id="tr-tax"
            required
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all appearance-none cursor-pointer text-right"
        >
          <option value="">اختر الضريبة...</option>
          @foreach($taxes as $t)
            <option value="{{ $t->id }}" {{ old('tax_id') == $t->id ? 'selected' : '' }}>{{ $t->name }} ({{ $t->type === 'percentage' ? 'نسبة ' . $t->rate . '%' : 'ثابت ' . $t->amount . ' ر.ي' }})</option>
          @endforeach
        </select>
        <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
      </div>
      @error('tax_id')
        <p class="text-[10px] text-rose-600 flex items-center gap-1">
          <i data-lucide="alert-circle" class="w-3 h-3"></i> {{ $message }}
        </p>
      @enderror
    </div>
  </div>

  {{-- القسم 2: نطاق التطبيق --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs flex items-center gap-1.5 text-purple-600">
        <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i>
        نطاق التطبيق
      </h4>
      <span class="text-[9px] font-bold text-purple-500 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded-full">اختياري (الكل = فارغ)</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

      {{-- الدولة --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="tr-country">
          <i data-lucide="flag" class="w-3.5 h-3.5"></i> الدولة
        </label>
        <div class="relative">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="flag" class="w-4 h-4"></i>
          </div>
          <select name="country_id" id="tr-country" class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all appearance-none cursor-pointer text-right">
            <option value="">الكل</option>
            @foreach($countries as $c)
              <option value="{{ $c->id }}" {{ old('country_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
        </div>
        @error('country_id')
          <p class="text-[10px] text-rose-600 flex items-center gap-1"><i data-lucide="alert-circle" class="w-3 h-3"></i> {{ $message }}</p>
        @enderror
      </div>

      {{-- المزود --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="tr-provider">
          <i data-lucide="building-2" class="w-3.5 h-3.5"></i> المزود
        </label>
        <div class="relative">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="building-2" class="w-4 h-4"></i>
          </div>
          <select name="provider_id" id="tr-provider" class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all appearance-none cursor-pointer text-right">
            <option value="">الكل</option>
            @foreach($providers as $p)
              <option value="{{ $p->id }}" {{ old('provider_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
        </div>
        @error('provider_id')
          <p class="text-[10px] text-rose-600 flex items-center gap-1"><i data-lucide="alert-circle" class="w-3 h-3"></i> {{ $message }}</p>
        @enderror
      </div>

      {{-- نوع الخدمة --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="tr-service-type">
          <i data-lucide="wrench" class="w-3.5 h-3.5"></i> نوع الخدمة
        </label>
        <div class="relative">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="wrench" class="w-4 h-4"></i>
          </div>
          <select name="service_type_id" id="tr-service-type" class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all appearance-none cursor-pointer text-right">
            <option value="">الكل</option>
            @foreach($serviceTypes as $st)
              <option value="{{ $st->id }}" {{ old('service_type_id') == $st->id ? 'selected' : '' }}>{{ $st->type_name }}</option>
            @endforeach
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
        </div>
        @error('service_type_id')
          <p class="text-[10px] text-rose-600 flex items-center gap-1"><i data-lucide="alert-circle" class="w-3 h-3"></i> {{ $message }}</p>
        @enderror
      </div>

      {{-- وسيلة الدفع --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="tr-payment-method">
          <i data-lucide="wallet" class="w-3.5 h-3.5"></i> وسيلة الدفع
        </label>
        <div class="relative">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="wallet" class="w-4 h-4"></i>
          </div>
          <select name="payment_method_id" id="tr-payment-method" class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all appearance-none cursor-pointer text-right">
            <option value="">الكل</option>
            @foreach($paymentMethods as $pm)
              <option value="{{ $pm->id }}" {{ old('payment_method_id') == $pm->id ? 'selected' : '' }}>{{ $pm->method_name }}</option>
            @endforeach
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
        </div>
        @error('payment_method_id')
          <p class="text-[10px] text-rose-600 flex items-center gap-1"><i data-lucide="alert-circle" class="w-3 h-3"></i> {{ $message }}</p>
        @enderror
      </div>

      {{-- الرحلة --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="tr-trip">
          <i data-lucide="bus" class="w-3.5 h-3.5"></i> الرحلة
        </label>
        <div class="relative">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="bus" class="w-4 h-4"></i>
          </div>
          <select name="trip_id" id="tr-trip" class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all appearance-none cursor-pointer text-right">
            <option value="">الكل</option>
            @foreach($trips as $tr)
              <option value="{{ $tr->id }}" {{ old('trip_id') == $tr->id ? 'selected' : '' }}>{{ $tr->trip_number ?? 'رحلة #'.$tr->id }}</option>
            @endforeach
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
        </div>
        @error('trip_id')
          <p class="text-[10px] text-rose-600 flex items-center gap-1"><i data-lucide="alert-circle" class="w-3 h-3"></i> {{ $message }}</p>
        @enderror
      </div>

      {{-- العملة --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="tr-currency">
          <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> العملة
        </label>
        <div class="relative">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="refresh-cw" class="w-4 h-4"></i>
          </div>
          <select name="currency_id" id="tr-currency" class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all appearance-none cursor-pointer text-right">
            <option value="">الكل</option>
            @foreach($currencies as $cur)
              <option value="{{ $cur->id }}" {{ old('currency_id') == $cur->id ? 'selected' : '' }}>{{ $cur->name }} ({{ $cur->code ?? '' }})</option>
            @endforeach
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
        </div>
        @error('currency_id')
          <p class="text-[10px] text-rose-600 flex items-center gap-1"><i data-lucide="alert-circle" class="w-3 h-3"></i> {{ $message }}</p>
        @enderror
      </div>

    </div>
  </div>

  {{-- القسم 3: الحدود --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs flex items-center gap-1.5 text-blue-600">
        <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i>
        حدود المبلغ
      </h4>
      <span class="text-[9px] font-bold text-blue-500 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-full">اختياري</span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      {{-- الحد الأدنى --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="tr-min-amount">
          <i data-lucide="trending-down" class="w-3.5 h-3.5"></i> الحد الأدنى
        </label>
        <input type="number" name="min_amount" id="tr-min-amount" step="any" min="0" dir="ltr" value="{{ old('min_amount') }}" placeholder="0" class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-4 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left font-mono">
        @error('min_amount')
          <p class="text-[10px] text-rose-600 flex items-center gap-1"><i data-lucide="alert-circle" class="w-3 h-3"></i> {{ $message }}</p>
        @enderror
      </div>
      {{-- الحد الأعلى --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="tr-max-amount">
          <i data-lucide="trending-up" class="w-3.5 h-3.5"></i> الحد الأعلى
        </label>
        <input type="number" name="max_amount" id="tr-max-amount" step="any" min="0" dir="ltr" value="{{ old('max_amount') }}" placeholder="∞" class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-4 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left font-mono">
        @error('max_amount')
          <p class="text-[10px] text-rose-600 flex items-center gap-1"><i data-lucide="alert-circle" class="w-3 h-3"></i> {{ $message }}</p>
        @enderror
      </div>
    </div>
  </div>

  {{-- القسم 4: الحالة --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs flex items-center gap-1.5 text-slate-600">
        <i data-lucide="toggle-right" class="w-3.5 h-3.5"></i> الحالة
      </h4>
    </div>
    <div class="flex items-center gap-3">
      <label class="relative inline-flex items-center cursor-pointer">
        <input type="checkbox" name="is_active" id="tr-is-active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }} class="sr-only peer">
        <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:bg-emerald-500 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
      </label>
      <span class="text-sm font-bold text-slate-700" id="tr-status-label">نشط</span>
    </div>
  </div>

  {{-- القسم 5: معلومات إضافية (للتعديل فقط) --}}
  <div class="space-y-4" id="tr-extra-info" style="display:none;">
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
        <span class="text-xs font-mono text-slate-600" dir="ltr" id="tr-info-created">—</span>
      </div>
      <div class="flex items-center gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
        <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-slate-400"></i>
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">آخر تحديث:</span>
        <span class="text-xs font-bold text-slate-600" id="tr-info-updated">—</span>
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
      <span>إلغاء</span>
    </button>
    <button
        type="submit"
        class="flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95"
        id="tr-submit-btn"
        style="background: linear-gradient(to right, #115128, #0f3d20);"
    >
      <i data-lucide="save" class="w-4 h-4"></i>
      <span id="tr-submit-text">حفظ القاعدة</span>
    </button>
  </x-slot>
</x-form-modal>

{{-- ======================================================================= --}}
{{-- ===== Tax Rule Details Modal (View) ===== --}}
{{-- ======================================================================= --}}
<x-modal
    id="tax-rule-details-modal"
    title="تفاصيل القاعدة الضريبية"
    variant="view"
    :maxWidth="'max-w-2xl'"
    data-no-reset
>
  <div class="space-y-5">
    <div class="bg-white border border-[#eeeef0] rounded-2xl p-4 sm:p-5 shadow-sm">
      <div class="flex items-center justify-between border-b border-[#eeeef0] pb-3 mb-4">
        <h4 class="font-bold text-sm text-slate-800 flex items-center gap-2">
          <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center text-white shadow-md">
            <i data-lucide="info" class="w-4 h-4"></i>
          </div>
          معلومات القاعدة
        </h4>
        <span class="text-[9px] font-bold text-teal-600 bg-teal-50 border border-teal-200 px-2 py-0.5 rounded-full">قاعدة ضريبية</span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        {{-- الضريبة --}}
        <div class="flex items-center justify-between gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
            <i data-lucide="receipt" class="w-3.5 h-3.5"></i> الضريبة
          </span>
          <span class="text-sm font-bold text-slate-700" id="details-tax-name">—</span>
        </div>
        {{-- الرحلة --}}
        <div class="flex items-center justify-between gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
            <i data-lucide="bus" class="w-3.5 h-3.5"></i> الرحلة
          </span>
          <span class="text-sm font-bold text-slate-700" id="details-trip-name">الكل</span>
        </div>
        {{-- الدولة --}}
        <div class="flex items-center justify-between gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
            <i data-lucide="flag" class="w-3.5 h-3.5"></i> الدولة
          </span>
          <span class="text-sm font-bold text-slate-700" id="details-country-name">الكل</span>
        </div>
        {{-- الحد الأدنى --}}
        <div class="flex items-center justify-between gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
            <i data-lucide="trending-down" class="w-3.5 h-3.5"></i> الحد الأدنى
          </span>
          <span class="text-sm font-mono text-slate-600" dir="ltr" id="details-min-amount">—</span>
        </div>
        {{-- المزود --}}
        <div class="flex items-center justify-between gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
            <i data-lucide="building-2" class="w-3.5 h-3.5"></i> المزود
          </span>
          <span class="text-sm font-bold text-slate-700" id="details-provider-name">الكل</span>
        </div>
        {{-- الحد الأعلى --}}
        <div class="flex items-center justify-between gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
            <i data-lucide="trending-up" class="w-3.5 h-3.5"></i> الحد الأعلى
          </span>
          <span class="text-sm font-mono text-slate-600" dir="ltr" id="details-max-amount">—</span>
        </div>
        {{-- نوع الخدمة --}}
        <div class="flex items-center justify-between gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
            <i data-lucide="wrench" class="w-3.5 h-3.5"></i> نوع الخدمة
          </span>
          <span class="text-sm font-bold text-slate-700" id="details-service-type-name">الكل</span>
        </div>
        {{-- العملة --}}
        <div class="flex items-center justify-between gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> العملة
          </span>
          <span class="text-sm font-bold text-slate-700" id="details-currency-code">الكل</span>
        </div>
        {{-- وسيلة الدفع --}}
        <div class="flex items-center justify-between gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
            <i data-lucide="wallet" class="w-3.5 h-3.5"></i> وسيلة الدفع
          </span>
          <span class="text-sm font-bold text-slate-700" id="details-payment-method-name">الكل</span>
        </div>
        {{-- الحالة --}}
        <div class="flex items-center justify-between gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
            <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> الحالة
          </span>
          <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border" id="details-status-badge">—</span>
        </div>
        {{-- تاريخ الإنشاء --}}
        <div class="flex items-center justify-between gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
            <i data-lucide="clock" class="w-3.5 h-3.5"></i> تاريخ الإنشاء
          </span>
          <span class="text-xs font-mono text-slate-600" dir="ltr" id="details-created">—</span>
        </div>
      </div>
    </div>
  </div>
</x-modal>

<x-delete-confirm />

@endsection

@push('scripts')
<script>
  // ===== Toggle status label =====
  (function() {
    var cb = document.getElementById('tr-is-active');
    var label = document.getElementById('tr-status-label');
    if (cb && label) {
      cb.addEventListener('change', function() {
        label.textContent = this.checked ? 'نشط' : 'غير نشط';
        label.className = this.checked
          ? 'text-sm font-bold text-emerald-600'
          : 'text-sm font-bold text-rose-600';
      });
    }
  })();

  // ===== Details modal fill =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id === 'tax-rule-details-modal') {
      var data = e.detail.data || {};
      document.getElementById('details-tax-name').textContent = data.taxName || '—';
      document.getElementById('details-trip-name').textContent = data.tripName || 'الكل';
      document.getElementById('details-country-name').textContent = data.countryName || 'الكل';
      document.getElementById('details-min-amount').textContent = data.minAmount !== null ? new Intl.NumberFormat('en-US').format(data.minAmount) : '—';
      document.getElementById('details-max-amount').textContent = data.maxAmount !== null ? new Intl.NumberFormat('en-US').format(data.maxAmount) : '—';
      document.getElementById('details-provider-name').textContent = data.providerName || 'الكل';
      document.getElementById('details-service-type-name').textContent = data.serviceTypeName || 'الكل';
      document.getElementById('details-currency-code').textContent = data.currencyCode ? data.currencyCode + ' (عملة)' : 'الكل';
      document.getElementById('details-payment-method-name').textContent = data.paymentMethodName || 'الكل';

      var badge = document.getElementById('details-status-badge');
      if (data.isActive) {
        badge.className = 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border bg-emerald-50 text-emerald-600 border-emerald-200';
        badge.innerHTML = '<i data-lucide="check-circle" class="w-2.5 h-2.5"></i> نشط';
      } else {
        badge.className = 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border bg-rose-50 text-rose-600 border-rose-200';
        badge.innerHTML = '<i data-lucide="x-circle" class="w-2.5 h-2.5"></i> غير نشط';
      }

      document.getElementById('details-created').textContent = data.createdAt || '—';
      if (window.lucide) window.lucide.createIcons();
    }
  });

  // ===== Tax Rule Form Modal: dynamic create/edit =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'tax-rule-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.id);

    // Helper to set select value
    function setSel(id, val) {
      var el = document.getElementById(id);
      if (el) el.value = val || '';
    }

    if (isEdit) {
      form.action = "{{ route('admin.tax-rules.update', '__ID__') }}".replace('__ID__', data.id);
      var m = form.querySelector('input[name="_method"]');
      if (m) m.value = 'PUT';
      else { var el = document.createElement('input'); el.type = 'hidden'; el.name = '_method'; el.value = 'PUT'; el.setAttribute('data-dynamic','true'); form.appendChild(el); }

      // Banner amber
      var b = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (b) b.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';
      var t = modal.querySelector('.aracari-modal-panel h2');
      if (t) t.innerHTML = '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل القاعدة الضريبية';

      document.getElementById('tr-submit-btn').style.background = 'linear-gradient(to right, #b45309, #92400e)';
      document.getElementById('tr-submit-text').textContent = 'حفظ التعديلات';

      setSel('tr-tax', data.tax_id);
      setSel('tr-country', data.country_id);
      setSel('tr-provider', data.provider_id);
      setSel('tr-service-type', data.service_type_id);
      setSel('tr-payment-method', data.payment_method_id);
      setSel('tr-trip', data.trip_id);
      setSel('tr-currency', data.currency_id);

      var minEl = document.getElementById('tr-min-amount');
      if (minEl) minEl.value = data.min_amount || '';
      var maxEl = document.getElementById('tr-max-amount');
      if (maxEl) maxEl.value = data.max_amount || '';

      var cb = document.getElementById('tr-is-active');
      var label = document.getElementById('tr-status-label');
      if (cb) cb.checked = data.is_active;
      if (label) { label.textContent = data.is_active ? 'نشط' : 'غير نشط'; label.className = data.is_active ? 'text-sm font-bold text-emerald-600' : 'text-sm font-bold text-rose-600'; }

      document.getElementById('tr-extra-info').style.display = '';
      document.getElementById('tr-info-created').textContent = data.createdAt || '—';
      document.getElementById('tr-info-updated').textContent = data.updatedAt || '—';

    } else {
      form.action = "{{ route('admin.tax-rules.store') }}";
      var dm = form.querySelector('input[data-dynamic="true"]');
      if (dm) dm.remove();

      var b = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (b) b.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
      var t = modal.querySelector('.aracari-modal-panel h2');
      if (t) t.innerHTML = '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة قاعدة ضريبية جديدة';

      document.getElementById('tr-submit-btn').style.background = 'linear-gradient(to right, #115128, #0f3d20)';
      document.getElementById('tr-submit-text').textContent = 'حفظ القاعدة';

      ['tr-tax','tr-country','tr-provider','tr-service-type','tr-payment-method','tr-trip','tr-currency'].forEach(function(id) { setSel(id, ''); });
      var minEl = document.getElementById('tr-min-amount'); if (minEl) minEl.value = '';
      var maxEl = document.getElementById('tr-max-amount'); if (maxEl) maxEl.value = '';
      var cb = document.getElementById('tr-is-active'); if (cb) cb.checked = true;
      var label = document.getElementById('tr-status-label'); if (label) { label.textContent = 'نشط'; label.className = 'text-sm font-bold text-emerald-600'; }
      document.getElementById('tr-extra-info').style.display = 'none';
    }
    if (window.lucide) window.lucide.createIcons();
  });
</script>
@endpush
