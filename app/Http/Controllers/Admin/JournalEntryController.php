<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\JournalEntry\StoreJournalEntryRequest;
use App\Http\Requests\Api\JournalEntry\UpdateJournalEntryRequest;
use App\Http\Resources\Admin\JournalEntryResource;
use App\Models\Account;
use App\Models\TransactionReference;
use App\Services\JournalEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JournalEntryController extends Controller
{
    public function __construct(
        private JournalEntryService $journalEntryService
    ) {
        $this->middleware('require.admin');
    }

    /**
     * عرض قائمة القيود المحاسبية مع إمكانية البحث والتصفية.
     */
    public function index(Request $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Old Web implementation - kept for reference
        |--------------------------------------------------------------------------
        $journalEntries = $this->journalEntryService->getJournalEntries([
            'search' => $request->search,
            'status' => $request->status,
        ]);

        $accounts = cache()->remember('accounts-list', 3600, fn() => Account::orderBy('account_code')->get());

        return view('admin.journal-entries.index', compact('journalEntries', 'accounts'));
        */

        $journalEntries = $this->journalEntryService->getJournalEntries([
            'search' => $request->search,
            'status' => $request->status,
        ]);

        $journalEntries->getCollection()->load(['creator', 'transactions.account', 'transactions.currency']);

        $accounts = cache()->remember('accounts-list', 3600, fn() => Account::orderBy('account_code')->get());

        return $this->success([
            'journal_entries' => JournalEntryResource::collection($journalEntries->items()),
            'accounts' => $accounts,
            'pagination' => [
                'current_page' => $journalEntries->currentPage(),
                'last_page' => $journalEntries->lastPage(),
                'per_page' => $journalEntries->perPage(),
                'total' => $journalEntries->total(),
            ],
        ], 'تم جلب القيود المحاسبية بنجاح');
    }

    /**
     * إنشاء قيد محاسبي جديد.
     */
    public function store(StoreJournalEntryRequest $request): JsonResponse
    {
        $request->merge(['lines' => $this->normalizeLines($request)]);
        $validated = $request->validated();

        try {
            $result = $this->journalEntryService->createJournalEntry($validated, auth()->id());

            if (!$result['success']) {
                /*
                |--------------------------------------------------------------------------
                | Old Web implementation - kept for reference
                |--------------------------------------------------------------------------
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $result['message']], 422);
                }
                return back()->with('error', $result['message'])->withInput();
                */
                return $this->error($result['message'], null, 422);
            }

            $result['data']->loadMissing(['creator', 'transactions.account', 'transactions.currency']);

            return $this->success(
                new JournalEntryResource($result['data']),
                $result['message']
            );
        } catch (\Exception $e) {
            Log::error('Journal entry store failed: ' . $e->getMessage());

            /*
            |--------------------------------------------------------------------------
            | Old Web implementation - kept for reference
            |--------------------------------------------------------------------------
            $msg = 'حدث خطأ مالي غير متوقع أثناء حفظ القيد.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg)->withInput();
            */

            return $this->error('حدث خطأ مالي غير متوقع أثناء حفظ القيد.', null, 422);
        }
    }

    /**
     * تحديث قيد محاسبي.
     */
    public function update(UpdateJournalEntryRequest $request, string $reference): JsonResponse
    {
        $request->merge(['lines' => $this->normalizeLines($request)]);
        $validated = $request->validated();

        $ref = TransactionReference::where('reference_number', $reference)->firstOrFail();

        if ($ref->isConfirmed() || $ref->isReversed()) {
            /*
            |--------------------------------------------------------------------------
            | Old Web implementation - kept for reference
            |--------------------------------------------------------------------------
            $msg = 'لا يمكن تعديل قيد معتمد أو معكوس';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg)->withInput();
            */
            return $this->error('لا يمكن تعديل قيد معتمد أو معكوس', null, 422);
        }

        try {
            $result = $this->journalEntryService->updateJournalEntry($ref, $validated, auth()->id());

            if (!$result['success']) {
                /*
                |--------------------------------------------------------------------------
                | Old Web implementation - kept for reference
                |--------------------------------------------------------------------------
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $result['message']], 422);
                }
                return back()->with('error', $result['message'])->withInput();
                */
                return $this->error($result['message'], null, 422);
            }

            $result['data']->loadMissing(['creator', 'transactions.account', 'transactions.currency']);

            return $this->success(
                new JournalEntryResource($result['data']),
                $result['message']
            );
        } catch (\Exception $e) {
            Log::error('Journal entry update failed: ' . $e->getMessage());

            /*
            |--------------------------------------------------------------------------
            | Old Web implementation - kept for reference
            |--------------------------------------------------------------------------
            $msg = 'حدث خطأ مالي غير متوقع أثناء تحديث القيد.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg)->withInput();
            */

            return $this->error('حدث خطأ مالي غير متوقع أثناء تحديث القيد.', null, 422);
        }
    }

    /**
     * حذف قيد محاسبي.
     */
    public function destroy(string $reference): JsonResponse
    {
        $ref = TransactionReference::where('reference_number', $reference)->firstOrFail();

        if ($ref->isConfirmed()) {
            /*
            |--------------------------------------------------------------------------
            | Old Web implementation - kept for reference
            |--------------------------------------------------------------------------
            $msg = 'لا يمكن حذف قيد معتمد. استخدم عكس القيد بدلاً من ذلك.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
            */
            return $this->error('لا يمكن حذف قيد معتمد. استخدم عكس القيد بدلاً من ذلك.', null, 422);
        }

        try {
            $result = $this->journalEntryService->deleteJournalEntry($ref, auth()->id());

            if (!$result['success']) {
                return $this->error($result['message'], null, 422);
            }

            return $this->success(null, 'تم حذف القيد المحاسبي بنجاح');
        } catch (\Exception $e) {
            Log::error('Journal entry destroy failed: ' . $e->getMessage());
            return $this->error('حدث خطأ غير متوقع أثناء حذف القيد.', null, 422);
        }
    }

    /**
     * اعتماد قيد محاسبي.
     */
    public function confirm(string $reference): JsonResponse
    {
        $ref = TransactionReference::where('reference_number', $reference)->firstOrFail();
        $result = $this->journalEntryService->markAsConfirmed($ref, auth()->id());

        /*
        |--------------------------------------------------------------------------
        | Old Web implementation - kept for reference
        |--------------------------------------------------------------------------
        return $result['success']
            ? redirect()->back()->with('success', $result['message'])
            : redirect()->back()->with('error', $result['message']);
        */

        return $result['success']
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], null, 422);
    }

    /**
     * عكس قيد محاسبي معتمد.
     */
    public function reverse(string $reference): JsonResponse
    {
        $ref = TransactionReference::where('reference_number', $reference)->firstOrFail();
        $result = $this->journalEntryService->createReversal($ref, auth()->id());

        /*
        |--------------------------------------------------------------------------
        | Old Web implementation - kept for reference
        |--------------------------------------------------------------------------
        return $result['success']
            ? redirect()->back()->with('success', $result['message'])
            : redirect()->back()->with('error', $result['message']);
        */

        if ($result['success']) {
            $data = isset($result['data']) ? new JournalEntryResource($result['data']) : null;

            return $this->success($data, $result['message']);
        }

        return $this->error($result['message'], null, 422);
    }

    /**
     * إلغاء قيد محاسبي.
     */
    public function cancel(string $reference): JsonResponse
    {
        $ref = TransactionReference::where('reference_number', $reference)->firstOrFail();
        $result = $this->journalEntryService->markAsCancelled($ref, auth()->id());

        /*
        |--------------------------------------------------------------------------
        | Old Web implementation - kept for reference
        |--------------------------------------------------------------------------
        return $result['success']
            ? redirect()->back()->with('success', $result['message'])
            : redirect()->back()->with('error', $result['message']);
        */

        return $result['success']
            ? $this->success(null, $result['message'])
            : $this->error($result['message'], null, 422);
    }

    private function normalizeLines(Request $request): array
    {
        $lines = $request->input('lines', []);

        if (is_array($lines)) {
            return array_values(array_filter($lines, fn($line) =>
                filled($line['account_code'] ?? null) ||
                filled($line['debit'] ?? null) ||
                filled($line['credit'] ?? null)
            ));
        }

        return [];
    }
}
