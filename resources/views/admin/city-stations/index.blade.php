@extends('layouts.admin')

@section('title', 'أركاري | محطات المدن')
@section('header_title', 'محطات المدن')

@section('content')

@php
    $totalStations  = $stations->total();
    $activeStations = $stations->getCollection()->filter(fn($s) => $s->isActive())->count();
    $uniqueCities   = $stations->getCollection()->pluck('city_id')->unique()->count();
    $avgPerCity     = $uniqueCities > 0 ? round($totalStations / $uniqueCities, 1) : '0.0';

    // Fetch cities for the modal select (controller index() doesn't pass them)
    $cities = \App\Models\City::orderBy('name')->pluck('name', 'id');

    $user      = auth()->user();
    $canView   = $user && $user->hasPermission('city_stations:view');
    $canCreate = $user && $user->hasPermission('city_stations:create');
    $canEdit   = $user && $user->hasPermission('city_stations:edit');
    $canDelete = $user && $user->hasPermission('city_stations:delete');
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="map-pin"
      title="محطات المدن"
      :count="$totalStations"
      countLabel="محطة"
      description="إدارة محطات الانطلاق والوصول لكل مدينة"
      descriptionIcon="info"
  >
    @if($canCreate)
      <button
          type="button"
          data-create-modal="city-station-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        <span class="hidden sm:inline">إضافة محطة</span>
        <span class="sm:hidden">إضافة</span>
      </button>
    @endif
  </x-page-header>

  {{-- ===== Refreshable region: stats + table ===== --}}
  <div id="aracari-refreshable" class="space-y-6">

  {{-- ===== Stat Cards (4) ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    {{-- إجمالي المحطات --}}
    <x-stat-card :value="$totalStations" label="إجمالي المحطات" icon="map-pin" variant="slate" badge="تعمل" badgeIcon="trending-up" />
    {{-- محطات نشطة --}}
    <x-stat-card :value="$activeStations" label="محطات نشطة" icon="check-circle" variant="emerald" badge="تعمل" badgeIcon="check-circle" />
    {{-- مدن مغطاة --}}
    <x-stat-card :value="$uniqueCities" label="مدن مغطاة" icon="building-2" variant="blue" badge="مدينة" badgeIcon="building-2" />
    {{-- متوسط لكل مدينة --}}
    <x-stat-card :value="$avgPerCity" label="متوسط لكل مدينة" icon="bar-chart-3" variant="amber" badge="محطة/مدينة" badgeIcon="bar-chart-3" />
  </div>

  {{-- ===== Data Table Card ===== --}}
  <x-data-table-card :searchable="true" :searchMode="'server'" searchPlaceholder="ابحث عن محطة..." :footerInfo="'عرض ' . $stations->count() . ' من أصل ' . $stations->total() . ' محطة'">
    <x-slot name="thead">
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المحطة</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">المدينة</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
    </x-slot>

    <x-slot name="rows">
      @forelse($stations as $station)
        <tr class="hover:bg-slate-50/50 transition-colors group">
          {{-- # --}}
          <td class="px-6 py-4">
            <span class="font-mono font-black text-slate-400 text-sm">{{ str_pad((string) ($stations->firstItem() + $loop->index), 4, '0', STR_PAD_LEFT) }}</span>
          </td>

          {{-- المحطة --}}
          <td data-searchable class="px-6 py-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500/20 to-amber-600/10 flex items-center justify-center border border-amber-100 group-hover:scale-110 transition-transform duration-300">
                <i data-lucide="map-pin" class="w-5 h-5 text-amber-600"></i>
              </div>
              <div>
                <div class="font-bold text-slate-800">{{ $station->name }}</div>
                <div class="text-[10px] font-bold text-slate-400 flex items-center gap-1">
                  <i data-lucide="tag" class="w-3 h-3"></i>
                  {{ \Illuminate\Support\Str::substr((string) ($station->uuid ?? ''), 0, 8) }}
                </div>
              </div>
            </div>
          </td>

          {{-- المدينة --}}
          <td class="px-6 py-4 text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 text-[11px] font-bold border border-blue-200">
              <i data-lucide="building-2" class="w-3.5 h-3.5"></i>
              {{ $station->city->name ?? '—' }}
            </span>
          </td>

          {{-- الحالة --}}
          <td class="px-6 py-4 text-center">
            @if($station->isActive())
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-bold border border-emerald-200">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                نشطة
              </span>
            @else
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-rose-50 text-rose-700 text-[11px] font-bold border border-rose-200">
                <i data-lucide="pause-circle" class="w-3.5 h-3.5"></i>
                متوقفة
              </span>
            @endif
          </td>

          {{-- الإجراءات --}}
          <td class="px-6 py-4 text-center">
            <div class="flex items-center justify-center gap-1.5">
              @if($canEdit)
                <x-action-button
                    variant="edit"
                    data-edit-modal="city-station-form-modal"
                    data-edit-json="{{ json_encode([
                        'uuid'    => $station->uuid,
                        'cityId'  => $station->city_id,
                        'cityName'=> $station->city->name ?? '',
                        'name'    => $station->name,
                        'status'  => $station->isActive() ? 'active' : 'inactive',
                    ], JSON_UNESCAPED_UNICODE) }}"
                    :ariaLabel="'تعديل ' . $station->name"
                />
              @endif
              @if($canDelete)
                <x-action-button
                    variant="delete"
                    data-delete-url="{{ route('admin.city_stations.destroy', $station) }}"
                    data-delete-label="{{ $station->name }}"
                />
              @endif
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state
            icon="map-pin"
            :title="request('search') ? 'لا توجد نتائج مطابقة' : 'لا توجد محطات'"
            :message="request('search') ? 'جرّب تعديل كلمات البحث' : 'أضف محطة جديدة لبدء إدارة المحطات'"
            :colspan="5"
        >
          @if(!request('search') && $canCreate)
            <button
                type="button"
                data-create-modal="city-station-form-modal"
                class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
            >
              <i data-lucide="plus" class="w-4 h-4"></i>
              إضافة محطة
            </button>
          @endif
        </x-empty-state>
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$stations" />
    </x-slot>
  </x-data-table-card>

  </div>{{-- end #aracari-refreshable -->}

</div>

{{-- ===== City Station Form Modal (Create + Edit) ===== --}}
<x-form-modal
    id="city-station-form-modal"
    title="إضافة محطة جديدة"
    :variant="'create'"
    :action="route('admin.city_stations.store')"
    method="POST"
    successMessage="تم حفظ المحطة بنجاح"
>
  {{-- ===== القسم 1: معلومات المحطة ===== --}}
  <div class="space-y-3">
    <div class="flex items-center gap-2 pb-1.5 border-b border-[#eeeef0]">
      <div class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center border border-teal-100">
        <i data-lucide="map-pin" class="w-4 h-4"></i>
      </div>
      <div class="flex-1">
        <h4 class="font-bold text-xs text-[#115128]">معلومات المحطة</h4>
        <p class="text-[10px] text-slate-400">مطلوب</p>
      </div>
      <span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">مطلوب</span>
    </div>

    {{-- حقول المدينة + الاسم + الحالة --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      {{-- المدينة --}}
      <x-form-field name="city_id" label="المدينة" icon="building-2" required>
        <div class="relative group">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
            <i data-lucide="building-2" class="w-4 h-4"></i>
          </div>
          <select
              name="city_id"
              id="city_id"
              required
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none cursor-pointer"
          >
            <option value="" disabled>اختر المدينة...</option>
            @foreach($cities ?? [] as $cityId => $cityName)
              <option value="{{ $cityId }}">{{ $cityName }}</option>
            @endforeach
          </select>
          <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
            <i data-lucide="chevron-down" class="w-4 h-4"></i>
          </div>
        </div>
      </x-form-field>

      {{-- اسم المحطة --}}
      <x-form-field name="name" label="اسم المحطة" icon="map-pin" required>
        <div class="relative group">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
            <i data-lucide="map-pin" class="w-4 h-4"></i>
          </div>
          <input
              type="text"
              name="name"
              id="station_name"
              required
              placeholder="مثال: محطة السيف"
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
          >
        </div>
      </x-form-field>

      {{-- الحالة (كامل العرض) --}}
      <div class="md:col-span-2 space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="station_status">
          <i data-lucide="toggle-right" class="w-3.5 h-3.5"></i>
          الحالة <span class="text-rose-500">*</span>
        </label>
        <div class="relative group">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
            <i data-lucide="toggle-right" class="w-4 h-4"></i>
          </div>
          <select
              name="status"
              id="station_status"
              required
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none cursor-pointer"
          >
            <option value="active">نشطة</option>
            <option value="inactive">متوقفة</option>
          </select>
          <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
            <i data-lucide="chevron-down" class="w-4 h-4"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ===== القسم 2: معاينة المحطة ===== --}}
  <div class="space-y-2 mt-2">
    <div class="flex items-center gap-2 pb-1.5 border-b border-[#eeeef0]">
      <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100">
        <i data-lucide="eye" class="w-4 h-4"></i>
      </div>
      <div class="flex-1">
        <h4 class="font-bold text-xs text-purple-700">معاينة المحطة</h4>
        <p class="text-[10px] text-slate-400">مباشر</p>
      </div>
      <span class="text-[10px] font-bold text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full border border-purple-200">مباشر</span>
    </div>
    <div class="bg-[#f9f9fc] border border-[#eeeef0] rounded-xl p-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center border bg-teal-50 border-teal-100 text-teal-600">
          <i data-lucide="map-pin" class="w-5 h-5"></i>
        </div>
        <div>
          <h4 class="text-sm font-bold text-slate-800" id="station-preview-name">محطة جديدة</h4>
          <div class="flex items-center gap-2 mt-0.5">
            <span class="inline-flex items-center gap-1 text-[10px] text-slate-400">
              <i data-lucide="building-2" class="w-3 h-3"></i>
              <span id="station-preview-city">اختر مدينة</span>
            </span>
            <span class="inline-flex items-center gap-1 text-[10px] text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-full border border-emerald-200">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
              نشطة
            </span>
          </div>
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
  // ===== City Station modal: dynamic action + title + variant switching =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'city-station-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.uuid);

    // Populate fields on edit
    if (isEdit) {
      var citySelect = modal.querySelector('#city_id');
      if (citySelect) citySelect.value = data.cityId || '';
      var nameInput = modal.querySelector('#station_name');
      if (nameInput) nameInput.value = data.name || '';
      var statusSelect = modal.querySelector('#station_status');
      if (statusSelect) statusSelect.value = data.status || 'active';

      // Update action URL
      var updateUrl = "{{ route('admin.city_stations.update', '__UUID__') }}".replace('__UUID__', data.uuid);
      form.setAttribute('action', updateUrl);
      var existingMethod = form.querySelector('input[name="_method"]');
      if (existingMethod) existingMethod.value = 'PUT';
      else {
        var m = document.createElement('input');
        m.type = 'hidden'; m.name = '_method'; m.value = 'PUT';
        form.appendChild(m);
      }

      // Update header
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل المحطة';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }
      form.setAttribute('data-success-message', 'تم تحديث المحطة بنجاح');

      // Update preview
      var previewName = modal.querySelector('#station-preview-name');
      if (previewName) previewName.textContent = data.name || 'محطة';
      var previewCity = modal.querySelector('#station-preview-city');
      if (previewCity) previewCity.textContent = data.cityName || '';

    } else {
      // Reset for create
      form.setAttribute('action', "{{ route('admin.city_stations.store') }}");
      var m = form.querySelector('input[name="_method"]');
      if (m) m.remove();

      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة محطة جديدة';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30';
        badge.textContent = 'جديد';
      }
      form.setAttribute('data-success-message', 'تم حفظ المحطة بنجاح');

      // Reset preview
      var previewName = modal.querySelector('#station-preview-name');
      if (previewName) previewName.textContent = 'محطة جديدة';
      var previewCity = modal.querySelector('#station-preview-city');
      if (previewCity) previewCity.textContent = 'اختر مدينة';
    }

    if (window.lucide) window.lucide.createIcons();
  });

  // ===== Live preview: name + city → preview elements =====
  (function() {
    var nameInput = document.getElementById('station_name');
    var citySelect = document.getElementById('city_id');
    var previewName = document.getElementById('station-preview-name');
    var previewCity = document.getElementById('station-preview-city');
    if (nameInput && previewName) {
      nameInput.addEventListener('input', function() {
        previewName.textContent = nameInput.value.trim() || 'محطة جديدة';
      });
    }
    if (citySelect && previewCity) {
      citySelect.addEventListener('change', function() {
        var selectedOption = citySelect.options[citySelect.selectedIndex];
        previewCity.textContent = selectedOption && !selectedOption.disabled ? selectedOption.text : 'اختر مدينة';
      });
    }
  })();
</script>
@endpush
