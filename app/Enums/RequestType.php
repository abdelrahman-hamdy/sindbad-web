<?php

namespace App\Enums;

enum RequestType: string
{
    case Service = 'service';
    case Installation = 'installation';

    public function label(): string
    {
        return match($this) {
            self::Service      => __('Service'),
            self::Installation => __('Installation'),
        };
    }
}
