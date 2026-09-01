
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'id' => 'aracari-modal',
    'title' => '',
    'subtitle' => '',
    'icon' => null,
    'variant' => 'create', // create | edit | view
    'static' => false,
    'maxWidth' => 'max-w-lg',
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
    'id' => 'aracari-modal',
    'title' => '',
    'subtitle' => '',
    'icon' => null,
    'variant' => 'create', // create | edit | view
    'static' => false,
    'maxWidth' => 'max-w-lg',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
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
?>

<div
    id="<?php echo e($id); ?>"
    data-aracari-modal
    class="hidden fixed inset-0 z-[80] overflow-y-auto font-cairo"
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
    <?php if($static): ?> data-static <?php endif; ?>
>
    
    <div
        class="aracari-modal-overlay fixed inset-0 bg-[#1a1c1e]/60 backdrop-blur-sm opacity-0 transition-opacity duration-200"
    ></div>

    
    <div class="flex min-h-full items-center justify-center p-4">
        <div
            class="aracari-modal-panel relative bg-white rounded-2xl <?php echo e($maxWidth); ?> w-full shadow-2xl overflow-hidden border border-[#eeeef0] flex flex-col text-right opacity-0 scale-95 translate-y-4 transition-all duration-200"
            dir="rtl"
        >
            
            <div class="relative h-24 text-white p-5 flex flex-col justify-end <?php echo e($bannerClass); ?>">
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
                    <span class="text-[10px] font-bold px-2.5 py-1 rounded-full border <?php echo e($badgeClass); ?>">
                        <?php echo e($badgeLabel); ?>

                    </span>
                    <h2 class="text-lg font-extrabold tracking-tight flex items-center gap-2">
                        <?php if($resolvedIcon): ?>
                            <i data-lucide="<?php echo e($resolvedIcon); ?>" class="w-5 h-5"></i>
                        <?php endif; ?>
                        <?php echo e($title); ?>

                    </h2>
                    <?php if($subtitle): ?>
                        <p class="text-xs text-white/70"><?php echo e($subtitle); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="p-4 sm:p-6 space-y-5 max-h-[65vh] overflow-y-auto scrollbar-thin">
                <?php echo e($slot); ?>

            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/components/modal.blade.php ENDPATH**/ ?>