<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\DirectTransferPayment\IndexDirectTransferPaymentRequest;
use App\Http\Requests\Web\DirectTransferPayment\StoreDirectTransferPaymentRequest;
use App\Http\Requests\Web\DirectTransferPayment\UpdateDirectTransferPaymentRequest;
use App\Http\Resources\Admin\DirectTransferPaymentResource;
use App\Repositories\Contracts\DirectTransferPaymentRepositoryInterface;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DirectTransferPaymentController extends Controller
{
    public function __construct(
        private AuditService $auditService,
        private DirectTransferPaymentRepositoryInterface $directTransferPaymentRepository
    ) {
        $this->middleware('require.admin');
    }

    /*
    |--------------------------------------------------------------------------
    | واجهة برمجية (API) - استجابات JSON
    |--------------------------------------------------------------------------
    |
    | إدارة وسائل التحويل المباشر (الحسابات والإعدادات).
    | هذه الأدوات منفصلة عن عمليات اعتماد/رفض التحويلات التي تتم
    | في DirectTransferPaymentAdminController.
    |
    */

    public function index(IndexDirectTransferPaymentRequest $request): JsonResponse
    {
        $records = $this->directTransferPaymentRepository->search(
            $request->validated(),
            $request->validated('per_page') ?? 15
        );

        return $this->success(
            DirectTransferPaymentResource::collection($records),
            'تم استرجاع وسائل التحويل المباشر بنجاح'
        );
    }

    public function show(string $uuid): JsonResponse
    {
        $record = $this->directTransferPaymentRepository->findByUuid($uuid);

        if (! $record) {
            return $this->error(
                'بيانات التحويل المباشر غير موجودة',
                null,
                404
            );
        }

        return $this->success(
            new DirectTransferPaymentResource($record),
            'تم استرجاع بيانات التحويل المباشر بنجاح'
        );
    }

    public function store(StoreDirectTransferPaymentRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $record = $this->directTransferPaymentRepository->create($request->validated());

            DB::afterCommit(function () use ($record) {
                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'direct_transfer_payments',
                    $record->id,
                    null,
                    $record->fresh()->toArray()
                );
            });

            DB::commit();

            return $this->success(
                new DirectTransferPaymentResource($record->fresh()),
                'تم إضافة وسيلة التحويل المباشر بنجاح'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('DirectTransferPayment store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'حدث خطأ أثناء إضافة وسيلة التحويل. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
    }

    public function update(UpdateDirectTransferPaymentRequest $request, string $uuid): JsonResponse
    {
        $record = $this->directTransferPaymentRepository->findByUuid($uuid);

        if (! $record) {
            return $this->error(
                'بيانات التحويل المباشر غير موجودة',
                null,
                404
            );
        }

        $oldData = $record->toArray();

        try {
            DB::beginTransaction();

            $this->directTransferPaymentRepository->update($uuid, $request->validated());

            DB::afterCommit(function () use ($record, $oldData) {
                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'direct_transfer_payments',
                    $record->id,
                    $oldData,
                    $record->fresh()->toArray()
                );
            });

            DB::commit();

            return $this->success(
                new DirectTransferPaymentResource($record->fresh()),
                'تم تحديث وسيلة التحويل المباشر بنجاح'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('DirectTransferPayment update failed', [
                'error' => $e->getMessage(),
                'id'    => $record->id,
            ]);

            return $this->error(
                'حدث خطأ أثناء تحديث وسيلة التحويل. يرجى المحاولة مرة أخرى.',
                null,
                422
            );
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        $record = $this->directTransferPaymentRepository->findByUuid($uuid);

        if (! $record) {
            return $this->error(
                'بيانات التحويل المباشر غير موجودة',
                null,
                404
            );
        }

        if ($this->directTransferPaymentRepository->hasLinkedUserInfo($uuid)) {
            return $this->error(
                'لا يمكن حذف وسيلة التحويل لارتباطها بتحويلات مستخدمين',
                null,
                422
            );
        }

        $recordId = $record->id;
        $oldData = $record->toArray();

        try {
            DB::beginTransaction();

            $this->directTransferPaymentRepository->delete($uuid);

            DB::afterCommit(function () use ($recordId, $oldData) {
                $this->auditService->log(
                    auth()->id(),
                    'delete',
                    'direct_transfer_payments',
                    $recordId,
                    $oldData,
                    null
                );
            });

            DB::commit();

            return $this->success(
                null,
                'تم حذف وسيلة التحويل المباشر بنجاح'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('DirectTransferPayment destroy failed', [
                'error' => $e->getMessage(),
                'id'    => $recordId,
            ]);

            return $this->error(
                'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.',
                null,
                500
            );
        }
    }
}