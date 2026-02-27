<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && ! $request->user()->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'الحساب غير مفعل',
            ], 403);
        }

        return $next($request);
    }
}
