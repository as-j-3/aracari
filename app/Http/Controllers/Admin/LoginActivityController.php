<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginActivity\CleanupLoginActivityRequest;
use App\Http\Requests\Api\LoginActivity\FilterLoginActivityRequest;
use App\Http\Resources\Admin\LoginActivityResource;
use App\Models\LoginActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginActivityController extends Controller
{
    public function __construct()
    {
        $this->middleware('require.admin');
    }

    /**
     * عرض قائمة سجلات الدخول مع إحصائيات.
     */
    public function index(FilterLoginActivityRequest $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Old Web implementation - kept for reference
        |--------------------------------------------------------------------------
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
        */

        $perPage = $request->validated('per_page', 10);

        $query = LoginActivity::with('user')->latest();

        if ($request->filled('status'))     $query->where('status', $request->validated('status'));
        if ($request->filled('date_from'))  $query->whereDate('created_at', '>=', $request->validated('date_from'));
        if ($request->filled('date_to'))    $query->whereDate('created_at', '<=', $request->validated('date_to'));
        if ($request->filled('user_id'))    $query->where('user_id', $request->validated('user_id'));
        if ($request->filled('identifier')) $query->where('identifier', 'like', '%' . $request->validated('identifier') . '%');

        $loginActivities = $query->paginate($perPage);

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

        return $this->success([
            'login_activities' => LoginActivityResource::collection($loginActivities->items()),
            'pagination' => [
                'current_page' => $loginActivities->currentPage(),
                'last_page'    => $loginActivities->lastPage(),
                'per_page'     => $loginActivities->perPage(),
                'total'        => $loginActivities->total(),
            ],
            'stats' => [
                'total'      => $stats->total ?? 0,
                'success'    => $stats->success ?? 0,
                'failed'     => $stats->failed ?? 0,
                'today'      => $stats->today ?? 0,
                'this_week'  => $stats->this_week ?? 0,
                'this_month' => $stats->this_month ?? 0,
            ],
        ], 'تم جلب سجلات الدخول بنجاح');
    }

    /**
     * عرض تفاصيل سجل دخول معين.
     */
    public function show(LoginActivity $loginActivity): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Old Web implementation - kept for reference
        |--------------------------------------------------------------------------
        $loginActivity->load('user');

        return response()->json([
            'success' => true,
            'data' => $loginActivity
        ]);
        */

        $loginActivity->load('user');

        return $this->success(
            new LoginActivityResource($loginActivity),
            'تم جلب تفاصيل سجل الدخول بنجاح'
        );
    }

    /**
     * تصدير سجلات الدخول بصيغة CSV.
     */
    public function export(Request $request): JsonResponse
    {
        $query = LoginActivity::with('user')->limit(10000);

        if ($request->filled('status'))     $query->where('status', $request->status);
        if ($request->filled('date_from'))  $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))    $query->whereDate('created_at', '<=', $request->date_to);

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

        return $this->success($csvData, 'تم تصدير البيانات بنجاح');
    }

    /**
     * حذف سجلات الدخول القديمة.
     */
    public function cleanup(CleanupLoginActivityRequest $request): JsonResponse
    {
        $days = max(30, (int) $request->validated('days', 90));

        $deleted = LoginActivity::where('created_at', '<', now()->subDays($days))
            ->delete();

        return $this->success(null, "تم حذف {$deleted} سجل دخول قديم");
    }
}
