<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SecurityIncident\BlockIpRequest;
use App\Http\Requests\Api\SecurityIncident\FilterSecurityIncidentRequest;
use App\Http\Resources\Admin\SecurityIncidentResource;
use App\Models\SecurityIncident;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SecurityIncidentController extends Controller
{
    public function __construct()
    {
        $this->middleware('require.admin');
    }

    /**
     * عرض قائمة الحوادث الأمنية.
     */
    public function index(FilterSecurityIncidentRequest $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Old Web implementation - kept for reference
        |--------------------------------------------------------------------------
        $query = SecurityIncident::orderBy('occurred_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                ->orWhere('request_path', 'like', "%{$search}%")
                ->orWhere('incident_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->has('blocked') && $request->blocked !== '') {
            $query->where('was_blocked', $request->boolean('blocked'));
        }

        $incidents = $query->paginate(20);

        return view('admin.security-incidents.index', compact('incidents'));
        */

        $perPage = $request->validated('per_page', 20);

        $query = SecurityIncident::orderBy('occurred_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->validated('search');
            $query->where(function ($q) use ($search) {
                $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
                $q->where('ip_address', 'like', "%{$escaped}%")
                    ->orWhere('request_path', 'like', "%{$escaped}%")
                    ->orWhere('incident_type', 'like', "%{$escaped}%");
            });
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->validated('severity'));
        }

        if ($request->has('blocked') && $request->blocked !== '') {
            $query->where('was_blocked', $request->boolean('blocked'));
        }

        $incidents = $query->paginate($perPage);

        return $this->success([
            'incidents' => SecurityIncidentResource::collection($incidents->items()),
            'pagination' => [
                'current_page' => $incidents->currentPage(),
                'last_page'    => $incidents->lastPage(),
                'per_page'     => $incidents->perPage(),
                'total'        => $incidents->total(),
            ],
        ], 'تم جلب الحوادث الأمنية بنجاح');
    }

    /**
     * فك حظر عنوان IP.
     */
    public function unblock(BlockIpRequest $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Old Web implementation - kept for reference
        |--------------------------------------------------------------------------
        $request->validate([
            'ip_address' => 'required|ip',
        ]);

        $ip = $request->ip_address;

        try {
            DB::beginTransaction();

            SecurityIncident::where('ip_address', $ip)
                ->where('was_blocked', true)
                ->update([
                    'was_blocked' => false,
                    'severity' => 'low'
                ]);

            DB::afterCommit(function () use ($ip) {
                Cache::forget('blocked_ip:' . $ip);

                app(AuditService::class)->log(
                    auth()->id(),
                    'unblock_ip',
                    'security_incidents',
                    null,
                    ['ip_address' => $ip, 'action' => 'unblocked'],
                    null
                );
            });

            DB::commit();

            return redirect()->back()
                ->with('success', "تم فك الحظر عن الـ IP: {$ip} بنجاح.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'فشل فك الحظر. يرجى المحاولة مرة أخرى.');
        }
        */

        $ip = $request->validated('ip_address');

        try {
            DB::beginTransaction();

            SecurityIncident::where('ip_address', $ip)
                ->where('was_blocked', true)
                ->update([
                    'was_blocked' => false,
                    'severity' => 'low'
                ]);

            DB::afterCommit(function () use ($ip) {
                Cache::forget('blocked_ip:' . $ip);

                app(AuditService::class)->log(
                    auth()->id(),
                    'unblock_ip',
                    'security_incidents',
                    null,
                    ['ip_address' => $ip, 'action' => 'unblocked'],
                    null
                );
            });

            DB::commit();

            return $this->success(null, "تم فك الحظر عن الـ IP: {$ip} بنجاح.");
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('فشل فك الحظر. يرجى المحاولة مرة أخرى.', null, 500);
        }
    }

    /**
     * حظر عنوان IP.
     */
    public function block(BlockIpRequest $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Old Web implementation - kept for reference
        |--------------------------------------------------------------------------
        $request->validate([
            'ip_address' => 'required|ip',
        ]);

        $ip = $request->ip_address;

        try {
            DB::beginTransaction();

            SecurityIncident::where('ip_address', $ip)
                ->where('was_blocked', false)
                ->update([
                    'was_blocked' => true,
                    'severity' => 'high'
                ]);

            DB::afterCommit(function () use ($ip) {
                Cache::put('blocked_ip:' . $ip, true, now()->addMonth());

                app(AuditService::class)->log(
                    auth()->id(),
                    'block_ip',
                    'security_incidents',
                    null,
                    null,
                    ['ip_address' => $ip, 'action' => 'blocked']
                );
            });

            DB::commit();

            return redirect()->back()
                ->with('success', "تم حظر الـ IP: {$ip} بنجاح.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'فشل الحظر. يرجى المحاولة مرة أخرى.');
        }
        */

        $ip = $request->validated('ip_address');

        try {
            DB::beginTransaction();

            SecurityIncident::where('ip_address', $ip)
                ->where('was_blocked', false)
                ->update([
                    'was_blocked' => true,
                    'severity' => 'high'
                ]);

            DB::afterCommit(function () use ($ip) {
                Cache::put('blocked_ip:' . $ip, true, now()->addMonth());

                app(AuditService::class)->log(
                    auth()->id(),
                    'block_ip',
                    'security_incidents',
                    null,
                    null,
                    ['ip_address' => $ip, 'action' => 'blocked']
                );
            });

            DB::commit();

            return $this->success(null, "تم حظر الـ IP: {$ip} بنجاح.");
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('فشل الحظر. يرجى المحاولة مرة أخرى.', null, 500);
        }
    }
}
