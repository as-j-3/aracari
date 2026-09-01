
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'name' => '',
    'label' => '',
    'icon' => null,
    'required' => false,
    'type' => 'text', // text | select | textarea | checkbox | radio | date | number | email | tel | password | file
    'hint' => null,
    'value' => null,
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
    'name' => '',
    'label' => '',
    'icon' => null,
    'required' => false,
    'type' => 'text', // text | select | textarea | checkbox | radio | date | number | email | tel | password | file
    'hint' => null,
    'value' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $fieldId = $name ?: ('aracari-field-' . str_replace(' ', '-', strtolower($label)));
?>

<div class="space-y-2">
    <?php if($type !== 'checkbox'): ?>
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1" for="<?php echo e($fieldId); ?>">
            <?php if($icon): ?>
                <i data-lucide="<?php echo e($icon); ?>" class="w-3.5 h-3.5"></i>
            <?php endif; ?>
            <?php echo e($label); ?>

            <?php if($required): ?><span class="text-rose-500">*</span><?php endif; ?>
        </label>
    <?php endif; ?>

    <?php if($slot->isNotEmpty()): ?>
        <?php echo e($slot); ?>

    <?php elseif($type === 'textarea'): ?>
        <textarea
            name="<?php echo e($name); ?>"
            id="<?php echo e($fieldId); ?>"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
            <?php if($required): ?> required <?php endif; ?>
        ><?php echo e(old($name, $value)); ?></textarea>
    <?php elseif($type === 'select'): ?>
        
        <?php echo e($slot); ?>

    <?php else: ?>
        <div class="relative group">
            <?php if($icon): ?>
                <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
                    <i data-lucide="<?php echo e($icon); ?>" class="w-4 h-4"></i>
                </div>
            <?php endif; ?>
            <input
                type="<?php echo e($type); ?>"
                name="<?php echo e($name); ?>"
                id="<?php echo e($fieldId); ?>"
                value="<?php echo e(old($name, $value)); ?>"
                class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl <?php echo e($icon ? 'pr-10' : 'pr-3'); ?> pl-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
                <?php if($required): ?> required <?php endif; ?>
            >
        </div>
    <?php endif; ?>

    <?php if($hint): ?>
        <p class="text-[10px] text-slate-400"><?php echo e($hint); ?></p>
    <?php endif; ?>

    
    <p class="text-[11px] text-rose-600 font-bold" data-error-for="<?php echo e($name); ?>"></p>
</div>
<?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/components/form-field.blade.php ENDPATH**/ ?>