@extends('layouts.admin')

@section('title', 'أركاري | تسويات المزودين')
@section('header_title', 'تسوية مستحقات المزودين')

@section('content')

@php
    $user    = auth()->user();
    $canSettle = $user && $user->hasPermission('providers:settle');

    if (!$canSettle) {
        abort(403);
    }

    // Compute stats from $providers array
    $totalProviders = count($providers);
    $totalPending   = 0;
    $currencyCount  = 0;
    $methodCount    = 0;
    foreach ($providers as $p) {
        $totalPending += $p['total_base'];
        $currencyCount += count($p['balances']);
        $methodCount  += $p['revenue_methods']?->count() ?? 0;
    }

    // Load currencies for the settle modal
    $currencies = \App\Models\Currency::orderBy('name')->get();

    // Flash messages
    $successMsg = session('success');
    $errorMsg   = session('error');

    // Helper to format amount
    $formatAmount = function($val) {
        return number_format((float) $val, 2);
    };
    $formatCompact = function($val) {
        $val = (float) $val;
        if ($val >= 1_000_000) {
            $formatted = $val / 1_000_000;
            return rtrim(rtrim(number_format($formatted, 1), '0'), '.') . 'M';
        }
        if ($val >= 1_000) {
            $formatted = $val / 1_000;
            return rtrim(rtrim(number_format($formatted, 1), '0'), '.') . 'K';
        }
        return number_format($val, 2);
    };

    // Count settlements this month
    $settlementsThisMonth = \App\Models\TransactionReference::where('reference_type', 'provider_settlement')
        ->whereMonth('created_at', now()->month)
        ->whereYear('created_at', now()->year)
        ->count();

    // Total settled this month
    $settledThisMonthAmount = \App\Models\Transaction::whereHas('reference', function($q) {
        $q->where('reference_type', 'provider_settlement')
          ->whereMonth('created_at', now()->month)
          ->whereYear('created_at', now()->year);
    })->where('transaction_type', 'debit')->sum('amount');
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="banknote"
      title="تسوية مستحقات المزودين"
      :count="$totalProviders"
      countLabel="مزود"
      description="عرض وتحويل المستحقات المالية لمزودي الخدمة"
      descriptionIcon="info"
  >
    <x-slot name="action">
      <a
          href="{{ route('admin.provider-settlements.history') }}"
          class="h-11 px-5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-bold text-sm shadow-lg shadow-slate-500/20 hover:shadow-slate-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2 flex-shrink-0"
      >
        <i data-lucide="history" class="w-4 h-4"></i>
        <span>سجل التسويات</span>
      </a>
    </x-slot>
  </x-page-header>

  {{-- ===== Flash Messages ===== --}}
  @if($successMsg)
    <div class="flex items-start gap-3 bg-emerald-50/80 backdrop-blur border border-emerald-200 text-emerald-800 px-5 py-4 rounded-2xl shadow-sm">
      <div class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-200 flex items-center justify-center flex-shrink-0">
        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
      </div>
      <div class="min-w-0">
        <p class="text-sm font-bold text-emerald-800">تمت العملية بنجاح</p>
        <p class="text-xs text-emerald-700 mt-0.5">{{ $successMsg }}</p>
      </div>
    </div>
  @endif
  @if($errorMsg)
    <div class="flex items-start gap-3 bg-rose-50/80 backdrop-blur border border-rose-200 text-rose-800 px-5 py-4 rounded-2xl shadow-sm">
      <div class="w-9 h-9 rounded-xl bg-rose-500/10 border border-rose-200 flex items-center justify-center flex-shrink-0">
        <i data-lucide="x-circle" class="w-5 h-5 text-rose-600"></i>
      </div>
      <div class="min-w-0">
        <p class="text-sm font-bold text-rose-800">تعذّرت العملية</p>
        <p class="text-xs text-rose-700 mt-0.5">{{ $errorMsg }}</p>
      </div>
    </div>
  @endif

  {{-- ===== 4 Stat Cards ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    <x-stat-card
        :value="$formatAmount($totalPending)"
        label="إجمالي المستحقات"
        icon="wallet"
        variant="emerald"
        badge="ريال"
        badgeIcon="banknote"
    />
    <x-stat-card
        :value="$totalProviders"
        label="مزودين بمستحقات"
        icon="landmark"
        variant="slate"
        badge="نشط"
        badgeIcon="users"
    />
    <x-stat-card
        :value="$settlementsThisMonth"
        label="تسويات هذا الشهر"
        icon="check-circle"
        variant="emerald"
        :badge="'تم تسوية ' . $formatAmount($settledThisMonthAmount) . ' ريال'"
        badgeIcon="trending-up"
    />
    <x-stat-card
        :value="$currencyCount"
        label="عملات مستخدمة"
        icon="refresh-cw"
        variant="blue"
        badge="{{ $methodCount }} طريقة استلام"
        badgeIcon="credit-card"
    />
  </div>

  {{-- ===== Provider Cards Grid ===== --}}
  @if(count($providers) > 0)
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      @foreach($providers as $provider)
        @php
          $initial = mb_substr($provider['name'], 0, 1);
          $maxBalance = collect($provider['balances'])->sortByDesc('amount')->first();
          $revenueMethods = $provider['revenue_methods'] ?? collect();
        @endphp
        <div class="group bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-2xl p-4 hover:border-emerald-500/40 hover:shadow-xl hover:shadow-emerald-500/10 hover:-translate-y-1 transition-all duration-300 relative overflow-hidden flex flex-col">

          {{-- Decorative orb --}}
          <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-500/5 rounded-full blur-2xl -translate-y-1/2 translate-x-1/2 pointer-events-none group-hover:scale-150 transition-transform duration-700"></div>

          {{-- Card header: avatar + name --}}
          <div class="relative z-10 flex items-start gap-2.5 mb-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-700 to-slate-900 flex items-center justify-center text-white font-black text-sm flex-shrink-0 shadow-md">
              {{ $initial }}
            </div>
            <div class="min-w-0 flex-1">
              <h3 class="font-black text-slate-800 text-sm leading-tight truncate">{{ $provider['name'] }}</h3>
              @if(!empty($provider['email']))
                <p class="text-[10px] text-slate-500 font-medium flex items-center gap-1 truncate" dir="ltr">
                  <i data-lucide="mail" class="w-2.5 h-2.5 text-slate-400 flex-shrink-0"></i>
                  <span class="truncate">{{ $provider['email'] }}</span>
                </p>
              @endif
            </div>
          </div>

          {{-- Balance box --}}
          <div class="relative z-10 bg-gradient-to-br from-slate-50/80 to-white/50 border border-slate-200/70 rounded-xl p-3 mb-3">
            <div class="flex items-center justify-between mb-1">
              <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                <i data-lucide="wallet" class="w-3 h-3 text-emerald-500"></i>
                إجمالي المستحقات
              </span>
              <span class="text-[9px] font-bold text-slate-400">
                {{ count($provider['balances']) }} عملة
              </span>
            </div>
            <div class="flex items-baseline gap-1">
              <span class="font-mono font-black text-emerald-700 text-lg leading-none" dir="ltr">
                {{ $formatAmount($provider['total_base']) }}
              </span>
              <span class="text-[10px] font-bold text-slate-400">ريال</span>
            </div>

            {{-- Currency chips --}}
            <div class="flex flex-wrap gap-1 mt-2">
              @foreach($provider['balances'] as $bal)
                @php
                  $isMax = $maxBalance && $bal['currency_id'] == $maxBalance['currency_id'];
                @endphp
                <span
                  class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[9px] font-bold border {{ $isMax ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-slate-50 border-slate-200 text-slate-500' }}"
                  title="{{ $formatAmount($bal['amount']) }} {{ $bal['currency_code'] }}"
                >
                  <i data-lucide="refresh-cw" class="w-2 h-2"></i>
                  <span class="font-mono" dir="ltr">{{ $bal['currency_code'] }}</span>
                  <span class="font-mono font-black" dir="ltr">{{ $formatCompact($bal['amount']) }}</span>
                </span>
              @endforeach
            </div>
          </div>

          {{-- Revenue methods info --}}
          <div class="relative z-10 flex items-center gap-2 mb-3 text-[10px]">
            @if($revenueMethods->count() > 0)
              <span class="inline-flex items-center gap-1 text-slate-500 bg-slate-50 border border-slate-200 px-2 py-1 rounded-lg font-bold">
                <i data-lucide="credit-card" class="w-2.5 h-2.5 text-slate-400"></i>
                {{ $revenueMethods->count() }} طرق استلام
              </span>
            @else
              <span class="inline-flex items-center gap-1 text-amber-600 bg-amber-50 border border-amber-200 px-2 py-1 rounded-lg font-bold">
                <i data-lucide="info" class="w-2.5 h-2.5"></i>
                لا توجد طرق استلام
              </span>
            @endif
          </div>

          {{-- Settle button --}}
          <button
              type="button"
              onclick="openSettleModal({{ $provider['id'] }}, '{{ addslashes($provider['name']) }}', {{ json_encode($provider['balances']) }}, {{ json_encode($revenueMethods) }})"
              class="relative z-10 mt-auto w-full h-10 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white rounded-xl font-bold text-xs shadow-md shadow-emerald-500/20 hover:shadow-emerald-500/40 hover:scale-[1.02] transition-all duration-300 flex items-center justify-center gap-1.5"
          >
            <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
            تسوية المستحقات
            <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>
          </button>
        </div>
      @endforeach
    </div>
  @else
    {{-- ===== Empty State ===== --}}
    <div class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-3xl p-12 text-center shadow-xl shadow-slate-200/20">
      <div class="w-20 h-20 rounded-full bg-emerald-50 mx-auto flex items-center justify-center text-emerald-400 mb-4">
        <i data-lucide="banknote" class="w-9 h-9"></i>
      </div>
      <h3 class="text-lg font-bold text-slate-700 mb-1">لا توجد مستحقات حالياً</h3>
      <p class="text-slate-400 text-sm">كل المزودين تمت تسويتهم بالكامل.</p>
    </div>
  @endif

</div>

{{-- ===== Settlement Modal ===== --}}
<x-modal
    id="settle-modal"
    title="تسوية مستحقات المزود"
    :subtitle="'تحويل المبلغ المستحق للمزود المحدد'"
    variant="create"
    icon="check-circle"
    maxWidth="max-w-lg"
>
  <form
      id="settle-form"
      method="POST"
      action=""
      class="space-y-5"
  >
    @csrf

    {{-- Provider name display --}}
    <div class="flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-xl p-3">
      <div id="settle-provider-avatar" class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-md"></div>
      <div class="min-w-0">
        <p class="text-[10px] text-slate-400 font-bold">المزود</p>
        <p id="settle-provider-name" class="font-black text-slate-800 text-sm truncate"></p>
      </div>
    </div>

    {{-- Currency select --}}
    <div>
      <label class="block text-[11px] font-bold text-slate-500 mb-1.5">
        <i data-lucide="refresh-cw" class="w-3 h-3 inline-block ml-1 text-emerald-500"></i>
        العملة <span class="text-rose-500">*</span>
      </label>
      <div class="relative group">
        <select
            name="currency_id"
            id="settle-currency"
            required
            onchange="onCurrencyChange()"
            class="w-full h-11 bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 pr-10 text-[13px] font-bold text-slate-700 focus:outline-none focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all appearance-none cursor-pointer"
        >
          <option value="">اختر العملة</option>
          @foreach($currencies as $currency)
            <option value="{{ $currency->id }}">{{ $currency->name }} ({{ $currency->code }})</option>
          @endforeach
        </select>
        <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none group-focus-within:text-emerald-500 transition-colors"></i>
      </div>
      <p id="settle-available-balance" class="text-[11px] text-slate-400 mt-1 font-bold hidden">
        الرصيد المتاح: <span id="settle-balance-value" class="text-emerald-600 font-mono" dir="ltr"></span>
      </p>
    </div>

    {{-- Amount input --}}
    <div>
      <label class="block text-[11px] font-bold text-slate-500 mb-1.5">
        <i data-lucide="banknote" class="w-3 h-3 inline-block ml-1 text-emerald-500"></i>
        مبلغ التسوية <span class="text-rose-500">*</span>
      </label>
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-emerald-500 transition-colors">
          <i data-lucide="banknote" class="w-4 h-4"></i>
        </div>
        <input
            type="number"
            name="amount"
            id="settle-amount"
            step="0.01"
            min="0.01"
            required
            placeholder="أدخل مبلغ التسوية"
            dir="ltr"
            class="w-full h-11 bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-4 text-[13px] font-bold text-left placeholder:text-slate-400 focus:outline-none focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all"
        >
      </div>
      @error('amount')
        <p class="text-[11px] text-rose-500 mt-1 font-bold">{{ $message }}</p>
      @enderror
    </div>

    {{-- Revenue method select --}}
    <div>
      <label class="block text-[11px] font-bold text-slate-500 mb-1.5">
        <i data-lucide="credit-card" class="w-3 h-3 inline-block ml-1 text-emerald-500"></i>
        طريقة الاستلام
        <span class="text-slate-400 font-normal">(اختياري)</span>
      </label>
      <div class="relative group">
        <select
            name="revenue_method_id"
            id="settle-revenue-method"
            class="w-full h-11 bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 pr-10 text-[13px] font-bold text-slate-700 focus:outline-none focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all appearance-none cursor-pointer"
        >
          <option value="">بدون طريقة محددة</option>
        </select>
        <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none group-focus-within:text-emerald-500 transition-colors"></i>
      </div>
    </div>

    {{-- Submit --}}
    <div class="flex items-center gap-3 pt-2">
      <button
          type="submit"
          id="settle-submit-btn"
          class="flex-1 h-12 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white rounded-xl font-bold text-sm shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/40 hover:scale-[1.02] transition-all duration-300 flex items-center justify-center gap-2"
      >
        <i data-lucide="check-circle" class="w-4 h-4"></i>
        تنفيذ التسوية
      </button>
      <button
          type="button"
          data-close-modal
          class="h-12 px-6 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl font-bold text-sm transition-all duration-300 flex items-center justify-center gap-2"
      >
        <i data-lucide="x" class="w-4 h-4"></i>
        إلغاء
      </button>
    </div>
  </form>
</x-modal>

@push('scripts')
<script>
  // Current provider data
  let currentProviderBalances = [];
  let currentProviderId = null;

  /**
   * Open the settle modal with provider data.
   */
  function openSettleModal(providerId, providerName, balances, revenueMethods) {
    currentProviderId = providerId;
    currentProviderBalances = balances || [];

    // Set provider info
    document.getElementById('settle-provider-name').textContent = providerName;
    document.getElementById('settle-provider-avatar').textContent = providerName.charAt(0);

    // Set form action
    document.getElementById('settle-form').action = '/admin/provider-settlements/' + providerId + '/settle';

    // Populate currency select with only currencies that have balance
    const currencySelect = document.getElementById('settle-currency');
    currencySelect.innerHTML = '<option value="">اختر العملة</option>';
    (balances || []).forEach(function(b) {
      const opt = document.createElement('option');
      opt.value = b.currency_id;
      opt.textContent = b.currency_code + ' — ' + parseFloat(b.amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      currencySelect.appendChild(opt);
    });

    // Populate revenue methods
    const methodSelect = document.getElementById('settle-revenue-method');
    methodSelect.innerHTML = '<option value="">بدون طريقة محددة</option>';
    (revenueMethods || []).forEach(function(m) {
      const opt = document.createElement('option');
      opt.value = m.id;
      opt.textContent = m.account_name + (m.account_number_last4 ? ' (...' + m.account_number_last4 + ')' : '');
      methodSelect.appendChild(opt);
    });

    // Reset amount and balance display
    document.getElementById('settle-amount').value = '';
    document.getElementById('settle-available-balance').classList.add('hidden');

    // Open modal
    if (typeof window.openAracariModal === 'function') {
      window.openAracariModal('settle-modal');
    } else {
      var modal = document.getElementById('settle-modal');
      if (modal) {
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(function() {
          var overlay = modal.querySelector('.aracari-modal-overlay');
          var panel   = modal.querySelector('.aracari-modal-panel');
          if (overlay) overlay.style.opacity = '1';
          if (panel)   { panel.style.opacity = '1'; panel.style.transform = 'scale(1) translateY(0)'; }
        });
      }
    }

    if (typeof window.lucide !== 'undefined') {
      window.lucide.createIcons();
    }
  }

  /**
   * Show available balance when currency changes.
   */
  function onCurrencyChange() {
    const currencyId = parseInt(document.getElementById('settle-currency').value);
    const balanceEl = document.getElementById('settle-available-balance');
    const balanceValueEl = document.getElementById('settle-balance-value');
    const amountInput = document.getElementById('settle-amount');

    const bal = currentProviderBalances.find(function(b) { return b.currency_id === currencyId; });
    if (bal) {
      balanceValueEl.textContent = parseFloat(bal.amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      balanceEl.classList.remove('hidden');
      amountInput.max = bal.amount;
    } else {
      balanceEl.classList.add('hidden');
      amountInput.removeAttribute('max');
    }
  }
</script>
@endpush

@endsection