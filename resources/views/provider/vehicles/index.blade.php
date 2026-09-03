@extends('layouts.provider')

@section('title', 'أركاري | إدارة المركبات')
@section('header_title', 'إدارة المركبات والأسطول')

@section('content')

@php
    $totalVehicles = $vehicles->total();

    // الإحصائيات من الصفحة الحالية
    $allItems = $vehicles->getCollection();
    $activeVehicles    = $allItems->filter(fn($v) => $v->status === 'active')->count();
    $maintenanceVehicles = $allItems->filter(fn($v) => $v->status === 'maintenance')->count();

    // التحقق من الصلاحيات
    $currentUser = auth()->user();
    $canCreate = $currentUser && $currentUser->hasPermission('provider:vehicles:create');
    $canEdit   = $currentUser && $currentUser->hasPermission('provider:vehicles:edit');
    $canDelete = $currentUser && $currentUser->hasPermission('provider:vehicles:delete');

    // خريطة ألوان حالة المركبة
    $statusBadges = [
        'active'      => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'maintenance' => 'bg-amber-50 text-amber-700 border-amber-200',
        'inactive'    => 'bg-slate-100 text-slate-600 border-slate-200',
    ];
    $statusLabels = [
        'active'      => 'نشط',
        'maintenance' => 'صيانة',
        'inactive'    => 'غير نشط',
    ];
    $statusDotColors = [
        'active'      => 'bg-emerald-500',
        'maintenance' => 'bg-amber-500',
        'inactive'    => 'bg-slate-400',
    ];
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== رأس الصفحة ===== --}}
  <x-page-header
      icon="car"
      title="المركبات"
      :count="$totalVehicles"
      countLabel="مركبة"
      description="إدارة أسطول المركبات الخاص بك"
      descriptionIcon="car"
  >
    @if($canCreate)
      <button
          type="button"
          data-create-modal="vehicle-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        إضافة مركبة جديدة
      </button>
    @endif
  </x-page-header>

  {{-- ===== المنطقة القابلة للتحديث: الإحصائيات + الجدول ===== --}}
  <div id="aracari-refreshable" class="space-y-6">

  {{-- ===== بطاقات الإحصائيات (3 بطاقات) ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 lg:gap-6">
    <x-stat-card :value="$totalVehicles" label="إجمالي المركبات" icon="car" variant="slate" badge="مركبة" badgeIcon="car" />
    <x-stat-card :value="$activeVehicles" label="مركبات نشطة" icon="circle-check" variant="emerald" badge="جاهزة" badgeIcon="circle-check" />
    <x-stat-card :value="$maintenanceVehicles" label="قيد الصيانة" icon="wrench" variant="amber" badge="إصلاح" badgeIcon="wrench" />
  </div>

  {{-- ===== بطاقة جدول البيانات ===== --}}
  <x-data-table-card :searchable="true" :searchMode="'server'" searchPlaceholder="ابحث عن مركبة..." :footerInfo="'عرض ' . $vehicles->count() . ' من أصل ' . $vehicles->total() . ' مركبة'">
    <x-slot name="thead">
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الاسم</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الموديل</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">النوع</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">المقاعد</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الرحلات</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
    </x-slot>

    <x-slot name="rows">
      @forelse($vehicles as $vehicle)
        @php
          $badgeClass   = $statusBadges[$vehicle->status] ?? $statusBadges['inactive'];
          $label        = $statusLabels[$vehicle->status] ?? 'غير محدد';
          $dotColor     = $statusDotColors[$vehicle->status] ?? 'bg-slate-400';
        @endphp
        <tr class="hover:bg-slate-50/50 transition-colors group">

          {{-- الاسم + صورة خارجية --}}
          <td data-searchable class="px-6 py-4">
            <div class="flex items-center gap-3">
              @if($vehicle->outside_photo_url)
                <img src="{{ $vehicle->outside_photo_url }}" alt="{{ $vehicle->name }}" class="w-10 h-10 rounded-xl object-cover border border-slate-200 flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
              @else
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500/20 to-teal-600/10 flex items-center justify-center border border-teal-100 group-hover:scale-110 transition-transform duration-300 flex-shrink-0">
                  <i data-lucide="car" class="w-5 h-5 text-teal-600"></i>
                </div>
              @endif
              <div class="min-w-0">
                <div class="font-bold text-slate-800 text-sm truncate max-w-[180px]">{{ $vehicle->name }}</div>
                <div class="text-[10px] text-slate-400 flex items-center gap-1 mt-0.5">
                  @if($vehicle->inside_photo_url)
                    <i data-lucide="image" class="w-3 h-3"></i>
                    صورة داخلية متوفرة
                  @else
                    <i data-lucide="image-off" class="w-3 h-3"></i>
                    بدون صورة داخلية
                  @endif
                </div>
              </div>
            </div>
          </td>

          {{-- الموديل --}}
          <td data-searchable class="px-6 py-4">
            <span class="text-xs text-slate-600 font-medium">{{ $vehicle->model ?? '—' }}</span>
          </td>

          {{-- نوع المركبة --}}
          <td class="px-6 py-4 text-center">
            <span class="inline-flex items-center gap-1 text-xs font-bold text-slate-700 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-lg">
              <i data-lucide="car" class="w-3 h-3 text-slate-400"></i>
              {{ $vehicle->vehicleType->type_name ?? '—' }}
            </span>
          </td>

          {{-- المقاعد --}}
          <td class="px-6 py-4 text-center">
            <span class="inline-flex items-center gap-1 text-sm font-bold text-slate-700">
              <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-100 flex items-center justify-center text-xs font-bold">
                {{ $vehicle->seats_count ?? 0 }}
              </span>
              مقعد
            </span>
          </td>

          {{-- الحالة --}}
          <td class="px-6 py-4 text-center">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $badgeClass }}">
              <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
              {{ $label }}
            </span>
          </td>

          {{-- الرحلات --}}
          <td class="px-6 py-4 text-center">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 text-[10px] font-bold border border-blue-200">
              <i data-lucide="route" class="w-3 h-3"></i>
              {{ $vehicle->trips_count ?? 0 }} رحلة
            </span>
          </td>

          {{-- الإجراءات --}}
          <td class="px-6 py-4 text-center">
            <div class="flex items-center justify-center gap-1.5">
              @if($canEdit)
                <x-action-button
                    variant="edit"
                    data-edit-modal="vehicle-form-modal"
                    data-edit-json="{{ json_encode([
                        'uuid'            => $vehicle->uuid,
                        'name'            => $vehicle->name,
                        'vehicle_type_id' => $vehicle->vehicle_type_id,
                        'model'           => $vehicle->model,
                        'seats_count'     => $vehicle->seats_count,
                        'status'          => $vehicle->status,
                        'outside_photo_url' => $vehicle->outside_photo_url,
                        'inside_photo_url'  => $vehicle->inside_photo_url,
                    ], JSON_UNESCAPED_UNICODE) }}"
                    :ariaLabel="'تعديل ' . $vehicle->name"
                />
              @endif
              @if($canDelete)
                <x-action-button
                    variant="delete"
                    data-delete-url="{{ route('admin.provider.vehicles.destroy', $vehicle->uuid) }}"
                    data-delete-label="{{ $vehicle->name }}"
                />
              @endif
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state
            icon="car"
            :title="request('search') ? 'لا توجد نتائج مطابقة' : 'لا توجد مركبات'"
            :message="request('search') ? 'جرّب تعديل كلمات البحث' : 'لم تقم بإضافة أي مركبة بعد. أضف مركبة جديدة لبدء إدارة أسطولك'"
            :colspan="7"
        >
          @if(!request('search') && $canCreate)
            <button
                type="button"
                data-create-modal="vehicle-form-modal"
                class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
            >
              <i data-lucide="plus" class="w-4 h-4"></i>
              إضافة مركبة جديدة
            </button>
          @endif
        </x-empty-state>
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$vehicles" />
    </x-slot>
  </x-data-table-card>

  </div>{{-- نهاية #aracari-refreshable -->}

</div>

{{-- ===== نافذة نموذج المركبة (إنشاء + تعديل) ===== --}}
<x-form-modal
    id="vehicle-form-modal"
    title="إضافة مركبة جديدة"
    :variant="'create'"
    :action="route('admin.provider.vehicles.store')"
    method="POST"
    successMessage="تم إنشاء المركبة بنجاح"
>

  {{-- ===== اسم المركبة + نوع المركبة ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    {{-- اسم المركبة --}}
    <x-form-field name="name" label="اسم المركبة" icon="car" required>
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="car" class="w-4 h-4"></i>
        </div>
        <input
            type="text"
            name="name"
            id="vehicle_name"
            required
            placeholder="حافلة النور 1"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
        >
      </div>
    </x-form-field>

    {{-- نوع المركبة --}}
    <x-form-field name="vehicle_type_id" label="نوع المركبة" icon="tag" required>
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="tag" class="w-4 h-4"></i>
        </div>
        <select
            name="vehicle_type_id"
            id="vehicle_type_id"
            required
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none cursor-pointer"
        >
          <option value="">اختر النوع</option>
          @foreach($vehicleTypes as $vt)
            <option value="{{ $vt->id }}">{{ $vt->type_name }}</option>
          @endforeach
        </select>
        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
          <i data-lucide="chevron-down" class="w-4 h-4"></i>
        </div>
      </div>
    </x-form-field>
  </div>

  {{-- ===== الموديل + عدد المقاعد ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    {{-- الموديل --}}
    <x-form-field name="model" label="الموديل" icon="settings">
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="settings" class="w-4 h-4"></i>
        </div>
        <input
            type="text"
            name="model"
            id="vehicle_model"
            placeholder="مرسيدس بنز O500 2023"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
        >
      </div>
    </x-form-field>

    {{-- عدد المقاعد --}}
    <x-form-field name="seats_count" label="عدد المقاعد" icon="hash" required>
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="hash" class="w-4 h-4"></i>
        </div>
        <input
            type="number"
            name="seats_count"
            id="vehicle_seats_count"
            required
            min="1"
            max="80"
            placeholder="50"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
        >
      </div>
    </x-form-field>
  </div>

  {{-- ===== الحالة + طريقة إنشاء المقاعد ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    {{-- الحالة --}}
    <div class="space-y-1.5">
      <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
        <i data-lucide="toggle-right" class="w-3.5 h-3.5"></i>
        الحالة
      </label>
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="toggle-right" class="w-4 h-4"></i>
        </div>
        <select
            name="status"
            id="vehicle_status"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none cursor-pointer"
        >
          <option value="active">نشط</option>
          <option value="maintenance">صيانة</option>
          <option value="inactive">غير نشط</option>
        </select>
        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
          <i data-lucide="chevron-down" class="w-4 h-4"></i>
        </div>
      </div>
    </div>

    {{-- طريقة إنشاء المقاعد (إنشاء فقط) / خيار المقاعد (تعديل فقط) --}}
    <div class="space-y-1.5" id="vehicle-seat-mode-section">
      <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
        <i data-lucide="settings" class="w-3.5 h-3.5"></i>
        طريقة إنشاء المقاعد <span class="text-rose-500" id="vehicle-seat-mode-required-star">*</span>
      </label>
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="settings" class="w-4 h-4"></i>
        </div>
        {{-- إنشاء: seat_generation_type --}}
        <select
            name="seat_generation_type"
            id="vehicle_seat_generation_type"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none cursor-pointer"
        >
          <option value="auto">تلقائي</option>
          <option value="manual">يدوي</option>
        </select>
        {{-- تعديل: seat_option --}}
        <select
            name="seat_option"
            id="vehicle_seat_option"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none cursor-pointer hidden"
        >
          <option value="keep">الاحتفاظ بالمقاعد الحالية</option>
          <option value="regenerate">إعادة إنشاء تلقائي</option>
          <option value="manual">إدخال يدوي</option>
        </select>
        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
          <i data-lucide="chevron-down" class="w-4 h-4"></i>
        </div>
      </div>
    </div>
  </div>

  {{-- ===== بادئة المقعد (تلقائي فقط) ===== --}}
  <div id="vehicle-seat-prefix-section" class="space-y-1.5">
    <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
      <i data-lucide="tag" class="w-3.5 h-3.5"></i>
      بادئة المقعد <span class="text-rose-500" id="vehicle-prefix-required-star">*</span>
    </label>
    <div class="relative group">
      <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
        <i data-lucide="tag" class="w-4 h-4"></i>
      </div>
      <input
          type="text"
          name="seat_prefix"
          id="vehicle_seat_prefix"
          value="Seat-"
          placeholder="Seat-"
          maxlength="10"
          dir="ltr"
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left"
      >
    </div>
    <p class="text-[10px] text-slate-400 font-medium" id="vehicle-prefix-hint">
      سيتم إنشاء المقاعد بالصيغة: <span class="text-emerald-600 font-bold" dir="ltr" id="vehicle-prefix-preview">Seat-1, Seat-2 ...</span>
    </p>
  </div>

  {{-- ===== أسماء المقاعد (يدوي فقط) ===== --}}
  <div id="vehicle-seat-names-section" class="space-y-2 hidden">
    <div class="flex items-center justify-between">
      <label class="text-[11px] font-bold text-slate-600 flex items-center gap-1">
        <i data-lucide="list-ordered" class="w-3.5 h-3.5"></i>
        أسماء المقاعد
        <span class="text-slate-400" id="vehicle-seat-names-counter"></span>
      </label>
      <button
          type="button"
          id="vehicle-add-seat-btn"
          class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 bg-emerald-50 border border-emerald-200 hover:bg-emerald-500 hover:text-white hover:border-emerald-500 transition-all px-2.5 py-1 rounded-lg"
      >
        <i data-lucide="plus" class="w-3 h-3"></i>
        إضافة مقعد
      </button>
    </div>
    <p class="text-[10px] text-slate-400 font-medium" id="vehicle-seat-names-hint">
      أضف أسماء المقاعد يدوياً حتى تصل إلى العدد المطلوب.
    </p>
    <div id="vehicle-seat-names-list" class="space-y-2 max-h-48 overflow-y-auto pr-1">
      {{-- سيتم ملؤها ديناميكياً --}}
    </div>
  </div>

  {{-- ===== الصور ===== --}}
  <div class="space-y-2">
    <h4 class="font-bold text-xs text-[#115128] border-b border-[#eeeef0] pb-2 flex items-center gap-1.5">
      <i data-lucide="camera" class="w-3.5 h-3.5"></i>
      صور المركبة
    </h4>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      {{-- الصورة الخارجية --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
          <i data-lucide="image" class="w-3.5 h-3.5"></i>
          الصورة الخارجية
        </label>
        <div class="relative" id="vehicle-outside-photo-wrapper">
          <input
              type="file"
              name="outside_photo"
              id="vehicle_outside_photo"
              accept="image/*"
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 py-2 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100"
          >
        </div>
        <div id="vehicle-outside-photo-preview" class="hidden">
          <div class="relative rounded-xl overflow-hidden border border-slate-200">
            <img id="vehicle-outside-photo-img" src="" alt="الصورة الخارجية" class="w-full h-24 object-cover">
            <button
                type="button"
                onclick="vehicleRemovePhoto('outside')"
                class="absolute top-1 left-1 w-6 h-6 rounded-full bg-rose-500 text-white flex items-center justify-center hover:bg-rose-600 transition-colors"
            >
              <i data-lucide="x" class="w-3 h-3"></i>
            </button>
          </div>
        </div>
        {{-- صورة حالية في وضع التعديل --}}
        <div id="vehicle-outside-photo-existing" class="hidden">
          <div class="relative rounded-xl overflow-hidden border border-slate-200">
            <img id="vehicle-outside-photo-existing-img" src="" alt="الصورة الخارجية" class="w-full h-24 object-cover">
            <span class="absolute bottom-1 right-1 text-[9px] font-bold text-white bg-black/50 px-2 py-0.5 rounded-full">الصورة الحالية</span>
          </div>
        </div>
      </div>

      {{-- الصورة الداخلية --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
          <i data-lucide="image" class="w-3.5 h-3.5"></i>
          الصورة الداخلية
        </label>
        <div class="relative" id="vehicle-inside-photo-wrapper">
          <input
              type="file"
              name="inside_photo"
              id="vehicle_inside_photo"
              accept="image/*"
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 py-2 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100"
          >
        </div>
        <div id="vehicle-inside-photo-preview" class="hidden">
          <div class="relative rounded-xl overflow-hidden border border-slate-200">
            <img id="vehicle-inside-photo-img" src="" alt="الصورة الداخلية" class="w-full h-24 object-cover">
            <button
                type="button"
                onclick="vehicleRemovePhoto('inside')"
                class="absolute top-1 left-1 w-6 h-6 rounded-full bg-rose-500 text-white flex items-center justify-center hover:bg-rose-600 transition-colors"
            >
              <i data-lucide="x" class="w-3 h-3"></i>
            </button>
          </div>
        </div>
        {{-- صورة حالية في وضع التعديل --}}
        <div id="vehicle-inside-photo-existing" class="hidden">
          <div class="relative rounded-xl overflow-hidden border border-slate-200">
            <img id="vehicle-inside-photo-existing-img" src="" alt="الصورة الداخلية" class="w-full h-24 object-cover">
            <span class="absolute bottom-1 right-1 text-[9px] font-bold text-white bg-black/50 px-2 py-0.5 rounded-full">الصورة الحالية</span>
          </div>
        </div>
      </div>
    </div>
  </div>

</x-form-modal>

@endsection

@push('scripts')
<script>
  // ===== متغيرات عامة =====
  var vehicleSeatNames = [];
  var vehicleManualSeatCounter = 0;

  // ===== معاينة الصور عند اختيارها =====
  document.getElementById('vehicle_outside_photo').addEventListener('change', function(e) {
    var file = e.target.files[0];
    var preview = document.getElementById('vehicle-outside-photo-preview');
    var img = document.getElementById('vehicle-outside-photo-img');
    var existing = document.getElementById('vehicle-outside-photo-existing');
    if (file) {
      var reader = new FileReader();
      reader.onload = function(ev) { img.src = ev.target.result; preview.classList.remove('hidden'); existing.classList.add('hidden'); };
      reader.readAsDataURL(file);
    } else {
      preview.classList.add('hidden');
    }
  });

  document.getElementById('vehicle_inside_photo').addEventListener('change', function(e) {
    var file = e.target.files[0];
    var preview = document.getElementById('vehicle-inside-photo-preview');
    var img = document.getElementById('vehicle-inside-photo-img');
    var existing = document.getElementById('vehicle-inside-photo-existing');
    if (file) {
      var reader = new FileReader();
      reader.onload = function(ev) { img.src = ev.target.result; preview.classList.remove('hidden'); existing.classList.add('hidden'); };
      reader.readAsDataURL(file);
    } else {
      preview.classList.add('hidden');
    }
  });

  // ===== إزالة الصورة المختارة =====
  function vehicleRemovePhoto(type) {
    var input = document.getElementById('vehicle_' + type + '_photo');
    var preview = document.getElementById('vehicle-' + type + '-photo-preview');
    var img = document.getElementById('vehicle-' + type + '-photo-img');
    if (input) input.value = '';
    if (img) img.src = '';
    if (preview) preview.classList.add('hidden');
  }

  // ===== عرض/إخفاء قسم المقاعد حسب نوع الإنشاء =====
  function vehicleToggleSeatSections() {
    var genType = document.getElementById('vehicle_seat_generation_type');
    var seatOption = document.getElementById('vehicle_seat_option');
    var prefixSection = document.getElementById('vehicle-seat-prefix-section');
    var namesSection = document.getElementById('vehicle-seat-names-section');

    // تحديد أي select مرئي (إنشاء أو تعديل)
    var visibleSelect = !genType.classList.contains('hidden') ? genType : seatOption;
    var value = visibleSelect ? visibleSelect.value : 'auto';

    if (value === 'auto' || value === 'regenerate') {
      prefixSection.classList.remove('hidden');
      namesSection.classList.add('hidden');
    } else if (value === 'manual') {
      prefixSection.classList.add('hidden');
      namesSection.classList.remove('hidden');
    } else {
      // keep
      prefixSection.classList.add('hidden');
      namesSection.classList.add('hidden');
    }
  }

  document.getElementById('vehicle_seat_generation_type').addEventListener('change', vehicleToggleSeatSections);
  document.getElementById('vehicle_seat_option').addEventListener('change', vehicleToggleSeatSections);

  // ===== تحديث معاينة البادئة =====
  function vehicleUpdatePrefixPreview() {
    var prefix = document.getElementById('vehicle_seat_prefix').value || 'Seat-';
    var preview = document.getElementById('vehicle-prefix-preview');
    if (preview) preview.textContent = prefix + '1, ' + prefix + '2 ...';
  }
  document.getElementById('vehicle_seat_prefix').addEventListener('input', vehicleUpdatePrefixPreview);

  // ===== إضافة/حذف/تعديل أسماء المقاعد اليدوية =====
  function vehicleUpdateSeatCounter() {
    var seatsCount = parseInt(document.getElementById('vehicle_seats_count').value) || 0;
    var counter = document.getElementById('vehicle-seat-names-counter');
    if (counter) {
      counter.textContent = '(' + vehicleSeatNames.length + (seatsCount ? '/' + seatsCount : '') + ')';
    }
  }

  function vehicleAddSeatName() {
    var seatsCount = parseInt(document.getElementById('vehicle_seats_count').value) || 0;
    if (seatsCount && vehicleSeatNames.length >= seatsCount) return;
    vehicleManualSeatCounter++;
    vehicleSeatNames.push('مقعد ' + vehicleManualSeatCounter);
    vehicleRenderSeatNames();
  }

  function vehicleUpdateSeatName(index, value) {
    vehicleSeatNames[index] = value;
  }

  function vehicleRemoveSeatName(index) {
    vehicleSeatNames.splice(index, 1);
    vehicleRenderSeatNames();
  }

  function vehicleRenderSeatNames() {
    var list = document.getElementById('vehicle-seat-names-list');
    var hint = document.getElementById('vehicle-seat-names-hint');
    var seatsCount = parseInt(document.getElementById('vehicle_seats_count').value) || 0;

    if (vehicleSeatNames.length === 0) {
      list.innerHTML = '';
      if (hint) hint.style.display = '';
      vehicleUpdateSeatCounter();
      return;
    }

    if (hint) hint.style.display = 'none';
    var html = '';
    for (var i = 0; i < vehicleSeatNames.length; i++) {
      html += '<div class="flex items-center gap-2">' +
        '<span class="w-7 h-7 rounded-lg bg-slate-100 text-slate-500 text-[11px] font-bold flex items-center justify-center flex-shrink-0">' + (i + 1) + '</span>' +
        '<input type="text" name="seat_names[]" value="' + (vehicleSeatNames[i] || '').replace(/"/g, '&quot;') + '" placeholder="اسم المقعد ' + (i + 1) + '" ' +
        'class="flex-1 bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-3 pl-3 py-2 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right" ' +
        'oninput="vehicleUpdateSeatName(' + i + ', this.value)">' +
        '<button type="button" onclick="vehicleRemoveSeatName(' + i + ')" class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white border border-rose-200 hover:border-rose-500 transition-all flex items-center justify-center flex-shrink-0" title="حذف">' +
        '<i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></div>';
    }
    list.innerHTML = html;
    if (window.lucide) window.lucide.createIcons();
    vehicleUpdateSeatCounter();
  }

  document.getElementById('vehicle-add-seat-btn').addEventListener('click', vehicleAddSeatName);
  document.getElementById('vehicle_seats_count').addEventListener('change', vehicleUpdateSeatCounter);

  // ===== نافذة المركبة: تبديل الإنشاء/التعديل + ملء الحقول =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'vehicle-form-modal') return;

    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.uuid);

    // ===== 1. تبديل حقول المقاعد بين الإنشاء والتعديل =====
    var genTypeSelect = document.getElementById('vehicle_seat_generation_type');
    var seatOptionSelect = document.getElementById('vehicle_seat_option');

    if (isEdit) {
      genTypeSelect.classList.add('hidden');
      genTypeSelect.removeAttribute('name');
      seatOptionSelect.classList.remove('hidden');
      seatOptionSelect.setAttribute('name', 'seat_option');
    } else {
      genTypeSelect.classList.remove('hidden');
      genTypeSelect.setAttribute('name', 'seat_generation_type');
      seatOptionSelect.classList.add('hidden');
      seatOptionSelect.removeAttribute('name');
    }

    // ===== 2. تبديل رابط الإجراء والعنوان =====
    if (isEdit) {
      var updateUrl = "{{ route('admin.provider.vehicles.update', '__UUID__') }}".replace('__UUID__', data.uuid);
      form.setAttribute('action', updateUrl);

      var existingMethod = form.querySelector('input[name="_method"]');
      if (existingMethod) existingMethod.value = 'PUT';
      else {
        var m = document.createElement('input');
        m.type = 'hidden'; m.name = '_method'; m.value = 'PUT';
        m.setAttribute('data-dynamic', 'true');
        form.appendChild(m);
      }

      // شريط تعديل (عنبري)
      var banner = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (banner) banner.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';

      var titleEl = modal.querySelector('.aracari-modal-panel h2');
      if (titleEl) titleEl.innerHTML = '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل المركبة';

      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }

      var subtitle = modal.querySelector('.aracari-modal-panel p');
      if (subtitle) subtitle.textContent = 'تحديث معلومات المركبة';

      form.setAttribute('data-success-message', 'تم تحديث المركبة بنجاح');
    } else {
      form.setAttribute('action', "{{ route('admin.provider.vehicles.store') }}");
      var m = form.querySelector('input[name="_method"]');
      if (m) m.remove();

      var banner = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (banner) banner.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';

      var titleEl = modal.querySelector('.aracari-modal-panel h2');
      if (titleEl) titleEl.innerHTML = '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة مركبة جديدة';

      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30';
        badge.textContent = 'جديد';
      }

      var subtitle = modal.querySelector('.aracari-modal-panel p');
      if (subtitle) subtitle.textContent = 'أضف مركبة جديدة إلى أسطولك';

      form.setAttribute('data-success-message', 'تم إنشاء المركبة بنجاح');
    }

    // ===== 3. ملء حقول النموذج =====
    var nameInput = modal.querySelector('#vehicle_name');
    var typeSelect = modal.querySelector('#vehicle_type_id');
    var modelInput = modal.querySelector('#vehicle_model');
    var seatsInput = modal.querySelector('#vehicle_seats_count');
    var statusSelect = modal.querySelector('#vehicle_status');
    var prefixInput = modal.querySelector('#vehicle_seat_prefix');

    if (nameInput) nameInput.value = isEdit ? (data.name || '') : '';
    if (typeSelect) typeSelect.value = isEdit ? (data.vehicle_type_id || '') : '';
    if (modelInput) modelInput.value = isEdit ? (data.model || '') : '';
    if (seatsInput) seatsInput.value = isEdit ? (data.seats_count || '') : '';
    if (statusSelect) statusSelect.value = isEdit ? (data.status || 'active') : 'active';
    if (prefixInput) prefixInput.value = 'Seat-';

    // ===== 4. إعادة تعيين أقسام المقاعد =====
    vehicleSeatNames = [];
    vehicleManualSeatCounter = 0;
    if (isEdit) {
      seatOptionSelect.value = 'keep';
    } else {
      genTypeSelect.value = 'auto';
    }
    vehicleToggleSeatSections();
    vehicleUpdatePrefixPreview();
    vehicleUpdateSeatCounter();
    vehicleRenderSeatNames();

    // ===== 5. إعادة تعيين الصور =====
    var outsideInput = document.getElementById('vehicle_outside_photo');
    var insideInput = document.getElementById('vehicle_inside_photo');
    if (outsideInput) outsideInput.value = '';
    if (insideInput) insideInput.value = '';

    var outsidePreview = document.getElementById('vehicle-outside-photo-preview');
    var insidePreview = document.getElementById('vehicle-inside-photo-preview');
    var outsideExisting = document.getElementById('vehicle-outside-photo-existing');
    var insideExisting = document.getElementById('vehicle-inside-photo-existing');
    var outsideExistingImg = document.getElementById('vehicle-outside-photo-existing-img');
    var insideExistingImg = document.getElementById('vehicle-inside-photo-existing-img');

    if (outsidePreview) outsidePreview.classList.add('hidden');
    if (insidePreview) insidePreview.classList.add('hidden');

    if (isEdit) {
      // إظهار الصور الحالية في التعديل
      if (data.outside_photo_url && outsideExisting && outsideExistingImg) {
        outsideExistingImg.src = data.outside_photo_url;
        outsideExisting.classList.remove('hidden');
      } else if (outsideExisting) {
        outsideExisting.classList.add('hidden');
      }
      if (data.inside_photo_url && insideExisting && insideExistingImg) {
        insideExistingImg.src = data.inside_photo_url;
        insideExisting.classList.remove('hidden');
      } else if (insideExisting) {
        insideExisting.classList.add('hidden');
      }
    } else {
      if (outsideExisting) outsideExisting.classList.add('hidden');
      if (insideExisting) insideExisting.classList.add('hidden');
    }

    // ===== 6. إعادة تصيير أيقونات لوسيد =====
    if (window.lucide) window.lucide.createIcons();
  });
</script>
@endpush
