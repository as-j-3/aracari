@extends('layouts.provider')

@section('title', 'أركاري | كوبونات الخصم')
@section('header_title', 'كوبونات الخصم والترويج')

@section('content')

@php
    // ===== الإحصائيات من وحدة التحكم =====
    $totalCoupons   = $statusCounts['total'];
    $activeCoupons  = $statusCounts['active'];
    $expiredCoupons = $statusCounts['expired'];

    // ===== خريطة حالات الكوبونات =====
    $statusMap = [
        \App\Constants\CouponStatus::ACTIVE   => ['label' => 'نشط',     'bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'dot' => 'bg-emerald-500'],
        \App\Constants\CouponStatus::INACTIVE => ['label' => 'غير نشط', 'bg' => 'bg-slate-100 text-slate-600 border-slate-200',     'dot' => 'bg-slate-400'],
        \App\Constants\CouponStatus::EXPIRED  => ['label' => 'منتهي',   'bg' => 'bg-rose-50 text-rose-700 border-rose-200',         'dot' => 'bg-rose-500'],
        \App\Constants\CouponStatus::USED     => ['label' => 'مستخدم',  'bg' => 'bg-amber-50 text-amber-700 border-amber-200',      'dot' => 'bg-amber-500'],
    ];

    // ===== التحقق من الصلاحيات =====
    $currentUser = auth()->user();
    $canCreate = $currentUser && $currentUser->hasPermission('provider:coupons:create');
    $canEdit   = $currentUser && $currentUser->hasPermission('provider:coupons:edit');
    $canDelete = $currentUser && $currentUser->hasPermission('provider:coupons:delete');

    // ===== حالات مخصصة: مجدول (لم يبدأ بعد) =====
    function getCouponDisplayStatus($coupon) {
        if ($coupon->is_expired) return \App\Constants\CouponStatus::EXPIRED;
        if ($coupon->isFullyUsed()) return \App\Constants\CouponStatus::USED;
        if ($coupon->start_date && now()->lt($coupon->start_date)) return 'scheduled';
        if ($coupon->isValid()) return \App\Constants\CouponStatus::ACTIVE;
        return $coupon->status_id;
    }

    $scheduledStatus = [
        'label' => 'مجدول',
        'bg' => 'bg-amber-50 text-amber-700 border-amber-200',
        'dot' => 'bg-amber-500',
    ];
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="ticket"
      title="كوبونات الخصم"
      :count="$totalCoupons"
      countLabel="كوبون"
      description="إنشاء وإدارة كوبونات الخصم لجذب العملاء وزيادة الحجوزات"
      descriptionIcon="ticket"
  >
    @if($canCreate)
      <button
          type="button"
          data-create-modal="coupon-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إنشاء كوبون جديد
      </button>
    @endif
  </x-page-header>

  {{-- ===== Refreshable region: stats + table ===== --}}
  <div id="aracari-refreshable" class="space-y-6">

  {{-- ===== Stat Cards ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 lg:gap-6">
    <x-stat-card :value="$totalCoupons" label="إجمالي الكوبونات" icon="ticket" variant="slate" badge="كوبون" badgeIcon="ticket" />
    <x-stat-card :value="$activeCoupons" label="كوبونات نشطة" icon="check-circle" variant="emerald" badge="نشط" badgeIcon="check-circle" />
    <x-stat-card :value="$expiredCoupons" label="كوبونات منتهية" icon="alert-circle" variant="rose" badge="منتهي" badgeIcon="alert-circle" />
  </div>

  {{-- ===== Data Table Card ===== --}}
  <x-data-table-card :searchable="true" :searchMode="'server'" searchPlaceholder="ابحث بكود الكوبون..." :footerInfo="'عرض ' . $coupons->count() . ' من أصل ' . $coupons->total() . ' كوبون'">
    <x-slot name="thead">
      <th class="px-5 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الكود</th>
      <th class="px-5 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الخصم</th>
      <th class="px-5 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الاستخدام</th>
      <th class="px-5 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">تاريخ البدء</th>
      <th class="px-5 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">تاريخ الانتهاء</th>
      <th class="px-5 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
      <th class="px-5 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
    </x-slot>

    <x-slot name="rows">
      @forelse($coupons as $coupon)
        @php
          $displayStatus = getCouponDisplayStatus($coupon);
          $statusInfo = $statusMap[$displayStatus] ?? $scheduledStatus;
        @endphp
        <tr class="hover:bg-slate-50/50 transition-colors group">

          {{-- الكود (monospace + copy) --}}
          <td class="px-5 py-4">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-teal-500/20 to-teal-600/10 flex items-center justify-center border border-teal-100 flex-shrink-0">
                <i data-lucide="ticket" class="w-4 h-4 text-teal-600"></i>
              </div>
              <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                  <span class="font-mono font-black text-teal-600 text-sm tracking-wide">{{ $coupon->code }}</span>
                  <button
                      type="button"
                      onclick="navigator.clipboard.writeText('{{ $coupon->code }}'); this.innerHTML = '<i data-lucide=\'check\' class=\'w-3 h-3 text-emerald-500\'></i>'; if(window.lucide) window.lucide.createIcons(); setTimeout(() => { this.innerHTML = '<i data-lucide=\'copy\' class=\'w-3 h-3 text-slate-400\'></i>'; if(window.lucide) window.lucide.createIcons(); }, 1500);"
                      class="p-1 rounded hover:bg-slate-100 transition-colors flex-shrink-0"
                      title="نسخ الكود"
                  >
                    <i data-lucide="copy" class="w-3 h-3 text-slate-400"></i>
                  </button>
                </div>
                @if($coupon->description)
                  <div class="text-[10px] text-slate-400 truncate max-w-[180px] mt-0.5">{{ $coupon->description }}</div>
                @endif
              </div>
            </div>
          </td>

          {{-- الخصم (نسبة مئوية) --}}
          <td class="px-5 py-4 text-center">
            <span class="inline-flex items-center gap-1 text-sm font-bold text-emerald-600">
              <i data-lucide="percent" class="w-3.5 h-3.5"></i>
              {{ number_format((float) $coupon->percentage, 0) }}%
            </span>
          </td>

          {{-- الاستخدام --}}
          <td class="px-5 py-4 text-center">
            <div class="flex flex-col items-center gap-1">
              <span class="text-xs font-bold text-slate-700">
                {{ $coupon->usages_count ?? 0 }}
                @if($coupon->usage_limit)
                  / {{ $coupon->usage_limit }}
                @else
                  / —
                @endif
              </span>
              @if($coupon->usage_limit && $coupon->usage_limit > 0)
                @php
                  $usagePercent = min(100, round((($coupon->usages_count ?? 0) / $coupon->usage_limit) * 100));
                  $usageColor = $usagePercent >= 80 ? 'bg-emerald-500' : ($usagePercent >= 50 ? 'bg-amber-500' : 'bg-rose-500');
                @endphp
                <div class="w-16 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                  <div class="{{ $usageColor }} h-full rounded-full transition-all" style="width: {{ $usagePercent }}%"></div>
                </div>
              @endif
            </div>
          </td>

          {{-- تاريخ البدء --}}
          <td class="px-5 py-4">
            <span class="text-xs text-slate-600 flex items-center gap-1">
              <i data-lucide="calendar" class="w-3 h-3 text-slate-400"></i>
              {{ optional($coupon->start_date)->translatedFormat('j F، Y') ?? '—' }}
            </span>
          </td>

          {{-- تاريخ الانتهاء --}}
          <td class="px-5 py-4">
            <span class="text-xs text-slate-600 flex items-center gap-1">
              <i data-lucide="calendar" class="w-3 h-3 text-slate-400"></i>
              {{ optional($coupon->end_date)->translatedFormat('j F، Y') ?? '—' }}
            </span>
          </td>

          {{-- الحالة --}}
          <td class="px-5 py-4 text-center">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold {{ $statusInfo['bg'] }} border">
              <span class="w-1.5 h-1.5 rounded-full {{ $statusInfo['dot'] }}"></span>
              {{ $statusInfo['label'] }}
            </span>
          </td>

          {{-- الإجراءات --}}
          <td class="px-5 py-4 text-center">
            <div class="flex items-center justify-center gap-1.5">
              @if($canEdit)
                <x-action-button
                    variant="edit"
                    data-edit-modal="coupon-form-modal"
                    data-edit-json="{{ json_encode([
                        'uuid'            => $coupon->uuid,
                        'code'            => $coupon->code,
                        'percentage'      => $coupon->percentage,
                        'description'     => $coupon->description,
                        'start_date'      => optional($coupon->start_date)->format('Y-m-d'),
                        'end_date'        => optional($coupon->end_date)->format('Y-m-d'),
                        'usage_limit'     => $coupon->usage_limit,
                        'max_usage_per_user' => $coupon->max_usage_per_user,
                        'minimum_amount'  => $coupon->minimum_amount,
                        'status_id'       => $coupon->status_id,
                    ], JSON_UNESCAPED_UNICODE) }}"
                    :ariaLabel="'تعديل ' . $coupon->code"
                />
              @endif
              @if($canDelete)
                <x-action-button
                    variant="delete"
                    data-delete-url="{{ route('admin.provider.coupons.destroy', $coupon->uuid) }}"
                    data-delete-label="{{ $coupon->code }}"
                />
              @endif
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state
            icon="ticket"
            :title="request('search') ? 'لا توجد نتائج مطابقة' : 'لا توجد كوبونات'"
            :message="request('search') ? 'جرّب تعديل كلمات البحث' : 'لم تقم بإنشاء أي كوبون بعد. أنشئ كوبون خصم جديد لجذب العملاء وزيادة الحجوزات'"
            :colspan="7"
        >
          @if(!request('search') && $canCreate)
            <button
                type="button"
                data-create-modal="coupon-form-modal"
                class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
            >
              <i data-lucide="plus" class="w-4 h-4"></i>
              إنشاء كوبون جديد
            </button>
          @endif
        </x-empty-state>
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$coupons" />
    </x-slot>
  </x-data-table-card>

  </div>{{-- end #aracari-refreshable -->}

</div>

{{-- ===== Coupon Form Modal (Create + Edit) ===== --}}
<x-form-modal
    id="coupon-form-modal"
    title="إنشاء كوبون خصم جديد"
    :variant="'create'"
    :action="route('admin.provider.coupons.store')"
    method="POST"
    successMessage="تم إنشاء الكوبون بنجاح"
>

  {{-- كود الكوبون --}}
  <x-form-field name="code" label="كود الكوبون" icon="ticket" required>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="ticket" class="w-4 h-4"></i>
      </div>
      <input
          type="text"
          name="code"
          id="coupon_code"
          required
          placeholder="SUMMER20"
          dir="ltr"
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold font-mono tracking-widest uppercase outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-center"
      >
    </div>
  </x-form-field>

  {{-- نسبة الخصم + الحد الأدنى للمبلغ --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <x-form-field name="percentage" label="نسبة الخصم (%)" icon="percent" type="number" required>
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="percent" class="w-4 h-4"></i>
        </div>
        <input
            type="number"
            name="percentage"
            id="coupon_percentage"
            required
            min="0"
            max="100"
            step="0.01"
            placeholder="20"
            dir="ltr"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
        >
      </div>
    </x-form-field>

    <x-form-field name="minimum_amount" label="الحد الأدنى للمبلغ (ر.س)" icon="dollar-sign" type="number">
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="dollar-sign" class="w-4 h-4"></i>
        </div>
        <input
            type="number"
            name="minimum_amount"
            id="coupon_minimum_amount"
            min="0"
            step="0.01"
            placeholder="50"
            dir="ltr"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
        >
      </div>
    </x-form-field>
  </div>

  {{-- تاريخ البدء + تاريخ الانتهاء --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <x-form-field name="start_date" label="تاريخ البدء" icon="calendar" type="date" required>
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="calendar" class="w-4 h-4"></i>
        </div>
        <input
            type="date"
            name="start_date"
            id="coupon_start_date"
            required
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
        >
      </div>
    </x-form-field>

    <x-form-field name="end_date" label="تاريخ الانتهاء" icon="calendar" type="date" required>
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="calendar" class="w-4 h-4"></i>
        </div>
        <input
            type="date"
            name="end_date"
            id="coupon_end_date"
            required
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
        >
      </div>
    </x-form-field>
  </div>

  {{-- حدود الاستخدام --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <x-form-field name="usage_limit" label="حد الاستخدام العام" icon="hash" type="number">
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="hash" class="w-4 h-4"></i>
        </div>
        <input
            type="number"
            name="usage_limit"
            id="coupon_usage_limit"
            min="1"
            placeholder="100"
            dir="ltr"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
        >
      </div>
    </x-form-field>

    <x-form-field name="max_usage_per_user" label="حد الاستخدام لكل مستخدم" icon="users" type="number">
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="users" class="w-4 h-4"></i>
        </div>
        <input
            type="number"
            name="max_usage_per_user"
            id="coupon_max_usage_per_user"
            min="1"
            placeholder="3"
            dir="ltr"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
        >
      </div>
    </x-form-field>
  </div>

  {{-- الحالة --}}
  <div class="space-y-2">
    <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="coupon_status_id">
      <i data-lucide="toggle-right" class="w-3.5 h-3.5"></i>
      الحالة <span class="text-rose-500">*</span>
    </label>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="toggle-right" class="w-4 h-4"></i>
      </div>
      <select
          name="status_id"
          id="coupon_status_id"
          required
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-9 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none cursor-pointer"
      >
        @foreach($couponStatuses as $cs)
          <option value="{{ $cs->id }}">{{ $cs->name }}</option>
        @endforeach
      </select>
      <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
        <i data-lucide="chevron-down" class="w-4 h-4"></i>
      </div>
    </div>
    <p class="text-[11px] text-rose-600 font-bold" data-error-for="status_id"></p>
  </div>

  {{-- الوصف --}}
  <div class="space-y-2">
    <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="coupon_description">
      <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
      الوصف
    </label>
    <textarea
        name="description"
        id="coupon_description"
        rows="2"
        placeholder="وصف مختصر للكوبون..."
        class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 py-2.5 text-[13px] font-medium outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all resize-none"
    ></textarea>
    <p class="text-[11px] text-rose-600 font-bold" data-error-for="description"></p>
  </div>

  {{-- تنبيه الكوبون --}}
  <div class="bg-teal-50/70 border border-teal-200 rounded-xl p-3">
    <div class="flex items-start gap-2.5">
      <i data-lucide="info" class="w-4 h-4 text-teal-500 flex-shrink-0 mt-0.5"></i>
      <span class="text-[11px] text-teal-600">
        سيتم إنشاء الكوبون بناءً على النسبة المئوية المحددة. يمكن للعملاء إدخال الكود عند الحجز للاستفادة من الخصم.
      </span>
    </div>
  </div>

</x-form-modal>

@endsection

@push('scripts')
<script>
  // ===== Coupon modal: dynamic action + title + variant switching =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'coupon-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.uuid);

    if (isEdit) {
      var updateUrl = "{{ route('admin.provider.coupons.update', '__UUID__') }}".replace('__UUID__', data.uuid);
      form.setAttribute('action', updateUrl);
      var existingMethod = form.querySelector('input[name="_method"]');
      if (existingMethod) existingMethod.value = 'PUT';
      else {
        var m = document.createElement('input');
        m.type = 'hidden'; m.name = '_method'; m.value = 'PUT';
        form.appendChild(m);
      }

      // Switch banner to amber (edit mode)
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل الكوبون';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }
      form.setAttribute('data-success-message', 'تم تحديث الكوبون بنجاح');
    } else {
      form.setAttribute('action', "{{ route('admin.provider.coupons.store') }}");
      var m = form.querySelector('input[name="_method"]');
      if (m) m.remove();

      // Switch banner to teal (create mode)
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="plus-circle" class="w-5 h-5"></i> إنشاء كوبون خصم جديد';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30';
        badge.textContent = 'جديد';
      }
      form.setAttribute('data-success-message', 'تم إنشاء الكوبون بنجاح');
    }

    // Pre-fill form fields
    var fieldMap = {
      'coupon_code':              'code',
      'coupon_percentage':        'percentage',
      'coupon_description':       'description',
      'coupon_start_date':        'start_date',
      'coupon_end_date':          'end_date',
      'coupon_usage_limit':       'usage_limit',
      'coupon_max_usage_per_user':'max_usage_per_user',
      'coupon_minimum_amount':    'minimum_amount',
    };
    Object.keys(fieldMap).forEach(function(fieldId) {
      var el = document.getElementById(fieldId);
      if (el) {
        var val = data[fieldMap[fieldId]];
        el.value = (val !== null && val !== undefined) ? val : '';
      }
    });

    // Pre-fill status select
    var statusSelect = document.getElementById('coupon_status_id');
    if (statusSelect && data.status_id) {
      statusSelect.value = data.status_id;
    }

    // Re-render lucide icons
    if (window.lucide) window.lucide.createIcons();
  });
</script>
@endpush
