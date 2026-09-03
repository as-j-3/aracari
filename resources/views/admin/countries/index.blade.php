@extends('layouts.admin')

@section('title', 'أركاري | إدارة الدول')
@section('header_title', 'إدارة الدول')

@section('content')

@php
    // Stats computed from the paginator + collection
    $totalCountries    = $countries->total();
    $activeCountries   = $countries->getCollection()->filter(fn($c) => ($c->cities_count ?? 0) > 0)->count();
    $totalCitiesLinked = $countries->getCollection()->sum(fn($c) => $c->cities_count ?? 0);

    // Permission checks (real authorization, not just route existence)
    $user          = auth()->user();
    $canView       = $user && $user->hasPermission('countries:view');
    $canCreate     = $user && $user->hasPermission('countries:create');
    $canEdit       = $user && $user->hasPermission('countries:edit');
    $canDelete     = $user && $user->hasPermission('countries:delete');
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="globe"
      title="الدول"
      :count="$totalCountries"
      countLabel="دولة"
      description="إدارة الدول والمدن المرتبطة بها"
      descriptionIcon="globe"
  >
    @if($canCreate)
      <button
          type="button"
          data-create-modal="country-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إضافة دولة جديدة
      </button>
    @endif
  </x-page-header>

  {{-- ===== Refreshable region: stats + table (refreshed together after CRUD) ===== --}}
  <div id="aracari-refreshable" class="space-y-6">

  {{-- ===== Stat Cards ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
    <x-stat-card :value="$totalCountries" label="إجمالي الدول" icon="globe" variant="slate" />
    <x-stat-card :value="$activeCountries" label="دول نشطة" icon="check-circle" variant="emerald" badge="متاحة" badgeIcon="check-circle" />
    <x-stat-card :value="$totalCitiesLinked" label="إجمالي المدن" icon="building-2" variant="blue" badge="مدينة" badgeIcon="building-2" />
  </div>

  {{-- ===== Data Table Card (refreshable via aracari.js) ===== --}}
  <x-data-table-card :searchable="true" :searchMode="'server'" searchPlaceholder="ابحث عن دولة..." :footerInfo="'عرض ' . $countries->count() . ' من أصل ' . $countries->total() . ' سجل'">
    <x-slot name="thead">
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الدولة</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">المدن</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">تاريخ الإضافة</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
    </x-slot>

    <x-slot name="rows">
      @forelse($countries as $country)
        <tr class="hover:bg-slate-50/50 transition-colors group">
          {{-- # --}}
          <td class="px-6 py-4">
            <span class="font-mono font-black text-slate-400 text-sm">{{ str_pad((string) $country->id, 4, '0', STR_PAD_LEFT) }}</span>
          </td>

          {{-- الدولة — searchable cell (client mode would search this) --}}
          <td data-searchable class="px-6 py-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500/20 to-teal-600/10 flex items-center justify-center border border-teal-100">
                <i data-lucide="globe" class="w-5 h-5 text-teal-600"></i>
              </div>
              <div>
                <div class="font-bold text-slate-800">{{ $country->name }}</div>
                <div class="text-[10px] font-bold text-slate-400 flex items-center gap-1">
                  <i data-lucide="globe" class="w-3 h-3"></i>
                  {{ \Illuminate\Support\Str::substr((string) ($country->uuid ?? ''), 0, 8) }}
                </div>
              </div>
            </div>
          </td>

          {{-- المدن — NOT searchable (count badge, not text the user searches for) --}}
          <td class="px-6 py-4 text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 text-[10px] font-bold border border-blue-200">
              <i data-lucide="building-2" class="w-3 h-3"></i>
              {{ $country->cities_count ?? 0 }} مدينة
            </span>
          </td>

          {{-- تاريخ الإضافة — NOT searchable --}}
          <td class="px-6 py-4 text-center">
            <div class="flex flex-col items-center gap-1">
              <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-700">
                <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                {{ optional($country->created_at)->translatedFormat('j F، Y') ?? '—' }}
              </span>
              <span class="inline-flex items-center gap-1 text-[10px] text-slate-400">
                <i data-lucide="clock" class="w-3 h-3"></i>
                {{ optional($country->created_at)->diffForHumans() ?? '—' }}
              </span>
            </div>
          </td>

          {{-- الإجراءات — NOT searchable (buttons only) --}}
          <td class="px-6 py-4 text-center">
            <div class="flex items-center justify-center gap-1.5">
              @if($canEdit)
                <x-action-button
                    variant="edit"
                    data-edit-modal="country-form-modal"
                    data-edit-json="{{ json_encode([
                        'uuid' => $country->uuid,
                        'name' => $country->name,
                        'cities_count' => $country->cities_count ?? 0,
                    ], JSON_UNESCAPED_UNICODE) }}"
                    :ariaLabel="'تعديل ' . $country->name"
                />
              @endif
              @if($canDelete)
                <x-action-button
                    variant="delete"
                    data-delete-url="{{ route('admin.countries.destroy', $country) }}"
                    data-delete-label="{{ $country->name }}"
                />
              @endif
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state
            icon="globe"
            :title="request('search') ? 'لا توجد نتائج مطابقة' : 'لا توجد دول مسجلة'"
            :message="request('search') ? 'جرّب تعديل كلمات البحث' : 'أضف دولة جديدة لتتمكن من ربط المدن والمواقع بها'"
            :colspan="5"
        >
          @if(!request('search') && $canCreate)
            <button
                type="button"
                data-create-modal="country-form-modal"
                class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
            >
              <i data-lucide="plus" class="w-4 h-4"></i>
              إضافة دولة جديدة
            </button>
          @endif
        </x-empty-state>
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$countries" />
    </x-slot>
  </x-data-table-card>

  </div>{{-- end #aracari-refreshable --}}

</div>

{{-- ===== Country Form Modal (Create + Edit) ===== --}}
<x-form-modal
    id="country-form-modal"
    title="إضافة دولة جديدة"
    :variant="'create'"
    :action="route('admin.countries.store')"
    method="POST"
    successMessage="تم حفظ الدولة بنجاح"
>
  {{-- اسم الدولة --}}
  <x-form-field name="name" label="اسم الدولة" icon="globe" required>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="globe" class="w-4 h-4"></i>
      </div>
      <input
          type="text"
          name="name"
          id="name"
          required
          placeholder="مثال: اليمن، المملكة العربية السعودية، الإمارات..."
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
      >
    </div>
  </x-form-field>

  {{-- تنبيه إنشاء الدولة --}}
  <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-3">
    <div class="flex items-start gap-2.5">
      <i data-lucide="info" class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5"></i>
      <span class="text-[11px] text-amber-600">
        سيتم إضافة الدولة وستكون متاحة للاختيار عند إنشاء المدن والمواقع والرحلات.
      </span>
    </div>
  </div>

  {{-- معاينة حية --}}
  <div class="space-y-2">
    <h4 class="font-bold text-xs text-[#115128] border-b border-[#eeeef0] pb-2 flex items-center gap-1.5">
      <i data-lucide="eye" class="w-3.5 h-3.5"></i>
      معاينة الدولة
    </h4>
    <div class="bg-[#f9f9fc] border border-[#eeeef0] rounded-xl p-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center border bg-teal-50 border-teal-100 text-teal-500">
          <i data-lucide="globe" class="w-5 h-5"></i>
        </div>
        <div>
          <h4 class="text-sm font-bold text-slate-800" id="name-preview" data-preview-default="دولة جديدة">دولة جديدة</h4>
          <span class="inline-flex items-center gap-1 text-[10px] text-slate-400 mt-0.5">
            <i data-lucide="check-circle" class="w-3 h-3"></i>
            دولة
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
  // ===== Country modal: dynamic action + title + variant switching =====
  // When opening for edit, switch the form to PUT + update title/banner.
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'country-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var titleEl = modal.querySelector('h2');
    var isEdit = !!(data.uuid);

    if (isEdit) {
      // edit mode → PUT to update route
      var updateUrl = "{{ route('admin.countries.update', '__UUID__') }}".replace('__UUID__', data.uuid);
      form.setAttribute('action', updateUrl);
      var existingMethod = form.querySelector('input[name="_method"]');
      if (existingMethod) existingMethod.value = 'PUT';
      else {
        var m = document.createElement('input');
        m.type = 'hidden'; m.name = '_method'; m.value = 'PUT';
        form.appendChild(m);
      }
      // update title + banner to amber (edit variant)
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل الدولة';
      // badge
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }
      // success message
      form.setAttribute('data-success-message', 'تم تحديث الدولة بنجاح');
    } else {
      // create mode → POST to store route
      form.setAttribute('action', "{{ route('admin.countries.store') }}");
      var m = form.querySelector('input[name="_method"]');
      if (m) m.remove();
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة دولة جديدة';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30';
        badge.textContent = 'جديد';
      }
      form.setAttribute('data-success-message', 'تم حفظ الدولة بنجاح');
    }
    // re-render icons (we injected new <i data-lucide>)
    if (window.lucide) window.lucide.createIcons();

    // sync live preview after fields are filled programmatically by openModal
    var nameInput = modal.querySelector('#name');
    var preview = modal.querySelector('#name-preview');
    if (nameInput && preview) {
      preview.textContent = nameInput.value.trim() || 'دولة جديدة';
    }
  });

  // ===== Live preview: name → preview element =====
  (function() {
    var nameInput = document.getElementById('name');
    var preview = document.getElementById('name-preview');
    if (!nameInput || !preview) return;
    nameInput.addEventListener('input', function() {
      preview.textContent = nameInput.value.trim() || 'دولة جديدة';
    });
  })();
</script>
@endpush
