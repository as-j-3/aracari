<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\User\ProfileUpdateRequest;
use App\Models\Booking;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\UserService;
use App\Services\FileUploadService;
use App\Services\Auth\VerificationService;
use App\Services\AuditService;
use App\Services\Wallet\WalletService;
use App\Services\CurrencyService;
use App\Constants\CountryCodes;
use App\Traits\NormalizesPhone;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use NormalizesPhone;

    private UserService $userService;
    private VerificationService $verificationService;
    private FileUploadService $fileUploadService;
    private AuditService $auditService;
    private WalletService $walletService;
    private CurrencyService $currencyService;

    public function __construct(
        UserService $userService, 
        VerificationService $verificationService, 
        FileUploadService $fileUploadService,
        AuditService $auditService,
        WalletService $walletService,
        CurrencyService $currencyService
    ) {
        $this->userService = $userService;
        $this->verificationService = $verificationService; 
        $this->fileUploadService = $fileUploadService;
        $this->auditService = $auditService;
        $this->walletService = $walletService;
        $this->currencyService = $currencyService;
    }

    public function dashboard(): View
    {
        $user = auth()->user();

        $totalBookings = Booking::where('user_id', $user->id)->count();
        $upcomingBookings = Booking::where('user_id', $user->id)
            ->whereIn('booking_status_id', [\App\Constants\BookingStatus::CONFIRMED, \App\Constants\BookingStatus::PENDING])
            ->count();
        $completedBookings = Booking::where('user_id', $user->id)
            ->where('booking_status_id', \App\Constants\BookingStatus::COMPLETED)
            ->count();
        $cancelledBookings = Booking::where('user_id', $user->id)
            ->whereIn('booking_status_id', \App\Constants\BookingStatus::getCancelledStatuses())
            ->count();

        $recentBookings = Booking::where('user_id', $user->id)
            ->with([
                'status:id,status_name',
                'payment:id,booking_id,payed_amount',
                'currency:id,code',
                'trip.departureCity:id,name',
                'trip.destinationCity:id,name',
                'trip:trips.id,trips.departure_time,trips.departure_city,trips.destination_city',
            ])
            ->latest()->limit(3)->get();

        $stats = compact('totalBookings', 'upcomingBookings', 'completedBookings', 'cancelledBookings');

        return view('user.dashboard', compact('user', 'stats', 'recentBookings'));
    }

    public function profile(): View
    {
        return view('user.profile');
    }

    public function bookings(): View
    {
        $user = auth()->user();
        
        $bookings = Booking::where('user_id', $user->id)
            ->with([
                "status:id,status_name",
                "payment:id,booking_id,payment_method_id,payed_amount",
                "payment.paymentMethod:id,method_name",
                "currency:id,code,name",
                "trip.departureCity:id,name",
                "trip.destinationCity:id,name",
                "trip:trips.id,trips.departure_time,trips.departure_city,trips.destination_city",
            ])
            ->latest()
            ->paginate(10);
        
        return view('user.bookings', compact('bookings'));
    }

    public function editProfile(): View
    {
        $countries = CountryCodes::all();

        $userPhone = auth()->user()->phone_number;
        $userCountryCode = '967';
        $userLocalPhone = '';

        if ($userPhone && str_starts_with($userPhone, '+')) {
            $digitsOnly = substr($userPhone, 1); 
            $codes = array_keys($countries);
            usort($codes, fn($a, $b) => strlen($b) - strlen($a));
            foreach ($codes as $code) {
                if (str_starts_with($digitsOnly, $code)) {
                    $userCountryCode = $code;
                    $userLocalPhone = substr($digitsOnly, strlen($code));
                    break;
                }
            }
        }

        return view('user.profile-edit', compact('countries', 'userCountryCode', 'userLocalPhone'));
    }

    public function updateProfile(ProfileUpdateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $newUploadedPath = null;
        $oldPhotoPath = auth()->user()->profile_photo;
        $user = auth()->user();

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        if (!empty($data['phone_number']) && $data['phone_number'] !== $user->phone_number) {
            $data['phone_verified_at'] = null;
        } else {
            unset($data['phone_number']);
        }

        try {
            if ($request->hasFile('profile_photo')) {
                $result = $this->fileUploadService->uploadImage($request->file('profile_photo'), 'profiles/' . auth()->id());
                if ($result['success']) {
                    $data['profile_photo'] = $result['file_path'];
                    $newUploadedPath = $result['file_path'];
                } else {
                    return back()->with('error', $result['message'] ?? 'فشل رفع الصورة');
                }
            }

            DB::beginTransaction();

            $oldData = auth()->user()->toArray();
            $result = $this->userService->updateProfile(auth()->id(), $data);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            DB::afterCommit(function () use ($oldData, $oldPhotoPath) {
                $this->auditService->log(auth()->id(), 'update', 'users', auth()->id(), $oldData, auth()->user()->fresh()->toArray());

                if ($oldPhotoPath) {
                    $this->fileUploadService->deleteFile($oldPhotoPath);
                }
            });

            DB::commit();
            return redirect()->route('dashboard')->with('success', $result['message']);

        } catch (\Exception $e) {
            DB::rollBack();

            if ($newUploadedPath) {
                $this->fileUploadService->deleteFile($newUploadedPath);
            }

            Log::error('Profile update failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث الملف الشخصي.');
        }
    }

    public function wallet(): View
    {
        try {
            $user = auth()->user();
            $baseCurrencyId = $this->currencyService->baseCurrencyId();
            
            $walletAccount = $this->walletService->getOrCreateAccount($user->id, $baseCurrencyId);
            $balance = (float) $walletAccount->balance;
            
            $recentTransactions = WalletTransaction::where('wallet_account_id', $walletAccount->id)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            return view('user.wallet.index', compact('balance', 'recentTransactions'));
        } catch (\App\Exceptions\WalletDisabledException $e) {
            return view('user.wallet.disabled');
        }
    }
    public function transactions(Request $request): View
    {
        $user = auth()->user();
        $baseCurrencyId = $this->currencyService->baseCurrencyId();
        
        $walletAccount = $this->walletService->getOrCreateAccount($user->id, $baseCurrencyId);

        $query = WalletTransaction::where('wallet_account_id', $walletAccount->id)
            ->orderBy('created_at', 'desc');

        if ($request->has('type') && in_array($request->type, ['credit', 'debit'])) {
            $query->where('type', $request->type);
        }

        $transactions = $query->paginate(20);

        return view('user.wallet.transactions', compact('transactions'));
    }
    
    public function phoneVerify()
    {
        return view('user.phone-verify');
    }

    public function sendPhoneOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'country_code' => ['required', 'string', 'max:5'],
            'phone' => 'required|string|regex:/^[0-9]{7,15}$/',
        ]);
        
        $user = auth()->user();
        
        $fullPhone = $this->normalizePhone($request->country_code, $request->phone);

        if ($fullPhone === $user->phone_number && $user->phone_verified_at) {
            return back()->with('error', 'هذا هو رقمك الحالي. أدخل رقم مختلف إذا أردت التغيير.');
        }

        $exists = User::where('phone_number', $fullPhone)->where('id', '!=', $user->id)->exists();
        if ($exists) {
            return back()->with('error', 'هذا الرقم مستخدم بواسطة حساب آخر.');
        }

        $result = $this->verificationService->sendOtp($user, 'verify_phone', 'phone', $fullPhone);

        if ($result['success']) {
            Cache::put("pending_phone:{$user->id}", $fullPhone, now()->addMinutes(10));
        }

        return redirect()->back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    public function verifyPhoneOtp(Request $request): RedirectResponse
    {
        $request->validate(['code' => 'required|numeric|digits:6']);

        $user = auth()->user();
        
        $phone = Cache::get("pending_phone:{$user->id}");
        if (!$phone) {
            return redirect()->route('profile.phone.verify')->with('error', 'انتهت صلاحية الطلب، يرجى طلب رمز تحقق جديد');
        }

        try {
            DB::beginTransaction();

            $result = $this->verificationService->verifyOtp($user->uuid, $request->code, 'verify_phone');

            if ($result['success']) {
                $oldData = $user->fresh()->toArray();

                $phoneChanged = $phone !== $user->phone_number;

                $user->phone_number = $phone;
                $user->phone_verified_at = now();
                $user->save();

                //  Audit + Cache clear في afterCommit
                DB::afterCommit(function () use ($user, $oldData) {
                    $this->auditService->log($user->id, 'update', 'users', $user->id, $oldData, $user->fresh()->toArray());
                    Cache::forget("pending_phone:{$user->id}");
                });

                DB::commit();

                return redirect()->route('dashboard')->with('success', 'تم تفعيل رقم الهاتف بنجاح');
            }

            DB::rollBack();
            return redirect()->back()->with('error', $result['message']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Phone OTP verification failed: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء التحقق، يرجى المحاولة مرة أخرى.');
        }
    }
}