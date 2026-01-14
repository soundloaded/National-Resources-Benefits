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
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // For SQLite, we need to recreate the table to modify the check constraint
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
            
            DB::statement('INSERT INTO "transfers_new" SELECT * FROM "transfers"');
            DB::statement('DROP TABLE "transfers"');
            DB::statement('ALTER TABLE "transfers_new" RENAME TO "transfers"');
            DB::statement('CREATE INDEX "transfers_type_index" on "transfers" ("type")');
            DB::statement('CREATE INDEX "transfers_status_index" on "transfers" ("status")');
            DB::statement('CREATE INDEX "transfers_user_id_index" on "transfers" ("user_id")');
        } else {
            // For MySQL/PostgreSQL, just modify the column with ENUM
            DB::statement("ALTER TABLE `transfers` MODIFY COLUMN `type` ENUM('wire', 'domestic', 'interbank', 'account-to-account', 'internal') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'sqlite') {
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
            
            DB::statement('INSERT INTO "transfers_new" SELECT * FROM "transfers" WHERE "type" != \'internal\'');
            DB::statement('DROP TABLE "transfers"');
            DB::statement('ALTER TABLE "transfers_new" RENAME TO "transfers"');
            DB::statement('CREATE INDEX "transfers_type_index" on "transfers" ("type")');
            DB::statement('CREATE INDEX "transfers_status_index" on "transfers" ("status")');
            DB::statement('CREATE INDEX "transfers_user_id_index" on "transfers" ("user_id")');
        } else {
            // For MySQL/PostgreSQL, modify column back
            DB::statement("ALTER TABLE `transfers` MODIFY COLUMN `type` ENUM('wire', 'domestic', 'interbank', 'account-to-account') NOT NULL");
        }
    }
};
