@extends('layouts.admin')

@section('title', 'أركاري | الخدمات التشغيلية')
@section('header_title', 'الخدمات التشغيلية')

@section('content')

@php
    $totalCount = $services->total();

    $user      = auth()->user();
    $canCreate = $user && $user->hasPermission('service-types:create');
    $canEdit   = $user && $user->hasPermission('service-types:edit');
    $canDelete = $user && $user->hasPermission('service-types:delete');
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="concierge-bell"
      title="الخدمات التشغيلية"
      :count="$totalCount"
      countLabel="خدمة"
      description="إدارة أنواع الخدمات التشغيلية للرحلات"
      descriptionIcon="concierge-bell"
  >
    @if($canCreate)
      <button
          type="button"
          data-create-modal="service-type-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إضافة خدمة جديدة
      </button>
    @endif
  </x-page-header>

  {{-- ===== Refreshable region ===== --}}
  <div id="aracari-refreshable" class="space-y-6">

    {{-- ===== Data Table Card ===== --}}
    <x-data-table-card :footerInfo="'عرض ' . $services->count() . ' من أصل ' . $services->total() . ' خدمة'">
      <x-slot name="thead">
        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الخدمة</th>
        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
      </x-slot>

      <x-slot name="rows">
        @forelse($services as $service)
          <tr class="hover:bg-slate-50/50 transition-colors group">
            {{-- # --}}
            <td class="px-6 py-4">
              <span class="font-mono font-black text-slate-400 text-sm">{{ str_pad((string) $service->id, 4, '0', STR_PAD_LEFT) }}</span>
            </td>

            {{-- الخدمة --}}
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500/20 to-teal-600/10 flex items-center justify-center border border-teal-100">
                  <i data-lucide="concierge-bell" class="w-5 h-5 text-teal-600"></i>
                </div>
                <div>
                  <div class="font-bold text-slate-800">{{ $service->type_name }}</div>
                </div>
              </div>
            </td>

            {{-- الإجراءات --}}
            <td class="px-6 py-4 text-center">
              <div class="flex items-center justify-center gap-1.5">
                @if($canEdit)
                  <x-action-button
                      variant="edit"
                      data-edit-modal="service-type-form-modal"
                      data-edit-json="{{ json_encode([
                          'id'         => $service->id,
                          'type_name'  => $service->type_name,
                      ], JSON_UNESCAPED_UNICODE) }}"
                      :ariaLabel="'تعديل ' . $service->type_name"
                  />
                @endif
                @if($canDelete)
                  <x-action-button
                      variant="delete"
                      data-delete-url="{{ route('admin.service-types.destroy', $service) }}"
                      data-delete-label="{{ $service->type_name }}"
                  />
                @endif
              </div>
            </td>
          </tr>
        @empty
          <x-empty-state
              icon="concierge-bell"
              :title="'لا توجد خدمات تشغيلية'"
              message="أضف خدمة تشغيلية جديدة لتتمكن من استخدامها في الرحلات"
              :colspan="3"
          >
            @if($canCreate)
              <button
                  type="button"
                  data-create-modal="service-type-form-modal"
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
        <x-pagination :paginator="$services" />
      </x-slot>
    </x-data-table-card>

  </div>{{-- end #aracari-refreshable --}}

</div>

{{-- ===== Service Type Form Modal (Create + Edit) ===== --}}
<x-form-modal
    id="service-type-form-modal"
    title="إضافة خدمة تشغيلية"
    :variant="'create'"
    :action="route('admin.service-types.store')"
    method="POST"
    successMessage="تم حفظ الخدمة بنجاح"
>
  {{-- اسم الخدمة --}}
  <x-form-field name="type_name" label="اسم الخدمة" icon="settings" required>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="settings" class="w-4 h-4"></i>
      </div>
      <input
          type="text"
          name="type_name"
          id="type_name"
          required
          placeholder="مثال: نقل داخلي، نقل خارجي، سياحي، VIP..."
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
      >
    </div>
  </x-form-field>

  {{-- تنبيه إنشاء --}}
  <div id="create-info-block" class="bg-amber-50/70 border border-amber-200 rounded-xl p-3">
    <div class="flex items-start gap-2.5">
      <i data-lucide="info" class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5"></i>
      <span class="text-[11px] text-amber-600">
        سيتم إضافة الخدمة التشغيلية وستكون متاحة للاختيار عند إنشاء الرحلات.
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
        <div id="preview-icon" class="w-11 h-11 rounded-xl flex items-center justify-center border bg-teal-50 border-teal-100 text-teal-500">
          <i data-lucide="concierge-bell" class="w-5 h-5"></i>
        </div>
        <div>
          <h4 class="text-sm font-bold text-slate-800" id="type-name-preview" data-preview-default="خدمة جديدة">خدمة جديدة</h4>
          <span class="inline-flex items-center gap-1 text-[10px] text-slate-400 mt-0.5">
            <i data-lucide="check-circle" class="w-3 h-3"></i>
            خدمة تشغيلية
          </span>
        </div>
      </div>
      <span id="preview-badge" class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-200">
        <i data-lucide="plus-circle" class="w-2.5 h-2.5"></i>
        جديد
      </span>
    </div>
  </div>
</x-form-modal>

@endsection

@push('scripts')
<script>
  // ===== Service Type modal: dynamic action + title + variant switching =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'service-type-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.id);

    // Fill the name field
    var nameInput = modal.querySelector('#type_name');
    if (nameInput) nameInput.value = data.type_name || '';

    if (isEdit) {
      var updateUrl = "{{ route('admin.service-types.update', '__ID__') }}".replace('__ID__', data.id);
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
        '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل الخدمة التشغيلية';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }
      form.setAttribute('data-success-message', 'تم تحديث الخدمة التشغيلية بنجاح');

      var previewBadge = modal.querySelector('#preview-badge');
      var previewIcon = modal.querySelector('#preview-icon');
      if (previewBadge) {
        previewBadge.className = 'inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200';
        previewBadge.innerHTML = '<i data-lucide="pencil" class="w-2.5 h-2.5"></i> تعديل';
      }
      if (previewIcon) {
        previewIcon.className = 'w-11 h-11 rounded-xl flex items-center justify-center border bg-amber-50 border-amber-100 text-amber-500';
      }
    } else {
      form.setAttribute('action', "{{ route('admin.service-types.store') }}");
      var m = form.querySelector('input[name="_method"]');
      if (m) m.remove();
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة خدمة تشغيلية';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30';
        badge.textContent = 'جديد';
      }
      form.setAttribute('data-success-message', 'تم حفظ الخدمة بنجاح');

      var previewBadge = modal.querySelector('#preview-badge');
      var previewIcon = modal.querySelector('#preview-icon');
      if (previewBadge) {
        previewBadge.className = 'inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-200';
        previewBadge.innerHTML = '<i data-lucide="plus-circle" class="w-2.5 h-2.5"></i> جديد';
      }
      if (previewIcon) {
        previewIcon.className = 'w-11 h-11 rounded-xl flex items-center justify-center border bg-teal-50 border-teal-100 text-teal-500';
      }
    }
    if (window.lucide) window.lucide.createIcons();

    // sync live preview
    var preview = modal.querySelector('#type-name-preview');
    if (nameInput && preview) {
      preview.textContent = nameInput.value.trim() || 'خدمة جديدة';
    }
  });

  // ===== Live preview: type_name -> preview element =====
  (function() {
    var nameInput = document.getElementById('type_name');
    var preview = document.getElementById('type-name-preview');
    if (!nameInput || !preview) return;
    nameInput.addEventListener('input', function() {
      preview.textContent = nameInput.value.trim() || 'خدمة جديدة';
    });
  })();
</script>
@endpush
