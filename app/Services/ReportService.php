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
        $avgRatings = DB::table('ratings')
            ->join('requests', 'ratings.request_id', '=', 'requests.id')
            ->whereNotNull('requests.technician_id')
            ->select('requests.technician_id', DB::raw('ROUND(AVG((ratings.product_rating + ratings.service_rating) / 2), 1) as avg_rating'))
            ->groupBy('requests.technician_id')
            ->pluck('avg_rating', 'technician_id');

        return User::technicians()
            ->withCount(['assignedRequests as completed_count' => function ($q) {
                $q->where('status', RequestStatus::Completed->value);
            }])
            ->orderByDesc('completed_count')
            ->limit($limit)
            ->get()
            ->map(function ($tech) use ($avgRatings) {
                $tech->avg_rating = $avgRatings[$tech->id] ?? null;
                return $tech;
            });
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
        $avgRatings = DB::table('ratings')
            ->join('requests', 'ratings.request_id', '=', 'requests.id')
            ->whereNotNull('requests.technician_id')
            ->select('requests.technician_id', DB::raw('ROUND(AVG((ratings.product_rating + ratings.service_rating) / 2), 1) as avg_rating'))
            ->groupBy('requests.technician_id')
            ->pluck('avg_rating', 'technician_id');

        return User::technicians()
            ->withCount(['assignedRequests as completed_count' => function ($q) {
                $q->where('status', RequestStatus::Completed->value);
            }])
            ->having('completed_count', '>', 0)
            ->orderBy('completed_count')
            ->limit($limit)
            ->get()
            ->map(function ($tech) use ($avgRatings) {
                $tech->avg_rating = $avgRatings[$tech->id] ?? null;
                return $tech;
            });
    }

    public function getAvgCompletionTime(): float
    {
        return max(0.0, round((float) Request::where('status', RequestStatus::Completed->value)
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(GREATEST(0, TIMESTAMPDIFF(HOUR, created_at, completed_at))) as avg_hours')
            ->value('avg_hours'), 1));
    }

    public function getServiceTypeBreakdown(): array
    {
        $typeCounts = Request::select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get()
            ->mapWithKeys(fn($row) => [$row->type->value => (int) $row->count])
            ->toArray();

        $serviceSubBreakdown = Request::where('type', 'service')
            ->select('service_type', DB::raw('count(*) as count'))
            ->whereNotNull('service_type')
            ->groupBy('service_type')
            ->get()
            ->mapWithKeys(fn($row) => [$row->service_type->value => (int) $row->count])
            ->toArray();

        return [
            'service'         => (int) ($typeCounts['service'] ?? 0),
            'installation'    => (int) ($typeCounts['installation'] ?? 0),
            'by_service_type' => $serviceSubBreakdown,
        ];
    }

    public function getRequestTrendsGrouped(int $days = 30): array
    {
        $results = Request::query()
            ->select(
                DB::raw('DATE(created_at) as date'),
                'type',
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get();

        $dateMap = [];
        foreach ($results as $row) {
            $dateMap[$row->date][$row->type->value] = $row->count;
        }

        $labels           = [];
        $serviceData      = [];
        $installationData = [];

        $current = now()->subDays($days)->startOfDay();
        $end     = now()->endOfDay();

        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            $labels[]           = $current->format('m/d');
            $serviceData[]      = $dateMap[$dateStr]['service'] ?? 0;
            $installationData[] = $dateMap[$dateStr]['installation'] ?? 0;
            $current->addDay();
        }

        return [
            'labels'       => $labels,
            'service'      => $serviceData,
            'installation' => $installationData,
        ];
    }

    public function getFilteredStats(string $period): array
    {
        $query = Request::query();

        match ($period) {
            'today' => $query->whereDate('created_at', today()),
            '7'     => $query->where('created_at', '>=', now()->subDays(7)),
            '30'    => $query->where('created_at', '>=', now()->subDays(30)),
            '90'    => $query->where('created_at', '>=', now()->subDays(90)),
            default => null,
        };

        $total     = (clone $query)->count();
        $completed = (clone $query)->where('status', RequestStatus::Completed->value)->count();
        $pending   = (clone $query)->where('status', RequestStatus::Pending->value)->count();
        $canceled  = (clone $query)->where('status', RequestStatus::Canceled->value)->count();
        $active    = (clone $query)->whereIn('status', [
            RequestStatus::Assigned->value,
            RequestStatus::OnWay->value,
            RequestStatus::InProgress->value,
        ])->count();

        return [
            'total'           => $total,
            'completed'       => $completed,
            'pending'         => $pending,
            'canceled'        => $canceled,
            'active'          => $active,
            'completion_rate' => $total > 0 ? round($completed / $total * 100, 1) : 0,
        ];
    }
}
