{{--
    Aracari Status Badge
    Colored pill badge for status display.

    Usage:
      <x-status-badge status="active" label="نشط" />
      <x-status-badge variant="success" label="مدفوع" icon="check-circle" />

    Variants: success | warning | danger | info | neutral | primary
--}}
@props([
    'status' => null,
    'variant' => null,
    'label' => '',
    'icon' => null,
])

@php
    // map common status strings to variants
    $statusMap = [
        // success / active
        'active' => 'success', 'paid' => 'success', 'confirmed' => 'success', 'approved' => 'success', 'verified' => 'success', 'completed' => 'success', 'success' => 'success', 'نشط' => 'success', 'مدفوع' => 'success', 'مؤكد' => 'success', 'مكتمل' => 'success', 'موافق' => 'success',
        // warning / pending
        'pending' => 'warning', 'waiting' => 'warning', 'review' => 'warning', 'معلق' => 'warning', 'قيد الانتظار' => 'warning', 'قيد المراجعة' => 'warning',
        // danger / cancelled / rejected
        'cancelled' => 'danger', 'rejected' => 'danger', 'failed' => 'danger', 'inactive' => 'danger', 'blocked' => 'danger', 'suspended' => 'danger', 'ملغي' => 'danger', 'مرفوض' => 'danger', 'فشل' => 'danger', 'غير نشط' => 'danger', 'محظور' => 'danger',
        // info
        'draft' => 'info', 'new' => 'info', 'مسودة' => 'info', 'جديد' => 'info',
        // neutral
        'expired' => 'neutral', 'archived' => 'neutral', 'منتهي' => 'neutral', 'مؤرشف' => 'neutral',
    ];
    $resolved = $variant ?: ($statusMap[$status] ?? ($status ? $statusMap[mb_strtolower($status)] ?? 'neutral' : 'neutral'));

    $styles = [
        'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'warning' => 'bg-amber-50 text-amber-700 border-amber-200',
        'danger'  => 'bg-rose-50 text-rose-700 border-rose-200',
        'info'    => 'bg-blue-50 text-blue-700 border-blue-200',
        'neutral' => 'bg-slate-100 text-slate-600 border-slate-200',
        'primary' => 'bg-teal-50 text-teal-700 border-teal-200',
    ];
    $iconMap = [
        'success' => 'check-circle',
        'warning' => 'clock',
        'danger'  => 'x-circle',
        'info'    => 'info',
        'neutral' => 'circle',
        'primary' => 'check',
    ];
    $resolvedIcon = $icon ?: ($iconMap[$resolved] ?? null);
    $resolvedLabel = $label ?: $status;
@endphp

<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $styles[$resolved] ?? $styles['neutral'] }}">
    @if($resolvedIcon)
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-{{ $resolvedIcon }}"><use href=""></use></svg>
    @endif
    {{ $resolvedLabel }}
</span>
