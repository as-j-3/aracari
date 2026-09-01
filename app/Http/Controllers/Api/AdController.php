<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAdRequest;
use App\Http\Requests\UpdateAdRequest;
use App\Http\Resources\AdResource;
use App\Models\Ad;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Traits\ApiResponse;

class AdController extends Controller
{
    use ApiResponse;


    public function index(): JsonResponse
    {
        $ads = Ad::active()
                ->orderBy('created_at', 'desc')
                ->paginate(20);

        return $this->success(AdResource::collection($ads), 'تم جلب الإعلانات بنجاح');

    }


    public function store(CreateAdRequest $request): JsonResponse
    {
            if (!auth()->user()->isAdmin()) 
            {
            return $this->error('غير مصرح لك بإنشاء اعلان', 403);
            }
        $ad = Ad::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'description' => $request->description,
            'image_url' => $request->image_url,
            'target_url' => $request->target_url,
            'status' => $request->status ?? 'pending',
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return $this->success(new AdResource($ad), 'تم إنشاء الإعلان بنجاح', 201);
    }


    public function show(string $uuid): JsonResponse
    {
        $ad = Ad::where('uuid', $uuid)->first();
        
        if (!$ad) {
            return $this->notFound('الإعلان غير موجود');
        }
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            if (!$ad->isActive()) {
                return $this->notFound('الإعلان غير متاح');
            }
        }

        return $this->success(new AdResource($ad));
    }


    public function update(UpdateAdRequest $request, string $uuid): JsonResponse
    {
        $ad = Ad::where('uuid', $uuid)->first();

        if (!$ad) {
            return $this->notFound('الإعلان غير موجود');
        }

        if ($ad->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            return $this->error('غير مصرح لك بتعديل هذا الإعلان', 403);
        }

        $ad->update($request->validated());

        return $this->success(new AdResource($ad->fresh()), 'تم تحديث الإعلان بنجاح');
    }


    public function destroy(string $uuid): JsonResponse
    {
        $ad = Ad::where('uuid', $uuid)->first();

        if (!$ad) {
            return $this->notFound('الإعلان غير موجود');
        }

        if ($ad->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            return $this->error('غير مصرح لك بحذف هذا الإعلان', 403);
        }

        $ad->delete();

        return $this->success(null, 'تم حذف الإعلان بنجاح');
    }


    public function adminIndex(): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return $this->error('غير مصرح لك', 403);
        }
        $ads = Ad::with('user:id,name,email')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

        return $this->success(AdResource::collection($ads), 'تم جلب جميع الإعلانات بنجاح');
    }
}
