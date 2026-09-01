<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireAdmin
{

    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        return $next($request);
    }
}