@extends('layouts.admin')

@section('title', 'أركاري | إدارة الخدمات والمرافق')
@section('header_title', 'إدارة الخدمات والمرافق')

@section('content')

@php
    $totalAmenities = $amenities->total();
    $amenities->getCollection()->loadCount('tripAmenities'); // prevent N+1 queries

    $user      = auth()->user();
    $canView   = $user && $user->hasPermission('amenities:view');
    $canCreate = $user && $user->hasPermission('amenities:create');
    $canEdit   = $user && $user->hasPermission('amenities:edit');
    $canDelete = $user && $user->hasPermission('amenities:delete');
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="concierge-bell"
      title="الخدمات والمرافق الإضافية"
      :count="$totalAmenities"
      countLabel="خدمة"
      description="إدارة مرافق الرحلات كالإنترنت، الوجبات، والمقاعد المريحة"
      descriptionIcon="concierge-bell"
  >
    @if($canCreate)
      <button
          type="button"
          data-create-modal="amenity-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إضافة خدمة جديدة
      </button>
    @endif
  </x-page-header>

  {{-- ===== Refreshable region: stats + table ===== --}}
  <div id="aracari-refreshable" class="space-y-6">

  {{-- ===== Stat Cards ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
    <x-stat-card :value="$totalAmenities" label="إجمالي الخدمات" icon="concierge-bell" variant="slate" badge="خدمة" badgeIcon="concierge-bell" />
    <x-stat-card :value="$amenities->getCollection()->filter(fn($a) => ($a->trip_amenities_count ?? 0) > 0)->count()" label="مستخدمة في الرحلات" icon="bus" variant="blue" badge="رحلة" badgeIcon="bus" />
    <x-stat-card :value="$amenities->getCollection()->sum(fn($a) => $a->trip_amenities_count ?? 0)" label="إجمالي الارتباطات" icon="link" variant="emerald" badge="ارتباط" badgeIcon="link" />
  </div>

  {{-- ===== Data Table Card ===== --}}
  <x-data-table-card :searchable="true" :searchMode="'server'" searchPlaceholder="ابحث عن خدمة..." :footerInfo="'عرض ' . $amenities->count() . ' من أصل ' . $amenities->total() . ' سجل'">
    <x-slot name="thead">
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الخدمة</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الاستخدام</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">تاريخ الإضافة</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
    </x-slot>

    <x-slot name="rows">
      @forelse($amenities as $amenity)
        <tr class="hover:bg-slate-50/50 transition-colors group">
          {{-- # --}}
          <td class="px-6 py-4">
            <span class="font-mono font-black text-slate-400 text-sm">{{ str_pad((string) $amenity->id, 4, '0', STR_PAD_LEFT) }}</span>
          </td>

          {{-- الخدمة --}}
          <td data-searchable class="px-6 py-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500/20 to-teal-600/10 flex items-center justify-center border border-teal-100 text-xl group-hover:scale-110 transition-transform duration-300">
                <span>⭐</span>
              </div>
              <div>
                <div class="font-bold text-slate-800">{{ $amenity->name }}</div>
                <div class="text-[10px] font-bold text-slate-400 flex items-center gap-1">
                  <i data-lucide="tag" class="w-3 h-3"></i>
                  {{ \Illuminate\Support\Str::substr((string) ($amenity->uuid ?? ''), 0, 8) }}
                </div>
              </div>
            </div>
          </td>

          {{-- الاستخدام --}}
          <td class="px-6 py-4 text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 text-[10px] font-bold border border-blue-200">
              <i data-lucide="bus" class="w-3 h-3"></i>
              {{ $amenity->trip_amenities_count }} رحلة
            </span>
          </td>

          {{-- تاريخ الإضافة --}}
          <td class="px-6 py-4 text-center">
            <div class="flex flex-col items-center gap-1">
              <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-700">
                <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                {{ optional($amenity->created_at)->translatedFormat('j F، Y') ?? '—' }}
              </span>
              <span class="inline-flex items-center gap-1 text-[10px] text-slate-400">
                <i data-lucide="clock" class="w-3 h-3"></i>
                {{ optional($amenity->created_at)->diffForHumans() ?? '—' }}
              </span>
            </div>
          </td>

          {{-- الإجراءات --}}
          <td class="px-6 py-4 text-center">
            <div class="flex items-center justify-center gap-1.5">
              @if($canEdit)
                <x-action-button
                    variant="edit"
                    data-edit-modal="amenity-form-modal"
                    data-edit-json="{{ json_encode([
                        'uuid' => $amenity->uuid,
                        'name' => $amenity->name,
                        'id'   => $amenity->id,
                    ], JSON_UNESCAPED_UNICODE) }}"
                    :ariaLabel="'تعديل ' . $amenity->name"
                />
              @endif
              @if($canDelete)
                <x-action-button
                    variant="delete"
                    data-delete-url="{{ route('admin.amenities.destroy', $amenity) }}"
                    data-delete-label="{{ $amenity->name }}"
                />
              @endif
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state
            icon="concierge-bell"
            :title="request('search') ? 'لا توجد نتائج مطابقة' : 'لا توجد خدمات إضافية'"
            :message="request('search') ? 'جرّب تعديل كلمات البحث' : 'أضف الخدمات المميزة التي تقدمها لعملائك في الرحلات'"
            :colspan="5"
        >
          @if(!request('search') && $canCreate)
            <button
                type="button"
                data-create-modal="amenity-form-modal"
                class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
            >
              <i data-lucide="plus" class="w-4 h-4"></i>
              إضافة خدمة جديدة
            </button>
          @endif
        </x-empty-state>
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$amenities" />
    </x-slot>
  </x-data-table-card>

  </div>{{-- end #aracari-refreshable -->}

</div>

{{-- ===== Amenity Form Modal (Create + Edit) ===== --}}
<x-form-modal
    id="amenity-form-modal"
    title="إضافة خدمة إضافية"
    :variant="'create'"
    :action="route('admin.amenities.store')"
    method="POST"
    successMessage="تم حفظ الخدمة بنجاح"
>
  {{-- اسم الخدمة --}}
  <x-form-field name="name" label="اسم الخدمة" icon="star" required>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="star" class="w-4 h-4"></i>
      </div>
      <input
          type="text"
          name="name"
          id="name"
          required
          placeholder="مثال: واي فاي مجاني، وجبة خفيفة، تكييف..."
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
      >
    </div>
  </x-form-field>

  {{-- تنبيه إنشاء الخدمة --}}
  <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-3">
    <div class="flex items-start gap-2.5">
      <i data-lucide="info" class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5"></i>
      <span class="text-[11px] text-amber-600">
        سيتم إضافة الخدمة وستكون متاحة للاختيار عند إنشاء الرحلات وتعديلها.
      </span>
    </div>
  </div>

  {{-- معاينة حية --}}
  <div class="space-y-2">
    <h4 class="font-bold text-xs text-[#115128] border-b border-[#eeeef0] pb-2 flex items-center gap-1.5">
      <i data-lucide="eye" class="w-3.5 h-3.5"></i>
      معاينة الخدمة
    </h4>
    <div class="bg-[#f9f9fc] border border-[#eeeef0] rounded-xl p-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center border bg-teal-50 border-teal-100 text-2xl">
          <span>⭐</span>
        </div>
        <div>
          <h4 class="text-sm font-bold text-slate-800" id="name-preview" data-preview-default="خدمة جديدة">خدمة جديدة</h4>
          <span class="inline-flex items-center gap-1 text-[10px] text-slate-400 mt-0.5">
            <i data-lucide="check-circle" class="w-3 h-3"></i>
            متاحة للرحلات
          </span>
        </div>
      </div>
      <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-200">
        <i data-lucide="plus-circle" class="w-2.5 h-2.5"></i>
        جديد
      </span>
    </div>
  </div>
</x-form-modal>

@endsection
@push('scripts')
<script>
  // ===== Amenity modal: dynamic action + title + variant switching =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'amenity-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.uuid);

    if (isEdit) {
      var updateUrl = "{{ route('admin.amenities.update', '__UUID__') }}".replace('__UUID__', data.uuid);
      form.setAttribute('action', updateUrl);
      var existingMethod = form.querySelector('input[name="_method"]');
      if (existingMethod) existingMethod.value = 'PUT';
      else {
        var m = document.createElement('input');
        m.type = 'hidden'; m.name = '_method'; m.value = 'PUT';
        form.appendChild(m);
      }
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل الخدمة الإضافية';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }
      form.setAttribute('data-success-message', 'تم تحديث الخدمة بنجاح');
    } else {
      form.setAttribute('action', "{{ route('admin.amenities.store') }}");
      var m = form.querySelector('input[name="_method"]');
      if (m) m.remove();
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة خدمة إضافية';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30';
        badge.textContent = 'جديد';
      }
      form.setAttribute('data-success-message', 'تم حفظ الخدمة بنجاح');
    }
    if (window.lucide) window.lucide.createIcons();

    var nameInput = modal.querySelector('#name');
    var preview = modal.querySelector('#name-preview');
    if (nameInput && preview) {
      preview.textContent = nameInput.value.trim() || 'خدمة جديدة';
    }
  });

  // ===== Live preview: name → preview element =====
  (function() {
    var nameInput = document.getElementById('name');
    var preview = document.getElementById('name-preview');
    if (!nameInput || !preview) return;
    nameInput.addEventListener('input', function() {
      preview.textContent = nameInput.value.trim() || 'خدمة جديدة';
    });
  })();
</script>
@endpush
