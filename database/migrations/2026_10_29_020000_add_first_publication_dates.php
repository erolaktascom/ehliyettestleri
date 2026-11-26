<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = ['questions', 'exams', 'topic_tests', 'blog_posts', 'lectures'];

    public function up(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->timestamp('first_published_at')->nullable());
        }
        DB::table('topic_tests')->update(['duration_minutes' => null]);
        // Only blogs already have a recorded publication date. Do not invent dates for legacy content.
        DB::table('blog_posts')->where('is_published', true)->whereNotNull('published_at')
            ->where('published_at', '<=', now())->update(['first_published_at' => DB::raw('published_at')]);
    }

    public function down(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('first_published_at'));
        }
    }
};
