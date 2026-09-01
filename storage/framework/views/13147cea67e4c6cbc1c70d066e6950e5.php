
<?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['id' => 'aracari-delete-modal','title' => 'تأكيد الحذف','variant' => 'view','icon' => 'trash-2','static' => true,'maxWidth' => 'max-w-md']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'aracari-delete-modal','title' => 'تأكيد الحذف','variant' => 'view','icon' => 'trash-2','static' => true,'maxWidth' => 'max-w-md']); ?>
    <form
        id="aracari-delete-form"
        method="POST"
        data-aracari-ajax
        data-success-message="تم الحذف بنجاح"
        class="space-y-5"
    >
        <?php echo csrf_field(); ?>
        <input type="hidden" name="_method" value="DELETE">
        
        <div class="aracari-error-banner hidden p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-600 text-xs flex items-start gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
            <span data-banner-message>حدث خطأ أثناء الحذف.</span>
        </div>

        
        <div class="text-center space-y-3 py-2">
            <div class="w-16 h-16 rounded-full bg-rose-50 border border-rose-100 mx-auto flex items-center justify-center">
                <i data-lucide="trash-2" class="w-8 h-8 text-rose-500"></i>
            </div>
            <h3 class="text-base font-bold text-slate-800" data-delete-title>تأكيد الحذف</h3>
            <p class="text-sm text-slate-500 leading-relaxed" data-delete-message>
                هل أنت متأكد من حذف هذا العنصر؟ لا يمكن التراجع عن هذا الإجراء.
            </p>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-xs font-bold">
                <i data-lucide="tag" class="w-3.5 h-3.5"></i>
                <span data-delete-label>—</span>
            </div>
        </div>

        
        <div class="flex gap-3 pt-1">
            <button
                type="button"
                data-close-modal
                class="flex-1 bg-[#f3f3f6] hover:bg-[#eeeef0] text-gray-600 font-bold py-3 px-4 rounded-xl text-xs transition-all flex items-center justify-center gap-2"
            >
                <i data-lucide="x" class="w-4 h-4"></i>
                إلغاء
            </button>
            <button
                type="submit"
                class="flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 bg-rose-600 hover:bg-rose-700 active:scale-95"
            >
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                <span>نعم، احذف</span>
            </button>
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
<?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/components/delete-confirm.blade.php ENDPATH**/ ?>