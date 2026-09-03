@extends('layouts.admin')

@section('title', 'أركاري | إدارة المستخدمين')
@section('header_title', 'إدارة مستخدمين النظام')

@section('content')

@php
    // Fetch relations needed for form selects (controller index() doesn't pass them)
    $statuses = \App\Models\UserStatus::all();
    $types = \App\Models\TypeOfUser::all();
    $roles = \App\Models\Role::orderBy('name')->get();

    // Local stats from paginator collection
    $totalUsers    = $users->total();
    $approvedUsers = $users->getCollection()->filter(fn($u) => $u->is_approved)->count();
    $pendingUsers  = $users->getCollection()->filter(fn($u) => !$u->is_approved)->count();
    $recentUsers   = $users->getCollection()->filter(fn($u) => $u->created_at && $u->created_at->gte(now()->subDays(30)))->count();

    // Eager-load relations to prevent N+1 queries in the table loop
    $users->getCollection()->loadCount(['trips', 'bookings']);
    $users->getCollection()->load(['status', 'roles']);

    // Permission checks (real authorization, not just route existence)
    $user        = auth()->user();
    $canView     = $user && $user->hasPermission('users:view');
    $canCreate   = $user && $user->hasPermission('users:create');
    $canEdit     = $user && $user->hasPermission('users:edit');
    $canDelete   = $user && $user->hasPermission('users:delete');
    $canApprove  = $user && $user->hasPermission('users:approve');
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="users"
      title="المستخدمون"
      :count="$totalUsers"
      countLabel="حساب"
      description="عرض وإدارة قاعدة بيانات المستخدمين، صلاحياتهم، وحالة اعتماد الحسابات والمزودين."
      descriptionIcon="users"
  >
    @if($canCreate)
      <button
          type="button"
          data-create-modal="user-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إضافة مستخدم جديد
      </button>
    @endif
  </x-page-header>

  {{-- ===== Refreshable region: stats + table (refreshed together after CRUD) ===== --}}
  <div id="aracari-refreshable" class="space-y-6">

  {{-- ===== Stat Cards ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 lg:gap-6">
    <x-stat-card
        :value="$totalUsers"
        label="إجمالي الحسابات"
        icon="users"
        variant="slate"
        badge="+12%"
        badgeIcon="trending-up"
        caption="عن الشهر الماضي"
    />
    <x-stat-card
        :value="$approvedUsers"
        label="معتمدة"
        icon="shield-check"
        variant="emerald"
        badge="نشطة"
        badgeIcon="check-circle"
    />
    <x-stat-card
        :value="$pendingUsers"
        label="بانتظار الموافقة"
        icon="hourglass"
        variant="amber"
        badge="قيد الانتظار"
        badgeIcon="hourglass"
    />
    <x-stat-card
        :value="$recentUsers"
        label="تسجيلات حديثة"
        icon="sparkles"
        variant="blue"
        badge="هذا الشهر"
        badgeIcon="sparkles"
    />
  </div>

  {{-- ===== Data Table Card ===== --}}
  <x-data-table-card
      :searchable="true"
      :searchMode="'server'"
      searchPlaceholder="ابحث بالاسم، البريد، أو الجوال..."
      :footerInfo="'عرض ' . $users->count() . ' من أصل ' . $users->total() . ' حساب'"
  >
    <x-slot name="thead">
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المستخدم</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">التسجيل والاتصال</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الدور الوظيفي</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة والأمان</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
    </x-slot>

    <x-slot name="rows">
      @forelse($users as $user)
        @php
          // Role badge class — match against multiple needles via a foreach loop
          // (PHP 8.2 rejects arrays passed to str_contains(); loop over needles instead)
          $roleName = (string)($user->roles->first()?->name ?? $user->user_type_name ?? '');
          $roleBadgeClass = 'bg-slate-100/80 text-slate-700 border-slate-200/50'; // default
          $roleGroups = [
              ['needles' => ['مسؤول نظام', 'مدير نظام', 'admin'],         'class' => 'bg-rose-100/80 text-rose-700 border-rose-200/50'],
              ['needles' => ['مزود خدمة', 'provider'],                     'class' => 'bg-indigo-100/80 text-indigo-700 border-indigo-200/50'],
              ['needles' => ['مدير فرع', 'branch_manager'],                'class' => 'bg-blue-100/80 text-blue-700 border-blue-200/50'],
              ['needles' => ['مسؤول حجز', 'booking_agent'],                'class' => 'bg-amber-100/80 text-amber-700 border-amber-200/50'],
              ['needles' => ['عميل', 'customer'],                          'class' => 'bg-teal-100/80 text-teal-700 border-teal-200/50'],
          ];
          foreach ($roleGroups as $group) {
              foreach ($group['needles'] as $needle) {
                  if (str_contains($roleName, (string) $needle)) {
                      $roleBadgeClass = $group['class'];
                      break 2;
                  }
              }
          }

          // Status badge
          $isTrashed = $user->deleted_at !== null;
          $statusCode = $user->status?->status_code;
          $statusBadge = match(true) {
              $isTrashed => ['bg' => 'bg-rose-50 text-rose-700 border-rose-200', 'text' => 'محذوف', 'dot' => 'bg-rose-500'],
              $statusCode === 'active' => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'text' => 'نشط', 'dot' => 'bg-emerald-500'],
              $statusCode === 'inactive' => ['bg' => 'bg-slate-50 text-slate-700 border-slate-200', 'text' => 'غير نشط', 'dot' => 'bg-slate-400'],
              $statusCode === 'pending' => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'text' => 'قيد الانتظار', 'dot' => 'bg-amber-500'],
              $statusCode === 'suspended' => ['bg' => 'bg-rose-50 text-rose-700 border-rose-200', 'text' => 'موقوف', 'dot' => 'bg-rose-500'],
              default => ['bg' => 'bg-slate-50 text-slate-700 border-slate-200', 'text' => 'غير محدد', 'dot' => 'bg-slate-400'],
          };

          // Approval badge
          $approvalBadge = $user->is_approved
              ? ['bg' => 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20', 'text' => 'معتمد من الإدارة']
              : ['bg' => 'bg-amber-500/10 text-amber-600 border-amber-500/20', 'text' => 'قيد المراجعة'];

          // Phone verified badge
          $phoneBadge = $user->phone_verified_at
              ? ['bg' => 'bg-blue-500/10 text-blue-600 border-blue-500/20', 'text' => 'هاتف مفعل']
              : ['bg' => 'bg-slate-500/10 text-slate-500 border-slate-500/20', 'text' => 'هاتف غير مفعل'];

          // Avatar initial
          $initial = mb_substr($user->name, 0, 1, 'UTF-8');
        @endphp

        <tr class="hover:bg-slate-50/50 transition-colors group">

          {{-- المستخدم — searchable cell --}}
          <td data-searchable class="px-6 py-4">
            <div class="flex items-center gap-3">
              <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-600 to-teal-700 text-white flex items-center justify-center font-black text-lg shadow-md shadow-emerald-500/10 group-hover:scale-105 transition-transform duration-300">
                {{ $initial }}
              </div>
              <div>
                <div class="font-bold text-slate-800 text-sm">{{ $user->name }}</div>
                <div class="text-[10px] font-bold text-slate-400 flex items-center gap-1 mt-0.5">
                  <i data-lucide="mail" class="w-3 h-3 text-slate-400"></i>
                  <span>{{ $user->email ?? 'لا يوجد بريد مسجل' }}</span>
                </div>
              </div>
            </div>
          </td>

          {{-- التسجيل والاتصال — searchable cell --}}
          <td data-searchable class="px-6 py-4">
            <div class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
              <i data-lucide="phone" class="w-3.5 h-3.5 text-slate-400"></i>
              <span dir="ltr">{{ $user->phone_number }}</span>
            </div>
            <div class="text-[10px] font-bold text-slate-400 flex items-center gap-1.5 mt-1">
              <i data-lucide="calendar" class="w-3 h-3 text-slate-400"></i>
              <span>{{ optional($user->created_at)->translatedFormat('j F، Y') ?? '—' }}</span>
            </div>
          </td>

          {{-- الدور الوظيفي — NOT searchable (badge, not text the user searches for) --}}
          <td class="px-6 py-4 text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-bold border {{ $roleBadgeClass }}">
              <i data-lucide="shield" class="w-3 h-3"></i>
              {{ $roleName }}
            </span>
          </td>

          {{-- الحالة والأمان — NOT searchable (badges only) --}}
          <td class="px-6 py-4">
            <div class="flex flex-col items-center gap-1.5">
              {{-- Status badge --}}
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9px] font-bold border {{ $statusBadge['bg'] }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $statusBadge['dot'] }}"></span>
                <span>{{ $statusBadge['text'] }}</span>
              </span>
              {{-- Approval badge --}}
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[8px] font-bold border {{ $approvalBadge['bg'] }}">
                <i data-lucide="check" class="w-2.5 h-2.5"></i>
                <span>{{ $approvalBadge['text'] }}</span>
              </span>
              {{-- Phone verified badge --}}
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[8px] font-bold border {{ $phoneBadge['bg'] }}">
                <i data-lucide="phone" class="w-2.5 h-2.5"></i>
                <span>{{ $phoneBadge['text'] }}</span>
              </span>
            </div>
          </td>

          {{-- الإجراءات — NOT searchable (buttons only) --}}
          <td class="px-6 py-4 text-center">
            <div class="flex items-center justify-center gap-1.5">
              @if($canView)
                <x-action-button
                    variant="view"
                    data-view-modal="user-details-modal"
                    data-view-json="{{ json_encode([
                        'uuid' => $user->uuid,
                        'name' => $user->name,
                        'phone_number' => $user->phone_number,
                        'email' => $user->email,
                        'type_name' => $user->user_type_name,
                        'role_name' => $user->roles->first()?->name ?? $user->user_type_name ?? '—',
                        'status_name' => $user->status?->status_name ?? '—',
                        'status_code' => $user->status?->status_code ?? '',
                        'is_approved' => (int) $user->is_approved,
                        'phone_verified_at' => $user->phone_verified_at ? $user->phone_verified_at->translatedFormat('j F، Y') : null,
                        'is_phone_verified' => $user->phone_verified_at ? 1 : 0,
                        'created_at' => optional($user->created_at)->translatedFormat('j F، Y'),
                        'is_provider' => $user->isProvider() ? 1 : 0,
                        'company_name' => $user->providerService?->name ?? ($user->name ?? '---'),
                        'cr_number' => $user->providerService?->cr_number ?? '---',
                        'born_country' => $user->providerService?->born_country ?? 'اليمن',
                        'website' => $user->providerService?->website ?? '',
                        'trips_count' => $user->trips_count ?? 0,
                        'bookings_count' => $user->bookings_count ?? 0,
                        'is_trashed' => $isTrashed ? 1 : 0,
                    ], JSON_UNESCAPED_UNICODE) }}"
                    :ariaLabel="'عرض ' . $user->name"
                />
              @endif
              @if($canEdit && !$isTrashed)
                <x-action-button
                    variant="edit"
                    data-edit-modal="user-form-modal"
                    data-edit-json="{{ json_encode([
                        'uuid' => $user->uuid,
                        'name' => $user->name,
                        'phone_number' => $user->phone_number,
                        'country_code' => $user->country_code ?? '967',
                        'email' => $user->email,
                        'status_id' => $user->status_id,
                        'type_user_code' => $user->type_user_code,
                        'is_approved' => $user->is_approved,
                        'role_id' => $user->roles->first()?->id,
                    ], JSON_UNESCAPED_UNICODE) }}"
                    :ariaLabel="'تعديل ' . $user->name"
                />
              @endif
              @if($canDelete)
                @if(!$isTrashed)
                  <x-action-button
                      variant="delete"
                      data-delete-url="{{ route('admin.users.destroy', $user->uuid) }}"
                      data-delete-label="{{ $user->name }}"
                  />
                @else
                  {{-- Restore button for soft-deleted users --}}
                  <button
                      type="button"
                      data-restore-url="{{ route('admin.users.restore', $user->uuid) }}"
                      data-restore-label="{{ $user->name }}"
                      class="w-9 h-9 rounded-xl bg-white border border-emerald-200 text-emerald-600 hover:bg-emerald-50 hover:border-emerald-300 transition-all duration-300 shadow-sm flex items-center justify-center"
                      title="استعادة المستخدم"
                      aria-label="استعادة {{ $user->name }}"
                  >
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                  </button>
                @endif
              @endif
            </div>
          </td>

        </tr>
      @empty
        <x-empty-state
            icon="users"
            :title="request('search') ? 'لا توجد نتائج مطابقة' : 'لا توجد حسابات مسجلة'"
            :message="request('search') ? 'جرّب تعديل كلمات البحث' : 'أضف مستخدم جديد لتتمكن من إدارة الحسابات والصلاحيات'"
            :colspan="5"
        >
          @if(!request('search') && $canCreate)
            <button
                type="button"
                data-create-modal="user-form-modal"
                class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
            >
              <i data-lucide="plus" class="w-4 h-4"></i>
              إضافة مستخدم جديد
            </button>
          @endif
        </x-empty-state>
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$users" />
    </x-slot>
  </x-data-table-card>

  </div>{{-- end #aracari-refreshable --}}

</div>

{{-- ===== User Form Modal (Create + Edit) ===== --}}
<x-form-modal
    id="user-form-modal"
    title="إضافة مستخدم جديد"
    :variant="'create'"
    :action="route('admin.users.store')"
    method="POST"
    successMessage="تم حفظ المستخدم بنجاح"
>
  {{-- الاسم الكامل --}}
  <x-form-field name="name" label="الاسم الكامل" icon="user" required>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="user" class="w-4 h-4"></i>
      </div>
      <input
          type="text"
          name="name"
          id="name"
          required
          placeholder="مثال: أحمد محمد"
          value="{{ old('name') }}"
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
      >
    </div>
  </x-form-field>

  {{-- رقم الهاتف الجوال: رمز الدولة + الرقم في سطر واحد --}}
  <div class="space-y-1.5">
    <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
      <i data-lucide="phone" class="w-3.5 h-3.5"></i>
      رقم الهاتف الجوال <span class="text-rose-500">*</span>
    </label>
    <div class="flex gap-2">
      {{-- رمز الدولة (ضيق) --}}
      <div class="relative group">
        <select
            name="country_code"
            id="country_code"
            required
            class="w-28 bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-2 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-center appearance-none cursor-pointer"
        >
          <option value="">الدولة</option>
          @foreach(\App\Constants\CountryCodes::all() as $code => $name)
            <option value="{{ $code }}" {{ old('country_code') == $code ? 'selected' : '' }}>(+{{ $code }})</option>
          @endforeach
        </select>
        <div class="absolute inset-y-0 left-2 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
        </div>
      </div>
      {{-- رقم الجوال (مرن) --}}
      <div class="relative group flex-1">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="phone" class="w-4 h-4"></i>
        </div>
        <input
            type="tel"
            name="phone_number"
            id="phone_number"
            required
            dir="ltr"
            placeholder="7XXXXXXXX"
            value="{{ old('phone_number') }}"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left font-mono"
        >
      </div>
    </div>
    <p class="text-[10px] text-slate-400">أدخل الرقم بدون رمز الدولة (سيتم إضافته تلقائياً)</p>
    <p class="text-[11px] text-rose-600 font-bold" data-error-for="phone_number"></p>
    <p class="text-[11px] text-rose-600 font-bold" data-error-for="country_code"></p>
  </div>

  {{-- البريد الإلكتروني --}}
  <x-form-field name="email" label="البريد الإلكتروني" icon="mail">
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="mail" class="w-4 h-4"></i>
      </div>
      <input
          type="email"
          name="email"
          id="email"
          dir="ltr"
          placeholder="email@example.com"
          value="{{ old('email') }}"
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left"
      >
    </div>
  </x-form-field>

  {{-- كلمة المرور --}}
  <x-form-field name="password" label="كلمة المرور" icon="lock" required id="password-field">
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="lock" class="w-4 h-4"></i>
      </div>
      <input
          type="password"
          name="password"
          id="password"
          required
          placeholder="الحد الأدنى 8 أحرف"
          data-edit-optional
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
      >
    </div>
  </x-form-field>

  {{-- تأكيد كلمة المرور --}}
  <x-form-field name="password_confirmation" label="تأكيد كلمة المرور" icon="lock" required id="password-confirmation-field">
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="lock" class="w-4 h-4"></i>
      </div>
      <input
          type="password"
          name="password_confirmation"
          id="password_confirmation"
          required
          placeholder="أعد إدخال كلمة المرور"
          data-edit-optional
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
      >
    </div>
  </x-form-field>

  {{-- حالة الحساب --}}
  <x-form-field name="status_id" label="حالة الحساب" icon="activity" required>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="activity" class="w-4 h-4"></i>
      </div>
      <select
          name="status_id"
          id="status_id"
          required
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none"
      >
        <option value="">اختر الحالة</option>
        @foreach($statuses as $s)
          <option value="{{ $s->id }}" {{ (old('status_id') == $s->id) ? 'selected' : '' }}>{{ $s->status_name }}</option>
        @endforeach
      </select>
    </div>
  </x-form-field>

  {{-- نوع المستخدم --}}
  <x-form-field name="type_user_code" label="نوع المستخدم" icon="user-cog" required>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="user-cog" class="w-4 h-4"></i>
      </div>
      <select
          name="type_user_code"
          id="type_user_code"
          required
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none"
      >
        <option value="">اختر النوع</option>
        @foreach($types as $t)
          <option value="{{ $t->id }}" {{ (old('type_user_code') == $t->id) ? 'selected' : '' }}>{{ $t->type_name }}</option>
        @endforeach
      </select>
    </div>
  </x-form-field>

  {{-- الدور الوظيفي --}}
  <x-form-field name="role_id" label="الدور الوظيفي" icon="shield" required>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="shield" class="w-4 h-4"></i>
      </div>
      <select
          name="role_id"
          id="role_id"
          required
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none"
      >
        <option value="">اختر الدور</option>
        @foreach($roles as $r)
          <option value="{{ $r->id }}" {{ (old('role_id') == $r->id) ? 'selected' : '' }}>{{ $r->name }}</option>
        @endforeach
      </select>
    </div>
  </x-form-field>

  {{-- اعتماد الحساب (checkbox) --}}
  <x-form-field name="is_approved" label="اعتماد الحساب" icon="check-circle">
    <label class="flex items-center gap-3 cursor-pointer select-none">
      <div class="relative">
        <input
            type="checkbox"
            name="is_approved"
            id="is_approved"
            value="1"
            class="sr-only peer"
        >
        <div class="w-11 h-6 bg-slate-200 peer-focus:ring-4 peer-focus:ring-teal-500/10 rounded-full peer peer-checked:bg-teal-500 transition-all"></div>
        <div class="absolute top-0.5 right-0.5 bg-white w-5 h-5 rounded-full shadow peer-checked:translate-x-[-20px] transition-all"></div>
      </div>
      <span class="text-xs font-bold text-slate-600">اعتماد الحساب وتفعيله</span>
    </label>
  </x-form-field>

</x-form-modal>

{{-- ===== User Details Modal (View) ===== --}}
{{--
  User Details Modal — مطابق لـ UserDetailsModal.tsx (Next.js design reference)
  Layout: Banner header + 3-column body (status+actions | company+stats+activity) + footer
  Driven by data-view-json. Inline script handles dynamic bits (badges, action visibility).
--}}
<div
    id="user-details-modal"
    data-aracari-modal
    class="hidden fixed inset-0 z-[80] overflow-y-auto font-cairo"
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
>
  {{-- Overlay --}}
  <div class="aracari-modal-overlay fixed inset-0 bg-[#1a1c1e]/60 backdrop-blur-sm opacity-0 transition-opacity duration-200"></div>

  {{-- Panel --}}
  <div class="flex min-h-full items-center justify-center p-4">
    <div
        class="aracari-modal-panel relative bg-white rounded-3xl max-w-4xl w-full shadow-2xl overflow-hidden border border-[#eeeef0] flex flex-col max-h-[90vh] opacity-0 scale-95 translate-y-4 transition-all duration-200"
        dir="rtl"
    >

      {{-- ===== Cover Banner Header (teal gradient) ===== --}}
      <div class="relative p-6 bg-gradient-to-r from-teal-900 via-[#1b3d24] to-emerald-950 text-white flex flex-col justify-end min-h-48">
        {{-- Close button --}}
        <div class="absolute top-4 left-4">
          <button
              type="button"
              data-close-modal
              class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur text-white flex items-center justify-center transition-all"
              aria-label="إغلاق"
          >
            <i data-lucide="x" class="w-5 h-5"></i>
          </button>
        </div>

        <div class="relative z-10 flex flex-col md:flex-row items-center md:items-end gap-6 text-center md:text-right">
          {{-- Avatar with approval badge --}}
          <div class="relative group">
            <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-0.5 shadow-lg">
              <div class="w-full h-full rounded-[14px] bg-white flex items-center justify-center font-black text-4xl text-teal-700 shadow-inner">
                <span data-preview="name" data-preview-default="U">U</span>
              </div>
            </div>
            {{-- Approval badge: shown/colored by inline script --}}
            <div id="user-details-approval-badge" class="absolute -bottom-1 -left-1 w-7 h-7 bg-amber-500 border-2 border-white rounded-xl flex items-center justify-center text-white shadow">
              <i data-lucide="hourglass" class="w-4 h-4"></i>
            </div>
          </div>

          {{-- Profile info --}}
          <div class="space-y-1.5 flex-1">
            <div class="flex flex-wrap items-center justify-center md:justify-start gap-2">
              <h2 class="text-xl font-extrabold tracking-tight" data-preview="name" data-preview-default="—">—</h2>
              <span id="user-details-role-badge" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black border bg-slate-100/80 text-slate-700 border-slate-200/50">
                <i data-lucide="shield" class="w-3 h-3"></i>
                <span data-preview="role_name" data-preview-default="—">—</span>
              </span>
              <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-white/10 text-emerald-200 text-[10px] font-bold">
                <i data-lucide="user-cog" class="w-3 h-3"></i>
                <span data-preview="type_name" data-preview-default="—">—</span>
              </span>
            </div>

            <p class="text-xs text-emerald-200/80 font-medium flex items-center justify-center md:justify-start gap-1.5">
              <i data-lucide="mail" class="w-3.5 h-3.5"></i>
              <span data-preview="email" data-preview-default="لا يوجد بريد مسجل">لا يوجد بريد مسجل</span>
            </p>

            <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 pt-2">
              <span class="bg-white/10 text-white text-[10px] font-bold px-2.5 py-1 rounded-lg flex items-center gap-1">
                <i data-lucide="phone" class="w-3.5 h-3.5 text-emerald-400"></i>
                <span dir="ltr" data-preview="phone_number" data-preview-default="—">—</span>
              </span>
              <span class="bg-white/10 text-white text-[10px] font-bold px-2.5 py-1 rounded-lg flex items-center gap-1">
                <i data-lucide="calendar" class="w-3.5 h-3.5 text-amber-400"></i>
                <span>انضم: <span data-preview="created_at" data-preview-default="—">—</span></span>
              </span>
            </div>
          </div>
        </div>
      </div>

      {{-- ===== Body: 3-column grid ===== --}}
      <div class="p-6 flex-1 overflow-y-auto space-y-6 md:space-y-0 md:grid md:grid-cols-3 md:gap-6 text-right scrollbar-thin">

        {{-- ===== COLUMN 1: Account Status + Actions (spans 1) ===== --}}
        <div class="space-y-5 md:col-span-1">

          {{-- Account Status Card --}}
          <div class="bg-slate-50/50 p-5 rounded-2xl border border-gray-200 space-y-4">
            <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-gray-100 pb-2">
              <i data-lucide="sliders-horizontal" class="w-4 h-4 text-teal-600"></i>
              <span>حالة الحساب والأمان</span>
            </h4>

            <div class="space-y-3">
              {{-- 1. Activation State --}}
              <div class="flex justify-between items-center p-3 bg-white border border-gray-150 rounded-xl">
                <span class="text-xs font-bold text-slate-500">حالة التفعيل:</span>
                <span id="user-details-activation-badge" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold border bg-emerald-50 text-emerald-700 border-emerald-200">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                  <span data-preview="status_name" data-preview-default="—">—</span>
                </span>
              </div>

              {{-- 2. Management Approval --}}
              <div class="flex justify-between items-center p-3 bg-white border border-gray-150 rounded-xl">
                <span class="text-xs font-bold text-slate-500">اعتماد الإدارة:</span>
                <span id="user-details-approval-text" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold border bg-amber-50 text-amber-700 border-amber-200">
                  <i data-lucide="hourglass" class="w-3 h-3"></i>
                  <span>قيد المراجعة</span>
                </span>
              </div>

              {{-- 3. Phone Verification --}}
              <div class="flex justify-between items-center p-3 bg-white border border-gray-150 rounded-xl">
                <span class="text-xs font-bold text-slate-500">رقم الهاتف:</span>
                <span id="user-details-phone-badge" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold border bg-amber-50 text-amber-700 border-amber-200">
                  <i data-lucide="phone" class="w-3 h-3"></i>
                  <span>غير مفعل</span>
                </span>
              </div>
            </div>
          </div>

          {{-- ===== Quick Operational Actions ===== --}}
          <div class="space-y-2 pt-1">
            <span class="text-[10px] font-black text-slate-400 block px-1">إجراءات التحكم والاعتماد</span>

            {{-- 1. Approve account (shown only if NOT approved) --}}
            @if($canApprove)
              <form
                  data-aracari-ajax
                  data-success-message="تم اعتماد الحساب بنجاح"
                  class="hidden"
                  id="user-details-approve-form"
              >
                @csrf
                <button
                    type="submit"
                    class="w-full py-2.5 bg-gradient-to-r from-emerald-600 to-teal-700 text-white text-xs font-bold rounded-xl shadow hover:shadow-md transition-all flex items-center justify-center gap-1.5"
                >
                  <i data-lucide="check-circle" class="w-4 h-4"></i>
                  <span>اعتماد الحساب الآن</span>
                </button>
              </form>
            @endif

            {{-- 2. Verify phone (shown only if NOT verified) --}}
            <form
                data-aracari-ajax
                data-success-message="تم تفعيل رقم الهاتف بنجاح"
                class="hidden"
                id="user-details-verify-phone-form"
            >
              @csrf
              <button
                  type="submit"
                  class="w-full py-2.5 bg-gradient-to-r from-blue-600 to-indigo-700 text-white text-xs font-bold rounded-xl shadow hover:shadow-md transition-all flex items-center justify-center gap-1.5"
              >
                <i data-lucide="phone" class="w-4 h-4"></i>
                <span>تفعيل رقم الهاتف</span>
              </button>
            </form>

            {{-- 3. Toggle suspension (opens edit modal with current status pre-filled) --}}
            @if($canEdit)
              <button
                  type="button"
                  id="user-details-toggle-btn"
                  class="w-full py-2.5 text-xs font-bold rounded-xl border flex items-center justify-center gap-1.5 transition-all bg-rose-50 border-rose-200 text-rose-700 hover:bg-rose-100"
              >
                <i data-lucide="toggle-left" class="w-4 h-4"></i>
                <span id="user-details-toggle-label">إيقاف/تجميد الحساب</span>
              </button>
            @endif

            {{-- 4. Restore deleted user --}}
            @if($canDelete)
              <button
                  type="button"
                  id="user-details-restore-btn"
                  class="hidden w-full py-2.5 text-xs font-bold rounded-xl border flex items-center justify-center gap-1.5 transition-all bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100"
              >
                <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                <span>استعادة الحساب المحذوف</span>
              </button>
            @endif

            {{-- 5. Delete permanently (uses global delete-confirm) --}}
            @if($canDelete)
              <button
                  type="button"
                  id="user-details-delete-btn"
                  class="w-full py-2.5 bg-rose-600 text-white hover:bg-rose-700 text-xs font-bold rounded-xl shadow transition-all flex items-center justify-center gap-1.5"
              >
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                <span>حذف المستخدم نهائياً</span>
              </button>
            @endif
          </div>
        </div>

        {{-- ===== COLUMN 2-3: Company info + Stats + Activity (spans 2) ===== --}}
        <div class="space-y-5 md:col-span-2">

          {{-- 1. Provider Company Info (shown only if is_provider) --}}
          <div id="user-details-company-section" class="hidden bg-[#fffbeb] p-5 rounded-2xl border border-amber-200 space-y-3">
            <h4 class="font-bold text-xs text-amber-950 uppercase tracking-wider flex items-center gap-1.5 border-b border-amber-200/50 pb-2">
              <i data-lucide="building" class="w-4 h-4 text-amber-700"></i>
              <span>بيانات الشركة والترخيص</span>
            </h4>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 text-xs text-amber-900">
              <div>
                <span class="text-amber-800/60 block font-bold mb-0.5">الاسم التجاري:</span>
                <span class="font-extrabold text-amber-950" data-preview="company_name" data-preview-default="---">---</span>
              </div>
              <div>
                <span class="text-amber-800/60 block font-bold mb-0.5">رقم السجل التجاري:</span>
                <span class="font-mono font-extrabold text-amber-950" data-preview="cr_number" data-preview-default="---">---</span>
              </div>
              <div>
                <span class="text-amber-800/60 block font-bold mb-0.5">بلد المنشأ:</span>
                <span class="font-extrabold text-amber-950" data-preview="born_country" data-preview-default="اليمن">اليمن</span>
              </div>
              <div id="user-details-website-wrap" class="hidden">
                <span class="text-amber-800/60 block font-bold mb-0.5">الموقع الإلكتروني:</span>
                <span class="text-amber-600 font-mono font-black break-all" data-preview="website" data-preview-default="">—</span>
              </div>
            </div>
          </div>

          {{-- 2. Operational Metrics --}}
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Completed trips --}}
            <div class="bg-white border border-gray-200 p-4 rounded-2xl shadow-sm flex items-center gap-4 hover:shadow transition-shadow">
              <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                <i data-lucide="check-circle" class="w-6 h-6"></i>
              </div>
              <div>
                <div class="text-xl font-black text-gray-900" data-preview="trips_count" data-preview-default="0">0</div>
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">رحلة منشأة</div>
              </div>
            </div>

            {{-- Active Bookings --}}
            <div class="bg-white border border-gray-200 p-4 rounded-2xl shadow-sm flex items-center gap-4 hover:shadow transition-shadow">
              <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <i data-lucide="bookmark" class="w-6 h-6"></i>
              </div>
              <div>
                <div class="text-xl font-black text-gray-900" data-preview="bookings_count" data-preview-default="0">0</div>
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">حجز مسجل</div>
              </div>
            </div>
          </div>

          {{-- 3. Metadata strip --}}
          <div class="bg-slate-50/50 border border-slate-200/70 rounded-2xl p-4">
            <div class="flex flex-wrap items-center gap-4 text-[10px] font-bold text-slate-400">
              <span class="flex items-center gap-1.5">
                <i data-lucide="fingerprint" class="w-3.5 h-3.5"></i>
                UUID: <span class="font-mono text-slate-600" data-preview="uuid" data-preview-default="—">—</span>
              </span>
              <span class="flex items-center gap-1.5">
                <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                تاريخ التسجيل: <span class="text-slate-600" data-preview="created_at" data-preview-default="—">—</span>
              </span>
              <span class="flex items-center gap-1.5">
                <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                تفعيل الهاتف: <span class="text-slate-600" data-preview="phone_verified_at" data-preview-default="غير مفعل">غير مفعل</span>
              </span>
            </div>
          </div>
        </div>

      </div>{{-- end body grid --}}

      {{-- ===== Footer ===== --}}
      <div class="p-4 border-t border-[#eeeef0] bg-[#f9f9fc] flex justify-end">
        <button
            type="button"
            data-close-modal
            class="px-5 py-2 bg-slate-700 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow transition-all active:scale-95"
        >
          إغلاق الملف الشخصي
        </button>
      </div>

      {{-- Hidden input to carry uuid for action buttons --}}
      <input type="hidden" id="user-details-uuid-hidden" value="">
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
  // ===== User form modal: dynamic action + title + variant switching =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'user-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.uuid);

    // Handle password fields required toggle
    var passwordFields = modal.querySelectorAll('[data-edit-optional]');
    passwordFields.forEach(function(f) {
      if (isEdit) {
        f.removeAttribute('required');
      } else {
        f.setAttribute('required', '');
      }
    });

    if (isEdit) {
      // edit mode → PUT to update route
      var updateUrl = "{{ route('admin.users.update', '__UUID__') }}".replace('__UUID__', data.uuid);
      form.setAttribute('action', updateUrl);
      var existingMethod = form.querySelector('input[name="_method"]');
      if (existingMethod) existingMethod.value = 'PUT';
      else {
        var m = document.createElement('input');
        m.type = 'hidden'; m.name = '_method'; m.value = 'PUT';
        m.setAttribute('data-dynamic', 'true');
        form.appendChild(m);
      }
      // update banner to amber (edit variant)
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل المستخدم';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }
      // success message
      form.setAttribute('data-success-message', 'تم تحديث المستخدم بنجاح');
    } else {
      // create mode → POST to store route
      form.setAttribute('action', "{{ route('admin.users.store') }}");
      var m = form.querySelector('input[name="_method"]');
      if (m) m.remove();
      modal.querySelector('.aracari-modal-panel .relative.h-24').className =
        'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
      modal.querySelector('.aracari-modal-panel h2').innerHTML =
        '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة مستخدم جديد';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30';
        badge.textContent = 'جديد';
      }
      form.setAttribute('data-success-message', 'تم حفظ المستخدم بنجاح');
    }
    // re-render icons (we injected new <i data-lucide>)
    if (window.lucide) window.lucide.createIcons();
  });

  // ===== User details modal: dynamic content + action buttons =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'user-details-modal') return;
    var data = e.detail.data || {};
    var uuid = data.uuid || '';

    // 1. Carry uuid in hidden input (for toggle/edit button)
    var uuidHidden = document.getElementById('user-details-uuid-hidden');
    if (uuidHidden) uuidHidden.value = uuid;

    // 2. Avatar initial (first letter of name)
    var nameVal = (data.name || '').trim();
    var avatarInitial = nameVal ? nameVal.charAt(0) : 'U';
    var avatarSpan = modal.querySelector('.w-24.h-24 span[data-preview="name"]');
    if (avatarSpan) avatarSpan.textContent = avatarInitial;

    // 3. Approval badge on avatar (✓ emerald معتمد / ⏳ amber قيد المراجعة)
    var approved = !!(data.is_approved == 1 || data.is_approved === true || data.is_approved === '1');
    var approvalAvatarBadge = document.getElementById('user-details-approval-badge');
    if (approvalAvatarBadge) {
      approvalAvatarBadge.className = 'absolute -bottom-1 -left-1 w-7 h-7 border-2 border-white rounded-xl flex items-center justify-center text-white shadow ' +
        (approved ? 'bg-emerald-500' : 'bg-amber-500');
      approvalAvatarBadge.innerHTML = approved
        ? '<i data-lucide="check" class="w-4 h-4 stroke-[3]"></i>'
        : '<i data-lucide="hourglass" class="w-4 h-4"></i>';
      approvalAvatarBadge.title = approved ? 'حساب معتمد' : 'قيد المراجعة';
    }

    // 4. Approval text in status card
    var approvalTextEl = document.getElementById('user-details-approval-text');
    if (approvalTextEl) {
      approvalTextEl.className = 'inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold border ' +
        (approved ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200');
      approvalTextEl.innerHTML = (approved
        ? '<i data-lucide="check" class="w-3 h-3"></i><span>حساب معتمد</span>'
        : '<i data-lucide="hourglass" class="w-3 h-3"></i><span>قيد المراجعة</span>');
    }

    // 5. Phone verification badge
    var phoneVerified = !!(data.is_phone_verified == 1 || data.is_phone_verified === true);
    var phoneBadge = document.getElementById('user-details-phone-badge');
    if (phoneBadge) {
      phoneBadge.className = 'inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold border ' +
        (phoneVerified ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-amber-50 text-amber-700 border-amber-200');
      phoneBadge.innerHTML = (phoneVerified
        ? '<i data-lucide="phone" class="w-3 h-3"></i><span>مفعل ومؤكد</span>'
        : '<i data-lucide="phone" class="w-3 h-3"></i><span>غير مفعل</span>');
    }

    // 6. Activation status badge (نشط / موقوف / ...)
    var statusCode = (data.status_code || '').toLowerCase();
    var isSuspended = (statusCode === 'suspended' || statusCode === 'inactive' || (data.status_name || '').indexOf('موقوف') !== -1);
    var activationBadge = document.getElementById('user-details-activation-badge');
    if (activationBadge) {
      activationBadge.className = 'inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold border ' +
        (isSuspended ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200');
      activationBadge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full ' + (isSuspended ? 'bg-rose-500' : 'bg-emerald-500') + '"></span>' +
        '<span>' + (data.status_name || '—') + '</span>';
    }

    // 7. Action buttons visibility + URLs
    var isTrashed = !!(data.is_trashed == 1 || data.is_trashed === true);
    var approveForm = document.getElementById('user-details-approve-form');
    var verifyPhoneForm = document.getElementById('user-details-verify-phone-form');
    var toggleLabel = document.getElementById('user-details-toggle-label');
    var toggleBtn = document.getElementById('user-details-toggle-btn');
    var restoreBtn = document.getElementById('user-details-restore-btn');
    var deleteBtn = document.getElementById('user-details-delete-btn');

    // Hide approve / verify / toggle actions for trashed users (no point)
    if (isTrashed) {
      if (approveForm) approveForm.classList.add('hidden');
      if (verifyPhoneForm) verifyPhoneForm.classList.add('hidden');
      if (toggleBtn) toggleBtn.classList.add('hidden');
      // Show restore button for trashed users
      if (restoreBtn) restoreBtn.classList.remove('hidden');
    } else {
      if (toggleBtn) toggleBtn.classList.remove('hidden');
      if (restoreBtn) restoreBtn.classList.add('hidden');

      // 7a. Approve form (only if NOT approved)
      if (approveForm) {
        if (!approved && uuid) {
          approveForm.classList.remove('hidden');
          approveForm.setAttribute('action', "{{ route('admin.users.approve', '__UUID__') }}".replace('__UUID__', uuid));
        } else {
          approveForm.classList.add('hidden');
        }
      }

      // 7b. Verify phone form (only if NOT verified)
      if (verifyPhoneForm) {
        if (!phoneVerified && uuid) {
          verifyPhoneForm.classList.remove('hidden');
          verifyPhoneForm.setAttribute('action', "{{ route('admin.users.verify-phone', '__UUID__') }}".replace('__UUID__', uuid));
        } else {
          verifyPhoneForm.classList.add('hidden');
        }
      }

      // 7c. Toggle button label (إيقاف if active / استعادة if suspended)
      if (toggleLabel && toggleBtn) {
        // remove old listeners by cloning
        var newToggleBtn = toggleBtn.cloneNode(true);
        toggleBtn.parentNode.replaceChild(newToggleBtn, toggleBtn);
        var toggleLabel2 = newToggleBtn.querySelector('#user-details-toggle-label');
        if (isSuspended) {
          if (toggleLabel2) toggleLabel2.textContent = 'استعادة وتفعيل الحساب';
          newToggleBtn.className = 'w-full py-2.5 text-xs font-bold rounded-xl border flex items-center justify-center gap-1.5 transition-all bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100';
        } else {
          if (toggleLabel2) toggleLabel2.textContent = 'إيقاف/تجميد الحساب';
          newToggleBtn.className = 'w-full py-2.5 text-xs font-bold rounded-xl border flex items-center justify-center gap-1.5 transition-all bg-rose-50 border-rose-200 text-rose-700 hover:bg-rose-100';
        }
        // Toggle button opens the edit modal so admin can change status
        newToggleBtn.addEventListener('click', function() {
          if (window.Aracari) {
            window.Aracari.openModal('user-form-modal', data);
          }
        });
      }
    }

    // 7d. Restore button → direct AJAX POST (only for trashed users)
    if (restoreBtn) {
      var newRestoreBtn = restoreBtn.cloneNode(true);
      restoreBtn.parentNode.replaceChild(newRestoreBtn, restoreBtn);
      if (isTrashed && uuid) {
        var restoreUrl = "{{ route('admin.users.restore', '__UUID__') }}".replace('__UUID__', uuid);
        newRestoreBtn.addEventListener('click', function() {
          var btn = newRestoreBtn;
          var origHtml = btn.innerHTML;
          btn.disabled = true;
          btn.innerHTML = '<svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> جارٍ الاستعادة...';
          var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
          fetch(restoreUrl, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': token,
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
          })
            .then(function(res) {
              var ct = res.headers.get('content-type') || '';
              if (ct.indexOf('application/json') !== -1) return res.json();
              return { success: true, message: 'تمت الاستعادة بنجاح' };
            })
            .then(function(j) {
              btn.disabled = false;
              btn.innerHTML = origHtml;
              if (window.lucide) window.lucide.createIcons();
              if (j && j.success === false) {
                if (window.Aracari) window.Aracari.toast(j.message || 'تعذرت الاستعادة', 'error');
                return;
              }
              if (window.Aracari) window.Aracari.toast(j && j.message ? j.message : 'تمت الاستعادة بنجاح', 'success');
              // Close details modal and refresh
              if (window.Aracari) window.Aracari.closeModal('user-details-modal');
              if (document.getElementById('aracari-refreshable')) {
                if (window.Aracari) window.Aracari.refreshTableCard();
              } else {
                setTimeout(function() { window.location.reload(); }, 600);
              }
            })
            .catch(function(err) {
              btn.disabled = false;
              btn.innerHTML = origHtml;
              if (window.lucide) window.lucide.createIcons();
              if (window.Aracari) window.Aracari.toast('حدث خطأ أثناء الاستعادة', 'error');
            });
        });
      } else {
        newRestoreBtn.classList.add('hidden');
      }
    }

    // 7e. Delete button → open global delete-confirm modal
    if (deleteBtn) {
      // remove old listeners by cloning
      var newDeleteBtn = deleteBtn.cloneNode(true);
      deleteBtn.parentNode.replaceChild(newDeleteBtn, deleteBtn);
      if (uuid) {
        var deleteUrl = "{{ route('admin.users.destroy', '__UUID__') }}".replace('__UUID__', uuid);
        newDeleteBtn.addEventListener('click', function() {
          if (window.Aracari && window.Aracari.openDeleteConfirm) {
            window.Aracari.openDeleteConfirm(deleteUrl, data.name || 'هذا المستخدم');
          }
        });
      }
    }

    // 8. Company section visibility (only for providers)
    var isProvider = !!(data.is_provider == 1 || data.is_provider === true);
    var companySection = document.getElementById('user-details-company-section');
    if (companySection) {
      if (isProvider) {
        companySection.classList.remove('hidden');
      } else {
        companySection.classList.add('hidden');
      }
    }

    // 9. Website field visibility (only if website is non-empty)
    var websiteWrap = document.getElementById('user-details-website-wrap');
    if (websiteWrap) {
      if (data.website && String(data.website).trim()) {
        websiteWrap.classList.remove('hidden');
      } else {
        websiteWrap.classList.add('hidden');
      }
    }

    // re-render lucide icons (we injected new <i data-lucide> elements)
    if (window.lucide) window.lucide.createIcons();
  });
</script>
@endpush
