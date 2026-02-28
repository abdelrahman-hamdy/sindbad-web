<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Http\Controllers\Controller;
use App\Http\Resources\RequestResource;
use App\Models\Notification;
use App\Models\Request as ServiceRequest;
use App\Services\TechnicianLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TechnicianController extends Controller
{
    public function __construct(private TechnicianLocationService $locationService) {}

    public function home(Request $request): JsonResponse
    {
        $user = $request->user();

        $activeStatuses = [
            RequestStatus::Assigned->value,
            RequestStatus::OnWay->value,
            RequestStatus::InProgress->value,
        ];

        $serviceRequests = ServiceRequest::with(['user'])
            ->where('technician_id', $user->id)
            ->where('type', RequestType::Service->value)
            ->whereIn('status', $activeStatuses)
            ->latest()
            ->get();

        $installationRequests = ServiceRequest::with(['user'])
            ->where('technician_id', $user->id)
            ->where('type', RequestType::Installation->value)
            ->whereIn('status', $activeStatuses)
            ->latest()
            ->get();

        $unreadNotifications = Notification::where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'profile' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'avatar_url' => $user->avatar_url,
                ],
                'service_requests' => RequestResource::collection($serviceRequests),
                'installation_requests' => RequestResource::collection($installationRequests),
                'unread_notifications' => $unreadNotifications,
            ],
        ]);
    }

    public function updateLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'heading' => 'nullable|numeric',
            'speed' => 'nullable|numeric|min:0',
            'recorded_at' => 'nullable|date',
        ]);

        $user = $request->user();
        if (! $user->isTechnician()) {
            return response()->json(['message' => __('Unauthorized')], 403);
        }

        $this->locationService->updateLocation($user, $request->all());

        return response()->noContent();
    }

    public function goOffline(Request $request)
    {
        $user = $request->user();
        if (! $user->isTechnician()) {
            return response()->json(['message' => __('Unauthorized')], 403);
        }

        $this->locationService->markOffline($user);

        return response()->noContent();
    }

    public function uploadImages(Request $request)
    {
        $request->validate([
            'request_id' => 'required|integer|exists:requests,id',
            'images' => 'required|array',
            'images.*' => 'image|max:5120',
            'notes' => 'nullable|string',
        ]);

        $req = \App\Models\Request::findOrFail($request->request_id);

        foreach ($request->file('images') as $image) {
            $req->addMedia($image)
                ->withCustomProperties(['notes' => $request->notes, 'technician_id' => $request->user()->id])
                ->toMediaCollection('technician_images');
        }

        return response()->json(['success' => true, 'message' => __('Images uploaded')]);
    }

    public function getImages(Request $request)
    {
        $request->validate(['request_id' => 'required|integer|exists:requests,id']);
        $req = \App\Models\Request::findOrFail($request->request_id);

        $images = $req->getMedia('technician_images')->map(fn($m) => [
            'id' => $m->id,
            'image_url' => $m->getUrl(),
            'notes' => $m->getCustomProperty('notes'),
            'technician_id' => $m->getCustomProperty('technician_id'),
            'uploaded_at' => $m->created_at->toISOString(),
        ]);

        return response()->json(['success' => true, 'data' => $images]);
    }

    public function deleteImage($id)
    {
        $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::findOrFail($id);
        $media->delete();
        return response()->json(['success' => true, 'message' => __('Deleted successfully')]);
    }
}
