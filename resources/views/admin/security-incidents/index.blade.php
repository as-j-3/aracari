@extends('layouts.admin')

@section('title', 'أركاري | الحوادث الأمنية')
@section('header_title', 'الحوادث الأمنية')

@section('content')

@php
    // Permission checks
    $user    = auth()->user();
    $canView = $user && $user->hasPermission('security:view');
    $canManage = $user && $user->hasPermission('security:manage');

    if (!$canView) {
        abort(403);
    }

    // Severity badge style mapping
    $severityStyles = [
        'high'   => ['bg' => 'bg-rose-50',    'text' => 'text-rose-700',    'border' => 'border-rose-200',    'dot' => 'bg-rose-500',    'icon' => 'alert-triangle', 'label' => 'عالية'],
        'medium' => ['bg' => 'bg-amber-50',    'text' => 'text-amber-700',    'border' => 'border-amber-200',    'dot' => 'bg-amber-500',    'icon' => 'info',           'label' => 'متوسطة'],
        'low'    => ['bg' => 'bg-emerald-50',  'text' => 'text-emerald-700',  'border' => 'border-emerald-200',  'dot' => 'bg-emerald-500',  'icon' => 'check-circle',   'label' => 'منخفضة'],
    ];
    $defaultSeverityStyle = ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'border' => 'border-slate-200', 'dot' => 'bg-slate-400', 'icon' => 'help-circle', 'label' => 'غير معروف'];

    $getSeverityStyle = function($severity) use ($severityStyles, $defaultSeverityStyle) {
        return $severityStyles[$severity] ?? $defaultSeverityStyle;
    };

    // HTTP method badge style mapping
    $methodStyles = [
        'GET'    => ['bg' => 'bg-blue-50',    'text' => 'text-blue-700',    'border' => 'border-blue-200'],
        'POST'   => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200'],
        'PUT'    => ['bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'border' => 'border-amber-200'],
        'PATCH'  => ['bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'border' => 'border-amber-200'],
        'DELETE' => ['bg' => 'bg-rose-50',    'text' => 'text-rose-700',    'border' => 'border-rose-200'],
    ];
    $defaultMethodStyle = ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'border' => 'border-slate-200'];

    $getMethodStyle = function($method) use ($methodStyles, $defaultMethodStyle) {
        return $methodStyles[strtoupper($method)] ?? $defaultMethodStyle;
    };

    // Stats (computed from the full dataset, not just current page)
    $totalCount     = \App\Models\SecurityIncident::count();
    $highCount      = \App\Models\SecurityIncident::where('severity', 'high')->count();
    $mediumCount    = \App\Models\SecurityIncident::where('severity', 'medium')->count();
    $lowCount       = \App\Models\SecurityIncident::where('severity', 'low')->count();

    // Current filter values
    $currentSearch  = request('search');
    $currentSeverity = request('severity');
    $currentBlocked  = request('blocked');

    // Active filters check
    $currentQueryParams = array_filter(request()->query(), function($v) { return $v !== null && $v !== ''; });
    $hasActiveFilters  = count($currentQueryParams) > 0;
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="shield-alert"
      title="سجل الحوادث الأمنية"
      :count="$incidents->total()"
      countLabel="حادث"
      description="مراقبة وتحليل التهديدات والمحاولات الأمنية المشبوهة"
      descriptionIcon="bar-chart-3"
  />

  {{-- ===== 4 Stat Cards ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    {{-- إجمالي الحوادث — slate --}}
    <x-stat-card
        :value="$totalCount"
        label="إجمالي الحوادث"
        icon="shield-alert"
        variant="slate"
        badge="حوادث"
        badgeIcon="trending-up"
    />
    {{-- خطورة عالية — rose --}}
    <x-stat-card
        :value="$highCount"
        label="خطورة عالية"
        icon="alert-triangle"
        variant="rose"
        badge="خطير"
        badgeIcon="alert-triangle"
    />
    {{-- خطورة متوسطة — amber --}}
    <x-stat-card
        :value="$mediumCount"
        label="خطورة متوسطة"
        icon="info"
        variant="amber"
        badge="تحذير"
        badgeIcon="info"
    />
    {{-- خطورة منخفضة — emerald --}}
    <x-stat-card
        :value="$lowCount"
        label="خطورة منخفضة"
        icon="check-circle"
        variant="emerald"
        badge="آمن"
        badgeIcon="check-circle"
    />
  </div>

  {{-- ===== Filter Card ===== --}}
  <div class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-3xl overflow-hidden shadow-xl shadow-slate-200/20">
    <form method="GET" action="{{ route('admin.security-incidents') }}" id="si-filter-form">
      <div class="p-5 border-b border-slate-200/70 bg-white/40 backdrop-blur">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <i data-lucide="filter" class="w-4 h-4 text-rose-600"></i>
            <h3 class="text-sm font-black text-slate-700">تصفية الحوادث</h3>
          </div>
          @if($hasActiveFilters)
            <a
                href="{{ route('admin.security-incidents') }}"
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
          {{-- البحث --}}
          <div>
            <label class="block text-[11px] font-bold text-slate-500 mb-1.5">
              <i data-lucide="search" class="w-3 h-3 inline-block ml-1 text-slate-400"></i>
              بحث
            </label>
            <div class="relative group">
              <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-rose-600 transition-colors">
                <i data-lucide="search" class="w-4 h-4"></i>
              </div>
              <input
                  type="text"
                  name="search"
                  value="{{ $currentSearch }}"
                  placeholder="بحث بـ IP أو المسار أو النوع..."
                  dir="ltr"
                  class="w-full h-11 bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 text-[13px] font-bold text-left placeholder:text-slate-400 focus:outline-none focus:bg-white focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 transition-all"
              >
            </div>
          </div>

          {{-- الخطورة --}}
          <div>
            <label class="block text-[11px] font-bold text-slate-500 mb-1.5">
              <i data-lucide="alert-triangle" class="w-3 h-3 inline-block ml-1 text-slate-400"></i>
              الخطورة
            </label>
            <div class="relative group">
              <select
                  name="severity"
                  class="w-full h-11 bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 pr-10 text-[13px] font-bold text-slate-700 focus:outline-none focus:bg-white focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 transition-all appearance-none cursor-pointer"
              >
                <option value="" {{ !$currentSeverity ? 'selected' : '' }}>جميع المستويات</option>
                <option value="high" {{ $currentSeverity === 'high' ? 'selected' : '' }}>عالية</option>
                <option value="medium" {{ $currentSeverity === 'medium' ? 'selected' : '' }}>متوسطة</option>
                <option value="low" {{ $currentSeverity === 'low' ? 'selected' : '' }}>منخفضة</option>
              </select>
              <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none group-focus-within:text-rose-600 transition-colors"></i>
            </div>
          </div>

          {{-- الحظر --}}
          <div>
            <label class="block text-[11px] font-bold text-slate-500 mb-1.5">
              <i data-lucide="lock" class="w-3 h-3 inline-block ml-1 text-slate-400"></i>
              الحالة
            </label>
            <div class="relative group">
              <select
                  name="blocked"
                  class="w-full h-11 bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 pr-10 text-[13px] font-bold text-slate-700 focus:outline-none focus:bg-white focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 transition-all appearance-none cursor-pointer"
              >
                <option value="" {{ $currentBlocked === '' || $currentBlocked === null ? 'selected' : '' }}>جميع الحالات</option>
                <option value="1" {{ $currentBlocked === '1' ? 'selected' : '' }}>محظور</option>
                <option value="0" {{ $currentBlocked === '0' ? 'selected' : '' }}>غير محظور</option>
              </select>
              <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none group-focus-within:text-rose-600 transition-colors"></i>
            </div>
          </div>

          {{-- spacer for alignment --}}
          <div class="hidden lg:block"></div>
        </div>

        {{-- Submit / Reset buttons --}}
        <div class="mt-4 flex items-center gap-3">
          <button
              type="submit"
              class="px-5 py-2.5 bg-gradient-to-r from-rose-500 to-rose-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-rose-500/20 hover:shadow-rose-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
          >
            <i data-lucide="search" class="w-4 h-4"></i>
            بحث وتصفية
          </button>
          @if($hasActiveFilters)
            <a
                href="{{ route('admin.security-incidents') }}"
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
      :footerInfo="'عرض ' . $incidents->count() . ' من أصل ' . $incidents->total() . ' حادث'"
  >
    <x-slot name="thead">
      <tr>
        <th class="px-4 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
        <th class="px-4 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">نوع الحادث</th>
        <th class="px-4 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الخطورة</th>
        <th class="px-4 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">IP</th>
        <th class="px-4 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المسار</th>
        <th class="px-4 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الطريقة</th>
        <th class="px-4 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المستخدم</th>
        <th class="px-4 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">المحاولات</th>
        <th class="px-4 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحظر</th>
        <th class="px-4 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">التاريخ</th>
        <th class="px-4 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
      </tr>
    </x-slot>

    <x-slot name="rows">
      @forelse($incidents as $index => $incident)
        @php
            $sStyle    = $getSeverityStyle($incident->severity);
            $mStyle    = $getMethodStyle($incident->request_method);
            $userName  = $incident->user?->name ?? 'مجهول';
            $initial   = mb_substr($userName, 0, 1);
            $incidentJson = json_encode([
                'id' => $incident->id,
                'incident_type' => $incident->incident_type,
                'severity' => $incident->severity,
                'ip_address' => $incident->ip_address,
                'user_id' => $incident->user_id,
                'user_name' => $userName,
                'request_method' => $incident->request_method,
                'request_path' => $incident->request_path,
                'request_inputs' => $incident->request_inputs,
                'attempt_count' => $incident->attempt_count,
                'was_blocked' => $incident->was_blocked,
                'user_agent' => $incident->user_agent,
                'occurred_at' => $incident->occurred_at ? $incident->occurred_at->format('Y-m-d H:i:s') : null,
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
        @endphp
        <tr class="hover:bg-slate-50/50 transition-colors group" data-incident-id="{{ $incident->id }}" data-incident="{{ $incidentJson }}">

          {{-- # --}}
          <td class="px-4 py-3.5">
            <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg bg-slate-900 text-white text-[11px] font-mono font-black shadow-sm">
              {{ $incidents->firstItem() + $index }}
            </span>
          </td>

          {{-- نوع الحادث --}}
          <td class="px-4 py-3.5">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-[11px] font-bold border border-slate-200">
              <i data-lucide="zap" class="w-3 h-3 text-amber-500"></i>
              {{ $incident->incident_type }}
            </span>
          </td>

          {{-- الخطورة (badge) --}}
          <td class="px-4 py-3.5 text-center">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $sStyle['bg'] }} {{ $sStyle['text'] }} {{ $sStyle['border'] }}">
              <span class="w-1.5 h-1.5 rounded-full {{ $sStyle['dot'] }}"></span>
              <i data-lucide="{{ $sStyle['icon'] }}" class="w-3 h-3"></i>
              {{ $sStyle['label'] }}
            </span>
          </td>

          {{-- IP (mono + copy button) --}}
          <td class="px-4 py-3.5">
            <div class="flex items-center gap-1.5">
              <span
                  class="inline-block font-mono text-xs font-bold bg-white/30 border border-slate-200 text-slate-700 px-2.5 py-1 rounded-lg"
                  dir="ltr"
              >
                {{ $incident->ip_address }}
              </span>
              <button
                  type="button"
                  onclick="copyToClipboard('{{ $incident->ip_address }}', this)"
                  class="w-7 h-7 rounded-lg bg-slate-100 border border-slate-200 text-slate-400 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all flex items-center justify-center flex-shrink-0"
                  title="نسخ IP"
              >
                <i data-lucide="copy" class="w-3 h-3"></i>
              </button>
            </div>
          </td>

          {{-- المسار (request_path) --}}
          <td class="px-4 py-3.5">
            <span
                class="block max-w-[150px] truncate text-xs font-mono font-bold text-slate-600"
                title="{{ $incident->request_path }}"
                dir="ltr"
            >
              {{ $incident->request_path ?: '—' }}
            </span>
          </td>

          {{-- الطريقة (HTTP method badge) --}}
          <td class="px-4 py-3.5 text-center">
            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black border {{ $mStyle['bg'] }} {{ $mStyle['text'] }} {{ $mStyle['border'] }}" dir="ltr">
              {{ strtoupper($incident->request_method) }}
            </span>
          </td>

          {{-- المستخدم --}}
          <td class="px-4 py-3.5">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-rose-500 to-rose-600 flex items-center justify-center text-white font-bold text-[11px] flex-shrink-0 shadow-sm shadow-rose-500/20 group-hover:scale-110 transition-transform duration-300">
                {{ $initial }}
              </div>
              <div class="min-w-0">
                <p class="font-bold text-slate-800 text-xs truncate max-w-[100px]">{{ $userName }}</p>
                <p class="text-[10px] text-slate-400 font-mono" dir="ltr">ID: {{ $incident->user_id ?? '—' }}</p>
              </div>
            </div>
          </td>

          {{-- المحاولات --}}
          <td class="px-4 py-3.5 text-center">
            <span class="inline-flex items-center justify-center min-w-[32px] px-2.5 py-1 rounded-lg text-[11px] font-black border
              {{ $incident->attempt_count > 5 ? 'bg-rose-50 text-rose-700 border-rose-200' : ($incident->attempt_count > 2 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-slate-100 text-slate-700 border-slate-200') }}"
            >
              {{ $incident->attempt_count }}
            </span>
          </td>

          {{-- الحظر (blocked status) --}}
          <td class="px-4 py-3.5 text-center">
            @if($incident->was_blocked)
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">
                <i data-lucide="lock" class="w-3 h-3"></i>
                محظور
              </span>
            @else
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 text-[10px] font-bold border border-amber-200">
                <i data-lucide="lock-open" class="w-3 h-3"></i>
                غير محظور
              </span>
            @endif
          </td>

          {{-- التاريخ --}}
          <td class="px-4 py-3.5">
            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700">
              <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0"></i>
              <span dir="ltr">{{ $incident->occurred_at ? $incident->occurred_at->format('Y-m-d H:i') : '—' }}</span>
            </div>
          </td>

          {{-- الإجراءات --}}
          <td class="px-4 py-3.5 text-center">
            <div class="flex items-center justify-center gap-1.5">
              {{-- عرض التفاصيل --}}
              <button
                  type="button"
                  onclick="viewSecurityIncident({{ $incident->id }})"
                  class="w-9 h-9 rounded-xl bg-rose-50 border border-rose-200 text-rose-600 hover:bg-rose-500 hover:text-white hover:border-rose-500 transition-all duration-300 flex items-center justify-center shadow-sm"
                  title="عرض التفاصيل"
                  aria-label="عرض تفاصيل الحادث"
              >
                <i data-lucide="eye" class="w-4 h-4"></i>
              </button>
              @if($canManage)
                @if($incident->was_blocked)
                  {{-- فك الحظر --}}
                  <button
                      type="button"
                      onclick="handleUnblockIp('{{ $incident->ip_address }}', this)"
                      class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-200 text-blue-600 hover:bg-blue-500 hover:text-white hover:border-blue-500 transition-all duration-300 flex items-center justify-center shadow-sm"
                      title="فك حظر {{ $incident->ip_address }}"
                      aria-label="فك حظر {{ $incident->ip_address }}"
                  >
                    <i data-lucide="lock-open" class="w-4 h-4"></i>
                  </button>
                @else
                  {{-- حظر --}}
                  <button
                      type="button"
                      onclick="handleBlockIp('{{ $incident->ip_address }}', this)"
                      class="w-9 h-9 rounded-xl bg-rose-50 border border-rose-200 text-rose-600 hover:bg-rose-500 hover:text-white hover:border-rose-500 transition-all duration-300 flex items-center justify-center shadow-sm"
                      title="حظر {{ $incident->ip_address }}"
                      aria-label="حظر {{ $incident->ip_address }}"
                  >
                    <i data-lucide="lock" class="w-4 h-4"></i>
                  </button>
                @endif
              @endif
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state
            icon="shield-check"
            :title="$hasActiveFilters ? 'لا توجد نتائج مطابقة' : 'لا توجد حوادث أمنية'"
            :message="$hasActiveFilters ? 'جرّب تعديل خيارات التصفية للعثور على الحوادث المطلوبة' : 'النظام آمن ولا توجد أي تهديدات مسجلة'"
            :colspan="11"
        />
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$incidents" />
    </x-slot>
  </x-data-table-card>
  </div>{{-- end #aracari-refreshable -->

</div>

{{-- ===== Detail Modal ===== --}}
<x-modal
    id="si-detail-modal"
    title="تفاصيل الحادث الأمني"
    variant="view"
    icon="shield-alert"
    maxWidth="max-w-2xl"
>
  {{-- Loading state --}}
  <div id="si-detail-loading" class="flex flex-col items-center justify-center py-12 space-y-3">
    <div class="w-12 h-12 rounded-full bg-rose-50 border-2 border-rose-200 flex items-center justify-center animate-pulse">
      <i data-lucide="loader-2" class="w-6 h-6 text-rose-500 animate-spin"></i>
    </div>
    <p class="text-sm font-bold text-slate-500">جارٍ تحميل التفاصيل...</p>
  </div>

  {{-- Content (hidden until loaded) --}}
  <div id="si-detail-content" class="hidden space-y-4">
    {{-- Top badges --}}
    <div class="flex items-center gap-3 flex-wrap">
      <span id="si-detail-severity-badge" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border">
        <span id="si-detail-severity-dot" class="w-1.5 h-1.5 rounded-full"></span>
        <span id="si-detail-severity-text"></span>
      </span>
      <span id="si-detail-method-badge" class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black border" dir="ltr"></span>
      <span id="si-detail-blocked-badge" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border"></span>
    </div>

    {{-- Info Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      {{-- نوع الحادث --}}
      <div class="bg-slate-50/50 border border-slate-200/70 rounded-xl p-3">
        <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
          <i data-lucide="zap" class="w-3.5 h-3.5"></i>
          نوع الحادث
        </div>
        <div class="font-bold text-sm text-slate-800" id="si-detail-type">—</div>
      </div>

      {{-- IP --}}
      <div class="bg-slate-50/50 border border-slate-200/70 rounded-xl p-3">
        <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
          <i data-lucide="globe" class="w-3.5 h-3.5"></i>
          عنوان IP
        </div>
        <div class="flex items-center gap-2">
          <span class="font-bold text-sm text-slate-800 font-mono" dir="ltr" id="si-detail-ip">—</span>
          <button
              type="button"
              onclick="copyToClipboard(document.getElementById('si-detail-ip').textContent, this)"
              class="w-7 h-7 rounded-lg bg-slate-100 border border-slate-200 text-slate-400 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all flex items-center justify-center flex-shrink-0"
              title="نسخ IP"
          >
            <i data-lucide="copy" class="w-3 h-3"></i>
          </button>
        </div>
      </div>

      {{-- المستخدم --}}
      <div class="bg-slate-50/50 border border-slate-200/70 rounded-xl p-3">
        <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
          <i data-lucide="user" class="w-3.5 h-3.5"></i>
          المستخدم
        </div>
        <div id="si-detail-user">—</div>
      </div>

      {{-- المحاولات --}}
      <div class="bg-slate-50/50 border border-slate-200/70 rounded-xl p-3">
        <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
          <i data-lucide="repeat" class="w-3.5 h-3.5"></i>
          عدد المحاولات
        </div>
        <div class="font-bold text-sm text-slate-800" id="si-detail-attempts">—</div>
      </div>

      {{-- المسار --}}
      <div class="bg-slate-50/50 border border-slate-200/70 rounded-xl p-3">
        <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
          <i data-lucide="route" class="w-3.5 h-3.5"></i>
          المسار
        </div>
        <div class="font-bold text-sm text-slate-800 font-mono break-all" dir="ltr" id="si-detail-path">—</div>
      </div>

      {{-- التاريخ --}}
      <div class="bg-slate-50/50 border border-slate-200/70 rounded-xl p-3">
        <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
          <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
          التاريخ والوقت
        </div>
        <div class="font-bold text-sm text-slate-800" dir="ltr" id="si-detail-date">—</div>
      </div>

      {{-- User Agent — full width --}}
      <div class="bg-slate-50/50 border border-slate-200/70 rounded-xl p-3 md:col-span-2">
        <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">
          <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
          الجهاز / User Agent
        </div>
        <div class="font-bold text-[11px] text-slate-600 leading-relaxed whitespace-pre-wrap break-all max-h-24 overflow-y-auto" dir="ltr" id="si-detail-useragent">—</div>
      </div>

      {{-- Request Inputs (JSON) — full width --}}
      <div class="bg-slate-50/50 border border-slate-200/70 rounded-xl p-3 md:col-span-2">
        <div class="flex items-center justify-between mb-1.5">
          <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
            <i data-lucide="code" class="w-3.5 h-3.5"></i>
            بيانات الطلب (Request Inputs)
          </div>
          <button
              type="button"
              onclick="copyJsonFromDetailModal()"
              class="px-3 py-1.5 bg-rose-50 border border-rose-200 text-rose-600 rounded-lg text-[10px] font-bold hover:bg-rose-100 transition-all flex items-center gap-1.5"
          >
            <i data-lucide="copy" class="w-3 h-3"></i>
            نسخ
          </button>
        </div>
        <pre id="si-detail-inputs" class="bg-slate-950 text-emerald-400 rounded-2xl p-4 text-[12px] font-mono leading-relaxed overflow-x-auto max-h-48 overflow-y-auto scrollbar-thin" dir="ltr"></pre>
      </div>
    </div>
  </div>
</x-modal>

@push('scripts')
<script>
  /**
   * Severity style mapping (mirrors PHP)
   */
  var severityStyles = {
    high:   { bg: 'bg-rose-50',    text: 'text-rose-700',    border: 'border-rose-200',    dot: 'bg-rose-500',    label: 'عالية' },
    medium: { bg: 'bg-amber-50',    text: 'text-amber-700',    border: 'border-amber-200',    dot: 'bg-amber-500',    label: 'متوسطة' },
    low:    { bg: 'bg-emerald-50',  text: 'text-emerald-700',  border: 'border-emerald-200',  dot: 'bg-emerald-500',  label: 'منخفضة' }
  };
  var defaultSeverityStyle = { bg: 'bg-slate-100', text: 'text-slate-600', border: 'border-slate-200', dot: 'bg-slate-400', label: 'غير معروف' };

  /**
   * HTTP method style mapping
   */
  var methodStyles = {
    GET:    { bg: 'bg-blue-50',    text: 'text-blue-700',    border: 'border-blue-200' },
    POST:   { bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200' },
    PUT:    { bg: 'bg-amber-50',   text: 'text-amber-700',   border: 'border-amber-200' },
    PATCH:  { bg: 'bg-amber-50',   text: 'text-amber-700',   border: 'border-amber-200' },
    DELETE: { bg: 'bg-rose-50',    text: 'text-rose-700',    border: 'border-rose-200' }
  };
  var defaultMethodStyle = { bg: 'bg-slate-100', text: 'text-slate-600', border: 'border-slate-200' };

  /**
   * View Security Incident Details
   * Opens the detail modal and populates it with incident data from the page.
   */
  function viewSecurityIncident(id) {
    // Find the incident data from the DOM (stored as data attributes on the row)
    // We'll find the row by the data-incident-id attribute
    var row = document.querySelector('tr[data-incident-id="' + id + '"]');

    // Show loading, hide content
    var loadingEl = document.getElementById('si-detail-loading');
    var contentEl = document.getElementById('si-detail-content');
    if (loadingEl) loadingEl.classList.remove('hidden');
    if (contentEl) contentEl.classList.add('hidden');

    // Open modal
    if (typeof window.openAracariModal === 'function') {
      window.openAracariModal('si-detail-modal');
    }

    // Read data from the row's data attributes
    if (row) {
      var data = JSON.parse(row.getAttribute('data-incident') || '{}');
      populateDetailModal(data);
    }
  }

  /**
   * Populate the detail modal with incident data
   */
  function populateDetailModal(d) {
    var s = severityStyles[d.severity] || defaultSeverityStyle;
    var m = methodStyles[(d.request_method || '').toUpperCase()] || defaultMethodStyle;

    // Severity badge
    var sevBadge = document.getElementById('si-detail-severity-badge');
    if (sevBadge) {
      sevBadge.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border ' + s.bg + ' ' + s.text + ' ' + s.border;
    }
    var sevDot = document.getElementById('si-detail-severity-dot');
    if (sevDot) sevDot.className = 'w-1.5 h-1.5 rounded-full ' + s.dot;
    var sevText = document.getElementById('si-detail-severity-text');
    if (sevText) sevText.textContent = s.label;

    // Method badge
    var methodBadge = document.getElementById('si-detail-method-badge');
    if (methodBadge) {
      methodBadge.className = 'inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black border ' + m.bg + ' ' + m.text + ' ' + m.border;
      methodBadge.textContent = (d.request_method || '—').toUpperCase();
    }

    // Blocked badge
    var blockedBadge = document.getElementById('si-detail-blocked-badge');
    if (blockedBadge) {
      if (d.was_blocked) {
        blockedBadge.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border bg-emerald-50 text-emerald-700 border-emerald-200';
        blockedBadge.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> محظور';
      } else {
        blockedBadge.className = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border bg-amber-50 text-amber-700 border-amber-200';
        blockedBadge.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg> غير محظور';
      }
    }

    // Type
    var typeEl = document.getElementById('si-detail-type');
    if (typeEl) typeEl.textContent = d.incident_type || '—';

    // IP
    var ipEl = document.getElementById('si-detail-ip');
    if (ipEl) ipEl.textContent = d.ip_address || '—';

    // User
    var userEl = document.getElementById('si-detail-user');
    if (userEl) {
      var userName = d.user_name || 'مجهول';
      var userId = d.user_id || '—';
      userEl.innerHTML = '<div class="flex items-center gap-2">' +
        '<div class="w-8 h-8 rounded-lg bg-gradient-to-br from-rose-500 to-rose-600 flex items-center justify-center text-white font-bold text-xs flex-shrink-0">' +
        userName.charAt(0) + '</div>' +
        '<div><p class="font-bold text-slate-800 text-sm">' + userName + '</p>' +
        '<p class="text-[10px] text-slate-400 font-mono" dir="ltr">ID: ' + userId + '</p></div></div>';
    }

    // Attempts
    var attemptsEl = document.getElementById('si-detail-attempts');
    if (attemptsEl) {
      var count = d.attempt_count || 0;
      var countClass = count > 5 ? 'bg-rose-50 text-rose-700 border-rose-200' : (count > 2 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-slate-100 text-slate-700 border-slate-200');
      attemptsEl.innerHTML = '<span class="inline-flex items-center justify-center min-w-[32px] px-2.5 py-1 rounded-lg text-[11px] font-black border ' + countClass + '">' + count + '</span>';
    }

    // Path
    var pathEl = document.getElementById('si-detail-path');
    if (pathEl) pathEl.textContent = d.request_path || '—';

    // Date
    var dateEl = document.getElementById('si-detail-date');
    if (dateEl) dateEl.textContent = d.occurred_at || '—';

    // User Agent
    var uaEl = document.getElementById('si-detail-useragent');
    if (uaEl) uaEl.textContent = d.user_agent || '—';

    // Request Inputs (JSON)
    var inputsEl = document.getElementById('si-detail-inputs');
    if (inputsEl) {
      var inputs = d.request_inputs;
      if (inputs && typeof inputs === 'object') {
        inputsEl.textContent = JSON.stringify(inputs, null, 2);
      } else {
        inputsEl.textContent = inputs ? String(inputs) : '—';
      }
    }

    // Hide loading, show content
    var loadingEl = document.getElementById('si-detail-loading');
    var contentEl = document.getElementById('si-detail-content');
    if (loadingEl) loadingEl.classList.add('hidden');
    if (contentEl) contentEl.classList.remove('hidden');

    // Re-init lucide
    if (typeof window.lucide !== 'undefined') {
      window.lucide.createIcons();
    }
  }

  /**
   * Copy IP to clipboard with visual feedback
   */
  function copyToClipboard(text, btn) {
    navigator.clipboard.writeText(text).then(function() {
      var originalHtml = btn.innerHTML;
      btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
      btn.classList.add('!bg-emerald-50', '!text-emerald-600', '!border-emerald-200');
      setTimeout(function() {
        btn.innerHTML = originalHtml;
        btn.classList.remove('!bg-emerald-50', '!text-emerald-600', '!border-emerald-200');
        if (typeof window.lucide !== 'undefined') window.lucide.createIcons();
      }, 1500);
    });
  }

  /**
   * Copy JSON from detail modal
   */
  function copyJsonFromDetailModal() {
    var content = document.getElementById('si-detail-inputs');
    if (!content) return;
    var text = content.textContent || '';
    navigator.clipboard.writeText(text).then(function() {
      showSiNotification('تم نسخ البيانات بنجاح', 'success');
    });
  }

  /**
   * Block IP via fetch — handles redirect response
   */
  function handleBlockIp(ipAddress, btn) {
    if (!confirm('هل أنت متأكد من حظر هذا الـ IP (' + ipAddress + ') يدوياً؟')) return;

    var originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>';

    var formData = new FormData();
    formData.append('ip_address', ipAddress);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

    fetch('{{ route("admin.security.block") }}', {
      method: 'POST',
      body: formData,
      redirect: 'manual'
    })
    .then(function(response) {
      // The controller returns redirect()->back() which is 302
      // We treat any non-exception response as success
      btn.disabled = false;
      btn.innerHTML = originalHtml;
      if (typeof window.lucide !== 'undefined') window.lucide.createIcons();

      showSiNotification('تم حظر الـ IP: ' + ipAddress + ' بنجاح', 'success');

      // Reload page after short delay to show updated data
      setTimeout(function() { window.location.reload(); }, 1200);
    })
    .catch(function(err) {
      console.error('Block error:', err);
      btn.disabled = false;
      btn.innerHTML = originalHtml;
      if (typeof window.lucide !== 'undefined') window.lucide.createIcons();

      showSiNotification('فشل حظر الـ IP. يرجى المحاولة مرة أخرى.', 'error');
    });
  }

  /**
   * Unblock IP via fetch — handles redirect response
   */
  function handleUnblockIp(ipAddress, btn) {
    var originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>';

    var formData = new FormData();
    formData.append('ip_address', ipAddress);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

    fetch('{{ route("admin.security.unblock") }}', {
      method: 'POST',
      body: formData,
      redirect: 'manual'
    })
    .then(function(response) {
      btn.disabled = false;
      btn.innerHTML = originalHtml;
      if (typeof window.lucide !== 'undefined') window.lucide.createIcons();

      showSiNotification('تم فك الحظر عن الـ IP: ' + ipAddress + ' بنجاح', 'success');

      // Reload page after short delay to show updated data
      setTimeout(function() { window.location.reload(); }, 1200);
    })
    .catch(function(err) {
      console.error('Unblock error:', err);
      btn.disabled = false;
      btn.innerHTML = originalHtml;
      if (typeof window.lucide !== 'undefined') window.lucide.createIcons();

      showSiNotification('فشل فك الحظر. يرجى المحاولة مرة أخرى.', 'error');
    });
  }

  /**
   * Toast notification
   */
  function showSiNotification(message, type) {
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