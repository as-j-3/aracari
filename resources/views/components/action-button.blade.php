{{--
    Aracari Action Button
    Icon button for table row actions (view / edit / delete) or custom.

    Usage:
      <x-action-button variant="view" data-view-modal="..." data-view-json="{{ json_encode($row) }}" />
      <x-action-button variant="edit" data-edit-modal="..." data-edit-json="{{ json_encode($row) }}" />
      <x-action-button variant="delete" data-delete-url="..." data-delete-label="{{ $row->name }}" />
      <x-action-button variant="custom" icon="eye" href="..." />
--}}
@props([
    'variant' => 'custom', // view | edit | delete | custom
    'icon' => null,
    'href' => null,
    'title' => null,
    'ariaLabel' => null,
])

@php
    $config = [
        'view'   => ['icon' => 'eye',     'title' => 'عرض',   'classes' => 'hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200'],
        'edit'   => ['icon' => 'pencil',  'title' => 'تعديل', 'classes' => 'hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200'],
        'delete' => ['icon' => 'trash-2', 'title' => 'حذف',   'classes' => 'hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200'],
        'custom' => ['icon' => $icon ?: 'circle', 'title' => $title ?: '', 'classes' => 'hover:bg-slate-50 hover:text-slate-700 hover:border-slate-200'],
    ];
    $c = $config[$variant] ?? $config['custom'];
    $resolvedIcon = $c['icon'];
    $resolvedTitle = $title ?: $c['title'];
    $tag = $href ? 'a' : 'button';
    $extraAttrs = $href ? 'href="' . e($href) . '"' : 'type="button"';
@endphp

<{{ $tag }}
    {{ $extraAttrs }}
    class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-500 {{ $c['classes'] }} transition-all duration-300 shadow-sm flex items-center justify-center"
    title="{{ $resolvedTitle }}"
    aria-label="{{ $ariaLabel ?: $resolvedTitle }}"
    {{ $attributes->only(['data-view-modal', 'data-view-json', 'data-edit-modal', 'data-edit-json', 'data-delete-url', 'data-delete-label', 'data-create-modal', 'data-partial-link']) }}
>
    <i data-lucide="{{ $resolvedIcon }}" class="w-4 h-4"></i>
</{{ $tag }}>
