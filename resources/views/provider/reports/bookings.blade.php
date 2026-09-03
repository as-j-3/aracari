@extends('layouts.provider')

@section('title', 'أركاري | تقرير الحجوزات')
@section('header_title', 'تقرير الحجوزات')

@section('content')
@php
    $stats = $bookingStats;
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

    {{-- ===== Page Header ===== --}}
    <x-page-header
        icon="ticket"
        title="تقرير الحجوزات"
        description="تحليل الحجوزات في الفترة من {{ $startDate->format('d/m/Y') }} إلى {{ $endDate->format('d/m/Y') }}"
        descriptionIcon="calendar"
    >
        <x-slot name="action">
            <a href="{{ route('admin.provider.dashboard') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/70 border border-slate-200 rounded-xl text-slate-600 font-bold text-sm hover:bg-white hover:shadow-lg transition-all duration-300 group">
                <i data-lucide="arrow-right" class="w-4 h-4 group-hover:-translate-x-1 transition-transform"></i>
                <span class="hidden sm:inline">العودة للوحة التحكم</span>
            </a>
        </x-slot>
    </x-page-header>

    {{-- ===== Date Filter ===== --}}
    <div class="bg-white/70 backdrop-blur-xl border border-slate-200/70 rounded-3xl p-4 sm:p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.provider.reports.bookings') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2 flex-1 min-w-[140px]">
                <label class="text-xs font-bold text-slate-600 flex items-center gap-1.5 whitespace-nowrap">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-teal-500"></i>
                    من:
                </label>
                <input
                    type="date"
                    name="start_date"
                    value="{{ $startDate->format('Y-m-d') }}"
                    class="flex-1 h-11 bg-slate-50/70 border-2 border-slate-200 rounded-xl py-2 px-3 text-slate-800 font-bold focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all outline-none"
                />
            </div>
            <div class="flex items-center gap-2 flex-1 min-w-[140px]">
                <label class="text-xs font-bold text-slate-600 flex items-center gap-1.5 whitespace-nowrap">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-teal-500"></i>
                    إلى:
                </label>
                <input
                    type="date"
                    name="end_date"
                    value="{{ $endDate->format('Y-m-d') }}"
                    class="flex-1 h-11 bg-slate-50/70 border-2 border-slate-200 rounded-xl py-2 px-3 text-slate-800 font-bold focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all outline-none"
                />
            </div>
            <button
                type="submit"
                class="h-11 px-6 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all flex items-center gap-2">
                <i data-lucide="filter" class="w-4 h-4"></i>
                تصفية
            </button>
        </form>
    </div>

    {{-- ===== Summary Stat Cards (3) ===== --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 lg:gap-6">
        {{-- إجمالي الحجوزات --}}
        <x-stat-card
            :value="$stats['total_bookings']"
            label="إجمالي الحجوزات"
            icon="ticket"
            variant="blue"
            :badge="number_format($stats['active_bookings']) . ' نشط'"
            badgeIcon="check-circle"
        />

        {{-- حجوزات مؤكدة --}}
        <x-stat-card
            :value="$stats['confirmed']"
            label="حجوزات مؤكدة"
            icon="check-circle"
            variant="emerald"
            :badge="number_format($stats['confirmation_rate'], 1) . '%'"
            badgeIcon="percent"
        />

        {{-- حجوزات ملغاة --}}
        <x-stat-card
            :value="$stats['cancelled']"
            label="حجوزات ملغاة"
            icon="x-circle"
            variant="rose"
            :badge="$stats['total_bookings'] > 0 ? number_format(($stats['cancelled'] / $stats['total_bookings']) * 100, 1) . '%' : '0%'"
            badgeIcon="percent"
        />
    </div>

    {{-- ===== Financial Details Panel ===== --}}
    <div class="bg-white/70 backdrop-blur-xl border border-slate-200/70 rounded-3xl p-4 sm:p-6 shadow-sm">
        <div class="flex items-center gap-2 mb-5">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center text-white shadow-md flex-shrink-0">
                <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
            </div>
            <h3 class="text-base sm:text-lg font-bold text-slate-800">التفاصيل المالية</h3>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- إجمالي الإيرادات --}}
            <div class="bg-emerald-50/60 border border-emerald-200/50 rounded-2xl p-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                        <i data-lucide="wallet" class="w-4 h-4 text-emerald-600"></i>
                    </div>
                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">إجمالي الإيرادات</span>
                </div>
                <p class="text-lg font-black text-emerald-700">{{ number_format($stats['total_revenue'], 2) }} <span class="text-xs font-bold text-emerald-500">ر.س</span></p>
            </div>

            {{-- صافي الأرباح --}}
            <div class="bg-teal-50/60 border border-teal-200/50 rounded-2xl p-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-teal-100 flex items-center justify-center">
                        <i data-lucide="trending-up" class="w-4 h-4 text-teal-600"></i>
                    </div>
                    <span class="text-[10px] font-bold text-teal-600 uppercase tracking-wider">صافي الأرباح</span>
                </div>
                <p class="text-lg font-black text-teal-700">{{ number_format($stats['net_profit'], 2) }} <span class="text-xs font-bold text-teal-500">ر.س</span></p>
            </div>

            {{-- متوسط قيمة الحجز --}}
            <div class="bg-purple-50/60 border border-purple-200/50 rounded-2xl p-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                        <i data-lucide="percent" class="w-4 h-4 text-purple-600"></i>
                    </div>
                    <span class="text-[10px] font-bold text-purple-600 uppercase tracking-wider">متوسط قيمة الحجز</span>
                </div>
                <p class="text-lg font-black text-purple-700">{{ number_format($stats['average_booking_value'], 2) }} <span class="text-xs font-bold text-purple-500">ر.س</span></p>
            </div>

            {{-- نسبة التأكيد --}}
            <div class="bg-blue-50/60 border border-blue-200/50 rounded-2xl p-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-4 h-4 text-blue-600"></i>
                    </div>
                    <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wider">نسبة التأكيد</span>
                </div>
                <div class="flex items-center gap-3">
                    <p class="text-lg font-black text-blue-700">{{ number_format($stats['confirmation_rate'], 1) }}%</p>
                    <div class="flex-1 bg-blue-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-gradient-to-l from-blue-500 to-blue-400 h-2 rounded-full transition-all duration-700"
                             style="width: {{ $stats['confirmation_rate'] }}%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Bookings Breakdown Table ===== --}}
    <x-data-table-card :searchable="false">
        <x-slot name="thead">
            <tr>
                <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المؤشر</th>
                <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">القيمة</th>
                <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">النسبة</th>
            </tr>
        </x-slot>
        <x-slot name="rows">
            {{-- إجمالي الحجوزات --}}
            <tr class="hover:bg-slate-50/50 transition-colors">
                <td class="px-6 py-3.5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-2.5 h-2.5 rounded-full bg-blue-500 flex-shrink-0"></div>
                        <span class="text-sm font-bold text-slate-800">إجمالي الحجوزات</span>
                    </div>
                </td>
                <td class="px-6 py-3.5">
                    <span class="text-sm font-bold text-slate-700">{{ number_format($stats['total_bookings']) }}</span>
                </td>
                <td class="px-6 py-3.5 text-center">
                    <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full">100%</span>
                </td>
            </tr>

            {{-- حجوزات مؤكدة --}}
            <tr class="hover:bg-slate-50/50 transition-colors">
                <td class="px-6 py-3.5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 flex-shrink-0"></div>
                        <span class="text-sm font-bold text-slate-800">حجوزات مؤكدة</span>
                    </div>
                </td>
                <td class="px-6 py-3.5">
                    <span class="text-sm font-bold text-emerald-600">{{ number_format($stats['confirmed']) }}</span>
                </td>
                <td class="px-6 py-3.5 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <div class="w-16 bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="bg-emerald-500 h-2 rounded-full transition-all duration-700" style="width: {{ $stats['confirmation_rate'] }}%;"></div>
                        </div>
                        <span class="text-[10px] font-bold text-emerald-600">{{ number_format($stats['confirmation_rate'], 1) }}%</span>
                    </div>
                </td>
            </tr>

            {{-- حجوزات ملغاة --}}
            <tr class="hover:bg-slate-50/50 transition-colors">
                <td class="px-6 py-3.5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-2.5 h-2.5 rounded-full bg-rose-500 flex-shrink-0"></div>
                        <span class="text-sm font-bold text-slate-800">حجوزات ملغاة</span>
                    </div>
                </td>
                <td class="px-6 py-3.5">
                    <span class="text-sm font-bold text-rose-600">{{ number_format($stats['cancelled']) }}</span>
                </td>
                <td class="px-6 py-3.5 text-center">
                    @php $cancelRate = $stats['total_bookings'] > 0 ? ($stats['cancelled'] / $stats['total_bookings']) * 100 : 0; @endphp
                    <div class="flex items-center justify-center gap-2">
                        <div class="w-16 bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="bg-rose-500 h-2 rounded-full transition-all duration-700" style="width: {{ $cancelRate }}%;"></div>
                        </div>
                        <span class="text-[10px] font-bold text-rose-600">{{ number_format($cancelRate, 1) }}%</span>
                    </div>
                </td>
            </tr>

            {{-- حجوزات نشطة --}}
            <tr class="hover:bg-slate-50/50 transition-colors">
                <td class="px-6 py-3.5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-2.5 h-2.5 rounded-full bg-teal-500 flex-shrink-0"></div>
                        <span class="text-sm font-bold text-slate-800">حجوزات نشطة (غير ملغاة)</span>
                    </div>
                </td>
                <td class="px-6 py-3.5">
                    <span class="text-sm font-bold text-teal-600">{{ number_format($stats['active_bookings']) }}</span>
                </td>
                <td class="px-6 py-3.5 text-center">
                    @php $activeRate = $stats['total_bookings'] > 0 ? ($stats['active_bookings'] / $stats['total_bookings']) * 100 : 0; @endphp
                    <span class="text-[10px] font-bold text-teal-600 bg-teal-50 px-2.5 py-1 rounded-full border border-teal-200">{{ number_format($activeRate, 1) }}%</span>
                </td>
            </tr>
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
