<?php

namespace App\Http\Middleware;

use App\Models\Request as ServiceRequest;
use Closure;
use Illuminate\Http\Request;

class PreventTechnicianCompleteWithoutRating
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->isTechnician()) {
            return $next($request);
        }

        $newStatus = $request->input('status');

        if ($newStatus !== 'completed') {
            return $next($request);
        }

        $id = $request->route('id') ?? $request->route('request');
        $model = ServiceRequest::find($id);

        if ($model && ! $model->hasRating()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إكمال الطلب قبل الحصول على تقييم العميل',
            ], 422);
        }

        return $next($request);
    }
}
