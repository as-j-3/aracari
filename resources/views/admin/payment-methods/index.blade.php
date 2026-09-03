@extends('layouts.admin')

@section('title', 'أركاري | قنوات ووسائل الدفع')
@section('header_title', 'قنوات ووسائل الدفع')

@section('content')

@php
    $user      = auth()->user();
    $canView   = $user && $user->hasPermission('payment-methods:view');
    $canCreate = $user && $user->hasPermission('payment-methods:create');
    $canEdit   = $user && $user->hasPermission('payment-methods:edit');
    $canDelete = $user && $user->hasPermission('payment-methods:delete');
    $totalCount = $methods->total();
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="credit-card"
      title="قنوات ووسائل الدفع"
      :count="$totalCount"
      countLabel="وسيلة"
      description="حدد القنوات المالية المتاحة للركاب كالنقد والفيزا والمحافظ الإلكترونية"
      descriptionIcon="credit-card"
  >
    @if($canCreate)
      <button
          type="button"
          data-create-modal="payment-method-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إضافة وسيلة جديدة
      </button>
    @endif
  </x-page-header>

  {{-- ===== Refreshable region ===== --}}
  <div id="aracari-refreshable" class="space-y-6">

    {{-- ===== Data Table Card ===== --}}
    <x-data-table-card :searchable="true" :searchMode="'server'" searchPlaceholder="ابحث عن وسيلة دفع..." :footerInfo="'عرض ' . $methods->count() . ' من أصل ' . $methods->total() . ' وسيلة'">
      <x-slot name="thead">
        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">وسيلة الدفع</th>
        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
      </x-slot>

      <x-slot name="rows">
        @forelse($methods as $method)
          <tr class="hover:bg-slate-50/50 transition-colors group">
            {{-- # --}}
            <td class="px-6 py-4">
              <span class="font-mono font-black text-slate-400 text-sm">{{ str_pad((string) $method->id, 4, '0', STR_PAD_LEFT) }}</span>
            </td>

            {{-- وسيلة الدفع --}}
            <td data-searchable class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500/20 to-emerald-600/10 flex items-center justify-center border border-emerald-100 group-hover:scale-105 transition-transform duration-300">
                  <i data-lucide="credit-card" class="w-5 h-5 text-emerald-600"></i>
                </div>
                <div>
                  <div class="font-bold text-slate-800 text-sm">{{ $method->method_name }}</div>
                  <div class="text-[10px] text-slate-400 font-medium">
                    تم الإنشاء: {{ optional($method->created_at)->format('Y/m/d') ?? '—' }}
                  </div>
                </div>
              </div>
            </td>

            {{-- الإجراءات --}}
            <td class="px-6 py-4 text-center">
              <div class="flex items-center justify-center gap-1.5">
                @if($canEdit)
                  <x-action-button
                      variant="edit"
                      data-edit-modal="payment-method-form-modal"
                      data-edit-json="{{ json_encode([
                          'id'           => $method->id,
                          'method_name'  => $method->method_name,
                          'created_at'   => optional($method->created_at)->format('Y/m/d H:i'),
                          'updated_at'   => optional($method->updated_at)->format('Y/m/d H:i'),
                      ], JSON_UNESCAPED_UNICODE) }}"
                      :ariaLabel="'تعديل ' . $method->method_name"
                  />
                @endif
                @if($canDelete)
                  <x-action-button
                      variant="delete"
                      data-delete-url="{{ route('admin.lookups.payment-methods.destroy', $method->id) }}"
                      data-delete-label="{{ $method->method_name }}"
                  />
                @endif
              </div>
            </td>
          </tr>
        @empty
          <x-empty-state
              icon="credit-card"
              :title="request('search') ? 'لا توجد نتائج مطابقة' : 'لا توجد وسائل دفع'"
              :message="request('search') ? 'جرّب تعديل كلمات البحث' : 'أضف القنوات المالية لبدء استقبال الحجوزات'"
              :colspan="3"
          >
            @if(!request('search') && $canCreate)
              <button
                  type="button"
                  data-create-modal="payment-method-form-modal"
                  class="text-emerald-600 hover:text-emerald-700 mt-3 inline-flex items-center gap-1 font-bold"
              >
                <i data-lucide="plus" class="w-4 h-4"></i>
                إضافة وسيلة جديدة
              </button>
            @endif
          </x-empty-state>
        @endforelse
      </x-slot>

      <x-slot name="footer">
        <x-pagination :paginator="$methods" />
      </x-slot>
    </x-data-table-card>

  </div>{{-- end #aracari-refreshable --}}

</div>

{{-- ===== Payment Method Form Modal (Create + Edit) ===== --}}
<x-form-modal
    id="payment-method-form-modal"
    title="إضافة طريقة دفع"
    :variant="'create'"
    :action="route('admin.lookups.payment-methods.store')"
    method="POST"
    successMessage="تم حفظ طريقة الدفع بنجاح"
>
  {{-- اسم طريقة الدفع --}}
  <x-form-field name="method_name" label="اسم طريقة الدفع" icon="tag" required>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="tag" class="w-4 h-4"></i>
      </div>
      <input
          type="text"
          name="method_name"
          id="method_name"
          required
          placeholder="مثال: نقدي، بطاقة ائتمان، محفظة إلكترونية، تحويل بنكي..."
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-[#115128] transition-all text-right"
      >
    </div>
  </x-form-field>

  {{-- معلومات إضافية (قراءة فقط) --}}
  <div class="space-y-3">
    <div class="flex items-center gap-2 pb-1.5 border-b border-[#eeeef0]">
      <div class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center border border-teal-100">
        <i data-lucide="info" class="w-4 h-4"></i>
      </div>
      <div class="flex-1">
        <h4 class="font-bold text-xs text-[#115128]">معلومات إضافية</h4>
        <p class="text-[10px] text-slate-400">سيتم تعبئتها تلقائياً</p>
      </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div class="space-y-1.5">
        <label class="text-[11px] font-bold text-slate-500 flex items-center gap-1">
          <i data-lucide="clock" class="w-3.5 h-3.5"></i>
          تاريخ الإنشاء
        </label>
        <input
            type="text"
            readonly
            id="created-at-display"
            value="—"
            class="w-full bg-slate-100/50 border border-slate-200 rounded-xl pr-3 pl-3 py-2.5 text-[13px] font-bold text-slate-500 cursor-not-allowed outline-none text-right"
            dir="ltr"
        >
      </div>
      <div class="space-y-1.5">
        <label class="text-[11px] font-bold text-slate-500 flex items-center gap-1">
          <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
          آخر تحديث
        </label>
        <input
            type="text"
            readonly
            id="updated-at-display"
            value="—"
            class="w-full bg-slate-100/50 border border-slate-200 rounded-xl pr-3 pl-3 py-2.5 text-[13px] font-bold text-slate-500 cursor-not-allowed outline-none text-right"
            dir="ltr"
        >
      </div>
    </div>
  </div>

  {{-- معاينة حية --}}
  <div class="space-y-2">
    <h4 class="font-bold text-xs text-[#115128] border-b border-[#eeeef0] pb-2 flex items-center gap-1.5">
      <i data-lucide="eye" class="w-3.5 h-3.5"></i>
      معاينة طريقة الدفع
    </h4>
    <div class="bg-[#f9f9fc] border border-[#eeeef0] rounded-xl p-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center border bg-emerald-50 border-emerald-100 text-emerald-500">
          <i data-lucide="credit-card" class="w-5 h-5"></i>
        </div>
        <div>
          <h4 class="text-sm font-bold text-slate-800" id="method-name-preview" data-preview-default="طريقة دفع جديدة">طريقة دفع جديدة</h4>
          <span class="inline-flex items-center gap-1 text-[10px] text-slate-400 mt-0.5">
            <i data-lucide="check-circle" class="w-3 h-3"></i>
            طريقة دفع
          </span>
        </div>
      </div>
      <span id="preview-badge" class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
        <i data-lucide="plus-circle" class="w-2.5 h-2.5"></i>
        جديد
      </span>
    </div>
  </div>
</x-form-modal>

@endsection

@push('scripts')
<script>
  // ===== Payment Method modal: dynamic action + title + variant switching =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'payment-method-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.id);

    // Fill the name field
    var nameInput = modal.querySelector('#method_name');
    if (nameInput) nameInput.value = data.method_name || '';

    // Fill readonly timestamps
    var createdEl = modal.querySelector('#created-at-display');
    var updatedEl = modal.querySelector('#updated-at-display');
    if (createdEl) createdEl.value = data.created_at || '—';
    if (updatedEl) updatedEl.value = data.updated_at || '—';

    if (isEdit) {
      // edit mode -> PUT
      var updateUrl = "{{ route('admin.lookups.payment-methods.update', '__ID__') }}".replace('__ID__', data.id);
      form.setAttribute('action', updateUrl);
      var existingMethod = form.querySelector('input[name="_method"]');
      if (existingMethod) existingMethod.value = 'PUT';
      else {
        var m = document.createElement('input');
        m.type = 'hidden'; m.name = '_method'; m.value = 'PUT';
        form.appendChild(m);
      }
      // banner to amber
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل طريقة دفع';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }
      form.setAttribute('data-success-message', 'تم تحديث وسيلة الدفع بنجاح');

      // preview badge -> edit
      var previewBadge = modal.querySelector('#preview-badge');
      if (previewBadge) {
        previewBadge.className = 'inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200';
        previewBadge.innerHTML = '<i data-lucide="pencil" class="w-2.5 h-2.5"></i> جاري التعديل';
      }
    } else {
      // create mode -> POST
      form.setAttribute('action', "{{ route('admin.lookups.payment-methods.store') }}");
      var m = form.querySelector('input[name="_method"]');
      if (m) m.remove();
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة طريقة دفع';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30';
        badge.textContent = 'جديد';
      }
      form.setAttribute('data-success-message', 'تم حفظ طريقة الدفع بنجاح');

      // reset timestamps
      var createdEl = modal.querySelector('#created-at-display');
      var updatedEl = modal.querySelector('#updated-at-display');
      if (createdEl) createdEl.value = '—';
      if (updatedEl) updatedEl.value = '—';

      // preview badge -> new
      var previewBadge = modal.querySelector('#preview-badge');
      if (previewBadge) {
        previewBadge.className = 'inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200';
        previewBadge.innerHTML = '<i data-lucide="plus-circle" class="w-2.5 h-2.5"></i> جديد';
      }
    }
    // re-render lucide icons
    if (window.lucide) window.lucide.createIcons();

    // sync live preview
    var preview = modal.querySelector('#method-name-preview');
    if (nameInput && preview) {
      preview.textContent = nameInput.value.trim() || 'طريقة دفع جديدة';
    }
  });

  // ===== Live preview: method_name -> preview element =====
  (function() {
    var nameInput = document.getElementById('method_name');
    var preview = document.getElementById('method-name-preview');
    if (!nameInput || !preview) return;
    nameInput.addEventListener('input', function() {
      preview.textContent = nameInput.value.trim() || 'طريقة دفع جديدة';
    });
  })();
</script>
@endpush
