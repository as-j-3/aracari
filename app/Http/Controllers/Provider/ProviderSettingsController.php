<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ProviderService;
use App\Models\ReceiveRevenueMethod;
use App\Models\DigitalPocket;
use App\Services\ProviderContext;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProviderSettingsController extends Controller
{
    public function __construct(
        protected ProviderContext $providerContext,
        protected FileUploadService $fileUploadService,
    ) {
    }

    public function index(Request $request): View
    {
        $providerServiceId = $this->providerContext->providerServiceIdFor($request->user());
        $provider = ProviderService::with(['receiveRevenueMethods.digitalPocket'])
            ->find($providerServiceId);
        if (!$provider) {
            abort(404, 'بيانات المزود غير موجودة');
        }
        $digitalPockets = DigitalPocket::with('currency')->get();
        return view('provider.settings.index', compact('provider', 'digitalPockets'));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string|max:2000',
            'cr_number'      => 'nullable|string|max:100',
            'contact_phone'  => 'nullable|string|max:20',
            'contact_email'  => 'nullable|email|max:255',
            'website'        => 'nullable|url|max:255',
            'born_country'   => 'nullable|string|max:255',
        ], [
            'name.required'       => 'اسم الشركة مطلوب',
            'name.max'            => 'اسم الشركة يجب ألا يتجاوز 255 حرفاً',
            'contact_email.email' => 'البريد الإلكتروني غير صالح',
            'website.url'         => 'رابط الموقع غير صالح',
        ]);
        $providerServiceId = $this->providerContext->providerServiceIdFor($request->user());
        $provider = ProviderService::find($providerServiceId);
        if (!$provider) {
            return $this->ajaxError('بيانات المزود غير موجودة.', 404);
        }
        try {
            $provider->update([
                'name'          => $request->name,
                'description'   => $request->description,
                'cr_number'     => $request->cr_number,
                'contact_phone' => $request->contact_phone,
                'contact_email' => $request->contact_email,
                'website'       => $request->website,
                'born_country'  => $request->born_country,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث بيانات الشركة بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error('Provider profile update failed: ' . $e->getMessage());
            return $this->ajaxError('حدث خطأ أثناء تحديث البيانات.');
        }
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'logo.required'  => 'الرجاء اختيار صورة',
            'logo.image'     => 'الملف يجب أن يكون صورة',
            'logo.max'       => 'حجم الصورة يجب ألا يتجاوز 2 ميجا',
        ]);
        $providerServiceId = $this->providerContext->providerServiceIdFor($request->user());
        $provider = ProviderService::find($providerServiceId);
        if (!$provider) {
            return $this->ajaxError('بيانات المزود غير موجودة.', 404);
        }
        try {
            if ($provider->brand_photo) {
                $this->fileUploadService->deleteFile($provider->brand_photo);
            }
            $upload = $this->fileUploadService->uploadImage($request->file('logo'), 'providers/' . $provider->id);
            if (!$upload['success']) {
                return $this->ajaxError($upload['message']);
            }
            $provider->update(['brand_photo' => $upload['file_path']]);
            $logoUrl = asset('storage/' . $upload['file_path']);
            return response()->json([
                'success'  => true,
                'message'  => 'تم تحديث الشعار بنجاح',
                'logo_url' => $logoUrl,
            ]);
        } catch (\Exception $e) {
            Log::error('Provider logo upload failed: ' . $e->getMessage());
            return $this->ajaxError('حدث خطأ أثناء رفع الشعار.');
        }
    }

    public function removeLogo(Request $request): JsonResponse
    {
        $providerServiceId = $this->providerContext->providerServiceIdFor($request->user());
        $provider = ProviderService::find($providerServiceId);
        if (!$provider) {
            return $this->ajaxError('بيانات المزود غير موجودة.', 404);
        }
        if ($provider->brand_photo) {
            $this->fileUploadService->deleteFile($provider->brand_photo);
            $provider->update(['brand_photo' => null]);
        }
        return response()->json([
            'success' => true,
            'message' => 'تم حذف الشعار بنجاح',
        ]);
    }

    public function storeBankAccount(Request $request): JsonResponse
    {
        $request->validate([
            'account_name'      => 'required|string|max:255',
            'account_number'    => 'required|string|max:100',
            'digital_pocket_id' => 'nullable|exists:digital_pockets,id',
        ], [
            'account_name.required'      => 'اسم صاحب الحساب مطلوب',
            'account_number.required'    => 'رقم الحساب مطلوب',
            'digital_pocket_id.exists' => 'المحفظة المحددة غير موجودة',
        ]);
        $providerServiceId = $this->providerContext->providerServiceIdFor($request->user());
        try {
            ReceiveRevenueMethod::create([
                'provider_id'       => $providerServiceId,
                'account_name'     => $request->account_name,
                'account_number'   => $request->account_number,
                'digital_pocket_id' => $request->digital_pocket_id,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الحساب البنكي بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error('Bank account store failed: ' . $e->getMessage());
            return $this->ajaxError('حدث خطأ أثناء إضافة الحساب.');
        }
    }

    public function updateBankAccount(Request $request, ReceiveRevenueMethod $method): JsonResponse
    {
        $providerServiceId = $this->providerContext->providerServiceIdFor($request->user());
        if ($method->provider_id != $providerServiceId) {
            return $this->ajaxError('غير مصرح.', 403);
        }
        $request->validate([
            'account_name'      => 'required|string|max:255',
            'account_number'    => 'required|string|max:100',
            'digital_pocket_id' => 'nullable|exists:digital_pockets,id',
        ], [
            'account_name.required'      => 'اسم صاحب الحساب مطلوب',
            'account_number.required'    => 'رقم الحساب مطلوب',
        ]);
        try {
            $method->update([
                'account_name'      => $request->account_name,
                'account_number'    => $request->account_number,
                'digital_pocket_id' => $request->digital_pocket_id,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الحساب البنكي بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error('Bank account update failed: ' . $e->getMessage());
            return $this->ajaxError('حدث خطأ أثناء تحديث الحساب.');
        }
    }

    public function destroyBankAccount(Request $request, ReceiveRevenueMethod $method): JsonResponse
    {
        $providerServiceId = $this->providerContext->providerServiceIdFor($request->user());
        if ($method->provider_id != $providerServiceId) {
            return $this->ajaxError('غير مصرح.', 403);
        }
        try {
            $method->delete();
            return response()->json([
                'success' => true,
                'message' => 'تم حذف الحساب البنكي بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error('Bank account delete failed: ' . $e->getMessage());
            return $this->ajaxError('حدث خطأ أثناء حذف الحساب.');
        }
    }
    private function ajaxError(string $message, int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
