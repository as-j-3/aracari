<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>انتهت صلاحية الجلسة</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Cairo', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: #f9f5ec;
        }

        .card {
            max-width: 440px;
            width: 100%;
            background: #fff;
            border-radius: 28px;
            border: 1px solid rgba(31, 68, 46, 0.06);
            box-shadow: 0 24px 64px rgba(31, 68, 46, 0.08);
            padding: 40px 32px;
            text-align: center;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .icon-box {
            width: 72px;
            height: 72px;
            margin: 0 auto 20px;
            border-radius: 22px;
            background: rgba(245, 158, 11, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            animation: float 3s ease-in-out infinite;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            border-radius: 9999px;
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.2);
            color: #b8921f;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            margin-bottom: 16px;
        }

        .title {
            font-size: 22px;
            font-weight: 800;
            color: #1f442e;
            margin-bottom: 10px;
        }

        .desc {
            color: #64748b;
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 24px;
        }

        .desc strong {
            color: #1f442e;
            font-weight: 700;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 14px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 13px;
            font-family: 'Cairo', sans-serif;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 10px;
        }

        .btn-gold {
            background: linear-gradient(135deg, #e7bd54, #d49920);
            color: #0c1d14;
            box-shadow: 0 8px 24px rgba(231, 189, 84, 0.25);
        }
        .btn-gold:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 32px rgba(231, 189, 84, 0.35);
        }

        .btn-outline {
            background: #fff;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .btn-outline:hover {
            background: #f9f5ec;
            border-color: #cbd5e1;
        }

        .tips {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid rgba(31, 68, 46, 0.05);
        }

        .tips-label {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .tips-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            text-align: right;
        }

        .tip-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #94a3b8;
            font-size: 11px;
        }

        .countdown {
            margin-top: 16px;
            color: #94a3b8;
            font-size: 12px;
        }
        .countdown strong {
            color: #1f442e;
            font-weight: 700;
        }
        .countdown a {
            color: #1f442e;
            font-weight: 600;
            cursor: pointer;
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .card { padding: 28px 20px; }
            .tips-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-box">
            <i data-lucide="timer-off" style="width:32px;height:32px;color:#f59e0b;"></i>
        </div>

        <div class="badge">
            <i data-lucide="alert-circle" style="width:12px;height:12px;"></i>
            خطأ 419
        </div>

        <h1 class="title">انتهت صلاحية الجلسة</h1>

        <p class="desc">
            عذراً، الجلسة الحالية انتهت صلاحيتها لأسباب أمنية.<br>
            يمكنك <strong>تسجيل الدخول مرة أخرى</strong> بكل سهولة.
        </p>

        <a href="<?php echo e(route('login')); ?>" class="btn btn-gold">
            <i data-lucide="log-in" style="width:16px;height:16px;"></i>
            تسجيل الدخول مجدداً
        </a>
        <a href="<?php echo e(route('home')); ?>" class="btn btn-outline">
            <i data-lucide="home" style="width:16px;height:16px;"></i>
            الصفحة الرئيسية
        </a>

        <div class="tips">
            <p class="tips-label">نصائح لتجنب هذه المشكلة:</p>
            <div class="tips-grid">
                <div class="tip-item">
                    <i data-lucide="check-circle-2" style="width:13px;height:13px;color:#22c55e;flex-shrink:0;"></i>
                    تجنب ترك الصفحة مفتوحة طويلاً
                </div>
                <div class="tip-item">
                    <i data-lucide="check-circle-2" style="width:13px;height:13px;color:#22c55e;flex-shrink:0;"></i>
                    استخدم "تذكرني" عند الدخول
                </div>
            </div>
        </div>

        <div class="countdown">
            إعادة توجيه خلال <strong id="countdown">30</strong> ثانية
            &mdash; <a onclick="goNow()">تحديث الآن</a>
        </div>
    </div>

    <script>
        lucide.createIcons();
        let s = 30;
        const el = document.getElementById('countdown');
        const t = setInterval(() => {
            s--;
            el.textContent = s;
            if (s <= 0) { clearInterval(t); goNow(); }
        }, 1000);
        function goNow() { window.location.href = '<?php echo e(route("login")); ?>'; }
    </script>
</body>
</html><?php /**PATH C:\Users\Akram Khalid\Desktop\aracari\resources\views/errors/419.blade.php ENDPATH**/ ?>