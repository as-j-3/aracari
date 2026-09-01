
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'icon' => null,
    'title' => '',
    'count' => null,
    'countLabel' => '',
    'description' => null,
    'descriptionIcon' => null,
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
    'icon' => null,
    'title' => '',
    'count' => null,
    'countLabel' => '',
    'description' => null,
    'descriptionIcon' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white/60 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/70 shadow-sm relative overflow-hidden">
    <div class="absolute top-0 right-0 w-64 h-64 bg-teal-500/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
    <div class="flex items-center gap-4 relative z-10">
        <?php if($icon): ?>
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center shadow-lg shadow-teal-500/20 flex-shrink-0">
                <i data-lucide="<?php echo e($icon); ?>" class="w-7 h-7 text-white"></i>
            </div>
        <?php endif; ?>
        <div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2">
                <?php echo e($title); ?>

                <?php if($count !== null): ?>
                    <span class="text-xs font-bold text-slate-400 bg-slate-100 px-3 py-1 rounded-full"><?php echo e(number_format($count)); ?> <?php echo e($countLabel); ?></span>
                <?php endif; ?>
            </h2>
            <?php if($description): ?>
                <p class="text-slate-500 font-medium text-sm flex items-center gap-2 mt-1">
                    <?php if($descriptionIcon): ?><i data-lucide="<?php echo e($descriptionIcon); ?>" class="w-4 h-4 text-slate-400"></i><?php endif; ?>
                    <?php echo e($description); ?>

                </p>
            <?php endif; ?>
        </div>
    </div>
    <?php if($slot->isNotEmpty()): ?>
        <div class="relative z-10">
            <?php echo e($slot); ?>

        </div>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/components/page-header.blade.php ENDPATH**/ ?>