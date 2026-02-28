<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-expire stale technician locations every 5 minutes.
// Any technician who hasn't sent a GPS ping in 10+ minutes is marked offline.
Schedule::command('technician:expire-locations')->everyFiveMinutes();
