@extends('layouts.admin')

@section('title', 'أركاري | كشف حساب')
@section('header_title', 'كشف حساب')

@section('content')
@php
    $defaultStart = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');
    $defaultEnd = \Carbon\Carbon::now()->format('Y-m-d');
@endphp

<div class="max-w-3xl mx-auto space-y-6 font-cairo text-right" dir="rtl">

    {{-- ===== الهيدر ===== --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white/60 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/70 shadow-sm relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-teal-500/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>

        <div class="flex items-center gap-4 relative z-10">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center shadow-lg shadow-teal-500/20">
                <i data-lucide="file-text" class="w-7 h-7 text-white"></i>
            </div>
            <div class="min-w-0">
                <h2 class="text-2xl font-black text-slate-800 tracking-tight">كشف حساب</h2>
                <p class="text-slate-500 font-medium text-sm flex items-center gap-2 mt-1">
                    <i data-lucide="info" class="w-4 h-4 text-slate-400"></i>
                    تحديد الحساب والفترة الزمنية لعرض الحركات
                </p>
            </div>
        </div>
    </div>

    {{-- ===== بطاقة النموذج ===== --}}
    <form
        action="{{ route('admin.accounting.reports.account-statement') }}"
        method="POST"
        class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-3xl overflow-hidden shadow-xl shadow-slate-200/20 max-w-full"
    >
        @csrf

        {{-- رأس الكارد --}}
        <div class="p-6 border-b border-slate-200/70 bg-white/40 backdrop-blur flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center shadow-lg shadow-teal-500/20 flex-shrink-0">
                <i data-lucide="search" class="w-5 h-5 text-white"></i>
            </div>
            <div class="min-w-0">
                <h3 class="text-base font-black text-slate-800 truncate">معايير كشف الحساب</h3>
                <p class="text-[11px] text-slate-500 font-medium">اختر الحساب وحدد نطاق التاريخ لعرض القيود</p>
            </div>
        </div>

        <div class="p-6 space-y-6">
            {{-- أخطاء الفورم --}}
            @if($errors->any())
                <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-xs font-bold text-rose-600 flex items-start gap-2">
                    <i data-lucide="info" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-xs font-bold text-rose-600 flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4 flex-shrink-0"></i>
                    {{ session('error') }}
                </div>
            @endif

            {{-- اختيار الحساب --}}
            <div class="space-y-2">
                <label for="account_code" class="text-xs font-bold text-slate-600 flex items-center gap-1.5">
                    <i data-lucide="building-2" class="w-3.5 h-3.5 text-teal-500"></i>
                    الحساب <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <select
                        id="account_code"
                        name="account_code"
                        required
                        class="w-full h-12 bg-white/70 border border-slate-200 rounded-xl pr-10 pl-10 text-sm text-slate-700 font-bold focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all appearance-none cursor-pointer"
                    >
                        <option value="">— اختر حساباً —</option>
                        @foreach($accounts as $code => $name)
                            <option value="{{ $code }}" {{ old('account_code') == $code ? 'selected' : '' }}>{{ $code }} — {{ $name }}</option>
                        @endforeach
                    </select>
                    <i data-lucide="building-2" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                    <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                </div>
            </div>

            {{-- نطاق التاريخ --}}
            <div class="rounded-2xl border border-slate-200/70 bg-slate-50/40 p-5 space-y-4">
                <div class="flex items-center gap-2 text-xs font-bold text-slate-600 uppercase tracking-wider">
                    <i data-lucide="calendar" class="w-4 h-4 text-teal-500"></i>
                    الفترة الزمنية
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label for="start_date" class="text-xs font-bold text-slate-600 flex items-center gap-1.5">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-teal-500"></i>
                            تاريخ البداية
                        </label>
                        <input
                            id="start_date"
                            type="date"
                            name="start_date"
                            value="{{ old('start_date', $defaultStart) }}"
                            class="w-full h-12 bg-white/70 border border-slate-200 rounded-xl px-4 text-sm text-slate-700 font-bold focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all"
                        >
                    </div>
                    <div class="space-y-2">
                        <label for="end_date" class="text-xs font-bold text-slate-600 flex items-center gap-1.5">
                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-teal-500"></i>
                            تاريخ النهاية
                        </label>
                        <input
                            id="end_date"
                            type="date"
                            name="end_date"
                            value="{{ old('end_date', $defaultEnd) }}"
                            class="w-full h-12 bg-white/70 border border-slate-200 rounded-xl px-4 text-sm text-slate-700 font-bold focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all"
                        >
                    </div>
                </div>
            </div>

            {{-- الأزرار --}}
            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button
                    type="submit"
                    class="flex-1 h-12 px-5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center justify-center gap-2"
                >
                    <i data-lucide="search" class="w-4 h-4"></i>
                    عرض كشف الحساب
                </button>
                <button
                    type="reset"
                    class="h-12 px-5 bg-white/70 border border-slate-200 text-slate-600 rounded-xl font-bold text-sm hover:bg-white hover:border-slate-300 transition-all duration-300 flex items-center justify-center gap-2"
                >
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    إعادة تعيين
                </button>
            </div>
        </div>
    </form>

    {{-- ===== 3 بطاقات معلوماتية ===== --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- نوع التقرير --}}
        <div class="group relative overflow-hidden rounded-2xl bg-white/80 backdrop-blur-xl border border-slate-200/70 p-5 hover:border-teal-500/30 transition-all duration-300 hover:shadow-lg hover:shadow-teal-500/10 hover:-translate-y-0.5">
            <div class="absolute top-0 right-0 w-24 h-24 bg-teal-500/5 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10 flex items-start gap-3">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center shadow-lg shadow-teal-500/20 flex-shrink-0">
                    <i data-lucide="info" class="w-5 h-5 text-white"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">نوع التقرير</p>
                    <h4 class="text-sm font-black text-slate-800 mt-0.5">كشف حساب تفصيلي</h4>
                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">عرض جميع القيود المدينة والدائنة على الحساب</p>
                </div>
            </div>
        </div>

        {{-- الفترة الافتراضية --}}
        <div class="group relative overflow-hidden rounded-2xl bg-white/80 backdrop-blur-xl border border-slate-200/70 p-5 hover:border-amber-500/30 transition-all duration-300 hover:shadow-lg hover:shadow-amber-500/10 hover:-translate-y-0.5">
            <div class="absolute top-0 right-0 w-24 h-24 bg-amber-500/5 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10 flex items-start gap-3">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/20 flex-shrink-0">
                    <i data-lucide="calendar" class="w-5 h-5 text-white"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">الفترة الافتراضية</p>
                    <h4 class="text-sm font-black text-slate-800 mt-0.5">الشهر الحالي</h4>
                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed" dir="ltr">{{ $defaultStart }} → {{ $defaultEnd }}</p>
                </div>
            </div>
        </div>

        {{-- صيغة التقرير --}}
        <div class="group relative overflow-hidden rounded-2xl bg-white/80 backdrop-blur-xl border border-slate-200/70 p-5 hover:border-slate-400/30 transition-all duration-300 hover:shadow-lg hover:shadow-slate-500/10 hover:-translate-y-0.5">
            <div class="absolute top-0 right-0 w-24 h-24 bg-slate-500/5 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative z-10 flex items-start gap-3">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-slate-700 to-slate-800 flex items-center justify-center shadow-lg shadow-slate-500/20 flex-shrink-0">
                    <i data-lucide="file-text" class="w-5 h-5 text-white"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">صيغة التقرير</p>
                    <h4 class="text-sm font-black text-slate-800 mt-0.5">جدول تفصيلي</h4>
                    <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">تاريخ، مرجع، وصف، مدين، دائن، ورصيد جاري</p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
@push('scripts')
<script>
  // Reset button sets default dates
  document.querySelector('button[type="reset"]')?.addEventListener('click', function() {
    setTimeout(function() {
        document.getElementById('start_date').value = '{{ $defaultStart }}';
        document.getElementById('end_date').value = '{{ $defaultEnd }}';
    }, 10);
  });
</script>
@endpush
