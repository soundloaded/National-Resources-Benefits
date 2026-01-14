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
            $table->boolean('is_active')->default(true);
            $table->boolean('can_deposit')->default(true);
            $table->boolean('can_exchange')->default(true);
            $table->boolean('can_transfer')->default(true);
            $table->boolean('can_request')->default(true);
            $table->boolean('can_withdraw')->default(true);
            $table->boolean('can_use_voucher')->default(true);
            $table->timestamp('kyc_verified_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_active',
                'can_deposit',
                'can_exchange',
                'can_transfer',
                'can_request',
                'can_withdraw',
                'can_use_voucher',
                'kyc_verified_at',
            ]);
        });
    }
};
