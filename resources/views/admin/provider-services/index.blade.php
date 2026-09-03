@extends('layouts.admin')

@section('title', 'أركاري | إدارة خدمات المزودين')
@section('header_title', 'إدارة خدمات المزودين')

@section('content')

@php
    $user = auth()->user();
    $canView   = $user && $user->hasPermission('providers:view');
    $canCreate = $user && $user->hasPermission('providers:create');
    $canEdit   = $user && $user->hasPermission('providers:edit');
    $canDelete = $user && $user->hasPermission('providers:delete');

    $totalServices = $providerServices->total();

    $statusMeta = [
        'active'   => ['label' => 'نشط',       'bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'icon' => 'check-circle', 'ping' => true],
        'pending'  => ['label' => 'في الانتظار', 'bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'border' => 'border-amber-200',   'icon' => 'clock',         'ping' => false],
        'inactive' => ['label' => 'غير نشط',    'bg' => 'bg-rose-50',     'text' => 'text-rose-700',     'border' => 'border-rose-200',     'icon' => 'x-circle',      'ping' => false],
    ];

    $commissionMeta = [
        'service' => ['label' => 'خدمة', 'bg' => 'bg-teal-50',   'text' => 'text-teal-700',   'border' => 'border-teal-200'],
        'booking' => ['label' => 'حجز',   'bg' => 'bg-blue-50',   'text' => 'text-blue-700',   'border' => 'border-blue-200'],
    ];

    $hasFilters = request()->hasAny(['search', 'status', 'type_of_service_id', 'commission_type']);

    // JSON data for edit/view buttons
    $editJsonBase = function($ps) {
        return json_encode([
            'uuid'            => $ps->uuid,
            'user_id'         => $ps->user_id,
            'user_name'       => $ps->user ? $ps->user->name : '',
            'name'            => $ps->name,
            'type_of_service_id' => $ps->type_of_service_id,
            'cr_number'       => $ps->cr_number,
            'status'          => $ps->status,
            'commission_type' => $ps->commission_type,
            'contact_phone'   => $ps->contact_phone,
            'contact_email'   => $ps->contact_email,
            'description'     => $ps->description,
        ], JSON_UNESCAPED_UNICODE);
    };
    $viewJsonBase = function($ps) {
        return json_encode(['uuid' => $ps->uuid, 'name' => $ps->name], JSON_UNESCAPED_UNICODE);
    };
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="concierge-bell"
      title="إدارة خدمات المزودين"
      :count="$totalServices"
      countLabel="خدمة"
      description="إدارة خدمات المزودين والفروع ونسب العمولات"
      descriptionIcon="info"
  >
    <span class="text-xs font-bold text-teal-700 bg-teal-50 border border-teal-200 px-3 py-1 rounded-full">إدارة</span>
    @if($canCreate)
      <button
          type="button"
          data-create-modal="ps-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus-circle" class="w-4 h-4"></i>
        <span class="hidden sm:inline">إضافة خدمة مزود جديدة</span>
        <span class="sm:hidden">إضافة</span>
      </button>
    @endif
  </x-page-header>

  {{-- ===== Stat Cards ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 lg:gap-6">
      <x-stat-card :value="$statusCounts['total']" label="إجمالي الخدمات" icon="concierge-bell" variant="slate" badge="خدمة" badgeIcon="concierge-bell" />
      <x-stat-card :value="$statusCounts['active']" label="خدمات نشطة" icon="check-circle" variant="emerald" badge="نشطة" badgeIcon="check-circle" />
      <x-stat-card :value="$statusCounts['pending']" label="قيد الانتظار" icon="clock" variant="amber" badge="بانتظار" badgeIcon="clock" />
  </div>

  {{-- ===== Refreshable region ===== --}}
  <div id="aracari-refreshable" class="space-y-6">

  {{-- ===== Filter Card ===== --}}
  <div class="rounded-3xl p-3">
    <form method="GET" action="{{ route('admin.provider-services.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">

      {{-- Search --}}
      <div class="relative lg:col-span-1">
        <i data-lucide="search" class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="ابحث بالاسم أو السجل التجاري..." class="w-full h-10 bg-white/70 border border-slate-200 rounded-xl pr-11 pl-4 text-[13px] font-bold text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all duration-300">
      </div>

      {{-- Status Filter --}}
      <div class="relative">
        <i data-lucide="info" class="w-4 h-4 text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
        <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
        <select name="status" class="w-full h-10 bg-white/70 border border-slate-200 rounded-xl pr-10 pl-8 text-[13px] font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all duration-300 appearance-none cursor-pointer">
          <option value="">كل الحالات</option>
          <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>نشط</option>
          <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>في الانتظار</option>
          <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>غير نشط</option>
        </select>
      </div>

      {{-- Service Type Filter --}}
      <div class="relative">
        <i data-lucide="tag" class="w-4 h-4 text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
        <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
        <select name="type_of_service_id" class="w-full h-10 bg-white/70 border border-slate-200 rounded-xl pr-10 pl-8 text-[13px] font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all duration-300 appearance-none cursor-pointer">
          <option value="">كل أنواع الخدمات</option>
          @foreach($serviceTypes as $st)
            <option value="{{ $st->id }}" {{ (string) request('type_of_service_id') === (string) $st->id ? 'selected' : '' }}>{{ $st->type_name }}</option>
          @endforeach
        </select>
      </div>

      {{-- Commission Type Filter --}}
      <div class="relative">
        <i data-lucide="file-text" class="w-4 h-4 text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
        <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
        <select name="commission_type" class="w-full h-10 bg-white/70 border border-slate-200 rounded-xl pr-10 pl-8 text-[13px] font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all duration-300 appearance-none cursor-pointer">
          <option value="">كل أنواع العمولة</option>
          <option value="service" {{ request('commission_type') === 'service' ? 'selected' : '' }}>خدمة</option>
          <option value="booking" {{ request('commission_type') === 'booking' ? 'selected' : '' }}>حجز</option>
        </select>
      </div>

      {{-- Action Buttons --}}
      <div class="flex gap-2">
        <button type="submit" class="flex-1 h-10 px-4 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-[13px] hover:shadow-lg hover:shadow-teal-500/20 transition-all flex items-center justify-center gap-1.5">
          <i data-lucide="search" class="w-4 h-4"></i>
          <span class="hidden sm:inline">تصفية</span>
        </button>
        @if($hasFilters)
        <a href="{{ route('admin.provider-services.index') }}" class="h-10 px-4 bg-rose-50/80 border border-rose-200 text-rose-600 rounded-xl font-bold text-[13px] hover:bg-rose-500 hover:text-white hover:border-rose-500 transition-all flex items-center gap-1.5 flex-shrink-0">
          <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
        </a>
        @endif
      </div>
    </form>
  </div>

  {{-- ===== Data Table Card ===== --}}
  <x-data-table-card :searchable="false" :footerInfo="'عرض ' . $providerServices->count() . ' من أصل ' . $providerServices->total() . ' خدمة'">
    <x-slot name="thead">
      <tr>
        <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
        <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">اسم الخدمة</th>
        <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المستخدم</th>
        <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">السجل التجاري</th>
        <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">نوع الخدمة</th>
        <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
        <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الفروع</th>
        <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
      </tr>
    </x-slot>
    <x-slot name="rows">
      @forelse($providerServices as $index => $ps)
        @php
          $meta = $statusMeta[$ps->status] ?? $statusMeta['inactive'];
          $cMeta = $commissionMeta[$ps->commission_type] ?? ['label' => '-', 'bg' => 'bg-slate-50', 'text' => 'text-slate-500', 'border' => 'border-slate-200'];
          $isActive = $ps->status === 'active';
          $branchesCount = $ps->branches ? $ps->branches->count() : 0;
          $rowNum = ($providerServices->currentPage() - 1) * $providerServices->perPage() + $index + 1;
        @endphp
        <tr class="hover:bg-slate-50/50 transition-colors group">
          <td class="px-5 py-3.5"><span class="font-mono font-black text-slate-400 text-sm">{{ str_pad((string) $rowNum, 4, '0', STR_PAD_LEFT) }}</span></td>
          <td data-searchable class="px-5 py-3.5">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-teal-500/20 to-teal-600/10 flex items-center justify-center border border-teal-100 group-hover:scale-110 transition-transform duration-300">
                <i data-lucide="concierge-bell" class="w-4 h-4 text-teal-600"></i>
              </div>
              <div>
                <div class="font-bold text-slate-800">{{ $ps->name }}</div>
                <div class="text-[10px] font-bold text-slate-400 flex items-center gap-1 mt-0.5">
                  <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded {{ $cMeta['bg'] }} {{ $cMeta['text'] }} {{ $cMeta['border'] }} border">عمولة {{ $cMeta['label'] }}</span>
                </div>
              </div>
            </div>
          </td>
          <td class="px-5 py-3.5">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-full bg-gradient-to-br from-slate-700 to-slate-900 flex items-center justify-center text-white font-black text-xs flex-shrink-0">{{ $ps->user ? mb_substr($ps->user->name, 0, 1) : '?' }}</div>
              <span class="text-sm font-bold text-slate-700 truncate max-w-[160px]">{{ $ps->user ? $ps->user->name : '-' }}</span>
            </div>
          </td>
          <td class="px-5 py-3.5">
            @if($ps->cr_number)
              <span class="inline-flex items-center gap-1 font-mono font-bold text-slate-700 bg-slate-100/70 border border-slate-200 px-2.5 py-1 rounded-lg text-xs" dir="ltr"><i data-lucide="file-text" class="w-3 h-3 text-slate-400"></i> {{ $ps->cr_number }}</span>
            @else
              <span class="text-slate-300 text-xs">-</span>
            @endif
          </td>
          <td class="px-5 py-3.5">
            @if($ps->typeOfService)
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-purple-50 text-purple-700 text-[10px] font-bold border border-purple-200"><i data-lucide="tag" class="w-3 h-3"></i> {{ $ps->typeOfService->type_name }}</span>
            @else
              <span class="text-slate-300 text-xs">-</span>
            @endif
          </td>
          <td class="px-5 py-3.5 text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full {{ $meta['bg'] }} {{ $meta['text'] }} text-[10px] font-bold border {{ $meta['border'] }}">
              @if($meta['ping'])
                <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span></span>
              @endif
              <i data-lucide="{{ $meta['icon'] }}" class="w-3 h-3"></i>
              {{ $meta['label'] }}
            </span>
          </td>
          <td class="px-5 py-3.5 text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 text-[10px] font-bold border border-blue-200"><i data-lucide="store" class="w-3 h-3"></i> {{ number_format($branchesCount) }}</span>
          </td>
          <td class="px-5 py-3.5 text-center">
            <div class="flex items-center justify-center gap-1.5">
              @if($canView)
              <x-action-button variant="view" data-view-modal="ps-details-modal" data-view-json="{{ $viewJsonBase($ps) }}" :ariaLabel="'عرض ' . $ps->name" />
              @endif
              @if($canEdit)
              <x-action-button variant="edit" data-edit-modal="ps-form-modal" data-edit-json="{{ $editJsonBase($ps) }}" :ariaLabel="'تعديل ' . $ps->name" />
              @endif
              @if($canEdit)
              <form method="POST" action="{{ route('admin.provider-services.toggle-status', $ps->uuid) }}" class="inline" data-aracari-ajax data-success-message="تم تغيير الحالة بنجاح">@csrf
                <button type="submit" class="w-9 h-9 rounded-xl bg-white border border-slate-200 transition-all duration-300 shadow-sm flex items-center justify-center {{ $isActive ? 'text-slate-500 hover:bg-slate-100 hover:text-slate-700 hover:border-slate-300' : 'text-emerald-600 hover:bg-emerald-50 hover:border-emerald-200' }}" title="{{ $isActive ? 'إيقاف' : 'تفعيل' }}">
                  <i data-lucide="{{ $isActive ? 'pause' : 'play' }}" class="w-4 h-4"></i>
                </button>
              </form>
              @endif
              @if($canDelete)
              <x-action-button variant="delete" data-delete-url="{{ route('admin.provider-services.destroy', $ps->uuid) }}" data-delete-label="{{ $ps->name }}" />
              @endif
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state icon="concierge-bell" :title="$hasFilters ? 'لا توجد نتائج مطابقة' : 'لا توجد خدمات مزودين'" :message="$hasFilters ? 'جرّب تعديل الفلاتر للعثور على الخدمات المطلوبة' : 'أضف خدمة مزود جديدة لبدء إدارة الخدمات'" :colspan="8">
          @if(!$hasFilters && $canCreate)
            <button type="button" data-create-modal="ps-form-modal" class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"><i data-lucide="plus-circle" class="w-4 h-4"></i> إضافة خدمة مزود جديدة</button>
          @endif
          @if($hasFilters)
            <a href="{{ route('admin.provider-services.index') }}" class="mt-3 text-teal-600 hover:text-teal-700 inline-flex items-center gap-1 font-bold text-sm"><i data-lucide="refresh-cw" class="w-4 h-4"></i> إعادة تعيين الفلاتر</a>
          @endif
        </x-empty-state>
      @endforelse
    </x-slot>
    <x-slot name="footer">
      <x-pagination :paginator="$providerServices" />
    </x-slot>
  </x-data-table-card>

  </div>{{-- end #aracari-refreshable --}}

</div>

{{-- ===== Create/Edit Form Modal ===== --}}
<x-form-modal
    id="ps-form-modal"
    title="إضافة خدمة مزود جديدة"
    :action="route('admin.provider-services.store')"
    method="POST"
    successMessage="تم إضافة خدمة المزود بنجاح"
    maxWidth="max-w-2xl"
>

  {{-- القسم 1: البيانات الأساسية --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs flex items-center gap-1.5 text-[#115128] ps-form-accent"><i data-lucide="info" class="w-3.5 h-3.5"></i> البيانات الأساسية</h4>
      <span class="text-[9px] font-bold text-rose-600 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-full ps-form-required-badge">مطلوب</span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

      {{-- المزود (user_id) --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="ps-user-id"><i data-lucide="user" class="w-3.5 h-3.5"></i> المزود <span class="text-rose-500 ps-user-required">*</span></label>
        {{-- Create: select --}}
        <div id="ps-user-select-wrap" class="relative">
          <select id="ps-user-id" name="user_id" class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none cursor-pointer">
            <option value="">اختر المزود</option>
            @foreach($users as $u)
              <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
          </select>
          <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
          <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
        </div>
        {{-- Edit: readonly --}}
        <div id="ps-user-readonly-wrap" class="hidden relative">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400"><i data-lucide="user" class="w-4 h-4"></i></div>
          <input id="ps-user-readonly" type="text" name="user_name_display" readonly class="w-full bg-slate-100/50 border border-slate-200 rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold text-slate-500 cursor-not-allowed outline-none text-right">
          <input type="hidden" id="ps-user-id-hidden" disabled>
        </div>
      </div>

      {{-- اسم الخدمة --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="ps-name"><i data-lucide="tag" class="w-3.5 h-3.5"></i> اسم الخدمة <span class="text-rose-500">*</span></label>
        <div class="relative group">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none transition-colors text-slate-400 group-focus-within:text-[#115128]"><i data-lucide="tag" class="w-4 h-4"></i></div>
          <input id="ps-name" type="text" name="name" placeholder="مثال: خدمات النقل السريع" required class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right">
        </div>
      </div>

      {{-- نوع الخدمة --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="ps-type"><i data-lucide="layers" class="w-3.5 h-3.5"></i> نوع الخدمة <span class="text-rose-500">*</span></label>
        <div class="relative">
          <select id="ps-type" name="type_of_service_id" class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none cursor-pointer">
            <option value="">اختر نوع الخدمة</option>
            @foreach($serviceTypes as $st)
              <option value="{{ $st->id }}">{{ $st->type_name }}</option>
            @endforeach
          </select>
          <i data-lucide="layers" class="w-4 h-4 text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
          <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
        </div>
      </div>

      {{-- السجل التجاري --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="ps-cr-number"><i data-lucide="file-text" class="w-3.5 h-3.5"></i> السجل التجاري <span class="text-rose-500">*</span></label>
        <div class="relative group">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none transition-colors text-slate-400 group-focus-within:text-[#115128]"><i data-lucide="file-text" class="w-4 h-4"></i></div>
          <input id="ps-cr-number" type="text" name="cr_number" dir="ltr" placeholder="CR-1010-2024" required class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold font-mono outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left">
        </div>
      </div>

      {{-- الحالة --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="ps-status"><i data-lucide="toggle-right" class="w-3.5 h-3.5"></i> الحالة <span class="text-rose-500">*</span></label>
        <div class="relative">
          <select id="ps-status" name="status" class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none cursor-pointer">
            <option value="active">نشط</option>
            <option value="pending">في الانتظار</option>
            <option value="inactive">غير نشط</option>
          </select>
          <i data-lucide="toggle-right" class="w-4 h-4 text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
          <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
        </div>
      </div>

      {{-- نوع العمولة --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="ps-commission-type"><i data-lucide="percent" class="w-3.5 h-3.5"></i> نوع العمولة <span class="text-rose-500">*</span></label>
        <div class="relative">
          <select id="ps-commission-type" name="commission_type" class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none cursor-pointer">
            <option value="service">خدمة</option>
            <option value="booking">حجز</option>
          </select>
          <i data-lucide="percent" class="w-4 h-4 text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
          <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
        </div>
      </div>

      {{-- هاتف التواصل --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="ps-phone"><i data-lucide="phone" class="w-3.5 h-3.5"></i> هاتف التواصل</label>
        <div class="relative group">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none transition-colors text-slate-400 group-focus-within:text-[#115128]"><i data-lucide="phone" class="w-4 h-4"></i></div>
          <input id="ps-phone" type="text" name="contact_phone" dir="ltr" placeholder="+967 1 555 1010" class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold font-mono outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left">
        </div>
      </div>

      {{-- البريد الإلكتروني --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="ps-email"><i data-lucide="mail" class="w-3.5 h-3.5"></i> البريد الإلكتروني</label>
        <div class="relative group">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none transition-colors text-slate-400 group-focus-within:text-[#115128]"><i data-lucide="mail" class="w-4 h-4"></i></div>
          <input id="ps-email" type="email" name="contact_email" dir="ltr" placeholder="info@example.com" class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left">
        </div>
      </div>

      {{-- وصف الخدمة --}}
      <div class="md:col-span-2 space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="ps-description"><i data-lucide="file-text" class="w-3.5 h-3.5"></i> وصف الخدمة</label>
        <textarea id="ps-description" name="description" rows="3" placeholder="وصف مختصر للخدمة..." class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl p-3 text-[13px] font-medium outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all resize-none text-right"></textarea>
      </div>
    </div>
  </div>

  {{-- القسم 2: شعار المزود --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs flex items-center gap-1.5 text-purple-600"><i data-lucide="image" class="w-3.5 h-3.5"></i> شعار المزود</h4>
      <span class="text-[9px] font-bold text-slate-500 bg-slate-50 border border-slate-200 px-2 py-0.5 rounded-full">اختياري</span>
    </div>
    {{-- الشعار الحالي (edit mode only) --}}
    <div id="ps-existing-brand" class="hidden space-y-1.5">
      <label class="block text-[11px] font-bold text-slate-500 flex items-center gap-1"><i data-lucide="image" class="w-3.5 h-3.5"></i> الشعار الحالي</label>
      <div class="relative w-28 h-28 rounded-xl overflow-hidden border-2 border-slate-200 group">
        <img id="ps-existing-brand-img" src="" alt="" class="w-full h-full object-cover">
      </div>
    </div>
    <div class="space-y-1.5">
      <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="ps-brand-photo"><i data-lucide="upload" class="w-3.5 h-3.5"></i> <span id="ps-brand-label">شعار المزود</span></label>
      <label for="ps-brand-photo" id="ps-brand-upload-label" class="flex flex-col items-center justify-center w-full h-28 rounded-xl border-2 border-dashed border-slate-200 bg-[#f3f3f6] hover:bg-[#eeeef0] cursor-pointer transition-all">
        <div class="flex flex-col items-center justify-center pt-3"><i data-lucide="upload" class="w-6 h-6 mb-1 text-slate-400"></i><p class="text-[11px] font-bold text-slate-500">اضغط لاختيار شعار</p><p class="text-[9px] text-slate-400 mt-0.5">PNG، JPG، WEBP — حتى 2MB</p></div>
        <input id="ps-brand-photo" type="file" name="brand_photo" accept="image/jpeg,image/png,image/jpg,image/webp" class="hidden" onchange="psPreviewBrand(this)">
      </label>
      <div id="ps-brand-preview" class="hidden items-start gap-3">
        <div class="relative w-28 h-28 rounded-xl overflow-hidden border-2 border-teal-200 flex-shrink-0">
          <img id="ps-brand-preview-img" src="" alt="معاينة" class="w-full h-full object-cover">
          <button type="button" onclick="psRemoveBrand()" class="absolute top-1 left-1 w-6 h-6 rounded-full bg-rose-500 text-white flex items-center justify-center hover:bg-rose-600 transition-colors shadow"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
        </div>
        <p class="text-[10px] text-slate-500 mt-1 leading-relaxed">تم اختيار شعار جديد. اضغط على × لإزالته.</p>
      </div>
    </div>
  </div>

  {{-- القسم 3: مستند السجل التجاري --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs flex items-center gap-1.5 text-amber-600"><i data-lucide="file-text" class="w-3.5 h-3.5"></i> مستند السجل التجاري</h4>
      <span class="text-[9px] font-bold text-slate-500 bg-slate-50 border border-slate-200 px-2 py-0.5 rounded-full">اختياري</span>
    </div>
    <div id="ps-existing-crdoc" class="hidden space-y-1.5">
      <label class="block text-[11px] font-bold text-slate-500 flex items-center gap-1"><i data-lucide="file-text" class="w-3.5 h-3.5"></i> المستند الحالي</label>
      <a id="ps-existing-crdoc-link" href="#" target="_blank" rel="noreferrer" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-amber-200 bg-amber-50 text-amber-700 text-xs font-bold hover:bg-amber-100 transition-colors"><i data-lucide="external-link" class="w-3.5 h-3.5"></i> عرض المستند الحالي</a>
    </div>
    <div class="space-y-1.5">
      <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="ps-cr-document"><i data-lucide="upload" class="w-3.5 h-3.5"></i> <span id="ps-crdoc-label">مستند السجل التجاري</span></label>
      <label for="ps-cr-document" id="ps-crdoc-upload-label" class="flex flex-col items-center justify-center w-full h-28 rounded-xl border-2 border-dashed border-slate-200 bg-[#f3f3f6] hover:bg-[#eeeef0] cursor-pointer transition-all">
        <div class="flex flex-col items-center justify-center pt-3"><i data-lucide="upload" class="w-6 h-6 mb-1 text-slate-400"></i><p class="text-[11px] font-bold text-slate-500">اضغط لاختيار مستند</p><p class="text-[9px] text-slate-400 mt-0.5">PDF، JPG، PNG — حتى 2MB</p></div>
        <input id="ps-cr-document" type="file" name="cr_document" accept=".pdf,.jpg,.jpeg,.png" class="hidden" onchange="psShowCrDoc(this)">
      </label>
      <div id="ps-crdoc-preview" class="hidden items-center gap-3 p-3 rounded-xl border-2 border-amber-200 bg-amber-50/50">
        <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center text-amber-600 flex-shrink-0"><i data-lucide="file-text" class="w-5 h-5"></i></div>
        <div class="flex-1 min-w-0"><p id="ps-crdoc-name" class="text-xs font-bold text-slate-700 truncate"></p><p class="text-[10px] text-slate-500">سيتم رفع المستند عند الحفظ</p></div>
        <button type="button" onclick="psRemoveCrDoc()" class="w-7 h-7 rounded-full bg-rose-500 text-white flex items-center justify-center hover:bg-rose-600 transition-colors shadow flex-shrink-0"><i data-lucide="x" class="w-4 h-4"></i></button>
      </div>
    </div>
  </div>

</x-form-modal>

{{-- ===== Details View Modal ===== --}}
<x-modal id="ps-details-modal" title="تفاصيل خدمة المزود" variant="view" :static="true" maxWidth="max-w-4xl">
  <div id="ps-details-body" class="space-y-5">
    <div class="text-center py-12"><div class="animate-spin w-8 h-8 mx-auto border-4 border-teal-500 border-t-transparent rounded-full"></div><p class="text-sm text-slate-400 mt-3">جارٍ تحميل البيانات...</p></div>
  </div>
</x-modal>

@push('scripts')
<script>
// ===== File upload helpers =====
function psPreviewBrand(input) {
  var preview = document.getElementById('ps-brand-preview');
 var label = document.getElementById('ps-brand-upload-label');
  var img = document.getElementById('ps-brand-preview-img');
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) { img.src = e.target.result; preview.classList.remove('hidden'); preview.classList.add('flex'); label.classList.add('hidden'); };
    reader.readAsDataURL(input.files[0]);
  }
}
function psRemoveBrand() {
  var preview = document.getElementById('ps-brand-preview');
  var label = document.getElementById('ps-brand-upload-label');
  document.getElementById('ps-brand-photo').value = '';
  preview.classList.add('hidden'); preview.classList.remove('flex'); label.classList.remove('hidden');
}
function psShowCrDoc(input) {
  var preview = document.getElementById('ps-crdoc-preview');
  var nameEl = document.getElementById('ps-crdoc-name');
  var label = document.getElementById('ps-crdoc-upload-label');
  if (input.files && input.files[0]) {
    nameEl.textContent = input.files[0].name;
    preview.classList.remove('hidden'); preview.classList.add('flex'); label.classList.add('hidden');
  }
}
function psRemoveCrDoc() {
  var preview = document.getElementById('ps-crdoc-preview');
  var label = document.getElementById('ps-crdoc-upload-label');
  document.getElementById('ps-cr-document').value = '';
  preview.classList.add('hidden'); preview.classList.remove('flex'); label.classList.remove('hidden');
}

// ===== Form Modal: switch create/edit mode =====
document.addEventListener('aracari:modal-open', function(e) {
  var modal = e.target;
  if (modal.id !== 'ps-form-modal') return;
  var data = e.detail.data || {};
  var form = modal.querySelector('form');
  var isEdit = !!(data.uuid);
  var h24 = modal.querySelector('.aracari-modal-panel .relative.h-24');
  var h2 = modal.querySelector('.aracari-modal-panel h2');
  var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
  var submitBtn = form.querySelector('[type="submit"] span');

  // Toggle user field: select vs readonly
  var selectWrap = document.getElementById('ps-user-select-wrap');
  var readonlyWrap = document.getElementById('ps-user-readonly-wrap');
  var userRequired = modal.querySelector('.ps-user-required');
  var requiredBadge = modal.querySelector('.ps-form-required-badge');
  var accentLabel = modal.querySelector('.ps-form-accent');

  // Toggle existing brand/cr doc
  var existingBrand = document.getElementById('ps-existing-brand');
  var existingCrDoc = document.getElementById('ps-existing-crdoc');

  if (isEdit) {
    // Switch to PUT
    var updateUrl = "{{ route('admin.provider-services.update', '__UUID__') }}".replace('__UUID__', data.uuid);
    form.setAttribute('action', updateUrl);
    var m = form.querySelector('input[name="_method"]');
    if (m) m.value = 'PUT'; else { var mi = document.createElement('input'); mi.type = 'hidden'; mi.name = '_method'; mi.value = 'PUT'; mi.setAttribute('data-dynamic', ''); form.appendChild(mi); }
    // Banner → amber
    h24.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';
    h2.innerHTML = '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل خدمة المزود';
    badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
    badge.textContent = 'تحديث';
    form.setAttribute('data-success-message', 'تم تحديث خدمة المزود بنجاح');
    if (submitBtn) submitBtn.textContent = 'حفظ التعديلات';
    // User → readonly
    selectWrap.classList.add('hidden');
    readonlyWrap.classList.remove('hidden');
    // Disable select so it doesn't submit
    var selectEl = document.getElementById('ps-user-id');
    if (selectEl) selectEl.removeAttribute('name');
    var hiddenInput = document.getElementById('ps-user-id-hidden');
    if (hiddenInput) { hiddenInput.disabled = false; hiddenInput.name = 'user_id'; hiddenInput.value = data.user_id; }
    if (userRequired) userRequired.style.display = 'none';
    if (requiredBadge) requiredBadge.textContent = 'قابل للتعديل';
    if (accentLabel) accentLabel.className = 'font-bold text-xs flex items-center gap-1.5 text-amber-600 ps-form-accent';
    // Show existing brand/crdoc
    if (data.brand_photo_url) { existingBrand.classList.remove('hidden'); document.getElementById('ps-existing-brand-img').src = data.brand_photo_url; }
    if (data.cr_document_url) { existingCrDoc.classList.remove('hidden'); document.getElementById('ps-existing-crdoc-link').href = data.cr_document_url; }
    // Update upload labels
    document.getElementById('ps-brand-label').textContent = 'رفع شعار جديد (اختياري)';
    document.getElementById('ps-crdoc-label').textContent = 'رفع مستند جديد (اختياري)';
  } else {
    // Create mode → POST
    form.setAttribute('action', "{{ route('admin.provider-services.store') }}");
    var m = form.querySelector('input[name="_method"][data-dynamic]'); if (m) m.remove();
    h24.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
    h2.innerHTML = '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة خدمة مزود جديدة';
    badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30';
    badge.textContent = 'جديد';
    form.setAttribute('data-success-message', 'تم إضافة خدمة المزود بنجاح');
    if (submitBtn) submitBtn.textContent = 'حفظ الخدمة';
    // User → select
    selectWrap.classList.remove('hidden');
    readonlyWrap.classList.add('hidden');
    // Restore select name, disable hidden
    var selectEl = document.getElementById('ps-user-id');
    if (selectEl) selectEl.name = 'user_id';
    var hiddenInput = document.getElementById('ps-user-id-hidden');
    if (hiddenInput) { hiddenInput.disabled = true; hiddenInput.removeAttribute('name'); hiddenInput.value = ''; }
    if (userRequired) userRequired.style.display = '';
    if (requiredBadge) requiredBadge.textContent = 'مطلوب';
    if (accentLabel) accentLabel.className = 'font-bold text-xs flex items-center gap-1.5 text-[#115128] ps-form-accent';
    existingBrand.classList.add('hidden');
    existingCrDoc.classList.add('hidden');
    document.getElementById('ps-brand-label').textContent = 'شعار المزود';
    document.getElementById('ps-crdoc-label').textContent = 'مستند السجل التجاري';
  }
  if (window.lucide) window.lucide.createIcons();
});

// ===== Details Modal: AJAX load content =====
document.addEventListener('aracari:modal-open', function(e) {
  var modal = e.target;
  if (modal.id !== 'ps-details-modal') return;
  var data = e.detail.data || {};
  if (!data.uuid) return;
  var body = document.getElementById('ps-details-body');
  body.innerHTML = '<div class="text-center py-12"><div class="animate-spin w-8 h-8 mx-auto border-4 border-teal-500 border-t-transparent rounded-full"></div><p class="text-sm text-slate-400 mt-3">جارٍ تحميل البيانات...</p></div>';
  fetch('/admin/provider-services/' + data.uuid + '?ajax=1', {
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
  }).then(function(r) { return r.text(); }).then(function(html) {
    body.innerHTML = html;
    if (window.lucide) window.lucide.createIcons();
    // Rebind delete/toggle buttons loaded inside the partial
    if (window.Aracari && window.Aracari.refreshTableCard) { /* bindRowActions not needed - aracari.js handles on DOMContentLoaded */ }
    // Re-bind event delegation (buttons inside the partial need binding)
    var deleteBtns = body.querySelectorAll('[data-delete-url]');
    deleteBtns.forEach(function(btn) {
      if (!btn.__aracariBound) {
        btn.__aracariBound = true;
        btn.addEventListener('click', function(ev) {
          ev.preventDefault();
          if (window.Aracari) window.Aracari.openDeleteConfirm(btn.getAttribute('data-delete-url'), btn.getAttribute('data-delete-label') || '');
        });
      }
    });
    // Re-bind form AJAX inside partial
    var ajaxForms = body.querySelectorAll('[data-aracari-ajax]');
    ajaxForms.forEach(function(f) {
      if (!f.__aracariBound) {
        f.__aracariBound = true;
        f.addEventListener('submit', function(ev) { ev.preventDefault(); if (window.Aracari) window.Aracari.openModal && window.Aracari.closeModal('ps-details-modal'); });
      }
    });
  });
});
</script>
@endpush

@endsection
