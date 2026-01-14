<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            if (!Schema::hasColumn('loans', 'loan_plan_id')) {
                $table->foreignId('loan_plan_id')->nullable()->constrained('loan_plans')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            if (Schema::hasColumn('loans', 'loan_plan_id')) {
                $table->dropForeign(['loan_plan_id']);
                $table->dropColumn('loan_plan_id');
            }
        });
    }
};
