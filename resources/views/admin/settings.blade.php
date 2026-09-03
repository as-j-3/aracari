@extends('layouts.admin')

@section('title', 'أركاري | إعدادات النظام')
@section('header_title', 'إعدادات النظام')

@section('content')

@php
    $user       = auth()->user();
    $canEdit    = $user && $user->hasPermission('settings:edit');

    $siteTitle          = $settings['site_title'] ?? '';
    $siteLogo           = $settings['site_logo'] ?? '';
    $primaryColor       = $settings['primary_color'] ?? '#0f766e';
    $secondaryColor     = $settings['secondary_color'] ?? '#f59e0b';
    $defaultTheme       = $settings['default_theme'] ?? 'light';
    $platformCommission = $settings['platform_commission'] ?? '';
    $defaultVat         = $settings['default_vat'] ?? '';
    $baseCurrencyId     = $settings['base_currency_id'] ?? '';
    $maintenanceMode    = ($settings['maintenance_mode'] ?? 'off') === 'on';
    $apiMaintenanceMode = ($settings['api_maintenance_mode'] ?? 'off') === 'on';
    $maintenanceTitle   = $settings['maintenance_title'] ?? '';
    $maintenanceContent = $settings['maintenance_content'] ?? '';
    $maintenanceAllowedIps = $settings['maintenance_allowed_ips'] ?? '';
    $walletFeature      = ($settings['wallet_feature_enabled'] ?? 'false') === 'true';
    $walletPayment      = ($settings['wallet_payment_enabled'] ?? 'false') === 'true';
    $walletTopup        = ($settings['wallet_topup_enabled'] ?? 'false') === 'true';
    $anyMaintenanceOn   = $maintenanceMode || $apiMaintenanceMode;
    $logoUrl            = $siteLogo ? (filter_var($siteLogo, FILTER_VALIDATE_URL) ? $siteLogo : Storage::url($siteLogo)) : '';
@endphp

{{-- ===== Page Header ===== --}}
<x-page-header
    icon="settings"
    title="إعدادات المنصة"
    description="إدارة الهوية البصرية والتشغيلية في لوحة واحدة مركزية"
    descriptionIcon="info"
/>

{{-- ===== Form ===== --}}
<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" id="settings-form">
  @csrf

  <div class="space-y-6">

    {{-- ===== Row 1: Branding + Financial (2-col) ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

      {{-- ===== Card 1: الهوية البصرية والعلامة ===== --}}
      <div class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-3xl shadow-xl shadow-slate-200/20 p-6 sm:p-8 relative overflow-hidden">
        <div class="absolute -left-10 -top-10 w-40 h-40 bg-teal-500/5 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200/70 relative z-10">
          <h3 class="text-sm font-black text-slate-800 flex items-center gap-3">
            <span class="w-10 h-10 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-600 text-white flex items-center justify-center shadow-lg shadow-teal-500/20">
              <i data-lucide="palette" class="w-5 h-5"></i>
            </span>
            <span class="pt-1">الهوية البصرية والعلامة</span>
          </h3>
          <span class="text-[9px] font-black text-rose-600 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-full">مطلوب</span>
        </div>

        <div class="space-y-6 relative z-10">
          {{-- Site Title --}}
          <div class="space-y-2">
            <label for="site_title" class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mx-1">اسم المنصة الرسمي</label>
            <div class="relative">
              <i data-lucide="edit" class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
              <input type="text" id="site_title" name="site_title" value="{{ old('site_title', $siteTitle) }}" maxlength="255"
                class="w-full h-12 bg-slate-50/50 border border-slate-200/80 rounded-2xl pr-11 pl-4 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all duration-300 outline-none shadow-sm hover:bg-white/80"
                placeholder="أركاري" />
            </div>
          </div>

          {{-- Site Logo --}}
          <div class="space-y-2">
            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mx-1">شعار المنصة (Logo)</label>
            <div class="p-4 bg-slate-50/50 border border-slate-200/80 border-dashed rounded-2xl flex items-center justify-between gap-4 transition-colors hover:bg-slate-50">
              <div id="logo-preview-container">
                @if($logoUrl)
                  <div class="relative flex-shrink-0">
                    <img src="{{ $logoUrl }}" alt="Logo" class="w-16 h-16 rounded-xl object-contain bg-white p-2 shadow-sm border border-slate-200/70" />
                    <button type="button" onclick="removeLogoPreview()" class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-rose-500 text-white flex items-center justify-center hover:bg-rose-600 transition-colors shadow text-[10px]" aria-label="إزالة الشعار">×</button>
                  </div>
                @else
                  <div class="w-16 h-16 rounded-xl bg-white flex items-center justify-center text-slate-300 shadow-sm border border-slate-200/70 flex-shrink-0" id="logo-placeholder">
                    <i data-lucide="image" class="w-6 h-6"></i>
                  </div>
                @endif
              </div>
              <label class="w-full cursor-pointer">
                <span class="block text-[10px] font-bold text-slate-400 mb-1.5">اضغط لاختيار صورة (PNG، JPG، SVG)</span>
                <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/jpg,image/gif,image/svg+xml,image/webp" onchange="handleLogoChange(this)"
                  class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-white file:text-slate-700 file:shadow-sm hover:file:bg-slate-100 transition-all cursor-pointer" />
              </label>
            </div>
          </div>

          {{-- Colors --}}
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <label for="primary_color" class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mx-1">اللون الأساسي</label>
              <div class="flex items-center justify-between bg-slate-50/50 border border-slate-200/80 rounded-2xl px-4 py-2 shadow-sm">
                <input type="color" id="primary_color" name="primary_color" value="{{ old('primary_color', $primaryColor) }}" oninput="document.getElementById('primary-color-hex').textContent = this.value"
                  class="w-8 h-8 rounded-xl border-none cursor-pointer p-0 bg-transparent" aria-label="اللون الأساسي" />
                <span id="primary-color-hex" dir="ltr" class="bg-white px-2 py-1 rounded-xl text-[10px] font-mono font-black text-slate-500 border border-slate-200/70">{{ old('primary_color', $primaryColor) }}</span>
              </div>
            </div>
            <div class="space-y-2">
              <label for="secondary_color" class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mx-1">اللون الثانوي</label>
              <div class="flex items-center justify-between bg-slate-50/50 border border-slate-200/80 rounded-2xl px-4 py-2 shadow-sm">
                <input type="color" id="secondary_color" name="secondary_color" value="{{ old('secondary_color', $secondaryColor) }}" oninput="document.getElementById('secondary-color-hex').textContent = this.value"
                  class="w-8 h-8 rounded-xl border-none cursor-pointer p-0 bg-transparent" aria-label="اللون الثانوي" />
                <span id="secondary-color-hex" dir="ltr" class="bg-white px-2 py-1 rounded-xl text-[10px] font-mono font-black text-slate-500 border border-slate-200/70">{{ old('secondary_color', $secondaryColor) }}</span>
              </div>
            </div>
          </div>

          {{-- Default Theme --}}
          <div class="space-y-2">
            <label for="default_theme" class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mx-1">الثيم الافتراضي للمستخدمين</label>
            <div class="relative">
              <i data-lucide="chevron-down" class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
              <select id="default_theme" name="default_theme"
                class="w-full h-12 bg-slate-50/50 border border-slate-200/80 rounded-2xl pr-11 pl-10 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all duration-300 outline-none shadow-sm appearance-none cursor-pointer hover:bg-white/80">
                <option value="light" {{ old('default_theme', $defaultTheme) === 'light' ? 'selected' : '' }}>فاتح (Light Mode)</option>
                <option value="dark" {{ old('default_theme', $defaultTheme) === 'dark' ? 'selected' : '' }}>داكن (Dark Mode)</option>
                <option value="auto" {{ in_array(old('default_theme', $defaultTheme), ['auto', 'system']) ? 'selected' : '' }}>تصفية تلقائية حسب النظام</option>
              </select>
              <i data-lucide="chevron-down" class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
            </div>
          </div>
        </div>
      </div>

      {{-- ===== Card 2: البيانات المالية والتشغيلية ===== --}}
      <div class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-3xl shadow-xl shadow-slate-200/20 p-6 sm:p-8 relative overflow-hidden flex flex-col">
        <div class="absolute -left-10 -top-10 w-40 h-40 bg-emerald-500/5 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200/70 relative z-10">
          <h3 class="text-sm font-black text-slate-800 flex items-center gap-3">
            <span class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 text-white flex items-center justify-center shadow-lg shadow-emerald-500/20">
              <i data-lucide="percent" class="w-5 h-5"></i>
            </span>
            <span class="pt-1">البيانات المالية والتشغيلية</span>
          </h3>
          <span class="text-[9px] font-black text-rose-600 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-full">مطلوب</span>
        </div>

        <div class="space-y-6 relative z-10 flex flex-col flex-1">
          {{-- Platform Commission --}}
          <div class="space-y-2">
            <label for="platform_commission" class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mx-1">
              عمولة المنصة (%)
              <span class="text-slate-400 font-medium mr-1">المقتطعة من المزود</span>
            </label>
            <div class="relative">
              <i data-lucide="banknote" class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
              <input type="number" id="platform_commission" name="platform_commission" value="{{ old('platform_commission', $platformCommission) }}" step="0.5" min="0" max="100" dir="ltr"
                class="w-full h-12 bg-slate-50/50 border border-slate-200/80 rounded-2xl pr-11 pl-4 text-sm font-mono font-black text-slate-800 focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all duration-300 outline-none shadow-sm text-left hover:bg-white/80" />
            </div>
          </div>

          {{-- Default VAT --}}
          <div class="space-y-2">
            <label for="default_vat" class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mx-1">ضريبة القيمة المضافة (%)</label>
            <div class="relative">
              <i data-lucide="receipt" class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
              <input type="number" id="default_vat" name="default_vat" value="{{ old('default_vat', $defaultVat) }}" step="0.1" min="0" max="100" dir="ltr"
                class="w-full h-12 bg-slate-50/50 border border-slate-200/80 rounded-2xl pr-11 pl-4 text-sm font-mono font-black text-slate-800 focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all duration-300 outline-none shadow-sm text-left hover:bg-white/80" />
            </div>
          </div>

          {{-- Live Preview --}}
          <div class="mt-auto pt-6 border-t border-slate-200/70">
            <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-2xl p-6 relative overflow-hidden shadow-xl shadow-slate-900/20">
              <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-white/10 via-transparent to-transparent"></div>
              <div class="relative z-10 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-center sm:text-right">
                  <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-1">معاينة مباشرة</p>
                  <h4 class="text-lg font-black tracking-tight">ملخص الإعدادات المالية</h4>
                </div>
                <div class="flex gap-4">
                  <div class="text-center">
                    <div id="commission-preview" class="w-14 h-14 rounded-xl bg-white/10 border border-white/10 flex items-center justify-center font-mono font-black text-xl mb-1 shadow-inner backdrop-blur-md">
                      {{ $platformCommission ?: '0' }}<span class="text-xs text-white/50 ml-0.5">%</span>
                    </div>
                    <span class="text-[9px] font-bold text-slate-400">العمولة</span>
                  </div>
                  <div class="text-center">
                    <div id="vat-preview" class="w-14 h-14 rounded-xl bg-white/10 border border-white/10 flex items-center justify-center font-mono font-black text-xl mb-1 shadow-inner backdrop-blur-md">
                      {{ $defaultVat ?: '0' }}<span class="text-xs text-white/50 ml-0.5">%</span>
                    </div>
                    <span class="text-[9px] font-bold text-slate-400">الضريبة</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- ===== Card 3: وضع الصيانة ===== --}}
    <div class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-3xl shadow-xl shadow-slate-200/20 p-6 sm:p-8 relative overflow-hidden">
      <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-amber-500/5 rounded-full blur-3xl pointer-events-none"></div>

      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-6 pb-4 border-b border-slate-200/70 relative z-10">
        <h3 class="text-sm font-black text-slate-800 flex items-center gap-3">
          <span class="w-10 h-10 rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 text-white flex items-center justify-center shadow-lg shadow-amber-500/20">
            <i data-lucide="construction" class="w-5 h-5"></i>
          </span>
          <span class="pt-1">حالة المنصة ووضع الصيانة</span>
        </h3>
        @if($anyMaintenanceOn)
          <span id="maintenance-status-badge" class="px-3 py-1.5 bg-rose-50/80 text-rose-600 border border-rose-200/50 text-[10px] font-black uppercase tracking-widest rounded-xl shadow-sm flex items-center gap-1.5">
            <i data-lucide="alert-triangle" class="w-3 h-3"></i> وضع الصيانة نشط
          </span>
        @else
          <span id="maintenance-status-badge" class="px-3 py-1.5 bg-emerald-50/80 text-emerald-600 border border-emerald-200/50 text-[10px] font-black uppercase tracking-widest rounded-xl shadow-sm flex items-center gap-1.5">
            <i data-lucide="check-circle" class="w-3 h-3"></i> المنصة تعمل
          </span>
        @endif
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8 relative z-10">
        {{-- Right Column: Toggles + Title + Content --}}
        <div class="space-y-6">
          <div id="maintenance-mode-card" class="p-5 rounded-2xl border transition-colors {{ $maintenanceMode ? 'border-rose-200/50 bg-rose-50/50' : 'border-slate-200/70 bg-slate-50/50' }}">
            <label class="flex items-center justify-between cursor-pointer">
              <div class="pl-4">
                <div class="text-sm font-black text-slate-800 mb-1">تفعيل وضع الصيانة - الموقع</div>
                <div class="text-[11px] text-slate-500 font-bold leading-relaxed">يمنع الزوار من تصفح الموقع.</div>
              </div>
              <div class="relative shrink-0">
                <input type="checkbox" name="maintenance_mode" value="on" {{ $maintenanceMode ? 'checked' : '' }} onchange="updateMaintenanceStatus()" class="sr-only peer" id="maintenance_mode" />
                <div class="w-14 h-7 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-rose-500 shadow-inner"></div>
              </div>
            </label>
          </div>

          <div id="api-maintenance-mode-card" class="p-5 rounded-2xl border transition-colors {{ $apiMaintenanceMode ? 'border-rose-200/50 bg-rose-50/50' : 'border-slate-200/70 bg-slate-50/50' }}">
            <label class="flex items-center justify-between cursor-pointer">
              <div class="pl-4">
                <div class="text-sm font-black text-slate-800 mb-1">تفعيل وضع الصيانة - التطبيق</div>
                <div class="text-[11px] text-slate-500 font-bold leading-relaxed">يمنع التطبيق والجوال من العمل.</div>
              </div>
              <div class="relative shrink-0">
                <input type="checkbox" name="api_maintenance_mode" value="on" {{ $apiMaintenanceMode ? 'checked' : '' }} onchange="updateMaintenanceStatus()" class="sr-only peer" id="api_maintenance_mode" />
                <div class="w-14 h-7 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-orange-500 shadow-inner"></div>
              </div>
            </label>
          </div>

          <div class="space-y-2">
            <label for="maintenance_title" class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mx-1">عنوان رسالة الصيانة</label>
            <div class="relative">
              <i data-lucide="type" class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
              <input type="text" id="maintenance_title" name="maintenance_title" value="{{ old('maintenance_title', $maintenanceTitle) }}" maxlength="255" oninput="updateMaintenancePreview()"
                class="w-full h-12 bg-slate-50/50 border border-slate-200/80 rounded-2xl pr-11 pl-4 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-all duration-300 outline-none shadow-sm hover:bg-white/80"
                placeholder="نعود إليكم قريباً" />
            </div>
          </div>

          <div class="space-y-2">
            <label for="maintenance_content" class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mx-1">النص التفصيلي</label>
            <div class="relative">
              <i data-lucide="sticky-note" class="w-4 h-4 absolute right-4 top-4 text-slate-300 pointer-events-none"></i>
              <textarea id="maintenance_content" name="maintenance_content" maxlength="1000" rows="3" oninput="updateMaintenancePreview()"
                class="w-full bg-slate-50/50 border border-slate-200/80 rounded-2xl p-4 pr-11 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-all duration-300 outline-none shadow-sm hover:bg-white/80 leading-relaxed resize-none"
                placeholder="نص رسالة الصيانة التفصيلي...">{{ old('maintenance_content', $maintenanceContent) }}</textarea>
            </div>
          </div>
        </div>

        {{-- Left Column: IP + Preview --}}
        <div class="space-y-6">
          <div class="space-y-2">
            <label for="maintenance_allowed_ips" class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mx-1">استثناء عناوين IP</label>
            <div class="relative">
              <i data-lucide="network" class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
              <input type="text" id="maintenance_allowed_ips" name="maintenance_allowed_ips" value="{{ old('maintenance_allowed_ips', $maintenanceAllowedIps) }}" dir="ltr"
                class="w-full h-12 bg-slate-50/50 border border-slate-200/80 rounded-2xl pr-11 pl-4 text-sm font-mono font-black text-slate-800 focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-all duration-300 outline-none shadow-sm text-left hover:bg-white/80"
                placeholder="127.0.0.1, 192.168.1.1" />
            </div>
            <p class="text-[10px] text-slate-400 font-bold mx-1 mt-1">أدخل العناوين مفصولة بفاصلة. اترك الحقل فارغاً للسماح للجميع بالوصول.</p>
          </div>

          <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-6 text-center text-white border border-slate-700 shadow-xl relative overflow-hidden h-full flex flex-col justify-center min-h-[220px]">
            <div class="absolute inset-0 opacity-30" style="background-image: url(&quot;data:image/svg+xml,%3Csvg width='20' height='20' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='1' cy='1' r='1' fill='rgba(255,255,255,0.05)'/%3E%3C/svg%3E&quot;)"></div>
            <div class="relative z-10 w-full">
              <div class="w-12 h-12 mx-auto bg-white/10 rounded-full flex items-center justify-center mb-4 backdrop-blur-md border border-white/10 shadow-inner">
                <i data-lucide="construction" class="w-6 h-6 text-amber-400"></i>
              </div>
              <h4 id="maintenance-preview-title" class="font-black text-lg mb-2 break-words">{{ $maintenanceTitle ?: 'عنوان افتراضي' }}</h4>
              <p id="maintenance-preview-content" class="text-xs text-slate-300 font-medium leading-relaxed max-w-sm mx-auto line-clamp-2">{{ $maintenanceContent ?: 'نص المحتوى الافتراضي' }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- ===== Card 4: العملات ===== --}}
    <div class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-3xl shadow-xl shadow-slate-200/20 p-6 sm:p-8 relative overflow-hidden">
      <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-violet-500/5 rounded-full blur-3xl pointer-events-none"></div>

      <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200/70 relative z-10">
        <h3 class="text-sm font-black text-slate-800 flex items-center gap-3">
          <span class="w-10 h-10 rounded-2xl bg-gradient-to-br from-violet-500 to-violet-600 text-white flex items-center justify-center shadow-lg shadow-violet-500/20">
            <i data-lucide="refresh-cw" class="w-5 h-5"></i>
          </span>
          <span class="pt-1">إعدادات العملات</span>
        </h3>
        <span class="text-[9px] font-black text-slate-500 bg-slate-50 border border-slate-200 px-2 py-0.5 rounded-full">اختياري</span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 relative z-10">
        <div class="space-y-2">
          <label for="base_currency_id" class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mx-1">العملة الأساسية للنظام</label>
          <div class="relative">
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
            <select id="base_currency_id" name="base_currency_id" required
              class="w-full h-12 bg-slate-50/50 border border-slate-200/80 rounded-2xl pr-11 pl-10 text-sm font-bold text-slate-800 focus:ring-2 focus:ring-violet-500/30 focus:border-violet-500 transition-all duration-300 outline-none shadow-sm appearance-none cursor-pointer hover:bg-white/80">
              <option value="" disabled {{ !$baseCurrencyId ? 'selected' : '' }}>اختر العملة الأساسية...</option>
              @foreach($currencies as $id => $name)
                <option value="{{ $id }}" {{ old('base_currency_id', $baseCurrencyId) == $id ? 'selected' : '' }}>{{ $name }}</option>
              @endforeach
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
          </div>
          <p class="text-[10px] text-slate-400 font-bold mx-1 mt-1">تحدد العملة المرجعية لجميع المعاملات المالية في النظام.</p>
        </div>
      </div>
    </div>

    {{-- ===== Card 5: المحفظة الرقمية ===== --}}
    <div class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-3xl shadow-xl shadow-slate-200/20 p-6 sm:p-8 relative overflow-hidden">
      <div class="absolute -left-10 -top-10 w-40 h-40 bg-teal-500/5 rounded-full blur-3xl pointer-events-none"></div>

      <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200/70 relative z-10">
        <h3 class="text-sm font-black text-slate-800 flex items-center gap-3">
          <span class="w-10 h-10 rounded-2xl bg-gradient-to-br from-teal-500 to-emerald-600 text-white flex items-center justify-center shadow-lg shadow-teal-500/20">
            <i data-lucide="wallet" class="w-5 h-5"></i>
          </span>
          <span class="pt-1">إعدادات المحفظة الإلكترونية</span>
        </h3>
        <span class="text-[9px] font-black text-slate-500 bg-slate-50 border border-slate-200 px-2 py-0.5 rounded-full">اختياري</span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 relative z-10">
        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200/70">
          <div>
            <p class="font-bold text-slate-700 text-sm">تفعيل المحفظة</p>
            <p class="text-xs text-slate-400 mt-0.5">تشغيل/إيقاف نظام المحفظة بالكامل</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="wallet_feature_enabled" value="1" {{ $walletFeature ? 'checked' : '' }} onchange="updateWalletDependents()" class="sr-only peer" id="wallet_feature_enabled" />
            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600"></div>
          </label>
        </div>
        <div id="wallet-payment-card" class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200/70">
          <div>
            <p class="font-bold text-slate-700 text-sm">الدفع عبر المحفظة</p>
            <p class="text-xs text-slate-400 mt-0.5">السماح للعملاء بالدفع من الرصيد</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="wallet_payment_enabled" value="1" {{ $walletPayment ? 'checked' : '' }} class="sr-only peer" id="wallet_payment_enabled" />
            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600"></div>
          </label>
        </div>
        <div id="wallet-topup-card" class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200/70">
          <div>
            <p class="font-bold text-slate-700 text-sm">شحن المحفظة (Top-Up)</p>
            <p class="text-xs text-slate-400 mt-0.5">السماح للعملاء بإضافة رصيد جديد</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="wallet_topup_enabled" value="1" {{ $walletTopup ? 'checked' : '' }} class="sr-only peer" id="wallet_topup_enabled" />
            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600"></div>
          </label>
        </div>
      </div>
    </div>

    {{-- ===== Save Button ===== --}}
    @if($canEdit)
      <div class="flex justify-center lg:justify-end">
        <button type="submit" id="settings-submit-btn"
          class="w-full lg:w-auto px-10 py-4 bg-gradient-to-r from-slate-800 to-slate-900 text-white rounded-2xl font-black text-sm uppercase tracking-wider shadow-xl shadow-slate-900/20 hover:scale-[1.02] hover:from-slate-900 hover:to-black transition-all duration-300 flex items-center justify-center gap-3 active:scale-95">
          <i data-lucide="save" class="w-4 h-4"></i>
          حفظ تطبيق الإعدادات
        </button>
      </div>
    @endif

  </div>
</form>

@endsection

@push('scripts')
<script>
if (window.lucide) window.lucide.createIcons();

// Wallet init on load
(function() {
  if (document.getElementById('wallet_feature_enabled') && !document.getElementById('wallet_feature_enabled').checked) {
    updateWalletDependents();
  }
})();

function handleLogoChange(input) {
  var file = input.files && input.files[0];
  if (!file) return;
  if (file.size > 2048 * 1024) { input.value = ''; alert('حجم الملف يتجاوز الحد المسموح (2MB)'); return; }
  var reader = new FileReader();
  reader.onload = function(e) {
    var c = document.getElementById('logo-preview-container');
    if (!c) return;
    c.innerHTML = '<div class="relative flex-shrink-0"><img src="' + e.target.result + '" alt="Preview" class="w-16 h-16 rounded-xl object-contain bg-white p-2 shadow-sm border border-slate-200/70" /><button type="button" onclick="removeLogoPreview()" class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-rose-500 text-white flex items-center justify-center hover:bg-rose-600 transition-colors shadow text-[10px]" aria-label="إزالة الشعار">×</button></div>';
    if (window.lucide) window.lucide.createIcons();
  };
  reader.readAsDataURL(file);
}

function removeLogoPreview() {
  var c = document.getElementById('logo-preview-container');
  var f = document.getElementById('logo');
  if (f) f.value = '';
  if (!c) return;
  c.innerHTML = '<div class="w-16 h-16 rounded-xl bg-white flex items-center justify-center text-slate-300 shadow-sm border border-slate-200/70 flex-shrink-0" id="logo-placeholder"><i data-lucide="image" class="w-6 h-6"></i></div>';
  if (window.lucide) window.lucide.createIcons();
}

function updateMaintenanceStatus() {
  var siteOn = document.getElementById('maintenance_mode').checked;
  var apiOn = document.getElementById('api_maintenance_mode').checked;
  var anyOn = siteOn || apiOn;
  var badge = document.getElementById('maintenance-status-badge');
  var siteCard = document.getElementById('maintenance-mode-card');
  var apiCard = document.getElementById('api-maintenance-mode-card');
  if (siteCard) siteCard.className = 'p-5 rounded-2xl border transition-colors ' + (siteOn ? 'border-rose-200/50 bg-rose-50/50' : 'border-slate-200/70 bg-slate-50/50');
  if (apiCard) apiCard.className = 'p-5 rounded-2xl border transition-colors ' + (apiOn ? 'border-rose-200/50 bg-rose-50/50' : 'border-slate-200/70 bg-slate-50/50');
  if (badge) {
    if (anyOn) {
      badge.className = 'px-3 py-1.5 bg-rose-50/80 text-rose-600 border border-rose-200/50 text-[10px] font-black uppercase tracking-widest rounded-xl shadow-sm flex items-center gap-1.5';
      badge.innerHTML = '<i data-lucide="alert-triangle" class="w-3 h-3"></i> وضع الصيانة نشط';
    } else {
      badge.className = 'px-3 py-1.5 bg-emerald-50/80 text-emerald-600 border border-emerald-200/50 text-[10px] font-black uppercase tracking-widest rounded-xl shadow-sm flex items-center gap-1.5';
      badge.innerHTML = '<i data-lucide="check-circle" class="w-3 h-3"></i> المنصة تعمل';
    }
    if (window.lucide) window.lucide.createIcons();
  }
}

function updateMaintenancePreview() {
  var t = document.getElementById('maintenance_title');
  var c = document.getElementById('maintenance_content');
  var pt = document.getElementById('maintenance-preview-title');
  var pc = document.getElementById('maintenance-preview-content');
  if (pt && t) pt.textContent = t.value.trim() || 'عنوان افتراضي';
  if (pc && c) pc.textContent = c.value.trim() || 'نص المحتوى الافتراضي';
}

function updateWalletDependents() {
  var on = document.getElementById('wallet_feature_enabled').checked;
  var p = document.getElementById('wallet-payment-card');
  var t = document.getElementById('wallet-topup-card');
  if (p) {
    p.className = 'flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200/70' + (on ? '' : ' opacity-50 pointer-events-none');
    if (!on) document.getElementById('wallet_payment_enabled').checked = false;
  }
  if (t) {
    t.className = 'flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200/70' + (on ? '' : ' opacity-50 pointer-events-none');
    if (!on) document.getElementById('wallet_topup_enabled').checked = false;
  }
}

(function() {
  var ci = document.getElementById('platform_commission');
  var vi = document.getElementById('default_vat');
  var cp = document.getElementById('commission-preview');
  var vp = document.getElementById('vat-preview');
  function up() {
    if (cp) { var v = ci && ci.value ? parseFloat(ci.value) : 0; cp.innerHTML = v + '<span class="text-xs text-white/50 ml-0.5">%</span>'; }
    if (vp) { var v = vi && vi.value ? parseFloat(vi.value) : 0; vp.innerHTML = v + '<span class="text-xs text-white/50 ml-0.5">%</span>'; }
  }
  if (ci) ci.addEventListener('input', up);
  if (vi) vi.addEventListener('input', up);
})();

(function() {
  var form = document.getElementById('settings-form');
  var btn = document.getElementById('settings-submit-btn');
  if (!form || !btn) return;
  form.addEventListener('submit', function() {
    btn.disabled = true;
    btn.classList.add('opacity-70', 'pointer-events-none');
    btn.innerHTML = '<svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> جارٍ الحفظ...';
  });
})();
</script>
@endpush
