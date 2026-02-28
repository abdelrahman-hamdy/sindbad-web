<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestType;
use App\Http\Controllers\Controller;
use App\Http\Resources\RequestResource;
use App\Services\Odoo\OdooServiceInterface;
use Exception;
use Illuminate\Http\Request;

class CustomerHomeController extends Controller
{
    public function __construct(private OdooServiceInterface $odoo) {}

    public function home(Request $request)
    {
        $user = $request->user();
        $financials = null;
        $financialsError = null;

        if ($user->isCustomer() && $user->odoo_id) {
            try {
                $orders = $this->odoo->getCustomerOrders($user->odoo_id, $user->phone, $user->name);
                $financials = [
                    'orders' => $orders,
                    'summary' => [
                        'total_amount' => collect($orders)->sum('amount_total'),
                        'total_due' => collect($orders)->sum('amount_due'),
                    ],
                ];
            } catch (Exception $e) {
                $financialsError = 'تعذر تحميل البيانات المالية';
            }
        }

        $serviceRequests = $user->requests()
            ->with(['technician:id,name,phone'])
            ->where('type', RequestType::Service->value)
            ->latest()
            ->get();

        $installationRequests = $user->requests()
            ->with(['technician:id,name,phone'])
            ->where('type', RequestType::Installation->value)
            ->latest()
            ->get();

        $unread = $user->appNotifications()->whereNull('read_at')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'profile' => $user,
                'financials' => $financials,
                'financials_error' => $financialsError,
                'service_requests' => RequestResource::collection($serviceRequests),
                'installation_requests' => RequestResource::collection($installationRequests),
                'unread_notifications' => $unread,
            ],
        ]);
    }
}
