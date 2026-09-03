@extends('layouts.admin')

@section('title', 'أركاري | حالات الكوبونات')
@section('header_title', 'حالات الكوبونات')

@section('content')

@php
    $totalCount = $statuses->total();
    $user      = auth()->user();
    $canCreate = $user && $user->hasPermission('coupon-statuses:create');
    $canEdit   = $user && $user->hasPermission('coupon-statuses:edit');
    $canDelete = $user && $user->hasPermission('coupon-statuses:delete');
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="tags"
      title="حالات الكوبونات والعروض"
      :count="$totalCount"
      countLabel="حالة"
      description="حدد الأكواد المرجعية والأسماء الوصفية لحالات أكواد الخصم"
      descriptionIcon="tag"
  >
    @if($canCreate)
      <button
          type="button"
          data-create-modal="coupon-status-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-rose-500 to-rose-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-rose-500/20 hover:shadow-rose-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إضافة حالة جديدة
      </button>
    @endif
  </x-page-header>

  {{-- ===== Refreshable region ===== --}}
  <div id="aracari-refreshable" class="space-y-6">

    {{-- ===== Data Table Card ===== --}}
    <x-data-table-card :searchable="true" :searchMode="'server'" searchPlaceholder="ابحث بالكود أو الاسم..." :footerInfo="'عرض ' . $statuses->count() . ' من أصل ' . $statuses->total() . ' حالة'">
      <x-slot name="thead">
        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الكود البرمجي</th>
        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المسمى</th>
        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
      </x-slot>

      <x-slot name="rows">
        @forelse($statuses as $status)
          <tr class="hover:bg-slate-50/50 transition-colors group">
            {{-- # --}}
            <td class="px-6 py-4">
              <span class="font-mono font-black text-slate-400 text-sm">{{ str_pad((string) $status->id, 4, '0', STR_PAD_LEFT) }}</span>
            </td>

            {{-- الكود البرمجي --}}
            <td data-searchable class="px-6 py-4">
              <span class="inline-flex items-center bg-slate-100 text-slate-600 px-3 py-1.5 rounded-lg font-mono font-bold text-xs tracking-wider border border-slate-200 shadow-sm" dir="ltr">
                {{ $status->status_code }}
              </span>
            </td>

            {{-- المسمى --}}
            <td data-searchable class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500/20 to-rose-600/10 flex items-center justify-center border border-rose-100 group-hover:scale-105 transition-transform duration-300">
                  <i data-lucide="tags" class="w-5 h-5 text-rose-500"></i>
                </div>
                <div>
                  <div class="font-bold text-slate-800 text-sm">{{ $status->name }}</div>
                  <div class="text-[10px] text-slate-400 font-medium">
                    تم الإنشاء: {{ optional($status->created_at)->format('Y/m/d') ?? '—' }}
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
                      data-edit-modal="coupon-status-form-modal"
                      data-edit-json="{{ json_encode([
                          'id'          => $status->id,
                          'status_code' => $status->status_code,
                          'name'        => $status->name,
                          'created_at'  => optional($status->created_at)->format('Y/m/d H:i'),
                          'updated_at'  => optional($status->updated_at)->format('Y/m/d H:i'),
                      ], JSON_UNESCAPED_UNICODE) }}"
                      :ariaLabel="'تعديل ' . $status->name"
                  />
                @endif
                @if($canDelete)
                  <x-action-button
                      variant="delete"
                      data-delete-url="{{ route('admin.lookups.coupon-statuses.destroy', $status->id) }}"
                      data-delete-label="{{ $status->name }}"
                  />
                @endif
              </div>
            </td>
          </tr>
        @empty
          <x-empty-state
              icon="tags"
              :title="request('search') ? 'لا توجد نتائج مطابقة' : 'لا توجد حالات معرفة'"
              :message="request('search') ? 'جرّب تعديل كلمات البحث' : 'أضف حالات مثل (active) للكوبونات لتعمل بشكل صحيح'"
              :colspan="4"
          >
            @if(!request('search') && $canCreate)
              <button
                  type="button"
                  data-create-modal="coupon-status-form-modal"
                  class="text-rose-600 hover:text-rose-700 mt-3 inline-flex items-center gap-1 font-bold"
              >
                <i data-lucide="plus" class="w-4 h-4"></i>
                إضافة حالة جديدة
              </button>
            @endif
          </x-empty-state>
        @endforelse
      </x-slot>

      <x-slot name="footer">
        <x-pagination :paginator="$statuses" />
      </x-slot>
    </x-data-table-card>

  </div>{{-- end #aracari-refreshable -->}

</div>

{{-- ===== Coupon Status Form Modal (Create + Edit) ===== --}}
<x-form-modal
    id="coupon-status-form-modal"
    title="إضافة حالة كوبون"
    :variant="'create'"
    :action="route('admin.lookups.coupon-statuses.store')"
    method="POST"
    successMessage="تم حفظ حالة الكوبون بنجاح"
>
  {{-- الكود البرمجي --}}
  <x-form-field name="status_code" label="الكود البرمجي" icon="hash" required>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-rose-500 transition-colors">
        <i data-lucide="hash" class="w-4 h-4"></i>
      </div>
      <input
          type="text"
          name="status_code"
          id="status_code"
          required
          placeholder="مثال: active, expired, used..."
          dir="ltr"
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold font-mono tracking-wider outline-none focus:bg-white focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 transition-all text-left"
      >
    </div>
  </x-form-field>

  {{-- اسم الحالة --}}
  <x-form-field name="name" label="المسمى الوصفي" icon="tag" required>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-rose-500 transition-colors">
        <i data-lucide="tag" class="w-4 h-4"></i>
      </div>
      <input
          type="text"
          name="name"
          id="name"
          required
          placeholder="مثال: نشط، منتهي الصلاحية، مستخدم، ملغى..."
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 transition-all text-right"
      >
    </div>
  </x-form-field>

  {{-- معلومات إضافية (قراءة فقط) --}}
  <div class="space-y-3">
    <div class="flex items-center gap-2 pb-1.5 border-b border-[#eeeef0]">
      <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100">
        <i data-lucide="info" class="w-4 h-4"></i>
      </div>
      <div class="flex-1">
        <h4 class="font-bold text-xs text-rose-600">معلومات إضافية</h4>
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
    <h4 class="font-bold text-xs text-rose-600 border-b border-[#eeeef0] pb-2 flex items-center gap-1.5">
      <i data-lucide="eye" class="w-3.5 h-3.5"></i>
      معاينة الحالة
    </h4>
    <div class="bg-[#f9f9fc] border border-[#eeeef0] rounded-xl p-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div id="preview-icon" class="w-11 h-11 rounded-xl flex items-center justify-center border bg-rose-50 border-rose-100 text-rose-500">
          <i data-lucide="tags" class="w-5 h-5"></i>
        </div>
        <div>
          <h4 class="text-sm font-bold text-slate-800" id="status-name-preview" data-preview-default="حالة جديدة">حالة جديدة</h4>
          <span id="status-code-badge" class="inline-flex items-center gap-1 text-[10px] text-slate-400 mt-0.5 font-mono">
            <i data-lucide="hash" class="w-3 h-3"></i>
            —
          </span>
        </div>
      </div>
      <span id="preview-badge" class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
        <i data-lucide="plus-circle" class="w-2.5 h-2.5"></i>
        جديد
      </span>
    </div>
  </div>
</x-form-modal>

@endsection

@push('scripts')
<script>
  // ===== Coupon Status modal: dynamic action + title + variant switching =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'coupon-status-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.id);

    // Fill fields
    var codeInput = modal.querySelector('#status_code');
    var nameInput = modal.querySelector('#name');
    if (codeInput) codeInput.value = data.status_code || '';
    if (nameInput) nameInput.value = data.name || '';

    // Fill readonly timestamps
    var createdEl = modal.querySelector('#created-at-display');
    var updatedEl = modal.querySelector('#updated-at-display');
    if (createdEl) createdEl.value = data.created_at || '—';
    if (updatedEl) updatedEl.value = data.updated_at || '—';

    if (isEdit) {
      var updateUrl = "{{ route('admin.lookups.coupon-statuses.update', '__ID__') }}".replace('__ID__', data.id);
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
        '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل حالة الكوبون';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }
      form.setAttribute('data-success-message', 'تم تحديث حالة الكوبون بنجاح');

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
      form.setAttribute('action', "{{ route('admin.lookups.coupon-statuses.store') }}");
      var m = form.querySelector('input[name="_method"]');
      if (m) m.remove();
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-rose-900 to-rose-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة حالة كوبون';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-rose-500/20 text-rose-300 border-rose-500/30';
        badge.textContent = 'جديد';
      }
      form.setAttribute('data-success-message', 'تم حفظ حالة الكوبون بنجاح');

      var createdEl = modal.querySelector('#created-at-display');
      var updatedEl = modal.querySelector('#updated-at-display');
      if (createdEl) createdEl.value = '—';
      if (updatedEl) updatedEl.value = '—';

      var previewBadge = modal.querySelector('#preview-badge');
      var previewIcon = modal.querySelector('#preview-icon');
      if (previewBadge) {
        previewBadge.className = 'inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200';
        previewBadge.innerHTML = '<i data-lucide="plus-circle" class="w-2.5 h-2.5"></i> جديد';
      }
      if (previewIcon) {
        previewIcon.className = 'w-11 h-11 rounded-xl flex items-center justify-center border bg-rose-50 border-rose-100 text-rose-500';
      }
    }
    if (window.lucide) window.lucide.createIcons();

    // sync live preview
    var previewName = modal.querySelector('#status-name-preview');
    var previewCode = modal.querySelector('#status-code-badge');
    if (nameInput && previewName) {
      previewName.textContent = nameInput.value.trim() || 'حالة جديدة';
    }
    if (codeInput && previewCode) {
      previewCode.innerHTML = '<i data-lucide="hash" class="w-3 h-3"></i> ' + (codeInput.value.trim() || '—');
      if (window.lucide) window.lucide.createIcons();
    }
  });

  // ===== Live preview: sync name + code on input =====
  (function() {
    var nameInput = document.getElementById('name');
    var codeInput = document.getElementById('status_code');
    var previewName = document.getElementById('status-name-preview');
    var previewCode = document.getElementById('status-code-badge');
    if (!nameInput || !previewName) return;

    nameInput.addEventListener('input', function() {
      previewName.textContent = nameInput.value.trim() || 'حالة جديدة';
    });

    if (codeInput && previewCode) {
      codeInput.addEventListener('input', function() {
        previewCode.innerHTML = '<i data-lucide="hash" class="w-3 h-3"></i> ' + (codeInput.value.trim() || '—');
        if (window.lucide) window.lucide.createIcons();
      });
    }
  })();
</script>
@endpush
