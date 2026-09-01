<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ProviderService\CreateProviderServiceRequest;
use App\Http\Requests\Web\ProviderService\UpdateProviderServiceRequest;
use App\Http\Requests\Web\ProviderService\IndexProviderServiceRequest;
use App\Http\Requests\Web\ProviderService\StoreProviderFileRequest;
use App\Models\Account;
use App\Models\ProviderService;
use App\Models\TypeOfService;
use App\Models\ProviderFile;
use App\Constants\AccountCodes;
use App\Constants\AccountTypes;
use App\Constants\ProviderFileTypes;
use App\Constants\ProviderStatus;
use App\Constants\FileStatus;
use App\Constants\UserTypes;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ProviderServiceController extends Controller
{
    protected AuditService $auditService;
    protected FileUploadService $fileUploadService;

    public function __construct(AuditService $auditService, FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
        $this->auditService = $auditService;
        $this->middleware('require.admin');
    }


    public function index(IndexProviderServiceRequest $request): View
    {
        $query = ProviderService::with(['user', 'typeOfService', 'branches']);

        if ($search = $request->get('search')) {
            $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(function ($q) use ($escapedSearch) {
                $q->where('name', 'like', "%{$escapedSearch}%")
                  ->orWhere('cr_number', 'like', "%{$escapedSearch}%")
                  ->orWhere('contact_phone', 'like', "%{$escapedSearch}%")
                  ->orWhere('contact_email', 'like', "%{$escapedSearch}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($serviceTypeId = $request->get('type_of_service_id')) {
            $query->where('type_of_service_id', $serviceTypeId);
        }
        if ($commissionType = $request->get('commission_type')) {
            $query->where('commission_type', $commissionType);
        }

        $providerServices = $query->latest()->paginate(15)->withQueryString();
        $serviceTypes = cache()->remember('service-types-list', 3600, function () {
            return TypeOfService::orderBy('type_name')->get(['id', 'type_name']);
        });

        $statusCounts = cache()->remember('provider-services-status-counts', 300, function () {
            return [
                'total'    => ProviderService::count(),
                'active'   => ProviderService::where('status', 'active')->count(),
                'pending'  => ProviderService::where('status', 'pending')->count(),
                'inactive' => ProviderService::where('status', 'inactive')->count(),
            ];
        });

        $users = $this->getProvidersWithoutService();

        return view('admin.provider-services.index', compact('providerServices', 'serviceTypes', 'statusCounts', 'users'));
    }


    public function create(): View
    {
        $serviceTypes = TypeOfService::all();
        $users = $this->getProvidersWithoutService();

        return view('admin.provider-services.create', compact('serviceTypes', 'users'));
    }


    public function store(CreateProviderServiceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $uploadedPaths = [];

        try {
            DB::beginTransaction();

            if ($request->hasFile('brand_photo')) {
                $result = $this->fileUploadService->uploadImage(
                    $request->file('brand_photo'),
                    'providers/temp'
                );
                if ($result['success']) {
                    $data['brand_photo'] = $result['file_path'];
                    $uploadedPaths[] = $result['file_path'];
                }
            }

            if ($request->hasFile('cr_document')) {
                $result = $this->fileUploadService->uploadFile(
                    $request->file('cr_document'),
                    'providers/temp/docs',
                    'document'
                );
                if ($result['success']) {
                    $data['cr_document'] = $result['file_path'];
                    $uploadedPaths[] = $result['file_path'];
                }
            }

            $providerService = ProviderService::create($data);

            $this->ensureProviderAccount($providerService->user_id, $providerService->name);

            DB::afterCommit(function () use ($providerService) {
                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'provider_services',
                    $providerService->id,
                    null,
                    $providerService->fresh()->toArray()
                );
            });

            DB::commit();

            return redirect()
                ->route('admin.provider-services.index')
                ->with('success', 'تم إضافة خدمة المزود بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();

            foreach ($uploadedPaths as $path) {
                $this->fileUploadService->deleteFile($path);
            }

            Log::error('Provider Service store failed', [
                'error'  => $e->getMessage(),
                'user_id' => auth()->id(),
                'trace'  => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'فشل إضافة خدمة المزود. يرجى المحاولة مرة أخرى.');
        }
    }


    public function show(string $uuid)
    {
        $providerService = ProviderService::where('uuid', $uuid)->firstOrFail();

        $providerService->load([
            'user', 'typeOfService', 'branches.city', 'branches.country', 'serviceCommissions'
        ]);

        $files = $providerService->files()->paginate(10, ['*'], 'files_page');

        if (request()->ajax()) {
            return response()->view('admin.provider-services._details', compact('providerService', 'files'));
        }

        return view('admin.provider-services.show', compact('providerService', 'files'));
    }


    public function edit(string $uuid): View
    {
        $providerService = ProviderService::where('uuid', $uuid)->firstOrFail();
        $serviceTypes = TypeOfService::all();
        $users = $this->getProvidersWithoutService();

        return view('admin.provider-services.edit', compact('providerService', 'serviceTypes', 'users'));
    }


    public function update(UpdateProviderServiceRequest $request, string $uuid): RedirectResponse
    {
        $providerService = ProviderService::where('uuid', $uuid)->firstOrFail();
        $oldData = $providerService->toArray();
        $data = $request->validated();
        $newPathsToDelete = [];

        try {
            DB::beginTransaction();

            if ($request->hasFile('brand_photo')) {
                $result = $this->fileUploadService->uploadImage(
                    $request->file('brand_photo'),
                    'providers/temp'
                );
                if ($result['success']) {
                    if ($providerService->brand_photo) {
                        $newPathsToDelete[] = $providerService->brand_photo;
                    }
                    $data['brand_photo'] = $result['file_path'];
                }
            }

            if ($request->hasFile('cr_document')) {
                $result = $this->fileUploadService->uploadFile(
                    $request->file('cr_document'),
                    'providers/temp/docs',
                    'document'
                );
                if ($result['success']) {
                    if ($providerService->cr_document) {
                        $newPathsToDelete[] = $providerService->cr_document;
                    }
                    $data['cr_document'] = $result['file_path'];
                }
            }

            $providerService->update($data);

            DB::afterCommit(function () use ($providerService, $oldData, $newPathsToDelete) {
                foreach ($newPathsToDelete as $path) {
                    $this->fileUploadService->deleteFile($path);
                }

                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'provider_services',
                    $providerService->id,
                    $oldData,
                    $providerService->fresh()->toArray()
                );
            });

            DB::commit();

            return redirect()
                ->route('admin.provider-services.index')
                ->with('success', 'تم تحديث خدمة المزود بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Provider Service update failed', [
                'error' => $e->getMessage(),
                'id'    => $providerService->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'فشل تحديث خدمة المزود. يرجى المحاولة مرة أخرى.');
        }
    }


    public function destroy(string $uuid): RedirectResponse
    {
        $providerService = ProviderService::where('uuid', $uuid)->firstOrFail();

        if ($providerService->branches()->exists()) {
            return redirect()->route('admin.provider-services.index')
                ->with('error', 'لا يمكن حذف هذه الخدمة لأنها مرتبطة بفروع');
        }

        if ($providerService->files()->exists()) {
            return redirect()->route('admin.provider-services.index')
                ->with('error', 'لا يمكن حذف هذه الخدمة لأنها مرتبطة بملفات');
        }

        $oldData = $providerService->toArray();
        $filesToDelete = array_filter([
            $providerService->brand_photo,
            $providerService->cr_document,
        ]);

        try {
            DB::beginTransaction();

            $providerService->delete();

            DB::afterCommit(function () use ($filesToDelete, $oldData) {
                foreach ($filesToDelete as $path) {
                    $this->fileUploadService->deleteFile($path);
                }

                $this->auditService->log(
                    auth()->id(),
                    'delete',
                    'provider_services',
                    $oldData['id'] ?? null,
                    $oldData,
                    null
                );
            });

            DB::commit();

            return redirect()
                ->route('admin.provider-services.index')
                ->with('success', 'تم حذف خدمة المزود بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Provider Service destroy failed', [
                'error' => $e->getMessage(),
                'id'    => $providerService->id,
            ]);

            return redirect()
                ->back()
                ->with('error', 'فشل حذف خدمة المزود. يرجى المحاولة مرة أخرى.');
        }
    }


    public function toggleStatus(string $uuid): RedirectResponse
    {
        $providerService = ProviderService::where('uuid', $uuid)->firstOrFail();

        try {
            $oldStatus = $providerService->status;
            $newStatus = ProviderStatus::toggle($oldStatus);

            DB::beginTransaction();

            $providerService->update(['status' => $newStatus]);

            DB::afterCommit(function () use ($providerService, $oldStatus, $newStatus) {
                $this->auditService->log(
                    auth()->id(),
                    'toggle-status',
                    'provider_services',
                    $providerService->id,
                    ['status' => $oldStatus],
                    ['status' => $newStatus]
                );
            });

            DB::commit();

            return redirect()->back()->with('success', 'تم تغيير الحالة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Provider Service toggleStatus failed', [
                'error' => $e->getMessage(),
                'id'    => $providerService->id,
            ]);

            return redirect()->back()->with('error', 'فشل تغيير الحالة. يرجى المحاولة مرة أخرى.');
        }
    }


    public function uploadFile(StoreProviderFileRequest $request, string $uuid): RedirectResponse
    {
        $providerService = ProviderService::where('uuid', $uuid)->firstOrFail();
        $validated = $request->validated();
        $uploadedPath = null;

        try {
            $directory = "providers/{$providerService->id}/docs";
            $uploadResult = $validated['file_type'] === 'image'
                ? $this->fileUploadService->uploadImage($request->file('file'), $directory)
                : $this->fileUploadService->uploadFile($request->file('file'), $directory, 'document');

            if (!$uploadResult['success']) {
                return back()->with('error', $uploadResult['message']);
            }

            $uploadedPath = $uploadResult['file_path'];

            DB::beginTransaction();

            $file = ProviderFile::create([
                'provider_id' => $providerService->id,
                'name'        => ProviderFileTypes::getLabel($validated['name']),
                'file'        => $uploadedPath,
                'file_type'   => $validated['file_type'],
                'status'      => FileStatus::ACTIVE,
            ]);

            DB::afterCommit(function () use ($providerService, $file) {
                $this->auditService->log(
                    auth()->id(),
                    'upload-file',
                    'provider_services',
                    $providerService->id,
                    null,
                    ['file_id' => $file->id, 'file_name' => $file->name]
                );
            });

            DB::commit();

            return back()->with('success', 'تم رفع الملف بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($uploadedPath) {
                $this->fileUploadService->deleteFile($uploadedPath);
            }

            Log::error('Provider file upload failed', [
                'error' => $e->getMessage(),
                'id'    => $providerService->id,
            ]);

            return back()->with('error', 'فشل رفع الملف. يرجى المحاولة مرة أخرى.');
        }
    }


    public function deleteFile(string $uuid, int $fileId): RedirectResponse
    {
        $providerService = ProviderService::where('uuid', $uuid)->firstOrFail();
        $file = ProviderFile::where('provider_id', $providerService->id)
            ->where('id', $fileId)
            ->firstOrFail();

        $filePath = $file->file;
        $fileId = $file->id;

        try {
            DB::beginTransaction();

            $file->delete();

            DB::afterCommit(function () use ($filePath, $providerService, $fileId) {
                if ($filePath) {
                    $this->fileUploadService->deleteFile($filePath);
                }

                $this->auditService->log(
                    auth()->id(),
                    'delete-file',
                    'provider_services',
                    $providerService->id,
                    ['file_id' => $fileId],
                    null
                );
            });

            DB::commit();

            return back()->with('success', 'تم حذف الملف بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Provider file delete failed', [
                'error' => $e->getMessage(),
                'id'    => $fileId,
            ]);

            return back()->with('error', 'فشل حذف الملف.');
        }
    }


    public function toggleFileStatus(string $uuid, int $fileId): RedirectResponse
    {
        $providerService = ProviderService::where('uuid', $uuid)->firstOrFail();
        $file = ProviderFile::where('provider_id', $providerService->id)
            ->where('id', $fileId)
            ->firstOrFail();

        try {
            $oldStatus = $file->status;
            $newStatus = FileStatus::toggle($oldStatus);

            DB::beginTransaction();

            $file->update(['status' => $newStatus]);

            DB::afterCommit(function () use ($providerService, $fileId, $oldStatus, $newStatus) {
                $this->auditService->log(
                    auth()->id(),
                    'toggle-file-status',
                    'provider_services',
                    $providerService->id,
                    ['file_id' => $fileId, 'status' => $oldStatus],
                    ['file_id' => $fileId, 'status' => $newStatus]
                );
            });

            DB::commit();

            return back()->with('success', 'تم تغيير حالة الملف بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Provider file toggle failed', [
                'error' => $e->getMessage(),
                'id'    => $fileId,
            ]);

            return back()->with('error', 'فشل تغيير حالة الملف.');
        }
    }


    private function ensureProviderAccount(int $userId, string $providerName): void
    {
        $accountCode = AccountCodes::providerAccount($userId);
        $accountName = 'مستحقات مزود - ' . $providerName;

        $parent = Account::where('account_code', AccountCodes::LIABILITIES)->first();
        if (!$parent) {
            Log::warning('Parent LIABILITIES account not found, skipping provider account creation', [
                'user_id' => $userId,
            ]);
            return;
        }

        Account::firstOrCreate(
            ['account_code' => $accountCode],
            [
                'account_name'    => $accountName,
                'account_type'    => AccountTypes::LIABILITY,
                'level'           => $parent->level + 1,
                'parent_id'       => $parent->id,
                'created_by_user' => auth()->id(),
            ]
        );
    }

    private function getProvidersWithoutService()
    {
        return User::where('type_user_code', UserTypes::PROVIDER)
            ->whereDoesntHave('providerService')
            ->orderBy('name')
            ->get(['id', 'name', 'phone_number', 'status_id']);
    }
}