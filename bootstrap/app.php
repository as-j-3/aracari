<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\Access\AuthorizationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        \App\Providers\AuthServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'branch.permission' => \App\Http\Middleware\BranchPermissionMiddleware::class,
            'require.admin' => \App\Http\Middleware\RequireAdmin::class,
            'require.provider' => \App\Http\Middleware\RequireProvider::class,
            'phone.verified' => \App\Http\Middleware\EnsurePhoneForBooking::class,
        ]);

        $middleware->append([
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\AntiAbuse::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\MaintenanceModeMiddleware::class,
        ]);
        $middleware->api(append: [
            \App\Http\Middleware\MaintenanceModeMiddleware::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle Throttle Requests
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, \Illuminate\Http\Request $request) {
            $path = trim($request->path(), '/');
            $message = match (true) {
                str_contains($path, 'forgot-password') => 'تم تقييد طلبات استعادة كلمة المرور مؤقتاً. يرجى المحاولة بعد قليل.',
                str_contains($path, 'verify') => 'تم تقييد محاولات التحقق مؤقتاً بسبب كثرة المحاولات. يرجى الانتظار قليلاً.',
                str_contains($path, 'reset-password') => 'تم تقييد محاولات إعادة تعيين كلمة المرور مؤقتاً. يرجى المحاولة بعد قليل.',
                str_contains($path, 'login') => 'تم حظر محاولات الدخول مؤقتاً بسبب المحاولات المتكررة. يرجى المحاولة بعد دقيقة.',
                str_contains($path, 'register') => 'تم تقييد محاولات التسجيل مؤقتاً بسبب كثرة الطلبات. يرجى المحاولة بعد قليل.',
                default => 'تم تقييد هذا الإجراء مؤقتاً بسبب كثرة الطلبات. يرجى المحاولة بعد قليل.',
            };

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'code' => 429,
                ], 429);
            }

            return redirect()->back()
                ->with('error', $message)
                ->withInput();
        });

        // Hide SQL errors from API (SECURITY FIX)
        $exceptions->render(function (\Illuminate\Database\QueryException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                // Log the real error for debugging
                \Illuminate\Support\Facades\Log::error('Database error', [
                    'error' => $e->getMessage(),
                    'sql' => $e->getSql(),
                    'bindings' => $e->getBindings(),
                ]);

                // Return generic message to user
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ في النظام. يرجى المحاولة لاحقاً أو التواصل مع الدعم.',
                    'code' => 500,
                ], 500);
            }
        });

        // Handle CSRF Token Mismatch (419 Page Expired) - Show custom view for web
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'انتهت صلاحية الجلسة. يرجى تحديث الصفحة والمحاولة مرة أخرى.',
                    'code' => 419,
                ], 419);
            }

            // Return custom 419 view for web requests
            return response()->view('errors.419', [], 419);
        });

        // General exception handler for API (hide internal details)
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                // Don't hide validation errors (422)
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'هناك بيانات ناقصه او غير صحيحه الرجاء التاكد',
                        'errors' => $e->validator->errors(),
                        'code' => 422,
                    ], 422);
                }

                // Don't hide model not found (404)
                if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'المورد المطلوب غير موجود.',
                        'code' => 404,
                    ], 404);
                }

                // Don't hide authentication errors (401)
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'يرجى تسجيل الدخول للوصول لهذا المورد.',
                        'code' => 401,
                    ], 401);
                }

                // Don't hide authorization errors (403)
                if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ليس لديك صلاحية للوصول لهذا المورد.',
                        'code' => 403,
                    ], 403);
                }

                // Log unknown errors
                \Illuminate\Support\Facades\Log::error('API Exception', [
                    'error' => $e->getMessage(),
                    'class' => get_class($e),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Return generic error for any other exception
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ غير متوقع. يرجى المحاولة لاحقاً.',
                    'code' => 500,
                ], 500);
            }
        });
                $exceptions->render(function (AuthorizationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا تملك صلاحية الوصول لهذا الفرع',
                ], 403);
            }

            return redirect()->route('provider.prodashboard.index')
                ->with('error', 'لا تملك صلاحية الوصول لهذا الفرع');
        });
    })->create();
