<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class BookingSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'booking_work_days'                      => '[0,1,2,3,4]',
            'booking_work_start'                     => '08:00',
            'booking_work_end'                       => '17:00',
            'booking_service_slot_minutes'           => '120',
            'booking_max_service_per_tech_per_day'   => '4',
            'booking_max_concurrent_installation'    => '1',
        ];

        foreach ($defaults as $key => $value) {
            if (AppSetting::where('key', $key)->doesntExist()) {
                AppSetting::create(['key' => $key, 'value' => $value]);
            }
        }
    }
}
