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
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('wallet_type_id')->nullable()->after('user_id')->constrained('wallet_types')->nullOnDelete();
            // We can drop account_type later or now. Keeping it for a moment might save existing data until we migrate it.
            // But since this is likely dev, let's just make it nullable or ignore it for now.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['wallet_type_id']);
            $table->dropColumn('wallet_type_id');
        });
    }
};
