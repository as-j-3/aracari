<?php $__env->startSection('title', 'أركاري | إدارة الحجوزات'); ?>
<?php $__env->startSection('header_title', 'إدارة الحجوزات'); ?>

<?php $__env->startSection('content'); ?>

<?php
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
?>

<div class="space-y-6 font-cairo text-right" dir="rtl">

  
  <?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['icon' => 'ticket','title' => 'إدارة حجوزات النظام','count' => $totalBookings,'countLabel' => 'حجز','description' => 'عرض ومتابعة جميع الحجوزات عبر النظام','descriptionIcon' => 'ticket']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'ticket','title' => 'إدارة حجوزات النظام','count' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalBookings),'countLabel' => 'حجز','description' => 'عرض ومتابعة جميع الحجوزات عبر النظام','descriptionIcon' => 'ticket']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e)): ?>
<?php $attributes = $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e; ?>
<?php unset($__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e)): ?>
<?php $component = $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e; ?>
<?php unset($__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e); ?>
<?php endif; ?>

  
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $totalBookings,'label' => 'إجمالي الحجوزات','icon' => 'ticket','variant' => 'slate','badge' => 'متاحة','badgeIcon' => 'check-circle']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalBookings),'label' => 'إجمالي الحجوزات','icon' => 'ticket','variant' => 'slate','badge' => 'متاحة','badgeIcon' => 'check-circle']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $confirmedCount,'label' => 'حجوزات مؤكدة','icon' => 'check-circle','variant' => 'emerald','badge' => 'مؤكدة','badgeIcon' => 'check-circle']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($confirmedCount),'label' => 'حجوزات مؤكدة','icon' => 'check-circle','variant' => 'emerald','badge' => 'مؤكدة','badgeIcon' => 'check-circle']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $pendingCount,'label' => 'حجوزات معلقة','icon' => 'clock','variant' => 'amber','badge' => 'بانتظار','badgeIcon' => 'clock']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($pendingCount),'label' => 'حجوزات معلقة','icon' => 'clock','variant' => 'amber','badge' => 'بانتظار','badgeIcon' => 'clock']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $cancelledCount,'label' => 'حجوزات ملغاة','icon' => 'x-circle','variant' => 'rose','badge' => 'ملغاة','badgeIcon' => 'x-circle']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($cancelledCount),'label' => 'حجوزات ملغاة','icon' => 'x-circle','variant' => 'rose','badge' => 'ملغاة','badgeIcon' => 'x-circle']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
  </div>

  
  <div id="aracari-refreshable">
  <?php if (isset($component)) { $__componentOriginal90c131072f947a512cfcd740e0e498fa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal90c131072f947a512cfcd740e0e498fa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.data-table-card','data' => ['searchable' => true,'searchMode' => 'server','searchPlaceholder' => 'ابحث برقم الحجز أو اسم العميل...','footerInfo' => 'عرض ' . $bookings->count() . ' من أصل ' . $bookings->total() . ' حجز']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('data-table-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['searchable' => true,'searchMode' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('server'),'searchPlaceholder' => 'ابحث برقم الحجز أو اسم العميل...','footerInfo' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('عرض ' . $bookings->count() . ' من أصل ' . $bookings->total() . ' حجز')]); ?>
     <?php $__env->slot('filters', null, []); ?> 
      <form id="aracari-status-filter-form" class="relative md:w-44 flex-1">
        <select name="status" id="aracari-status-filter" class="w-full h-11 bg-white/70 border border-slate-200 rounded-xl px-3 pr-8 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all appearance-none cursor-pointer">
          <option value="">جميع الحالات</option>
          <option value="confirmed" <?php echo e(request('status') === 'confirmed' ? 'selected' : ''); ?>>مؤكد</option>
          <option value="pending" <?php echo e(request('status') === 'pending' ? 'selected' : ''); ?>>معلق</option>
          <option value="cancelled" <?php echo e(request('status') === 'cancelled' ? 'selected' : ''); ?>>ملغي</option>
          <option value="completed" <?php echo e(request('status') === 'completed' ? 'selected' : ''); ?>>مكتمل</option>
        </select>
        <i data-lucide="chevron-down" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
      </form>
     <?php $__env->endSlot(); ?>

     <?php $__env->slot('thead', null, []); ?> 
      <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المرجع</th>
      <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">العميل</th>
      <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
      <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المبلغ</th>
      <th class="px-6 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">التاريخ</th>
      <th class="px-6 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
     <?php $__env->endSlot(); ?>

     <?php $__env->slot('rows', null, []); ?> 
      <?php $__empty_1 = true; $__currentLoopData = $bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
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
        ?>
        <tr class="hover:bg-slate-50/50 transition-colors group">

          
          <td class="px-6 py-3.5">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gradient-to-r from-slate-900 to-slate-800 text-white font-mono font-black text-xs tracking-wider shadow-lg">
              <i data-lucide="ticket" class="w-3 h-3 text-teal-400"></i>
              <?php echo e(Str::substr($booking->uuid, 0, 8)); ?>

            </div>
          </td>

          
          <td class="px-6 py-3.5">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                <?php echo e($userInitial); ?>

              </div>
              <div class="min-w-0">
                <p class="font-bold text-slate-800 text-sm truncate"><?php echo e($userName); ?></p>
                <?php if($userPhone): ?>
                  <p class="text-[11px] text-slate-400 font-mono flex items-center gap-1" dir="ltr"><i data-lucide="phone" class="w-3 h-3"></i><?php echo e($userPhone); ?></p>
                <?php else: ?>
                  <p class="text-[11px] text-slate-400 flex items-center gap-1"><i data-lucide="mail" class="w-3 h-3"></i><?php echo e($userEmail); ?></p>
                <?php endif; ?>
              </div>
            </div>
          </td>

          
          <td class="px-6 py-3.5">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border
              <?php if($statusCode === 'confirmed'): ?> bg-emerald-50 text-emerald-600 border-emerald-200
              <?php elseif($statusCode === 'pending'): ?> bg-amber-50 text-amber-600 border-amber-200
              <?php elseif($statusCode === 'cancelled'): ?> bg-rose-50 text-rose-600 border-rose-200
              <?php elseif($statusCode === 'completed'): ?> bg-blue-50 text-blue-600 border-blue-200
              <?php else: ?> bg-slate-100 text-slate-600 border-slate-200 <?php endif; ?>">
              <span class="w-1.5 h-1.5 rounded-full
                <?php if($statusCode === 'confirmed'): ?> bg-emerald-500
                <?php elseif($statusCode === 'pending'): ?> bg-amber-500
                <?php elseif($statusCode === 'cancelled'): ?> bg-rose-500
                <?php elseif($statusCode === 'completed'): ?> bg-blue-500
                <?php else: ?> bg-slate-400 <?php endif; ?>"></span>
              <i data-lucide="<?php echo e($statusIcon); ?>" class="w-3 h-3"></i>
              <?php echo e($statusLabel); ?>

            </span>
          </td>

          
          <td class="px-6 py-3.5">
            <span class="font-black text-teal-500 text-base">
              <?php echo e(number_format((float) $booking->amount, 2)); ?>

              <span class="text-[10px] font-bold mr-1 text-slate-400"><?php echo e($currencySym); ?></span>
            </span>
          </td>

          
          <td class="px-6 py-3.5">
            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700">
              <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
              <span dir="ltr"><?php echo e($booking->created_at?->format('Y-m-d H:i')); ?></span>
            </div>
          </td>

          
          <td class="px-6 py-3.5 text-center">
            <div class="flex items-center justify-center gap-1.5">
              <?php if (isset($component)) { $__componentOriginald4c6978101b1c254eb70511d3c21c03f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald4c6978101b1c254eb70511d3c21c03f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.action-button','data' => ['variant' => 'view','dataViewModal' => 'booking-details-modal','dataViewJson' => ''.e(json_encode($modalData, JSON_UNESCAPED_UNICODE)).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('action-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'view','data-view-modal' => 'booking-details-modal','data-view-json' => ''.e(json_encode($modalData, JSON_UNESCAPED_UNICODE)).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald4c6978101b1c254eb70511d3c21c03f)): ?>
<?php $attributes = $__attributesOriginald4c6978101b1c254eb70511d3c21c03f; ?>
<?php unset($__attributesOriginald4c6978101b1c254eb70511d3c21c03f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald4c6978101b1c254eb70511d3c21c03f)): ?>
<?php $component = $__componentOriginald4c6978101b1c254eb70511d3c21c03f; ?>
<?php unset($__componentOriginald4c6978101b1c254eb70511d3c21c03f); ?>
<?php endif; ?>
              <?php if($canCancel && $canCancelRow): ?>
                <form method="POST" action="<?php echo e(route('admin.bookings.cancel', $booking->uuid)); ?>" onsubmit="return confirm('هل أنت متأكد من إلغاء هذا الحجز؟')" class="inline-flex">
                  <?php echo csrf_field(); ?>
                  <button type="submit" class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm flex items-center justify-center" title="إلغاء الحجز">
                    <i data-lucide="ban" class="w-4 h-4"></i>
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <?php if (isset($component)) { $__componentOriginal074a021b9d42f490272b5eefda63257c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal074a021b9d42f490272b5eefda63257c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty-state','data' => ['icon' => 'ticket','title' => (request('search') || request('status')) ? 'لا توجد نتائج مطابقة' : 'لا توجد حجوزات مسجلة','message' => (request('search') || request('status')) ? 'جرّب تعديل كلمات البحث أو الفلتر' : 'ستظهر الحجوزات هنا فور قيام العملاء بالحجز','colspan' => 6]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'ticket','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute((request('search') || request('status')) ? 'لا توجد نتائج مطابقة' : 'لا توجد حجوزات مسجلة'),'message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute((request('search') || request('status')) ? 'جرّب تعديل كلمات البحث أو الفلتر' : 'ستظهر الحجوزات هنا فور قيام العملاء بالحجز'),'colspan' => 6]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal074a021b9d42f490272b5eefda63257c)): ?>
<?php $attributes = $__attributesOriginal074a021b9d42f490272b5eefda63257c; ?>
<?php unset($__attributesOriginal074a021b9d42f490272b5eefda63257c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal074a021b9d42f490272b5eefda63257c)): ?>
<?php $component = $__componentOriginal074a021b9d42f490272b5eefda63257c; ?>
<?php unset($__componentOriginal074a021b9d42f490272b5eefda63257c); ?>
<?php endif; ?>
      <?php endif; ?>
     <?php $__env->endSlot(); ?>

     <?php $__env->slot('footer', null, []); ?> 
      <?php if (isset($component)) { $__componentOriginal41032d87daf360242eb88dbda6c75ed1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal41032d87daf360242eb88dbda6c75ed1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pagination','data' => ['paginator' => $bookings]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pagination'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($bookings)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal41032d87daf360242eb88dbda6c75ed1)): ?>
<?php $attributes = $__attributesOriginal41032d87daf360242eb88dbda6c75ed1; ?>
<?php unset($__attributesOriginal41032d87daf360242eb88dbda6c75ed1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal41032d87daf360242eb88dbda6c75ed1)): ?>
<?php $component = $__componentOriginal41032d87daf360242eb88dbda6c75ed1; ?>
<?php unset($__componentOriginal41032d87daf360242eb88dbda6c75ed1); ?>
<?php endif; ?>
     <?php $__env->endSlot(); ?>
   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal90c131072f947a512cfcd740e0e498fa)): ?>
<?php $attributes = $__attributesOriginal90c131072f947a512cfcd740e0e498fa; ?>
<?php unset($__attributesOriginal90c131072f947a512cfcd740e0e498fa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal90c131072f947a512cfcd740e0e498fa)): ?>
<?php $component = $__componentOriginal90c131072f947a512cfcd740e0e498fa; ?>
<?php unset($__componentOriginal90c131072f947a512cfcd740e0e498fa); ?>
<?php endif; ?>
  </div>

</div>


<?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['id' => 'booking-details-modal','title' => 'تفاصيل الحجز','variant' => 'view','icon' => 'ticket','static' => true,'maxWidth' => 'max-w-4xl']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'booking-details-modal','title' => 'تفاصيل الحجز','variant' => 'view','icon' => 'ticket','static' => true,'maxWidth' => 'max-w-4xl']); ?>

  
  <div id="booking-trips-container"></div>

  
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    
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

  
  <div id="detail-note-section" style="display:none;">
    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs font-medium text-slate-600 leading-relaxed">
      <span class="font-bold text-slate-800 block mb-1 flex items-center gap-1"><i data-lucide="message-square" class="w-3.5 h-3.5"></i> ملاحظات:</span>
      <p id="detail-note">—</p>
    </div>
  </div>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $attributes = $__attributesOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__attributesOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $component = $__componentOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__componentOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/admin/bookings/index.blade.php ENDPATH**/ ?>