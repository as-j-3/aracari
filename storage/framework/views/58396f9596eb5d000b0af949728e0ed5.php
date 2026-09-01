
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'icon' => 'inbox',
    'title' => 'لا توجد بيانات',
    'message' => 'لم يتم العثور على أي سجلات مطابقة.',
    'colspan' => 5,
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
    'icon' => 'inbox',
    'title' => 'لا توجد بيانات',
    'message' => 'لم يتم العثور على أي سجلات مطابقة.',
    'colspan' => 5,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<td colspan="<?php echo e($colspan); ?>" class="px-6 py-16 text-center">
    <div class="w-20 h-20 rounded-full bg-slate-50 mx-auto flex items-center justify-center text-slate-300 mb-4">
        <i data-lucide="<?php echo e($icon); ?>" class="w-9 h-9"></i>
    </div>
    <h3 class="text-lg font-bold text-slate-700 mb-1"><?php echo e($title); ?></h3>
    <p class="text-slate-400 text-sm mb-4"><?php echo e($message); ?></p>
    <?php if($slot->isNotEmpty()): ?>
        <div class="mt-2"><?php echo e($slot); ?></div>
    <?php endif; ?>
</td>
<?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/components/empty-state.blade.php ENDPATH**/ ?>