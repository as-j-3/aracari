<?php

namespace App\Http\Controllers\Web;

use App\Models\User;
use App\Constants\UserTypes as UserType;
use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use App\Services\Auth\VerificationService;
use App\Services\AuditService;
use App\Services\UserService;
use App\Constants\UserStatus;
use App\Services\Auth\UnifiedOtpService;
use App\Http\Requests\Web\Auth\VerifyOtpRequest;
use App\Http\Requests\Web\Auth\ResendOtpRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Constants\CountryCodes;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AuthController extends Controller
{
    protected AuthService $authService;
    protected UserService $userService;
    protected VerificationService $verificationService;
    protected AuditService $auditService;
    protected UnifiedOtpService $unifiedOtpService;

    public function __construct(
        AuthService $authService,
        UserService $userService,
        VerificationService $verificationService,
    ) {
        $this->authService = $authService;
        $this->userService = $userService;
        $this->verificationService = $verificationService;
    }

    public function showLoginForm(): View
    {
        $countries = CountryCodes::all();
        return view('auth.login', compact('countries'));
    }

    public function showRegistrationForm(): View
    {
        $countries = CountryCodes::all();
        return view('auth.register', compact('countries'));
    }

    /**
     * Handle login request (Web) - Simplified
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'identity' => 'required|string',
            'password' => 'required|string',
            'g-recaptcha-response' => ['required', new \App\Rules\Recaptcha],
        ], [
            'identity.required' => 'يرجى إدخال البريد الإلكتروني أو رقم الهاتف.',
            'password.required' => 'يرجى إدخال كلمة المرور.',
            'g-recaptcha-response.required' => 'يرجى التحقق من أنك لست روبوت.',
        ]);

        $key = 'login.' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);
            return back()->withErrors(['identity' => "تم تقييد محاولات الدخول. حاول بعد {$minutes} دقيقة."])
                ->onlyInput('identity');
        }

        $identity = $request->identity;
        $password = $request->password;

        $result = $this->authService->webLogin($identity, $password);

        if (isset($result['needs_otp']) && $result['needs_otp'] && isset($result['uuid'])) {
            RateLimiter::clear($key);  
            return redirect()->route('verify', ['uuid' => $result['uuid']])
                ->with('error', 'حسابك يحتاج إلى تفعيل. تم إرسال رمز التحقق.');
        }

        if (!$result['success']) {
            RateLimiter::hit($key, 600);
            return back()->withErrors(['identity' => $result['message']])->onlyInput('identity');
        }

        RateLimiter::clear($key);

        $user = $result['user'];

        Auth::login($user);
        $request->session()->regenerate();
        
        return redirect()->route('home');
    }

    public function register(Request $request): RedirectResponse
    {
        if ($request->has('password') || $request->has('password_confirmation')) {
            return back()->with('error', 'لا يمكن تعيين كلمة المرور في الخطوة الأولى.')->withInput();
        }

        $data = $request->validate([
            'name' => 'required|string|max:255|min:2',
            'email' => 'required_without:phone_number|nullable|email|unique:users,email',
            'phone_number' => 'required_without:email|nullable|string|regex:/^\+?[0-9]{7,15}$/|unique:users,phone_number',
            'country_code' => [
                'required_with:phone_number',
                'nullable',
                'string',
                'max:5',
                function ($attr, $value, $fail) {
                    if ($value && !array_key_exists($value, \App\Constants\CountryCodes::all())) {
                        $fail('رمز الدولة غير صالح.');
                    }
                },
            ],
            'g-recaptcha-response' => ['required', new \App\Rules\Recaptcha],
        ]);

        $countryCode = $request->country_code;
        $phone = $request->phone_number;
        $fullPhone = ($countryCode && $phone) ? '+' . $countryCode . ltrim($phone, '0') : null;

        $registration = $this->authService->resolveRegistrationState($fullPhone, $data['email'] ?? null);

        if ($registration['state'] === UserStatus::ACTIVE) {
            return back()->with('error', 'هذا الرقم مسجل بالفعل. يرجى تسجيل الدخول.')->withInput();
        }

        if ($registration['state'] === UserStatus::PENDING) {
            return redirect()->route('verify', ['uuid' => $registration['user']->uuid])
                ->with('error', 'حسابك يحتاج تفعيل. أدخل الكود المرسل.');
        }

        $result = $this->userService->createUser([
            'phone_number' => $fullPhone,
            'email' => $data['email'] ?? null,
            'name' => $data['name'],
            'is_admin_creation' => false,
            'skip_password' => true,
        ]);

        if (!$result['success']) {
            return back()->with('error', $result['message'])->withInput();
        }

        return redirect()->route('verify', ['uuid' => $result['data']->uuid])
            ->with('success', 'تم إنشاء حسابك مبدئياً! يرجى إدخال رمز التحقق.');
    }

    public function showVerificationForm(string $uuid): View
    {
        return view('auth.verify', compact('uuid'));
    }

    public function verifyOtp(VerifyOtpRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = User::where('uuid', $data['uuid'])->first();
        
        if (!$user) {
            return back()->with('error', 'المستخدم غير موجود')->withInput();
        }
        $result = $this->authService->verifyAccountOtp($user, $data['code']);

        if (!$result['success']) {
            return back()->with('error', $result['message'])->withInput();
        }

        return redirect()->route('password.set', ['uuid' => $user->uuid])
            ->with('success', 'تم التحقق بنجاح! يرجى تعيين كلمة المرور.');
    }

    public function resendOtp(ResendOtpRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $key = 'resend-otp:' . $data['uuid'];
        
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->with('error', 'عدد محاولات إعادة الإرسال كثيرة. يرجى المحاولة بعد 10 دقائق.');
        }
        RateLimiter::hit($key, 600);

        $approvalResult = $this->authService->checkUserApproval($data['uuid']);

        if (!$approvalResult['success']) {
           return back()->with('success', 'إذا كان الحساب موجوداً، تم إرسال رمز التحقق.');
        }

        if ($approvalResult['approved']) {
            return redirect()->route('login')
                ->with('success', 'حسابك مفعل بالفعل! يمكنك الآن تسجيل الدخول.');
        }

        $result = $this->verificationService->resendOtp($data['uuid'], 'verify_account');

        if ($result['success']) {
            return back()->with('success', 'تم إرسال رمز التحقق بنجاح.');
        }

        return back()->with('error', $result['message']);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function showSetPasswordForm(string $uuid): View
    {
        $user = User::where('uuid', $uuid)->first();
        if (!$user || !$user->is_approved || $user->password) {
            abort(403, 'رابط غير صالح أو انتهت صلاحيته.');
        }
        return view('auth.set-password', compact('uuid'));
    }

    public function showForgotPasswordForm(): View
    {
        $countries = CountryCodes::all();
        return view('auth.forgot-password', compact('countries'));
    }

    public function forgotPassword(Request $request): RedirectResponse
    {
        $request->validate(['identifier' => 'required|string']);
        
        $this->authService->forgotPassword($request->identifier);
        
        return back()->with('success', 'إذا كان الحساب مسجلًا لدينا، فستتلقى كود التحقق قريبًا.');
    }

    public function showResetPasswordForm(): View
    {
        return view('auth.reset-password');
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => 'required|string',
            'code' => 'required|string|min:4|max:6',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
        ],
        [
            'password.regex' => 'كلمة المرور يجب أن تحتوي على حرف كبير، حرف صغير، ورقم.',
        ]);

        $verifyResult = $this->authService->verifyPasswordResetOtp($data['identifier'], $data['code']);

        if (!$verifyResult['success']) {
            return back()->with('error', $verifyResult['message'])->withInput();
        }

        $resetResult = $this->authService->resetPasswordWithToken(
            $data['identifier'],
            $verifyResult['reset_token'],
            $data['password']
        );

        if ($resetResult['success']) {
            return redirect()->route('login')->with('success', $resetResult['message']);
        }

        return back()->with('error', $resetResult['message'])->withInput();
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'old_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
        ],
        [
            'password.regex' => 'كلمة المرور يجب أن تحتوي على حرف كبير، حرف صغير، ورقم.',
        ]);

        $result = $this->authService->changePassword($request->user(), $data['old_password'], $data['password']);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    public function setPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'uuid' => 'required|string|exists:users,uuid',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
        ]);

        $user = User::where('uuid', $data['uuid'])->first();
        $result = $this->authService->activatePassword($user, $data['password']);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return redirect()->route('login')->with('success', 'تم تفعيل حسابك بنجاح! يمكنك تسجيل الدخول الآن.');
    }
}