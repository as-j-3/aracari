<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Passenger\CreatePassengerRequest;
use App\Http\Requests\Web\Passenger\UpdatePassengerRequest;
use App\Http\Requests\Web\Passenger\IndexPassengerRequest;
use App\Http\Resources\PassengerResource;
use App\Models\Passenger;
use App\Models\Gender;
use App\Models\Country;
use App\Services\AuditService;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

class PassengerController extends Controller
{
    protected AuditService $auditService;
    protected FileUploadService $fileUploadService;

    public function __construct(AuditService $auditService, FileUploadService $fileUploadService)
    {
        $this->auditService = $auditService;
        $this->fileUploadService = $fileUploadService;
        $this->middleware('require.admin');
    }

    public function index(IndexPassengerRequest $request): JsonResponse
    {
        /*
        // الكود السابق (Web View):
        $query = Passenger::with(['gender', 'creator'])
            ->withCount(['tripReservations']);

        if ($search = $request->get('search')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(function (Builder $q) use ($escaped) {
                $q->where('full_name', 'like', "%{$escaped}%")
                  ->orWhere('phone_number', 'like', "%{$escaped}%")
                  ->orWhere('personal_card_id', 'like', "%{$escaped}%")
                  ->orWhere('passport_number', 'like', "%{$escaped}%");
            });
        }

        if ($genderId = $request->get('gender_id')) {
            $query->where('gender_id', $genderId);
        }

        if ($minAge = $request->get('min_age')) {
            $query->where('birthday', '<=', now()->subYears($minAge)->endOfDay());
        }
        if ($maxAge = $request->get('max_age')) {
            $query->where('birthday', '>=', now()->subYears($maxAge + 1)->startOfDay());
        }

        $passengers = $query->latest()
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        $genders = cache()->remember('genders-list', 3600, function () {
            return Gender::orderBy('name')->get(['id', 'name']);
        });

        return view('admin.passengers.index', compact('passengers', 'genders'));
        */

        // كود الـ API:
        $query = Passenger::with(['gender', 'creator', 'passportCountry', 'passportCity', 'personalCountry', 'personalCity'])
            ->withCount(['tripReservations']);

        if ($search = $request->get('search')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(function (Builder $q) use ($escaped) {
                $q->where('full_name', 'like', "%{$escaped}%")
                  ->orWhere('phone_number', 'like', "%{$escaped}%")
                  ->orWhere('personal_card_id', 'like', "%{$escaped}%")
                  ->orWhere('passport_number', 'like', "%{$escaped}%");
            });
        }

        if ($genderId = $request->get('gender_id')) {
            $query->where('gender_id', $genderId);
        }

        if ($minAge = $request->get('min_age')) {
            $query->where('birthday', '<=', now()->subYears($minAge)->endOfDay());
        }
        if ($maxAge = $request->get('max_age')) {
            $query->where('birthday', '>=', now()->subYears($maxAge + 1)->startOfDay());
        }

        $passengers = $query->latest()
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        return $this->success(PassengerResource::collection($passengers), 'تم جلب بيانات المسافرين بنجاح');
    }

    /*
    * دالة create خاصة بعرض صفحة الـ HTML (تم تركها كما هي دون تحويل)
    */
    public function create(): View
    {
        $genders = Gender::all();
        $countries = Country::with('cities')->get();
        return view('admin.passengers.create', compact('genders', 'countries'));
    }

    public function store(CreatePassengerRequest $request): JsonResponse
    {
        /*
        // الكود السابق (Web Redirect):
        $data = $request->validated();
        $uploadedPaths = [];

        try {
            DB::beginTransaction();

            if ($request->hasFile('personal_card_id_photo')) {
                $result = $this->fileUploadService->uploadImage(
                    $request->file('personal_card_id_photo'),
                    'passengers'
                );
                if ($result['success']) {
                    $data['personal_card_id_photo'] = $result['file_path'];
                    $uploadedPaths[] = $result['file_path'];
                }
            }

            if ($request->hasFile('passport_photo')) {
                $result = $this->fileUploadService->uploadImage(
                    $request->file('passport_photo'),
                    'passengers'
                );
                if ($result['success']) {
                    $data['passport_photo'] = $result['file_path'];
                    $uploadedPaths[] = $result['file_path'];
                }
            }

            $passenger = Passenger::create($data);

            DB::afterCommit(function () use ($passenger) {
                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'passengers',
                    $passenger->id,
                    null,
                    $passenger->fresh()->toArray()
                );
            });

            DB::commit();

            return redirect()->route('admin.passengers.index')
                ->with('success', 'تم إضافة المسافر بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();

            foreach ($uploadedPaths as $path) {
                $this->fileUploadService->deleteFile($path);
            }

            Log::error('Passenger store failed', [
                'error'  => $e->getMessage(),
                'user_id' => auth()->id(),
                'trace'  => $e->getTraceAsString(),
            ]);

            return redirect()->back()->withInput()
                ->with('error', 'فشل إضافة المسافر. يرجى المحاولة مرة أخرى.');
        }
        */

        // كود الـ API:
        $data = $request->validated();
        $uploadedPaths = [];

        try {
            DB::beginTransaction();

            if ($request->hasFile('personal_card_id_photo')) {
                $result = $this->fileUploadService->uploadImage(
                    $request->file('personal_card_id_photo'),
                    'passengers'
                );
                if ($result['success']) {
                    $data['personal_card_id_photo'] = $result['file_path'];
                    $uploadedPaths[] = $result['file_path'];
                }
            }

            if ($request->hasFile('passport_photo')) {
                $result = $this->fileUploadService->uploadImage(
                    $request->file('passport_photo'),
                    'passengers'
                );
                if ($result['success']) {
                    $data['passport_photo'] = $result['file_path'];
                    $uploadedPaths[] = $result['file_path'];
                }
            }

            $passenger = Passenger::create($data);

            DB::afterCommit(function () use ($passenger) {
                $this->auditService->log(
                    auth()->id(),
                    'create',
                    'passengers',
                    $passenger->id,
                    null,
                    $passenger->fresh()->toArray()
                );
            });

            DB::commit();

            return $this->success(
                new PassengerResource($passenger->fresh()->load(['gender', 'creator', 'passportCountry', 'passportCity', 'personalCountry', 'personalCity'])),
                'تم إضافة المسافر بنجاح',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();

            foreach ($uploadedPaths as $path) {
                $this->fileUploadService->deleteFile($path);
            }

            Log::error('Passenger store API failed', [
                'error'  => $e->getMessage(),
                'user_id' => auth()->id(),
                'trace'  => $e->getTraceAsString(),
            ]);

            return $this->error('فشل إضافة المسافر. يرجى المحاولة مرة أخرى.', null, 500);
        }
    }

    public function show(Passenger $passenger): JsonResponse
    {
        /*
        // الكود السابق (Web View):
        $passenger->load(['gender', 'creator', 'tripReservations.trip']);
        return view('admin.passengers.show', compact('passenger'));
        */

        // كود الـ API:
        $passenger->load(['gender', 'creator', 'tripReservations.trip', 'passportCountry', 'passportCity', 'personalCountry', 'personalCity']);
        return $this->success(new PassengerResource($passenger), 'تم جلب بيانات المسافر بنجاح');
    }

    /*
    * دالة edit خاصة بعرض صفحة الـ HTML (تم تركها كما هي دون تحويل)
    */
    public function edit(Passenger $passenger): View
    {
        $countries = Country::with('cities')->get();
        $genders = Gender::all();
        return view('admin.passengers.edit', compact('passenger', 'genders', 'countries'));
    }

    public function update(UpdatePassengerRequest $request, Passenger $passenger): JsonResponse
    {
        /*
        // الكود السابق (Web Redirect):
        $oldData = $passenger->toArray();
        $data = $request->validated();
        $oldPathsToDelete = [];

        try {
            DB::beginTransaction();

            if ($request->hasFile('personal_card_id_photo')) {
                $result = $this->fileUploadService->uploadImage(
                    $request->file('personal_card_id_photo'),
                    'passengers'
                );
                if ($result['success']) {
                    if ($passenger->personal_card_id_photo) {
                        $oldPathsToDelete[] = $passenger->personal_card_id_photo;
                    }
                    $data['personal_card_id_photo'] = $result['file_path'];
                }
            }

            if ($request->hasFile('passport_photo')) {
                $result = $this->fileUploadService->uploadImage(
                    $request->file('passport_photo'),
                    'passengers'
                );
                if ($result['success']) {
                    if ($passenger->passport_photo) {
                        $oldPathsToDelete[] = $passenger->passport_photo;
                    }
                    $data['passport_photo'] = $result['file_path'];
                }
            }

            $passenger->update($data);

            DB::afterCommit(function () use ($passenger, $oldData, $oldPathsToDelete) {
                foreach ($oldPathsToDelete as $path) {
                    $this->fileUploadService->deleteFile($path);
                }

                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'passengers',
                    $passenger->id,
                    $oldData,
                    $passenger->fresh()->toArray()
                );
            });

            DB::commit();

            return redirect()->route('admin.passengers.index')
                ->with('success', 'تم تحديث المسافر بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Passenger update failed', [
                'error' => $e->getMessage(),
                'id'    => $passenger->id,
            ]);

            return redirect()->back()->withInput()
                ->with('error', 'فشل تحديث المسافر. يرجى المحاولة مرة أخرى.');
        }
        */

        // كود الـ API:
        $oldData = $passenger->toArray();
        $data = $request->validated();
        $oldPathsToDelete = [];

        try {
            DB::beginTransaction();

            if ($request->hasFile('personal_card_id_photo')) {
                $result = $this->fileUploadService->uploadImage(
                    $request->file('personal_card_id_photo'),
                    'passengers'
                );
                if ($result['success']) {
                    if ($passenger->personal_card_id_photo) {
                        $oldPathsToDelete[] = $passenger->personal_card_id_photo;
                    }
                    $data['personal_card_id_photo'] = $result['file_path'];
                }
            }

            if ($request->hasFile('passport_photo')) {
                $result = $this->fileUploadService->uploadImage(
                    $request->file('passport_photo'),
                    'passengers'
                );
                if ($result['success']) {
                    if ($passenger->passport_photo) {
                        $oldPathsToDelete[] = $passenger->passport_photo;
                    }
                    $data['passport_photo'] = $result['file_path'];
                }
            }

            $passenger->update($data);

            DB::afterCommit(function () use ($passenger, $oldData, $oldPathsToDelete) {
                foreach ($oldPathsToDelete as $path) {
                    $this->fileUploadService->deleteFile($path);
                }

                $this->auditService->log(
                    auth()->id(),
                    'update',
                    'passengers',
                    $passenger->id,
                    $oldData,
                    $passenger->fresh()->toArray()
                );
            });

            DB::commit();

            return $this->success(
                new PassengerResource($passenger->fresh()->load(['gender', 'creator', 'passportCountry', 'passportCity', 'personalCountry', 'personalCity'])),
                'تم تحديث المسافر بنجاح'
            );
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Passenger update API failed', [
                'error' => $e->getMessage(),
                'id'    => $passenger->id,
            ]);

            return $this->error('فشل تحديث المسافر. يرجى المحاولة مرة أخرى.', null, 500);
        }
    }

    public function destroy(Passenger $passenger): JsonResponse
    {
        /*
        // الكود السابق (Web Redirect):
        if ($passenger->tripReservations()->exists()) {
            return redirect()->route('admin.passengers.index')
                ->with('error', 'لا يمكن حذف هذا المسافر لأنه مرتبط بحجوزات');
        }

        $oldData = $passenger->toArray();
        $passengerId = $passenger->id;
        $filesToDelete = array_filter([
            $passenger->personal_card_id_photo,
            $passenger->passport_photo,
        ]);

        try {
            DB::beginTransaction();

            $passenger->delete();

            DB::afterCommit(function () use ($filesToDelete, $oldData, $passengerId) {
                foreach ($filesToDelete as $path) {
                    $this->fileUploadService->deleteFile($path);
                }

                $this->auditService->log(
                    auth()->id(),
                    'delete',
                    'passengers',
                    $passengerId,
                    $oldData,
                    null
                );
            });

            DB::commit();

            return redirect()->route('admin.passengers.index')
                ->with('success', 'تم حذف المسافر بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Passenger destroy failed', [
                'error' => $e->getMessage(),
                'id'    => $passenger->id,
            ]);

            return redirect()->back()
                ->with('error', 'فشل حذف المسافر. يرجى المحاولة مرة أخرى.');
        }
        */

        // كود الـ API:
        if ($passenger->tripReservations()->exists()) {
            return $this->error('لا يمكن حذف هذا المسافر لأنه مرتبط بحجوزات', null, 422);
        }

        $oldData = $passenger->toArray();
        $passengerId = $passenger->id;
        $filesToDelete = array_filter([
            $passenger->personal_card_id_photo,
            $passenger->passport_photo,
        ]);

        try {
            DB::beginTransaction();

            $passenger->delete();

            DB::afterCommit(function () use ($filesToDelete, $oldData, $passengerId) {
                foreach ($filesToDelete as $path) {
                    $this->fileUploadService->deleteFile($path);
                }

                $this->auditService->log(
                    auth()->id(),
                    'delete',
                    'passengers',
                    $passengerId,
                    $oldData,
                    null
                );
            });

            DB::commit();

            return $this->success(null, 'تم حذف المسافر بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Passenger destroy API failed', [
                'error' => $e->getMessage(),
                'id'    => $passenger->id,
            ]);

            return $this->error('فشل حذف المسافر. يرجى المحاولة مرة أخرى.', null, 500);
        }
    }
     public function passengerStatistics(): JsonResponse
    {
        if (! auth()->user()?->isAdmin()) {
            return $this->error('غير مصرح لك', null, 403);
        }

        $totalPassengers = Passenger::count();

        $genderCounts = Passenger::selectRaw('gender_id, COUNT(*) as count')
            ->groupBy('gender_id')
            ->with('gender:id,name')
            ->get()
            ->map(function ($item) {
                return [
                    'gender_id' => $item->gender_id,
                    'gender_name' => $item->gender?->name,
                    'count' => $item->count,
                ];
            });

        $averageAge = Passenger::whereNotNull('birthday')
            ->selectRaw('AVG(TIMESTAMPDIFF(YEAR, birthday, CURDATE())) as avg_age')
            ->value('avg_age');

        return $this->success([
            'total_passengers' => $totalPassengers,
            'gender_counts' => $genderCounts,
            'average_age' => round((float) $averageAge, 1),
        ], 'تم جلب إحصائيات المسافرين بنجاح');
    }
}