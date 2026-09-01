<?php $__env->startSection('title', 'الصفحة غير موجودة - 404'); ?>

<?php $__env->startSection('content'); ?>

<div class="min-h-screen flex items-center justify-center px-4 py-16 bg-[#f9f5ec]">
  <div class="max-w-md mx-auto text-center animate-fade-in-up">

    
    <div class="w-24 h-24 mx-auto mb-6 rounded-3xl bg-[#1f442e] flex items-center justify-center shadow-lg shadow-[#1f442e]/20 float-anim">
      <i data-lucide="compass" class="w-12 h-12 text-[#e7bd54]"></i>
    </div>

    
    <h1 class="text-7xl sm:text-8xl font-black text-[#1f442e] leading-none mb-3" style="font-family:'Outfit',sans-serif;letter-spacing:-0.04em;">
      4<span class="text-[#e7bd54]">0</span>4
    </h1>

    
    <h2 class="text-xl sm:text-2xl font-bold text-[#1f442e] mb-3">ضللت الطريق</h2>

    
    <p class="text-sm text-slate-400 leading-relaxed mb-8">
      الصفحة التي تبحث عنها غير موجودة أو تم نقلها.<br>
      دعنا نساعدك في العودة إلى الطريق الصحيح.
    </p>

    
    <div class="flex flex-col sm:flex-row gap-3 justify-center mb-8">
      <a href="<?php echo e(route('home')); ?>" class="btn-gold inline-flex items-center justify-center gap-2 px-8 py-3 rounded-xl text-sm">
        <i data-lucide="home" class="w-4 h-4"></i>
        العودة للرئيسية
      </a>
      <a href="<?php echo e(route('home.search')); ?>" class="inline-flex items-center justify-center gap-2 px-8 py-3 rounded-xl text-sm bg-white border border-[#1f442e]/10 text-[#1f442e]/70 hover:border-[#1f442e]/20 transition-all">
        <i data-lucide="search" class="w-4 h-4"></i>
        البحث عن رحلة
      </a>
    </div>

    
    <div class="pt-6 border-t border-[#1f442e]/5">
      <p class="text-[10px] text-slate-400 uppercase tracking-wider font-bold mb-3">صفحات مفيدة</p>
      <div class="flex flex-wrap justify-center gap-4 text-xs text-slate-400">
        <a href="<?php echo e(route('home')); ?>" class="hover:text-[#1f442e] transition-colors">الرئيسية</a>
        <?php if(auth()->guard()->check()): ?>
          <a href="<?php echo e(auth()->user()->hasPermission('admin:access') ? route('admin.dashboard') : route('dashboard')); ?>" class="hover:text-[#1f442e] transition-colors">لوحة التحكم</a>
        <?php else: ?>
          <a href="<?php echo e(route('login')); ?>" class="hover:text-[#1f442e] transition-colors">تسجيل الدخول</a>
          <a href="<?php echo e(route('register')); ?>" class="hover:text-[#1f442e] transition-colors">إنشاء حساب</a>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php $__env->startPush('scripts'); ?>
<style>
  @keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
  }
  .float-anim { animation: float 3s ease-in-out infinite; }
</style>
<script>lucide.createIcons();</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.web', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/errors/404.blade.php ENDPATH**/ ?>