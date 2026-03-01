<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Models\Request;
use App\Models\User;
use Exception;
use App\Services\BookingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RequestService
{
    public function __construct(
        private NotificationService $notification,
        private \App\Services\Odoo\OdooServiceInterface $odoo,
    ) {}

    public function createRequest(User $creator, array $data, RequestType $type): Request
    {
        $prefix = $type === RequestType::Service
            ? config('sindbad.request_prefix_service')
            : config('sindbad.request_prefix_installation');

        return DB::transaction(function () use ($creator, $data, $type, $prefix) {
            $req = Request::create(array_merge($data, [
                'type' => $type->value,
                'user_id' => $creator->id,
                'status' => RequestStatus::Pending->value,
                'invoice_number' => isset($data['invoice_number'])
                    ? $prefix . ltrim($data['invoice_number'], 'TB-')
                    : null,
            ]));

            $typeLabel = $type === RequestType::Service ? __('Maintenance') : __('Installation');
            $this->notification->notifyAdmins(
                __('New Request'),
                __('New :type request #:id from :name', ['type' => $typeLabel, 'id' => $req->id, 'name' => $creator->name]),
                ['type' => 'new_request', 'request_id' => (string) $req->id, 'request_type' => $type->value]
            );

            return $req;
        });
    }

    public function assignTechnician(Request $request, int $technicianId, array $timing = []): Request
    {
        // Run booking validation when precise datetime slots are provided
        if (! empty($timing['scheduled_start_at']) && ! empty($timing['scheduled_end_at'])) {
            $start = \Carbon\Carbon::parse($timing['scheduled_start_at']);
            $end   = \Carbon\Carbon::parse($timing['scheduled_end_at']);
            $type  = $request->type instanceof \App\Enums\RequestType
                ? $request->type->value
                : (string) $request->type;

            app(BookingService::class)->validateAssignment($technicianId, $start, $end, $type);
        }

        DB::transaction(function () use ($request, $technicianId, $timing) {
            $request->update(array_merge([
                'technician_id' => $technicianId,
                'status' => RequestStatus::Assigned->value,
            ], array_filter($timing)));
        });

        $request->load(['user', 'technician']);

        if ($request->technician) {
            $this->notification->notifyUser(
                $request->technician,
                __('You have been assigned to a request'),
                __('You have been assigned to request #:id', ['id' => $request->id]),
                ['type' => 'request_assigned', 'request_id' => (string) $request->id, 'request_type' => $request->type]
            );
        }

        if ($request->user) {
            $this->notification->notifyUser(
                $request->user,
                __('A technician has been assigned to your request'),
                __('A technician has been assigned to your request #:id', ['id' => $request->id]),
                ['type' => 'technician_assigned', 'request_id' => (string) $request->id, 'request_type' => $request->type]
            );
        }

        return $request;
    }

    public function updateStatus(Request $request, RequestStatus $newStatus, User $actor): Request
    {
        // Block technician from completing without rating
        if ($newStatus === RequestStatus::Completed
            && $actor->isTechnician()
            && ! $request->hasRating()
        ) {
            throw new Exception(__('Cannot complete the request before receiving client rating'));
        }

        // Prevent a technician from having more than one on_way task simultaneously
        if ($newStatus === RequestStatus::OnWay && $request->technician_id) {
            $alreadyOnWay = Request::where('technician_id', $request->technician_id)
                ->where('id', '!=', $request->id)
                ->where('status', RequestStatus::OnWay->value)
                ->exists();

            if ($alreadyOnWay) {
                throw new Exception(__('لديك طلب آخر في الطريق بالفعل. يرجى إنهاؤه أو إلغاؤه أولاً'));
            }
        }

        $updateData = ['status' => $newStatus->value];
        if ($newStatus === RequestStatus::Completed) {
            $updateData['completed_at'] = now();
        }
        $request->update($updateData);

        Cache::forget('dashboard_stats_' . today()->toDateString());
        Cache::forget('performance_reports_' . today()->toDateString());

        $request->load(['user', 'technician']);

        if ($request->user) {
            if ($newStatus === RequestStatus::InProgress) {
                $this->notification->notifyUser(
                    $request->user,
                    'يمكنك الآن تقييم الخدمة',
                    'الفني بدأ العمل في طلبك #' . $request->id . '. قيّم الخدمة الآن من التطبيق قبل إغلاق الطلب.',
                    ['type' => 'rating_request', 'request_id' => (string) $request->id, 'request_type' => $request->type->value]
                );
            } else {
                $this->notification->notifyUser(
                    $request->user,
                    __('Request Update'),
                    __('Your request #:id status changed to: :status', ['id' => $request->id, 'status' => $newStatus->label()]),
                    ['type' => 'status_update', 'request_id' => (string) $request->id, 'request_type' => $request->type->value, 'status' => $newStatus->value]
                );
            }
        }

        if ($request->technician && ! $actor->isTechnician()) {
            $this->notification->notifyUser(
                $request->technician,
                __('Request Update'),
                __('Request #:id status updated to: :status', ['id' => $request->id, 'status' => $newStatus->label()]),
                ['type' => 'status_update', 'request_id' => (string) $request->id, 'request_type' => $request->type, 'status' => $newStatus->value]
            );
        }

        // Broadcast status change to admin live-map
        if ($request->technician_id && in_array($request->status->value, [
            RequestStatus::OnWay->value,
            RequestStatus::InProgress->value,
            RequestStatus::Completed->value,
            RequestStatus::Canceled->value,
        ])) {
            try {
                broadcast(new \App\Events\RequestStatusChanged($request));
            } catch (\Throwable) {}
        }

        return $request->fresh();
    }

    public function checkTechnicianAvailability(int $technicianId, string $start, string $end): bool
    {
        return ! Request::where('technician_id', $technicianId)
            ->whereIn('status', [
                RequestStatus::Assigned->value,
                RequestStatus::OnWay->value,
                RequestStatus::InProgress->value,
            ])
            ->where(function ($q) use ($start, $end) {
                // Check against precise datetime columns when available
                $q->where(function ($q2) use ($start, $end) {
                    $q2->whereNotNull('scheduled_start_at')
                       ->where('scheduled_start_at', '<', $end)
                       ->where('scheduled_end_at', '>', $start);
                })->orWhere(function ($q2) use ($start, $end) {
                    // Fall back to legacy date columns for older records
                    $q2->whereNull('scheduled_start_at')
                       ->where(function ($q3) use ($start, $end) {
                           $q3->whereBetween('scheduled_at', [$start, $end])
                              ->orWhereBetween('end_date', [$start, $end])
                              ->orWhere(function ($q4) use ($start, $end) {
                                  $q4->where('scheduled_at', '<=', $start)
                                     ->where('end_date', '>=', $end);
                              });
                       });
                });
            })
            ->exists();
    }

    public function generateInvoicePrefix(RequestType $type): string
    {
        return $type === RequestType::Service
            ? config('sindbad.request_prefix_service')
            : config('sindbad.request_prefix_installation');
    }
}
