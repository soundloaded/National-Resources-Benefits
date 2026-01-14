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
        Schema::table('kyc_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('kyc_template_id')->nullable()->after('user_id');
            // Remove 'document_type' requirement if using template
            $table->string('document_type')->nullable()->change();
            $table->string('document_path')->nullable()->change();
            
            $table->json('data')->nullable()->after('rejection_reason'); // Stores dynamic form data
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kyc_documents', function (Blueprint $table) {
            $table->dropColumn(['kyc_template_id', 'data']);
             // Cannot easily reverse nullable change without potential data loss or strict mode issues
        });
    }
};
