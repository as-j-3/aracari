@extends('layouts.admin')

@section('title', 'أركاري | إدارة الضرائب والرسوم')
@section('header_title', 'إدارة الضرائب والرسوم')

@section('content')

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="receipt"
      title="إدارة الضرائب والرسوم"
      :count="$taxes->total()"
      countLabel="ضريبة / رسم"
      description="تهيئة نسب الضرائب والرسوم الثابتة المضافة على تذاكر الحجوزات"
      descriptionIcon="receipt"
  >
    <button
        type="button"
        data-create-modal="tax-form-modal"
        class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-amber-500/20 hover:shadow-amber-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
    >
      <i data-lucide="plus-circle" class="w-4 h-4"></i>
      <span class="hidden sm:inline">إضافة ضريبة جديدة</span>
    </button>
  </x-page-header>

  {{-- ===== 4 Stat Cards ===== --}}
  <div id="aracari-refreshable">
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    {{-- Total --}}
    <x-stat-card
        :value="$stats['total']"
        label="إجمالي الضرائب والرسوم"
        icon="receipt"
        variant="slate"
        badge="مسجّلة"
        badgeIcon="trending-up"
    />
    {{-- Percentage --}}
    <x-stat-card
        :value="$stats['percentage']"
        label="نسبة مئوية (%)"
        icon="percent"
        variant="sky"
        badge="تضاف كنسبة"
        badgeIcon="percent"
    />
    {{-- Fixed --}}
    <x-stat-card
        :value="$stats['fixed']"
        label="قيمة ثابتة (ر.ي)"
        icon="banknote"
        variant="emerald"
        badge="مبلغ مقطوع"
        badgeIcon="banknote"
    />
    {{-- Highest Tax --}}
    <x-stat-card
        :value="$highestTax ? $highestTax->name : 'لا يوجد'"
        label="أعلى قيمة ضريبة / رسم"
        icon="trending-up"
        variant="rose"
        :badge="$highestTax ? ($highestTax->type === 'percentage' ? $highestTax->rate . '%' : number_format((float) $highestTax->amount) . ' ر.ي') : '---'"
        badgeIcon="trending-up"
    />
  </div>

  {{-- ===== Data Table ===== --}}
  <x-data-table-card
      :searchable="true"
      :searchMode="'server'"
      searchPlaceholder="ابحث عن ضريبة باسم الضريبة أو الرسم..."
      :searchValue="request('search')"
      :footerInfo="'عرض ' . $taxes->count() . ' من أصل ' . $taxes->total() . ' ضريبة / رسم'"
  >
    <x-slot name="filters">
      {{-- Type Filter --}}
      <form id="aracari-type-filter-form" class="relative md:w-44 flex-1">
        <select
            name="type"
            id="aracari-type-filter"
            class="w-full h-11 bg-white/70 border border-slate-200 rounded-xl px-3 pr-8 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-all appearance-none cursor-pointer"
        >
          <option value="">جميع الأنواع</option>
          <option value="percentage" @selected(request('type') === 'percentage')">نسبة مئوية (%)</option>
          <option value="fixed" @selected(request('type') === 'fixed')">قيمة ثابتة (ر.ي)</option>
        </select>
        <i data-lucide="percent" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
        <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
      </form>
    </x-slot>

    <x-slot name="thead">
      <tr>
        <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">اسم الضريبة / الرسم</th>
        <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">نوع الحساب</th>
        <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">القيمة</th>
        <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">تاريخ الإضافة</th>
        <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
      </tr>
    </x-slot>

    <x-slot name="rows">
      @forelse($taxes as $index => $tax)
        @php
            $isPercentage = $tax->type === 'percentage';
            $displayValue = $isPercentage ? number_format((float) $tax->rate) : number_format((float) $tax->amount);
            $typeBg    = $isPercentage ? 'bg-amber-50' : 'bg-emerald-50';
            $typeText  = $isPercentage ? 'text-amber-700' : 'text-emerald-700';
            $typeBorder = $isPercentage ? 'border-amber-100' : 'border-emerald-100';
            $iconBg    = $isPercentage ? 'bg-amber-50 border border-amber-100 text-amber-600' : 'bg-emerald-50 border border-emerald-100 text-emerald-600';
            $typeLabel = $isPercentage ? 'نسبة مئوية (%)' : 'مبلغ ثابت مقطوع';
            $valueColor = $isPercentage ? 'text-amber-600' : 'text-emerald-700';
            $valueSuffix = $isPercentage ? '%' : ' ر.ي';
        @endphp
        <tr class="hover:bg-slate-50/50 transition-colors group">

          {{-- اسم الضريبة: أيقونة + اسم + UUID --}}
          <td class="px-6 py-3.5">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold {{ $iconBg }}">
                @if($isPercentage)
                  <i data-lucide="percent" class="w-5 h-5"></i>
                @else
                  <span class="text-xs font-bold">ر.ي</span>
                @endif
              </div>
              <div class="min-w-0">
                <div class="font-bold text-slate-800 text-sm group-hover:text-amber-600 transition-colors line-clamp-1">{{ $tax->name }}</div>
                <div class="text-[10px] text-gray-400 font-mono block mt-0.5">
                  {{ str_pad(($taxes->firstItem() + $index), 4, '0', STR_PAD_LEFT) }} · {{ $tax->uuid }}
                </div>
              </div>
            </div>
          </td>

          {{-- نوع الحساب --}}
          <td class="px-6 py-3.5 text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border {{ $typeBg }} {{ $typeText }} {{ $typeBorder }}">
              @if($isPercentage)
                <i data-lucide="percent" class="w-3.5 h-3.5"></i>
              @else
                <span>ر.ي</span>
              @endif
              {{ $typeLabel }}
            </span>
          </td>

          {{-- القيمة --}}
          <td class="px-6 py-3.5 text-center">
            <span class="font-black {{ $valueColor }} text-sm" dir="ltr">
              {{ $displayValue }}<span class="text-[10px] font-bold">{{ $valueSuffix }}</span>
            </span>
          </td>

          {{-- تاريخ الإضافة --}}
          <td class="px-6 py-3.5 text-center">
            <span class="text-xs text-gray-400">{{ $tax->created_at ? $tax->created_at->format('Y/m/d') : '—' }}</span>
          </td>

          {{-- الإجراءات --}}
          <td class="px-6 py-3.5 text-center">
            <div class="flex items-center justify-center gap-1.5">
              <x-action-button
                  variant="edit"
                  data-edit-modal="tax-form-modal"
                  data-edit-json="{{ json_encode([
                      'uuid'       => $tax->uuid,
                      'name'       => $tax->name,
                      'type'       => $tax->type,
                      'rate'       => $tax->rate,
                      'amount'     => $tax->amount,
                      'created_at' => $tax->created_at?->format('Y-m-d H:i:s'),
                      'updated_at' => $tax->updated_at?->diffForHumans(),
                  ], JSON_UNESCAPED_UNICODE) }}"
                  :ariaLabel="'تعديل الضريبة'"
              />
              <x-action-button
                  variant="delete"
                  data-delete-url="{{ route('admin.taxes.destroy', $tax->uuid) }}"
                  data-delete-label="{{ $tax->name }}"
              />
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state
            icon="receipt"
            :title="(request('search') || request('type')) ? 'لا توجد نتائج مطابقة' : 'لا توجد ضرائب أو رسوم مسجلة'"
            :message="(request('search') || request('type')) ? 'جرّب تعديل كلمات البحث أو الفلتر' : 'ابدأ بإضافة أول ضريبة أو رسم مالي'"
            :colspan="5"
        >
          @if(!request('search') && !request('type'))
            <button
                type="button"
                data-create-modal="tax-form-modal"
                class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
            >
              <i data-lucide="plus-circle" class="w-4 h-4"></i>
              إضافة ضريبة جديدة
            </button>
          @endif
        </x-empty-state>
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$taxes" />
    </x-slot>
  </x-data-table-card>
  </div>{{-- end #aracari-refreshable --}}

</div>

{{-- ======================================================================= --}}
{{-- ===== Tax Form Modal (Create + Edit) ===== --}}
{{-- ======================================================================= --}}
<x-form-modal
    id="tax-form-modal"
    title="إضافة ضريبة أو رسم مالي جديد"
    :variant="'create'"
    :action="route('admin.taxes.store')"
    method="POST"
    successMessage="تم حفظ الضريبة بنجاح"
    maxWidth="max-w-lg"
>

  {{-- القسم 1: معلومات الضريبة الأساسية --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs flex items-center gap-1.5" style="color: #92400e;">
        <i data-lucide="receipt" class="w-3.5 h-3.5"></i>
        معلومات الضريبة الأساسية
      </h4>
      <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border text-rose-600 bg-rose-50 border-rose-200">مطلوب</span>
    </div>

    <div class="space-y-4">
      {{-- اسم الضريبة --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="tax-name">
          <i data-lucide="type" class="w-3.5 h-3.5"></i>
          اسم الضريبة أو الرسم <span class="text-rose-500">*</span>
        </label>
        <div class="relative">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-amber-600 transition-colors">
            <i data-lucide="type" class="w-4 h-4"></i>
          </div>
          <input
              type="text"
              name="name"
              id="tax-name"
              required
              maxlength="255"
              value="{{ old('name') }}"
              placeholder="مثال: ضريبة القيمة المضافة، رسوم دعم الصناديق..."
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 transition-all text-right"
          >
        </div>
        @error('name')
          <p class="text-[10px] text-rose-600 flex items-center gap-1">
            <i data-lucide="alert-circle" class="w-3 h-3"></i>
            {{ $message }}
          </p>
        @enderror
      </div>

      {{-- طريقة احتساب الضريبة --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
          <i data-lucide="calculator" class="w-3.5 h-3.5"></i>
          طريقة احتساب الضريبة / الرسم <span class="text-rose-500">*</span>
        </label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          {{-- نسبة مئوية --}}
          <button
              type="button"
              id="tax-type-percentage-btn"
              onclick="setTaxType('percentage')"
              class="p-4 rounded-2xl border text-right transition-all flex flex-col justify-between h-24 relative overflow-hidden border-gray-200 bg-white hover:bg-amber-50/20 text-slate-600"
          >
            <div class="flex justify-between items-center w-full">
              <span class="text-xs font-black block">نسبة مئوية (%)</span>
              <input type="radio" name="type" value="percentage" {{ old('type', 'percentage') === 'percentage' ? 'checked' : '' }} class="accent-amber-500">
            </div>
            <div class="flex items-center gap-1 text-[11px] text-gray-400 mt-2 font-medium">
              <i data-lucide="percent" class="w-3.5 h-3.5 text-amber-500"></i>
              <span>تُحسب كنسبة مئوية من سعر التذكرة</span>
            </div>
          </button>

          {{-- قيمة ثابتة --}}
          <button
              type="button"
              id="tax-type-fixed-btn"
              onclick="setTaxType('fixed')"
              class="p-4 rounded-2xl border text-right transition-all flex flex-col justify-between h-24 relative overflow-hidden border-gray-200 bg-white hover:bg-emerald-50/10 text-slate-600"
          >
            <div class="flex justify-between items-center w-full">
              <span class="text-xs font-black block">قيمة ثابتة (ر.ي)</span>
              <input type="radio" name="type" value="fixed" {{ old('type') === 'fixed' ? 'checked' : '' }} class="accent-emerald-700">
            </div>
            <div class="flex items-center gap-1 text-[11px] text-gray-400 mt-2 font-medium">
              <span class="font-bold text-xs text-emerald-600 font-mono">ر.ي</span>
              <span>مبلغ مقطوع وثابت لكل تذكرة مباشرة</span>
            </div>
          </button>
        </div>
        @error('type')
          <p class="text-[10px] text-rose-600 flex items-center gap-1">
            <i data-lucide="alert-circle" class="w-3 h-3"></i>
            {{ $message }}
          </p>
        @enderror
      </div>

      {{-- قيمة الضريبة --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="tax-value">
          <i data-lucide="banknote" class="w-3.5 h-3.5"></i>
          قيمة الضريبة / الرسم <span class="text-rose-500">*</span>
        </label>
        <div class="relative">
          <input
              type="number"
              name="rate"
              id="tax-rate"
              step="any"
              min="0"
              max="100"
              dir="ltr"
              value="{{ old('rate') }}"
              placeholder="مثال: 15"
              class="w-full pr-4 pl-20 py-2.5 bg-[#f3f3f6] border border-[#e2e2e5] rounded-2xl text-sm focus:outline-none focus:border-amber-500 focus:bg-white transition-all text-left font-mono font-bold"
          >
          <input
              type="number"
              name="amount"
              id="tax-amount"
              step="any"
              min="0"
              dir="ltr"
              value="{{ old('amount') }}"
              placeholder="مثال: 1000"
              class="w-full pr-4 pl-20 py-2.5 bg-[#f3f3f6] border border-[#e2e2e5] rounded-2xl text-sm focus:outline-none focus:border-amber-500 focus:bg-white transition-all text-left font-mono font-bold"
              style="display:none;"
          >
          <div class="absolute left-4 top-1/2 -translate-y-1/2 flex items-center pointer-events-none text-xs font-bold text-gray-400">
            <span id="tax-value-suffix">%</span>
          </div>
        </div>
        <p class="text-[11px] text-gray-400 mt-1 flex items-center gap-1 font-medium">
          <i data-lucide="help-circle" class="w-3.5 h-3.5 shrink-0"></i>
          <span id="tax-value-hint">سيتم مضافتها كنسبة من السعر الكلي للتذكرة.</span>
        </p>
        @error('rate')
          <p class="text-[10px] text-rose-600 flex items-center gap-1">
            <i data-lucide="alert-circle" class="w-3 h-3"></i>
            {{ $message }}
          </p>
        @enderror
        @error('amount')
          <p class="text-[10px] text-rose-600 flex items-center gap-1">
            <i data-lucide="alert-circle" class="w-3 h-3"></i>
            {{ $message }}
          </p>
        @enderror
      </div>
    </div>
  </div>

  {{-- القسم 2: معلومات إضافية (للتعديل فقط) --}}
  <div class="space-y-4" id="tax-extra-info" style="display:none;">
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
        <span class="text-xs font-mono text-slate-600" dir="ltr" id="tax-info-created">—</span>
      </div>
      <div class="flex items-center gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
        <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-slate-400"></i>
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">آخر تحديث:</span>
        <span class="text-xs font-bold text-slate-600" id="tax-info-updated">—</span>
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
      <span id="tax-cancel-text">إلغاء التراجع</span>
    </button>
    <button
        type="submit"
        class="flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95"
        id="tax-submit-btn"
        style="background: linear-gradient(to right, #d97706, #b45309);"
    >
      <i data-lucide="save" class="w-4 h-4"></i>
      <span id="tax-submit-text">حفظ التعديلات والبيانات</span>
    </button>
  </x-slot>
</x-form-modal>

<x-delete-confirm />

@endsection

@push('scripts')
<script>
  // ===== Type switcher: percentage / fixed =====
  function setTaxType(type) {
    var pBtn = document.getElementById('tax-type-percentage-btn');
    var fBtn = document.getElementById('tax-type-fixed-btn');
    var rateInput = document.getElementById('tax-rate');
    var amountInput = document.getElementById('tax-amount');
    var suffix = document.getElementById('tax-value-suffix');
    var hint = document.getElementById('tax-value-hint');

    if (type === 'percentage') {
      pBtn.className = 'p-4 rounded-2xl border text-right transition-all flex flex-col justify-between h-24 relative overflow-hidden border-amber-500 bg-amber-50/20 text-amber-900 shadow-sm';
      fBtn.className = 'p-4 rounded-2xl border text-right transition-all flex flex-col justify-between h-24 relative overflow-hidden border-gray-200 bg-white hover:bg-emerald-50/10 text-slate-600';
      rateInput.style.display = '';
      amountInput.style.display = 'none';
      suffix.textContent = '%';
      hint.textContent = 'سيتم مضافتها كنسبة من السعر الكلي للتذكرة.';
    } else {
      fBtn.className = 'p-4 rounded-2xl border text-right transition-all flex flex-col justify-between h-24 relative overflow-hidden border-emerald-700 bg-emerald-50/10 text-emerald-950 shadow-sm';
      pBtn.className = 'p-4 rounded-2xl border text-right transition-all flex flex-col justify-between h-24 relative overflow-hidden border-gray-200 bg-white hover:bg-amber-50/20 text-slate-600';
      rateInput.style.display = 'none';
      amountInput.style.display = '';
      suffix.textContent = 'ريال يمني';
      hint.textContent = 'سيتم إضافة هذا المبلغ الإضافي المقطوع على إجمالي الفاتورة.';
    }
    // Sync radio buttons
    var radios = document.querySelectorAll('input[name="type"]');
    radios.forEach(function(r) { r.checked = r.value === type; });
  }

  // Initialize default state
  (function() {
    var checked = document.querySelector('input[name="type"]:checked');
    if (checked) setTaxType(checked.value);
  })();

  // ===== Tax form modal: dynamic create/edit =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'tax-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.uuid);

    if (isEdit) {
      // Update URL + method
      form.action = "{{ route('admin.taxes.update', '__UUID__') }}".replace('__UUID__', data.uuid);
      var m = form.querySelector('input[name="_method"]');
      if (m) m.value = 'PUT';
      else { var el = document.createElement('input'); el.type = 'hidden'; el.name = '_method'; el.value = 'PUT'; el.setAttribute('data-dynamic','true'); form.appendChild(el); }

      // Banner amber
      var b = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (b) b.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';
      var t = modal.querySelector('.aracari-modal-panel h2');
      if (t) t.innerHTML = '<i data-lucide="receipt" class="w-5 h-5"></i> تعديل ضريبة أو رسم نظام';

      document.getElementById('tax-submit-btn').style.background = 'linear-gradient(to right, #b45309, #92400e)';
      document.getElementById('tax-submit-text').textContent = 'حفظ التعديلات والبيانات';

      // Fill fields
      var nameInput = document.getElementById('tax-name');
      if (nameInput) nameInput.value = data.name || '';

      setTaxType(data.type || 'percentage');

      if (data.type === 'percentage') {
        var rateInput = document.getElementById('tax-rate');
        if (rateInput) rateInput.value = data.rate || '';
      } else {
        var amountInput = document.getElementById('tax-amount');
        if (amountInput) amountInput.value = data.amount || '';
      }

      // Extra info
      document.getElementById('tax-extra-info').style.display = '';
      document.getElementById('tax-info-created').textContent = data.created_at || '—';
      document.getElementById('tax-info-updated').textContent = data.updated_at || '—';

    } else {
      form.action = "{{ route('admin.taxes.store') }}";
      var dm = form.querySelector('input[data-dynamic="true"]');
      if (dm) dm.remove();

      var b = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (b) b.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-800 to-amber-950';
      var t = modal.querySelector('.aracari-modal-panel h2');
      if (t) t.innerHTML = '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة ضريبة أو رسم مالي جديد';

      document.getElementById('tax-submit-btn').style.background = 'linear-gradient(to right, #d97706, #b45309)';
      document.getElementById('tax-submit-text').textContent = 'حفظ التعديلات والبيانات';

      document.getElementById('tax-name').value = '';
      document.getElementById('tax-rate').value = '';
      document.getElementById('tax-amount').value = '';
      setTaxType('percentage');
      document.getElementById('tax-extra-info').style.display = 'none';
    }
    if (window.lucide) window.lucide.createIcons();
  });
</script>
@endpush
