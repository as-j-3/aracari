<?php $__env->startSection('title', 'تواصل معنا | أراكاري'); ?>

<?php $__env->startSection('content'); ?>

<section class="relative pt-28 pb-20 overflow-hidden bg-gradient-to-br from-[#0c1d14] via-[#0c1d14] to-[#1f442e]">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-[#1f442e]/30 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-[#e7bd54]/10 rounded-full blur-[100px]"></div>
    </div>
    <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(#e7bd54 1px, transparent 1px); background-size: 24px 24px;"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        <div class="flex items-center justify-center gap-3 mb-6">
            <div class="w-16 h-0.5 bg-[#e7bd54] rounded-full"></div>
            <span class="text-[#e7bd54] font-bold text-sm uppercase tracking-[0.2em]">تواصل معنا</span>
            <div class="w-16 h-0.5 bg-[#e7bd54] rounded-full"></div>
        </div>

        <h1 class="text-4xl md:text-6xl font-black text-white leading-tight mb-4">
            هنا للمساعدة
            <span class="text-gold-gradient">على مدار الساعة</span>
        </h1>

        <p class="text-lg md:text-xl text-white/60 max-w-3xl mx-auto leading-relaxed">
            فريق أراكاري جاهز للرد على استفساراتك ومساعدتك في اختيار أفضل رحلة برية في اليمن بسهولة وسرعة.
        </p>
    </div>
</section>


<div class="relative z-20 -mt-12 pb-20 bg-[#0c1d14]">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        <div class="xl:col-span-2 space-y-8">
            
            <div class="bg-white/5 backdrop-blur border border-white/10 rounded-3xl p-8 shadow-xl hover:border-white/20 transition-all duration-300">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-14 h-14 rounded-3xl bg-gradient-to-br from-[#1f442e] to-[#316b44] flex items-center justify-center text-[#e7bd54] shadow-lg shadow-[#e7bd54]/20">
                        <i data-lucide="send" class="w-7 h-7"></i>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-white">أرسل لنا رسالة</h2>
                        <p class="text-white/60 mt-1">سنرد عليك في خلال 24 ساعة أو أسرع.</p>
                    </div>
                </div>

                <form action="#" method="POST" class="space-y-6">
                    <?php echo csrf_field(); ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <label class="block">
                            <span class="text-sm font-bold text-white/80">الاسم الكامل</span>
                            <input type="text" name="name" required class="mt-2 w-full rounded-3xl border border-white/10 bg-white/5 py-3 px-4 text-white placeholder:text-white/40 outline-none focus:border-[#e7bd54] focus:ring-2 focus:ring-[#e7bd54]/20 transition" placeholder="ادخل اسمك">
                        </label>
                        <label class="block">
                            <span class="text-sm font-bold text-white/80">البريد الإلكتروني</span>
                            <input type="email" name="email" required class="mt-2 w-full rounded-3xl border border-white/10 bg-white/5 py-3 px-4 text-white placeholder:text-white/40 outline-none focus:border-[#e7bd54] focus:ring-2 focus:ring-[#e7bd54]/20 transition" placeholder="your@example.com">
                        </label>
                    </div>

                    <label class="block">
                        <span class="text-sm font-bold text-white/80">الموضوع</span>
                        <input type="text" name="subject" required class="mt-2 w-full rounded-3xl border border-white/10 bg-white/5 py-3 px-4 text-white placeholder:text-white/40 outline-none focus:border-[#e7bd54] focus:ring-2 focus:ring-[#e7bd54]/20 transition" placeholder="اكتب موضوع الرسالة">
                    </label>

                    <label class="block">
                        <span class="text-sm font-bold text-white/80">الرسالة</span>
                        <textarea name="message" rows="6" required class="mt-2 w-full rounded-3xl border border-white/10 bg-white/5 py-3 px-4 text-white placeholder:text-white/40 outline-none focus:border-[#e7bd54] focus:ring-2 focus:ring-[#e7bd54]/20 transition resize-none" placeholder="كيف يمكننا مساعدتك؟"></textarea>
                    </label>

                    <button type="submit" class="btn-gold px-8 py-3.5 rounded-3xl text-sm font-bold inline-flex items-center gap-2">
                        إرسال الرسالة
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>

            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white/5 backdrop-blur border border-white/10 rounded-3xl p-6 hover:border-white/20 transition-all duration-300">
                    <div class="w-14 h-14 rounded-3xl bg-gradient-to-br from-[#1f442e] to-[#316b44] text-[#e7bd54] mb-4 flex items-center justify-center">
                        <i data-lucide="phone" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">الهاتف</h3>
                    <p class="text-white/60" dir="ltr">+967 1 234 5678</p>
                </div>

                <div class="bg-white/5 backdrop-blur border border-white/10 rounded-3xl p-6 hover:border-white/20 transition-all duration-300">
                    <div class="w-14 h-14 rounded-3xl bg-gradient-to-br from-[#1f442e] to-[#316b44] text-[#e7bd54] mb-4 flex items-center justify-center">
                        <i data-lucide="mail" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">البريد الإلكتروني</h3>
                    <p class="text-white/60">info@aracari.com</p>
                </div>

                <div class="bg-white/5 backdrop-blur border border-white/10 rounded-3xl p-6 hover:border-white/20 transition-all duration-300">
                    <div class="w-14 h-14 rounded-3xl bg-gradient-to-br from-[#e7bd54] to-[#d49920] text-[#0c1d14] mb-4 flex items-center justify-center">
                        <i data-lucide="map-pin" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">العنوان</h3>
                    <p class="text-white/60">صنعاء، اليمن</p>
                </div>

                <div class="bg-white/5 backdrop-blur border border-white/10 rounded-3xl p-6 hover:border-white/20 transition-all duration-300">
                    <div class="w-14 h-14 rounded-3xl bg-gradient-to-br from-red-500 to-red-700 text-white mb-4 flex items-center justify-center">
                        <i data-lucide="clock" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">ساعات العمل</h3>
                    <p class="text-white/60">الأحد - الخميس: 8:00 ص - 8:00 م</p>
                    <p class="text-white/60">الجمعة - السبت: مغلق</p>
                </div>
            </div>
        </div>

        
        <aside class="space-y-6">
            <div class="bg-white/5 backdrop-blur border border-white/10 rounded-3xl p-8 hover:border-white/20 transition-all duration-300">
                <h3 class="text-2xl font-bold text-white mb-4">لماذا تختارنا؟</h3>
                <ul class="space-y-4 text-white/60">
                    <li class="flex items-start gap-3">
                        <i data-lucide="check-circle" class="w-5 h-5 text-[#e7bd54] mt-1 shrink-0"></i>
                        <span>دعم سريع ومتواصل حتى عند الحاجة.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <i data-lucide="check-circle" class="w-5 h-5 text-[#e7bd54] mt-1 shrink-0"></i>
                        <span>أفضل العروض والأسعار المضمونة.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <i data-lucide="check-circle" class="w-5 h-5 text-[#e7bd54] mt-1 shrink-0"></i>
                        <span>تجربة سهلة وموثوقة لحجز رحلاتك البرية.</span>
                    </li>
                </ul>
            </div>

            <div class="bg-white/5 backdrop-blur border border-white/10 rounded-3xl p-8 hover:border-white/20 transition-all duration-300">
                <h3 class="text-2xl font-bold text-white mb-4">الأسئلة الشائعة</h3>
                <p class="text-white/50 text-sm mb-4">لم تجد إجابتك؟ تفضل بزيارة صفحة الأسئلة الشائعة.</p>
                <a href="<?php echo e(route('pages.faq')); ?>" class="btn-gold block text-center px-6 py-3 rounded-xl text-sm font-bold">
                    الأسئلة الشائعة
                </a>
            </div>
        </aside>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>lucide.createIcons();</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.web', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/pages/contact.blade.php ENDPATH**/ ?>