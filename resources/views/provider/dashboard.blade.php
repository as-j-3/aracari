@extends('layouts.provider')
@section('title', 'أركاري | لوحة تحكم المزود')
@section('header_title', 'لوحة التحكم')

@section('content')
<div class="space-y-6 font-cairo text-right" dir="rtl">

    {{-- ========================================================================
         HEADER CARD — decorative header with title and last-update timestamp
         ======================================================================== --}}
    <div class="relative overflow-hidden rounded-3xl bg-white/70 backdrop-blur-xl p-6 border border-slate-200/70 shadow-sm">
        {{-- Decorative blurred circles --}}
        <div class="absolute top-0 left-0 w-64 h-64 bg-teal-500/5 rounded-full blur-3xl -translate-y-1/2 -translate-x-1/2 pointer-events-none"></div>
        <div class="absolute bottom-0 right-0 w-48 h-48 bg-emerald-500/5 rounded-full blur-3xl translate-y-1/2 translate-x-1/2 pointer-events-none"></div>

        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                {{-- Icon badge --}}
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-teal-500 to-emerald-600 flex items-center justify-center shadow-lg shadow-teal-500/20">
                    <i data-lucide="bar-chart-3" class="w-7 h-7 text-white"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2">
                        لوحة تحكم المزود
                        <span class="text-[10px] font-bold text-teal-600 bg-teal-50 px-2.5 py-1 rounded-full border border-teal-200">
                            نظرة عامة
                        </span>
                    </h2>
                    <p class="text-slate-500 font-medium text-sm flex items-center gap-2 mt-1">
                        <i data-lucide="trending-up" class="w-4 h-4 text-slate-400"></i>
                        نظرة عامة على أداء نشاطك
                    </p>
                </div>
            </div>

            {{-- Last update badge --}}
            <div class="flex items-center gap-2 text-xs text-slate-500 bg-slate-50/70 px-3 py-2 rounded-xl border border-slate-200">
                <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-emerald-500"></i>
                <span>آخر تحديث: {{ now()->format('H:i') }}</span>
            </div>
        </div>
    </div>

    {{-- ========================================================================
         QUICK ACTION BUTTONS — 8 gradient buttons for main navigation
         ======================================================================== --}}
    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-3">
        @php
            $quickActions = [
                ['label' => 'رحلة جديدة', 'icon' => 'plus',        'gradient' => 'from-sky-500 to-sky-600',       'hover' => 'hover:shadow-sky-500/40',   'route' => 'admin.provider.branches.index'],
                ['label' => 'الرحلات',     'icon' => 'bus',         'gradient' => 'from-slate-600 to-slate-700',   'hover' => 'hover:shadow-slate-500/40',  'route' => 'admin.provider.branches.index'],
                ['label' => 'الحجوزات',    'icon' => 'ticket',      'gradient' => 'from-slate-600 to-slate-700',   'hover' => 'hover:shadow-slate-500/40',  'route' => 'admin.provider.branches.index'],
                ['label' => 'الموظفون',    'icon' => 'users',       'gradient' => 'from-cyan-500 to-cyan-600',     'hover' => 'hover:shadow-cyan-500/40',   'route' => 'admin.provider.staff.index'],
                ['label' => 'المركبات',    'icon' => 'car',         'gradient' => 'from-emerald-500 to-emerald-600', 'hover' => 'hover:shadow-emerald-500/40', 'route' => 'admin.provider.vehicles.index'],
                ['label' => 'الكوبونات',   'icon' => 'percent',     'gradient' => 'from-orange-500 to-orange-600', 'hover' => 'hover:shadow-orange-500/40', 'route' => 'admin.provider.coupons.index'],
                ['label' => 'الفروع',      'icon' => 'store',       'gradient' => 'from-pink-500 to-pink-600',     'hover' => 'hover:shadow-pink-500/40',   'route' => 'admin.provider.branches.index'],
                ['label' => 'الإيرادات',   'icon' => 'bar-chart-3', 'gradient' => 'from-teal-600 to-teal-700',     'hover' => 'hover:shadow-teal-500/40',   'route' => 'admin.provider.reports.revenue'],
            ];
        @endphp

        @foreach($quickActions as $action)
            <a href="{{ route($action['route']) }}"
               class="group relative overflow-hidden rounded-2xl bg-gradient-to-br {{ $action['gradient'] }} p-4 text-white shadow-lg {{ $action['hover'] }} hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 flex flex-col items-center gap-2">
                {{-- Hover glow --}}
                <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500"></div>
                <i data-lucide="{{ $action['icon'] }}" class="w-6 h-6 relative z-10 group-hover:scale-110 transition-transform duration-300"></i>
                <span class="text-xs font-bold relative z-10 text-center">{{ $action['label'] }}</span>
            </a>
        @endforeach
    </div>

    {{-- ========================================================================
         DATE FILTER FORM — start_date / end_date with GET method
         ======================================================================== --}}
    <div class="bg-white/70 backdrop-blur-xl border border-slate-200/70 rounded-3xl p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.provider.prodashboard.index') }}" class="flex flex-wrap items-center gap-3">
            {{-- Start date --}}
            <div class="flex items-center gap-2 flex-1 min-w-[200px]">
                <label for="start_date" class="text-xs font-bold text-slate-600 flex items-center gap-1.5 whitespace-nowrap">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-teal-500"></i>
                    من:
                </label>
                <input
                    type="date"
                    name="start_date"
                    id="start_date"
                    value="{{ $startDate->format('Y-m-d') }}"
                    class="aracari-input h-11"
                />
            </div>

            {{-- End date --}}
            <div class="flex items-center gap-2 flex-1 min-w-[200px]">
                <label for="end_date" class="text-xs font-bold text-slate-600 flex items-center gap-1.5 whitespace-nowrap">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-teal-500"></i>
                    إلى:
                </label>
                <input
                    type="date"
                    name="end_date"
                    id="end_date"
                    value="{{ $endDate->format('Y-m-d') }}"
                    class="aracari-input h-11"
                />
            </div>

            {{-- Filter button --}}
            <button
                type="submit"
                class="h-11 px-6 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all flex items-center gap-2">
                <i data-lucide="filter" class="w-4 h-4"></i>
                تصفية
            </button>

            {{-- Reset button --}}
            <a
                href="{{ route('admin.provider.prodashboard.index') }}"
                class="h-11 px-6 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-sm transition-all flex items-center gap-2">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                إعادة تعيين
            </a>
        </form>
    </div>

    {{-- ========================================================================
         SUMMARY STAT CARDS (×4) — revenue / bookings / active / completed trips
         ======================================================================== --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">

        {{-- 1. Total Revenue — dark slate card --}}
        <div class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 to-slate-800 p-6 border border-white/5 hover:border-emerald-500/20 transition-all duration-500 hover:shadow-2xl hover:shadow-emerald-500/10 hover:-translate-y-1">
            <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-emerald-500/10 to-transparent rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10 flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">إجمالي الإيرادات</p>
                    <h3 class="text-3xl font-extrabold text-white mt-1.5 flex items-baseline gap-1">
                        {{ number_format($totalRevenue, 0, '.', ',') }}
                        <span class="text-sm text-slate-400 font-bold">ر.س</span>
                    </h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/20 group-hover:shadow-emerald-500/40 group-hover:scale-110 transition-all duration-300">
                    <i data-lucide="wallet" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        {{-- 2. Total Bookings — dark blue card --}}
        <div class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-900 to-blue-800 p-6 border border-white/5 hover:border-blue-500/20 transition-all duration-500 hover:shadow-2xl hover:shadow-blue-500/10 hover:-translate-y-1">
            <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-blue-500/10 to-transparent rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10 flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold text-blue-300/70 uppercase tracking-wider">إجمالي الحجوزات</p>
                    <h3 class="text-3xl font-extrabold text-white mt-1.5">{{ number_format($totalBookings) }}</h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/20 group-hover:shadow-blue-500/40 group-hover:scale-110 transition-all duration-300">
                    <i data-lucide="ticket" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        {{-- 3. Active Trips — dark purple card --}}
        <div class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-purple-900 to-purple-800 p-6 border border-white/5 hover:border-purple-500/20 transition-all duration-500 hover:shadow-2xl hover:shadow-purple-500/10 hover:-translate-y-1">
            <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-purple-500/10 to-transparent rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10 flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold text-purple-300/70 uppercase tracking-wider">الرحلات النشطة</p>
                    <h3 class="text-3xl font-extrabold text-white mt-1.5">{{ number_format($activeTrips) }}</h3>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="inline-flex items-center gap-1 text-purple-400 text-xs font-bold bg-purple-400/10 px-2.5 py-1 rounded-full border border-purple-400/20">
                            <i data-lucide="bus" class="w-3 h-3"></i>
                            قيد التشغيل
                        </span>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-lg shadow-purple-500/20 group-hover:shadow-purple-500/40 group-hover:scale-110 transition-all duration-300">
                    <i data-lucide="bus" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        {{-- 4. Completed Trips — dark amber card --}}
        <div class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-amber-900 to-amber-800 p-6 border border-white/5 hover:border-amber-500/20 transition-all duration-500 hover:shadow-2xl hover:shadow-amber-500/10 hover:-translate-y-1">
            <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-amber-500/10 to-transparent rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10 flex items-start justify-between">
                <div>
                    <p class="text-xs font-bold text-amber-300/70 uppercase tracking-wider">الرحلات المكتملة</p>
                    <h3 class="text-3xl font-extrabold text-white mt-1.5">{{ number_format($completedTrips) }}</h3>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="inline-flex items-center gap-1 text-amber-400 text-xs font-bold bg-amber-400/10 px-2.5 py-1 rounded-full border border-amber-400/20">
                            <i data-lucide="check-circle" class="w-3 h-3"></i>
                            مكتملة بنجاح
                        </span>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/20 group-hover:shadow-amber-500/40 group-hover:scale-110 transition-all duration-300">
                    <i data-lucide="check-circle" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================================
         MONTHLY REVENUE CHART — CSS bar chart (no recharts in Blade)
         ======================================================================== --}}
    <div class="bg-white/70 backdrop-blur-xl border border-slate-200/70 rounded-3xl p-6 shadow-sm">
        {{-- Chart header --}}
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-md">
                    <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
                </div>
                تقرير الإيرادات الشهري
                <span class="text-xs font-bold text-slate-400 bg-slate-100 px-2.5 py-1 rounded-full">{{ now()->year }}</span>
            </h3>
            <span class="text-xs text-slate-400">بالريال السعودي (ر.س)</span>
        </div>

        @php
            // Arabic month names for display
            $arabicMonths = [
                '01' => 'يناير', '02' => 'فبراير', '03' => 'مارس', '04' => 'أبريل',
                '05' => 'مايو', '06' => 'يونيو', '07' => 'يوليو', '08' => 'أغسطس',
                '09' => 'سبتمبر', '10' => 'أكتوبر', '11' => 'نوفمبر', '12' => 'ديسمبر',
            ];

            // Build chart data array with normalized month names
            $chartItems = [];
            foreach ($monthlyRevenue as $item) {
                $monthKey = substr($item['month'], 5, 2); // '01'..'12'
                $chartItems[] = [
                    'label'    => $arabicMonths[$monthKey] ?? $monthKey,
                    'revenue'  => (float) ($item['total_revenue'] ?? 0),
                    'bookings' => (int) ($item['total_bookings'] ?? 0),
                ];
            }

            // Calculate max for bar height percentages (add 15% headroom)
            $maxRevenue = collect($chartItems)->max('revenue') ?: 1;
            $chartCeil  = $maxRevenue * 1.15;
        @endphp

        @if(count($chartItems) > 0)
            {{-- Chart wrapper --}}
            <div class="relative" dir="ltr">
                {{-- Y-axis labels + bars area --}}
                <div class="flex gap-0">
                    {{-- Y-axis labels column --}}
                    <div class="flex flex-col justify-between text-[10px] text-slate-400 font-bold w-12 flex-shrink-0 py-1">
                        <span>{{ number_format($chartCeil, 0, '.', ',') }}</span>
                        <span>{{ number_format($chartCeil / 2, 0, '.', ',') }}</span>
                        <span>0</span>
                    </div>

                    {{-- Chart body --}}
                    <div class="flex-1 relative">
                        {{-- Horizontal grid lines --}}
                        <div class="absolute inset-0 flex flex-col justify-between pointer-events-none py-1">
                            <div class="border-b border-dashed border-slate-200"></div>
                            <div class="border-b border-dashed border-slate-200"></div>
                            <div class="border-b border-slate-100"></div>
                        </div>

                        {{-- Bars container --}}
                        <div class="relative flex items-end gap-1 sm:gap-2 h-56 px-1 pb-1">
                            @foreach($chartItems as $item)
                                @php
                                    $barHeight = max(2, ($item['revenue'] / $chartCeil) * 100);
                                @endphp
                                <div class="flex-1 flex flex-col items-center gap-1 group min-w-0 relative z-10">
                                    {{-- Tooltip (visible on hover) --}}
                                    <div class="absolute -top-16 left-1/2 -translate-x-1/2 bg-slate-800 text-white text-[11px] font-bold px-3 py-2 rounded-xl shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-20">
                                        {{ number_format($item['revenue'], 0, '.', ',') }} ر.س
                                        <div class="text-[9px] text-slate-300 mt-0.5">{{ $item['bookings'] }} حجز</div>
                                        {{-- Tooltip arrow --}}
                                        <div class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-slate-800 rotate-45"></div>
                                    </div>

                                    {{-- Bar --}}
                                    <div
                                        class="w-full max-w-[48px] min-w-[16px] rounded-t-lg bg-gradient-to-t from-teal-600 to-emerald-400 transition-all duration-500 hover:from-teal-500 hover:to-emerald-300 cursor-pointer relative overflow-hidden group-hover:shadow-lg group-hover:shadow-emerald-500/30"
                                        style="height: {{ $barHeight }}%; min-height: 4px;"
                                    >
                                        {{-- Subtle shine overlay --}}
                                        <div class="absolute inset-0 bg-gradient-to-l from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- X-axis line --}}
                        <div class="border-t border-slate-200 mt-1"></div>

                        {{-- X-axis labels (month names) --}}
                        <div class="flex gap-1 sm:gap-2 px-1 mt-2">
                            @foreach($chartItems as $item)
                                <div class="flex-1 text-center text-[10px] sm:text-[11px] font-bold text-slate-500 truncate">
                                    {{ $item['label'] }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @else
            {{-- Empty state when no monthly data --}}
            <div class="flex flex-col items-center justify-center py-16 text-slate-400">
                <i data-lucide="bar-chart-3" class="w-12 h-12 mb-3 opacity-40"></i>
                <p class="text-sm font-bold">لا توجد بيانات إيرادات شهرية للعرض</p>
            </div>
        @endif
    </div>

    {{-- ========================================================================
         TRIP PERFORMANCE TABLE
         ======================================================================== --}}
    <div class="bg-white/70 backdrop-blur-xl border border-slate-200/70 rounded-3xl overflow-hidden shadow-sm">
        {{-- Table header --}}
        <div class="p-5 border-b border-slate-200/70 bg-white/40 backdrop-blur flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center text-white shadow-md">
                    <i data-lucide="trending-up" class="w-4 h-4"></i>
                </div>
                أداء الرحلات
            </h3>
            <span class="text-[11px] text-slate-400 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200">
                ({{ $startDate->translatedFormat('j F، Y') }} ← {{ $endDate->translatedFormat('j F، Y') }})
            </span>
        </div>

        @if(count($tripPerformance) > 0)

            <div class="overflow-x-auto min-w-0">
                <table class="w-full min-w-[640px]">
                    <thead class="bg-slate-50/70 border-b border-slate-200/70">
                        <tr>
                            <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الرحلة</th>
                            <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">تاريخ المغادرة</th>
                            <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">المقاعد المحجوزة</th>
                            <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">نسبة الامتلاء</th>
                            <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإيرادات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($tripPerformance as $trip)
                            @php
                                // Determine occupancy bar color
                                $occRate = (float) ($trip->occupancy ?? $trip->occupancy_rate ?? 0);
                                $occColor = $occRate >= 80 ? 'bg-emerald-500' : ($occRate >= 50 ? 'bg-amber-500' : 'bg-rose-500');

                                // Trip route display
                                $departure = $trip->departure_city ?? $trip->departureCity ?? '';
                                $dest      = $trip->destination_city ?? $trip->destinationCity ?? '';
                                $depDate   = $trip->departure_time ?? $trip->departureTime ?? null;
                                if ($depDate instanceof \Carbon\Carbon) {
                                    $depDateStr = $depDate->translatedFormat('j F، Y');
                                } else {
                                    $depDateStr = $depDate ?? '—';
                                }
                                $booked = (int) ($trip->bookings_count ?? $trip->bookedSeats ?? $trip->bookings ?? 0);
                                $total  = (int) ($trip->total_seats ?? $trip->totalSeats ?? 0);
                                $revenue = (float) ($trip->revenue ?? $trip->total_revenue ?? 0);
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                {{-- Trip route --}}
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-1.5 text-sm font-bold text-slate-800">
                                        <span>{{ $departure }}</span>
                                        <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-slate-400"></i>
                                        <span>{{ $dest }}</span>
                                    </div>
                                </td>

                                {{-- Departure date --}}
                                <td class="px-6 py-3.5 text-xs text-slate-600 font-medium" dir="ltr">
                                    {{ $depDateStr }}
                                </td>

                                {{-- Booked seats --}}
                                <td class="px-6 py-3.5 text-center">
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-slate-700">
                                        <span class="text-emerald-600">{{ $booked }}</span>
                                        <span class="text-slate-400">/ {{ $total }}</span>
                                    </span>
                                </td>

                                {{-- Occupancy bar --}}
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-slate-200 rounded-full h-2 overflow-hidden min-w-[80px]">
                                            <div class="{{ $occColor }} h-2 rounded-full transition-all duration-700"
                                                 style="width: {{ min($occRate, 100) }}%"></div>
                                        </div>
                                        <span class="text-xs font-bold text-slate-600 w-12 text-left">
                                            {{ number_format($occRate, 1) }}%
                                        </span>
                                    </div>
                                </td>

                                {{-- Revenue --}}
                                <td class="px-6 py-3.5">
                                    <span class="text-sm font-bold text-emerald-600 flex items-center gap-1">
                                        {{ number_format($revenue, 0, '.', ',') }}
                                        <span class="text-[10px] text-slate-400 font-normal">ر.س</span>
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-16 text-slate-400">
                <i data-lucide="bus" class="w-12 h-12 mb-3 opacity-40"></i>
                <p class="text-sm font-bold">لا توجد بيانات أداء رحلات في هذه الفترة</p>
            </div>
        @endif
    </div>

    {{-- ========================================================================
         RECENT BOOKINGS TABLE
         ======================================================================== --}}
    <div class="bg-white/70 backdrop-blur-xl border border-slate-200/70 rounded-3xl overflow-hidden shadow-sm">
        {{-- Table header --}}
        <div class="p-5 border-b border-slate-200/70 bg-white/40 backdrop-blur flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white shadow-md">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </div>
                آخر الحجوزات
            </h3>
            @if(Route::has('admin.provider.branches.index'))
                <a href="{{ route('admin.provider.branches.index') }}" class="text-xs font-bold text-teal-600 hover:text-teal-700 flex items-center gap-1">
                    عرض الكل
                    <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>
                </a>
            @endif
        </div>

        @if($recentBookings->count() > 0)
            <div class="overflow-x-auto min-w-0">
                <table class="w-full min-w-[640px]">
                    <thead class="bg-slate-50/70 border-b border-slate-200/70">
                        <tr>
                            <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">رقم الحجز</th>
                            <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المستخدم</th>
                            <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الرحلة</th>
                            <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
                            <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recentBookings as $booking)
                            @php
                                // Status badge styling
                                $statusName = $booking->status?->name ?? 'غير محدد';
                                $statusKey  = $booking->status?->key ?? '';
                                if ($statusKey === 'confirmed' || $statusKey === 'completed') {
                                    $statusClasses = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                } elseif ($statusKey === 'cancelled' || $statusKey === 'rejected') {
                                    $statusClasses = 'bg-rose-50 text-rose-700 border-rose-200';
                                } else {
                                    $statusClasses = 'bg-amber-50 text-amber-700 border-amber-200';
                                }

                                // User info
                                $userName = $booking->user?->name ?? 'مستخدم';
                                $userInitial = mb_substr($userName, 0, 1);

                                // Trip route from first reservation
                                $tripRoute = '—';
                                $firstRes = $booking->tripReservations?->first();
                                if ($firstRes?->trip) {
                                    $dep = $firstRes->trip->departureCity?->name ?? '';
                                    $dst = $firstRes->trip->destinationCity?->name ?? '';
                                    if ($dep && $dst) {
                                        $tripRoute = $dep . ' ← ' . $dst;
                                    } elseif ($firstRes->trip->name) {
                                        $tripRoute = $firstRes->trip->name;
                                    }
                                }

                                // Booking amount
                                $amount = (float) ($booking->total_amount ?? $booking->amount ?? $booking->total ?? 0);
                                $bookingRef = $booking->uuid ?? $booking->id ?? '—';
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                {{-- Booking # --}}
                                <td class="px-6 py-3.5">
                                    <span class="text-xs font-mono font-bold text-slate-800">#{{ $bookingRef }}</span>
                                </td>

                                {{-- User --}}
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center text-white font-bold text-xs flex-shrink-0">
                                            {{ $userInitial }}
                                        </div>
                                        <span class="text-xs font-bold text-slate-800">{{ $userName }}</span>
                                    </div>
                                </td>

                                {{-- Trip route --}}
                                <td class="px-6 py-3.5">
                                    <span class="text-xs text-slate-600 font-medium">{{ $tripRoute }}</span>
                                </td>

                                {{-- Status badge --}}
                                <td class="px-6 py-3.5 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $statusClasses }}">
                                        {{ $statusName }}
                                    </span>
                                </td>

                                {{-- Amount --}}
                                <td class="px-6 py-3.5">
                                    <span class="text-sm font-bold text-emerald-600 flex items-center gap-1">
                                        {{ number_format($amount, 0, '.', ',') }}
                                        <span class="text-[10px] text-slate-400 font-normal">ر.س</span>
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-16 text-slate-400">
                <i data-lucide="ticket" class="w-12 h-12 mb-3 opacity-40"></i>
                <p class="text-sm font-bold">لا توجد حجوزات حديثة</p>
            </div>
        @endif
    </div>

</div>
@endsection
