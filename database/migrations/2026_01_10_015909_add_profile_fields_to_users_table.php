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
        Schema::table('users', function (Blueprint $table) {
            // Funding Intent
            $table->string('funding_amount')->nullable()->after('email');
            $table->string('funding_category')->nullable()->after('funding_amount');
            
            // Profile / Demographics
            $table->string('phone')->nullable()->after('funding_category');
            $table->string('citizenship_status')->nullable()->after('phone');
            $table->string('zip_code')->nullable()->after('citizenship_status');
            $table->string('city')->nullable()->after('zip_code');
            $table->string('state')->nullable()->after('city');
            $table->string('age_range')->nullable()->after('state');
            $table->string('gender')->nullable()->after('age_range');
            $table->string('ethnicity')->nullable()->after('gender');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'funding_amount',
                'funding_category',
                'phone',
                'citizenship_status',
                'zip_code',
                'city',
                'state',
                'age_range',
                'gender',
                'ethnicity',
            ]);
        });
    }
};
