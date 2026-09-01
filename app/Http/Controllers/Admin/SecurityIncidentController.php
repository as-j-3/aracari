<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Cache;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use App\Models\SecurityIncident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\AuditService;
use Illuminate\View\View;

class SecurityIncidentController extends Controller
{
        public function __construct()
    {
        $this->middleware('require.admin');
    }

    public function index(Request $request): View
    {
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
    }

    public function unblock(Request $request)
    {
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
    }

    public function block(Request $request)
    {
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
    }
}