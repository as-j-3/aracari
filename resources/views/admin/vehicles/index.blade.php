@extends('layouts.admin')

@section('title', 'أركاري | المركبات')
@section('header_title', 'المركبات')

@section('content')

@php
    $allItems = $vehicles->getCollection();
    $totalFleet    = $vehicles->total();
    $activeCount   = $allItems->filter(fn($v) => $v->status === 'active')->count();
    $maintenanceCount = $allItems->filter(fn($v) => $v->status === 'maintenance')->count();
    $inactiveCount = $allItems->filter(fn($v) => $v->status === 'inactive')->count();
    $totalSeats    = $allItems->sum('seats_count');

    $vehicles->getCollection()->each(function($v) {
        $v->loadMissing(['user', 'vehicleType', 'seats']);
    });
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
    icon="bus"
    title="المركبات"
    :count="$totalFleet"
    countLabel="مركبة"
    description="الإطلاع على أسطول المركبات وتصنيفاتها"
    descriptionIcon="bus"
  />

  <div id="aracari-refreshable" class="space-y-6">

  {{-- ===== Stat Cards ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    <x-stat-card :value="$totalFleet" label="إجمالي الأسطول" icon="bus" variant="slate" badge="مركبة" badgeIcon="bus" />
    <x-stat-card :value="$activeCount" label="نشطة" icon="check-circle" variant="emerald" badge="جاهزة" badgeIcon="check-circle" />
    <x-stat-card :value="$totalSeats" label="السعة الاستيعابية" icon="users" variant="blue" badge="مقعد" badgeIcon="users" />
    <x-stat-card :value="($maintenanceCount + $inactiveCount)" label="صيانة ومتوقفة" icon="alert-triangle" variant="amber" :badge="$maintenanceCount . ' صيانة · ' . $inactiveCount . ' متوقفة'" badgeIcon="alert-triangle" />
  </div>

  {{-- ===== Data Table ===== --}}
  <x-data-table-card :searchable="true" :searchMode="'server'" searchPlaceholder="ابحث عن مركبة أو موديل..." :footerInfo="'عرض ' . $vehicles->count() . ' من أصل ' . $vehicles->total() . ' مركبة'">

    <x-slot name="filters">
      <select name="status" form="aracari-search-form" class="h-11 bg-white/70 border border-slate-200 rounded-2xl px-4 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all appearance-none cursor-pointer text-right w-full sm:w-auto">
        <option value="" {{ !request('status') ? 'selected' : '' }}>كل الحالات</option>
        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>نشطة</option>
        <option value="maintenance" {{ request('status') === 'maintenance' ? 'selected' : '' }}>صيانة</option>
        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>متوقفة</option>
      </select>
      <select name="vehicle_type_id" form="aracari-search-form" class="h-11 bg-white/70 border border-slate-200 rounded-2xl px-4 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all appearance-none cursor-pointer text-right w-full sm:w-auto">
        <option value="" {{ !request('vehicle_type_id') ? 'selected' : '' }}>كل الفئات</option>
        @foreach($vehicleTypes as $vt)
          <option value="{{ $vt->id }}" {{ request('vehicle_type_id') == $vt->id ? 'selected' : '' }}>{{ $vt->type_name ?? $vt->name }}</option>
        @endforeach
      </select>
      @if($providers && $providers->isNotEmpty())
      <select name="provider_id" form="aracari-search-form" class="h-11 bg-white/70 border border-slate-200 rounded-2xl px-4 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all appearance-none cursor-pointer text-right w-full sm:w-auto">
        <option value="" {{ !request('provider_id') ? 'selected' : '' }}>كل المزودين</option>
        @foreach($providers as $p)
          <option value="{{ $p->id }}" {{ request('provider_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
        @endforeach
      </select>
      @endif
    </x-slot>

    <x-slot name="thead">
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المركبة والموديل</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المزود</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الفئة</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">السعة</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">عرض</th>
    </x-slot>

    <x-slot name="rows">
      @forelse($vehicles as $vehicle)
        @php
          $statusConfig = [
            'active'      => ['bg' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'label' => 'نشطة'],
            'maintenance' => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200', 'label' => 'صيانة'],
            'inactive'    => ['bg' => 'bg-rose-50 text-rose-700 border-rose-200', 'label' => 'متوقفة'],
          ];
          $sCfg = $statusConfig[$vehicle->status] ?? $statusConfig['inactive'];

          $providerName = $vehicle->user?->providerService?->name ?? $vehicle->user?->name ?? '—';
          $providerInitial = mb_substr($providerName, 0, 1);
          $typeName = optional($vehicle->vehicleType)->type_name ?? 'عام';

          $seatsCollection = $vehicle->relationLoaded('seats') ? $vehicle->seats : collect();
          $availableSeats  = $seatsCollection->where('status', 'available')->count();
          $bookedSeats     = $seatsCollection->where('status', 'booked')->count();
          $maintenanceSeats = $seatsCollection->where('status', 'maintenance')->count();

          $modalData = [
              'name'             => $vehicle->name ?? 'مركبة #' . $vehicle->id,
              'model'            => $vehicle->model ?? '',
              'type_name'        => $typeName,
              'provider_name'    => $providerName,
              'status'           => $sCfg['label'],
              'status_bg'        => $sCfg['bg'],
              'seats_total'      => (string) ($vehicle->seats_count ?? 0),
              'seats_available'  => (string) $availableSeats,
              'seats_booked'     => (string) $bookedSeats,
              'seats_maintenance' => (string) $maintenanceSeats,
              'outside_photo'    => $vehicle->outside_photo_url ?? '',
              'inside_photo'     => $vehicle->inside_photo_url ?? '',
              'uuid'             => $vehicle->uuid ?? '',
              'created_at'       => optional($vehicle->created_at)->translatedFormat('j F، Y الساعة H:i') ?? '—',
              'updated_at'       => optional($vehicle->updated_at)->translatedFormat('j F، Y الساعة H:i') ?? '—',
          ];
        @endphp

        <tr class="hover:bg-slate-50/50 transition-colors group">
          {{-- # --}}
          <td class="px-6 py-4">
            <span class="font-mono font-black text-slate-400 text-sm">{{ str_pad((string) $vehicle->id, 4, '0', STR_PAD_LEFT) }}</span>
          </td>

          {{-- المركبة والموديل --}}
          <td data-searchable class="px-6 py-4">
            <div class="flex items-center gap-3">
              @if($vehicle->outside_photo_url)
                <div class="w-11 h-11 rounded-xl overflow-hidden border border-slate-200 shadow-sm flex-shrink-0">
                  <img src="{{ $vehicle->outside_photo_url }}" class="w-full h-full object-cover" alt="{{ $vehicle->name }}" referrerpolicy="no-referrer">
                </div>
              @else
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-teal-500/20 to-teal-600/10 flex items-center justify-center border border-teal-100 flex-shrink-0">
                  <i data-lucide="bus" class="w-5 h-5 text-teal-600"></i>
                </div>
              @endif
              <div>
                <div class="font-bold text-slate-800 text-sm">{{ $vehicle->name ?? 'مركبة #' . $vehicle->id }}</div>
                <div class="text-[10px] font-medium text-slate-400 font-mono">{{ $vehicle->model }}</div>
              </div>
            </div>
          </td>

          {{-- المزود --}}
          <td data-searchable class="px-6 py-4">
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 text-[10px] font-bold shadow-sm flex-shrink-0">{{ $providerInitial }}</div>
              <span class="text-xs font-bold text-slate-700">{{ $providerName }}</span>
            </div>
          </td>

          {{-- الفئة --}}
          <td class="px-6 py-4">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-black bg-slate-100 text-slate-700 border border-slate-200">
              <i data-lucide="tag" class="w-3.5 h-3.5 text-slate-500"></i>
              {{ $typeName }}
            </span>
          </td>

          {{-- السعة --}}
          <td class="px-6 py-4 text-center">
            <span class="font-mono font-black text-slate-800 text-sm">{{ $vehicle->seats_count }}</span>
            <span class="text-[10px] text-slate-400 font-medium mr-1">مقعد</span>
          </td>

          {{-- الحالة --}}
          <td class="px-6 py-4 text-center">
            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-[10px] font-bold border {{ $sCfg['bg'] }}">
              <span class="w-1.5 h-1.5 rounded-full @if($vehicle->status === 'active') bg-emerald-500 @elseif($vehicle->status === 'maintenance') bg-amber-500 @else bg-rose-500 @endif"></span>
              {{ $sCfg['label'] }}
            </span>
          </td>

          {{-- عرض --}}
          <td class="px-6 py-4 text-center">
            <x-action-button variant="view" data-view-modal="vehicle-details-modal" data-view-json="{{ json_encode($modalData, JSON_UNESCAPED_UNICODE) }}" />
          </td>
        </tr>
      @empty
        <x-empty-state icon="bus" :title="request('search') || request('status') || request('vehicle_type_id') || request('provider_id') ? 'لا توجد نتائج مطابقة' : 'لا توجد مركبات مسجلة'" :message="request('search') || request('status') || request('vehicle_type_id') || request('provider_id') ? 'جرّب تعديل خيارات البحث' : 'سيظهر هنا المركبات عندما يقوم المزودون بإضافتها'" :colspan="7" />
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$vehicles" />
    </x-slot>
  </x-data-table-card>

  </div>{{-- end #aracari-refreshable -->}
</div>

{{-- ========================================================== --}}
{{-- ===== MODAL: Vehicle Details (View Only) ===== --}}
{{-- ========================================================== --}}
<x-modal id="vehicle-details-modal" title="تفاصيل المركبة" variant="view" icon="bus" :static="true" maxWidth="max-w-3xl">

  {{-- 1. Vehicle Basic Info --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="p-5 bg-white border border-[#eeeef0] rounded-2xl shadow-sm space-y-3">
      <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1"><i data-lucide="bus" class="w-4 h-4 text-teal-600"></i><span>بيانات المركبة</span></h4>
      <div class="space-y-2.5">
        <div class="flex justify-between items-center py-1 border-b border-slate-50 text-xs"><span class="text-slate-500 font-medium">اسم المركبة:</span><span class="font-black text-slate-800" data-preview="name">—</span></div>
        <div class="flex justify-between items-center py-1 border-b border-slate-50 text-xs"><span class="text-slate-500 font-medium">الموديل:</span><span class="font-black text-slate-800 font-mono text-[11px]" data-preview="model">—</span></div>
        <div class="flex justify-between items-center py-1 border-b border-slate-50 text-xs"><span class="text-slate-500 font-medium">التصنيف:</span><span class="font-black text-slate-800" data-preview="type_name">—</span></div>
        <div class="flex justify-between items-center py-1 text-xs"><span class="text-slate-500 font-medium">مزود الخدمة:</span><span class="font-black text-slate-800" data-preview="provider_name">—</span></div>
      </div>
    </div>

    <div class="p-5 bg-white border border-[#eeeef0] rounded-2xl shadow-sm space-y-3">
      <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1"><i data-lucide="activity" class="w-4 h-4 text-emerald-600"></i><span>الحالة والسعة</span></h4>
      <div class="space-y-2.5">
        <div class="flex justify-between items-center py-1 border-b border-slate-50 text-xs"><span class="text-slate-500 font-medium">الحالة التشغيلية:</span><span class="font-black" data-preview="status">—</span></div>
        <div class="flex justify-between items-center py-1 border-b border-slate-50 text-xs"><span class="text-slate-500 font-medium">السعة الكلية:</span><span class="font-black text-slate-800"><span data-preview="seats_total">0</span> مقعد</span></div>
        <div class="flex justify-between items-center py-1 text-xs"><span class="text-slate-500 font-medium">متاحة للحجز:</span><span class="font-black text-emerald-700"><span data-preview="seats_available">0</span> مقعد</span></div>
      </div>
    </div>
  </div>

  {{-- 2. Seat Distribution --}}
  <div class="p-5 bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl border border-white/5">
    <h4 class="font-bold text-xs text-slate-300 uppercase tracking-wider flex items-center gap-1.5 mb-4">
      <i data-lucide="users" class="w-4 h-4 text-teal-400"></i>
      <span class="text-white">توزيع المقاعد</span>
    </h4>
    <div class="grid grid-cols-3 gap-3 mb-4">
      <div class="bg-white/10 rounded-xl p-3 text-center border border-white/5">
        <p class="text-2xl font-black text-emerald-400 font-mono" data-preview="seats_available">0</p>
        <p class="text-[10px] text-slate-300 font-bold mt-1">متاحة</p>
      </div>
      <div class="bg-white/10 rounded-xl p-3 text-center border border-white/5">
        <p class="text-2xl font-black text-blue-400 font-mono" data-preview="seats_booked">0</p>
        <p class="text-[10px] text-slate-300 font-bold mt-1">محجوزة</p>
      </div>
      <div class="bg-white/10 rounded-xl p-3 text-center border border-white/5">
        <p class="text-2xl font-black text-amber-400 font-mono" data-preview="seats_maintenance">0</p>
        <p class="text-[10px] text-slate-300 font-bold mt-1">صيانة</p>
      </div>
    </div>
    {{-- Progress bar --}}
    <div class="w-full h-2.5 bg-white/10 rounded-full overflow-hidden flex" id="detail-seats-bar"></div>
  </div>

  {{-- 3. Photos --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="detail-photos-section" style="display:none;">
    <div class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-50">
      <p class="text-[10px] font-bold text-slate-400 bg-slate-100 px-4 py-2 border-b border-slate-200">الصورة الخارجية</p>
      <div class="p-3"><img id="detail-outside-photo" src="" class="w-full h-40 object-cover rounded-xl" alt="خارجية"></div>
    </div>
    <div class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-50">
      <p class="text-[10px] font-bold text-slate-400 bg-slate-100 px-4 py-2 border-b border-slate-200">الصورة الداخلية</p>
      <div class="p-3"><img id="detail-inside-photo" src="" class="w-full h-40 object-cover rounded-xl" alt="داخلية"></div>
    </div>
  </div>

  {{-- 4. Metadata --}}
  <div class="p-4 bg-slate-50 rounded-2xl border border-[#eeeef0]">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-[10px]">
      <div><span class="text-slate-400 font-bold block">الرمز</span><span class="text-slate-700 font-bold">#<span data-preview="id">—</span></span></div>
      <div><span class="text-slate-400 font-bold block">UUID</span><span class="text-slate-700 font-bold font-mono text-[9px]" data-preview="uuid">—</span></div>
      <div><span class="text-slate-400 font-bold block">تاريخ الإنشاء</span><span class="text-slate-700 font-bold" data-preview="created_at">—</span></div>
      <div><span class="text-slate-400 font-bold block">آخر تحديث</span><span class="text-slate-700 font-bold" data-preview="updated_at">—</span></div>
    </div>
  </div>

</x-modal>

@endsection

@push('scripts')
<script>
// ===== Vehicle details modal: dynamic content =====
document.addEventListener('aracari:modal-open', function(e) {
  var modal = e.target;
  if (modal.id !== 'vehicle-details-modal') return;
  var data = e.detail.data || {};

  // Change banner
  var b = modal.querySelector('.aracari-modal-panel .relative.h-24');
  if (b) b.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
  var t = modal.querySelector('.aracari-modal-panel h2');
  if (t) t.innerHTML = '<i data-lucide="bus" class="w-5 h-5"></i> تفاصيل المركبة: ' + (data.name || '—');
  var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span[class*="rounded-full"]');
  if (badge) { badge.textContent = 'عرض'; badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30'; }

  // Status badge color
  var statusEl = modal.querySelector('[data-preview="status"]');
  if (statusEl && data.status_bg) {
    statusEl.className = 'inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-[10px] font-bold border ' + data.status_bg;
    var dot = document.createElement('span');
    dot.className = 'w-1.5 h-1.5 rounded-full';
    if (data.status === 'نشطة') dot.classList.add('bg-emerald-500');
    else if (data.status === 'صيانة') dot.classList.add('bg-amber-500');
    else dot.classList.add('bg-rose-500');
    statusEl.innerHTML = '';
    statusEl.appendChild(dot);
    statusEl.appendChild(document.createTextNode(' ' + data.status));
  }

  // Seats progress bar
  var bar = document.getElementById('detail-seats-bar');
  if (bar) {
    var total = parseInt(data.seats_total) || 1;
    var avail = parseInt(data.seats_available) || 0;
    var booked = parseInt(data.seats_booked) || 0;
    var maint = parseInt(data.seats_maintenance) || 0;
    bar.innerHTML = '<div class="bg-emerald-500 h-full" style="width:' + ((avail/total)*100) + '%"></div>' +
                    '<div class="bg-blue-500 h-full" style="width:' + ((booked/total)*100) + '%"></div>' +
                    '<div class="bg-amber-500 h-full" style="width:' + ((maint/total)*100) + '%"></div>';
  }

  // Photos
  var photoSection = document.getElementById('detail-photos-section');
  var outsideImg = document.getElementById('detail-outside-photo');
  var insideImg = document.getElementById('detail-inside-photo');
  if (photoSection) {
    var hasPhotos = data.outside_photo || data.inside_photo;
    photoSection.style.display = hasPhotos ? '' : 'none';
    if (outsideImg) outsideImg.src = data.outside_photo || '';
    if (insideImg) insideImg.src = data.inside_photo || '';
  }

  if (window.lucide) window.lucide.createIcons();
});
</script>
@endpush
