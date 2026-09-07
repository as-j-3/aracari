<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DirectTransferPayment;
use Illuminate\Http\JsonResponse;

class DirectTransferPaymentController extends Controller
{
    /**
     * عرض قائمة حسابات التحويل المباشر المتاحة للمستخدمين.
     */
    public function index(): JsonResponse
    {
        $accounts = DirectTransferPayment::query()
            ->select([
                'uuid',
                'bank_name',
                'name',
                'bank_account_number',
                'phone_number',
            ])
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب حسابات التحويل المباشر بنجاح',
            'data'    => $accounts,
        ], 200);
    }
}