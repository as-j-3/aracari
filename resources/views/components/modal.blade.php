{{--
    Aracari Modal — Base shell
    Usage:
      <x-modal id="my-modal" title="عنوان" variant="create|edit|view" :static="$static ?? false">
        ...body...
      </x-modal>

    Variants:
      create → teal gradient banner
      edit   → amber gradient banner
      view   → slate gradient banner
--}}
@props([
    'id' => 'aracari-modal',
    'title' => '',
    'subtitle' => '',
    'icon' => null,
    'variant' => 'create', // create | edit | view
    'static' => false,
    'maxWidth' => 'max-w-lg',
])

@php
    $bannerGradients = [
        'create' => 'bg-gradient-to-r from-teal-900 to-teal-950',
        'edit'   => 'bg-gradient-to-r from-amber-900 to-amber-950',
        'view'   => 'bg-gradient-to-r from-slate-900 to-slate-950',
    ];
    $badgeStyles = [
        'create' => 'bg-teal-500/20 text-teal-300 border-teal-500/30',
        'edit'   => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
        'view'   => 'bg-slate-500/20 text-slate-300 border-slate-500/30',
    ];
    $badgeLabels = [
        'create' => 'جديد',
        'edit'   => 'تحديث',
        'view'   => 'عرض',
    ];
    $iconMap = [
        'create' => 'plus-circle',
        'edit'   => 'pencil',
        'view'   => 'eye',
    ];
    $resolvedIcon = $icon ?: ($iconMap[$variant] ?? null);
    $bannerClass = $bannerGradients[$variant] ?? $bannerGradients['create'];
    $badgeClass = $badgeStyles[$variant] ?? $badgeStyles['create'];
    $badgeLabel = $badgeLabels[$variant] ?? $badgeLabels['create'];
@endphp

<div
    id="{{ $id }}"
    data-aracari-modal
    class="hidden fixed inset-0 z-[80] overflow-y-auto font-cairo"
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
    @if($static) data-static @endif
>
    {{-- Overlay --}}
    <div
        class="aracari-modal-overlay fixed inset-0 bg-[#1a1c1e]/60 backdrop-blur-sm opacity-0 transition-opacity duration-200"
    ></div>

    {{-- Panel --}}
    <div class="flex min-h-full items-center justify-center p-4">
        <div
            class="aracari-modal-panel relative bg-white rounded-2xl {{ $maxWidth }} w-full shadow-2xl overflow-hidden border border-[#eeeef0] flex flex-col text-right opacity-0 scale-95 translate-y-4 transition-all duration-200"
            dir="rtl"
        >
            {{-- Banner Header --}}
            <div class="relative h-24 text-white p-5 flex flex-col justify-end {{ $bannerClass }}">
                <div class="absolute top-4 left-4">
                    <button
                        type="button"
                        data-close-modal
                        class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur text-white flex items-center justify-center transition-all"
                        aria-label="إغلاق"
                    >
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="relative z-10 space-y-1">
                    <span class="text-[10px] font-bold px-2.5 py-1 rounded-full border {{ $badgeClass }}">
                        {{ $badgeLabel }}
                    </span>
                    <h2 class="text-lg font-extrabold tracking-tight flex items-center gap-2">
                        @if($resolvedIcon)
                            <i data-lucide="{{ $resolvedIcon }}" class="w-5 h-5"></i>
                        @endif
                        {{ $title }}
                    </h2>
                    @if($subtitle)
                        <p class="text-xs text-white/70">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>

            {{-- Body --}}
            <div class="p-4 sm:p-6 space-y-5 max-h-[65vh] overflow-y-auto scrollbar-thin">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
