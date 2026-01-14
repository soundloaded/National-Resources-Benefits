<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('funding_sources', function (Blueprint $table) {
            if (!Schema::hasColumn('funding_sources', 'funding_category_id')) {
                $table->unsignedBigInteger('funding_category_id')->nullable()->after('category');
            }
        });

        // Backfill: map existing string `category` (slug or name) to funding_categories.id
        $categories = DB::table('funding_categories')->select('id', 'slug', 'name')->get();
        $map = [];
        foreach ($categories as $cat) {
            if ($cat->slug) { $map[strtolower($cat->slug)] = $cat->id; }
            if ($cat->name) { $map[strtolower($cat->name)] = $cat->id; }
        }

        $sources = DB::table('funding_sources')->select('id', 'category')->get();
        foreach ($sources as $src) {
            $key = strtolower((string) $src->category);
            $id = $map[$key] ?? null;
            if ($id) {
                DB::table('funding_sources')->where('id', $src->id)->update([
                    'funding_category_id' => $id,
                ]);
            }
        }

        Schema::table('funding_sources', function (Blueprint $table) {
            $table->foreign('funding_category_id')
                ->references('id')->on('funding_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('funding_sources', function (Blueprint $table) {
            try {
                $table->dropForeign(['funding_category_id']);
            } catch (\Throwable $e) {}
            $table->dropColumn('funding_category_id');
        });
    }
};
