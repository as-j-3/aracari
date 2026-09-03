@extends('layouts.admin')

@section('title', 'أركاري | إدارة أنماط الرحلات')
@section('header_title', 'إدارة أنماط وفئات الرحلات')

@section('content')

@php
    $totalTypes = $types->total();

    $user      = auth()->user();
    $canView   = $user && $user->hasPermission('trip-types:view');
    $canCreate = $user && $user->hasPermission('trip-types:create');
    $canEdit   = $user && $user->hasPermission('trip-types:edit');
    $canDelete = $user && $user->hasPermission('trip-types:delete');
    $types->getCollection()->loadCount('trips');
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="layers"
      title="أنماط وفئات الرحلات"
      :count="$totalTypes"
      countLabel="نمط"
      description="أضف أنماطاً جديدة لتصنيف رحلات الأسطول (مثال: دولي، محلي)"
      descriptionIcon="layers"
  >
    @if($canCreate)
      <button
          type="button"
          data-create-modal="trip-type-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إضافة نمط جديد
      </button>
    @endif
  </x-page-header>

  {{-- ===== Refreshable region: stats + table ===== --}}
  <div id="aracari-refreshable" class="space-y-6">

  {{-- ===== Stat Cards ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
    <x-stat-card :value="$totalTypes" label="إجمالي الأنماط" icon="layers" variant="slate" badge="نمط" badgeIcon="layers" />
    <x-stat-card :value="$types->getCollection()->filter(fn($t) => ($t->trips_count ?? 0) > 0)->count()" label="أنماط مستخدمة" icon="check-circle" variant="emerald" badge="متاح" badgeIcon="check-circle" />
    <x-stat-card :value="$types->getCollection()->sum(fn($t) => $t->trips_count ?? 0)" label="إجمالي الرحلات" icon="bus" variant="blue" badge="رحلة" badgeIcon="bus" />
  </div>

  {{-- ===== Data Table Card ===== --}}
  <x-data-table-card :searchable="true" :searchMode="'server'" searchPlaceholder="ابحث عن نمط..." :footerInfo="'عرض ' . $types->count() . ' من أصل ' . $types->total() . ' سجل'">
    <x-slot name="thead">
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">مسمى النمط</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الرحلات</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">تاريخ الإضافة</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
    </x-slot>

    <x-slot name="rows">
      @forelse($types as $type)
        <tr class="hover:bg-slate-50/50 transition-colors group">
          {{-- # --}}
          <td class="px-6 py-4">
            <span class="font-mono font-black text-slate-400 text-sm">{{ str_pad((string) $type->id, 4, '0', STR_PAD_LEFT) }}</span>
          </td>

          {{-- مسمى النمط --}}
          <td data-searchable class="px-6 py-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500/20 to-teal-600/10 flex items-center justify-center border border-white/10 group-hover:scale-110 transition-transform duration-300">
                <i data-lucide="layers" class="w-5 h-5 text-teal-600"></i>
              </div>
              <div>
                <div class="font-bold text-slate-800">{{ $type->type_name }}</div>
                <div class="text-[10px] font-bold text-slate-400 flex items-center gap-1">
                  <i data-lucide="calendar" class="w-3 h-3"></i>
                  {{ optional($type->created_at)->translatedFormat('j F، Y') ?? '—' }}
                </div>
              </div>
            </div>
          </td>

          {{-- الرحلات --}}
          <td class="px-6 py-4 text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 text-[10px] font-bold border border-blue-200">
              <i data-lucide="bus" class="w-3 h-3"></i>
              {{ $type->trips_count ?? 0 }} رحلة
            </span>
          </td>

          {{-- تاريخ الإضافة --}}
          <td class="px-6 py-4 text-center">
            <div class="flex flex-col items-center gap-1">
              <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-700">
                <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                {{ optional($type->created_at)->translatedFormat('j F، Y') ?? '—' }}
              </span>
              <span class="inline-flex items-center gap-1 text-[10px] text-slate-400">
                <i data-lucide="clock" class="w-3 h-3"></i>
                {{ optional($type->created_at)->diffForHumans() ?? '—' }}
              </span>
            </div>
          </td>

          {{-- الإجراءات --}}
          <td class="px-6 py-4 text-center">
            <div class="flex items-center justify-center gap-1.5">
              @if($canEdit)
                <x-action-button
                    variant="edit"
                    data-edit-modal="trip-type-form-modal"
                    data-edit-json="{{ json_encode([
                        'id' => $type->id,
                        'type_name' => $type->type_name,
                    ], JSON_UNESCAPED_UNICODE) }}"
                    :ariaLabel="'تعديل ' . $type->type_name"
                />
              @endif
              @if($canDelete)
                <x-action-button
                    variant="delete"
                    data-delete-url="{{ route('admin.lookups.trip-types.destroy', $type->id) }}"
                    data-delete-label="{{ $type->type_name }}"
                />
              @endif
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state
            icon="layers"
            :title="request('search') ? 'لا توجد نتائج مطابقة' : 'لا توجد أنماط'"
            :message="request('search') ? 'جرّب تعديل كلمات البحث' : 'أضف نمطاً جديداً لتصنيف رحلات الأسطول (مثال: دولي، محلي، سياحي)'"
            :colspan="5"
        >
          @if(!request('search') && $canCreate)
            <button
                type="button"
                data-create-modal="trip-type-form-modal"
                class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
            >
              <i data-lucide="plus" class="w-4 h-4"></i>
              إضافة نمط جديد
            </button>
          @endif
        </x-empty-state>
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$types" />
    </x-slot>
  </x-data-table-card>

  </div>{{-- end #aracari-refreshable -->}

</div>

{{-- ===== Trip Type Form Modal (Create + Edit) ===== --}}
<x-form-modal
    id="trip-type-form-modal"
    title="إضافة نمط رحلة جديد"
    :variant="'create'"
    :action="route('admin.lookups.trip-types.store')"
    method="POST"
    successMessage="تم حفظ النمط بنجاح"
>
  {{-- اسم النمط --}}
  <x-form-field name="type_name" label="اسم النمط" icon="layers" required>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="layers" class="w-4 h-4"></i>
      </div>
      <input
          type="text"
          name="type_name"
          id="type_name"
          required
          placeholder="مثال: رحلات دولية، رحلات محلية، رحلات سياحية..."
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
      >
    </div>
  </x-form-field>

  {{-- تنبيه إنشاء النمط --}}
  <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-3">
    <div class="flex items-start gap-2.5">
      <i data-lucide="info" class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5"></i>
      <span class="text-[11px] text-amber-600">
        سيتم إضافة النمط وستكون متاحة للاختيار عند إنشاء الرحلات الجديدة.
      </span>
    </div>
  </div>

  {{-- معاينة حية --}}
  <div class="space-y-2">
    <h4 class="font-bold text-xs text-[#115128] border-b border-[#eeeef0] pb-2 flex items-center gap-1.5">
      <i data-lucide="eye" class="w-3.5 h-3.5"></i>
      معاينة النمط
    </h4>
    <div class="bg-[#f9f9fc] border border-[#eeeef0] rounded-xl p-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center border bg-teal-50 border-teal-100 text-teal-500">
          <i data-lucide="layers" class="w-5 h-5"></i>
        </div>
        <div>
          <h4 class="text-sm font-bold text-slate-800" id="type_name-preview" data-preview-default="نمط جديد">نمط جديد</h4>
          <span class="inline-flex items-center gap-1 text-[10px] text-slate-400 mt-0.5">
            <i data-lucide="check-circle" class="w-3 h-3"></i>
            نمط رحلة
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
  // ===== Trip Type modal: dynamic action + title + variant switching =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'trip-type-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.id);

    if (isEdit) {
      var updateUrl = "{{ route('admin.lookups.trip-types.update', '__ID__') }}".replace('__ID__', data.id);
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
        '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل نمط الرحلة';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }
      form.setAttribute('data-success-message', 'تم تحديث النمط بنجاح');
    } else {
      form.setAttribute('action', "{{ route('admin.lookups.trip-types.store') }}");
      var m = form.querySelector('input[name="_method"]');
      if (m) m.remove();
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة نمط رحلة جديد';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30';
        badge.textContent = 'جديد';
      }
      form.setAttribute('data-success-message', 'تم حفظ النمط بنجاح');
    }
    if (window.lucide) window.lucide.createIcons();

    var nameInput = modal.querySelector('#type_name');
    var preview = modal.querySelector('#type_name-preview');
    if (nameInput && preview) {
      preview.textContent = nameInput.value.trim() || 'نمط جديد';
    }
  });

  // ===== Live preview --}}
  (function() {
    var nameInput = document.getElementById('type_name');
    var preview = document.getElementById('type_name-preview');
    if (!nameInput || !preview) return;
    nameInput.addEventListener('input', function() {
      preview.textContent = nameInput.value.trim() || 'نمط جديد';
    });
  })();
</script>
@endpush
