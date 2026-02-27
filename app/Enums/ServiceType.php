<?php

namespace App\Enums;

enum ServiceType: string
{
    case Maintenance = 'maintenance';
    case Repair = 'repair';
    case Inspection = 'inspection';

    public function label(): string
    {
        return match($this) {
            self::Maintenance => __('Maintenance'),
            self::Repair      => __('Repair'),
            self::Inspection  => __('Inspection'),
        };
    }
}
