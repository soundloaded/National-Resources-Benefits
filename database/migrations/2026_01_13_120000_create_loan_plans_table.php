<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('tagline')->nullable(); // Short tagline like "Fast approval for urgent needs"
            $table->decimal('min_amount', 15, 2)->default(100);
            $table->decimal('max_amount', 15, 2)->default(10000);
            $table->decimal('interest_rate', 5, 2)->default(5); // Monthly interest rate
            $table->integer('min_duration_months')->default(1);
            $table->integer('max_duration_months')->default(12);
            $table->integer('default_duration_months')->default(3);
            $table->string('icon')->default('pi-bolt'); // PrimeVue icon
            $table->string('color')->default('green'); // Theme color: green, blue, purple, etc.
            $table->string('gradient_from')->nullable(); // e.g., "green-500"
            $table->string('gradient_to')->nullable(); // e.g., "emerald-600"
            $table->json('features')->nullable(); // Array of feature objects
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->decimal('processing_fee', 15, 2)->default(0);
            $table->enum('processing_fee_type', ['fixed', 'percentage'])->default('fixed');
            $table->integer('approval_days')->default(1); // Expected approval time in days
            $table->boolean('requires_collateral')->default(false);
            $table->boolean('early_repayment_allowed')->default(true);
            $table->decimal('early_repayment_fee', 5, 2)->default(0); // Percentage fee
            $table->json('eligibility_criteria')->nullable(); // JSON criteria
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_plans');
    }
};
