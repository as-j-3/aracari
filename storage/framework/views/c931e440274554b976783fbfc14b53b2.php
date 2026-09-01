
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'id' => 'aracari-form-modal',
    'title' => '',
    'subtitle' => null,
    'icon' => null,
    'variant' => 'create', // create | edit
    'action' => '',
    'method' => 'POST',
    'successMessage' => 'تم الحفظ بنجاح',
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
    'id' => 'aracari-form-modal',
    'title' => '',
    'subtitle' => null,
    'icon' => null,
    'variant' => 'create', // create | edit
    'action' => '',
    'method' => 'POST',
    'successMessage' => 'تم الحفظ بنجاح',
    'maxWidth' => 'max-w-lg',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['id' => $id,'title' => $title,'subtitle' => $subtitle,'icon' => $icon,'variant' => $variant,'maxWidth' => $maxWidth]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($id),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($title),'subtitle' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($subtitle),'icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($icon),'variant' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($variant),'maxWidth' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($maxWidth)]); ?>
    <form
        method="POST"
        action="<?php echo e($action); ?>"
        data-aracari-ajax
        data-success-message="<?php echo e($successMessage); ?>"
        class="space-y-5"
    >
        <?php echo csrf_field(); ?>
        <?php if(strtoupper($method) !== 'POST'): ?>
            <input type="hidden" name="_method" value="<?php echo e(strtoupper($method)); ?>" data-dynamic="true">
        <?php endif; ?>

        
        <div class="aracari-error-banner hidden p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-600 text-xs flex items-start gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
            <span data-banner-message>يرجى تصحيح الأخطاء أدناه.</span>
        </div>

        
        <?php echo e($slot); ?>


        
        <div class="flex gap-3 pt-1">
            <button
                type="button"
                data-close-modal
                class="flex-1 bg-[#f3f3f6] hover:bg-[#eeeef0] text-gray-600 font-bold py-3 px-4 rounded-xl text-xs transition-all flex items-center justify-center gap-2"
            >
                <i data-lucide="x" class="w-4 h-4"></i>
                إلغاء
            </button>
            <?php if(isset($actions)): ?>
                <?php echo e($actions); ?>

            <?php else: ?>
                <button
                    type="submit"
                    class="flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95 <?php echo e($variant === 'edit' ? 'bg-amber-600 hover:bg-amber-700' : 'bg-[#115128] hover:bg-emerald-950'); ?>"
                >
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span><?php echo e($variant === 'edit' ? 'حفظ التعديلات' : 'حفظ'); ?></span>
                </button>
            <?php endif; ?>
        </div>
    </form>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $attributes = $__attributesOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__attributesOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $component = $__componentOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__componentOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/components/form-modal.blade.php ENDPATH**/ ?>