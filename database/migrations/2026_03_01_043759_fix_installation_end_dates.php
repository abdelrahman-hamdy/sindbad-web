<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Set end_date = scheduled_at for installation requests that have a scheduled_at but no end_date
        DB::statement("
            UPDATE requests
            SET end_date = scheduled_at
            WHERE type = 'installation' AND end_date IS NULL AND scheduled_at IS NOT NULL
        ");
    }

    public function down(): void
    {
        // Not reversible — cannot distinguish which end_dates were set by this migration
    }
};
