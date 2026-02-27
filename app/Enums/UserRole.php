<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Technician = 'technician';
    case Customer = 'customer';

    public function label(): string
    {
        return match($this) {
            self::Admin      => __('Admin'),
            self::Technician => __('Technician'),
            self::Customer   => __('Customer'),
        };
    }
}
