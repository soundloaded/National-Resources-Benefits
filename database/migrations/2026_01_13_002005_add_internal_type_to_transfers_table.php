<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to recreate the table to modify the check constraint
        // First, create a new table with the updated constraint
        DB::statement('CREATE TABLE "transfers_new" (
            "id" integer primary key autoincrement not null,
            "user_id" integer not null,
            "type" varchar check ("type" in (\'wire\', \'domestic\', \'interbank\', \'account-to-account\', \'internal\')) not null,
            "amount" numeric not null,
            "description" text,
            "status" varchar check ("status" in (\'pending\', \'completed\', \'failed\', \'cancelled\')) not null default \'pending\',
            "email_sent" tinyint(1) not null default \'0\',
            "created_by" integer not null,
            "created_at" datetime,
            "updated_at" datetime,
            foreign key("user_id") references "users"("id") on delete cascade,
            foreign key("created_by") references "users"("id") on delete cascade
        )');
        
        // Copy existing data
        DB::statement('INSERT INTO "transfers_new" SELECT * FROM "transfers"');
        
        // Drop old table
        DB::statement('DROP TABLE "transfers"');
        
        // Rename new table
        DB::statement('ALTER TABLE "transfers_new" RENAME TO "transfers"');
        
        // Recreate indexes
        DB::statement('CREATE INDEX "transfers_type_index" on "transfers" ("type")');
        DB::statement('CREATE INDEX "transfers_status_index" on "transfers" ("status")');
        DB::statement('CREATE INDEX "transfers_user_id_index" on "transfers" ("user_id")');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse - remove 'internal' from the constraint
        DB::statement('CREATE TABLE "transfers_new" (
            "id" integer primary key autoincrement not null,
            "user_id" integer not null,
            "type" varchar check ("type" in (\'wire\', \'domestic\', \'interbank\', \'account-to-account\')) not null,
            "amount" numeric not null,
            "description" text,
            "status" varchar check ("status" in (\'pending\', \'completed\', \'failed\', \'cancelled\')) not null default \'pending\',
            "email_sent" tinyint(1) not null default \'0\',
            "created_by" integer not null,
            "created_at" datetime,
            "updated_at" datetime,
            foreign key("user_id") references "users"("id") on delete cascade,
            foreign key("created_by") references "users"("id") on delete cascade
        )');
        
        // Copy existing data (excluding 'internal' type transfers)
        DB::statement('INSERT INTO "transfers_new" SELECT * FROM "transfers" WHERE "type" != \'internal\'');
        
        // Drop old table
        DB::statement('DROP TABLE "transfers"');
        
        // Rename new table
        DB::statement('ALTER TABLE "transfers_new" RENAME TO "transfers"');
        
        // Recreate indexes
        DB::statement('CREATE INDEX "transfers_type_index" on "transfers" ("type")');
        DB::statement('CREATE INDEX "transfers_status_index" on "transfers" ("status")');
        DB::statement('CREATE INDEX "transfers_user_id_index" on "transfers" ("user_id")');
    }
};
