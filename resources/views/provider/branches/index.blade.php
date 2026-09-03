@extends('layouts.provider')

@section('title', 'أركاري | إدارة الفروع')
@section('header_title', 'إدارة الفروع')

@section('content')

@php
    $totalBranches  = $branches->total();
    $allItems       = $branches->getCollection();
    $activeBranches = $allItems->filter(fn($b) => $b->status === 'active')->count();
    $inactiveBranches = $allItems->filter(fn($b) => $b->status === 'inactive')->count();

    // التحقق من الصلاحيات
    $currentUser = auth()->user();
    $canCreate = $currentUser && $currentUser->hasPermission('provider:branches:create');
    $canEdit   = $currentUser && $currentUser->hasPermission('provider:branches:edit');
    $canDelete = $currentUser && $currentUser->hasPermission('provider:branches:delete');

    // خريطة ألوان الحالة
    $statusBadges = [
        'active'   => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'inactive' => 'bg-rose-50 text-rose-700 border-rose-200',
    ];
    $statusLabels = [
        'active'   => 'نشط',
        'inactive' => 'غير نشط',
    ];
    $statusDotColors = [
        'active'   => 'bg-emerald-500',
        'inactive' => 'bg-rose-500',
    ];
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== رأس الصفحة ===== --}}
  <x-page-header
      icon="store"
      title="فروع المزود"
      :count="$totalBranches"
      countLabel="فرع"
      description="إدارة فروع مزود الخدمات الخاص بك"
      descriptionIcon="store"
  >
    @if($canCreate)
      <button
          type="button"
          data-create-modal="branch-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إضافة فرع جديد
      </button>
    @endif
  </x-page-header>

  {{-- ===== منطقة التحديث: الإحصائيات + الجدول ===== --}}
  <div id="aracari-refreshable" class="space-y-6">

  {{-- ===== بطاقات الإحصائيات ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 lg:gap-6">
    <x-stat-card :value="$totalBranches" label="إجمالي الفروع" icon="store" variant="slate" badge="فرع" badgeIcon="store" />
    <x-stat-card :value="$activeBranches" label="فروع نشطة" icon="check-circle" variant="emerald" badge="نشط" badgeIcon="check-circle" />
    <x-stat-card :value="$inactiveBranches" label="فروع معطّلة" icon="x-circle" variant="rose" badge="معطّل" badgeIcon="x-circle" />
  </div>

  {{-- ===== بطاقة جدول البيانات ===== --}}
  <x-data-table-card :searchable="true" :searchMode="'server'" searchPlaceholder="ابحث عن فرع..." :footerInfo="'عرض ' . $branches->count() . ' من أصل ' . $branches->total() . ' سجل'">
    <x-slot name="thead">
      <th class="px-4 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
      <th class="px-4 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">العنوان</th>
      <th class="px-4 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المدينة</th>
      <th class="px-4 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الهاتف</th>
      <th class="px-4 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
      <th class="px-4 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الرحلات</th>
      <th class="px-4 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الموظفون</th>
      <th class="px-4 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
    </x-slot>

    <x-slot name="rows">
      @forelse($branches as $branch)
        @php
          $badgeClass = $statusBadges[$branch->status] ?? 'bg-slate-100 text-slate-600 border-slate-200';
          $label      = $statusLabels[$branch->status] ?? 'غير معروف';
          $dotColor   = $statusDotColors[$branch->status] ?? 'bg-slate-400';
        @endphp
        <tr class="hover:bg-slate-50/50 transition-colors group">
          {{-- # --}}
          <td class="px-4 py-4">
            <span class="font-mono font-black text-slate-400 text-sm">{{ str_pad((string) $branch->id, 4, '0', STR_PAD_LEFT) }}</span>
          </td>

          {{-- العنوان --}}
          <td data-searchable class="px-4 py-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500/20 to-teal-600/10 flex items-center justify-center border border-teal-100 group-hover:scale-110 transition-transform duration-300 flex-shrink-0">
                <i data-lucide="store" class="w-5 h-5 text-teal-600"></i>
              </div>
              <div class="min-w-0">
                <div class="font-bold text-slate-800 text-sm truncate max-w-[200px]">{{ $branch->address }}</div>
                <div class="text-[10px] text-slate-400 flex items-center gap-1 mt-0.5">
                  <i data-lucide="calendar" class="w-3 h-3"></i>
                  {{ optional($branch->created_at)->translatedFormat('j F، Y') ?? '—' }}
                </div>
              </div>
            </div>
          </td>

          {{-- المدينة --}}
          <td data-searchable class="px-4 py-4">
            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-700 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-lg">
              <i data-lucide="map-pin" class="w-3 h-3 text-slate-400"></i>
              {{ $branch->city->name ?? '—' }}
            </span>
          </td>

          {{-- الهاتف --}}
          <td data-searchable class="px-4 py-4">
            <span class="text-xs font-bold text-slate-700 font-mono" dir="ltr">
              {{ $branch->phone_number ?? '—' }}
            </span>
          </td>

          {{-- الحالة --}}
          <td class="px-4 py-4 text-center">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $badgeClass }}">
              <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
              {{ $label }}
            </span>
          </td>

          {{-- الرحلات --}}
          <td class="px-4 py-4 text-center">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 text-[10px] font-bold border border-blue-200">
              <i data-lucide="bus" class="w-3 h-3"></i>
              {{ $branch->trips_count ?? 0 }} رحلة
            </span>
          </td>

          {{-- الموظفون --}}
          <td class="px-4 py-4 text-center">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-cyan-50 text-cyan-700 text-[10px] font-bold border border-cyan-200">
              <i data-lucide="users" class="w-3 h-3"></i>
              {{ $branch->branch_users_count ?? 0 }} موظف
            </span>
          </td>

          {{-- الإجراءات --}}
          <td class="px-4 py-4 text-center">
            <div class="flex items-center justify-center gap-1.5">
              {{-- عرض التفاصيل --}}
              <x-action-button
                  variant="view"
                  data-view-modal="branch-details-modal"
                  data-view-json="{{ json_encode([
                      'uuid'            => $branch->uuid,
                      'address'         => $branch->address,
                      'phone_number'    => $branch->phone_number,
                      'city_name'       => $branch->city->name ?? '',
                      'country_name'    => $branch->country->name ?? '',
                      'latitude'        => (string) ($branch->latitude ?? ''),
                      'longitude'       => (string) ($branch->longitude ?? ''),
                      'status'          => $branch->status,
                      'trips_count'     => $branch->trips_count ?? 0,
                      'branch_users_count' => $branch->branch_users_count ?? 0,
                      'created_at'      => optional($branch->created_at)->translatedFormat('j F، Y') ?? '',
                      'updated_at'      => optional($branch->updated_at)->diffForHumans() ?? '',
                  ], JSON_UNESCAPED_UNICODE) }}"
              />

              {{-- تعديل --}}
              @if($canEdit)
                <x-action-button
                    variant="edit"
                    data-edit-modal="branch-form-modal"
                    data-edit-json="{{ json_encode([
                        'uuid'         => $branch->uuid,
                        'city_id'      => $branch->city_id,
                        'address'      => $branch->address,
                        'phone_number' => $branch->phone_number,
                        'latitude'     => (string) ($branch->latitude ?? ''),
                        'longitude'    => (string) ($branch->longitude ?? ''),
                        'status'       => $branch->status,
                    ], JSON_UNESCAPED_UNICODE) }}"
                    :ariaLabel="'تعديل ' . $branch->address"
                />
              @endif

              {{-- تبديل الحالة --}}
              @if($canEdit)
                <x-action-button
                    variant="custom"
                    icon="{{ $branch->status === 'active' ? 'pause-circle' : 'play-circle' }}"
                    :title="$branch->status === 'active' ? 'إيقاف' : 'تفعيل'"
                    :ariaLabel="$branch->status === 'active' ? 'إيقاف الفرع' : 'تفعيل الفرع'"
                    data-toggle-url="{{ route('admin.provider.branches.toggle-status', $branch->uuid) }}"
                    data-toggle-label="{{ $branch->address }}"
                    data-toggle-action="{{ $branch->status === 'active' ? 'إيقاف' : 'تفعيل' }}"
                />
              @endif

              {{-- الرحلات --}}
              <x-action-button
                  variant="custom"
                  icon="bus"
                  title="الرحلات"
                  href="{{ route('admin.provider.branches.trips.index', $branch->uuid) }}"
              />

              {{-- الحجوزات --}}
              <x-action-button
                  variant="custom"
                  icon="ticket"
                  title="الحجوزات"
                  href="{{ route('admin.provider.branches.bookings.index', $branch->uuid) }}"
              />

              {{-- حذف --}}
              @if($canDelete)
                <x-action-button
                    variant="delete"
                    data-delete-url="{{ route('admin.provider.branches.destroy', $branch->uuid) }}"
                    data-delete-label="{{ $branch->address }}"
                />
              @endif
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state
            icon="store"
            :title="request('search') ? 'لا توجد نتائج مطابقة' : 'لا توجد فروع'"
            :message="request('search') ? 'جرّب تعديل كلمات البحث' : 'لم تقم بإضافة أي فرع بعد. أضف فرعاً جديداً لبدء إدارة فروعك'"
            :colspan="8"
        >
          @if(!request('search') && $canCreate)
            <button
                type="button"
                data-create-modal="branch-form-modal"
                class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
            >
              <i data-lucide="plus" class="w-4 h-4"></i>
              إضافة فرع جديد
            </button>
          @endif
        </x-empty-state>
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$branches" />
    </x-slot>
  </x-data-table-card>

  </div>{{-- نهاية #aracari-refreshable -->}

</div>

{{-- ===== نافذة نموذج الفرع (إنشاء + تعديل) ===== --}}
<x-form-modal
    id="branch-form-modal"
    title="إضافة فرع جديد"
    :variant="'create'"
    :action="route('admin.provider.branches.store')"
    method="POST"
    successMessage="تم إنشاء الفرع بنجاح"
>

  {{-- حقل مخفي لمعرف الدولة (يُملأ تلقائياً من المدينة) --}}
  <input type="hidden" name="country_id" id="branch_country_id" value="">

  {{-- ===== المدينة + الهاتف ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    {{-- المدينة --}}
    <x-form-field name="city_id" label="المدينة" icon="building" required>
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="building" class="w-4 h-4"></i>
        </div>
        <select
            name="city_id"
            id="branch_city_id"
            required
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none cursor-pointer"
        >
          <option value="">اختر المدينة</option>
          @foreach($cities as $city)
            <option value="{{ $city->id }}">{{ $city->name }}</option>
          @endforeach
        </select>
        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
          <i data-lucide="chevron-down" class="w-4 h-4"></i>
        </div>
      </div>
    </x-form-field>

    {{-- رقم الهاتف --}}
    <x-form-field name="phone_number" label="رقم الهاتف" icon="phone" required>
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="phone" class="w-4 h-4"></i>
        </div>
        <input
            type="tel"
            name="phone_number"
            id="branch_phone_number"
            required
            placeholder="رقم الهاتف"
            dir="ltr"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left"
        >
      </div>
    </x-form-field>
  </div>

  {{-- ===== العنوان ===== --}}
  <x-form-field name="address" label="العنوان" icon="home" required>
    <div class="relative group">
      <div class="absolute top-3 right-3 pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="home" class="w-4 h-4"></i>
      </div>
      <textarea
          name="address"
          id="branch_address"
          required
          rows="2"
          placeholder="أدخل العنوان التفصيلي"
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right resize-none leading-relaxed"
      ></textarea>
    </div>
  </x-form-field>

  {{-- ===== خط العرض + خط الطول ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    {{-- خط العرض --}}
    <x-form-field name="latitude" label="خط العرض" icon="locate-fixed">
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="locate-fixed" class="w-4 h-4"></i>
        </div>
        <input
            type="number"
            name="latitude"
            id="branch_latitude"
            step="any"
            placeholder="24.7136"
            dir="ltr"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left"
        >
      </div>
    </x-form-field>

    {{-- خط الطول --}}
    <x-form-field name="longitude" label="خط الطول" icon="locate-fixed">
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="locate-fixed" class="w-4 h-4"></i>
        </div>
        <input
            type="number"
            name="longitude"
            id="branch_longitude"
            step="any"
            placeholder="46.6753"
            dir="ltr"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left"
        >
      </div>
    </x-form-field>
  </div>

  {{-- ===== الحالة --}}
  <div class="space-y-1.5">
    <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
      <i data-lucide="toggle-right" class="w-3.5 h-3.5"></i>
      حالة الفرع
    </label>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="toggle-right" class="w-4 h-4"></i>
      </div>
      <select
          name="status"
          id="branch_status"
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none cursor-pointer"
      >
        <option value="active">نشط</option>
        <option value="inactive">غير نشط</option>
      </select>
      <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
        <i data-lucide="chevron-down" class="w-4 h-4"></i>
      </div>
    </div>
  </div>

  {{-- ===== تنبيه إنشاء الفرع ===== --}}
  <div class="bg-teal-50/70 border border-teal-200 rounded-xl p-3">
    <div class="flex items-start gap-2.5">
      <i data-lucide="info" class="w-4 h-4 text-teal-500 flex-shrink-0 mt-0.5"></i>
      <span class="text-[11px] text-teal-600">
        عند إنشاء الفرع، سيتم إنشاء خدمة (Service) تلقائياً مرتبطة بهذا الفرع لاستخدامها في إنشاء الرحلات.
      </span>
    </div>
  </div>

</x-form-modal>

{{-- ===== نافذة تفاصيل الفرع ===== --}}
<x-modal
    id="branch-details-modal"
    title="تفاصيل الفرع"
    subtitle=""
    :variant="'view'"
    :maxWidth="'max-w-2xl'"
>
  {{-- يتم ملؤها ديناميكياً عبر aracari:modal-open --}}
  <div id="branch-details-content">
    {{-- سيتم تعبئة المحتوى هنا عبر JS --}}
  </div>
</x-modal>

@endsection

@push('scripts')
<script>
  // ===== خريطة المدينة ← الدولة =====
  var cityCountryMap = {
    @foreach($cities as $city)
      '{{ $city->id }}': {{ $city->country_id }},
    @endforeach
  };

  // ===== تحديث country_id تلقائياً عند تغيير المدينة =====
  var citySelect = document.getElementById('branch_city_id');
  var countryInput = document.getElementById('branch_country_id');
  if (citySelect && countryInput) {
    citySelect.addEventListener('change', function() {
      countryInput.value = cityCountryMap[citySelect.value] || '';
    });
  }

  // ===== تبديل حالة الفرع (POST مع تأكيد) =====
  document.addEventListener('click', function(e) {
    var toggleBtn = e.target.closest('[data-toggle-url]');
    if (!toggleBtn) return;
    e.preventDefault();

    var url = toggleBtn.getAttribute('data-toggle-url');
    var label = toggleBtn.getAttribute('data-toggle-label') || 'هذا الفرع';
    var actionLabel = toggleBtn.getAttribute('data-toggle-action') || 'تبديل';

    if (!confirm('هل أنت متأكد من ' + actionLabel + ' الفرع "' + label + '"؟')) return;

    // حالة التحميل
    var originalHtml = toggleBtn.innerHTML;
    toggleBtn.disabled = true;
    toggleBtn.innerHTML = '<svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>';

    var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    fetch(url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
    })
    .then(function(res) {
      if (res.status === 419) {
        if (window.Aracari) window.Aracari.toast('انتهت صلاحية الجلسة. سيتم إعادة تحميل الصفحة...', 'warning');
        setTimeout(function() { window.location.reload(); }, 1500);
        return null;
      }
      return res.json().then(function(j) { return { json: j, status: res.status }; });
    })
    .then(function(result) {
      if (!result) return;
      if (result.status === 302 || (result.json && result.json.success !== false)) {
        if (window.Aracari) window.Aracari.toast('تم ' + actionLabel + ' الفرع بنجاح', 'success');
        if (document.getElementById('aracari-refreshable')) {
          if (window.Aracari) window.Aracari.refreshTableCard();
          else window.location.reload();
        } else {
          window.location.reload();
        }
      } else {
        if (window.Aracari) window.Aracari.toast(result.json?.message || 'تعذر تغيير الحالة', 'error');
        toggleBtn.disabled = false;
        toggleBtn.innerHTML = originalHtml;
      }
    })
    .catch(function(err) {
      console.error('[branches] toggle error', err);
      if (window.Aracari) window.Aracari.toast('حدث خطأ. يرجى المحاولة مرة أخرى.', 'error');
      toggleBtn.disabled = false;
      toggleBtn.innerHTML = originalHtml;
    });
  });

  // ===== تفاصيل الفرع: تعبئة المحتوى عند فتح النافذة =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;

    // ===== 1. تعبئة نافذة التفاصيل =====
    if (modal.id === 'branch-details-modal') {
      var d = e.detail.data || {};
      var content = document.getElementById('branch-details-content');
      if (!content) return;

      var statusBadge = d.status === 'active'
        ? '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border bg-emerald-50 text-emerald-700 border-emerald-200"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>نشط</span>'
        : '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border bg-rose-50 text-rose-700 border-rose-200"><span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>غير نشط</span>';

      var coordsHtml = (d.latitude && d.longitude)
        ? '<div class="flex items-start gap-3 p-3 bg-white rounded-xl border border-[#eeeef0] hover:border-[#115128]/20 transition-colors">' +
            '<div class="w-9 h-9 rounded-lg bg-gradient-to-br from-purple-500/20 to-purple-600/10 flex items-center justify-center text-purple-600 border border-white/20 flex-shrink-0"><i data-lucide="locate-fixed" class="w-4 h-4"></i></div>' +
            '<div class="min-w-0 flex-1"><p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">الإحداثيات</p>' +
            '<span class="font-mono font-bold text-slate-800 text-sm" dir="ltr">' + d.latitude + ', ' + d.longitude + '</span></div></div>'
        : '';

      content.innerHTML =
        '<div class="space-y-4">' +
          // بطاقة بيانات الفرع
          '<div class="space-y-3 bg-[#f9f9fc] p-5 rounded-2xl border border-[#eeeef0]">' +
            '<h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-gray-100 pb-2">' +
              '<i data-lucide="store" class="w-4 h-4 text-emerald-600"></i><span>بيانات الفرع</span></h4>' +
            // العنوان
            '<div class="flex items-start gap-3 p-3 bg-white rounded-xl border border-[#eeeef0]">' +
              '<div class="w-9 h-9 rounded-lg bg-gradient-to-br from-teal-500/20 to-teal-600/10 flex items-center justify-center text-teal-600 border border-white/20 flex-shrink-0"><i data-lucide="home" class="w-4 h-4"></i></div>' +
              '<div class="min-w-0 flex-1"><p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">العنوان</p>' +
              '<span class="font-bold text-slate-800 break-words text-sm">' + (d.address || '—') + '</span></div></div>' +
            // المدينة
            '<div class="flex items-start gap-3 p-3 bg-white rounded-xl border border-[#eeeef0]">' +
              '<div class="w-9 h-9 rounded-lg bg-gradient-to-br from-amber-500/20 to-amber-600/10 flex items-center justify-center text-amber-600 border border-white/20 flex-shrink-0"><i data-lucide="map-pin" class="w-4 h-4"></i></div>' +
              '<div class="min-w-0 flex-1"><p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">المدينة</p>' +
              '<span class="font-bold text-slate-800 text-sm">' + (d.city_name || '—') + '</span></div></div>' +
            // الهاتف
            '<div class="flex items-start gap-3 p-3 bg-white rounded-xl border border-[#eeeef0]">' +
              '<div class="w-9 h-9 rounded-lg bg-gradient-to-br from-emerald-500/20 to-emerald-600/10 flex items-center justify-center text-emerald-600 border border-white/20 flex-shrink-0"><i data-lucide="phone" class="w-4 h-4"></i></div>' +
              '<div class="min-w-0 flex-1"><p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">رقم الهاتف</p>' +
              '<span class="font-mono font-bold text-slate-800 text-sm" dir="ltr">' + (d.phone_number || '—') + '</span></div></div>' +
            // الإحداثيات
            coordsHtml +
            // الحالة + التواريخ
            '<div class="flex items-start gap-3 p-3 bg-white rounded-xl border border-[#eeeef0]">' +
              '<div class="w-9 h-9 rounded-lg bg-gradient-to-br from-slate-500/20 to-slate-600/10 flex items-center justify-center text-slate-500 border border-slate-200 flex-shrink-0"><i data-lucide="info" class="w-4 h-4"></i></div>' +
              '<div class="flex-1"><p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">معلومات إضافية</p>' +
              '<div class="flex flex-wrap items-center gap-3 mt-2">' +
                statusBadge +
                '<span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-1 rounded-lg bg-slate-100 text-slate-600"><i data-lucide="calendar" class="w-3 h-3"></i>' + (d.created_at || '—') + '</span>' +
                '<span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-1 rounded-lg bg-slate-100 text-slate-600"><i data-lucide="refresh-cw" class="w-3 h-3"></i>' + (d.updated_at || '—') + '</span>' +
              '</div></div></div>' +
          '</div>' +
          // إحصائيات سريعة
          '<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">' +
            '<div class="bg-blue-50/70 border border-blue-200 rounded-xl p-3 flex items-center gap-3">' +
              '<div class="w-9 h-9 rounded-lg bg-blue-500/20 flex items-center justify-center text-blue-600"><i data-lucide="bus" class="w-5 h-5"></i></div>' +
              '<div><p class="text-[10px] font-bold text-slate-400 uppercase">الرحلات</p>' +
              '<p class="text-lg font-black text-slate-800">' + (d.trips_count ?? 0) + '</p></div></div>' +
            '<div class="bg-cyan-50/70 border border-cyan-200 rounded-xl p-3 flex items-center gap-3">' +
              '<div class="w-9 h-9 rounded-lg bg-cyan-500/20 flex items-center justify-center text-cyan-600"><i data-lucide="users" class="w-5 h-5"></i></div>' +
              '<div><p class="text-[10px] font-bold text-slate-400 uppercase">الموظفون</p>' +
              '<p class="text-lg font-black text-slate-800">' + (d.branch_users_count ?? 0) + '</p></div></div>' +
          '</div>' +
        '</div>';

      if (window.lucide) window.lucide.createIcons();
      return;
    }

    // ===== 2. تبديل نافذة النموذج: إنشاء / تعديل =====
    if (modal.id !== 'branch-form-modal') return;

    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.uuid);

    if (isEdit) {
      var updateUrl = "{{ route('admin.provider.branches.update', '__UUID__') }}".replace('__UUID__', data.uuid);
      form.setAttribute('action', updateUrl);

      var existingMethod = form.querySelector('input[name="_method"]');
      if (existingMethod) existingMethod.value = 'PUT';
      else {
        var m = document.createElement('input');
        m.type = 'hidden'; m.name = '_method'; m.value = 'PUT';
        m.setAttribute('data-dynamic', 'true');
        form.appendChild(m);
      }

      // شريط تعديل (عنبري)
      var banner = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (banner) banner.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';

      var titleEl = modal.querySelector('.aracari-modal-panel h2');
      if (titleEl) titleEl.innerHTML = '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل بيانات الفرع';

      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }

      var subtitle = modal.querySelector('.aracari-modal-panel p');
      if (subtitle) subtitle.textContent = 'تحديث بيانات الفرع والخدمة المرتبطة';

      form.setAttribute('data-success-message', 'تم تحديث الفرع بنجاح');
    } else {
      form.setAttribute('action', "{{ route('admin.provider.branches.store') }}");
      var m = form.querySelector('input[name="_method"]');
      if (m) m.remove();

      var banner = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (banner) banner.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';

      var titleEl = modal.querySelector('.aracari-modal-panel h2');
      if (titleEl) titleEl.innerHTML = '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة فرع جديد';

      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30';
        badge.textContent = 'جديد';
      }

      var subtitle = modal.querySelector('.aracari-modal-panel p');
      if (subtitle) subtitle.textContent = 'إنشاء فرع جديد للمزود - سيتم إنشاء خدمة تلقائياً للفرع';

      form.setAttribute('data-success-message', 'تم إنشاء الفرع بنجاح');
    }

    // ملء حقول النموذج
    var citySelect    = modal.querySelector('#branch_city_id');
    var phoneInput    = modal.querySelector('#branch_phone_number');
    var addressInput  = modal.querySelector('#branch_address');
    var latInput      = modal.querySelector('#branch_latitude');
    var lngInput      = modal.querySelector('#branch_longitude');
    var statusSelect  = modal.querySelector('#branch_status');

    if (citySelect)    citySelect.value   = isEdit ? (data.city_id || '') : '';
    if (phoneInput)    phoneInput.value   = isEdit ? (data.phone_number || '') : '';
    if (addressInput)  addressInput.value = isEdit ? (data.address || '') : '';
    if (latInput)      latInput.value     = isEdit ? (data.latitude || '') : '';
    if (lngInput)      lngInput.value     = isEdit ? (data.longitude || '') : '';
    if (statusSelect)  statusSelect.value = isEdit ? (data.status || 'active') : 'active';

    // تحديث country_id من المدينة المختارة
    if (citySelect && countryInput) {
      countryInput.value = cityCountryMap[citySelect.value] || '';
    }

    // إعادة تصيير أيقونات لوسيد
    if (window.lucide) window.lucide.createIcons();
  });
</script>
@endpush
