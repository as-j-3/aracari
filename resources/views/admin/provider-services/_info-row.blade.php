@props(['icon' => 'info', 'label' => '', 'value' => '-', 'raw' => false])

<div class="flex items-center justify-between gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
        <span class="text-slate-400"><i data-lucide="{{ $icon }}" class="w-3.5 h-3.5"></i></span>
        {{ $label }}
    </span>
    <div class="font-bold text-slate-700 text-sm">{!! $raw ? $value : e($value) !!}</div>
</div>
