<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20); // service | installation
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('invoice_number', 100)->nullable()->index();
            $table->string('address');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->date('scheduled_at')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('technician_accepted_at')->nullable();
            $table->timestamp('task_start_time')->nullable();
            $table->timestamp('task_end_time')->nullable();

            // Service-only (nullable)
            $table->string('service_type', 30)->nullable();
            $table->text('description')->nullable();
            $table->json('details')->nullable();

            // Installation-only (nullable)
            $table->string('product_type', 255)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->boolean('is_site_ready')->default(false);
            $table->json('readiness_details')->nullable();
            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('user_id');
            $table->index('technician_id');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
