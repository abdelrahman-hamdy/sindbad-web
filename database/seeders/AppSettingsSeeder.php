<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class AppSettingsSeeder extends Seeder
{
    public function run(): void
    {
        AppSetting::set('enforce_financial_eligibility', 'false');
        AppSetting::set('block_pending_requests', 'false');
    }
}
