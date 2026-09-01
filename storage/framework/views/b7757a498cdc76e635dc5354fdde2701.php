<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['icon' => 'info', 'label' => '', 'value' => '-', 'raw' => false]));

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

foreach (array_filter((['icon' => 'info', 'label' => '', 'value' => '-', 'raw' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="flex items-center justify-between gap-2 p-3 bg-slate-50/50 rounded-xl border border-slate-200/70">
    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
        <span class="text-slate-400"><i data-lucide="<?php echo e($icon); ?>" class="w-3.5 h-3.5"></i></span>
        <?php echo e($label); ?>

    </span>
    <div class="font-bold text-slate-700 text-sm"><?php echo $raw ? $value : e($value); ?></div>
</div>
<?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/admin/provider-services/_info-row.blade.php ENDPATH**/ ?>