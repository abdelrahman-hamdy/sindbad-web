<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Http\Controllers\Controller;
use App\Http\Resources\RequestResource;
use App\Models\Request as InstallationRequest;
use App\Services\NotificationService;
use App\Services\RequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstallationController extends Controller
{
    public function __construct(
        private RequestService $requestService,
        private NotificationService $notificationService,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $query = InstallationRequest::with(['user', 'technician', 'rating'])
            ->where('type', RequestType::Installation->value);

        if ($user->isCustomer()) $query->where('user_id', $user->id);
        elseif ($user->isTechnician()) $query->where('technician_id', $user->id);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('id', 'like', "%$s%")->orWhere('invoice_number', 'like', "%$s%"));
        }
        if ($request->filled('status') && $request->status !== 'all') $query->where('status', $request->status);
        if ($request->filled('date_from')) $query->whereDate('scheduled_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('scheduled_at', '<=', $request->date_to);

        return response()->json(['success' => true, 'data' => RequestResource::collection($query->latest()->paginate(20))]);
    }

    public function show($id)
    {
        $req = InstallationRequest::with(['user', 'technician', 'rating'])->findOrFail($id);
        $techImages = $req->getMedia('technician_images')->map(fn($m) => [
            'id' => $m->id,
            'image_url' => $m->getUrl(),
            'uploaded_at' => $m->created_at->toISOString(),
        ]);
        return response()->json(['success' => true, 'data' => array_merge((new RequestResource($req))->toArray(request()), ['technician_images' => $techImages])]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_type' => 'required|string',
            'quantity' => 'nullable|integer|min:1',
            'is_site_ready' => 'required|accepted',
            'readiness_details' => 'nullable|array',
            'description' => 'nullable|string',
            'address' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'scheduled_at' => 'required|date',
            'end_date' => 'nullable|date',
            'invoice_number' => 'nullable|string',
        ]);

        try {
            $req = $this->requestService->createRequest($request->user(), $request->all(), RequestType::Installation);
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $req->addMedia($image)->toMediaCollection('attachments');
                }
            }
            return response()->json(['success' => true, 'message' => __('Request received successfully'), 'data' => new RequestResource($req->load(['user', 'technician']))], 201);
        } catch (\Exception $e) {
            Log::error('Create installation request: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => __('An unexpected error occurred')], 500);
        }
    }

    public function destroy($id)
    {
        $req = InstallationRequest::findOrFail($id);
        $this->authorize('delete', $req);
        $req->delete();
        return response()->json(['success' => true, 'message' => __('Deleted successfully')]);
    }

    public function updateStatus(Request $request, $id)
    {
        $req = InstallationRequest::findOrFail($id);
        $request->validate(['status' => 'required|string']);
        try {
            $newStatus = RequestStatus::from($request->status);
            $this->requestService->updateStatus($req, $newStatus, $request->user());
            $req->refresh();
            return response()->json(['success' => true, 'data' => $req]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function assignTechnician(Request $request, $id)
    {
        $req = InstallationRequest::findOrFail($id);
        $request->validate(['technician_id' => 'required|integer|exists:users,id']);
        $updated = $this->requestService->assignTechnician($req, $request->technician_id, [
            'scheduled_at' => $request->scheduled_at,
            'end_date' => $request->end_date,
        ]);
        return response()->json(['success' => true, 'data' => new RequestResource($updated)]);
    }

    public function submitRating(Request $request, $id)
    {
        $req = InstallationRequest::findOrFail($id);
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

    public function acceptRequest(Request $request)
    {
        $request->validate(['id' => 'required|integer|exists:requests,id']);
        $req = InstallationRequest::findOrFail($request->id);
        $req->update(['technician_accepted_at' => now()]);
        if ($req->user) {
            $this->notificationService->notifyUser(
                $req->user,
                __('Installation request accepted'),
                __('Your installation request #:id has been accepted.', ['id' => $req->id]),
                ['type' => 'installation_accepted', 'request_id' => (string) $req->id]
            );
        }
        return response()->json(['success' => true, 'message' => __('Request accepted')]);
    }

    public function updateReadiness(Request $request, $id)
    {
        $req = InstallationRequest::findOrFail($id);
        $request->validate(['is_site_ready' => 'required|boolean', 'readiness_details' => 'nullable|array']);
        $req->update($request->only(['is_site_ready', 'readiness_details']));
        return response()->json(['success' => true, 'data' => new RequestResource($req)]);
    }

    public function bulkDestroy(Request $request)
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => __('Unauthorized')], 403);
        }
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        InstallationRequest::where('type', RequestType::Installation->value)->whereIn('id', $request->ids)->delete();
        return response()->json(['success' => true, 'message' => __('Deleted successfully')]);
    }
}
