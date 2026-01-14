<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->unique(); // e.g., 'stripe_deposit', 'bank_wire_1'
            $table->enum('type', ['automatic', 'manual']);
            $table->enum('category', ['deposit', 'withdrawal']);
            
            // Financial Limits & Fees
            $table->decimal('min_limit', 15, 2)->default(0);
            $table->decimal('max_limit', 15, 2)->nullable();
            $table->decimal('fee_fixed', 10, 2)->default(0);
            $table->decimal('fee_percentage', 5, 2)->default(0);

            // Configuration
            $table->text('instructions')->nullable(); // Markdown supported instructions for users
            $table->json('credentials')->nullable(); // API keys or Bank Details
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
