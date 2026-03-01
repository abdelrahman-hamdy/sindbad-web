<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Models\AppSetting;
use App\Models\Request;
use App\Models\TechnicianHoliday;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BookingService
{
    public function isWorkingDay(Carbon $date): bool
    {
        $workDays = json_decode(AppSetting::get('booking_work_days', '[0,1,2,3,4]'), true) ?? [0, 1, 2, 3, 4];
        return in_array($date->dayOfWeek, $workDays);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function getWorkingWindow(Carbon $date): array
    {
        $start = AppSetting::get('booking_work_start', '08:00');
        $end   = AppSetting::get('booking_work_end', '17:00');

        [$startH, $startM] = explode(':', $start);
        [$endH, $endM]     = explode(':', $end);

        $windowStart = $date->copy()->setTime((int) $startH, (int) $startM, 0);
        $windowEnd   = $date->copy()->setTime((int) $endH, (int) $endM, 0);

        return [$windowStart, $windowEnd];
    }

    public function isTechnicianOnHoliday(int $technicianId, Carbon $date): bool
    {
        return TechnicianHoliday::where('technician_id', $technicianId)
            ->where('start_date', '<=', $date->toDateString())
            ->where('end_date', '>=', $date->toDateString())
            ->exists();
    }

    public function generateTimeSlots(Carbon $date, int $durationMinutes): Collection
    {
        [$windowStart, $windowEnd] = $this->getWorkingWindow($date);

        $slots   = collect();
        $current = $windowStart->copy();

        while (true) {
            $slotEnd = $current->copy()->addMinutes($durationMinutes);
            if ($slotEnd->greaterThan($windowEnd)) {
                break;
            }
            $slots->push(['start' => $current->copy(), 'end' => $slotEnd->copy()]);
            $current = $slotEnd;
        }

        return $slots;
    }

    /**
     * Returns available service slots per technician for a given date.
     *
     * @return array<int, array{technician: User, slots: array<array{start: string, end: string, available: bool}>}>
     */
    public function getServiceSlotsForDate(Carbon $date): array
    {
        $slotMinutes = (int) AppSetting::get('booking_service_slot_minutes', '120');
        $maxPerDay   = (int) AppSetting::get('booking_max_service_per_tech_per_day', '4');

        $technicians = User::technicians()->active()->get();
        $result      = [];

        foreach ($technicians as $tech) {
            if ($this->isTechnicianOnHoliday($tech->id, $date)) {
                continue;
            }

            $slots        = $this->generateTimeSlots($date, $slotMinutes);
            $formattedSlots = [];

            // Count service bookings for the day
            $dayServiceCount = Request::where('technician_id', $tech->id)
                ->where('type', RequestType::Service->value)
                ->whereIn('status', [
                    RequestStatus::Assigned->value,
                    RequestStatus::OnWay->value,
                    RequestStatus::InProgress->value,
                ])
                ->whereDate('scheduled_start_at', $date->toDateString())
                ->count();

            // Existing bookings for overlap check
            $existingBookings = Request::where('technician_id', $tech->id)
                ->whereIn('status', [
                    RequestStatus::Assigned->value,
                    RequestStatus::OnWay->value,
                    RequestStatus::InProgress->value,
                ])
                ->whereDate('scheduled_start_at', $date->toDateString())
                ->whereNotNull('scheduled_start_at')
                ->get(['scheduled_start_at', 'scheduled_end_at']);

            foreach ($slots as $slot) {
                $available = true;

                // Max daily service count reached
                if ($dayServiceCount >= $maxPerDay) {
                    $available = false;
                }

                // Check slot overlap with existing bookings
                if ($available) {
                    foreach ($existingBookings as $booking) {
                        if ($booking->scheduled_start_at < $slot['end'] && $booking->scheduled_end_at > $slot['start']) {
                            $available = false;
                            break;
                        }
                    }
                }

                $formattedSlots[] = [
                    'start'     => $slot['start']->format('H:i'),
                    'end'       => $slot['end']->format('H:i'),
                    'available' => $available,
                ];
            }

            $result[$tech->id] = [
                'technician' => $tech,
                'slots'      => $formattedSlots,
            ];
        }

        return $result;
    }

    /**
     * Returns date strings for the next $daysAhead days where at least one technician
     * can accept an installation request.
     *
     * @return string[]
     */
    public function getAvailableInstallationDates(int $daysAhead = 30): array
    {
        $maxConcurrent = (int) AppSetting::get('booking_max_concurrent_installation', '1');
        $technicians   = User::technicians()->active()->get();
        $dates         = [];

        for ($i = 0; $i <= $daysAhead; $i++) {
            $date = now()->addDays($i)->startOfDay();

            if (! $this->isWorkingDay($date)) {
                continue;
            }

            foreach ($technicians as $tech) {
                if ($this->isTechnicianOnHoliday($tech->id, $date)) {
                    continue;
                }

                $activeInstallations = Request::where('technician_id', $tech->id)
                    ->where('type', RequestType::Installation->value)
                    ->whereIn('status', [
                        RequestStatus::Assigned->value,
                        RequestStatus::OnWay->value,
                        RequestStatus::InProgress->value,
                    ])
                    ->where('scheduled_at', '<=', $date->toDateString())
                    ->where('end_date', '>=', $date->toDateString())
                    ->count();

                if ($activeInstallations < $maxConcurrent) {
                    $dates[] = $date->toDateString();
                    break; // At least one tech is available — include this date
                }
            }
        }

        return $dates;
    }

    /**
     * Validates that a technician can be assigned to a request at the given time.
     *
     * @throws \Exception on rule violation
     */
    public function validateAssignment(int $technicianId, Carbon $start, Carbon $end, string $type): void
    {
        // Holiday check
        if ($this->isTechnicianOnHoliday($technicianId, $start)) {
            throw new \Exception(__('The technician is on holiday on this date.'));
        }

        // Working day check
        if (! $this->isWorkingDay($start)) {
            throw new \Exception(__('The selected date is not a working day.'));
        }

        // Overlap with existing bookings
        $hasOverlap = Request::where('technician_id', $technicianId)
            ->whereIn('status', [
                RequestStatus::Assigned->value,
                RequestStatus::OnWay->value,
                RequestStatus::InProgress->value,
            ])
            ->whereNotNull('scheduled_start_at')
            ->where('scheduled_start_at', '<', $end)
            ->where('scheduled_end_at', '>', $start)
            ->exists();

        if ($hasOverlap) {
            throw new \Exception(__('The technician already has a booking that overlaps with this time slot.'));
        }

        if ($type === RequestType::Service->value) {
            $maxPerDay    = (int) AppSetting::get('booking_max_service_per_tech_per_day', '4');
            $serviceCount = Request::where('technician_id', $technicianId)
                ->where('type', RequestType::Service->value)
                ->whereIn('status', [
                    RequestStatus::Assigned->value,
                    RequestStatus::OnWay->value,
                    RequestStatus::InProgress->value,
                ])
                ->whereDate('scheduled_start_at', $start->toDateString())
                ->count();

            if ($serviceCount >= $maxPerDay) {
                throw new \Exception(__('The technician has reached the maximum number of service requests for this day (:max).', ['max' => $maxPerDay]));
            }
        }

        if ($type === RequestType::Installation->value) {
            $maxConcurrent = (int) AppSetting::get('booking_max_concurrent_installation', '1');
            $activeCount   = Request::where('technician_id', $technicianId)
                ->where('type', RequestType::Installation->value)
                ->whereIn('status', [
                    RequestStatus::Assigned->value,
                    RequestStatus::OnWay->value,
                    RequestStatus::InProgress->value,
                ])
                ->where('scheduled_at', '<=', $start->toDateString())
                ->where('end_date', '>=', $start->toDateString())
                ->count();

            if ($activeCount >= $maxConcurrent) {
                throw new \Exception(__('The technician already has the maximum number of concurrent installation requests (:max).', ['max' => $maxConcurrent]));
            }
        }
    }

    /**
     * Returns the workload summary for a technician on a given day.
     */
    public function getTechnicianDayLoad(int $technicianId, Carbon $date): array
    {
        $serviceCount = Request::where('technician_id', $technicianId)
            ->where('type', RequestType::Service->value)
            ->whereIn('status', [
                RequestStatus::Assigned->value,
                RequestStatus::OnWay->value,
                RequestStatus::InProgress->value,
            ])
            ->whereDate('scheduled_start_at', $date->toDateString())
            ->count();

        $hasActiveInstallation = Request::where('technician_id', $technicianId)
            ->where('type', RequestType::Installation->value)
            ->whereIn('status', [
                RequestStatus::Assigned->value,
                RequestStatus::OnWay->value,
                RequestStatus::InProgress->value,
            ])
            ->where('scheduled_at', '<=', $date->toDateString())
            ->where('end_date', '>=', $date->toDateString())
            ->exists();

        $bookings = Request::where('technician_id', $technicianId)
            ->whereIn('status', [
                RequestStatus::Assigned->value,
                RequestStatus::OnWay->value,
                RequestStatus::InProgress->value,
            ])
            ->whereDate('scheduled_start_at', $date->toDateString())
            ->get(['id', 'type', 'status', 'scheduled_start_at', 'scheduled_end_at'])
            ->toArray();

        return [
            'service_count'          => $serviceCount,
            'has_active_installation' => $hasActiveInstallation,
            'on_holiday'             => $this->isTechnicianOnHoliday($technicianId, $date),
            'bookings'               => $bookings,
        ];
    }
}
