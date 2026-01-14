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
        Schema::create('wallet_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Standard Savings", "Bitcoin Wallet"
            $table->string('slug')->unique(); // e.g., "savings", "btc"
            $table->string('currency_code', 10); // e.g., "USD", "BTC"
            $table->string('type')->default('fiat'); // e.g., "fiat", "crypto"
            $table->boolean('is_default')->default(false); // Assigned on signup
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_types');
    }
};
