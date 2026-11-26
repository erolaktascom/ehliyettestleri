<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topic_categories', fn (Blueprint $table) => $table->boolean('is_active')->default(true));
        DB::transaction(function () {
            $aliases = ['ilk-yardim-bilgisi' => 'ilk-yardim', 'trafik-ve-cevre-bilgisi' => 'trafik-ve-cevre'];
            foreach (DB::table('question_topics')->orderBy('id')->get() as $topic) {
                $slug = $aliases[$topic->slug] ?? $topic->slug;
                $category = DB::table('topic_categories')->where('slug', $slug)->first();
                $categoryId = $category?->id ?? DB::table('topic_categories')->insertGetId([
                    'name' => $topic->name, 'slug' => $slug, 'description' => $topic->description,
                    'icon' => $topic->icon, 'order' => $topic->order, 'is_active' => $topic->is_active,
                    'created_at' => $topic->created_at, 'updated_at' => $topic->updated_at,
                ]);
                if ($category) {
                    DB::table('topic_categories')->where('id', $categoryId)->update(['is_active' => $topic->is_active]);
                }
                DB::table('questions')->whereNull('topic_category_id')->where('question_topic_id', $topic->id)
                    ->update(['topic_category_id' => $categoryId]);
            }
            // A question assigned to a topic test belongs to that test's category.
            foreach (DB::table('topic_tests')->get(['id', 'topic_category_id']) as $test) {
                DB::table('questions')->where('topic_test_id', $test->id)
                    ->update(['topic_category_id' => $test->topic_category_id]);
            }
        });
        // Legacy tables/keys remain for compatibility; active screens use topic_categories.
    }

    public function down(): void
    {
        Schema::table('topic_categories', fn (Blueprint $table) => $table->dropColumn('is_active'));
    }
};
