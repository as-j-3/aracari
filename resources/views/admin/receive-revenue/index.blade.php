@extends('layouts.admin')

@section('title', 'أركاري | الحسابات البنكية')
@section('header_title', 'الحسابات البنكية')

@section('content')

@php
    $user      = auth()->user();
    $canCreate = $user && $user->hasPermission('receive-revenue:create');
    $canEdit   = $user && $user->hasPermission('receive-revenue:edit');
    $canDelete = $user && $user->hasPermission('receive-revenue:delete');

    // جلب المحافظ والمزودين للمودال (لأن controller index() لا يمررهم)
    $pockets   = \App\Models\DigitalPocket::with('currency')->orderBy('pocket_name')->get();
    $providers = \App\Models\ProviderService::orderBy('name')->get();

    // إحصائيات من قاعدة البيانات
    $allAccounts      = \App\Models\ReceiveRevenueMethod::with('digitalPocket')->get();
    $totalCount       = $allAccounts->count();
    $activeCount      = $allAccounts->filter(fn($a) => ($a->digitalPocket->status ?? 'active') === 'active')->count();
    $inactiveCount    = $totalCount - $activeCount;
    $uniqueProviders  = $allAccounts->pluck('provider_id')->unique()->count();
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="landmark"
      title="الحسابات البنكية"
      :count="$totalCount"
      countLabel="حساب"
      description="إدارة حسابات استلام الإيرادات للمزودين"
      descriptionIcon="info"
  >
    @if($canCreate)
      <button
          type="button"
          data-create-modal="receive-revenue-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إضافة حساب بنكي
      </button>
    @endif
  </x-page-header>

  {{-- ===== Stat Cards (4) ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    {{-- إجمالي الحسابات --}}
    <x-stat-card
        icon="landmark"
        :label="'إجمالي الحسابات'"
        :value="$totalCount"
        :gradient="'from-slate-900 to-slate-800'"
        :accentColor="'emerald'"
    />
    {{-- حسابات نشطة --}}
    <x-stat-card
        icon="check-circle"
        :label="'حسابات نشطة'"
        :value="$activeCount"
        :gradient="'from-emerald-900/90 to-emerald-800/90'"
        :accentColor="'emerald'"
        :badge="'نشط'"
    />
    {{-- حسابات غير نشطة --}}
    <x-stat-card
        icon="x-circle"
        :label="'حسابات غير نشطة'"
        :value="$inactiveCount"
        :gradient="'from-rose-900/90 to-rose-800/90'"
        :accentColor="'rose'"
        :badge="'غير نشط'"
    />
    {{-- إجمالي المزودين --}}
    <x-stat-card
        icon="building-2"
        :label="'إجمالي المزودين'"
        :value="$uniqueProviders"
        :gradient="'from-blue-900/90 to-blue-800/90'"
        :accentColor="'blue'"
        :badge="'مزود'"
    />
  </div>

  {{-- ===== Refreshable region ===== --}}
  <div id="aracari-refreshable" class="space-y-6">

    {{-- ===== Data Table Card ===== --}}
    <x-data-table-card
        :searchable="true"
        :searchMode="'client'"
        searchPlaceholder="ابحث عن حساب..."
        :footerInfo="'عرض ' . $accounts->count() . ' من أصل ' . $accounts->total() . ' حساب'"
    >

      <x-slot name="thead">
        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المحفظة</th>
        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المزود</th>
        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">اسم الحساب</th>
        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">رقم الحساب</th>
        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
      </x-slot>

      <x-slot name="rows">
        @forelse($accounts as $account)
          @php
            $pocketStatus = $account->digitalPocket->status ?? 'active';
            $isActive = $pocketStatus === 'active';
          @endphp
          <tr class="hover:bg-slate-50/50 transition-colors group" data-searchable-content="{{ $account->account_name }} {{ $account->digitalPocket->pocket_name ?? '' }} {{ $account->provider->name ?? '' }}">
            {{-- # --}}
            <td class="px-6 py-4">
              <span class="font-mono font-black text-slate-400 text-sm">
                {{ str_pad(($accounts->firstItem() + $loop->index), 4, '0', STR_PAD_LEFT) }}
              </span>
            </td>

            {{-- المحفظة --}}
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500/20 to-violet-600/10 flex items-center justify-center border border-violet-100 group-hover:scale-110 transition-transform duration-300">
                  <i data-lucide="wallet" class="w-5 h-5 text-violet-600"></i>
                </div>
                <div>
                  <div class="font-bold text-slate-800">{{ $account->digitalPocket->pocket_name ?? '—' }}</div>
                  <div class="text-[10px] font-bold text-slate-400">
                    {{ $account->digitalPocket->currency->code ?? '—' }}
                  </div>
                </div>
              </div>
            </td>

            {{-- المزود --}}
            <td class="px-6 py-4">
              <div class="flex items-center gap-2">
                <i data-lucide="building-2" class="w-4 h-4 text-slate-400 flex-shrink-0"></i>
                <span class="font-bold text-slate-700">{{ $account->provider->name ?? '—' }}</span>
              </div>
            </td>

            {{-- اسم الحساب --}}
            <td class="px-6 py-4">
              <div class="flex items-center gap-2">
                <i data-lucide="landmark" class="w-4 h-4 text-emerald-500 flex-shrink-0"></i>
                <span class="font-bold text-slate-800">{{ $account->account_name }}</span>
              </div>
            </td>

            {{-- رقم الحساب (مقنع مع زر إظهار/إخفاء) --}}
            <td class="px-6 py-4 text-center">
              <div class="flex items-center justify-center gap-1.5">
                <span class="inline-flex items-center gap-1.5 font-mono font-bold text-slate-700 bg-slate-100/50 border border-slate-200 px-2.5 py-1 rounded-xl" id="acc-num-{{ $account->id }}">
                  <i data-lucide="hash" class="w-3 h-3 text-slate-400"></i>
                  {{ $account->account_number_last_4 }}
                </span>
                <button
                    type="button"
                    onclick="toggleAccountNumber({{ $account->id }}, this, {{ json_encode($account->account_number) }})"
                    class="w-7 h-7 rounded-lg bg-white border border-slate-200 text-slate-400 hover:text-emerald-600 hover:border-emerald-300 transition-all flex items-center justify-center"
                    title="إظهار/إخفاء رقم الحساب"
                    data-eye-toggle
                >
                  <i data-lucide="eye-off" class="w-3.5 h-3.5"></i>
                </button>
              </div>
            </td>

            {{-- الحالة --}}
            <td class="px-6 py-4 text-center">
              @if($isActive)
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
            <td class="px-6 py-4 text-center">
              <div class="flex items-center justify-center gap-1.5">
                @if($canEdit)
                  <x-action-button
                      variant="edit"
                      data-edit-modal="receive-revenue-form-modal"
                      data-edit-json="{{ json_encode([
                          'id'                => $account->id,
                          'account_name'      => $account->account_name,
                          'account_number'    => $account->account_number,
                          'digital_pocket_id' => $account->digital_pocket_id,
                          'provider_id'       => $account->provider_id,
                          'provider_name'     => $account->provider->name ?? '',
                          'pocket_name'       => $account->digitalPocket->pocket_name ?? '',
                          'created_at'        => $account->created_at ? $account->created_at->format('Y-m-d H:i:s') : '',
                          'updated_at'        => $account->updated_at ? $account->updated_at->format('Y-m-d H:i:s') : '',
                      ], JSON_UNESCAPED_UNICODE) }}"
                      :ariaLabel="'تعديل ' . $account->account_name"
                  />
                @endif
                @if($canDelete)
                  <x-action-button
                      variant="delete"
                      data-delete-url="{{ route('admin.receive-revenue.destroy', $account) }}"
                      data-delete-label="{{$account->account_name}}"
                  />
                @endif
              </div>
            </td>
          </tr>
        @empty
          <x-empty-state
              icon="landmark"
              :title="request('search') ? 'لا توجد نتائج مطابقة' : 'لا توجد حسابات بنكية'"
              :message="request('search') ? 'جرّب تعديل كلمات البحث' : 'قم بإضافة حساب بنكي جديد لاستلام الإيرادات'"
              :colspan="7"
          >
            @if(!request('search') && $canCreate)
              <button
                  type="button"
                  data-create-modal="receive-revenue-form-modal"
                  class="inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
              >
                <i data-lucide="plus" class="w-4 h-4"></i>
                إضافة حساب بنكي
              </button>
            @endif
            @if(request('search'))
              <a href="{{ request()->url() }}" class="mt-3 text-emerald-600 hover:text-emerald-700 inline-flex items-center gap-1 font-bold text-sm">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                إعادة تعيين الفلاتر
              </a>
            @endif
          </x-empty-state>
        @endforelse
      </x-slot>

      <x-slot name="footer">
        <x-pagination :paginator="$accounts" />
      </x-slot>
    </x-data-table-card>

  </div>{{-- end #aracari-refreshable -->}

</div>

{{-- ===== Receive Revenue Form Modal (Create + Edit) ===== --}}
<x-form-modal
    id="receive-revenue-form-modal"
    title="إضافة حساب بنكي"
    :variant="'create'"
    :action="route('admin.receive-revenue.store')"
    method="POST"
    successMessage="تم حفظ الحساب البنكي بنجاح"
>

  {{-- القسم 1: معلومات الحساب البنكي --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs text-emerald-600 flex items-center gap-1.5" id="rr-section1-title">
        <i data-lucide="landmark" class="w-3.5 h-3.5"></i>
        معلومات الحساب البنكي
      </h4>
      <span class="text-[9px] font-bold text-rose-600 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-full" id="rr-section1-badge">مطلوب</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

      {{-- المحفظة الرقمية --}}
      <x-form-field name="digital_pocket_id" label="المحفظة الرقمية" icon="wallet" required>
        <div class="relative">
          <select
              name="digital_pocket_id"
              id="rr-digital-pocket-id"
              required
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-[#10b981] transition-all text-right appearance-none cursor-pointer"
          >
            <option value="">اختر المحفظة</option>
            @foreach($pockets as $pocket)
              <option value="{{ $pocket->id }}" {{ old('digital_pocket_id') == $pocket->id ? 'selected' : '' }}>
                {{ $pocket->pocket_name }}{{ $pocket->currency ? ' (' . $pocket->currency->code . ')' : '' }}
              </option>
            @endforeach
          </select>
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="wallet" class="w-4 h-4"></i>
          </div>
          <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="chevron-down" class="w-4 h-4"></i>
          </div>
        </div>
      </x-form-field>

      {{-- مزود الخدمة (إنشاء: select، تعديل: read-only) --}}
      <div class="space-y-1.5" id="rr-provider-field">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="rr-provider-id">
          <i data-lucide="building-2" class="w-3.5 h-3.5"></i>
          مزود الخدمة <span class="text-rose-500" id="rr-provider-asterisk">*</span>
        </label>
        {{-- إنشاء: select --}}
        <div class="relative" id="rr-provider-select-wrap">
          <select
              name="provider_id"
              id="rr-provider-id"
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-[#10b981] transition-all text-right appearance-none cursor-pointer"
          >
            <option value="">اختر المزود</option>
            @foreach($providers as $prov)
              <option value="{{ $prov->id }}" {{ old('provider_id') == $prov->id ? 'selected' : '' }}>{{ $prov->name }}</option>
            @endforeach
          </select>
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="building-2" class="w-4 h-4"></i>
          </div>
          <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="chevron-down" class="w-4 h-4"></i>
          </div>
        </div>
        {{-- تعديل: read-only input (مخفي أولاً) --}}
        <div class="relative" id="rr-provider-readonly-wrap" style="display:none;">
          <input
              type="text"
              id="rr-provider-readonly"
              value=""
              readonly
              dir="rtl"
              class="w-full bg-slate-100/50 border border-slate-200 rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold text-slate-500 cursor-not-allowed outline-none text-right"
          >
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="building-2" class="w-4 h-4"></i>
          </div>
          <p class="text-[10px] text-slate-400 flex items-center gap-1 mt-1.5" id="rr-provider-hint" style="display:none;">
            <i data-lucide="info" class="w-3 h-3"></i>
            لا يمكن تغيير المزود بعد إنشاء الحساب
          </p>
        </div>
      </div>

      {{-- اسم صاحب الحساب --}}
      <x-form-field name="account_name" label="اسم صاحب الحساب" icon="user" required>
        <div class="relative group">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-emerald-500 transition-colors">
            <i data-lucide="user" class="w-4 h-4"></i>
          </div>
          <input
              type="text"
              name="account_name"
              id="rr-account-name"
              required
              maxlength="255"
              value="{{ old('account_name') }}"
              placeholder="أدخل اسم صاحب الحساب"
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-[#10b981] transition-all text-right"
          >
        </div>
      </x-form-field>

      {{-- رقم الحساب --}}
      <x-form-field name="account_number" label="رقم الحساب" icon="hash" required>
        <div class="relative group">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-emerald-500 transition-colors">
            <i data-lucide="hash" class="w-4 h-4"></i>
          </div>
          <input
              type="text"
              name="account_number"
              id="rr-account-number"
              required
              maxlength="50"
              value="{{ old('account_number') }}"
              placeholder="أدخل رقم الحساب"
              dir="ltr"
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold font-mono outline-none focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-[#10b981] transition-all text-left"
          >
        </div>
      </x-form-field>

      {{-- تأكيد رقم الحساب (مطلوب من FormRequest confirmed) --}}
      <x-form-field name="account_number_confirmation" label="تأكيد رقم الحساب" icon="shield-check" required>
        <div class="relative group">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-emerald-500 transition-colors">
            <i data-lucide="shield-check" class="w-4 h-4"></i>
          </div>
          <input
              type="text"
              name="account_number_confirmation"
              id="rr-account-number-confirmation"
              required
              maxlength="50"
              value="{{ old('account_number_confirmation') }}"
              placeholder="أعد إدخال رقم الحساب"
              dir="ltr"
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold font-mono outline-none focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-[#10b981] transition-all text-left"
          >
        </div>
        <p class="text-[10px] text-slate-400 leading-relaxed mt-1.5">
          يجب أن يتطابق مع رقم الحساب أعلاه
        </p>
      </x-form-field>

    </div>
  </div>

  {{-- القسم 2: معلومات إضافية (للتعديل فقط) --}}
  <div class="space-y-4" id="rr-extra-info" style="display:none;">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs text-slate-600 flex items-center gap-1.5">
        <i data-lucide="info" class="w-3.5 h-3.5"></i>
        معلومات إضافية
      </h4>
      <span class="text-[9px] font-bold text-slate-500 bg-slate-50 border border-slate-200 px-2 py-0.5 rounded-full">للقراءة فقط</span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div class="bg-slate-50/50 border border-slate-100 rounded-xl p-3 text-center">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-center gap-1">
          <i data-lucide="clock" class="w-3 h-3"></i>
          تاريخ الإنشاء
        </p>
        <p class="font-bold text-slate-700 text-xs mt-1.5 font-mono" dir="ltr" id="rr-info-created">—</p>
      </div>
      <div class="bg-slate-50/50 border border-slate-100 rounded-xl p-3 text-center">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-center gap-1">
          <i data-lucide="refresh-cw" class="w-3 h-3"></i>
          آخر تحديث
        </p>
        <p class="font-bold text-slate-700 text-xs mt-1.5 font-mono" dir="ltr" id="rr-info-updated">—</p>
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
      <span id="rr-cancel-text">إلغاء</span>
    </button>
    <button
        type="submit"
        class="flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95 bg-[#10b981] hover:bg-emerald-600"
        id="rr-submit-btn"
    >
      <i data-lucide="save" class="w-4 h-4"></i>
      <span id="rr-submit-text">حفظ</span>
    </button>
  </x-slot>
</x-form-modal>

@endsection

@push('scripts')
<script>
  // ===== Toggle account number visibility =====
  function toggleAccountNumber(id, btn, fullNumber) {
    var span = document.getElementById('acc-num-' + id);
    if (!span) return;
    var icon = btn.querySelector('[data-lucide]');
    var isHidden = span.dataset.visible !== 'true';

    if (isHidden) {
      span.innerHTML = '<i data-lucide="hash" class="w-3 h-3 text-slate-400"></i> ' + fullNumber;
      span.dataset.visible = 'true';
      if (icon) { icon.setAttribute('data-lucide', 'eye'); lucide.createIcons(); }
      btn.title = 'إخفاء رقم الحساب';
    } else {
      var last4 = fullNumber.length >= 4 ? fullNumber.slice(-4) : fullNumber;
      span.innerHTML = '<i data-lucide="hash" class="w-3 h-3 text-slate-400"></i> ****' + last4;
      span.dataset.visible = 'false';
      if (icon) { icon.setAttribute('data-lucide', 'eye-off'); lucide.createIcons(); }
      btn.title = 'إظهار رقم الحساب';
    }
  }

  // ===== Modal: dynamic action + title + variant switching =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'receive-revenue-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.id);

    if (isEdit) {
      // edit mode → PUT to update route
      var updateUrl = "{{ route('admin.receive-revenue.update', '__UUID__') }}".replace('__UUID__', data.id);
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
        '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل حساب بنكي';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }
      form.setAttribute('data-success-message', 'تم تحديث الحساب البنكي بنجاح');
      // subtitle
      var subtitleEl = modal.querySelector('.aracari-modal-panel .relative.z-10 p');
      if (subtitleEl) {
        subtitleEl.innerHTML = '<span class="inline-block text-xs text-white/80 bg-slate-900/40 border border-white/10 px-2 py-0.5 rounded-full max-w-[260px] truncate align-middle">#' + (data.id || '') + ' — ' + (data.account_name || '') + '</span>';
        subtitleEl.style.display = '';
      }
      // section badge
      var secBadge = modal.querySelector('#rr-section1-badge');
      if (secBadge) {
        secBadge.textContent = 'قابل للتعديل';
        secBadge.className = 'text-[9px] font-bold px-2 py-0.5 rounded-full border text-amber-600 bg-amber-50 border-amber-200';
      }
      var secTitle = modal.querySelector('#rr-section1-title');
      if (secTitle) secTitle.className = 'font-bold text-xs text-amber-600 flex items-center gap-1.5';

      // fill fields
      var pocketSelect = modal.querySelector('#rr-digital-pocket-id');
      if (pocketSelect) pocketSelect.value = data.digital_pocket_id || '';

      // provider: switch to read-only
      var providerSelectWrap = modal.querySelector('#rr-provider-select-wrap');
      var providerReadonlyWrap = modal.querySelector('#rr-provider-readonly-wrap');
      var providerHint = modal.querySelector('#rr-provider-hint');
      var providerAsterisk = modal.querySelector('#rr-provider-asterisk');
      if (providerSelectWrap) providerSelectWrap.style.display = 'none';
      if (providerReadonlyWrap) providerReadonlyWrap.style.display = '';
      if (providerHint) providerHint.style.display = '';
      if (providerAsterisk) providerAsterisk.style.display = 'none';
      var providerReadonly = modal.querySelector('#rr-provider-readonly');
      if (providerReadonly) providerReadonly.value = data.provider_name || '';
      // keep provider_id hidden input for update
      var hiddenProvider = form.querySelector('input[name="provider_id"][type="hidden"]');
      if (!hiddenProvider && data.provider_id) {
        var hp = document.createElement('input');
        hp.type = 'hidden'; hp.name = 'provider_id'; hp.value = data.provider_id;
        hp.setAttribute('data-dynamic', 'true');
        form.appendChild(hp);
      } else if (hiddenProvider) {
        hiddenProvider.value = data.provider_id || '';
      }

      var nameInput = modal.querySelector('#rr-account-name');
      if (nameInput) nameInput.value = data.account_name || '';
      // account number: leave empty for security (user must re-enter)
      var numberInput = modal.querySelector('#rr-account-number');
      if (numberInput) numberInput.value = '';
      var confirmInput = modal.querySelector('#rr-account-number-confirmation');
      if (confirmInput) confirmInput.value = '';

      // show extra info
      var extraInfo = modal.querySelector('#rr-extra-info');
      if (extraInfo) extraInfo.style.display = '';
      var infoCreated = modal.querySelector('#rr-info-created');
      if (infoCreated) infoCreated.textContent = data.created_at || '—';
      var infoUpdated = modal.querySelector('#rr-info-updated');
      if (infoUpdated) infoUpdated.textContent = data.updated_at || '—';

      // submit button style
      var submitBtn = modal.querySelector('#rr-submit-btn');
      if (submitBtn) submitBtn.className = 'flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95 bg-amber-600 hover:bg-amber-700';
      var submitText = modal.querySelector('#rr-submit-text');
      if (submitText) submitText.textContent = 'حفظ التعديلات';
      var cancelText = modal.querySelector('#rr-cancel-text');
      if (cancelText) cancelText.textContent = 'إلغاء';

    } else {
      // create mode → POST to store route
      form.setAttribute('action', "{{ route('admin.receive-revenue.store') }}");
      var m = form.querySelector('input[name="_method"]');
      if (m && m.getAttribute('data-dynamic')) m.remove();
      // banner
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-emerald-900 to-emerald-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة حساب بنكي';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-emerald-500/20 text-emerald-300 border-emerald-500/30';
        badge.textContent = 'جديد';
      }
      form.setAttribute('data-success-message', 'تم حفظ الحساب البنكي بنجاح');
      // subtitle
      var subtitleEl = modal.querySelector('.aracari-modal-panel .relative.z-10 p');
      if (subtitleEl) {
        subtitleEl.textContent = 'إنشاء حساب بنكي جديد لاستلام الإيرادات';
        subtitleEl.style.display = '';
      }
      // section badge
      var secBadge = modal.querySelector('#rr-section1-badge');
      if (secBadge) {
        secBadge.textContent = 'مطلوب';
        secBadge.className = 'text-[9px] font-bold px-2 py-0.5 rounded-full border text-rose-600 bg-rose-50 border-rose-200';
      }
      var secTitle = modal.querySelector('#rr-section1-title');
      if (secTitle) secTitle.className = 'font-bold text-xs text-emerald-600 flex items-center gap-1.5';

      // reset fields
      var pocketSelect = modal.querySelector('#rr-digital-pocket-id');
      if (pocketSelect) pocketSelect.value = '';
      var providerSelect = modal.querySelector('#rr-provider-id');
      if (providerSelect) providerSelect.value = '';

      // provider: show select, hide readonly
      var providerSelectWrap = modal.querySelector('#rr-provider-select-wrap');
      var providerReadonlyWrap = modal.querySelector('#rr-provider-readonly-wrap');
      var providerHint = modal.querySelector('#rr-provider-hint');
      var providerAsterisk = modal.querySelector('#rr-provider-asterisk');
      if (providerSelectWrap) providerSelectWrap.style.display = '';
      if (providerReadonlyWrap) providerReadonlyWrap.style.display = 'none';
      if (providerHint) providerHint.style.display = 'none';
      if (providerAsterisk) providerAsterisk.style.display = '';
      // remove hidden provider_id input
      var hiddenProvider = form.querySelector('input[name="provider_id"][type="hidden"][data-dynamic]');
      if (hiddenProvider) hiddenProvider.remove();

      var nameInput = modal.querySelector('#rr-account-name');
      if (nameInput) nameInput.value = '';
      var numberInput = modal.querySelector('#rr-account-number');
      if (numberInput) numberInput.value = '';
      var confirmInput = modal.querySelector('#rr-account-number-confirmation');
      if (confirmInput) confirmInput.value = '';

      // hide extra info
      var extraInfo = modal.querySelector('#rr-extra-info');
      if (extraInfo) extraInfo.style.display = 'none';

      // submit button style
      var submitBtn = modal.querySelector('#rr-submit-btn');
      if (submitBtn) submitBtn.className = 'flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95 bg-[#10b981] hover:bg-emerald-600';
      var submitText = modal.querySelector('#rr-submit-text');
      if (submitText) submitText.textContent = 'حفظ';
      var cancelText = modal.querySelector('#rr-cancel-text');
      if (cancelText) cancelText.textContent = 'إلغاء';
    }
    if (window.lucide) window.lucide.createIcons();
  });
</script>
@endpush
