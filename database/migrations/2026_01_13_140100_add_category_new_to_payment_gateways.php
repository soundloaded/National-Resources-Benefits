<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('payment_gateways', 'category_new')) {
            Schema::table('payment_gateways', function (Blueprint $table) {
                $table->string('category_new')->default('deposit');
            });
            
            // Copy data from old column to new
            DB::table('payment_gateways')->whereNotNull('category')->update([
                'category_new' => DB::raw('category')
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('payment_gateways', 'category_new')) {
            Schema::table('payment_gateways', function (Blueprint $table) {
                $table->dropColumn('category_new');
            });
        }
    }
};
