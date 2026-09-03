<?php $__env->startSection('content'); ?>

<?php
$cityGradients = ['from-emerald-800 to-teal-900','from-amber-800 to-orange-900','from-slate-700 to-slate-900','from-cyan-800 to-teal-900','from-indigo-800 to-slate-900','from-rose-800 to-pink-900'];
$gradientIndex = 0;
?>
<!-- ==================== SECTION 2: HERO ==================== -->
<section id="hero" class="relative min-h-screen flex items-center justify-center overflow-hidden">
  <!-- Background Image -->
  <div class="absolute inset-0">
    <img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1920&q=80" alt="" class="w-full h-full object-cover">
    <div class="absolute inset-0 bg-gradient-to-b from-[#0c1d14]/90 via-[#163022]/55 to-[#163022]/20"></div>
  </div>

  <!-- Decorative elements -->
  <div class="absolute top-20 left-10 w-72 h-72 bg-[#e7bd54]/5 rounded-full blur-3xl"></div>
  <div class="absolute bottom-20 right-10 w-96 h-96 bg-[#316b44]/10 rounded-full blur-3xl"></div>

  <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 text-center pt-24 pb-12">
    <!-- Badge -->
    <div class="inline-flex items-center gap-2 glass rounded-full px-5 py-2 mb-8 animate-fade-in-up">
      <span class="relative flex h-2.5 w-2.5">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#e7bd54] opacity-75"></span>
        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#e7bd54]"></span>
      </span>
      <span class="text-sm font-medium text-white/90">خدمة الحافلات السياحية الأولى في اليمن</span>
    </div>

    <!-- Heading -->
    <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-black leading-tight mb-6 animate-fade-in-up animate-delay-100">
      <span class="text-gold-gradient">خُذْ رحلة طيران</span>
      <br>
      <span class="text-white">على الطريق</span>
    </h1>

    <!-- Paragraph -->
    <p class="text-base sm:text-lg text-white/70 max-w-2xl mx-auto mb-10 leading-relaxed animate-fade-in-up animate-delay-200">
      أراكاري تقدم لك تجربة سفر فريدة تجمع بين الراحة والأمان. اكتشف جمال اليمن مع أسطول حديث وخدمة عملاء متميزة على مدار الساعة.
    </p>

    <!-- CTA Buttons -->
    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16 animate-fade-in-up animate-delay-300">
      <a href="#cta" class="btn-gold px-8 py-3.5 text-base rounded-full w-full sm:w-auto text-center">
        احجز رحلتك الآن
        <i data-lucide="arrow-left" class="w-4 h-4 inline-block mr-1 align-middle"></i>
      </a>
      <a href="#services" class="glass px-8 py-3.5 text-base font-medium rounded-full text-white/90 hover:text-white w-full sm:w-auto text-center transition-colors">
        اكتشف الخدمات
      </a>
    </div>

    <!-- Stats Strip -->
    <div class="flex flex-wrap items-center justify-center gap-8 sm:gap-12 animate-fade-in-up animate-delay-400">
      <div class="text-center">
        <div class="text-2xl sm:text-3xl font-black text-[#e7bd54]">+<?php echo e(number_format((int)($stats['total_cities'] ?? 25), 0)); ?></div>
        <div class="text-xs sm:text-sm text-white/50 mt-1">وجهة سياحية</div>
      </div>
      <div class="w-px h-10 bg-white/10 hidden sm:block"></div>
      <div class="text-center">
        <div class="text-2xl sm:text-3xl font-black text-[#e7bd54]">+<?php echo e(number_format((int)($stats['total_trips'] ?? 500), 0)); ?></div>
        <div class="text-xs sm:text-sm text-white/50 mt-1">رحلة متاحة</div>
      </div>
      <div class="w-px h-10 bg-white/10 hidden sm:block"></div>
      <div class="text-center">
        <div class="text-2xl sm:text-3xl font-black text-[#e7bd54]">+<?php echo e(number_format((int)($stats['total_vehicles'] ?? 50), 0)); ?></div>
        <div class="text-xs sm:text-sm text-white/50 mt-1">مركبة حديثة</div>
      </div>
      <div class="w-px h-10 bg-white/10 hidden sm:block"></div>
      <div class="text-center">
        <div class="text-2xl sm:text-3xl font-black text-[#e7bd54]">24/7</div>
        <div class="text-xs sm:text-sm text-white/50 mt-1">دعم متواصل</div>
      </div>
    </div>
  </div>

  <!-- Scroll Indicator -->
  <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-float">
    <div class="w-6 h-10 border-2 border-white/20 rounded-full flex justify-center pt-2">
      <div class="w-1 h-2.5 bg-[#e7bd54] rounded-full animate-bounce"></div>
    </div>
  </div>
</section>

<!-- ==================== SECTION 3: WELCOME / SERVICES ==================== -->
<section id="services" class="relative bg-[#f9f5ec] py-20 lg:py-28">
  <div class="dot-pattern absolute inset-0 opacity-50"></div>
  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
      <!-- Left Content -->
      <div>
        <span class="inline-flex items-center gap-2 bg-[#1f442e]/10 text-[#1f442e] text-xs font-bold px-4 py-1.5 rounded-full mb-6">
          <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
          مرحباً بك في أراكاري
        </span>
        <h2 class="text-3xl sm:text-4xl font-black text-[#0c1d14] leading-tight mb-5">
          كل ما تحتاجه لرحلة
          <span class="text-gold-gradient">مثالية</span>
          في مكان واحد
        </h2>
        <p class="text-[#1f442e]/70 leading-relaxed mb-8">
          نوفر لك منصة متكاملة لحجز الرحلات البرية، مع خيارات متعددة للدفع وتتبع الرحلة لحظة بلحظة. استمتع بتجربة سفر آمنة ومريحة مع أراكاري.
        </p>
        <a href="#cta" class="inline-flex items-center gap-2 bg-[#1f442e] text-white px-6 py-3 rounded-full font-bold text-sm hover:bg-[#163022] transition-colors">
          ابدأ الحجز الآن
          <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
      </div>

      <!-- Right: Service Tiles 2x2 -->
      <div class="grid grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow border border-[#1f442e]/5">
          <div class="w-11 h-11 rounded-xl bg-[#1f442e]/10 flex items-center justify-center mb-3">
            <i data-lucide="bus" class="w-5 h-5 text-[#1f442e]"></i>
          </div>
          <h3 class="font-bold text-[#0c1d14] text-sm mb-1">حجز باص</h3>
          <p class="text-xs text-[#1f442e]/60 leading-relaxed">اختر من بين مئات الرحلات المتاحة</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow border border-[#1f442e]/5">
          <div class="w-11 h-11 rounded-xl bg-[#e7bd54]/15 flex items-center justify-center mb-3">
            <i data-lucide="hotel" class="w-5 h-5 text-[#d49920]"></i>
          </div>
          <h3 class="font-bold text-[#0c1d14] text-sm mb-1">حجز فندق</h3>
          <p class="text-xs text-[#1f442e]/60 leading-relaxed">أفضل الفنادق بأسعار تنافسية</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow border border-[#1f442e]/5">
          <div class="w-11 h-11 rounded-xl bg-[#316b44]/10 flex items-center justify-center mb-3">
            <i data-lucide="car" class="w-5 h-5 text-[#316b44]"></i>
          </div>
          <h3 class="font-bold text-[#0c1d14] text-sm mb-1">حجز سيارة</h3>
          <p class="text-xs text-[#1f442e]/60 leading-relaxed">سيارات بأحدث الموديلات</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow border border-[#1f442e]/5">
          <div class="w-11 h-11 rounded-xl bg-[#e7bd54]/15 flex items-center justify-center mb-3">
            <i data-lucide="shield-check" class="w-5 h-5 text-[#d49920]"></i>
          </div>
          <h3 class="font-bold text-[#0c1d14] text-sm mb-1">دفع آمن</h3>
          <p class="text-xs text-[#1f442e]/60 leading-relaxed">حماية كاملة لبياناتك المالية</p>
        </div>
      </div>
    </div>

    <!-- Promo Strip -->
    <div class="mt-14 bg-gradient-to-l from-[#1f442e] via-[#316b44] to-[#1f442e] rounded-2xl p-6 sm:p-8 flex flex-col sm:flex-row items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-[#e7bd54]/20 flex items-center justify-center shrink-0">
          <i data-lucide="percent" class="w-6 h-6 text-[#e7bd54]"></i>
        </div>
        <div>
          <p class="text-white font-bold text-base">خصم 20% على رحلتك الأولى</p>
          <p class="text-white/60 text-sm">استخدم الكود عند الحجز</p>
        </div>
      </div>
      <div class="glass rounded-xl px-6 py-3 flex items-center gap-2">
        <span class="text-[#e7bd54] font-black text-lg tracking-wider">ARACARI20</span>
        <button onclick="navigator.clipboard.writeText('ARACARI20')" class="text-white/60 hover:text-white transition-colors">
          <i data-lucide="copy" class="w-4 h-4"></i>
        </button>
      </div>
    </div>
  </div>
</section>

<!-- ==================== SECTION 4: FEATURES ==================== -->
<section id="features" class="bg-white py-20 lg:py-28">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center max-w-2xl mx-auto mb-14">
      <span class="inline-flex items-center gap-2 bg-[#1f442e]/10 text-[#1f442e] text-xs font-bold px-4 py-1.5 rounded-full mb-5">
        <i data-lucide="zap" class="w-3.5 h-3.5"></i>
        لماذا أراكاري
      </span>
      <h2 class="text-3xl sm:text-4xl font-black text-[#0c1d14] leading-tight">
        تجربة سفر متكاملة
        <span class="text-gold-gradient">من البداية للنهاية</span>
      </h2>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
      <!-- Feature 1 -->
      <div class="group relative rounded-2xl overflow-hidden bg-[#f9f5ec] hover:shadow-xl transition-all duration-500">
        <div class="aspect-[4/3] overflow-hidden">
          <img src="https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=600&q=80" alt="حجز سريع" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
        </div>
        <div class="p-6">
          <div class="w-10 h-10 rounded-full bg-[#1f442e] flex items-center justify-center text-[#e7bd54] font-black text-sm mb-4">01</div>
          <h3 class="text-lg font-bold text-[#0c1d14] mb-2">حجز سريع وآمن</h3>
          <p class="text-sm text-[#1f442e]/60 leading-relaxed">احجز رحلتك في دقائق معدودة مع نظام حجز آمن ومحمي بأحدث تقنيات التشفير.</p>
        </div>
      </div>

      <!-- Feature 2 -->
      <div class="group relative rounded-2xl overflow-hidden bg-[#f9f5ec] hover:shadow-xl transition-all duration-500">
        <div class="aspect-[4/3] overflow-hidden">
          <img src="https://images.unsplash.com/photo-1580273916550-e323be2ae537?w=600&q=80" alt="تتبع مباشر" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
        </div>
        <div class="p-6">
          <div class="w-10 h-10 rounded-full bg-[#1f442e] flex items-center justify-center text-[#e7bd54] font-black text-sm mb-4">02</div>
          <h3 class="text-lg font-bold text-[#0c1d14] mb-2">تتبع الرحلة مباشرة</h3>
          <p class="text-sm text-[#1f442e]/60 leading-relaxed">تابع رحلتك لحظة بلحظة واعرف موقعها الحالي والوقت المتوقع للوصول.</p>
        </div>
      </div>

      <!-- Feature 3 -->
      <div class="group relative rounded-2xl overflow-hidden bg-[#f9f5ec] hover:shadow-xl transition-all duration-500">
        <div class="aspect-[4/3] overflow-hidden">
          <img src="https://images.unsplash.com/photo-1568605114967-8130f3a36994?w=600&q=80" alt="دعم متواصل" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
        </div>
        <div class="p-6">
          <div class="w-10 h-10 rounded-full bg-[#1f442e] flex items-center justify-center text-[#e7bd54] font-black text-sm mb-4">03</div>
          <h3 class="text-lg font-bold text-[#0c1d14] mb-2">دعم متواصل 24/7</h3>
          <p class="text-sm text-[#1f442e]/60 leading-relaxed">فريق دعم متخصص جاهز لمساعدتك على مدار الساعة عبر قنوات متعددة.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ==================== SECTION 5: FLEET ==================== -->
<section id="fleet" class="bg-[#0c1d14] py-20 lg:py-28 relative overflow-hidden">
  <div class="absolute top-0 right-0 w-96 h-96 bg-[#e7bd54]/5 rounded-full blur-3xl"></div>
  <div class="absolute bottom-0 left-0 w-72 h-72 bg-[#316b44]/10 rounded-full blur-3xl"></div>

  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
      <!-- Left Content -->
      <div>
        <span class="inline-flex items-center gap-2 bg-[#e7bd54]/15 text-[#e7bd54] text-xs font-bold px-4 py-1.5 rounded-full mb-6">
          <i data-lucide="truck" class="w-3.5 h-3.5"></i>
          أسطولنا
        </span>
        <h2 class="text-3xl sm:text-4xl font-black text-white leading-tight mb-5">
          أسطول حديث
          <span class="text-gold-gradient">لراحتك</span>
        </h2>
        <p class="text-white/60 leading-relaxed mb-8">
          نمتلك أسطولاً متنوعاً من الحافلات الحديثة المجهزة بأحدث وسائل الراحة والترفيه، لضمان تجربة سفر لا تُنسى.
        </p>

        <div class="space-y-3 mb-8">
          <div class="flex items-center gap-3">
            <div class="w-6 h-6 rounded-full bg-[#e7bd54]/20 flex items-center justify-center shrink-0">
              <i data-lucide="check" class="w-3.5 h-3.5 text-[#e7bd54]"></i>
            </div>
            <span class="text-white/80 text-sm">مقاعد مريحة مع مساحة واسعة للأرجل</span>
          </div>
          <div class="flex items-center gap-3">
            <div class="w-6 h-6 rounded-full bg-[#e7bd54]/20 flex items-center justify-center shrink-0">
              <i data-lucide="check" class="w-3.5 h-3.5 text-[#e7bd54]"></i>
            </div>
            <span class="text-white/80 text-sm">تكييف هوائي متقدم ونظام ترفيه</span>
          </div>
          <div class="flex items-center gap-3">
            <div class="w-6 h-6 rounded-full bg-[#e7bd54]/20 flex items-center justify-center shrink-0">
              <i data-lucide="check" class="w-3.5 h-3.5 text-[#e7bd54]"></i>
            </div>
            <span class="text-white/80 text-sm">واي فاي مجاني ومنافذ شحن</span>
          </div>
          <div class="flex items-center gap-3">
            <div class="w-6 h-6 rounded-full bg-[#e7bd54]/20 flex items-center justify-center shrink-0">
              <i data-lucide="check" class="w-3.5 h-3.5 text-[#e7bd54]"></i>
            </div>
            <span class="text-white/80 text-sm">سائقون محترفون ومرخصون</span>
          </div>
        </div>

        <a href="#cta" class="btn-gold px-6 py-3 rounded-full text-sm inline-flex items-center gap-2">
          احجز رحلتك
          <i data-lucide="arrow-left" class="w-4 h-4"></i>
        </a>
      </div>

      <!-- Right: Bus Images 2x2 -->
      <div class="grid grid-cols-2 gap-4">
        <div class="rounded-2xl overflow-hidden aspect-[3/4]">
          <img src="https://images.unsplash.com/photo-1570125909232-eb263c188f7e?w=400&q=80" alt="حافلة VIP" class="w-full h-full object-cover hover:scale-105 transition-transform duration-700">
        </div>
        <div class="rounded-2xl overflow-hidden aspect-[3/4] mt-8">
          <img src="https://images.unsplash.com/photo-1570125909232-eb263c188f7e?w=400&q=80" alt="حافلة قياسية" class="w-full h-full object-cover hover:scale-105 transition-transform duration-700">
        </div>
        <div class="rounded-2xl overflow-hidden aspect-[3/4] -mt-8">
          <img src="https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=400&q=80" alt="حافلة صغيرة" class="w-full h-full object-cover hover:scale-105 transition-transform duration-700">
        </div>
        <div class="rounded-2xl overflow-hidden aspect-[3/4]">
          <img src="https://images.unsplash.com/photo-1570125909232-eb263c188f7e?w=400&q=80" alt="حافلة سياحية" class="w-full h-full object-cover hover:scale-105 transition-transform duration-700">
        </div>
      </div>
    </div>

    <!-- Fleet Types Strip -->
    <div class="mt-14 flex flex-wrap items-center justify-center gap-4">
      <div class="glass rounded-xl px-6 py-3 text-center">
        <div class="text-[#e7bd54] font-black text-sm">V.I.P</div>
        <div class="text-white/50 text-xs mt-0.5">فاخرة</div>
      </div>
      <div class="glass rounded-xl px-6 py-3 text-center">
        <div class="text-[#e7bd54] font-black text-sm">قياسية</div>
        <div class="text-white/50 text-xs mt-0.5">مريحة</div>
      </div>
      <div class="glass rounded-xl px-6 py-3 text-center">
        <div class="text-[#e7bd54] font-black text-sm">صغيرة</div>
        <div class="text-white/50 text-xs mt-0.5">اقتصادية</div>
      </div>
    </div>
  </div>
</section>

<!-- ==================== SECTION 6: DESTINATIONS ==================== -->
<section id="destinations" class="bg-[#f9f5ec] py-20 lg:py-28">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center max-w-2xl mx-auto mb-14">
      <span class="inline-flex items-center gap-2 bg-[#1f442e]/10 text-[#1f442e] text-xs font-bold px-4 py-1.5 rounded-full mb-5">
        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
        الوجهات
      </span>
      <h2 class="text-3xl sm:text-4xl font-black text-[#0c1d14] leading-tight">
        اكتشف
        <span class="text-gold-gradient">أجمل الوجهات</span>
      </h2>
    </div>

    <?php if($popularDestinations && $popularDestinations->count() > 0): ?>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-14">
      <?php $__currentLoopData = $popularDestinations->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <?php
        $grad = $cityGradients[$gradientIndex % count($cityGradients)];
        $gradientIndex++;
        $tripCount = $city->departing_trips_count ?? 0;
      ?>
      <div class="group relative rounded-2xl overflow-hidden aspect-[3/4] cursor-pointer">
        <?php if($city->image_url): ?>
          <img src="<?php echo e($city->image_url); ?>" alt="<?php echo e($city->name); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
        <?php else: ?>
          <div class="w-full h-full bg-gradient-to-br <?php echo e($grad); ?> flex items-center justify-center">
            <i data-lucide="mountain" class="w-16 h-16 text-white/20"></i>
          </div>
        <?php endif; ?>
        <div class="absolute inset-0 bg-gradient-to-t from-[#0c1d14] via-[#0c1d14]/30 to-transparent"></div>
        <div class="absolute top-4 right-4">
          <span class="bg-[#e7bd54] text-[#0c1d14] text-xs font-bold px-3 py-1 rounded-full"><?php echo e($tripCount); ?> رحلة</span>
        </div>
        <div class="absolute bottom-0 inset-x-0 p-6 text-white">
          <h3 class="text-xl font-bold mb-1"><?php echo e($city->name); ?></h3>
          <p class="text-white/60 text-sm"><?php echo e($tripCount); ?> رحلة متاحة</p>
        </div>
      </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php else: ?>
    <!-- Static fallback destinations -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-14">
      <div class="group relative rounded-2xl overflow-hidden aspect-[3/4]">
        <div class="w-full h-full bg-gradient-to-br from-emerald-800 to-teal-900 flex items-center justify-center">
          <i data-lucide="mountain" class="w-16 h-16 text-white/20"></i>
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#0c1d14] via-[#0c1d14]/30 to-transparent"></div>
        <div class="absolute top-4 right-4">
          <span class="bg-[#e7bd54] text-[#0c1d14] text-xs font-bold px-3 py-1 rounded-full">شائع</span>
        </div>
        <div class="absolute bottom-0 inset-x-0 p-6 text-white">
          <h3 class="text-xl font-bold mb-1">صنعاء</h3>
          <p class="text-white/60 text-sm">العاصمة التاريخية</p>
        </div>
      </div>
      <div class="group relative rounded-2xl overflow-hidden aspect-[3/4]">
        <div class="w-full h-full bg-gradient-to-br from-amber-800 to-orange-900 flex items-center justify-center">
          <i data-lucide="waves" class="w-16 h-16 text-white/20"></i>
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#0c1d14] via-[#0c1d14]/30 to-transparent"></div>
        <div class="absolute top-4 right-4">
          <span class="bg-[#e7bd54] text-[#0c1d14] text-xs font-bold px-3 py-1 rounded-full">شائع</span>
        </div>
        <div class="absolute bottom-0 inset-x-0 p-6 text-white">
          <h3 class="text-xl font-bold mb-1">عدن</h3>
          <p class="text-white/60 text-sm">مدينة السواحل</p>
        </div>
      </div>
      <div class="group relative rounded-2xl overflow-hidden aspect-[3/4]">
        <div class="w-full h-full bg-gradient-to-br from-cyan-800 to-teal-900 flex items-center justify-center">
          <i data-lucide="palmtree" class="w-16 h-16 text-white/20"></i>
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#0c1d14] via-[#0c1d14]/30 to-transparent"></div>
        <div class="absolute top-4 right-4">
          <span class="bg-[#e7bd54] text-[#0c1d14] text-xs font-bold px-3 py-1 rounded-full">شائع</span>
        </div>
        <div class="absolute bottom-0 inset-x-0 p-6 text-white">
          <h3 class="text-xl font-bold mb-1">تعز</h3>
          <p class="text-white/60 text-sm">مدينة الجمال</p>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Winding Road Banner -->
    <div class="relative rounded-2xl overflow-hidden h-48 sm:h-56">
      <img src="https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=1200&q=80" alt="" class="w-full h-full object-cover">
      <div class="absolute inset-0 bg-gradient-to-l from-[#0c1d14]/80 to-[#1f442e]/60"></div>
      <div class="absolute inset-0 flex items-center justify-center text-center px-4">
        <div>
          <h3 class="text-2xl sm:text-3xl font-black text-white mb-3">مئات الوجهات تنتظرك</h3>
          <a href="#cta" class="btn-gold px-6 py-2.5 rounded-full text-sm inline-flex items-center gap-2">
            ابحث عن رحلتك
            <i data-lucide="search" class="w-4 h-4"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ==================== SECTION 6.5: FEATURED TRIPS ==================== -->
<section class="bg-white py-20 lg:py-28">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-end justify-between mb-10">
      <div>
        <span class="inline-flex items-center gap-2 bg-[#1f442e]/10 text-[#1f442e] text-xs font-bold px-4 py-1.5 rounded-full mb-5">
          <i data-lucide="star" class="w-3.5 h-3.5"></i>
          رحلات مميزة
        </span>
        <h2 class="text-3xl sm:text-4xl font-black text-[#0c1d14] leading-tight">
          رحلات <span class="text-gold-gradient">مختارة لك</span>
        </h2>
      </div>
      <?php if($featuredTrips && $featuredTrips->count() > 4): ?>
      <a href="<?php echo e(route('home.search')); ?>" class="hidden sm:inline-flex items-center gap-2 text-[#1f442e] text-sm font-bold hover:text-[#d49920] transition-colors">
        عرض الكل
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
      </a>
      <?php endif; ?>
    </div>

    <?php if($featuredTrips && $featuredTrips->count() > 0): ?>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
      <?php $__currentLoopData = $featuredTrips->take(8); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trip): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <?php
        $grad = $cityGradients[$gradientIndex % count($cityGradients)];
        $gradientIndex++;
      ?>
      <a href="<?php echo e(route('home.show', $trip->uuid)); ?>" class="group relative rounded-2xl overflow-hidden aspect-[3/4]">
        <div class="aspect-[3/4] overflow-hidden">
          <?php if($trip->image_url): ?>
            <img src="<?php echo e($trip->image_url); ?>" alt="<?php echo e($trip->departureCity->name ?? ''); ?> - <?php echo e($trip->destinationCity->name ?? ''); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
          <?php else: ?>
            <div class="w-full h-full bg-gradient-to-br <?php echo e($grad); ?> flex items-center justify-center">
              <i data-lucide="route" class="w-12 h-12 text-white/15"></i>
            </div>
          <?php endif; ?>
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#0c1d14] via-[#0c1d14]/40 to-transparent"></div>
        <div class="absolute bottom-0 inset-x-0 p-5 text-white">
          <?php if($trip->levelTrip): ?>
          <span class="inline-block bg-[#e7bd54] text-[#0c1d14] text-[10px] font-bold px-2.5 py-0.5 rounded-full mb-2"><?php echo e($trip->levelTrip->level_name); ?></span>
          <?php endif; ?>
          <h3 class="font-bold text-sm mb-1 leading-snug"><?php echo e($trip->departureCity->name ?? ''); ?> — <?php echo e($trip->destinationCity->name ?? ''); ?></h3>
          <div class="flex items-center justify-between mt-2">
            <p class="text-[#e7bd54] font-bold text-sm">من <?php echo e(number_format((int)$trip->price_for_adult, 0)); ?> ر.ي</p>
            <span class="text-white/50 text-xs flex items-center gap-1">
              <i data-lucide="users" class="w-3 h-3"></i>
              <?php echo e($trip->available_seats); ?> مقعد
            </span>
          </div>
        </div>
      </a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php else: ?>
    <div class="text-center py-16 text-[#1f442e]/40">
      <i data-lucide="calendar-x" class="w-16 h-16 mx-auto mb-4 opacity-50"></i>
      <p class="text-lg font-medium">لا توجد رحلات متاحة حالياً</p>
      <p class="text-sm mt-1">تابعنا لمعرفة آخر الرحلات</p>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ==================== SECTION 7: CONTACT ==================== -->
<section id="contact" class="bg-white py-20 lg:py-28">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
      <!-- Left: Map -->
      <div class="relative rounded-2xl overflow-hidden h-80 lg:h-[480px] bg-gradient-to-br from-[#1f442e] to-[#0c1d14] flex items-center justify-center">
        <div class="absolute inset-0 opacity-20">
          <img src="https://images.unsplash.com/photo-1524661135-423995f22d0b?w=800&q=60" alt="" class="w-full h-full object-cover">
        </div>
        <div class="relative text-center">
          <div class="w-16 h-16 mx-auto rounded-full bg-[#e7bd54]/20 flex items-center justify-center mb-4 animate-float">
            <i data-lucide="map-pin" class="w-8 h-8 text-[#e7bd54]"></i>
          </div>
          <p class="text-white font-bold text-lg">صنعاء، اليمن</p>
          <p class="text-white/50 text-sm mt-1">الشارع الرئيسي، بجوار البنك المركزي</p>
        </div>
      </div>

      <!-- Right: Contact Info -->
      <div>
        <span class="inline-flex items-center gap-2 bg-[#1f442e]/10 text-[#1f442e] text-xs font-bold px-4 py-1.5 rounded-full mb-6">
          <i data-lucide="phone" class="w-3.5 h-3.5"></i>
          تواصل معنا
        </span>
        <h2 class="text-3xl sm:text-4xl font-black text-[#0c1d14] leading-tight mb-5">
          نحن هنا
          <span class="text-gold-gradient">لمساعدتك</span>
        </h2>
        <p class="text-[#1f442e]/70 leading-relaxed mb-8">
          لا تتردد في التواصل معنا لأي استفسار أو مساعدة. فريقنا جاهز لخدمتك على مدار الساعة.
        </p>

        <div class="space-y-5 mb-8">
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-[#1f442e]/10 flex items-center justify-center shrink-0">
              <i data-lucide="map-pin" class="w-5 h-5 text-[#1f442e]"></i>
            </div>
            <div>
              <p class="font-bold text-[#0c1d14] text-sm">العنوان</p>
              <p class="text-[#1f442e]/60 text-sm">صنعاء، اليمن - الشارع الرئيسي</p>
            </div>
          </div>
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-[#e7bd54]/15 flex items-center justify-center shrink-0">
              <i data-lucide="phone" class="w-5 h-5 text-[#d49920]"></i>
            </div>
            <div>
              <p class="font-bold text-[#0c1d14] text-sm">الهاتف</p>
              <p class="text-[#1f442e]/60 text-sm" dir="ltr">+967 1 234 567</p>
            </div>
          </div>
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-[#1f442e]/10 flex items-center justify-center shrink-0">
              <i data-lucide="mail" class="w-5 h-5 text-[#1f442e]"></i>
            </div>
            <div>
              <p class="font-bold text-[#0c1d14] text-sm">البريد الإلكتروني</p>
              <p class="text-[#1f442e]/60 text-sm">info@aracari.ye</p>
            </div>
          </div>
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-[#e7bd54]/15 flex items-center justify-center shrink-0">
              <i data-lucide="clock" class="w-5 h-5 text-[#d49920]"></i>
            </div>
            <div>
              <p class="font-bold text-[#0c1d14] text-sm">ساعات العمل</p>
              <p class="text-[#1f442e]/60 text-sm">السبت - الخميس: 7:00 ص - 10:00 م</p>
            </div>
          </div>
        </div>

        <!-- Social Icons -->
        <div class="flex items-center gap-3">
          <a href="#" class="w-10 h-10 rounded-full bg-[#1f442e]/10 flex items-center justify-center hover:bg-[#1f442e] hover:text-white text-[#1f442e] transition-colors">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
          </a>
          <a href="#" class="w-10 h-10 rounded-full bg-[#1f442e]/10 flex items-center justify-center hover:bg-[#1f442e] hover:text-white text-[#1f442e] transition-colors">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
          </a>
          <a href="#" class="w-10 h-10 rounded-full bg-[#1f442e]/10 flex items-center justify-center hover:bg-[#1f442e] hover:text-white text-[#1f442e] transition-colors">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          </a>
          <a href="#" class="w-10 h-10 rounded-full bg-[#1f442e]/10 flex items-center justify-center hover:bg-[#1f442e] hover:text-white text-[#1f442e] transition-colors">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ==================== SECTION 8: APP ==================== -->
<section id="app" class="relative py-20 lg:py-28 overflow-hidden">
  <div class="absolute inset-0 bg-gradient-to-bl from-[#0c1d14] via-[#163022] to-[#1f442e]"></div>
  <div class="absolute top-10 left-10 w-72 h-72 bg-[#e7bd54]/5 rounded-full blur-3xl"></div>

  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
      <!-- Left Content -->
      <div>
        <span class="inline-flex items-center gap-2 bg-[#e7bd54]/15 text-[#e7bd54] text-xs font-bold px-4 py-1.5 rounded-full mb-6">
          <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
          تطبيق أراكاري
        </span>
        <h2 class="text-3xl sm:text-4xl font-black text-white leading-tight mb-5">
          احجز رحلاتك من
          <span class="text-gold-gradient">تطبيقك</span>
        </h2>
        <p class="text-white/60 leading-relaxed mb-8">
          حمّل تطبيق أراكاري واستمتع بتجربة حجز سلسة من هاتفك المحمول. تتبع رحلتك، احجز تذاكرك، واحصل على عروض حصرية.
        </p>

        <div class="space-y-3 mb-8">
          <div class="flex items-center gap-3">
            <div class="w-6 h-6 rounded-full bg-[#e7bd54]/20 flex items-center justify-center shrink-0">
              <i data-lucide="check" class="w-3.5 h-3.5 text-[#e7bd54]"></i>
            </div>
            <span class="text-white/80 text-sm">حجز سريع بضغطة زر</span>
          </div>
          <div class="flex items-center gap-3">
            <div class="w-6 h-6 rounded-full bg-[#e7bd54]/20 flex items-center justify-center shrink-0">
              <i data-lucide="check" class="w-3.5 h-3.5 text-[#e7bd54]"></i>
            </div>
            <span class="text-white/80 text-sm">إشعارات فورية لتحديثات الرحلة</span>
          </div>
          <div class="flex items-center gap-3">
            <div class="w-6 h-6 rounded-full bg-[#e7bd54]/20 flex items-center justify-center shrink-0">
              <i data-lucide="check" class="w-3.5 h-3.5 text-[#e7bd54]"></i>
            </div>
            <span class="text-white/80 text-sm">دفع إلكتروني آمن ومشفر</span>
          </div>
          <div class="flex items-center gap-3">
            <div class="w-6 h-6 rounded-full bg-[#e7bd54]/20 flex items-center justify-center shrink-0">
              <i data-lucide="check" class="w-3.5 h-3.5 text-[#e7bd54]"></i>
            </div>
            <span class="text-white/80 text-sm">عروض وخصومات حصرية</span>
          </div>
        </div>

        <div class="flex flex-wrap gap-3">
          <a href="#" class="inline-flex items-center gap-3 bg-white text-[#0c1d14] px-5 py-3 rounded-xl hover:bg-gray-100 transition-colors">
            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor"><path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/></svg>
            <div class="text-right">
              <div class="text-[9px] text-gray-500 leading-none">تحميل من</div>
              <div class="text-sm font-bold leading-tight">App Store</div>
            </div>
          </a>
          <a href="#" class="inline-flex items-center gap-3 bg-white text-[#0c1d14] px-5 py-3 rounded-xl hover:bg-gray-100 transition-colors">
            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor"><path d="M3.609 1.814L13.792 12 3.61 22.186a.996.996 0 01-.61-.92V2.734a1 1 0 01.609-.92zm10.89 10.893l2.302 2.302-10.937 6.333 8.635-8.635zm3.199-3.198l2.807 1.626a1 1 0 010 1.73l-2.808 1.626L15.206 12l2.492-2.491zM5.864 2.658L16.802 8.99l-2.303 2.303-8.635-8.635z"/></svg>
            <div class="text-right">
              <div class="text-[9px] text-gray-500 leading-none">تحميل من</div>
              <div class="text-sm font-bold leading-tight">Google Play</div>
            </div>
          </a>
        </div>
      </div>

      <!-- Right: Phone Mockups -->
      <div class="relative flex items-center justify-center">
        <!-- Back phone -->
        <div class="absolute w-48 sm:w-56 h-96 sm:h-[440px] bg-[#0c1d14] rounded-[2.5rem] border-4 border-[#1f442e] shadow-2xl -rotate-6 -translate-x-8 top-8 opacity-70 overflow-hidden">
          <div class="w-full h-full bg-gradient-to-b from-[#163022] to-[#0c1d14] flex flex-col items-center justify-center p-6">
            <div class="w-12 h-12 rounded-2xl bg-[#1f442e] ring-2 ring-[#e7bd54]/40 flex items-center justify-center mb-4">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 2L4 6v6c0 5.55 3.84 10.74 8 12 4.16-1.26 8-6.45 8-12V6L12 2z" stroke="#e7bd54" stroke-width="1.5"/></svg>
            </div>
            <p class="text-white font-bold text-sm mb-1">أراكاري</p>
            <p class="text-white/40 text-xs">تتبع رحلتك</p>
          </div>
        </div>
        <!-- Front phone -->
        <div class="relative w-52 sm:w-60 h-[420px] sm:h-[480px] bg-[#0c1d14] rounded-[2.5rem] border-4 border-[#1f442e] shadow-2xl rotate-3 translate-x-8 overflow-hidden z-10">
          <div class="w-full h-full bg-gradient-to-b from-[#163022] to-[#0c1d14] flex flex-col items-center justify-center p-6">
            <div class="w-14 h-14 rounded-2xl bg-[#1f442e] ring-2 ring-[#e7bd54]/40 flex items-center justify-center mb-5">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M12 2L4 6v6c0 5.55 3.84 10.74 8 12 4.16-1.26 8-6.45 8-12V6L12 2z" stroke="#e7bd54" stroke-width="1.5"/><path d="M12 8v4l3 2" stroke="#e7bd54" stroke-width="1.5" stroke-linecap="round"/></svg>
            </div>
            <p class="text-white font-bold text-base mb-2">أراكاري</p>
            <p class="text-white/50 text-xs mb-8">ARACARI</p>
            <div class="w-full space-y-2.5">
              <div class="h-2.5 bg-white/10 rounded-full w-full"></div>
              <div class="h-2.5 bg-white/10 rounded-full w-3/4"></div>
              <div class="h-2.5 bg-white/10 rounded-full w-5/6"></div>
            </div>
            <div class="mt-6 w-full bg-gradient-to-l from-[#e7bd54] to-[#d49920] rounded-xl py-3 text-center">
              <span class="text-[#0c1d14] font-bold text-xs">احجز الآن</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ==================== SECTION 9: PARTNERS ==================== -->
<section class="bg-[#f9f5ec] py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <p class="text-center text-[#1f442e]/40 text-sm font-medium mb-10">شركاؤنا في النجاح</p>
    <div class="flex flex-wrap items-center justify-center gap-8 sm:gap-12 opacity-40">
      <div class="flex items-center gap-2 text-[#1f442e]"><i data-lucide="building-2" class="w-8 h-8"></i><span class="font-bold text-lg hidden sm:inline">شريك 1</span></div>
      <div class="flex items-center gap-2 text-[#1f442e]"><i data-lucide="landmark" class="w-8 h-8"></i><span class="font-bold text-lg hidden sm:inline">شريك 2</span></div>
      <div class="flex items-center gap-2 text-[#1f442e]"><i data-lucide="factory" class="w-8 h-8"></i><span class="font-bold text-lg hidden sm:inline">شريك 3</span></div>
      <div class="flex items-center gap-2 text-[#1f442e]"><i data-lucide="store" class="w-8 h-8"></i><span class="font-bold text-lg hidden sm:inline">شريك 4</span></div>
      <div class="flex items-center gap-2 text-[#1f442e]"><i data-lucide="briefcase" class="w-8 h-8"></i><span class="font-bold text-lg hidden sm:inline">شريك 5</span></div>
    </div>
  </div>
</section>

<!-- ==================== SECTION 10: CTA / SEARCH ==================== -->
<section id="cta" class="relative py-20 lg:py-28 overflow-hidden">
  <div class="absolute inset-0">
    <img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1920&q=80" alt="" class="w-full h-full object-cover">
    <div class="absolute inset-0 bg-gradient-to-b from-[#0c1d14]/95 via-[#163022]/90 to-[#0c1d14]/95"></div>
  </div>
  <div class="absolute top-0 left-0 w-96 h-96 bg-[#e7bd54]/5 rounded-full blur-3xl"></div>

  <div class="relative max-w-3xl mx-auto px-4 sm:px-6 text-center">
    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-tight mb-4">
      أحجز الآن +
      <span class="text-gold-gradient">استمتع برحلة</span>
    </h2>
    <p class="text-white/60 mb-10">ابحث عن الرحلة المناسبة لك واحجز تذكرتك بدقائق</p>

    <!-- Search Form -->
    <form action="<?php echo e(route('home.search')); ?>" method="GET" class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-5 sm:p-7">
      <div class="grid sm:grid-cols-3 gap-4 mb-5">
        <div>
          <label class="block text-white/50 text-xs font-medium mb-2 text-right">من مدينة</label>
          <select name="from_city" class="w-full bg-white/10 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#e7bd54]/50 focus:border-[#e7bd54]/50 appearance-none cursor-pointer">
            <option value="" class="bg-[#0c1d14] text-white">اختر مدينة الانطلاق</option>
            <?php $__currentLoopData = $cities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($city->uuid); ?>" class="bg-[#0c1d14] text-white"><?php echo e($city->name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </div>
        <div>
          <label class="block text-white/50 text-xs font-medium mb-2 text-right">إلى مدينة</label>
          <select name="to_city" class="w-full bg-white/10 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#e7bd54]/50 focus:border-[#e7bd54]/50 appearance-none cursor-pointer">
            <option value="" class="bg-[#0c1d14] text-white">اختر مدينة الوصول</option>
            <?php $__currentLoopData = $cities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $city): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($city->uuid); ?>" class="bg-[#0c1d14] text-white"><?php echo e($city->name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </div>
        <div>
          <label class="block text-white/50 text-xs font-medium mb-2 text-right">تاريخ السفر</label>
          <input type="date" name="departure_date" class="w-full bg-white/10 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#e7bd54]/50 focus:border-[#e7bd54]/50 cursor-pointer">
        </div>
      </div>
      <button type="submit" class="w-full btn-gold py-3.5 rounded-xl text-base font-bold flex items-center justify-center gap-2">
        <i data-lucide="search" class="w-5 h-5"></i>
        ابحث عن رحلة
      </button>
    </form>

    <!-- Trust Badges -->
    <div class="mt-8 flex flex-wrap items-center justify-center gap-6 sm:gap-10">
      <div class="flex items-center gap-2 text-white/50">
        <i data-lucide="shield-check" class="w-5 h-5 text-[#e7bd54]"></i>
        <span class="text-sm">حجز آمن مضمون</span>
      </div>
      <div class="flex items-center gap-2 text-white/50">
        <i data-lucide="rotate-ccw" class="w-5 h-5 text-[#e7bd54]"></i>
        <span class="text-sm">إلغاء مجاني</span>
      </div>
      <div class="flex items-center gap-2 text-white/50">
        <i data-lucide="headphones" class="w-5 h-5 text-[#e7bd54]"></i>
        <span class="text-sm">دعم 24/7</span>
      </div>
    </div>
  </div>
</section>


<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
let currencies = <?php echo json_encode($currencies, 15, 512) ?>;
let baseCurrencyId = <?php echo e($baseCurrencyId ?? 'null'); ?>;
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.web', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\AracariBackend\resources\views/home/index.blade.php ENDPATH**/ ?>