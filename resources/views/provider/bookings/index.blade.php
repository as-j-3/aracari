@extends('layouts.provider')

@section('title', 'أركاري | الحجوزات')
@section('header_title', 'الحجوزات')

@section('content')
<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Toolbar: Branch Filter ===== --}}
  <div class="bg-white rounded-2xl border border-[#e2e2e5] p-4">
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">

      {{-- Branch Filter --}}
      <form method="GET" action="{{ route('admin.provider.bookings.index') }}" class="flex items-center gap-2 flex-1">
        <div class="relative flex-1 max-w-xs">
          <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
            <i data-lucide="filter" class="w-4 h-4"></i>
          </div>
          <select
            name="branch"
            onchange="this.form.submit()"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all appearance-none cursor-pointer"
          >
            <option value="">كل الفروع</option>
            @foreach($branches as $branch)
              <option value="{{ $branch->id }}" {{ $selectedBranch == $branch->id ? 'selected' : '' }}>
                {{ $branch->city?->name ?? 'فرع #' . $branch->id }}
              </option>
            @endforeach
          </select>
        </div>
        @if($selectedBranch)
          <a href="{{ route('admin.provider.bookings.index') }}" class="text-[11px] text-rose-500 hover:text-rose-700 font-bold flex items-center gap-1 whitespace-nowrap">
            <i data-lucide="x" class="w-3 h-3"></i>
            إزالة الفلتر
          </a>
        @endif
      </form>

      {{-- Info --}}
      <div class="flex items-center gap-2">
        <span class="text-[10px] text-slate-400 flex items-center gap-1">
          <i data-lucide="info" class="w-3 h-3"></i>
          عرض الحجوزات من جميع الفروع — لإنشاء حجز ادخل الفرع
        </span>
      </div>
    </div>
  </div>

  {{-- ===== Bookings Table ===== --}}
  <div class="bg-white rounded-2xl border border-[#e2e2e5] overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-[#f9f9fc] border-b border-[#e2e2e5]">
            <th class="px-4 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">رقم الحجز</th>
            <th class="px-4 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">المسافر</th>
            <th class="px-4 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">الرحلة</th>
            <th class="px-4 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">الفرع</th>
            <th class="px-4 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">التاريخ</th>
            <th class="px-4 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
            <th class="px-4 py-3 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">إجراءات</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-[#f0f0f3]">
          @forelse($bookings as $booking)
            @php
              $firstReservation = $booking->tripReservations->first();
              $trip = $firstReservation?->trip;
              $branch = $trip?->service?->branch;
            @endphp
            <tr class="hover:bg-slate-50/50 transition-colors">
              <td class="px-4 py-3">
                <span class="font-bold text-slate-800 text-[13px] font-mono">{{ $booking->uuid ? substr($booking->uuid, 0, 8) : '#' . $booking->id }}</span>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="user" class="w-4 h-4 text-slate-500"></i>
                  </div>
                  <span class="text-[13px] font-bold text-slate-700">{{ $booking->user?->name ?? '—' }}</span>
                </div>
              </td>
              <td class="px-4 py-3">
                @if($trip)
                  <div class="flex items-center gap-1.5 text-[12px]">
                    <span class="font-bold text-slate-700">{{ $trip->departureCity?->name ?? '—' }}</span>
                    <i data-lucide="arrow-left" class="w-3 h-3 text-slate-300"></i>
                    <span class="font-bold text-slate-700">{{ $trip->destinationCity?->name ?? '—' }}</span>
                  </div>
                  <p class="text-[10px] text-slate-400 mt-0.5">{{ $trip->departure_time?->format('d/m/Y h:i A') }}</p>
                @else
                  <span class="text-[12px] text-slate-400">—</span>
                @endif
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center gap-1 text-[11px] font-bold bg-slate-100 text-slate-600 px-2 py-1 rounded-lg">
                  <i data-lucide="store" class="w-3 h-3"></i>
                  {{ $branch?->city?->name ?? '—' }}
                </span>
              </td>
              <td class="px-4 py-3">
                <p class="text-[12px] font-bold text-slate-700">{{ $booking->created_at?->format('d/m/Y') }}</p>
                <p class="text-[11px] text-slate-400">{{ $booking->created_at?->format('h:i A') }}</p>
              </td>
              <td class="px-4 py-3">
                @php
                  $statusName = $booking->status?->status_name ?? 'غير معروف';
                  $statusColor = match($statusName) {
                    'confirmed', 'مؤكد' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                    'pending', 'معلق' => 'bg-amber-100 text-amber-700 border-amber-200',
                    'cancelled', 'ملغي' => 'bg-rose-100 text-rose-700 border-rose-200',
                    default => 'bg-slate-100 text-slate-600 border-slate-200',
                  };
                @endphp
                <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $statusColor }}">
                  {{ $statusName }}
                </span>
              </td>
              <td class="px-4 py-3 text-center">
                @if($branch)
                  <div class="flex items-center justify-center gap-1">
                    <a href="{{ route('admin.provider.branches.bookings.show', [$branch, $booking]) }}"
                       class="w-8 h-8 rounded-lg flex items-center justify-center text-[#115128] hover:bg-teal-50 transition-colors" title="عرض">
                      <i data-lucide="eye" class="w-4 h-4"></i>
                    </a>
                  </div>
                @else
                  <span class="text-[11px] text-slate-400">—</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="px-4 py-12 text-center">
                <div class="flex flex-col items-center gap-3">
                  <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center">
                    <i data-lucide="ticket" class="w-8 h-8 text-slate-300"></i>
                  </div>
                  <p class="text-sm font-bold text-slate-400">لا توجد حجوزات حالياً</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Pagination --}}
    @if($bookings->hasPages())
      <div class="px-4 py-3 border-t border-[#e2e2e5] flex items-center justify-between">
        <p class="text-[11px] text-slate-400">عرض {{ $bookings->firstItem() }} - {{ $bookings->lastItem() }} من {{ $bookings->total() }}</p>
        {{ $bookings->links('components::pagination') }}
      </div>
    @endif
  </div>

</div>
@endsection

@push('scripts')
<script>lucide.createIcons();</script>
@endpush
