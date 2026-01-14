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
        // SQLite doesn't support ALTER COLUMN, so we need to recreate the table
        // First, let's add a new column and migrate data
        
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->string('category_new')->default('deposit')->after('category');
        });
        
        // Copy data from old column to new
        DB::table('payment_gateways')->update([
            'category_new' => DB::raw('category')
        ]);
        
        // Since SQLite doesn't support dropping constraints easily,
        // we'll work with the new column
        // The old 'category' column will remain but won't be used
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropColumn('category_new');
        });
    }
};
