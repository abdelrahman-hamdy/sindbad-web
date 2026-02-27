<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\ManualOrder;
use App\Models\Rating;
use App\Models\Request;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getStatusCounts(): array
    {
        $counts = Request::query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $result = [];
        foreach (RequestStatus::cases() as $status) {
            $result[$status->value] = $counts[$status->value] ?? 0;
        }

        return $result;
    }

    public function getRequestTrends(int $days = 7): array
    {
        return Request::query()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($r) => ['date' => $r->date, 'count' => $r->count])
            ->toArray();
    }

    public function getLeaderboard(int $limit = 5): Collection
    {
        return User::technicians()
            ->withCount(['assignedRequests as completed_count' => function ($q) {
                $q->where('status', RequestStatus::Completed->value);
            }])
            ->orderByDesc('completed_count')
            ->limit($limit)
            ->get();
    }

    public function getDailyCompleted(string $date): Collection
    {
        return Request::with(['user', 'technician'])
            ->where('status', RequestStatus::Completed->value)
            ->whereDate('completed_at', $date)
            ->get();
    }

    public function getPerformanceSummary(): array
    {
        $total = Request::count();
        $completed = Request::where('status', RequestStatus::Completed->value)->count();
        $pending = Request::where('status', RequestStatus::Pending->value)->count();
        $canceled = Request::where('status', RequestStatus::Canceled->value)->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'canceled' => $canceled,
            'completion_rate' => $total > 0 ? round($completed / $total * 100, 1) : 0,
        ];
    }

    public function getRatingStats(): array
    {
        return [
            'total_ratings' => Rating::count(),
            'avg_product' => round((float) Rating::avg('product_rating'), 1),
            'avg_service' => round((float) Rating::avg('service_rating'), 1),
        ];
    }

    public function getTopCustomers(int $limit = 5): array
    {
        return ManualOrder::select(
                'user_id',
                DB::raw('SUM(total_amount) as total_spent'),
                DB::raw('SUM(paid_amount) as total_paid'),
                DB::raw('COUNT(*) as order_count')
            )
            ->with('user:id,name,phone')
            ->groupBy('user_id')
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get()
            ->map(fn($row) => [
                'user_id'     => $row->user_id,
                'name'        => $row->user?->name ?? 'N/A',
                'phone'       => $row->user?->phone ?? 'N/A',
                'order_count' => $row->order_count,
                'total_spent' => round((float) $row->total_spent, 3),
                'total_paid'  => round((float) $row->total_paid, 3),
            ])
            ->toArray();
    }

    public function getDailyActivity(string $date): array
    {
        return Request::with(['user:id,name,phone', 'technician:id,name', 'rating'])
            ->whereDate('completed_at', $date)
            ->get()
            ->map(fn($req) => [
                'id'             => $req->id,
                'invoice_number' => $req->invoice_number,
                'type'           => $req->type->value,
                'customer'       => $req->user?->name ?? '-',
                'technician'     => $req->technician?->name ?? '-',
                'rating'         => $req->rating
                    ? round(((float)($req->rating->product_rating ?? 0) + (float)($req->rating->service_rating ?? 0)) / 2, 1)
                    : null,
                'completed_at'   => $req->completed_at?->format('Y-m-d H:i'),
            ])
            ->toArray();
    }

    public function getLatestRatings(int $limit = 20): array
    {
        return Rating::with(['user:id,name', 'request:id,invoice_number,type'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn($rating) => [
                'customer'       => $rating->user?->name ?? '-',
                'invoice_number' => $rating->request?->invoice_number ?? '-',
                'type'           => $rating->request?->type?->value ?? '-',
                'product_rating' => $rating->product_rating,
                'service_rating' => $rating->service_rating,
                'notes'          => $rating->customer_notes,
                'how_found_us'   => $rating->how_found_us,
                'created_at'     => $rating->created_at?->format('Y-m-d H:i'),
            ])
            ->toArray();
    }

    public function getRatingBreakdown(): array
    {
        $breakdown = [];
        for ($i = 1; $i <= 5; $i++) {
            $breakdown[$i] = Rating::where(function ($q) use ($i) {
                $q->where('product_rating', $i)->orWhere('service_rating', $i);
            })->count();
        }
        return $breakdown;
    }

    public function getBottomPerformers(int $limit = 5): Collection
    {
        return User::technicians()
            ->withCount(['assignedRequests as completed_count' => function ($q) {
                $q->where('status', RequestStatus::Completed->value);
            }])
            ->having('completed_count', '>', 0)
            ->orderBy('completed_count')
            ->limit($limit)
            ->get();
    }
}
