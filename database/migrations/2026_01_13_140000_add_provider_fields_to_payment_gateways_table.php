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
            if (!Schema::hasColumn('payment_gateways', 'provider')) {
                $table->string('provider')->nullable();
            }
            
            // Mode for auto gateways (live/test)
            if (!Schema::hasColumn('payment_gateways', 'mode')) {
                $table->string('mode')->default('test');
            }
            
            if (!Schema::hasColumn('payment_gateways', 'supported_currencies')) {
                $table->string('supported_currencies')->nullable();
            }
            if (!Schema::hasColumn('payment_gateways', 'sort_order')) {
                $table->integer('sort_order')->default(0);
            }
            if (!Schema::hasColumn('payment_gateways', 'description')) {
                $table->text('description')->nullable();
            }
        });
        
        // Add indexes for faster queries (check if they don't already exist)
        try {
            Schema::table('payment_gateways', function (Blueprint $table) {
                $table->index(['type', 'is_active'], 'payment_gateways_type_is_active_index');
                $table->index(['category', 'is_active'], 'payment_gateways_category_is_active_index');
            });
        } catch (\Exception $e) {
            // Indexes might already exist
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            try {
                $table->dropIndex('payment_gateways_type_is_active_index');
                $table->dropIndex('payment_gateways_category_is_active_index');
            } catch (\Exception $e) {}
            
            $columns = ['provider', 'mode', 'supported_currencies', 'sort_order', 'description'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('payment_gateways', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
