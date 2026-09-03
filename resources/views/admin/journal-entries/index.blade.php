@extends('layouts.admin')

@section('title', 'أركاري | القيود المحاسبية')
@section('header_title', 'القيود المحاسبية')

@section('content')

@php
    // ===== Permissions =====
    $user       = auth()->user();
    $canView    = $user && $user->hasPermission('journal:view');
    $canCreate  = $user && $user->hasPermission('journal:create');
    $canEdit    = $user && $user->hasPermission('journal:edit');
    $canDelete  = $user && $user->hasPermission('journal:delete');

    if (!$canView) {
        abort(403);
    }

    // ===== Style Mappings =====
    $statusStyles = [
        'draft'     => ['bg' => 'bg-amber-50',  'text' => 'text-amber-700',  'border' => 'border-amber-200',  'dot' => 'bg-amber-500',  'icon' => 'pencil',     'label' => 'مسودة'],
        'confirmed' => ['bg' => 'bg-emerald-50','text' => 'text-emerald-700','border' => 'border-emerald-200','dot' => 'bg-emerald-500','icon' => 'check-circle','label' => 'معتمد'],
        'reversed'  => ['bg' => 'bg-purple-50', 'text' => 'text-purple-700', 'border' => 'border-purple-200', 'dot' => 'bg-purple-500', 'icon' => 'undo',       'label' => 'معكوس'],
        'cancelled' => ['bg' => 'bg-rose-50',   'text' => 'text-rose-700',   'border' => 'border-rose-200',   'dot' => 'bg-rose-500',   'icon' => 'x-circle',    'label' => 'ملغي'],
    ];
    $defaultStatusStyle = ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'border' => 'border-slate-200', 'dot' => 'bg-slate-400', 'icon' => 'help-circle', 'label' => 'غير معروف'];

    $getStatusStyle = function($status) use ($statusStyles, $defaultStatusStyle) {
        return $statusStyles[$status] ?? $defaultStatusStyle;
    };

    // Type → badge mapping
    $typeStyles = [
        'booking'                 => ['label' => 'حجز',       'bg' => 'bg-sky-50',    'text' => 'text-sky-700',    'border' => 'border-sky-200'],
        'refund'                  => ['label' => 'استرداد',   'bg' => 'bg-rose-50',   'text' => 'text-rose-700',   'border' => 'border-rose-200'],
        'booking_modification'    => ['label' => 'تعديل حجز','bg' => 'bg-amber-50',  'text' => 'text-amber-700',  'border' => 'border-amber-200'],
        'emergency_cancellation'  => ['label' => 'إلغاء طارئ','bg' => 'bg-orange-50', 'text' => 'text-orange-700', 'border' => 'border-orange-200'],
        'reversal'                => ['label' => 'عكس قيد',  'bg' => 'bg-purple-50', 'text' => 'text-purple-700', 'border' => 'border-purple-200'],
        'manual_journal_entry'    => ['label' => 'يدوي',     'bg' => 'bg-slate-100', 'text' => 'text-slate-700',  'border' => 'border-slate-200'],
    ];
    $defaultTypeStyle = ['label' => 'أخرى', 'bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'border' => 'border-slate-200'];

    $getTypeStyle = function($refType) use ($typeStyles, $defaultTypeStyle) {
        return $typeStyles[$refType] ?? $defaultTypeStyle;
    };

    // ===== Stats =====
    $totalCount     = \App\Models\TransactionReference::count();
    $draftCount     = \App\Models\TransactionReference::where('status', 'draft')->count();
    $confirmedCount = \App\Models\TransactionReference::where('status', 'confirmed')->count();
    $reversedCount  = \App\Models\TransactionReference::where('status', 'reversed')->count();

    $statusTabCounts = [
        'all'       => $totalCount,
        'draft'     => $draftCount,
        'confirmed' => $confirmedCount,
        'reversed'  => $reversedCount,
        'cancelled' => \App\Models\TransactionReference::where('status', 'cancelled')->count(),
    ];

    $currentStatus = request('status');
    $hasActiveFilters = $currentStatus || request('search');
    $baseUrl = route('admin.accounting.journal-entries.index');

    // Helpers
    $getDebitTotal = function($entry) {
        return (float) $entry->transactions->where('transaction_type', 'debit')->sum('amount');
    };
    $getCreditTotal = function($entry) {
        return (float) $entry->transactions->where('transaction_type', 'credit')->sum('amount');
    };
    $isBalanced = function($entry) use ($getDebitTotal, $getCreditTotal) {
        return abs($getDebitTotal($entry) - $getCreditTotal($entry)) < 0.01;
    };
    $getEntryDescription = function($entry) {
        if (!empty($entry->description)) {
            return $entry->description;
        }
        return $entry->transactions->first()?->description ?? '—';
    };
    $getTransactionCount = function($entry) {
        return $entry->transactions->count();
    };
    $formatAmount = function($value) {
        return number_format($value, 2);
    };

    // Build edit data for each entry (used in data-edit-json)
    $buildEditData = function($entry) {
        $lines = [];
        foreach ($entry->transactions as $tx) {
            $lines[] = [
                'account_code' => $tx->account_code ?? '',
                'debit'        => $tx->transaction_type === 'debit' ? $tx->amount : '',
                'credit'       => $tx->transaction_type === 'credit' ? $tx->amount : '',
                'description'  => $tx->description ?? '',
            ];
        }
        return [
            'reference_number' => $entry->reference_number,
            'date'             => $entry->created_at?->format('Y-m-d') ?? date('Y-m-d'),
            'description'      => $entry->transactions->first()?->description ?? '',
            'lines'            => $lines,
        ];
    };
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="receipt-text"
      title="القيود المحاسبية"
      :count="$totalCount"
      countLabel="قيد"
      description="إدارة القيود المحاسبية وتتبع المعاملات المالية"
      descriptionIcon="scale"
  >
    @if($canCreate)
      <button
          type="button"
          data-create-modal="journal-entry-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        <span class="hidden sm:inline">إنشاء قيد جديد</span>
        <span class="sm:hidden">إنشاء</span>
      </button>
    @endif
  </x-page-header>

  {{-- ===== Status Filter Tabs ===== --}}
  <div class="bg-white/60 backdrop-blur-xl border border-slate-200/70 rounded-3xl p-3 flex flex-wrap gap-2">
    @php
        $tabs = [
            ['id' => '',           'label' => 'الكل',   'icon' => 'list-filter', 'colorClasses' => 'from-slate-700 to-slate-800 shadow-slate-700/30'],
            ['id' => 'draft',      'label' => 'مسودة',  'icon' => 'pencil',      'colorClasses' => 'from-amber-500 to-amber-600 shadow-amber-500/30'],
            ['id' => 'confirmed',  'label' => 'معتمد',  'icon' => 'check-circle', 'colorClasses' => 'from-emerald-500 to-emerald-600 shadow-emerald-500/30'],
            ['id' => 'reversed',   'label' => 'معكوس', 'icon' => 'undo',        'colorClasses' => 'from-purple-500 to-purple-600 shadow-purple-500/30'],
            ['id' => 'cancelled',  'label' => 'ملغي',   'icon' => 'x-circle',    'colorClasses' => 'from-rose-500 to-rose-600 shadow-rose-500/30'],
        ];
    @endphp
    @foreach($tabs as $tab)
      @php
          $isActive = $currentStatus === $tab['id'];
          $tabUrl = $tab['id']
              ? $baseUrl . '?' . http_build_query(array_filter(['search' => request('search'), 'status' => $tab['id']]))
              : ($baseUrl . (request('search') ? '?search=' . urlencode(request('search')) : ''));
          $tabCount = $statusTabCounts[$tab['id']] ?? 0;
      @endphp
      <a
          href="{{ $tabUrl }}"
          class="px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 transition-all duration-300 flex-1 sm:flex-initial justify-center {{ $isActive ? 'bg-gradient-to-r ' . $tab['colorClasses'] . ' text-white shadow-lg' : 'bg-white/70 border border-slate-200 text-slate-600 hover:bg-white hover:border-slate-300' }}"
      >
        <i data-lucide="{{ $tab['icon'] }}" class="w-3.5 h-3.5"></i>
        <span>{{ $tab['label'] }}</span>
        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full {{ $isActive ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">
          {{ $tabCount }}
        </span>
      </a>
    @endforeach
  </div>

  {{-- ===== 4 Stat Cards ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    <x-stat-card
        :value="$totalCount"
        label="إجمالي القيود"
        icon="receipt-text"
        variant="slate"
        badge="{{ $totalCount > 0 ? 'مُسجّل' : 'فارغ' }}"
        badgeIcon="trending-up"
    />
    <x-stat-card
        :value="$draftCount"
        label="مسودات"
        icon="pencil"
        variant="amber"
        badge="مسودة"
        badgeIcon="pencil"
    />
    <x-stat-card
        :value="$confirmedCount"
        label="معتمدة"
        icon="check-circle"
        variant="emerald"
        badge="معتمدة"
        badgeIcon="check-circle"
    />
    <x-stat-card
        :value="$reversedCount"
        label="معكوسة"
        icon="undo"
        variant="blue"
        badge="معكوسة"
        badgeIcon="undo"
    />
  </div>

  {{-- ===== Data Table (refreshable) ===== --}}
  <div id="aracari-refreshable">
  <x-data-table-card
      :searchable="true"
      :searchMode="'server'"
      searchPlaceholder="ابحث برقم المرجع أو الوصف..."
      :footerInfo="'عرض ' . $journalEntries->count() . ' من أصل ' . $journalEntries->total() . ' قيد'"
  >
    <x-slot name="thead">
      <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
      <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">رقم المرجع</th>
      <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">التاريخ</th>
      <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">النوع</th>
      <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الوصف</th>
      <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الأسطر</th>
      <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">المبلغ</th>
      <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">التوازن</th>
      <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
      <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
    </x-slot>

    <x-slot name="rows">
      @forelse($journalEntries as $index => $entry)
        @php
            $sStyle     = $getStatusStyle($entry->status);
            $tStyle     = $getTypeStyle($entry->reference_type);
            $debit      = $getDebitTotal($entry);
            $credit     = $getCreditTotal($entry);
            $balanced   = $isBalanced($entry);
            $lineCount  = $getTransactionCount($entry);
            $desc       = $getEntryDescription($entry);
            $rowNumber  = $journalEntries->firstItem() + $index;
        @endphp
        <tr class="hover:bg-slate-50/50 transition-colors group">
          {{-- # --}}
          <td class="px-5 py-3.5">
            <span class="text-xs font-bold text-slate-400">{{ $rowNumber }}</span>
          </td>

          {{-- رقم المرجع --}}
          <td data-searchable class="px-5 py-3.5">
            <div class="flex items-center gap-2">
              <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500/20 to-sky-600/10 flex items-center justify-center border border-sky-100 group-hover:scale-110 transition-transform duration-300 flex-shrink-0">
                <i data-lucide="hash" class="w-4 h-4 text-sky-600"></i>
              </div>
              <span class="font-mono font-black text-slate-600 text-sm" dir="ltr">
                {{ $entry->reference_number }}
              </span>
            </div>
          </td>

          {{-- التاريخ --}}
          <td class="px-5 py-3.5">
            <div class="flex flex-col">
              <span class="font-mono font-bold text-slate-700 text-sm" dir="ltr">
                {{ $entry->created_at?->format('Y/m/d') }}
              </span>
              <span class="font-mono text-[10px] text-slate-400" dir="ltr">
                {{ $entry->created_at?->format('H:i') }}
              </span>
            </div>
          </td>

          {{-- النوع --}}
          <td class="px-5 py-3.5 text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full {{ $tStyle['bg'] }} {{ $tStyle['text'] }} text-[10px] font-bold border {{ $tStyle['border'] }}">
              {{ $tStyle['label'] }}
            </span>
          </td>

          {{-- الوصف --}}
          <td data-searchable class="px-5 py-3.5">
            <div class="max-w-[180px]">
              <p class="text-xs font-bold text-slate-700 leading-relaxed line-clamp-2">
                {{ $desc }}
              </p>
            </div>
          </td>

          {{-- الأسطر --}}
          <td class="px-5 py-3.5 text-center">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-[10px] font-bold border border-slate-200">
              <i data-lucide="building-2" class="w-3 h-3"></i>
              {{ $lineCount }}
            </span>
          </td>

          {{-- المبلغ --}}
          <td class="px-5 py-3.5 text-center">
            <div class="inline-flex flex-col items-center gap-0.5">
              <span class="font-mono font-bold text-emerald-700 text-sm" dir="ltr">
                {{ $formatAmount($debit) }}
              </span>
              <span class="text-[9px] font-bold text-slate-400" dir="ltr">
                م {{ $formatAmount($credit) }} د
              </span>
            </div>
          </td>

          {{-- التوازن --}}
          <td class="px-5 py-3.5 text-center">
            @if($balanced)
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">
                <i data-lucide="check-circle" class="w-3 h-3"></i>
                متوازن
              </span>
            @else
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 text-[10px] font-bold border border-rose-200">
                <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                غير متوازن
              </span>
            @endif
          </td>

          {{-- الحالة --}}
          <td class="px-5 py-3.5 text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full {{ $sStyle['bg'] }} {{ $sStyle['text'] }} text-[10px] font-bold border {{ $sStyle['border'] }}">
              <span class="relative flex h-2 w-2">
                @if($entry->status === 'confirmed')
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $sStyle['dot'] }} opacity-75"></span>
                @endif
                <span class="relative inline-flex rounded-full h-2 w-2 {{ $sStyle['dot'] }}"></span>
              </span>
              {{ $sStyle['label'] }}
            </span>
          </td>

          {{-- الإجراءات --}}
          <td class="px-5 py-3.5 text-center">
            <div class="flex items-center justify-center gap-1.5">
              @if($entry->status === 'draft')
                {{-- اعتماد --}}
                @if($canEdit)
                  <form
                      method="POST"
                      action="{{ route('admin.accounting.journal-entries.confirm', $entry->reference_number) }}"
                      onsubmit="return confirm('هل أنت متأكد من اعتماد هذا القيد؟ سيتم ترصيد الحسابات فوراً.')"
                      class="inline-flex"
                  >
                    @csrf
                    <button
                        type="submit"
                        class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200 transition-all duration-300 shadow-sm flex items-center justify-center"
                        title="اعتماد القيد"
                    >
                      <i data-lucide="check" class="w-4 h-4"></i>
                    </button>
                  </form>
                @endif

                {{-- تعديل (مودال) --}}
                @if($canEdit)
                  <x-action-button
                      variant="edit"
                      data-edit-modal="journal-entry-form-modal"
                      data-edit-json="{{ json_encode($buildEditData($entry), JSON_UNESCAPED_UNICODE) }}"
                      :ariaLabel="'تعديل ' . $entry->reference_number"
                  />
                @endif

                {{-- حذف --}}
                @if($canDelete)
                  <x-action-button
                      variant="delete"
                      :data-delete-url="route('admin.accounting.journal-entries.destroy', $entry->reference_number)"
                      :data-delete-label="'القيد ' . $entry->reference_number"
                  />
                @endif
              @elseif($entry->status === 'confirmed')
                {{-- عكس القيد --}}
                @if($canEdit)
                  <form
                      method="POST"
                      action="{{ route('admin.accounting.journal-entries.reverse', $entry->reference_number) }}"
                      onsubmit="return confirm('هل أنت متأكد من عكس هذا القيد المعتمد؟ سيتم إنشاء قيد عكسي تلقائياً.')"
                      class="inline-flex"
                  >
                    @csrf
                    <button
                        type="submit"
                        class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-purple-50 hover:text-purple-600 hover:border-purple-200 transition-all duration-300 shadow-sm flex items-center justify-center"
                        title="عكس القيد"
                    >
                      <i data-lucide="undo" class="w-4 h-4"></i>
                    </button>
                  </form>
                @endif

                <span
                    class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200 text-slate-300 cursor-not-allowed flex items-center justify-center"
                    title="القيد مقفل"
                >
                  <i data-lucide="lock" class="w-4 h-4"></i>
                </span>
              @else
                <span class="text-slate-300 text-sm font-bold">—</span>
              @endif
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state
            icon="receipt-text"
            :title="$hasActiveFilters ? 'لا توجد نتائج مطابقة' : 'لا توجد قيود محاسبية'"
            :message="$hasActiveFilters ? 'جرّب تعديل كلمات البحث أو فلتر الحالة' : 'القيود المحاسبية ستظهر هنا تلقائياً عند إجراء الحجوزات وعمليات الإلغاء'"
            :colspan="10"
        >
          @if(!$hasActiveFilters && $canCreate)
            <button
                type="button"
                data-create-modal="journal-entry-form-modal"
                class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
            >
              <i data-lucide="plus" class="w-4 h-4"></i>
              إنشاء قيد جديد
            </button>
          @endif
          @if($hasActiveFilters)
            <a
                href="{{ $baseUrl }}"
                class="mt-3 text-sky-600 hover:text-sky-700 inline-flex items-center gap-1 font-bold text-sm"
            >
              <i data-lucide="refresh-cw" class="w-4 h-4"></i>
              إعادة تعيين الفلاتر
            </a>
          @endif
        </x-empty-state>
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$journalEntries" />
    </x-slot>
  </x-data-table-card>
  </div>{{-- end #aracari-refreshable -->}}

</div>

{{-- ===== Journal Entry Form Modal (Create + Edit) ===== --}}
<x-form-modal
    id="journal-entry-form-modal"
    title="إنشاء قيد محاسبي جديد"
    :variant="'create'"
    :action="route('admin.accounting.journal-entries.store')"
    method="POST"
    successMessage="تم حفظ القيد المحاسبي بنجاح"
    maxWidth="max-w-4xl"
>

  {{-- التاريخ والوصف --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <x-form-field name="date" label="التاريخ" icon="calendar" required>
      <input
          type="date"
          name="date"
          id="je-date"
          required
          value="{{ date('Y-m-d') }}"
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
      >
    </x-form-field>

    <x-form-field name="description" label="الوصف" icon="file-text" required>
      <input
          type="text"
          name="description"
          id="je-description"
          required
          placeholder="وصف القيد المحاسبي..."
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
      >
    </x-form-field>
  </div>

  {{-- أسطر القيد --}}
  <div class="space-y-3">
    <div class="flex items-center justify-between">
      <h4 class="font-bold text-xs text-[#115128] border-b border-[#eeeef0] pb-2 flex items-center gap-1.5">
        <i data-lucide="list" class="w-3.5 h-3.5"></i>
        أسطر القيد
      </h4>
      <button
          type="button"
          id="je-add-line-btn"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold text-teal-700 bg-teal-50 border border-teal-200 rounded-lg hover:bg-teal-100 transition-all"
      >
        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
        إضافة سطر
      </button>
    </div>

    {{-- جدول الأسطر --}}
    <div class="border border-[#e2e2e5] rounded-xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-right" id="je-lines-table">
          <thead>
            <tr class="bg-[#f3f3f6]">
              <th class="px-3 py-2.5 text-[10px] font-bold text-slate-500 uppercase w-10 text-center">#</th>
              <th class="px-3 py-2.5 text-[10px] font-bold text-slate-500 uppercase min-w-[200px]">الحساب</th>
              <th class="px-3 py-2.5 text-[10px] font-bold text-slate-500 uppercase w-32 text-center">مدين</th>
              <th class="px-3 py-2.5 text-[10px] font-bold text-slate-500 uppercase w-32 text-center">دائن</th>
              <th class="px-3 py-2.5 text-[10px] font-bold text-slate-500 uppercase min-w-[150px]">الوصف</th>
              <th class="px-3 py-2.5 text-[10px] font-bold text-slate-500 uppercase w-10 text-center"></th>
            </tr>
          </thead>
          <tbody id="je-lines-body">
            {{-- الأسطر ستُضاف ديناميكياً بالجافاسكربت --}}
          </tbody>
        </table>
      </div>
    </div>

    {{-- ملخص التوازن --}}
    <div class="flex items-center justify-between bg-[#f9f9fc] border border-[#eeeef0] rounded-xl p-3">
      <div class="flex items-center gap-4">
        <div class="text-center">
          <div class="text-[9px] font-bold text-slate-400 mb-0.5">إجمالي المدين</div>
          <div class="font-mono font-bold text-emerald-700 text-sm" id="je-total-debit" dir="ltr">0.00</div>
        </div>
        <div class="text-center">
          <div class="text-[9px] font-bold text-slate-400 mb-0.5">إجمالي الدائن</div>
          <div class="font-mono font-bold text-blue-700 text-sm" id="je-total-credit" dir="ltr">0.00</div>
        </div>
      </div>
      <div class="flex items-center gap-2" id="je-balance-indicator">
        <div class="text-center">
          <div class="text-[9px] font-bold text-slate-400 mb-0.5">الفرق</div>
          <div class="font-mono font-bold text-sm" id="je-difference" dir="ltr">0.00</div>
        </div>
        <span id="je-balance-badge" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">
          <i data-lucide="check-circle" class="w-3 h-3"></i>
          متوازن
        </span>
      </div>
    </div>
  </div>

  {{-- تنبيه --}}
  <div class="bg-sky-50/70 border border-sky-200 rounded-xl p-3">
    <div class="flex items-start gap-2.5">
      <i data-lucide="info" class="w-4 h-4 text-sky-500 flex-shrink-0 mt-0.5"></i>
      <span class="text-[11px] text-sky-600">
        يجب أن يكون إجمالي المدين مساوياً لإجمالي الدائن لكي يكون القيد متوازناً. القيد غير المتوازن لن يُقبل.
      </span>
    </div>
  </div>

  {{-- Slot: hidden line template (used by JS to clone) --}}
  <template id="je-line-template">
    <tr class="je-line-row border-b border-[#eeeef0] last:border-b-0 hover:bg-slate-50/50 transition-colors">
      <td class="px-3 py-2 text-center">
        <span class="je-line-number text-[10px] font-bold text-slate-400">1</span>
      </td>
      <td class="px-3 py-2">
        <select
            name="lines[__IDX__][account_code]"
            class="je-line-account w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-lg px-2 py-2 text-[12px] font-bold outline-none focus:bg-white focus:ring-2 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
            required
        >
          <option value="">اختر الحساب...</option>
          @foreach($accounts as $account)
            <option value="{{ $account->account_code }}">{{ str_repeat('── ', $account->level - 1) }}{{ $account->account_code }} - {{ $account->account_name }}</option>
          @endforeach
        </select>
      </td>
      <td class="px-3 py-2 text-center">
        <input
            type="number"
            name="lines[__IDX__][debit]"
            class="je-line-debit w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-lg px-2 py-2 text-[12px] font-bold text-center outline-none focus:bg-white focus:ring-2 focus:ring-teal-500/10 focus:border-[#115128] transition-all"
            step="0.01"
            min="0"
            placeholder="0.00"
            dir="ltr"
        >
      </td>
      <td class="px-3 py-2 text-center">
        <input
            type="number"
            name="lines[__IDX__][credit]"
            class="je-line-credit w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-lg px-2 py-2 text-[12px] font-bold text-center outline-none focus:bg-white focus:ring-2 focus:ring-teal-500/10 focus:border-[#115128] transition-all"
            step="0.01"
            min="0"
            placeholder="0.00"
            dir="ltr"
        >
      </td>
      <td class="px-3 py-2">
        <input
            type="text"
            name="lines[__IDX__][description]"
            class="je-line-desc w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-lg px-2 py-2 text-[12px] font-bold outline-none focus:bg-white focus:ring-2 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
            placeholder="وصف السطر..."
        >
      </td>
      <td class="px-3 py-2 text-center">
        <button
            type="button"
            class="je-remove-line-btn w-7 h-7 rounded-lg bg-rose-50 border border-rose-200 text-rose-500 hover:bg-rose-100 hover:text-rose-600 transition-all flex items-center justify-center"
            title="حذف السطر"
        >
          <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
        </button>
      </td>
    </tr>
  </template>

</x-form-modal>

@endsection

@push('scripts')
<script>
(function() {
  'use strict';

  var modal = document.getElementById('journal-entry-form-modal');
  if (!modal) return;

  var form = modal.querySelector('form');
  var linesBody = document.getElementById('je-lines-body');
  var template = document.getElementById('je-line-template');
  var addLineBtn = document.getElementById('je-add-line-btn');
  var totalDebitEl = document.getElementById('je-total-debit');
  var totalCreditEl = document.getElementById('je-total-credit');
  var differenceEl = document.getElementById('je-difference');
  var balanceBadge = document.getElementById('je-balance-badge');

  var lineCounter = 0;
  var isEditMode = false;

  // ===== Add a new empty line =====
  function addLine(data) {
    var idx = lineCounter++;
    var row = template.content.querySelector('tr').cloneNode(true);

    // Replace __IDX__ with actual index
    row.innerHTML = row.innerHTML.replace(/__IDX__/g, idx);

    // Update line number
    updateLineNumbers();

    // Fill data if provided (for edit mode)
    if (data) {
      var select = row.querySelector('.je-line-account');
      if (data.account_code && select) select.value = data.account_code;
      var debitInput = row.querySelector('.je-line-debit');
      if (data.debit !== undefined && data.debit !== '' && debitInput) debitInput.value = data.debit;
      var creditInput = row.querySelector('.je-line-credit');
      if (data.credit !== undefined && data.credit !== '' && creditInput) creditInput.value = data.credit;
      var descInput = row.querySelector('.je-line-desc');
      if (data.description && descInput) descInput.value = data.description;
    }

    linesBody.appendChild(row);
    bindLineEvents(row);
    updateBalance();

    if (window.lucide) window.lucide.createIcons();
    return row;
  }

  // ===== Remove a line =====
  function removeLine(btn) {
    var row = btn.closest('.je-line-row');
    if (row) {
      row.remove();
      updateLineNumbers();
      updateBalance();
    }
  }

  // ===== Update line numbers =====
  function updateLineNumbers() {
    var rows = linesBody.querySelectorAll('.je-line-row');
    rows.forEach(function(row, i) {
      var num = row.querySelector('.je-line-number');
      if (num) num.textContent = i + 1;
    });
  }

  // ===== Calculate and update balance =====
  function updateBalance() {
    var totalDebit = 0;
    var totalCredit = 0;
    var rows = linesBody.querySelectorAll('.je-line-row');

    rows.forEach(function(row) {
      var debit = parseFloat(row.querySelector('.je-line-debit').value) || 0;
      var credit = parseFloat(row.querySelector('.je-line-credit').value) || 0;
      totalDebit += debit;
      totalCredit += credit;
    });

    var diff = Math.abs(totalDebit - totalCredit);
    var balanced = diff < 0.01;

    totalDebitEl.textContent = totalDebit.toFixed(2);
    totalCreditEl.textContent = totalCredit.toFixed(2);
    differenceEl.textContent = diff.toFixed(2);

    if (balanced) {
      differenceEl.className = 'font-mono font-bold text-sm text-emerald-700';
      balanceBadge.className = 'inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200';
      balanceBadge.innerHTML = '<i data-lucide="check-circle" class="w-3 h-3"></i> متوازن';
    } else {
      differenceEl.className = 'font-mono font-bold text-sm text-rose-600';
      balanceBadge.className = 'inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-rose-50 text-rose-600 text-[10px] font-bold border border-rose-200';
      balanceBadge.innerHTML = '<i data-lucide="alert-triangle" class="w-3 h-3"></i> غير متوازن';
    }

    if (window.lucide) window.lucide.createIcons();
  }

  // ===== Bind events to a line row =====
  function bindLineEvents(row) {
    var debitInput = row.querySelector('.je-line-debit');
    var creditInput = row.querySelector('.je-line-credit');
    var removeBtn = row.querySelector('.je-remove-line-btn');

    if (debitInput) debitInput.addEventListener('input', updateBalance);
    if (creditInput) creditInput.addEventListener('input', updateBalance);
    if (removeBtn) removeBtn.addEventListener('click', function() { removeLine(removeBtn); });

    // Auto-clear opposite side when one is filled
    if (debitInput) {
      debitInput.addEventListener('input', function() {
        if (parseFloat(this.value) > 0 && creditInput) creditInput.value = '';
      });
    }
    if (creditInput) {
      creditInput.addEventListener('input', function() {
        if (parseFloat(this.value) > 0 && debitInput) debitInput.value = '';
      });
    }
  }

  // ===== Clear all lines =====
  function clearLines() {
    linesBody.innerHTML = '';
    lineCounter = 0;
  }

  // ===== Add line button =====
  if (addLineBtn) {
    addLineBtn.addEventListener('click', function() { addLine(); });
  }

  // ===== Modal open event: handle create/edit switching =====
  document.addEventListener('aracari:modal-open', function(e) {
    var target = e.target;
    if (target.id !== 'journal-entry-form-modal' || !target.closest) {
      // Check if the modal is an ancestor
      var m = target.closest ? target.closest('#journal-entry-form-modal') : null;
      if (!m) return;
      target = m;
    }
    if (target.id !== 'journal-entry-form-modal') return;

    var data = e.detail.data || {};
    var isEdit = !!(data.reference_number);
    isEditMode = isEdit;

    // Clear existing lines
    clearLines();

    if (isEdit) {
      // Switch to edit mode
      var updateUrl = "{{ route('admin.accounting.journal-entries.update', '__REF__') }}".replace('__REF__', data.reference_number);
      form.setAttribute('action', updateUrl);
      var existingMethod = form.querySelector('input[name="_method"]');
      if (existingMethod) {
        existingMethod.value = 'PUT';
      } else {
        var mInput = document.createElement('input');
        mInput.type = 'hidden'; mInput.name = '_method'; mInput.value = 'PUT';
        form.appendChild(mInput);
      }

      // Update banner to amber (edit)
      var banner = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (banner) banner.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';
      var titleEl = modal.querySelector('.aracari-modal-panel h2');
      if (titleEl) titleEl.innerHTML = '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل القيد المحاسبي';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }
      form.setAttribute('data-success-message', 'تم تحديث القيد المحاسبي بنجاح');

      // Populate lines from data
      if (Array.isArray(data.lines) && data.lines.length > 0) {
        data.lines.forEach(function(line) { addLine(line); });
      } else {
        addLine(); addLine();
      }
    } else {
      // Create mode — reset everything
      form.setAttribute('action', "{{ route('admin.accounting.journal-entries.store') }}");
      var allMethods = form.querySelectorAll('input[name="_method"]');
      allMethods.forEach(function(el) { el.remove(); });

      var banner2 = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (banner2) banner2.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
      var titleEl2 = modal.querySelector('.aracari-modal-panel h2');
      if (titleEl2) titleEl2.innerHTML = '<i data-lucide="plus-circle" class="w-5 h-5"></i> إنشاء قيد محاسبي جديد';
      var badge2 = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge2) {
        badge2.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30';
        badge2.textContent = 'جديد';
      }
      form.setAttribute('data-success-message', 'تم حفظ القيد المحاسبي بنجاح');

      // Set default date to today
      var dateInput = modal.querySelector('#je-date');
      if (dateInput && !dateInput.value) dateInput.value = '{{ date("Y-m-d") }}';

      // Add 2 empty lines
      addLine(); addLine();
    }

    if (window.lucide) window.lucide.createIcons();
  });

  // ===== Clear line errors and state on modal close =====
  var closeObserver = new MutationObserver(function(mutations) {
    mutations.forEach(function(m) {
      if (m.type === 'attributes' && m.attributeName === 'class') {
        var el = m.target;
        if (el.id === 'journal-entry-form-modal' && el.classList.contains('hidden')) {
          isEditMode = false;
          clearLines();
        }
      }
    });
  });
  closeObserver.observe(modal, { attributes: true });

})();
</script>
@endpush
