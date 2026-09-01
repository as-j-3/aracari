<?php $__env->startSection('title', 'أركاري | الإعدادات'); ?>
<?php $__env->startSection('header_title', 'إعدادات المزود'); ?>

<?php $__env->startSection('content'); ?>

<?php
    $currentUser = auth()->user();
    $canEditProfile = $currentUser && $currentUser->hasPermission('provider:settings:edit');
    $logoUrl = $provider->brand_photo_url;
?>

<div class="space-y-6 font-cairo text-right" dir="rtl">

  
  <?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['icon' => 'settings','title' => 'الإعدادات','description' => 'إدارة بيانات الشركة والشعار والحسابات البنكية','descriptionIcon' => 'building-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'settings','title' => 'الإعدادات','description' => 'إدارة بيانات الشركة والشعار والحسابات البنكية','descriptionIcon' => 'building-2']); ?>
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

  <div id="aracari-refreshable" class="space-y-6">

  
  
  
  <div class="bg-white rounded-2xl border border-[#e2e2e5] shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-[#e2e2e5] bg-gradient-to-r from-slate-50 to-white flex items-center gap-3">
      <div class="w-9 h-9 rounded-lg bg-teal-50 border border-teal-100 flex items-center justify-center">
        <i data-lucide="building-2" class="w-4.5 h-4.5 text-teal-600"></i>
      </div>
      <div>
        <h3 class="font-bold text-sm text-slate-800">بيانات الشركة</h3>
        <p class="text-[11px] text-slate-400">المعلومات الأساسية لمزود الخدمة</p>
      </div>
    </div>

    <form id="profile-form" class="p-6 space-y-5">
      <?php echo csrf_field(); ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        
        <div class="space-y-1.5">
          <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
            <i data-lucide="building" class="w-3.5 h-3.5"></i>
            اسم الشركة <span class="text-rose-500">*</span>
          </label>
          <input type="text" name="name" value="<?php echo e(old('name', $provider->name)); ?>" required
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-4 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
            placeholder="اسم الشركة أو المؤسسة">
        </div>

        
        <div class="space-y-1.5">
          <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
            <i data-lucide="file-badge" class="w-3.5 h-3.5"></i>
            رقم السجل التجاري
          </label>
          <input type="text" name="cr_number" value="<?php echo e(old('cr_number', $provider->cr_number)); ?>"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-4 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
            placeholder="رقم السجل التجاري">
        </div>

        
        <div class="space-y-1.5">
          <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
            <i data-lucide="phone" class="w-3.5 h-3.5"></i>
            هاتف التواصل
          </label>
          <input type="text" name="contact_phone" value="<?php echo e(old('contact_phone', $provider->contact_phone)); ?>" dir="ltr"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-4 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left"
            placeholder="+967 7XX XXX XXX">
        </div>

        
        <div class="space-y-1.5">
          <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
            <i data-lucide="mail" class="w-3.5 h-3.5"></i>
            البريد الإلكتروني
          </label>
          <input type="email" name="contact_email" value="<?php echo e(old('contact_email', $provider->contact_email)); ?>" dir="ltr"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-4 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left"
            placeholder="info@company.com">
        </div>

        
        <div class="space-y-1.5">
          <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
            <i data-lucide="globe" class="w-3.5 h-3.5"></i>
            الموقع الإلكتروني
          </label>
          <input type="url" name="website" value="<?php echo e(old('website', $provider->website)); ?>" dir="ltr"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-4 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left"
            placeholder="https://www.company.com">
        </div>

        
        <div class="space-y-1.5">
          <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
            <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
            بلد المنشأ
          </label>
          <input type="text" name="born_country" value="<?php echo e(old('born_country', $provider->born_country)); ?>"
            class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-4 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
            placeholder="البلد">
        </div>

      </div>

      
      <div class="space-y-1.5">
        <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
          <i data-lucide="align-right" class="w-3.5 h-3.5"></i>
          وصف الشركة
        </label>
        <textarea name="description" rows="3"
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-4 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right resize-none"
          placeholder="وصف مختصر عن الشركة وخدماتها..."><?php echo e(old('description', $provider->description)); ?></textarea>
      </div>

      
      <div class="flex justify-end">
        <button type="submit"
          class="px-6 py-2.5 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-teal-500/20 hover:shadow-teal-500/30 hover:scale-[1.02] transition-all duration-300 flex items-center gap-2"
        >
          <i data-lucide="save" class="w-4 h-4"></i>
          حفظ بيانات الشركة
        </button>
      </div>
    </form>
  </div>

  
  
  
  <div class="bg-white rounded-2xl border border-[#e2e2e5] shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-[#e2e2e5] bg-gradient-to-r from-slate-50 to-white flex items-center gap-3">
      <div class="w-9 h-9 rounded-lg bg-teal-50 border border-teal-100 flex items-center justify-center">
        <i data-lucide="image" class="w-4.5 h-4.5 text-teal-600"></i>
      </div>
      <div>
        <h3 class="font-bold text-sm text-slate-800">شعار الشركة</h3>
        <p class="text-[11px] text-slate-400">يظهر في صفحة الرحلات والفروع</p>
      </div>
    </div>

    <div class="p-6">
      <div class="flex flex-col sm:flex-row items-center gap-6">

        
        <div id="logo-preview-area" class="relative group">
          <?php if($logoUrl): ?>
            <img id="logo-preview-img" src="<?php echo e($logoUrl); ?>" alt="شعار الشركة"
              class="w-32 h-32 rounded-2xl object-cover border-2 border-[#e2e2e5] shadow-sm">
            <button type="button" id="remove-logo-btn"
              class="absolute -top-2 -left-2 w-7 h-7 rounded-full bg-rose-500 text-white flex items-center justify-center shadow-lg opacity-0 group-hover:opacity-100 transition-opacity hover:bg-rose-600"
              title="حذف الشعار">
              <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
          <?php else: ?>
            <div id="logo-placeholder" class="w-32 h-32 rounded-2xl bg-[#f3f3f6] border-2 border-dashed border-[#e2e2e5] flex flex-col items-center justify-center gap-2">
              <i data-lucide="image-plus" class="w-8 h-8 text-slate-300"></i>
              <span class="text-[10px] text-slate-400 font-bold">لا يوجد شعار</span>
            </div>
          <?php endif; ?>
        </div>

        
        <div class="flex-1 space-y-3">
          <div class="border-2 border-dashed border-[#e2e2e5] rounded-xl p-5 text-center hover:border-teal-300 hover:bg-teal-50/30 transition-all cursor-pointer"
               id="logo-upload-area">
            <i data-lucide="upload-cloud" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
            <p class="text-xs font-bold text-slate-600">اضغط أو اسحب صورة هنا</p>
            <p class="text-[10px] text-slate-400 mt-1">JPG, PNG, WebP — حد أقصى 2 ميجا</p>
            <input type="file" id="logo-input" name="logo" accept="image/jpeg,image/png,image/webp" class="hidden">
          </div>
        </div>

      </div>
    </div>
  </div>

  
  
  
  <div class="bg-white rounded-2xl border border-[#e2e2e5] shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-[#e2e2e5] bg-gradient-to-r from-slate-50 to-white flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg bg-teal-50 border border-teal-100 flex items-center justify-center">
          <i data-lucide="landmark" class="w-4.5 h-4.5 text-teal-600"></i>
        </div>
        <div>
          <h3 class="font-bold text-sm text-slate-800">الحسابات البنكية</h3>
          <p class="text-[11px] text-slate-400">حسابات استلام الإيرادات</p>
        </div>
      </div>
      <?php if($canEditProfile): ?>
      <button type="button" id="add-bank-btn"
        class="px-4 py-2 bg-teal-50 text-teal-700 border border-teal-200 hover:bg-teal-500 hover:text-white hover:border-teal-500 transition-all rounded-xl text-[11px] font-bold flex items-center gap-1.5"
      >
        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
        إضافة حساب
      </button>
      <?php endif; ?>
    </div>

    <div class="p-6">
      <?php $__empty_1 = true; $__currentLoopData = $provider->receiveRevenueMethods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $method): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="bank-account-card border border-[#e2e2e5] rounded-xl p-4 mb-3 hover:bg-slate-50/50 transition-colors" data-method-id="<?php echo e($method->id); ?>">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-amber-50 border border-amber-100 flex items-center justify-center">
                <i data-lucide="credit-card" class="w-4.5 h-4.5 text-amber-600"></i>
              </div>
              <div>
                <div class="font-bold text-sm text-slate-800"><?php echo e($method->account_name); ?></div>
                <div class="text-[11px] text-slate-400 font-mono" dir="ltr"><?php echo e($method->account_number_last4); ?></div>
              </div>
            </div>
            <?php if($canEditProfile): ?>
            <div class="flex items-center gap-1.5">
              <button type="button" class="edit-bank-btn w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-500 hover:text-white border border-blue-200 hover:border-blue-500 transition-all flex items-center justify-center"
                data-id="<?php echo e($method->id); ?>"
                data-name="<?php echo e($method->account_name); ?>"
                data-number="<?php echo e($method->account_number); ?>"
                data-pocket="<?php echo e($method->digital_pocket_id); ?>"
                title="تعديل">
                <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
              </button>
              <button type="button" class="delete-bank-btn w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white border border-rose-200 hover:border-rose-500 transition-all flex items-center justify-center"
                data-delete-url="<?php echo e(route('provider.settings.banks.destroy', $method->id)); ?>"
                data-delete-label="<?php echo e($method->account_name); ?>"
                title="حذف">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
              </button>
            </div>
            <?php endif; ?>
          </div>
          <?php if($method->digitalPocket): ?>
            <div class="mt-2 mr-13 flex items-center gap-1.5">
              <i data-lucide="wallet" class="w-3 h-3 text-slate-400"></i>
              <span class="text-[10px] text-slate-400"><?php echo e($method->digitalPocket->pocket_name); ?> (<?php echo e($method->digitalPocket->currency?->symbol ?? ''); ?>)</span>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="text-center py-8">
          <i data-lucide="landmark" class="w-10 h-10 text-slate-200 mx-auto mb-2"></i>
          <p class="text-xs text-slate-400 font-bold">لا توجد حسابات بنكية مسجلة</p>
          <p class="text-[10px] text-slate-300 mt-1">أضف حسابك البنكي لاستلام الإيرادات</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  </div>
</div>


<?php if (isset($component)) { $__componentOriginaldf85e6552b95df616d59caebcbf2473d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldf85e6552b95df616d59caebcbf2473d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form-modal','data' => ['id' => 'bank-form-modal','title' => 'إضافة حساب بنكي','variant' => 'create','action' => route('provider.settings.banks.store'),'method' => 'POST','successMessage' => 'تم إضافة الحساب بنجاح']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form-modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'bank-form-modal','title' => 'إضافة حساب بنكي','variant' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('create'),'action' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('provider.settings.banks.store')),'method' => 'POST','successMessage' => 'تم إضافة الحساب بنجاح']); ?>
  <div class="space-y-4">
    
    <div class="space-y-1.5">
      <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
        <i data-lucide="user" class="w-3.5 h-3.5"></i>
        اسم صاحب الحساب <span class="text-rose-500">*</span>
      </label>
      <input type="text" name="account_name" id="bank_account_name" required
        class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-4 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right"
        placeholder="محمد أحمد">
    </div>

    
    <div class="space-y-1.5">
      <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
        <i data-lucide="credit-card" class="w-3.5 h-3.5"></i>
        رقم الحساب / IBAN <span class="text-rose-500">*</span>
      </label>
      <input type="text" name="account_number" id="bank_account_number" required dir="ltr"
        class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl px-4 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-left font-mono"
        placeholder="SA0380000000608010167519">
    </div>

    
    <div class="space-y-1.5">
      <label class="block text-[11px] font-bold text-slate-600 flex items-center gap-1">
        <i data-lucide="wallet" class="w-3.5 h-3.5"></i>
        المحفظة المرتبطة
      </label>
      <div class="relative group">
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#115128] transition-colors">
          <i data-lucide="wallet" class="w-4 h-4"></i>
        </div>
        <select name="digital_pocket_id" id="bank_digital_pocket_id"
          class="w-full bg-[#f3f3f6] border border-[#e2e2e5] rounded-xl pr-10 pl-9 py-2.5 text-[13px] font-bold outline-none focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:border-[#115128] transition-all text-right appearance-none cursor-pointer"
        >
          <option value="">-- بدون --</option>
          <?php $__currentLoopData = $digitalPockets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pocket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($pocket->id); ?>"><?php echo e($pocket->pocket_name); ?> (<?php echo e($pocket->currency?->symbol ?? ''); ?>)</option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
          <i data-lucide="chevron-down" class="w-4 h-4"></i>
        </div>
      </div>
    </div>
  </div>
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
// ===== حفظ بيانات الشركة =====
document.getElementById('profile-form').addEventListener('submit', function(e) {
  e.preventDefault();
  var form = this;
  var btn = form.querySelector('button[type=submit]');
  var originalHtml = btn.innerHTML;
  btn.innerHTML = '<svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> جارٍ الحفظ...';
  btn.disabled = true;

  fetch('<?php echo e(route('provider.settings.profile.update')); ?>', {
    method: 'POST',
    headers: {'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json', 'Content-Type': 'application/json'},
    body: JSON.stringify(Object.fromEntries(new FormData(form)))
  })
  .then(function(r) { return r.json(); })
  .then(function(res) {
    btn.innerHTML = originalHtml;
    btn.disabled = false;
    if (res.success) {
      showToast(res.message, 'success');
    } else {
      showToast(res.message || 'حدث خطأ', 'error');
    }
  })
  .catch(function() { btn.innerHTML = originalHtml; btn.disabled = false; showToast('خطأ في الاتصال', 'error'); });
});

// ===== رفع الشعار =====
var logoUploadArea = document.getElementById('logo-upload-area');
var logoInput = document.getElementById('logo-input');
var logoPreviewArea = document.getElementById('logo-preview-area');

logoUploadArea.addEventListener('click', function() { logoInput.click(); });
logoUploadArea.addEventListener('dragover', function(e) { e.preventDefault(); this.classList.add('border-teal-400', 'bg-teal-50/50'); });
logoUploadArea.addEventListener('dragleave', function(e) { e.preventDefault(); this.classList.remove('border-teal-400', 'bg-teal-50/50'); });
logoUploadArea.addEventListener('drop', function(e) {
  e.preventDefault(); this.classList.remove('border-teal-400', 'bg-teal-50/50');
  if (e.dataTransfer.files.length > 0) { handleLogoUpload(e.dataTransfer.files[0]); }
});
logoInput.addEventListener('change', function() { if (this.files.length > 0) handleLogoUpload(this.files[0]); });

function handleLogoUpload(file) {
  if (!file.type.startsWith('image/')) { showToast('الملف يجب أن يكون صورة', 'error'); return; }
  if (file.size > 2 * 1024 * 1024) { showToast('حجم الصورة يجب ألا يتجاوز 2 ميجا', 'error'); return; }

  var fd = new FormData();
  fd.append('logo', file);
  fd.append('_token', document.querySelector('meta[name=csrf-token]').content);

  fetch('<?php echo e(route('provider.settings.logo.upload')); ?>', {
    method: 'POST',
    headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'},
    body: fd
  })
  .then(function(r) { return r.json(); })
  .then(function(res) {
    if (res.success) {
      showToast(res.message, 'success');
      setTimeout(function() { location.reload(); }, 800);
    } else { showToast(res.message || 'فشل رفع الشعار', 'error'); }
  })
  .catch(function() { showToast('خطأ في الاتصال', 'error'); });
}

// ===== حذف الشعار =====
var removeBtn = document.getElementById('remove-logo-btn');
if (removeBtn) {
  removeBtn.addEventListener('click', function() {
    if (!confirm('هل أنت متأكد من حذف الشعار؟')) return;
    fetch('<?php echo e(route('provider.settings.logo.remove')); ?>', {
      method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json'}
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
      if (res.success) { showToast(res.message, 'success'); setTimeout(function() { location.reload(); }, 800); }
      else { showToast(res.message, 'error'); }
    });
  });
}

// ===== إضافة حساب بنكي =====
document.getElementById('add-bank-btn').addEventListener('click', function() {
  document.getElementById('bank_account_name').value = '';
  document.getElementById('bank_account_number').value = '';
  document.getElementById('bank_digital_pocket_id').value = '';
  Aracari.openModal('bank-form-modal', {});
});

// ===== تعديل حساب بنكي =====
document.querySelectorAll('.edit-bank-btn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    document.getElementById('bank_account_name').value = this.dataset.name;
    document.getElementById('bank_account_number').value = this.dataset.number;
    document.getElementById('bank_digital_pocket_id').value = this.dataset.pocket || '';
    var methodId = this.dataset.id;
    Aracari.openModal('bank-form-modal', {uuid: methodId, isEdit: true, editUrl: '<?php echo e(route('provider.settings.banks.update', '__ID__')); ?>'.replace('__ID__', methodId)});
  });
});

// ===== حذف حساب بنكي =====
document.querySelectorAll('.delete-bank-btn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var label = this.dataset.deleteLabel;
    if (!confirm('هل أنت متأكد من حذف الحساب "' + label + '"؟')) return;
    var url = this.dataset.deleteUrl;
    fetch(url, {
      method: 'DELETE', headers: {'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json'}
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
      if (res.success) { showToast(res.message, 'success'); setTimeout(function() { location.reload(); }, 800); }
      else { showToast(res.message, 'error'); }
    });
  });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.provider', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/provider/settings/index.blade.php ENDPATH**/ ?>