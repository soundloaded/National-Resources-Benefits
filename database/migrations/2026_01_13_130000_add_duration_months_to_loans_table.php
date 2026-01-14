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
        Schema::table('loans', function (Blueprint $table) {
            // Add new columns for monthly loan tracking
            if (!Schema::hasColumn('loans', 'duration_months')) {
                $table->integer('duration_months')->default(1);
            }
            if (!Schema::hasColumn('loans', 'monthly_payment')) {
                $table->decimal('monthly_payment', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('loans', 'purpose')) {
                $table->text('purpose')->nullable();
            }
            if (!Schema::hasColumn('loans', 'account_id')) {
                $table->uuid('account_id')->nullable();
                // Add foreign key for account
                $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropColumn(['duration_months', 'monthly_payment', 'purpose', 'account_id']);
        });
    }
};
