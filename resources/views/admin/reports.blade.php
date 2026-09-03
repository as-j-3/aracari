@extends('layouts.admin')

@section('title', 'أركاري | مركز التحليل المالي')
@section('header_title', 'مركز التحليل المالي')

@section('content')
@php
    $currencyCode = $selectedCurrency->code ?? $baseCurrency->code ?? 'YER';
    $periodLabels = ['day' => 'تقرير يومي', 'week' => 'تقرير أسبوعي', 'month' => 'تقرير شهري', 'year' => 'تقرير سنوي'];
    $currentPeriod = request('period', 'month');

    // إجمالي المدفوعات لحساب النسب
    $totalPayments = collect($paymentMethodsReport)->sum('total') ?: 1;

    // ألوان طرق الدفع
    $paymentColors = [
        'Cash' => ['dot' => 'bg-emerald-500', 'gradient' => 'from-emerald-400 to-emerald-600', 'label' => 'نقدي'],
        'Wallet' => ['dot' => 'bg-violet-500', 'gradient' => 'from-violet-400 to-violet-600', 'label' => 'محفظة إلكترونية'],
        'Credit Card' => ['dot' => 'bg-blue-500', 'gradient' => 'from-blue-400 to-blue-600', 'label' => 'بطاقة ائتمان'],
    ];
    $defaultColor = ['dot' => 'bg-slate-400', 'gradient' => 'from-slate-400 to-slate-500', 'label' => 'أخرى'];
@endphp

<div class="space-y-6 max-w-7xl mx-auto font-cairo text-right" dir="rtl">

    {{-- ===== الهيدر ===== --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white/60 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/70 shadow-sm relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-teal-500/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>

        <div class="flex items-center gap-4 relative z-10">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center shadow-lg shadow-teal-500/20 flex-shrink-0">
                <i data-lucide="bar-chart-3" class="w-7 h-7 text-white"></i>
            </div>
            <div class="min-w-0">
                <h2 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2 flex-wrap">
                    <span>مركز التحليل المالي</span>
                    <span class="text-xs font-bold text-teal-700 bg-teal-50 border border-teal-200 px-3 py-1 rounded-full inline-flex items-center gap-1" dir="ltr">
                        {{ $startDate->format('Y-m-d') }} → {{ $endDate->format('Y-m-d') }}
                    </span>
                </h2>
                <p class="text-slate-500 font-medium text-sm flex items-center gap-2 mt-1">
                    <i data-lucide="info" class="w-4 h-4 text-slate-400"></i>
                    قراءة سريعة للأداء المالي والتشغيلي خلال الفترة المحددة
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3 relative z-10 w-full md:w-auto">
            {{-- فلتر الفترة --}}
            <form method="GET" action="{{ route('admin.reports.financial') }}" class="relative flex-1 md:w-44">
                <i data-lucide="clock" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                <select
                    name="period"
                    onchange="this.form.submit()"
                    class="w-full h-11 bg-white/70 border border-slate-200 rounded-xl pr-10 pl-9 text-sm text-slate-700 font-bold focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all appearance-none cursor-pointer"
                >
                    <option value="day" {{ $currentPeriod === 'day' ? 'selected' : '' }}>اليوم</option>
                    <option value="week" {{ $currentPeriod === 'week' ? 'selected' : '' }}>الأسبوع</option>
                    <option value="month" {{ $currentPeriod === 'month' ? 'selected' : '' }}>الشهر</option>
                    <option value="year" {{ $currentPeriod === 'year' ? 'selected' : '' }}>السنة</option>
                </select>
                <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
            </form>

            {{-- زر تصدير --}}
            <button
                type="button"
                onclick="exportFinancials()"
                class="h-11 px-5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2 flex-shrink-0"
            >
                <i data-lucide="download" class="w-4 h-4"></i>
                <span class="hidden sm:inline">تصدير Excel</span>
                <span class="sm:hidden">تصدير</span>
            </button>
        </div>
    </div>

    {{-- ===== بطاقة "نظرة سريعة" (Hero) ===== --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 p-8 border border-white/5 shadow-xl">
        <div class="absolute top-0 right-0 w-72 h-72 bg-teal-500/10 rounded-full blur-3xl -translate-y-1/3 translate-x-1/3 pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-72 h-72 bg-amber-500/10 rounded-full blur-3xl translate-y-1/3 -translate-x-1/3 pointer-events-none"></div>

        <div class="relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
            {{-- الجانب الأيمن — النص --}}
            <div>
                <p class="text-xs font-bold text-teal-400 uppercase tracking-wider mb-2">نظرة سريعة</p>
                <h3 class="text-2xl sm:text-3xl font-black text-white leading-tight mb-3">
                    قراءة سريعة للأداء المالي والتشغيلي خلال الفترة المحددة
                </h3>
                <p class="text-slate-300/80 text-sm leading-relaxed max-w-md">
                    يعرض هذا التقرير ملخصاً شاملاً لإيرادات المنصة وأرباحها الصافية، إلى جانب نسب تأكيد الحجوزات وترتيب الرحلات الأعلى أداءً وقنوات الدفع المستخدمة.
                </p>
            </div>

            {{-- الجانب الأيسر — بطاقتين صغيرتين --}}
            <div class="grid grid-cols-2 gap-4">
                {{-- صافي الربح --}}
                <div class="bg-white/5 rounded-2xl p-5 border border-white/10 hover:bg-white/10 transition-colors duration-300">
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">صافي الربح</p>
                    <p class="text-3xl font-black text-white font-mono" dir="ltr">{{ number_format($bookingStats['net_profit'], 2) }}</p>
                    <p class="text-[11px] font-bold text-teal-400 mt-1">{{ $currencyCode }}</p>
                </div>

                {{-- نسبة التأكيد --}}
                <div class="bg-white/5 rounded-2xl p-5 border border-white/10 hover:bg-white/10 transition-colors duration-300">
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">نسبة التأكيد</p>
                    <p class="text-3xl font-black text-white font-mono">{{ $bookingStats['confirmation_rate'] }}%</p>
                    <p class="text-[11px] font-bold text-slate-400 mt-1">من إجمالي الحجوزات</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== 3 بطاقات إحصائية ===== --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
        {{-- إجمالي الحجوزات --}}
        <div class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 to-slate-800 p-6 border border-white/5 hover:border-teal-500/20 transition-all duration-500 hover:shadow-2xl hover:shadow-teal-500/10 hover:-translate-y-1">
            <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-teal-500/10 to-transparent rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10 flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">إجمالي الحجوزات</p>
                    <h3 class="text-3xl font-extrabold text-white mt-1.5">{{ number_format($bookingStats['total']) }}</h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center shadow-lg shadow-teal-500/20 group-hover:scale-110 transition-all duration-300 flex-shrink-0">
                    <i data-lucide="calendar-days" class="w-6 h-6 text-white"></i>
                </div>
            </div>
            <div class="relative z-10 mt-4 flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center gap-1 text-emerald-400 text-xs font-bold bg-emerald-400/10 px-2.5 py-1 rounded-full border border-emerald-400/20">
                    <i data-lucide="trending-up" class="w-3 h-3"></i>
                    مؤكد: {{ number_format($bookingStats['confirmed']) }}
                </span>
                <span class="inline-flex items-center gap-1 text-rose-400 text-xs font-bold bg-rose-400/10 px-2.5 py-1 rounded-full border border-rose-400/20">
                    <i data-lucide="trending-down" class="w-3 h-3"></i>
                    ملغي: {{ number_format($bookingStats['cancelled']) }}
                </span>
            </div>
        </div>

        {{-- إجمالي الإيرادات --}}
        <div class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 to-slate-800 p-6 border border-white/5 hover:border-emerald-500/20 transition-all duration-500 hover:shadow-2xl hover:shadow-emerald-500/10 hover:-translate-y-1">
            <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-emerald-500/10 to-transparent rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10 flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">إجمالي الإيرادات</p>
                    <h3 class="text-3xl font-extrabold text-white mt-1.5 font-mono" dir="ltr">{{ number_format($bookingStats['total_revenue'], 2) }}</h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/20 group-hover:scale-110 transition-all duration-300 flex-shrink-0">
                    <i data-lucide="banknote" class="w-6 h-6 text-white"></i>
                </div>
            </div>
            <div class="relative z-10 mt-4 flex items-center gap-2">
                <span class="inline-flex items-center gap-1 text-emerald-400 text-xs font-bold bg-emerald-400/10 px-2.5 py-1 rounded-full border border-emerald-400/20">
                    <i data-lucide="trending-up" class="w-3 h-3"></i>
                    صافي الربح: {{ number_format($bookingStats['net_profit'], 2) }} {{ $currencyCode }}
                </span>
            </div>
        </div>

        {{-- متوسط قيمة الحجز --}}
        <div class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 to-slate-800 p-6 border border-white/5 hover:border-blue-500/20 transition-all duration-500 hover:shadow-2xl hover:shadow-blue-500/10 hover:-translate-y-1">
            <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-blue-500/10 to-transparent rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10 flex items-start justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">متوسط قيمة الحجز</p>
                    <h3 class="text-3xl font-extrabold text-white mt-1.5 font-mono" dir="ltr">{{ number_format($bookingStats['average_booking_value'], 2) }}</h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/20 group-hover:scale-110 transition-all duration-300 flex-shrink-0">
                    <i data-lucide="bar-chart-3" class="w-6 h-6 text-white"></i>
                </div>
            </div>
            <div class="relative z-10 mt-4 flex items-center gap-2">
                <span class="inline-flex items-center gap-1 text-blue-400 text-xs font-bold bg-blue-400/10 px-2.5 py-1 rounded-full border border-blue-400/20">
                    <i data-lucide="trending-up" class="w-3 h-3"></i>
                    معدل التأكيد: {{ $bookingStats['confirmation_rate'] }}%
                </span>
            </div>
        </div>
    </div>

    {{-- ===== شبكة من عمودين: الرحلات الأعلى أداءً + قنوات الدفع ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6">

        {{-- الرحلات الأعلى أداءً --}}
        <div class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-3xl overflow-hidden shadow-xl shadow-slate-200/20 max-w-full">
            <div class="p-6 border-b border-slate-200/70 bg-white/40 backdrop-blur flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/20 flex-shrink-0">
                        <i data-lucide="trophy" class="w-5 h-5 text-white"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-black text-slate-800 truncate">الرحلات الأعلى أداءً</h3>
                        <p class="text-[11px] text-slate-500 font-medium">ترتيب حسب الإيرادات خلال {{ $periodLabels[$currentPeriod] ?? 'الشهر' }}</p>
                    </div>
                </div>
            </div>

            <div class="p-4 sm:p-5 space-y-2.5">
                @forelse($tripsReport as $index => $trip)
                    @php
                        $rank = $index + 1;
                        $isFirst = $rank === 1;
                        $rankClass = $rank === 1
                            ? 'bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-md shadow-amber-500/30'
                            : ($rank === 2
                                ? 'bg-gradient-to-br from-slate-300 to-slate-400 text-white shadow-md shadow-slate-400/30'
                                : ($rank === 3
                                    ? 'bg-gradient-to-br from-amber-700 to-amber-800 text-white shadow-md shadow-amber-700/30'
                                    : 'bg-slate-200 text-slate-600'
                                )
                            );
                    @endphp
                    <div class="flex items-center gap-3 p-3 rounded-2xl border transition-all duration-300
                        {{ $isFirst ? 'bg-amber-50/80 border-amber-200/50' : 'bg-white/50 border-slate-100 hover:bg-slate-50/80 hover:border-slate-200' }}">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center font-black text-sm flex-shrink-0 {{ $rankClass }}">
                            {{ $rank }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-slate-800 text-sm truncate">{{ $trip['route'] }}</p>
                            <p class="text-[11px] text-slate-500 font-medium">{{ number_format($trip['bookings_count']) }} حجز</p>
                        </div>
                        <div class="text-left flex-shrink-0">
                            <p class="font-mono font-black text-slate-800 text-sm" dir="ltr">{{ number_format($trip['total_revenue'], 2) }}</p>
                            <p class="text-[10px] text-slate-400 font-bold">{{ $currencyCode }}</p>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center">
                        <div class="w-16 h-16 rounded-full bg-slate-50 mx-auto flex items-center justify-center text-slate-300 mb-3">
                            <i data-lucide="bar-chart-3" class="w-8 h-8"></i>
                        </div>
                        <h4 class="text-sm font-bold text-slate-700 mb-1">لا توجد بيانات أداء</h4>
                        <p class="text-slate-400 text-xs">لم تُسجَّل أي حجوزات خلال هذه الفترة</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- قنوات الدفع --}}
        <div class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-3xl overflow-hidden shadow-xl shadow-slate-200/20 max-w-full">
            <div class="p-6 border-b border-slate-200/70 bg-white/40 backdrop-blur flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/20 flex-shrink-0">
                        <i data-lucide="wallet" class="w-5 h-5 text-white"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-black text-slate-800 truncate">قنوات الدفع</h3>
                        <p class="text-[11px] text-slate-500 font-medium">توزيع الإيرادات حسب طريقة الدفع</p>
                    </div>
                </div>
            </div>

            <div class="p-4 sm:p-5 space-y-4">
                @forelse($paymentMethodsReport as $method)
                    @php
                        $color = $paymentColors[$method['method_name']] ?? $defaultColor;
                        $pct = $bookingStats['total_revenue'] > 0 ? round(($method['total'] / $bookingStats['total_revenue']) * 100, 1) : 0;
                    @endphp
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-2.5 h-2.5 rounded-full {{ $color['dot'] }} flex-shrink-0"></span>
                                <span class="font-bold text-slate-800 text-sm truncate">{{ $color['label'] }}</span>
                            </div>
                            <span class="font-mono font-black text-slate-800 text-sm" dir="ltr">{{ number_format($method['total'], 2) }} {{ $currencyCode }}</span>
                        </div>
                        <div class="h-2.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r {{ $color['gradient'] }} rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                        </div>
                        <p class="text-[10px] text-slate-400 font-bold">{{ $pct }}% من إجمالي الإيرادات</p>
                    </div>
                @empty
                    <div class="py-12 text-center">
                        <div class="w-16 h-16 rounded-full bg-slate-50 mx-auto flex items-center justify-center text-slate-300 mb-3">
                            <i data-lucide="wallet" class="w-8 h-8"></i>
                        </div>
                        <h4 class="text-sm font-bold text-slate-700 mb-1">لا توجد معاملات</h4>
                        <p class="text-slate-400 text-xs">لم تُسجَّل أي مدفوعات خلال هذه الفترة</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ===== جدول آخر القيود المحاسبية ===== --}}
    <div class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-3xl overflow-hidden shadow-xl shadow-slate-200/20 max-w-full">
        <div class="p-6 border-b border-slate-200/70 bg-white/40 backdrop-blur flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/20 flex-shrink-0">
                    <i data-lucide="receipt-text" class="w-5 h-5 text-white"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-black text-slate-800 truncate">آخر القيود المحاسبية</h3>
                    <p class="text-[11px] text-slate-500 font-medium">سجل أحدث العمليات المالية على دفتر الأستاذ</p>
                </div>
            </div>
            <a href="{{ route('admin.accounting.journal-entries.index') }}" class="text-xs font-bold text-blue-600 hover:text-blue-700 inline-flex items-center gap-1 flex-shrink-0">
                عرض كشف كامل
                <i data-lucide="chevron-left" class="w-4 h-4"></i>
            </a>
        </div>

        <div class="overflow-x-auto min-w-0">
            <table class="w-full min-w-[800px]">
                <thead class="bg-slate-50/70 border-b border-slate-200/70">
                    <tr>
                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">التاريخ</th>
                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحساب</th>
                        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">النوع</th>
                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المبلغ</th>
                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">البيان</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php
                        $recentTransactions = \App\Models\Transaction::latest()->take(10)->get();
                    @endphp
                    @forelse($recentTransactions as $tx)
                        @php
                            $isDebit = $tx->transaction_type === 'debit';
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            {{-- التاريخ --}}
                            <td class="px-6 py-4">
                                <div class="font-mono font-black text-slate-700 text-xs" dir="ltr">{{ optional($tx->created_at)->format('Y/m/d') }}</div>
                                <div class="text-[10px] text-slate-400 font-mono mt-0.5" dir="ltr">{{ optional($tx->created_at)->format('H:i') }}</div>
                            </td>

                            {{-- الحساب --}}
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-[11px] font-mono font-black border border-slate-200" dir="ltr">{{ $tx->account_code }}</span>
                            </td>

                            {{-- النوع --}}
                            <td class="px-6 py-4 text-center">
                                @if($isDebit)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">
                                        <i data-lucide="trending-up" class="w-3 h-3"></i>
                                        مدين
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 text-[10px] font-bold border border-rose-200">
                                        <i data-lucide="trending-down" class="w-3 h-3"></i>
                                        دائن
                                    </span>
                                @endif
                            </td>

                            {{-- المبلغ --}}
                            <td class="px-6 py-4">
                                <span class="font-mono font-black text-sm {{ $isDebit ? 'text-emerald-600' : 'text-rose-600' }}" dir="ltr">
                                    {{ $isDebit ? '+' : '−' }}{{ number_format($tx->amount, 2) }}
                                </span>
                                <span class="text-[10px] font-bold text-slate-400 mr-1">{{ $tx->currency->code ?? $currencyCode }}</span>
                            </td>

                            {{-- البيان --}}
                            <td class="px-6 py-4">
                                <p class="text-xs text-slate-600 leading-relaxed max-w-[200px] line-clamp-2" title="{{ $tx->description }}">
                                    {{ \Illuminate\Support\Str::limit($tx->description, 40) }}
                                </p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="w-20 h-20 rounded-full bg-slate-50 mx-auto flex items-center justify-center text-slate-300 mb-4">
                                    <i data-lucide="receipt-text" class="w-9 h-9"></i>
                                </div>
                                <h3 class="text-lg font-bold text-slate-700 mb-1">لا توجد قيود محاسبية</h3>
                                <p class="text-slate-400 text-sm">لم تُسجَّل أي عمليات مالية خلال هذه الفترة</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function exportFinancials() {
    var period = '{{ request('period', 'month') }}';
    window.open('/admin/reports/export?period=' + period, '_blank');
}
</script>
@endpush
