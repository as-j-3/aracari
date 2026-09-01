<?php $__env->startSection('title', 'الأسئلة الشائعة | أراكاري'); ?>

<?php $__env->startSection('content'); ?>

<section class="relative pt-28 pb-20 overflow-hidden bg-gradient-to-br from-[#0c1d14] via-[#0c1d14] to-[#1f442e]">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-[#1f442e]/30 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-[#e7bd54]/10 rounded-full blur-[100px]"></div>
    </div>
    
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        <div class="flex items-center justify-center gap-3 mb-6">
            <div class="w-16 h-0.5 bg-[#e7bd54] rounded-full"></div>
            <span class="text-[#e7bd54] font-bold text-sm uppercase tracking-[0.2em]">مساعدة</span>
            <div class="w-16 h-0.5 bg-[#e7bd54] rounded-full"></div>
        </div>
        
        <h1 class="text-4xl md:text-5xl font-black text-white leading-tight mb-4">
            الأسئلة الشائعة
        </h1>
        
        <p class="text-lg text-white/60 max-w-3xl mx-auto leading-relaxed">
            إجابات على الأسئلة الأكثر شيوعاً حول خدمتنا
        </p>
    </div>
</section>


<div class="relative z-20 -mt-12 pb-20 bg-[#0c1d14]">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="space-y-3">
        <?php
            $faqs = [
                ['q' => 'كيف يمكنني حجز رحلة؟', 'a' => 'يمكنك حجز رحلة بسهولة من خلال منصتنا. اختر مدينة المغادرة والوصول، حدد تاريخ الرحلة، ثم اختر من بين الرحلات المتاحة. أكمل الحجز عن طريق إدخال بياناتك الشخصية والدفع.'],
                ['q' => 'ما هي طرق الدفع المتاحة؟', 'a' => 'ندعم طرق دفع متعددة بما في ذلك البطاقات الائتمانية، التحويل البنكي، والدفع عند الصعود في بعض الحالات. يمكنك أيضاً استخدام رصيدك في المحفظة الرقمية للدفع.'],
                ['q' => 'هل يمكنني إلغاء حجزي؟', 'a' => 'نعم، يمكنك إلغاء حجزك قبل موعد الرحلة بفترة زمنية محددة. سياسة الإلغاء تعتمد على نوع التذكرة والوقت المتبقي قبل الرحلة. يمكنك إلغاء الحجز من خلال صفحة حجوزاتي.'],
                ['q' => 'كيف يمكنني استخدام كوبون الخصم؟', 'a' => 'يمكنك إدخال كود الكوبون في صفحة الدفع عند إتمام الحجز. سيتم تطبيق الخصم تلقائياً على السعر الإجمالي إذا كان الكوبون صالحاً وغير منتهي الصلاحية.'],
                ['q' => 'ما هي سياسة استرداد الأموال؟', 'a' => 'سياسة الاسترداد تعتمد على وقت الإلغاء ونوع التذكرة. الإلغاء قبل 24 ساعة: استرداد كامل. قبل 12 ساعة: استرداد 50%. قبل 6 ساعات: لا يوجد استرداد. يتم إرجاع المبلغ إلى محفظتك الرقمية.'],
                ['q' => 'كيف يمكنني التواصل مع الدعم الفني؟', 'a' => 'يمكنك التواصل معنا عبر نموذج الاتصال في صفحة "تواصل معنا"، أو عبر البريد الإلكتروني info@aracari.com، أو عبر الهاتف +967 1 234 5678. متاحون من الأحد إلى الخميس 8:00 ص - 8:00 م.'],
            ];
        ?>

        <?php $__currentLoopData = $faqs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $faq): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="bg-white/5 backdrop-blur border border-white/10 rounded-2xl overflow-hidden hover:border-white/20 transition-all duration-300">
            <details class="group">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-white/5 transition-colors">
                    <h3 class="text-base font-bold text-white flex items-center gap-3">
                        <span class="w-7 h-7 rounded-lg bg-[#e7bd54]/20 flex items-center justify-center text-[#e7bd54] text-xs font-black shrink-0"><?php echo e($i + 1); ?></span>
                        <?php echo e($faq['q']); ?>

                    </h3>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-[#e7bd54] transition-transform duration-300 group-open:rotate-180 shrink-0"></i>
                </summary>
                <div class="px-5 pb-5 pt-0">
                    <p class="text-white/60 leading-relaxed pr-10"><?php echo e($faq['a']); ?></p>
                </div>
            </details>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    
    <div class="mt-12 bg-gradient-to-br from-[#1f442e] to-[#0c1d14] rounded-3xl p-8 md:p-10 text-center border border-white/10">
        <h2 class="text-2xl font-bold text-white mb-3">لم تجد إجابة على سؤالك؟</h2>
        <p class="text-white/50 mb-6">فريق الدعم جاهز لمساعدتك على مدار الساعة</p>
        <a href="<?php echo e(route('pages.contact')); ?>" class="btn-gold inline-flex items-center gap-2 px-8 py-3.5 rounded-xl font-bold">
            <span>تواصل معنا</span>
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>lucide.createIcons();</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.web', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/pages/faq.blade.php ENDPATH**/ ?>