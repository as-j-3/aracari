@extends('layouts.admin')

@section('title', 'أركاري | إدارة المدن')
@section('header_title', 'إدارة المدن')

@section('content')

@php
    // Permission checks (real authorization, not just route existence)
    $user          = auth()->user();
    $canView       = $user && $user->hasPermission('cities:view');
    $canCreate     = $user && $user->hasPermission('cities:create');
    $canEdit       = $user && $user->hasPermission('cities:edit');
    $canDelete     = $user && $user->hasPermission('cities:delete');

    // Stats from paginator collection + inline count queries
    $totalCities       = $cities->total();
    $cities->getCollection()->loadCount('stations'); // prevent N+1 queries
    $activeCities      = $cities->getCollection()->filter(fn($c) => $c->stations_count > 0)->count();
    $totalStations     = \App\Models\CityStation::count(); // global count, fast
    $totalCountriesLinked = \App\Models\Country::whereHas('cities')->count();

    // Country list for the form modal select
    $countries = \App\Models\Country::orderBy('name')->get();
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="building-2"
      title="المدن"
      :count="$totalCities"
      countLabel="مدينة"
      description="إدارة المدن والمحطات المرتبطة بها"
      descriptionIcon="map-pin"
  >
    @if($canCreate)
      <button
          type="button"
          data-create-modal="city-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إضافة مدينة جديدة
      </button>
    @endif
  </x-page-header>

  {{-- ===== Refreshable region: stats + table (refreshed together after CRUD) ===== --}}
  <div id="aracari-refreshable" class="space-y-6">

  {{-- ===== Stat Cards (4) ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    <x-stat-card :value="$totalCities" label="إجمالي المدن" icon="building-2" variant="slate" badge="متاحة" badgeIcon="check-circle" />
    <x-stat-card :value="$activeCities" label="مدن نشطة" icon="check-circle" variant="emerald" badge="بمحطات" badgeIcon="map-pin" />
    <x-stat-card :value="$totalStations" label="إجمالي المحطات" icon="map-pin" variant="blue" badge="محطة" badgeIcon="map-pin" />
    <x-stat-card :value="$totalCountriesLinked" label="دول ذات مدن" icon="globe" variant="slate" badge="دولة" badgeIcon="globe" />
  </div>

  {{-- ===== Data Table Card (refreshable via aracari.js) ===== --}}
  <x-data-table-card :searchable="true" :searchMode="'server'" searchPlaceholder="ابحث عن مدينة أو دولة..." :footerInfo="'عرض ' . $cities->count() . ' من أصل ' . $cities->total() . ' مدينة'">
    <x-slot name="thead">
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المدينة</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الدولة</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">المحطات</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
    </x-slot>

    <x-slot name="rows">
      @forelse($cities as $city)
        <tr class="hover:bg-slate-50/50 transition-colors group">
          {{-- # — NOT searchable --}}
          <td class="px-6 py-4">
            <span class="font-mono font-black text-slate-400 text-sm">{{ str_pad((string) $city->id, 4, '0', STR_PAD_LEFT) }}</span>
          </td>

          {{-- المدينة — searchable cell (client mode would search this) --}}
          <td data-searchable class="px-6 py-4">
            <div class="flex items-center gap-3">
              @if($city->image)
                <div class="w-10 h-10 rounded-xl overflow-hidden flex-shrink-0 border border-slate-100 shadow-sm">
                  <img src="{{ \Storage::url($city->image) }}" alt="{{ $city->name }}" class="w-full h-full object-cover">
                </div>
              @else
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500/20 to-teal-600/10 flex items-center justify-center border border-teal-100 flex-shrink-0">
                  <i data-lucide="building-2" class="w-5 h-5 text-teal-600"></i>
                </div>
              @endif
              <div>
                <div class="font-bold text-slate-800">{{ $city->name }}</div>
                <div class="text-[10px] font-bold text-slate-400 flex items-center gap-1">
                  <i data-lucide="building-2" class="w-3 h-3"></i>
                  {{ \Illuminate\Support\Str::substr((string) ($city->uuid ?? ''), 0, 8) }}
                </div>
              </div>
            </div>
          </td>

          {{-- الدولة — searchable cell (country name text) --}}
          <td data-searchable class="px-6 py-4">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-teal-50 text-teal-700 text-[11px] font-bold border border-teal-200">
              <i data-lucide="globe" class="w-3.5 h-3.5"></i>
              {{ $city->country?->name ?? '—' }}
            </span>
          </td>

          {{-- المحطات — NOT searchable (count badge) --}}
          <td class="px-6 py-4 text-center">
            @php $stationCount = $city->stations_count; @endphp
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-bold border {{ $stationCount > 0 ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-slate-50 text-slate-500 border-slate-200' }}">
              <i data-lucide="map-pin" class="w-3 h-3"></i>
              {{ $stationCount }} محطة
            </span>
          </td>

          {{-- الإجراءات — NOT searchable (buttons only) --}}
          <td class="px-6 py-4 text-center">
            <div class="flex items-center justify-center gap-1.5">
              @if($canEdit)
                <x-action-button
                    variant="edit"
                    data-edit-modal="city-form-modal"
                    data-edit-json="{{ json_encode([
                        'uuid' => $city->uuid,
                        'name' => $city->name,
                        'country_id' => $city->country_id,
                    ], JSON_UNESCAPED_UNICODE) }}"
                    :ariaLabel="'تعديل ' . $city->name"
                />
              @endif
              @if($canDelete)
                <x-action-button
                    variant="delete"
                    data-delete-url="{{ route('admin.cities.destroy', $city) }}"
                    data-delete-label="{{ $city->name }}"
                />
              @endif
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state
            icon="building-2"
            :title="request('search') ? 'لا توجد نتائج مطابقة' : 'لا توجد مدن مسجلة'"
            :message="request('search') ? 'جرّب تعديل كلمات البحث' : 'أضف مدينة جديدة لتتمكن من ربط المحطات والمواقع بها'"
            :colspan="5"
        >
          @if(!request('search') && $canCreate)
            <button
                type="button"
                data-create-modal="city-form-modal"
                class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
            >
              <i data-lucide="plus" class="w-4 h-4"></i>
              إضافة مدينة جديدة
            </button>
          @endif
        </x-empty-state>
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$cities" />
    </x-slot>
  </x-data-table-card>

  </div>{{-- end #aracari-refreshable --}}

</div>

{{-- ===== City Form Modal (Create + Edit) ===== --}}
<x-form-modal
    id="city-form-modal"
    title="إضافة مدينة جديدة"
    :variant="'create'"
    :action="route('admin.cities.store')"
    method="POST"
    successMessage="تم حفظ المدينة بنجاح"
>
  {{-- اسم المدينة --}}
  <x-form-field name="name" label="اسم المدينة" icon="building-2" required>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="building-2" class="w-4 h-4"></i>
      </div>
      <input
          type="text"
          name="name"
          id="name"
          required
          value="{{ old('name') }}"
          placeholder="مثال: صنعاء، عدن، تعز..."
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
      >
    </div>
  </x-form-field>

  {{-- الدولة --}}
  <x-form-field name="country_id" label="الدولة" icon="globe" required>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="globe" class="w-4 h-4"></i>
      </div>
      <select
          name="country_id"
          id="country_id"
          required
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none cursor-pointer"
      >
        <option value="">اختر الدولة</option>
        @foreach($countries as $c)
          <option value="{{ $c->id }}" {{ old('country_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
        @endforeach
      </select>
      <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
        <i data-lucide="chevron-down" class="w-4 h-4"></i>
      </div>
    </div>
  </x-form-field>

  {{-- صورة المدينة (اختياري) --}}
  <x-form-field name="image" label="صورة المدينة (اختياري)" icon="image">
    <input
        type="file"
        name="image"
        id="image"
        accept="image/*"
        class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right file:ml-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-teal-50 file:text-teal-700 file:cursor-pointer"
    >
  </x-form-field>

  {{-- تنبيه إنشاء المدينة --}}
  <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-3">
    <div class="flex items-start gap-2.5">
      <i data-lucide="info" class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5"></i>
      <span class="text-[11px] text-amber-600">
        سيتم إضافة المدينة وستكون متاحة للاختيار عند إنشاء المحطات والرحلات.
      </span>
    </div>
  </div>

  {{-- معاينة حية --}}
  <div class="space-y-2">
    <h4 class="font-bold text-xs text-[#115128] border-b border-[#eeeef0] pb-2 flex items-center gap-1.5">
      <i data-lucide="eye" class="w-3.5 h-3.5"></i>
      معاينة المدينة
    </h4>
    <div class="bg-[#f9f9fc] border border-[#eeeef0] rounded-xl p-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center border bg-teal-50 border-teal-100 text-teal-500">
          <i data-lucide="building-2" class="w-5 h-5"></i>
        </div>
        <div>
          <h4 class="text-sm font-bold text-slate-800" id="name-preview" data-preview-default="مدينة جديدة">مدينة جديدة</h4>
          <span class="inline-flex items-center gap-1 text-[10px] text-slate-400 mt-0.5">
            <i data-lucide="map-pin" class="w-3 h-3"></i>
            مدينة
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
  // ===== City modal: dynamic action + title + variant switching =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'city-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.uuid);

    // Ensure multipart/form-data for image uploads
    form.setAttribute('enctype', 'multipart/form-data');

    if (isEdit) {
      // edit mode → PUT to update route
      var updateUrl = "{{ route('admin.cities.update', '__UUID__') }}".replace('__UUID__', data.uuid);
      form.setAttribute('action', updateUrl);
      // inject _method=PUT (mark as dynamic so closeModal can clean it up)
      var existingMethod = form.querySelector('input[name="_method"]');
      if (existingMethod) existingMethod.value = 'PUT';
      else {
        var m = document.createElement('input');
        m.type = 'hidden'; m.name = '_method'; m.value = 'PUT';
        m.setAttribute('data-dynamic', 'true');
        form.appendChild(m);
      }
      // update title + banner to amber (edit variant)
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل المدينة';
      // badge
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }
      // success message
      form.setAttribute('data-success-message', 'تم تحديث المدينة بنجاح');
    } else {
      // create mode → POST to store route
      form.setAttribute('action', "{{ route('admin.cities.store') }}");
      var m = form.querySelector('input[name="_method"]');
      if (m) m.remove();
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة مدينة جديدة';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30';
        badge.textContent = 'جديد';
      }
      form.setAttribute('data-success-message', 'تم حفظ المدينة بنجاح');
    }
    // re-render icons (we injected new <i data-lucide>)
    if (window.lucide) window.lucide.createIcons();

    // sync live preview after fields are filled programmatically by openModal
    var nameInput = modal.querySelector('#name');
    var preview = modal.querySelector('#name-preview');
    if (nameInput && preview) {
      preview.textContent = nameInput.value.trim() || 'مدينة جديدة';
    }
  });

  // ===== Live preview: name → preview element =====
  (function() {
    var nameInput = document.getElementById('name');
    var preview = document.getElementById('name-preview');
    if (!nameInput || !preview) return;
    nameInput.addEventListener('input', function() {
      preview.textContent = nameInput.value.trim() || 'مدينة جديدة';
    });
  })();
</script>
@endpush
