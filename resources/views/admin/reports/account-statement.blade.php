@extends('layouts.admin')

@section('title', 'أركاري | كشف حساب - ' . $account->account_code)
@section('header_title', 'كشف الحساب')

@section('content')
@php
    $totalDebit = collect($statementLines)->sum('debit');
    $totalCredit = collect($statementLines)->sum('credit');
    $transactionsCount = count($statementLines);
    $isClosingNegative = $closingBalance < 0;
@endphp

<div class="space-y-6 max-w-7xl mx-auto font-cairo text-right" dir="rtl">

    {{-- ===== الهيدر ===== --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white/60 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/70 shadow-sm relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-teal-500/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>

        <div class="flex items-center gap-4 relative z-10">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center shadow-lg shadow-teal-500/20">
                <i data-lucide="file-text" class="w-7 h-7 text-white"></i>
            </div>
            <div class="min-w-0">
                <h2 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2 flex-wrap">
                    <span>كشف الحساب</span>
                    <span class="text-xs font-bold text-teal-700 bg-teal-50 border border-teal-200 px-3 py-1 rounded-full inline-flex items-center gap-1 font-mono" dir="ltr">
                        <i data-lucide="hash" class="w-3 h-3"></i>
                        {{ $account->account_code }}
                    </span>
                </h2>
                <p class="text-slate-500 font-medium text-sm flex items-center gap-2 mt-1">
                    <i data-lucide="info" class="w-4 h-4 text-slate-400"></i>
                    <span class="font-bold text-slate-700">{{ $account->account_name }}</span>
                    <span class="text-slate-300">—</span>
                    <span>عرض جميع حركات الحساب</span>
                </p>
            </div>
        </div>
    </div>

    {{-- ===== بطاقات معلومات الحساب (6 أعمدة) ===== --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        {{-- كود الحساب --}}
        <div class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-2xl p-4 hover:border-teal-500/30 transition-all duration-300">
            <div class="flex items-center gap-2 mb-1.5">
                <i data-lucide="hash" class="w-3.5 h-3.5 text-slate-400"></i>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">كود الحساب</p>
            </div>
            <p class="text-sm font-mono font-black text-slate-800" dir="ltr">{{ $account->account_code }}</p>
        </div>

        {{-- اسم الحساب --}}
        <div class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-2xl p-4 hover:border-teal-500/30 transition-all duration-300">
            <div class="flex items-center gap-2 mb-1.5">
                <i data-lucide="wallet" class="w-3.5 h-3.5 text-slate-400"></i>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">اسم الحساب</p>
            </div>
            <p class="text-sm font-black text-slate-800 truncate">{{ $account->account_name }}</p>
        </div>

        {{-- نوع الحساب --}}
        <div class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-2xl p-4 hover:border-teal-500/30 transition-all duration-300">
            <div class="flex items-center gap-2 mb-1.5">
                <i data-lucide="arrow-left-right" class="w-3.5 h-3.5 text-slate-400"></i>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">النوع</p>
            </div>
            <p class="text-sm font-bold text-slate-700">{{ $account->account_type }}</p>
        </div>

        {{-- المستوى --}}
        <div class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-2xl p-4 hover:border-teal-500/30 transition-all duration-300">
            <div class="flex items-center gap-2 mb-1.5">
                <i data-lucide="layers" class="w-3.5 h-3.5 text-slate-400"></i>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">المستوى</p>
            </div>
            <p class="text-sm font-black text-slate-800">{{ $account->level }}</p>
        </div>

        {{-- رصيد افتتاحي --}}
        <div class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-2xl p-4 hover:border-amber-500/30 transition-all duration-300">
            <div class="flex items-center gap-2 mb-1.5">
                <i data-lucide="trending-down" class="w-3.5 h-3.5 text-amber-500"></i>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">رصيد افتتاحي</p>
            </div>
            <p class="text-sm font-mono font-black text-slate-800" dir="ltr">{{ number_format($openingBalance, 2) }}</p>
        </div>

        {{-- رصيد ختامي (accent) --}}
        <div class="bg-gradient-to-br from-teal-500 to-teal-600 border border-teal-400/30 rounded-2xl p-4 shadow-lg shadow-teal-500/20 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-16 h-16 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative z-10">
                <div class="flex items-center gap-2 mb-1.5">
                    <i data-lucide="trending-up" class="w-3.5 h-3.5 text-teal-100"></i>
                    <p class="text-[10px] font-bold text-teal-100 uppercase tracking-wider">رصيد ختامي</p>
                </div>
                <p class="text-sm font-mono font-black {{ $isClosingNegative ? 'text-rose-100' : 'text-white' }}" dir="ltr">{{ number_format($closingBalance, 2) }}</p>
            </div>
        </div>
    </div>

    {{-- ===== جدول الحركات ===== --}}
    <div class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-3xl overflow-hidden shadow-xl shadow-slate-200/20 max-w-full">
        {{-- رأس الجدول --}}
        <div class="p-6 border-b border-slate-200/70 bg-white/40 backdrop-blur flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center shadow-lg shadow-teal-500/20 flex-shrink-0">
                    <i data-lucide="file-text" class="w-5 h-5 text-white"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-black text-slate-800 truncate">حركات الحساب</h3>
                    <p class="text-[11px] text-slate-500 font-medium">{{ $transactionsCount }} حركة مسجلة</p>
                </div>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-teal-50 text-teal-700 text-[10px] font-bold border border-teal-200 flex-shrink-0">
                <i data-lucide="scale" class="w-3 h-3"></i>
                {{ $transactionsCount }} قيد
            </span>
        </div>

        <div class="overflow-x-auto min-w-0">
            <table class="w-full min-w-[900px]">
                <thead class="bg-slate-50/70 border-b border-slate-200/70">
                    <tr>
                        <th class="px-5 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">التاريخ</th>
                        <th class="px-5 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المرجع</th>
                        <th class="px-5 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الوصف</th>
                        <th class="px-5 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">مدين</th>
                        <th class="px-5 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">دائن</th>
                        <th class="px-5 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">العملة</th>
                        <th class="px-5 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الرصيد الجاري</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($statementLines as $line)
                        @php
                            $tx = $line['transaction'];
                            $currencyCode = $tx->currency?->code ?? $baseCurrencyCode ?? '-';
                            $isNegative = $line['running_balance'] < 0;
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            {{-- التاريخ --}}
                            <td class="px-5 py-3.5">
                                <span class="font-mono font-black text-slate-700 text-xs" dir="ltr">{{ optional($tx->created_at)->format('Y-m-d') }}</span>
                            </td>

                            {{-- المرجع --}}
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-[11px] font-mono font-black border border-slate-200" dir="ltr">
                                    {{ $tx->reference->reference_number ?? '-' }}
                                </span>
                            </td>

                            {{-- الوصف --}}
                            <td class="px-5 py-3.5">
                                <span class="text-xs font-bold text-slate-700">{{ $tx->description }}</span>
                            </td>

                            {{-- مدين --}}
                            <td class="px-5 py-3.5 text-center">
                                @if($line['debit'] > 0)
                                    <span class="font-mono font-black text-teal-600 text-xs" dir="ltr">{{ number_format($line['debit'], 2) }}</span>
                                @else
                                    <span class="text-slate-300 text-xs font-mono">—</span>
                                @endif
                            </td>

                            {{-- دائن --}}
                            <td class="px-5 py-3.5 text-center">
                                @if($line['credit'] > 0)
                                    <span class="font-mono font-black text-amber-600 text-xs" dir="ltr">{{ number_format($line['credit'], 2) }}</span>
                                @else
                                    <span class="text-slate-300 text-xs font-mono">—</span>
                                @endif
                            </td>

                            {{-- العملة --}}
                            <td class="px-5 py-3.5 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-50 text-slate-500 text-[10px] font-mono font-bold border border-slate-200">{{ $currencyCode }}</span>
                            </td>

                            {{-- الرصيد الجاري --}}
                            <td class="px-5 py-3.5 text-center">
                                <span class="font-mono font-black text-xs {{ $isNegative ? 'text-rose-600' : 'text-slate-800' }}" dir="ltr">{{ number_format($line['running_balance'], 2) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="w-20 h-20 rounded-full bg-slate-50 mx-auto flex items-center justify-center text-slate-300 mb-4">
                                    <i data-lucide="file-text" class="w-9 h-9"></i>
                                </div>
                                <h3 class="text-lg font-bold text-slate-700 mb-1">لا توجد حركات</h3>
                                <p class="text-slate-400 text-sm">لا توجد قيود مسجلة على هذا الحساب ضمن الفترة المحددة</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                {{-- الإجماليات --}}
                @if($transactionsCount > 0)
                    <tfoot class="bg-slate-50/80 border-t-2 border-slate-200">
                        <tr>
                            <td colspan="3" class="px-5 py-4 text-right">
                                <span class="text-xs font-black text-slate-700 uppercase tracking-wider">الإجماليات</span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="font-mono font-black text-teal-700 text-sm" dir="ltr">{{ number_format($totalDebit, 2) }}</span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="font-mono font-black text-amber-700 text-sm" dir="ltr">{{ number_format($totalCredit, 2) }}</span>
                            </td>
                            <td colspan="2" class="px-5 py-4 text-center">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-200 text-slate-700 text-[10px] font-bold border border-slate-300">
                                    <i data-lucide="scale" class="w-3 h-3"></i>
                                    صافي: {{ number_format($totalDebit - $totalCredit, 2) }}
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- زر العودة --}}
    <div class="flex justify-start">
        <a href="{{ route('admin.accounting.reports.account-statement.form') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/70 border border-slate-200 text-slate-600 rounded-xl font-bold text-sm hover:bg-white hover:border-slate-300 transition-all duration-300">
            <i data-lucide="arrow-right" class="w-4 h-4"></i>
            بحث جديد
        </a>
    </div>

</div>
@endsection
