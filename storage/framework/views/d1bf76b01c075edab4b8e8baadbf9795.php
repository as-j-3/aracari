
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'value' => 0,
    'label' => '',
    'icon' => null,
    'variant' => 'slate',
    'badge' => null,
    'badgeIcon' => null,
    'caption' => null,
    'id' => null,
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
    'value' => 0,
    'label' => '',
    'icon' => null,
    'variant' => 'slate',
    'badge' => null,
    'badgeIcon' => null,
    'caption' => null,
    'id' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $gradients = [
        'slate'   => 'from-slate-900 to-slate-800',
        'emerald' => 'from-emerald-900/95 to-emerald-800/95',
        'blue'    => 'from-blue-900/95 to-blue-800/95',
        'amber'   => 'from-amber-900/95 to-amber-800/95',
        'rose'    => 'from-rose-900/95 to-rose-800/95',
        'teal'    => 'from-teal-900 to-teal-950',
    ];
    $iconBg = [
        'slate'   => 'from-teal-500 to-teal-600 shadow-teal-500/20',
        'emerald' => 'from-emerald-500 to-emerald-600 shadow-emerald-500/20',
        'blue'    => 'from-blue-500 to-blue-600 shadow-blue-500/20',
        'amber'   => 'from-amber-500 to-amber-600 shadow-amber-500/20',
        'rose'    => 'from-rose-500 to-rose-600 shadow-rose-500/20',
        'teal'    => 'from-teal-500 to-teal-600 shadow-teal-500/20',
    ];
    $orbFrom = [
        'slate'   => 'from-teal-500/10',
        'emerald' => 'from-emerald-500/10',
        'blue'    => 'from-blue-500/10',
        'amber'   => 'from-amber-500/10',
        'rose'    => 'from-rose-500/10',
        'teal'    => 'from-teal-500/10',
    ];
    $labelColor = [
        'slate'   => 'text-slate-400',
        'emerald' => 'text-emerald-300/70',
        'blue'    => 'text-blue-300/70',
        'amber'   => 'text-amber-300/70',
        'rose'    => 'text-rose-300/70',
        'teal'    => 'text-teal-300/70',
    ];
    $hoverBorder = [
        'slate'   => 'hover:border-teal-500/20 hover:shadow-teal-500/10',
        'emerald' => 'hover:border-emerald-500/20 hover:shadow-emerald-500/10',
        'blue'    => 'hover:border-blue-500/20 hover:shadow-blue-500/10',
        'amber'   => 'hover:border-amber-500/20 hover:shadow-amber-500/10',
        'rose'    => 'hover:border-rose-500/20 hover:shadow-rose-500/10',
        'teal'    => 'hover:border-teal-500/20 hover:shadow-teal-500/10',
    ];
    $badgeStyle = [
        'slate'   => 'text-emerald-400 bg-emerald-400/10 border-emerald-400/20',
        'emerald' => 'text-emerald-400 bg-emerald-400/20 border-emerald-400/30',
        'blue'    => 'text-blue-400 bg-blue-400/20 border-blue-400/30',
        'amber'   => 'text-amber-400 bg-amber-400/20 border-amber-400/30',
        'rose'    => 'text-rose-400 bg-rose-400/20 border-rose-400/30',
        'teal'    => 'text-teal-400 bg-teal-400/20 border-teal-400/30',
    ];
?>

<div
    class="group relative overflow-hidden rounded-3xl bg-gradient-to-br <?php echo e($gradients[$variant] ?? $gradients['slate']); ?> p-6 border border-white/5 transition-all duration-500 hover:shadow-2xl <?php echo e($hoverBorder[$variant] ?? $hoverBorder['slate']); ?> hover:-translate-y-1"
    <?php if($id): ?> id="<?php echo e($id); ?>" <?php endif; ?>
>
    <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br <?php echo e($orbFrom[$variant] ?? $orbFrom['slate']); ?> to-transparent rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
    <div class="relative z-10 flex items-start justify-between">
        <div class="space-y-1.5 text-right">
            <p class="text-xs font-bold <?php echo e($labelColor[$variant] ?? $labelColor['slate']); ?> uppercase tracking-wider"><?php echo e($label); ?></p>
            <h3 class="text-3xl font-extrabold tracking-tight mt-1"><?php echo e(is_numeric($value) ? number_format($value) : $value); ?></h3>
            <?php if($badge || $caption): ?>
                <div class="flex items-center gap-1.5 mt-2">
                    <?php if($badge): ?>
                        <span class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-0.5 rounded-full border <?php echo e($badgeStyle[$variant] ?? $badgeStyle['slate']); ?>">
                            <?php if($badgeIcon): ?><i data-lucide="<?php echo e($badgeIcon); ?>" class="w-3 h-3"></i><?php endif; ?>
                            <?php echo e($badge); ?>

                        </span>
                    <?php endif; ?>
                    <?php if($caption): ?>
                        <span class="text-[10px] text-slate-500 font-medium"><?php echo e($caption); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php if($icon): ?>
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br <?php echo e($iconBg[$variant] ?? $iconBg['slate']); ?> flex items-center justify-center shadow-lg group-hover:shadow-lg group-hover:scale-110 transition-all duration-300 text-white">
                <i data-lucide="<?php echo e($icon); ?>" class="w-6 h-6"></i>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/components/stat-card.blade.php ENDPATH**/ ?>