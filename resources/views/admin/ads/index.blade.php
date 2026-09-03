@extends('layouts.admin')

@section('title', 'أركاري | إدارة الإعلانات')
@section('header_title', 'إدارة الإعلانات')

@section('content')

@php
    $statusMeta = [
        'active'   => ['label' => 'نشط',        'bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'icon' => 'check-circle'],
        'pending'  => ['label' => 'قيد المراجعة', 'bg' => 'bg-amber-50',  'text' => 'text-amber-700',  'border' => 'border-amber-200',  'icon' => 'clock'],
        'rejected' => ['label' => 'مرفوض',      'bg' => 'bg-rose-50',    'text' => 'text-rose-700',    'border' => 'border-rose-200',    'icon' => 'x-circle'],
        'inactive' => ['label' => 'متوقف',      'bg' => 'bg-slate-100',  'text' => 'text-slate-600',  'border' => 'border-slate-200',  'icon' => 'pause-circle'],
    ];
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="megaphone"
      title="إدارة الإعلانات"
      :count="$ads->total()"
      countLabel="إعلان"
      description="إدارة الإعلانات الترويجية والحملات الإعلانية"
      descriptionIcon="megaphone"
  >
    <button
        type="button"
        data-create-modal="ad-form-modal"
        class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
    >
      <i data-lucide="plus-circle" class="w-4 h-4"></i>
      <span class="hidden sm:inline">إضافة إعلان جديد</span>
    </button>
  </x-page-header>

  {{-- ===== 4 Stat Cards ===== --}}
  <div id="aracari-refreshable">
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    <x-stat-card :value="$stats['total']" label="إجمالي الإعلانات" icon="megaphone" variant="slate" badge="مسجّلة" badgeIcon="trending-up" />
    <x-stat-card :value="$stats['active']" label="نشطة" icon="check-circle" variant="emerald" badge="تعمل حالياً" badgeIcon="check-circle" />
    <x-stat-card :value="$stats['pending']" label="قيد المراجعة" icon="clock" variant="amber" badge="في الانتظار" badgeIcon="clock" />
    <x-stat-card :value="$stats['inactive']" label="منتهية / مرفوضة" icon="x-circle" variant="rose" badge="غير نشطة" badgeIcon="x-circle" />
  </div>

  {{-- ===== Data Table ===== --}}
  <x-data-table-card
      :searchable="true"
      :searchMode="'server'"
      searchPlaceholder="ابحث عن إعلان بالعنوان أو الوصف..."
      :searchValue="request('search')"
      :footerInfo="'عرض ' . $ads->count() . ' من أصل ' . $ads->total() . ' إعلان'"
  >
    <x-slot name="filters">
      {{-- Status Filter --}}
      <form id="aracari-status-filter-form" class="relative md:w-44 flex-1">
        <select
            name="status"
            id="aracari-status-filter"
            class="w-full h-11 bg-white/70 border border-slate-200 rounded-xl px-3 pr-8 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all appearance-none cursor-pointer"
        >
          <option value="">جميع الحالات</option>
          <option value="active" @selected(request('status') === 'active')">نشط</option>
          <option value="pending" @selected(request('status') === 'pending')">قيد المراجعة</option>
          <option value="inactive" @selected(request('status') === 'inactive')">متوقف</option>
          <option value="rejected" @selected(request('status') === 'rejected')">مرفوض</option>
        </select>
        <i data-lucide="check-circle" class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
        <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
      </form>
    </x-slot>

    <x-slot name="thead">
      <tr>
        <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإعلان</th>
        <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المحتوى</th>
        <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
        <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الصلاحية</th>
        <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
      </tr>
    </x-slot>

    <x-slot name="rows">
      @forelse($ads as $index => $ad)
        @php
            $sInfo = $statusMeta[$ad->status] ?? ['label' => $ad->status, 'bg' => 'bg-slate-50', 'text' => 'text-slate-700', 'border' => 'border-slate-200', 'icon' => 'circle'];
            $startDate = $ad->start_date ? $ad->start_date->format('Y/m/d') : '-';
            $endDate   = $ad->end_date ? $ad->end_date->format('Y/m/d') : '-';
        @endphp
        <tr class="hover:bg-slate-50/50 transition-colors group">

          {{-- الإعلان: صورة + عنوان + id --}}
          <td class="px-6 py-3.5">
            <div class="flex items-center gap-3">
              <div class="w-14 h-14 rounded-xl overflow-hidden border border-slate-200 flex-shrink-0 bg-slate-50">
                @if($ad->image_url)
                  <img src="{{ $ad->getImageUrl() }}" alt="{{ $ad->title }}" class="w-full h-full object-cover" loading="lazy">
                @else
                  <div class="w-full h-full bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center">
                    <i data-lucide="image" class="w-6 h-6 text-slate-400"></i>
                  </div>
                @endif
              </div>
              <div class="min-w-0">
                <div class="font-bold text-slate-800 line-clamp-1">{{ $ad->title }}</div>
                <div class="text-[10px] font-bold text-slate-400 flex items-center gap-1 mt-0.5">
                  <i data-lucide="tag" class="w-3 h-3"></i>
                  #{{ str_pad(($ads->firstItem() + $index), 4, '0', STR_PAD_LEFT) }}
                </div>
              </div>
            </div>
          </td>

          {{-- المحتوى: وصف + رابط --}}
          <td class="px-6 py-3.5">
            <p class="text-xs text-slate-600 leading-relaxed max-w-[250px] line-clamp-2">{{ $ad->description ?? '—' }}</p>
            @if($ad->target_url)
              <a href="{{ $ad->target_url }}" target="_blank" rel="noopener noreferrer" class="mt-1.5 inline-flex items-center gap-1 text-[10px] font-bold text-teal-700 bg-teal-50 border border-teal-200 px-2 py-0.5 rounded-full hover:bg-teal-100 transition-colors">
                <i data-lucide="external-link" class="w-3 h-3"></i>
                رابط التوجيه
              </a>
            @endif
          </td>

          {{-- الحالة --}}
          <td class="px-6 py-3.5 text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-bold border {{ $sInfo['bg'] }} {{ $sInfo['text'] }} {{ $sInfo['border'] }}">
              <i data-lucide="{{ $sInfo['icon'] }}" class="w-3 h-3"></i>
              {{ $sInfo['label'] }}
            </span>
          </td>

          {{-- الصلاحية --}}
          <td class="px-6 py-3.5 text-center">
            <div class="flex flex-col items-center gap-1.5">
              <div class="flex items-center gap-1.5 text-[10px] text-slate-500">
                <i data-lucide="calendar" class="w-3 h-3 text-slate-400"></i>
                <span>من</span>
                <span class="font-mono font-bold text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded" dir="ltr">{{ $startDate }}</span>
              </div>
              <div class="flex items-center gap-1.5 text-[10px] text-slate-500">
                <i data-lucide="calendar" class="w-3 h-3 text-slate-400"></i>
                <span>إلى</span>
                <span class="font-mono font-bold text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded" dir="ltr">{{ $endDate }}</span>
              </div>
            </div>
          </td>

          {{-- الإجراءات --}}
          <td class="px-6 py-3.5 text-center">
            <div class="flex items-center justify-center gap-1.5">
              <x-action-button
                  variant="edit"
                  data-edit-modal="ad-form-modal"
                  data-edit-json="{{ json_encode([
                      'uuid'        => $ad->uuid,
                      'title'       => $ad->title,
                      'description' => $ad->description,
                      'target_url'  => $ad->target_url,
                      'status'      => $ad->status,
                      'start_date'  => $ad->start_date?->format('Y-m-d'),
                      'end_date'    => $ad->end_date?->format('Y-m-d'),
                      'image_url'   => $ad->getImageUrl(),
                      'created_at'  => $ad->created_at?->format('Y-m-d H:i:s'),
                      'updated_at'  => $ad->updated_at?->diffForHumans(),
                  ], JSON_UNESCAPED_UNICODE) }}"
                  :ariaLabel="'تعديل إعلان'"
              />
              <x-action-button
                  variant="delete"
                  data-delete-url="{{ route('admin.ads.destroy', $ad->uuid) }}"
                  data-delete-label="{{ $ad->title }}"
              />
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state
            icon="megaphone"
            :title="(request('search') || request('status')) ? 'لا توجد نتائج مطابقة' : 'لا توجد إعلانات'"
            :message="(request('search') || request('status')) ? 'جرّب تعديل كلمات البحث أو الفلتر' : 'ابدأ بإضافة أول إعلان ترويجي'"
            :colspan="5"
        >
          @if(!request('search') && !request('status'))
            <button
                type="button"
                data-create-modal="ad-form-modal"
                class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
            >
              <i data-lucide="plus-circle" class="w-4 h-4"></i>
              إضافة إعلان جديد
            </button>
          @endif
        </x-empty-state>
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$ads" />
    </x-slot>
  </x-data-table-card>
  </div>{{-- end #aracari-refreshable --}}

</div>

{{-- ======================================================================= --}}
{{-- ===== Ad Form Modal (Create + Edit) ===== --}}
{{-- ======================================================================= --}}
<x-form-modal
    id="ad-form-modal"
    title="إضافة إعلان جديد"
    :variant="'create'"
    :action="route('admin.ads.store')"
    method="POST"
    successMessage="تم حفظ الإعلان بنجاح"
    maxWidth="max-w-2xl"
    :hasFiles="true"
>

  {{-- القسم 1: معلومات الإعلان الأساسية --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs flex items-center gap-1.5" id="ad-section-header" style="color: #115128;">
        <i data-lucide="megaphone" class="w-3.5 h-3.5"></i>
        معلومات الإعلان الأساسية
      </h4>
      <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border text-rose-600 bg-rose-50 border-rose-200">مطلوب</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      {{-- العنوان --}}
      <div class="space-y-1.5 md:col-span-2">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="ad-title">
          <i data-lucide="type" class="w-3.5 h-3.5"></i>
          عنوان الإعلان <span class="text-rose-500">*</span>
        </label>
        <div class="relative">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
            <i data-lucide="type" class="w-4 h-4"></i>
          </div>
          <input
              type="text"
              name="title"
              id="ad-title"
              required
              maxlength="255"
              value="{{ old('title') }}"
              placeholder="أدخل عنوان الإعلان..."
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
          >
        </div>
      </div>

      {{-- الوصف --}}
      <div class="space-y-1.5 md:col-span-2">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="ad-description">
          <i data-lucide="align-right" class="w-3.5 h-3.5"></i>
          وصف الإعلان <span class="text-rose-500">*</span>
        </label>
        <div class="relative">
          <div class="absolute right-3 top-3 pointer-events-none text-slate-400">
            <i data-lucide="align-right" class="w-4 h-4"></i>
          </div>
          <textarea
              name="description"
              id="ad-description"
              rows="3"
              required
              placeholder="أدخل وصفاً للإعلان..."
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-medium outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all resize-none text-right"
          >{{ old('description') }}</textarea>
        </div>
      </div>

      {{-- رابط الهدف --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="ad-target-url">
          <i data-lucide="link" class="w-3.5 h-3.5"></i>
          رابط التوجيه
        </label>
        <div class="relative">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="link" class="w-4 h-4"></i>
          </div>
          <input
              type="url"
              name="target_url"
              id="ad-target-url"
              value="{{ old('target_url') }}"
              placeholder="https://example.com"
              dir="ltr"
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left"
          >
        </div>
        <p class="text-[10px] text-slate-500 leading-relaxed flex items-center gap-1">
          <i data-lucide="info" class="w-3 h-3 text-slate-400"></i>
          اختياري — رابط التوجيه عند الضغط على الإعلان
        </p>
      </div>

      {{-- الحالة --}}
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="ad-status">
          <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
          الحالة
        </label>
        <div class="relative">
          <select
              name="status"
              id="ad-status"
              class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all appearance-none cursor-pointer text-right"
          >
            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>نشط</option>
            <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>قيد المراجعة</option>
            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>متوقف</option>
            <option value="rejected" {{ old('status') == 'rejected' ? 'selected' : '' }}>مرفوض</option>
          </select>
          <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
        </div>
      </div>
    </div>
  </div>

  {{-- القسم 2: صورة الإعلان --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs flex items-center gap-1.5 text-purple-600">
        <i data-lucide="image" class="w-3.5 h-3.5"></i>
        صورة الإعلان
      </h4>
      <span id="ad-image-badge" class="text-[9px] font-bold text-purple-500 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded-full">مطلوب</span>
    </div>

    <div class="space-y-1.5">
      {{-- معاينة الصورة المرفوعة --}}
      <div id="ad-image-preview-wrap" style="display:none;" class="mb-3">
        <img id="ad-image-preview" src="" alt="معاينة الصورة" class="w-full max-h-48 object-contain rounded-xl border border-slate-200 bg-slate-50 p-2">
      </div>
      {{-- الصورة الحالية (edit mode) --}}
      <div id="ad-existing-image-wrap" style="display:none;" class="mb-3">
        <div class="relative inline-block">
          <img id="ad-existing-image" src="" alt="الصورة الحالية" class="max-h-40 rounded-xl border border-slate-200 object-contain">
          <button type="button" id="ad-remove-image-btn" class="absolute -top-2 -left-2 w-7 h-7 bg-rose-500 text-white rounded-full flex items-center justify-center shadow-md hover:bg-rose-600 transition-colors">
            <i data-lucide="x" class="w-3.5 h-3.5"></i>
          </button>
        </div>
        <p class="text-[10px] text-slate-500 mt-1">الصورة الحالية (ارفع صورة جديدة لاستبدالها)</p>
      </div>
      {{-- Dropzone --}}
      <label for="ad-image" class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-slate-300 rounded-xl bg-slate-50/50 cursor-pointer hover:bg-teal-50/50 hover:border-teal-300 transition-all group">
        <div class="flex flex-col items-center justify-center pt-5 pb-5">
          <i data-lucide="upload" class="w-8 h-8 text-slate-400 group-hover:text-teal-500 transition-colors mb-2"></i>
          <p class="text-xs font-bold text-slate-600 group-hover:text-teal-700">اضغط لرفع صورة</p>
          <p class="text-[10px] text-slate-400 mt-0.5">JPG, PNG, WebP — حد أقصى 5 ميجا</p>
        </div>
        <input type="file" name="image" id="ad-image" accept="image/jpeg,image/png,image/webp" class="hidden" required>
      </label>
      @error('image')
        <p class="text-[10px] text-rose-600 flex items-center gap-1">
          <i data-lucide="alert-circle" class="w-3 h-3"></i>
          {{ $message }}
        </p>
      @enderror
    </div>
  </div>

  {{-- القسم 3: الصلاحية الزمنية --}}
  <div class="space-y-4">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs flex items-center gap-1.5 text-blue-600">
        <i data-lucide="calendar-days" class="w-3.5 h-3.5"></i>
        الصلاحية الزمنية
      </h4>
      <span class="text-[9px] font-bold text-blue-500 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-full">مطلوب</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="ad-start-date">
          <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
          تاريخ البدء <span class="text-rose-500">*</span>
        </label>
        <input
            type="date"
            name="start_date"
            id="ad-start-date"
            required
            value="{{ old('start_date') }}"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all"
        >
      </div>
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="ad-end-date">
          <i data-lucide="calendar-clock" class="w-3.5 h-3.5"></i>
          تاريخ الانتهاء <span class="text-rose-500">*</span>
        </label>
        <input
            type="date"
            name="end_date"
            id="ad-end-date"
            required
            value="{{ old('end_date') }}"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all"
        >
      </div>
    </div>
  </div>

  {{-- القسم 4: معلومات إضافية (للتعديل فقط) --}}
  <div class="space-y-4" id="ad-extra-info" style="display:none;">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
      <h4 class="font-bold text-xs flex items-center gap-1.5 text-slate-600">
        <i data-lucide="info" class="w-3.5 h-3.5"></i>
        معلومات إضافية
      </h4>
      <span class="text-[9px] font-bold text-slate-500 bg-slate-50 border border-slate-200 px-2 py-0.5 rounded-full">للقراءة فقط</span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div class="flex items-center gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
        <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">تاريخ الإنشاء:</span>
        <span class="text-xs font-mono text-slate-600" dir="ltr" id="ad-info-created">—</span>
      </div>
      <div class="flex items-center gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
        <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-slate-400"></i>
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">آخر تحديث:</span>
        <span class="text-xs font-bold text-slate-600" id="ad-info-updated">—</span>
      </div>
    </div>
  </div>

  {{-- Actions: زر الحفظ فقط (زر إلغاء الافتراضي من form-modal يكفي) --}}
  <x-slot name="actions">
    <button
        type="submit"
        class="flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95"
        id="ad-submit-btn"
        style="background: linear-gradient(to right, #115128, #0f3d20);"
    >
      <i data-lucide="save" class="w-4 h-4"></i>
      <span id="ad-submit-text">حفظ الإعلان</span>
    </button>
  </x-slot>
</x-form-modal>

<x-delete-confirm />

@endsection

@push('scripts')
<script>
  // ===== Status filter =====
  (function() {
    var sel = document.getElementById('aracari-status-filter');
    if (!sel) return;
    sel.addEventListener('change', function() {
      var p = new URLSearchParams(window.location.search);
      var s = p.get('search');
      var url = window.location.pathname;
      var q = [];
      if (this.value) q.push('status=' + encodeURIComponent(this.value));
      if (s) q.push('search=' + encodeURIComponent(s));
      if (q.length) url += '?' + q.join('&');
      window.location.href = url;
    });
  })();

  // ===== Image preview =====
  (function() {
    var input = document.getElementById('ad-image');
    var wrap = document.getElementById('ad-image-preview-wrap');
    var img = document.getElementById('ad-image-preview');
    if (!input || !wrap || !img) return;
    input.addEventListener('change', function() {
      var f = this.files[0];
      if (f && f.type.startsWith('image/')) {
        var r = new FileReader();
        r.onload = function(e) { img.src = e.target.result; wrap.style.display = 'block'; };
        r.readAsDataURL(f);
      } else { wrap.style.display = 'none'; }
    });
  })();

  // ===== Ad form modal: dynamic create/edit =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'ad-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.uuid);

    if (isEdit) {
      // Update URL + method
      form.action = "{{ route('admin.ads.update', '__UUID__') }}".replace('__UUID__', data.uuid);
      var m = form.querySelector('input[name="_method"]');
      if (m) m.value = 'PUT';
      else { var el = document.createElement('input'); el.type = 'hidden'; el.name = '_method'; el.value = 'PUT'; el.setAttribute('data-dynamic','true'); form.appendChild(el); }

      // Banner amber
      var b = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (b) b.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';
      var t = modal.querySelector('.aracari-modal-panel h2');
      if (t) t.innerHTML = '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل الإعلان';
      var badge = modal.querySelector('.aracari-modal-panel span.text-\\[10px\\].font-bold');
      if (badge) { badge.textContent = 'تحديث'; badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30'; }

      document.getElementById('ad-submit-btn').style.background = 'linear-gradient(to right, #b45309, #92400e)';
      document.getElementById('ad-submit-text').textContent = 'حفظ التعديلات';

      // Fill fields
      var fm = { 'ad-title': data.title, 'ad-description': data.description, 'ad-target-url': data.target_url, 'ad-status': data.status, 'ad-start-date': data.start_date, 'ad-end-date': data.end_date };
      Object.keys(fm).forEach(function(id) { var el = document.getElementById(id); if (el && fm[id]) { if (el.tagName==='TEXTAREA') el.textContent=fm[id]; else el.value=fm[id]; } });

      // Existing image
      var ew = document.getElementById('ad-existing-image-wrap');
      var ei = document.getElementById('ad-existing-image');
      if (ew && ei && data.image_url) { ei.src = data.image_url; ew.style.display = 'block'; }

      // Extra info
      document.getElementById('ad-extra-info').style.display = '';
      document.getElementById('ad-info-created').textContent = data.created_at || '—';
      document.getElementById('ad-info-updated').textContent = data.updated_at || '—';

      // Image not required in edit
      document.getElementById('ad-image').removeAttribute('required');
      document.getElementById('ad-image-badge').textContent = 'اختياري';

    } else {
      form.action = "{{ route('admin.ads.store') }}";
      var dm = form.querySelector('input[data-dynamic="true"]');
      if (dm) dm.remove();

      var b = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (b) b.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
      var t = modal.querySelector('.aracari-modal-panel h2');
      if (t) t.innerHTML = '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة إعلان جديد';
      var badge = modal.querySelector('.aracari-modal-panel span.text-\\[10px\\].font-bold');
      if (badge) { badge.textContent = 'جديد'; badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30'; }

      document.getElementById('ad-submit-btn').style.background = 'linear-gradient(to right, #115128, #0f3d20)';
      document.getElementById('ad-submit-text').textContent = 'حفظ الإعلان';

      ['ad-title','ad-description','ad-target-url','ad-start-date','ad-end-date'].forEach(function(id) { var el = document.getElementById(id); if(el) el.value=''; });
      var se = document.getElementById('ad-status'); if(se) se.value='active';
      document.getElementById('ad-image-preview-wrap').style.display = 'none';
      document.getElementById('ad-existing-image-wrap').style.display = 'none';
      document.getElementById('ad-extra-info').style.display = 'none';
      document.getElementById('ad-image').setAttribute('required','');
      document.getElementById('ad-image-badge').textContent = 'مطلوب';
    }
    if (window.lucide) window.lucide.createIcons();
  });

  // ===== Remove existing image =====
  (function() {
    var btn = document.getElementById('ad-remove-image-btn');
    var wrap = document.getElementById('ad-existing-image-wrap');
    if (!btn||!wrap) return;
    btn.addEventListener('click', function() { wrap.style.display = 'none'; });
  })();
</script>
@endpush
