@extends('layouts.admin')

@section('title', 'أركاري | أسعار الصرف')
@section('header_title', 'أسعار الصرف')

@section('content')

@php
    $totalCount = $exchangeRates->total();
    $user      = auth()->user();
    $canCreate = $user && $user->hasPermission('exchange-rates:create');
    $canEdit   = $user && $user->hasPermission('exchange-rates:edit');
    $canDelete = $user && $user->hasPermission('exchange-rates:delete');

    // للإحصائيات
    $allRates = \App\Models\ExchangeRate::with(['fromCurrency', 'toCurrency'])->get();
    $highestRate = $allRates->max('rate');
    $lowestRate  = $allRates->min('rate');
    $latestDate  = $allRates->max('rate_date');
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="refresh-cw"
      title="أسعار الصرف"
      :count="$totalCount"
      countLabel="سعر"
      description="إدارة أسعار صرف العملات مقابل بعضها"
      descriptionIcon="info"
  >
    @if($canCreate)
      <button
          type="button"
          data-create-modal="exchange-rate-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-violet-500 to-violet-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-violet-500/20 hover:shadow-violet-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إضافة سعر صرف
      </button>
    @endif
  </x-page-header>

  {{-- ===== Stat Cards ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    {{-- إجمالي الأسعار --}}
    <x-stat-card
        icon="refresh-cw"
        :label="'إجمالي الأسعار'"
        :value="$totalCount"
        :gradient="'from-slate-900 to-slate-800'"
        :accentColor="'violet'"
    />
    {{-- أعلى سعر --}}
    <x-stat-card
        icon="trending-up"
        :label="'أعلى سعر'"
        :value="number_format((float) $highestRate, 4)"
        :gradient="'from-emerald-900/90 to-emerald-800/90'"
        :accentColor="'emerald'"
        :badge="'أعلى'"
        :isMono="true"
    />
    {{-- أقل سعر --}}
    <x-stat-card
        icon="trending-down"
        :label="'أقل سعر'"
        :value="number_format((float) $lowestRate, 4)"
        :gradient="'from-rose-900/90 to-rose-800/90'"
        :accentColor="'rose'"
        :badge="'أقل'"
        :isMono="true"
    />
    {{-- آخر تحديث --}}
    <x-stat-card
        icon="calendar"
        :label="'آخر تحديث'"
        :value="$latestDate ? $latestDate->format('Y/m/d') : '—'"
        :gradient="'from-blue-900/90 to-blue-800/90'"
        :accentColor="'blue'"
        :badge="'آخر تحديث'"
        :isMono="true"
    />
  </div>

  {{-- ===== Refreshable region ===== --}}
  <div id="aracari-refreshable" class="space-y-6">

    {{-- ===== Data Table Card ===== --}}
    <x-data-table-card :searchable="true" :searchMode="'server'" searchPlaceholder="ابحث عن عملة..." :footerInfo="'عرض ' . $exchangeRates->count() . ' من أصل ' . $exchangeRates->total() . ' سعر'">
      <x-slot name="thead">
        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">من</th>
        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">إلى</th>
        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">السعر</th>
        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">التاريخ</th>
        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
      </x-slot>

      <x-slot name="rows">
        @forelse($exchangeRates as $rate)
          <tr class="hover:bg-slate-50/50 transition-colors group">
            {{-- # --}}
            <td class="px-6 py-4">
              <span class="font-mono font-black text-slate-400 text-sm">{{ str_pad((string) $rate->id, 4, '0', STR_PAD_LEFT) }}</span>
            </td>

            {{-- من --}}
            <td data-searchable class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500/20 to-violet-600/10 flex items-center justify-center border border-violet-100 group-hover:scale-110 transition-transform duration-300">
                  <i data-lucide="refresh-cw" class="w-5 h-5 text-violet-600"></i>
                </div>
                <div>
                  <div class="font-bold text-slate-800" dir="ltr">{{ $rate->fromCurrency->code ?? '—' }}</div>
                  <div class="text-[10px] font-bold text-slate-400">{{ $rate->fromCurrency->name ?? '—' }}</div>
                </div>
              </div>
            </td>

            {{-- إلى --}}
            <td data-searchable class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500/20 to-emerald-600/10 flex items-center justify-center border border-emerald-100 group-hover:scale-110 transition-transform duration-300">
                  <i data-lucide="refresh-cw" class="w-5 h-5 text-emerald-600"></i>
                </div>
                <div>
                  <div class="font-bold text-slate-800" dir="ltr">{{ $rate->toCurrency->code ?? '—' }}</div>
                  <div class="text-[10px] font-bold text-slate-400">{{ $rate->toCurrency->name ?? '—' }}</div>
                </div>
              </div>
            </td>

            {{-- السعر --}}
            <td class="px-6 py-4 text-center">
              <span class="inline-flex items-center gap-1.5 font-mono font-black text-slate-800 bg-slate-100/60 border border-slate-200 px-3 py-1.5 rounded-xl" dir="ltr">
                {{ number_format((float) $rate->rate, 4) }}
              </span>
            </td>

            {{-- التاريخ --}}
            <td class="px-6 py-4 text-center">
              <span class="inline-flex items-center gap-1.5 text-slate-600 text-xs font-bold" dir="ltr">
                <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                {{ optional($rate->rate_date)->format('Y/m/d') ?? '—' }}
              </span>
            </td>

            {{-- الإجراءات --}}
            <td class="px-6 py-4 text-center">
              <div class="flex items-center justify-center gap-1.5">
                @if($canEdit)
                  <x-action-button
                      variant="edit"
                      data-edit-modal="exchange-rate-form-modal"
                      data-edit-json="{{ json_encode([
                          'id'               => $rate->id,
                          'from_currency_id' => $rate->from_currency_id,
                          'to_currency_id'   => $rate->to_currency_id,
                          'from_currency_code' => $rate->fromCurrency->code ?? '',
                          'to_currency_code'   => $rate->toCurrency->code ?? '',
                          'from_currency_name' => $rate->fromCurrency->name ?? '',
                          'to_currency_name'   => $rate->toCurrency->name ?? '',
                          'rate'             => number_format((float) $rate->rate, 4),
                          'rate_date'        => optional($rate->rate_date)->format('Y-m-d') ?? '',
                      ], JSON_UNESCAPED_UNICODE) }}"
                      :ariaLabel="'تعديل ' . ($rate->fromCurrency->code ?? '') . ' → ' . ($rate->toCurrency->code ?? '')"
                  />
                @endif
                @if($canDelete)
                  <x-action-button
                      variant="delete"
                      data-delete-url="{{ route('admin.exchange-rates.destroy', $rate) }}"
                      data-delete-label="{{ ($rate->fromCurrency->code ?? '') . ' → ' . ($rate->toCurrency->code ?? '') }}"
                  />
                @endif
              </div>
            </td>
          </tr>
        @empty
          <x-empty-state
              icon="refresh-cw"
              :title="request('search') ? 'لا توجد نتائج مطابقة' : 'لا توجد أسعار صرف'"
              :message="request('search') ? 'جرّب تعديل كلمات البحث' : 'قم بإضافة سعر صرف جديد'"
              :colspan="6"
          >
            @if(!request('search') && $canCreate)
              <button
                  type="button"
                  data-create-modal="exchange-rate-form-modal"
                  class="text-violet-600 hover:text-violet-700 mt-3 inline-flex items-center gap-1 font-bold"
              >
                <i data-lucide="plus" class="w-4 h-4"></i>
                إضافة سعر صرف
              </button>
            @endif
          </x-empty-state>
        @endforelse
      </x-slot>

      <x-slot name="footer">
        <x-pagination :paginator="$exchangeRates" />
      </x-slot>
    </x-data-table-card>

  </div>{{-- end #aracari-refreshable -->}

</div>

{{-- ===== Exchange Rate Form Modal (Create + Edit) ===== --}}
<x-form-modal
    id="exchange-rate-form-modal"
    title="إضافة سعر صرف"
    :variant="'create'"
    :action="route('admin.exchange-rates.store')"
    method="POST"
    successMessage="تم حفظ سعر الصرف بنجاح"
>

  {{-- العملة المصدر --}}
  <x-form-field name="from_currency_id" label="العملة المصدر" icon="arrow-up-right" required>
    <select
        name="from_currency_id"
        id="from_currency_id"
        required
        class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-violet-500/10 focus:border-violet-500 transition-all text-right appearance-none cursor-pointer"
    >
      <option value="">اختر العملة المصدر...</option>
      @foreach($currencies as $curr)
        <option value="{{ $curr->id }}">{{ $curr->name }} ({{ $curr->code }})</option>
      @endforeach
    </select>
  </x-form-field>

  {{-- العملة الهدف --}}
  <x-form-field name="to_currency_id" label="العملة الهدف" icon="arrow-down-left" required>
    <select
        name="to_currency_id"
        id="to_currency_id"
        required
        class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-violet-500/10 focus:border-violet-500 transition-all text-right appearance-none cursor-pointer"
    >
      <option value="">اختر العملة الهدف...</option>
      @foreach($currencies as $curr)
        <option value="{{ $curr->id }}">{{ $curr->name }} ({{ $curr->code }})</option>
      @endforeach
    </select>
  </x-form-field>

  {{-- سعر الصرف --}}
  <x-form-field name="rate" label="سعر الصرف" icon="calculator" required>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-violet-500 transition-colors">
        <i data-lucide="calculator" class="w-4 h-4"></i>
      </div>
      <input
          type="number"
          name="rate"
          id="rate"
          required
          step="0.0001"
          min="0.0001"
          max="999999.9999"
          placeholder="مثال: 0.0385"
          dir="ltr"
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold font-mono outline-none focus:bg-white focus:ring-4 focus:ring-violet-500/10 focus:border-violet-500 transition-all text-left"
      >
    </div>
  </x-form-field>

  {{-- التاريخ (تلقائي - اليوم فقط) --}}
  <input type="hidden" name="rate_date" id="rate_date" value="{{ now()->format('Y-m-d') }}" class="hidden-date-input">

  {{-- معاينة حية --}}
  <div class="space-y-2">
    <h4 class="font-bold text-xs text-violet-600 border-b border-[#eeeef0] pb-2 flex items-center gap-1.5">
      <i data-lucide="eye" class="w-3.5 h-3.5"></i>
      معاينة سعر الصرف
    </h4>
    <div class="bg-[#f9f9fc] border border-[#eeeef0] rounded-xl p-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center border bg-violet-50 border-violet-100 text-violet-500">
          <i data-lucide="refresh-cw" class="w-5 h-5"></i>
        </div>
        <div>
          <h4 class="text-sm font-bold text-slate-800" id="rate-preview-text" data-preview-default="1.0000 عملة ← → عملة">1.0000 — — ← → —</h4>
          <span class="inline-flex items-center gap-1 text-[10px] text-slate-400 mt-0.5">
            <i data-lucide="info" class="w-3 h-3"></i>
            سعر الصرف
          </span>
        </div>
      </div>
      <span id="preview-badge" class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold bg-violet-50 text-violet-700 border border-violet-200">
        <i data-lucide="plus-circle" class="w-2.5 h-2.5"></i>
        جديد
      </span>
    </div>
  </div>
</x-form-modal>

@endsection

@push('scripts')
<script>
  // ===== Exchange Rate modal: dynamic action + title + variant switching =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'exchange-rate-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.id);

    // Get select options text helper
    function getSelectText(selectId, val) {
      var sel = modal.querySelector('#' + selectId);
      if (!sel) return '';
      for (var i = 0; i < sel.options.length; i++) {
        if (sel.options[i].value == val) return sel.options[i].text;
      }
      return '';
    }

    if (isEdit) {
      var updateUrl = "{{ route('admin.exchange-rates.update', '__ID__') }}".replace('__ID__', data.id);
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
        '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل سعر الصرف';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }
      form.setAttribute('data-success-message', 'تم تحديث سعر الصرف بنجاح');

      // Fill selects
      var fromSel = modal.querySelector('#from_currency_id');
      var toSel = modal.querySelector('#to_currency_id');
      if (fromSel) { fromSel.value = data.from_currency_id; fromSel.disabled = true; }
      if (toSel) { toSel.value = data.to_currency_id; toSel.disabled = true; }
      // Hidden inputs to submit disabled selects
      var existingFrom = modal.querySelector('input[name="from_currency_id_hidden"]');
      var existingTo = modal.querySelector('input[name="to_currency_id_hidden"]');
      if (!existingFrom) {
        var hf = document.createElement('input'); hf.type = 'hidden'; hf.name = 'from_currency_id'; hf.value = data.from_currency_id; form.appendChild(hf);
      }
      if (!existingTo) {
        var ht = document.createElement('input'); ht.type = 'hidden'; ht.name = 'to_currency_id'; ht.value = data.to_currency_id; form.appendChild(ht);
      }

      // Fill rate (date stays as today for edit too)
      var rateInput = modal.querySelector('#rate');
      var dateInput = modal.querySelector('#rate_date');
      if (rateInput) rateInput.value = data.rate;
      // في وضع التعديل نحافظ على التاريخ الأصلي
      if (dateInput) dateInput.value = data.rate_date || '{{ now()->format('Y-m-d') }}';

      var previewBadge = modal.querySelector('#preview-badge');
      if (previewBadge) {
        previewBadge.className = 'inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200';
        previewBadge.innerHTML = '<i data-lucide="pencil" class="w-2.5 h-2.5"></i> تعديل';
      }
    } else {
      form.setAttribute('action', "{{ route('admin.exchange-rates.store') }}");
      var m = form.querySelector('input[name="_method"]');
      if (m) m.remove();
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-violet-900 to-violet-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة سعر صرف';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-violet-500/20 text-violet-300 border-violet-500/30';
        badge.textContent = 'جديد';
      }
      form.setAttribute('data-success-message', 'تم حفظ سعر الصرف بنجاح');

      // Re-enable selects for create
      var fromSel = modal.querySelector('#from_currency_id');
      var toSel = modal.querySelector('#to_currency_id');
      if (fromSel) fromSel.disabled = false;
      if (toSel) toSel.disabled = false;
      // Remove hidden inputs
      form.querySelectorAll('input[name="from_currency_id"][type="hidden"]').forEach(function(el) { el.remove(); });
      form.querySelectorAll('input[name="to_currency_id"][type="hidden"]').forEach(function(el) { el.remove(); });

      var previewBadge = modal.querySelector('#preview-badge');
      if (previewBadge) {
        previewBadge.className = 'inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold bg-violet-50 text-violet-700 border border-violet-200';
        previewBadge.innerHTML = '<i data-lucide="plus-circle" class="w-2.5 h-2.5"></i> جديد';
      }
    }
    if (window.lucide) window.lucide.createIcons();

    // sync live preview
    syncRatePreview(modal);
  });

  function syncRatePreview(modal) {
    var rateInput = modal.querySelector('#rate');
    var fromSel = modal.querySelector('#from_currency_id');
    var toSel = modal.querySelector('#to_currency_id');
    var preview = modal.querySelector('#rate-preview-text');
    if (!preview) return;
    var rate = rateInput && rateInput.value ? parseFloat(rateInput.value).toFixed(4) : '—';
    var fromCode = fromSel && fromSel.value ? getSelectText('from_currency_id', fromSel.value).split('(')[1].replace(')', '').trim() : '—';
    var toCode = toSel && toSel.value ? getSelectText('to_currency_id', toSel.value).split('(')[1].replace(')', '').trim() : '—';
    preview.textContent = '1.0000 ' + fromCode + ' ← → ' + rate + ' ' + toCode;
  }

  (function() {
    var rateInput = document.getElementById('rate');
    var fromSel = document.getElementById('from_currency_id');
    var toSel = document.getElementById('to_currency_id');
    if (!rateInput) return;
    rateInput.addEventListener('input', function() { syncRatePreview(document.getElementById('exchange-rate-form-modal')); });
    if (fromSel) fromSel.addEventListener('change', function() { syncRatePreview(document.getElementById('exchange-rate-form-modal')); });
    if (toSel) toSel.addEventListener('change', function() { syncRatePreview(document.getElementById('exchange-rate-form-modal')); });
  })();
</script>
@endpush
