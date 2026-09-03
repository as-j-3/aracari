@extends('layouts.admin')

@section('title', 'أركاري | العملات')
@section('header_title', 'العملات')

@section('content')

@php
    $allItems = $currencies->getCollection();
    $totalCurrencies = $currencies->total();
    $activeCount   = $allItems->filter(fn($c) => ($c->is_active ?? true))->count();
    $inactiveCount = $totalCurrencies - $activeCount;

    $highestRate     = $allItems->max('price_to_yr');
    $highestCurrency = $allItems->firstWhere('price_to_yr', $highestRate);
    $highestCode     = $highestCurrency ? $highestCurrency->code : '—';

    $user      = auth()->user();
    $canView   = $user && $user->hasPermission('currencies:view');
    $canCreate = $user && $user->hasPermission('currencies:create');
    $canEdit   = $user && $user->hasPermission('currencies:edit');
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
    icon="refresh-cw"
    title="العملات"
    :count="$totalCurrencies"
    countLabel="عملة"
    description="إدارة العملات المدعومة في النظام وأسعار الصرف"
    descriptionIcon="info"
  >
    @if($canCreate)
      <button
          type="button"
          data-create-modal="currency-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إضافة عملة جديدة
      </button>
    @endif
  </x-page-header>

  <div id="aracari-refreshable" class="space-y-6">

  {{-- ===== Stat Cards ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    <x-stat-card :value="$totalCurrencies" label="إجمالي العملات" icon="refresh-cw" variant="slate" badge="عملة" badgeIcon="refresh-cw" />
    <x-stat-card :value="$activeCount" label="عملات نشطة" icon="check-circle" variant="emerald" badge="نشطة" badgeIcon="check-circle" />
    <x-stat-card :value="$inactiveCount" label="غير نشطة" icon="x-circle" variant="amber" badge="متوقفة" badgeIcon="x-circle" />
    <x-stat-card :value="number_format((float) ($highestRate ?? 0), 4)" :label="'أعلى سعر صرف · ' . $highestCode" icon="trending-up" variant="blue" badge="YER" badgeIcon="trending-up" />
  </div>

  {{-- ===== Data Table ===== --}}
  <x-data-table-card :searchable="true" :searchMode="'server'" searchPlaceholder="ابحث عن عملة بالاسم أو الكود أو الرمز..." :footerInfo="'عرض ' . $currencies->count() . ' من أصل ' . $currencies->total() . ' عملة'">

    <x-slot name="filters">
      <select name="is_active" form="aracari-search-form" class="h-11 bg-white/70 border border-slate-200 rounded-2xl px-4 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all appearance-none cursor-pointer text-right w-full sm:w-auto">
        <option value="" {{ !request()->has('is_active') ? 'selected' : '' }}>كل الحالات</option>
        <option value="1" {{ request('is_active') === '1' || request('is_active') === 'true' ? 'selected' : '' }}>نشطة</option>
        <option value="0" {{ request('is_active') === '0' || request('is_active') === 'false' ? 'selected' : '' }}>غير نشطة</option>
      </select>
    </x-slot>

    <x-slot name="thead">
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الكود</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الاسم</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الرمز</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">سعر الصرف (YER)</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الاستخدام</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
    </x-slot>

    <x-slot name="rows">
      @forelse($currencies as $currency)
        @php
          $isActive = $currency->is_active ?? true;
          $sCfg = $isActive
            ? ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'label' => 'نشطة', 'dot' => 'bg-emerald-500']
            : ['bg' => 'bg-rose-50 text-rose-700 border-rose-200', 'label' => 'متوقفة', 'dot' => 'bg-rose-500'];

          $modalData = [
              'name'               => $currency->name ?? '—',
              'code'               => $currency->code ?? '—',
              'symbol'             => $currency->symbol ?? '—',
              'price_to_yr'        => number_format((float) ($currency->price_to_yr ?? 0), 4),
              'rate_source'        => $currency->rate_source ?? '—',
              'last_rate_update'   => $currency->last_rate_update ? $currency->last_rate_update->translatedFormat('j F، Y الساعة H:i') : 'لم يتم التحديث',
              'is_active'          => $sCfg['label'],
              'status_bg'          => $sCfg['bg'],
              'status_dot'         => $sCfg['dot'],
              'bookings_count'     => (string) ($currency->bookings_count ?? 0),
              'transactions_count' => (string) ($currency->transactions_count ?? 0),
              'trips_count'        => (string) ($currency->trips_count ?? 0),
              'id'                 => (string) $currency->id,
              'created_at'         => optional($currency->created_at)->translatedFormat('j F، Y الساعة H:i') ?? '—',
              'updated_at'         => optional($currency->updated_at)->translatedFormat('j F، Y الساعة H:i') ?? '—',
          ];
        @endphp

        <tr class="hover:bg-slate-50/50 transition-colors group">
          {{-- # --}}
          <td class="px-6 py-4">
            <span class="font-mono font-black text-slate-400 text-sm">{{ str_pad((string) $currency->id, 4, '0', STR_PAD_LEFT) }}</span>
          </td>

          {{-- الكود --}}
          <td data-searchable class="px-6 py-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500/20 to-teal-600/10 flex items-center justify-center border border-teal-100 group-hover:scale-110 transition-transform duration-300">
                <i data-lucide="refresh-cw" class="w-5 h-5 text-teal-600"></i>
              </div>
              <span class="font-bold text-slate-800 font-mono" dir="ltr">{{ $currency->code }}</span>
            </div>
          </td>

          {{-- الاسم --}}
          <td data-searchable class="px-6 py-4">
            <span class="font-bold text-slate-800 text-sm">{{ $currency->name }}</span>
          </td>

          {{-- الرمز --}}
          <td class="px-6 py-4 text-center">
            <span class="font-bold text-slate-700 text-sm" dir="rtl">{{ $currency->symbol }}</span>
          </td>

          {{-- سعر الصرف --}}
          <td class="px-6 py-4 text-center">
            <span class="inline-flex items-center gap-1.5 font-mono font-black text-slate-800 bg-slate-100/60 border border-slate-200 px-3 py-1.5 rounded-xl text-sm" dir="ltr">
              {{ number_format((float) ($currency->price_to_yr ?? 0), 4) }}
            </span>
          </td>

          {{-- الاستخدام --}}
          <td class="px-6 py-4 text-center">
            <span class="inline-flex items-center gap-1.5 text-slate-700 text-[11px] font-bold bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-xl">
              {{ number_format($currency->bookings_count ?? 0) }} حجز
            </span>
          </td>

          {{-- الحالة --}}
          <td class="px-6 py-4 text-center">
            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-[10px] font-bold border {{ $sCfg['bg'] }}">
              <span class="w-1.5 h-1.5 rounded-full {{ $sCfg['dot'] }}"></span>
              {{ $sCfg['label'] }}
            </span>
          </td>

          {{-- الإجراءات --}}
          <td class="px-6 py-4 text-center">
            <div class="flex items-center justify-center gap-1.5">
              <x-action-button variant="view" data-view-modal="currency-details-modal" data-view-json="{{ json_encode($modalData, JSON_UNESCAPED_UNICODE) }}" />
              @if($canEdit)
                <x-action-button
                    variant="edit"
                    data-edit-modal="currency-form-modal"
                    data-edit-json="{{ json_encode([
                        'id'            => $currency->id,
                        'name'          => $currency->name,
                        'code'          => $currency->code,
                        'symbol'        => $currency->symbol,
                        'price_to_yr'   => $currency->price_to_yr,
                        'rate_source'   => $currency->rate_source,
                        'is_active'     => $currency->is_active ?? true,
                    ], JSON_UNESCAPED_UNICODE) }}"
                    :ariaLabel="'تعديل ' . $currency->name"
                />
              @endif
              <x-action-button
                  variant="delete"
                  data-delete-url="{{ route('admin.currencies.destroy', $currency) }}"
                  data-delete-label="{{ $currency->name }}"
              />
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state
          icon="refresh-cw"
          :title="request('search') || request()->has('is_active') ? 'لا توجد نتائج مطابقة' : 'لا توجد عملات مسجلة'"
          :message="request('search') || request()->has('is_active') ? 'جرّب تعديل خيارات البحث أو الفلتر' : 'قم بإضافة عملة جديدة للنظام'"
          :colspan="8"
        >
          @if(!request('search') && !request()->has('is_active') && $canCreate)
            <button
                type="button"
                data-create-modal="currency-form-modal"
                class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
            >
              <i data-lucide="plus" class="w-4 h-4"></i>
              إضافة عملة جديدة
            </button>
          @endif
        </x-empty-state>
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$currencies" />
    </x-slot>
  </x-data-table-card>

  </div>{{-- end #aracari-refreshable --}}}
</div>

{{-- ========================================================== --}}
{{-- ===== MODAL: Currency Details (View Only) ===== --}}
{{-- ========================================================== --}}
<x-modal id="currency-details-modal" title="تفاصيل العملة" variant="view" icon="refresh-cw" :static="true" maxWidth="max-w-3xl">

  {{-- 1. Currency Basic Info --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="p-5 bg-white border border-[#eeeef0] rounded-2xl shadow-sm space-y-3">
      <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
        <i data-lucide="refresh-cw" class="w-4 h-4 text-teal-600"></i>
        <span>بيانات العملة</span>
      </h4>
      <div class="space-y-2.5">
        <div class="flex justify-between items-center py-1 border-b border-slate-50 text-xs">
          <span class="text-slate-500 font-medium">اسم العملة:</span>
          <span class="font-black text-slate-800" data-preview="name">—</span>
        </div>
        <div class="flex justify-between items-center py-1 border-b border-slate-50 text-xs">
          <span class="text-slate-500 font-medium">الكود:</span>
          <span class="font-black text-slate-800 font-mono" dir="ltr" data-preview="code">—</span>
        </div>
        <div class="flex justify-between items-center py-1 text-xs">
          <span class="text-slate-500 font-medium">الرمز:</span>
          <span class="font-black text-slate-800 text-base" data-preview="symbol">—</span>
        </div>
      </div>
    </div>

    <div class="p-5 bg-white border border-[#eeeef0] rounded-2xl shadow-sm space-y-3">
      <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
        <i data-lucide="activity" class="w-4 h-4 text-emerald-600"></i>
        <span>السعر والحالة</span>
      </h4>
      <div class="space-y-2.5">
        <div class="flex justify-between items-center py-1 border-b border-slate-50 text-xs">
          <span class="text-slate-500 font-medium">سعر الصرف (YER):</span>
          <span class="font-black text-slate-800 font-mono" dir="ltr" data-preview="price_to_yr">0.0000</span>
        </div>
        <div class="flex justify-between items-center py-1 border-b border-slate-50 text-xs">
          <span class="text-slate-500 font-medium">الحالة:</span>
          <span class="font-black" data-preview="is_active">—</span>
        </div>
        <div class="flex justify-between items-center py-1 text-xs">
          <span class="text-slate-500 font-medium">مصدر السعر:</span>
          <span class="font-black text-slate-800" data-preview="rate_source">—</span>
        </div>
      </div>
    </div>
  </div>

  {{-- 2. Usage Stats (Dark card) --}}
  <div class="p-5 bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl border border-white/5">
    <h4 class="font-bold text-xs text-slate-300 uppercase tracking-wider flex items-center gap-1.5 mb-4">
      <i data-lucide="bar-chart-3" class="w-4 h-4 text-teal-400"></i>
      <span class="text-white">إحصائيات الاستخدام</span>
    </h4>
    <div class="grid grid-cols-3 gap-3">
      <div class="bg-white/10 rounded-xl p-3 text-center border border-white/5">
        <p class="text-2xl font-black text-emerald-400 font-mono" data-preview="bookings_count">0</p>
        <p class="text-[10px] text-slate-300 font-bold mt-1">حجز</p>
      </div>
      <div class="bg-white/10 rounded-xl p-3 text-center border border-white/5">
        <p class="text-2xl font-black text-blue-400 font-mono" data-preview="transactions_count">0</p>
        <p class="text-[10px] text-slate-300 font-bold mt-1">معاملة</p>
      </div>
      <div class="bg-white/10 rounded-xl p-3 text-center border border-white/5">
        <p class="text-2xl font-black text-amber-400 font-mono" data-preview="trips_count">0</p>
        <p class="text-[10px] text-slate-300 font-bold mt-1">رحلة</p>
      </div>
    </div>
  </div>

  {{-- 3. Metadata --}}
  <div class="p-4 bg-slate-50 rounded-2xl border border-[#eeeef0]">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-[10px]">
      <div>
        <span class="text-slate-400 font-bold block">الرمز</span>
        <span class="text-slate-700 font-bold">#<span data-preview="id">—</span></span>
      </div>
      <div>
        <span class="text-slate-400 font-bold block">آخر تحديث للسعر</span>
        <span class="text-slate-700 font-bold" data-preview="last_rate_update">—</span>
      </div>
      <div>
        <span class="text-slate-400 font-bold block">تاريخ الإنشاء</span>
        <span class="text-slate-700 font-bold" data-preview="created_at">—</span>
      </div>
      <div>
        <span class="text-slate-400 font-bold block">آخر تحديث</span>
        <span class="text-slate-700 font-bold" data-preview="updated_at">—</span>
      </div>
    </div>
  </div>

</x-modal>

{{-- ========================================================== --}}
{{-- ===== MODAL: Currency Form (Create + Edit) ===== --}}
{{-- ========================================================== --}}
<x-form-modal
    id="currency-form-modal"
    title="إضافة عملة جديدة"
    :variant="'create'"
    :action="route('admin.currencies.store')"
    method="POST"
    successMessage="تم حفظ العملة بنجاح"
>
  {{-- اسم العملة --}}
  <x-form-field name="name" label="اسم العملة" icon="type" required>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-teal-600 transition-colors">
        <i data-lucide="type" class="w-4 h-4"></i>
      </div>
      <input type="text" name="name" id="currency_name" required placeholder="مثال: الريال السعودي" class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right">
    </div>
  </x-form-field>

  {{-- كود العملة --}}
  <x-form-field name="code" label="كود العملة (ISO)" icon="code" required>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-teal-600 transition-colors">
        <i data-lucide="code" class="w-4 h-4"></i>
      </div>
      <input type="text" name="code" id="currency_code" required maxlength="3" placeholder="مثال: USD, SAR, EUR" dir="ltr" class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left font-mono uppercase">
    </div>
  </x-form-field>

  {{-- رمز العملة --}}
  <x-form-field name="symbol" label="رمز العملة" icon="hash" required>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-teal-600 transition-colors">
        <i data-lucide="hash" class="w-4 h-4"></i>
      </div>
      <input type="text" name="symbol" id="currency_symbol" required maxlength="10" placeholder="مثال: $, ر.س, €" dir="ltr" class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-center font-mono">
    </div>
  </x-form-field>

  {{-- سعر الصرف --}}
  <x-form-field name="price_to_yr" label="سعر الصرف مقابل الريال اليمني" icon="trending-up" required>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-teal-600 transition-colors">
        <i data-lucide="trending-up" class="w-4 h-4"></i>
      </div>
      <input type="number" name="price_to_yr" id="currency_price" required step="0.0001" min="0.0001" placeholder="0.0000" dir="ltr" class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left font-mono">
    </div>
  </x-form-field>

  {{-- مصدر السعر --}}
  <x-form-field name="rate_source" label="مصدر سعر الصرف" icon="globe">
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-teal-600 transition-colors">
        <i data-lucide="globe" class="w-4 h-4"></i>
      </div>
      <input type="text" name="rate_source" id="currency_rate_source" placeholder="مثال: البنك المركزي اليمني" class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right">
    </div>
  </x-form-field>

  {{-- تنبيه --}}
  <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-3">
    <div class="flex items-start gap-2.5">
      <i data-lucide="info" class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5"></i>
      <span class="text-[11px] text-amber-600">
        سعر الصرف يدوياً، تأكد من إدخال السعر الصحيح مقابل الريال اليمني (YER).
      </span>
    </div>
  </div>
</x-form-modal>

@endsection

@push('scripts')
<script>
// ===== Currency form modal: dynamic action + title + variant switching =====
document.addEventListener('aracari:modal-open', function(e) {
  var modal = e.target;
  if (modal.id !== 'currency-form-modal') return;
  var data = e.detail.data || {};
  var form = modal.querySelector('form');
  var isEdit = !!(data.id);

  if (isEdit) {
    var updateUrl = "{{ route('admin.currencies.update', '__ID__') }}".replace('__ID__', data.id);
    form.setAttribute('action', updateUrl);
    var existingMethod = form.querySelector('input[name="_method"]');
    if (existingMethod) existingMethod.value = 'PUT';
    else {
      var m = document.createElement('input');
      m.type = 'hidden'; m.name = '_method'; m.value = 'PUT';
      form.appendChild(m);
    }
    modal.querySelector('.aracari-modal-panel .relative.h-24').className =
      'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';
    modal.querySelector('.aracari-modal-panel h2').innerHTML =
      '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل بيانات العملة';
    var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
    if (badge) {
      badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
      badge.textContent = 'تحديث';
    }
    form.setAttribute('data-success-message', 'تم تحديث العملة بنجاح');
  } else {
    form.setAttribute('action', "{{ route('admin.currencies.store') }}");
    var m = form.querySelector('input[name="_method"]');
    if (m) m.remove();
    modal.querySelector('.aracari-modal-panel .relative.h-24').className =
      'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
    modal.querySelector('.aracari-modal-panel h2').innerHTML =
      '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة عملة جديدة';
    var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
    if (badge) {
      badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30';
      badge.textContent = 'جديد';
    }
    form.setAttribute('data-success-message', 'تم حفظ العملة بنجاح');
  }
  if (window.lucide) window.lucide.createIcons();
});

// ===== Currency details modal: dynamic content =====
document.addEventListener('aracari:modal-open', function(e) {
  var modal = e.target;
  if (modal.id !== 'currency-details-modal') return;
  var data = e.detail.data || {};

  var b = modal.querySelector('.aracari-modal-panel .relative.h-24');
  if (b) b.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
  var t = modal.querySelector('.aracari-modal-panel h2');
  if (t) t.innerHTML = '<i data-lucide="refresh-cw" class="w-5 h-5"></i> تفاصيل العملة: ' + (data.name || '—');
  var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span[class*="rounded-full"]');
  if (badge) { badge.textContent = 'عرض'; badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30'; }

  var statusEl = modal.querySelector('[data-preview="is_active"]');
  if (statusEl && data.status_bg) {
    statusEl.className = 'inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-[10px] font-bold border ' + data.status_bg;
    var dot = document.createElement('span');
    dot.className = 'w-1.5 h-1.5 rounded-full ' + (data.status_dot || 'bg-slate-500');
    statusEl.innerHTML = '';
    statusEl.appendChild(dot);
    statusEl.appendChild(document.createTextNode(' ' + data.is_active));
  }

  if (window.lucide) window.lucide.createIcons();
});
</script>
@endpush