{{--
    Aracari Form Field
    Renders label + input/select/textarea + error slot.

    Usage:
      <x-form-field name="name" label="الاسم" icon="tag" required>
        <input type="text" name="name" id="name" class="aracari-input" value="{{ old('name') }}">
      </x-form-field>

    The error element is auto-rendered and wired to aracari.js (data-error-for).
--}}
@props([
    'name' => '',
    'label' => '',
    'icon' => null,
    'required' => false,
    'type' => 'text', // text | select | textarea | checkbox | radio | date | number | email | tel | password | file
    'hint' => null,
    'value' => null,
])

@php
    $fieldId = $name ?: ('aracari-field-' . str_replace(' ', '-', strtolower($label)));
@endphp

<div class="space-y-2">
    @if($type !== 'checkbox')
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="{{ $fieldId }}">
            @if($icon)
                <i data-lucide="{{ $icon }}" class="w-3.5 h-3.5"></i>
            @endif
            {{ $label }}
            @if($required)<span class="text-rose-500">*</span>@endif
        </label>
    @endif

    @if($slot->isNotEmpty())
        {{ $slot }}
    @elseif($type === 'textarea')
        <textarea
            name="{{ $name }}"
            id="{{ $fieldId }}"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
            @if($required) required @endif
        >{{ old($name, $value) }}</textarea>
    @elseif($type === 'select')
        {{-- caller supplies <select> via slot --}}
        {{ $slot }}
    @else
        <div class="relative group">
            @if($icon)
                <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
                    <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>
                </div>
            @endif
            <input
                type="{{ $type }}"
                name="{{ $name }}"
                id="{{ $fieldId }}"
                value="{{ old($name, $value) }}"
                class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl {{ $icon ? 'pr-10' : 'pr-3' }} pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
                @if($required) required @endif
            >
        </div>
    @endif

    @if($hint)
        <p class="text-[10px] text-slate-400">{{ $hint }}</p>
    @endif

    {{-- Error slot — wired to aracari.js --}}
    <p class="text-[11px] text-rose-600 font-bold" data-error-for="{{ $name }}"></p>
</div>
