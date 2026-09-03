@extends('layouts.admin')

@section('title', 'أركاري | إدارة أنواع المركبات')
@section('header_title', 'إدارة أنواع المركبات')

@section('content')

@php
    $totalVehicleTypes = $vehicleTypes->total();

    $user      = auth()->user();
    $canView   = $user && $user->hasPermission('vehicle_types:view');
    $canCreate = $user && $user->hasPermission('vehicle_types:create');
    $canEdit   = $user && $user->hasPermission('vehicle_types:edit');
    $canDelete = $user && $user->hasPermission('vehicle_types:delete');
    $vehicleTypes->getCollection()->loadCount('vehicles');
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="car"
      title="أنواع المركبات"
      :count="$totalVehicleTypes"
      countLabel="نوع"
      description="إدارة أنواع المركبات المتاحة في الأسطول"
      descriptionIcon="car"
  >
    @if($canCreate)
      <button
          type="button"
          data-create-modal="vehicle-type-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إضافة نوع مركبة جديد
      </button>
    @endif
  </x-page-header>

  {{-- ===== Refreshable region: stats + table ===== --}}
  <div id="aracari-refreshable" class="space-y-6">

  {{-- ===== Stat Cards ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
    <x-stat-card :value="$totalVehicleTypes" label="إجمالي الأنواع" icon="car" variant="slate" badge="نوع" badgeIcon="car" />
    <x-stat-card :value="$vehicleTypes->getCollection()->filter(fn($v) => ($v->vehicles_count ?? 0) > 0)->count()" label="أنواع مستخدمة" icon="check-circle" variant="emerald" badge="متاح" badgeIcon="check-circle" />
    <x-stat-card :value="$vehicleTypes->getCollection()->sum(fn($v) => $v->vehicles_count ?? 0)" label="إجمالي المركبات" icon="truck" variant="blue" badge="مركبة" badgeIcon="truck" />
  </div>

  {{-- ===== Data Table Card ===== --}}
  <x-data-table-card :searchable="true" :searchMode="'server'" searchPlaceholder="ابحث عن نوع مركبة..." :footerInfo="'عرض ' . $vehicleTypes->count() . ' من أصل ' . $vehicleTypes->total() . ' سجل'">
    <x-slot name="thead">
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">نوع المركبة</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">المركبات</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">تاريخ الإضافة</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
    </x-slot>

    <x-slot name="rows">
      @forelse($vehicleTypes as $vehicleType)
        <tr class="hover:bg-slate-50/50 transition-colors group">
          {{-- # --}}
          <td class="px-6 py-4">
            <span class="font-mono font-black text-slate-400 text-sm">{{ str_pad((string) $vehicleType->id, 4, '0', STR_PAD_LEFT) }}</span>
          </td>

          {{-- نوع المركبة --}}
          <td data-searchable class="px-6 py-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500/20 to-teal-600/10 flex items-center justify-center border border-teal-100 group-hover:scale-110 transition-transform duration-300">
                <i data-lucide="car" class="w-5 h-5 text-teal-600"></i>
              </div>
              <div>
                <div class="font-bold text-slate-800">{{ $vehicleType->type_name }}</div>
                <div class="text-[10px] font-bold text-slate-400 flex items-center gap-1">
                  <i data-lucide="fingerprint" class="w-3 h-3"></i>
                  {{ \Illuminate\Support\Str::substr((string) ($vehicleType->uuid ?? ''), 0, 8) }}
                </div>
              </div>
            </div>
          </td>

          {{-- المركبات --}}
          <td class="px-6 py-4 text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 text-[10px] font-bold border border-blue-200">
              <i data-lucide="truck" class="w-3 h-3"></i>
              {{ $vehicleType->vehicles_count ?? 0 }} مركبة
            </span>
          </td>

          {{-- تاريخ الإضافة --}}
          <td class="px-6 py-4 text-center">
            <div class="flex flex-col items-center gap-1">
              <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-700">
                <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                {{ optional($vehicleType->created_at)->translatedFormat('j F، Y') ?? '—' }}
              </span>
              <span class="inline-flex items-center gap-1 text-[10px] text-slate-400">
                <i data-lucide="clock" class="w-3 h-3"></i>
                {{ optional($vehicleType->created_at)->diffForHumans() ?? '—' }}
              </span>
            </div>
          </td>

          {{-- الإجراءات --}}
          <td class="px-6 py-4 text-center">
            <div class="flex items-center justify-center gap-1.5">
              @if($canEdit)
                <x-action-button
                    variant="edit"
                    data-edit-modal="vehicle-type-form-modal"
                    data-edit-json="{{ json_encode([
                        'uuid' => $vehicleType->uuid,
                        'type_name' => $vehicleType->type_name,
                        'id'   => $vehicleType->id,
                    ], JSON_UNESCAPED_UNICODE) }}"
                    :ariaLabel="'تعديل ' . $vehicleType->type_name"
                />
              @endif
              @if($canDelete)
                <x-action-button
                    variant="delete"
                    data-delete-url="{{ route('admin.vehicle_types.destroy', $vehicleType) }}"
                    data-delete-label="{{ $vehicleType->type_name }}"
                />
              @endif
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state
            icon="car"
            :title="request('search') ? 'لا توجد نتائج مطابقة' : 'لا توجد أنواع مركبات'"
            :message="request('search') ? 'جرّب تعديل كلمات البحث' : 'أضف أنواع المركبات المتاحة في أسطولك'"
            :colspan="5"
        >
          @if(!request('search') && $canCreate)
            <button
                type="button"
                data-create-modal="vehicle-type-form-modal"
                class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
            >
              <i data-lucide="plus" class="w-4 h-4"></i>
              إضافة نوع مركبة جديد
            </button>
          @endif
        </x-empty-state>
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$vehicleTypes" />
    </x-slot>
  </x-data-table-card>

  </div>{{-- end #aracari-refreshable -->}

</div>

{{-- ===== Vehicle Type Form Modal (Create + Edit) ===== --}}
<x-form-modal
    id="vehicle-type-form-modal"
    title="إضافة نوع مركبة جديد"
    :variant="'create'"
    :action="route('admin.vehicle_types.store')"
    method="POST"
    successMessage="تم حفظ نوع المركبة بنجاح"
>
  {{-- اسم النوع --}}
  <x-form-field name="type_name" label="اسم النوع" icon="car" required>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="car" class="w-4 h-4"></i>
      </div>
      <input
          type="text"
          name="type_name"
          id="type_name"
          required
          placeholder="مثال: حافلة صغيرة، حافلة كبيرة، ميكروباص..."
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
      >
    </div>
  </x-form-field>

  {{-- تنبيه إنشاء النوع --}}
  <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-3">
    <div class="flex items-start gap-2.5">
      <i data-lucide="info" class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5"></i>
      <span class="text-[11px] text-amber-600">
        سيتم إضافة نوع المركبة وستكون متاحة للاختيار عند إضافة مركبات جديدة.
      </span>
    </div>
  </div>

  {{-- معاينة حية --}}
  <div class="space-y-2">
    <h4 class="font-bold text-xs text-[#115128] border-b border-[#eeeef0] pb-2 flex items-center gap-1.5">
      <i data-lucide="eye" class="w-3.5 h-3.5"></i>
      معاينة نوع المركبة
    </h4>
    <div class="bg-[#f9f9fc] border border-[#eeeef0] rounded-xl p-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center border bg-teal-50 border-teal-100 text-teal-500">
          <i data-lucide="car" class="w-5 h-5"></i>
        </div>
        <div>
          <h4 class="text-sm font-bold text-slate-800" id="type_name-preview" data-preview-default="نوع مركبة جديد">نوع مركبة جديد</h4>
          <span class="inline-flex items-center gap-1 text-[10px] text-slate-400 mt-0.5">
            <i data-lucide="check-circle" class="w-3 h-3"></i>
            نوع مركبة
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
  // ===== Vehicle Type modal: dynamic action + title + variant switching =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'vehicle-type-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.uuid);

    if (isEdit) {
      var updateUrl = "{{ route('admin.vehicle_types.update', '__UUID__') }}".replace('__UUID__', data.uuid);
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
        '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل نوع المركبة';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }
      form.setAttribute('data-success-message', 'تم تحديث نوع المركبة بنجاح');
    } else {
      form.setAttribute('action', "{{ route('admin.vehicle_types.store') }}");
      var m = form.querySelector('input[name="_method"]');
      if (m) m.remove();
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة نوع مركبة جديد';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30';
        badge.textContent = 'جديد';
      }
      form.setAttribute('data-success-message', 'تم حفظ نوع المركبة بنجاح');
    }
    if (window.lucide) window.lucide.createIcons();

    var nameInput = modal.querySelector('#type_name');
    var preview = modal.querySelector('#type_name-preview');
    if (nameInput && preview) {
      preview.textContent = nameInput.value.trim() || 'نوع مركبة جديد';
    }
  });

  // ===== Live preview --}}
  (function() {
    var nameInput = document.getElementById('type_name');
    var preview = document.getElementById('type_name-preview');
    if (!nameInput || !preview) return;
    nameInput.addEventListener('input', function() {
      preview.textContent = nameInput.value.trim() || 'نوع مركبة جديد';
    });
  })();
</script>
@endpush
