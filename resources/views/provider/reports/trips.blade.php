@extends('layouts.provider')

@section('title', 'أركاري | تقرير الرحلات')
@section('header_title', 'تقرير الرحلات')

@section('content')
@php
    $completedTrips = collect($tripPerformance)->filter(fn($t) => false)->count();
    $cancelledTrips = collect($tripPerformance)->filter(fn($t) => false)->count();

    // Calculate completed/cancelled from data if available
    // Note: getTripPerformanceReport excludes cancelled trips, so we show active trips
    $activeTrips = count($tripPerformance);
@endendphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

    {{-- ===== Page Header ===== --}}
    <x-page-header
        icon="bus"
        title="تقرير الرحلات"
        description="أداء الرحلات في الفترة من {{ $startDate->format('d/m/Y') }} إلى {{ $endDate->format('d/m/Y') }}"
        descriptionIcon="calendar"
    >
        <x-slot name="action">
            <a href="{{ route('admin.provider.prodashboard.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/70 border border-slate-200 rounded-xl text-slate-600 font-bold text-sm hover:bg-white hover:shadow-lg transition-all duration-300 group">
                <i data-lucide="arrow-right" class="w-4 h-4 group-hover:-translate-x-1 transition-transform"></i>
                <span class="hidden sm:inline">العودة للوحة التحكم</span>
            </a>
        </x-slot>
    </x-page-header>

    {{-- ===== Date Filter ===== --}}
    <div class="bg-white/70 backdrop-blur-xl border border-slate-200/70 rounded-3xl p-4 sm:p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.provider.reports.trips') }}" class="flex flex-wrap items-center gap-3">
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
        {{-- إجمالي الرحلات --}}
        <x-stat-card
            :value="$summary['total_trips']"
            label="إجمالي الرحلات"
            icon="bus"
            variant="blue"
            caption="رحلة نشطة"
        />

        {{-- إجمالي الإيرادات --}}
        <x-stat-card
            :value="number_format($summary['total_revenue'], 2)"
            label="إجمالي الإيرادات"
            icon="wallet"
            variant="emerald"
            caption="ر.س"
        />

        {{-- متوسط الامتلاء --}}
        <x-stat-card
            :value="number_format($summary['average_occupancy'], 1) . '%'"
            label="متوسط الامتلاء"
            icon="pie-chart"
            variant="purple"
            caption="من إجمالي المقاعد"
        />
    </div>

    {{-- ===== Trip Performance Table ===== --}}
    <x-data-table-card :searchable="false" :footerInfo="count($tripPerformance) . ' رحلة في هذه الفترة'">
        <x-slot name="thead">
            <tr>
                <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الرحلة</th>
                <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">تاريخ المغادرة</th>
                <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">المقاعد المحجوزة</th>
                <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">نسبة الامتلاء</th>
                <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإيرادات</th>
            </tr>
        </x-slot>
        <x-slot name="rows">
            @forelse($tripPerformance as $trip)
                @php
                    $occRate = $trip['occupancy_rate'];
                    $occColor = $occRate >= 80 ? 'bg-emerald-500' : ($occRate >= 50 ? 'bg-amber-500' : 'bg-rose-500');
                    $occTextColor = $occRate >= 80 ? 'text-emerald-600' : ($occRate >= 50 ? 'text-amber-600' : 'text-rose-600');
                    $departureDate = \Carbon\Carbon::parse($trip['departure_time']);
                @endphp
                <tr class="hover:bg-slate-50/50 transition-colors">
                    {{-- Route --}}
                    <td class="px-6 py-3.5">
                        <div class="flex items-center gap-1.5 text-sm font-bold text-slate-800">
                            <span>{{ $trip['departure_city'] }}</span>
                            <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-slate-400 flex-shrink-0"></i>
                            <span>{{ $trip['destination_city'] }}</span>
                        </div>
                    </td>
                    {{-- Departure Date --}}
                    <td class="px-6 py-3.5">
                        <span class="text-xs text-slate-600 font-medium" dir="ltr">{{ $departureDate->format('Y/m/d') }}</span>
                        <span class="text-[10px] text-slate-400 mr-1">{{ $departureDate->format('h:i A') }}</span>
                    </td>
                    {{-- Seats --}}
                    <td class="px-6 py-3.5 text-center">
                        <span class="text-xs font-bold text-slate-700">
                            <span class="text-emerald-600">{{ $trip['booked_seats'] }}</span>
                            <span class="text-slate-400"> / {{ $trip['total_seats'] }}</span>
                        </span>
                    </td>
                    {{-- Occupancy Bar --}}
                    <td class="px-6 py-3.5">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-slate-100 rounded-full h-2.5 overflow-hidden min-w-[60px]">
                                <div class="{{ $occColor }} h-2.5 rounded-full transition-all duration-700"
                                     style="width: {{ min($occRate, 100) }}%;"></div>
                            </div>
                            <span class="text-xs font-bold {{ $occTextColor }} w-12 text-left">{{ number_format($occRate, 1) }}%</span>
                        </div>
                    </td>
                    {{-- Revenue --}}
                    <td class="px-6 py-3.5">
                        <span class="text-sm font-bold text-emerald-600 flex items-center gap-1">
                            {{ number_format($trip['revenue'], 2) }}
                            <span class="text-[10px] text-slate-400 font-normal">ر.س</span>
                        </span>
                    </td>
                </tr>
            @empty
                <x-empty-state
                    icon="bus"
                    title="لا توجد رحلات"
                    message="لم يتم العثور على رحلات في الفترة المحددة. جرب تغيير تاريخ التصفية."
                    :colspan="5"
                />
            @endforelse
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
