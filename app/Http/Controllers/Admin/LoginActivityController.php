<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginActivity;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class LoginActivityController extends Controller
{
    public function __construct()
    {
        $this->middleware('require.admin');
    }

    public function index(Request $request): View
    {
        $query = LoginActivity::with('user')->latest();

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date_to);
        if ($request->filled('user_id')) $query->where('user_id', $request->user_id);
        if ($request->filled('identifier')) $query->where('identifier', 'like', '%' . $request->identifier . '%');

        $loginActivities = $query->paginate(10);

        $stats = LoginActivity::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today,
            SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as this_week,
            SUM(CASE WHEN MONTH(created_at) = ? AND YEAR(created_at) = ? THEN 1 ELSE 0 END) as this_month
        ", [
            now()->startOfWeek(), 
            now()->endOfWeek(), 
            now()->month, 
            now()->year
        ])->first();

        $statsArray = [
            'total' => $stats->total ?? 0,
            'success' => $stats->success ?? 0,
            'failed' => $stats->failed ?? 0,
            'today' => $stats->today ?? 0,
            'this_week' => $stats->this_week ?? 0,
            'this_month' => $stats->this_month ?? 0,
        ];

        return view('admin.login-activities.index', compact('loginActivities', 'statsArray'));
    }

    public function show(LoginActivity $loginActivity): JsonResponse
    {
        $loginActivity->load('user');
        
        return response()->json([
            'success' => true,
            'data' => $loginActivity
        ]);
    }


    public function export(Request $request): JsonResponse
    {
        $query = LoginActivity::with('user')->limit(10000);

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date_to);

        $csvData = [];
        $csvData[] = ['ID', 'المستخدم', 'المعرف', 'الحالة', 'IP Address', 'User Agent', 'التاريخ'];

        $query->orderBy('id', 'desc')->chunkById(1000, function ($activities) use (&$csvData) {
            foreach ($activities as $activity) {
                $csvData[] = [
                    $activity->id,
                    $activity->user ? $activity->user->name : 'غير معروف',
                    $activity->identifier,
                    $activity->status === 'success' ? 'نجح' : 'فشل',
                    $activity->ip_address,
                    $activity->user_agent,
                    $activity->created_at->format('Y-m-d H:i:s')
                ];
            }
        });

        return response()->json([
            'success' => true,
            'data' => $csvData
        ]);
    }


    public function cleanup(Request $request): JsonResponse
    {
        $days = max(30, (int) $request->get('days', 90));
        
        $deleted = LoginActivity::where('created_at', '<', now()->subDays($days))
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "تم حذف {$deleted} سجل دخول قديم"
        ]);
    }
}
