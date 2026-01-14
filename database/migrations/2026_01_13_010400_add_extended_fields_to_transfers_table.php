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
        Schema::table('transfers', function (Blueprint $table) {
            $table->foreignId('sender_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('recipient_id')->nullable()->after('sender_id')->constrained('users')->nullOnDelete();
            $table->uuid('from_account_id')->nullable()->after('recipient_id');
            $table->uuid('to_account_id')->nullable()->after('from_account_id');
            $table->decimal('fee', 15, 2)->default(0)->after('amount');
            $table->string('reference_number')->nullable()->unique()->after('description');
            $table->json('metadata')->nullable()->after('reference_number');
            
            // Add foreign keys for accounts
            $table->foreign('from_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('to_account_id')->references('id')->on('accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropForeign(['sender_id']);
            $table->dropForeign(['recipient_id']);
            $table->dropForeign(['from_account_id']);
            $table->dropForeign(['to_account_id']);
            
            $table->dropColumn([
                'sender_id',
                'recipient_id',
                'from_account_id',
                'to_account_id',
                'fee',
                'reference_number',
                'metadata',
            ]);
        });
    }
};
