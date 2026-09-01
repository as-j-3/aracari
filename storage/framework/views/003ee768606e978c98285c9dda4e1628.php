
<?php if(session('success')): ?>
    <div class="aracari-flash aracari-flash-success mb-4 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center gap-3 animate-flash-in" role="alert">
        <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
        <span class="text-sm font-bold"><?php echo e(session('success')); ?></span>
        <button type="button" class="mr-auto text-emerald-400 hover:text-emerald-600" onclick="this.parentElement.remove()">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
<?php endif; ?>

<?php if(session('error')): ?>
    <div class="aracari-flash aracari-flash-error mb-4 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 flex items-center gap-3 animate-flash-in" role="alert">
        <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
        <span class="text-sm font-bold"><?php echo e(session('error')); ?></span>
        <button type="button" class="mr-auto text-rose-400 hover:text-rose-600" onclick="this.parentElement.remove()">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
<?php endif; ?>

<?php if(session('warning')): ?>
    <div class="aracari-flash aracari-flash-warning mb-4 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-700 flex items-center gap-3 animate-flash-in" role="alert">
        <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0"></i>
        <span class="text-sm font-bold"><?php echo e(session('warning')); ?></span>
        <button type="button" class="mr-auto text-amber-400 hover:text-amber-600" onclick="this.parentElement.remove()">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
<?php endif; ?>

<?php if(session('info')): ?>
    <div class="aracari-flash aracari-flash-info mb-4 p-4 rounded-2xl bg-blue-50 border border-blue-200 text-blue-700 flex items-center gap-3 animate-flash-in" role="alert">
        <i data-lucide="info" class="w-5 h-5 flex-shrink-0"></i>
        <span class="text-sm font-bold"><?php echo e(session('info')); ?></span>
        <button type="button" class="mr-auto text-blue-400 hover:text-blue-600" onclick="this.parentElement.remove()">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
<?php endif; ?>


<?php if($errors->any()): ?>
    <div class="aracari-flash aracari-flash-error mb-4 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700" role="alert">
        <div class="flex items-start gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
            <div class="flex-1">
                <p class="text-sm font-bold mb-1">يرجى تصحيح الأخطاء التالية:</p>
                <ul class="text-xs font-medium list-disc pr-4 space-y-0.5">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
            <button type="button" class="text-rose-400 hover:text-rose-600" onclick="this.parentElement.remove()">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </div>
<?php endif; ?>
<?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/partials/flash.blade.php ENDPATH**/ ?>