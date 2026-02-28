<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Models\Request;
use App\Models\User;
use Exception;
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

            $typeLabel = $type === RequestType::Service ? __('صيانة') : __('تركيب');
            $this->notification->notifyAdmins(
                __('طلب جديد'),
                __('طلب :type جديد #:id من :name', ['type' => $typeLabel, 'id' => $req->id, 'name' => $creator->name]),
                ['type' => 'new_request', 'request_id' => (string) $req->id, 'request_type' => $type->value]
            );

            return $req;
        });
    }

    public function assignTechnician(Request $request, int $technicianId, array $timing = []): Request
    {
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
                __('تم تعيينك لطلب'),
                __('تم تعيينك للطلب #:id', ['id' => $request->id]),
                ['type' => 'request_assigned', 'request_id' => (string) $request->id, 'request_type' => $request->type]
            );
        }

        if ($request->user) {
            $this->notification->notifyUser(
                $request->user,
                __('تم تعيين فني لطلبك'),
                __('تم تعيين فني لطلبك #:id', ['id' => $request->id]),
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
            throw new Exception(__('لا يمكن إكمال الطلب قبل الحصول على تقييم العميل'));
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
            $this->notification->notifyUser(
                $request->user,
                __('تحديث الطلب'),
                __('تم تغيير حالة طلبك #:id إلى: :status', ['id' => $request->id, 'status' => $newStatus->label()]),
                ['type' => 'status_update', 'request_id' => (string) $request->id, 'request_type' => $request->type, 'status' => $newStatus->value]
            );
        }

        if ($request->technician && ! $actor->isTechnician()) {
            $this->notification->notifyUser(
                $request->technician,
                __('تحديث الطلب'),
                __('تم تحديث حالة الطلب #:id إلى: :status', ['id' => $request->id, 'status' => $newStatus->label()]),
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
                $q->whereBetween('scheduled_at', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end])
                  ->orWhere(function ($q) use ($start, $end) {
                      $q->where('scheduled_at', '<=', $start)->where('end_date', '>=', $end);
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
