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
        Schema::create('funding_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('funding_source_id')->constrained()->onDelete('cascade');
            $table->string('application_number')->unique();
            $table->decimal('requested_amount', 15, 2);
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected', 'disbursed', 'cancelled'])->default('pending');
            $table->text('purpose')->nullable();
            $table->json('documents')->nullable();
            $table->json('form_data')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['funding_source_id', 'status']);
        });
        
        // Add new columns to funding_sources table
        Schema::table('funding_sources', function (Blueprint $table) {
            $table->boolean('is_internal')->default(true)->after('is_active');
            $table->json('requirements')->nullable()->after('deadline');
            $table->json('form_fields')->nullable()->after('requirements');
            $table->integer('max_applications_per_user')->nullable()->after('form_fields');
            $table->integer('total_slots')->nullable()->after('max_applications_per_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funding_applications');
        
        Schema::table('funding_sources', function (Blueprint $table) {
            $table->dropColumn(['is_internal', 'requirements', 'form_fields', 'max_applications_per_user', 'total_slots']);
        });
    }
};
