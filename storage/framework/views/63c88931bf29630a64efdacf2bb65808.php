<?php
    $statusBadge = [
        'active'   => ['label' => 'نشط',        'cls' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'check-circle'],
        'pending'  => ['label' => 'في الانتظار', 'cls' => 'bg-amber-50 text-amber-700 border-amber-200',      'icon' => 'clock'],
        'inactive' => ['label' => 'غير نشط',    'cls' => 'bg-rose-50 text-rose-700 border-rose-200',        'icon' => 'x-circle'],
    ];
    $sMeta = $statusBadge[$providerService->status] ?? $statusBadge['inactive'];

    $branchStatusBadge = [
        'active'   => ['label' => 'نشط',    'cls' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'check-circle'],
        'inactive' => ['label' => 'غير نشط', 'cls' => 'bg-rose-50 text-rose-700 border-rose-200',        'icon' => 'x-circle'],
    ];

    $fileStatusBadge = [
        'active'   => ['label' => 'نشط',    'cls' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        'inactive' => ['label' => 'غير نشط', 'cls' => 'bg-rose-50 text-rose-700 border-rose-200'],
    ];

    $brandPhotoUrl = $providerService->brand_photo ? \Storage::url($providerService->brand_photo) : null;
    $crDocumentUrl = $providerService->cr_document ? \Storage::url($providerService->cr_document) : null;
    $commissionTypeLabel = $providerService->commission_type === 'service' ? 'خدمة' : 'حجز';

    $user = auth()->user();
    $canEdit   = $user && $user->hasPermission('providers:edit');
    $canDelete = $user && $user->hasPermission('providers:delete');
?>


<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    
    <div class="lg:col-span-2 bg-white border border-[#eeeef0] rounded-2xl p-4 sm:p-5 shadow-sm">
        <div class="flex items-center justify-between border-b border-[#eeeef0] pb-3 mb-4">
            <h4 class="font-bold text-sm text-slate-800 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center text-white shadow-md">
                    <i data-lucide="info" class="w-4 h-4"></i>
                </div>
                بيانات الخدمة
            </h4>
            <span class="text-[9px] font-bold text-teal-600 bg-teal-50 border border-teal-200 px-2 py-0.5 rounded-full">معلومات أساسية</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <?php echo $__env->make('admin.provider-services._info-row', ['icon' => 'building-2', 'label' => 'المزود', 'value' => $providerService->user ? $providerService->user->name : '-'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('admin.provider-services._info-row', ['icon' => 'file-text', 'label' => 'السجل التجاري', 'value' => '<span class=font-mono dir=ltr>' . e($providerService->cr_number) . '</span>', 'raw' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('admin.provider-services._info-row', ['icon' => 'phone', 'label' => 'هاتف التواصل', 'value' => $providerService->contact_phone ?: '-'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('admin.provider-services._info-row', ['icon' => 'mail', 'label' => 'البريد الإلكتروني', 'value' => $providerService->contact_email ?: '-'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('admin.provider-services._info-row', ['icon' => 'percent', 'label' => 'نوع العمولة', 'value' => $commissionTypeLabel], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <div class="md:col-span-2">
                <?php echo $__env->make('admin.provider-services._info-row', ['icon' => 'file-text', 'label' => 'وصف الخدمة', 'value' => $providerService->description ?: 'لا يوجد وصف'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </div>
    </div>

    
    <div class="bg-white border border-[#eeeef0] rounded-2xl p-4 sm:p-5 shadow-sm">
        <div class="flex items-center justify-between border-b border-[#eeeef0] pb-3 mb-4">
            <h4 class="font-bold text-sm text-slate-800 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center text-white shadow-md">
                    <i data-lucide="image" class="w-4 h-4"></i>
                </div>
                الشعار
            </h4>
        </div>
        <?php if($brandPhotoUrl): ?>
        <div class="relative w-full aspect-square rounded-xl overflow-hidden border-2 border-slate-200 group">
            <img src="<?php echo e($brandPhotoUrl); ?>" alt="<?php echo e($providerService->name); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 flex items-center justify-center transition-all">
                <span class="text-white text-[10px] font-bold opacity-0 group-hover:opacity-100 transition-opacity">شعار <?php echo e($providerService->name); ?></span>
            </div>
        </div>
        <?php else: ?>
        <div class="w-full aspect-square rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/50 flex flex-col items-center justify-center text-slate-300">
            <i data-lucide="image" class="w-10 h-10 mb-2"></i>
            <span class="text-[10px] font-bold">لا يوجد شعار</span>
        </div>
        <?php endif; ?>
        <div class="mt-4 space-y-1.5">
            <div class="flex items-center gap-1.5 text-[10px] text-slate-500">
                <i data-lucide="calendar" class="w-3 h-3 text-slate-400"></i>
                <span class="font-bold">الإنشاء:</span>
                <span class="font-mono text-slate-600" dir="ltr"><?php echo e($providerService->created_at?->format('Y-m-d H:i')); ?></span>
            </div>
            <div class="flex items-center gap-1.5 text-[10px] text-slate-500">
                <i data-lucide="clock" class="w-3 h-3 text-slate-400"></i>
                <span class="font-bold">آخر تحديث:</span>
                <span class="font-mono text-slate-600" dir="ltr"><?php echo e($providerService->updated_at?->format('Y-m-d H:i')); ?></span>
            </div>
        </div>
        <?php if($crDocumentUrl): ?>
        <div class="mt-4 pt-4 border-t border-[#eeeef0]">
            <a href="<?php echo e($crDocumentUrl); ?>" target="_blank" rel="noreferrer" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-amber-200 bg-amber-50 text-amber-700 text-xs font-bold hover:bg-amber-100 transition-colors">
                <i data-lucide="external-link" class="w-3.5 h-3.5"></i> عرض مستند السجل التجاري
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>


<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    
    <div class="bg-white border border-[#eeeef0] rounded-2xl p-4 sm:p-5 shadow-sm">
        <div class="flex items-center justify-between border-b border-[#eeeef0] pb-3 mb-3">
            <h4 class="font-bold text-sm text-slate-800 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white shadow-md">
                    <i data-lucide="store" class="w-4 h-4"></i>
                </div>
                الفروع
            </h4>
            <span class="text-[9px] font-bold text-blue-600 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-full"><?php echo e($providerService->branches ? $providerService->branches->count() : 0); ?> فرع</span>
        </div>
        <?php if($providerService->branches && $providerService->branches->count() > 0): ?>
        <div class="space-y-2 max-h-80 overflow-y-auto pl-1">
            <?php $__currentLoopData = $providerService->branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php $bMeta = $branchStatusBadge[$branch->status] ?? $branchStatusBadge['inactive']; ?>
            <div class="p-3 rounded-xl border border-slate-200/70 bg-slate-50/50 hover:bg-slate-50 transition-colors">
                <div class="flex items-start gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500/20 to-blue-600/10 flex items-center justify-center text-blue-600 border border-blue-100 flex-shrink-0">
                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-bold text-slate-800 truncate"><?php echo e($branch->city ? $branch->city->name : '-'); ?> <span class="text-[10px] font-medium text-slate-400">— <?php echo e($branch->country ? $branch->country->name : '-'); ?></span></span>
                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[9px] font-bold border <?php echo e($bMeta['cls']); ?>"><i data-lucide="<?php echo e($bMeta['icon']); ?>" class="w-2.5 h-2.5"></i> <?php echo e($bMeta['label']); ?></span>
                        </div>
                        <?php if($branch->address): ?> <p class="text-[11px] text-slate-500 mt-0.5 truncate"><?php echo e($branch->address); ?></p> <?php endif; ?>
                        <?php if($branch->phone_number): ?> <p class="text-[10px] text-slate-400 font-mono mt-0.5" dir="ltr"><?php echo e($branch->phone_number); ?></p> <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php else: ?>
        <div class="text-center py-8 text-slate-400">
            <i data-lucide="store" class="w-8 h-8 mx-auto mb-1.5 opacity-50"></i>
            <p class="text-xs font-bold">لا توجد فروع مسجلة</p>
        </div>
        <?php endif; ?>
    </div>

    
    <div class="bg-white border border-[#eeeef0] rounded-2xl p-4 sm:p-5 shadow-sm">
        <div class="flex items-center justify-between border-b border-[#eeeef0] pb-3 mb-3">
            <h4 class="font-bold text-sm text-slate-800 flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center text-white shadow-md">
                    <i data-lucide="folder" class="w-4 h-4"></i>
                </div>
                الملفات
            </h4>
            <span class="text-[9px] font-bold text-amber-600 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full"><?php echo e($files->total()); ?> ملف</span>
        </div>
        <?php if($files->count() > 0): ?>
        <div class="space-y-2 max-h-80 overflow-y-auto pl-1">
            <?php $__currentLoopData = $files; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $file): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php $fMeta = $fileStatusBadge[$file->status] ?? $fileStatusBadge['active']; $fUrl = $file->file ? \Storage::url($file->file) : null; ?>
            <div class="p-3 rounded-xl border border-slate-200/70 bg-slate-50/50 hover:bg-slate-50 transition-colors">
                <div class="flex items-start gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-500/20 to-amber-600/10 flex items-center justify-center text-amber-600 border border-amber-100 flex-shrink-0">
                        <i data-lucide="file-text" class="w-4 h-4"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-bold text-slate-800 truncate" title="<?php echo e($file->name); ?>"><?php echo e($file->name); ?></span>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold border <?php echo e($fMeta['cls']); ?>"><?php echo e($fMeta['label']); ?></span>
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-[10px] text-slate-400 font-mono"><?php echo e($file->file_type); ?></span>
                            <?php if($fUrl): ?> <a href="<?php echo e($fUrl); ?>" target="_blank" rel="noreferrer" class="text-[10px] text-teal-600 hover:underline inline-flex items-center gap-0.5"><i data-lucide="external-link" class="w-2.5 h-2.5"></i> عرض</a> <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php else: ?>
        <div class="text-center py-8 text-slate-400">
            <i data-lucide="folder" class="w-8 h-8 mx-auto mb-1.5 opacity-50"></i>
            <p class="text-xs font-bold">لا توجد ملفات مسجلة</p>
        </div>
        <?php endif; ?>
    </div>
</div>


<div class="bg-white border border-[#eeeef0] rounded-2xl p-4 sm:p-5 shadow-sm">
    <div class="flex items-center justify-between border-b border-[#eeeef0] pb-3 mb-3">
        <h4 class="font-bold text-sm text-slate-800 flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center text-white shadow-md">
                <i data-lucide="percent" class="w-4 h-4"></i>
            </div>
            العمولات
        </h4>
        <span class="text-[9px] font-bold text-purple-600 bg-purple-50 border border-purple-200 px-2 py-0.5 rounded-full"><?php echo e($providerService->serviceCommissions ? $providerService->serviceCommissions->count() : 0); ?> عمولة</span>
    </div>
    <?php if($providerService->serviceCommissions && $providerService->serviceCommissions->count() > 0): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <?php $__currentLoopData = $providerService->serviceCommissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $commission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="p-3 rounded-xl border border-slate-200/70 bg-slate-50/50 hover:bg-slate-50 transition-colors">
            <div class="flex items-start gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-500/20 to-purple-600/10 flex items-center justify-center text-purple-600 border border-purple-100 flex-shrink-0">
                    <i data-lucide="percent" class="w-4 h-4"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-bold text-slate-800 truncate"><?php echo e($commission->name ?? '-'); ?></div>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs font-black text-purple-700" dir="ltr"><?php echo e($commission->value ?? 0); ?><?php echo e(($commission->type ?? '') === 'percentage' ? '%' : ''); ?></span>
                        <span class="text-[9px] text-slate-400 font-bold bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded"><?php echo e(($commission->type ?? '') === 'percentage' ? 'نسبة مئوية' : 'مبلغ ثابت'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php else: ?>
    <div class="text-center py-8 text-slate-400">
        <i data-lucide="percent" class="w-8 h-8 mx-auto mb-1.5 opacity-50"></i>
        <p class="text-xs font-bold">لا توجد عمولات مسجلة</p>
    </div>
    <?php endif; ?>
</div>


<div class="flex flex-wrap items-center justify-end gap-2 pt-2">
    <button type="button" data-close-modal class="px-5 py-2.5 bg-[#f3f3f6] hover:bg-[#eeeef0] text-gray-600 font-bold text-xs rounded-xl transition-all flex items-center gap-1.5">
        <i data-lucide="x" class="w-3.5 h-3.5"></i> إغلاق
    </button>
    <?php if($canDelete): ?>
    <button type="button" data-close-modal data-delete-url="<?php echo e(route('admin.provider-services.destroy', $providerService->uuid)); ?>" data-delete-label="<?php echo e($providerService->name); ?>" class="px-5 py-2.5 bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700 text-white font-bold text-xs rounded-xl transition-all flex items-center gap-1.5 shadow-md shadow-rose-500/20">
        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> حذف
    </button>
    <?php endif; ?>
    <?php if($canEdit): ?>
    <form method="POST" action="<?php echo e(route('admin.provider-services.toggle-status', $providerService->uuid)); ?>" class="inline" data-aracari-ajax data-success-message="تم تغيير الحالة بنجاح">
        <?php echo csrf_field(); ?>
        <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 text-white font-bold text-xs rounded-xl transition-all flex items-center gap-1.5 shadow-md shadow-slate-500/20">
            <?php if($providerService->status === 'active'): ?> <i data-lucide="pause-circle" class="w-3.5 h-3.5"></i> إيقاف <?php else: ?> <i data-lucide="play-circle" class="w-3.5 h-3.5"></i> تفعيل <?php endif; ?>
        </button>
    </form>
    <button type="button" data-close-modal data-edit-modal="ps-form-modal" data-edit-json="<?php echo e(json_encode(['uuid' => $providerService->uuid, 'user_id' => $providerService->user_id, 'user_name' => $providerService->user ? $providerService->user->name : '', 'name' => $providerService->name, 'type_of_service_id' => $providerService->type_of_service_id, 'cr_number' => $providerService->cr_number, 'status' => $providerService->status, 'commission_type' => $providerService->commission_type, 'contact_phone' => $providerService->contact_phone, 'contact_email' => $providerService->contact_email, 'description' => $providerService->description, 'brand_photo_url' => $brandPhotoUrl, 'cr_document_url' => $crDocumentUrl], JSON_UNESCAPED_UNICODE)); ?>" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-bold text-xs rounded-xl transition-all flex items-center gap-1.5 shadow-md shadow-amber-500/20">
        <i data-lucide="pencil" class="w-3.5 h-3.5"></i> تعديل
    </button>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/admin/provider-services/_details.blade.php ENDPATH**/ ?>