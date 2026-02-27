<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\RequestResource;
use App\Http\Resources\UserResource;
use App\Models\ManualOrder;
use App\Models\Request as ServiceRequest;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\Odoo\OdooServiceInterface;
use App\Services\ReportService;
use App\Services\RequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
        private ReportService $reportService,
        private RequestService $requestService,
        private OdooServiceInterface $odoo,
    ) {}

    public function dashboardStats(Request $request): JsonResponse
    {
        $base = fn($type) => ServiceRequest::where('type', $type);

        return response()->json(['success' => true, 'data' => [
            'service' => [
                'total'       => $base('service')->count(),
                'pending'     => $base('service')->where('status', 'pending')->count(),
                'assigned'    => $base('service')->where('status', 'assigned')->count(),
                'in_progress' => $base('service')->where('status', 'in_progress')->count(),
                'completed'   => $base('service')->where('status', 'completed')->count(),
                'canceled'    => $base('service')->where('status', 'canceled')->count(),
            ],
            'installation' => [
                'total'     => $base('installation')->count(),
                'pending'   => $base('installation')->where('status', 'pending')->count(),
                'completed' => $base('installation')->where('status', 'completed')->count(),
            ],
            'technicians'        => User::technicians()->count(),
            'active_technicians' => User::technicians()->active()->count(),
            'customers'          => User::customers()->count(),
            'today_completed'    => ServiceRequest::whereDate('completed_at', today())->where('status', 'completed')->count(),
        ]]);
    }

    public function getUsers(Request $request): JsonResponse
    {
        $query = User::query();
        if ($request->filled('role')) $query->where('role', $request->role);
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")->orWhere('email', 'like', "%$s%"));
        }

        $users = $query->latest()->get();

        if ($request->role === 'technician') {
            $users->each(function ($user) {
                $user->total_requests = ServiceRequest::where('technician_id', $user->id)->count();
                $user->completed_requests = ServiceRequest::where('technician_id', $user->id)->where('status', RequestStatus::Completed->value)->count();
            });
        }

        return response()->json(['success' => true, 'data' => UserResource::collection($users)]);
    }

    public function getUserDetails(Request $request, $id): JsonResponse
    {
        $user = User::with([
            'requests'         => fn($q) => $q->latest()->limit(10),
            'assignedRequests' => fn($q) => $q->with('user')->latest()->limit(10),
            'manualOrders',
        ])->findOrFail($id);

        $odooData = [];
        if ($user->odoo_id) {
            try {
                $partner = $this->odoo->findCustomerByPhoneOrName($user->phone, $user->name);
                if ($partner) {
                    $odooData = [
                        'partner_id' => $partner['id'],
                        'orders'     => $this->odoo->getCustomerOrders($partner['id'], $user->phone, $user->name),
                        'debt'       => $this->odoo->getCustomerDebt($partner['id']),
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Odoo lookup failed for user ' . $id . ': ' . $e->getMessage());
            }
        }

        return response()->json(['success' => true, 'data' => [
            'user'              => new UserResource($user),
            'requests'          => RequestResource::collection($user->requests),
            'assigned_requests' => RequestResource::collection($user->assignedRequests),
            'manual_orders'     => $user->manualOrders,
            'odoo'              => $odooData,
        ]]);
    }

    public function storeUser(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'phone'    => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6',
            'role'     => 'required|string|in:admin,technician,customer',
            'email'    => 'nullable|email|unique:users,email',
            'odoo_id'  => 'nullable|integer',
            'orders'                       => 'nullable|array',
            'orders.*.invoice_number'      => 'required|string',
            'orders.*.quotation_template'  => 'nullable|string',
            'orders.*.total_amount'        => 'required|numeric',
            'orders.*.paid_amount'         => 'required|numeric',
            'orders.*.remaining_amount'    => 'nullable|numeric',
            'orders.*.status'              => 'required|string|in:paid,partial',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'phone'     => $request->phone,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
            'odoo_id'   => $request->odoo_id,
            'is_active' => true,
        ]);

        $user->assignRole($request->role);

        foreach ($request->orders ?? [] as $order) {
            $user->manualOrders()->create(array_merge(['order_date' => now()->toDateString()], $order));
        }

        return response()->json(['success' => true, 'message' => __('User created successfully'), 'data' => new UserResource($user->load('manualOrders'))], 201);
    }

    public function deleteUser(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);
        if ($user->id === $request->user()->id) return response()->json(['success' => false, 'message' => __('Cannot delete your own account')], 422);
        $user->delete();
        return response()->json(['success' => true, 'message' => __('Deleted successfully')]);
    }

    public function updateUser(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $request->validate([
            'name'      => 'sometimes|string|max:255',
            'phone'     => 'sometimes|string|unique:users,phone,' . $id,
            'role'      => 'sometimes|string|in:admin,technician,customer',
            'is_active' => 'sometimes|boolean',
            'password'  => 'nullable|string|min:6',
        ]);
        if ($request->filled('password')) $request->merge(['password' => Hash::make($request->password)]);
        $user->update($request->only(['name', 'phone', 'email', 'role', 'is_active', 'password', 'odoo_id']));
        if ($request->filled('role')) {
            $user->syncRoles([$request->role]);
        }
        return response()->json(['success' => true, 'data' => new UserResource($user)]);
    }

    public function bulkDeleteUsers(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        User::whereIn('id', $request->ids)->where('id', '!=', $request->user()->id)->delete();
        return response()->json(['success' => true, 'message' => __('Deleted successfully')]);
    }

    public function lookupUserByPhone(Request $request, $phone): JsonResponse
    {
        $user    = User::with('manualOrders')->where('phone', $phone)->first();
        $partner = null;

        try {
            $partner = $this->odoo->findCustomerByPhoneOrName($phone, null);
        } catch (\Exception $e) {
            Log::warning('Odoo lookup failed for phone ' . $phone . ': ' . $e->getMessage());
        }

        if (! $user && ! $partner) {
            return response()->json(['success' => false, 'message' => __('User not found')], 404);
        }

        $odooData = ['linked' => false, 'orders' => []];
        if ($partner) {
            try {
                $orders = $this->odoo->getCustomerOrders($partner['id'], $phone, $user?->name);
                $odooData = [
                    'linked'     => true,
                    'partner_id' => $partner['id'],
                    'orders'     => array_map(fn($o) => [
                        'id'                 => $o['id'],
                        'name'               => $o['name'],
                        'quotation_template' => is_array($o['sale_order_template_id']) ? $o['sale_order_template_id'][1] : null,
                        'date'               => $o['date_order'],
                        'amount_total'       => $o['amount_total'],
                    ], $orders),
                ];
            } catch (\Exception $e) {
                Log::warning('Odoo orders fetch failed: ' . $e->getMessage());
            }
        }

        // Fall back to manual orders if no Odoo orders found
        if ($user && $user->manualOrders->isNotEmpty() && empty($odooData['orders'])) {
            $odooData['linked']  = true;
            $odooData['orders']  = $user->manualOrders->map(fn($o) => [
                'id'                 => 'manual_' . $o->id,
                'name'               => $o->invoice_number,
                'quotation_template' => $o->quotation_template,
                'date'               => $o->created_at->format('Y-m-d H:i:s'),
                'amount_total'       => $o->total_amount,
                'amount_residual'    => $o->remaining_amount,
                'is_manual'          => true,
            ])->toArray();
        }

        $userData = $user
            ? new UserResource($user)
            : ['id' => null, 'name' => $partner['name'], 'phone' => $phone, 'role' => 'customer', 'is_odoo_only' => true];

        return response()->json(['success' => true, 'data' => ['user' => $userData, 'odoo' => $odooData]]);
    }

    public function sendCustomNotification(Request $request): JsonResponse
    {
        $request->validate(['title' => 'required|string', 'body' => 'required|string', 'user_id' => 'nullable|integer|exists:users,id']);

        if ($request->filled('user_id')) {
            $user = User::findOrFail($request->user_id);
            $this->notificationService->notifyUser($user, $request->title, $request->body, ['type' => 'custom']);
        } else {
            $this->notificationService->notifyRole('customer', $request->title, $request->body, ['type' => 'custom']);
        }

        return response()->json(['success' => true, 'message' => __('Notification sent successfully')]);
    }

    public function createServiceRequest(Request $request): JsonResponse
    {
        $request->validate(['user_id' => 'required|integer|exists:users,id', 'service_type' => 'required|string', 'address' => 'required|string', 'latitude' => 'required|numeric', 'longitude' => 'required|numeric', 'scheduled_at' => 'required|date']);
        $owner = User::findOrFail($request->user_id);
        $req = $this->requestService->createRequest($owner, $request->all(), RequestType::Service);
        return response()->json(['success' => true, 'data' => new RequestResource($req->load(['user', 'technician']))], 201);
    }

    public function createInstallationRequest(Request $request): JsonResponse
    {
        $request->validate(['user_id' => 'required|integer|exists:users,id', 'product_type' => 'required|string', 'address' => 'required|string', 'latitude' => 'required|numeric', 'longitude' => 'required|numeric', 'scheduled_at' => 'required|date']);
        $owner = User::findOrFail($request->user_id);
        $req = $this->requestService->createRequest($owner, $request->all(), RequestType::Installation);
        return response()->json(['success' => true, 'data' => new RequestResource($req->load(['user', 'technician']))], 201);
    }

    public function getAvailableTechnicians(Request $request): JsonResponse
    {
        $technicians = User::technicians()->active()->get();
        if ($request->filled('start') && $request->filled('end')) {
            $technicians = $technicians->filter(fn($t) => $this->requestService->checkTechnicianAvailability($t->id, $request->start, $request->end));
        }
        return response()->json(['success' => true, 'data' => UserResource::collection($technicians->values())]);
    }

    public function getOdooProducts(Request $request): JsonResponse
    {
        try {
            $products = $this->odoo->getProducts(100);
            return response()->json(['success' => true, 'data' => $products]);
        } catch (\Exception $e) {
            Log::warning('Odoo products fetch failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => __('Failed to fetch products')], 503);
        }
    }

    public function getPerformanceReports(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->reportService->getPerformanceSummary()]);
    }

    public function getDailyCompletedRequests(Request $request): JsonResponse
    {
        $date = $request->date ?? now()->format('Y-m-d');
        return response()->json(['success' => true, 'data' => RequestResource::collection($this->reportService->getDailyCompleted($date))]);
    }

    public function getRatings(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->reportService->getRatingStats()]);
    }
}
