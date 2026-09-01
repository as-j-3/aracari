
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
  <title><?php echo $__env->yieldContent('title', 'أركاري | لوحة التحكم'); ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <style>
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
    .animate-ping { animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite; }
    @keyframes flashIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
    .animate-flash-in { animation: flashIn 0.3s ease-out; }
    .aracari-input { width:100%; background:#f3f3f6; border:1px solid #e2e2e5; border-radius:0.75rem; padding:0.625rem 0.75rem; font-size:13px; font-weight:700; outline:none; transition:all .2s; }
    .aracari-input:focus { background:#fff; border-color:#115128; box-shadow:0 0 0 4px rgba(13,148,136,0.1); }
  </style>
  <script>
    tailwind.config = { theme: { extend: { fontFamily: { cairo: ['Cairo', 'sans-serif'] } } } }
  </script>
  <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body class="font-cairo bg-gray-50 text-gray-900 antialiased flex flex-col min-h-screen">

  
  <div id="mobile-overlay" class="lg:hidden fixed inset-0 z-[35] bg-[#1a1c1e]/60 backdrop-blur-sm hidden" onclick="toggleMobileSidebar()"></div>

  
  <aside id="app-sidebar" class="w-[280px] bg-white text-gray-800 h-screen fixed top-0 right-0 z-40 flex flex-col font-cairo select-none border-l border-gray-200 shadow-sm transition-transform duration-300 translate-x-full lg:translate-x-0">

    
    <div class="p-5 flex items-center justify-between bg-[#115128] text-white">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center p-1.5 shadow-md relative overflow-hidden">
          <svg viewBox="0 0 100 100" class="w-full h-full text-emerald-800" fill="currentColor">
            <circle cx="50" cy="50" r="45" fill="#f4f7f6" />
            <circle cx="50" cy="50" r="41" fill="none" stroke="#2d6a3e" stroke-width="4" />
            <path d="M45,25 C30,25 25,38 25,55 C25,70 35,78 50,78 C52,78 60,75 65,70 C70,65 75,50 75,38 C75,28 65,22 55,22 Z" fill="#115128" />
            <path d="M50,22 C50,22 75,25 80,42 C85,55 70,62 55,62 C58,55 58,45 50,38 Z" fill="#e28743" />
            <path d="M72,30 C75,34 78,40 76,45 C73,43 68,40 65,38 Z" fill="#1e1e1e" />
            <circle cx="42" cy="38" r="5" fill="#ffffff" />
            <circle cx="42" cy="38" r="2.5" fill="#1e1e1e" />
            <path d="M28,52 C28,65 35,72 45,72 C40,65 38,58 38,52 Z" fill="#e28743" />
          </svg>
        </div>
        <div class="flex flex-col text-right">
          <span class="font-bold text-lg leading-tight tracking-wider font-cairo">أركاري</span>
          <span class="text-[10px] text-emerald-300 tracking-widest font-mono uppercase">Aracari</span>
        </div>
      </div>
      <div class="flex items-center gap-1.5">
        <span class="relative flex h-2.5 w-2.5">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#8FB339] opacity-75"></span>
          <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#8FB339]"></span>
        </span>
        <span class="text-[10px] text-emerald-200 font-cairo">نشط</span>
      </div>
    </div>

    
    <?php
        $isAnyActive = function($patterns) {
            foreach ($patterns as $pattern) {
                if (request()->routeIs($pattern)) return true;
            }
            return false;
        };
        $isGroupActive = function($items) use ($isAnyActive) {
            foreach ($items as $item) {
                if ($isAnyActive($item['patterns'])) return true;
            }
            return false;
        };

        $navStandalone = [
            ['route' => 'home',                               'label' => 'موقع المسافرين الرئيسي',  'icon' => 'compass',          'patterns' => ['home', 'home.search', 'home.show']],
            ['route' => 'admin.dashboard',                    'label' => 'لوحة التحكم',           'icon' => 'layout-dashboard', 'patterns' => ['admin.dashboard']],
            ['route' => 'admin.provider-branches.index',      'label' => 'فروع المزودين',         'icon' => 'building-2',       'patterns' => ['admin.provider-branches.*']],
        ];

        $navGroups = [
            [
                'label' => 'إدارة المستخدمين',
                'icon' => 'users',
                'items' => [
                    ['route' => 'admin.users.index',                  'label' => 'مستخدمين النظام',      'icon' => 'user-circle',      'patterns' => ['admin.users.*']],
                    ['route' => 'admin.passengers.index',             'label' => 'المسافرون',           'icon' => 'users',            'patterns' => ['admin.passengers.*']],
                    ['route' => 'admin.roles.index',                  'label' => 'الأدوار',              'icon' => 'user-cog',         'patterns' => ['admin.roles.*']],
                ],
            ],
            [
                'label' => 'الرحلات والحجوزات',
                'icon' => 'map',
                'items' => [
                    ['route' => 'admin.trips.index',                  'label' => 'إدارة الرحلات',       'icon' => 'bus',              'patterns' => ['admin.trips.*']],
                    ['route' => 'admin.bookings.index',               'label' => 'إدارة الحجوزات',      'icon' => 'ticket',           'patterns' => ['admin.bookings.*']],
                    ['route' => 'admin.payments.index',               'label' => 'المدفوعات',           'icon' => 'receipt',          'patterns' => ['admin.payments.*']],
                ],
            ],
            [
                'label' => 'الأسطول',
                'icon' => 'truck',
                'items' => [
                    ['route' => 'admin.vehicles.index',               'label' => 'إدارة المركبات',      'icon' => 'car',              'patterns' => ['admin.vehicles.*']],
                    ['route' => 'admin.vehicle_types.index',           'label' => 'تصنيفات المركبات',     'icon' => 'layers',           'patterns' => ['admin.vehicle_types.*']],
                ],
            ],
            [
                'label' => 'الخدمات',
                'icon' => 'concierge-bell',
                'items' => [
                    ['route' => 'admin.service-types.index',          'label' => 'الخدمات التشغيلية',    'icon' => 'briefcase',       'patterns' => ['admin.service-types.*']],
                    ['route' => 'admin.lookups.trip-types.index',     'label' => 'أنماط الرحلات',       'icon' => 'layers',           'patterns' => ['admin.lookups.trip-types.*']],
                    ['route' => 'admin.lookups.trip-levels.index',    'label' => 'مستويات الرحلات',      'icon' => 'gem',              'patterns' => ['admin.lookups.trip-levels.*']],
                    ['route' => 'admin.services.index',               'label' => 'الخدمات والعمولات',   'icon' => 'settings',         'patterns' => ['admin.services.*']],
                    ['route' => 'admin.provider-services.index',      'label' => 'خدمات المزودين',      'icon' => 'store',            'patterns' => ['admin.provider-services.*']],
                    ['route' => 'admin.amenities.index',              'label' => 'الخدمات الإضافية',    'icon' => 'star',             'patterns' => ['admin.amenities.*']],
                ],
            ],
            [
                'label' => 'المالية والمحاسبة',
                'icon' => 'wallet',
                'items' => [
                    ['route' => 'admin.taxes.index',                  'label' => 'الضرائب والرسوم',     'icon' => 'receipt',          'patterns' => ['admin.taxes.*']],
                    ['route' => 'admin.tax-rules.index',              'label' => 'القواعد الضريبية',    'icon' => 'gavel',            'patterns' => ['admin.tax-rules.*']],
                    ['route' => 'admin.commissions.index',            'label' => 'العمولات',            'icon' => 'percent',          'patterns' => ['admin.commissions.*']],
                    ['route' => 'admin.lookups.payment-methods.index','label' => 'طرق الدفع',           'icon' => 'credit-card',      'patterns' => ['admin.lookups.payment-methods.*']],
                    ['route' => 'admin.exchange-rates.index',         'label' => 'أسعار الصرف',         'icon' => 'refresh-cw',       'patterns' => ['admin.exchange-rates.*']],
                    ['route' => 'admin.digital-pockets.index',        'label' => 'المحافظ الرقمية',     'icon' => 'wallet',           'patterns' => ['admin.digital-pockets.*']],
                    ['route' => 'admin.currencies.index',             'label' => 'العملات',             'icon' => 'coins',            'patterns' => ['admin.currencies.*']],
                    ['route' => 'admin.receive-revenue.index',        'label' => 'الحسابات البنكية',    'icon' => 'landmark',         'patterns' => ['admin.receive-revenue.*']],
                    ['route' => 'admin.provider-settlements.index',   'label' => 'تسويات المزودين',     'icon' => 'banknote',         'patterns' => ['admin.provider-settlements.*']],
                    ['route' => 'admin.coupons.index',                'label' => 'كوبونات الخصم',       'icon' => 'tag',              'patterns' => ['admin.coupons.*']],
                    ['route' => 'admin.lookups.coupon-statuses.index','label' => 'حالات الكوبونات',     'icon' => 'tag',              'patterns' => ['admin.lookups.coupon-statuses.*']],
                ],
            ],
            [
                'label' => 'الجغرافيا والمواقع',
                'icon' => 'globe',
                'items' => [
                    ['route' => 'admin.countries.index',              'label' => 'إدارة الدول',         'icon' => 'globe',            'patterns' => ['admin.countries.*']],
                    ['route' => 'admin.cities.index',                 'label' => 'إدارة المدن',         'icon' => 'building-2',       'patterns' => ['admin.cities.*']],
                    ['route' => 'admin.city_stations.index',          'label' => 'محطات المدن',         'icon' => 'map-pin',          'patterns' => ['admin.city_stations.*']],
                ],
            ],
            [
                'label' => 'التقارير والسجلات',
                'icon' => 'bar-chart-3',
                'items' => [
                    ['route' => 'admin.reports.financial',            'label' => 'التقارير المالية',      'icon' => 'bar-chart-3',      'patterns' => ['admin.reports.financial']],
                    ['route' => 'admin.accounting.reports.account-statement.form', 'label' => 'التقارير المحاسبية', 'icon' => 'calculator', 'patterns' => ['admin.accounting.reports.*']],
                    ['route' => 'admin.accounting.journal-entries.index', 'label' => 'القيود المحاسبية',   'icon' => 'receipt-text',     'patterns' => ['admin.accounting.journal-entries.*']],
                    ['route' => 'admin.accounting.accounts.index',    'label' => 'دليل الحسابات',       'icon' => 'landmark',         'patterns' => ['admin.accounting.accounts.*']],
                    ['route' => 'admin.reports.audit-logs',           'label' => 'سجل العمليات',         'icon' => 'history',          'patterns' => ['admin.reports.audit-logs']],
                    ['route' => 'admin.login-activities.index',       'label' => 'سجلات الدخول',        'icon' => 'log-in',           'patterns' => ['admin.login-activities.*']],
                    ['route' => 'admin.security-incidents',           'label' => 'الحوادث الأمنية',      'icon' => 'shield-alert',     'patterns' => ['admin.security-incidents']],
                ],
            ],
        ];

        $navBottom = [
            ['route' => 'admin.policies.index',               'label' => 'سياسات وأحكام',        'icon' => 'file-text',        'patterns' => ['admin.policies.*']],
            ['route' => 'admin.ads.index',                    'label' => 'الإعلانات',             'icon' => 'megaphone',        'patterns' => ['admin.ads.*']],
            ['route' => 'admin.settings',                     'label' => 'الإعدادات',             'icon' => 'settings',         'patterns' => ['admin.settings']],
        ];
    ?>
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 bg-white scrollbar-thin scrollbar-thumb-gray-200 scrollbar-track-transparent">

      
      <?php $__currentLoopData = $navStandalone; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php $isActive = $isAnyActive($item['patterns']); ?>
        <?php if(Route::has($item['route'])): ?>
          <a href="<?php echo e(route($item['route'])); ?>" class="w-full flex items-center gap-3.5 px-4 py-3 rounded-lg text-right transition-all duration-200 group text-[14px] <?php echo e($isActive ? 'bg-[#235b32] text-white font-medium shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'); ?>">
            <i data-lucide="<?php echo e($item['icon']); ?>" class="w-5 h-5 transition-transform duration-200 <?php echo e($isActive ? 'text-white scale-110' : 'text-gray-400 group-hover:text-gray-600 group-hover:scale-105'); ?>"></i>
            <span class="flex-1 text-right"><?php echo e($item['label']); ?></span>
          </a>
        <?php endif; ?>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

      
      <?php $__currentLoopData = $navGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php $groupActive = $isGroupActive($group['items']); ?>
        <div class="pt-3">
          <button type="button" onclick="toggleNavGroup(this)" class="w-full flex items-center gap-3 px-4 py-2 rounded-lg text-right transition-all duration-200 text-[11px] font-bold text-slate-400 hover:text-slate-600 hover:bg-slate-50 uppercase tracking-wider">
            <i data-lucide="<?php echo e($group['icon']); ?>" class="w-4 h-4"></i>
            <span class="flex-1 text-right"><?php echo e($group['label']); ?></span>
            <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-300 nav-chevron <?php echo e($groupActive ? 'rotate-180' : ''); ?>"></i>
          </button>
          <div class="space-y-0.5 mt-1 mr-3 pr-3 border-r-2 border-slate-200 <?php echo e($groupActive ? '' : 'hidden'); ?>" data-nav-group>
            <?php $__currentLoopData = $group['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <?php $isActive = $isAnyActive($item['patterns']); ?>
              <?php if(Route::has($item['route'])): ?>
                <a href="<?php echo e(route($item['route'])); ?>" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-right transition-all duration-200 group text-[13px] <?php echo e($isActive ? 'bg-[#235b32] text-white font-medium shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'); ?>">
                  <i data-lucide="<?php echo e($item['icon']); ?>" class="w-4 h-4 transition-transform duration-200 <?php echo e($isActive ? 'text-white scale-110' : 'text-gray-400 group-hover:text-gray-600 group-hover:scale-105'); ?>"></i>
                  <span class="flex-1 text-right"><?php echo e($item['label']); ?></span>
                </a>
              <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

      
      <div class="pt-3 mt-2 border-t border-gray-100">
        <?php $__currentLoopData = $navBottom; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php $isActive = $isAnyActive($item['patterns']); ?>
          <?php if(Route::has($item['route'])): ?>
            <a href="<?php echo e(route($item['route'])); ?>" class="w-full flex items-center gap-3.5 px-4 py-3 rounded-lg text-right transition-all duration-200 group text-[14px] <?php echo e($isActive ? 'bg-[#235b32] text-white font-medium shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'); ?>">
              <i data-lucide="<?php echo e($item['icon']); ?>" class="w-5 h-5 transition-transform duration-200 <?php echo e($isActive ? 'text-white scale-110' : 'text-gray-400 group-hover:text-gray-600 group-hover:scale-105'); ?>"></i>
              <span class="flex-1 text-right"><?php echo e($item['label']); ?></span>
            </a>
          <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </nav>



    
    <div class="p-4 border-t border-gray-150 text-center bg-gray-50 text-[11px] text-gray-400 font-cairo">
      <p>إصدار النظام v2.4.0</p>
      <p class="mt-0.5">&copy; <?php echo e(date('Y')); ?> Emerald Logistics</p>
    </div>
  </aside>

  
  <div class="lg:mr-[280px] flex-1 flex flex-col min-h-screen">

    
    <header id="app-header" class="h-[70px] bg-white/80 backdrop-blur-xl border-b border-[#e2e2e5] px-4 sm:px-8 flex items-center justify-between  top-0 z-10 font-cairo shadow-sm select-none">

      
      <div class="flex items-center gap-3 min-w-0">
        <button onclick="toggleMobileSidebar()" class="lg:hidden w-10 h-10 rounded-lg flex items-center justify-center text-gray-600 hover:bg-[#f3f3f6] transition-colors flex-shrink-0" aria-label="القائمة">
          <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
        <h1 class="text-[16px] sm:text-[20px] font-bold text-[#1a1c1e] tracking-tight truncate"><?php echo $__env->yieldContent('header_title', 'لوحة التحكم'); ?></h1>
      </div>

      
      <div class="flex items-center gap-3 sm:gap-6 flex-shrink-0">
        <div class="hidden sm:flex items-center gap-2 border-l border-[#eeeef0] pl-4">
          <button class="w-10 h-10 rounded-full flex items-center justify-center text-gray-500 hover:text-emerald-800 hover:bg-[#f3f3f6] transition-colors" title="تبديل المظهر" onclick="document.documentElement.classList.toggle('dark')">
            <i data-lucide="moon" class="w-5 h-5"></i>
          </button>
          <button class="w-10 h-10 rounded-full flex items-center justify-center text-gray-500 hover:text-emerald-800 hover:bg-[#f3f3f6] transition-colors relative" title="الرسائل">
            <i data-lucide="mail" class="w-5 h-5"></i>
            <span class="absolute top-2 left-2 w-2.5 h-2.5 bg-emerald-500 rounded-full ring-2 ring-white"></span>
          </button>
          <button class="w-10 h-10 rounded-full flex items-center justify-center text-gray-500 hover:text-emerald-800 hover:bg-[#f3f3f6] transition-colors relative" title="الإشعارات">
            <i data-lucide="bell" class="w-5 h-5"></i>
            <?php if($notificationsCount ?? 0): ?>
              <span class="absolute top-2 left-2 w-4 h-4 bg-rose-500 rounded-full text-[9px] text-white flex items-center justify-center font-bold ring-2 ring-white"><?php echo e($notificationsCount); ?></span>
            <?php endif; ?>
          </button>
        </div>

        
        <button class="sm:hidden w-10 h-10 rounded-full flex items-center justify-center text-gray-500 hover:text-emerald-800 hover:bg-[#f3f3f6] transition-colors" title="تبديل المظهر" onclick="document.documentElement.classList.toggle('dark')">
          <i data-lucide="moon" class="w-5 h-5"></i>
        </button>

        
        <?php if(auth()->guard()->check()): ?>
        <div class="flex items-center gap-2 sm:gap-3 bg-[#f3f3f6] hover:bg-[#eeeef0] transition-colors px-2 sm:px-3 py-1.5 rounded-full cursor-pointer border border-[#e2e2e5]">
          <div class="text-right hidden sm:block">
            <p class="text-[13px] font-bold text-[#1a1c1e] leading-none"><?php echo e(auth()->user()->name ?? 'مستخدم'); ?></p>
            <p class="text-[10px] text-gray-500 leading-none mt-1"><?php echo e(auth()->user()->isAdmin() ? 'مدير النظام' : 'مستخدم'); ?></p>
          </div>
          <div class="w-8 h-8 rounded-full bg-[#115128] text-white flex items-center justify-center font-bold text-xs shadow-inner flex-shrink-0">
            <svg viewBox="0 0 100 100" class="w-5 h-5 text-amber-400" fill="currentColor">
              <path d="M50,10 C27.9,10 10,27.9 10,50 C10,72.1 27.9,90 50,90 C72.1,90 90,72.1 90,50 C90,27.9 72.1,10 50,10 Z M50,82 C32.3,82 18,67.7 18,50 C18,32.3 32.3,18 50,18 C67.7,18 82,32.3 82,50 C82,67.7 67.7,82 50,82 Z" />
              <path d="M42,35 L62,50 L42,65 Z" fill="#ffffff" />
            </svg>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </header>

    
    <main class="flex-1 p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto w-full">
      <?php echo $__env->make('partials.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      <?php echo $__env->yieldContent('content'); ?>
    </main>

    
    <footer class="mt-auto border-t border-[#eeeef0] bg-white/60 backdrop-blur-xl py-4 px-4 sm:px-8">
      <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2 text-[11px] text-gray-400 font-cairo">
        <p>&copy; <?php echo e(date('Y')); ?> أركاري — Emerald Logistics. جميع الحقوق محفوظة.</p>
        <p class="flex items-center gap-1.5">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
          النظام يعمل بشكل طبيعي
        </p>
      </div>
    </footer>
  </div>

  
  <?php if (isset($component)) { $__componentOriginal23d24a8c8869103e9236b358fd1af4a2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23d24a8c8869103e9236b358fd1af4a2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.delete-confirm','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('delete-confirm'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal23d24a8c8869103e9236b358fd1af4a2)): ?>
<?php $attributes = $__attributesOriginal23d24a8c8869103e9236b358fd1af4a2; ?>
<?php unset($__attributesOriginal23d24a8c8869103e9236b358fd1af4a2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal23d24a8c8869103e9236b358fd1af4a2)): ?>
<?php $component = $__componentOriginal23d24a8c8869103e9236b358fd1af4a2; ?>
<?php unset($__componentOriginal23d24a8c8869103e9236b358fd1af4a2); ?>
<?php endif; ?>

  <script src="<?php echo e(asset('js/aracari.js')); ?>"></script>
  <script>
    lucide.createIcons();
    function toggleMobileSidebar() {
      const sidebar = document.getElementById('app-sidebar');
      const overlay = document.getElementById('mobile-overlay');
      sidebar.classList.toggle('translate-x-full');
      overlay.classList.toggle('hidden');
    }
    function toggleNavGroup(btn) {
      var items = btn.nextElementSibling;
      var chevron = btn.querySelector('.nav-chevron');
      items.classList.toggle('hidden');
      chevron.classList.toggle('rotate-180');
    }
  </script>
  <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/layouts/admin.blade.php ENDPATH**/ ?>