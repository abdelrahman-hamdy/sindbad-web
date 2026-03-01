<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Request;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request as HttpRequest;

class BookingController extends Controller
{
    public function __construct(private BookingService $booking) {}

    public function availableSlots(HttpRequest $request)
    {
        $request->validate([
            'date'         => 'required|date',
            'request_type' => 'required|in:service,installation',
        ]);

        $date = Carbon::parse($request->date);

        if ($request->request_type === 'service') {
            if (! $this->booking->isWorkingDay($date)) {
                return response()->json([
                    'date'      => $date->toDateString(),
                    'available' => false,
                    'slots'     => [],
                    'message'   => 'Not a working day',
                ]);
            }

            $maxPerDay  = (int) AppSetting::get('booking_max_service_per_tech_per_day', 4);
            $techCount  = User::technicians()->active()->count();
            $totalCapacity = $maxPerDay * $techCount;

            $booked = Request::where('type', 'service')
                ->whereIn('status', [
                    RequestStatus::Assigned->value,
                    RequestStatus::OnWay->value,
                    RequestStatus::InProgress->value,
                ])
                ->whereDate('scheduled_at', $date->toDateString())
                ->count();

            return response()->json([
                'date'      => $date->toDateString(),
                'available' => $booked < $totalCapacity,
                'slots'     => [], // no more time slots — kept for API compat
            ]);
        }

        // Installation type
        $availableDates = $this->booking->getAvailableInstallationDates(30);

        return response()->json([
            'available_dates' => $availableDates,
        ]);
    }
}
