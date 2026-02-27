<?php

namespace App\Http\Middleware;

use App\Enums\RequestStatus;
use App\Models\AppSetting;
use App\Models\Request;
use Closure;
use Illuminate\Http\Request as HttpRequest;

class CheckPendingRequests
{
    public function handle(HttpRequest $request, Closure $next): mixed
    {
        if (! AppSetting::bool('block_pending_requests')) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user || ! $user->isCustomer()) {
            return $next($request);
        }

        $activeStatuses = [
            RequestStatus::Pending->value,
            RequestStatus::Assigned->value,
            RequestStatus::OnWay->value,
            RequestStatus::InProgress->value,
        ];

        $hasActive = Request::where('user_id', $user->id)
            ->whereIn('status', $activeStatuses)
            ->exists();

        if ($hasActive) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك إنشاء طلب جديد بينما لديك طلب نشط آخر. يرجى إتمام طلبك الحالي أولاً.',
            ], 400);
        }

        return $next($request);
    }
}
