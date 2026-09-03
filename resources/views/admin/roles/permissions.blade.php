@extends('layouts.admin')

@section('title', 'أركاري | صلاحيات ' . $role->name)
@section('header_title', 'صلاحيات الدور')

@section('content')

@php
    $isSystemRole = in_array($role->name, \App\Constants\UserRoles::systemRoles());
    $totalAvailable = $permissions->count();
    $totalAssigned  = count($rolePermissions);
    $totalModules   = $permissionGroups->count();
    $isSuperAdmin   = auth()->user() && auth()->user()->hasRole('super-admin');
@endphp

<div class="space-y-6 font-cairo text-right">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="key"
      title="صلاحيات الدور"
      :count="$totalAssigned"
      countLabel="صلاحية ممنوحة"
      description="تحديد الصلاحيات المتاحة للدور"
      descriptionIcon="shield-check"
  >
    <a
        href="{{ route('admin.roles.index') }}"
        class="inline-flex items-center gap-2 bg-[#f3f3f6] hover:bg-[#eeeef0] text-gray-600 px-4 py-2.5 rounded-xl font-bold text-sm transition-all"
    >
      <i data-lucide="arrow-right" class="w-4 h-4"></i>
      العودة للأدوار
    </a>
  </x-page-header>

  {{-- ===== Stats Cards ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <x-stat-card :value="$totalAssigned" label="صلاحيات ممنوحة" icon="check-circle" variant="teal" badge="من {{$totalAvailable}}" badgeIcon="key" />
    <x-stat-card :value="$totalAvailable - $totalAssigned" label="صلاحيات غير ممنوحة" icon="x-circle" variant="slate" />
    <x-stat-card :value="$totalModules" label="وحدة / نموذج" icon="layers" variant="purple" />
  </div>

  {{-- ===== Role Info Card ===== --}}
  <div class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-3xl shadow-xl shadow-slate-200/20 overflow-hidden">
    <div class="relative h-20 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-purple-900 to-purple-950">
      <div class="relative z-10 flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center">
          <i data-lucide="user-cog" class="w-5 h-5"></i>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <h2 class="text-base font-extrabold" dir="ltr">{{ $role->name }}</h2>
            @if($isSystemRole)
              <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30">
                دور نظام
              </span>
            @else
              <span class="text-[9px] font-bold px-2 py-0.5 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30">
                دور مخصص
              </span>
            @endif
          </div>
          <p class="text-[11px] text-white/60 mt-0.5">
            {{ $role->description ?: 'لا يوجد وصف' }}
            @if($role->relationLoaded('users'))
              &middot; {{ $role->users->count() }} مستخدم مرتبط
            @elseif($role->users_count ?? null)
              &middot; {{ $role->users_count }} مستخدم مرتبط
            @endif
          </p>
        </div>
      </div>
    </div>

    {{-- Form Body --}}
    <form method="POST" action="{{ route('admin.roles.assign-permissions', $role) }}" id="permissions-form" class="p-5 space-y-6">
      @csrf

      {{-- Error summary --}}
      @if($errors->any())
        <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-600 text-xs flex items-start gap-2">
          <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
          <div>
            <div class="font-bold mb-1">يرجى تصحيح الأخطاء التالية:</div>
            <ul class="list-disc list-inside text-rose-500/80 space-y-0.5">
              @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        </div>
      @endif

      {{-- ===== Toolbar ===== --}}
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-2 flex-wrap">
          <button type="button" id="btn-select-all" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-600 hover:bg-teal-50 hover:text-teal-700 hover:border-teal-200 transition-all">
            <i data-lucide="check-square" class="w-3.5 h-3.5"></i>
            تحديد الكل
          </button>
          <button type="button" id="btn-deselect-all" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-600 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all">
            <i data-lucide="square" class="w-3.5 h-3.5"></i>
            إلغاء الكل
          </button>
          <span class="text-[10px] text-slate-400 font-bold" id="selection-counter">{{ $totalAssigned }} من {{ $totalAvailable }}</span>
        </div>
        <div class="relative">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="search" class="w-3.5 h-3.5"></i>
          </div>
          <input
            type="text"
            id="permissions-search"
            placeholder="بحث في الصلاحيات..."
            class="w-56 bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-9 pl-3 py-2 text-[12px] font-medium outline-none focus:bg-white focus:ring-4 focus:ring-purple-500/10 focus:border-purple-400 transition-all text-right"
          >
        </div>
      </div>

      {{-- ===== Permission Groups Container ===== --}}
      <div class="space-y-8 overflow-hidden">

      @foreach($permissionGroups as $moduleLabel => $groupPermissions)
        <div class="permission-group " data-module="{{ $moduleLabel }}">

          {{-- Module Header --}}
          <button type="button" class="module-header w-full flex items-center justify-between p-3.5 rounded-2xl bg-[#f9f9fc] border border-slate-200/70 hover:bg-slate-50 transition-all group">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-md shadow-purple-500/15">
                <i data-lucide="folder" class="w-4 h-4 text-white"></i>
              </div>
              <div class="text-right">
                <h3 class="font-bold text-sm text-slate-700">{{ $moduleLabel }}</h3>
                <p class="text-[10px] text-slate-400 font-bold">
                  <span class="module-assigned-count">{{ $groupPermissions->filter(fn($p) => in_array($p->id, $rolePermissions))->count() }}</span>
                  من {{ $groupPermissions->count() }} صلاحية
                </p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              {{-- Select all for module --}}
              <span role="button" tabindex="0" class="module-select-all w-7 h-7 rounded-lg border border-slate-200 bg-white flex items-center justify-center text-slate-400 hover:bg-purple-50 hover:text-purple-600 hover:border-purple-200 transition-all cursor-pointer" title="تحديد كل صلاحيات هذه الوحدة">
                <i data-lucide="check" class="w-3.5 h-3.5"></i>
              </span>
              <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-hover:text-slate-600 transition-colors module-chevron transition-transform duration-200"></i>
            </div>
          </button>

          {{-- Permissions Grid --}}
          <div class="module-permissions grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 mt-2 px-1">
            @foreach($groupPermissions as $permission)
              @php
                $isChecked = in_array($permission->id, $rolePermissions);
                $actionColor = match($permission->action_label) {
                  'عرض'   => 'emerald',
                  'إنشاء'  => 'teal',
                  'تعديل'  => 'amber',
                  'حذف'   => 'rose',
                  default  => 'slate',
                };
              @endphp

              <label
                class="permission-item flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all duration-200
                  {{ $isChecked
                    ? 'bg-purple-50/60 border-purple-200 shadow-sm shadow-purple-100/50'
                    : 'bg-white border-slate-200/70 hover:bg-slate-50 hover:border-slate-300'
                  }}"
                data-permission-id="{{ $permission->id }}"
                data-permission-name="{{ $permission->display_description }}"
              >
                {{-- Custom Checkbox --}}
                <div class="relative flex-shrink-0">
                  <input
                    type="checkbox"
                    name="permissions[]"
                    value="{{ $permission->id }}"
                    {{ $isChecked ? 'checked' : '' }}
                    class="peer sr-only"
                  >
                  <div class="w-5 h-5 rounded-lg border-2 flex items-center justify-center transition-all duration-200
                    {{ $isChecked
                      ? 'bg-purple-600 border-purple-600'
                      : 'border-slate-300 peer-hover:border-purple-400'
                    }}">
                    <i data-lucide="check" class="w-3 h-3 text-white {{ $isChecked ? '' : 'opacity-0' }} transition-opacity"></i>
                  </div>
                </div>

                {{-- Permission Info --}}
                <div class="flex-1 min-w-0">
                  <p class="text-[12px] font-bold text-slate-700 leading-snug">{{ $permission->display_description }}</p>
                  <p class="text-[9px] text-slate-400 font-mono mt-0.5 truncate" dir="ltr">{{ $permission->name }}</p>
                </div>

                {{-- Action Badge --}}
                <span class="flex-shrink-0 text-[9px] font-bold px-2 py-0.5 rounded-full border
                  bg-{{ $actionColor }}-50 text-{{ $actionColor }}-600 border-{{ $actionColor }}-200">
                  {{ $permission->action_label }}
                </span>
              </label>

            @endforeach
          </div>
        </div>

      @endforeach

      </div>{{-- / Permission Groups Container --}}

      {{-- ===== Submit Section ===== --}}
      <div class="flex items-center justify-between pt-2 border-t border-slate-100">
        <div class="text-[11px] text-slate-400 flex items-center gap-1.5">
          <i data-lucide="info" class="w-3.5 h-3.5"></i>
          @if($isSystemRole && !$isSuperAdmin)
            <span class="text-amber-600 font-bold">تحذير:</span> تعديل صلاحيات دور النظام يتطلب صلاحية المدير العام
          @else
            تم اختيار <span id="final-counter" class="font-bold text-purple-600">{{ $totalAssigned }}</span> صلاحية من أصل {{ $totalAvailable }}
          @endif
        </div>
        <div class="flex gap-3">
          <a
            href="{{ route('admin.roles.index') }}"
            class="bg-[#f3f3f6] hover:bg-[#eeeef0] text-gray-600 font-bold py-2.5 px-5 rounded-xl text-xs transition-all flex items-center gap-2"
          >
            <i data-lucide="x" class="w-4 h-4"></i>
            إلغاء
          </a>
          <button
            type="submit"
            class="text-white font-bold py-2.5 px-6 rounded-xl text-xs transition-all shadow-lg shadow-purple-500/20 flex items-center gap-2 active:scale-95 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800"
          >
            <i data-lucide="save" class="w-4 h-4"></i>
            حفظ الصلاحيات
          </button>
        </div>
      </div>

    </form>
  </div>

</div>

@push('scripts')
<script>
(function() {
  var form = document.getElementById('permissions-form');
  if (!form) return;

  var allCheckboxes = form.querySelectorAll('input[name="permissions[]"]');
  var selectAllBtn  = document.getElementById('btn-select-all');
  var deselectAllBtn = document.getElementById('btn-deselect-all');
  var searchInput   = document.getElementById('permissions-search');
  var selectionCounter = document.getElementById('selection-counter');
  var finalCounter  = document.getElementById('final-counter');

  // ===== Counter update =====
  function updateCounters() {
    var checked = form.querySelectorAll('input[name="permissions[]"]:checked').length;
    var total   = allCheckboxes.length;
    if (selectionCounter) selectionCounter.textContent = checked + ' من ' + total;
    if (finalCounter)    finalCounter.textContent = checked;
    // update module counts
    form.querySelectorAll('.permission-group').forEach(function(group) {
      var groupCheckboxes = group.querySelectorAll('input[name="permissions[]"]');
      var groupChecked    = group.querySelectorAll('input[name="permissions[]"]:checked').length;
      var countEl = group.querySelector('.module-assigned-count');
      if (countEl) countEl.textContent = groupChecked;
      // update checkbox visual
      groupCheckboxes.forEach(function(cb) {
        var label = cb.closest('.permission-item');
        if (!label) return;
        var box = label.querySelector('div > div');
        var icon = label.querySelector('div i[data-lucide="check"]');
        if (cb.checked) {
          label.classList.remove('bg-white', 'border-slate-200/70', 'hover:bg-slate-50', 'hover:border-slate-300');
          label.classList.add('bg-purple-50/60', 'border-purple-200', 'shadow-sm', 'shadow-purple-100/50');
          if (box) { box.classList.remove('border-slate-300'); box.classList.add('bg-purple-600', 'border-purple-600'); }
          if (icon) icon.classList.remove('opacity-0');
        } else {
          label.classList.remove('bg-purple-50/60', 'border-purple-200', 'shadow-sm', 'shadow-purple-100/50');
          label.classList.add('bg-white', 'border-slate-200/70', 'hover:bg-slate-50', 'hover:border-slate-300');
          if (box) { box.classList.remove('bg-purple-600', 'border-purple-600'); box.classList.add('border-slate-300'); }
          if (icon) icon.classList.add('opacity-0');
        }
      });
    });
  }

  // ===== Select / Deselect All =====
  if (selectAllBtn) {
    selectAllBtn.addEventListener('click', function() {
      allCheckboxes.forEach(function(cb) { cb.checked = true; });
      updateCounters();
    });
  }
  if (deselectAllBtn) {
    deselectAllBtn.addEventListener('click', function() {
      allCheckboxes.forEach(function(cb) { cb.checked = false; });
      updateCounters();
    });
  }

  // ===== Checkbox change =====
  allCheckboxes.forEach(function(cb) {
    cb.addEventListener('change', updateCounters);
  });

  // ===== Module toggle =====
  form.querySelectorAll('.module-header').forEach(function(header) {
    header.addEventListener('click', function(e) {
      if (e.target.closest('.module-select-all')) return;
      var group = header.closest('.permission-group');
      var perms = group.querySelector('.module-permissions');
      var chevron = header.querySelector('.module-chevron');
      if (perms.style.display === 'none') {
        perms.style.display = '';
        if (chevron) chevron.style.transform = '';
      } else {
        perms.style.display = 'none';
        if (chevron) chevron.style.transform = 'rotate(-90deg)';
      }
    });
  });

  // ===== Module select all =====
  form.querySelectorAll('.module-select-all').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      var group = btn.closest('.permission-group');
      var cbs = group.querySelectorAll('input[name="permissions[]"]');
      var allChecked = Array.from(cbs).every(function(cb) { return cb.checked; });
      cbs.forEach(function(cb) { cb.checked = !allChecked; });
      updateCounters();
    });
  });

  // ===== Search =====
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      var q = searchInput.value.trim().toLowerCase();
      form.querySelectorAll('.permission-group').forEach(function(group) {
        var items = group.querySelectorAll('.permission-item');
        var visibleCount = 0;
        items.forEach(function(item) {
          var name  = (item.getAttribute('data-permission-name') || '').toLowerCase();
          var match = !q || name.indexOf(q) !== -1;
          item.style.display = match ? '' : 'none';
          if (match) visibleCount++;
        });
        group.style.display = visibleCount > 0 ? '' : 'none';
        // auto-expand if searching
        if (q && visibleCount > 0) {
          var perms = group.querySelector('.module-permissions');
          var chevron = group.querySelector('.module-chevron');
          if (perms) perms.style.display = '';
          if (chevron) chevron.style.transform = '';
        }
      });
    });
  }

  // Initial counter
  updateCounters();

  // re-render lucide icons
  if (window.lucide) window.lucide.createIcons();
})();
</script>
@endpush

@endsection
