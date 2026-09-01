
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
  <title><?php echo $__env->yieldContent('title', 'أراكاري | منصة حجز الرحلات البرية'); ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <style>
    :root {
      --brand-950: #0c1d14;
      --brand-800: #1f442e;
      --brand-600: #316b44;
      --brand-500: #438656;
      --gold-400: #e7bd54;
      --gold-500: #d49920;
      --cream: #f9f5ec;
    }
    * { font-family: 'Cairo', sans-serif; }
    .scrollbar-thin { scrollbar-width: thin; scrollbar-color: #d1d5db transparent; }
    .scrollbar-thin::-webkit-scrollbar { width: 6px; height: 6px; }
    .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
    .scrollbar-thin::-webkit-scrollbar-thumb { background-color: #d1d5db; border-radius: 9999px; }
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 9999px; }
    ::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
    input[type="date"]::-webkit-calendar-picker-indicator { cursor: pointer; opacity: 0.6; }
    @keyframes ping { 75%, 100% { transform: scale(2); opacity: 0; } }
    .animate-ping { animation: ping 1s cubic-bezier(0, 0, 0.2, 1) infinite; }
    @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
    .animate-float { animation: float 3s ease-in-out infinite; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-in-up { animation: fadeInUp 0.7s ease-out forwards; }
    .animate-delay-100 { animation-delay: 0.1s; }
    .animate-delay-200 { animation-delay: 0.2s; }
    .animate-delay-300 { animation-delay: 0.3s; }
    .animate-delay-400 { animation-delay: 0.4s; }
    html { scroll-behavior: smooth; }
    .glass { background: rgba(255,255,255,0.08); backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,0.12); }
    .glass-light { background: rgba(255,255,255,0.7); backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,0.4); }
    .dot-pattern { background-image: radial-gradient(circle, rgba(31,68,46,0.08) 1px, transparent 1px); background-size: 24px 24px; }
    .text-gold-gradient { background: linear-gradient(135deg, #e7bd54, #d49920, #e7bd54); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .btn-gold { background: linear-gradient(135deg, #e7bd54, #d49920); color: #0c1d14; font-weight: 700; transition: all 0.3s; }
    .btn-gold:hover { background: linear-gradient(135deg, #d49920, #c0891a); transform: translateY(-1px); box-shadow: 0 8px 24px rgba(227,189,84,0.3); }
    .nav-link { position: relative; }
    .nav-link::after {
      content: '';
      position: absolute;
      bottom: 2px;
      left: 50%;
      width: 0;
      height: 2px;
      background: #e7bd54;
      border-radius: 999px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      transform: translateX(-50%);
    }
    .nav-link:hover::after { width: 70%; }
    .nav-glass {
      background: rgba(12, 29, 20, 0.55);
      backdrop-filter: blur(20px) saturate(1.8);
      border: 1px solid rgba(255, 255, 255, 0.08);
      box-shadow: 0 4px 30px rgba(0, 0, 0, 0.15);
    }
    .nav-glass.scrolled {
      background: rgba(12, 29, 20, 0.92);
      backdrop-filter: blur(24px) saturate(2);
      border-color: rgba(255, 255, 255, 0.12);
      box-shadow: 0 8px 40px rgba(0, 0, 0, 0.3);
    }
    .user-dropdown { opacity: 0; visibility: hidden; transform: translateY(8px) scale(0.95); transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
    .user-menu:hover .user-dropdown, .user-menu:focus-within .user-dropdown { opacity: 1; visibility: visible; transform: translateY(0) scale(1); }
  </style>
  <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body class="bg-[#f9f5ec] text-[#1f442e] overflow-x-hidden">


<nav id="navbar" class="fixed top-0 inset-x-0 z-50 transition-all duration-500">
  <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 pt-3">
    <div id="navInner" class="flex items-center justify-between h-16 px-4 sm:px-6 rounded-2xl nav-glass transition-all duration-500">
      
      <a href="<?php echo e(route('home')); ?>" class="flex items-center gap-2.5 shrink-0 group">
        <div class="w-10 h-10 rounded-2xl bg-[#1f442e] ring-2 ring-[#e7bd54]/40 flex items-center justify-center group-hover:ring-[#e7bd54]/70 transition-all duration-300 group-hover:scale-105">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2L4 6v6c0 5.55 3.84 10.74 8 12 4.16-1.26 8-6.45 8-12V6L12 2z" fill="#e7bd54" opacity="0.15"/>
            <path d="M12 2L4 6v6c0 5.55 3.84 10.74 8 12 4.16-1.26 8-6.45 8-12V6L12 2z" stroke="#e7bd54" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 8v4l3 2" stroke="#e7bd54" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <div class="flex flex-col leading-tight">
          <span class="text-base font-bold text-[#e7bd54] group-hover:text-[#f0cc6e] transition-colors">أراكاري</span>
          <span class="text-[10px] font-semibold tracking-widest text-[#e7bd54]/70 group-hover:text-[#e7bd54]/90 transition-colors">ARACARI</span>
        </div>
      </a>

      
      <div class="hidden lg:flex items-center gap-1">
        <a href="<?php echo e(route('home')); ?>" class="nav-link px-3 py-2 text-sm font-medium text-white/80 hover:text-white rounded-lg transition-all duration-300 hover:bg-white/5">الرئيسية</a>
        <a href="<?php echo e(route('pages.about')); ?>" class="nav-link px-3 py-2 text-sm font-medium text-white/80 hover:text-white rounded-lg transition-all duration-300 hover:bg-white/5">من نحن</a>
        <a href="<?php echo e(route('pages.contact')); ?>" class="nav-link px-3 py-2 text-sm font-medium text-white/80 hover:text-white rounded-lg transition-all duration-300 hover:bg-white/5">تواصل معنا</a>
        <a href="<?php echo e(route('pages.faq')); ?>" class="nav-link px-3 py-2 text-sm font-medium text-white/80 hover:text-white rounded-lg transition-all duration-300 hover:bg-white/5">الأسئلة الشائعة</a>
        <?php if(auth()->check()): ?>
          <a href="<?php echo e(route('home.search')); ?>" class="nav-link px-3 py-2 text-sm font-medium text-white/80 hover:text-white rounded-lg transition-all duration-300 hover:bg-white/5">البحث</a>
          <a href="<?php echo e(route('dashboard')); ?>" class="nav-link px-3 py-2 text-sm font-medium text-white/80 hover:text-white rounded-lg transition-all duration-300 hover:bg-white/5">حسابي</a>
        <?php endif; ?>
      </div>

      
      <div class="hidden lg:flex items-center gap-2.5">
        <?php if(auth()->guard()->guest()): ?>
          <a href="<?php echo e(route('login')); ?>" class="px-4 py-2 text-sm font-medium text-white/80 hover:text-white rounded-full transition-all duration-300 hover:bg-white/10">
            <i data-lucide="log-in" class="w-4 h-4 inline-block ml-1 align-middle"></i>
            دخول
          </a>
          <a href="<?php echo e(route('register')); ?>" class="btn-gold px-5 py-2 text-sm rounded-full">إنشاء حساب</a>
        <?php endif; ?>
        <?php if(auth()->guard()->check()): ?>
          
          <div class="user-menu relative">
            <button class="flex items-center gap-2.5 pl-3 pr-1 py-1 rounded-full transition-all duration-300 hover:bg-white/10">
              <span class="text-sm font-medium text-white/90"><?php echo e(auth()->user()->name); ?></span>
              <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#e7bd54] to-[#d49920] flex items-center justify-center shadow-lg shadow-[#e7bd54]/20">
                <span class="text-[#0c1d14] font-bold text-xs"><?php echo e(mb_substr(auth()->user()->name, 0, 1)); ?></span>
              </div>
            </button>
            <div class="user-dropdown absolute top-full mt-2 left-0 w-52 bg-[#163022]/98 backdrop-blur-2xl border border-white/10 rounded-xl shadow-2xl shadow-black/30 overflow-hidden">
              <div class="px-4 py-3 border-b border-white/5">
                <p class="text-sm font-bold text-white truncate"><?php echo e(auth()->user()->name); ?></p>
                <p class="text-xs text-white/40 mt-0.5 truncate"><?php echo e(auth()->user()->email ?? auth()->user()->phone_number); ?></p>
              </div>
              <div class="py-1.5">
                <?php if(auth()->user()->isAdmin()): ?>
                  <a href="<?php echo e(route('admin.dashboard')); ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/70 hover:text-white hover:bg-white/5 transition-colors">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i>لوحة التحكم
                  </a>
                <?php elseif(auth()->user()->isProvider()): ?>
                  <a href="<?php echo e(route('provider.dashboard')); ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/70 hover:text-white hover:bg-white/5 transition-colors">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i>لوحة الموفر
                  </a>
                <?php endif; ?>
                <a href="<?php echo e(route('dashboard')); ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/70 hover:text-white hover:bg-white/5 transition-colors">
                  <i data-lucide="user" class="w-4 h-4"></i>حسابي
                </a>
                <a href="<?php echo e(route('bookings')); ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/70 hover:text-white hover:bg-white/5 transition-colors">
                  <i data-lucide="ticket" class="w-4 h-4"></i>حجوزاتي
                </a>
              </div>
              <div class="border-t border-white/5 py-1.5">
                <form method="POST" action="<?php echo e(route('logout')); ?>" class="block">
                  <?php echo csrf_field(); ?>
                  <button type="submit" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-red-300 hover:text-red-200 hover:bg-red-500/10 transition-colors">
                    <i data-lucide="log-out" class="w-4 h-4"></i>تسجيل الخروج
                  </button>
                </form>
              </div>
            </div>
          </div>
          <a href="<?php echo e(route('home.search')); ?>" class="btn-gold px-5 py-2 text-sm rounded-full">احجز الآن</a>
        <?php endif; ?>
      </div>

      
      <button id="mobileMenuBtn" class="lg:hidden p-2 rounded-xl hover:bg-white/10 transition-all duration-300">
        <i data-lucide="menu" class="w-6 h-6 text-white nav-icon"></i>
      </button>
    </div>
  </div>

  
  <div id="mobileMenu" class="hidden lg:hidden mx-3 sm:mx-6 mt-2 bg-[#0c1d14]/98 backdrop-blur-2xl border border-white/10 rounded-2xl shadow-2xl shadow-black/30 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 py-3 space-y-0.5">
      <a href="<?php echo e(route('home')); ?>" class="mobile-link block px-4 py-3 text-sm font-medium text-white/80 hover:text-white hover:bg-white/5 rounded-xl transition-colors">الرئيسية</a>
      <a href="<?php echo e(route('pages.about')); ?>" class="mobile-link block px-4 py-3 text-sm font-medium text-white/80 hover:text-white hover:bg-white/5 rounded-xl transition-colors">
        <i data-lucide="info" class="w-4 h-4 inline-block ml-1 align-middle"></i>من نحن
      </a>
      <a href="<?php echo e(route('pages.contact')); ?>" class="mobile-link block px-4 py-3 text-sm font-medium text-white/80 hover:text-white hover:bg-white/5 rounded-xl transition-colors">
        <i data-lucide="phone" class="w-4 h-4 inline-block ml-1 align-middle"></i>تواصل معنا
      </a>
      <a href="<?php echo e(route('pages.faq')); ?>" class="mobile-link block px-4 py-3 text-sm font-medium text-white/80 hover:text-white hover:bg-white/5 rounded-xl transition-colors">
        <i data-lucide="help-circle" class="w-4 h-4 inline-block ml-1 align-middle"></i>الأسئلة الشائعة
      </a>
      <?php if(auth()->check()): ?>
        <a href="<?php echo e(route('home.search')); ?>" class="mobile-link block px-4 py-3 text-sm font-medium text-white/80 hover:text-white hover:bg-white/5 rounded-xl transition-colors">
          <i data-lucide="search" class="w-4 h-4 inline-block ml-1 align-middle"></i>البحث عن رحلات
        </a>
        <a href="<?php echo e(route('dashboard')); ?>" class="mobile-link block px-4 py-3 text-sm font-medium text-white/80 hover:text-white hover:bg-white/5 rounded-xl transition-colors">
          <i data-lucide="user" class="w-4 h-4 inline-block ml-1 align-middle"></i>حسابي
        </a>
      <?php endif; ?>
      <div class="pt-3 mt-2 border-t border-white/10 flex flex-col gap-2">
        <?php if(auth()->guard()->guest()): ?>
          <a href="<?php echo e(route('login')); ?>" class="text-center px-4 py-3 text-sm font-medium rounded-full text-white/90 hover:bg-white/5 transition-colors">
            <i data-lucide="log-in" class="w-4 h-4 inline-block ml-1 align-middle"></i>تسجيل الدخول
          </a>
          <a href="<?php echo e(route('register')); ?>" class="text-center btn-gold px-4 py-3 text-sm rounded-full">إنشاء حساب</a>
        <?php endif; ?>
        <?php if(auth()->guard()->check()): ?>
          <?php if(auth()->user()->isAdmin()): ?>
            <a href="<?php echo e(route('admin.dashboard')); ?>" class="flex items-center justify-center gap-2 px-4 py-3 text-sm font-medium rounded-full text-white/90 hover:bg-white/5 transition-colors">
              <i data-lucide="layout-dashboard" class="w-4 h-4"></i>لوحة التحكم
            </a>
          <?php elseif(auth()->user()->isProvider()): ?>
            <a href="<?php echo e(route('provider.dashboard')); ?>" class="flex items-center justify-center gap-2 px-4 py-3 text-sm font-medium rounded-full text-white/90 hover:bg-white/5 transition-colors">
              <i data-lucide="layout-dashboard" class="w-4 h-4"></i>لوحة الموفر
            </a>
          <?php endif; ?>
          <form method="POST" action="<?php echo e(route('logout')); ?>">
            <?php echo csrf_field(); ?>
            <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-3 text-sm font-medium rounded-full text-red-300 hover:bg-red-500/10 transition-colors">
              <i data-lucide="log-out" class="w-4 h-4"></i>تسجيل الخروج
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>




<?php echo $__env->yieldContent('content'); ?>


<?php $__env->startSection('footer'); ?>
  <?php if(!isset($hideFooter)): ?>
  <footer class="bg-[#0c1d14] text-white mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">
        
        <div>
          <a href="<?php echo e(route('home')); ?>" class="flex items-center gap-2.5 mb-5">
            <div class="w-10 h-10 rounded-2xl bg-[#1f442e] ring-2 ring-[#e7bd54]/40 flex items-center justify-center">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 2L4 6v6c0 5.55 3.84 10.74 8 12 4.16-1.26 8-6.45 8-12V6L12 2z" fill="#e7bd54" opacity="0.15"/><path d="M12 2L4 6v6c0 5.55 3.84 10.74 8 12 4.16-1.26 8-6.45 8-12V6L12 2z" stroke="#e7bd54" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 8v4l3 2" stroke="#e7bd54" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="flex flex-col leading-tight">
              <span class="text-base font-bold text-[#e7bd54]">أراكاري</span>
              <span class="text-[10px] font-semibold tracking-widest text-[#e7bd54]/80">ARACARI</span>
            </div>
          </a>
          <p class="text-white/40 text-sm leading-relaxed mb-5">
            منصة حجز الرحلات البرية الأولى في اليمن. نسعى لتقديم تجربة سفر آمنة ومريحة للجميع.
          </p>
          <div class="flex items-center gap-2">
            <a href="#" class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center text-white/40 hover:text-[#e7bd54] hover:bg-[#e7bd54]/10 transition-colors">
              <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            </a>
            <a href="#" class="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center text-white/40 hover:text-[#e7bd54] hover:bg-[#e7bd54]/10 transition-colors">
              <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            </a>
          </div>
        </div>
        
        <div>
          <h4 class="text-white font-bold text-sm mb-5">روابط سريعة</h4>
          <ul class="space-y-3">
            <li><a href="<?php echo e(route('home')); ?>" class="text-white/40 text-sm hover:text-[#e7bd54] transition-colors">الرئيسية</a></li>
            <li><a href="<?php echo e(route('pages.about')); ?>" class="text-white/40 text-sm hover:text-[#e7bd54] transition-colors">من نحن</a></li>
            <li><a href="<?php echo e(route('home.search')); ?>" class="text-white/40 text-sm hover:text-[#e7bd54] transition-colors">بحث وحجز رحلات</a></li>
            <li><a href="<?php echo e(route('pages.contact')); ?>" class="text-white/40 text-sm hover:text-[#e7bd54] transition-colors">تواصل معنا</a></li>
          </ul>
        </div>
        
        <div>
          <h4 class="text-white font-bold text-sm mb-5">الخدمات</h4>
          <ul class="space-y-3">
            <li><a href="<?php echo e(route('home.search')); ?>" class="text-white/40 text-sm hover:text-[#e7bd54] transition-colors">حجز الرحلات</a></li>
            <li><a href="<?php echo e(route('pages.faq')); ?>" class="text-white/40 text-sm hover:text-[#e7bd54] transition-colors">الأسئلة الشائعة</a></li>
            <li><a href="<?php echo e(route('pages.contact')); ?>" class="text-white/40 text-sm hover:text-[#e7bd54] transition-colors">الدعم الفني</a></li>
          </ul>
        </div>
        
        <div>
          <h4 class="text-white font-bold text-sm mb-5">النشرة البريدية</h4>
          <p class="text-white/40 text-sm mb-4">اشترك في نشرتنا البريدية للحصول على آخر العروض والرحلات.</p>
          <form class="flex gap-2" onsubmit="event.preventDefault();">
            <input type="email" placeholder="بريدك الإلكتروني" class="flex-1 min-w-0 bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-[#e7bd54]/50">
            <button type="submit" class="btn-gold px-4 py-2.5 rounded-xl text-sm shrink-0">
              <i data-lucide="send" class="w-4 h-4"></i>
            </button>
          </form>
        </div>
      </div>
      <div class="border-t border-white/5 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
        <p class="text-white/30 text-xs">© <?php echo e(date('Y')); ?> أراكاري. جميع الحقوق محفوظة.</p>
        <div class="flex items-center gap-4">
          <a href="<?php echo e(route('pages.terms')); ?>" class="text-white/30 text-xs hover:text-white/50 transition-colors">سياسة الخصوصية</a>
          <a href="<?php echo e(route('pages.terms')); ?>" class="text-white/30 text-xs hover:text-white/50 transition-colors">الشروط والأحكام</a>
        </div>
      </div>
    </div>
  </footer>
  <?php endif; ?>
<?php $__env->stopSection(); ?>


<script>
lucide.createIcons();

// Navbar scroll behavior
const navbar = document.getElementById('navbar');
const navInner = document.getElementById('navInner');
const navIcon = document.querySelectorAll('.nav-icon');

function updateNavbar() {
  const scrolled = window.scrollY > 60;
  if (scrolled) {
    navInner.classList.add('scrolled');
  } else {
    navInner.classList.remove('scrolled');
  }
}

window.addEventListener('scroll', updateNavbar);
updateNavbar();

// Mobile menu toggle
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const mobileMenu = document.getElementById('mobileMenu');
mobileMenuBtn?.addEventListener('click', () => {
  mobileMenu.classList.toggle('hidden');
  const icon = mobileMenuBtn.querySelector('[data-lucide]');
  if (mobileMenu.classList.contains('hidden')) {
    icon.setAttribute('data-lucide', 'menu');
  } else {
    icon.setAttribute('data-lucide', 'x');
  }
  lucide.createIcons();
});

// Close mobile menu on link click
document.querySelectorAll('.mobile-link').forEach(link => {
  link.addEventListener('click', () => {
    mobileMenu.classList.add('hidden');
    const icon = mobileMenuBtn.querySelector('[data-lucide]');
    icon.setAttribute('data-lucide', 'menu');
    lucide.createIcons();
  });
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    e.preventDefault();
    const target = document.querySelector(this.getAttribute('href'));
    if (target) {
      const offset = 90;
      const top = target.getBoundingClientRect().top + window.scrollY - offset;
      window.scrollTo({ top, behavior: 'smooth' });
    }
  });
});
</script>
<?php echo $__env->yieldPushContent('scripts'); ?>

</body>
</html>
<?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/layouts/web.blade.php ENDPATH**/ ?>