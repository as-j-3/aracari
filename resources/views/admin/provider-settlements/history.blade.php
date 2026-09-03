@extends('layouts.admin')

@section('title', 'أركاري | سجل التسويات')
@section('header_title', 'سجل تسويات المزودين')

@section('content')

@php
    $user    = auth()->user();
    $canView = $user && $user->hasPermission('providers:settle');

    if (!$canView) {
        abort(403);
    }

    // Status → badge style mapping
    $statusStyles = [
        'confirmed' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600', 'border' => 'border-emerald-200', 'dot' => 'bg-emerald-500', 'icon' => 'check-circle', 'label' => 'مؤكدة'],
        'draft'     => ['bg' => 'bg-amber-50',   'text' => 'text-amber-600',   'border' => 'border-amber-200',   'dot' => 'bg-amber-500',   'icon' => 'clock',         'label' => 'مسودة'],
        'reversed'  => ['bg' => 'bg-rose-50',    'text' => 'text-rose-600',    'border' => 'border-rose-200',    'dot' => 'bg-rose-500',    'icon' => 'rotate-ccw',     'label' => 'ملغاة'],
        'cancelled' => ['bg' => 'bg-slate-100',   'text' => 'text-slate-600',   'border' => 'border-slate-200',   'dot' => 'bg-slate-400',   'icon' => 'x-circle',      'label' => 'ملغاة'],
    ];
    $defaultStatusStyle = ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'border' => 'border-slate-200', 'dot' => 'bg-slate-400', 'icon' => 'circle-dot', 'label' => 'غير معروفة'];

    $getStatusStyle = function($status) use ($statusStyles, $defaultStatusStyle) {
        return $statusStyles[$status] ?? $defaultStatusStyle;
    };

    // Stats
    $totalCount     = \App\Models\TransactionReference::where('reference_type', 'provider_settlement')->count();
    $confirmedCount = \App\Models\TransactionReference::where('reference_type', 'provider_settlement')->where('status', 'confirmed')->count();
    $draftCount     = \App\Models\TransactionReference::where('reference_type', 'provider_settlement')->where('status', 'draft')->count();
    $reversedCount  = \App\Models\TransactionReference::where('reference_type', 'provider_settlement')->where('status', 'reversed')->count();

    // Current filter
    $currentProviderId = request('provider_id');
    $hasActiveFilters  = (bool) $currentProviderId;

    // Helper to get settlement amount and currency from transactions
    $getSettlementAmount = function($settlement) {
        $debit = $settlement->transactions->where('transaction_type', 'debit')->first();
        return $debit ? [
            'amount'     => $debit->amount,
            'currency_id' => $debit->currency_id,
        ] : null;
    };

    // Load currency names for display
    $currencyNames = \App\Models\Currency::pluck('code', 'id')->toArray();
    $currencySymbols = \App\Models\Currency::pluck('symbol', 'id')->toArray();

    // Provider names for display
    $providerNames = \App\Models\User::where('type_user_code', \App\Constants\UserTypes::PROVIDER)
        ->pluck('name', 'id')->toArray();
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="history"
      title="سجل تسويات المزودين"
      :count="$totalCount"
      countLabel="تسوية"
      description="عرض جميع عمليات تسوية مستحقات المزودين"
      descriptionIcon="shield"
  >
    <x-slot name="action">
      <a
          href="{{ route('admin.provider-settlements.index') }}"
          class="h-11 px-5 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white rounded-xl font-bold text-sm shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/40 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2 flex-shrink-0"
      >
        <i data-lucide="banknote" class="w-4 h-4"></i>
        <span>تسوية مستحقات</span>
      </a>
    </x-slot>
  </x-page-header>

  {{-- ===== 4 Stat Cards ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    <x-stat-card
        :value="$totalCount"
        label="إجمالي التسويات"
        icon="activity"
        variant="slate"
        badge="في النظام"
        badgeIcon="trending-up"
    />
    <x-stat-card
        :value="$confirmedCount"
        label="تسويات مؤكدة"
        icon="check-circle"
        variant="emerald"
        badge="confirmed"
        badgeIcon="check-circle"
    />
    <x-stat-card
        :value="$draftCount"
        label="تسويات مسودة"
        icon="clock"
        variant="amber"
        badge="draft"
        badgeIcon="clock"
    />
    <x-stat-card
        :value="$reversedCount"
        label="تسويات ملغاة"
        icon="rotate-ccw"
        variant="rose"
        badge="reversed"
        badgeIcon="rotate-ccw"
    />
  </div>

  {{-- ===== Filter Card ===== --}}
  <div class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-3xl overflow-hidden shadow-xl shadow-slate-200/20">
    <form method="GET" action="{{ route('admin.provider-settlements.history') }}">
      <div class="p-5 border-b border-slate-200/70 bg-white/40 backdrop-blur">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <i data-lucide="filter" class="w-4 h-4 text-emerald-600"></i>
            <h3 class="text-sm font-black text-slate-700">تصفية السجلات</h3>
          </div>
          @if($hasActiveFilters)
            <a
                href="{{ route('admin.provider-settlements.history') }}"
                class="flex items-center gap-1.5 text-[11px] font-bold text-rose-500 hover:text-rose-600 transition-colors"
            >
              <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
              مسح الفلاتر
            </a>
          @endif
        </div>
      </div>
      <div class="p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {{-- المزود --}}
          <div>
            <label class="block text-[11px] font-bold text-slate-500 mb-1.5">
              <i data-lucide="landmark" class="w-3 h-3 inline-block ml-1 text-emerald-500"></i>
              المزود
            </label>
            <div class="relative group">
              <select
                  name="provider_id"
                  class="w-full h-11 bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 pr-10 text-[13px] font-bold text-slate-700 focus:outline-none focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all appearance-none cursor-pointer"
              >
                <option value="">جميع المزودين</option>
                @foreach($providers as $pid => $pname)
                  <option value="{{ $pid }}" @selected($currentProviderId == $pid)>{{ $pname }}</option>
                @endforeach
              </select>
              <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none group-focus-within:text-emerald-500 transition-colors"></i>
            </div>
          </div>
        </div>

        {{-- Submit button --}}
        <div class="mt-4 flex items-center gap-3">
          <button
              type="submit"
              class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
          >
            <i data-lucide="search" class="w-4 h-4"></i>
            بحث وتصفية
          </button>
          @if($hasActiveFilters)
            <a
                href="{{ route('admin.provider-settlements.history') }}"
                class="px-5 py-2.5 bg-rose-50 border border-rose-200 text-rose-600 rounded-xl font-bold text-sm hover:bg-rose-500 hover:text-white hover:border-rose-500 transition-all duration-300 flex items-center gap-2"
            >
              <i data-lucide="refresh-cw" class="w-4 h-4"></i>
              إعادة تعيين
            </a>
          @endif
        </div>
      </div>
    </form>
  </div>

  {{-- ===== Data Table Card ===== --}}
  <div id="aracari-refreshable">
  <x-data-table-card
      :searchable="false"
      :footerInfo="'عرض ' . $settlements->count() . ' من أصل ' . $settlements->total() . ' سجل'"
  >
    <x-slot name="thead">
      <tr>
        <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
        <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">رقم المرجع</th>
        <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المزود</th>
        <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">المبلغ</th>
        <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
        <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">بواسطة</th>
        <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">التاريخ</th>
      </tr>
    </x-slot>

    <x-slot name="rows">
      @forelse($settlements as $index => $settlement)
        @php
            $style      = $getStatusStyle($settlement->status);
            $amountInfo = $getSettlementAmount($settlement);
            $providerName = $providerNames[$settlement->reference_id] ?? ('مزود #' . $settlement->reference_id);
            $creatorName  = $settlement->creator?->name ?? 'نظام';
            $initial     = mb_substr($providerName, 0, 1);
            $currencyCode = $amountInfo ? ($currencyNames[$amountInfo['currency_id']] ?? '') : '';
            $currencySymbol = $amountInfo ? ($currencySymbols[$amountInfo['currency_id']] ?? '') : '';
        @endphp
        <tr class="hover:bg-slate-50/50 transition-colors group">

          {{-- # --}}
          <td class="px-5 py-3.5 text-center">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-900 text-white text-[11px] font-mono font-black shadow-sm">
              {{ $settlements->firstItem() + $index }}
            </span>
          </td>

          {{-- Reference number --}}
          <td class="px-5 py-3.5">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-50 text-slate-700 text-[11px] font-mono font-bold border border-slate-200">
              <i data-lucide="hash" class="w-3 h-3 text-emerald-500"></i>
              <span dir="ltr">{{ $settlement->reference_number }}</span>
            </span>
          </td>

          {{-- Provider name --}}
          <td class="px-5 py-3.5">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-sm shadow-emerald-500/20 group-hover:scale-110 transition-transform duration-300">
                {{ $initial }}
              </div>
              <div class="min-w-0">
                <p class="font-bold text-slate-800 text-sm truncate">{{ $providerName }}</p>
                <p class="text-[11px] text-slate-400 font-mono" dir="ltr">ID: {{ $settlement->reference_id }}</p>
              </div>
            </div>
          </td>

          {{-- Amount --}}
          <td class="px-5 py-3.5 text-center">
            @if($amountInfo)
              <div class="inline-flex flex-col items-center">
                <span class="font-mono font-black text-emerald-700 text-sm" dir="ltr">
                  {{ number_format((float) $amountInfo['amount'], 2) }}
                </span>
                @if($currencyCode)
                  <span class="text-[9px] font-bold text-slate-400 mt-0.5">{{ $currencyCode }}</span>
                @endif
              </div>
            @else
              <span class="text-slate-400 text-xs">—</span>
            @endif
          </td>

          {{-- Status --}}
          <td class="px-5 py-3.5 text-center">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $style['bg'] }} {{ $style['text'] }} {{ $style['border'] }}">
              <span class="w-1.5 h-1.5 rounded-full {{ $style['dot'] }}"></span>
              <i data-lucide="{{ $style['icon'] }}" class="w-3 h-3"></i>
              {{ $style['label'] }}
            </span>
          </td>

          {{-- Creator --}}
          <td class="px-5 py-3.5">
            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700">
              <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0"></i>
              {{ $creatorName }}
            </div>
          </td>

          {{-- Date --}}
          <td class="px-5 py-3.5">
            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700">
              <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0"></i>
              <span dir="ltr">{{ $settlement->created_at?->format('Y-m-d H:i') }}</span>
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state
            icon="history"
            :title="$hasActiveFilters ? 'لا توجد نتائج مطابقة' : 'لا توجد تسويات بعد'"
            :message="$hasActiveFilters ? 'جرّب تعديل خيارات التصفية' : 'ستظهر عمليات التسوية هنا فور تنفيذ أول تسوية'"
            :colspan="7"
        />
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$settlements" />
    </x-slot>
  </x-data-table-card>
  </div>{{-- end #aracari-refreshable --}}

</div>

@endsection