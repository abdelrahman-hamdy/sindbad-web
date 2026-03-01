<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private BookingService $booking) {}

    public function availableSlots(Request $request)
    {
        $request->validate([
            'date'         => 'required|date',
            'request_type' => 'required|in:service,installation',
        ]);

        $date = Carbon::parse($request->date);

        if ($request->request_type === 'service') {
            if (! $this->booking->isWorkingDay($date)) {
                return response()->json([
                    'date'  => $date->toDateString(),
                    'slots' => [],
                    'message' => 'Not a working day',
                ]);
            }

            $techSlots = $this->booking->getServiceSlotsForDate($date);

            // Aggregate across all technicians: a slot is available if ANY tech is free
            $aggregated = [];
            foreach ($techSlots as $techData) {
                foreach ($techData['slots'] as $slot) {
                    $key = $slot['start'];
                    if (! isset($aggregated[$key])) {
                        $aggregated[$key] = [
                            'start'     => $slot['start'],
                            'end'       => $slot['end'],
                            'available' => false,
                        ];
                    }
                    if ($slot['available']) {
                        $aggregated[$key]['available'] = true;
                    }
                }
            }

            return response()->json([
                'date'  => $date->toDateString(),
                'slots' => array_values($aggregated),
            ]);
        }

        // Installation type
        $availableDates = $this->booking->getAvailableInstallationDates(30);

        return response()->json([
            'available_dates' => $availableDates,
        ]);
    }
}
