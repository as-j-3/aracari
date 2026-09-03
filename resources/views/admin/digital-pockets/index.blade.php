@extends('layouts.admin')

@section('title', 'أركاري | المحافظ الرقمية')
@section('header_title', 'المحافظ الرقمية')

@section('content')

@php
    $user      = auth()->user();
    $canCreate = $user && $user->hasPermission('digital-pockets:create');
    $canEdit   = $user && $user->hasPermission('digital-pockets:edit');
    $canDelete = $user && $user->hasPermission('digital-pockets:delete');

    // إحصائيات من قاعدة البيانات
    $allPockets    = \App\Models\DigitalPocket::all();
    $totalCount    = $allPockets->count();
    $activeCount   = $allPockets->where('status', 'active')->count();
    $inactiveCount = $totalCount - $activeCount;
    $usedCurrencies = $allPockets->pluck('currency_id')->filter()->unique()->count();

    // فلتر العملة
    $activeCurrencyFilter = request('currency_id');
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="wallet"
      title="المحافظ الرقمية"
      :count="$totalCount"
      countLabel="محفظة"
      description="إدارة المحافظ الرقمية المتاحة لاستلام الإيرادات"
      descriptionIcon="wallet"
  >
    @if($canCreate)
      <button
          type="button"
          data-create-modal="pocket-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-violet-500 to-violet-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-violet-500/20 hover:shadow-violet-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إضافة محفظة رقمية جديدة
      </button>
    @endif
  </x-page-header>

  {{-- ===== Stat Cards (4) ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    {{-- إجمالي المحافظ --}}
    <x-stat-card
        icon="wallet"
        :label="'إجمالي المحافظ'"
        :value="$totalCount"
        :gradient="'from-slate-900 to-slate-800'"
        :accentColor="'violet'"
        :badge="'محفظة'"
    />
    {{-- محافظ نشطة --}}
    <x-stat-card
        icon="check-circle"
        :label="'محافظ نشطة'"
        :value="$activeCount"
        :gradient="'from-emerald-900/90 to-emerald-800/90'"
        :accentColor="'emerald'"
        :badge="'نشطة'"
    />
    {{-- محافظ غير نشطة --}}
    <x-stat-card
        icon="x-circle"
        :label="'محافظ غير نشطة'"
        :value="$inactiveCount"
        :gradient="'from-rose-900/90 to-rose-800/90'"
        :accentColor="'rose'"
        :badge="'غير نشطة'"
    />
    {{-- عملات مستخدمة --}}
    <x-stat-card
        icon="refresh-cw"
        :label="'عملات مستخدمة'"
        :value="$usedCurrencies"
        :gradient="'from-blue-900/90 to-blue-800/90'"
        :accentColor="'blue'"
        :badge="'عملة'"
    />
  </div>

  {{-- ===== Refreshable region ===== --}}
  <div id="aracari-refreshable" class="space-y-6">

    {{-- ===== Data Table Card ===== --}}
    <x-data-table-card
        :searchable="true"
        :searchMode="'server'"
        searchPlaceholder="ابحث باسم المحفظة..."
        :footerInfo="'عرض ' . $pockets->count() . ' من أصل ' . $pockets->total() . ' محفظة'"
    >
      {{-- فلتر العملة (فوق الجدول) --}}
      <x-slot name="filters">
        <div class="flex items-center gap-3">
          <select
              id="pocket-currency-filter"
              onchange="window.location.href = '{{ request()->url() }}' + (this.value ? '?currency_id=' + this.value : '')"
              class="h-11 bg-white/70 border border-slate-200 rounded-xl px-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500/30 focus:border-violet-500 transition-all appearance-none cursor-pointer min-w-[160px]"
          >
            <option value="">جميع العملات</option>
            @foreach($currencies as $cur)
              <option value="{{ $cur->id }}" {{ (string) $activeCurrencyFilter === (string) $cur->id ? 'selected' : '' }}>{{ $cur->name }} ({{ $cur->code }})</option>
            @endforeach
          </select>
          @if($activeCurrencyFilter)
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
        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">اسم المحفظة</th>
        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">العملة</th>
        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الرصيد</th>
        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">تاريخ الإنشاء</th>
        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
      </x-slot>

      <x-slot name="rows">
        @forelse($pockets as $pocket)
          @php
            $pocketStatus = $pocket->status ?? 'active';
            $pocketBalance = $pocket->balance ?? 0;
            $isActive = $pocketStatus === 'active';
          @endphp
          <tr class="hover:bg-slate-50/50 transition-colors group">
            {{-- # --}}
            <td class="px-6 py-4">
              <span class="font-mono font-black text-slate-400 text-sm">{{ str_pad(($pockets->firstItem() + $loop->index), 4, '0', STR_PAD_LEFT) }}</span>
            </td>

            {{-- اسم المحفظة --}}
            <td data-searchable class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500/20 to-violet-600/10 flex items-center justify-center border border-violet-100 group-hover:scale-110 transition-transform duration-300">
                  <i data-lucide="wallet" class="w-5 h-5 text-violet-600"></i>
                </div>
                <span class="font-bold text-slate-800">{{ $pocket->pocket_name }}</span>
              </div>
            </td>

            {{-- العملة --}}
            <td class="px-6 py-4 text-center">
              @if($pocket->currency)
                <div class="flex flex-col items-center gap-1">
                  <span class="inline-flex items-center gap-1.5 font-mono font-black text-slate-800 bg-blue-50 border border-blue-200 px-3 py-1.5 rounded-xl" dir="ltr">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-blue-600"></i>
                    {{ $pocket->currency->code }}
                  </span>
                  <span class="text-[10px] text-slate-400 font-medium">{{ $pocket->currency->name }}</span>
                </div>
              @else
                <span class="text-xs text-slate-400 font-medium">—</span>
              @endif
            </td>

            {{-- الحالة --}}
            <td class="px-6 py-4 text-center">
              @if($isActive)
                <span class="inline-flex items-center gap-1.5 text-emerald-700 text-xs font-bold bg-emerald-50 border border-emerald-200 px-3 py-1.5 rounded-xl">
                  <i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-600"></i>
                  نشطة
                </span>
              @else
                <span class="inline-flex items-center gap-1.5 text-rose-700 text-xs font-bold bg-rose-50 border border-rose-200 px-3 py-1.5 rounded-xl">
                  <i data-lucide="x-circle" class="w-3.5 h-3.5 text-rose-600"></i>
                  غير نشطة
                </span>
              @endif
            </td>

            {{-- الرصيد --}}
            <td class="px-6 py-4 text-center">
              <span class="font-mono font-black text-violet-600 text-sm" dir="ltr">{{ number_format((float) $pocketBalance, 2) }}</span>
            </td>

            {{-- تاريخ الإنشاء --}}
            <td class="px-6 py-4">
              <span class="text-xs font-bold text-slate-500" dir="ltr">{{ $pocket->created_at ? $pocket->created_at->format('Y/m/d') : '—' }}</span>
            </td>

            {{-- الإجراءات --}}
            <td class="px-6 py-4 text-center">
              <div class="flex items-center justify-center gap-1.5">
                @if($canEdit)
                  <x-action-button
                      variant="edit"
                      data-edit-modal="pocket-form-modal"
                      data-edit-json="{{ json_encode([
                          'id'           => $pocket->id,
                          'pocket_name'  => $pocket->pocket_name,
                          'currency_id'  => $pocket->currency_id,
                          'status'       => $pocketStatus,
                          'balance'      => $pocketBalance,
                          'created_at'   => $pocket->created_at ? $pocket->created_at->format('Y-m-d H:i:s') : '',
                          'currency_code' => $pocket->currency ? $pocket->currency->code : '',
                      ], JSON_UNESCAPED_UNICODE) }}"
                      :ariaLabel="'تعديل ' . $pocket->pocket_name"
                  />
                @endif
                @if($canDelete)
                  <x-action-button
                      variant="delete"
                      data-delete-url="{{ route('admin.digital-pockets.destroy', $pocket) }}"
                      data-delete-label="$pocket->pocket_name"
                  />
                @endif
              </div>
            </td>
          </tr>
        @empty
          <x-empty-state
              icon="wallet"
              :title="request('search') ? 'لا توجد نتائج مطابقة' : 'لا توجد محافظ رقمية'"
              :message="request('search') ? 'جرّب تعديل كلمات البحث' : 'قم بإضافة محفظة رقمية جديدة لاستلام الإيرادات'"
              :colspan="7"
          >
            @if(!request('search') && !request('currency_id') && $canCreate)
              <button
                  type="button"
                  data-create-modal="pocket-form-modal"
                  class="inline-flex items-center gap-2 bg-violet-500 hover:bg-violet-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
              >
                <i data-lucide="plus" class="w-4 h-4"></i>
                إضافة محفظة رقمية جديدة
              </button>
            @endif
            @if(request('search') || request('currency_id'))
              <a href="{{ request()->url() }}" class="mt-3 text-violet-600 hover:text-violet-700 inline-flex items-center gap-1 font-bold text-sm">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                إعادة تعيين الفلاتر
              </a>
            @endif
          </x-empty-state>
        @endforelse
      </x-slot>

      <x-slot name="footer">
        <x-pagination :paginator="$pockets" />
      </x-slot>
    </x-data-table-card>

  </div>{{-- end #aracari-refreshable -->}

</div>

{{-- ===== Pocket Form Modal (Create + Edit) ===== --}}
<x-form-modal
    id="pocket-form-modal"
    title="إضافة محفظة رقمية جديدة"
    :variant="'create'"
    :action="route('admin.digital-pockets.store')"
    method="POST"
    successMessage="تم حفظ المحفظة بنجاح"
>

  {{-- القسم 1: معلومات المحفظة --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs text-violet-600 flex items-center gap-1.5" id="pocket-section-info-title">
        <i data-lucide="wallet" class="w-3.5 h-3.5"></i>
        معلومات المحفظة
      </h4>
      <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border text-rose-600 bg-rose-50 border-rose-200" id="pocket-info-badge">مطلوب</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      {{-- اسم المحفظة --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="dp-pocket-name">
          <i data-lucide="wallet" class="w-3.5 h-3.5"></i>
          اسم المحفظة <span class="text-rose-500">*</span>
        </label>
        <div class="relative group">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-violet-600 transition-colors">
            <i data-lucide="wallet" class="w-4 h-4"></i>
          </div>
          <input
              type="text"
              name="pocket_name"
              id="dp-pocket-name"
              required
              maxlength="100"
              value="{{ old('pocket_name') }}"
              placeholder="مثال: محفظة جوالي"
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-violet-500/10 focus:border-violet-600 transition-all text-right"
          >
        </div>
        <p class="text-[10px] text-slate-400 leading-relaxed mt-1.5 flex items-center gap-1">
          <i data-lucide="info" class="w-3 h-3"></i>
          الاسم الظاهر للمستخدمين عند اختيار المحفظة
        </p>
      </div>

      {{-- العملة --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="dp-currency">
          <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
          العملة
        </label>
        <div class="relative">
          <i data-lucide="refresh-cw" class="w-4 h-4 text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
          <select
              name="currency_id"
              id="dp-currency"
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-violet-500/10 focus:border-violet-600 transition-all text-right appearance-none cursor-pointer"
          >
            <option value="">اختر العملة</option>
            @foreach($currencies as $cur)
              <option value="{{ $cur->id }}" {{ old('currency_id') == $cur->id ? 'selected' : '' }}>{{ $cur->name }} ({{ $cur->code }})</option>
            @endforeach
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
        </div>
        <p class="text-[10px] text-slate-400 leading-relaxed mt-1.5 flex items-center gap-1">
          <i data-lucide="info" class="w-3 h-3"></i>
          العملة المستخدمة لعمليات استلام الإيرادات
        </p>
      </div>
    </div>

    {{-- الحالة --}}
    <div class="space-y-1.5">
      <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="dp-status">
        <i data-lucide="circle-dot" class="w-3.5 h-3.5"></i>
        الحالة
      </label>
      <select
          name="status"
          id="dp-status"
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-violet-500/10 focus:border-violet-600 transition-all text-right appearance-none cursor-pointer"
      >
        <option value="active" selected>نشطة</option>
        <option value="inactive">غير نشطة</option>
      </select>
    </div>
  </div>

  {{-- القسم 2: معلومات إضافية (للتعديل فقط) --}}
  <div class="space-y-4" id="pocket-extra-info" style="display:none;">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs text-slate-600 flex items-center gap-1.5">
        <i data-lucide="info" class="w-3.5 h-3.5"></i>
        معلومات إضافية
      </h4>
      <span class="text-[9px] font-bold text-slate-500 bg-slate-50 border border-slate-200 px-2 py-0.5 rounded-full">للقراءة فقط</span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-3 text-center">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-center gap-1">
          <i data-lucide="wallet" class="w-3 h-3"></i>
          الرصيد الحالي
        </p>
        <p class="font-mono font-bold text-violet-600 text-sm mt-1.5" dir="ltr" id="pocket-info-balance">0.00</p>
      </div>
      <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-3 text-center">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-center gap-1">
          <i data-lucide="calendar" class="w-3 h-3"></i>
          تاريخ الإنشاء
        </p>
        <p class="font-bold text-slate-700 text-xs mt-1.5" dir="ltr" id="pocket-info-created">—</p>
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
      <span id="pocket-cancel-text">إعادة تعيين</span>
    </button>
    <button
        type="submit"
        class="flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95 bg-violet-600 hover:bg-violet-700"
        id="pocket-submit-btn"
    >
      <i data-lucide="save" class="w-4 h-4"></i>
      <span id="pocket-submit-text">حفظ المحفظة</span>
    </button>
  </x-slot>
</x-form-modal>

@endsection

@push('scripts')
<script>
  // ===== Pocket form modal: dynamic action + title + variant switching =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'pocket-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.id);

    if (isEdit) {
      // edit mode → PUT to update route
      var updateUrl = "{{ route('admin.digital-pockets.update', '__ID__') }}".replace('__ID__', data.id);
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
        '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل المحفظة الرقمية';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }
      form.setAttribute('data-success-message', 'تم تحديث المحفظة بنجاح');
      // subtitle
      var subtitleEl = modal.querySelector('.aracari-modal-panel .relative.z-10 p');
      if (subtitleEl) {
        subtitleEl.innerHTML = '<span class="inline-block text-xs text-white/85 bg-slate-900/40 border border-white/10 px-2 py-0.5 rounded-full max-w-[260px] truncate align-middle font-bold">' + (data.pocket_name || '') + '</span>';
        subtitleEl.style.display = '';
      }
      // info badge
      var infoBadge = modal.querySelector('#pocket-info-badge');
      if (infoBadge) {
        infoBadge.textContent = 'قابل للتعديل';
        infoBadge.className = 'text-[9px] font-bold px-2 py-0.5 rounded-full border text-amber-600 bg-amber-50 border-amber-200';
        var sectionTitle = modal.querySelector('#pocket-section-info-title');
        if (sectionTitle) sectionTitle.className = 'font-bold text-xs text-amber-600 flex items-center gap-1.5';
      }
      // fill form fields
      var nameInput = modal.querySelector('#dp-pocket-name');
      if (nameInput) nameInput.value = data.pocket_name || '';
      var currencySelect = modal.querySelector('#dp-currency');
      if (currencySelect) currencySelect.value = data.currency_id || '';
      var statusSelect = modal.querySelector('#dp-status');
      if (statusSelect) statusSelect.value = data.status || 'active';
      // show extra info
      var extraInfo = modal.querySelector('#pocket-extra-info');
      if (extraInfo) extraInfo.style.display = '';
      var infoBalance = modal.querySelector('#pocket-info-balance');
      if (infoBalance) infoBalance.textContent = parseFloat(data.balance || 0).toFixed(2);
      var infoCreated = modal.querySelector('#pocket-info-created');
      if (infoCreated) infoCreated.textContent = data.created_at || '—';
      // cancel text
      var cancelText = modal.querySelector('#pocket-cancel-text');
      if (cancelText) cancelText.textContent = 'إلغاء';
      // submit button style
      var submitBtn = modal.querySelector('#pocket-submit-btn');
      if (submitBtn) submitBtn.className = 'flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95 bg-amber-600 hover:bg-amber-700';
      var submitText = modal.querySelector('#pocket-submit-text');
      if (submitText) submitText.textContent = 'حفظ التعديلات';
    } else {
      // create mode → POST to store route
      form.setAttribute('action', "{{ route('admin.digital-pockets.store') }}");
      var m = form.querySelector('input[name="_method"]');
      if (m) m.remove();
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-violet-900 to-violet-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة محفظة رقمية جديدة';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-violet-500/20 text-violet-300 border-violet-500/30';
        badge.textContent = 'جديد';
      }
      form.setAttribute('data-success-message', 'تم حفظ المحفظة بنجاح');
      // subtitle
      var subtitleEl = modal.querySelector('.aracari-modal-panel .relative.z-10 p');
      if (subtitleEl) {
        subtitleEl.textContent = 'إضافة محفظة رقمية جديدة لاستلام الإيرادات';
        subtitleEl.style.display = '';
      }
      // info badge
      var infoBadge = modal.querySelector('#pocket-info-badge');
      if (infoBadge) {
        infoBadge.textContent = 'مطلوب';
        infoBadge.className = 'text-[9px] font-bold px-2 py-0.5 rounded-full border text-rose-600 bg-rose-50 border-rose-200';
        var sectionTitle = modal.querySelector('#pocket-section-info-title');
        if (sectionTitle) sectionTitle.className = 'font-bold text-xs text-violet-600 flex items-center gap-1.5';
      }
      // reset form fields
      var nameInput = modal.querySelector('#dp-pocket-name');
      if (nameInput) nameInput.value = '';
      var currencySelect = modal.querySelector('#dp-currency');
      if (currencySelect) currencySelect.value = '';
      var statusSelect = modal.querySelector('#dp-status');
      if (statusSelect) statusSelect.value = 'active';
      // hide extra info
      var extraInfo = modal.querySelector('#pocket-extra-info');
      if (extraInfo) extraInfo.style.display = 'none';
      // cancel text
      var cancelText = modal.querySelector('#pocket-cancel-text');
      if (cancelText) cancelText.textContent = 'إعادة تعيين';
      // submit button style
      var submitBtn = modal.querySelector('#pocket-submit-btn');
      if (submitBtn) submitBtn.className = 'flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95 bg-violet-600 hover:bg-violet-700';
      var submitText = modal.querySelector('#pocket-submit-text');
      if (submitText) submitText.textContent = 'حفظ المحفظة';
    }
    if (window.lucide) window.lucide.createIcons();
  });
</script>
@endpush
