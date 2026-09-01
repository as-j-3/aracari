<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Http\Requests\Web\JournalEntry\StoreJournalEntryRequest;
use App\Http\Requests\Web\JournalEntry\UpdateJournalEntryRequest;
use App\Models\TransactionReference;
use App\Services\JournalEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class JournalEntryController extends Controller
{
    public function __construct(
        private JournalEntryService $journalEntryService
    ) {
        $this->middleware('require.admin');
    }

    public function index(Request $request): View
    {
        $journalEntries = $this->journalEntryService->getJournalEntries([
            'search' => $request->search,
            'status' => $request->status,
        ]);

        $accounts = cache()->remember('accounts-list', 3600, fn() => Account::orderBy('account_code')->get());

        return view('admin.journal-entries.index', compact('journalEntries', 'accounts'));
    }

    public function store(StoreJournalEntryRequest $request): RedirectResponse|JsonResponse
    {
        $request->merge(['lines' => $this->normalizeLines($request)]);
        $validated = $request->validated();

        try {
            $result = $this->journalEntryService->createJournalEntry($validated, auth()->id());

            if (!$result['success']) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $result['message']], 422);
                }
                return back()->with('error', $result['message'])->withInput();
            }

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $result['message']]);
            }

            return redirect()->route('admin.accounting.journal-entries.index')
                ->with('success', $result['message']);
        } catch (\Exception $e) {
            Log::error('Journal entry store failed: ' . $e->getMessage());
            $msg = 'حدث خطأ مالي غير متوقع أثناء حفظ القيد.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg)->withInput();
        }
    }

    public function update(UpdateJournalEntryRequest $request, string $reference): RedirectResponse|JsonResponse
    {
        $request->merge(['lines' => $this->normalizeLines($request)]);
        $validated = $request->validated();

        $ref = TransactionReference::where('reference_number', $reference)->firstOrFail();

        if ($ref->isConfirmed() || $ref->isReversed()) {
            $msg = 'لا يمكن تعديل قيد معتمد أو معكوس';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg)->withInput();
        }

        try {
            $result = $this->journalEntryService->updateJournalEntry($ref, $validated, auth()->id());

            if (!$result['success']) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $result['message']], 422);
                }
                return back()->with('error', $result['message'])->withInput();
            }

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $result['message']]);
            }

            return redirect()->route('admin.accounting.journal-entries.index')
                ->with('success', $result['message']);
        } catch (\Exception $e) {
            Log::error('Journal entry update failed: ' . $e->getMessage());
            $msg = 'حدث خطأ مالي غير متوقع أثناء تحديث القيد.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg)->withInput();
        }
    }

    public function destroy(Request $request, string $reference): RedirectResponse|JsonResponse
    {
        $ref = TransactionReference::where('reference_number', $reference)->firstOrFail();

        if ($ref->isConfirmed()) {
            $msg = 'لا يمكن حذف قيد معتمد. استخدم عكس القيد بدلاً من ذلك.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        try {
            $result = $this->journalEntryService->deleteJournalEntry($ref, auth()->id());

            if (!$result['success']) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $result['message']], 422);
                }
                return back()->with('error', $result['message']);
            }

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم حذف القيد المحاسبي بنجاح']);
            }

            return redirect()->route('admin.accounting.journal-entries.index')
                ->with('success', 'تم حذف القيد المحاسبي بنجاح');
        } catch (\Exception $e) {
            Log::error('Journal entry destroy failed: ' . $e->getMessage());
            $msg = 'حدث خطأ غير متوقع أثناء حذف القيد.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }
    }

    public function confirm(string $reference): RedirectResponse
    {
        $ref = TransactionReference::where('reference_number', $reference)->firstOrFail();
        $result = $this->journalEntryService->markAsConfirmed($ref, auth()->id());

        return $result['success']
            ? redirect()->back()->with('success', $result['message'])
            : redirect()->back()->with('error', $result['message']);
    }

    public function reverse(string $reference): RedirectResponse
    {
        $ref = TransactionReference::where('reference_number', $reference)->firstOrFail();
        $result = $this->journalEntryService->createReversal($ref, auth()->id());

        return $result['success']
            ? redirect()->back()->with('success', $result['message'])
            : redirect()->back()->with('error', $result['message']);
    }

    public function cancel(string $reference): RedirectResponse
    {
        $ref = TransactionReference::where('reference_number', $reference)->firstOrFail();
        $result = $this->journalEntryService->markAsCancelled($ref, auth()->id());

        return $result['success']
            ? redirect()->back()->with('success', $result['message'])
            : redirect()->back()->with('error', $result['message']);
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
