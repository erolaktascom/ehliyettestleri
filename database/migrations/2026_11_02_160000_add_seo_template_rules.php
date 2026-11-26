<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    private array $tables = ['exams', 'topic_tests', 'blog_posts', 'lectures', 'pages', 'topic_categories', 'lecture_categories', 'blog_categories', 'traffic_sign_categories', 'seo_route_settings'];
    public function up(): void
    {
        Schema::table('seo_settings', fn (Blueprint $table) => $table->json('content_rules')->nullable());
        foreach ($this->tables as $name) {
            // User-approved migration: retain noindex and all content/audit timestamps.
            DB::table($name)->where('seo_index', 'index')->update(['seo_index' => 'inherit']);
        }
    }
    public function down(): void
    {
        foreach ($this->tables as $name) {
            DB::table($name)->where('seo_index', 'inherit')->update(['seo_index' => 'index']);
        }
        Schema::table('seo_settings', fn (Blueprint $table) => $table->dropColumn('content_rules'));
    }
};
