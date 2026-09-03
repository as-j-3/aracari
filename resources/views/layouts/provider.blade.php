{{--
    Aracari Provider Layout
    Matches ProviderSidebar.tsx + ProviderHeader.tsx from the Next.js design reference.
    Sticky footer at bottom; aracari.js + flash partial included.

    Note on routes: Provider trips & bookings are nested under a branch
    (admin.provider.branches.{branch}.trips.index). Since the sidebar shows them
    as top-level items, we link them to the branches index which lets the user
    pick a branch first — respecting the actual route structure.
--}}
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'أركاري | لوحة تحكم المزود')</title>
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
  @stack('styles')
</head>
<body class="font-cairo bg-gray-50 text-gray-900 antialiased flex flex-col min-h-screen">

  {{-- ===== MOBILE OVERLAY ===== --}}
  <div id="mobile-overlay" class="lg:hidden fixed inset-0 z-[35] bg-[#1a1c1e]/60 backdrop-blur-sm hidden" onclick="toggleMobileSidebar()"></div>

  {{-- ===== PROVIDER SIDEBAR ===== --}}
  <aside id="appSidebar" class="w-[280px] bg-white text-gray-800 h-screen fixed top-0 right-0 z-40 flex flex-col font-cairo select-none border-l border-gray-200 shadow-sm transition-transform duration-300 translate-x-full lg:translate-x-0">

    {{-- Brand Header --}}
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
          <span class="text-[10px] text-amber-300 tracking-widest font-mono uppercase">Provider Panel</span>
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

    {{-- Provider Badge --}}
    <div class="px-5 py-3 bg-gradient-to-l from-teal-50 to-transparent border-b border-slate-100">
      <div class="flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center text-white shadow-md">
          <i data-lucide="store" class="w-4 h-4"></i>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-xs font-bold text-slate-700 truncate">{{ auth()->user()->providerService?->name ?? auth()->user()->name ?? 'مزود خدمة' }}</p>
          <p class="text-[10px] text-slate-400 truncate">مزود خدمة</p>
        </div>
      </div>
    </div>

    {{-- Navigation — matches ProviderSidebar.tsx (8 items) --}}
    @php
        $providerNav = [
            ['route' => 'admin.provider.prodashboard.index', 'label' => 'لوحة التحكم',        'icon' => 'layout-dashboard', 'patterns' => ['admin.provider.prodashboard.*']],
            ['route' => 'admin.provider.trips.index',         'label' => 'الرحلات',            'icon' => 'bus',              'patterns' => ['admin.provider.trips.*']],
            ['route' => 'admin.provider.bookings.index',       'label' => 'الحجوزات',           'icon' => 'ticket',           'patterns' => ['admin.provider.bookings.*']],
            ['route' => 'admin.provider.staff.index',        'label' => 'الموظفون',           'icon' => 'users',            'patterns' => ['admin.provider.staff.*']],
            ['route' => 'admin.provider.vehicles.index',     'label' => 'المركبات',           'icon' => 'car',              'patterns' => ['admin.provider.vehicles.*']],
            ['route' => 'admin.provider.coupons.index',      'label' => 'الكوبونات',          'icon' => 'percent',          'patterns' => ['admin.provider.coupons.*']],
            ['route' => 'admin.provider.branches.index',     'label' => 'الفروع',             'icon' => 'store',            'patterns' => ['admin.provider.branches.index', 'admin.provider.branches.create', 'admin.provider.branches.edit', 'admin.provider.branches.show']],
            ['route' => 'admin.provider.reports.revenue',    'label' => 'التقارير والإيرادات', 'icon' => 'bar-chart-3',      'patterns' => ['admin.provider.reports.*']],
        ];
    @endphp
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 bg-white scrollbar-thin">
      <p class="px-4 pb-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">القائمة الرئيسية</p>
      @foreach($providerNav as $item)
        @php
            $isActive = false;
            foreach ($item['patterns'] as $pattern) {
                if (request()->routeIs($pattern)) { $isActive = true; break; }
            }
        @endphp
        @if(Route::has($item['route']))
          <a href="{{ route($item['route']) }}" class="w-full flex items-center gap-3.5 px-4 py-3 rounded-lg text-right transition-all duration-200 group text-[14px] {{ $isActive ? 'bg-[#235b32] text-white font-medium shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
            <i data-lucide="{{ $item['icon'] }}" class="w-5 h-5 transition-transform duration-200 {{ $isActive ? 'text-white scale-110' : 'text-gray-400 group-hover:text-gray-600' }}"></i>
            <span class="flex-1 text-right">{{ $item['label'] }}</span>
          </a>
        @endif
      @endforeach
    </nav>

    {{-- Switch to Admin --}}
    <div class="p-3 border-t border-slate-100 bg-slate-50/50">
      <a href="{{ route('admin.dashboard') }}" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-right transition-all duration-200 group text-[12px] text-slate-500 hover:bg-white hover:text-[#115128] border border-transparent hover:border-slate-200" title="التبديل إلى لوحة تحكم الإدارة">
        <i data-lucide="arrow-left-right" class="w-4 h-4 text-slate-400 group-hover:text-[#115128] group-hover:rotate-180 transition-all duration-300"></i>
        <span class="flex-1 font-bold">لوحة تحكم الإدارة</span>
      </a>
    </div>

    {{-- Footer --}}
    <div class="p-4 border-t border-gray-200 text-center bg-gray-50 text-[11px] text-gray-400 font-cairo">
      <p>إصدار المزود v1.2.0</p>
      <p class="mt-0.5">&copy; {{ date('Y') }} Emerald Logistics</p>
    </div>
  </aside>

  {{-- ===== MAIN CONTENT AREA ===== --}}
  <div class="lg:mr-[280px] flex-1 flex flex-col min-h-screen">

    {{-- Sticky Header --}}
    <header class="h-[70px] bg-white/80 backdrop-blur-xl border-b border-[#e2e2e5] px-4 sm:px-8 flex items-center justify-between sticky top-0 z-10 font-cairo shadow-sm select-none">
      <div class="flex items-center gap-3 min-w-0">
        <button onclick="toggleMobileSidebar()" class="lg:hidden w-10 h-10 rounded-lg flex items-center justify-center text-gray-600 hover:bg-[#f3f3f6] transition-colors flex-shrink-0" aria-label="القائمة">
          <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
        <div class="flex items-center gap-2 min-w-0">
          <h1 class="text-[16px] sm:text-[20px] font-bold text-[#1a1c1e] tracking-tight truncate">@yield('header_title', 'لوحة تحكم المزود')</h1>
          <span class="inline-flex items-center text-[10px] font-bold bg-teal-100 text-teal-700 px-2 py-0.5 rounded-full border border-teal-200 flex-shrink-0">مزود</span>
        </div>
      </div>

      {{-- Quick search (hidden on mobile, matches ProviderHeader.tsx) --}}
      <div class="hidden md:flex items-center gap-2 bg-[#f3f3f6] rounded-full px-4 py-2 w-full max-w-sm border border-[#e2e2e5] focus-within:bg-white focus-within:border-teal-400 focus-within:ring-2 focus-within:ring-teal-500/20 transition-all">
        <i data-lucide="search" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
        <input type="text" placeholder="بحث سريع..." class="bg-transparent border-none outline-none text-sm w-full text-[#1a1c1e] placeholder:text-gray-400 font-cairo" aria-label="بحث سريع">
      </div>

      <div class="flex items-center gap-3 sm:gap-6 flex-shrink-0">
        <div class="hidden sm:flex items-center gap-2 border-l border-[#eeeef0] pl-4">
          <button class="w-10 h-10 rounded-full flex items-center justify-center text-gray-500 hover:text-emerald-800 hover:bg-[#f3f3f6] transition-colors" title="تبديل المظهر" onclick="document.documentElement.classList.toggle('dark')"><i data-lucide="moon" class="w-5 h-5"></i></button>
          <button class="w-10 h-10 rounded-full flex items-center justify-center text-gray-500 hover:text-emerald-800 hover:bg-[#f3f3f6] transition-colors relative" title="الرسائل"><i data-lucide="mail" class="w-5 h-5"></i><span class="absolute top-2 left-2 w-2.5 h-2.5 bg-emerald-500 rounded-full ring-2 ring-white"></span></button>
          <button class="w-10 h-10 rounded-full flex items-center justify-center text-gray-500 hover:text-emerald-800 hover:bg-[#f3f3f6] transition-colors relative" title="الإشعارات"><i data-lucide="bell" class="w-5 h-5"></i>@if($notificationsCount ?? 0)<span class="absolute top-2 left-2 w-4 h-4 bg-rose-500 rounded-full text-[9px] text-white flex items-center justify-center font-bold ring-2 ring-white">{{ $notificationsCount }}</span>@endif</button>
        </div>
        <button class="sm:hidden w-10 h-10 rounded-full flex items-center justify-center text-gray-500 hover:text-emerald-800 hover:bg-[#f3f3f6] transition-colors" title="تبديل المظهر" onclick="document.documentElement.classList.toggle('dark')"><i data-lucide="moon" class="w-5 h-5"></i></button>
        <div class="flex items-center gap-2 sm:gap-3 bg-[#f3f3f6] hover:bg-[#eeeef0] transition-colors px-2 sm:px-3 py-1.5 rounded-full cursor-pointer border border-[#e2e2e5]">
          <div class="text-right hidden sm:block"><p class="text-[13px] font-bold text-[#1a1c1e] leading-none">{{ auth()->user()->name ?? 'مستخدم' }}</p><p class="text-[10px] text-gray-500 leading-none mt-1">مزود خدمة</p></div>
          <div class="w-8 h-8 rounded-full bg-[#115128] text-white flex items-center justify-center font-bold text-xs shadow-inner flex-shrink-0">
            <svg viewBox="0 0 100 100" class="w-5 h-5 text-amber-400" fill="currentColor"><path d="M50,10 C27.9,10 10,27.9 10,50 C10,72.1 27.9,90 50,90 C72.1,90 90,72.1 90,50 C90,27.9 72.1,10 50,10 Z M50,82 C32.3,82 18,67.7 18,50 C18,32.3 32.3,18 50,18 C67.7,18 82,32.3 82,50 C82,67.7 67.7,82 50,82 Z"/><path d="M42,35 L62,50 L42,65 Z" fill="#ffffff"/></svg>
          </div>
        </div>
      </div>
    </header>

    {{-- Page Content --}}
    <main class="flex-1 p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto w-full">
      @include('partials.flash')
      @yield('content')
    </main>

    {{-- ===== FOOTER (sticky to bottom) ===== --}}
    <footer class="mt-auto border-t border-[#eeeef0] bg-white/60 backdrop-blur-xl py-4 px-4 sm:px-8">
      <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2 text-[11px] text-gray-400 font-cairo">
        <p>&copy; {{ date('Y') }} أركاري — Emerald Logistics. جميع الحقوق محفوظة.</p>
        <p class="flex items-center gap-1.5">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
          لوحة المزود تعمل بشكل طبيعي
        </p>
      </div>
    </footer>
  </div>

  {{-- ===== Global delete confirmation modal ===== --}}
  <x-delete-confirm />

  <script src="{{ asset('js/aracari.js') }}"></script>
  <script>
    lucide.createIcons();
    function toggleMobileSidebar() {
      const sidebar = document.getElementById('appSidebar');
      const overlay = document.getElementById('mobile-overlay');
      sidebar.classList.toggle('translate-x-full');
      overlay.classList.toggle('hidden');
    }
  </script>
  @stack('scripts')
</body>
</html>
