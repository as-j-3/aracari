@extends('layouts.auth')

@section('title', 'تأكيد الحساب | أراكاري')

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
            <h1 class="text-2xl font-extrabold text-[#0c1d14] tracking-tight">تأكيد الحساب</h1>
            <p class="text-sm text-slate-500 mt-1">الخطوة 2 من 3 — أدخل رمز التأكيد</p>
        </div>

        <!-- Progress Bars -->
        <div class="flex items-center justify-center gap-2 mb-6">
            <span class="w-8 h-2 rounded-full bg-[#438656]"></span>
            <span class="w-8 h-2 rounded-full bg-[#d49920]"></span>
            <span class="w-8 h-2 rounded-full bg-slate-200"></span>
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

        <!-- Info Box -->
        <div class="bg-[#f9f5ec]/60 border border-[#e0efe3] rounded-2xl p-3 mb-5 text-center">
            <div class="flex items-center justify-center gap-2 text-sm text-slate-600">
                <i data-lucide="smartphone" class="w-4 h-4 text-[#438656]"></i>
                <span>أرسلنا رمز التأكيد إلى</span>
                <span class="font-bold text-[#0c1d14]" dir="ltr">{{ $identifier ?? '...' }}</span>
            </div>
        </div>

        <!-- OTP Form -->
        <form action="{{ route('verify.otp') }}" method="POST"
            x-data="otpVerifyForm()"
            x-init="init()"
            class="space-y-5">
            @csrf

            <!-- Hidden inputs -->
            <input type="hidden" name="uuid" value="{{ $uuid ?? request()->route('uuid') }}">
            <input type="hidden" name="code" x-ref="codeInput">

            <!-- 6 OTP Boxes -->
            <div class="flex justify-center gap-2" dir="ltr" x-ref="otpWrapper" x-on:paste="onPaste($event)">
                <template x-for="i in 6" :key="i - 1">
                    <input
                        type="text"
                        inputmode="numeric"
                        maxlength="1"
                        :data-index="i - 1"
                        x-on:input="onInput(i - 1, $event)"
                        x-on:keydown="onKeydown(i - 1, $event)"
                        class="w-11 h-14 sm:w-12 sm:h-14 text-center text-2xl font-bold bg-[#f9f5ec]/50 border-2 border-[#e0efe3] rounded-2xl text-[#0c1d14] focus:ring-4 focus:ring-[#e7bd54]/20 focus:border-[#dba933] transition-all outline-none"
                        autocomplete="one-time-code"
                    >
                </template>
            </div>

            @error('code')
            <p class="text-red-600 text-xs flex items-center justify-center gap-1">
                <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                {{ $message }}
            </p>
            @enderror
            @error('uuid')
            <p class="text-red-600 text-xs flex items-center justify-center gap-1">
                <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                {{ $message }}
            </p>
            @enderror

            <!-- Resend -->
            <div class="text-center">
                <span x-show="!canResend" x-cloak class="text-slate-500">
                    إعادة إرسال الرمز خلال
                    <span class="font-mono font-bold text-[#1f442e]" dir="ltr" x-text="timerDisplay"></span>
                </span>
                <form action="{{ route('resend.otp') }}" method="POST"
                    x-data="{ countdown: 60, canResend: false, timerDisplay: '01:00', init() {
                        const timer = setInterval(() => {
                            this.countdown--;
                            if (this.countdown <= 0) { clearInterval(timer); this.canResend = true; }
                            const mm = String(Math.floor(this.countdown / 60)).padStart(2, '0');
                            const ss = String(this.countdown % 60).padStart(2, '0');
                            this.timerDisplay = mm + ':' + ss;
                        }, 1000);
                    }}"
                    x-show="canResend"
                    x-cloak
                    x-init="init()"
                    class="inline">
                    @csrf
                    <input type="hidden" name="uuid" value="{{ $uuid ?? request()->route('uuid') }}">
                    <button
                        type="submit"
                        class="text-sm font-bold text-[#1f442e] hover:text-[#dba933] transition-colors inline-flex items-center gap-1.5"
                    >
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                        إعادة إرسال الرمز
                    </button>
                </form>
            </div>

            <!-- Submit -->
            <button
                type="submit"
                class="btn-gold w-full text-[#0c1d14] font-bold rounded-2xl py-3.5 shadow-lg shadow-[#e7bd54]/20 hover:shadow-[#e7bd54]/40 transition-all"
            >
                تأكيد الحساب
            </button>
        </form>

        <!-- Change Identifier -->
        <div class="text-center mt-5">
            <a href="{{ route('register') }}" class="text-sm font-bold text-[#438656] hover:text-[#1f442e] transition-colors">
                تغيير رقم الهاتف/البريد
            </a>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function otpVerifyForm() {
    return {
        digits: ['', '', '', '', '', ''],

        init() {
            this.$nextTick(() => {
                const boxes = this.getBoxes();
                if (boxes.length) boxes[0].focus();
            });
        },

        getBoxes() {
            return this.$refs.otpWrapper.querySelectorAll('input');
        },

        onInput(index, e) {
            const val = e.target.value.replace(/\D/g, '');
            e.target.value = val.slice(-1);
            this.digits[index] = e.target.value;

            const boxes = this.getBoxes();
            if (val && index < boxes.length - 1) {
                boxes[index + 1].focus();
            }
            this.syncCode();
        },

        onKeydown(index, e) {
            const boxes = this.getBoxes();
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                boxes[index - 1].focus();
                boxes[index - 1].value = '';
                this.digits[index - 1] = '';
                this.syncCode();
            }
        },

        onPaste(e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            if (!text) return;

            const boxes = this.getBoxes();
            for (let i = 0; i < text.length && i < boxes.length; i++) {
                boxes[i].value = text[i];
                this.digits[i] = text[i];
            }
            const nextIdx = Math.min(text.length, boxes.length - 1);
            boxes[nextIdx].focus();
            this.syncCode();
        },

        syncCode() {
            const boxes = this.getBoxes();
            let code = '';
            boxes.forEach(b => code += b.value);
            this.$refs.codeInput.value = code;
        }
    };
}
</script>
@endpush
