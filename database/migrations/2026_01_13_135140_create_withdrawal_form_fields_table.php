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
        Schema::create('withdrawal_form_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Field identifier (e.g., 'bank_name', 'account_number')
            $table->string('label'); // Display label
            $table->string('type')->default('text'); // text, select, number, email, etc.
            $table->string('placeholder')->nullable();
            $table->text('options')->nullable(); // JSON array for select options
            $table->text('validation_rules')->nullable(); // JSON array of validation rules
            $table->text('help_text')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Add settings for withdrawal account limits
        \App\Models\Setting::set('withdrawal_account_limit', 3, 'withdraw', 'integer');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_form_fields');
    }
};
