
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['paginator']));

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

foreach (array_filter((['paginator']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    if (!($paginator instanceof \Illuminate\Pagination\AbstractCursorPaginator) && method_exists($paginator, 'onEachSide')) {
        $paginator->onEachSide(1);
    }
    $elements = $paginator instanceof \Illuminate\Pagination\AbstractCursorPaginator ? [] : $paginator->links()->elements ?? [];
    // build manually to control link attributes
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $pages = [];
    if ($lastPage <= 7) {
        for ($i = 1; $i <= $lastPage; $i++) $pages[] = $i;
    } else {
        $pages[] = 1;
        if ($currentPage > 3) $pages[] = '...';
        $start = max(2, $currentPage - 1);
        $end = min($lastPage - 1, $currentPage + 1);
        for ($i = $start; $i <= $end; $i++) $pages[] = $i;
        if ($currentPage < $lastPage - 2) $pages[] = '...';
        $pages[] = $lastPage;
    }
?>

<?php if($paginator->hasPages()): ?>
    <?php if(!$paginator->onFirstPage()): ?>
        <a
            href="<?php echo e($paginator->previousPageUrl()); ?>"
            data-partial-link
            class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 flex items-center justify-center text-xs font-bold transition-all"
            aria-label="السابق"
            rel="prev"
        >
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
        </a>
    <?php else: ?>
        <span class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-300 flex items-center justify-center text-xs font-bold cursor-not-allowed" aria-disabled="true">
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
        </span>
    <?php endif; ?>

    <?php $__currentLoopData = $pages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if($page === '...'): ?>
            <span class="w-8 h-8 flex items-center justify-center text-slate-400 text-xs font-bold">…</span>
        <?php elseif($page == $currentPage): ?>
            <span class="w-8 h-8 rounded-lg bg-teal-500 text-white shadow-md shadow-teal-500/30 flex items-center justify-center text-xs font-bold" aria-current="page"><?php echo e($page); ?></span>
        <?php else: ?>
            <a
                href="<?php echo e($paginator->url($page)); ?>"
                data-partial-link
                class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 flex items-center justify-center text-xs font-bold transition-all"
            ><?php echo e($page); ?></a>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <?php if($paginator->hasMorePages()): ?>
        <a
            href="<?php echo e($paginator->nextPageUrl()); ?>"
            data-partial-link
            class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 flex items-center justify-center text-xs font-bold transition-all"
            aria-label="التالي"
            rel="next"
        >
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
        </a>
    <?php else: ?>
        <span class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-300 flex items-center justify-center text-xs font-bold cursor-not-allowed" aria-disabled="true">
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
        </span>
    <?php endif; ?>
<?php endif; ?>
<?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/components/pagination.blade.php ENDPATH**/ ?>