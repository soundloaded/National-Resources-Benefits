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
        Schema::table('payment_gateways', function (Blueprint $table) {
            // Add provider field for auto gateways (stripe, paystack, flutterwave)
            $table->string('provider')->nullable()->after('code');
            
            // Mode for auto gateways (live/test)
            $table->enum('mode', ['live', 'test'])->default('test')->after('provider');
            
            // Expand category to support more use cases
            // Change category to support: deposit, withdrawal, payment (for generic payments like loan repayment)
            // We'll handle this by adding a new column since SQLite doesn't support MODIFY
            $table->string('supported_currencies')->nullable()->after('is_active');
            $table->integer('sort_order')->default(0)->after('supported_currencies');
            $table->text('description')->nullable()->after('instructions');
        });
        
        // Add index for faster queries
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->index(['type', 'is_active']);
            $table->index(['category', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropIndex(['type', 'is_active']);
            $table->dropIndex(['category', 'is_active']);
            $table->dropColumn(['provider', 'mode', 'supported_currencies', 'sort_order', 'description']);
        });
    }
};
