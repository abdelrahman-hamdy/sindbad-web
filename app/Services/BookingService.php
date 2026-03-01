<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Models\AppSetting;
use App\Models\Request;
use App\Models\TechnicianHoliday;
use App\Models\User;
use Carbon\Carbon;

class BookingService
{
    public function isWorkingDay(Carbon $date): bool
    {
        $workDays = json_decode(AppSetting::get('booking_work_days', '[0,1,2,3,4]'), true) ?? [0, 1, 2, 3, 4];
        return in_array($date->dayOfWeek, $workDays);
    }

    public function isTechnicianOnHoliday(int $technicianId, Carbon $date): bool
    {
        return TechnicianHoliday::where('technician_id', $technicianId)
            ->where('start_date', '<=', $date->toDateString())
            ->where('end_date', '>=', $date->toDateString())
            ->exists();
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
     * Validates that a technician can be assigned to a request on the given date.
     *
     * @throws \Exception on rule violation
     */
    public function validateAssignment(int $technicianId, Carbon $date, string $type): void
    {
        // Holiday check
        if ($this->isTechnicianOnHoliday($technicianId, $date)) {
            throw new \Exception(__('The technician is on holiday on this date.'));
        }

        // Working day check
        if (! $this->isWorkingDay($date)) {
            throw new \Exception(__('The selected date is not a working day.'));
        }

        // Service: daily count check
        if ($type === RequestType::Service->value) {
            $maxPerDay = (int) AppSetting::get('booking_max_service_per_tech_per_day', '4');
            $count     = Request::where('technician_id', $technicianId)
                ->where('type', RequestType::Service->value)
                ->whereIn('status', [
                    RequestStatus::Assigned->value,
                    RequestStatus::OnWay->value,
                    RequestStatus::InProgress->value,
                ])
                ->whereDate('scheduled_at', $date->toDateString())
                ->count();

            if ($count >= $maxPerDay) {
                throw new \Exception(__('The technician has reached the maximum number of service requests for this day (:max).', ['max' => $maxPerDay]));
            }
        }

        // Installation: concurrent check (scheduled_at <= date <= end_date)
        if ($type === RequestType::Installation->value) {
            $maxConcurrent = (int) AppSetting::get('booking_max_concurrent_installation', '1');
            $activeCount   = Request::where('technician_id', $technicianId)
                ->where('type', RequestType::Installation->value)
                ->whereIn('status', [
                    RequestStatus::Assigned->value,
                    RequestStatus::OnWay->value,
                    RequestStatus::InProgress->value,
                ])
                ->where('scheduled_at', '<=', $date->toDateString())
                ->where('end_date', '>=', $date->toDateString())
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
            ->whereDate('scheduled_at', $date->toDateString())
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

        return [
            'service_count'           => $serviceCount,
            'has_active_installation' => $hasActiveInstallation,
            'on_holiday'              => $this->isTechnicianOnHoliday($technicianId, $date),
        ];
    }

    /**
     * Returns a 14-day workload preview for a technician, used in the scheduling modal.
     *
     * @return array<int, array{date: string, day: string, count: int, max: int, on_holiday: bool, is_working: bool, full: bool}>
     */
    public function getTechnicianLoad14Days(int $techId): array
    {
        $maxPerDay = (int) AppSetting::get('booking_max_service_per_tech_per_day', '4');
        $result    = [];

        for ($i = 0; $i < 14; $i++) {
            $date      = now()->addDays($i)->startOfDay();
            $isWorkDay = $this->isWorkingDay($date);
            $isHoliday = $this->isTechnicianOnHoliday($techId, $date);

            $count = 0;
            if ($isWorkDay && ! $isHoliday) {
                $count = Request::where('technician_id', $techId)
                    ->whereIn('status', [
                        RequestStatus::Assigned->value,
                        RequestStatus::OnWay->value,
                        RequestStatus::InProgress->value,
                    ])
                    ->whereDate('scheduled_at', $date->toDateString())
                    ->count();
            }

            $result[] = [
                'date'       => $date->format('M j'),
                'day'        => $date->format('D'),
                'count'      => $count,
                'max'        => $maxPerDay,
                'on_holiday' => $isHoliday,
                'is_working' => $isWorkDay,
                'full'       => $count >= $maxPerDay,
            ];
        }

        return $result;
    }
}
