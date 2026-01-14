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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('value', 15, 2);
            $table->enum('voucher_type', ['single_use', 'multi_use'])->default('single_use');
            $table->enum('category', ['housing', 'food', 'healthcare', 'education', 'transportation', 'utilities', 'general'])->default('general');
            $table->integer('max_uses')->nullable(); // null = unlimited for multi_use
            $table->integer('current_uses')->default(0);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('expiration_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
