<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('require.admin');
    }


    public function show(Service $service): View
    {
        try {
            $user = auth()->user();
            
            if (!$user->isAdmin()) {
                $serviceProviderId = $service->branch?->provider_id;
                $userProviderId = $user->provider_id ?? null;

                if (is_null($serviceProviderId) || $serviceProviderId !== $userProviderId) {
                    abort(403, 'غير مصرح لك بعرض هذه الخدمة');
                }
            }

            $service->load([
                'branch.providerService',
                'branch.city',
                'branch.country',
                'serviceCommissions.commission'
            ]);

            return view('services.show', compact('service'));
            
        } catch (\Exception $e) {
            Log::error('Failed to display service: ' . $e->getMessage());
            abort(500, 'حدث خطأ أثناء عرض الخدمة. يرجى المحاولة لاحقاً.');
        }
    }
}