<?php

namespace App\Observers;

use App\Enums\RequestStatus;
use App\Models\Request;
use App\Services\NotificationService;

class RequestObserver
{
    public function __construct(private NotificationService $notificationService) {}

    public function created(Request $request): void
    {
        // Notifications handled by RequestService::createRequest()
    }

    public function updated(Request $request): void
    {
        // Safety net: ensure completed_at is set even if updated outside the service layer
        if ($request->isDirty('status')
            && $request->status === RequestStatus::Completed
            && ! $request->completed_at) {
            $request->timestamps = false;
            $request->updateQuietly(['completed_at' => now()]);
        }
        // Status change and assignment notifications are handled by RequestService
    }
}
