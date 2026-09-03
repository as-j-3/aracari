@extends('layouts.admin')

@section('title', 'أركاري | لوحة التحكم')
@section('header_title', 'لوحة التحكم العامة')

@section('content')
<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="layout-dashboard"
      title="لوحة التحكم"
      description="نظرة عامة على نشاط النظام والرحلات والحجوزات"
      descriptionIcon="activity"
  />

  {{-- ===== 4 Top Metric Cards ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    <x-stat-card
        :value="$totalTrips"
        label="إجمالي الرحلات"
        icon="bus"
        variant="slate"
        badge="رحلة"
        badgeIcon="bus"
    />
    <x-stat-card
        :value="$activeTrips"
        label="رحلات نشطة"
        icon="clock"
        variant="emerald"
        badge="نشطة"
        badgeIcon="check-circle"
    />
    <x-stat-card
        :value="$todaysBookings"
        label="حجوزات اليوم"
        icon="calendar-check"
        variant="blue"
        badge="اليوم"
        badgeIcon="calendar"
    />
    <x-stat-card
        :value="number_format($totalRevenue)"
        label="الإيرادات"
        icon="banknote"
        variant="amber"
        badge="إجمالي"
        badgeIcon="trending-up"
    />
  </div>

  {{-- ===== Main Dual-Column Area ===== --}}
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6">

    {{-- RIGHT COLUMN - Upcoming Trips (2/3 Width) --}}
    <div class="lg:col-span-2 bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-3xl shadow-xl shadow-slate-200/20 flex flex-col overflow-hidden">
      <div class="p-5 border-b border-slate-200/70 bg-white/40 backdrop-blur flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500/20 to-sky-600/10 flex items-center justify-center border border-sky-100">
            <i data-lucide="route" class="w-4 h-4 text-sky-600"></i>
          </div>
          <h3 class="font-bold text-sm text-slate-800">الرحلات القادمة</h3>
        </div>
        @if(Route::has('admin.trips.index'))
          <a href="{{ route('admin.trips.index') }}" class="text-[11px] font-bold text-teal-600 hover:text-teal-700 bg-teal-50/80 border border-teal-200 px-3 py-1.5 rounded-lg transition-all flex items-center gap-1">
            <span>عرض الكل</span>
            <i data-lucide="arrow-left" class="w-3 h-3"></i>
          </a>
        @endif
      </div>

      <div class="flex-1 overflow-x-auto">
        <table class="w-full min-w-[500px]">
          <thead>
            <tr class="bg-slate-50/70 border-b border-slate-200/70">
              <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">رقم الرحلة</th>
              <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المسار</th>
              <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">وقت المغادرة</th>
              <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">المقاعد</th>
              <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراء</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @forelse($upcomingTrips as $trip)
              @php
                $bookedSeats = $trip['booked_seats'] ?? 0;
                $totalSeats  = $trip['total_seats'] ?? 0;
                $seatRatio   = $totalSeats > 0 ? ($bookedSeats / $totalSeats) * 100 : 0;
                if ($seatRatio >= 90) {
                    $seatClass = 'bg-rose-50 text-rose-700 border-rose-200';
                    $seatLabel = 'مكتمل';
                } elseif ($seatRatio >= 50) {
                    $seatClass = 'bg-amber-50 text-amber-600 border-amber-200';
                    $seatLabel = 'متوسط';
                } else {
                    $seatClass = 'bg-sky-50 text-sky-700 border-sky-200';
                    $seatLabel = 'متاح';
                }
                $depDate = $trip['departure_date'] ?? null;
                $arabicDate = '—';
                if ($depDate) {
                    try {
                        $arabicDate = \Carbon\Carbon::parse($depDate)->translatedFormat('j F، Y');
                    } catch (\Throwable $e) {
                        $arabicDate = $depDate;
                    }
                }
              @endphp
              <tr class="hover:bg-slate-50/50 transition-colors">
                <td class="px-5 py-3.5">
                  <span class="font-mono font-bold text-slate-600 text-xs" dir="ltr">{{ \Illuminate\Support\Str::substr($trip['uuid'] ?? '', 0, 8) }}</span>
                </td>
                <td class="px-5 py-3.5">
                  <div class="flex items-center gap-1.5">
                    <span class="text-xs font-bold text-slate-700">{{ $trip['origin'] ?? '—' }}</span>
                    <i data-lucide="arrow-left-right" class="w-3 h-3 text-slate-400"></i>
                    <span class="text-xs font-bold text-slate-700">{{ $trip['destination'] ?? '—' }}</span>
                  </div>
                </td>
                <td class="px-5 py-3.5">
                  <div class="flex flex-col">
                    <span class="text-xs font-bold text-slate-700">{{ $arabicDate }}</span>
                    <span class="font-mono text-[10px] text-slate-400" dir="ltr">{{ $trip['departure_time'] ?? '' }}</span>
                  </div>
                </td>
                <td class="px-5 py-3.5 text-center">
                  <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $seatClass }}">
                    <i data-lucide="armchair" class="w-3 h-3"></i>
                    {{ $bookedSeats }}/{{ $totalSeats }}
                  </span>
                </td>
                <td class="px-5 py-3.5 text-center">
                  <a href="{{ Route::has('admin.trips.show') ? route('admin.trips.show', $trip['uuid'] ?? '') : route('admin.trips.index') }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-sky-50 text-sky-700 text-[10px] font-bold border border-sky-200 hover:bg-sky-100 transition-all">
                    <i data-lucide="eye" class="w-3 h-3"></i>
                    عرض
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="px-5 py-12 text-center">
                  <div class="flex flex-col items-center gap-3">
                    <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center">
                      <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                    </div>
                    <div>
                      <p class="text-sm font-bold text-slate-600">لا توجد رحلات قادمة</p>
                      <p class="text-xs text-slate-400 mt-1">ستظهر الرحلات القادمة هنا تلقائياً</p>
                    </div>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- LEFT COLUMN - Latest Bookings (1/3 Width) --}}
    <div class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-3xl shadow-xl shadow-slate-200/20 flex flex-col overflow-hidden">
      <div class="p-5 border-b border-slate-200/70 bg-white/40 backdrop-blur">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500/20 to-emerald-600/10 flex items-center justify-center border border-emerald-100">
            <i data-lucide="smartphone" class="w-4 h-4 text-emerald-600"></i>
          </div>
          <h3 class="font-bold text-sm text-slate-800">أحدث الحجوزات</h3>
        </div>
      </div>

      <div class="p-4 flex-1 flex flex-col">
        <div class="space-y-3 flex-1">
          @forelse($recentBookings as $booking)
            @php
              $statusKey   = $booking['status'] ?? 'pending';
              $statusLabel = $booking['status_label'] ?? 'قيد الانتظار';
              $statusBadge = match($statusKey) {
                'paid'      => 'text-emerald-700 bg-emerald-50 border-emerald-200',
                'pending'   => 'text-amber-700 bg-amber-50 border-amber-200',
                'cancelled' => 'text-rose-700 bg-rose-50 border-rose-200',
                default     => 'text-amber-700 bg-amber-50 border-amber-200',
              };
              $statusIcon = match($statusKey) {
                'paid'      => 'check-circle',
                'pending'   => 'clock',
                'cancelled' => 'x-circle',
                default     => 'clock',
              };
            @endphp
            <div class="p-3 rounded-xl bg-slate-50/50 border border-slate-200/70 hover:border-slate-300 transition-all">
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-800">{{ $booking['user_name'] ?? 'عميل' }}</span>
                <span class="text-[9px] font-bold text-slate-400">{{ $booking['time_ago'] ?? '' }}</span>
              </div>
              <p class="text-[11px] text-slate-500 font-medium mb-2.5">{{ $booking['trip_route'] ?? '—' }}</p>
              <div class="flex items-center justify-between">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $statusBadge }}">
                  <i data-lucide="{{ $statusIcon }}" class="w-2.5 h-2.5"></i>
                  {{ $statusLabel }}
                </span>
              </div>
            </div>
          @empty
            <div class="flex flex-col items-center justify-center py-12 gap-3">
              <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center">
                <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
              </div>
              <div class="text-center">
                <p class="text-sm font-bold text-slate-600">لا توجد حجوزات حديثة</p>
                <p class="text-xs text-slate-400 mt-1">ستظهر الحجوزات الجديدة هنا</p>
              </div>
            </div>
          @endforelse
        </div>

        @if(Route::has('admin.bookings.index'))
          <a href="{{ route('admin.bookings.index') }}" class="w-full py-2.5 text-xs font-bold text-teal-600 hover:bg-teal-50 rounded-xl border border-teal-200 transition-all text-center block mt-3 flex items-center justify-center gap-1.5">
            <span>عرض كل الحجوزات</span>
            <i data-lucide="arrow-left" class="w-3 h-3"></i>
          </a>
        @endif
      </div>
    </div>

  </div>
</div>
@endsection

@push('scripts')
<script>
  if (window.lucide) window.lucide.createIcons();
</script>
@endpush
