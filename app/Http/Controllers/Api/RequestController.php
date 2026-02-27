<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Http\Controllers\Controller;
use App\Http\Resources\RequestResource;
use App\Models\AppSetting;
use App\Models\Request as ServiceRequest;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\Odoo\OdooServiceInterface;
use App\Services\RequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RequestController extends Controller
{
    public function __construct(
        private RequestService $requestService,
        private NotificationService $notificationService,
        private OdooServiceInterface $odoo,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $query = ServiceRequest::with(['user', 'technician', 'rating'])
            ->where('type', RequestType::Service->value);

        if ($user->isCustomer()) {
            $query->where('user_id', $user->id);
        } elseif ($user->isTechnician()) {
            $query->where('technician_id', $user->id);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('id', 'like', "%$s%")
                ->orWhere('invoice_number', 'like', "%$s%")
                ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%"))
            );
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) $query->whereDate('scheduled_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('scheduled_at', '<=', $request->date_to);

        return response()->json([
            'success' => true,
            'data' => RequestResource::collection($query->latest()->paginate(20)),
        ]);
    }

    public function show($id)
    {
        $req = ServiceRequest::with(['user', 'technician', 'rating'])->findOrFail($id);
        $this->authorize('view', $req);
        return response()->json(['success' => true, 'data' => new RequestResource($req)]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'service_type' => 'required|string',
            'description' => 'nullable|string',
            'details' => 'nullable|array',
            'address' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'scheduled_at' => 'required|date',
            'end_date' => 'nullable|date',
            'invoice_number' => 'nullable|string',
        ]);

        try {
            $req = $this->requestService->createRequest($request->user(), $request->all(), RequestType::Service);
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $req->addMedia($image)->toMediaCollection('attachments');
                }
            }
            return response()->json(['success' => true, 'message' => __('Request received successfully'), 'data' => new RequestResource($req->load(['user', 'technician']))], 201);
        } catch (\Exception $e) {
            Log::error('Create service request: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => __('An unexpected error occurred')], 500);
        }
    }

    public function destroy($id)
    {
        $req = ServiceRequest::findOrFail($id);
        $this->authorize('delete', $req);
        $req->delete();
        return response()->json(['success' => true, 'message' => __('Deleted successfully')]);
    }

    public function updateStatus(Request $request, $id)
    {
        $req = ServiceRequest::findOrFail($id);
        $request->validate(['status' => 'required|string']);

        try {
            $newStatus = RequestStatus::from($request->status);
            $this->requestService->updateStatus($req, $newStatus, $request->user());
            return response()->json(['success' => true, 'message' => __('Status updated')]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function assignTechnician(Request $request, $id)
    {
        $req = ServiceRequest::findOrFail($id);
        $request->validate(['technician_id' => 'required|integer|exists:users,id']);

        $updated = $this->requestService->assignTechnician($req, $request->technician_id, [
            'scheduled_at' => $request->scheduled_at,
            'end_date' => $request->end_date,
            'task_start_time' => $request->task_start_time,
            'task_end_time' => $request->task_end_time,
        ]);

        return response()->json(['success' => true, 'data' => new RequestResource($updated)]);
    }

    public function submitRating(Request $request, $id)
    {
        $req = ServiceRequest::findOrFail($id);
        $request->validate([
            'product_rating' => 'nullable|integer|min:1|max:5',
            'service_rating' => 'nullable|integer|min:1|max:5',
            'how_found_us' => 'nullable|string',
            'customer_notes' => 'nullable|string',
        ]);

        if ($req->hasRating()) {
            return response()->json(['success' => false, 'message' => __('Already rated')], 422);
        }

        $rating = $req->rating()->create(array_merge($request->only(['product_rating', 'service_rating', 'how_found_us', 'customer_notes']), ['user_id' => $request->user()->id]));

        if ($request->hasFile('image')) {
            $rating->addMedia($request->file('image'))->toMediaCollection('rating_images');
        }

        return response()->json(['success' => true, 'message' => __('Thank you for your rating')]);
    }

    public function addAttachment(Request $request, $id)
    {
        $req = ServiceRequest::findOrFail($id);
        $request->validate(['images' => 'required|array', 'images.*' => 'image|max:5120']);

        foreach ($request->file('images') as $image) {
            $req->addMedia($image)->toMediaCollection('attachments');
        }

        return response()->json(['success' => true, 'message' => __('Images uploaded')]);
    }

    public function acceptRequest(Request $request)
    {
        $request->validate(['id' => 'required|integer|exists:requests,id']);
        $req = ServiceRequest::findOrFail($request->id);

        $req->update(['status' => RequestStatus::OnWay->value, 'technician_accepted_at' => now()]);

        if ($req->user) {
            $this->notificationService->notifyUser(
                $req->user,
                __('Request accepted'),
                __('Your request #:id has been accepted by the technician and they are on their way.', ['id' => $req->id]),
                ['type' => 'request_accepted', 'request_id' => (string) $req->id]
            );
        }

        return response()->json(['success' => true, 'message' => __('Request accepted')]);
    }

    public function getMyOrders(Request $request)
    {
        $user = $request->user();
        $partner = $this->odoo->findCustomerByPhoneOrName($user->phone, $user->name);

        if (! $partner) {
            return response()->json(['success' => true, 'message' => __('No linked Odoo account'), 'data' => []]);
        }

        $orders = $this->odoo->getCustomerOrders($partner['id'], $user->phone, $user->name);

        $formatted = array_map(fn($o) => [
            'id' => $o['id'],
            'invoice_number' => $o['name'],
            'date' => $o['date_order'],
            'quotation_template' => is_array($o['sale_order_template_id']) ? $o['sale_order_template_id'][1] : null,
            'total' => $o['amount_total'],
        ], $orders);

        return response()->json(['success' => true, 'data' => $formatted]);
    }

    public function checkEligibility(Request $request)
    {
        $user = $request->user();
        $eligible = true;
        $reasons = [];
        $amountDue = 0;

        // Check pending requests block
        if (AppSetting::bool('block_pending_requests') && $user->isCustomer()) {
            $activeStatuses = [
                RequestStatus::Pending->value,
                RequestStatus::Assigned->value,
                RequestStatus::OnWay->value,
                RequestStatus::InProgress->value,
            ];
            if (ServiceRequest::where('user_id', $user->id)->whereIn('status', $activeStatuses)->exists()) {
                $eligible = false;
                $reasons[] = __('You have an active request. Please complete it before creating a new one.');
            }
        }

        // Check financial eligibility
        if (AppSetting::bool('enforce_financial_eligibility') && $user->isCustomer()) {
            $partner = $user->odoo_id
                ? ['id' => $user->odoo_id]
                : $this->odoo->findCustomerByPhoneOrName($user->phone, $user->name);

            if ($partner) {
                $debt = $this->odoo->getCustomerDebt($partner['id']);
                if ($debt > 0) {
                    $eligible = false;
                    $amountDue = $debt;
                    $reasons[] = __('You have outstanding dues: :amount OMR', ['amount' => $debt]);
                }
            }
        }

        return response()->json([
            'success'    => true,
            'eligible'   => $eligible,
            'reasons'    => $reasons,
            'amount_due' => $amountDue,
        ]);
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        $this->authorize('delete', ServiceRequest::class);
        ServiceRequest::whereIn('id', $request->ids)->delete();
        return response()->json(['success' => true, 'message' => __('Deleted')]);
    }

    public function getMySchedule(Request $request)
    {
        $user = $request->user();
        if (! $user->isTechnician()) {
            return response()->json(['message' => __('Unauthorized')], 403);
        }

        $requests = ServiceRequest::with(['user'])
            ->where('technician_id', $user->id)
            ->whereIn('status', [RequestStatus::Assigned->value, RequestStatus::OnWay->value, RequestStatus::InProgress->value])
            ->get()
            ->map(fn($r) => [
                'id'                     => $r->id,
                'type'                   => $r->type->value,
                'invoice_number'         => $r->invoice_number,
                'start_date'             => $r->scheduled_at?->format('Y-m-d'),
                'end_date'               => $r->end_date?->format('Y-m-d'),
                'status'                 => $r->status->value,
                'address'                => $r->address,
                'latitude'               => $r->latitude,
                'longitude'              => $r->longitude,
                'service_type'           => $r->service_type?->value,
                'description'            => $r->description,
                'product_type'           => $r->product_type,
                'quantity'               => $r->quantity,
                'technician_accepted_at' => $r->technician_accepted_at?->toIso8601String(),
            ]);

        return response()->json(['success' => true, 'data' => $requests]);
    }
}
