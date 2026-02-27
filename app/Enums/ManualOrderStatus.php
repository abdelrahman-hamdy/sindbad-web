<?php

namespace App\Enums;

enum ManualOrderStatus: string
{
    case Paid = 'paid';
    case Partial = 'partial';

    public function label(): string
    {
        return match($this) {
            self::Paid => __('Paid'),
            self::Partial => __('Partial'),
        };
    }
}
