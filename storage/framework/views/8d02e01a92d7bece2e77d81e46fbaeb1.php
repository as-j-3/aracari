
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'searchable' => true,
    'searchMode' => 'server', // 'server' (default) | 'client'
    'searchPlaceholder' => 'ابحث...',
    'searchValue' => null,
    'showReset' => true,
    'footerInfo' => null, // e.g. "عرض 5 من 20 سجل"
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
    'searchable' => true,
    'searchMode' => 'server', // 'server' (default) | 'client'
    'searchPlaceholder' => 'ابحث...',
    'searchValue' => null,
    'showReset' => true,
    'footerInfo' => null, // e.g. "عرض 5 من 20 سجل"
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div
    id="data-table-card"
    data-search-mode="<?php echo e($searchMode); ?>"
    class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-3xl overflow-hidden shadow-xl shadow-slate-200/20 max-w-full"
>
    <?php if($searchable): ?>
        <div class="p-5 border-b border-slate-200/70 bg-white/40 backdrop-blur flex flex-col sm:flex-row gap-4 items-center justify-between">
            <form id="aracari-search-form" class="relative w-full sm:w-80">
                <i data-lucide="search" class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                <input
                    type="text"
                    id="aracari-search-input"
                    name="search"
                    value="<?php echo e($searchValue ?? request('search')); ?>"
                    placeholder="<?php echo e($searchPlaceholder); ?>"
                    class="w-full h-11 bg-white/70 border border-slate-200 rounded-2xl pr-11 pl-4 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all duration-300"
                >
            </form>
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <?php if($showReset && ($searchMode === 'client' || request('search') || request()->query())): ?>
                    <button
                        id="aracari-search-reset"
                        type="button"
                        class="h-11 px-4 bg-rose-50/80 border border-rose-200 text-rose-600 rounded-xl font-bold text-sm hover:bg-rose-500 hover:text-white hover:border-rose-500 transition-all flex items-center gap-1.5 flex-shrink-0"
                    >
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                        <span class="hidden sm:inline">إعادة تعيين</span>
                    </button>
                <?php endif; ?>
                
                <?php echo e($filters ?? ''); ?>

            </div>
        </div>
    <?php endif; ?>

    
    <div class="overflow-x-auto min-w-0">
        <table class="w-full min-w-[640px]">
            <thead class="bg-slate-50/70 border-b border-slate-200/70">
                <?php echo e($thead); ?>

            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if(isset($rows)): ?>
                    <?php echo e($rows); ?>

                <?php endif; ?>
            </tbody>
        </table>
    </div>

    
    <?php if($footerInfo || (isset($footer) && $footer->isNotEmpty())): ?>
        <div class="px-6 py-4 border-t border-slate-200/70 bg-slate-50/40 flex flex-col sm:flex-row items-center justify-between gap-3">
            <?php if($footerInfo): ?>
                <p data-footer-info class="text-[10px] font-bold text-slate-400"><?php echo e($footerInfo); ?></p>
            <?php endif; ?>
            <?php if(isset($footer)): ?>
                <div data-pagination-wrap class="flex items-center gap-1.5 flex-wrap"><?php echo e($footer); ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/components/data-table-card.blade.php ENDPATH**/ ?>