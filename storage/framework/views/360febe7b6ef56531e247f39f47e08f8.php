<?php $__env->startSection('title', 'إنشاء حساب جديد | أراكاري'); ?>

<?php $__env->startSection('content'); ?>

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
            <h1 class="text-2xl font-extrabold text-[#0c1d14] tracking-tight">إنشاء حساب جديد</h1>
            <p class="text-sm text-slate-500 mt-1">الخطوة 1 من 3 — معلومات الحساب</p>
        </div>

        <!-- Progress bars -->
        <div class="flex items-center justify-center gap-2 mb-6">
            <span class="w-8 h-2 rounded-full bg-[#d49920]"></span>
            <span class="w-8 h-2 rounded-full bg-slate-200"></span>
            <span class="w-8 h-2 rounded-full bg-slate-200"></span>
        </div>

        <!-- Flash Messages -->
        <?php if(session('error')): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-3 text-sm mb-4 flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
            <span><?php echo e(session('error')); ?></span>
        </div>
        <?php endif; ?>

        <?php if(session('success')): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl p-3 text-sm mb-4 flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 flex-shrink-0"></i>
            <span><?php echo e(session('success')); ?></span>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <form action="<?php echo e(route('register')); ?>" method="POST"
            x-data="registerForm()"
            x-on:submit.prevent="handleSubmit()"
            class="space-y-4">
            <?php echo csrf_field(); ?>

            <!-- Full Name -->
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1.5">الاسم الكامل</label>
                <div class="relative">
                    <i data-lucide="user" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                    <input
                        type="text"
                        name="name"
                        value="<?php echo e(old('name')); ?>"
                        placeholder="مثال: أحمد محمد علي"
                        class="w-full h-12 bg-[#f9f5ec]/50 border border-[#e0efe3] rounded-2xl pr-10 pl-3 text-slate-800 font-bold focus:ring-4 focus:ring-[#e7bd54]/20 focus:border-[#dba933] transition-all outline-none text-right"
                        required
                        autofocus
                    >
                </div>
                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="text-red-600 text-xs mt-1.5 flex items-center gap-1">
                    <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                    <?php echo e($message); ?>

                </p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <!-- Method Toggle -->
            <div class="grid grid-cols-2 gap-2 p-1 bg-[#f9f5ec] rounded-2xl">
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
                    <!-- Country Code Dropdown -->
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
                        <!-- Dropdown -->
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
                        name="phone_number"
                        x-model="phoneVal"
                        inputmode="numeric"
                        dir="ltr"
                        placeholder="7XX XXX XXX"
                        class="flex-1 h-12 bg-[#f9f5ec]/50 border border-[#e0efe3] rounded-2xl px-4 text-slate-800 font-bold focus:ring-4 focus:ring-[#e7bd54]/20 focus:border-[#dba933] transition-all outline-none text-left placeholder:text-slate-400"
                        required
                    >
                </div>
                <input type="hidden" name="country_code" :value="selectedCountry.replace('+','')">
                <?php $__errorArgs = ['phone_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="text-red-600 text-xs mt-1.5 flex items-center gap-1">
                    <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                    <?php echo e($message); ?>

                </p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <?php $__errorArgs = ['country_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="text-red-600 text-xs mt-1.5 flex items-center gap-1">
                    <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                    <?php echo e($message); ?>

                </p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <!-- Email Mode -->
            <div x-show="method === 'email'" x-cloak x-transition.duration.200ms>
                <label class="block text-xs font-bold text-slate-600 mb-1.5">البريد الإلكتروني</label>
                <div class="relative">
                    <i data-lucide="mail" class="w-4 h-4 text-slate-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                    <input
                        type="email"
                        name="email"
                        x-model="emailVal"
                        dir="ltr"
                        placeholder="you@example.com"
                        class="w-full h-12 bg-[#f9f5ec]/50 border border-[#e0efe3] rounded-2xl px-4 pr-10 text-slate-800 font-bold focus:ring-4 focus:ring-[#e7bd54]/20 focus:border-[#dba933] transition-all outline-none text-left placeholder:text-slate-400"
                        required
                    >
                </div>
                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="text-red-600 text-xs mt-1.5 flex items-center gap-1">
                    <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                    <?php echo e($message); ?>

                </p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <!-- reCAPTCHA -->
            <?php if(config('services.recaptcha.site_key')): ?>
            <div class="flex justify-center">
                <div class="g-recaptcha" data-sitekey="<?php echo e(config('services.recaptcha.site_key')); ?>"></div>
            </div>
            <?php $__errorArgs = ['g-recaptcha-response'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <p class="text-red-600 text-xs text-center flex items-center justify-center gap-1">
                <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                <?php echo e($message); ?>

            </p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            <?php else: ?>
            <!-- reCAPTCHA Placeholder -->
            <div class="bg-[#f9f5ec] border border-[#e0efe3] rounded-2xl px-4 py-3 flex items-center justify-between">
                <label class="flex items-center gap-2.5 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        x-model="captcha"
                        name="captcha_placeholder"
                        value="1"
                        class="w-5 h-5 rounded accent-[#1f442e]"
                    >
                    <span class="text-sm font-bold text-slate-700">أنا لست روبوت</span>
                </label>
                <div class="flex flex-col items-center gap-0.5">
                    <i data-lucide="shield" class="w-5 h-5 text-[#1f88ff]"></i>
                    <span class="text-[9px] text-slate-400 font-bold tracking-wide">reCAPTCHA</span>
                    <span class="text-[8px] text-slate-300">Privacy &middot; Terms</span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Terms Agreement -->
            <label class="flex items-start gap-2.5 cursor-pointer select-none text-sm text-slate-600">
                <input
                    type="checkbox"
                    name="terms"
                    value="1"
                    x-model="agreed"
                    class="w-4 h-4 mt-0.5 rounded accent-[#1f442e] flex-shrink-0 cursor-pointer"
                    required
                >
                <span class="leading-relaxed">
                    أوافق على
                    <a href="#" class="font-bold text-[#1f442e] hover:underline">شروط الاستخدام</a>
                    و
                    <a href="#" class="font-bold text-[#1f442e] hover:underline">سياسة الخصوصية</a>
                </span>
            </label>

            <!-- Submit -->
            <button
                type="submit"
                class="btn-gold w-full text-[#0c1d14] font-bold rounded-2xl py-3.5 shadow-lg shadow-[#e7bd54]/20 hover:shadow-[#e7bd54]/40 mt-2 transition-all"
            >
                متابعة
            </button>
        </form>

        <!-- Back to login -->
        <div class="text-center mt-5">
            <p class="text-sm text-slate-500">
                لديك حساب؟
                <a href="<?php echo e(route('login')); ?>" class="font-extrabold text-[#1f442e] hover:text-[#dba933] transition-colors underline-offset-4 hover:underline">
                    تسجيل الدخول
                </a>
            </p>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function registerForm() {
    return {
        method: 'phone',
        agreed: false,
        captcha: false,
        phoneVal: <?php echo \Illuminate\Support\Js::from(old('phone_number'))->toHtml() ?>,
        emailVal: <?php echo \Illuminate\Support\Js::from(old('email'))->toHtml() ?>,
        countryOpen: false,
        countries: <?php echo json_encode($countries, 15, 512) ?>,
        selectedCountry: '+967',
        handleSubmit() {
            if (this.method === 'phone') {
                this.$el.querySelector('[name=country_code]').value = this.selectedCountry.replace('+', '');
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\AracariBackend\resources\views/auth/register.blade.php ENDPATH**/ ?>