@extends('layouts.provider')

@section('title', 'أركاري | تقرير الإيرادات')
@section('header_title', 'تقرير الإيرادات')

@section('content')
@php
    $maxRevenue = collect($monthlyRevenue)->max('total_revenue') ?: 1;

    // Arabic month names
    $arabicMonths = [
        'January'   => 'يناير',
        'February'  => 'فبراير',
        'March'     => 'مارس',
        'April'     => 'أبريل',
        'May'       => 'مايو',
        'June'      => 'يونيو',
        'July'      => 'يوليو',
        'August'    => 'أغسطس',
        'September' => 'سبتمبر',
        'October'   => 'أكتوبر',
        'November'  => 'نوفمبر',
        'December'  => 'ديسمبر',
    ];

    // Chart bar colors (matching Aracari palette)
    $chartColors = [
        'from-teal-400 to-teal-500',
        'from-emerald-400 to-emerald-500',
        'from-green-400 to-green-500',
        'from-lime-400 to-lime-500',
        'from-amber-400 to-amber-500',
        'from-orange-400 to-orange-500',
        'from-rose-400 to-rose-500',
        'from-pink-400 to-pink-500',
        'from-fuchsia-400 to-fuchsia-500',
        'from-purple-400 to-purple-500',
        'from-violet-400 to-violet-500',
        'from-blue-400 to-blue-500',
    ];
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

    {{-- ===== Page Header ===== --}}
    <x-page-header
        icon="wallet"
        title="تقرير الإيرادات"
        description="تحليل الإيرادات الشهرية وتفاصيل الأداء المالي"
        descriptionIcon="trending-up"
    >
        <x-slot name="action">
            <a href="{{ route('admin.provider.prodashboard.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/70 border border-slate-200 rounded-xl text-slate-600 font-bold text-sm hover:bg-white hover:shadow-lg transition-all duration-300 group">
                <i data-lucide="arrow-right" class="w-4 h-4 group-hover:-translate-x-1 transition-transform"></i>
                <span class="hidden sm:inline">العودة للوحة التحكم</span>
            </a>
        </x-slot>
    </x-page-header>

    {{-- ===== Year Filter ===== --}}
    <div class="bg-white/70 backdrop-blur-xl border border-slate-200/70 rounded-3xl p-4 sm:p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.provider.reports.revenue') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2 flex-1 min-w-[180px]">
                <label class="text-xs font-bold text-slate-600 flex items-center gap-1.5 whitespace-nowrap">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-teal-500"></i>
                    السنة:
                </label>
                <select name="year"
                    class="flex-1 h-11 bg-slate-50/70 border-2 border-slate-200 rounded-xl py-2 px-3 text-slate-800 font-bold focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all outline-none appearance-none cursor-pointer">
                    @for($y = now()->year + 1; $y >= 2020; $y--)
                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <button
                type="submit"
                class="h-11 px-6 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all flex items-center gap-2">
                <i data-lucide="filter" class="w-4 h-4"></i>
                عرض التقرير
            </button>
        </form>
    </div>

    {{-- ===== Summary Stat Cards (3) ===== --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 lg:gap-6">
        {{-- إجمالي الإيرادات --}}
        <x-stat-card
            :value="number_format($summary['total_revenue'], 2)"
            label="إجمالي الإيرادات"
            icon="wallet"
            variant="emerald"
            caption="ر.س"
        />

        {{-- إجمالي الحجوزات --}}
        <x-stat-card
            :value="$summary['total_bookings']"
            label="إجمالي الحجوزات"
            icon="ticket"
            variant="blue"
            caption="حجز مؤكد"
        />

        {{-- متوسط قيمة الحجز --}}
        <x-stat-card
            :value="number_format($summary['average_booking_value'], 2)"
            label="متوسط قيمة الحجز"
            icon="trending-up"
            variant="purple"
            caption="ر.س"
        />
    </div>

    {{-- ===== CSS Bar Chart ===== --}}
    <div class="bg-white/70 backdrop-blur-xl border border-slate-200/70 rounded-3xl p-4 sm:p-6 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-base sm:text-lg font-bold text-slate-800 flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-md flex-shrink-0">
                    <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
                </div>
                <span class="truncate">تقرير الإيرادات الشهري - {{ $year }}</span>
            </h3>
            <span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-3 py-1 rounded-full border border-slate-200">
                12 شهر
            </span>
        </div>

        {{-- Chart Bars --}}
        <div class="flex items-end gap-1.5 sm:gap-2 h-52 sm:h-72">
            @foreach($monthlyRevenue as $index => $month)
                @php
                    $barHeight = $month['total_revenue'] > 0
                        ? max(4, ($month['total_revenue'] / $maxRevenue) * 100)
                        : 2;
                    $colorClass = $chartColors[$index % 12];
                    $arMonth = $arabicMonths[$month['month_name']] ?? $month['month_name'];
                @endphp
                <div class="flex-1 flex flex-col items-center gap-2 group">
                    {{-- Tooltip --}}
                    <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 bg-slate-800 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-lg whitespace-nowrap pointer-events-none z-10 mb-1 shadow-lg">
                        {{ $arMonth }}: {{ number_format($month['total_revenue'], 0) }} ر.س
                    </div>
                    {{-- Bar --}}
                    <div class="w-full rounded-t-lg bg-gradient-to-t {{ $colorClass }} transition-all duration-700 ease-out hover:opacity-90 min-h-[4px]"
                         style="height: {{ $barHeight }}%;"
                         title="{{ number_format($month['total_revenue'], 2) }} ر.س">
                    </div>
                    {{-- Label --}}
                    <span class="text-[9px] sm:text-[10px] font-bold text-slate-500 whitespace-nowrap leading-tight">
                        {{ mb_substr($arMonth, 0, 3) }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ===== Monthly Revenue Data Table ===== --}}
    <x-data-table-card :searchable="false">
        <x-slot name="thead">
            <tr>
                <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الشهر</th>
                <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">عدد الحجوزات</th>
                <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">إجمالي الإيرادات</th>
                <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">متوسط قيمة الحجز</th>
                <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الأداء</th>
            </tr>
        </x-slot>
        <x-slot name="rows">
            @foreach($monthlyRevenue as $index => $month)
                @php
                    $arMonth = $arabicMonths[$month['month_name']] ?? $month['month_name'];
                    $perfPercent = $maxRevenue > 0 ? ($month['total_revenue'] / $maxRevenue) * 100 : 0;
                    $colorClass = $chartColors[$index % 12];
                @endphp
                <tr class="hover:bg-slate-50/50 transition-colors {{ $month['total_bookings'] == 0 ? 'opacity-50' : '' }}">
                    <td class="px-6 py-3.5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-2.5 h-2.5 rounded-full bg-gradient-to-br {{ $colorClass }} flex-shrink-0"></div>
                            <span class="text-sm font-bold text-slate-800">{{ $arMonth }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-3.5">
                        <span class="text-sm font-bold text-slate-700">{{ number_format($month['total_bookings']) }}</span>
                    </td>
                    <td class="px-6 py-3.5">
                        <span class="text-sm font-bold text-emerald-600 flex items-center gap-1">
                            {{ number_format($month['total_revenue'], 2) }}
                            <span class="text-[10px] text-slate-400 font-normal">ر.س</span>
                        </span>
                    </td>
                    <td class="px-6 py-3.5">
                        <span class="text-sm text-slate-600 font-medium">
                            {{ number_format($month['average_booking_value'], 2) }} ر.س
                        </span>
                    </td>
                    <td class="px-6 py-3.5">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-slate-100 rounded-full h-2 overflow-hidden min-w-[60px]">
                                <div class="bg-gradient-to-l {{ $colorClass }} h-2 rounded-full transition-all duration-700"
                                     style="width: {{ $perfPercent }}%;"></div>
                            </div>
                            <span class="text-[10px] font-bold text-slate-500 w-10 text-left">{{ number_format($perfPercent, 0) }}%</span>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-slot>
    </x-data-table-card>

</div>
@endsection
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        lucide.createIcons();
    });
</script>
@endpush
