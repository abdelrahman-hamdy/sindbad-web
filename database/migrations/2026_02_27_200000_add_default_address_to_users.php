<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('default_address')->nullable()->after('avatar_url');
            $table->decimal('default_latitude', 10, 7)->nullable()->after('default_address');
            $table->decimal('default_longitude', 10, 7)->nullable()->after('default_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['default_address', 'default_latitude', 'default_longitude']);
        });
    }
};
