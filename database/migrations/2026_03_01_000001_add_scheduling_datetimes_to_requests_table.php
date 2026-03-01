<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dateTime('scheduled_start_at')->nullable()->after('scheduled_at');
            $table->dateTime('scheduled_end_at')->nullable()->after('scheduled_start_at');
            $table->index('scheduled_start_at');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropIndex(['scheduled_start_at']);
            $table->dropColumn(['scheduled_start_at', 'scheduled_end_at']);
        });
    }
};
