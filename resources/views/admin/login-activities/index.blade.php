@extends('layouts.admin')

@section('title', 'أركاري | سجلات الدخول')
@section('header_title', 'سجلات الدخول')

@section('content')

@php
    // Permission checks
    $user         = auth()->user();
    $canView      = $user && $user->hasPermission('login-activities:view');
    $canExport    = $user && $user->hasPermission('login-activities:export');
    $canCleanup   = $user && $user->hasPermission('login-activities:cleanup');

    if (!$canView) {
        abort(403);
    }

    // Status badge style mapping
    $statusStyles = [
        'success' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'dot' => 'bg-emerald-500', 'icon' => 'check-circle', 'label' => 'نجح'],
        'failed'  => ['bg' => 'bg-rose-50',    'text' => 'text-rose-700',    'border' => 'border-rose-200',    'dot' => 'bg-rose-500',    'icon' => 'x-circle',     'label' => 'فشل'],
    ];
    $defaultStatusStyle = ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'border' => 'border-slate-200', 'dot' => 'bg-slate-400', 'icon' => 'help-circle', 'label' => 'غير معروف'];

    $getStatusStyle = function($status) use ($statusStyles, $defaultStatusStyle) {
        return $statusStyles[$status] ?? $defaultStatusStyle;
    };

    // Stats from controller
    $totalStat     = $statsArray['total'] ?? 0;
    $successStat   = $statsArray['success'] ?? 0;
    $failedStat    = $statsArray['failed'] ?? 0;
    $todayStat     = $statsArray['today'] ?? 0;
    $thisWeekStat  = $statsArray['this_week'] ?? 0;
    $thisMonthStat = $statsArray['this_month'] ?? 0;

    // Current filter values
    $currentIdentifier = request('identifier');
    $currentStatus    = request('status');
    $currentDateFrom  = request('date_from');
    $currentDateTo    = request('date_to');
    $currentUserId    = request('user_id');

    // Active filters check
    $currentQueryParams = array_filter(request()->query(), function($v) { return $v !== null && $v !== ''; });
    $hasActiveFilters  = count($currentQueryParams) > 0;
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="log-in"
      title="سجلات الدخول"
      :count="$loginActivities->total()"
      countLabel="سجل"
      description="مراقبة وتحليل محاولات تسجيل الدخول إلى النظام"
      descriptionIcon="bar-chart-3"
  >
    <x-slot name="action">
      <div class="flex items-center gap-2 flex-wrap">
        @if($canExport)
          <button
              type="button"
              id="btn-export-csv"
              class="h-11 px-4 bg-emerald-50/80 border border-emerald-200 text-emerald-700 rounded-xl font-bold text-sm hover:bg-emerald-500 hover:text-white hover:border-emerald-500 transition-all flex items-center gap-1.5 flex-shrink-0"
          >
            <i data-lucide="download" class="w-4 h-4"></i>
            <span class="hidden sm:inline">تصدير CSV</span>
          </button>
        @endif
        @if($canCleanup)
          <button
              type="button"
              id="btn-open-cleanup"
              class="h-11 px-4 bg-rose-50/80 border border-rose-200 text-rose-600 rounded-xl font-bold text-sm hover:bg-rose-500 hover:text-white hover:border-rose-500 transition-all flex items-center gap-1.5 flex-shrink-0"
          >
            <i data-lucide="eraser" class="w-4 h-4"></i>
            <span class="hidden sm:inline">تنظيف السجلات</span>
          </button>
        @endif
      </div>
    </x-slot>
  </x-page-header>

  {{-- ===== 6 Stat Cards ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 lg:gap-6">
    {{-- إجمالي السجلات — slate --}}
    <x-stat-card
        :value="$totalStat"
        label="إجمالي السجلات"
        icon="list"
        variant="slate"
        badge="سجلات"
        badgeIcon="trending-up"
    />
    {{-- نجح الدخول — emerald --}}
    <x-stat-card
        :value="$successStat"
        label="نجح الدخول"
        icon="check-circle"
        variant="emerald"
        badge="ناجح"
        badgeIcon="check-circle"
    />
    {{-- فشل الدخول — rose --}}
    <x-stat-card
        :value="$failedStat"
        label="فشل الدخول"
        icon="x-circle"
        variant="rose"
        badge="فاشل"
        badgeIcon="x-circle"
    />
    {{-- سجلات اليوم — blue --}}
    <x-stat-card
        :value="$todayStat"
        label="سجلات اليوم"
        icon="calendar-days"
        variant="blue"
        badge="اليوم"
        badgeIcon="calendar-days"
    />
    {{-- هذا الأسبوع — teal --}}
    <x-stat-card
        :value="$thisWeekStat"
        label="هذا الأسبوع"
        icon="calendar-range"
        variant="teal"
        badge="أسبوع"
        badgeIcon="clock"
    />
    {{-- هذا الشهر — amber --}}
    <x-stat-card
        :value="$thisMonthStat"
        label="هذا الشهر"
        icon="calendar"
        variant="amber"
        badge="شهر"
        badgeIcon="calendar"
    />
  </div>

  {{-- ===== Filter Card ===== --}}
  <div class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-3xl overflow-hidden shadow-xl shadow-slate-200/20">
    <form method="GET" action="{{ route('admin.login-activities.index') }}" id="la-filter-form">
      <div class="p-5 border-b border-slate-200/70 bg-white/40 backdrop-blur">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <i data-lucide="filter" class="w-4 h-4 text-emerald-600"></i>
            <h3 class="text-sm font-black text-slate-700">تصفية السجلات</h3>
          </div>
          @if($hasActiveFilters)
            <a
                href="{{ route('admin.login-activities.index') }}"
                class="flex items-center gap-1.5 text-[11px] font-bold text-rose-500 hover:text-rose-600 transition-colors"
            >
              <i data-lucide="x-circle" class="w-3.5 h-3.5"></i>
              مسح الفلاتر
            </a>
          @endif
        </div>
      </div>
      <div class="p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
          {{-- المعرف (identifier) --}}
          <div>
            <label class="block text-[11px] font-bold text-slate-500 mb-1.5">
              <i data-lucide="search" class="w-3 h-3 inline-block ml-1 text-slate-400"></i>
              المعرف
            </label>
            <div class="relative group">
              <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-emerald-600 transition-colors">
                <i data-lucide="search" class="w-4 h-4"></i>
              </div>
              <input
                  type="text"
                  name="identifier"
                  value="{{ $currentIdentifier }}"
                  placeholder="بحث بالإيميل أو الهاتف..."
                  dir="ltr"
                  class="w-full h-11 bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 text-[13px] font-bold text-left placeholder:text-slate-400 focus:outline-none focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 transition-all"
              >
            </div>
          </div>

          {{-- الحالة --}}
          <div>
            <label class="block text-[11px] font-bold text-slate-500 mb-1.5">
              <i data-lucide="check-circle" class="w-3 h-3 inline-block ml-1 text-slate-400"></i>
              الحالة
            </label>
            <div class="relative group">
              <select
                  name="status"
                  class="w-full h-11 bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 pr-10 text-[13px] font-bold text-slate-700 focus:outline-none focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 transition-all appearance-none cursor-pointer"
              >
                <option value="">جميع الحالات</option>
                <option value="success" @selected($currentStatus === 'success')>نجح</option>
                <option value="failed" @selected($currentStatus === 'failed')>فشل</option>
              </select>
              <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none group-focus-within:text-emerald-600 transition-colors"></i>
            </div>
          </div>

          {{-- من تاريخ --}}
          <div>
            <label class="block text-[11px] font-bold text-slate-500 mb-1.5">
              <i data-lucide="calendar" class="w-3 h-3 inline-block ml-1 text-slate-400"></i>
              من تاريخ
            </label>
            <div class="relative group">
              <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-emerald-600 transition-colors">
                <i data-lucide="calendar" class="w-4 h-4"></i>
              </div>
              <input
                  type="date"
                  name="date_from"
                  value="{{ $currentDateFrom }}"
                  dir="ltr"
                  class="w-full h-11 bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 text-[13px] font-bold text-left text-slate-700 focus:outline-none focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 transition-all"
              >
            </div>
          </div>

          {{-- إلى تاريخ --}}
          <div>
            <label class="block text-[11px] font-bold text-slate-500 mb-1.5">
              <i data-lucide="calendar-check" class="w-3 h-3 inline-block ml-1 text-slate-400"></i>
              إلى تاريخ
            </label>
            <div class="relative group">
              <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-emerald-600 transition-colors">
                <i data-lucide="calendar-check" class="w-4 h-4"></i>
              </div>
              <input
                  type="date"
                  name="date_to"
                  value="{{ $currentDateTo }}"
                  dir="ltr"
                  class="w-full h-11 bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 text-[13px] font-bold text-left text-slate-700 focus:outline-none focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 transition-all"
              >
            </div>
          </div>

          {{-- المستخدم --}}
          <div>
            <label class="block text-[11px] font-bold text-slate-500 mb-1.5">
              <i data-lucide="user" class="w-3 h-3 inline-block ml-1 text-slate-400"></i>
              معرف المستخدم
            </label>
            <div class="relative group">
              <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-emerald-600 transition-colors">
                <i data-lucide="user" class="w-4 h-4"></i>
              </div>
              <input
                  type="text"
                  name="user_id"
                  value="{{ $currentUserId }}"
                  placeholder="ID المستخدم..."
                  dir="ltr"
                  class="w-full h-11 bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 text-[13px] font-bold text-left placeholder:text-slate-400 focus:outline-none focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-600 transition-all"
              >
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
                href="{{ route('admin.login-activities.index') }}"
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

  {{-- ===== Data Table Card (refreshable) ===== --}}
  <div id="aracari-refreshable">
  <x-data-table-card
      :searchable="false"
      :footerInfo="'عرض ' . $loginActivities->count() . ' من أصل ' . $loginActivities->total() . ' سجل'"
  >
    <x-slot name="thead">
      <tr>
        <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
        <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المستخدم</th>
        <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المعرف</th>
        <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
        <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">IP</th>
        <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الجهاز</th>
        <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">التاريخ</th>
        <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
      </tr>
    </x-slot>

    <x-slot name="rows">
      @forelse($loginActivities as $index => $activity)
        @php
            $style     = $getStatusStyle($activity->status);
            $userName  = $activity->user?->name ?? 'غير معروف';
            $initial   = mb_substr($userName, 0, 1);
            $dateValue = $activity->attempted_at ?? $activity->created_at;
        @endphp
        <tr class="hover:bg-slate-50/50 transition-colors group">

          {{-- # --}}
          <td class="px-5 py-3.5">
            <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg bg-slate-900 text-white text-[11px] font-mono font-black shadow-sm">
              {{ $loginActivities->firstItem() + $index }}
            </span>
          </td>

          {{-- المستخدم --}}
          <td class="px-5 py-3.5">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-sm shadow-emerald-500/20 group-hover:scale-110 transition-transform duration-300">
                {{ $initial }}
              </div>
              <div class="min-w-0">
                <p class="font-bold text-slate-800 text-sm truncate">{{ $userName }}</p>
                <p class="text-[11px] text-slate-400 font-mono" dir="ltr">ID: {{ $activity->user_id ?? '—' }}</p>
              </div>
            </div>
          </td>

          {{-- المعرف (identifier — mono font) --}}
          <td class="px-5 py-3.5">
            <span
                class="inline-block max-w-[200px] font-mono text-xs font-bold bg-white/30 border border-slate-200 text-slate-700 px-2.5 py-1 rounded-lg truncate"
                dir="ltr"
                title="{{ $activity->identifier }}"
            >
                {{ $activity->identifier }}
            </span>
          </td>

          {{-- الحالة (badge) --}}
          <td class="px-5 py-3.5 text-center">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $style['bg'] }} {{ $style['text'] }} {{ $style['border'] }}">
              <span class="w-1.5 h-1.5 rounded-full {{ $style['dot'] }}"></span>
              <i data-lucide="{{ $style['icon'] }}" class="w-3 h-3"></i>
              {{ $style['label'] }}
            </span>
          </td>

          {{-- IP (mono font) --}}
          <td class="px-5 py-3.5">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-[11px] font-mono font-bold border border-slate-200" dir="ltr">
              <i data-lucide="globe" class="w-3 h-3 text-slate-400"></i>
              {{ $activity->ip_address }}
            </span>
          </td>

          {{-- الجهاز (user agent — truncated with tooltip) --}}
          <td class="px-5 py-3.5">
            <span
                class="block max-w-[220px] text-[11px] font-medium text-slate-600 truncate"
                title="{{ $activity->user_agent }}"
                dir="ltr"
            >
                {{ $activity->user_agent ?: '—' }}
            </span>
          </td>

          {{-- التاريخ --}}
          <td class="px-5 py-3.5">
            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700">
              <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0"></i>
              <span dir="ltr">{{ $dateValue ? $dateValue->format('Y-m-d H:i') : '—' }}</span>
            </div>
          </td>

          {{-- الإجراءات --}}
          <td class="px-5 py-3.5 text-center">
            <div class="flex items-center justify-center gap-1.5">
              <button
                  type="button"
                  onclick="viewLoginActivity('{{ $activity->uuid }}')"
                  class="w-9 h-9 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-600 hover:bg-emerald-500 hover:text-white hover:border-emerald-500 transition-all duration-300 flex items-center justify-center shadow-sm"
                  title="عرض التفاصيل"
                  aria-label="عرض تفاصيل سجل الدخول"
              >
                <i data-lucide="eye" class="w-4 h-4"></i>
              </button>
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state
            icon="log-in"
            :title="$hasActiveFilters ? 'لا توجد نتائج مطابقة' : 'لا توجد سجلات دخول'"
            :message="$hasActiveFilters ? 'جرّب تعديل خيارات التصفية للعثور على السجلات المطلوبة' : 'لم يتم تسجيل أي محاولات دخول حتى الآن'"
            :colspan="8"
        />
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$loginActivities" />
    </x-slot>
  </x-data-table-card>
  </div>{{-- end #aracari-refreshable --}}

</div>

{{-- ===== Detail Modal ===== --}}
<x-modal
    id="la-detail-modal"
    title="تفاصيل سجل الدخول"
    variant="view"
    icon="log-in"
    maxWidth="max-w-lg"
>
  {{-- Loading state --}}
  <div id="la-detail-loading" class="flex flex-col items-center justify-center py-12 space-y-3">
    <div class="w-12 h-12 rounded-full bg-emerald-50 border-2 border-emerald-200 flex items-center justify-center animate-pulse">
      <i data-lucide="loader-2" class="w-6 h-6 text-emerald-500 animate-spin"></i>
    </div>
    <p class="text-sm font-bold text-slate-500">جارٍ تحميل التفاصيل...</p>
  </div>

  {{-- Content (hidden until loaded) --}}
  <div id="la-detail-content" class="hidden space-y-4">
    {{-- Status Badge --}}
    <div class="flex items-center gap-3 flex-wrap">
      <span id="la-detail-status-badge" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border">
        <span id="la-detail-status-dot" class="w-1.5 h-1.5 rounded-full"></span>
        <i id="la-detail-status-icon" data-lucide="check-circle" class="w-3 h-3"></i>
        <span id="la-detail-status-text"></span>
      </span>
      <span id="la-detail-uuid-badge" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 text-[10px] font-mono font-bold border border-slate-200" dir="ltr">
        <i data-lucide="hash" class="w-3 h-3 text-slate-400"></i>
        <span id="la-detail-uuid-text"></span>
      </span>
    </div>

    {{-- Info Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      {{-- المستخدم --}}
      <div class="bg-slate-50/50 border border-slate-200/70 rounded-xl p-3">
        <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
          <i data-lucide="user" class="w-3.5 h-3.5"></i>
          المستخدم
        </div>
        <div class="font-bold text-sm text-slate-800" id="la-detail-user">—</div>
      </div>

      {{-- المعرف --}}
      <div class="bg-slate-50/50 border border-slate-200/70 rounded-xl p-3">
        <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
          <i data-lucide="badge-check" class="w-3.5 h-3.5"></i>
          المعرف
        </div>
        <div class="font-bold text-sm text-slate-800 font-mono break-all" dir="ltr" id="la-detail-identifier">—</div>
      </div>

      {{-- الحالة --}}
      <div class="bg-slate-50/50 border border-slate-200/70 rounded-xl p-3">
        <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
          <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
          الحالة
        </div>
        <div id="la-detail-status-field">—</div>
      </div>

      {{-- IP --}}
      <div class="bg-slate-50/50 border border-slate-200/70 rounded-xl p-3">
        <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
          <i data-lucide="globe" class="w-3.5 h-3.5"></i>
          عنوان IP
        </div>
        <div class="font-bold text-sm text-slate-800 font-mono" dir="ltr" id="la-detail-ip">—</div>
      </div>

      {{-- الجهاز (user agent) — full width --}}
      <div class="bg-slate-50/50 border border-slate-200/70 rounded-xl p-3 md:col-span-2">
        <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
          <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
          الجهاز / User Agent
        </div>
        <div class="font-bold text-[11px] text-slate-600 leading-relaxed whitespace-pre-wrap break-all max-h-24 overflow-y-auto" dir="ltr" id="la-detail-useragent">—</div>
      </div>

      {{-- التاريخ — full width --}}
      <div class="bg-slate-50/50 border border-slate-200/70 rounded-xl p-3 md:col-span-2">
        <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
          <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
          التاريخ والوقت
        </div>
        <div class="font-bold text-sm text-slate-800" dir="ltr" id="la-detail-date">—</div>
      </div>
    </div>
  </div>
</x-modal>

{{-- ===== Cleanup Confirmation Modal ===== --}}
@if($canCleanup)
<x-modal
    id="la-cleanup-modal"
    title="تنظيف السجلات القديمة"
    variant="view"
    icon="eraser"
    maxWidth="max-w-md"
>
  <div class="space-y-4">
    {{-- Warning --}}
    <div class="flex items-start gap-3 p-4 bg-rose-50 border border-rose-200 rounded-2xl">
      <div class="w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center flex-shrink-0">
        <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600"></i>
      </div>
      <div>
        <h4 class="font-bold text-sm text-rose-800">تحذير: عملية لا يمكن التراجع عنها</h4>
        <p class="text-[12px] text-rose-600 mt-1">سيتم حذف جميع سجلات الدخول القديمة بشكل نهائي. لا يمكن استرجاعها بعد الحذف.</p>
      </div>
    </div>

    {{-- Days input --}}
    <div>
      <label class="block text-[11px] font-bold text-slate-500 mb-1.5">
        <i data-lucide="calendar-x" class="w-3 h-3 inline-block ml-1 text-slate-400"></i>
        عدد الأيام (الحد الأدنى: 30 يوم)
      </label>
      <input
          type="number"
          id="la-cleanup-days"
          value="90"
          min="30"
          dir="ltr"
          class="w-full h-11 bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 text-[13px] font-bold text-center text-slate-700 focus:outline-none focus:bg-white focus:ring-4 focus:ring-rose-500/10 focus:border-rose-400 transition-all"
      >
      <p class="text-[10px] text-slate-400 mt-1">سيتم حذف جميع السجلات الأقدم من هذا العدد من الأيام</p>
    </div>

    {{-- Cleanup message area --}}
    <div id="la-cleanup-message" class="hidden"></div>

    {{-- Actions --}}
    <div class="flex items-center justify-end gap-2 pt-2">
      <button
          type="button"
          onclick="closeLaCleanupModal()"
          class="px-5 py-2.5 bg-[#f3f3f6] hover:bg-[#eeeef0] text-slate-700 font-bold text-xs rounded-xl transition-all flex items-center gap-2"
      >
        <i data-lucide="x" class="w-4 h-4"></i>
        إلغاء
      </button>
      <button
          type="button"
          id="la-cleanup-submit"
          onclick="executeCleanup()"
          class="px-5 py-2.5 bg-gradient-to-r from-rose-500 to-rose-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-rose-500/20 hover:shadow-rose-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="trash-2" class="w-4 h-4"></i>
        حذف السجلات
      </button>
    </div>
  </div>
</x-modal>
@endif

@push('scripts')
<script>
  /**
   * View Login Activity Details — AJAX fetch
   */
  function viewLoginActivity(uuid) {
    // Show loading, hide content
    var loadingEl = document.getElementById('la-detail-loading');
    var contentEl = document.getElementById('la-detail-content');
    if (loadingEl) loadingEl.classList.remove('hidden');
    if (contentEl) contentEl.classList.add('hidden');

    // Open modal first
    openAracariModal('la-detail-modal');

    // Fetch details
    fetch('{{ route('admin.login-activities.show', '__UUID__') }}'.replace('__UUID__', uuid), {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(function(response) {
      if (!response.ok) throw new Error('Server error: ' + response.status);
      return response.json();
    })
    .then(function(json) {
      if (!json.success || !json.data) throw new Error('Invalid response');

      var d = json.data;
      var status = d.status || 'unknown';

      // Status style mapping
      var styles = {
        success: { bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', dot: 'bg-emerald-500', label: 'نجح' },
        failed:  { bg: 'bg-rose-50',    text: 'text-rose-700',    border: 'border-rose-200',    dot: 'bg-rose-500',    label: 'فشل' }
      };
      var s = styles[status] || { bg: 'bg-slate-100', text: 'text-slate-600', border: 'border-slate-200', dot: 'bg-slate-400', label: 'غير معروف' };

      // Top badges
      var badge = document.getElementById('la-detail-status-badge');
      if (badge) {
        badge.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border ' + s.bg + ' ' + s.text + ' ' + s.border;
      }
      var dot = document.getElementById('la-detail-status-dot');
      if (dot) dot.className = 'w-1.5 h-1.5 rounded-full ' + s.dot;
      var statusText = document.getElementById('la-detail-status-text');
      if (statusText) statusText.textContent = s.label;
      var uuidText = document.getElementById('la-detail-uuid-text');
      if (uuidText) uuidText.textContent = d.uuid || '—';

      // User
      var userEl = document.getElementById('la-detail-user');
      if (userEl) {
        var userName = d.user && d.user.name ? d.user.name : 'غير معروف';
        var userId = d.user_id || '—';
        userEl.innerHTML = '<div class="flex items-center gap-2">' +
          '<div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white font-bold text-xs flex-shrink-0">' +
          userName.charAt(0) + '</div>' +
          '<div><p class="font-bold text-slate-800 text-sm">' + userName + '</p>' +
          '<p class="text-[10px] text-slate-400 font-mono" dir="ltr">ID: ' + userId + '</p></div></div>';
      }

      // Identifier
      var identEl = document.getElementById('la-detail-identifier');
      if (identEl) identEl.textContent = d.identifier || '—';

      // Status field (inline badge)
      var statusField = document.getElementById('la-detail-status-field');
      if (statusField) {
        statusField.innerHTML = '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border ' +
          s.bg + ' ' + s.text + ' ' + s.border + '">' +
          '<span class="w-1.5 h-1.5 rounded-full ' + s.dot + '"></span>' + s.label + '</span>';
      }

      // IP
      var ipEl = document.getElementById('la-detail-ip');
      if (ipEl) ipEl.textContent = d.ip_address || '—';

      // User Agent
      var uaEl = document.getElementById('la-detail-useragent');
      if (uaEl) uaEl.textContent = d.user_agent || '—';

      // Date
      var dateEl = document.getElementById('la-detail-date');
      if (dateEl) {
        var dateVal = d.attempted_at || d.created_at;
        if (dateVal) {
          dateEl.textContent = dateVal;
        } else {
          dateEl.textContent = '—';
        }
      }

      // Hide loading, show content
      if (loadingEl) loadingEl.classList.add('hidden');
      if (contentEl) contentEl.classList.remove('hidden');

      // Re-init lucide icons inside modal
      if (typeof window.lucide !== 'undefined') {
        window.lucide.createIcons();
      }
    })
    .catch(function(err) {
      console.error('Error loading login activity details:', err);
      if (loadingEl) loadingEl.classList.add('hidden');
      if (contentEl) {
        contentEl.classList.remove('hidden');
        contentEl.innerHTML = '<div class="text-center py-8">' +
          '<div class="w-16 h-16 rounded-full bg-rose-50 mx-auto flex items-center justify-center mb-3">' +
          '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-rose-500"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>' +
          '</div><p class="font-bold text-rose-600 text-sm">فشل تحميل التفاصيل</p>' +
          '<p class="text-[11px] text-slate-400 mt-1">يرجى المحاولة مرة أخرى</p></div>';
      }
    });
  }

  /**
   * Export CSV
   */
  document.addEventListener('DOMContentLoaded', function() {
    var exportBtn = document.getElementById('btn-export-csv');
    if (exportBtn) {
      exportBtn.addEventListener('click', function() {
        // Build filter params from current URL query
        var params = new URLSearchParams(window.location.search);

        exportBtn.disabled = true;
        exportBtn.innerHTML = '<svg class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> جارٍ التصدير...';

        fetch('{{ route('admin.login-activities.export') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
          },
          body: JSON.stringify(Object.fromEntries(params))
        })
        .then(function(response) {
          if (!response.ok) throw new Error('Export failed');
          return response.json();
        })
        .then(function(json) {
          if (!json.success || !json.data || !Array.isArray(json.data)) {
            throw new Error('Invalid export data');
          }

          // Build CSV string
          var csvContent = json.data.map(function(row) {
            return row.map(function(cell) {
              var val = String(cell == null ? '' : cell);
              if (val.includes(',') || val.includes('"') || val.includes('\n')) {
                val = '"' + val.replace(/"/g, '""') + '"';
              }
              return '\uFEFF' === val ? val : val;
            }).join(',');
          }).join('\n');

          // Add BOM for Arabic support
          var BOM = '\uFEFF';
          var blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
          var url = URL.createObjectURL(blob);
          var a = document.createElement('a');
          a.href = url;
          a.download = 'login-activities-' + new Date().toISOString().slice(0, 10) + '.csv';
          document.body.appendChild(a);
          a.click();
          document.body.removeChild(a);
          URL.revokeObjectURL(url);

          // Reset button
          exportBtn.disabled = false;
          exportBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> <span class="hidden sm:inline">تصدير CSV</span>';
          if (typeof window.lucide !== 'undefined') window.lucide.createIcons();

          // Show success notification
          showLaNotification('تم تصدير السجلات بنجاح', 'success');
        })
        .catch(function(err) {
          console.error('Export error:', err);
          exportBtn.disabled = false;
          exportBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> <span class="hidden sm:inline">تصدير CSV</span>';
          if (typeof window.lucide !== 'undefined') window.lucide.createIcons();

          showLaNotification('فشل تصدير السجلات', 'error');
        });
      });
    }

    // Cleanup button
    var cleanupBtn = document.getElementById('btn-open-cleanup');
    if (cleanupBtn) {
      cleanupBtn.addEventListener('click', function() {
        document.getElementById('la-cleanup-days').value = 90;
        var msgEl = document.getElementById('la-cleanup-message');
        if (msgEl) { msgEl.classList.add('hidden'); msgEl.innerHTML = ''; }
        openAracariModal('la-cleanup-modal');
      });
    }
  });

  /**
   * Execute cleanup
   */
  function executeCleanup() {
    var daysInput = document.getElementById('la-cleanup-days');
    var submitBtn = document.getElementById('la-cleanup-submit');
    var msgEl = document.getElementById('la-cleanup-message');

    var days = parseInt(daysInput.value) || 90;
    days = Math.max(30, days);
    daysInput.value = days;

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<svg class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> جارٍ الحذف...';

    fetch('{{ route('admin.login-activities.cleanup') }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
      },
      body: JSON.stringify({ days: days })
    })
    .then(function(response) {
      if (!response.ok) throw new Error('Cleanup failed');
      return response.json();
    })
    .then(function(json) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg> حذف السجلات';
      if (typeof window.lucide !== 'undefined') window.lucide.createIcons();

      if (msgEl) {
        msgEl.classList.remove('hidden');
        msgEl.innerHTML = '<div class="flex items-start gap-2 p-3 bg-emerald-50 border border-emerald-200 rounded-xl">' +
          '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>' +
          '<div><p class="font-bold text-sm text-emerald-700">' + (json.message || 'تم بنجاح') + '</p>' +
          '<p class="text-[10px] text-emerald-600 mt-1">قم بتحديث الصفحة لرؤية النتائج</p></div></div>';
      }
    })
    .catch(function(err) {
      console.error('Cleanup error:', err);
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg> حذف السجلات';
      if (typeof window.lucide !== 'undefined') window.lucide.createIcons();

      if (msgEl) {
        msgEl.classList.remove('hidden');
        msgEl.innerHTML = '<div class="flex items-start gap-2 p-3 bg-rose-50 border border-rose-200 rounded-xl">' +
          '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-rose-600 flex-shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>' +
          '<p class="font-bold text-sm text-rose-700">فشل تنظيف السجلات</p></div>';
      }
    });
  }

  /**
   * Close cleanup modal
   */
  function closeLaCleanupModal() {
    closeAracariModal('la-cleanup-modal');
  }

  /**
   * Simple notification toast
   */
  function showLaNotification(message, type) {
    var colors = {
      success: 'bg-emerald-500',
      error: 'bg-rose-500'
    };
    var bgClass = colors[type] || colors.success;

    var toast = document.createElement('div');
    toast.className = 'fixed bottom-6 left-6 z-[90] px-5 py-3 ' + bgClass + ' text-white text-sm font-bold rounded-2xl shadow-xl transition-all duration-500 opacity-0 translate-y-4';
    toast.textContent = message;
    document.body.appendChild(toast);

    requestAnimationFrame(function() {
      toast.style.opacity = '1';
      toast.style.transform = 'translateY(0)';
    });

    setTimeout(function() {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(16px)';
      setTimeout(function() {
        if (toast.parentNode) toast.parentNode.removeChild(toast);
      }, 500);
    }, 3000);
  }
</script>
@endpush

@endsection
