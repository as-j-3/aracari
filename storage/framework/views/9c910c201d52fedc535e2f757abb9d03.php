
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'variant' => 'custom', // view | edit | delete | custom
    'icon' => null,
    'href' => null,
    'title' => null,
    'ariaLabel' => null,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'variant' => 'custom', // view | edit | delete | custom
    'icon' => null,
    'href' => null,
    'title' => null,
    'ariaLabel' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $config = [
        'view'   => ['icon' => 'eye',     'title' => 'عرض',   'classes' => 'hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200'],
        'edit'   => ['icon' => 'pencil',  'title' => 'تعديل', 'classes' => 'hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200'],
        'delete' => ['icon' => 'trash-2', 'title' => 'حذف',   'classes' => 'hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200'],
        'custom' => ['icon' => $icon ?: 'circle', 'title' => $title ?: '', 'classes' => 'hover:bg-slate-50 hover:text-slate-700 hover:border-slate-200'],
    ];
    $c = $config[$variant] ?? $config['custom'];
    $resolvedIcon = $c['icon'];
    $resolvedTitle = $title ?: $c['title'];
    $resolvedLabel = $ariaLabel ?: $resolvedTitle;

    $cleanHref = $href ? trim((string) $href, '"\'') : null;
    $isLink = !empty($cleanHref);
    $tag = $isLink ? 'a' : 'button';
?>

<?php if($isLink): ?>
<a href="<?php echo e($cleanHref); ?>"
   class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-500 <?php echo e($c['classes']); ?> transition-all duration-300 shadow-sm flex items-center justify-center"
   title="<?php echo e($resolvedTitle); ?>"
   aria-label="<?php echo e($resolvedLabel); ?>"
   <?php echo e($attributes->only(['data-view-modal', 'data-view-json', 'data-edit-modal', 'data-edit-json', 'data-delete-url', 'data-delete-label', 'data-create-modal', 'data-partial-link'])); ?>>
    <i data-lucide="<?php echo e($resolvedIcon); ?>" class="w-4 h-4"></i>
</a>
<?php else: ?>
<button type="button"
        class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-500 <?php echo e($c['classes']); ?> transition-all duration-300 shadow-sm flex items-center justify-center"
        title="<?php echo e($resolvedTitle); ?>"
        aria-label="<?php echo e($resolvedLabel); ?>"
        <?php echo e($attributes->only(['data-view-modal', 'data-view-json', 'data-edit-modal', 'data-edit-json', 'data-delete-url', 'data-delete-label', 'data-create-modal', 'data-partial-link'])); ?>>
    <i data-lucide="<?php echo e($resolvedIcon); ?>" class="w-4 h-4"></i>
</button>
<?php endif; ?><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/components/action-button.blade.php ENDPATH**/ ?>