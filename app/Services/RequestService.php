<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Models\Request;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class RequestService
{
    public function __construct(
        private NotificationService $notification,
        private \App\Services\Odoo\OdooServiceInterface $odoo,
    ) {}

    public function createRequest(User $creator, array $data, RequestType $type): Request
    {
        $prefix = $type === RequestType::Service ? 'T-' : 'B-';

        return DB::transaction(function () use ($creator, $data, $type, $prefix) {
            $req = Request::create(array_merge($data, [
                'type' => $type->value,
                'user_id' => $creator->id,
                'status' => RequestStatus::Pending->value,
                'invoice_number' => isset($data['invoice_number'])
                    ? $prefix . ltrim($data['invoice_number'], 'TB-')
                    : null,
            ]));

            $typeLabel = $type === RequestType::Service ? 'صيانة' : 'تركيب';
            $this->notification->notifyAdmins(
                'طلب جديد',
                "طلب {$typeLabel} جديد #{$req->id} من {$creator->name}",
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
                'تم تعيينك لطلب',
                "تم تعيينك للطلب #{$request->id}",
                ['type' => 'request_assigned', 'request_id' => (string) $request->id, 'request_type' => $request->type]
            );
        }

        if ($request->user) {
            $this->notification->notifyUser(
                $request->user,
                'تم تعيين فني لطلبك',
                "تم تعيين فني لطلبك #{$request->id}",
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
            throw new Exception('لا يمكن إكمال الطلب قبل الحصول على تقييم العميل');
        }

        $updateData = ['status' => $newStatus->value];
        if ($newStatus === RequestStatus::Completed) {
            $updateData['completed_at'] = now();
        }
        $request->update($updateData);

        $request->load(['user', 'technician']);

        if ($request->user) {
            $this->notification->notifyUser(
                $request->user,
                'تحديث الطلب',
                "تم تغيير حالة طلبك #{$request->id} إلى: {$newStatus->label()}",
                ['type' => 'status_update', 'request_id' => (string) $request->id, 'request_type' => $request->type, 'status' => $newStatus->value]
            );
        }

        if ($request->technician && ! $actor->isTechnician()) {
            $this->notification->notifyUser(
                $request->technician,
                'تحديث الطلب',
                "تم تحديث حالة الطلب #{$request->id} إلى: {$newStatus->label()}",
                ['type' => 'status_update', 'request_id' => (string) $request->id, 'request_type' => $request->type, 'status' => $newStatus->value]
            );
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
        return $type === RequestType::Service ? 'T-' : 'B-';
    }
}
