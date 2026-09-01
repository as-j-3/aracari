<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    protected function success($data = null, string $message = '', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    protected function error(string $message = '', $errors = null, int $statusCode = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    protected function paginated(LengthAwarePaginator $paginator, $resourceOrMessage = null, ?string $message = null): JsonResponse
    {
        $numArgs = func_num_args();

        if ($numArgs === 2) {
            $message = $resourceOrMessage;
            $resourceClass = null;
        } elseif ($numArgs === 3) {
            $resourceClass = $resourceOrMessage;
        } else {
            $message = 'تم جلب البيانات بنجاح';
            $resourceClass = null;
        }

        $items = $paginator->items();

        if ($resourceClass) {
            $items = array_map(fn ($item) => new $resourceClass($item), $items);
        }

        return response()->json([
            'success' => true,
            'message' => $message ?: 'تم جلب البيانات بنجاح',
            'data' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    protected function notFound(string $message = ''): JsonResponse
    {
        return $this->error($message ?: 'المورد غير موجود', null, 404);
    }

    protected function unauthorized(string $message = ''): JsonResponse
    {
        return $this->error($message ?: 'غير مصرح لك', null, 401);
    }

    protected function forbidden(string $message = ''): JsonResponse
    {
        return $this->error($message ?: 'وصول مرفوض', null, 403);
    }

    protected function validationError($errors, string $message = ''): JsonResponse
    {
        return $this->error($message ?: 'فشل التحقق من البيانات', $errors, 422);
    }

    protected function serverError(string $message = ''): JsonResponse
    {
        \Log::error('Server error occurred', [
            'message' => $message ?: 'خطأ في الخادم',
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
        ]);

        return $this->error($message ?: 'خطأ في الخادم', null, 500);
    }
}
