<?php

namespace App\Console\Commands;

use App\Services\TechnicianLocationService;
use Illuminate\Console\Command;

class ExpireStaleTechnicianLocations extends Command
{
    protected $signature = 'technician:expire-locations
                            {--minutes=10 : Minutes of inactivity before marking offline}';

    protected $description = 'Mark as offline any technician who has not sent a location update recently';

    public function handle(TechnicianLocationService $service): void
    {
        $minutes = (int) $this->option('minutes');
        $count = $service->expireStale($minutes);

        if ($count > 0) {
            $this->info("Marked {$count} technician(s) offline (no update in {$minutes} min).");
        }
    }
}
