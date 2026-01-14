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
            // Drop the existing unique constraint on 'code' column
            $table->dropUnique(['code']);
            
            // Add composite unique constraint: same code can exist for different categories
            // e.g., 'stripe' can exist for both 'deposit' and 'withdrawal'
            $table->unique(['code', 'category'], 'payment_gateways_code_category_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique('payment_gateways_code_category_unique');
            
            // Restore the original unique constraint on 'code' only
            $table->unique('code');
        });
    }
};
