@extends('layouts.provider')

@section('title', 'أركاري | إدارة الموظفين')
@section('header_title', 'إدارة الموظفين')

@section('content')

@php
    // ===== الإحصائيات المحلية من مجموعة الموظفين =====
    $totalStaff    = $staffMembers->count();
    $activeStaff   = $staffMembers->filter(fn($m) => $m->status_id === \App\Constants\UserStatus::ACTIVE)->count();
    $inactiveStaff = $totalStaff - $activeStaff;

    // ===== الفروع والأدوار المتاحة للنموذج =====
    $currentUser = auth()->user();
    $branches = $currentUser->getAccessibleBranches();
    $roles    = app(\App\Services\ProviderStaffService::class)->getAllowedRoles($currentUser);

    // ===== التحقق من الصلاحيات =====
    $canCreate = $currentUser && $currentUser->hasPermission('provider:staff:create');
    $canEdit   = $currentUser && $currentUser->hasPermission('provider:staff:edit');
    $canDelete = $currentUser && $currentUser->hasPermission('provider:staff:delete');

    // ===== خريطة تسميات الأدوار بالعربية =====
    $roleLabels = [
        'provider_staff' => 'موظف عام',
        'branch_manager' => 'مدير فرع',
        'branch_staff'   => 'موظف فرع',
    ];

    // ===== خريطة ألوان شارات الأدوار =====
    $roleBadgeColors = [
        'provider_staff' => 'bg-teal-50 text-teal-700 border-teal-200',
        'branch_manager' => 'bg-blue-50 text-blue-700 border-blue-200',
        'branch_staff'   => 'bg-amber-50 text-amber-700 border-amber-200',
    ];
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== رأس الصفحة ===== --}}
  <x-page-header
      icon="users"
      title="الموظفون"
      :count="$totalStaff"
      countLabel="موظف"
      description="إدارة وعرض جميع الموظفين المسجلين في النظام"
      descriptionIcon="users"
  >
    @if($canCreate)
      <button
          type="button"
          data-create-modal="staff-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إضافة موظف جديد
      </button>
    @endif
  </x-page-header>

  {{-- ===== المنطقة القابلة للتحديث: الإحصائيات + الجدول ===== --}}
  <div id="aracari-refreshable" class="space-y-6">

  {{-- ===== بطاقات الإحصائيات (3 بطاقات) ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 lg:gap-6">
    <x-stat-card :value="$totalStaff" label="إجمالي الموظفين" icon="users" variant="slate" badge="موظف" badgeIcon="users" />
    <x-stat-card :value="$activeStaff" label="موظفون نشطون" icon="user-check" variant="emerald" badge="نشط" badgeIcon="user-check" />
    <x-stat-card :value="$inactiveStaff" label="غير نشطون" icon="user-x" variant="rose" badge="متوقف" badgeIcon="user-x" />
  </div>

  {{-- ===== بطاقة جدول البيانات ===== --}}
  <x-data-table-card :searchable="true" :searchMode="'client'" searchPlaceholder="ابحث بالاسم أو البريد أو الهاتف..." :footerInfo="'عرض ' . $staffMembers->count() . ' موظف'">
    <x-slot name="thead">
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الاسم</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">التواصل</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الدور</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الفروع</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
    </x-slot>

    <x-slot name="rows">
      @forelse($staffMembers as $member)
        @php
          // اسم الدور
          $memberRole     = $member->roles->first()?->name;
          $memberRoleLabel = $roleLabels[$memberRole] ?? ($member->roles->first()?->description ?? '—');
          $memberRoleColor = $roleBadgeColors[$memberRole] ?? 'bg-slate-100 text-slate-600 border-slate-200';

          // حالة التفعيل
          $isActive = $member->status_id === \App\Constants\UserStatus::ACTIVE;

          // الحرف الأول من الاسم
          $initial = mb_substr($member->name, 0, 1, 'UTF-8');
        @endphp

        <tr class="hover:bg-slate-50/50 transition-colors group">

          {{-- الاسم --}}
          <td data-searchable class="px-6 py-4">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-full bg-gradient-to-br from-cyan-500 to-cyan-600 flex items-center justify-center text-white font-bold text-xs flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                {{ $initial }}
              </div>
              <span class="font-bold text-slate-800 text-sm">{{ $member->name }}</span>
            </div>
          </td>

          {{-- التواصل --}}
          <td data-searchable class="px-6 py-4">
            <div class="text-[11px] text-slate-500 flex items-center gap-1">
              <i data-lucide="mail" class="w-3 h-3 flex-shrink-0"></i>
              <span dir="ltr" class="truncate max-w-[180px]">{{ $member->email }}</span>
            </div>
            <div class="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
              <i data-lucide="phone" class="w-3 h-3 flex-shrink-0"></i>
              <span dir="ltr">{{ $member->phone_number }}</span>
            </div>
          </td>

          {{-- الدور --}}
          <td class="px-6 py-4 text-center">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $memberRoleColor }}">
              <i data-lucide="shield-check" class="w-3 h-3"></i>
              {{ $memberRoleLabel }}
            </span>
          </td>

          {{-- الفروع --}}
          <td class="px-6 py-4 text-center">
            @if($member->branches->count() > 0)
              <div class="flex flex-wrap gap-1 justify-center max-w-[160px]">
                @foreach($member->branches->take(3) as $branch)
                  <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-slate-100 text-slate-600">
                    {{ $branch->city->name ?? 'فرع #' . $branch->id }}
                  </span>
                @endforeach
                @if($member->branches->count() > 3)
                  <span class="px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-slate-200 text-slate-500">
                    +{{ $member->branches->count() - 3 }}
                  </span>
                @endif
              </div>
            @else
              <span class="text-slate-300 text-xs">—</span>
            @endif
          </td>

          {{-- الحالة --}}
          <td class="px-6 py-4 text-center">
            @if($isActive)
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                نشط
              </span>
            @else
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                غير نشط
              </span>
            @endif
          </td>

          {{-- الإجراءات --}}
          <td class="px-6 py-4 text-center">
            <div class="flex items-center justify-center gap-1.5">
              @if($canEdit)
                <x-action-button
                    variant="edit"
                    data-edit-modal="staff-form-modal"
                    data-edit-json="{{ json_encode([
                        'uuid'        => $member->uuid,
                        'name'        => $member->name,
                        'email'       => $member->email,
                        'phone_number'=> $member->phone_number,
                        'role'        => $memberRole,
                        'branch_ids'  => $member->branches->pluck('id')->toArray(),
                    ], JSON_UNESCAPED_UNICODE) }}"
                    :ariaLabel="'تعديل ' . $member->name"
                />
              @endif
              @if($canDelete)
                <x-action-button
                    variant="delete"
                    data-delete-url="{{ route('admin.provider.staff.destroy', $member->uuid) }}"
                    data-delete-label="{{ $member->name }}"
                />
              @endif
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state
            icon="users"
            :title="request('search') ? 'لا توجد نتائج مطابقة' : 'لا يوجد موظفون'"
            :message="request('search') ? 'جرّب تعديل كلمات البحث' : 'يمكنك إضافة موظف جديد من زر الإضافة'"
            :colspan="6"
        >
          @if(!request('search') && $canCreate)
            <button
                type="button"
                data-create-modal="staff-form-modal"
                class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
            >
              <i data-lucide="plus" class="w-4 h-4"></i>
              إضافة موظف جديد
            </button>
          @endif
        </x-empty-state>
      @endforelse
    </x-slot>

  </x-data-table-card>

  </div>{{-- نهاية #aracari-refreshable -->}}

</div>

{{-- ===== نافذة نموذج الموظف (إنشاء + تعديل) ===== --}}
<x-form-modal
    id="staff-form-modal"
    title="إضافة موظف جديد"
    :variant="'create'"
    :action="route('admin.provider.staff.store')"
    method="POST"
    successMessage="تم حفظ الموظف بنجاح"
>

  {{-- ===== الاسم الكامل ===== --}}
  <x-form-field name="name" label="الاسم الكامل" icon="user" required>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="user" class="w-4 h-4"></i>
      </div>
      <input
          type="text"
          name="name"
          id="staff_name"
          required
          placeholder="مثال: أحمد محمد"
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
      >
    </div>
  </x-form-field>

  {{-- ===== البريد الإلكتروني + رقم الهاتف ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    {{-- البريد الإلكتروني --}}
    <div class="space-y-1.5">
      <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
        <i data-lucide="mail" class="w-3.5 h-3.5"></i>
        البريد الإلكتروني <span class="text-rose-500">*</span>
      </label>
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="mail" class="w-4 h-4"></i>
        </div>
        <input
            type="email"
            name="email"
            id="staff_email"
            required
            dir="ltr"
            placeholder="email@example.com"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left"
        >
      </div>
    </div>

    {{-- رقم الهاتف --}}
    <div class="space-y-1.5">
      <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
        <i data-lucide="phone" class="w-3.5 h-3.5"></i>
        رقم الهاتف <span class="text-rose-500">*</span>
      </label>
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="phone" class="w-4 h-4"></i>
        </div>
        <input
            type="tel"
            name="phone_number"
            id="staff_phone_number"
            required
            dir="ltr"
            placeholder="777123456"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left"
        >
      </div>
    </div>
  </div>

  {{-- ===== كلمة المرور + التأكيد ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    {{-- كلمة المرور --}}
    <div class="space-y-1.5">
      <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
        <i data-lucide="lock" class="w-3.5 h-3.5"></i>
        كلمة المرور <span class="text-rose-500" id="staff-password-required-star">*</span>
      </label>
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="lock" class="w-4 h-4"></i>
        </div>
        <input
            type="password"
            name="password"
            id="staff_password"
            required
            placeholder="الحد الأدنى 8 أحرف"
            data-edit-optional
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
        >
      </div>
    </div>

    {{-- تأكيد كلمة المرور --}}
    <div class="space-y-1.5">
      <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
        <i data-lucide="lock" class="w-3.5 h-3.5"></i>
        تأكيد كلمة المرور <span class="text-rose-500" id="staff-password-confirmation-required-star">*</span>
      </label>
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="lock" class="w-4 h-4"></i>
        </div>
        <input
            type="password"
            name="password_confirmation"
            id="staff_password_confirmation"
            required
            placeholder="أعد إدخال كلمة المرور"
            data-edit-optional
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
        >
      </div>
    </div>
  </div>

  {{-- ===== الدور الوظيفي ===== --}}
  <x-form-field name="role" label="الدور الوظيفي" icon="shield-check" required>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="shield-check" class="w-4 h-4"></i>
      </div>
      <select
          name="role"
          id="staff_role"
          required
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none cursor-pointer"
      >
        <option value="">اختر الدور</option>
        @foreach($roles as $r)
          <option value="{{ $r->name }}">{{ $roleLabels[$r->name] ?? $r->description ?? $r->name }}</option>
        @endforeach
      </select>
      <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
        <i data-lucide="chevron-down" class="w-4 h-4"></i>
      </div>
    </div>
    {{-- وصف الدور المختار --}}
    <p class="text-[10px] text-slate-400 mt-1.5 flex items-center gap-1" id="staff-role-hint">
      <i data-lucide="info" class="w-3 h-3"></i>
      اختر الدور المناسب للموظف
    </p>
  </x-form-field>

  {{-- ===== الفروع المرتبطة (يظهر فقط عندما الدور ≠ موظف عام) ===== --}}
  <div id="staff-branches-section" class="space-y-2 hidden">
    <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
      <i data-lucide="building-2" class="w-3.5 h-3.5"></i>
      الفروع المرتبطة <span class="text-rose-500" id="staff-branches-required-star">*</span>
    </label>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2" id="staff-branches-grid">
      @foreach($branches as $branch)
        <label class="staff-branch-checkbox flex items-center gap-2.5 p-3 rounded-xl cursor-pointer transition-all border bg-slate-50/70 border-slate-200 hover:bg-slate-100" data-branch-id="{{ $branch->id }}">
          <input
              type="checkbox"
              name="branch_ids[]"
              value="{{ $branch->id }}"
              class="w-4 h-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 cursor-pointer"
          >
          <div>
            <span class="text-xs font-bold text-slate-700 block">{{ $branch->city->name ?? 'فرع #' . $branch->id }}</span>
            <span class="text-[10px] text-slate-400">{{ $branch->address ?? 'فرع #' . $branch->id }}</span>
          </div>
        </label>
      @endforeach
    </div>
    @if($branches->isEmpty())
      <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-3 text-[11px] text-amber-600 flex items-start gap-2">
        <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
        لا توجد فروع متاحة حالياً. يرجى إنشاء فرع أولاً.
      </div>
    @endif
  </div>

  {{-- ===== تنبيه اختيار الفروع ===== --}}
  <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-3">
    <div class="flex items-start gap-2.5">
      <i data-lucide="info" class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5"></i>
      <span class="text-[11px] text-amber-600">
        عند اختيار دور «موظف عام» لن يحتاج الموظف لربطه بفرع محدد. أما الأدوار الأخرى فتتطلب تحديد فرع واحد على الأقل.
      </span>
    </div>
  </div>

</x-form-modal>

@endsection

@push('scripts')
<script>
  // ===== تحويل بيانات الفروع إلى JSON لاستخدامها في الجافاسكربت =====
  var staffBranchesData = {{ json_encode($branches->map(fn($b) => ['id' => $b->id, 'name' => $b->city->name ?? 'فرع #' . $b->id, 'address' => $b->address ?? '']), JSON_UNESCAPED_UNICODE) }};

  // ===== وصف كل دور بالعربية =====
  var staffRoleDescriptions = {
    'provider_staff': 'موظف عام — لا يحتاج لربط بفرع محدد',
    'branch_manager': 'مدير فرع — إدارة كاملة للفرع المحدد',
    'branch_staff':   'موظف فرع — صلاحيات محدودة ضمن الفرع',
  };

  // ===== إظهار/إخفاء قسم الفروع حسب الدور المختار =====
  document.getElementById('staff_role').addEventListener('change', function() {
    var roleVal = this.value;
    var branchesSection = document.getElementById('staff-branches-section');
    var hintEl = document.getElementById('staff-role-hint');

    if (roleVal && roleVal !== 'provider_staff') {
      branchesSection.classList.remove('hidden');
    } else {
      branchesSection.classList.add('hidden');
    }

    // تحديث وصف الدور
    if (hintEl && roleVal && staffRoleDescriptions[roleVal]) {
      hintEl.innerHTML = '<i data-lucide="info" class="w-3 h-3"></i> ' + staffRoleDescriptions[roleVal];
      if (window.lucide) window.lucide.createIcons();
    } else if (hintEl) {
      hintEl.innerHTML = '<i data-lucide="info" class="w-3 h-3"></i> اختر الدور المناسب للموظف';
      if (window.lucide) window.lucide.createIcons();
    }
  });

  // ===== تفعيل/تعطيل حالة تحديد خانات الفروع =====
  document.querySelectorAll('.staff-branch-checkbox').forEach(function(label) {
    var checkbox = label.querySelector('input[type="checkbox"]');
    if (!checkbox) return;

    checkbox.addEventListener('change', function() {
      if (this.checked) {
        label.classList.remove('bg-slate-50/70', 'border-slate-200', 'hover:bg-slate-100');
        label.classList.add('bg-teal-50/70', 'border-teal-200');
      } else {
        label.classList.remove('bg-teal-50/70', 'border-teal-200');
        label.classList.add('bg-slate-50/70', 'border-slate-200', 'hover:bg-slate-100');
      }
    });
  });

  // ===== نافذة الموظف: تبديل الإنشاء/التعديل + ملء الحقول =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'staff-form-modal') return;

    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.uuid);

    // ===== 1. تبديل حالة حقول كلمة المرور =====
    var passwordFields = modal.querySelectorAll('[data-edit-optional]');
    var passwordStar1 = document.getElementById('staff-password-required-star');
    var passwordStar2 = document.getElementById('staff-password-confirmation-required-star');

    passwordFields.forEach(function(f) {
      if (isEdit) {
        f.removeAttribute('required');
      } else {
        f.setAttribute('required', '');
      }
    });
    if (passwordStar1) passwordStar1.style.display = isEdit ? 'none' : '';
    if (passwordStar2) passwordStar2.style.display = isEdit ? 'none' : '';

    // ===== 2. تبديل رابط الإجراء والعنوان =====
    if (isEdit) {
      var updateUrl = "{{ route('admin.provider.staff.update', '__UUID__') }}".replace('__UUID__', data.uuid);
      form.setAttribute('action', updateUrl);

      var existingMethod = form.querySelector('input[name="_method"]');
      if (existingMethod) existingMethod.value = 'PUT';
      else {
        var m = document.createElement('input');
        m.type = 'hidden'; m.name = '_method'; m.value = 'PUT';
        m.setAttribute('data-dynamic', 'true');
        form.appendChild(m);
      }

      // تحديث الشريط العلوي إلى وضع التعديل (عنبري)
      var banner = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (banner) banner.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';

      var titleEl = modal.querySelector('.aracari-modal-panel h2');
      if (titleEl) titleEl.innerHTML = '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل بيانات الموظف';

      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }

      // وصف تحت الشريط
      var subtitle = modal.querySelector('.aracari-modal-panel p');
      if (subtitle) subtitle.textContent = data.name || '';

      form.setAttribute('data-success-message', 'تم تحديث الموظف بنجاح');
    } else {
      form.setAttribute('action', "{{ route('admin.provider.staff.store') }}");
      var m = form.querySelector('input[name="_method"]');
      if (m) m.remove();

      var banner = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (banner) banner.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';

      var titleEl = modal.querySelector('.aracari-modal-panel h2');
      if (titleEl) titleEl.innerHTML = '<i data-lucide="user-plus" class="w-5 h-5"></i> إضافة موظف جديد';

      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30';
        badge.textContent = 'جديد';
      }

      var subtitle = modal.querySelector('.aracari-modal-panel p');
      if (subtitle) subtitle.textContent = 'أدخل بيانات الموظف لمنحه صلاحيات الوصول';

      form.setAttribute('data-success-message', 'تم حفظ الموظف بنجاح');
    }

    // ===== 3. ملء حقول النموذج في وضع التعديل =====
    var nameInput = modal.querySelector('#staff_name');
    var emailInput = modal.querySelector('#staff_email');
    var phoneInput = modal.querySelector('#staff_phone_number');
    var roleSelect = modal.querySelector('#staff_role');

    if (nameInput) nameInput.value = isEdit ? (data.name || '') : '';
    if (emailInput) emailInput.value = isEdit ? (data.email || '') : '';
    if (phoneInput) phoneInput.value = isEdit ? (data.phone_number || '') : '';
    if (roleSelect) roleSelect.value = isEdit ? (data.role || '') : '';

    // ===== 4. إظهار/إخفاء الفروع حسب الدور + تحديد الفروع المربوطة =====
    var branchesSection = document.getElementById('staff-branches-section');
    var showBranches = isEdit ? (data.role && data.role !== 'provider_staff') : false;

    if (showBranches) {
      branchesSection.classList.remove('hidden');
    } else {
      branchesSection.classList.add('hidden');
    }

    // تحديد خانات الفروع المربوطة في وضع التعديل
    var editBranchIds = isEdit ? (data.branch_ids || []) : [];
    document.querySelectorAll('.staff-branch-checkbox').forEach(function(label) {
      var checkbox = label.querySelector('input[type="checkbox"]');
      var branchId = parseInt(label.getAttribute('data-branch-id'));
      var isChecked = editBranchIds.indexOf(branchId) !== -1;

      if (checkbox) checkbox.checked = isChecked;

      if (isChecked) {
        label.classList.remove('bg-slate-50/70', 'border-slate-200', 'hover:bg-slate-100');
        label.classList.add('bg-teal-50/70', 'border-teal-200');
      } else {
        label.classList.remove('bg-teal-50/70', 'border-teal-200');
        label.classList.add('bg-slate-50/70', 'border-slate-200', 'hover:bg-slate-100');
      }
    });

    // ===== 5. تحديث وصف الدور =====
    var hintEl = document.getElementById('staff-role-hint');
    var currentRole = isEdit ? (data.role || '') : (roleSelect ? roleSelect.value : '');
    if (hintEl && currentRole && staffRoleDescriptions[currentRole]) {
      hintEl.innerHTML = '<i data-lucide="info" class="w-3 h-3"></i> ' + staffRoleDescriptions[currentRole];
    } else if (hintEl) {
      hintEl.innerHTML = '<i data-lucide="info" class="w-3 h-3"></i> اختر الدور المناسب للموظف';
    }

    // ===== 6. إعادة تصيير أيقونات لوسيد =====
    if (window.lucide) window.lucide.createIcons();
  });
</script>
@endpush
