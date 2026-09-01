<?php $__env->startSection('title', auth()->user()->phone_verified_at ? 'تغيير رقم الهاتف' : 'تفعيل رقم الهاتف'); ?>

<?php $__env->startSection('content'); ?>

<div class="min-h-screen pt-28 pb-16 px-4 sm:px-6">
<div class="max-w-md mx-auto">

  
  <div class="flex items-center gap-3 mb-8 animate-fade-in-up">
    <a href="<?php echo e(route('dashboard')); ?>" class="w-10 h-10 rounded-xl bg-white border border-[#1f442e]/5 shadow-sm flex items-center justify-center hover:shadow-md transition-all duration-300">
      <i data-lucide="arrow-right" class="w-5 h-5 text-[#1f442e]/60"></i>
    </a>
    <div>
      <h1 class="text-xl font-bold text-[#1f442e]"><?php echo $__env->yieldContent('title'); ?></h1>
      <p class="text-xs text-slate-400 mt-0.5">
        <?php if(auth()->user()->phone_verified_at): ?>
          رقمك الحالي: <span dir="ltr" class="font-bold text-[#1f442e]"><?php echo e(auth()->user()->phone_number); ?></span>
        <?php else: ?>
          أكد رقم هاتفك لتتمكن من الحجز
        <?php endif; ?>
      </p>
    </div>
  </div>

  
  <?php if(session('success')): ?>
  <div class="mb-6 bg-emerald-50 border border-emerald-100 text-emerald-700 px-5 py-3 rounded-2xl text-sm font-medium flex items-center gap-3 animate-fade-in-up">
    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500 shrink-0"></i><?php echo e(session('success')); ?>

  </div>
  <?php endif; ?>
  <?php if(session('error')): ?>
  <div class="mb-6 bg-red-50 border border-red-100 text-red-700 px-5 py-3 rounded-2xl text-sm font-medium flex items-center gap-3 animate-fade-in-up">
    <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 shrink-0"></i><?php echo e(session('error')); ?>

  </div>
  <?php endif; ?>

  
  <?php if(!auth()->user()->phone_verified_at): ?>
  <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4 mb-6 animate-fade-in-up animate-delay-100">
    <div class="flex items-start gap-3">
      <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center shrink-0 mt-0.5">
        <i data-lucide="shield-alert" class="w-4 h-4 text-amber-600"></i>
      </div>
      <div>
        <p class="text-xs font-bold text-amber-800">لماذا تحتاج تفعيل هاتفك؟</p>
        <p class="text-[11px] text-amber-700 mt-1 leading-relaxed">رقم الهاتف المفعل ضروري لإتمام عمليات الحجز والدفع عبر المحفظة الإلكترونية.</p>
      </div>
    </div>
  </div>
  <?php else: ?>
  <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 mb-6 animate-fade-in-up animate-delay-100">
    <div class="flex items-start gap-3">
      <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0 mt-0.5">
        <i data-lucide="badge-check" class="w-4 h-4 text-emerald-600"></i>
      </div>
      <div>
        <p class="text-xs font-bold text-emerald-800">هاتفك مفعّل</p>
        <p class="text-[11px] text-emerald-700 mt-1 leading-relaxed">إذا أردت تغيير رقم هاتفك، أدخل الرقم الجديد وسيتم إلغاء تفعيل الرقم الحالي.</p>
      </div>
    </div>
  </div>
  <?php endif; ?>

  
  <div id="phoneFormCard" class="bg-white rounded-3xl border border-[#1f442e]/5 shadow-sm p-6 sm:p-8 animate-fade-in-up animate-delay-200">
    <form method="POST" action="<?php echo e(route('profile.phone.send-otp')); ?>" id="phoneForm">
      <?php echo csrf_field(); ?>

      <div class="text-center mb-6">
        <div class="w-16 h-16 rounded-2xl <?php echo e(auth()->user()->phone_verified_at ? 'bg-blue-500/10' : 'bg-[#1f442e]'); ?> flex items-center justify-center mx-auto mb-4">
          <i data-lucide="smartphone" class="w-8 h-8 <?php echo e(auth()->user()->phone_verified_at ? 'text-blue-600' : 'text-[#e7bd54]'); ?>"></i>
        </div>
        <h3 class="text-sm font-bold text-[#1f442e]"><?php if(auth()->user()->phone_verified_at): ?> أدخل الرقم الجديد <?php else: ?> أدخل رقم هاتفك <?php endif; ?></h3>
      </div>

      <div class="flex gap-3 mb-6">
        <select name="country_code" class="w-28 bg-[#f9f5ec]/50 border border-[#1f442e]/10 rounded-xl px-3 py-3 text-sm text-[#1f442e] focus:outline-none focus:ring-2 focus:ring-[#e7bd54]/50 transition-all" dir="ltr">
          <option value="967" <?php echo e(old('country_code', '967') == '967' ? 'selected' : ''); ?>>+967</option>
          <option value="966" <?php echo e(old('country_code') == '966' ? 'selected' : ''); ?>>+966</option>
          <option value="971" <?php echo e(old('country_code') == '971' ? 'selected' : ''); ?>>+971</option>
          <option value="965" <?php echo e(old('country_code') == '965' ? 'selected' : ''); ?>>+965</option>
          <option value="974" <?php echo e(old('country_code') == '974' ? 'selected' : ''); ?>>+974</option>
          <option value="20" <?php echo e(old('country_code') == '20' ? 'selected' : ''); ?>>+20</option>
          <option value="968" <?php echo e(old('country_code') == '968' ? 'selected' : ''); ?>>+968</option>
        </select>
        <input type="tel" name="phone" value="<?php echo e(old('phone')); ?>" required
          class="flex-1 bg-[#f9f5ec]/50 border border-[#1f442e]/10 rounded-xl px-4 py-3 text-sm text-[#1f442e] placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-[#e7bd54]/50 transition-all"
          placeholder="7XX XXX XXX" dir="ltr" maxlength="15">
      </div>

      <button type="submit" id="sendBtn" class="w-full btn-gold py-3.5 rounded-xl text-sm flex items-center justify-center gap-2">
        <i data-lucide="send" class="w-4 h-4"></i>
        إرسال رمز التحقق
      </button>
    </form>
  </div>

  
  <div id="otpSection" class="bg-white rounded-3xl border border-[#1f442e]/5 shadow-sm p-6 sm:p-8 mt-4 animate-fade-in-up hidden">
    <form method="POST" action="<?php echo e(route('profile.phone.verify-otp')); ?>" id="otpForm">
      <?php echo csrf_field(); ?>

      <div class="text-center mb-6">
        <div class="w-16 h-16 rounded-2xl bg-emerald-500/10 flex items-center justify-center mx-auto mb-4">
          <i data-lucide="key-round" class="w-8 h-8 text-emerald-600"></i>
        </div>
        <h3 class="text-sm font-bold text-[#1f442e]">أدخل رمز التحقق</h3>
        <p class="text-xs text-slate-400 mt-1">تم إرسال رمز مكون من 6 أرقام إلى هاتفك</p>
      </div>

      <div class="flex justify-center gap-3 mb-6" dir="ltr">
        <input type="hidden" name="code" id="otpCode">
        <?php for($i = 0; $i < 6; $i++): ?>
        <input type="text" maxlength="1" required
          class="otp-input w-11 h-14 bg-[#f9f5ec]/50 border border-[#1f442e]/10 rounded-xl text-center text-lg font-bold text-[#1f442e] focus:outline-none focus:ring-2 focus:ring-[#e7bd54]/50 transition-all"
          oninput="this.value = this.value.replace(/[^0-9]/g, ''); if(this.value && this.nextElementSibling && this.nextElementSibling.classList.contains('otp-input')) this.nextElementSibling.focus(); syncOtp();"
          onkeydown="if(event.key === 'Backspace' && !this.value && this.previousElementSibling && this.previousElementSibling.classList.contains('otp-input')) this.previousElementSibling.focus();">
        <?php endfor; ?>
      </div>

      <button type="submit" id="verifyBtn" class="w-full btn-gold py-3.5 rounded-xl text-sm flex items-center justify-center gap-2 mb-3">
        <i data-lucide="shield-check" class="w-4 h-4"></i>
        تأكيد الرمز
      </button>

      <button type="button" onclick="document.getElementById('otpSection').classList.add('hidden');" class="w-full text-xs text-slate-400 hover:text-[#1f442e] py-2 transition-colors">
        <i data-lucide="refresh-cw" class="w-3 h-3 inline-block ml-1"></i>
        إعادة إرسال الرمز (أعد ملء رقم الهاتف أولاً)
      </button>
    </form>
  </div>

</div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
lucide.createIcons();

document.getElementById('phoneForm').addEventListener('submit', function(e) {
  const btn = document.getElementById('sendBtn');
  btn.disabled = true;
  btn.innerHTML = '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> جاري الإرسال...';
});

<?php if(session('success')): ?>
document.getElementById('otpSection').classList.remove('hidden');
<?php endif; ?>

function syncOtp() {
  const inputs = document.querySelectorAll('.otp-input');
  let code = '';
  inputs.forEach(i => code += i.value);
  document.getElementById('otpCode').value = code;
}

document.getElementById('otpForm').addEventListener('submit', function(e) {
  syncOtp();
  const code = document.getElementById('otpCode').value;
  if (code.length !== 6) {
    e.preventDefault();
    alert('يرجى إدخال رمز التحقق كاملاً');
    return;
  }
  const btn = document.getElementById('verifyBtn');
  btn.disabled = true;
  btn.innerHTML = '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> جاري التحقق...';
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.web', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/user/phone-verify.blade.php ENDPATH**/ ?>