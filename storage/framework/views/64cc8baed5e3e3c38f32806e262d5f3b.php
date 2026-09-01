<?php $__env->startSection('title', 'أركاري | القيود المحاسبية'); ?>
<?php $__env->startSection('header_title', 'القيود المحاسبية'); ?>

<?php $__env->startSection('content'); ?>

<?php
    // ===== Permissions =====
    $user       = auth()->user();
    $canView    = $user && $user->hasPermission('journal:view');
    $canCreate  = $user && $user->hasPermission('journal:create');
    $canEdit    = $user && $user->hasPermission('journal:edit');
    $canDelete  = $user && $user->hasPermission('journal:delete');

    if (!$canView) {
        abort(403);
    }

    // ===== Style Mappings =====
    $statusStyles = [
        'draft'     => ['bg' => 'bg-amber-50',  'text' => 'text-amber-700',  'border' => 'border-amber-200',  'dot' => 'bg-amber-500',  'icon' => 'pencil',     'label' => 'مسودة'],
        'confirmed' => ['bg' => 'bg-emerald-50','text' => 'text-emerald-700','border' => 'border-emerald-200','dot' => 'bg-emerald-500','icon' => 'check-circle','label' => 'معتمد'],
        'reversed'  => ['bg' => 'bg-purple-50', 'text' => 'text-purple-700', 'border' => 'border-purple-200', 'dot' => 'bg-purple-500', 'icon' => 'undo',       'label' => 'معكوس'],
        'cancelled' => ['bg' => 'bg-rose-50',   'text' => 'text-rose-700',   'border' => 'border-rose-200',   'dot' => 'bg-rose-500',   'icon' => 'x-circle',    'label' => 'ملغي'],
    ];
    $defaultStatusStyle = ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'border' => 'border-slate-200', 'dot' => 'bg-slate-400', 'icon' => 'help-circle', 'label' => 'غير معروف'];

    $getStatusStyle = function($status) use ($statusStyles, $defaultStatusStyle) {
        return $statusStyles[$status] ?? $defaultStatusStyle;
    };

    // Type → badge mapping
    $typeStyles = [
        'booking'                 => ['label' => 'حجز',       'bg' => 'bg-sky-50',    'text' => 'text-sky-700',    'border' => 'border-sky-200'],
        'refund'                  => ['label' => 'استرداد',   'bg' => 'bg-rose-50',   'text' => 'text-rose-700',   'border' => 'border-rose-200'],
        'booking_modification'    => ['label' => 'تعديل حجز','bg' => 'bg-amber-50',  'text' => 'text-amber-700',  'border' => 'border-amber-200'],
        'emergency_cancellation'  => ['label' => 'إلغاء طارئ','bg' => 'bg-orange-50', 'text' => 'text-orange-700', 'border' => 'border-orange-200'],
        'reversal'                => ['label' => 'عكس قيد',  'bg' => 'bg-purple-50', 'text' => 'text-purple-700', 'border' => 'border-purple-200'],
        'manual_journal_entry'    => ['label' => 'يدوي',     'bg' => 'bg-slate-100', 'text' => 'text-slate-700',  'border' => 'border-slate-200'],
    ];
    $defaultTypeStyle = ['label' => 'أخرى', 'bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'border' => 'border-slate-200'];

    $getTypeStyle = function($refType) use ($typeStyles, $defaultTypeStyle) {
        return $typeStyles[$refType] ?? $defaultTypeStyle;
    };

    // ===== Stats =====
    $totalCount     = \App\Models\TransactionReference::count();
    $draftCount     = \App\Models\TransactionReference::where('status', 'draft')->count();
    $confirmedCount = \App\Models\TransactionReference::where('status', 'confirmed')->count();
    $reversedCount  = \App\Models\TransactionReference::where('status', 'reversed')->count();

    $statusTabCounts = [
        'all'       => $totalCount,
        'draft'     => $draftCount,
        'confirmed' => $confirmedCount,
        'reversed'  => $reversedCount,
        'cancelled' => \App\Models\TransactionReference::where('status', 'cancelled')->count(),
    ];

    $currentStatus = request('status');
    $hasActiveFilters = $currentStatus || request('search');
    $baseUrl = route('admin.accounting.journal-entries.index');

    // Helpers
    $getDebitTotal = function($entry) {
        return (float) $entry->transactions->where('transaction_type', 'debit')->sum('amount');
    };
    $getCreditTotal = function($entry) {
        return (float) $entry->transactions->where('transaction_type', 'credit')->sum('amount');
    };
    $isBalanced = function($entry) use ($getDebitTotal, $getCreditTotal) {
        return abs($getDebitTotal($entry) - $getCreditTotal($entry)) < 0.01;
    };
    $getEntryDescription = function($entry) {
        if (!empty($entry->description)) {
            return $entry->description;
        }
        return $entry->transactions->first()?->description ?? '—';
    };
    $getTransactionCount = function($entry) {
        return $entry->transactions->count();
    };
    $formatAmount = function($value) {
        return number_format($value, 2);
    };

    // Build edit data for each entry (used in data-edit-json)
    $buildEditData = function($entry) {
        $lines = [];
        foreach ($entry->transactions as $tx) {
            $lines[] = [
                'account_code' => $tx->account_code ?? '',
                'debit'        => $tx->transaction_type === 'debit' ? $tx->amount : '',
                'credit'       => $tx->transaction_type === 'credit' ? $tx->amount : '',
                'description'  => $tx->description ?? '',
            ];
        }
        return [
            'reference_number' => $entry->reference_number,
            'date'             => $entry->created_at?->format('Y-m-d') ?? date('Y-m-d'),
            'description'      => $entry->transactions->first()?->description ?? '',
            'lines'            => $lines,
        ];
    };
?>

<div class="space-y-6 font-cairo text-right" dir="rtl">

  
  <?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['icon' => 'receipt-text','title' => 'القيود المحاسبية','count' => $totalCount,'countLabel' => 'قيد','description' => 'إدارة القيود المحاسبية وتتبع المعاملات المالية','descriptionIcon' => 'scale']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'receipt-text','title' => 'القيود المحاسبية','count' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalCount),'countLabel' => 'قيد','description' => 'إدارة القيود المحاسبية وتتبع المعاملات المالية','descriptionIcon' => 'scale']); ?>
    <?php if($canCreate): ?>
      <button
          type="button"
          data-create-modal="journal-entry-form-modal"
          class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
      >
        <i data-lucide="plus" class="w-4 h-4"></i>
        <span class="hidden sm:inline">إنشاء قيد جديد</span>
        <span class="sm:hidden">إنشاء</span>
      </button>
    <?php endif; ?>
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

  
  <div class="bg-white/60 backdrop-blur-xl border border-slate-200/70 rounded-3xl p-3 flex flex-wrap gap-2">
    <?php
        $tabs = [
            ['id' => '',           'label' => 'الكل',   'icon' => 'list-filter', 'colorClasses' => 'from-slate-700 to-slate-800 shadow-slate-700/30'],
            ['id' => 'draft',      'label' => 'مسودة',  'icon' => 'pencil',      'colorClasses' => 'from-amber-500 to-amber-600 shadow-amber-500/30'],
            ['id' => 'confirmed',  'label' => 'معتمد',  'icon' => 'check-circle', 'colorClasses' => 'from-emerald-500 to-emerald-600 shadow-emerald-500/30'],
            ['id' => 'reversed',   'label' => 'معكوس', 'icon' => 'undo',        'colorClasses' => 'from-purple-500 to-purple-600 shadow-purple-500/30'],
            ['id' => 'cancelled',  'label' => 'ملغي',   'icon' => 'x-circle',    'colorClasses' => 'from-rose-500 to-rose-600 shadow-rose-500/30'],
        ];
    ?>
    <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <?php
          $isActive = $currentStatus === $tab['id'];
          $tabUrl = $tab['id']
              ? $baseUrl . '?' . http_build_query(array_filter(['search' => request('search'), 'status' => $tab['id']]))
              : ($baseUrl . (request('search') ? '?search=' . urlencode(request('search')) : ''));
          $tabCount = $statusTabCounts[$tab['id']] ?? 0;
      ?>
      <a
          href="<?php echo e($tabUrl); ?>"
          class="px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 transition-all duration-300 flex-1 sm:flex-initial justify-center <?php echo e($isActive ? 'bg-gradient-to-r ' . $tab['colorClasses'] . ' text-white shadow-lg' : 'bg-white/70 border border-slate-200 text-slate-600 hover:bg-white hover:border-slate-300'); ?>"
      >
        <i data-lucide="<?php echo e($tab['icon']); ?>" class="w-3.5 h-3.5"></i>
        <span><?php echo e($tab['label']); ?></span>
        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full <?php echo e($isActive ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500'); ?>">
          <?php echo e($tabCount); ?>

        </span>
      </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>

  
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $totalCount,'label' => 'إجمالي القيود','icon' => 'receipt-text','variant' => 'slate','badge' => ''.e($totalCount > 0 ? 'مُسجّل' : 'فارغ').'','badgeIcon' => 'trending-up']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalCount),'label' => 'إجمالي القيود','icon' => 'receipt-text','variant' => 'slate','badge' => ''.e($totalCount > 0 ? 'مُسجّل' : 'فارغ').'','badgeIcon' => 'trending-up']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $draftCount,'label' => 'مسودات','icon' => 'pencil','variant' => 'amber','badge' => 'مسودة','badgeIcon' => 'pencil']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($draftCount),'label' => 'مسودات','icon' => 'pencil','variant' => 'amber','badge' => 'مسودة','badgeIcon' => 'pencil']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $confirmedCount,'label' => 'معتمدة','icon' => 'check-circle','variant' => 'emerald','badge' => 'معتمدة','badgeIcon' => 'check-circle']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($confirmedCount),'label' => 'معتمدة','icon' => 'check-circle','variant' => 'emerald','badge' => 'معتمدة','badgeIcon' => 'check-circle']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['value' => $reversedCount,'label' => 'معكوسة','icon' => 'undo','variant' => 'blue','badge' => 'معكوسة','badgeIcon' => 'undo']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($reversedCount),'label' => 'معكوسة','icon' => 'undo','variant' => 'blue','badge' => 'معكوسة','badgeIcon' => 'undo']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.data-table-card','data' => ['searchable' => true,'searchMode' => 'server','searchPlaceholder' => 'ابحث برقم المرجع أو الوصف...','footerInfo' => 'عرض ' . $journalEntries->count() . ' من أصل ' . $journalEntries->total() . ' قيد']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('data-table-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['searchable' => true,'searchMode' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('server'),'searchPlaceholder' => 'ابحث برقم المرجع أو الوصف...','footerInfo' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('عرض ' . $journalEntries->count() . ' من أصل ' . $journalEntries->total() . ' قيد')]); ?>
     <?php $__env->slot('thead', null, []); ?> 
      <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
      <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">رقم المرجع</th>
      <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">التاريخ</th>
      <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">النوع</th>
      <th class="px-5 py-3.5 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الوصف</th>
      <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الأسطر</th>
      <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">المبلغ</th>
      <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">التوازن</th>
      <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الحالة</th>
      <th class="px-5 py-3.5 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">الإجراءات</th>
     <?php $__env->endSlot(); ?>

     <?php $__env->slot('rows', null, []); ?> 
      <?php $__empty_1 = true; $__currentLoopData = $journalEntries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
            $sStyle     = $getStatusStyle($entry->status);
            $tStyle     = $getTypeStyle($entry->reference_type);
            $debit      = $getDebitTotal($entry);
            $credit     = $getCreditTotal($entry);
            $balanced   = $isBalanced($entry);
            $lineCount  = $getTransactionCount($entry);
            $desc       = $getEntryDescription($entry);
            $rowNumber  = $journalEntries->firstItem() + $index;
        ?>
        <tr class="hover:bg-slate-50/50 transition-colors group">
          
          <td class="px-5 py-3.5">
            <span class="text-xs font-bold text-slate-400"><?php echo e($rowNumber); ?></span>
          </td>

          
          <td data-searchable class="px-5 py-3.5">
            <div class="flex items-center gap-2">
              <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-sky-500/20 to-sky-600/10 flex items-center justify-center border border-sky-100 group-hover:scale-110 transition-transform duration-300 flex-shrink-0">
                <i data-lucide="hash" class="w-4 h-4 text-sky-600"></i>
              </div>
              <span class="font-mono font-black text-slate-600 text-sm" dir="ltr">
                <?php echo e($entry->reference_number); ?>

              </span>
            </div>
          </td>

          
          <td class="px-5 py-3.5">
            <div class="flex flex-col">
              <span class="font-mono font-bold text-slate-700 text-sm" dir="ltr">
                <?php echo e($entry->created_at?->format('Y/m/d')); ?>

              </span>
              <span class="font-mono text-[10px] text-slate-400" dir="ltr">
                <?php echo e($entry->created_at?->format('H:i')); ?>

              </span>
            </div>
          </td>

          
          <td class="px-5 py-3.5 text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full <?php echo e($tStyle['bg']); ?> <?php echo e($tStyle['text']); ?> text-[10px] font-bold border <?php echo e($tStyle['border']); ?>">
              <?php echo e($tStyle['label']); ?>

            </span>
          </td>

          
          <td data-searchable class="px-5 py-3.5">
            <div class="max-w-[180px]">
              <p class="text-xs font-bold text-slate-700 leading-relaxed line-clamp-2">
                <?php echo e($desc); ?>

              </p>
            </div>
          </td>

          
          <td class="px-5 py-3.5 text-center">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-[10px] font-bold border border-slate-200">
              <i data-lucide="building-2" class="w-3 h-3"></i>
              <?php echo e($lineCount); ?>

            </span>
          </td>

          
          <td class="px-5 py-3.5 text-center">
            <div class="inline-flex flex-col items-center gap-0.5">
              <span class="font-mono font-bold text-emerald-700 text-sm" dir="ltr">
                <?php echo e($formatAmount($debit)); ?>

              </span>
              <span class="text-[9px] font-bold text-slate-400" dir="ltr">
                م <?php echo e($formatAmount($credit)); ?> د
              </span>
            </div>
          </td>

          
          <td class="px-5 py-3.5 text-center">
            <?php if($balanced): ?>
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">
                <i data-lucide="check-circle" class="w-3 h-3"></i>
                متوازن
              </span>
            <?php else: ?>
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 text-[10px] font-bold border border-rose-200">
                <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                غير متوازن
              </span>
            <?php endif; ?>
          </td>

          
          <td class="px-5 py-3.5 text-center">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full <?php echo e($sStyle['bg']); ?> <?php echo e($sStyle['text']); ?> text-[10px] font-bold border <?php echo e($sStyle['border']); ?>">
              <span class="relative flex h-2 w-2">
                <?php if($entry->status === 'confirmed'): ?>
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full <?php echo e($sStyle['dot']); ?> opacity-75"></span>
                <?php endif; ?>
                <span class="relative inline-flex rounded-full h-2 w-2 <?php echo e($sStyle['dot']); ?>"></span>
              </span>
              <?php echo e($sStyle['label']); ?>

            </span>
          </td>

          
          <td class="px-5 py-3.5 text-center">
            <div class="flex items-center justify-center gap-1.5">
              <?php if($entry->status === 'draft'): ?>
                
                <?php if($canEdit): ?>
                  <form
                      method="POST"
                      action="<?php echo e(route('admin.accounting.journal-entries.confirm', $entry->reference_number)); ?>"
                      onsubmit="return confirm('هل أنت متأكد من اعتماد هذا القيد؟ سيتم ترصيد الحسابات فوراً.')"
                      class="inline-flex"
                  >
                    <?php echo csrf_field(); ?>
                    <button
                        type="submit"
                        class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200 transition-all duration-300 shadow-sm flex items-center justify-center"
                        title="اعتماد القيد"
                    >
                      <i data-lucide="check" class="w-4 h-4"></i>
                    </button>
                  </form>
                <?php endif; ?>

                
                <?php if($canEdit): ?>
                  <?php if (isset($component)) { $__componentOriginald4c6978101b1c254eb70511d3c21c03f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald4c6978101b1c254eb70511d3c21c03f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.action-button','data' => ['variant' => 'edit','dataEditModal' => 'journal-entry-form-modal','dataEditJson' => ''.e(json_encode($buildEditData($entry), JSON_UNESCAPED_UNICODE)).'','ariaLabel' => 'تعديل ' . $entry->reference_number]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('action-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'edit','data-edit-modal' => 'journal-entry-form-modal','data-edit-json' => ''.e(json_encode($buildEditData($entry), JSON_UNESCAPED_UNICODE)).'','ariaLabel' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('تعديل ' . $entry->reference_number)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald4c6978101b1c254eb70511d3c21c03f)): ?>
<?php $attributes = $__attributesOriginald4c6978101b1c254eb70511d3c21c03f; ?>
<?php unset($__attributesOriginald4c6978101b1c254eb70511d3c21c03f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald4c6978101b1c254eb70511d3c21c03f)): ?>
<?php $component = $__componentOriginald4c6978101b1c254eb70511d3c21c03f; ?>
<?php unset($__componentOriginald4c6978101b1c254eb70511d3c21c03f); ?>
<?php endif; ?>
                <?php endif; ?>

                
                <?php if($canDelete): ?>
                  <?php if (isset($component)) { $__componentOriginald4c6978101b1c254eb70511d3c21c03f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald4c6978101b1c254eb70511d3c21c03f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.action-button','data' => ['variant' => 'delete','dataDeleteUrl' => route('admin.accounting.journal-entries.destroy', $entry->reference_number),'dataDeleteLabel' => 'القيد ' . $entry->reference_number]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('action-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'delete','data-delete-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.accounting.journal-entries.destroy', $entry->reference_number)),'data-delete-label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('القيد ' . $entry->reference_number)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald4c6978101b1c254eb70511d3c21c03f)): ?>
<?php $attributes = $__attributesOriginald4c6978101b1c254eb70511d3c21c03f; ?>
<?php unset($__attributesOriginald4c6978101b1c254eb70511d3c21c03f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald4c6978101b1c254eb70511d3c21c03f)): ?>
<?php $component = $__componentOriginald4c6978101b1c254eb70511d3c21c03f; ?>
<?php unset($__componentOriginald4c6978101b1c254eb70511d3c21c03f); ?>
<?php endif; ?>
                <?php endif; ?>
              <?php elseif($entry->status === 'confirmed'): ?>
                
                <?php if($canEdit): ?>
                  <form
                      method="POST"
                      action="<?php echo e(route('admin.accounting.journal-entries.reverse', $entry->reference_number)); ?>"
                      onsubmit="return confirm('هل أنت متأكد من عكس هذا القيد المعتمد؟ سيتم إنشاء قيد عكسي تلقائياً.')"
                      class="inline-flex"
                  >
                    <?php echo csrf_field(); ?>
                    <button
                        type="submit"
                        class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-purple-50 hover:text-purple-600 hover:border-purple-200 transition-all duration-300 shadow-sm flex items-center justify-center"
                        title="عكس القيد"
                    >
                      <i data-lucide="undo" class="w-4 h-4"></i>
                    </button>
                  </form>
                <?php endif; ?>

                <span
                    class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200 text-slate-300 cursor-not-allowed flex items-center justify-center"
                    title="القيد مقفل"
                >
                  <i data-lucide="lock" class="w-4 h-4"></i>
                </span>
              <?php else: ?>
                <span class="text-slate-300 text-sm font-bold">—</span>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <?php if (isset($component)) { $__componentOriginal074a021b9d42f490272b5eefda63257c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal074a021b9d42f490272b5eefda63257c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty-state','data' => ['icon' => 'receipt-text','title' => $hasActiveFilters ? 'لا توجد نتائج مطابقة' : 'لا توجد قيود محاسبية','message' => $hasActiveFilters ? 'جرّب تعديل كلمات البحث أو فلتر الحالة' : 'القيود المحاسبية ستظهر هنا تلقائياً عند إجراء الحجوزات وعمليات الإلغاء','colspan' => 10]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'receipt-text','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($hasActiveFilters ? 'لا توجد نتائج مطابقة' : 'لا توجد قيود محاسبية'),'message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($hasActiveFilters ? 'جرّب تعديل كلمات البحث أو فلتر الحالة' : 'القيود المحاسبية ستظهر هنا تلقائياً عند إجراء الحجوزات وعمليات الإلغاء'),'colspan' => 10]); ?>
          <?php if(!$hasActiveFilters && $canCreate): ?>
            <button
                type="button"
                data-create-modal="journal-entry-form-modal"
                class="inline-flex items-center gap-2 bg-teal-500 hover:bg-teal-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
            >
              <i data-lucide="plus" class="w-4 h-4"></i>
              إنشاء قيد جديد
            </button>
          <?php endif; ?>
          <?php if($hasActiveFilters): ?>
            <a
                href="<?php echo e($baseUrl); ?>"
                class="mt-3 text-sky-600 hover:text-sky-700 inline-flex items-center gap-1 font-bold text-sm"
            >
              <i data-lucide="refresh-cw" class="w-4 h-4"></i>
              إعادة تعيين الفلاتر
            </a>
          <?php endif; ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal074a021b9d42f490272b5eefda63257c)): ?>
<?php $attributes = $__attributesOriginal074a021b9d42f490272b5eefda63257c; ?>
<?php unset($__attributesOriginal074a021b9d42f490272b5eefda63257c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal074a021b9d42f490272b5eefda63257c)): ?>
<?php $component = $__componentOriginal074a021b9d42f490272b5eefda63257c; ?>
<?php unset($__componentOriginal074a021b9d42f490272b5eefda63257c); ?>
<?php endif; ?>
      <?php endif; ?>
     <?php $__env->endSlot(); ?>

     <?php $__env->slot('footer', null, []); ?> 
      <?php if (isset($component)) { $__componentOriginal41032d87daf360242eb88dbda6c75ed1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal41032d87daf360242eb88dbda6c75ed1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.pagination','data' => ['paginator' => $journalEntries]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('pagination'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($journalEntries)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal41032d87daf360242eb88dbda6c75ed1)): ?>
<?php $attributes = $__attributesOriginal41032d87daf360242eb88dbda6c75ed1; ?>
<?php unset($__attributesOriginal41032d87daf360242eb88dbda6c75ed1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal41032d87daf360242eb88dbda6c75ed1)): ?>
<?php $component = $__componentOriginal41032d87daf360242eb88dbda6c75ed1; ?>
<?php unset($__componentOriginal41032d87daf360242eb88dbda6c75ed1); ?>
<?php endif; ?>
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
<?php if (isset($component)) { $__componentOriginaldf85e6552b95df616d59caebcbf2473d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldf85e6552b95df616d59caebcbf2473d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form-modal','data' => ['id' => 'journal-entry-form-modal','title' => 'إنشاء قيد محاسبي جديد','variant' => 'create','action' => route('admin.accounting.journal-entries.store'),'method' => 'POST','successMessage' => 'تم حفظ القيد المحاسبي بنجاح','maxWidth' => 'max-w-4xl']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form-modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'journal-entry-form-modal','title' => 'إنشاء قيد محاسبي جديد','variant' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('create'),'action' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.accounting.journal-entries.store')),'method' => 'POST','successMessage' => 'تم حفظ القيد المحاسبي بنجاح','maxWidth' => 'max-w-4xl']); ?>

  
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <?php if (isset($component)) { $__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form-field','data' => ['name' => 'date','label' => 'التاريخ','icon' => 'calendar','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'date','label' => 'التاريخ','icon' => 'calendar','required' => true]); ?>
      <input
          type="date"
          name="date"
          id="je-date"
          required
          value="<?php echo e(date('Y-m-d')); ?>"
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
      >
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d)): ?>
<?php $attributes = $__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d; ?>
<?php unset($__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d)): ?>
<?php $component = $__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d; ?>
<?php unset($__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form-field','data' => ['name' => 'description','label' => 'الوصف','icon' => 'file-text','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'description','label' => 'الوصف','icon' => 'file-text','required' => true]); ?>
      <input
          type="text"
          name="description"
          id="je-description"
          required
          placeholder="وصف القيد المحاسبي..."
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-3 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
      >
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d)): ?>
<?php $attributes = $__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d; ?>
<?php unset($__attributesOriginalf4c8ecf26ef77d4de25edf56eae3a34d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d)): ?>
<?php $component = $__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d; ?>
<?php unset($__componentOriginalf4c8ecf26ef77d4de25edf56eae3a34d); ?>
<?php endif; ?>
  </div>

  
  <div class="space-y-3">
    <div class="flex items-center justify-between">
      <h4 class="font-bold text-xs text-[#115128] border-b border-[#eeeef0] pb-2 flex items-center gap-1.5">
        <i data-lucide="list" class="w-3.5 h-3.5"></i>
        أسطر القيد
      </h4>
      <button
          type="button"
          id="je-add-line-btn"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold text-teal-700 bg-teal-50 border border-teal-200 rounded-lg hover:bg-teal-100 transition-all"
      >
        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
        إضافة سطر
      </button>
    </div>

    
    <div class="border border-[#e2e2e5] rounded-xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-right" id="je-lines-table">
          <thead>
            <tr class="bg-[#f3f3f6]">
              <th class="px-3 py-2.5 text-[10px] font-bold text-slate-500 uppercase w-10 text-center">#</th>
              <th class="px-3 py-2.5 text-[10px] font-bold text-slate-500 uppercase min-w-[200px]">الحساب</th>
              <th class="px-3 py-2.5 text-[10px] font-bold text-slate-500 uppercase w-32 text-center">مدين</th>
              <th class="px-3 py-2.5 text-[10px] font-bold text-slate-500 uppercase w-32 text-center">دائن</th>
              <th class="px-3 py-2.5 text-[10px] font-bold text-slate-500 uppercase min-w-[150px]">الوصف</th>
              <th class="px-3 py-2.5 text-[10px] font-bold text-slate-500 uppercase w-10 text-center"></th>
            </tr>
          </thead>
          <tbody id="je-lines-body">
            
          </tbody>
        </table>
      </div>
    </div>

    
    <div class="flex items-center justify-between bg-[#f9f9fc] border border-[#eeeef0] rounded-xl p-3">
      <div class="flex items-center gap-4">
        <div class="text-center">
          <div class="text-[9px] font-bold text-slate-400 mb-0.5">إجمالي المدين</div>
          <div class="font-mono font-bold text-emerald-700 text-sm" id="je-total-debit" dir="ltr">0.00</div>
        </div>
        <div class="text-center">
          <div class="text-[9px] font-bold text-slate-400 mb-0.5">إجمالي الدائن</div>
          <div class="font-mono font-bold text-blue-700 text-sm" id="je-total-credit" dir="ltr">0.00</div>
        </div>
      </div>
      <div class="flex items-center gap-2" id="je-balance-indicator">
        <div class="text-center">
          <div class="text-[9px] font-bold text-slate-400 mb-0.5">الفرق</div>
          <div class="font-mono font-bold text-sm" id="je-difference" dir="ltr">0.00</div>
        </div>
        <span id="je-balance-badge" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">
          <i data-lucide="check-circle" class="w-3 h-3"></i>
          متوازن
        </span>
      </div>
    </div>
  </div>

  
  <div class="bg-sky-50/70 border border-sky-200 rounded-xl p-3">
    <div class="flex items-start gap-2.5">
      <i data-lucide="info" class="w-4 h-4 text-sky-500 flex-shrink-0 mt-0.5"></i>
      <span class="text-[11px] text-sky-600">
        يجب أن يكون إجمالي المدين مساوياً لإجمالي الدائن لكي يكون القيد متوازناً. القيد غير المتوازن لن يُقبل.
      </span>
    </div>
  </div>

  
  <template id="je-line-template">
    <tr class="je-line-row border-b border-[#eeeef0] last:border-b-0 hover:bg-slate-50/50 transition-colors">
      <td class="px-3 py-2 text-center">
        <span class="je-line-number text-[10px] font-bold text-slate-400">1</span>
      </td>
      <td class="px-3 py-2">
        <select
            name="lines[__IDX__][account_code]"
            class="je-line-account w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-lg px-2 py-2 text-[12px] font-bold outline-none focus:bg-white focus:ring-2 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
            required
        >
          <option value="">اختر الحساب...</option>
          <?php $__currentLoopData = $accounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $account): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($account->account_code); ?>"><?php echo e(str_repeat('── ', $account->level - 1)); ?><?php echo e($account->account_code); ?> - <?php echo e($account->account_name); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </td>
      <td class="px-3 py-2 text-center">
        <input
            type="number"
            name="lines[__IDX__][debit]"
            class="je-line-debit w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-lg px-2 py-2 text-[12px] font-bold text-center outline-none focus:bg-white focus:ring-2 focus:ring-teal-500/10 focus:border-[#115128] transition-all"
            step="0.01"
            min="0"
            placeholder="0.00"
            dir="ltr"
        >
      </td>
      <td class="px-3 py-2 text-center">
        <input
            type="number"
            name="lines[__IDX__][credit]"
            class="je-line-credit w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-lg px-2 py-2 text-[12px] font-bold text-center outline-none focus:bg-white focus:ring-2 focus:ring-teal-500/10 focus:border-[#115128] transition-all"
            step="0.01"
            min="0"
            placeholder="0.00"
            dir="ltr"
        >
      </td>
      <td class="px-3 py-2">
        <input
            type="text"
            name="lines[__IDX__][description]"
            class="je-line-desc w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-lg px-2 py-2 text-[12px] font-bold outline-none focus:bg-white focus:ring-2 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
            placeholder="وصف السطر..."
        >
      </td>
      <td class="px-3 py-2 text-center">
        <button
            type="button"
            class="je-remove-line-btn w-7 h-7 rounded-lg bg-rose-50 border border-rose-200 text-rose-500 hover:bg-rose-100 hover:text-rose-600 transition-all flex items-center justify-center"
            title="حذف السطر"
        >
          <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
        </button>
      </td>
    </tr>
  </template>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldf85e6552b95df616d59caebcbf2473d)): ?>
<?php $attributes = $__attributesOriginaldf85e6552b95df616d59caebcbf2473d; ?>
<?php unset($__attributesOriginaldf85e6552b95df616d59caebcbf2473d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldf85e6552b95df616d59caebcbf2473d)): ?>
<?php $component = $__componentOriginaldf85e6552b95df616d59caebcbf2473d; ?>
<?php unset($__componentOriginaldf85e6552b95df616d59caebcbf2473d); ?>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function() {
  'use strict';

  var modal = document.getElementById('journal-entry-form-modal');
  if (!modal) return;

  var form = modal.querySelector('form');
  var linesBody = document.getElementById('je-lines-body');
  var template = document.getElementById('je-line-template');
  var addLineBtn = document.getElementById('je-add-line-btn');
  var totalDebitEl = document.getElementById('je-total-debit');
  var totalCreditEl = document.getElementById('je-total-credit');
  var differenceEl = document.getElementById('je-difference');
  var balanceBadge = document.getElementById('je-balance-badge');

  var lineCounter = 0;
  var isEditMode = false;

  // ===== Add a new empty line =====
  function addLine(data) {
    var idx = lineCounter++;
    var row = template.content.querySelector('tr').cloneNode(true);

    // Replace __IDX__ with actual index
    row.innerHTML = row.innerHTML.replace(/__IDX__/g, idx);

    // Update line number
    updateLineNumbers();

    // Fill data if provided (for edit mode)
    if (data) {
      var select = row.querySelector('.je-line-account');
      if (data.account_code && select) select.value = data.account_code;
      var debitInput = row.querySelector('.je-line-debit');
      if (data.debit !== undefined && data.debit !== '' && debitInput) debitInput.value = data.debit;
      var creditInput = row.querySelector('.je-line-credit');
      if (data.credit !== undefined && data.credit !== '' && creditInput) creditInput.value = data.credit;
      var descInput = row.querySelector('.je-line-desc');
      if (data.description && descInput) descInput.value = data.description;
    }

    linesBody.appendChild(row);
    bindLineEvents(row);
    updateBalance();

    if (window.lucide) window.lucide.createIcons();
    return row;
  }

  // ===== Remove a line =====
  function removeLine(btn) {
    var row = btn.closest('.je-line-row');
    if (row) {
      row.remove();
      updateLineNumbers();
      updateBalance();
    }
  }

  // ===== Update line numbers =====
  function updateLineNumbers() {
    var rows = linesBody.querySelectorAll('.je-line-row');
    rows.forEach(function(row, i) {
      var num = row.querySelector('.je-line-number');
      if (num) num.textContent = i + 1;
    });
  }

  // ===== Calculate and update balance =====
  function updateBalance() {
    var totalDebit = 0;
    var totalCredit = 0;
    var rows = linesBody.querySelectorAll('.je-line-row');

    rows.forEach(function(row) {
      var debit = parseFloat(row.querySelector('.je-line-debit').value) || 0;
      var credit = parseFloat(row.querySelector('.je-line-credit').value) || 0;
      totalDebit += debit;
      totalCredit += credit;
    });

    var diff = Math.abs(totalDebit - totalCredit);
    var balanced = diff < 0.01;

    totalDebitEl.textContent = totalDebit.toFixed(2);
    totalCreditEl.textContent = totalCredit.toFixed(2);
    differenceEl.textContent = diff.toFixed(2);

    if (balanced) {
      differenceEl.className = 'font-mono font-bold text-sm text-emerald-700';
      balanceBadge.className = 'inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200';
      balanceBadge.innerHTML = '<i data-lucide="check-circle" class="w-3 h-3"></i> متوازن';
    } else {
      differenceEl.className = 'font-mono font-bold text-sm text-rose-600';
      balanceBadge.className = 'inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-rose-50 text-rose-600 text-[10px] font-bold border border-rose-200';
      balanceBadge.innerHTML = '<i data-lucide="alert-triangle" class="w-3 h-3"></i> غير متوازن';
    }

    if (window.lucide) window.lucide.createIcons();
  }

  // ===== Bind events to a line row =====
  function bindLineEvents(row) {
    var debitInput = row.querySelector('.je-line-debit');
    var creditInput = row.querySelector('.je-line-credit');
    var removeBtn = row.querySelector('.je-remove-line-btn');

    if (debitInput) debitInput.addEventListener('input', updateBalance);
    if (creditInput) creditInput.addEventListener('input', updateBalance);
    if (removeBtn) removeBtn.addEventListener('click', function() { removeLine(removeBtn); });

    // Auto-clear opposite side when one is filled
    if (debitInput) {
      debitInput.addEventListener('input', function() {
        if (parseFloat(this.value) > 0 && creditInput) creditInput.value = '';
      });
    }
    if (creditInput) {
      creditInput.addEventListener('input', function() {
        if (parseFloat(this.value) > 0 && debitInput) debitInput.value = '';
      });
    }
  }

  // ===== Clear all lines =====
  function clearLines() {
    linesBody.innerHTML = '';
    lineCounter = 0;
  }

  // ===== Add line button =====
  if (addLineBtn) {
    addLineBtn.addEventListener('click', function() { addLine(); });
  }

  // ===== Modal open event: handle create/edit switching =====
  document.addEventListener('aracari:modal-open', function(e) {
    var target = e.target;
    if (target.id !== 'journal-entry-form-modal' || !target.closest) {
      // Check if the modal is an ancestor
      var m = target.closest ? target.closest('#journal-entry-form-modal') : null;
      if (!m) return;
      target = m;
    }
    if (target.id !== 'journal-entry-form-modal') return;

    var data = e.detail.data || {};
    var isEdit = !!(data.reference_number);
    isEditMode = isEdit;

    // Clear existing lines
    clearLines();

    if (isEdit) {
      // Switch to edit mode
      var updateUrl = "<?php echo e(route('admin.accounting.journal-entries.update', '__REF__')); ?>".replace('__REF__', data.reference_number);
      form.setAttribute('action', updateUrl);
      var existingMethod = form.querySelector('input[name="_method"]');
      if (existingMethod) {
        existingMethod.value = 'PUT';
      } else {
        var mInput = document.createElement('input');
        mInput.type = 'hidden'; mInput.name = '_method'; mInput.value = 'PUT';
        form.appendChild(mInput);
      }

      // Update banner to amber (edit)
      var banner = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (banner) banner.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-amber-900 to-amber-950';
      var titleEl = modal.querySelector('.aracari-modal-panel h2');
      if (titleEl) titleEl.innerHTML = '<i data-lucide="pencil" class="w-5 h-5"></i> تعديل القيد المحاسبي';
      var badge = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge) {
        badge.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-amber-500/20 text-amber-300 border-amber-500/30';
        badge.textContent = 'تحديث';
      }
      form.setAttribute('data-success-message', 'تم تحديث القيد المحاسبي بنجاح');

      // Populate lines from data
      if (Array.isArray(data.lines) && data.lines.length > 0) {
        data.lines.forEach(function(line) { addLine(line); });
      } else {
        addLine(); addLine();
      }
    } else {
      // Create mode — reset everything
      form.setAttribute('action', "<?php echo e(route('admin.accounting.journal-entries.store')); ?>");
      var allMethods = form.querySelectorAll('input[name="_method"]');
      allMethods.forEach(function(el) { el.remove(); });

      var banner2 = modal.querySelector('.aracari-modal-panel .relative.h-24');
      if (banner2) banner2.className = 'relative h-24 text-white p-5 flex flex-col justify-end bg-gradient-to-r from-teal-900 to-teal-950';
      var titleEl2 = modal.querySelector('.aracari-modal-panel h2');
      if (titleEl2) titleEl2.innerHTML = '<i data-lucide="plus-circle" class="w-5 h-5"></i> إنشاء قيد محاسبي جديد';
      var badge2 = modal.querySelector('.aracari-modal-panel .relative.z-10 span');
      if (badge2) {
        badge2.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full border bg-teal-500/20 text-teal-300 border-teal-500/30';
        badge2.textContent = 'جديد';
      }
      form.setAttribute('data-success-message', 'تم حفظ القيد المحاسبي بنجاح');

      // Set default date to today
      var dateInput = modal.querySelector('#je-date');
      if (dateInput && !dateInput.value) dateInput.value = '<?php echo e(date("Y-m-d")); ?>';

      // Add 2 empty lines
      addLine(); addLine();
    }

    if (window.lucide) window.lucide.createIcons();
  });

  // ===== Clear line errors and state on modal close =====
  var closeObserver = new MutationObserver(function(mutations) {
    mutations.forEach(function(m) {
      if (m.type === 'attributes' && m.attributeName === 'class') {
        var el = m.target;
        if (el.id === 'journal-entry-form-modal' && el.classList.contains('hidden')) {
          isEditMode = false;
          clearLines();
        }
      }
    });
  });
  closeObserver.observe(modal, { attributes: true });

})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/admin/journal-entries/index.blade.php ENDPATH**/ ?>