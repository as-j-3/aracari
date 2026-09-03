@extends('layouts.admin')

@section('title', 'أركاري | دليل الحسابات')
@section('header_title', 'دليل الحسابات')

@section('content')

@php
    $user      = auth()->user();
    $canCreate = $user && $user->hasPermission('accounts:create');
    $canEdit   = $user && $user->hasPermission('accounts:edit');
    $canDelete = $user && $user->hasPermission('accounts:delete');

    // إحصائيات من قاعدة البيانات
    $allAccounts      = \App\Models\Account::all();
    $totalCount       = $allAccounts->count();
    $assetsCount      = $allAccounts->where('account_type', \App\Constants\AccountTypes::ASSET)->count();
    $liabilitiesCount = $allAccounts->where('account_type', \App\Constants\AccountTypes::LIABILITY)->count();
    $revenueCount     = $allAccounts->where('account_type', \App\Constants\AccountTypes::REVENUE)->count();
    $expenseCount     = $allAccounts->where('account_type', \App\Constants\AccountTypes::EXPENSE)->count();

    // كودات الحسابات المحمية (جذرية لا يمكن تعديلها أو حذفها)
    $protectedCodes = [
        \App\Constants\AccountCodes::ASSETS,
        \App\Constants\AccountCodes::LIABILITIES,
        \App\Constants\AccountCodes::EXPENSES,
        \App\Constants\AccountCodes::REVENUE,
    ];

    // أنواع الحسابات للفورم
    $accountTypeOptions = [
        \App\Constants\AccountTypes::ASSET     => 'أصول',
        \App\Constants\AccountTypes::LIABILITY => 'التزامات',
        \App\Constants\AccountTypes::REVENUE   => 'إيرادات',
        \App\Constants\AccountTypes::EXPENSE   => 'مصروفات',
    ];

    // الحسابات الآباء لقائمة الحسابات الأب في إنشاء حساب جديد
    $parentAccounts = \App\Models\Account::whereNull('parent_id')->get();

    // ميتا المستويات
    $levelMeta = [
        1 => ['label' => 'جذري',  'badge' => 'bg-slate-100 text-slate-700 border-slate-200'],
        2 => ['label' => 'فرعي',  'badge' => 'bg-blue-50 text-blue-700 border-blue-200'],
        3 => ['label' => 'فرعي',  'badge' => 'bg-teal-50 text-teal-700 border-teal-200'],
        4 => ['label' => 'فرعي',  'badge' => 'bg-purple-50 text-purple-700 border-purple-200'],
    ];

    // ميتا أنواع الحسابات
    $typeMeta = [
        'asset'     => ['label' => 'أصول',         'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'dot' => 'bg-emerald-500', 'icon' => 'text-emerald-600'],
        'liability' => ['label' => 'التزامات',     'badge' => 'bg-amber-50 text-amber-700 border-amber-200',       'dot' => 'bg-amber-500',   'icon' => 'text-amber-600'],
        'revenue'   => ['label' => 'إيرادات',      'badge' => 'bg-blue-50 text-blue-700 border-blue-200',         'dot' => 'bg-blue-500',    'icon' => 'text-blue-600'],
        'expense'   => ['label' => 'مصروفات',      'badge' => 'bg-rose-50 text-rose-700 border-rose-200',         'dot' => 'bg-rose-500',    'icon' => 'text-rose-600'],
        'equity'    => ['label' => 'حقوق الملكية', 'badge' => 'bg-purple-50 text-purple-700 border-purple-200',   'dot' => 'bg-purple-500',  'icon' => 'text-purple-600'],
    ];

    function getTypeMetaArr($type, $typeMeta) {
        return $typeMeta[$type] ?? ['label' => $type, 'badge' => 'bg-slate-100 text-slate-700 border-slate-200', 'dot' => 'bg-slate-400', 'icon' => 'text-slate-500'];
    }
    function getLevelMetaArr($level, $levelMeta) {
        return $levelMeta[$level] ?? $levelMeta[4] ?? ['label' => 'فرعي', 'badge' => 'bg-slate-100 text-slate-700 border-slate-200'];
    }
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="landmark"
      title="دليل الحسابات"
      :count="$totalCount"
      countLabel="حساب"
      description="إدارة الحسابات الرئيسية والفرعية في النظام المحاسبي"
      descriptionIcon="info"
  >
    @if($canCreate)
      <button
          type="button"
          data-create-modal="account-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إضافة حساب جديد
      </button>
    @endif
  </x-page-header>

  {{-- ===== Stat Cards (4) ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    {{-- إجمالي الحسابات --}}
    <x-stat-card
        icon="landmark"
        :label="'إجمالي الحسابات'"
        :value="$totalCount"
        :gradient="'from-slate-900 to-slate-800'"
        :accentColor="'teal'"
    />
    {{-- الأصول --}}
    <x-stat-card
        icon="landmark"
        :label="'الأصول'"
        :value="$assetsCount"
        :gradient="'from-emerald-900/90 to-emerald-800/90'"
        :accentColor="'emerald'"
    />
    {{-- الالتزامات --}}
    <x-stat-card
        icon="receipt-text"
        :label="'الالتزامات'"
        :value="$liabilitiesCount"
        :gradient="'from-amber-900/90 to-amber-800/90'"
        :accentColor="'amber'"
    />
    {{-- الإيرادات / المصروفات --}}
    <x-stat-card
        icon="trending-up"
        :label="'الإيرادات / المصروفات'"
        :value="$revenueCount . ' / ' . $expenseCount"
        :gradient="'from-blue-900/90 to-blue-800/90'"
        :accentColor="'blue'"
        :isMono="true"
    />
  </div>

  {{-- ===== Refreshable region ===== --}}
  <div id="aracari-refreshable" class="space-y-6">

    {{-- ===== Data Table Card ===== --}}
    <x-data-table-card
        :searchable="true"
        :searchMode="'server'"
        searchPlaceholder="ابحث عن حساب..."
        :footerInfo="'عرض ' . $accounts->count() . ' من أصل ' . $accounts->total() . ' حساب'"
    >

      <x-slot name="thead">
        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">كود الحساب</th>
        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">اسم الحساب</th>
        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">المستوى</th>
        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">النوع</th>
        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
      </x-slot>

      <x-slot name="rows">
        @forelse($accounts as $account)
          @php
            $tMeta = getTypeMetaArr($account->account_type, $typeMeta);
            $lMeta = getLevelMetaArr($account->level, $levelMeta);
            $isProtected = in_array($account->account_code, $protectedCodes);
          @endphp
          <tr class="hover:bg-slate-50/50 transition-colors group">
            {{-- كود الحساب --}}
            <td data-searchable class="px-6 py-4">
              <span class="font-mono font-black text-slate-400 text-sm tracking-wider" dir="ltr">
                {{ $account->account_code }}
              </span>
            </td>

            {{-- اسم الحساب --}}
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500/20 to-teal-600/10 flex items-center justify-center border border-teal-100 group-hover:scale-110 transition-transform duration-300">
                  <i data-lucide="landmark" class="w-5 h-5 {{ $tMeta['icon'] }}"></i>
                </div>
                <div>
                  <div class="font-bold text-slate-800">{{ $account->account_name }}</div>
                  <div class="text-[10px] font-bold text-slate-400">
                    {{ $tMeta['label'] }}
                    @if($account->parent)
                      · {{ $account->parent->account_name }}
                    @else
                      · حساب جذري
                    @endif
                  </div>
                </div>
              </div>
            </td>

            {{-- المستوى --}}
            <td class="px-6 py-4 text-center">
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-bold border {{ $lMeta['badge'] }}">
                <span class="font-mono">{{ $account->level }}</span>
                {{ $account->level === 1 ? 'جذري' : 'فرعي' }}
              </span>
            </td>

            {{-- النوع --}}
            <td class="px-6 py-4 text-center">
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-bold border {{ $tMeta['badge'] }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $tMeta['dot'] }}"></span>
                {{ $tMeta['label'] }}
              </span>
            </td>

            {{-- الإجراءات --}}
            <td class="px-6 py-4 text-center">
              <div class="flex items-center justify-center gap-1.5">
                @if($canEdit && !$isProtected)
                  <x-action-button
                      variant="edit"
                      data-edit-modal="account-form-modal"
                      data-edit-json="{{ json_encode([
                          'uuid'         => $account->uuid,
                          'account_name' => $account->account_name,
                          'account_code' => $account->account_code,
                          'account_type' => $account->account_type,
                          'level'        => $account->level,
                          'parent_name'  => $account->parent ? $account->parent->account_name : null,
                          'created_at'   => $account->created_at ? $account->created_at->format('Y/m/d H:i') : '',
                      ], JSON_UNESCAPED_UNICODE) }}"
                      :ariaLabel="'تعديل ' . $account->account_name"
                  />
                @endif
                @if($canDelete && !$isProtected)
                  <x-action-button
                      variant="delete"
                      data-delete-url="{{ route('admin.accounting.accounts.destroy', $account) }}"
                      data-delete-label="$account->account_name"
                  />
                @endif
              </div>
            </td>
          </tr>
        @empty
          <x-empty-state
              icon="landmark"
              :title="request('search') ? 'لا توجد نتائج مطابقة' : 'لا توجد حسابات'"
              :message="request('search') ? 'جرّب تعديل كلمات البحث' : 'أضف حساباً جديداً لبدء بناء دليل الحسابات المحاسبي'"
              :colspan="5"
          >
            @if(!request('search') && $canCreate)
              <button
                  type="button"
                  data-create-modal="account-form-modal"
                  class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
              >
                <i data-lucide="plus" class="w-4 h-4"></i>
                إضافة حساب جديد
              </button>
            @endif
            @if(request('search'))
              <a href="{{ request()->url() }}" class="mt-3 text-teal-600 hover:text-teal-700 inline-flex items-center gap-1 font-bold text-sm">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                إعادة تعيين الفلاتر
              </a>
            @endif
          </x-empty-state>
        @endforelse
      </x-slot>

      <x-slot name="footer">
        <x-pagination :paginator="$accounts" />
      </x-slot>
    </x-data-table-card>

  </div>{{-- end #aracari-refreshable -->}

</div>

{{-- ===== Account Form Modal (Create + Edit) ===== --}}
<x-form-modal
    id="account-form-modal"
    title="إضافة حساب جديد"
    :variant="'create'"
    :action="route('admin.accounting.accounts.store')"
    method="POST"
    successMessage="تم حفظ الحساب بنجاح"
>

  {{-- القسم 1: معلومات الحساب --}}
  <div class="space-y-4">
    <div class="flex items-center gap-2 pb-1.5 border-b border-[#eeeef0]">
      <div id="account-section-icon" class="w-7 h-7 rounded-lg flex items-center justify-center border bg-teal-50 text-teal-600 border-teal-100">
        <i data-lucide="landmark" class="w-4 h-4"></i>
      </div>
      <div class="flex-1">
        <h4 id="account-section-title" class="font-bold text-xs text-[#115128]">معلومات الحساب</h4>
        <p id="account-section-desc" class="text-[10px] text-slate-400">مطلوب</p>
      </div>
      <span id="account-section-badge" class="text-[10px] font-bold text-rose-600 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-full">مطلوب</span>
    </div>

    {{-- إنشاء: جميع الحقول نشطة --}}
    <div id="account-create-fields">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        {{-- اسم الحساب --}}
        <x-form-field name="account_name" label="اسم الحساب" icon="landmark" required>
          <div class="relative group">
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
              <i data-lucide="landmark" class="w-4 h-4"></i>
            </div>
            <input
                type="text"
                name="account_name"
                id="account-name"
                required
                maxlength="255"
                value="{{ old('account_name') }}"
                placeholder="مثال: النقدية، البنوك، رواتب الموظفين"
                class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
            >
          </div>
        </x-form-field>

        {{-- كود الحساب --}}
        <x-form-field name="account_code" label="كود الحساب" icon="hash" required>
          <div class="relative group">
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
              <i data-lucide="hash" class="w-4 h-4"></i>
            </div>
            <input
                type="text"
                name="account_code"
                id="account-code"
                required
                maxlength="50"
                value="{{ old('account_code') }}"
                placeholder="مثال: 1500"
                dir="ltr"
                class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-mono font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
            >
          </div>
        </x-form-field>

        {{-- نوع الحساب --}}
        <x-form-field name="account_type" label="نوع الحساب" icon="tag" required>
          <div class="relative group">
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
              <i data-lucide="tag" class="w-4 h-4"></i>
            </div>
            <select
                name="account_type"
                id="account-type"
                required
                class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none cursor-pointer"
            >
              <option value="">اختر نوع الحساب...</option>
              @foreach($accountTypeOptions as $value => $label)
                <option value="{{ $value }}" {{ old('account_type') == $value ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
            <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
              <i data-lucide="chevron-down" class="w-4 h-4"></i>
            </div>
          </div>
        </x-form-field>

        {{-- الحساب الأب --}}
        <x-form-field name="parent_id" label="الحساب الأب" icon="network">
          <div class="relative group">
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
              <i data-lucide="network" class="w-4 h-4"></i>
            </div>
            <select
                name="parent_id"
                id="account-parent"
                class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none cursor-pointer"
            >
              <option value="">بدون حساب أب (حساب جذري)</option>
              @foreach($parentAccounts as $p)
                <option value="{{ $p->id }}" {{ old('parent_id') == $p->id ? 'selected' : '' }}>{{ $p->account_code }} · {{ $p->account_name }}</option>
              @endforeach
            </select>
            <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
              <i data-lucide="chevron-down" class="w-4 h-4"></i>
            </div>
          </div>
          <p class="text-[10px] text-slate-400 leading-relaxed mt-1.5">
            اتركه فارغاً لإنشاء حساب جذري (المستوى 1)
          </p>
        </x-form-field>
      </div>

      {{-- بطاقة معلومات المستوى التلقائي --}}
      <div id="account-level-preview" class="rounded-xl p-3 border flex items-center justify-between bg-slate-50 border-slate-200">
        <div class="flex items-center gap-3">
          <div id="account-level-preview-icon" class="w-9 h-9 rounded-lg flex items-center justify-center border bg-slate-100 text-slate-500 border-slate-200">
            <i data-lucide="network" class="w-4 h-4"></i>
          </div>
          <div>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">المستوى المحسوب</p>
            <p id="account-level-preview-text" class="text-[13px] font-bold text-slate-600">جذري (المستوى 1)</p>
          </div>
        </div>
        <span id="account-level-preview-badge" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold border bg-slate-100 text-slate-700 border-slate-200">
          <span class="font-mono">1</span>
          جذري
        </span>
      </div>
    </div>

    {{-- تعديل: الاسم فقط + باقي الحقول للقراءة فقط --}}
    <div id="account-edit-fields" style="display:none;">
      {{-- اسم الحساب (قابل للتعديل) --}}
      <x-form-field name="account_name" label="اسم الحساب" icon="landmark" required>
        <div class="relative group">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-amber-500 transition-colors">
            <i data-lucide="landmark" class="w-4 h-4"></i>
          </div>
          <input
              type="text"
              name="account_name"
              id="account-name-edit"
              required
              maxlength="255"
              placeholder="مثال: النقدية، البنوك، رواتب الموظفين"
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 transition-all text-right"
          >
        </div>
      </x-form-field>

      {{-- حقول للقراءة فقط --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        {{-- كود الحساب (قراءة فقط) --}}
        <x-form-field name="_ro_code" label="كود الحساب" icon="hash">
          <div class="relative group">
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
              <i data-lucide="hash" class="w-4 h-4"></i>
            </div>
            <input
                type="text"
                id="account-code-ro"
                readonly
                dir="ltr"
                class="w-full bg-slate-100 border border-slate-200 rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-mono font-bold outline-none text-slate-500 cursor-not-allowed text-right"
            >
          </div>
        </x-form-field>

        {{-- نوع الحساب (قراءة فقط) --}}
        <x-form-field name="_ro_type" label="نوع الحساب" icon="tag">
          <div class="relative group">
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
              <i data-lucide="tag" class="w-4 h-4"></i>
            </div>
            <input
                type="text"
                id="account-type-ro"
                readonly
                class="w-full bg-slate-100 border border-slate-200 rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none text-slate-500 cursor-not-allowed text-right"
            >
          </div>
        </x-form-field>

        {{-- المستوى (قراءة فقط) --}}
        <x-form-field name="_ro_level" label="المستوى" icon="network">
          <div class="relative group">
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
              <i data-lucide="network" class="w-4 h-4"></i>
            </div>
            <input
                type="text"
                id="account-level-ro"
                readonly
                class="w-full bg-slate-100 border border-slate-200 rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none text-slate-500 cursor-not-allowed text-right"
            >
          </div>
        </x-form-field>

        {{-- الحساب الأب (قراءة فقط) --}}
        <x-form-field name="_ro_parent" label="الحساب الأب" icon="network">
          <div class="relative group">
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
              <i data-lucide="network" class="w-4 h-4"></i>
            </div>
            <input
                type="text"
                id="account-parent-ro"
                readonly
                class="w-full bg-slate-100 border border-slate-200 rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none text-slate-500 cursor-not-allowed text-right"
            >
          </div>
        </x-form-field>
      </div>
    </div>
  </div>

  {{-- القسم 2: معلومات إضافية (للتعديل فقط) --}}
  <div class="space-y-4" id="account-extra-info" style="display:none;">
    <div class="flex items-center gap-2 pb-1.5 border-b border-[#eeeef0]">
      <div class="w-7 h-7 rounded-lg bg-slate-50 text-slate-600 flex items-center justify-center border border-slate-200">
        <i data-lucide="info" class="w-4 h-4"></i>
      </div>
      <div class="flex-1">
        <h4 class="font-bold text-xs text-slate-700">معلومات إضافية</h4>
        <p class="text-[10px] text-slate-400">للقراءة فقط</p>
      </div>
      <span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">للقراءة فقط</span>
    </div>
    <div class="bg-slate-50/60 border border-slate-200/70 rounded-xl p-3">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        {{-- تاريخ الإنشاء --}}
        <div class="bg-white/70 border border-slate-100 rounded-lg p-2.5 text-center">
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-center gap-1">
            <i data-lucide="clock" class="w-3 h-3"></i>
            تاريخ الإنشاء
          </p>
          <p class="font-bold text-slate-700 text-[11px] mt-1" id="account-info-created">—</p>
        </div>
        {{-- UUID --}}
        <div class="bg-white/70 border border-slate-100 rounded-lg p-2.5 text-center">
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-center gap-1">
            <i data-lucide="hash" class="w-3 h-3"></i>
            UUID
          </p>
          <p class="font-bold text-slate-700 text-[11px] mt-1 font-mono truncate" dir="ltr" id="account-info-uuid">—</p>
        </div>
      </div>
    </div>
  </div>

  {{-- Actions slot --}}
  <x-slot name="actions">
    <button
        type="button"
        data-close-modal
        class="flex-1 bg-[#f3f3f6] hover:bg-[#eeeef0] text-gray-600 font-bold py-3 px-4 rounded-xl text-xs transition-all flex items-center justify-center gap-2"
    >
      <i data-lucide="x" class="w-4 h-4"></i>
      <span id="account-cancel-text">إعادة تعيين</span>
    </button>
    <button
        type="submit"
        class="flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95 bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 shadow-teal-500/30"
        id="account-submit-btn"
    >
      <i data-lucide="save" class="w-4 h-4"></i>
      <span id="account-submit-text">حفظ الحساب</span>
    </button>
  </x-slot>
</x-form-modal>

@endsection
@push('scripts')
<script>
  // ===== خريطة أنواع الحسابات (للتعديل) =====
  var TYPE_LABELS = {
    asset: 'أصول',
    liability: 'التزامات',
    revenue: 'إيرادات',
    expense: 'مصروفات',
    equity: 'حقوق الملكية'
  };

  // ===== Account form modal: dynamic action + title + variant switching =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'account-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.uuid);

    if (isEdit) {
      // ===== وضع التعديل =====
      var updateUrl = "{{ route('admin.accounting.accounts.update', '__UUID__') }}".replace('__UUID__', data.uuid);
      form.setAttribute('action', updateUrl);
      var existingMethod = form.querySelector('input[name="_method"]');
      if (existingMethod) existingMethod.value = 'PUT';
      else {
        var m = document.createElement('input');
        m.type = 'hidden'; m.name = '_method'; m.value = 'PUT';
        m.setAttribute('data-dynamic', 'true');
        form.appendChild(m);
      }
      // تحديث البانر إلى amber
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل حساب';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }
      form.setAttribute('data-success-message', 'تم تحديث الحساب بنجاح');
      // subtitle
      var subtitleEl = modal.querySelector('.aracari-modal-panel .relative.z-10 p');
      if (subtitleEl) {
        subtitleEl.innerHTML = '<span class="inline-flex items-center gap-1.5 bg-slate-900/40 border border-white/10 px-2 py-0.5 rounded-full font-mono font-bold" dir="ltr"><i data-lucide="hash" class="w-3 h-3 flex-shrink-0"></i>' + (data.account_code || '') + '</span>';
        subtitleEl.style.display = '';
      }
      // إخفاء حقول الإنشاء وإظهار حقول التعديل
      var createFields = modal.querySelector('#account-create-fields');
      var editFields = modal.querySelector('#account-edit-fields');
      if (createFields) createFields.style.display = 'none';
      if (editFields) editFields.style.display = '';
      // ملء حقول القراءة فقط
      var codeRo = modal.querySelector('#account-code-ro');
      if (codeRo) codeRo.value = data.account_code || '';
      var typeRo = modal.querySelector('#account-type-ro');
      if (typeRo) typeRo.value = TYPE_LABELS[data.account_type] || data.account_type || '';
      var levelRo = modal.querySelector('#account-level-ro');
      if (levelRo) levelRo.value = 'المستوى ' + (data.level || 1);
      var parentRo = modal.querySelector('#account-parent-ro');
      if (parentRo) {
        parentRo.value = data.parent_name || 'لا يوجد';
        if (!data.parent_name) parentRo.classList.add('italic');
        else parentRo.classList.remove('italic');
      }
      // ملء اسم الحساب للتعديل
      var nameEdit = modal.querySelector('#account-name-edit');
      if (nameEdit) nameEdit.value = data.account_name || '';
      // إظهار معلومات إضافية
      var extraInfo = modal.querySelector('#account-extra-info');
      if (extraInfo) extraInfo.style.display = '';
      var infoCreated = modal.querySelector('#account-info-created');
      if (infoCreated) infoCreated.textContent = data.created_at || '—';
      var infoUuid = modal.querySelector('#account-info-uuid');
      if (infoUuid) infoUuid.textContent = data.uuid || '—';
      // تحديث أقسام الهيدر
      var sectionIcon = modal.querySelector('#account-section-icon');
      if (sectionIcon) sectionIcon.className = 'w-7 h-7 rounded-lg flex items-center justify-center border bg-amber-50 text-amber-600 border-amber-100';
      var sectionTitle = modal.querySelector('#account-section-title');
      if (sectionTitle) { sectionTitle.className = 'font-bold text-xs text-amber-700'; sectionTitle.textContent = 'معلومات الحساب'; }
      var sectionDesc = modal.querySelector('#account-section-desc');
      if (sectionDesc) sectionDesc.textContent = 'الاسم قابل للتعديل · باقي الحقول للقراءة فقط';
      var sectionBadge = modal.querySelector('#account-section-badge');
      if (sectionBadge) { sectionBadge.textContent = 'قراءة فقط*'; sectionBadge.className = 'text-[10px] font-bold text-amber-600 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full'; }
      // cancel text
      var cancelText = modal.querySelector('#account-cancel-text');
      if (cancelText) cancelText.textContent = 'إلغاء';
      // submit button
      var submitBtn = modal.querySelector('#account-submit-btn');
      if (submitBtn) submitBtn.className = 'flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 shadow-amber-500/30';
      var submitText = modal.querySelector('#account-submit-text');
      if (submitText) submitText.textContent = 'حفظ التعديلات';
    } else {
      // ===== وضع الإنشاء =====
      form.setAttribute('action', "{{ route('admin.accounting.accounts.store') }}");
      var m = form.querySelector('input[name="_method"]');
      if (m) m.remove();
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة حساب جديد';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30';
        badge.textContent = 'جديد';
      }
      form.setAttribute('data-success-message', 'تم حفظ الحساب بنجاح');
      var subtitleEl = modal.querySelector('.aracari-modal-panel .relative.z-10 p');
      if (subtitleEl) {
        subtitleEl.innerHTML = '<span class="inline-flex items-center gap-1.5 bg-slate-900/40 border border-white/10 px-2 py-0.5 rounded-full font-bold text-white/90"><i data-lucide="landmark" class="w-3 h-3 flex-shrink-0"></i><span>إضافة حساب جديد لدليل الحسابات</span></span>';
        subtitleEl.style.display = '';
      }
      // إظهار حقول الإنشاء وإخفاء حقول التعديل
      var createFields = modal.querySelector('#account-create-fields');
      var editFields = modal.querySelector('#account-edit-fields');
      if (createFields) createFields.style.display = '';
      if (editFields) editFields.style.display = 'none';
      // إخفاء معلومات إضافية
      var extraInfo = modal.querySelector('#account-extra-info');
      if (extraInfo) extraInfo.style.display = 'none';
      // تحديث أقسام الهيدر
      var sectionIcon = modal.querySelector('#account-section-icon');
      if (sectionIcon) sectionIcon.className = 'w-7 h-7 rounded-lg flex items-center justify-center border bg-teal-50 text-teal-600 border-teal-100';
      var sectionTitle = modal.querySelector('#account-section-title');
      if (sectionTitle) { sectionTitle.className = 'font-bold text-xs text-[#115128]'; sectionTitle.textContent = 'معلومات الحساب'; }
      var sectionDesc = modal.querySelector('#account-section-desc');
      if (sectionDesc) sectionDesc.textContent = 'مطلوب';
      var sectionBadge = modal.querySelector('#account-section-badge');
      if (sectionBadge) { sectionBadge.textContent = 'مطلوب'; sectionBadge.className = 'text-[10px] font-bold text-rose-600 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-full'; }
      // cancel text
      var cancelText = modal.querySelector('#account-cancel-text');
      if (cancelText) cancelText.textContent = 'إعادة تعيين';
      // submit button
      var submitBtn = modal.querySelector('#account-submit-btn');
      if (submitBtn) submitBtn.className = 'flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95 bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 shadow-teal-500/30';
      var submitText = modal.querySelector('#account-submit-text');
      if (submitText) submitText.textContent = 'حفظ الحساب';
      // تحديث معاينة المستوى
      updateLevelPreview(modal);
    }
    if (window.lucide) window.lucide.createIcons();
  });

  // ===== تحديث معاينة المستوى عند تغيير الحساب الأب =====
  function updateLevelPreview(modal) {
    var parentSelect = document.getElementById('account-parent');
    if (!parentSelect) return;
    var isRoot = !parentSelect.value;
    var previewBox = modal.querySelector('#account-level-preview');
    var previewIcon = modal.querySelector('#account-level-preview-icon');
    var previewText = modal.querySelector('#account-level-preview-text');
    var previewBadge = modal.querySelector('#account-level-preview-badge');
    if (!previewBox) return;
    if (isRoot) {
      previewBox.className = 'rounded-xl p-3 border flex items-center justify-between bg-slate-50 border-slate-200';
      previewIcon.className = 'w-9 h-9 rounded-lg flex items-center justify-center border bg-slate-100 text-slate-500 border-slate-200';
      previewText.className = 'text-[13px] font-bold text-slate-600';
      previewText.textContent = 'جذري (المستوى 1)';
      previewBadge.className = 'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold border bg-slate-100 text-slate-700 border-slate-200';
      previewBadge.innerHTML = '<span class="font-mono">1</span> جذري';
    } else {
      var selectedOption = parentSelect.options[parentSelect.selectedIndex];
      var parentName = selectedOption ? selectedOption.textContent : '';
      previewBox.className = 'rounded-xl p-3 border flex items-center justify-between bg-teal-50/60 border-teal-200';
      previewIcon.className = 'w-9 h-9 rounded-lg flex items-center justify-center border bg-teal-100 text-teal-600 border-teal-200';
      previewText.className = 'text-[13px] font-bold text-teal-700';
      var parts = parentName.split('·');
      var shortName = parts.length > 1 ? parts[1].trim() : parentName.trim();
      previewText.textContent = 'فرعي · تحت "' + shortName + '"';
      previewBadge.className = 'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold border bg-blue-50 text-blue-700 border-blue-200';
      previewBadge.innerHTML = '<span class="font-mono">2</span> فرعي';
    }
  }

  (function() {
    var parentSelect = document.getElementById('account-parent');
    if (parentSelect) {
      parentSelect.addEventListener('change', function() {
        var modal = document.getElementById('account-form-modal');
        if (modal) updateLevelPreview(modal);
      });
    }
  })();
</script>
@endpush
