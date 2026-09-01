<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Ads\AdminIndexAdRequest;
use App\Http\Requests\Web\Ads\StoreAdRequest;
use App\Http\Requests\Web\Ads\UpdateAdRequest;
use App\Models\Ad;
use App\Services\AdService;
use App\Repositories\Contracts\AdRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class AdController extends Controller
{
    public function __construct(
        protected AdService $adService,
        protected AdRepositoryInterface $adRepository
    ) {
        $this->middleware('require.admin');
    }

    public function index(AdminIndexAdRequest $request): View
    {
        $result = $this->adService->getAllWithStats(
            perPage: $request->validated('per_page', 15),
            search: $request->validated('search'),
            status: $request->validated('status'),
        );

        return view('admin.ads.index', [
            'ads' => $result['ads'],
            'stats' => $result['stats'],
        ]);
    }

    public function create(): View
    {
        return view('admin.ads.create');
    }

    public function store(StoreAdRequest $request): RedirectResponse
    {
        try {
            $this->adService->createAd(
                $request->validated(),
                $request->file('image'),
            );
            return redirect()->route('admin.ads.index')->with('success', 'تم إنشاء الإعلان بنجاح');
        } catch (\Exception $e) {
            Log::error('Ad store failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء إنشاء الإعلان. يرجى المحاولة مرة أخرى.');
        }
    }

    public function edit(string $uuid): View
    {
        $ad = $this->adRepository->findByUuid($uuid);
        abort_unless($ad, 404, 'الإعلان غير موجود');

        return view('admin.ads.edit', compact('ad'));
    }

    public function update(UpdateAdRequest $request, string $uuid): RedirectResponse
    {
        $ad = $this->adRepository->findByUuid($uuid);
        abort_unless($ad, 404, 'الإعلان غير موجود');

        try {
            $this->adService->updateAd(
                $ad,
                $request->validated(),
                $request->file('image'),
            );
            return redirect()->route('admin.ads.index')->with('success', 'تم تحديث الإعلان بنجاح');
        } catch (\Exception $e) {
            Log::error('Ad update failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث الإعلان. يرجى المحاولة مرة أخرى.');
        }
    }

    public function destroy(string $uuid): RedirectResponse
    {
        $ad = $this->adRepository->findByUuid($uuid);
        abort_unless($ad, 404, 'الإعلان غير موجود');

        try {
            $this->adService->deleteAd($ad);
            return redirect()->route('admin.ads.index')->with('success', 'تم حذف الإعلان بنجاح');
        } catch (\Exception $e) {
            Log::error('Ad destroy failed: ' . $e->getMessage());
            return back()->with('error', 'تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.');
        }
    }
}
