<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('log_categories', 'slug')) {
            Schema::table('log_categories', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('name')->index();
            });
        }

        // Backfill slugs for existing categories
        $categories = DB::table('log_categories')->get();
        foreach ($categories as $cat) {
            $baseSlug = Str::slug($cat->name) ?: ('category-' . $cat->id);
            $slug = $baseSlug;
            $counter = 1;

            while (DB::table('log_categories')->where('slug', $slug)->where('id', '!=', $cat->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            DB::table('log_categories')->where('id', $cat->id)->update(['slug' => $slug]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('log_categories', 'slug')) {
            Schema::table('log_categories', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }
};
