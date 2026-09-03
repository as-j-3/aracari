<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'أراكاري'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <?php if(config('services.recaptcha.site_key')): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        cairo: ['Cairo', 'sans-serif'],
                        poppins: ['Poppins', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Cairo', sans-serif; }
        [x-cloak] { display: none !important; }

        /* CSS Transition equivalents of framer-motion */
        .fade-in-up {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.6s ease-out forwards;
        }
        .fade-in-up-delay-1 { animation-delay: 0.1s; }
        .fade-in-up-delay-2 { animation-delay: 0.2s; }
        .fade-in-up-delay-3 { animation-delay: 0.3s; }
        .fade-in-up-delay-4 { animation-delay: 0.4s; }
        .fade-in-up-delay-5 { animation-delay: 0.5s; }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }
        .pulse-dot { animation: pulse-dot 3s ease-in-out infinite; }

        /* Gold button hover effect */
        .btn-gold {
            background: linear-gradient(to left, #e7bd54, #dba933);
            transition: all 0.3s ease;
        }
        .btn-gold:hover {
            background: linear-gradient(to left, #d49920, #dba933);
            box-shadow: 0 10px 25px -5px rgba(231, 189, 84, 0.35);
            transform: translateY(-1px);
        }
        .btn-gold:active {
            transform: translateY(0);
        }

        /* Feature card hover */
        .feature-card {
            transition: all 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-4px);
            background: rgba(255, 255, 255, 0.12);
        }

        /* Toggle button styles */
        .toggle-active {
            background: #1f442e;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .toggle-inactive {
            background: transparent;
            color: #438656;
        }

        /* OTP input focus */
        .otp-box {
            transition: all 0.2s ease;
        }
        .otp-box:focus {
            border-color: #dba933;
            box-shadow: 0 0 0 4px rgba(231, 189, 84, 0.2);
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
        ::-webkit-scrollbar-thumb { background: rgba(31, 68, 46, 0.4); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(31, 68, 46, 0.6); }

        /* Navbar scroll transition */
        .nav-scrolled {
            background: rgba(12, 29, 20, 0.95);
            backdrop-filter: blur(12px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body class="font-cairo min-h-screen bg-[#0c1d14] text-white flex flex-col">

    <!-- ===== NAVBAR ===== -->
    <header
        x-data="{ mobileMenuOpen: false }"
        id="authNavbar"
        class="fixed top-0 inset-x-0 z-50 transition-all duration-300"
    >
        <nav class="max-w-7xl mx-auto px-4 md:px-8 py-3 flex items-center justify-between">
            <a href="<?php echo e(route('home')); ?>" class="flex items-center gap-3 group cursor-pointer">
                <div class="relative w-10 h-10 rounded-xl bg-[#1f442e] flex items-center justify-center shadow-lg ring-2 ring-[#e7bd54]/40 group-hover:ring-[#e7bd54] transition">
                    <svg class="w-5 h-5" viewBox="0 0 32 32" aria-hidden="true">
                        <path d="M16 4 L4 28 H10 L16 14 L22 28 H28 Z" fill="#e7bd54" />
                        <circle cx="16" cy="22" r="2" fill="#f9f5ec" />
                    </svg>
                </div>
                <div class="leading-tight text-right">
                    <div class="text-base font-extrabold text-white tracking-tight">أراكاري</div>
                    <div class="text-[9px] uppercase tracking-[0.25em] text-[#e7bd54]" style="font-family: Poppins, sans-serif;">ARACARI</div>
                </div>
            </a>

            <ul class="hidden lg:flex items-center gap-6 text-white/80 text-sm font-semibold">
                <li><a href="<?php echo e(route('home')); ?>#welcome" class="hover:text-[#e7bd54] transition">الرئيسية</a></li>
                <li><a href="<?php echo e(route('home')); ?>#features" class="hover:text-[#e7bd54] transition">خدماتنا</a></li>
                <li><a href="<?php echo e(route('home')); ?>#fleet" class="hover:text-[#e7bd54] transition">أسطولنا</a></li>
                <li><a href="<?php echo e(route('home')); ?>#destinations" class="hover:text-[#e7bd54] transition">الوجهات</a></li>
                <li><a href="<?php echo e(route('home')); ?>#app" class="hover:text-[#e7bd54] transition">التطبيق</a></li>
                <li><a href="<?php echo e(route('home')); ?>#contact" class="hover:text-[#e7bd54] transition">تواصل معنا</a></li>
            </ul>

            <div class="flex items-center gap-2">
                <a href="<?php echo e(route('home')); ?>#cta" class="hidden md:inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-[#d49920] hover:bg-[#dba933] text-[#0c1d14] font-bold text-xs shadow-lg transition">
                    احجز الآن
                </a>
                <button
                    x-on:click="mobileMenuOpen = !mobileMenuOpen"
                    class="lg:hidden p-2 text-white"
                    aria-label="القائمة"
                >
                    <i data-lucide="menu" class="w-6 h-6" x-show="!mobileMenuOpen"></i>
                    <i data-lucide="x" class="w-6 h-6" x-show="mobileMenuOpen" x-cloak></i>
                </button>
            </div>
        </nav>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="lg:hidden bg-[#0c1d14]/98 backdrop-blur-md border-t border-white/10 overflow-hidden">
            <ul class="px-6 py-4 space-y-1">
                <li><a href="<?php echo e(route('home')); ?>#welcome" class="block py-2.5 px-3 rounded-lg hover:bg-white/10 text-sm">الرئيسية</a></li>
                <li><a href="<?php echo e(route('home')); ?>#features" class="block py-2.5 px-3 rounded-lg hover:bg-white/10 text-sm">خدماتنا</a></li>
                <li><a href="<?php echo e(route('home')); ?>#fleet" class="block py-2.5 px-3 rounded-lg hover:bg-white/10 text-sm">أسطولنا</a></li>
                <li><a href="<?php echo e(route('home')); ?>#destinations" class="block py-2.5 px-3 rounded-lg hover:bg-white/10 text-sm">الوجهات</a></li>
                <li><a href="<?php echo e(route('home')); ?>#app" class="block py-2.5 px-3 rounded-lg hover:bg-white/10 text-sm">التطبيق</a></li>
                <li><a href="<?php echo e(route('home')); ?>#contact" class="block py-2.5 px-3 rounded-lg hover:bg-white/10 text-sm">تواصل معنا</a></li>
                <li class="pt-2">
                    <a href="<?php echo e(route('home')); ?>#cta" class="block w-full text-center py-2.5 rounded-full bg-[#d49920] text-[#0c1d14] font-bold text-sm">احجز الآن</a>
                </li>
            </ul>
        </div>
    </header>

    <!-- ===== SPLIT-SCREEN MAIN ===== -->
    <main class="flex-1 flex items-stretch min-h-screen pt-16">

        <!-- LEFT: Brand Showcase (hidden on mobile) -->
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-gradient-to-br from-[#0c1d14] via-[#163022] to-[#1f442e] flex-col justify-center px-12 xl:px-20 py-12">
            <!-- Decorative blurs -->
            <div class="absolute top-0 right-0 w-[500px] h-[500px] rounded-full bg-[#e7bd54]/8 blur-3xl pointer-events-none"></div>
            <div class="absolute bottom-0 left-0 w-[400px] h-[400px] rounded-full bg-[#438656]/15 blur-3xl pointer-events-none"></div>
            <div class="absolute top-1/2 left-1/3 w-[300px] h-[300px] rounded-full bg-[#d49920]/5 blur-3xl pointer-events-none"></div>

            <!-- Decorative grid pattern -->
            <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle, #e7bd54 1px, transparent 1px); background-size: 32px 32px;"></div>

            <div class="relative z-10 max-w-lg">
                <!-- Headline -->
                <div class="fade-in-up">
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur ring-1 ring-white/20 text-[#e7bd54] text-xs font-bold mb-6">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#e7bd54] pulse-dot"></span>
                        منصة السفر الأولى في اليمن
                    </span>
                    <h2 class="text-4xl xl:text-5xl font-extrabold text-white leading-tight mb-4">
                        رحلتك تبدأ
                        <br>
                        <span class="bg-gradient-to-l from-[#e7bd54] to-[#dba933] bg-clip-text text-transparent">من هنا</span>
                    </h2>
                    <p class="text-white/60 text-lg leading-relaxed mb-10">
                        انضم لآلاف المسافرين الذين اختاروا أراكاري لرحلاتهم. حجوزات فورية، أسطول فاخر، وأسعار تنافسية.
                    </p>
                </div>

                <!-- Features grid -->
                <div class="grid grid-cols-2 gap-4 mb-10 fade-in-up fade-in-up-delay-1">
                    <div class="feature-card group flex items-center gap-3 p-4 rounded-2xl bg-white/5 backdrop-blur border border-white/10 hover:bg-white/10 hover:border-[#e7bd54]/20 transition-all duration-300">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#1f442e] to-[#316b44] flex items-center justify-center group-hover:from-[#e7bd54] group-hover:to-[#dba933] transition-all duration-300 flex-shrink-0">
                            <i data-lucide="bus" class="w-5 h-5 text-white group-hover:text-[#0c1d14] transition-colors"></i>
                        </div>
                        <div>
                            <p class="font-bold text-white text-sm">حجز فوري</p>
                            <p class="text-xs text-white/50">احجز رحلتك في ثوانٍ</p>
                        </div>
                    </div>
                    <div class="feature-card group flex items-center gap-3 p-4 rounded-2xl bg-white/5 backdrop-blur border border-white/10 hover:bg-white/10 hover:border-[#e7bd54]/20 transition-all duration-300">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#1f442e] to-[#316b44] flex items-center justify-center group-hover:from-[#e7bd54] group-hover:to-[#dba933] transition-all duration-300 flex-shrink-0">
                            <i data-lucide="shield-check" class="w-5 h-5 text-white group-hover:text-[#0c1d14] transition-colors"></i>
                        </div>
                        <div>
                            <p class="font-bold text-white text-sm">دفع آمن</p>
                            <p class="text-xs text-white/50">معاملات مشفّرة 100%</p>
                        </div>
                    </div>
                    <div class="feature-card group flex items-center gap-3 p-4 rounded-2xl bg-white/5 backdrop-blur border border-white/10 hover:bg-white/10 hover:border-[#e7bd54]/20 transition-all duration-300">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#1f442e] to-[#316b44] flex items-center justify-center group-hover:from-[#e7bd54] group-hover:to-[#dba933] transition-all duration-300 flex-shrink-0">
                            <i data-lucide="wallet" class="w-5 h-5 text-white group-hover:text-[#0c1d14] transition-colors"></i>
                        </div>
                        <div>
                            <p class="font-bold text-white text-sm">محفظة رقمية</p>
                            <p class="text-xs text-white/50">رصيدك بين يديك</p>
                        </div>
                    </div>
                    <div class="feature-card group flex items-center gap-3 p-4 rounded-2xl bg-white/5 backdrop-blur border border-white/10 hover:bg-white/10 hover:border-[#e7bd54]/20 transition-all duration-300">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#1f442e] to-[#316b44] flex items-center justify-center group-hover:from-[#e7bd54] group-hover:to-[#dba933] transition-all duration-300 flex-shrink-0">
                            <i data-lucide="clock" class="w-5 h-5 text-white group-hover:text-[#0c1d14] transition-colors"></i>
                        </div>
                        <div>
                            <p class="font-bold text-white text-sm">دعم 24/7</p>
                            <p class="text-xs text-white/50">فريقنا دائماً متاح</p>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="flex items-center gap-8 mb-10 fade-in-up fade-in-up-delay-2">
                    <div>
                        <div class="text-3xl font-extrabold text-[#e7bd54]" style="font-family: Poppins, sans-serif;">+50K</div>
                        <div class="text-xs text-white/50 mt-1">راكب سعيد</div>
                    </div>
                    <div class="w-px h-12 bg-white/10"></div>
                    <div>
                        <div class="text-3xl font-extrabold text-[#e7bd54]" style="font-family: Poppins, sans-serif;">+25</div>
                        <div class="text-xs text-white/50 mt-1">وجهة سياحية</div>
                    </div>
                    <div class="w-px h-12 bg-white/10"></div>
                    <div>
                        <div class="text-3xl font-extrabold text-[#e7bd54]" style="font-family: Poppins, sans-serif;">4.9</div>
                        <div class="text-xs text-white/50 mt-1 flex items-center gap-0.5">
                            <i data-lucide="star" class="w-3 h-3 fill-[#e7bd54] text-[#e7bd54]"></i>
                            تقييم
                        </div>
                    </div>
                </div>

                <!-- Testimonial -->
                <div class="relative p-6 rounded-3xl bg-gradient-to-br from-white/5 to-transparent border border-white/10 fade-in-up fade-in-up-delay-3">
                    <i data-lucide="quote" class="w-8 h-8 text-[#e7bd54]/30 absolute top-4 left-4"></i>
                    <p class="text-white/70 text-sm leading-relaxed mb-4 pr-8">
                        "تجربة سفر استثنائية! حجزت رحلتي من صنعاء إلى عدن خلال دقائق، ووصلت بأمان وراحة تامة."
                    </p>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#e7bd54] to-[#dba933] flex items-center justify-center text-[#0c1d14] font-bold text-sm">
                            أ
                        </div>
                        <div>
                            <p class="font-bold text-white text-sm">أحمد الشامي</p>
                            <p class="text-xs text-white/40">راكب دائم</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: Form Area -->
        <div class="w-full lg:w-1/2 flex items-center justify-center px-4 sm:px-8 py-12 relative">
            <!-- Subtle decorative blurs for mobile -->
            <div class="lg:hidden absolute top-0 right-0 w-[300px] h-[300px] rounded-full bg-[#e7bd54]/8 blur-3xl pointer-events-none"></div>
            <div class="lg:hidden absolute bottom-0 left-0 w-[300px] h-[300px] rounded-full bg-[#1f442e]/20 blur-3xl pointer-events-none"></div>

            <div class="relative z-10 w-full max-w-lg">
                <?php echo $__env->yieldContent('content'); ?>
            </div>
        </div>
    </main>

    <!-- ===== FOOTER ===== -->
    <footer class="bg-[#0c1d14] border-t border-white/10 py-6">
        <div class="max-w-7xl mx-auto px-4 md:px-8 flex flex-col md:flex-row items-center justify-between gap-3 text-sm text-white/40">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-[#1f442e] flex items-center justify-center ring-1 ring-[#e7bd54]/30">
                    <svg class="w-4 h-4" viewBox="0 0 32 32" aria-hidden="true">
                        <path d="M16 4 L4 28 H10 L16 14 L22 28 H28 Z" fill="#e7bd54" />
                        <circle cx="16" cy="22" r="2" fill="#f9f5ec" />
                    </svg>
                </div>
                <span>&copy; <?php echo e(date('Y')); ?> أراكاري — جميع الحقوق محفوظة</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="#" class="hover:text-[#e7bd54] transition">سياسة الخصوصية</a>
                <span class="opacity-30">&bull;</span>
                <a href="#" class="hover:text-[#e7bd54] transition">الشروط والأحكام</a>
                <span class="opacity-30">&bull;</span>
                <div class="flex items-center gap-2">
                    <a href="#" aria-label="WhatsApp" class="w-7 h-7 rounded-lg bg-white/5 hover:bg-[#d49920] hover:text-[#0c1d14] flex items-center justify-center transition">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                    </a>
                    <a href="#" aria-label="Facebook" class="w-7 h-7 rounded-lg bg-white/5 hover:bg-[#d49920] hover:text-[#0c1d14] flex items-center justify-center transition">
                        <i data-lucide="facebook" class="w-3.5 h-3.5"></i>
                    </a>
                    <a href="#" aria-label="Instagram" class="w-7 h-7 rounded-lg bg-white/5 hover:bg-[#d49920] hover:text-[#0c1d14] flex items-center justify-center transition">
                        <i data-lucide="instagram" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('authNavbar');
            if (navbar) {
                if (window.scrollY > 40) {
                    navbar.classList.add('nav-scrolled');
                } else {
                    navbar.classList.remove('nav-scrolled');
                }
            }
        }, { passive: true });

        // Init lucide icons
        lucide.createIcons();

        // Re-create icons after Alpine renders (for x-show toggles)
        document.addEventListener('alpine:initialized', () => {
            setTimeout(() => { lucide.createIcons(); }, 100);
        });
    </script>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH E:\AracariBackend\resources\views/layouts/auth.blade.php ENDPATH**/ ?>