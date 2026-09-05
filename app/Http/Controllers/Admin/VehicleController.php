<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Models\User;
use App\Http\Requests\Web\Vehicle\IndexVehicleRequest;
use App\Http\Resources\VehicleResource;
use App\Constants\UserTypes;
use App\Constants\UserStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Database\Eloquent\Builder;

class VehicleController extends Controller
{
    public function __construct()
    {
        $this->middleware('require.admin');
    }

    public function index(IndexVehicleRequest $request): JsonResponse
    {
        /*
        // الكود السابق (Web View):
        $query = Vehicle::with(['user', 'vehicleType']);

        if ($search = $request->get('search')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(function (Builder $q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                  ->orWhere('model', 'like', "%{$escaped}%")
                  ->orWhereHas('user', fn($sq) => $sq->where('name', 'like', "%{$escaped}%"));
            });
        }

        if ($vehicleTypeId = $request->get('vehicle_type_id')) {
            $query->where('vehicle_type_id', $vehicleTypeId);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($request->get('provider_id')) {
            $query->where('user_id', $request->get('provider_id'));
        }

        $vehicles = $query->latest()
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        $vehicleTypes = $this->getVehicleTypes();
        $providers = $this->getProviders();

        return view('admin.vehicles.index', compact('vehicles', 'vehicleTypes', 'providers'));
        */

        // كود الـ API:
        $query = Vehicle::with(['user', 'vehicleType']);

        if ($search = $request->get('search')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(function (Builder $q) use ($escaped) {
                $q->where('name', 'like', "%{$escaped}%")
                  ->orWhere('model', 'like', "%{$escaped}%")
                  ->orWhereHas('user', fn ($sq) => $sq->where('name', 'like', "%{$escaped}%"));
            });
        }

        if ($vehicleTypeId = $request->get('vehicle_type_id')) {
            $query->where('vehicle_type_id', $vehicleTypeId);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($request->get('provider_id')) {
            $query->where('user_id', $request->get('provider_id'));
        }

        $vehicles = $query->latest()
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        // حساب الإحصائيات الأربع (المطابقة لبطاقات الواجهة)
        $allItems = $vehicles->getCollection();
        $totalFleet = $vehicles->total();
        $activeCount = $allItems->filter(fn ($v) => $v->status === 'active')->count();
        $maintenanceCount = $allItems->filter(fn ($v) => $v->status === 'maintenance')->count();
        $inactiveCount = $allItems->filter(fn ($v) => $v->status === 'inactive')->count();
        $totalSeats = (int) $allItems->sum('seats_count');

        $statistics = [
            'total_fleet'              => $totalFleet,
            'active_vehicles'          => $activeCount,
            'total_capacity'           => $totalSeats,
            'maintenance_and_inactive' => ($maintenanceCount + $inactiveCount),
            'maintenance_count'        => $maintenanceCount,
            'inactive_count'           => $inactiveCount,
        ];

        $vehicleTypes = $this->getVehicleTypes();
        $providers = $this->getProviders();

        return $this->success([
            'statistics'    => $statistics,
            'vehicles'      => VehicleResource::collection($vehicles)->response()->getData(true),
            'vehicle_types' => $vehicleTypes,
            'providers'     => $providers,
        ], 'تم جلب بيانات المركبات بنجاح');
    }

    private function getVehicleTypes()
    {
        return cache()->remember('vehicle-types-list', 3600, function () {
            return VehicleType::orderBy('type_name')->get(['id', 'type_name']);
        });
    }

    private function getProviders()
    {
        return cache()->remember('providers-list', 3600, function () {
            return User::where('type_user_code', UserTypes::PROVIDER)
                ->where('status_id', UserStatus::ACTIVE)
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get();
        });
    }
}