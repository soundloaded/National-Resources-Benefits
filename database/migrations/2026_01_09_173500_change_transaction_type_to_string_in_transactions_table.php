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
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('transaction_type')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Reverting to enum might be complex if data exists that doesn't match, 
            // but for down() we can try or just leave it as string.
            // $table->enum('transaction_type', ['deposit', 'withdrawal', 'transfer_in', 'transfer_out'])->change();
        });
    }
};
