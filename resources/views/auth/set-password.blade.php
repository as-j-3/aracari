@extends('layouts.auth')

@section('title', 'كلمة المرور | أراكاري')

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
            <h1 class="text-2xl font-extrabold text-[#0c1d14] tracking-tight">كلمة المرور</h1>
            <p class="text-sm text-slate-500 mt-1">الخطوة 3 من 3 — اختر كلمة مرور قوية</p>
        </div>

        <!-- Progress Bars -->
        <div class="flex items-center justify-center gap-2 mb-6">
            <span class="w-8 h-2 rounded-full bg-[#438656]"></span>
            <span class="w-8 h-2 rounded-full bg-[#438656]"></span>
            <span class="w-8 h-2 rounded-full bg-[#d49920]"></span>
        </div>

        <!-- Flash Messages -->
        @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-3 text-sm mb-4 flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
            <span>{{ session('error') }}</span>
        </div>
        @endif

        <!-- Form -->
        <form action="{{ route('password.set.store') }}" method="POST"
            x-data="{
                showPassword: false,
                showConfirm: false,
                password: '',
                confirm: '',
                hasMinLen() { return this.password.length >= 8; },
                hasUpper() { return /[A-Z]/.test(this.password); },
                hasLower() { return /[a-z]/.test(this.password); },
                hasNum() { return /[0-9]/.test(this.password); },
                hasSpec() { return /[^A-Za-z0-9]/.test(this.password); },
                get passedCount() {
                    return [this.hasMinLen(), this.hasUpper(), this.hasNum(), this.hasSpec()].filter(Boolean).length;
                },
                get strength() {
                    if (this.password.length === 0) return 0;
                    const c = this.passedCount;
                    if (c <= 1) return 1;
                    if (c === 2) return 2;
                    if (c === 3) return 3;
                    return 4;
                },
                get strengthLabel() {
                    const labels = ['', 'ضعيفة جداً', 'ضعيفة', 'متوسطة', 'قوية'];
                    return labels[this.strength] || '';
                },
                get strengthColor() {
                    if (this.strength <= 1) return 'bg-rose-500';
                    if (this.strength === 2) return 'bg-amber-500';
                    if (this.strength === 3) return 'bg-emerald-500';
                    return 'bg-emerald-600';
                },
                get strengthTextColor() {
                    if (this.strength <= 1) return 'text-rose-600';
                    if (this.strength === 2) return 'text-amber-600';
                    return 'text-emerald-600';
                },
                get passwordsMatch() { return this.confirm.length > 0 && this.password === this.confirm; },
                get passwordMismatch() { return this.confirm.length > 0 && this.password !== this.confirm; },
                get canComplete() { return this.strength >= 3 && this.passwordsMatch; }
            }"
            class="space-y-4">
            @csrf

            <!-- Hidden UUID -->
            <input type="hidden" name="uuid" value="{{ $uuid }}">

            <!-- Password -->
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1.5">كلمة المرور</label>
                <div class="relative">
                    <i data-lucide="lock" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                    <input
                        :type="showPassword ? 'text' : 'password'"
                        name="password"
                        x-model="password"
                        dir="ltr"
                        placeholder="••••••••"
                        class="w-full bg-[#f9f5ec]/50 border border-[#e0efe3] rounded-2xl px-4 py-3 pr-10 pl-10 text-slate-800 font-bold focus:ring-4 focus:ring-[#e7bd54]/20 focus:border-[#dba933] transition-all outline-none text-left placeholder:text-slate-400 placeholder:font-normal"
                        required
                        autofocus
                    >
                    <button
                        type="button"
                        x-on:click="showPassword = !showPassword"
                        class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
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

            <!-- Strength Bar -->
            <div x-show="password.length > 0" x-transition class="mt-2.5">
                <div class="flex items-center gap-1.5">
                    <template x-for="s in 4" :key="s">
                        <div
                            :class="s <= strength ? strengthColor : 'bg-[#e0efe3]'"
                            class="h-1.5 flex-1 rounded-full transition-all"
                        ></div>
                    </template>
                </div>
                <p class="text-xs font-bold mt-1.5" :class="strengthTextColor">
                    قوة كلمة المرور: <span x-text="strengthLabel"></span>
                </p>
            </div>

            <!-- Password Requirements -->
            <div class="bg-[#f9f5ec] border border-[#e0efe3] rounded-2xl px-4 py-3">
                <p class="text-xs font-bold text-slate-600 mb-2 flex items-center gap-1.5">
                    <i data-lucide="key-round" class="w-3.5 h-3.5"></i>
                    متطلبات كلمة المرور
                </p>
                <ul class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                    <li class="flex items-center gap-1.5 text-xs font-bold transition-colors"
                        :class="hasMinLen() ? 'text-emerald-600' : 'text-slate-400'">
                        <i data-lucide="check-circle" class="w-3.5 h-3.5 flex-shrink-0" x-show="hasMinLen()"></i>
                        <i data-lucide="x" class="w-3.5 h-3.5 flex-shrink-0" x-show="!hasMinLen()"></i>
                        8 أحرف على الأقل
                    </li>
                    <li class="flex items-center gap-1.5 text-xs font-bold transition-colors"
                        :class="hasUpper() ? 'text-emerald-600' : 'text-slate-400'">
                        <i data-lucide="check-circle" class="w-3.5 h-3.5 flex-shrink-0" x-show="hasUpper()"></i>
                        <i data-lucide="x" class="w-3.5 h-3.5 flex-shrink-0" x-show="!hasUpper()"></i>
                        حرف كبير (A-Z)
                    </li>
                    <li class="flex items-center gap-1.5 text-xs font-bold transition-colors"
                        :class="hasNum() ? 'text-emerald-600' : 'text-slate-400'">
                        <i data-lucide="check-circle" class="w-3.5 h-3.5 flex-shrink-0" x-show="hasNum()"></i>
                        <i data-lucide="x" class="w-3.5 h-3.5 flex-shrink-0" x-show="!hasNum()"></i>
                        رقم (0-9)
                    </li>
                    <li class="flex items-center gap-1.5 text-xs font-bold transition-colors"
                        :class="hasSpec() ? 'text-emerald-600' : 'text-slate-400'">
                        <i data-lucide="check-circle" class="w-3.5 h-3.5 flex-shrink-0" x-show="hasSpec()"></i>
                        <i data-lucide="x" class="w-3.5 h-3.5 flex-shrink-0" x-show="!hasSpec()"></i>
                        رمز خاص (!@#$...)
                    </li>
                </ul>
            </div>

            <!-- Confirm Password -->
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1.5">تأكيد كلمة المرور</label>
                <div class="relative">
                    <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input
                        :type="showConfirm ? 'text' : 'password'"
                        name="password_confirmation"
                        x-model="confirm"
                        dir="ltr"
                        placeholder="••••••••"
                        class="w-full bg-[#f9f5ec]/50 border border-[#e0efe3] rounded-2xl px-4 py-3 pr-10 pl-10 text-slate-800 font-bold focus:ring-4 focus:ring-[#e7bd54]/20 focus:border-[#dba933] transition-all outline-none text-left placeholder:text-slate-400 placeholder:font-normal"
                        :class="passwordMismatch ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-500/20' : ''"
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
                    <!-- Match check icon -->
                    <i data-lucide="check" class="w-4 h-4 text-emerald-500 absolute left-10 top-1/2 -translate-y-1/2"
                        x-show="passwordsMatch"></i>
                </div>
                <p x-show="passwordMismatch" x-cloak class="text-xs font-bold text-rose-600 mt-1.5 flex items-center gap-1">
                    <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                    كلمتا المرور غير متطابقتين
                </p>
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
                <i data-lucide="check-circle" class="w-4 h-4"></i>
                إكمال إنشاء الحساب
            </button>
        </form>

        <!-- Back -->
        <div class="text-center mt-5">
            <a href="{{ route('login') }}" class="text-sm font-bold text-[#438656] hover:text-[#1f442e] transition-colors">
                الرجوع لتسجيل الدخول
            </a>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Re-create icons after Alpine.js renders
    document.addEventListener('alpine:initialized', () => {
        setTimeout(() => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }, 100);
    });
</script>
@endpush
