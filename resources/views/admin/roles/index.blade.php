@extends('layouts.admin')

@section('title', 'أركاري | إدارة الأدوار والصلاحيات')
@section('header_title', 'إدارة الأدوار')

@section('content')

@php
    // Permission checks (real authorization, not just route existence)
    $user             = auth()->user();
    $canView          = $user && $user->hasPermission('roles:view');
    $canCreate        = $user && $user->hasPermission('roles:create');
    $canEdit          = $user && $user->hasPermission('roles:edit');
    $canDelete        = $user && $user->hasPermission('roles:delete');
    $canAssignPerms   = $user && $user->hasPermission('permissions:assign');

    // Stats
    $totalRoles       = $roles->total();
    $totalLinkedUsers = (clone $roles->getCollection())->sum('users_count');
    $totalPermissions = \App\Models\Permission::count();

    // System role names (to flag in UI)
    $systemRoles = \App\Constants\UserRoles::systemRoles();
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="shield"
      title="الأدوار والصلاحيات"
      :count="$totalRoles"
      countLabel="دور"
      description="إدارة أدوار المستخدمين وصلاحياتهم"
      descriptionIcon="key"
  >
    @if($canCreate)
      <button
          type="button"
          data-create-modal="role-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus-circle" class="w-4 h-4"></i>
        إضافة دور جديد
      </button>
    @endif
  </x-page-header>

  {{-- ===== Refreshable region: stats + table (refreshed together after CRUD) ===== --}}
  <div id="aracari-refreshable" class="space-y-6">

  {{-- ===== Stat Cards (3) ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
    <x-stat-card :value="$totalRoles" label="إجمالي الأدوار" icon="users" variant="slate" badge="نشطة" badgeIcon="check-circle" />
    <x-stat-card :value="$totalLinkedUsers" label="مستخدمين مرتبطين" icon="users" variant="blue" badge="في النظام" badgeIcon="users" />
    <x-stat-card :value="$totalPermissions" label="إجمالي الصلاحيات" icon="key" variant="teal" badge="مسجلة" badgeIcon="key" />
  </div>

  {{-- ===== Data Table Card (refreshable via aracari.js) ===== --}}
  <x-data-table-card :searchable="true" :searchMode="'server'" searchPlaceholder="ابحث عن دور..." :footerInfo="'عرض ' . $roles->count() . ' من أصل ' . $roles->total() . ' دور'">
    <x-slot name="thead">
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الدور</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الوصف</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">المستخدمين</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
    </x-slot>

    <x-slot name="rows">
      @forelse($roles as $role)
        <tr class="hover:bg-slate-50/50 transition-colors group">
          {{-- الدور --}}
          <td data-searchable class="px-6 py-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500/20 to-teal-600/10 flex items-center justify-center border border-teal-100 text-teal-600 group-hover:scale-110 transition-transform duration-300">
                <i data-lucide="user-cog" class="w-5 h-5"></i>
              </div>
              <div>
                <div class="font-bold text-slate-800" dir="ltr">{{ $role->name }}</div>
                <div class="text-[10px] font-bold text-slate-400 flex items-center gap-1">
                  <i data-lucide="tag" class="w-3 h-3"></i>
                  ID: {{ $role->id }}
                  @if(in_array($role->name, $systemRoles))
                    <span class="mr-1.5 inline-flex items-center gap-0.5 text-[9px] font-bold text-amber-600 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded-full">
                      <i data-lucide="shield" class="w-2.5 h-2.5"></i>
                      نظام
                    </span>
                  @endif
                </div>
              </div>
            </div>
          </td>

          {{-- الوصف --}}
          <td data-searchable class="px-6 py-4 max-w-[280px]">
            @if($role->description)
              <p class="text-xs text-slate-600 leading-relaxed line-clamp-2">{{ $role->description }}</p>
            @else
              <span class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-full">
                <i data-lucide="ban" class="w-3 h-3"></i>
                لا يوجد وصف
              </span>
            @endif
          </td>

          {{-- المستخدمين --}}
          <td class="px-6 py-4 text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 text-[10px] font-bold border border-blue-200">
              <i data-lucide="users" class="w-3 h-3"></i>
              {{ $role->users_count }} مستخدم
            </span>
          </td>

          {{-- الإجراءات --}}
          <td class="px-6 py-4 text-center">
            <div class="flex items-center justify-center gap-1.5">
              @if($canAssignPerms)
                <a
                    href="{{ route('admin.roles.permissions', $role) }}"
                    class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-purple-50 hover:text-purple-600 hover:border-purple-200 transition-all duration-300 shadow-sm flex items-center justify-center"
                    title="الصلاحيات"
                    aria-label="{{ 'صلاحيات ' . $role->name }}"
                >
                  <i data-lucide="key" class="w-4 h-4"></i>
                </a>
              @endif
              @if($canEdit)
                <x-action-button
                    variant="edit"
                    data-edit-modal="role-form-modal"
                    data-edit-json="{{ json_encode([
                        'id' => $role->id,
                        'name' => $role->name,
                        'description' => $role->description,
                        'users_count' => $role->users_count,
                        'created_at' => $role->created_at?->format('Y-m-d H:i:s'),
                        'updated_at' => $role->updated_at?->diffForHumans(),
                    ], JSON_UNESCAPED_UNICODE) }}"
                    :ariaLabel="'تعديل ' . $role->name"
                />
              @endif
              @if($canDelete && !in_array($role->name, $systemRoles))
                <x-action-button
                    variant="delete"
                    data-delete-url="{{ route('admin.roles.destroy', $role) }}"
                    data-delete-label="{{ $role->name }}"
                />
              @endif
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state
            icon="user-cog"
            :title="request('search') ? 'لا توجد نتائج مطابقة' : 'لا توجد أدوار'"
            :message="request('search') ? 'جرّب تعديل كلمات البحث' : 'أضف دوراً جديداً لبدء إدارة الصلاحيات'"
            :colspan="4"
        >
          @if(!request('search') && $canCreate)
            <button
                type="button"
                data-create-modal="role-form-modal"
                class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
            >
              <i data-lucide="plus-circle" class="w-4 h-4"></i>
              إضافة دور جديد
            </button>
          @endif
        </x-empty-state>
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$roles" />
    </x-slot>
  </x-data-table-card>

  </div>{{-- end #aracari-refreshable --}}

</div>

{{-- ===== Role Form Modal (Create + Edit) ===== --}}
<x-form-modal
    id="role-form-modal"
    title="إضافة دور جديد"
    :variant="'create'"
    :action="route('admin.roles.store')"
    method="POST"
    successMessage="تم حفظ الدور بنجاح"
    maxWidth="max-w-2xl"
>

  {{-- القسم 1: معلومات الدور --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs text-[#115128] flex items-center gap-1.5">
        <i data-lucide="tag" class="w-3.5 h-3.5"></i>
        معلومات الدور
      </h4>
      <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border text-rose-600 bg-rose-50 border-rose-200" id="role-info-badge">مطلوب</span>
    </div>

    {{-- اسم الدور --}}
    <x-form-field name="name" label="الاسم التقني" icon="badge" required>
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="badge" class="w-4 h-4"></i>
        </div>
        <input
            type="text"
            name="name"
            id="role-name"
            required
            value="{{ old('name') }}"
            placeholder="مثال: accountant, manager"
            dir="ltr"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left"
        >
      </div>
      <p class="text-[10px] text-slate-400 leading-relaxed mt-1.5">
        يُستخدم الاسم التقني داخل النظام (مثلاً: accountant). يفضل استخدام أحرف إنجليزية صغيرة و _
      </p>
    </x-form-field>

    {{-- الوصف --}}
    <x-form-field name="description" label="الوصف" icon="file-text">
      <textarea
          name="description"
          id="role-description"
          rows="3"
          placeholder="وصف مختصر لمسؤوليات هذا الدور..."
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl p-3 text-[13px] font-medium outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all resize-none text-right"
      >{{ old('description') }}</textarea>
    </x-form-field>
  </div>

  {{-- القسم 2: معاينة الدور --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs text-purple-600 flex items-center gap-1.5">
        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
        معاينة الدور
      </h4>
      <span class="text-[9px] font-bold text-purple-600 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded-full">مباشر</span>
    </div>
    <div class="bg-[#f9f9fc] border border-slate-200/70 rounded-2xl p-4">
      <div class="flex items-start gap-3">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center shadow-lg shadow-teal-500/20 flex-shrink-0" id="role-preview-icon">
          <i data-lucide="user-cog" class="w-6 h-6 text-white"></i>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="font-bold text-slate-800 text-sm" dir="ltr" id="role-preview-name">اسم الدور</span>
            <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border bg-teal-50 text-teal-700 border-teal-200" id="role-preview-badge">جديد</span>
          </div>
          <p class="text-[11px] text-slate-500 mt-1 leading-relaxed line-clamp-2" id="role-preview-desc">لا يوجد وصف</p>
          <div class="flex items-center gap-2 mt-2" id="role-preview-users-wrap" style="display:none;">
            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-blue-700 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-full">
              <i data-lucide="users" class="w-3 h-3"></i>
              <span id="role-preview-users">0</span> مستخدم
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- القسم 3: معلومات إضافية (للتعديل فقط) --}}
  <div class="space-y-4" id="role-extra-info" style="display:none;">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs text-blue-600 flex items-center gap-1.5">
        <i data-lucide="info" class="w-3.5 h-3.5"></i>
        معلومات إضافية
      </h4>
      <span class="text-[9px] font-bold text-blue-500 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-full">للقراءة فقط</span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
      <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-3 text-center">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-center gap-1">
          <i data-lucide="users" class="w-3 h-3"></i>
          المستخدمين
        </p>
        <p class="font-bold text-slate-700 text-xs mt-1.5" id="role-info-users">0 مستخدم</p>
      </div>
      <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-3 text-center">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-center gap-1">
          <i data-lucide="calendar-days" class="w-3 h-3"></i>
          تاريخ الإنشاء
        </p>
        <p class="font-bold text-slate-700 text-xs mt-1.5" dir="ltr" id="role-info-created">—</p>
      </div>
      <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-3 text-center">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-center gap-1">
          <i data-lucide="clock" class="w-3 h-3"></i>
          آخر تحديث
        </p>
        <p class="font-bold text-slate-700 text-xs mt-1.5" id="role-info-updated">—</p>
      </div>
    </div>
  </div>

  {{-- Actions slot: permissions button for edit mode --}}
  <x-slot name="actions">
    <button
        type="button"
        data-close-modal
        class="flex-1 bg-[#f3f3f6] hover:bg-[#eeeef0] text-gray-600 font-bold py-3 px-4 rounded-xl text-xs transition-all flex items-center justify-center gap-2"
    >
      <i data-lucide="x" class="w-4 h-4"></i>
      <span id="role-cancel-text">إعادة تعيين</span>
    </button>
    <a
        href="#"
        id="role-permissions-btn"
        style="display:none;"
        class="flex-1 bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-4 rounded-xl text-xs transition-all flex items-center justify-center gap-2 shadow-md shadow-purple-500/20"
    >
      <i data-lucide="key" class="w-4 h-4"></i>
      <span>الصلاحيات</span>
    </a>
    <button
        type="submit"
        class="flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95 bg-[#115128] hover:bg-emerald-950"
        id="role-submit-btn"
    >
      <i data-lucide="save" class="w-4 h-4"></i>
      <span id="role-submit-text">حفظ الدور</span>
    </button>
  </x-slot>
</x-form-modal>

{{-- ===== Role Details Modal (view) ===== --}}
<x-modal
    id="role-details-modal"
    title="تفاصيل الدور"
    variant="view"
    :maxWidth="'max-w-2xl'"
    data-no-reset
>
  <div class="space-y-4">
    {{-- Role info card --}}
    <div class="bg-[#f9f9fc] border border-slate-200/70 rounded-2xl p-4">
      <div class="flex items-start gap-3">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center shadow-lg shadow-teal-500/20 flex-shrink-0">
          <i data-lucide="user-cog" class="w-6 h-6 text-white"></i>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="font-bold text-slate-800 text-sm" dir="ltr" id="details-role-name">—</span>
            <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border bg-slate-100 text-slate-600 border-slate-200" id="details-role-badge">—</span>
          </div>
          <p class="text-[11px] text-slate-500 mt-1 leading-relaxed" id="details-role-desc">لا يوجد وصف</p>
          <div class="flex items-center gap-2 mt-2">
            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-blue-700 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-full">
              <i data-lucide="users" class="w-3 h-3"></i>
              <span id="details-role-users">0</span> مستخدم
            </span>
          </div>
        </div>
      </div>
    </div>

    {{-- Meta info --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
      <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-3 text-center">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-center gap-1">
          <i data-lucide="users" class="w-3 h-3"></i>
          المستخدمين
        </p>
        <p class="font-bold text-slate-700 text-xs mt-1.5" id="details-meta-users">0 مستخدم</p>
      </div>
      <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-3 text-center">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-center gap-1">
          <i data-lucide="calendar-days" class="w-3 h-3"></i>
          تاريخ الإنشاء
        </p>
        <p class="font-bold text-slate-700 text-xs mt-1.5" dir="ltr" id="details-meta-created">—</p>
      </div>
      <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-3 text-center">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-center gap-1">
          <i data-lucide="clock" class="w-3 h-3"></i>
          آخر تحديث
        </p>
        <p class="font-bold text-slate-700 text-xs mt-1.5" id="details-meta-updated">—</p>
      </div>
    </div>

    {{-- Permissions summary --}}
    <div class="space-y-3">
      <h4 class="font-bold text-xs text-purple-600 flex items-center gap-1.5 border-b border-[#eeeef0] pb-2">
        <i data-lucide="key" class="w-3.5 h-3.5"></i>
        الصلاحيات الممنوحة
      </h4>
      <div id="details-permissions-list" class="flex flex-wrap gap-1.5">
        <span class="text-xs text-slate-400">جارٍ التحميل...</span>
      </div>
    </div>
  </div>

  {{-- Footer actions --}}
  <div class="flex gap-3 pt-2">
    <button
        type="button"
        data-close-modal
        class="flex-1 bg-[#f3f3f6] hover:bg-[#eeeef0] text-gray-600 font-bold py-3 px-4 rounded-xl text-xs transition-all flex items-center justify-center gap-2"
    >
      <i data-lucide="x" class="w-4 h-4"></i>
      إغلاق
    </button>
    <a
        id="details-permissions-link"
        href="#"
        class="flex-[2] bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-lg shadow-purple-500/20 flex items-center justify-center gap-2 text-center"
    >
      <i data-lucide="key" class="w-4 h-4"></i>
      إدارة الصلاحيات
    </a>
  </div>
</x-modal>

@endsection

@push('scripts')
<script>
  // ===== Role form modal: dynamic action + title + variant switching =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'role-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.id);

    if (isEdit) {
      // edit mode → PUT to update route
      var updateUrl = "{{ route('admin.roles.update', '__ID__') }}".replace('__ID__', data.id);
      form.setAttribute('action', updateUrl);
      var existingMethod = form.querySelector('input[name="_method"]');
      if (existingMethod) existingMethod.value = 'PUT';
      else {
        var m = document.createElement('input');
        m.type = 'hidden'; m.name = '_method'; m.value = 'PUT';
        m.setAttribute('data-dynamic', 'true');
        form.appendChild(m);
      }
      // update banner to amber
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل الدور';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }
      form.setAttribute('data-success-message', 'تم تحديث الدور بنجاح');
      // subtitle: role name
      var subtitleEl = modal.querySelector('.aracari-modal-panel .relative.z-10 p');
      if (subtitleEl) {
        subtitleEl.textContent = '';
        subtitleEl.innerHTML = '<span class="inline-block text-xs text-white/80 bg-slate-900/40 border border-white/10 px-2 py-0.5 rounded-full max-w-[260px] truncate align-middle" dir="ltr">' + (data.name || '') + '</span>';
        subtitleEl.style.display = '';
      }
      // info badge
      var infoBadge = modal.querySelector('#role-info-badge');
      if (infoBadge) {
        infoBadge.textContent = 'قابل للتعديل';
        infoBadge.className = 'text-[9px] font-bold px-2 py-0.5 rounded-full border text-amber-600 bg-amber-50 border-amber-200';
      }
      // section header
      var sectionHeader = modal.querySelector('#role-info-badge');
      if (sectionHeader) {
        sectionHeader.closest('div').querySelector('h4').className = 'font-bold text-xs text-amber-600 flex items-center gap-1.5';
      }
      // preview: switch to edit mode
      var previewIcon = modal.querySelector('#role-preview-icon');
      if (previewIcon) {
        previewIcon.className = 'w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/20 flex-shrink-0';
      }
      var previewBadge = modal.querySelector('#role-preview-badge');
      if (previewBadge) {
        previewBadge.textContent = 'جاري التعديل';
        previewBadge.className = 'text-[9px] font-bold px-2 py-0.5 rounded-full border bg-amber-50 text-amber-700 border-amber-200';
      }
      // show extra info
      var extraInfo = modal.querySelector('#role-extra-info');
      if (extraInfo) extraInfo.style.display = '';
      var infoUsers = modal.querySelector('#role-info-users');
      if (infoUsers) infoUsers.textContent = (data.users_count || 0) + ' مستخدم';
      var infoCreated = modal.querySelector('#role-info-created');
      if (infoCreated) infoCreated.textContent = data.created_at || '—';
      var infoUpdated = modal.querySelector('#role-info-updated');
      if (infoUpdated) infoUpdated.textContent = data.updated_at || '—';
      // show preview users
      var previewUsersWrap = modal.querySelector('#role-preview-users-wrap');
      if (previewUsersWrap) previewUsersWrap.style.display = '';
      var previewUsers = modal.querySelector('#role-preview-users');
      if (previewUsers) previewUsers.textContent = data.users_count || 0;
      // permissions button
      var permBtn = modal.querySelector('#role-permissions-btn');
      if (permBtn) {
        permBtn.style.display = '';
        permBtn.href = "{{ route('admin.roles.permissions', '__ID__') }}".replace('__ID__', data.id);
      }
      // cancel text
      var cancelText = modal.querySelector('#role-cancel-text');
      if (cancelText) cancelText.textContent = 'إلغاء';
      // submit button style
      var submitBtn = modal.querySelector('#role-submit-btn');
      if (submitBtn) {
        submitBtn.className = 'flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95 bg-amber-600 hover:bg-amber-700';
      }
      var submitText = modal.querySelector('#role-submit-text');
      if (submitText) submitText.textContent = 'حفظ التعديلات';
    } else {
      // create mode → POST to store route
      form.setAttribute('action', "{{ route('admin.roles.store') }}");
      var m = form.querySelector('input[name="_method"]');
      if (m) m.remove();
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة دور جديد';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30';
        badge.textContent = 'جديد';
      }
      form.setAttribute('data-success-message', 'تم حفظ الدور بنجاح');
      // subtitle
      var subtitleEl = modal.querySelector('.aracari-modal-panel .relative.z-10 p');
      if (subtitleEl) {
        subtitleEl.textContent = 'إنشاء دور جديد مع تحديد الصلاحيات';
        subtitleEl.style.display = '';
      }
      // info badge
      var infoBadge = modal.querySelector('#role-info-badge');
      if (infoBadge) {
        infoBadge.textContent = 'مطلوب';
        infoBadge.className = 'text-[9px] font-bold px-2 py-0.5 rounded-full border text-rose-600 bg-rose-50 border-rose-200';
      }
      // section header
      var sectionHeader = modal.querySelector('#role-info-badge');
      if (sectionHeader) {
        sectionHeader.closest('div').querySelector('h4').className = 'font-bold text-xs text-[#115128] flex items-center gap-1.5';
      }
      // preview: reset to create mode
      var previewIcon = modal.querySelector('#role-preview-icon');
      if (previewIcon) {
        previewIcon.className = 'w-12 h-12 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center shadow-lg shadow-teal-500/20 flex-shrink-0';
      }
      var previewBadge = modal.querySelector('#role-preview-badge');
      if (previewBadge) {
        previewBadge.textContent = 'جديد';
        previewBadge.className = 'text-[9px] font-bold px-2 py-0.5 rounded-full border bg-teal-50 text-teal-700 border-teal-200';
      }
      // hide extra info
      var extraInfo = modal.querySelector('#role-extra-info');
      if (extraInfo) extraInfo.style.display = 'none';
      // hide preview users
      var previewUsersWrap = modal.querySelector('#role-preview-users-wrap');
      if (previewUsersWrap) previewUsersWrap.style.display = 'none';
      // hide permissions button
      var permBtn = modal.querySelector('#role-permissions-btn');
      if (permBtn) permBtn.style.display = 'none';
      // cancel text
      var cancelText = modal.querySelector('#role-cancel-text');
      if (cancelText) cancelText.textContent = 'إعادة تعيين';
      // submit button style
      var submitBtn = modal.querySelector('#role-submit-btn');
      if (submitBtn) {
        submitBtn.className = 'flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95 bg-[#115128] hover:bg-emerald-950';
      }
      var submitText = modal.querySelector('#role-submit-text');
      if (submitText) submitText.textContent = 'حفظ الدور';
    }
    // re-render icons
    if (window.lucide) window.lucide.createIcons();

    // sync live preview after fields are filled
    var nameInput = modal.querySelector('#role-name');
    var previewName = modal.querySelector('#role-preview-name');
    var previewDesc = modal.querySelector('#role-preview-desc');
    if (nameInput && previewName) {
      previewName.textContent = nameInput.value.trim() || 'اسم الدور';
    }
    if (previewDesc) {
      var descInput = modal.querySelector('#role-description');
      previewDesc.textContent = descInput && descInput.value.trim() ? descInput.value.trim() : 'لا يوجد وصف';
    }
  });

  // ===== Live preview: name + description → preview elements =====
  (function() {
    var nameInput = document.getElementById('role-name');
    var descInput = document.getElementById('role-description');
    var previewName = document.getElementById('role-preview-name');
    var previewDesc = document.getElementById('role-preview-desc');
    if (!nameInput) return;
    nameInput.addEventListener('input', function() {
      if (previewName) previewName.textContent = nameInput.value.trim() || 'اسم الدور';
    });
    if (descInput && previewDesc) {
      descInput.addEventListener('input', function() {
        previewDesc.textContent = descInput.value.trim() || 'لا يوجد وصف';
      });
    }
  })();
</script>
@endpush
