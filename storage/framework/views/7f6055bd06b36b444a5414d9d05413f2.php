<?php $__env->startSection('title', 'أركاري | فروع المزودين'); ?>
<?php $__env->startSection('header_title', 'فروع المزودين'); ?>

<?php $__env->startSection('content'); ?>

<?php
    $totalBranches  = $branches->total();
    $activeCount    = (clone $branches->getCollection())->where('status', \App\Constants\BranchStatus::ACTIVE)->count();
    $inactiveCount  = $totalBranches - $activeCount;
    $providersCount = (clone $branches->getCollection())->unique('provider_id')->count();
    $citiesCount    = (clone $branches->getCollection())->unique('city_id')->count();

    $statusLabels = \App\Constants\BranchStatus::all();

    $branchesJson = $branches->getCollection()->map(fn($b) => [
        'uuid'            => $b->uuid,
        'id'              => $b->id,
        'address'         => $b->address,
        'phone'           => $b->phone_number,
        'lat'             => $b->latitude,
        'lng'             => $b->longitude,
        'status'          => $b->status,
        'statusLabel'     => $statusLabels[$b->status] ?? $b->status,
        'provider'        => $b->provider?->name,
        'providerPhone'   => $b->provider?->contact_phone,
        'providerEmail'   => $b->provider?->contact_email,
        'providerWebsite' => $b->provider?->website,
        'providerCr'      => $b->provider?->cr_number,
        'serviceType'     => $b->provider?->typeOfService?->type_name,
        'ownerName'       => $b->provider?->user?->name,
        'city'            => $b->city?->name,
        'country'         => $b->country?->name,
        'usersCount'      => $b->branch_users_count ?? 0,
        'tripsCount'      => $b->trips_count ?? 0,
        'usersList'       => $b->branchUsers?->map(fn($bu) => $bu->user?->name)->filter()->values()->all() ?? [],
        'createdAt'       => $b->created_at?->format('Y-m-d H:i'),
        'updatedAt'       => $b->updated_at?->diffForHumans(),
    ])->values()->toJson();
?>

<div class="space-y-6 font-cairo text-right" dir="rtl">

  
  <?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['icon' => 'building-2','title' => 'فروع المزودين','count' => $totalBranches,'countLabel' => 'فرع','description' => 'الإطلاع على فروع مزودي الخدمة ومواقعهم الجغرافية','descriptionIcon' => 'eye']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'building-2','title' => 'فروع المزودين','count' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalBranches),'countLabel' => 'فرع','description' => 'الإطلاع على فروع مزودي الخدمة ومواقعهم الجغرافية','descriptionIcon' => 'eye']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e)): ?>
<?php $attributes = $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e; ?>
<?php unset($__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e)): ?>
<?php $component = $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e; ?>
<?php unset($__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e); ?>
<?php endif; ?>

  
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $totalBranches,'label' => 'إجمالي الفروع','icon' => 'building-2','variant' => 'teal','badge' => 'مسجّلة','badgeIcon' => 'map-pin']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalBranches),'label' => 'إجمالي الفروع','icon' => 'building-2','variant' => 'teal','badge' => 'مسجّلة','badgeIcon' => 'map-pin']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $activeCount,'label' => 'فروع نشطة','icon' => 'check-circle','variant' => 'emerald','badge' => 'تعمل','badgeIcon' => 'wifi']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($activeCount),'label' => 'فروع نشطة','icon' => 'check-circle','variant' => 'emerald','badge' => 'تعمل','badgeIcon' => 'wifi']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $providersCount,'label' => 'عدد المزودين','icon' => 'store','variant' => 'blue','badge' => 'شركة','badgeIcon' => 'building-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($providersCount),'label' => 'عدد المزودين','icon' => 'store','variant' => 'blue','badge' => 'شركة','badgeIcon' => 'building-2']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $citiesCount,'label' => 'مدن مغطاة','icon' => 'map-pin','variant' => 'amber','badge' => 'مدن','badgeIcon' => 'map-pin']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($citiesCount),'label' => 'مدن مغطاة','icon' => 'map-pin','variant' => 'amber','badge' => 'مدن','badgeIcon' => 'map-pin']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
  </div>

  
  <div id="aracari-refreshable">

  <?php if (isset($component)) { $__componentOriginal90c131072f947a512cfcd740e0e498fa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal90c131072f947a512cfcd740e0e498fa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.data-table-card','data' => ['searchable' => true,'searchMode' => 'server','searchPlaceholder' => 'ابحث عن فرع...','footerInfo' => 'عرض ' . $branches->count() . ' من أصل ' . $branches->total() . ' فرع']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('data-table-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['searchable' => true,'searchMode' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('server'),'searchPlaceholder' => 'ابحث عن فرع...','footerInfo' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('عرض ' . $branches->count() . ' من أصل ' . $branches->total() . ' فرع')]); ?>
     <?php $__env->slot('filters', null, []); ?> 
      <?php if($isAdmin && $providers->isNotEmpty()): ?>
        <select
            name="provider_id"
            onchange="window.location.href='<?php echo e(route('admin.provider-branches.index')); ?>?provider_id='+this.value"
            class="h-11 bg-white/70 border border-slate-200 rounded-xl px-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all cursor-pointer"
        >
          <option value="">كل المزودين</option>
          <?php $__currentLoopData = $providers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($id); ?>" <?php echo e(request('provider_id') == $id ? 'selected' : ''); ?>><?php echo e($name); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      <?php endif; ?>

      <select
          name="status"
          onchange="window.location.href='<?php echo e(route('admin.provider-branches.index')); ?>?status='+this.value"
          class="h-11 bg-white/70 border border-slate-200 rounded-xl px-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all cursor-pointer"
      >
        <option value="">كل الحالات</option>
        <?php $__currentLoopData = $statusLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($key); ?>" <?php echo e(request('status') == $key ? 'selected' : ''); ?>><?php echo e($label); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
     <?php $__env->endSlot(); ?>

     <?php $__env->slot('thead', null, []); ?> 
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المزود</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">المدينة</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">العنوان</th>
      <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الهاتف</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
      <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">عرض</th>
     <?php $__env->endSlot(); ?>

     <?php $__env->slot('rows', null, []); ?> 
      <?php $__empty_1 = true; $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr class="hover:bg-slate-50/50 transition-colors group">

          
          <td class="px-6 py-4">
            <span class="font-mono font-black text-slate-400 text-sm"><?php echo e(str_pad($branch->id, 4, '0', STR_PAD_LEFT)); ?></span>
          </td>

          
          <td data-searchable class="px-6 py-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500/20 to-purple-600/10 flex items-center justify-center border border-purple-100 text-purple-600 group-hover:scale-110 transition-transform duration-300">
                <i data-lucide="store" class="w-5 h-5"></i>
              </div>
              <div>
                <div class="font-bold text-slate-800 text-sm"><?php echo e($branch->provider?->name ?? '—'); ?></div>
                <div class="text-[10px] text-slate-400 mt-0.5"><?php echo e($branch->provider?->typeOfService?->type_name ?? ''); ?></div>
              </div>
            </div>
          </td>

          
          <td data-searchable class="px-6 py-4">
            <span class="inline-flex items-center gap-1.5 text-sm font-bold text-slate-700">
              <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400"></i>
              <?php echo e($branch->city?->name ?? '—'); ?>

            </span>
          </td>

          
          <td data-searchable class="px-6 py-4 max-w-[220px]">
            <?php if($branch->address): ?>
              <p class="text-xs text-slate-600 leading-relaxed line-clamp-2" title="<?php echo e($branch->address); ?>"><?php echo e($branch->address); ?></p>
            <?php else: ?>
              <span class="text-xs text-slate-400">—</span>
            <?php endif; ?>
          </td>

          
          <td class="px-6 py-4">
            <?php if($branch->phone_number): ?>
              <span class="inline-flex items-center gap-1.5 text-xs font-mono text-slate-600 bg-slate-50 border border-slate-200 px-2.5 py-1.5 rounded-lg">
                <i data-lucide="phone" class="w-3 h-3 text-slate-400"></i>
                <?php echo e($branch->phone_number); ?>

              </span>
            <?php else: ?>
              <span class="text-xs text-slate-400">—</span>
            <?php endif; ?>
          </td>

          
          <td class="px-6 py-4 text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-bold border
              <?php echo e($branch->status === \App\Constants\BranchStatus::ACTIVE
                  ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                  : 'bg-slate-100 text-slate-500 border-slate-200'); ?>">
              <span class="w-1.5 h-1.5 rounded-full
                <?php echo e($branch->status === \App\Constants\BranchStatus::ACTIVE
                    ? 'bg-emerald-500'
                    : 'bg-slate-400'); ?>"></span>
              <?php echo e($statusLabels[$branch->status] ?? $branch->status); ?>

            </span>
          </td>

          
          <td class="px-6 py-4 text-center">
            <button
                type="button"
                onclick="openBranchModal('<?php echo e($branch->uuid); ?>')"
                class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-teal-50 hover:text-teal-600 hover:border-teal-200 transition-all duration-300 shadow-sm flex items-center justify-center mx-auto cursor-pointer"
                title="عرض التفاصيل"
            >
              <i data-lucide="eye" class="w-4 h-4"></i>
            </button>
          </td>

        </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
          <td colspan="7" class="px-6 py-16 text-center">
            <div class="flex flex-col items-center gap-3">
              <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-300">
                <i data-lucide="building-2" class="w-8 h-8"></i>
              </div>
              <p class="text-sm font-bold text-slate-400">لا توجد فروع مسجلة</p>
            </div>
          </td>
        </tr>
      <?php endif; ?>
     <?php $__env->endSlot(); ?>

     <?php $__env->slot('footer', null, []); ?> 
      <?php echo e($branches->links()); ?>

     <?php $__env->endSlot(); ?>

   <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal90c131072f947a512cfcd740e0e498fa)): ?>
<?php $attributes = $__attributesOriginal90c131072f947a512cfcd740e0e498fa; ?>
<?php unset($__attributesOriginal90c131072f947a512cfcd740e0e498fa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal90c131072f947a512cfcd740e0e498fa)): ?>
<?php $component = $__componentOriginal90c131072f947a512cfcd740e0e498fa; ?>
<?php unset($__componentOriginal90c131072f947a512cfcd740e0e498fa); ?>
<?php endif; ?>

  </div>

</div>


<div id="branch-detail-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
  <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeBranchModal()"></div>
  <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">

    
    <div class="relative bg-gradient-to-l from-teal-600 to-teal-700 p-5 text-white flex-shrink-0">
      <div class="absolute top-0 left-0 w-40 h-40 bg-white/5 rounded-full blur-3xl -translate-y-1/2 -translate-x-1/2"></div>
      <div class="absolute bottom-0 right-0 w-24 h-24 bg-white/5 rounded-full blur-2xl translate-y-1/2 translate-x-1/2"></div>
      <div class="relative z-10 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-11 h-11 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center">
            <i data-lucide="building-2" class="w-5 h-5"></i>
          </div>
          <div>
            <h3 class="font-bold text-base">تفاصيل الفرع</h3>
            <p class="text-[11px] text-white/60 mt-0.5" id="modal-branch-subtitle"></p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span id="modal-branch-id" class="text-[10px] font-mono text-white/40 bg-white/10 px-2 py-1 rounded-lg"></span>
          <button type="button" onclick="closeBranchModal()" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 flex items-center justify-center transition-all cursor-pointer">
            <i data-lucide="x" class="w-4 h-4"></i>
          </button>
        </div>
      </div>
    </div>

    
    <div class="flex-1 overflow-y-auto" id="modal-branch-body"></div>

  </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
var branchDataAll = <?php echo $branchesJson; ?>;

var branchModal    = document.getElementById('branch-detail-modal');
var branchBody     = document.getElementById('modal-branch-body');
var branchSubtitle = document.getElementById('modal-branch-subtitle');
var branchIdBadge  = document.getElementById('modal-branch-id');

function bStatusBadge(status, label) {
  var isActive = status === 'active';
  var c = isActive ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200';
  var dot = isActive ? 'bg-emerald-500' : 'bg-slate-400';
  return '<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-bold border ' + c + '">' +
    '<span class="w-1.5 h-1.5 rounded-full ' + dot + '"></span>' +
    label + '</span>';
}

function bSection(title, icon, content) {
  return '<div class="p-5 border-b border-slate-100 last:border-b-0">' +
    '<div class="flex items-center gap-2 mb-4">' +
      '<div class="w-7 h-7 rounded-lg bg-teal-50 flex items-center justify-center text-teal-600">' +
        '<i data-lucide="' + icon + '" class="w-3.5 h-3.5"></i>' +
      '</div>' +
      '<h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">' + title + '</h4>' +
    '</div>' +
    '<div class="space-y-3">' + content + '</div>' +
  '</div>';
}

function bRow(icon, label, value, mono) {
  var vc = mono ? 'text-xs font-mono text-slate-700' : 'text-sm font-bold text-slate-700';
  return '<div class="flex items-start gap-3">' +
    '<div class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 mt-0.5 flex-shrink-0">' +
      '<i data-lucide="' + icon + '" class="w-4 h-4"></i>' +
    '</div>' +
    '<div class="flex-1 min-w-0">' +
      '<p class="text-[10px] font-bold text-slate-400">' + label + '</p>' +
      '<p class="' + vc + ' mt-0.5">' + (value || '—') + '</p>' +
    '</div>' +
  '</div>';
}

function bMiniStatUsers(val) {
  return '<div class="flex-1 min-w-0 bg-purple-50 border border-purple-100 rounded-xl p-3 text-center">' +
    '<div class="flex items-center justify-center gap-1.5 mb-1">' +
      '<i data-lucide="users" class="w-3.5 h-3.5 text-purple-500"></i>' +
      '<span class="text-lg font-black text-purple-700">' + val + '</span>' +
    '</div>' +
    '<p class="text-[10px] font-bold text-purple-500">مستخدم مرتبط</p>' +
  '</div>';
}

function bMiniStatTrips(val) {
  return '<div class="flex-1 min-w-0 bg-blue-50 border border-blue-100 rounded-xl p-3 text-center">' +
    '<div class="flex items-center justify-center gap-1.5 mb-1">' +
      '<i data-lucide="bus" class="w-3.5 h-3.5 text-blue-500"></i>' +
      '<span class="text-lg font-black text-blue-700">' + val + '</span>' +
    '</div>' +
    '<p class="text-[10px] font-bold text-blue-500">رحلة مرتبطة</p>' +
  '</div>';
}

function bMiniStatTime(val) {
  return '<div class="flex-1 min-w-0 bg-amber-50 border border-amber-100 rounded-xl p-3 text-center">' +
    '<div class="flex items-center justify-center gap-1.5 mb-1">' +
      '<i data-lucide="clock" class="w-3.5 h-3.5 text-amber-500"></i>' +
      '<span class="text-xs font-bold text-amber-700 leading-tight">' + (val || '—') + '</span>' +
    '</div>' +
    '<p class="text-[10px] font-bold text-amber-500">آخر تحديث</p>' +
  '</div>';
}

function bUserChip(name) {
  var parts = name.split(' ');
  var initials = parts[0][0];
  if (parts.length > 1) initials += parts[1][0];
  var palette = [
    ['bg-purple-100','text-purple-700'],
    ['bg-teal-100','text-teal-700'],
    ['bg-amber-100','text-amber-700'],
    ['bg-rose-100','text-rose-700'],
    ['bg-sky-100','text-sky-700']
  ];
  var c = palette[name.length % palette.length];
  return '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold ' + c[0] + ' ' + c[1] + '">' +
    '<span class="w-5 h-5 rounded-md ' + c[0] + ' flex items-center justify-center text-[9px] font-black">' + initials + '</span>' +
    name + '</span>';
}

function openBranchModal(uuid) {
  var b = branchDataAll.find(function(item) { return item.uuid === uuid; });
  if (!b) return;

  branchSubtitle.textContent = b.city ? b.city + (b.country ? ' - ' + b.country : '') : 'فرع #' + b.id;
  branchIdBadge.textContent = '#' + String(b.id).padStart(4, '0');

  var html = '';

  // Quick stats
  html += '<div class="grid grid-cols-3 gap-3 p-5 bg-slate-50/50">' +
    bMiniStatUsers(b.usersCount) +
    bMiniStatTrips(b.tripsCount) +
    bMiniStatTime(b.updatedAt) +
  '</div>';

  // Section: بيانات الفرع
  var rows = '';
  rows += bRow('store', 'المزود', b.provider);
  if (b.serviceType) rows += bRow('layers', 'نوع الخدمة', b.serviceType);
  if (b.ownerName) rows += bRow('user-circle', 'مالك الحساب', b.ownerName);
  var locStr = (b.city || '') + (b.country ? ' - ' + b.country : '');
  rows += bRow('map-pin', 'الموقع', locStr);
  rows += bRow('navigation', 'العنوان', b.address);
  rows += bRow('phone', 'رقم الهاتف', b.phone, true);
  if (b.lat && b.lng) rows += bRow('globe', 'الإحداثيات', b.lat + ', ' + b.lng, true);
  rows += '<div class="flex items-start gap-3">' +
    '<div class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 mt-0.5 flex-shrink-0">' +
      '<i data-lucide="toggle-left" class="w-4 h-4"></i>' +
    '</div>' +
    '<div class="flex-1 min-w-0">' +
      '<p class="text-[10px] font-bold text-slate-400">الحالة</p>' +
      '<div class="mt-1">' + bStatusBadge(b.status, b.statusLabel) + '</div>' +
    '</div></div>';
  html += bSection('بيانات الفرع', 'building-2', rows);

  // Section: بيانات المزود
  var pRows = '';
  if (b.providerPhone) pRows += bRow('phone', 'هاتف التواصل', b.providerPhone, true);
  if (b.providerEmail) pRows += bRow('mail', 'البريد الإلكتروني', b.providerEmail);
  if (b.providerWebsite) pRows += bRow('external-link', 'الموقع الإلكتروني', b.providerWebsite);
  if (b.providerCr) pRows += bRow('file-text', 'رقم السجل التجاري', b.providerCr, true);
  if (pRows) html += bSection('بيانات المزود', 'briefcase', pRows);

  // Section: المستخدمين
  if (b.usersList && b.usersList.length > 0) {
    var uHtml = '<div class="flex flex-wrap gap-2">';
    b.usersList.forEach(function(n){ uHtml += bUserChip(n); });
    uHtml += '</div>';
    html += bSection('المستخدمين المرتبطين (' + b.usersList.length + ')', 'users', uHtml);
  }

  // Footer
  html += '<div class="px-5 py-3 bg-slate-50/50 border-t border-slate-100">' +
    '<div class="flex items-center justify-between text-[10px] text-slate-400 font-bold">' +
      '<span class="flex items-center gap-1"><i data-lucide="calendar" class="w-3 h-3"></i> تاريخ التسجيل: ' + (b.createdAt || '—') + '</span>' +
      '<span class="flex items-center gap-1"><i data-lucide="refresh-cw" class="w-3 h-3"></i> آخر تحديث: ' + (b.updatedAt || '—') + '</span>' +
    '</div></div>';

  branchBody.innerHTML = html;
  branchModal.classList.remove('hidden');
  branchModal.classList.add('flex');
  if (window.lucide) window.lucide.createIcons();
}

function closeBranchModal() {
  branchModal.classList.add('hidden');
  branchModal.classList.remove('flex');
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape' && !branchModal.classList.contains('hidden')) closeBranchModal();
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/admin/provider_branches/index.blade.php ENDPATH**/ ?>