@extends('layouts.auth')

@section('title', 'استعادة كلمة المرور | أراكاري')

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
            <h1 class="text-2xl font-extrabold text-[#0c1d14] tracking-tight">استعادة كلمة المرور</h1>
            <p class="text-sm text-slate-500 mt-1">أدخل بريدك الإلكتروني أو رقم هاتفك وسنرسل لك رمز التحقق</p>
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
        <form action="{{ route('password.request') }}" method="POST"
            x-data="forgotPasswordForm()"
            x-on:submit.prevent="handleSubmit()"
            class="space-y-4">
            @csrf
            <input type="hidden" name="identifier" id="forgot_identity_hidden">

            <!-- Method toggle -->
            <div class="grid grid-cols-2 gap-2 p-1 bg-[#f9f5ec] rounded-2xl mb-2">
                <button
                    type="button"
                    x-on:click="method = 'phone'"
                    :class="method === 'phone' ? 'toggle-active' : 'toggle-inactive'"
                    class="flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-bold transition-all"
                >
                    <i data-lucide="phone" class="w-4 h-4"></i>
                    الهاتف
                </button>
                <button
                    type="button"
                    x-on:click="method = 'email'"
                    :class="method === 'email' ? 'toggle-active' : 'toggle-inactive'"
                    class="flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-bold transition-all"
                >
                    <i data-lucide="mail" class="w-4 h-4"></i>
                    البريد الإلكتروني
                </button>
            </div>

            <!-- Phone Mode -->
            <div x-show="method === 'phone'" x-transition.duration.200ms>
                <label class="block text-xs font-bold text-slate-600 mb-1.5">رقم الهاتف</label>
                <div class="flex gap-2" dir="ltr">
                    <div class="relative">
                        <button
                            type="button"
                            x-on:click="countryOpen = !countryOpen"
                            class="h-full flex items-center gap-1.5 bg-[#f9f5ec]/50 border border-[#e0efe3] rounded-2xl px-3 py-3 text-slate-800 font-bold hover:border-[#dba933] transition-all outline-none min-w-[110px] justify-between"
                        >
                            <span class="text-lg leading-none" x-text="selectedFlag"></span>
                            <span class="text-sm" x-text="selectedCountry"></span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400"></i>
                        </button>
                        <div x-show="countryOpen" x-transition
                            x-on:click.outside="countryOpen = false"
                            class="absolute z-20 mt-1 right-0 w-56 max-h-64 overflow-y-auto bg-white rounded-2xl shadow-2xl border border-[#e0efe3] py-1.5"
                            style="scrollbar-width: thin;">
                            <template x-for="c in sortedCountries" :key="c.code">
                                <button
                                    type="button"
                                    x-on:click="selectedCountry = '+' + c.code; countryOpen = false;"
                                    :class="selectedCountry === '+' + c.code ? 'bg-[#f9f5ec] text-[#1f442e]' : 'text-slate-700'"
                                    class="w-full flex items-center gap-3 px-3 py-2 text-right hover:bg-[#f9f5ec] transition-colors"
                                >
                                    <span class="text-lg" x-text="c.flag"></span>
                                    <span class="text-sm font-bold flex-1 text-right" x-text="c.name"></span>
                                    <span class="text-xs font-mono text-slate-500" x-text="'+' + c.code"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <input
                        type="tel"
                        x-model="phone"
                        inputmode="numeric"
                        dir="ltr"
                        placeholder="7XX XXX XXX"
                        class="flex-1 w-full bg-[#f9f5ec]/50 border border-[#e0efe3] rounded-2xl px-4 py-3 text-slate-800 font-bold focus:ring-4 focus:ring-[#e7bd54]/20 focus:border-[#dba933] transition-all outline-none text-left placeholder:text-slate-400 placeholder:font-normal"
                        autofocus
                    >
                </div>
            </div>

            <!-- Email Mode -->
            <div x-show="method === 'email'" x-cloak x-transition.duration.200ms>
                <label class="block text-xs font-bold text-slate-600 mb-1.5">البريد الإلكتروني</label>
                <div class="relative">
                    <i data-lucide="mail" class="w-4 h-4 text-slate-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input
                        type="email"
                        x-model="email"
                        dir="ltr"
                        placeholder="you@example.com"
                        class="w-full bg-[#f9f5ec]/50 border border-[#e0efe3] rounded-2xl px-4 py-3 pr-10 text-slate-800 font-bold focus:ring-4 focus:ring-[#e7bd54]/20 focus:border-[#dba933] transition-all outline-none text-left placeholder:text-slate-400 placeholder:font-normal"
                    >
                </div>
            </div>

            @error('identifier')
            <p class="text-red-600 text-xs flex items-center gap-1">
                <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                {{ $message }}
            </p>
            @enderror

            <!-- reCAPTCHA -->
            @if(config('services.recaptcha.site_key'))
            <div class="flex justify-center">
                <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
            </div>
            @error('g-recaptcha-response')
            <p class="text-red-600 text-xs text-center flex items-center justify-center gap-1">
                <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                {{ $message }}
            </p>
            @enderror
            @endif

            <!-- Submit -->
            <button
                type="submit"
                class="btn-gold w-full text-[#0c1d14] font-bold rounded-2xl py-3.5 shadow-lg shadow-[#e7bd54]/20 hover:shadow-[#e7bd54]/40 mt-2 flex items-center justify-center gap-2 transition-all"
            >
                <i data-lucide="send" class="w-4 h-4"></i>
                إرسال رمز التحقق
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

@push('scripts')
<script>
function forgotPasswordForm() {
    return {
        method: 'phone',
        countryOpen: false,
        countries: @json($countries ?? []),
        selectedCountry: '+967',
        phone: @js(old('identifier', '')),
        email: @js(old('identifier', '')),
        handleSubmit() {
            let identityInput = document.getElementById('forgot_identity_hidden');
            if (this.method === 'phone') {
                let digits = this.phone.replace(/\D/g, '');
                if (!digits) return;
                let fullPhone = this.selectedCountry.replace('+', '') + digits;
                identityInput.value = '+' + fullPhone;
            } else {
                if (!this.email) return;
                identityInput.value = this.email;
            }
            this.$el.submit();
        },
        get selectedFlag() {
            var code = this.selectedCountry.replace('+', '');
            var flags = {"93":"\u{1F1E6}\u{1F1EB}","355":"\u{1F1E6}\u{1F1F1}","213":"\u{1F1E9}\u{1F1FF}","973":"\u{1F1E7}\u{1F1ED}","965":"\u{1F1F0}\u{1F1FC}","968":"\u{1F1F4}\u{1F1E6}","974":"\u{1F1F6}\u{1F1E6}","20":"\u{1F1EA}\u{1F1EC}","962":"\u{1F1EF}\u{1F1F4}","961":"\u{1F1F1}\u{1F1E7}","964":"\u{1F1EE}\u{1F1F6}","963":"\u{1F1F8}\u{1F1FE}","212":"\u{1F1F2}\u{1F1E6}","216":"\u{1F1F9}\u{1F1F3}","218":"\u{1F1F1}\u{1F1FE}","249":"\u{1F1F8}\u{1F1E9}","967":"\u{1F1FE}\u{1F1EA}","966":"\u{1F1F8}\u{1F1E6}","971":"\u{1F1E6}\u{1F1EA}","1":"\u{1F1FA}\u{1F1F8}","44":"\u{1F1EC}\u{1F1E7}","90":"\u{1F1F9}\u{1F1F7}","98":"\u{1F1EE}\u{1F1F7}"};
            return flags[code] || '\u{1F30D}';
        },
        get sortedCountries() {
            return Object.entries(this.countries).map(function(entry) {
                var code = entry[0], name = entry[1];
                return {code: code, name: name, flag: this.getFlag(code)};
            }.bind(this)).sort(function(a, b) {
                return a.name.localeCompare(b.name);
            });
        },
        getFlag(code) {
            var flags = {"93":"\u{1F1E6}\u{1F1EB}","355":"\u{1F1E6}\u{1F1F1}","213":"\u{1F1E9}\u{1F1FF}","973":"\u{1F1E7}\u{1F1ED}","965":"\u{1F1F0}\u{1F1FC}","968":"\u{1F1F4}\u{1F1E6}","974":"\u{1F1F6}\u{1F1E6}","20":"\u{1F1EA}\u{1F1EC}","962":"\u{1F1EF}\u{1F1F4}","961":"\u{1F1F1}\u{1F1E7}","964":"\u{1F1EE}\u{1F1F6}","963":"\u{1F1F8}\u{1F1FE}","212":"\u{1F1F2}\u{1F1E6}","216":"\u{1F1F9}\u{1F1F3}","218":"\u{1F1F1}\u{1F1FE}","249":"\u{1F1F8}\u{1F1E9}","967":"\u{1F1FE}\u{1F1EA}","966":"\u{1F1F8}\u{1F1E6}","971":"\u{1F1E6}\u{1F1EA}","1":"\u{1F1FA}\u{1F1F8}","44":"\u{1F1EC}\u{1F1E7}","90":"\u{1F1F9}\u{1F1F7}","98":"\u{1F1EE}\u{1F1F7}"};
            return flags[code] || '\u{1F30D}';
        }
    };
}
</script>
@endpush
