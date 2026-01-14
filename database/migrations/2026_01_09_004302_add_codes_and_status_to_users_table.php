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
            $table->string('imf_code')->nullable();
            $table->string('tax_code')->nullable();
            $table->string('cot_code')->nullable();
            $table->string('withdrawal_status')->default('approved'); // approved, suspended, hold, etc.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['imf_code', 'tax_code', 'cot_code', 'withdrawal_status']);
        });
    }
};
