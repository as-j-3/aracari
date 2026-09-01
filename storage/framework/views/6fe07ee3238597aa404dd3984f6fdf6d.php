<?php $__env->startSection('title', 'غير مصرح - 403'); ?>

<?php $__env->startSection('content'); ?>

<div class="min-h-screen flex items-center justify-center px-4 py-16 bg-[#f9f5ec]">
  <div class="max-w-md mx-auto text-center animate-fade-in-up">

    
    <div class="w-24 h-24 mx-auto mb-6 rounded-3xl bg-red-500/10 flex items-center justify-center shadow-lg shadow-red-500/10 float-anim">
      <i data-lucide="shield-x" class="w-12 h-12 text-red-500"></i>
    </div>

    
    <h1 class="text-7xl sm:text-8xl font-black text-[#1f442e] leading-none mb-3" style="font-family:'Outfit',sans-serif;letter-spacing:-0.04em;">
      4<span class="text-red-500">0</span>3
    </h1>

    
    <h2 class="text-xl sm:text-2xl font-bold text-[#1f442e] mb-3">غير مصرح بالوصول</h2>

    
    <p class="text-sm text-slate-400 leading-relaxed mb-8">
      ليس لديك الصلاحية الكافية للوصول إلى هذه الصفحة.<br>
      إذا كنت تعتقد أن هذا خطأ، تواصل مع مدير النظام.
    </p>

    
    <div class="flex flex-col sm:flex-row gap-3 justify-center mb-8">
      <a href="<?php echo e(route('home')); ?>" class="btn-gold inline-flex items-center justify-center gap-2 px-8 py-3 rounded-xl text-sm">
        <i data-lucide="home" class="w-4 h-4"></i>
        العودة للرئيسية
      </a>
      <?php if(auth()->guard()->guest()): ?>
      <a href="<?php echo e(route('login')); ?>" class="inline-flex items-center justify-center gap-2 px-8 py-3 rounded-xl text-sm bg-white border border-[#1f442e]/10 text-[#1f442e]/70 hover:border-[#1f442e]/20 transition-all">
        <i data-lucide="log-in" class="w-4 h-4"></i>
        تسجيل الدخول
      </a>
      <?php endif; ?>
    </div>

    
    <div class="pt-6 border-t border-[#1f442e]/5">
      <p class="text-[10px] text-slate-400 uppercase tracking-wider font-bold mb-3">صفحات مفيدة</p>
      <div class="flex flex-wrap justify-center gap-4 text-xs text-slate-400">
        <a href="<?php echo e(route('home')); ?>" class="hover:text-[#1f442e] transition-colors">الرئيسية</a>
        <?php if(auth()->guard()->check()): ?>
          <a href="<?php echo e(auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard')); ?>" class="hover:text-[#1f442e] transition-colors">
            <?php echo e(auth()->user()->isAdmin() ? 'لوحة التحكم' : 'حسابي'); ?>

          </a>
        <?php endif; ?>
        <a href="#" class="hover:text-[#1f442e] transition-colors">الدعم الفني</a>
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
<?php echo $__env->make('layouts.web', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/errors/403.blade.php ENDPATH**/ ?>