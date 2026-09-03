@extends('layouts.admin')

@section('title', 'أركاري | إدارة الحجوزات')
@section('header_title', 'إدارة الحجوزات')

@section('content')

@php
    use App\Constants\BookingStatus;

    $user = auth()->user();
    $canCancel = $user && $user->hasPermission('bookings:cancel');

    $totalBookings  = $bookings->total();
    $confirmedCount = \App\Models\Booking::where('booking_status_id', BookingStatus::CONFIRMED)->count();
    $pendingCount   = \App\Models\Booking::where('booking_status_id', BookingStatus::PENDING)->count();
    $cancelledCount = \App\Models\Booking::whereIn('booking_status_id', BookingStatus::getCancelledStatuses())->count();

    $statusCodeMap = [
        BookingStatus::CONFIRMED         => 'confirmed',
        BookingStatus::PENDING           => 'pending',
        BookingStatus::CANCELLED         => 'cancelled',
        BookingStatus::COMPLETED         => 'completed',
        BookingStatus::CANCELLED_BY_USER => 'cancelled',
        BookingStatus::CANCELLED_BY_ADMIN=> 'cancelled',
        BookingStatus::PAYMENT_FAILED    => 'cancelled',
    ];

    $statusLabelMap = [
        'confirmed' => 'مؤكد',
        'pending'   => 'معلق',
        'cancelled' => 'ملغي',
        'completed' => 'مكتمل',
    ];

    $statusIconMap = [
        'confirmed' => 'check-circle',
        'pending'   => 'clock',
        'cancelled' => 'x-circle',
        'completed' => 'flag',
    ];

    $getStatusCode = function($b) use ($statusCodeMap) {
        return $statusCodeMap[$b->booking_status_id] ?? 'pending';
    };
    $getStatusLabel = function($b) use ($getStatusCode, $statusLabelMap) {
        if ($b->booking_status_id === BookingStatus::PAYMENT_FAILED) return 'فشل الدفع';
        $code = $getStatusCode($b);
        return $statusLabelMap[$code] ?? ($b->status->status_name ?? 'غير محدد');
    };
    $canCancelBooking = function($b) use ($getStatusCode) {
        $code = $getStatusCode($b);
        return $code !== 'cancelled' && $code !== 'completed';
    };
@endphp

<div class="space-y-6 font-cairo text-right" dir="rtl">

  {{-- ===== Page Header ===== --}}
  <x-page-header
      icon="ticket"
      title="إدارة حجوزات النظام"
      :count="$totalBookings"
      countLabel="حجز"
      description="عرض ومتابعة جميع الحجوزات عبر النظام"
      descriptionIcon="ticket"
  />

  {{-- ===== Stat Cards ===== --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    <x-stat-card :value="$totalBookings" label="إجمالي الحجوزات" icon="ticket" variant="slate" badge="متاحة" badgeIcon="check-circle" />
    <x-stat-card :value="$confirmedCount" label="حجوزات مؤكدة" icon="check-circle" variant="emerald" badge="مؤكدة" badgeIcon="check-circle" />
    <x-stat-card :value="$pendingCount" label="حجوزات معلقة" icon="clock" variant="amber" badge="بانتظار" badgeIcon="clock" />
    <x-stat-card :value="$cancelledCount" label="حجوزات ملغاة" icon="x-circle" variant="rose" badge="ملغاة" badgeIcon="x-circle" />
  </div>

  {{-- ===== Data Table ===== --}}
  <div id="aracari-refreshable">
  <x-data-table-card
      :searchable="true"
      :searchMode="'server'"
      searchPlaceholder="ابحث برقم الحجز أو اسم العميل..."
      :footerInfo="'عرض ' . $bookings->count() . ' من أصل ' . $bookings->total() . ' حجز'"
  >
    <x-slot name="filters">
      <form id="aracari-status-filter-form" class="relative md:w-44 flex-1">
        <select name="status" id="aracari-status-filter" class="w-full h-11 bg-white/70 border border-slate-200 rounded-xl px-3 pr-8 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all appearance-none cursor-pointer">
          <option value="">جميع الحالات</option>
          <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>مؤكد</option>
          <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>معلق</option>
          <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ملغي</option>
          <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>مكتمل</option>
        </select>
        <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
      </form>
    </x-slot>

    <x-slot name="thead">
      <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المرجع</th>
      <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">العميل</th>
      <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
      <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المبلغ</th>
      <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">التاريخ</th>
      <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
    </x-slot>

    <x-slot name="rows">
      @forelse($bookings as $booking)
        @php
            $statusCode   = $getStatusCode($booking);
            $statusLabel  = $getStatusLabel($booking);
            $statusIcon   = $statusIconMap[$statusCode] ?? 'circle';
            $canCancelRow = $canCancelBooking($booking);
            $currencyCode = $booking->currency->code ?? ($baseCurrency->code ?? '');
            $currencySym  = $booking->currency->symbol ?? ($baseCurrency->symbol ?? '');
            $userName     = $booking->user->name ?? 'مستخدم محذوف';
            $userEmail    = $booking->user->email ?? '—';
            $userPhone    = $booking->user->phone_number ?? null;
            $userInitial  = mb_substr($userName, 0, 1);

            // === بيانات المودال ===
            $tripsData = [];
            foreach ($booking->tripReservations as $res) {
                $trip = $res->trip;
                $ticketsArr = [];
                foreach ($res->tickets as $tk) {
                    $ticketsArr[] = [
                        'ticket_number'  => $tk->ticket_number ?? '—',
                        'passenger_name' => $tk->passenger->full_name ?? '—',
                        'seat_name'      => $tk->seat->seat_name ?? '—',
                        'price'          => number_format((float) ($tk->price_at_booking ?? 0), 2) . ' ' . $currencySym,
                        'status'         => $tk->cancelled_at ? 'ملغي' : 'نشط',
                    ];
                }
                $tripsData[] = [
                    'from_city'       => $trip->departureCity->name ?? '—',
                    'to_city'         => $trip->destinationCity->name ?? '—',
                    'from_station'    => $trip->departureStation->name ?? '',
                    'to_station'      => $trip->arrivalStation->name ?? '',
                    'departure_time'  => $trip->departure_time?->format('H:i') ?? '—',
                    'departure_date'  => $trip->departure_time?->translatedFormat('j F، Y') ?? '—',
                    'arrival_time'    => $trip->arrival_time?->format('H:i') ?? '—',
                    'vehicle_name'    => $trip->vehicle->name ?? ($trip->vehicle->model ?? '—'),
                    'vehicle_type'    => $trip->vehicle->vehicleType->type_name ?? '',
                    'tickets'         => $ticketsArr,
                ];
            }

            $taxTotal = $booking->taxes->sum('tax_amount');

            $modalData = [
                'uuid'          => $booking->uuid,
                'user_name'     => $userName,
                'user_email'    => $userEmail,
                'user_phone'    => $userPhone ?? '—',
                'status_name'   => $statusLabel,
                'status_code'   => $statusCode,
                'amount'        => number_format((float) $booking->amount, 2) . ' ' . $currencySym,
                'amount_raw'    => (float) $booking->amount,
                'currency'      => $currencyCode,
                'payment_method'=> $booking->payment->paymentMethod->method_name ?? 'غير محدد',
                'payed_amount'  => number_format((float) ($booking->payment->payed_amount ?? 0), 2) . ' ' . $currencySym,
                'commission'    => $booking->commission ? ($booking->commission->description ?? '') : '',
                'tax_amount'    => $taxTotal > 0 ? number_format($taxTotal, 2) . ' ' . $currencySym : '—',
                'created_at'    => $booking->created_at?->translatedFormat('j F، Y الساعة H:i') ?? '—',
                'cancelled_at'  => $booking->cancelled_at?->translatedFormat('j F، Y الساعة H:i') ?? '',
                'note'          => $booking->note ?? '',
                'trips'         => $tripsData,
            ];
        @endphp
        <tr class="hover:bg-slate-50/50 transition-colors group">

          {{-- Reference --}}
          <td class="px-6 py-3.5">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gradient-to-r from-slate-900 to-slate-800 text-white font-mono font-black text-xs tracking-wider shadow-lg">
              <i data-lucide="ticket" class="w-3 h-3 text-teal-400"></i>
              {{ Str::substr($booking->uuid, 0, 8) }}
            </div>
          </td>

          {{-- Customer --}}
          <td class="px-6 py-3.5">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                {{ $userInitial }}
              </div>
              <div class="min-w-0">
                <p class="font-bold text-slate-800 text-sm truncate">{{ $userName }}</p>
                @if($userPhone)
                  <p class="text-[11px] text-slate-400 font-mono flex items-center gap-1" dir="ltr"><i data-lucide="phone" class="w-3 h-3"></i>{{ $userPhone }}</p>
                @else
                  <p class="text-[11px] text-slate-400 flex items-center gap-1"><i data-lucide="mail" class="w-3 h-3"></i>{{ $userEmail }}</p>
                @endif
              </div>
            </div>
          </td>

          {{-- Status --}}
          <td class="px-6 py-3.5">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border
              @if($statusCode === 'confirmed') bg-emerald-50 text-emerald-600 border-emerald-200
              @elseif($statusCode === 'pending') bg-amber-50 text-amber-600 border-amber-200
              @elseif($statusCode === 'cancelled') bg-rose-50 text-rose-600 border-rose-200
              @elseif($statusCode === 'completed') bg-blue-50 text-blue-600 border-blue-200
              @else bg-slate-100 text-slate-600 border-slate-200 @endif">
              <span class="w-1.5 h-1.5 rounded-full
                @if($statusCode === 'confirmed') bg-emerald-500
                @elseif($statusCode === 'pending') bg-amber-500
                @elseif($statusCode === 'cancelled') bg-rose-500
                @elseif($statusCode === 'completed') bg-blue-500
                @else bg-slate-400 @endif"></span>
              <i data-lucide="{{ $statusIcon }}" class="w-3 h-3"></i>
              {{ $statusLabel }}
            </span>
          </td>

          {{-- Amount --}}
          <td class="px-6 py-3.5">
            <span class="font-black text-teal-500 text-base">
              {{ number_format((float) $booking->amount, 2) }}
              <span class="text-[10px] font-bold mr-1 text-slate-400">{{ $currencySym }}</span>
            </span>
          </td>

          {{-- Date --}}
          <td class="px-6 py-3.5">
            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700">
              <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
              <span dir="ltr">{{ $booking->created_at?->format('Y-m-d H:i') }}</span>
            </div>
          </td>

          {{-- Actions --}}
          <td class="px-6 py-3.5 text-center">
            <div class="flex items-center justify-center gap-1.5">
              <x-action-button variant="view" data-view-modal="booking-details-modal" data-view-json="{{ json_encode($modalData, JSON_UNESCAPED_UNICODE) }}" />
              @if($canCancel && $canCancelRow)
                <form method="POST" action="{{ route('admin.bookings.cancel', $booking->uuid) }}" onsubmit="return confirm('هل أنت متأكد من إلغاء هذا الحجز؟')" class="inline-flex">
                  @csrf
                  <button type="submit" class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm flex items-center justify-center" title="إلغاء الحجز">
                    <i data-lucide="ban" class="w-4 h-4"></i>
                  </button>
                </form>
              @endif
            </div>
          </td>
        </tr>
      @empty
        <x-empty-state icon="ticket" :title="(request('search') || request('status')) ? 'لا توجد نتائج مطابقة' : 'لا توجد حجوزات مسجلة'" :message="(request('search') || request('status')) ? 'جرّب تعديل كلمات البحث أو الفلتر' : 'ستظهر الحجوزات هنا فور قيام العملاء بالحجز'" :colspan="6" />
      @endforelse
    </x-slot>

    <x-slot name="footer">
      <x-pagination :paginator="$bookings" />
    </x-slot>
  </x-data-table-card>
  </div>

</div>

{{-- ===== MODAL: تفاصيل الحجز ===== --}}
<x-modal id="booking-details-modal" title="تفاصيل الحجز" variant="view" icon="ticket" :static="true" maxWidth="max-w-4xl">

  {{-- 1. Route info (trip timeline) --}}
  <div id="booking-trips-container"></div>

  {{-- 2. Grid: Customer + Payment --}}
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    {{-- Customer Card --}}
    <div class="p-5 bg-white border border-[#eeeef0] rounded-2xl shadow-sm space-y-3">
      <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1"><i data-lucide="user" class="w-4 h-4 text-teal-600"></i><span>بيانات العميل</span></h4>
      <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
        <div id="detail-customer-avatar" class="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center text-white font-bold text-lg flex-shrink-0">—</div>
        <div class="min-w-0 space-y-1">
          <p id="detail-customer-name" class="font-bold text-slate-800 text-sm truncate">—</p>
          <p id="detail-customer-phone" class="text-[11px] text-slate-400 font-mono flex items-center gap-1" dir="ltr"><i data-lucide="phone" class="w-3 h-3"></i><span>—</span></p>
          <p id="detail-customer-email" class="text-[11px] text-slate-400 flex items-center gap-1"><i data-lucide="mail" class="w-3 h-3"></i><span>—</span></p>
        </div>
      </div>
      <div class="space-y-2 text-xs">
        <div class="flex justify-between items-center py-1.5 border-b border-slate-50"><span class="text-slate-500 font-medium">تاريخ الحجز:</span><span id="detail-created-at" class="font-bold text-slate-800">—</span></div>
        <div id="detail-cancelled-row" class="flex justify-between items-center py-1.5 border-b border-slate-50" style="display:none;"><span class="text-slate-500 font-medium">تاريخ الإلغاء:</span><span id="detail-cancelled-at" class="font-bold text-rose-600">—</span></div>
      </div>
    </div>

    {{-- Payment Card --}}
    <div class="p-5 bg-white border border-[#eeeef0] rounded-2xl shadow-sm space-y-3">
      <h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1"><i data-lucide="credit-card" class="w-4 h-4 text-emerald-600"></i><span>بيانات الدفع</span></h4>
      <div class="space-y-2.5 text-xs">
        <div class="flex justify-between items-center py-1.5 border-b border-slate-50"><span class="text-slate-500 font-medium">طريقة الدفع:</span><span id="detail-payment-method" class="font-bold text-slate-800">—</span></div>
        <div class="flex justify-between items-center py-1.5 border-b border-slate-50"><span class="text-slate-500 font-medium">المبلغ المدفوع:</span><span id="detail-payed-amount" class="font-black text-emerald-700 text-base">—</span></div>
        <div class="flex justify-between items-center py-1.5 border-b border-slate-50"><span class="text-slate-500 font-medium">إجمالي المبلغ:</span><span id="detail-amount" class="font-black text-slate-800 text-base">—</span></div>
        <div id="detail-tax-row" class="flex justify-between items-center py-1.5 border-b border-slate-50" style="display:none;"><span class="text-slate-500 font-medium">الضرائب:</span><span id="detail-tax-amount" class="font-bold text-slate-700">—</span></div>
        <div id="detail-commission-row" class="flex justify-between items-center py-1.5" style="display:none;"><span class="text-slate-500 font-medium">العمولة:</span><span id="detail-commission" class="font-bold text-slate-700">—</span></div>
      </div>
    </div>
  </div>

  {{-- 3. Note --}}
  <div id="detail-note-section" style="display:none;">
    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs font-medium text-slate-600 leading-relaxed">
      <span class="font-bold text-slate-800 block mb-1 flex items-center gap-1"><i data-lucide="message-square" class="w-3.5 h-3.5"></i> ملاحظات:</span>
      <p id="detail-note">—</p>
    </div>
  </div>

</x-modal>

@endsection

@push('scripts')
<script>
// ===== Booking details modal =====
document.addEventListener('aracari:modal-open', function(e) {
  var modal = e.target;
  if (modal.id !== 'booking-details-modal') return;
  var data = e.detail.data || {};

  // Banner
  var b = modal.querySelector('.aracari-modal-panel .relative.h-24');
  if (b) b.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-emerald-900 to-teal-950';
  var t = modal.querySelector('.aracari-modal-panel h2');
  if (t) t.innerHTML = '<i data-lucide="ticket" class="w-5 h-5"></i> تفاصيل الحجز ' + (data.uuid ? data.uuid.substring(0, 8) : '—');
  var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span[class*="rounded-full"]');
  if (badge) {
    var statusColors = { confirmed: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30', pending: 'bg-amber-500/20 text-amber-300 border-amber-500/30', cancelled: 'bg-rose-500/20 text-rose-300 border-rose-500/30', completed: 'bg-blue-500/20 text-blue-300 border-blue-500/30' };
    var statusLabels = { confirmed: 'مؤكد', pending: 'معلق', cancelled: 'ملغي', completed: 'مكتمل' };
    badge.textContent = statusLabels[data.status_code] || 'حجز';
    badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border ' + (statusColors[data.status_code] || 'bg-slate-500/20 text-slate-300 border-slate-500/30');
  }

  // Customer
  var avatar = document.getElementById('detail-customer-avatar');
  if (avatar) avatar.textContent = data.user_name ? data.user_name.charAt(0) : '?';
  var nameEl = document.getElementById('detail-customer-name');
  if (nameEl) nameEl.textContent = data.user_name || '—';
  var phoneEl = document.getElementById('detail-customer-phone');
  if (phoneEl) phoneEl.innerHTML = '<i data-lucide="phone" class="w-3 h-3"></i> ' + (data.user_phone || '—');
  var emailEl = document.getElementById('detail-customer-email');
  if (emailEl) emailEl.innerHTML = '<i data-lucide="mail" class="w-3 h-3"></i> ' + (data.user_email || '—');

  // Payment
  var pmEl = document.getElementById('detail-payment-method');
  if (pmEl) pmEl.textContent = data.payment_method || 'غير محدد';
  var payedEl = document.getElementById('detail-payed-amount');
  if (payedEl) payedEl.textContent = data.payed_amount || '—';
  var amountEl = document.getElementById('detail-amount');
  if (amountEl) amountEl.textContent = data.amount || '—';

  // Tax
  var taxRow = document.getElementById('detail-tax-row');
  if (taxRow) { taxRow.style.display = (data.tax_amount && data.tax_amount !== '—') ? '' : 'none'; }
  var taxEl = document.getElementById('detail-tax-amount');
  if (taxEl) taxEl.textContent = data.tax_amount || '—';

  // Commission
  var commRow = document.getElementById('detail-commission-row');
  if (commRow) { commRow.style.display = data.commission ? '' : 'none'; }
  var commEl = document.getElementById('detail-commission');
  if (commEl) commEl.textContent = data.commission || '—';

  // Dates
  var createdEl = document.getElementById('detail-created-at');
  if (createdEl) createdEl.textContent = data.created_at || '—';
  var cancelRow = document.getElementById('detail-cancelled-row');
  if (cancelRow) { cancelRow.style.display = data.cancelled_at ? '' : 'none'; }
  var cancelEl = document.getElementById('detail-cancelled-at');
  if (cancelEl) cancelEl.textContent = data.cancelled_at || '—';

  // Note
  var noteSection = document.getElementById('detail-note-section');
  if (noteSection) { noteSection.style.display = data.note && data.note.trim() ? '' : 'none'; }
  var noteEl = document.getElementById('detail-note');
  if (noteEl) noteEl.textContent = data.note || '';

  // === Trips & Tickets ===
  var tripsContainer = document.getElementById('booking-trips-container');
  if (tripsContainer) {
    tripsContainer.innerHTML = '';
    if (data.trips && data.trips.length > 0) {
      data.trips.forEach(function(trip, idx) {
        var section = document.createElement('div');
        section.className = 'space-y-4 bg-slate-50 p-5 rounded-2xl border border-[#eeeef0] mb-4';

        var tripHeader = '';
        if (data.trips.length > 1) {
          tripHeader = '<h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-100 pb-2 mb-3"><i data-lucide="route" class="w-4 h-4 text-teal-600"></i> الرحلة ' + (idx + 1) + '</h4>';
        } else {
          tripHeader = '<h4 class="font-bold text-xs text-slate-400 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-100 pb-2 mb-3"><i data-lucide="map" class="w-4 h-4 text-teal-600"></i> مسار الرحلة</h4>';
        }

        var routeHtml = '<div class="relative pr-6 mr-2 py-2">'
          + '<div class="absolute top-3 bottom-3 right-[9px] w-0.5 bg-gradient-to-b from-emerald-500 to-rose-500"></div>'
          + '<div class="relative flex items-start gap-4 mb-4">'
          + '<span class="absolute right-[-22px] top-1.5 w-5 h-5 rounded-full bg-white border-4 border-emerald-500 z-10"></span>'
          + '<div class="space-y-0.5">'
          + '<h5 class="font-bold text-sm text-slate-800">' + trip.from_city + '</h5>'
          + (trip.from_station ? '<p class="text-[10px] text-slate-400">' + trip.from_station + '</p>' : '')
          + '<p class="text-[10px] text-emerald-700 font-bold font-mono">' + trip.departure_date + ' — ' + trip.departure_time + '</p>'
          + '</div></div>'
          + '<div class="relative flex items-start gap-4">'
          + '<span class="absolute right-[-22px] top-1.5 w-5 h-5 rounded-full bg-white border-4 border-rose-500 z-10"></span>'
          + '<div class="space-y-0.5">'
          + '<h5 class="font-bold text-sm text-slate-800">' + trip.to_city + '</h5>'
          + (trip.to_station ? '<p class="text-[10px] text-slate-400">' + trip.to_station + '</p>' : '')
          + '<p class="text-[10px] text-rose-700 font-bold font-mono">الوصول: ' + trip.arrival_time + '</p>'
          + '</div></div>'
          + '</div>';

        var vehicleHtml = '<div class="flex items-center gap-2 text-xs text-slate-500">'
          + '<i data-lucide="bus" class="w-3.5 h-3.5 text-teal-600"></i>'
          + '<span class="font-bold text-slate-700">' + trip.vehicle_name + '</span>'
          + (trip.vehicle_type ? '<span class="text-slate-400">(' + trip.vehicle_type + ')</span>' : '')
          + '</div>';

        // Tickets
        var ticketsHtml = '';
        if (trip.tickets && trip.tickets.length > 0) {
          ticketsHtml = '<div class="mt-4 pt-4 border-t border-[#eeeef0]">'
            + '<h5 class="font-bold text-[11px] text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-1"><i data-lucide="users" class="w-3.5 h-3.5"></i> التذاكر والمسافرين (' + trip.tickets.length + ')</h5>'
            + '<div class="space-y-2">';
          trip.tickets.forEach(function(tk) {
            var isActive = tk.status === 'نشط';
            var statusBg = isActive ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-600 border-rose-200';
            ticketsHtml += '<div class="flex items-center gap-3 p-3 bg-white rounded-xl border border-slate-100">'
              + '<div class="w-8 h-8 rounded-lg bg-gradient-to-br from-teal-500/20 to-teal-600/10 flex items-center justify-center border border-teal-100 flex-shrink-0"><i data-lucide="armchair" class="w-4 h-4 text-teal-600"></i></div>'
              + '<div class="flex-1 min-w-0">'
              + '<div class="flex items-center gap-2"><span class="font-bold text-sm text-slate-800">' + tk.passenger_name + '</span>'
              + '<span class="font-mono text-[10px] text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded">' + tk.ticket_number + '</span></div>'
              + '<div class="flex items-center gap-2 text-[10px] text-slate-400 mt-0.5">'
              + '<span>مقعد: <b class="text-slate-600">' + tk.seat_name + '</b></span>'
              + '<span>·</span>'
              + '<span>السعر: <b class="text-teal-700">' + tk.price + '</b></span>'
              + '</div></div>'
              + '<span class="px-2 py-0.5 rounded-full text-[9px] font-bold border ' + statusBg + '">' + tk.status + '</span>'
              + '</div>';
          });
          ticketsHtml += '</div></div>';
        }

        section.innerHTML = tripHeader + routeHtml + vehicleHtml + ticketsHtml;
        tripsContainer.appendChild(section);
      });
    } else {
      tripsContainer.innerHTML = '<div class="p-5 bg-slate-50 rounded-xl border border-slate-200 text-center text-slate-400 text-xs font-medium">لا توجد بيانات رحلات لهذا الحجز</div>';
    }
  }

  if (window.lucide) window.lucide.createIcons();
});
</script>
@endpush
