<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number', 100);
            $table->string('quotation_template', 255)->nullable();
            $table->decimal('total_amount', 10, 3)->default(0);
            $table->decimal('paid_amount', 10, 3)->default(0);
            $table->decimal('remaining_amount', 10, 3)->default(0);
            $table->string('status', 20)->default('partial'); // paid | partial
            $table->date('order_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_orders');
    }
};
