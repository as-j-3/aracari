<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreVehicleRequest;
use App\Http\Requests\Provider\UpdateVehicleRequest;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Services\ProviderContext;
use App\Services\VehicleService;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;


class VehicleController extends Controller
{
    protected VehicleService $vehicleService;
    protected ProviderContext $providerContext;
    protected FileUploadService $fileUploadService;

    public function __construct(
        VehicleService $vehicleService,
        ProviderContext $providerContext,
        FileUploadService $fileUploadService
    ) {
        $this->vehicleService = $vehicleService;
        $this->providerContext = $providerContext;
        $this->fileUploadService = $fileUploadService;
    }


    public function index(Request $request): View
    {
        $user = $request->user();
        $providerId = $this->providerContext->providerUserIdFor($user);

        if (!$providerId) {
            abort(403, 'لا يوجد مزود خدمة مرتبط بحسابك');
        }

        $vehicles = Vehicle::with('vehicleType')
            ->withCount('trips')
            ->where('user_id', $providerId)
            ->paginate(15);

        $vehicleTypes = cache()->remember('vehicle-types-list', 3600, function () {
            return VehicleType::orderBy('type_name')->get(['id', 'type_name']);
        });

        return view('provider.vehicles.index', compact('vehicles', 'vehicleTypes'));
    }


    public function create(): View
    {
        $vehicleTypes = cache()->remember('vehicle-types-list', 3600, function () {
            return VehicleType::orderBy('type_name')->get(['id', 'type_name']);
        });
        return view('provider.vehicles.create', compact('vehicleTypes'));
    }


    public function store(StoreVehicleRequest $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $providerId = $this->providerContext->providerUserIdFor($user);

        if (!$providerId) {
            return $this->ajaxError('لا يوجد مزود خدمة مرتبط بحسابك');
        }

        try {
            $data = $request->validated();
            $data['user_id'] = $providerId;
            $data['status'] = $data['status'] ?? 'active';

            $uploadedPaths = [];
            $photoErrors = $this->handleVehiclePhotos($request, $data, $uploadedPaths);
            if ($photoErrors) {
                foreach ($uploadedPaths as $path) {
                    $this->fileUploadService->deleteFile($path);
                }
                return $this->ajaxError($photoErrors);
            }

            $this->vehicleService->createVehicleWithSeats($data);

            return redirect()->route('provider.vehicles.index')
                ->with('success', 'تم إنشاء المركبة والمقاعد بنجاح');
        } catch (\Exception $e) {
            if (isset($uploadedPaths)) {
                foreach ($uploadedPaths as $path) {
                    $this->fileUploadService->deleteFile($path);
                }
            }
            Log::error('Provider vehicle store failed: ' . $e->getMessage());
            return $this->ajaxError('حدث خطأ أثناء إنشاء المركبة. يرجى المحاولة مرة أخرى.');
        }
    }


    public function edit(Vehicle $vehicle): View
    {
        if (!$this->canAccessVehicle($vehicle)) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه المركبة');
        }

        $vehicle->load('seats');
        $vehicleTypes = cache()->remember('vehicle-types-list', 3600, function () {
            return VehicleType::orderBy('type_name')->get(['id', 'type_name']);
        });

        return view('provider.vehicles.edit', compact('vehicle', 'vehicleTypes'));
    }


    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse|JsonResponse
    {
        if (!$this->canAccessVehicle($vehicle)) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه المركبة');
        }

        if ($vehicle->trips()->exists()) {
            $allowedFields = ['status'];
            $attemptedChanges = array_diff(array_keys($request->validated()), $allowedFields);

            if (!empty($attemptedChanges)) {
                return $this->ajaxError('لا يمكن تعديل بيانات المركبة المرتبطة برحلات. يمكنك تغيير حالتها فقط.');
            }
        }

        try {
            $data = $request->validated();

            $oldPathsToDelete = [];
            $uploadedPaths = [];

            if ($request->hasFile('outside_photo') && $vehicle->outside_photo) {
                $oldPathsToDelete[] = $vehicle->outside_photo;
            }
            if ($request->hasFile('inside_photo') && $vehicle->inside_photo) {
                $oldPathsToDelete[] = $vehicle->inside_photo;
            }

            $photoErrors = $this->handleVehiclePhotos($request, $data, $uploadedPaths);
            if ($photoErrors) {
                foreach ($uploadedPaths as $path) {
                    $this->fileUploadService->deleteFile($path);
                }
                return $this->ajaxError($photoErrors);
            }

            $this->vehicleService->updateVehicleWithSeats($vehicle, $data);

            foreach ($oldPathsToDelete as $path) {
                $this->fileUploadService->deleteFile($path);
            }

            return redirect()->route('provider.vehicles.index')
                ->with('success', 'تم تحديث المركبة بنجاح');
        } catch (\Exception $e) {
            if (isset($uploadedPaths)) {
                foreach ($uploadedPaths as $path) {
                    $this->fileUploadService->deleteFile($path);
                }
            }
            Log::error('Provider vehicle update failed: ' . $e->getMessage());
            return $this->ajaxError('حدث خطأ أثناء تحديث المركبة. يرجى المحاولة مرة أخرى.');
        }
    }

    public function destroy(Vehicle $vehicle): RedirectResponse|JsonResponse
    {
        if (!$this->canAccessVehicle($vehicle)) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه المركبة');
        }

        try {
            if ($vehicle->trips()->exists()) {
                return $this->ajaxError('لا يمكن حذف مركبة مرتبطة برحلات. يمكنك تغيير حالتها إلى "غير نشطة" بدلاً من الحذف.');
            }

            $photosToDelete = array_filter([$vehicle->outside_photo, $vehicle->inside_photo]);

            $vehicle->delete();

            foreach ($photosToDelete as $path) {
                $this->fileUploadService->deleteFile($path);
            }

            return redirect()->route('provider.vehicles.index')
                ->with('success', 'تم حذف المركبة بنجاح');
        } catch (\Exception $e) {
            Log::error('Provider vehicle destroy failed: ' . $e->getMessage());
            return $this->ajaxError('تعذر إتمام عملية الحذف. يرجى المحاولة لاحقاً.');
        }
    }


    private function canAccessVehicle(Vehicle $vehicle): bool
    {
        $user = auth()->user();
        return $vehicle->user_id === $user->id || $vehicle->user_id === $user->user_parent;
    }

    private function handleVehiclePhotos(Request $request, array &$data, array &$uploadedPaths = []): ?string
    {
        if ($request->hasFile('outside_photo')) {
            $result = $this->fileUploadService->uploadImage($request->file('outside_photo'), 'vehicles');
            if ($result['success']) {
                $data['outside_photo'] = $result['file_path'];
                $uploadedPaths[] = $result['file_path'];
            } else {
                return $result['message'] ?? 'فشل رفع الصورة الخارجية';
            }
        }

        if ($request->hasFile('inside_photo')) {
            $result = $this->fileUploadService->uploadImage($request->file('inside_photo'), 'vehicles');
            if ($result['success']) {
                $data['inside_photo'] = $result['file_path'];
                $uploadedPaths[] = $result['file_path'];
            } else {
                return $result['message'] ?? 'فشل رفع الصورة الداخلية';
            }
        }

        return null;
    }


    private function ajaxError(string $message, int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
