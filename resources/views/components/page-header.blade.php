{{--
    Aracari Page Header
    Glassmorphic header with icon, title, count badge, description, and action slot.

    Usage:
      <x-page-header icon="globe" title="الدول" :count="$countries->total()" countLabel="دولة" description="إدارة الدول والمدن">
        <x-slot name="action">
          <button data-create-modal="country-modal" class="...">إضافة دولة</button>
        </x-slot>
      </x-page-header>
--}}
@props([
    'icon' => null,
    'title' => '',
    'count' => null,
    'countLabel' => '',
    'description' => null,
    'descriptionIcon' => null,
])

<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white/60 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/70 shadow-sm relative overflow-hidden">
    <div class="absolute top-0 right-0 w-64 h-64 bg-teal-500/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
    <div class="flex items-center gap-4 relative z-10">
        @if($icon)
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center shadow-lg shadow-teal-500/20 flex-shrink-0">
                <i data-lucide="{{ $icon }}" class="w-7 h-7 text-white"></i>
            </div>
        @endif
        <div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2">
                {{ $title }}
                @if($count !== null)
                    <span class="text-xs font-bold text-slate-400 bg-slate-100 px-3 py-1 rounded-full">{{ number_format($count) }} {{ $countLabel }}</span>
                @endif
            </h2>
            @if($description)
                <p class="text-slate-500 font-medium text-sm flex items-center gap-2 mt-1">
                    @if($descriptionIcon)<i data-lucide="{{ $descriptionIcon }}" class="w-4 h-4 text-slate-400"></i>@endif
                    {{ $description }}
                </p>
            @endif
        </div>
    </div>
    @if($slot->isNotEmpty())
        <div class="relative z-10">
            {{ $slot }}
        </div>
    @endif
</div>
