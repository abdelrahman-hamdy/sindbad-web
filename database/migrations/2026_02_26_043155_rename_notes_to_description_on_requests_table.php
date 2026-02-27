<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Copy installation notes → description where description is still empty
        DB::table('requests')
            ->where('type', 'installation')
            ->whereNotNull('notes')
            ->where(fn($q) => $q->whereNull('description')->orWhere('description', ''))
            ->update(['description' => DB::raw('notes')]);

        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('description');
        });
    }
};
