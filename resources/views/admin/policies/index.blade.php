@extends('layouts.admin')

@section('title', 'أركاري | إدارة السياسات')
@section('header_title', 'إدارة السياسات')

@section('content')

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="file-text"
      title="إدارة السياسات"
      :count="$policies->total()"
      countLabel="سياسة"
      description="إدارة سياسات الإلغاء والحجز وقواعدها"
      descriptionIcon="file-text"
  >
    <button
        type="button"
        data-create-modal="policy-form-modal"
        class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
    >
      <i data-lucide="plus-circle" class="w-4 h-4"></i>
      <span class="hidden sm:inline">إضافة سياسة جديدة</span>
    </button>
  </x-page-header>

  {{-- ===== 4 Stat Cards ===== --}}
  <div id="aracari-refreshable">
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    {{-- إجمالي السياسات --}}
    <x-stat-card
        :value="$stats['total']"
        label="إجمالي السياسات"
        icon="file-text"
        variant="slate"
        badge="مسجّلة"
        badgeIcon="trending-up"
    />
    {{-- سياسات الإلغاء --}}
    <x-stat-card
        :value="$stats['cancellation']"
        label="سياسات الإلغاء"
        icon="x-circle"
        variant="amber"
        badge="إلغاء"
        badgeIcon="x-circle"
    />
    {{-- سياسات الحجز --}}
    <x-stat-card
        :value="$stats['booking']"
        label="سياسات الحجز"
        icon="calendar-check"
        variant="blue"
        badge="حجز"
        badgeIcon="calendar-check"
    />
    {{-- إجمالي القواعد --}}
    <x-stat-card
        :value="$stats['total_rules']"
        label="إجمالي القواعد"
        icon="scale"
        variant="purple"
        badge="قاعدة"
        badgeIcon="scale"
    />
  </div>

  {{-- ===== Data Table ===== --}}
  <div class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-3xl overflow-hidden shadow-xl shadow-slate-200/20 max-w-full">
    {{-- شريط البحث والفلترة --}}
    <div class="p-5 border-b border-slate-200/70 bg-white/40 backdrop-blur flex flex-col sm:flex-row gap-4 items-center justify-between">
      <form method="GET" action="{{ route('admin.policies.index') }}" class="relative w-full sm:w-80" id="policy-search-form">
        <i data-lucide="search" class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
        <input
            type="text"
            name="search"
            value="{{ request('search', '') }}"
            placeholder="ابحث عن سياسة..."
            class="w-full h-11 bg-white/70 border border-slate-200 rounded-2xl pr-11 pl-4 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all duration-300"
        />
      </form>

      <div class="flex items-center gap-3 w-full sm:w-auto">
        {{-- فلتر النوع --}}
        <form method="GET" action="{{ route('admin.policies.index') }}" class="flex items-center gap-2" id="policy-type-filter-form">
          <select
              name="type"
              onchange="this.form.submit()"
              class="h-11 bg-white/70 border border-slate-200 rounded-xl px-3 text-xs font-bold text-slate-600 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all cursor-pointer"
          >
            <option value="">كل الأنواع</option>
            <option value="cancellation" {{ request('type') === 'cancellation' ? 'selected' : '' }}>إلغاء</option>
            <option value="booking" {{ request('type') === 'booking' ? 'selected' : '' }}>حجز</option>
            <option value="other" {{ request('type') === 'other' ? 'selected' : '' }}>أخرى</option>
          </select>
        </form>

        @if(request('search') || request('type'))
        <a
            href="{{ route('admin.policies.index') }}"
            class="h-11 px-4 bg-rose-50/80 border border-rose-200 text-rose-600 rounded-xl font-bold text-sm hover:bg-rose-500 hover:text-white hover:border-rose-500 transition-all flex items-center gap-1.5 flex-shrink-0"
        >
          <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
          <span class="hidden sm:inline">إعادة تعيين</span>
        </a>
        @endif
      </div>
    </div>

    {{-- الجدول --}}
    <div class="overflow-x-auto min-w-0">
      <table class="w-full min-w-[920px]">
        <thead class="bg-slate-50/70 border-b border-slate-200/70">
          <tr>
            <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
            <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الاسم</th>
            <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">النوع</th>
            <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">القواعد</th>
            <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الرحلات</th>
            <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
            <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @forelse($policies as $idx => $policy)
          @php
            $editData = [
              "uuid" => $policy->uuid,
              "name" => $policy->name,
              "description" => $policy->description,
              "type" => $policy->type,
              "rules" => $policy->rules->map(function($r) {
                return ["id" => $r->id, "from_hours" => (int) $r->from_hours, "to_hours" => (int) $r->to_hours, "fee_percentage" => (float) $r->fee_percentage];
              })->values()->toArray(),
              "created_at" => $policy->created_at?->format("Y-m-d H:i"),
              "updated_at" => $policy->updated_at?->format("Y-m-d H:i"),
            ];
          @endphp
          <tr class="hover:bg-slate-50/50 transition-colors group">
            {{-- # --}}
            <td class="px-6 py-4">
              <span class="font-mono font-black text-slate-400 text-sm">
                {{ str_pad(($policies->firstItem() + $idx), 4, '0', STR_PAD_LEFT) }}
              </span>
            </td>

            {{-- الاسم --}}
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500/20 to-teal-600/10 flex items-center justify-center border border-teal-100 group-hover:scale-110 transition-transform duration-300">
                  <i data-lucide="file-text" class="w-5 h-5 text-teal-600"></i>
                </div>
                <div class="min-w-0">
                  <div class="font-bold text-slate-800 truncate">{{ $policy->name }}</div>
                  <div class="text-[10px] font-bold text-slate-400 font-mono" dir="ltr">
                    #{{ substr($policy->uuid, 0, 12) }}
                  </div>
                </div>
              </div>
            </td>

            {{-- النوع --}}
            <td class="px-6 py-4 text-center">
              @php
                $typeMeta = [
                  'cancellation' => ['label' => 'إلغاء', 'class' => 'bg-amber-50 text-amber-700 border-amber-200', 'icon' => 'x-circle'],
                  'booking'      => ['label' => 'حجز', 'class' => 'bg-blue-50 text-blue-700 border-blue-200', 'icon' => 'calendar-check'],
                  'other'        => ['label' => 'أخرى', 'class' => 'bg-slate-100 text-slate-600 border-slate-200', 'icon' => 'file-text'],
                ];
                $tm = $typeMeta[$policy->type] ?? $typeMeta['other'];
              @endphp
              <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-[10px] font-bold border {{ $tm['class'] }}">
                <i data-lucide="{{ $tm['icon'] }}" class="w-3 h-3"></i>
                {{ $tm['label'] }}
              </span>
            </td>

            {{-- القواعد --}}
            <td class="px-6 py-4 text-center">
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold border border-slate-200">
                <i data-lucide="scale" class="w-3 h-3"></i>
                {{ $policy->rules->count() }} قاعدة
              </span>
            </td>

            {{-- الرحلات --}}
            <td class="px-6 py-4 text-center">
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold border border-slate-200">
                <i data-lucide="calendar-check" class="w-3 h-3"></i>
                {{ $policy->trips_count }} رحلة
              </span>
            </td>

            {{-- الحالة --}}
            <td class="px-6 py-4 text-center">
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <i data-lucide="check-circle" class="w-3 h-3"></i>
                نشطة
              </span>
            </td>

            {{-- الإجراءات --}}
            <td class="px-6 py-4 text-center">
              <div class="flex items-center justify-center gap-1.5">
                <button
                    type="button"
                    onclick="openPolicyDetails('{{ $policy->uuid }}')"
                    class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-teal-50 hover:text-teal-600 hover:border-teal-200 transition-all duration-300 shadow-sm flex items-center justify-center"
                    title="عرض"
                >
                  <i data-lucide="eye" class="w-4 h-4"></i>
                </button>
                <button
                    type="button"
                    data-edit-modal="policy-form-modal"
                    data-edit-json='@json($editData)'
                    class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200 transition-all duration-300 shadow-sm flex items-center justify-center"
                    title="تعديل"
                >
                  <i data-lucide="pencil" class="w-4 h-4"></i>
                </button>
                <button
                    type="button"
                    data-delete-url="{{ route('admin.policies.destroy', $policy->uuid) }}"
                    data-delete-name="{{ $policy->name }}"
                    class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all duration-300 shadow-sm flex items-center justify-center"
                    title="حذف"
                >
                  <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="px-6 py-16 text-center">
              <div class="w-20 h-20 rounded-full bg-slate-50 mx-auto flex items-center justify-center text-slate-300 mb-4">
                <i data-lucide="file-text" class="w-9 h-9"></i>
              </div>
              <h3 class="text-lg font-bold text-slate-700 mb-1">
                {{ (request('search') || request('type')) ? 'لا توجد نتائج مطابقة' : 'لا توجد سياسات مسجلة' }}
              </h3>
              <p class="text-slate-400 text-sm mb-4">
                {{ (request('search') || request('type')) ? 'جرّب تعديل كلمات البحث أو الفلتر' : 'أضف سياسات الإلغاء والحجز الجديدة لتنظيم قواعد الرحلات' }}
              </p>
              @if(!request('search') && !request('type'))
              <button
                  type="button"
                  data-create-modal="policy-form-modal"
                  class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
              >
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                إضافة سياسة جديدة
              </button>
              @endif
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- التذييل والترقيم --}}
    @if($policies->hasPages())
    <div class="px-6 py-4 border-t border-slate-200/70 bg-slate-50/40 flex flex-col sm:flex-row items-center justify-between gap-3">
      <p class="text-[10px] font-bold text-slate-400">
        عرض {{ $policies->count() }} من أصل {{ $policies->total() }} سياسة
      </p>
      {{ $policies->links('aracari::pagination.tailwind') }}
    </div>
    @endif
  </div>
  </div>

  {{-- ============================================= --}}
  {{-- ===== MODAL 1: Create / Edit Policy ===== --}}
  {{-- ============================================= --}}
  <x-form-modal
      id="policy-form-modal"
      title="إضافة سياسة جديدة"
      icon="plus-circle"
      :action="route('admin.policies.store')"
      method="POST"
      successMessage="تم حفظ السياسة بنجاح"
  >

    {{-- القسم 1: المعلومات الأساسية --}}
      <div class="space-y-4">
        <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
          <h4 class="font-bold text-xs flex items-center gap-1.5 text-[#115128]" id="policy-section1-heading">
            <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
            المعلومات الأساسية
          </h4>
          <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border text-rose-600 bg-rose-50 border-rose-200" id="policy-section1-badge">مطلوب</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          {{-- الاسم --}}
          <div class="space-y-1.5">
            <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="policy-name">
              <i data-lucide="badge" class="w-3.5 h-3.5"></i>
              الاسم <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
              <i data-lucide="badge" class="w-4 h-4 text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
              <input
                  id="policy-name"
                  type="text"
                  name="name"
                  value="{{ old('name') }}"
                  placeholder="مثال: سياسة الإلغاء القياسية"
                  class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 transition-all text-right"
                  required
              />
            </div>
            <p class="text-[10px] text-rose-600 flex items-center gap-1" data-error-for="name">
              <i data-lucide="alert-circle" class="w-3 h-3"></i>
              <span data-error-text></span>
            </p>
          </div>

          {{-- النوع --}}
          <div class="space-y-1.5">
            <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="policy-type">
              <i data-lucide="tag" class="w-3.5 h-3.5"></i>
              النوع <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
              <select
                  id="policy-type"
                  name="type"
                  onchange="handlePolicyTypeChange()"
                  class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-8 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 transition-all text-right appearance-none cursor-pointer"
                  required
              >
                <option value="cancellation">سياسة إلغاء</option>
                <option value="booking">سياسة حجز</option>
                <option value="other">أخرى</option>
              </select>
              <i data-lucide="tag" class="w-4 h-4 text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
              <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
            </div>
            <p class="text-[10px] text-rose-600 flex items-center gap-1" data-error-for="type">
              <i data-lucide="alert-circle" class="w-3 h-3"></i>
              <span data-error-text></span>
            </p>
          </div>

          {{-- الوصف --}}
          <div class="md:col-span-2 space-y-1.5">
            <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="policy-description">
              <i data-lucide="sticky-note" class="w-3.5 h-3.5"></i>
              الوصف
            </label>
            <div class="relative">
              <i data-lucide="sticky-note" class="w-4 h-4 text-slate-400 absolute right-3.5 top-3.5 pointer-events-none"></i>
              <textarea
                  id="policy-description"
                  name="description"
                  rows="3"
                  placeholder="وصف مختصر للسياسة وشروط تطبيقها..."
                  class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 transition-all text-right resize-none"
              >{{ old('description') }}</textarea>
            </div>
          </div>
        </div>
      </div>

      {{-- القسم 2: قواعد الإلغاء (cancellation only) --}}
      <div class="space-y-4" id="policy-rules-section">
        <div class="flex items-center justify-between border-b border-[#eeeef0] pb-2">
          <h4 class="font-bold text-xs flex items-center gap-1.5 text-amber-600">
            <i data-lucide="ruler" class="w-3.5 h-3.5"></i>
            قواعد الإلغاء
          </h4>
          <span class="text-[9px] font-bold text-amber-600 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">
            تحديد نسب الرسوم حسب الفترات الزمنية
          </span>
        </div>

        <div class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-600 text-[11px] flex items-center gap-1.5" data-error-for="rules">
          <i data-lucide="alert-circle" class="w-3 h-3 flex-shrink-0"></i>
          <span class="font-bold" data-error-text></span>
        </div>

        <div class="flex items-center justify-between">
          <p class="text-[10px] text-slate-500 font-medium">
            مثال: من 0 إلى 24 ساعة = رسوم 50%
          </p>
          <button
              type="button"
              onclick="addPolicyRule()"
              class="inline-flex items-center gap-1 text-[11px] font-bold text-white bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 px-3 py-1.5 rounded-lg shadow-md shadow-emerald-500/20 transition-all"
          >
            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
            إضافة قاعدة
          </button>
        </div>

        {{-- حاوية القواعد الديناميكية --}}
        <div id="policy-rules-container" class="space-y-3">
          {{-- قاعدة 01 --}}
          <div class="policy-rule-item bg-slate-50/50 border border-slate-200/70 rounded-2xl p-4 relative" data-rule-index="0">
            <div class="flex items-center justify-between mb-3">
              <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">
                <i data-lucide="ruler" class="w-3 h-3"></i>
                قاعدة <span class="rule-number">01</span>
              </span>
              <button
                  type="button"
                  onclick="removePolicyRule(this)"
                  class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white border border-rose-200 hover:border-rose-500 transition-all flex items-center justify-center"
                  title="حذف القاعدة"
              >
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
              </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <div class="space-y-1">
                <label class="block text-[10px] font-bold text-slate-600 flex items-center gap-1">
                  <i data-lucide="clock" class="w-3 h-3"></i>
                  من (ساعة) <span class="text-rose-500">*</span>
                </label>
                <input
                    type="number"
                    name="rules[0][from_hours]"
                    min="0"
                    dir="ltr"
                    value="0"
                    class="w-full bg-white border border-[#e2e2e5] rounded-xl pr-3 pl-3 py-2 text-[13px] font-bold outline-none focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 transition-all text-left"
                />
              </div>
              <div class="space-y-1">
                <label class="block text-[10px] font-bold text-slate-600 flex items-center gap-1">
                  <i data-lucide="clock" class="w-3 h-3"></i>
                  إلى (ساعة) <span class="text-rose-500">*</span>
                </label>
                <input
                    type="number"
                    name="rules[0][to_hours]"
                    min="0"
                    dir="ltr"
                    value="24"
                    class="w-full bg-white border border-[#e2e2e5] rounded-xl pr-3 pl-3 py-2 text-[13px] font-bold outline-none focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 transition-all text-left"
                />
              </div>
              <div class="space-y-1">
                <label class="block text-[10px] font-bold text-slate-600 flex items-center gap-1">
                  <i data-lucide="percent" class="w-3 h-3"></i>
                  نسبة الرسوم (%) <span class="text-rose-500">*</span>
                </label>
                <input
                    type="number"
                    name="rules[0][fee_percentage]"
                    min="0"
                    max="100"
                    dir="ltr"
                    value="50"
                    class="w-full bg-white border border-[#e2e2e5] rounded-xl pr-3 pl-3 py-2 text-[13px] font-bold outline-none focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 transition-all text-left"
                />
              </div>
            </div>
            <p class="mt-2 text-[10px] text-slate-400 font-medium rule-description">
              من <span class="font-mono font-bold text-slate-500" dir="ltr">0</span> إلى <span class="font-mono font-bold text-slate-500" dir="ltr">24</span> ساعة = رسوم <span class="font-mono font-bold text-amber-600" dir="ltr">50%</span>
            </p>
          </div>
        </div>
      </div>

      {{-- القسم 3: معلومات إضافية (للتعديل فقط) --}}
      <div class="space-y-4" id="policy-extra-info" style="display:none;">
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
            <span class="text-xs font-mono text-slate-600" dir="ltr" id="policy-info-created">—</span>
          </div>
          <div class="flex items-center gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
            <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-slate-400"></i>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">آخر تحديث:</span>
            <span class="text-xs font-bold text-slate-600" id="policy-info-updated">—</span>
          </div>
        </div>
      </div>

      {{-- Actions --}}
      <x-slot name="actions">
        {{--<button
            type="button"
            data-close-modal
            class="flex-1 bg-[#f3f3f6] hover:bg-[#eeeef0] text-gray-600 font-bold py-3 px-4 rounded-xl text-xs transition-all flex items-center justify-center gap-2"
        >
          <i data-lucide="x" class="w-4 h-4"></i>
          <span id="policy-cancel-text">إلغاء</span>--}}
        </button>
        <button
            type="submit"
            class="flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95"
            id="policy-submit-btn"
            style="background: linear-gradient(to right, #115128, #0d3d1e);"
        >
          <i data-lucide="save" class="w-4 h-4"></i>
          <span id="policy-submit-text">حفظ السياسة</span>
        </button>
    </x-slot>
  </x-form-modal>

  {{-- ============================================= --}}
  {{-- ===== MODAL 2: Policy Details ===== --}}
  {{-- ============================================= --}}
  <div
      id="policy-details-modal"
      class="fixed inset-0 z-[80] hidden overflow-y-auto font-cairo flex items-center justify-center p-3 sm:p-4"
  >
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-[#1a1c1e]/60 backdrop-blur-sm" onclick="closePolicyDetails()"></div>

    {{-- Container --}}
    <div class="relative bg-white rounded-2xl max-w-3xl w-full shadow-2xl overflow-hidden border border-[#eeeef0] flex flex-col max-h-[92vh] text-right" dir="rtl">
      {{-- Banner Header --}}
      <div class="relative h-24 bg-gradient-to-r from-teal-900 to-teal-950 text-white p-5 flex flex-col justify-end">
        <div class="absolute top-4 left-4">
          <button
              onclick="closePolicyDetails()"
              class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur text-white flex items-center justify-center transition-all"
          >
            <i data-lucide="x" class="w-5 h-5"></i>
          </button>
        </div>
        <div class="relative z-10 space-y-1 text-right">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="bg-white/10 text-white text-[10px] font-mono font-bold px-2.5 py-1 rounded-full" dir="ltr" id="detail-uuid">—</span>
          </div>
          <h2 class="text-lg sm:text-xl font-extrabold tracking-tight flex items-center gap-2">
            <i data-lucide="file-text" class="w-6 h-6"></i>
            <span id="detail-name">—</span>
          </h2>
          <p class="text-xs text-teal-100/70 font-medium">
            عرض معلومات السياسة وقواعد الإلغاء والرحلات المرتبطة
          </p>
        </div>
      </div>

      {{-- Body (scrollable) --}}
      <div class="p-4 sm:p-6 space-y-5 max-h-[75vh] overflow-y-auto text-right">
        {{-- القسم 1: معلومات السياسة + إحصائيات --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          {{-- معلومات السياسة --}}
          <div class="lg:col-span-2 bg-white border border-[#eeeef0] rounded-2xl p-4 sm:p-5 shadow-sm">
            <div class="flex items-center justify-between border-b border-[#eeeef0] pb-3 mb-4">
              <h4 class="font-bold text-sm text-slate-800 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center text-white shadow-md">
                  <i data-lucide="info" class="w-4 h-4"></i>
                </div>
                معلومات السياسة
              </h4>
              <span class="text-[9px] font-bold text-teal-600 bg-teal-50 border border-teal-200 px-2 py-0.5 rounded-full">سياسة</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              {{-- الاسم --}}
              <div class="flex items-center justify-between gap-2 p-4 bg-slate-50/50 rounded-2xl border border-slate-200/70">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                  <i data-lucide="file-text" class="w-3.5 h-3.5 text-slate-400"></i>
                  الاسم
                </span>
                <div class="font-bold text-slate-700 text-sm" id="detail-name-val">—</div>
              </div>
              {{-- النوع --}}
              <div class="flex items-center justify-between gap-2 p-4 bg-slate-50/50 rounded-2xl border border-slate-200/70">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                  <i data-lucide="tag" class="w-3.5 h-3.5 text-slate-400"></i>
                  النوع
                </span>
                <div id="detail-type-badge">—</div>
              </div>
              {{-- المنشئ --}}
              <div class="flex items-center justify-between gap-2 p-4 bg-slate-50/50 rounded-2xl border border-slate-200/70">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                  <i data-lucide="badge-check" class="w-3.5 h-3.5 text-slate-400"></i>
                  تم الإنشاء بواسطة
                </span>
                <div class="font-bold text-slate-700 text-sm flex items-center gap-1" id="detail-creator">
                  <i data-lucide="badge-check" class="w-3.5 h-3.5 text-teal-500"></i>
                  —
                </div>
              </div>
              {{-- تاريخ الإنشاء --}}
              <div class="flex items-center justify-between gap-2 p-4 bg-slate-50/50 rounded-2xl border border-slate-200/70">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                  <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
                  تاريخ الإنشاء
                </span>
                <div class="font-mono text-xs text-slate-600" dir="ltr" id="detail-created-at">—</div>
              </div>
              {{-- آخر تحديث --}}
              <div class="flex items-center justify-between gap-2 p-4 bg-slate-50/50 rounded-2xl border border-slate-200/70">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                  <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-slate-400"></i>
                  آخر تحديث
                </span>
                <div class="font-mono text-xs text-slate-600" dir="ltr" id="detail-updated-at">—</div>
              </div>
              {{-- الوصف --}}
              <div class="md:col-span-2 flex items-center justify-between gap-2 p-4 bg-slate-50/50 rounded-2xl border border-slate-200/70">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1 flex-shrink-0">
                  <i data-lucide="file-text" class="w-3.5 h-3.5 text-slate-400"></i>
                  الوصف
                </span>
                <div class="text-xs text-slate-600 leading-relaxed" id="detail-description">لا يوجد وصف</div>
              </div>
            </div>
          </div>

          {{-- إحصائيات + حاسبة الرسوم --}}
          <div class="space-y-4">
            {{-- إحصائيات --}}
            <div class="bg-white border border-[#eeeef0] rounded-2xl p-4 sm:p-5 shadow-sm">
              <div class="flex items-center justify-between border-b border-[#eeeef0] pb-3 mb-3">
                <h4 class="font-bold text-sm text-slate-800 flex items-center gap-2">
                  <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center text-white shadow-md">
                    <i data-lucide="calculator" class="w-4 h-4"></i>
                  </div>
                  إحصائيات
                </h4>
              </div>
              <div class="grid grid-cols-2 gap-2.5">
                <div class="p-3 rounded-xl bg-teal-50/60 border border-teal-100 text-center">
                  <p class="text-[10px] font-bold text-teal-700 uppercase tracking-wider">عدد القواعد</p>
                  <p class="text-2xl font-extrabold text-teal-600 mt-1" id="detail-rules-count">0</p>
                </div>
                <div class="p-3 rounded-xl bg-amber-50/60 border border-amber-100 text-center">
                  <p class="text-[10px] font-bold text-amber-700 uppercase tracking-wider">الرحلات المرتبطة</p>
                  <p class="text-2xl font-extrabold text-amber-600 mt-1" id="detail-trips-count">0</p>
                </div>
              </div>
            </div>

            {{-- حاسبة الرسوم (إلغاء فقط) --}}
            <div class="bg-amber-50/60 border border-amber-200 rounded-2xl p-4 shadow-sm" id="detail-fee-calculator" style="display:none;">
              <div class="flex items-center justify-between border-b border-amber-200/70 pb-2 mb-3">
                <h4 class="font-bold text-xs text-amber-700 flex items-center gap-1.5">
                  <i data-lucide="calculator" class="w-4 h-4"></i>
                  حاسبة الرسوم
                </h4>
                <span class="text-[9px] font-bold text-amber-600 bg-amber-100 border border-amber-200 px-1.5 py-0.5 rounded-full">إلغاء</span>
              </div>
              <label class="block text-[10px] font-bold text-amber-700 mb-1.5">عدد الساعات قبل المغادرة</label>
              <input
                  type="number"
                  id="calc-hours"
                  min="0"
                  dir="ltr"
                  placeholder="مثال: 36"
                  class="w-full bg-white border border-amber-200 rounded-xl pr-3 pl-3 py-2.5 text-[13px] font-bold outline-none focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 transition-all text-left mb-2"
              />
              <button
                  type="button"
                  onclick="calculateFee()"
                  class="w-full py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-amber-500/20 flex items-center justify-center gap-1.5"
              >
                <i data-lucide="calculator" class="w-3.5 h-3.5"></i>
                احسب الرسوم
              </button>
              <div id="calc-result" class="mt-3 p-3 rounded-xl bg-white border border-amber-200" style="display:none;"></div>
            </div>
          </div>
        </div>

        {{-- قواعد السياسة --}}
        <div id="detail-rules-section" style="display:none;">
          <div class="bg-white border border-[#eeeef0] rounded-2xl p-4 sm:p-5 shadow-sm">
            <div class="flex items-center justify-between border-b border-[#eeeef0] pb-3 mb-3">
              <h4 class="font-bold text-sm text-slate-800 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center text-white shadow-md">
                  <i data-lucide="ruler" class="w-4 h-4"></i>
                </div>
                قواعد السياسة
              </h4>
              <span class="text-[9px] font-bold text-teal-600 bg-teal-50 border border-teal-200 px-2 py-0.5 rounded-full" id="detail-rules-badge">0 قاعدة</span>
            </div>
            <div class="overflow-x-auto min-w-0 -mx-1">
              <table class="w-full min-w-[520px]">
                <thead class="bg-slate-50/70 border-b border-slate-200/70">
                  <tr>
                    <th class="px-4 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
                    <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">من (ساعة)</th>
                    <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">إلى (ساعة)</th>
                    <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">نسبة الرسوم</th>
                    <th class="px-4 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الوصف</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" id="detail-rules-body"></tbody>
              </table>
            </div>
          </div>
        </div>

        {{-- الرحلات المرتبطة --}}
        <div id="detail-trips-section" style="display:none;">
          <div class="bg-white border border-[#eeeef0] rounded-2xl p-4 sm:p-5 shadow-sm">
            <div class="flex items-center justify-between border-b border-[#eeeef0] pb-3 mb-3">
              <h4 class="font-bold text-sm text-slate-800 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center text-white shadow-md">
                  <i data-lucide="bus" class="w-4 h-4"></i>
                </div>
                الرحلات المرتبطة
              </h4>
              <span class="text-[9px] font-bold text-amber-600 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full" id="detail-trips-badge">0 رحلة</span>
            </div>
            <div class="overflow-x-auto min-w-0 -mx-1">
              <table class="w-full min-w-[560px]">
                <thead class="bg-slate-50/70 border-b border-slate-200/70">
                  <tr>
                    <th class="px-4 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
                    <th class="px-4 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الرحلة</th>
                    <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">من</th>
                    <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">إلى</th>
                    <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">وقت المغادرة</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" id="detail-trips-body"></tbody>
              </table>
            </div>
            <div id="detail-trips-more" class="mt-3 text-center" style="display:none;">
              <p class="text-xs font-bold text-slate-500 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 inline-block"></p>
            </div>
          </div>
        </div>
      </div>

      {{-- Footer --}}
      <div class="p-4 border-t border-[#eeeef0] bg-[#f9f9fc] flex justify-end gap-2">
        <button
            onclick="closePolicyDetails()"
            class="px-5 py-2.5 bg-[#f3f3f6] hover:bg-[#eeeef0] text-gray-600 font-bold text-xs rounded-xl transition-all flex items-center gap-1.5"
        >
          <i data-lucide="x" class="w-3.5 h-3.5"></i>
          إغلاق
        </button>
        <button
            onclick="editFromDetails()"
            id="detail-edit-btn"
            class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-bold text-xs rounded-xl transition-all flex items-center gap-1.5 shadow-md shadow-amber-500/20"
        >
          <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
          تعديل
        </button>
      </div>
    </div>
  </div>

  <x-delete-confirm />

</div>
@endsection

@push('scripts')
<script>
  // ===== Global state for details =====
  var currentDetailPolicy = null;

  function formatDateTime(dt) {
    if (!dt) return '—';
    try {
      var d = new Date(dt);
      var y = d.getFullYear();
      var m = String(d.getMonth() + 1).padStart(2, '0');
      var day = String(d.getDate()).padStart(2, '0');
      var h = String(d.getHours()).padStart(2, '0');
      var min = String(d.getMinutes()).padStart(2, '0');
      return y + '-' + m + '-' + day + ' ' + h + ':' + min;
    } catch(e) { return dt; }
  }

  // ===== Policy type change handler =====
  function handlePolicyTypeChange() {
    var type = document.getElementById('policy-type').value;
    var rulesSection = document.getElementById('policy-rules-section');
    rulesSection.style.display = (type === 'cancellation') ? '' : 'none';
  }

  // Initialize
  (function() {
    var type = document.getElementById('policy-type').value;
    if (type !== 'cancellation') {
      document.getElementById('policy-rules-section').style.display = 'none';
    }
  })();

  // ===== Dynamic rules: add =====
  var policyRuleCounter = 1;
  function addPolicyRule(fromH, toH, fee, ruleId) {
    var container = document.getElementById('policy-rules-container');
    var idx = policyRuleCounter++;
    var num = String(idx + 1).padStart(2, '0');
    var fH = (fromH !== undefined) ? fromH : 0;
    var tH = (toH !== undefined) ? toH : 24;
    var fP = (fee !== undefined) ? fee : 50;
    var rId = (ruleId !== undefined && ruleId) ? '<input type="hidden" name="rules[' + idx + '][id]" value="' + ruleId + '">' : '';

    var html = '<div class="policy-rule-item bg-slate-50/50 border border-slate-200/70 rounded-2xl p-4 relative" data-rule-index="' + idx + '">' +
      rId +
      '<div class="flex items-center justify-between mb-3">' +
        '<span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">' +
          '<i data-lucide="ruler" class="w-3 h-3"></i> قاعدة <span class="rule-number">' + num + '</span>' +
        '</span>' +
        '<button type="button" onclick="removePolicyRule(this)" class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white border border-rose-200 hover:border-rose-500 transition-all flex items-center justify-center" title="حذف القاعدة">' +
          '<i data-lucide="trash-2" class="w-3.5 h-3.5"></i>' +
        '</button>' +
      '</div>' +
      '<div class="grid grid-cols-1 md:grid-cols-3 gap-3">' +
        '<div class="space-y-1">' +
          '<label class="block text-[10px] font-bold text-slate-600 flex items-center gap-1"><i data-lucide="clock" class="w-3 h-3"></i> من (ساعة) <span class="text-rose-500">*</span></label>' +
          '<input type="number" name="rules[' + idx + '][from_hours]" min="0" dir="ltr" value="' + fH + '" class="w-full bg-white border border-[#e2e2e5] rounded-xl pr-3 pl-3 py-2 text-[13px] font-bold outline-none focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 transition-all text-left" />' +
        '</div>' +
        '<div class="space-y-1">' +
          '<label class="block text-[10px] font-bold text-slate-600 flex items-center gap-1"><i data-lucide="clock" class="w-3 h-3"></i> إلى (ساعة) <span class="text-rose-500">*</span></label>' +
          '<input type="number" name="rules[' + idx + '][to_hours]" min="0" dir="ltr" value="' + tH + '" class="w-full bg-white border border-[#e2e2e5] rounded-xl pr-3 pl-3 py-2 text-[13px] font-bold outline-none focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 transition-all text-left" />' +
        '</div>' +
        '<div class="space-y-1">' +
          '<label class="block text-[10px] font-bold text-slate-600 flex items-center gap-1"><i data-lucide="percent" class="w-3 h-3"></i> نسبة الرسوم (%) <span class="text-rose-500">*</span></label>' +
          '<input type="number" name="rules[' + idx + '][fee_percentage]" min="0" max="100" dir="ltr" value="' + fP + '" class="w-full bg-white border border-[#e2e2e5] rounded-xl pr-3 pl-3 py-2 text-[13px] font-bold outline-none focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 transition-all text-left" />' +
        '</div>' +
      '</div>' +
      '<p class="mt-2 text-[10px] text-slate-400 font-medium rule-description">من <span class="font-mono font-bold text-slate-500" dir="ltr">' + fH + '</span> إلى <span class="font-mono font-bold text-slate-500" dir="ltr">' + tH + '</span> ساعة = رسوم <span class="font-mono font-bold text-amber-600" dir="ltr">' + fP + '%</span></p>' +
    '</div>';

    container.insertAdjacentHTML('beforeend', html);
    if (window.lucide) window.lucide.createIcons();
  }

  // ===== Dynamic rules: remove =====
  function removePolicyRule(btn) {
    var container = document.getElementById('policy-rules-container');
    var items = container.querySelectorAll('.policy-rule-item');
    if (items.length <= 1) {
      alert('يجب وجود قاعدة إلغاء واحدة على الأقل.');
      return;
    }
    btn.closest('.policy-rule-item').remove();
    renumberRules();
  }

  function renumberRules() {
    var items = document.querySelectorAll('#policy-rules-container .policy-rule-item');
    items.forEach(function(item, i) {
      var numEl = item.querySelector('.rule-number');
      if (numEl) numEl.textContent = String(i + 1).padStart(2, '0');
    });
  }

  // ===== Modal open: dynamic create/edit =====
  document.addEventListener('aracari:modal-open', function(e) {
    var modal = e.target;
    if (modal.id !== 'policy-form-modal') return;
    var data = e.detail.data || {};
    var form = modal.querySelector('form');
    var isEdit = !!(data.uuid);

    if (isEdit) {
      // Update URL + method
      form.action = "{{ route('admin.policies.update', '__UUID__') }}".replace('__UUID__', data.uuid);
      var m = form.querySelector('input[name="_method"]');
      if (m) m.value = 'PUT';
      else { var el = document.createElement('input'); el.type = 'hidden'; el.name = '_method'; el.value = 'PUT'; el.setAttribute('data-dynamic','true'); form.appendChild(el); }

      // Banner amber for edit
      var b = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (b) b.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';
      var t = modal.querySelector('.aracari-modal-panel h2');
      if (t) t.innerHTML = '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل السياسة';

      document.getElementById('policy-submit-btn').style.background = 'linear-gradient(to right, #b45309, #92400e)';
      document.getElementById('policy-submit-text').textContent = 'حفظ التعديلات';
      document.getElementById('policy-cancel-text').textContent = 'إلغاء';
      document.getElementById('policy-section1-heading').className = 'font-bold text-xs flex items-center gap-1.5 text-amber-600';
      document.getElementById('policy-section1-badge').className = 'text-[9px] font-bold px-2 py-0.5 rounded-full border text-amber-600 bg-amber-50 border-amber-200';
      document.getElementById('policy-section1-badge').textContent = 'قابل للتعديل';

      // Fill fields
      document.getElementById('policy-name').value = data.name || '';
      document.getElementById('policy-type').value = data.type || 'cancellation';
      document.getElementById('policy-description').value = data.description || '';
      handlePolicyTypeChange();

      // Fill rules
      var container = document.getElementById('policy-rules-container');
      container.innerHTML = '';
      policyRuleCounter = 0;

      if (data.type === 'cancellation' && data.rules && data.rules.length > 0) {
        data.rules.forEach(function(rule, i) {
          addPolicyRule(rule.from_hours, rule.to_hours, rule.fee_percentage, rule.id);
        });
      } else {
        // Default empty rule
        policyRuleCounter = 0;
        addPolicyRule();
      }

      // Extra info
      document.getElementById('policy-extra-info').style.display = '';
      document.getElementById('policy-info-created').textContent = data.created_at || '—';
      document.getElementById('policy-info-updated').textContent = data.updated_at || '—';

    } else {
      // Create mode
      form.action = "{{ route('admin.policies.store') }}";
      var dm = form.querySelector('input[data-dynamic="true"]');
      if (dm) dm.remove();

      var b = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (b) b.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
      var t = modal.querySelector('.aracari-modal-panel h2');
      if (t) t.innerHTML = '<i data-lucide="plus-circle" class="w-5 h-5"></i> إضافة سياسة جديدة';

      document.getElementById('policy-submit-btn').style.background = 'linear-gradient(to right, #115128, #0d3d1e)';
      document.getElementById('policy-submit-text').textContent = 'حفظ السياسة';
      document.getElementById('policy-cancel-text').textContent = 'إعادة تعيين';
      document.getElementById('policy-section1-heading').className = 'font-bold text-xs flex items-center gap-1.5 text-[#115128]';
      document.getElementById('policy-section1-badge').className = 'text-[9px] font-bold px-2 py-0.5 rounded-full border text-rose-600 bg-rose-50 border-rose-200';
      document.getElementById('policy-section1-badge').textContent = 'مطلوب';

      document.getElementById('policy-name').value = '';
      document.getElementById('policy-type').value = 'cancellation';
      document.getElementById('policy-description').value = '';

      var container = document.getElementById('policy-rules-container');
      container.innerHTML = '';
      policyRuleCounter = 0;
      addPolicyRule();
      handlePolicyTypeChange();

      document.getElementById('policy-extra-info').style.display = 'none';
    }
    if (window.lucide) window.lucide.createIcons();
  });

  // ===== Details Modal =====
  var typeBadgeMeta = {
    cancellation: { label: 'إلغاء', cls: 'bg-amber-50 text-amber-700 border-amber-200', icon: 'x-circle' },
    booking:      { label: 'حجز', cls: 'bg-blue-50 text-blue-700 border-blue-200', icon: 'calendar-check' },
    other:        { label: 'أخرى', cls: 'bg-slate-100 text-slate-600 border-slate-200', icon: 'file-text' },
  };

  function openPolicyDetails(uuid) {
    fetch("{{ route('admin.policies.show.json', '__UUID__') }}".replace('__UUID__', uuid), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
      if (!res.success) return;
      var p = res.data;
      currentDetailPolicy = p;

      document.getElementById('detail-uuid').textContent = '#' + p.uuid.slice(0, 12);
      document.getElementById('detail-name').textContent = p.name;
      document.getElementById('detail-name-val').textContent = p.name;

      // Type badge
      var tm = typeBadgeMeta[p.type] || typeBadgeMeta.other;
      document.getElementById('detail-type-badge').innerHTML =
        '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border ' + tm.cls + '">' +
        '<i data-lucide="' + tm.icon + '" class="w-2.5 h-2.5"></i> ' + tm.label + '</span>';

      // Creator
      document.getElementById('detail-creator').innerHTML =
        '<span class="text-sm font-bold text-slate-700 inline-flex items-center gap-1">' +
        (p.creator ? p.creator.name : '—') +
        ' <i data-lucide="badge-check" class="w-3.5 h-3.5 text-teal-500"></i></span>';

      document.getElementById('detail-created-at').textContent = formatDateTime(p.created_at);
      document.getElementById('detail-updated-at').textContent = formatDateTime(p.updated_at);
      document.getElementById('detail-description').textContent = p.description || 'لا يوجد وصف';

      // Stats
      document.getElementById('detail-rules-count').textContent = p.rules ? p.rules.length : 0;
      document.getElementById('detail-trips-count').textContent = p.trips ? p.trips.length : 0;

      // Fee calculator
      var calcDiv = document.getElementById('detail-fee-calculator');
      calcDiv.style.display = (p.type === 'cancellation') ? '' : 'none';
      document.getElementById('calc-hours').value = '';
      document.getElementById('calc-result').style.display = 'none';

      // Rules table
      var rulesSection = document.getElementById('detail-rules-section');
      var rulesBody = document.getElementById('detail-rules-body');
      if (p.rules && p.rules.length > 0) {
        rulesSection.style.display = '';
        document.getElementById('detail-rules-badge').textContent = p.rules.length + ' قاعدة';
        var rulesHtml = '';
        p.rules.forEach(function(rule, i) {
          var feeLabel = (parseFloat(rule.fee_percentage) === 0) ? 'إلغاء مجاني' : parseFloat(rule.fee_percentage) + '% رسوم';
          var barWidth = Math.min(100, Math.max(2, parseFloat(rule.fee_percentage)));
          var feeClass = (parseFloat(rule.fee_percentage) === 0) ? 'text-emerald-600' : 'text-teal-600';
          rulesHtml += '<tr class="hover:bg-slate-50/50 transition-colors">' +
            '<td class="px-4 py-3"><span class="font-mono font-black text-slate-400 text-sm">' + String(i + 1).padStart(2, '0') + '</span></td>' +
            '<td class="px-4 py-3 text-center"><span class="text-sm font-mono font-bold text-slate-700" dir="ltr">' + rule.from_hours + '</span></td>' +
            '<td class="px-4 py-3 text-center"><span class="text-sm font-mono font-bold text-slate-700" dir="ltr">' + rule.to_hours + '</span></td>' +
            '<td class="px-4 py-3"><div class="flex items-center gap-2 min-w-[140px]">' +
              '<div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden"><div class="h-full bg-gradient-to-r from-teal-400 to-teal-600 rounded-full transition-all" style="width:' + barWidth + '%"></div></div>' +
              '<span class="text-xs font-bold font-mono ' + feeClass + '" dir="ltr">' + rule.fee_percentage + '%</span>' +
            '</div></td>' +
            '<td class="px-4 py-3 text-right"><span class="text-[11px] font-medium text-slate-500">' + feeLabel + '</span></td>' +
          '</tr>';
        });
        rulesBody.innerHTML = rulesHtml;
      } else {
        rulesSection.style.display = 'none';
      }

      // Trips table
      var tripsSection = document.getElementById('detail-trips-section');
      var tripsBody = document.getElementById('detail-trips-body');
      var tripsMore = document.getElementById('detail-trips-more');
      if (p.trips && p.trips.length > 0) {
        tripsSection.style.display = '';
        document.getElementById('detail-trips-badge').textContent = p.trips.length + ' رحلة';
        var visible = p.trips.slice(0, 10);
        var remaining = Math.max(0, p.trips.length - 10);
        var tripsHtml = '';
        visible.forEach(function(trip, i) {
          tripsHtml += '<tr class="hover:bg-slate-50/50 transition-colors">' +
            '<td class="px-4 py-3"><span class="font-mono font-black text-slate-400 text-sm">' + String(i + 1).padStart(2, '0') + '</span></td>' +
            '<td class="px-4 py-3"><div class="flex items-center gap-2">' +
              '<div class="w-7 h-7 rounded-lg bg-amber-50 border border-amber-100 flex items-center justify-center flex-shrink-0"><i data-lucide="route" class="w-3.5 h-3.5 text-amber-600"></i></div>' +
              '<span class="text-sm font-bold font-mono text-slate-700" dir="ltr">' + (trip.trip_name_id || '—') + '</span>' +
            '</div></td>' +
            '<td class="px-4 py-3 text-center"><span class="text-sm font-bold text-slate-700">' + (trip.departure_city ? trip.departure_city.name : '—') + '</span></td>' +
            '<td class="px-4 py-3 text-center"><span class="text-sm font-bold text-slate-700">' + (trip.destination_city ? trip.destination_city.name : '—') + '</span></td>' +
            '<td class="px-4 py-3 text-center"><span class="text-xs font-mono font-bold text-slate-600 inline-flex items-center gap-1" dir="ltr"><i data-lucide="clock" class="w-3 h-3 text-slate-400"></i>' + formatDateTime(trip.departure_time) + '</span></td>' +
          '</tr>';
        });
        tripsBody.innerHTML = tripsHtml;

        if (remaining > 0) {
          tripsMore.style.display = '';
          tripsMore.querySelector('p').textContent = 'و ' + remaining + ' رحلات أخرى...';
        } else {
          tripsMore.style.display = 'none';
        }
      } else {
        tripsSection.style.display = 'none';
      }

      // Edit button
      document.getElementById('detail-edit-btn').setAttribute('onclick',
        "closePolicyDetails(); setTimeout(function(){ document.querySelector('[data-edit-modal=\"policy-form-modal\"][data-edit-uuid=\"" + p.uuid + "\"]').click(); }, 300);"
      );

      // Show modal
      document.getElementById('policy-details-modal').classList.remove('hidden');
      document.body.style.overflow = 'hidden';
      if (window.lucide) window.lucide.createIcons();
    });
  }

  function closePolicyDetails() {
    document.getElementById('policy-details-modal').classList.add('hidden');
    document.body.style.overflow = '';
    currentDetailPolicy = null;
  }

  function editFromDetails() {
    if (currentDetailPolicy) {
      closePolicyDetails();
      var editBtn = document.querySelector('[data-edit-modal="policy-form-modal"][data-edit-uuid="' + currentDetailPolicy.uuid + '"]');
      if (editBtn) {
        setTimeout(function() { editBtn.click(); }, 300);
      }
    }
  }

  // ===== Fee Calculator (client-side) =====
  function calculateFee() {
    var hours = document.getElementById('calc-hours').value;
    var resultDiv = document.getElementById('calc-result');
    resultDiv.style.display = '';

    if (!hours || isNaN(Number(hours)) || Number(hours) < 0) {
      resultDiv.innerHTML = '<p class="text-[11px] font-bold text-rose-600 text-center">أدخل قيمة ساعات صحيحة (رقم موجب)</p>';
      return;
    }

    var h = Number(hours);
    var match = null;
    if (currentDetailPolicy && currentDetailPolicy.rules) {
      for (var i = 0; i < currentDetailPolicy.rules.length; i++) {
        var r = currentDetailPolicy.rules[i];
        if (h >= parseFloat(r.from_hours) && h < parseFloat(r.to_hours)) {
          match = r;
          break;
        }
      }
    }

    if (match) {
      resultDiv.innerHTML =
        '<div class="flex items-center justify-between gap-2">' +
          '<div class="min-w-0"><p class="text-[10px] font-bold text-amber-700 uppercase tracking-wider">نسبة الرسوم المطبقة</p>' +
          '<p class="text-[10px] text-slate-500 mt-0.5">للإلغاء قبل <span class="font-bold font-mono" dir="ltr">' + hours + '</span> ساعة من المغادرة</p></div>' +
          '<span class="text-2xl font-black text-amber-600" dir="ltr">' + match.fee_percentage + '%</span>' +
        '</div>';
    } else {
      resultDiv.innerHTML =
        '<div class="flex items-center justify-between gap-2">' +
          '<div class="min-w-0"><p class="text-[10px] font-bold text-amber-700 uppercase tracking-wider">نسبة الرسوم المطبقة</p>' +
          '<p class="text-[10px] text-slate-500 mt-0.5">للإلغاء قبل <span class="font-bold font-mono" dir="ltr">' + hours + '</span> ساعة من المغادرة</p></div>' +
          '<span class="text-2xl font-black text-amber-600" dir="ltr">0%</span>' +
        '</div>';
    }
  }
</script>
@endpush
