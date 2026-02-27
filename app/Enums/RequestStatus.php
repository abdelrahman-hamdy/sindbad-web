<?php

namespace App\Enums;

enum RequestStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case OnWay = 'on_way';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match($this) {
            self::Pending    => __('Pending'),
            self::Assigned   => __('Assigned'),
            self::OnWay      => __('On Way'),
            self::InProgress => __('In Progress'),
            self::Completed  => __('Completed'),
            self::Canceled   => __('Canceled'),
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending => 'warning',
            self::Assigned => 'info',
            self::OnWay => 'primary',
            self::InProgress => 'primary',
            self::Completed => 'success',
            self::Canceled => 'danger',
        };
    }
}
