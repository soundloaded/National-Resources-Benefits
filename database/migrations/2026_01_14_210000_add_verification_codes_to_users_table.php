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
            // Email verification code (alternative to link)
            $table->string('email_verification_code', 6)->nullable()->after('email_verified_at');
            $table->timestamp('email_verification_code_expires_at')->nullable()->after('email_verification_code');
            
            // Login OTP
            $table->string('login_otp', 6)->nullable()->after('email_verification_code_expires_at');
            $table->timestamp('login_otp_expires_at')->nullable()->after('login_otp');
            $table->boolean('login_otp_verified')->default(false)->after('login_otp_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_verification_code',
                'email_verification_code_expires_at',
                'login_otp',
                'login_otp_expires_at',
                'login_otp_verified',
            ]);
        });
    }
};
