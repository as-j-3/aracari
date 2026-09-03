@extends('layouts.auth')

@section('title', 'إعادة تعيين كلمة المرور | أراكاري')

@section('content')

<div class="w-full max-w-lg mx-auto">
    <!-- Card -->
    <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl p-7 sm:p-8 border border-white/40">
        <!-- Logo -->
        <div class="flex flex-col items-center text-center mb-6">
            <div class="relative w-14 h-14 rounded-2xl bg-[#1f442e] flex items-center justify-center shadow-lg shadow-[#0c1d14]/30 ring-2 ring-[#e7bd54]/40 mb-3">
                <svg class="w-8 h-8" viewBox="0 0 32 32" aria-hidden="true">
                    <path d="M16 4 L4 28 H10 L16 14 L22 28 H28 Z" fill="#e7bd54" />
                    <circle cx="16" cy="22" r="2" fill="#f9f5ec" />
                </svg>
            </div>
            <h1 class="text-2xl font-extrabold text-[#0c1d14] tracking-tight">كلمة مرور جديدة</h1>
            <p class="text-sm text-slate-500 mt-1">أدخل البريد/الهاتف والرمز وكلمة المرور الجديدة</p>
        </div>

        <!-- Flash Messages -->
        @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-3 text-sm mb-4 flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
            <span>{{ session('error') }}</span>
        </div>
        @endif

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl p-3 text-sm mb-4 flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
        @endif

        <!-- Form -->
        <form action="{{ route('password.update') }}" method="POST"
            x-data="{ showPassword: false, showConfirm: false }"
            class="space-y-4">
            @csrf

            <!-- Identifier -->
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1.5">البريد الإلكتروني أو رقم الهاتف</label>
                <div class="relative">
                    <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input
                        type="text"
                        name="identifier"
                        value="{{ old('identifier', request('identifier')) }}"
                        dir="ltr"
                        placeholder="+9677XXXXXXXX"
                        class="w-full bg-[#f9f5ec]/50 border border-[#e0efe3] rounded-2xl px-4 py-3 pr-10 text-slate-800 font-bold focus:ring-4 focus:ring-[#e7bd54]/20 focus:border-[#dba933] transition-all outline-none text-left placeholder:text-slate-400 placeholder:font-normal"
                        required
                        autofocus
                    >
                </div>
                @error('identifier')
                <p class="text-red-600 text-xs mt-1.5 flex items-center gap-1">
                    <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                    {{ $message }}
                </p>
                @enderror
            </div>

            <!-- Verification Code -->
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1.5">رمز التحقق</label>
                <div class="relative">
                    <i data-lucide="shield" class="w-4 h-4 text-slate-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input
                        type="text"
                        name="code"
                        value="{{ old('code') }}"
                        placeholder="000000"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        inputmode="numeric"
                        dir="ltr"
                        class="w-full bg-[#f9f5ec]/50 border border-[#e0efe3] rounded-2xl px-4 py-3 pr-10 text-slate-800 font-bold focus:ring-4 focus:ring-[#e7bd54]/20 focus:border-[#dba933] transition-all outline-none text-left placeholder:text-slate-400 placeholder:font-normal tracking-[0.3em]"
                        required
                        autocomplete="one-time-code"
                        x-init="$el.addEventListener('input', e => e.target.value = e.target.value.replace(/\D/g, '').slice(0, 6))"
                    >
                </div>
                @error('code')
                <p class="text-red-600 text-xs mt-1.5 flex items-center gap-1">
                    <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                    {{ $message }}
                </p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1.5">كلمة المرور الجديدة</label>
                <div class="relative">
                    <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input
                        :type="showPassword ? 'text' : 'password'"
                        name="password"
                        dir="ltr"
                        placeholder="••••••••"
                        class="w-full bg-[#f9f5ec]/50 border border-[#e0efe3] rounded-2xl px-4 py-3 pr-10 pl-10 text-slate-800 font-bold focus:ring-4 focus:ring-[#e7bd54]/20 focus:border-[#dba933] transition-all outline-none text-left placeholder:text-slate-400 placeholder:font-normal"
                        required
                    >
                    <button
                        type="button"
                        x-on:click="showPassword = !showPassword"
                        class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#1f442e] transition-colors"
                        aria-label="إظهار/إخفاء كلمة المرور"
                    >
                        <i data-lucide="eye" class="w-4 h-4" x-show="!showPassword"></i>
                        <i data-lucide="eye-off" class="w-4 h-4" x-show="showPassword" x-cloak></i>
                    </button>
                </div>
                @error('password')
                <p class="text-red-600 text-xs mt-1.5 flex items-center gap-1">
                    <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                    {{ $message }}
                </p>
                @enderror
            </div>

            <!-- Password Confirmation -->
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1.5">تأكيد كلمة المرور</label>
                <div class="relative">
                    <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input
                        :type="showConfirm ? 'text' : 'password'"
                        name="password_confirmation"
                        dir="ltr"
                        placeholder="••••••••"
                        class="w-full bg-[#f9f5ec]/50 border border-[#e0efe3] rounded-2xl px-4 py-3 pr-10 pl-10 text-slate-800 font-bold focus:ring-4 focus:ring-[#e7bd54]/20 focus:border-[#dba933] transition-all outline-none text-left placeholder:text-slate-400 placeholder:font-normal"
                        required
                    >
                    <button
                        type="button"
                        x-on:click="showConfirm = !showConfirm"
                        class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#1f442e] transition-colors"
                        aria-label="إظهار/إخفاء كلمة المرور"
                    >
                        <i data-lucide="eye" class="w-4 h-4" x-show="!showConfirm"></i>
                        <i data-lucide="eye-off" class="w-4 h-4" x-show="showConfirm" x-cloak></i>
                    </button>
                </div>
                @error('password_confirmation')
                <p class="text-red-600 text-xs mt-1.5 flex items-center gap-1">
                    <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                    {{ $message }}
                </p>
                @enderror
            </div>

            <!-- Submit -->
            <button
                type="submit"
                class="btn-gold w-full text-[#0c1d14] font-bold rounded-2xl py-3.5 shadow-lg shadow-[#e7bd54]/20 hover:shadow-[#e7bd54]/40 mt-2 flex items-center justify-center gap-2 transition-all"
            >
                <i data-lucide="key-round" class="w-4 h-4"></i>
                إعادة تعيين كلمة المرور
            </button>
        </form>

        <!-- Back to Login -->
        <div class="text-center mt-5">
            <p class="text-sm text-slate-500">
                <a href="{{ route('login') }}" class="inline-flex items-center gap-1 text-[#438656] hover:text-[#1f442e] font-bold transition-colors">
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    العودة لتسجيل الدخول
                </a>
            </p>
        </div>
    </div>
</div>

@endsection
